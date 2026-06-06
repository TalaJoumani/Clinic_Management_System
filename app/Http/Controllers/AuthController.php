<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\AuthServices;
use App\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    protected AuthServices $authService;
    public function __construct(AuthServices $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $registerRequest)
    {
        $user = $this->authService->register($registerRequest->validated());
        return response()->json([
            'message' => 'Registration successful. Please check your email for the OTP to verify your account.',
            'user' => $user,
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|numeric',
        ]);

        $result = $this->authService->verifyOtp($request->email, $request->otp_code);
        if ($result instanceof \Illuminate\Http\JsonResponse) {
            return $result;
        }

        return response()->json([
            'message' => 'OTP verified successfully. Your account is now active.',
            'user' => $result,
        ], 200);
    }
    

    public function login(LoginRequest $loginRequest)
     {
        $result = $this->authService->login($loginRequest->validated());
        if ($result instanceof \Illuminate\Http\JsonResponse) {
            return $result;
        }
        return response()->json($result, 200);
    }
  
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $result = $this->authService->sendPasswordResetOtp($request->email);
        return $result;
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'new_password' => 'required|string|min:6',
        ]);

        $result = $this->authService->resetPassword($request->email, $request->new_password);
        return $result;
    }

    public function verifyResetOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|numeric',
        ]);

        $result = $this->authService->verifyResetOtp($request->email, $request->otp_code);
        return $result;
    }
}
