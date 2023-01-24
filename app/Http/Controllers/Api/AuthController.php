<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Create User
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password)
        ]);

        switch ((int)$request->role) {
            case (1):
                $user->assign('admin');
                break;
            default:
                $user->assign('manager');
                break;
        }

        return response()->json([
            'status'  => true,
            'message' => 'User Created Successfully',
            'token'   => $user->createToken("API TOKEN")->plainTextToken
        ]);
    }

    /**
     * Login The User
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only(['email', 'password']))) {
            return response()->json([
                'status'  => false,
                'data'    => $request->all(),
                'message' => __('errors.login'),
            ], 401);
        }

        $user = User::where('email', $request->email)->first();

        return response()->json([
            'status' => true,
            'message' => 'User Logged In Successfully',
            'result' => [
                'user' => [
                    'name' => $user->name,
                    'token' => $user->createToken("API TOKEN")->plainTextToken]
            ],
        ], 200);

    }
}
