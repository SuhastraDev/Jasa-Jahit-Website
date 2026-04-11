@extends('layouts.admin')
@section('page-title', 'Chat Pelanggan')
@section('content')
<div class="py-4 sm:py-6">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex"
             style="height: calc(100vh - 140px); min-height: 500px;">

            {{-- Sidebar --}}
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
                            <div class="relative shrink-0 mt-0.5">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-slate-600 to-slate-800 flex items-center justify-center">
                                    <span class="text-sm font-bold text-white">{{ strtoupper(substr($c->user->name, 0, 1)) }}</span>
                                </div>
                                {{-- Online dot --}}
                                <span class="online-dot absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white {{ $c->user->isOnline() ? 'bg-green-500' : 'bg-gray-300' }}"
                                      data-user-id="{{ $c->user->id }}"></span>
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
            <div class="flex-col bg-white min-w-0
                        {{ $activeChat ? 'flex flex-1' : 'hidden md:flex md:flex-1' }}">

                @if($activeChat)
                    <div class="flex flex-col h-full" x-data="adminChat({{ $activeChat->id }})">

                        {{-- Header --}}
                        <div class="px-4 sm:px-6 py-3.5 border-b border-gray-200 flex items-center justify-between bg-white shadow-sm z-10 flex-shrink-0">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.chat.index') }}"
                                   class="md:hidden w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 transition-colors flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </a>
                                <div class="relative">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-slate-600 to-slate-800 flex items-center justify-center flex-shrink-0">
                                        <span class="text-sm font-bold text-white">{{ strtoupper(substr($activeChat->user->name, 0, 1)) }}</span>
                                    </div>
                                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-white"
                                          :class="userOnline ? 'bg-green-500' : 'bg-gray-400'"></span>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-bold text-gray-800 text-sm sm:text-base truncate">{{ $activeChat->user->name }}</h3>
                                    <p class="text-xs truncate" :class="userOnline ? 'text-green-500' : 'text-gray-400'"
                                       x-text="userOnline ? 'Online' : (userLastSeen ? 'Terakhir dilihat ' + userLastSeen : 'Offline')"></p>
                                </div>
                            </div>

                            {{-- Tombol pilih / hapus --}}
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <template x-if="!selectMode">
                                    <button @click="enterSelectMode"
                                            class="text-xs text-gray-500 hover:text-blue-600 px-2.5 py-1.5 rounded-lg hover:bg-blue-50 transition-colors flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        Pilih
                                    </button>
                                </template>
                                <template x-if="selectMode">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500" x-text="selectedIds.length + ' dipilih'"></span>
                                        <button @click="deleteSelected" x-show="selectedIds.length > 0"
                                                class="text-xs text-red-500 hover:text-red-700 px-2.5 py-1.5 rounded-lg hover:bg-red-50 transition-colors flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Hapus
                                        </button>
                                        <button @click="exitSelectMode"
                                                class="text-xs text-gray-500 px-2.5 py-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                            Batal
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Area Pesan --}}
                        <div class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-50 flex flex-col gap-1" id="chatbox" x-ref="chatbox">
                            <template x-for="(msg, index) in messages" :key="msg.id">
                                <div class="flex w-full group"
                                     :class="parseInt(msg.sender_id) === {{ (int) auth()->id() }} ? 'justify-end' : 'justify-start'">

                                    {{-- Select checkbox --}}
                                    <template x-if="selectMode">
                                        <div class="flex items-center mr-2 self-center">
                                            <input type="checkbox"
                                                   :checked="selectedIds.includes(msg.id)"
                                                   @change="toggleSelect(msg.id)"
                                                   class="w-4 h-4 rounded border-gray-300 text-blue-600 cursor-pointer">
                                        </div>
                                    </template>

                                    <div class="max-w-[80%] sm:max-w-[72%] relative"
                                         @click="selectMode ? toggleSelect(msg.id) : null">
                                        <div class="rounded-2xl px-3.5 py-2.5 shadow-sm"
                                             :class="[
                                                parseInt(msg.sender_id) === {{ (int) auth()->id() }}
                                                    ? 'bg-blue-600 text-white rounded-tr-sm'
                                                    : 'bg-white border border-gray-200 text-gray-800 rounded-tl-sm',
                                                selectMode && selectedIds.includes(msg.id) ? 'ring-2 ring-blue-400' : ''
                                             ]">
                                            <template x-if="msg.type === 'text'">
                                                <p class="text-sm whitespace-pre-wrap leading-relaxed" x-text="msg.content"></p>
                                            </template>
                                            <template x-if="msg.type === 'image'">
                                                <img :src="'/storage/' + msg.content" class="rounded-lg max-h-48 cursor-pointer hover:opacity-90 transition-opacity" alt="Image">
                                            </template>

                                            {{-- Waktu + read receipt --}}
                                            <div class="flex items-center mt-1 gap-1"
                                                 :class="parseInt(msg.sender_id) === {{ (int) auth()->id() }} ? 'justify-end' : 'justify-start'">
                                                <span class="text-[10px] opacity-60" x-text="formatTime(msg.created_at)"></span>
                                                <template x-if="parseInt(msg.sender_id) === {{ (int) auth()->id() }}">
                                                    <template x-if="msg.is_read">
                                                        <svg class="w-3.5 h-3.5 text-blue-200" viewBox="0 0 16 10" fill="none">
                                                            <path d="M1 5l3.5 3.5L11 1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <path d="M5 5l3.5 3.5L15 1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                    </template>
                                                    <template x-if="!msg.is_read">
                                                        <svg class="w-3.5 h-3.5 text-white/50" viewBox="0 0 12 10" fill="none">
                                                            <path d="M1 5l3.5 3.5L11 1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                    </template>
                                                </template>
                                            </div>
                                        </div>

                                        {{-- Tombol hapus (hover) --}}
                                        <template x-if="!selectMode">
                                            <div class="absolute top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity"
                                                 :class="parseInt(msg.sender_id) === {{ (int) auth()->id() }} ? '-left-8' : '-right-8'">
                                                <button @click.stop="deleteMessage(msg.id)"
                                                        class="w-6 h-6 bg-white border border-gray-200 rounded-full flex items-center justify-center shadow-sm hover:bg-red-50 hover:border-red-200 transition-colors"
                                                        title="Hapus pesan">
                                                    <svg class="w-3 h-3 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <div x-show="messages.length === 0" class="m-auto text-center text-gray-400 py-12" x-cloak>
                                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                <p class="text-sm">Belum ada pesan dari pelanggan ini.</p>
                            </div>
                        </div>

                        {{-- Input --}}
                        <div class="bg-white border-t border-gray-200 px-3 sm:px-4 py-3 flex-shrink-0" x-show="!selectMode">
                            <form @submit.prevent="sendMessage" class="flex items-end gap-2">
                                <div class="relative shrink-0">
                                    <input type="file" x-ref="imageInput" @change="handleFileChange" accept="image/*" class="hidden">
                                    <button type="button" @click="$refs.imageInput.click()"
                                            class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors focus:outline-none">
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
    window.activeChatId = {{ $activeChat->id }};
    window.activeUserId = {{ $activeChat->user->id }};
