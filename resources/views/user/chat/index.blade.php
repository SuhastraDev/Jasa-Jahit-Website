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
         x-data="chatApp({{ $chat->id }}, {{ auth()->id() }})">

        {{-- Header --}}
        <div class="bg-white border-b border-gray-100 px-5 py-4 flex items-center gap-3 flex-shrink-0">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                ZT
            </div>
            <div>
                <h3 class="font-bold text-gray-800 text-sm">Admin ZrintTailor</h3>
                <p class="text-xs text-green-500 font-medium flex items-center gap-1">
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full inline-block"></span>
                    Online
                </p>
            </div>
        </div>

        {{-- Area Pesan --}}
        <div class="flex-1 overflow-y-auto p-5 bg-gray-50/50 flex flex-col gap-3" id="chatbox" x-ref="chatbox">
            <template x-for="msg in messages" :key="msg.id">
                <div class="flex w-full" :class="parseInt(msg.sender_id) === userId ? 'justify-end' : 'justify-start'">
                    <template x-if="parseInt(msg.sender_id) !== userId">
                        <div class="w-7 h-7 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-xs font-bold mr-2 flex-shrink-0 self-end mb-1">ZT</div>
                    </template>

                    <div class="max-w-[72%] rounded-2xl px-4 py-2.5 shadow-sm relative"
                         :class="parseInt(msg.sender_id) === userId
                            ? 'bg-blue-600 text-white rounded-br-sm'
                            : 'bg-white border border-gray-100 text-gray-800 rounded-bl-sm'">

                        <template x-if="msg.type === 'text'">
                            <p class="text-sm whitespace-pre-wrap leading-relaxed" x-text="msg.content"></p>
                        </template>

                        <template x-if="msg.type === 'image'">
                            <div class="mt-1 mb-1">
                                <img :src="'/storage/' + msg.content" class="rounded-xl max-h-52 cursor-pointer hover:opacity-90 transition-opacity" alt="Image">
                            </div>
                        </template>

                        <div class="flex items-center justify-end gap-1 mt-1">
                            <span class="text-[10px] opacity-60" x-text="formatTime(msg.created_at)"></span>
                            <template x-if="parseInt(msg.sender_id) === userId">
                                <svg class="w-3 h-3" :class="msg.is_read ? 'text-blue-200' : 'text-white/40'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </div>
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
        <div class="bg-white border-t border-gray-100 px-4 py-3 flex-shrink-0">
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
        Alpine.data('chatApp', (chatId, currentUserId) => ({
            chatId: chatId,
            userId: parseInt(currentUserId),
            messages: window.initialMessages || [],
            newMessage: '',
            selectedFile: null,
            filePreview: null,
            isSending: false,
            pollInterval: null,

            init() {
                this.scrollToBottom();
                this.pollInterval = setInterval(() => {
                    this.fetchMessages();
                }, 3000);
            },

            destroy() {
                if (this.pollInterval) clearInterval(this.pollInterval);
            },

            scrollToBottom() {
                setTimeout(() => {
                    const box = this.$refs.chatbox;
                    if (box) box.scrollTop = box.scrollHeight;
                }, 50);
            },

            formatTime(dateString) {
                const date = new Date(dateString);
                return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
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
                            this.messages = [
                                ...this.messages.filter(m => !data.messages.map(x=>x.id).includes(m.id)),
                                ...data.messages
                            ];
                            const box = this.$refs.chatbox;
                            const isNearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 100;
                            if (isNearBottom) this.scrollToBottom();
                        }
                    }
                } catch (error) {
                    console.error('Polling error:', error);
                }
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
                            this.fetchMessages();
                            this.scrollToBottom();
                        }
                    }
                } catch (error) {
                    alert('Gagal mengirim pesan');
                } finally {
                    this.isSending = false;
                }
            }
        }));
    });
</script>
@endpush
@endsection
