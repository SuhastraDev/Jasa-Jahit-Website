@extends('layouts.user')
@section('page-title', 'Chat Admin')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-5">
        <h1 class="text-xl font-bold text-gray-900">Konsultasi Chat</h1>
        <p class="text-sm text-gray-500 mt-0.5">Tanyakan seputar pesanan langsung ke admin kami.</p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col"
         style="height: calc(100vh - 220px); min-height: 480px; max-height: 700px;"
         x-data="chatApp({{ $chat->id }}, {{ auth()->id() }}, {{ $adminOnline ? 'true' : 'false' }}, {{ $adminLastSeen ? json_encode($adminLastSeen) : 'null' }})">

        {{-- Header --}}
        <div class="bg-white border-b border-gray-100 px-5 py-3 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        ZT
                    </div>
                    <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white"
                          :class="adminOnline ? 'bg-green-500' : 'bg-gray-400'"></span>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">Admin ZrintTailor</h3>
                    <p class="text-xs font-medium flex items-center gap-1"
                       :class="adminOnline ? 'text-green-500' : 'text-gray-400'">
                        <span x-text="adminOnline ? 'Online' : (adminLastSeen ? 'Terakhir dilihat ' + adminLastSeen : 'Offline')"></span>
                    </p>
                </div>
            </div>

            {{-- Tombol pilih / batal pilih --}}
            <div class="flex items-center gap-2">
                <template x-if="!selectMode">
                    <div class="flex items-center gap-1.5">
                        <button @click="enterSelectMode"
                                class="text-xs text-gray-500 hover:text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition-colors flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Pilih
                        </button>
                        <button @click="deleteAllMyMessages" x-show="messages.filter(m => parseInt(m.sender_id) === userId).length > 0"
                                class="text-xs text-red-400 hover:text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-50 transition-colors flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus Semua
                        </button>
                    </div>
                </template>
                <template x-if="selectMode">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500" x-text="selectedIds.length + ' dipilih'"></span>
                        <button @click="deleteSelected"
                                x-show="selectedIds.length > 0"
                                class="text-xs text-red-500 hover:text-red-700 px-3 py-1.5 rounded-lg hover:bg-red-50 transition-colors flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus
                        </button>
                        <button @click="exitSelectMode"
                                class="text-xs text-gray-500 hover:text-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                            Batal
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Area Pesan --}}
        <div class="flex-1 overflow-y-auto p-5 bg-gray-50/50 flex flex-col gap-1" id="chatbox" x-ref="chatbox">
            <template x-for="(msg, index) in messages" :key="msg.id">
                <div class="flex w-full group"
                     :class="parseInt(msg.sender_id) === userId ? 'justify-end' : 'justify-start'"
                     :style="showDateSeparator(index) ? 'margin-top: 16px' : ''">

                    {{-- Date separator --}}
                    <template x-if="showDateSeparator(index)">
                        <div class="w-full flex justify-center mb-2 absolute" style="pointer-events:none">
                            {{-- handled below --}}
                        </div>
                    </template>

                    {{-- Avatar admin --}}
                    <template x-if="parseInt(msg.sender_id) !== userId">
                        <div class="w-7 h-7 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-xs font-bold mr-2 flex-shrink-0 self-end mb-1">ZT</div>
                    </template>

                    {{-- Select checkbox --}}
                    <template x-if="selectMode && parseInt(msg.sender_id) === userId">
                        <div class="flex items-center mr-2 self-center">
                            <input type="checkbox"
                                   :checked="selectedIds.includes(msg.id)"
                                   @change="toggleSelect(msg.id)"
                                   class="w-4 h-4 rounded border-gray-300 text-blue-600 cursor-pointer">
                        </div>
                    </template>

                    <div class="max-w-[72%] relative"
                         @click="selectMode && parseInt(msg.sender_id) === userId ? toggleSelect(msg.id) : null">

                        {{-- Bubble --}}
                        <div class="rounded-2xl px-4 py-2.5 shadow-sm relative"
                             :class="[
                                parseInt(msg.sender_id) === userId
                                    ? 'bg-blue-600 text-white rounded-br-sm'
                                    : 'bg-white border border-gray-100 text-gray-800 rounded-bl-sm',
                                selectMode && parseInt(msg.sender_id) === userId && selectedIds.includes(msg.id) ? 'ring-2 ring-blue-400' : ''
                             ]">

                            <template x-if="msg.type === 'text'">
                                <p class="text-sm whitespace-pre-wrap leading-relaxed" x-text="msg.content"></p>
                            </template>

                            <template x-if="msg.type === 'image'">
                                <div class="mt-1 mb-1">
                                    <img :src="'/storage/' + msg.content" class="rounded-xl max-h-52 cursor-pointer hover:opacity-90 transition-opacity" alt="Image">
                                </div>
                            </template>

                            {{-- Waktu + read receipt --}}
                            <div class="flex items-center justify-end gap-1 mt-1">
                                <span class="text-[10px] opacity-60" x-text="msg.formatted_time"></span>
                                <template x-if="parseInt(msg.sender_id) === userId">
                                    {{-- Double check = dibaca, single = terkirim --}}
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

                        {{-- Context menu (hapus) - hanya muncul di pesan sendiri saat hover --}}
                        <template x-if="!selectMode && parseInt(msg.sender_id) === userId">
                            <div class="absolute -left-8 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity">
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
                <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <p class="text-sm font-medium text-gray-500">Belum ada obrolan</p>
                <p class="text-xs mt-1">Kirim pesan untuk memulai konsultasi.</p>
            </div>
        </div>

        {{-- Form Input --}}
        <div class="bg-white border-t border-gray-100 px-4 py-3 flex-shrink-0" x-show="!selectMode">
            <form @submit.prevent="sendMessage" class="flex items-end gap-2">

                {{-- Lampiran --}}
                <div class="relative flex-shrink-0">
                    <input type="file" x-ref="imageInput" @change="handleFileChange" accept="image/*" class="hidden">
                    <button type="button" @click="$refs.imageInput.click()"
                            class="p-2.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors focus:outline-none" title="Lampirkan Gambar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    </button>
                    <div x-show="selectedFile" class="absolute bottom-12 left-0 bg-white border border-gray-200 rounded-xl p-2 shadow-lg flex items-center gap-2 min-w-[150px] z-10" x-cloak>
                        <div class="w-9 h-9 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                            <img :src="filePreview" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1 truncate text-xs text-gray-600" x-text="selectedFile?.name"></div>
                        <button type="button" @click="resetFile" class="text-red-400 hover:text-red-600 p-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Input Teks --}}
                <div class="flex-1 bg-gray-100 rounded-2xl px-4 py-2 flex items-center">
                    <textarea x-model="newMessage" @keydown.enter.prevent="sendMessage" rows="1"
                              class="w-full bg-transparent border-0 focus:ring-0 resize-none px-0 text-sm py-1"
                              placeholder="Ketik pesan..." style="min-height: 36px; max-height: 100px;"></textarea>
                </div>

                {{-- Kirim --}}
                <button type="submit" :disabled="isSending || (!newMessage.trim() && !selectedFile)"
                        class="p-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0 shadow-sm">
                    <template x-if="!isSending">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/></svg>
                    </template>
                    <template x-if="isSending">
                        <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </template>
                </button>
            </form>
        </div>

    </div>
