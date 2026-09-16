<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

#[Group('Auth')]
class AuthController extends Controller
{
    #[Endpoint(title: 'Register a new user', description: 'Create a new user account and return an auth token.')]
    public function register(Request $request)
    {
        $validate = $request->validate([
            'name' => 'required|string|max:110',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create($validate);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token,
        ], 201);
    }

    #[Endpoint(title: 'Log in a user', description: 'Authenticate user credentials and return a personal token.')]
    public function login(Request $request)
    {
        $validate = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validate['email'])->first();

        if (!$user || !Auth::attempt($validate)) {
            throw ValidationException::withMessages([
                'email' => ['Wrong Informations'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'token' => $token,
        ]);
    }

    #[Endpoint(title: 'Send password reset link', description: 'Email a password reset link to the user.')]
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'We have emailed your password reset link.']);
        }

        return response()->json(['message' => __($status)], 400);
    }

    #[Endpoint(title: 'Reset the user password', description: 'Reset a forgotten password using the email token.')]
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(str()->random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successfully.']);
        }

        return response()->json(['message' => __($status)], 400);
    }

    #[Endpoint(title: 'Log out the authenticated user', description: 'Revoke the current user tokens and end the session.')]
    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();

            return response()->json(['message' => 'Logged out Successfully.'], 201);
        } catch (\Exception $error) {
            return response()->json(['message' => $error]);
        }
    }

    #[Endpoint(title: 'Get authenticated user profile', description: 'Return the current authenticated user record.')]
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized!'], 403);
        }

        return response()->json(['data' => $user], 200);
    }

    #[Endpoint(title: 'Update a user role', description: 'Allow only admins to change a user role.')]
    public function updateUserRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|in:admin,developer,editor',
        ]);

        $user->update([
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'User role updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    #[Endpoint(title: 'Get all users', description: 'Return all users. Accessible only by admins.')]
    public function users()
    {
        $users = User::all();

        return response()->json(['data' => $users], 200);
    }

    #[Endpoint(title: 'Get single user', description: 'Return one user by ID. Accessible only by admins.')]
    public function user(User $user)
    {
        return response()->json(['data' => $user], 200);
    }
}
