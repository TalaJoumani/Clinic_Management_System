<?php 
namespace App\Services; 

use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
   
class AuthServices 
{ 
   public function register(array $data) 
   { 
       $user = User::create([ 
           'first_name' => $data['first_name'], 
           'last_name' => $data['last_name'], 
           'email' => $data['email'], 
           'password' => Hash::make($data['password']),
           'gender' => $data['gender'],
           'phone' => $data['phone'],   
            'birth' => $data['birth'], 
            'is_verified' => false,
            'role'=> 'patient', 
       ]);
       $user->patient()->create([
           'blood_type' => $data['blood_type'] ?? null,
           'previous_illnesses' => $data['previous_illnesses'] ?? null,
       ]);
       $otp= rand(100000, 999999);
       $user->otp()->create([
           'otp_code' => $otp,
           'expires_at' => now()->addMinutes(10),
       ]);
       Mail::to($user->email)->send(new OtpMail($otp, 'Email Verification OTP'));
       return $user;
   }

   public function verifyOtp($email,$code)
   {
       $user = User::where('email', $email)->first();

       if (!$user) {
           return response()->json([
            'message' => 'User not found',
            ], 404);
       }
         $otpRecord = $user->otp()->where('otp_code', $code)->where('expires_at', '>', now())->first();
         if (!$otpRecord) {
            return response()->json([
                'message' => 'Invalid or expired OTP',
            ], 400);
         }
         $user->update(['is_verified' => true]);
         $user->otp()->delete();
         return $user;

     
   }

   public function login(array $credentials)
   {
       $user=User::where('email',$credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
                ], 401);
        }

        if(!$user->is_verified){
            return response()->json([
                'message' => 'Account not verified. Please check your email for the OTP to verify your account.'
                ], 403);
        }
        
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
   }
  
}