<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VolunteerFeedback;
use App\Models\FeedbackTemplate;
use App\Models\VolunteerAssignment;
use App\Services\VolunteerFeedbackService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class VolunteerFeedbackController extends Controller
{
    public function __construct(
        private VolunteerFeedbackService $feedbackService
    ) {}

    /**
     * Display feedback dashboard
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $type = $request->get('type', 'received');
        
        $filters = [
            'feedback_type' => $request->get('feedback_type'),
            'rating_min' => $request->get('rating_min'),
            'rating_max' => $request->get('rating_max'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'has_response' => $request->get('has_response'),
            'per_page' => $request->get('per_page', 15),
        ];

        $feedback = $this->feedbackService->getFeedbackForUser($user, $type, $filters);
        $stats = $this->feedbackService->getUserFeedbackStats($user);

        return view('client.volunteering.feedback.index', compact(
            'feedback',
            'stats',
            'type',
            'filters'
        ));
    }

    /**
     * Show feedback form
     */
    public function create(Request $request): View
    {
        $assignmentId = $request->get('assignment_id');
        $feedbackType = $request->get('feedback_type');
        $templateId = $request->get('template_id');

        $assignment = VolunteerAssignment::findOrFail($assignmentId);
        
        // Check if user can provide feedback for this assignment
        $this->authorize('createFeedback', [$assignment, $feedbackType]);

        $template = null;
        if ($templateId) {
            $template = FeedbackTemplate::findOrFail($templateId);
        } else {
            // Get default template for feedback type
            $template = FeedbackTemplate::active()
                ->feedbackType($feedbackType)
                ->default()
                ->first();
        }

        // Determine reviewer and reviewee based on feedback type
        [$reviewer, $reviewee, $reviewerType] = $this->determineReviewerAndReviewee(
            $assignment,
            $feedbackType,
            $request->user()
        );

        return view('client.volunteering.feedback.create', compact(
            'assignment',
            'template',
            'feedbackType',
            'reviewer',
            'reviewee',
            'reviewerType'
        ));
    }

    /**
     * Store new feedback
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'assignment_id' => 'required|exists:volunteer_assignments,id',
            'feedback_type' => 'required|in:volunteer_to_organization,organization_to_volunteer,supervisor_to_volunteer,volunteer_to_supervisor,beneficiary_to_volunteer',
            'template_id' => 'nullable|exists:feedback_templates,id',
            'overall_rating' => 'nullable|numeric|min:1|max:5',
            'communication_rating' => 'nullable|numeric|min:1|max:5',
            'reliability_rating' => 'nullable|numeric|min:1|max:5',
            'skill_rating' => 'nullable|numeric|min:1|max:5',
            'attitude_rating' => 'nullable|numeric|min:1|max:5',
            'impact_rating' => 'nullable|numeric|min:1|max:5',
            'positive_feedback' => 'nullable|string|max:2000',
            'improvement_feedback' => 'nullable|string|max:2000',
            'additional_comments' => 'nullable|string|max:2000',
            'structured_ratings' => 'nullable|array',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_anonymous' => 'boolean',
            'is_public' => 'boolean',
        ]);

        $assignment = VolunteerAssignment::findOrFail($request->assignment_id);
        $template = $request->template_id ? FeedbackTemplate::findOrFail($request->template_id) : null;

        // Check authorization
        $this->authorize('createFeedback', [$assignment, $request->feedback_type]);

        // Determine reviewer and reviewee
        [$reviewer, $reviewee, $reviewerType] = $this->determineReviewerAndReviewee(
            $assignment,
            $request->feedback_type,
            $request->user()
        );

        try {
            // Create feedback from template if provided
            if ($template) {
                $feedback = $this->feedbackService->createFromTemplate(
                    $template,
                    $assignment,
                    $reviewer,
                    $reviewee,
                    $reviewerType
                );
            } else {
                $feedback = VolunteerFeedback::create([
                    'assignment_id' => $assignment->id,
                    'reviewer_id' => $reviewer->id,
                    'reviewee_id' => $reviewee->id,
                    'feedback_type' => $request->feedback_type,
                    'reviewer_type' => $reviewerType,
                    'status' => 'draft',
                ]);
            }

            // Submit feedback with data
            $this->feedbackService->submitFeedback($feedback, $request->all(), $template);

            return redirect()
                ->route('client.volunteering.feedback.show', $feedback)
                ->with('success', 'Feedback submitted successfully!');

        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['feedback' => $e->getMessage()]);
        }
    }

    /**
     * Display specific feedback
     */
    public function show(VolunteerFeedback $feedback): View
    {
        $this->authorize('view', $feedback);

        $feedback->load(['reviewer', 'reviewee', 'assignment.organization']);

        return view('client.volunteering.feedback.show', compact('feedback'));
    }

    /**
     * Show edit form for feedback
     */
    public function edit(VolunteerFeedback $feedback): View
    {
        $this->authorize('update', $feedback);

        if (!$feedback->canBeEdited()) {
            abort(403, 'This feedback cannot be edited.');
        }

        $feedback->load(['assignment', 'reviewer', 'reviewee']);

        return view('client.volunteering.feedback.edit', compact('feedback'));
    }

    /**
     * Update feedback
     */
    public function update(Request $request, VolunteerFeedback $feedback): RedirectResponse
    {
        $this->authorize('update', $feedback);

        if (!$feedback->canBeEdited()) {
            abort(403, 'This feedback cannot be edited.');
        }

        $request->validate([
            'overall_rating' => 'nullable|numeric|min:1|max:5',
            'communication_rating' => 'nullable|numeric|min:1|max:5',
            'reliability_rating' => 'nullable|numeric|min:1|max:5',
            'skill_rating' => 'nullable|numeric|min:1|max:5',
            'attitude_rating' => 'nullable|numeric|min:1|max:5',
            'impact_rating' => 'nullable|numeric|min:1|max:5',
            'positive_feedback' => 'nullable|string|max:2000',
            'improvement_feedback' => 'nullable|string|max:2000',
            'additional_comments' => 'nullable|string|max:2000',
            'structured_ratings' => 'nullable|array',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_anonymous' => 'boolean',
            'is_public' => 'boolean',
        ]);

        $feedback->update($request->only([
            'overall_rating',
            'communication_rating',
            'reliability_rating',
            'skill_rating',
            'attitude_rating',
            'impact_rating',
            'positive_feedback',
            'improvement_feedback',
            'additional_comments',
            'structured_ratings',
            'tags',
            'is_anonymous',
            'is_public',
        ]));

        return redirect()
            ->route('client.volunteering.feedback.show', $feedback)
            ->with('success', 'Feedback updated successfully!');
    }

    /**
     * Add response to feedback
     */
    public function respond(Request $request, VolunteerFeedback $feedback): RedirectResponse
    {
        $this->authorize('respond', $feedback);

        $request->validate([
            'response' => 'required|string|max:2000',
        ]);

        $this->feedbackService->addResponse($feedback, $request->response, $request->user());

        return back()->with('success', 'Response added successfully!');
    }

    /**
     * Request follow-up for feedback
     */
    public function requestFollowUp(Request $request, VolunteerFeedback $feedback): RedirectResponse
    {
        $this->authorize('requestFollowUp', $feedback);

        $request->validate([
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $scheduledAt = $request->scheduled_at ? \Carbon\Carbon::parse($request->scheduled_at) : null;

        $this->feedbackService->requestFollowUp($feedback, $request->user(), $scheduledAt);

        return back()->with('success', 'Follow-up requested successfully!');
    }

    /**
     * Get available templates for feedback type
     */
    public function getTemplates(Request $request): JsonResponse
    {
        $request->validate([
            'feedback_type' => 'required|string',
            'organization_id' => 'nullable|exists:organizations,id',
        ]);

        $query = FeedbackTemplate::active()
            ->feedbackType($request->feedback_type);

        if ($request->organization_id) {
            $query->where(function ($q) use ($request) {
                $q->forOrganization($request->organization_id)
                  ->orWhere('organization_id', null); // Include global templates
            });
        } else {
            $query->global();
        }

        $templates = $query->orderBy('is_default', 'desc')
                          ->orderBy('name')
                          ->get();

        return response()->json([
            'templates' => $templates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'is_default' => $template->is_default,
                    'preview' => $template->getPreviewData(),
                ];
            }),
        ]);
    }

    /**
     * Get available tags for feedback type
     */
    public function getTags(Request $request): JsonResponse
    {
        $request->validate([
            'feedback_type' => 'required|string',
        ]);

        $tags = VolunteerFeedback::getAvailableTags($request->feedback_type);

        return response()->json(['tags' => $tags]);
    }

    /**
     * Get feedback statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->feedbackService->getUserFeedbackStats($user);

        return response()->json(['stats' => $stats]);
    }

    /**
     * Get public feedback
     */
    public function public(Request $request): View
    {
        $filters = [
            'feedback_type' => $request->get('feedback_type'),
            'rating_min' => $request->get('rating_min'),
            'organization_id' => $request->get('organization_id'),
            'per_page' => $request->get('per_page', 15),
        ];

        $feedback = $this->feedbackService->getPublicFeedback($filters);

        return view('client.volunteering.feedback.public', compact('feedback', 'filters'));
    }

    /**
     * Determine reviewer and reviewee based on feedback type and current user
     */
    private function determineReviewerAndReviewee(
        VolunteerAssignment $assignment,
        string $feedbackType,
        $currentUser
    ): array {
        return match ($feedbackType) {
            'volunteer_to_organization' => [
                $currentUser, // reviewer (volunteer)
                $assignment->organization->users()->where('role', 'admin')->first(), // reviewee (org admin)
                'volunteer'
            ],
            'organization_to_volunteer' => [
                $currentUser, // reviewer (org admin)
                $assignment->volunteer, // reviewee (volunteer)
                'organization_admin'
            ],
            'supervisor_to_volunteer' => [
                $currentUser, // reviewer (supervisor)
                $assignment->volunteer, // reviewee (volunteer)
                'supervisor'
            ],
            'volunteer_to_supervisor' => [
                $currentUser, // reviewer (volunteer)
                $assignment->supervisor ?? $assignment->organization->users()->where('role', 'supervisor')->first(), // reviewee (supervisor)
                'volunteer'
            ],
            'beneficiary_to_volunteer' => [
                $currentUser, // reviewer (beneficiary)
                $assignment->volunteer, // reviewee (volunteer)
                'beneficiary'
            ],
            default => throw new \InvalidArgumentException("Invalid feedback type: {$feedbackType}")
        };
    }
}