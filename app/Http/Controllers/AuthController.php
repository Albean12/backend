<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // Validate token and differentiate between roles
    public function validateToken(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->role === 'admin' || $user->role === 'tenant') {
            return response()->json(['role' => $user->role], 200);
        }

        $guestUser = DB::table('guest_user')->where('user_email', $user->email)->first();

        if ($guestUser) {
            return response()->json(['role' => 'guest_user'], 200);
        }

        return response()->json(['error' => 'Invalid user type'], 403);
    }

    public function refreshToken(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->role === 'admin' || $user->role === 'tenant') {
            $user->tokens()->delete();
            $token = $user->createToken('API Token')->plainTextToken;

            return response()->json(['access_token' => $token]);
        }

        return response()->json(['error' => 'Invalid user type'], 403);
    }

    // Login for admin/tenant
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:4',
        ]);

        $credentials = $request->only('email', 'password');
        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('API Token', ['admin-tenant'])->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'role' => $user->role,
            'user_id' => $user->id,
        ]);
    }

    // Register for guests
    public function registerGuest(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:guest_user,user_email',
            'password' => 'required|string|min:4',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:Male,Female,Other',
        ]);

        try {
            $guestId = DB::table('guest_user')->insertGetId([
                'name' => $request->name,
                'user_email' => $request->email,
                'password' => bcrypt($request->password),
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'visit_count' => 0,
            ]);

            $user = User::firstOrCreate(
                ['email' => $request->email],
                ['name' => $request->name, 'password' => bcrypt($request->password)]
            );

            $token = $user->createToken('API Token', ['guest'])->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Account created and authenticated successfully.',
                'access_token' => $token,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Register Guest Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create account.'], 500);
        }
    }

    // Google login for guests
    public function googleLogin(Request $request)
    {
        $request->validate(['token' => 'required']);

        try {
            $firebase = (new Factory)->withServiceAccount(storage_path('app/firebase-service-account.json'))->createAuth();
            $verifiedToken = $firebase->verifyIdToken($request->token);

            $email = $verifiedToken->claims()->get('email');
            $name = $verifiedToken->claims()->get('name');

            if (!$email) {
                throw new \Exception('No email found in token claims.');
            }

            $guest = DB::table('guest_user')->updateOrInsert(
                ['user_email' => $email],
                ['name' => $name, 'password' => bcrypt('default_password'), 'visit_count' => DB::raw('visit_count + 1')]
            );

            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => bcrypt('default_password')]
            );

            $token = $user->createToken('GoogleLogin')->plainTextToken;

            return response()->json(['access_token' => $token, 'email' => $email], 200);
        } catch (\Throwable $e) {
            Log::error('Google Login Error: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid Google token'], 400);
        }
    }

    // Guest login
    public function loginGuest(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:4',
        ]);

        $user = DB::table('guest_user')->where('user_email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $userModel = User::firstOrCreate(
            ['email' => $request->email],
            ['name' => $user->name, 'password' => bcrypt($request->password)]
        );

        $token = $userModel->createToken('API Token', ['guest'])->plainTextToken;

        return response()->json(['access_token' => $token, 'user_email' => $user->user_email], 200);
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $user->tokens()->delete();
        }
        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
