<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        //set validation
        $validator = Validator::make($request->all(), [
            'email'     => 'required|string|email',
            'password'  => 'required'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        //get credentials from request
        $credentials = $request->only('email', 'password');

        //if auth failed
        if (!$token = auth()->guard('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password Anda salah'
            ], 401);
        }

        $user = auth()->guard('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Authentication failed'], 401);
        }

        $customClaims = ['roles' => $user->roles->pluck('role_name')];
        $token = JWTAuth::claims($customClaims)->fromUser($user);

        //if auth success
        return response()->json([
            'success' => true,
            'token'   => $token
        ], 200);
    }

    public function register(Request $request)
    {
        //set validation
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'umur' => 'integer|min:1',
            'status_anggota' => 'nullable|boolean',
            'role_id' => 'required|exists:user_roles,id'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        //create user
        $user = User::create([
            'nama'      => $request->nama,
            'email'     => $request->email,
            'password'  => bcrypt($request->password),
            'umur' => $request->umur,
            'status_anggota' => $request->status_anggota,

        ]);

        //return response JSON user is created
        if ($user) {
            $roleId = $request->role_id ?? 1;
            $user->roles()->attach($roleId);
            $customClaims = ['roles' => $user->roles->pluck('role_name')];
            $token = JWTAuth::claims($customClaims)->fromUser($user);

            return response()->json(['success' => true, 'user' => $user, 'token' => $token]);
        }

        //return JSON process insert failed 
        return response()->json([
            'success' => false,
        ], 409);
    }

    public function logout(Request $request)
    {
        try {
            $removeToken = JWTAuth::invalidate(JWTAuth::getToken());
            if ($removeToken) {
                response()->json(['success' => true, 'message' => 'Logout Berhasil!',]);
            }
        } catch (TokenInvalidException $e) {
            return response()->json(['success' => false, 'message' => 'Token tidak valid',], 401);
        } catch (TokenExpiredException $e) {
            return response()->json(['success' => false, 'message' => 'Token telah kadaluarsa',], 401);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan pada server',], 500);
        }
    }
}
