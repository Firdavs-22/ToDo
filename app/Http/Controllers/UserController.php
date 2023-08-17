<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed',
            'language' => 'integer',
            'theme' => 'integer'
        ]);

        $fileName = 'user.png';
//        if ($request->hasFile('photo')) {
//            $fileName = time() . '.' . $request->file('photo')->getClientOriginalExtension();
//            $request->file('photo')->storeAs('images', $fileName, 'public');
//            return $fileName;
//        }

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'language' => $request->input('language', 1),
            'theme' => $request->input('theme', 1),
            'photo' => $fileName,
            'status' => 1
        ]);

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [

            'token' => $token
        ];

        return response($response, 201);
    }

    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'Logged out'
        ];
    }

    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $fields['email'])
            ->where('status', 1)
            ->first();
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response([
                'message' => 'Bad creds'
            ], 401);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $validatedData = $request->validate([
            'name' => 'string',
            'password' => 'string|confirmed',
            'language' => 'integer',
            'theme' => 'integer',
        ]);

        $user->name = $validatedData['name'] ?? $user->name;
        $user->password = isset($validatedData['password']) ? bcrypt($validatedData['password']) : $user->password;
        $user->language = $validatedData['language'] ?? $user->language;
        $user->theme = $validatedData['theme'] ?? $user->theme;
        $user->save();

        return response()->json([
            'message' => 'Update successful',
            'user' => $user
        ]);
    }

    public function destroy()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $user->status = 0;
        $user->save();

        auth()->user()->tokens()->delete();
        return response()->json(['message' => 'User deleted']);
    }

    public function show()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($user        );
    }
}
