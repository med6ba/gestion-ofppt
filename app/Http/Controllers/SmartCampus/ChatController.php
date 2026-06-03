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

class ChatController extends Controller
{
    public function index(ChatAccessService $chatAccess): View
    {
        return view('chat.index', [
            'contacts' => $chatAccess->contactsFor(auth()->user()),
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
            'conversations' => $this->conversationList(),
            'activeConversation' => $conversation->load('participants'),
            'messages' => $conversation->messages()->with('sender')->oldest()->get(),
        ]);
    }

    public function storeMessage(SendMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('send', $conversation);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
            'is_read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);
        $conversation->participants()
            ->wherePivot('user_id', $request->user()->id)
            ->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        $conversation->participants()
            ->whereKeyNot($request->user()->id)
            ->get()
            ->each(fn (User $participant) => $participant->notify(new SmartCampusNotification(
                'New message',
                "{$request->user()->name}: ".str($message->body)->limit(90),
                route('chat.show', $conversation),
                'message'
            )));

        return back();
    }

    public function messages(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $this->markRead($conversation);

        return response()->json([
            'messages' => $conversation->messages()->with('sender')->oldest()->get()->map(fn (Message $message) => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender' => $message->sender->name,
                'body' => $message->body,
                'is_read' => (bool) $message->is_read,
                'created_at' => $message->created_at->format('H:i'),
            ]),
        ]);
    }

    private function conversationList()
    {
        return auth()->user()
            ->conversations()
            ->with(['participants', 'messages' => fn ($query) => $query->latest()->take(1)])
            ->withCount([
                'messages as unread_messages_count' => fn ($query) => $query
                    ->where('sender_id', '!=', auth()->id())
                    ->where('is_read', false),
            ])
            ->orderByDesc('last_message_at')
            ->get();
    }

    private function markRead(Conversation $conversation): void
    {
        $conversation->participants()->updateExistingPivot(auth()->id(), ['last_read_at' => now()]);

        $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
