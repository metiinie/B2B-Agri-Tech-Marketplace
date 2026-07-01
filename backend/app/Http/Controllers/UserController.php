<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users (admin only).
     */
    public function index(): Response
    {
        $users = User::with(['capabilities', 'listings', 'orders'])->paginate(15);
        return response($users);
    }

    /**
     * Display the authenticated user's profile.
     */
    public function show(User $user): Response
    {
        $user->load(['capabilities', 'listings', 'orders', 'orderFulfillments']);
        return response($user);
    }

    /**
     * Update user profile.
     */
    public function update(Request $request, User $user): Response
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);
        return response($user);
    }

    /**
     * Get current authenticated user.
     */
    public function me(): Response
    {
        $user = auth()->user();
        if (!$user) {
            return response(['error' => 'Unauthenticated'], 401);
        }
        $user->load(['capabilities', 'listings', 'orders']);
        return response($user);
    }

    /**
     * Soft delete a user (account suspension).
     */
    public function destroy(User $user): Response
    {
        $user->delete();
        return response(['message' => 'User account suspended'], 200);
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(string $id): Response
    {
        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return response(['error' => 'User not found'], 404);
        }
        $user->restore();
        return response(['message' => 'User account restored'], 200);
    }

    /**
     * Get user's account status.
     */
    public function status(User $user): Response
    {
        return response([
            'id' => $user->id,
            'phone' => $user->phone,
            'account_status' => $user->account_status,
            'is_admin' => $user->is_admin,
            'phone_verified_at' => $user->phone_verified_at,
        ]);
    }
}
