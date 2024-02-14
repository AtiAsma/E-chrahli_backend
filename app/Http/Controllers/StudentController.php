<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
  
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'username' => 'required|unique:students',
            'email' => 'required|email|unique:students',
            'password' => 'required',
            'level' => 'required|in:Primary,Middle,Secondary',
        ]);

        $student = new Student();
        $student->first_name = $request->input('first_name');
        $student->last_name = $request->input('last_name');
        $student->username = $request->input('username');
        $student->email = $request->input('email');
        $student->password = bcrypt($request->input('password'));
        $student->level = $request->input('level');
        $student->save();

        // Generate a JWT for the student
        $token = JWTAuth::fromUser($student);

        return response()->json(['id' => $student->id , 'token' => $token, 'fname' => $student->first_name], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::guard('students')->attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $student = Auth::guard('students')->user();
        $token = JWTAuth::fromUser($student);

        return response()->json(['id' => $student->id, 'token' => $token, 'fname' => $student->first_name]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $student = Student::findOrFail($id);

        return response()->json(['student' => $student], 200);
    }

    

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $student_id)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'username' => 'required|unique:students,username,',
            'email' => 'required|email|unique:students,email,',
            'level' => 'required|in:Primary,Middle,Secondary',
        ]);

        $student = Student::findOrFail($student_id);
        $student->first_name = $request->input('first_name');
        $student->last_name = $request->input('last_name');
        $student->username = $request->input('username');
        $student->email = $request->input('email');
        $student->level = $request->input('level');
        $student->save();

        return response()->json(['message' => 'Student updated successfully'], 200);
    }

    public function changePasswordWithoutReset(Request $request, $id)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8',
        ]);

        $student = Student::find($id);

        // Verify the current password
        if (!Hash::check($request->input('current_password'), $student->password)) {
            return response()->json(['error' => 'Invalid current password'], 400);
        }

        // Update the password
        $student->password = bcrypt($request->input('new_password'));
        $student->save();

        return response()->json(['message' => 'Password changed successfully'], 200);
    }

    
    public function logout()
    {
        Auth::guard('students')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }
}
