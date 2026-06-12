<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserOtp;
use App\Mail\OTPMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Connexion par session Sanctum SPA.
     * Flux :
     *  1. Vérifie credentials + is_active
     *  2. Si déjà un OTP vérifié → crée la session directement
     *  3. Sinon → envoie OTP par email, retourne requires_otp: true
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password'  => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = User::where('email', $request->input('email'))->first();

        // Vérifie d'abord si l'utilisateur existe
        if (!$user) {
            return response()->json(['message' => 'Aucun compte associé à cet e-mail. Veuillez contacter l\'administrateur.'], 404);
        }

        // Vérifie ensuite le mot de passe
        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'Identifiants invalides. Veuillez réessayer.'], 401);
        }

        // Vérifie si le compte est actif
        if (!$user->is_active) {
            return response()->json(['message' => 'Votre compte est désactivé. Veuillez contacter l\'administrateur.'], 403);
        }

        // Vérifie si l'utilisateur a déjà validé un OTP (première connexion déjà faite)
        $hasVerifiedOtp = UserOtp::where('user_id', $user->id)
            ->where('verified', true)
            ->exists();

        if ($hasVerifiedOtp) {
            // ── Authentification par SESSION ──────────────────────────────
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return response()->json([
                'user'         => $this->userWithAvatarUrl($user),
                'requires_otp' => false,
            ]);
        }

        // ── Première connexion : envoie OTP ───────────────────────────────
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        UserOtp::create([
            'user_id'    => $user->id,
            'code'       => $otpCode,
            'expires_at' => Carbon::now()->addMinutes(15),
            'verified'   => false,
        ]);

        try {
            Mail::to($user->email)->send(new OTPMail($otpCode));
        } catch (\Exception $e) {
            // En dev on continue sans email
        }

        return response()->json([
            'user'         => ['email' => $user->email, 'name' => $user->name],
            'requires_otp' => true,
        ]);
    }

    /**
     * Vérification du code OTP → crée la session.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->input('email'))->firstOrFail();

        $otp = UserOtp::where('user_id', $user->id)
            ->where('code', $request->input('code'))
            ->where('verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'Code invalide ou expiré'], 422);
        }

        $otp->update(['verified' => true]);

        // ── Crée la session après validation OTP ──────────────────────────
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return response()->json([
            'user' => $this->userWithAvatarUrl($user),
        ]);
    }

    /**
     * Renvoi d'un OTP.
     */
    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        UserOtp::create([
            'user_id'    => $user->id,
            'code'       => $otpCode,
            'expires_at' => Carbon::now()->addMinutes(15),
            'verified'   => false,
        ]);

        try {
            Mail::to($user->email)->send(new OTPMail($otpCode));
        } catch (\Exception $e) {
            //
        }

        return response()->json(['message' => 'Un nouveau code a été envoyé']);
    }

    /**
     * Déconnexion — invalide la session courante.
     */
    public function logout(Request $request)
    {
        try {
            Auth::logout();
            
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json(['message' => 'Déconnecté avec succès']);
        } catch (\Exception $e) {
            // En cas d'erreur, on retourne quand même un succès
            return response()->json(['message' => 'Déconnecté avec succès']);
        }
    }

    /**
     * Retourne l'utilisateur authentifié (session courante).
     * Utilisé par le frontend au montage pour restaurer l'état.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        return response()->json($this->userWithAvatarUrl($user->load('agent')));
    }

    /**
     * Helper : retourne un tableau user avec avatar_url calculée.
     */
    private function userWithAvatarUrl(User $user): array
    {
        $arr = $user->toArray();
        $arr['avatar_url'] = $user->avatar
            ? Storage::url($user->avatar)
            : null;
        return $arr;
    }
}
