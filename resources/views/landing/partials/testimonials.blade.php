@php
    $dummyTestimonials = [
        [
            'name'    => 'Budi Santoso',
            'service' => 'Custom',
            'rating'  => 5,
            'avatar'  => 'BS',
            'color'   => 'bg-blue-600',
            'comment' => 'Ukurannya pas banget! Tidak perlu bolak-balik fitting. Teknologi CV-nya benar-benar akurat. Kemeja saya jadi seperti buatan brand ternama.',
        ],
        [
            'name'    => 'Siti Rahayu',
            'service' => 'Desain',
            'rating'  => 5,
            'avatar'  => 'SR',
            'color'   => 'bg-pink-500',
            'comment' => 'Desain gaun pesta saya terwujud sempurna. Tim sangat responsif di chat dan selalu update progress. Hasilnya melebihi ekspektasi saya!',
        ],
        [
            'name'    => 'Ahmad Fauzi',
            'service' => 'Permak',
            'rating'  => 5,
            'avatar'  => 'AF',
            'color'   => 'bg-green-600',
            'comment' => 'Permak celana chino saya jadi lebih slim dan rapi. Pengerjaannya cepat, harga terjangkau. Pasti akan order lagi untuk baju yang lain.',
        ],
        [
            'name'    => 'Dewi Lestari',
            'service' => 'Custom',
            'rating'  => 5,
            'avatar'  => 'DL',
            'color'   => 'bg-purple-600',
            'comment' => 'Awalnya ragu karena tidak bisa fitting langsung, tapi ternyata hasilnya luar biasa! Fitur ukuran via foto betul-betul canggih dan akurat.',
        ],
        [
            'name'    => 'Rudi Hartono',
            'service' => 'Desain',
            'rating'  => 4,
            'avatar'  => 'RH',
            'color'   => 'bg-amber-600',
            'comment' => 'Pelayanannya sangat profesional. Notifikasi WhatsApp sangat membantu untuk pantau status. Pengiriman juga tepat waktu sesuai estimasi.',
        ],
        [
            'name'    => 'Maya Indah',
            'service' => 'Custom',
            'rating'  => 5,
            'avatar'  => 'MI',
            'color'   => 'bg-teal-600',
            'comment' => 'Sudah 3x order di ZRINTTAILOR dan tidak pernah kecewa. Kualitas jahitan rapi, bahan yang dipilih admin juga sesuai rekomendasi. Recommended!',
        ],
    ];
@endphp

<div id="testimonials" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <span class="inline-block text-blue-600 font-semibold text-sm uppercase tracking-widest mb-3">Testimoni</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-4">Apa Kata Pelanggan Kami?</h2>
            <p class="text-gray-500 max-w-xl mx-auto">Kepuasan pelanggan adalah prioritas utama kami. Berikut pengalaman nyata mereka.</p>

            <!-- Rating summary -->
            <div class="inline-flex items-center gap-2 mt-6 bg-yellow-50 border border-yellow-200 px-5 py-2.5 rounded-full">
                <div class="flex text-yellow-400">
                    @for($i=0; $i<5; $i++)
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <span class="font-bold text-gray-800 text-sm">4.9 / 5.0</span>
                <span class="text-gray-400 text-sm">dari 200+ ulasan</span>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @forelse($testimonials as $testimonial)
            <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow duration-300">
                <!-- Stars -->
                <div class="flex text-yellow-400 mb-3">
                    @for($i=0; $i<$testimonial->rating; $i++)
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-gray-600 text-sm leading-relaxed mb-5 italic">"{{ $testimonial->comment }}"</p>
                <div class="flex items-center gap-3 pt-4 border-t border-gray-50">
                    <div class="w-9 h-9 bg-blue-600 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {{ strtoupper(substr($testimonial->user->name ?? 'C', 0, 2)) }}
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 text-sm">{{ $testimonial->user->name ?? 'Customer' }}</div>
                        <div class="text-xs text-gray-400">{{ $testimonial->order->service->name ?? 'Layanan' }}</div>
                    </div>
                </div>
            </div>
            @empty
            {{-- Dummy testimonials --}}
            @foreach($dummyTestimonials as $dummy)
            <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow duration-300">
                <!-- Stars -->
                <div class="flex text-yellow-400 mb-3">
                    @for($i=0; $i<$dummy['rating']; $i++)
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-gray-600 text-sm leading-relaxed mb-5 italic">"{{ $dummy['comment'] }}"</p>
                <div class="flex items-center gap-3 pt-4 border-t border-gray-50">
                    <div class="w-9 h-9 {{ $dummy['color'] }} rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {{ $dummy['avatar'] }}
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 text-sm">{{ $dummy['name'] }}</div>
                        <div class="text-xs text-gray-400">Layanan {{ $dummy['service'] }}</div>
                    </div>
                </div>
            </div>
            @endforeach
            @endforelse
        </div>
    </div>
</div>
