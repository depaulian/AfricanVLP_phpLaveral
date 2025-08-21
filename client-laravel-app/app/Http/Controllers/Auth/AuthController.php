<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
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
     * Show the registration form.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
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

        // Check if user exists
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please verify your email or contact support.'],
            ]);
        }

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // Update last login
            $user->update([
                'last_login' => now(),
                'login_count' => $user->login_count + 1,
            ]);

            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    /**
     * Handle registration request.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:45|regex:/^[a-zA-Z\s\-\'\.]+$/',
            'last_name' => 'required|string|max:45|regex:/^[a-zA-Z\s\-\'\.]+$/',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'terms' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'country_id' => $request->country_id,
            'city_id' => $request->city_id,
            'status' => 'pending',
            'email_verification_token' => Str::random(60),
            'created' => now(),
            'modified' => now(),
        ]);

        // Send verification email
        $this->sendVerificationEmail($user);

        return redirect()->route('verification.notice')
                        ->with('success', 'Registration successful! Please check your email to verify your account.');
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
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
            return redirect()->route('dashboard')->with('success', 'Email already verified.');
        }

        $user->update([
            'email_verified_at' => now(),
            'status' => 'active',
            'email_verification_token' => null,
            'modified' => now(),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Email verified successfully! Welcome to AU VLP.');
    }

    /**
     * Send verification email.
     */
    public function sendVerificationEmail(User $user)
    {
        try {
            $user->notify(new \App\Notifications\EmailVerificationNotification());
            
            Log::info('Email verification sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'timestamp' => now()
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email verification', [
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
            return redirect()->route('dashboard');
        }

        $success = $this->sendVerificationEmail($request->user());
        
        if ($success) {
            return back()->with('success', 'Verification link sent!');
        } else {
            return back()->with('error', 'Failed to send verification email. Please try again.');
        }
    }

    /**
     * Show forgot password form.
     */
    public function showForgotPasswordForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Send password reset link.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $user = User::where('email', $request->email)->first();
        $token = Str::random(60);

        $user->update([
            'password_reset_token' => $token,
            'modified' => now(),
        ]);

        // Send password reset email
        // Implementation would go here

        return back()->with('success', 'Password reset link sent to your email!');
    }

    /**
     * Show password reset form.
     */
    public function showResetPasswordForm(Request $request, $token)
    {
        return view('auth.passwords.reset', ['token' => $token, 'email' => $request->email]);
    }

    /**
     * Reset password.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $user = User::where('email', $request->email)
                   ->where('password_reset_token', $request->token)
                   ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Invalid reset token.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'password_reset_token' => null,
            'modified' => now(),
        ]);

        return redirect()->route('login')->with('success', 'Password reset successfully!');
    }
}