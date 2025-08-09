<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthenticationController extends Controller
{
    public function existUser(Request $request)
    {
        $data = $request->validate([
            'tg_id' => 'required|string',
        ]);

        $user = User::where('tg_id', $data['tg_id'])->first();

        return response()->json([
            'success' => true,
            'exists'  => (bool) $user,
            'data'    => $user
        ]);
    }


    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'tg_id'    => 'required|string|unique:users,tg_id',
            'phone'    => 'nullable|string|unique:users,phone',
            'username' => 'nullable|string|max:255',
            'address'  => 'nullable|string',
        ]);

        $user = User::create([
            'tg_id'    => $data['tg_id'],
            'phone'    => $data['phone'] ?? null,
            'username' => $data['username'] ?? null,
            'address'  => $data['address'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data'    => $user
        ], 201);
    }

    /**
     * Admin login
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'identifier' => 'required|string', // phone or username
                'password'   => 'required|string',
            ]);

            $user = User::where('phone', $credentials['identifier'])
                        ->orWhere('username', $credentials['identifier'])
                        ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if ($user->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not active'
                ], 403);
            }

            if (!Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect password'
                ], 401);
            }

            $token = $user->createToken('adminToken')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get authenticated admin info
     */
    public function userInfo(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'message' => 'Authenticated admin data fetched',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            Log::error('User Info Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user data'
            ], 500);
        }
    }

    /**
     * Logout admin
     */
    public function logOut(Request $request)
    {
        try {
            $user = $request->user();

            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Token revoked successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Logout Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during logout',
            ], 500);
        }
    }
}
