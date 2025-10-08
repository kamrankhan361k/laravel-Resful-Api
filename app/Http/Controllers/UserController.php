<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Update user profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            $user->update($validated);

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'updated_at' => $user->updated_at,
            ];

            return $this->successResponse(['user' => $userData], 'Profile updated successfully');

        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());
            return $this->errorResponse('Profile update failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Change password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            // Check current password
            if (!Hash::check($validated['current_password'], $user->password)) {
                return $this->errorResponse('Current password is incorrect', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Update password
            $user->update([
                'password' => Hash::make($validated['new_password'])
            ]);

            return $this->successResponse([], 'Password changed successfully');

        } catch (\Exception $e) {
            Log::error('Password change error: ' . $e->getMessage());
            return $this->errorResponse('Password change failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
