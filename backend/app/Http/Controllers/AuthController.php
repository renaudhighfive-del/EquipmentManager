<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserOtp;
use App\Mail\OTPMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Identifiants invalides'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Votre compte est désactivé'], 403);
        }

        // Check if user has already verified an OTP once (First connection logic)
        $hasVerifiedOtp = UserOtp::where('user_id', $user->id)
            ->where('verified', true)
            ->exists();

        if ($hasVerifiedOtp) {
            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'user' => $user,
                'token' => $token,
                'requires_otp' => false
            ]);
        }

        // Generate OTP
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        UserOtp::create([
            'user_id' => $user->id,
            'code' => $otpCode,
            'expires_at' => Carbon::now()->addMinutes(15),
            'verified' => false
        ]);

        // Send Email (in production, use a Queue)
        try {
            Mail::to($user->email)->send(new OTPMail($otpCode));
        } catch (\Exception $e) {
            // Log error but continue for dev
        }

        return response()->json([
            'user' => [
                'email' => $user->email,
                'name' => $user->name
            ],
            'requires_otp' => true
        ]);
    }

    /**
     * Verify OTP and complete login.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        $otp = UserOtp::where('user_id', $user->id)
            ->where('code', $request->code)
            ->where('verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'Code invalide ou expiré'], 422);
        }

        $otp->update(['verified' => true]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Resend OTP to user.
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Generate new OTP
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        UserOtp::create([
            'user_id' => $user->id,
            'code' => $otpCode,
            'expires_at' => Carbon::now()->addMinutes(15),
            'verified' => false
        ]);

        try {
            Mail::to($user->email)->send(new OTPMail($otpCode));
        } catch (\Exception $e) {
            // Log error
        }

        return response()->json(['message' => 'Un nouveau code a été envoyé']);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté avec succès']);
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
