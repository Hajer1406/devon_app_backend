<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function forgot(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        // Do not reveal existence
        if (!$user) {
            return response()->json(['message' => 'If the email exists, a code was sent.']);
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_codes')
            ->where('email', $user->email)
            ->whereNull('used_at')
            ->delete();

        DB::table('password_reset_codes')->insert([
            'email' => $user->email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'used_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Mail::to($user->email)->send(new PasswordResetCodeMail($code));

        return response()->json(['message' => 'If the email exists, a code was sent.']);
    }


    public function reset(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email'],
            'code' => ['required','string','size:6'],
            'password' => ['required','string','min:8','confirmed'],
        ]);

        $user = User::where('email', $data['email'])->first();

        // same response for safety
        if (!$user) {
            return response()->json(['message' => 'Invalid code or email.'], 422);
        }

        $row = DB::table('password_reset_codes')
            ->where('email', $user->email)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();

        if (!$row || now()->greaterThan($row->expires_at)) {
            return response()->json(['message' => 'Code expired or not found.'], 422);
        }

        if ($row->attempts >= 5) {
            return response()->json(['message' => 'Too many attempts. Request a new code.'], 429);
        }

        DB::table('password_reset_codes')->where('id', $row->id)->update([
            'attempts' => $row->attempts + 1,
            'updated_at' => now(),
        ]);

        if (!Hash::check($data['code'], $row->code_hash)) {
            return response()->json(['message' => 'Invalid code.'], 422);
        }

        DB::table('password_reset_codes')->where('id', $row->id)->update([
            'used_at' => now(),
            'updated_at' => now(),
        ]);

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        // Optional: revoke all tokens after reset
        // $user->tokens()->delete();

        return response()->json(['message' => 'Password reset successful.']);
    }


    
   /*public function reset(Request $request)
{
    // 1️⃣ Valider la requête
    $data = $request->validate([
        'email' => ['required','email'],
        'token' => ['required','string'],
        'password' => ['required','string','min:8','confirmed'], // 'confirmed' vérifie password_confirmation
    ]);

    try {
        // 2️⃣ Appeler la fonction de reset de Laravel
        $status = Password::reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $request->password_confirmation, // ⚠️ utiliser $request directement
                'token' => $data['token'],
            ],
            function ($user) use ($data) {
                // Mettre à jour le mot de passe et le remember_token
                $user->forceFill([
                    'password' => Hash::make($data['password']),
                    'remember_token' => Str::random(60),
                ])->save();

                // Optionnel : révoquer tous les tokens existants après reset
                // $user->tokens()->delete();
            }
        );

        // 3️⃣ Si le reset échoue, retourner le statut exact
        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'status' => $status, // permet de voir si c'est 'passwords.token' ou 'passwords.user'
            ], 422);
        }

        // 4️⃣ Succès
        return response()->json([
            'message' => 'Password reset successful.',
        ]);

    } catch (\Exception $e) {
        // 5️⃣ Capture toute exception pour éviter le 500
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
}*/


    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required','string'],
            'password' => ['required','string','min:8','confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        // Optional: revoke current token (force re-login)
        // $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Password updated.',
        ]);
    }
}
