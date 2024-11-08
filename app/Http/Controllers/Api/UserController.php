<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        $cacheKey = "users_page_{$page}_per_page_{$perPage}";

        $users = Cache::remember($cacheKey, 60, function () use ($perPage, $page) {
            return User::paginate($perPage, ['*'], 'page', $page);
        });

        return response()->json(['users' => $users]);
    }

    public function show($id)
    {
        $user = Cache::remember("user_{$id}", 60, function () use ($id) {
            return User::find($id);
        });
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json(['user' => $user]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:6|confirmed',
            'umur' => 'sometimes|integer|min:1',
            'status_anggota' => 'nullable|boolean',
            'role_id' => 'sometimes|exists:user_roles,id'
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $authenticatedUser = Auth::user();
        if ($authenticatedUser->id != $id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->update($request->all());

        Cache::put("user_{$id}", $user, 60);

        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    public function destroy($id)
    {
        $authenticatedUser = Auth::user();
        if (!$authenticatedUser->roles->contains('role_name', 'Admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->delete();

        Cache::forget("user_{$id}");

        return response()->json(['message' => 'User deleted successfully']);
    }
}
