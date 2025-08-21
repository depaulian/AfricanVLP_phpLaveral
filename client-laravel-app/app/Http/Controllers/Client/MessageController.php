<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\ConversationParticipant;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of conversations.
     */
    public function index(Request $request)
    {
        $conversations = Conversation::whereHas('participants', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->with(['participants.user', 'participants.organization', 'lastMessage.user'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('client.messages.index', compact('conversations'));
    }

    /**
     * Show the form for creating a new conversation.
     */
    public function create(Request $request)
    {
        $recipientType = $request->get('type'); // 'user' or 'organization'
        $recipientId = $request->get('id');

        $recipient = null;
        if ($recipientType === 'user' && $recipientId) {
            $recipient = User::find($recipientId);
        } elseif ($recipientType === 'organization' && $recipientId) {
            $recipient = Organization::find($recipientId);
        }

        // Get user's organizations for potential recipients
        $organizations = Auth::user()->organizations()->where('status', 'active')->get();
        
        // Get other users from the same organizations
        $organizationIds = $organizations->pluck('id');
        $users = User::whereHas('organizations', function ($query) use ($organizationIds) {
                $query->whereIn('organizations.id', $organizationIds);
            })
            ->where('id', '!=', Auth::id())
            ->where('status', 'active')
            ->get();

        return view('client.messages.create', compact('recipient', 'recipientType', 'organizations', 'users'));
    }

    /**
     * Store a newly created conversation.
     */
    public function store(Request $request)
    {
        $request->validate([
            'recipient_type' => 'required|in:user,organization',
            'recipient_id' => 'required|integer',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        // Verify recipient exists
        if ($request->recipient_type === 'user') {
            $recipient = User::findOrFail($request->recipient_id);
            
            // Check if users share an organization
            $sharedOrgs = Auth::user()->organizations()
                ->whereHas('users', function ($query) use ($recipient) {
                    $query->where('users.id', $recipient->id);
                })->exists();
                
            if (!$sharedOrgs) {
                abort(403, 'You can only message users from your organizations.');
            }
        } else {
            $recipient = Organization::findOrFail($request->recipient_id);
            
            // Check if user belongs to organization
            if (!Auth::user()->organizations()->where('organizations.id', $recipient->id)->exists()) {
                abort(403, 'You can only message organizations you belong to.');
            }
        }

        DB::transaction(function () use ($request) {
            // Create conversation
            $conversation = Conversation::create([
                'subject' => $request->subject,
                'created_by' => Auth::id(),
                'status' => 'active',
            ]);

            // Add participants
            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => Auth::id(),
                'joined_at' => now(),
            ]);

            if ($request->recipient_type === 'user') {
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $request->recipient_id,
                    'joined_at' => now(),
                ]);
            } else {
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'organization_id' => $request->recipient_id,
                    'joined_at' => now(),
                ]);
            }

            // Create first message
            ConversationMessage::create([
                'conversation_id' => $conversation->id,
                'user_id' => Auth::id(),
                'message' => $request->message,
                'message_type' => 'text',
                'status' => 'sent',
            ]);

            $conversation->touch();
        });

        return redirect()->route('messages.index')
            ->with('success', 'Message sent successfully!');
    }

    /**
     * Display the specified conversation.
     */
    public function show(Conversation $conversation)
    {
        // Check if user is participant
        if (!$conversation->participants()->where('user_id', Auth::id())->exists()) {
            abort(403, 'You do not have access to this conversation.');
        }

        $conversation->load(['participants.user', 'participants.organization']);

        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        // Mark messages as read
        $conversation->messages()
            ->where('user_id', '!=', Auth::id())
            ->where('read_at', null)
            ->update(['read_at' => now()]);

        return view('client.messages.show', compact('conversation', 'messages'));
    }

    /**
     * Store a new message in the conversation.
     */
    public function storeMessage(Request $request, Conversation $conversation)
    {
        // Check if user is participant
        if (!$conversation->participants()->where('user_id', Auth::id())->exists()) {
            abort(403, 'You do not have access to this conversation.');
        }

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
            'message_type' => 'text',
            'status' => 'sent',
        ]);

        $conversation->touch();

        return back()->with('success', 'Message sent!');
    }

    /**
     * Mark conversation as archived.
     */
    public function archive(Conversation $conversation)
    {
        // Check if user is participant
        $participant = $conversation->participants()->where('user_id', Auth::id())->first();
        if (!$participant) {
            abort(403, 'You do not have access to this conversation.');
        }

        $participant->update(['archived_at' => now()]);

        return back()->with('success', 'Conversation archived.');
    }

    /**
     * Mark conversation as unarchived.
     */
    public function unarchive(Conversation $conversation)
    {
        // Check if user is participant
        $participant = $conversation->participants()->where('user_id', Auth::id())->first();
        if (!$participant) {
            abort(403, 'You do not have access to this conversation.');
        }

        $participant->update(['archived_at' => null]);

        return back()->with('success', 'Conversation unarchived.');
    }

    /**
     * Get unread message count for the authenticated user.
     */
    public function unreadCount()
    {
        $count = ConversationMessage::whereHas('conversation.participants', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->where('user_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Search conversations and messages.
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query) {
            return redirect()->route('messages.index');
        }

        $conversations = Conversation::whereHas('participants', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->where(function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                  ->orWhereHas('messages', function ($mq) use ($query) {
                      $mq->where('message', 'like', "%{$query}%");
                  });
            })
            ->with(['participants.user', 'participants.organization', 'lastMessage.user'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('client.messages.index', compact('conversations', 'query'));
    }
}