<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'User registered successfully!',
            'user'    => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        try {
            // Validate the input
            $credentials = $request->validate([
                'email'    => 'required|string|email',
                'password' => 'required|string',
            ]);

            // Attempt to log the user in
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Invalid credentials',
                ], 401);
            }

            // Retrieve the authenticated user
            $user = Auth::user();
            // Check if the user is inactive
            // Generate a token for the user
            $token = $user->createToken('auth_token')->plainTextToken;
            // Return a successful response
            return response()->json([
                'status'  => 'success',
                'message' => 'Login successful',
                'user'    => $user,
                'token'   => $token,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred during login',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logout successful!']);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }
}
