<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class VerificationController extends Controller
{
    // POST /api/email/send-code (auth:sanctum)
    public function sendCode(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        // 6-digit code
        $code = (string) random_int(100000, 999999);

        DB::table('email_verification_codes')
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        DB::table('email_verification_codes')->insert([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'used_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Mail::to($user->email)->send(new EmailVerificationCodeMail($code));

        return response()->json(['message' => 'Verification code sent.']);
    }

    // POST /api/email/verify-code (auth:sanctum)
    public function verifyCode(Request $request)
    {
        $data = $request->validate([
            'code' => ['required','string','size:6'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $row = DB::table('email_verification_codes')
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();

        if (!$row || now()->greaterThan($row->expires_at)) {
            return response()->json(['message' => 'Code expired or not found.'], 422);
        }

        if ($row->attempts >= 5) {
            return response()->json(['message' => 'Too many attempts. Request a new code.'], 429);
        }

        // increment attempts
        DB::table('email_verification_codes')->where('id', $row->id)->update([
            'attempts' => $row->attempts + 1,
            'updated_at' => now(),
        ]);

        if (!Hash::check($data['code'], $row->code_hash)) {
            return response()->json(['message' => 'Invalid code.'], 422);
        }

        // mark used
        DB::table('email_verification_codes')->where('id', $row->id)->update([
            'used_at' => now(),
            'updated_at' => now(),
        ]);

        // verify user email
        $user->forceFill(['email_verified_at' => now()])->save();

        return response()->json(['message' => 'Email verified.']);
    }
}