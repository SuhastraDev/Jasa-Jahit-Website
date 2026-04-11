<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /**
     * Tampilkan halaman obrolan untuk user yang sedang login.
     */
    public function index()
    {
        $user = auth()->user();
        $chat = Chat::firstOrCreate(['user_id' => $user->id]);

        // Tandai semua pesan dari admin sebagai dibaca
        $chat->messages()
             ->where('sender_id', '!=', $user->id)
             ->where('is_read', false)
             ->update(['is_read' => true]);

        return view('user.chat.index', compact('chat'));
    }

    /**
     * Polling pesan terbaru + update read status.
     */
    public function fetchMessages(Request $request)
    {
        $user = auth()->user();
        $chat = Chat::where('user_id', $user->id)->firstOrFail();

        $query = $chat->messages()->with('sender')->orderBy('created_at', 'asc');

        if ($request->has('last_id')) {
            $query->where('id', '>', $request->last_id);

            $chat->messages()
                 ->where('id', '>', $request->last_id)
                 ->where('sender_id', '!=', $user->id)
                 ->update(['is_read' => true]);
        }

        $messages = $query->get();

        return response()->json(['success' => true, 'messages' => $messages]);
    }

    /**
     * Status online admin untuk ditampilkan di header chat user.
     */
    public function adminStatus()
    {
        $admin = User::where('role', 'admin')->orderByDesc('last_seen_at')->first();

        if (!$admin || !$admin->last_seen_at) {
            return response()->json(['online' => false, 'last_seen' => null]);
        }

        $online = $admin->isOnline();
        $lastSeen = $online ? null : $admin->last_seen_at->diffForHumans();

        return response()->json(['online' => $online, 'last_seen' => $lastSeen]);
    }

    /**
     * Unread count dari admin.
     */
    public function unreadCount()
    {
        $user = auth()->user();
        $chat = Chat::where('user_id', $user->id)->first();
        $count = 0;
        if ($chat) {
            $count = $chat->messages()
                ->where('sender_id', '!=', $user->id)
                ->where('is_read', false)
                ->count();
        }
        return response()->json(['unread' => $count]);
    }

    /**
     * Kirim pesan baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required_without:image|string|max:1000',
            'image'   => 'nullable|image|max:5120',
        ]);

        $user = auth()->user();
        $chat = Chat::where('user_id', $user->id)->firstOrFail();

        $content = $request->input('content');
        $type    = 'text';

        if ($request->hasFile('image')) {
            $path    = $request->file('image')->store('chats', 'public');
            $content = $path;
            $type    = 'image';
        }

        $message = $chat->messages()->create([
            'sender_id' => $user->id,
            'type'      => $type,
            'content'   => $content,
        ]);

        if ($request->hasFile('image') && $request->filled('content')) {
            $chat->messages()->create([
                'sender_id' => $user->id,
                'type'      => 'text',
                'content'   => $request->input('content'),
            ]);
        }

        $chat->update(['last_message_at' => now()]);

        return response()->json(['success' => true, 'message' => $message->load('sender')]);
    }

    /**
     * Hapus satu pesan milik user sendiri.
     */
    public function destroyMessage(Message $message)
    {
        if ((int) $message->sender_id !== (int) auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($message->type === 'image') {
            Storage::disk('public')->delete($message->content);
        }

        $message->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Hapus banyak pesan milik user sendiri.
     */
    public function destroyMessages(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $user = auth()->user();
        $chat = Chat::where('user_id', $user->id)->firstOrFail();

        $messages = Message::whereIn('id', $request->ids)
            ->where('chat_id', $chat->id)
            ->where('sender_id', $user->id)
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
