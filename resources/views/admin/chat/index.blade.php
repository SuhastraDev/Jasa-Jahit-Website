@extends('layouts.admin')
@section('page-title', 'Chat Pelanggan')
@section('content')
<div class="py-4 sm:py-6">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">

        {{-- Mobile: tampil salah satu (list ATAU chat) --}}
        {{-- Desktop: tampil keduanya side by side --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex"
             style="height: calc(100vh - 140px); min-height: 500px;">

            {{-- Sidebar: Daftar Obrolan --}}
            {{-- Mobile: full width jika tidak ada activeChat, hidden jika ada activeChat --}}
            {{-- Desktop: always visible, fixed w-72 --}}
            <div class="flex flex-col border-r border-gray-200 bg-gray-50
                        {{ $activeChat ? 'hidden md:flex md:w-72 lg:w-80' : 'flex w-full md:w-72 lg:w-80' }}"
                 id="chat-sidebar">

                <div class="p-4 border-b border-gray-200 bg-white flex-shrink-0">
                    <h3 class="font-bold text-gray-800">Pesan Pelanggan</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Pilih pelanggan untuk membalas</p>
                </div>

                <div class="flex-1 overflow-y-auto" id="chat-list-container">
                    @forelse($chats as $c)
                        <a href="{{ route('admin.chat.index', ['user_id' => $c->user->id]) }}"
                           class="flex items-start gap-3 p-4 border-b border-gray-100 hover:bg-white transition-colors
                                  {{ ($activeChat && $activeChat->id == $c->id) ? 'bg-white border-l-4 border-l-blue-600' : 'border-l-4 border-l-transparent' }}"
                           data-chat-id="{{ $c->id }}"
                           data-user-id="{{ $c->user->id }}">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-slate-600 to-slate-800 flex items-center justify-center shrink-0 mt-0.5">
                                <span class="text-sm font-bold text-white">{{ strtoupper(substr($c->user->name, 0, 1)) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start gap-1">
                                    <span class="font-semibold text-gray-800 text-sm truncate">{{ $c->user->name }}</span>
                                    <span class="text-[10px] text-gray-400 whitespace-nowrap shrink-0">{{ $c->last_message_at ? $c->last_message_at->format('H:i') : '' }}</span>
                                </div>
                                <div class="flex justify-between items-center mt-0.5 gap-2">
                                    <p class="text-xs text-gray-500 truncate">
                                        @if($c->latestMessage)
                                            @if($c->latestMessage->type == 'image') 📷 Gambar
                                            @else {{ Str::limit($c->latestMessage->content, 32) }}
                                            @endif
                                        @else Belum ada pesan @endif
                                    </p>
                                    @if($c->unread_count > 0)
                                        <span class="unread-badge bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full shrink-0" data-chat-id="{{ $c->id }}">{{ $c->unread_count }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-8 text-center text-sm text-gray-400 flex flex-col items-center gap-3">
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            Tidak ada obrolan
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Area Chat Aktif --}}
            {{-- Mobile: full width jika ada activeChat, hidden jika tidak ada --}}
            {{-- Desktop: always visible, flex-1 --}}
            <div class="flex-col bg-white min-w-0
                        {{ $activeChat ? 'flex flex-1' : 'hidden md:flex md:flex-1' }}">

                @if($activeChat)
                    <div class="flex flex-col h-full" x-data="adminChat({{ $activeChat->id }})">

                        {{-- Header chat --}}
                        <div class="px-4 sm:px-6 py-3.5 border-b border-gray-200 flex items-center justify-between bg-white shadow-sm z-10 flex-shrink-0">
                            <div class="flex items-center gap-3">
                                {{-- Back button (mobile only) --}}
                                <a href="{{ route('admin.chat.index') }}"
                                   class="md:hidden w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 transition-colors flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </a>
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-slate-600 to-slate-800 flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-bold text-white">{{ strtoupper(substr($activeChat->user->name, 0, 1)) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-bold text-gray-800 text-sm sm:text-base truncate">{{ $activeChat->user->name }}</h3>
                                    <p class="text-xs text-gray-400 truncate hidden sm:block">{{ $activeChat->user->email }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                                <span class="text-xs text-gray-400 font-medium">Live</span>
                            </div>
                        </div>

                        {{-- Area Pesan --}}
                        <div class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-50 flex flex-col gap-3" id="chatbox" x-ref="chatbox">
                            <template x-for="msg in messages" :key="msg.id">
                                <div class="flex w-full" :class="msg.sender_id === {{ auth()->id() }} ? 'justify-end' : 'justify-start'">
                                    <div class="max-w-[80%] sm:max-w-[72%]">
                                        <div class="rounded-2xl px-3.5 py-2.5 shadow-sm"
                                             :class="msg.sender_id === {{ auth()->id() }}
                                                ? 'bg-blue-600 text-white rounded-tr-sm'
                                                : 'bg-white border border-gray-200 text-gray-800 rounded-tl-sm'">
                                            <template x-if="msg.type === 'text'">
                                                <p class="text-sm whitespace-pre-wrap leading-relaxed" x-text="msg.content"></p>
                                            </template>
                                            <template x-if="msg.type === 'image'">
                                                <img :src="'/storage/' + msg.content" class="rounded-lg max-h-48 cursor-pointer hover:opacity-90 transition-opacity" alt="Image">
                                            </template>
                                        </div>
                                        <div class="flex items-center mt-1 gap-1" :class="msg.sender_id === {{ auth()->id() }} ? 'justify-end' : 'justify-start'">
                                            <span class="text-[10px] text-gray-400" x-text="formatTime(msg.created_at)"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Input --}}
                        <div class="bg-white border-t border-gray-200 px-3 sm:px-4 py-3 flex-shrink-0">
                            <form @submit.prevent="sendMessage" class="flex items-end gap-2">
                                <div class="relative shrink-0">
                                    <input type="file" x-ref="imageInput" @change="handleFileChange" accept="image/*" class="hidden">
                                    <button type="button" @click="$refs.imageInput.click()"
                                            class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors focus:outline-none" title="Lampirkan Gambar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    </button>
                                    <div x-show="selectedFile" class="absolute bottom-10 left-0 bg-white border border-gray-200 rounded-xl p-2 shadow-lg flex items-center gap-2 min-w-[140px] z-10" x-cloak>
                                        <div class="w-8 h-8 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                                            <img :src="filePreview" class="w-full h-full object-cover">
                                        </div>
                                        <div class="flex-1 truncate text-xs text-gray-600" x-text="selectedFile?.name"></div>
                                        <button type="button" @click="resetFile" class="text-red-400 hover:text-red-600 shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex-1 bg-gray-100 rounded-2xl px-4 py-2">
                                    <textarea x-model="newMessage" @keydown.enter.prevent="sendMessage" rows="1"
                                              class="w-full bg-transparent border-0 focus:ring-0 resize-none px-0 text-sm py-1"
                                              placeholder="Balas pelanggan..."
                                              style="min-height: 34px; max-height: 100px;"></textarea>
                                </div>
                                <button type="submit" :disabled="isSending || (!newMessage.trim() && !selectedFile)"
                                        class="p-2.5 sm:p-3 bg-blue-600 text-white rounded-2xl hover:bg-blue-700 transition-colors focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed shrink-0 shadow-sm">
                                    <template x-if="!isSending">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/></svg>
                                    </template>
                                    <template x-if="isSending">
                                        <svg class="animate-spin w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </template>
                                </button>
                            </form>
                        </div>
                    </div>

                @else
                    {{-- Empty state (desktop only) --}}
                    <div class="flex-1 flex-col items-center justify-center text-gray-400 p-8 text-center bg-gray-50/40 hidden md:flex">
                        <div class="w-20 h-20 bg-gray-100 rounded-3xl flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <p class="text-lg font-semibold text-gray-500">Pilih Obrolan</p>
                        <p class="text-sm mt-1.5 text-gray-400 max-w-xs">Pilih pelanggan dari daftar di sebelah kiri untuk mulai membalas pesan.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

@if($activeChat)
<script>
    window.initialMessages = @json($activeChat->messages()->orderBy('created_at', 'asc')->get());
    window.adminUserId = {{ auth()->id() }};
</script>
@endif

<script>
function playNotificationSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        osc.frequency.setValueAtTime(1100, ctx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.25, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.4);
    } catch(e) {}
}

