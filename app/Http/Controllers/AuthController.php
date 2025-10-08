<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            return $this->successResponse([
                'user' => $userData,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ], 'User registered successfully', Response::HTTP_CREATED);

        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return $this->errorResponse('Registration failed. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Login user and create token
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();

            if (!Auth::attempt($credentials)) {
                return $this->errorResponse('Invalid login credentials', Response::HTTP_UNAUTHORIZED);
            }

            $user = User::where('email', $credentials['email'])->firstOrFail();

            // Revoke all existing tokens for single device login
            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            return $this->successResponse([
                'user' => $userData,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ], 'Login successful');

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return $this->errorResponse('Login failed. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Logout user (revoke the token)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return $this->successResponse([], 'Logged out successfully');

        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return $this->errorResponse('Logout failed. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            return $this->successResponse(['user' => $userData], 'Profile retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Profile fetch error: ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch profile', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Validate token
     */
    public function validateToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];

            return $this->successResponse(['user' => $userData], 'Token is valid');

        } catch (\Exception $e) {
            Log::error('Token validation error: ' . $e->getMessage());
            return $this->errorResponse('Token validation failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
