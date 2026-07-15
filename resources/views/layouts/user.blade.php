<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ZRINTTAILOR') }} — @yield('page-title', 'Dashboard')</title>
    @include('partials.seo-meta')
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="font-sans antialiased bg-gray-50" x-data="{ sidebarOpen: false }">

    <!-- Mobile backdrop -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen=false"
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-20 lg:hidden"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
    </div>

    <!-- ── Sidebar ── -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-100 flex flex-col z-30 transition-transform duration-300 ease-in-out shadow-sm"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

        <!-- Logo -->
        <div class="px-5 py-5 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
            <a href="{{ route('landing') }}" class="flex items-center gap-2.5 group">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center shadow-sm group-hover:bg-blue-700 transition-colors">
                    <svg style="width:18px;height:18px" class="text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z" />
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-800 tracking-tight">ZRINT<span style="color:#2563eb;">TAILOR</span></span>
            </a>
            <button @click="sidebarOpen=false" class="lg:hidden text-gray-400 hover:text-gray-600 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        @php
        $navUnreadChat = 0;
        $navActionOrders = 0;
        if (auth()->check()) {
        $navChatObj = \App\Models\Chat::where('user_id', auth()->id())->first();
        $navUnreadChat = $navChatObj
        ? $navChatObj->messages()->where('sender_id', '!=', auth()->id())->where('is_read', false)->count()
        : 0;
        // Pesanan yang butuh aksi user: confirmed (perlu bayar) atau shipped (perlu konfirmasi terima)
        $navActionOrders = \App\Models\Order::where('user_id', auth()->id())
        ->whereIn('status', ['confirmed', 'shipped'])->count();
        }
        @endphp
        <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-0.5">

            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>

            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest px-3 mb-1.5">Pesanan</p>
            </div>

            <a href="{{ route('user.orders.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('user.orders.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span class="flex-1">Pesanan Saya</span>
                @if($navActionOrders > 0)
                <span class="ml-auto min-w-[20px] h-5 px-1.5 bg-blue-500 text-white text-xs font-bold rounded-full flex items-center justify-center leading-none">{{ $navActionOrders }}</span>
                @endif
            </a>

            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest px-3 mb-1.5">Komunikasi</p>
            </div>

            <a href="{{ route('user.chat.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('user.chat.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span class="flex-1">Chat Admin</span>
                @if($navUnreadChat > 0)
                <span id="nav-chat-badge-user" class="ml-auto min-w-[20px] h-5 px-1.5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center leading-none">{{ $navUnreadChat }}</span>
                @else
                <span id="nav-chat-badge-user" class="hidden ml-auto min-w-[20px] h-5 px-1.5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center leading-none"></span>
                @endif
            </a>

            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest px-3 mb-1.5">Ukur Badan</p>
            </div>

            <a href="{{ route('user.measurement.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('user.measurement.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Ukur Badan (CV)
            </a>

            <div class="pt-4 border-t border-gray-100 mt-2">
                <a href="{{ route('landing') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    Kembali ke Beranda
                </a>
            </div>
        </nav>

        <!-- User info -->
        <div class="px-3 py-4 border-t border-gray-100 flex-shrink-0">
            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-gray-50 border border-gray-100">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-gray-800 text-sm font-semibold truncate">{{ auth()->user()->name }}</div>
                    <div class="text-gray-400 text-xs truncate">{{ auth()->user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Logout" class="text-gray-400 hover:text-red-500 transition-colors p-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- ── Main Content ── -->
    <div class="lg:pl-64 min-h-screen flex flex-col">

        <!-- Top Header Bar -->
        <header class="bg-white border-b border-gray-100 h-16 flex items-center px-4 sm:px-6 sticky top-0 z-10 gap-4">
            <button @click="sidebarOpen=true" class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <div class="flex-1">
                <span class="text-gray-900 font-semibold text-base">@yield('page-title', 'Dashboard')</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('user.orders.create') }}"
                    class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-semibold hover:bg-blue-700 transition-colors shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Pesan Sekarang
                </a>
                <div class="relative" x-data="{ open: false }">
                    <button @click="open=!open" class="flex items-center gap-2 px-2.5 py-1.5 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="w-7 h-7 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <span class="text-sm font-medium text-gray-700 hidden sm:block">{{ auth()->user()->name }}</span>
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open=false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50">
                        <div class="px-3 py-2 border-b border-gray-50">
                            <p class="text-xs font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Edit Profil
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 w-full text-left transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash messages -->
        @if(session('success'))
        <div class="mx-4 sm:mx-6 mt-4">
            <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        </div>
        @endif
        @if(session('error'))
        <div class="mx-4 sm:mx-6 mt-4">
            <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
                <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('error') }}
            </div>
        </div>
        @endif

        <!-- Page Content -->
        <main class="flex-1">
            @yield('content')
        </main>
    </div>

    @stack('scripts')

    {{-- Global chat notification --}}
    @auth
    <script>
        (function() {
            var lastUnread = 0;
            var isChatPage = window.location.pathname.startsWith('/chat');

            function playNotifSound() {
                try {
                    var ctx = new(window.AudioContext || window.webkitAudioContext)();
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, ctx.currentTime);
                    osc.frequency.setValueAtTime(1100, ctx.currentTime + 0.1);
                    gain.gain.setValueAtTime(0.25, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.4);
                } catch (e) {}
            }

            function checkUnread() {
                if (window.location.pathname.startsWith('/chat')) return;
                fetch('/chat/unread-count', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(r) {
                        return r.ok ? r.json() : null;
                    })
                    .then(function(data) {
                        if (!data) return;
                        if (data.unread > lastUnread) playNotifSound();
                        lastUnread = data.unread;
                        // Update sidebar chat badge
                        var badge = document.getElementById('nav-chat-badge-user');
                        if (badge) {
                            if (data.unread > 0) {
                                badge.textContent = data.unread > 99 ? '99+' : data.unread;
                                badge.classList.remove('hidden');
                            } else {
                                badge.classList.add('hidden');
                            }
                        }
                    }).catch(function() {});
            }
            setInterval(checkUnread, 5000);
        })();
    </script>
    @endauth
</body>

</html>
