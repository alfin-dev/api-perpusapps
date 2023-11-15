<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validasi = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        $errors = $validasi->errors();

        if ($validasi->fails()) {
            return response()->json([
                'status' => 409,
                'message' => [
                    'username' => $errors->first('username'),
                    'password' => $errors->first('password'),
                ],
            ]);
        }

        try {
            $credential = $request->only('username', 'password');

            if (Auth::attempt($credential)) {
                $user = User::with('roles')->find(Auth::id());
                $token = $user->createToken('auth_token')->accessToken;
                // $token = $user->createToken('auth_token')->plainTextToken;
            } else {
                return response()->json(["status" => 409, "message" => "User tidak ditemukan, username dan password tidak cocok"]);
            }
        } catch (\Exception $e) {
            return response()->internalServerError('Login gagal ' . $e->getMessage(), $e->getMessage());
        }

        // return $user->getRoleNames();

        return response()->ok(['user' => $user, 'token' => $token, 'token_type' => 'Bearer', 'expire_in' => 1200], 'Login berhasil dilakukan');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'username' => 'required|unique:users,username',
            'email' => 'required|unique:users,email',
            'password' => 'required',
            'confirm_password' => 'required',
        ]);

        $errors = $validator->errors();

        if ($validator->fails()) {
            return response()->json([
                'status' => 409,
                'message' => [
                    'name' => $errors->first('name') ?: 'kosong',
                    'username' => $errors->first('username') ?: 'kosong',
                    'email' => $errors->first('email') ?: 'kosong',
                    'password' => $errors->first('password') ?: 'kosong',
                    'confirm_password' => $errors->first('confirm_password') ?: 'kosong',
                    $errors
                ],
            ]);
        }

        if ($request->input('password') !== $request->input('confirm_password')) {
            return response()->json([
                'status' => 409,
                'message' => [
                    'confirm_password' => 'Password konfirmasi tidak sesuai',
                ],
            ]);;
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->input('name'),
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'password' => bcrypt($request->input('password')),
                'confirm_password' => $request->input('confirm_password'),
            ]);

            $user->assignRole('member');

            DB::commit();

            // $token = $user->createToken('auth_token')->accessToken;
            // $token = $user->createToken('auth_token')->plainTextToken;
        } catch (\Exception $e) {
            DB::rollback();
            return response()->internalServerError('Gagal registrasi user ' . $e->getMessage(), $e->getMessage());
        }

        return response()->ok(['user' => $user], 'Berhasil registrasi user');
    }

    public function logout()
    {
        Auth::user()->tokens()->delete();
        return response()->ok(null, 'Berhasil logout user');
    }
}
