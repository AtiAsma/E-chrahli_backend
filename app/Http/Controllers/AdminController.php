<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::guard('admins')->attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $admin = Auth::guard('admins')->user();

        $token = JWTAuth::fromUser($admin);

        return response()->json(['token' => $token]);
    }

    public function addAdmin(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'username' => 'required|unique:admins',
            'email' => 'required|email|unique:admins',
            'password' => 'required',
        ]);

        $admin = new Admin();
        $admin->first_name = $request->input('first_name');
        $admin->last_name = $request->input('last_name');
        $admin->username = $request->input('username');
        $admin->email = $request->input('email');
        $admin->password = bcrypt($request->input('password'));
        $admin->save();

        return response()->json(['message' => 'Admin added successfully'], 201);
    }

    public function deleteAdmin($id)
    {
        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }

        $admin->delete();

        return response()->json(['message' => 'Admin deleted successfully']);
    }

    public function logout()
    {
        Auth::guard('admins')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

}
