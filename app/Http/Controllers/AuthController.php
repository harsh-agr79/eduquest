<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\HasApiTokens;
// use Google_Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\ForgotPassword;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
     public function register( Request $request ) {
        $validator = Validator::make( $request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers',
            'password' => [ 'required', 'string', 'min:8', 'regex:/[ A-Za-z ]/', 'regex:/[ 0-9 ]/', 'regex:/[ @$!%*#?& ]/' ],
        ] );

        if ( $validator->fails() ) {
            return response()->json( $validator->errors(), 422 );
        }

        $customer = Customer::create( [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make( $request->password ),
            'email_verified_at' => '2025-08-30 00:00:00', // For testing purposes, remove in production
        ] );


        return response()->json( [
            'message' => 'Registration successful.'
        ], 201 );
    }

    public function login( Request $request ) {
        $request->validate( [
            'email' => 'required|email',
            'password' => 'required',
        ] );

        // Apply rate limiting for login attempts
        // if ( RateLimiter::tooManyAttempts( 'login:'.$request->ip(), 5 ) ) {
        //     return response()->json( [ 'error' => 'Too many login attempts. Please try again later.' ], 429 );
        // }

        $credentials = $request->only( 'email', 'password' );

        if ( Auth::guard( 'customer' )->attempt( $credentials ) ) {
            $user = Auth::guard( 'customer' )->user();

            // Check if email is verified
            if ( !$user->hasVerifiedEmail() ) {
                return response()->json( [ 'error' => 'Email not verified.' ], 403 );
            }

            // Generate token
            $token = $user->createToken( 'auth_token' )->plainTextToken;

            // Clear failed attempts on successful login
            RateLimiter::clear( 'login:'.$request->ip() );

            return response()->json( [ 'token' => $token, 'user' => $user ], 200 )->cookie( 'auth_token', $token, 60 * 24, '/', null, true, true );
        }

        // Increment failed attempts if login fails
        RateLimiter::hit( 'login:'.$request->ip() );

        return response()->json( [ 'error' => 'Unauthorized login' ], 401 );
    }

    // public function sendResetLinkEmail(Request $request)
    // {
    //     // Validate email
    //     $validator = Validator::make($request->all(), [
    //         'email' => 'required|email|exists:customers,email',
    //     ]);

    //     if (RateLimiter::tooManyAttempts('password-reset:' . $request->ip(), 5)) {
    //         return response()->json([
    //             'error' => 'Too many password reset attempts. Please try again later.'
    //         ], 429);
    //     }

    //     if ($validator->fails()) {
    //         return response()->json( $validator->errors(), 404 );
    //     }

    //     try {
    //         // Find the user by email
    //         $user = Customer::where('email', $request->email)->first();

    //         // Create a random token for resetting password
    //         $token = Str::random(60);

    //         // Save token in password_resets table (you appear to be storing in customers table instead)
    //         Customer::where('email', $request->email)->update([
    //             'email_enc' => Hash::make($user->email),
    //             'token_fp' => Hash::make($token),
    //             'fp_at' => now()
    //         ]);

    //         // Send reset password email
    //         Mail::to($user->email)->send(new ForgotPassword(
    //             $user,
    //             Crypt::encryptString($token),
    //             Crypt::encryptString($user->email)
    //         ));

    //         \Log::info('Password reset email triggered for '.$user->email);

    //         return response()->json('Email Has Been Sent', 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'error' => 'Failed to send reset email.',
    //             'exception' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    // public function rp_validateCreds( Request $request ) {
    //     $email = Crypt::decryptString( $request->email );
    //     $token = Crypt::decryptString( $request->token );

    //     $user = DB::table( 'customers' )->where( 'email', $email )->first();

    //     if ( $user ) {
    //         // Check if the token has expired ( example: 60 minutes validity )
    //         $tokenExpiryTime = now()->subMinutes( 60 );
    //         if ( $user->fp_at < $tokenExpiryTime ) {
    //             return response()->json( 'Token has expired', 400 );
    //         }

    //         // Check if the token and email match
    //         if ( Hash::check( $token, $user->token_fp ) && Hash::check( $email, $user->email_enc ) ) {
    //             return response()->json( $request->email, 200 );
    //         } else {
    //             return response()->json( 'Invalid credentials', 400 );
    //         }
    //     }
    //     return response()->json( 'Invalid credentials', 400 );
    // }

    // public function set_newpass( Request $request ) {
    //     $email = Crypt::decryptString( $request->token );
    //     $password = $request->password;

    //     $user = DB::table( 'customers' )->where( 'email', $email )->first();
    //     if ( Hash::check( $email, $user->email_enc ) ) {
    //         DB::table( 'customers' )->where( 'email', $email )->update( [
    //             'password' => Hash::make( $request->password ),
    //             'email_enc'=> Str::random( 60 ),
    //             'token_fp'=> Hash::make( Str::random( 60 ) ),
    //             'fp_at'=> NULL,
    //         ] );

    //         // Revoke all tokens after password reset
    //         DB::table( 'personal_access_tokens' )->where( 'tokenable_id', $user->id )->delete();

    //         return response()->json( 'Password Changed', 200 );
    //     } else {
    //         return response()->json( 'Error', 406 );
    //     }
    // }

    public function logout( Request $request ) {
        $user = $request->user();

        if ( $user ) {
            // Revoke all tokens for the authenticated user
            $user->tokens()->delete();

            return response()->json( [ 'message' => 'Logged out successfully' ], 200 )
            ->cookie( 'auth_token', '', -1 );
            // Remove the auth_token cookie
        }

        return response()->json( [ 'error' => 'Not authenticated' ], 401 );
    }

    // public function checkToken( Request $request ) {
    //     $user = $request->user();
    //     $token = $user?->currentAccessToken();

    //     if ( !$token ) {
    //         return response()->json( [ 'error' => 'No token found.' ], 401 );
    //     }

    //     // Check if token is older than 30 days
    //     if ( $token->created_at->lt( Carbon::now()->subDays( 30 ) ) ) {
    //         $token->delete();
    //         // Delete expired token
    //         return response()->json( [ 'error' => 'Token expired.' ], 401 );
    //     }

    //     return response()->json( [ 'message' => 'Token is valid.' ], 200 );
    // }

    // public function updateProfile(Request $request)
    // {
    //     $user = $request->user();
    //     $data = $request->validate([
    //         'name' => 'string|max:255',
    //         'phone_no' => 'string|max:20',
    //         'location' => 'string|max:255',
    //     ]);

    //     $user->update($data);

    //     return response()->json(['message' => 'Profile updated successfully', 'user' => $user]);
    // }

    // public function updateDp(Request $request)
    // {
    //     $user = $request->user();
    //     $data = $request->validate([
    //         'profile_pic' => 'required|image|max:2048',
    //     ]);

    //     // Handle the profile image upload
    //     if ($request->hasFile('profile_pic')) {
    //         $path = $request->file('profile_pic')->store('profile_pics', 'public');
    //         $user->update(['profile_pic' => $path]);
    //     }

    //     return response()->json(['message' => 'Profile image updated successfully', 'user' => $user]);
    // }
    
    // public function changePassword(Request $request)
    // {
    //     $user = $request->user();
    //     $data = $request->validate([
    //         'current_password' => 'required',
    //         'new_password' => ['required', 'string', 'min:8', 'regex:/[ A-Za-z ]/', 'regex:/[ 0-9 ]/', 'regex:/[ @$!%*#?& ]/'],
    //     ]);

    //     // Check if current password matches
    //     if (!Hash::check($data['current_password'], $user->password)) {
    //         return response()->json(['error' => 'Current password is incorrect'], 400);
    //     }

    //     // Update to new password
    //     $user->update(['password' => Hash::make($data['new_password'])]);

    //     // Revoke all tokens after password change
    //     $user->tokens()->delete();

    //     return response()->json(['message' => 'Password changed successfully. Please log in again.']);
    // }
}
