<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\NewsletterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    protected $newsletterService;

    public function __construct(NewsletterService $newsletterService)
    {
        $this->newsletterService = $newsletterService;
    }

    /**
     * Display newsletter subscription form
     */
    public function index()
    {
        return view('client.newsletter.index');
    }

    /**
     * Subscribe to newsletter
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'preferences' => 'sometimes|array',
            'preferences.news' => 'sometimes|boolean',
            'preferences.events' => 'sometimes|boolean',
            'preferences.resources' => 'sometimes|boolean',
            'preferences.volunteering' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $preferences = $request->input('preferences', [
            'news' => true,
            'events' => true,
            'resources' => true,
            'volunteering' => true
        ]);

        $result = $this->newsletterService->subscribe(
            $request->input('email'),
            $preferences
        );

        return response()->json($result);
    }

    /**
     * Unsubscribe from newsletter
     */
    public function unsubscribe(Request $request)
    {
        $email = $request->query('email');
        $token = $request->query('token');

        if (!$email || !$token) {
            return view('client.newsletter.unsubscribe-form');
        }

        $result = $this->newsletterService->unsubscribe($email, $token);

        return view('client.newsletter.unsubscribe-result', compact('result'));
    }

    /**
     * Process unsubscribe form
     */
    public function processUnsubscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->newsletterService->unsubscribe($request->input('email'));

        return response()->json($result);
    }

    /**
     * Get subscription status
     */
    public function getStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $status = $this->newsletterService->getSubscriptionStatus($request->input('email'));

        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }

    /**
     * Update subscription preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'preferences' => 'required|array',
            'preferences.news' => 'sometimes|boolean',
            'preferences.events' => 'sometimes|boolean',
            'preferences.resources' => 'sometimes|boolean',
            'preferences.volunteering' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->newsletterService->updatePreferences(
            $request->input('email'),
            $request->input('preferences')
        );

        return response()->json($result);
    }

    /**
     * Display newsletter preferences form
     */
    public function preferences(Request $request)
    {
        $email = $request->query('email');
        $token = $request->query('token');

        if (!$email || !$token) {
            return redirect()->route('newsletter.index')
                           ->with('error', 'Invalid preferences link');
        }

        $status = $this->newsletterService->getSubscriptionStatus($email);

        if (!$status['subscribed']) {
            return redirect()->route('newsletter.index')
                           ->with('error', 'Subscription not found');
        }

        return view('client.newsletter.preferences', [
            'email' => $email,
            'token' => $token,
            'subscription' => $status['subscription']
        ]);
    }
}