<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validate = $request->validate([
            'name' => 'required|string|max:110',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create($validate);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $validate = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validate['email'])->first();

        $token = $user->createToken('auth_token')->plainTextToken;

        if (!$user) {
            return response()->json(['User Not Found!'], 404);
        }

        if (!Auth::attempt($validate)) {
            throw ValidationException::withMessages([
                'message' => 'Wrong Informations',
            ]);
        }

        return response()->json(['message' => 'Logged in successfully', 'token' => $token]);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();

            return response()->json(['message' => 'Logged out Successfully.'], 201);
        } catch (\Exception $error) {
            return response()->json(['message' => $error]);
        }
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized!'], 403);
        }

        return response()->json(['data' => $user], 200);
    }
}