function updateSidebarUnread() {
    fetch('/admin/chat/unread-counts', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.ok ? r.json() : null).then(data => {
        if (!data) return;
        data.forEach(item => {
            const badge = document.querySelector(`.unread-badge[data-chat-id="${item.chat_id}"]`);
            if (item.unread_count > 0) {
                if (badge) { badge.textContent = item.unread_count; }
                else {
                    const link = document.querySelector(`a[data-chat-id="${item.chat_id}"]`);
                    if (link) {
                        const wrapper = link.querySelector('.flex.justify-between.items-center');
                        if (wrapper) {
                            const span = document.createElement('span');
                            span.className = 'unread-badge bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full shrink-0';
                            span.dataset.chatId = item.chat_id;
                            span.textContent = item.unread_count;
                            wrapper.appendChild(span);
                        }
                    }
                }
            } else if (badge) { badge.remove(); }
        });
    }).catch(() => {});
}

setInterval(updateSidebarUnread, 5000);

document.addEventListener('alpine:init', () => {
    Alpine.data('adminChat', (chatId) => ({
        chatId,
        messages: window.initialMessages || [],
        newMessage: '',
        selectedFile: null,
        filePreview: null,
        isSending: false,
        pollInterval: null,

        init() {
            this.scrollToBottom();
            this.pollInterval = setInterval(() => this.fetchMessages(), 3000);
        },
        destroy() { if (this.pollInterval) clearInterval(this.pollInterval); },
        scrollToBottom() {
            setTimeout(() => { const b = this.$refs.chatbox; if (b) b.scrollTop = b.scrollHeight; }, 50);
        },
        formatTime(d) {
            return new Date(d).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },
        handleFileChange(e) {
            const file = e.target.files[0];
            if (file) { this.selectedFile = file; this.filePreview = URL.createObjectURL(file); }
        },
        resetFile() {
            this.selectedFile = null; this.filePreview = null;
            if (this.$refs.imageInput) this.$refs.imageInput.value = '';
        },
        async fetchMessages() {
            const lastId = this.messages.length ? this.messages[this.messages.length - 1].id : 0;
            try {
                const res = await fetch(`/admin/chat/${this.chatId}/poll?last_id=${lastId}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.success && data.messages.length > 0) {
                        const existingIds = new Set(this.messages.map(m => m.id));
                        const newMsgs = data.messages.filter(m => !existingIds.has(m.id));
                        if (newMsgs.some(m => m.sender_id !== window.adminUserId)) playNotificationSound();
                        this.messages = [...this.messages, ...newMsgs];
                        const b = this.$refs.chatbox;
                        if (b.scrollHeight - b.scrollTop - b.clientHeight < 120) this.scrollToBottom();
                    }
                }
            } catch(e) { console.error('Poll error:', e); }
        },
        async sendMessage() {
            if ((!this.newMessage.trim() && !this.selectedFile) || this.isSending) return;
            this.isSending = true;
            const fd = new FormData();
            fd.append('_token', '{{ csrf_token() }}');
            if (this.newMessage.trim()) fd.append('content', this.newMessage.trim());
            if (this.selectedFile) fd.append('image', this.selectedFile);
            try {
                const res = await fetch(`/admin/chat/${this.chatId}/send`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.success) { this.newMessage = ''; this.resetFile(); await this.fetchMessages(); this.scrollToBottom(); }
                }
            } catch(e) { alert('Gagal mengirim pesan.'); }
            finally { this.isSending = false; }
        }
    }));
});
</script>

@endsection
