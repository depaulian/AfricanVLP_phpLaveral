@extends('layouts.app')

@section('title', 'Verify Your Email Address')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-yellow-100">
                <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Verify Your Email Address
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Before proceeding, please check your email for a verification link.
            </p>
        </div>

        <div class="mt-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">
                                {{ session('error') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="rounded-md bg-blue-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            Check Your Email
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>
                                We've sent a verification link to your email address. Please click the link in the email to verify your account and complete the registration process.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Didn't receive the email?
                </p>
                <form method="POST" action="{{ route('verification.send') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-auvlp-primary hover:bg-auvlp-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-auvlp-primary">
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Resend Verification Email
                    </button>
                </form>
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Need help? 
                    <a href="{{ route('contact') }}" class="font-medium text-auvlp-primary hover:text-auvlp-primary-hover">
                        Contact Support
                    </a>
                </p>
            </div>

            <div class="text-center">
                <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    ← Back to Home
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-auvlp-primary {
        background-color: #8A2B13;
    }
    .bg-auvlp-primary-hover:hover {
        background-color: #F4F2C9;
        color: #8A2B13;
    }
    .text-auvlp-primary {
        color: #8A2B13;
    }
    .text-auvlp-primary-hover:hover {
        color: #F4F2C9;
    }
    .focus\:ring-auvlp-primary:focus {
        --tw-ring-color: #8A2B13;
    }
</style>
@endpush
