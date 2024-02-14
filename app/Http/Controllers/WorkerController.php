<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class WorkerController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::guard('workers')->attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $worker = Auth::guard('workers')->user();
        $token = JWTAuth::fromUser($worker);

        return response()->json(['id' => $worker->id, 'token' => $token, 'fname' => $worker->first_name]);
    }

    public function addWorker(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'username' => 'required|unique:workers',
            'email' => 'required|email|unique:workers',
            'password' => 'required',
            'domain' => 'required|in:Maths,Physics,Sciences'
        ]);

        $worker = new Worker();
        $worker->first_name = $request->input('first_name');
        $worker->last_name = $request->input('last_name');
        $worker->username = $request->input('username');
        $worker->email = $request->input('email');
        $worker->password = bcrypt($request->input('password'));
        $worker->domain = $request->input('domain');
        $worker->rating = 2.5;
        $worker->is_available = 0;
        $worker->save();

        return response()->json(['message' => 'Worker added successfully'], 201);
    }

    public function deleteWorker($id)
    {
        $worker = Worker::find($id);

        if (!$worker) {
            return response()->json(['error' => 'Worker not found'], 404);
        }

        $worker->delete();

        return response()->json(['message' => 'Worker deleted successfully']);
    }

    public function getAvailabilityStatus($id)
    {
        $worker = Worker::find($id);

        return response()->json(['is_available' => $worker->is_available]);
    }

    public function toggleAvailabilityStatus($id)
    {
        $worker = Worker::find($id);
        $worker->is_available = !$worker->is_available;
        $worker->save();

        return response()->json(['message' => 'availability status changed successfully']);
    }

    public function logout()
    {
        Auth::guard('workers')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

}
