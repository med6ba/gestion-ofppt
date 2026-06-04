<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\SmartCampusNotification;
use App\Services\ChatAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function index(ChatAccessService $chatAccess): View
    {
        return view('chat.index', [
            'contacts' => $chatAccess->contactsFor(auth()->user()),
            'contactSections' => $chatAccess->contactSectionsFor(auth()->user()),
            'teachingGroups' => $chatAccess->teachingGroupsFor(auth()->user()),
            'conversations' => $this->conversationList(),
            'activeConversation' => null,
            'messages' => collect(),
        ]);
    }

    public function start(Request $request, ChatAccessService $chatAccess): RedirectResponse
    {
        $data = $request->validate([
            'receiver_id' => ['required', 'exists:users,id'],
        ]);

        $receiver = User::findOrFail($data['receiver_id']);
        abort_unless($chatAccess->canMessage($request->user(), $receiver), 403);

        $conversation = $chatAccess->findOrCreatePrivateConversation($request->user(), $receiver);

        return redirect()->route('chat.show', $conversation);
    }

    public function show(Conversation $conversation, ChatAccessService $chatAccess): View
    {
        $this->authorize('view', $conversation);
        $this->markRead($conversation);

        return view('chat.index', [
            'contacts' => $chatAccess->contactsFor(auth()->user()),
            'contactSections' => $chatAccess->contactSectionsFor(auth()->user()),
            'teachingGroups' => $chatAccess->teachingGroupsFor(auth()->user()),
            'conversations' => $this->conversationList(),
            'activeConversation' => $conversation->load('participants', 'group', 'module'),
            'messages' => $conversation->messages()->with('sender')->oldest()->get(),
        ]);
    }

    public function storeMessage(SendMessageRequest $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        $this->authorize('send', $conversation);

        $data = [
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
            'is_read' => false,
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('chat_attachments', 'public');
            
            $ext = strtolower($file->getClientOriginalExtension());
            $categories = [
                'image' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                'video' => ['mp4', 'webm', 'mov'],
                'pdf' => ['pdf'],
                'word' => ['doc', 'docx'],
                'powerpoint' => ['ppt', 'pptx'],
                'excel' => ['xls', 'xlsx', 'csv'],
            ];
            
            $category = 'other';
            foreach ($categories as $cat => $extensions) {
                if (in_array($ext, $extensions)) {
                    $category = $cat;
                    break;
                }
            }
            
            $data['attachment_path'] = $path;
            $data['attachment_category'] = $category;
            $data['attachment_original_name'] = $file->getClientOriginalName();
            $data['attachment_mime_type'] = $file->getMimeType();
            $data['attachment_size'] = $file->getSize();
        }

        $message = Message::create($data);

        $conversation->update(['last_message_at' => now()]);
        $conversation->participants()
            ->wherePivot('user_id', $request->user()->id)
            ->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        $messageData = [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender' => $request->user()->name,
            'body' => $message->body,
            'attachment_url' => $message->attachmentUrl(),
            'attachment_category' => $message->attachment_category,
            'attachment_original_name' => $message->attachment_original_name,
            'attachment_mime_type' => $message->attachment_mime_type,
            'attachment_size' => $message->attachment_size,
            'is_read' => false,
            'created_at' => $message->created_at->format('H:i'),
        ];

        broadcast(new \App\Events\MessageSent($messageData, $conversation->id))->toOthers();

        $conversation->participants()
            ->whereKeyNot($request->user()->id)
            ->get()
            ->each(fn (User $participant) => $participant->notify(new SmartCampusNotification(
                'Nouveau message' . ($conversation->type === 'group' ? ' dans ' . $conversation->title : ''),
                "{$request->user()->name}: " . ($message->body ? str($message->body)->limit(90) : 'Pièce jointe'),
                route('chat.show', $conversation),
                'message'
            )));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $messageData,
                'html' => view('chat.partials.message-bubble', [
                    'message' => $message,
                    'conversation' => $conversation
                ])->render()
            ]);
        }

        return back();
    }

    public function messages(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $this->markRead($conversation);

        if (request('html')) {
            $messages = $conversation->messages()->with('sender')->oldest()->get();
            $html = '';
            foreach ($messages as $message) {
                $html .= view('chat.partials.message-bubble', [
                    'message' => $message,
                    'conversation' => $conversation
                ])->render();
            }
            return response()->json(['html' => $html]);
        }

        return response()->json([
            'messages' => $conversation->messages()->with('sender')->oldest()->get()->map(fn (Message $message) => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender' => $message->sender->name,
                'body' => $message->body,
                'attachment_url' => $message->attachmentUrl(),
                'attachment_category' => $message->attachment_category,
                'attachment_original_name' => $message->attachment_original_name,
                'attachment_mime_type' => $message->attachment_mime_type,
                'attachment_size' => $message->attachment_size,
                'is_read' => $message->is_read,
                'created_at' => $message->created_at->format('H:i'),
            ]),
        ]);
    }

    private function conversationList()
    {
        $query = auth()->user()->conversations()
            ->with(['participants', 'messages' => fn ($query) => $query->latest()->take(1), 'group', 'module'])
            ->withCount([
                'messages as unread_messages_count' => fn ($q) => $q
                    ->where('sender_id', '!=', auth()->id())
                    ->where('is_read', false),
            ]);

        $filter = request('filter', 'tous');
        
        if ($filter === 'formateurs') {
            $query->withParticipantRole(User::ROLE_FORMATEUR, auth()->id());
        } elseif ($filter === 'stagiaires') {
            $query->withParticipantRole(User::ROLE_STAGIAIRE, auth()->id());
        } elseif ($filter === 'groupes') {
            $query->where('type', 'group');
        } elseif ($filter === 'non_lus') {
            $query->unreadForUser(auth()->id());
        } elseif ($filter === 'lus') {
            $query->readForUser(auth()->id());
        }

        $search = request('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('group', fn ($g) => $g->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                  ->orWhereHas('module', fn ($m) => $m->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('participants', fn ($p) => $p->where('users.id', '!=', auth()->id())->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->orderByDesc('last_message_at')->get();
    }

    private function markRead(Conversation $conversation): void
    {
        $conversation->participants()->updateExistingPivot(auth()->id(), ['last_read_at' => now()]);

        $updated = $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($updated > 0) {
            broadcast(new \App\Events\MessageRead($conversation->id, auth()->id()))->toOthers();
        }
    }
}
