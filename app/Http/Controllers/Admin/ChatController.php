<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /**
     * Tampilkan halaman obrolan admin.
     */
    public function index(Request $request)
    {
        $chats = Chat::with(['user', 'latestMessage'])
                     ->withCount(['messages as unread_count' => function ($query) {
                         $query->where('sender_id', '!=', auth()->id())
                               ->where('is_read', false);
                     }])
                     ->orderByDesc('last_message_at')
                     ->get();

        $activeChat    = null;
        $activeUserOnline   = false;
        $activeUserLastSeen = null;

        if ($request->has('user_id')) {
            $activeChat = Chat::with('user')->where('user_id', $request->user_id)->first();

            if ($activeChat) {
                $activeChat->messages()
                           ->where('sender_id', '!=', auth()->id())
                           ->where('is_read', false)
                           ->update(['is_read' => true]);

                $activeUserOnline   = $activeChat->user->isOnline();
                $activeUserLastSeen = (!$activeUserOnline && $activeChat->user->last_seen_at)
                                        ? $activeChat->user->last_seen_at->diffForHumans()
                                        : null;
            }
        }

        return view('admin.chat.index', compact('chats', 'activeChat', 'activeUserOnline', 'activeUserLastSeen'));
    }

    /**
     * Ambil pesan terbaru + mark as read.
     */
    public function fetchMessages(Chat $chat, Request $request)
    {
        $query = $chat->messages()->with('sender')->orderBy('created_at', 'asc');

        if ($request->has('last_id')) {
            $query->where('id', '>', $request->last_id);

            $chat->messages()
                 ->where('id', '>', $request->last_id)
                 ->where('sender_id', '!=', auth()->id())
                 ->update(['is_read' => true]);
        }

        $messages = $query->get();

        $globalUnread = Message::where('sender_id', '!=', auth()->id())
                               ->where('is_read', false)
                               ->count();

        return response()->json([
            'success'       => true,
            'messages'      => $messages,
            'global_unread' => $globalUnread,
        ]);
    }

    /**
     * Unread count per chat untuk sidebar.
     */
    public function unreadCounts()
    {
        $counts = Chat::withCount(['messages as unread_count' => function ($query) {
                            $query->where('sender_id', '!=', auth()->id())
                                  ->where('is_read', false);
                        }])
                        ->with('user:id,last_seen_at')
                        ->get(['id', 'user_id'])
                        ->map(fn($c) => [
                            'chat_id'      => $c->id,
                            'unread_count' => $c->unread_count,
                            'user_online'  => $c->user?->isOnline() ?? false,
                        ]);

        return response()->json($counts);
    }

    /**
     * Status online user tertentu untuk header chat admin.
     */
    public function userStatus(User $user)
    {
        if (!$user->last_seen_at) {
            return response()->json(['online' => false, 'last_seen' => null]);
        }

        $online = $user->isOnline();
        return response()->json([
            'online'    => $online,
            'last_seen' => $online ? null : $user->last_seen_at->diffForHumans(),
        ]);
    }

    /**
     * Kirim pesan ke user.
     */
    public function store(Request $request, Chat $chat)
    {
        $request->validate([
            'content' => 'required_without:image|string|max:1000',
            'image'   => 'nullable|image|max:5120',
        ]);

        $content = $request->input('content');
        $type    = 'text';

        if ($request->hasFile('image')) {
            $path    = $request->file('image')->store('chats', 'public');
            $content = $path;
            $type    = 'image';
        }

        $message = $chat->messages()->create([
            'sender_id' => auth()->id(),
            'type'      => $type,
            'content'   => $content,
        ]);

        if ($request->hasFile('image') && $request->filled('content')) {
            $chat->messages()->create([
                'sender_id' => auth()->id(),
                'type'      => 'text',
                'content'   => $request->input('content'),
            ]);
        }

        $chat->update(['last_message_at' => now()]);

        return response()->json(['success' => true, 'message' => $message->load('sender')]);
    }

    /**
     * Hapus satu pesan (admin bisa hapus pesan siapa pun).
     */
    public function destroyMessage(Message $message)
    {
        if ($message->type === 'image') {
            Storage::disk('public')->delete($message->content);
        }

        $message->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Hapus banyak pesan dalam satu chat.
     */
    public function destroyMessages(Request $request, Chat $chat)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $messages = Message::whereIn('id', $request->ids)
            ->where('chat_id', $chat->id)
            ->get();

        foreach ($messages as $msg) {
            if ($msg->type === 'image') {
                Storage::disk('public')->delete($msg->content);
            }
            $msg->delete();
        }

        return response()->json(['success' => true, 'deleted' => $messages->count()]);
    }
}
