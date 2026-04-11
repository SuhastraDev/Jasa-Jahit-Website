<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /**
     * Tampilkan halaman obrolan admin.
     * Sidebar berisi daftar user (chat room) diurutkan dari yang terbaru chatnya.
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

        $activeChat = null;
        if ($request->has('user_id')) {
            $activeChat = Chat::with('user')->where('user_id', $request->user_id)->first();
            
            if ($activeChat) {
                // Tandai pesan sebagai dibaca
                $activeChat->messages()
                           ->where('sender_id', '!=', auth()->id())
                           ->where('is_read', false)
                           ->update(['is_read' => true]);
            }
        }

        return view('admin.chat.index', compact('chats', 'activeChat'));
    }

    /**
     * Ambil data pesan baru untuk user yang sedang aktif di chat room.
     */
    public function fetchMessages(Chat $chat, Request $request)
    {
        $query = $chat->messages()->with('sender')->orderBy('created_at', 'asc');

        if ($request->has('last_id')) {
            $query->where('id', '>', $request->last_id);
            
            // Tandai pesan terbaru user sebagai sudah dibaca
            $chat->messages()
                 ->where('id', '>', $request->last_id)
                 ->where('sender_id', '!=', auth()->id())
                 ->update(['is_read' => true]);
        }

        $messages = $query->get();

        // Cari tahu info unread global untuk admin badge / sidebar
        $globalUnread = Message::where('sender_id', '!=', auth()->id())
                               ->where('is_read', false)
                               ->count();

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'global_unread' => $globalUnread
        ]);
    }

    /**
     * Kembalikan unread count per chat untuk update sidebar secara live.
     */
    public function unreadCounts()
    {
        $counts = Chat::withCount(['messages as unread_count' => function ($query) {
                            $query->where('sender_id', '!=', auth()->id())
                                  ->where('is_read', false);
                        }])
                        ->get(['id'])
                        ->map(fn($c) => ['chat_id' => $c->id, 'unread_count' => $c->unread_count]);

        return response()->json($counts);
    }

    /**
     * Kirim pesan ke user.
     */
    public function store(Request $request, Chat $chat)
    {
        $request->validate([
            'content' => 'required_without:image|string|max:1000',
            'image' => 'nullable|image|max:5120'
        ]);

        $content = $request->input('content');
        $type = 'text';

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('chats', 'public');
            $content = $path;
            $type = 'image';
        }

        $message = $chat->messages()->create([
            'sender_id' => auth()->id(),
            'type' => $type,
            'content' => $content,
        ]);

        if ($request->hasFile('image') && $request->filled('content')) {
            $chat->messages()->create([
                'sender_id' => auth()->id(),
                'type' => 'text',
                'content' => $request->input('content'),
            ]);
        }

        $chat->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => $message->load('sender')
        ]);
    }
}
