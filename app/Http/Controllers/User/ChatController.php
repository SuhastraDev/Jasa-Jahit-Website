<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /**
     * Tampilkan halaman obrolan untuk user yang sedang login.
     * Secara otomatis membuat ruang obrolan jika belum ada.
     */
    public function index()
    {
        $user = auth()->user();

        // 1 user hanya memiliki 1 chat room (dengan admin)
        $chat = Chat::firstOrCreate(['user_id' => $user->id]);

        // Tandai semua pesan dari admin sebagai dibaca
        $chat->messages()
             ->where('sender_id', '!=', $user->id)
             ->where('is_read', false)
             ->update(['is_read' => true]);

        return view('user.chat.index', compact('chat'));
    }

    /**
     * URL Polling untuk mengambil pesan-pesan terbaru.
     */
    public function fetchMessages(Request $request)
    {
        $user = auth()->user();
        $chat = Chat::where('user_id', $user->id)->firstOrFail();

        $query = $chat->messages()->with('sender')->orderBy('created_at', 'asc');

        if ($request->has('last_id')) {
            $query->where('id', '>', $request->last_id);
            
            // Auto mark as read untuk pesan baru milik admin
            $chat->messages()
                 ->where('id', '>', $request->last_id)
                 ->where('sender_id', '!=', $user->id)
                 ->update(['is_read' => true]);
        }

        $messages = $query->get();

        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }

    /**
     * Cek jumlah pesan belum dibaca dari admin (untuk notif global).
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
            'image' => 'nullable|image|max:5120'
        ]);

        $user = auth()->user();
        $chat = Chat::where('user_id', $user->id)->firstOrFail();

        $content = $request->input('content');
        $type = 'text';

        // Handle gambar jika ada
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('chats', 'public');
            $content = $path;
            
            // Jika ada teks sisipan di gambar, buat pesan teks baru setelah gambar
            $type = 'image';
        }

        $message = $chat->messages()->create([
            'sender_id' => $user->id,
            'type' => $type,
            'content' => $content,
        ]);

        // Jika ada teks *along* with gambar, kita bikin 2 messages di database aja untuk simplifikasi
        if ($request->hasFile('image') && $request->filled('content')) {
            $chat->messages()->create([
                'sender_id' => $user->id,
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
