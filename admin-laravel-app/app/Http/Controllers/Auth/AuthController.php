<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        // Check if user exists and is admin
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (!$user->isAdmin()) {
            throw ValidationException::withMessages([
                'email' => ['Access denied. Admin privileges required.'],
            ]);
        }

        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please contact administrator.'],
            ]);
        }

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // Update last login
            $user->update([
                'last_login' => now(),
                'login_count' => $user->login_count + 1,
            ]);

            return redirect()->intended(route('admin.dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Show email verification notice.
     */
    public function showVerifyEmailForm()
    {
        return view('auth.verify-email');
    }

    /**
     * Handle email verification.
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw ValidationException::withMessages([
                'email' => ['Invalid verification link.'],
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('admin.dashboard')->with('success', 'Email already verified.');
        }

        $user->update([
            'email_verified_at' => now(),
            'status' => 'active',
            'email_verification_token' => null,
            'modified' => now(),
        ]);

        Auth::login($user);

        return redirect()->route('admin.dashboard')->with('success', 'Email verified successfully! Welcome to AU VLP Admin.');
    }

    /**
     * Send verification email.
     */
    public function sendVerificationEmail(User $user)
    {
        try {
            $user->notify(new \App\Notifications\EmailVerificationNotification());
            
            Log::info('Admin email verification sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'timestamp' => now()
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send admin email verification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'timestamp' => now()
            ]);
            
            return false;
        }
    }

    /**
     * Resend verification email (public endpoint).
     */
    public function resendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('admin.dashboard');
        }

        $success = $this->sendVerificationEmail($request->user());
        
        if ($success) {
            return back()->with('success', 'Verification link sent!');
        } else {
            return back()->with('error', 'Failed to send verification email. Please try again.');
        }
    }
}