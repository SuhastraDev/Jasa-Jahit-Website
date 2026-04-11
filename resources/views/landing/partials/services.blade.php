@php
    $serviceIcons = [
        'Permak' => [
            'icon' => '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>',
            'bg'   => 'bg-green-100',
            'text' => 'text-green-600',
            'badge'=> 'bg-green-50 text-green-700 border-green-200',
            'btn'  => 'bg-green-600 hover:bg-green-700',
            'border'=> 'hover:border-green-200',
        ],
        'Desain' => [
            'icon' => '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>',
            'bg'   => 'bg-purple-100',
            'text' => 'text-purple-600',
            'badge'=> 'bg-purple-50 text-purple-700 border-purple-200',
            'btn'  => 'bg-purple-600 hover:bg-purple-700',
            'border'=> 'hover:border-purple-200',
        ],
        'Custom' => [
            'icon' => '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>',
            'bg'   => 'bg-blue-100',
            'text' => 'text-blue-600',
            'badge'=> 'bg-blue-50 text-blue-700 border-blue-200',
            'btn'  => 'bg-blue-600 hover:bg-blue-700',
            'border'=> 'hover:border-blue-200',
        ],
    ];
    $defaultIcon = [
        'icon' => '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>',
        'bg'   => 'bg-gray-100',
        'text' => 'text-gray-600',
        'badge'=> 'bg-gray-50 text-gray-700 border-gray-200',
        'btn'  => 'bg-gray-700 hover:bg-gray-800',
        'border'=> 'hover:border-gray-200',
    ];
@endphp

<div id="services" class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <span class="inline-block text-blue-600 font-semibold text-sm uppercase tracking-widest mb-3">Layanan</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-4">Pilih Layanan Yang Tepat</h2>
            <p class="text-gray-500 max-w-xl mx-auto">Dari perbaikan sederhana hingga pakaian kustom penuh kami siap melayani setiap kebutuhan jahit Anda.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            @foreach($services as $service)
            @php
                $style = $serviceIcons[$service->name] ?? $defaultIcon;
            @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 flex flex-col transition-all duration-300 hover:shadow-lg hover:-translate-y-1 {{ $style['border'] }}">
                <!-- Icon -->
                <div class="w-14 h-14 {{ $style['bg'] }} {{ $style['text'] }} rounded-2xl flex items-center justify-center mb-6">
                    {!! $style['icon'] !!}
                </div>

                <!-- Badge -->
                <div class="mb-4">
                    <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full border {{ $style['badge'] }}">{{ $service->name }}</span>
                </div>

                <h3 class="text-xl font-bold text-gray-900 mb-3">{{ $service->name }}</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-6 flex-1">{{ $service->description }}</p>

                <!-- Price & Duration -->
                <div class="flex items-center justify-between mb-6 py-4 border-t border-b border-gray-100">
                    <div>
                        <div class="text-xs text-gray-400 mb-0.5">Mulai dari</div>
                        <div class="text-xl font-bold text-gray-900">Rp {{ number_format($service->base_price, 0, ',', '.') }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-400 mb-0.5">Estimasi</div>
                        <div class="text-sm font-semibold text-gray-700">{{ $service->estimated_days }} hari</div>
                    </div>
                </div>

                <div class="space-y-2.5">
                    <a href="{{ route('register') }}" class="block w-full text-center text-white py-3 px-4 rounded-xl font-semibold transition-colors {{ $style['btn'] }}">Pesan Sekarang</a>
                    @if(in_array($service->name, ['Desain', 'Custom']))
                        <a href="{{ route('catalogs.public', ['service' => $service->id]) }}" class="block w-full text-center border border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50 py-3 px-4 rounded-xl font-medium transition-colors text-sm">Lihat Referensi</a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