</div>

@push('scripts')
<script>
    window.initialMessages = @json($chat->messages()->orderBy('created_at', 'asc')->get());
</script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('chatApp', (chatId, currentUserId, initAdminOnline, initAdminLastSeen) => ({
            chatId: chatId,
            userId: parseInt(currentUserId),
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

            // Admin status — seeded from PHP for no flicker
            adminOnline: initAdminOnline,
            adminLastSeen: initAdminLastSeen,

            init() {
                this.scrollToBottom();
                this.pollInterval = setInterval(() => this.fetchMessages(), 3000);
                this.fetchAdminStatus();
                this.statusInterval = setInterval(() => this.fetchAdminStatus(), 15000);
            },

            destroy() {
                if (this.pollInterval) clearInterval(this.pollInterval);
                if (this.statusInterval) clearInterval(this.statusInterval);
            },

            scrollToBottom() {
                setTimeout(() => {
                    const box = this.$refs.chatbox;
                    if (box) box.scrollTop = box.scrollHeight;
                }, 50);
            },

            showDateSeparator(index) {
                if (index === 0) return true;
                const prev = new Date(this.messages[index - 1].created_at).toDateString();
                const curr = new Date(this.messages[index].created_at).toDateString();
                return prev !== curr;
            },

            handleFileChange(event) {
                const file = event.target.files[0];
                if (file) {
                    this.selectedFile = file;
                    this.filePreview = URL.createObjectURL(file);
                }
            },

            resetFile() {
                this.selectedFile = null;
                this.filePreview = null;
                this.$refs.imageInput.value = '';
            },

            playSound() {
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
            },

            async fetchAdminStatus() {
                try {
                    const res = await fetch('/chat/admin-status', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.adminOnline = data.online;
                        this.adminLastSeen = data.last_seen;
                    }
                } catch(e) {}
            },

            async fetchMessages() {
                const lastId = this.messages.length > 0 ? this.messages[this.messages.length - 1].id : 0;
                try {
                    const response = await fetch(`/chat/poll?last_id=${lastId}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success && data.messages.length > 0) {
                            const existingIds = new Set(this.messages.map(m => m.id));
                            const newMsgs = data.messages.filter(m => !existingIds.has(m.id));
                            if (newMsgs.some(m => parseInt(m.sender_id) !== this.userId)) this.playSound();
                            // Merge: update existing (is_read may change) + add new
                            const msgMap = new Map(this.messages.map(m => [m.id, m]));
                            data.messages.forEach(m => msgMap.set(m.id, m));
                            this.messages = Array.from(msgMap.values()).sort((a,b) => a.id - b.id);
                            const box = this.$refs.chatbox;
                            const isNearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 100;
                            if (isNearBottom || newMsgs.length > 0) this.scrollToBottom();
                        }
                    }
                } catch (error) {}
            },

            async sendMessage() {
                if (!this.newMessage.trim() && !this.selectedFile || this.isSending) return;
                this.isSending = true;
                let formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                if (this.newMessage.trim()) formData.append('content', this.newMessage.trim());
                if (this.selectedFile) formData.append('image', this.selectedFile);

                try {
                    const response = await fetch(`/chat/send`, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: formData
                    });
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            this.newMessage = '';
                            this.resetFile();
                            await this.fetchMessages();
                            this.scrollToBottom();
                        }
                    }
                } catch (error) {
                    alert('Gagal mengirim pesan');
                } finally {
                    this.isSending = false;
                }
            },

            // === Select mode ===
            enterSelectMode() {
                this.selectMode = true;
                this.selectedIds = [];
            },
            exitSelectMode() {
                this.selectMode = false;
                this.selectedIds = [];
            },
            toggleSelect(id) {
                if (this.selectedIds.includes(id)) {
                    this.selectedIds = this.selectedIds.filter(x => x !== id);
                } else {
                    this.selectedIds.push(id);
                }
            },

            // === Delete single message ===
            async deleteMessage(id) {
                if (!confirm('Hapus pesan ini?')) return;
                try {
                    const res = await fetch(`/chat/message/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (res.ok) {
                        this.messages = this.messages.filter(m => m.id !== id);
                    }
                } catch(e) { alert('Gagal menghapus pesan'); }
            },

            // === Delete selected messages ===
            async deleteSelected() {
                if (this.selectedIds.length === 0) return;
                if (!confirm(`Hapus ${this.selectedIds.length} pesan?`)) return;
                try {
                    const res = await fetch('/chat/messages', {
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
            },

            // === Hapus semua pesan milik sendiri ===
            async deleteAllMyMessages() {
                const myIds = this.messages.filter(m => parseInt(m.sender_id) === this.userId).map(m => m.id);
                if (myIds.length === 0) return;
                if (!confirm(`Hapus semua ${myIds.length} pesan Anda?`)) return;
                try {
                    const res = await fetch('/chat/messages', {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ ids: myIds })
                    });
                    if (res.ok) {
                        this.messages = this.messages.filter(m => parseInt(m.sender_id) !== this.userId);
                    }
                } catch(e) { alert('Gagal menghapus pesan'); }
            }
        }));
    });
</script>
@endpush
@endsection
