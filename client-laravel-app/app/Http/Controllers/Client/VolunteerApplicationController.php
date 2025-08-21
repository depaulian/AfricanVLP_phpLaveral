<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VolunteerApplication;
use App\Models\VolunteeringOpportunity;
use App\Http\Requests\VolunteerApplicationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VolunteerApplicationController extends Controller
{
    /**
     * Display a listing of the user's volunteer applications.
     */
    public function index(Request $request)
    {
        $query = VolunteerApplication::with([
            'opportunity.organization',
            'opportunity.category'
        ])->where('user_id', Auth::id());

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('opportunity', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('organization', function ($orgQuery) use ($search) {
                      $orgQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(10);

        // Get statistics
        $stats = [
            'total' => VolunteerApplication::where('user_id', Auth::id())->count(),
            'pending' => VolunteerApplication::where('user_id', Auth::id())->where('status', 'pending')->count(),
            'approved' => VolunteerApplication::where('user_id', Auth::id())->where('status', 'approved')->count(),
            'rejected' => VolunteerApplication::where('user_id', Auth::id())->where('status', 'rejected')->count(),
        ];

        return view('client.volunteering.applications.index', compact('applications', 'stats'));
    }

    /**
     * Show the form for creating a new volunteer application.
     */
    public function create(VolunteeringOpportunity $opportunity)
    {
        // Check if user already applied
        $existingApplication = VolunteerApplication::where('user_id', Auth::id())
            ->where('opportunity_id', $opportunity->id)
            ->first();

        if ($existingApplication) {
            return redirect()->route('client.volunteering.applications.show', $existingApplication)
                ->with('info', 'You have already applied for this opportunity.');
        }

        // Check if opportunity is still accepting applications
        if ($opportunity->application_deadline && $opportunity->application_deadline->isPast()) {
            return redirect()->route('client.volunteering.opportunities.show', $opportunity)
                ->with('error', 'The application deadline for this opportunity has passed.');
        }

        return view('client.volunteering.applications.create', compact('opportunity'));
    }

    /**
     * Store a newly created volunteer application.
     */
    public function store(VolunteerApplicationRequest $request, VolunteeringOpportunity $opportunity)
    {
        // Check if user already applied
        $existingApplication = VolunteerApplication::where('user_id', Auth::id())
            ->where('opportunity_id', $opportunity->id)
            ->first();

        if ($existingApplication) {
            return redirect()->route('client.volunteering.applications.show', $existingApplication)
                ->with('info', 'You have already applied for this opportunity.');
        }

        DB::beginTransaction();
        try {
            $application = VolunteerApplication::create([
                'user_id' => Auth::id(),
                'opportunity_id' => $opportunity->id,
                'cover_letter' => $request->cover_letter,
                'availability' => $request->availability,
                'experience' => $request->experience,
                'skills' => $request->skills ? json_encode($request->skills) : null,
                'status' => 'pending',
            ]);

            // Create status history entry
            $application->statusHistory()->create([
                'status' => 'pending',
                'notes' => 'Application submitted',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('client.volunteering.applications.show', $application)
                ->with('success', 'Your application has been submitted successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'There was an error submitting your application. Please try again.');
        }
    }

    /**
     * Display the specified volunteer application.
     */
    public function show(VolunteerApplication $application)
    {
        // Ensure user can only view their own applications
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to application.');
        }

        $application->load([
            'opportunity.organization',
            'opportunity.category',
            'statusHistory' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            },
            'assignment'
        ]);

        // Get related applications from the same user
        $relatedApplications = VolunteerApplication::with(['opportunity.organization'])
            ->where('user_id', Auth::id())
            ->where('id', '!=', $application->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('client.volunteering.applications.show', compact('application', 'relatedApplications'));
    }

    /**
     * Withdraw the specified volunteer application.
     */
    public function withdraw(Request $request, VolunteerApplication $application)
    {
        // Ensure user can only withdraw their own applications
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to application.');
        }

        // Check if application can be withdrawn
        if (!in_array($application->status, ['pending', 'approved'])) {
            return back()->with('error', 'This application cannot be withdrawn.');
        }

        $request->validate([
            'withdrawal_reason' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $application->update([
                'status' => 'withdrawn',
                'withdrawal_reason' => $request->withdrawal_reason,
                'withdrawn_at' => now(),
            ]);

            // Create status history entry
            $application->statusHistory()->create([
                'status' => 'withdrawn',
                'notes' => $request->withdrawal_reason ?: 'Application withdrawn by volunteer',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('client.volunteering.applications.index')
                ->with('success', 'Your application has been withdrawn successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'There was an error withdrawing your application. Please try again.');
        }
    }

    /**
     * Send a message related to the application.
     */
    public function sendMessage(Request $request, VolunteerApplication $application)
    {
        // Ensure user can only message about their own applications
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to application.');
        }

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        try {
            $application->messages()->create([
                'content' => $request->message,
                'from_admin' => false,
                'sender_id' => Auth::id(),
            ]);

            return back()->with('success', 'Your message has been sent successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'There was an error sending your message. Please try again.');
        }
    }

    /**
     * Download application as PDF.
     */
    public function downloadPdf(VolunteerApplication $application)
    {
        // Ensure user can only download their own applications
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to application.');
        }

        $application->load([
            'opportunity.organization',
            'opportunity.category',
            'user'
        ]);

        // This would typically use a PDF generation library like DomPDF or wkhtmltopdf
        // For now, we'll return a simple response
        return response()->json([
            'message' => 'PDF download functionality would be implemented here',
            'application_id' => $application->id
        ]);
    }
}