</script>
@endif

<script>
function playNotificationSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        osc.frequency.setValueAtTime(1100, ctx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.25, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
        osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.4);
    } catch(e) {}
}

function updateSidebarUnread() {
    fetch('/admin/chat/unread-counts', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.ok ? r.json() : null).then(data => {
        if (!data) return;
        data.forEach(item => {
            // Update unread badge
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

            // Update online dot in sidebar
            const link = document.querySelector(`a[data-user-id="${item.user_id ?? ''}"]`);
            // Note: user_id not in item — update via data-user-id would need extra mapping
            // Online dots handled by separate data attribute lookup below
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
        statusInterval: null,

        // Select mode
        selectMode: false,
        selectedIds: [],

        // User status
        userOnline: false,
        userLastSeen: null,

        init() {
            this.scrollToBottom();
            this.pollInterval = setInterval(() => this.fetchMessages(), 3000);
            if (window.activeUserId) {
                this.fetchUserStatus();
                this.statusInterval = setInterval(() => this.fetchUserStatus(), 30000);
            }
        },

        destroy() {
            if (this.pollInterval) clearInterval(this.pollInterval);
            if (this.statusInterval) clearInterval(this.statusInterval);
        },

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

        async fetchUserStatus() {
            try {
                const res = await fetch(`/admin/chat/user-status/${window.activeUserId}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.userOnline = data.online;
                    this.userLastSeen = data.last_seen;
                }
            } catch(e) {}
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
                        if (newMsgs.some(m => parseInt(m.sender_id) !== parseInt(window.adminUserId))) playNotificationSound();
                        // Merge + update read status
                        const msgMap = new Map(this.messages.map(m => [m.id, m]));
                        data.messages.forEach(m => msgMap.set(m.id, m));
                        this.messages = Array.from(msgMap.values()).sort((a,b) => a.id - b.id);
                        const b = this.$refs.chatbox;
                        if (b && b.scrollHeight - b.scrollTop - b.clientHeight < 120) this.scrollToBottom();
                    }
                }
            } catch(e) {}
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
        },

        // Select mode
        enterSelectMode() { this.selectMode = true; this.selectedIds = []; },
        exitSelectMode()  { this.selectMode = false; this.selectedIds = []; },
        toggleSelect(id) {
            if (this.selectedIds.includes(id)) this.selectedIds = this.selectedIds.filter(x => x !== id);
            else this.selectedIds.push(id);
        },

        async deleteMessage(id) {
            if (!confirm('Hapus pesan ini?')) return;
            try {
                const res = await fetch(`/admin/chat/message/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) this.messages = this.messages.filter(m => m.id !== id);
            } catch(e) { alert('Gagal menghapus pesan'); }
        },

        async deleteSelected() {
            if (!this.selectedIds.length) return;
            if (!confirm(`Hapus ${this.selectedIds.length} pesan?`)) return;
            try {
                const res = await fetch(`/admin/chat/${this.chatId}/messages`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ ids: this.selectedIds })
                });
                if (res.ok) {
                    const deleted = new Set(this.selectedIds);
                    this.messages = this.messages.filter(m => !deleted.has(m.id));
                    this.exitSelectMode();
                }
            } catch(e) { alert('Gagal menghapus pesan'); }
        }
    }));
});
</script>

@endsection
