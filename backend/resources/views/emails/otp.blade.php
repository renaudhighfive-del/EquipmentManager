<x-mail::message>
# Vérification de votre compte

Bonjour,

Utilisez le code ci-dessous pour vous connecter à votre espace **EquipTrack**. Ce code est valable pendant 15 minutes.

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
