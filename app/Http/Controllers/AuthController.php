<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = User::create([
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
            'verified' => false,
            'otp'      => rand(10000, 99999),
        ]);

        $otp = $user['otp'];
        try {
            Mail::to($user->email)->send(new OtpMail($otp));

            return response()->json(['message' => 'User registered successfully. Please check your email for OTP.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send email. Please try again.'], 500);
        }
    }

    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'otp' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $user = User::where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid OTP or email'], 422);
        }

        $user->update(['verified' => true]);

        return response()->json(['success' => 'Verified successfully .']);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $user = User::where('email', $request->email)
            ->first();
        if ( $user && $user->verified == 0) {
            return response()->json(['error' => 'You are not verified user. Verify by your email Otp']);
        }

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    public function logout()
    {
        try {

            JWTAuth::invalidate(JWTAuth::parseToken());

            return response()->json(['message' => 'Logout successful'], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => 'Unable to logout'], 500);
        }
    }
}
