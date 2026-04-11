@extends('layouts.user')
@section('page-title', 'Beri Rating')
@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6">
        <a href="{{ route('user.orders.show', $order) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Pesanan
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

        {{-- Header Card --}}
        <div class="bg-gradient-to-br from-yellow-400 to-orange-500 px-6 pt-8 pb-10 text-center">
            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-9 h-9 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </div>
            <h1 class="text-xl font-bold text-white mb-1">Bagaimana pengalaman Anda?</h1>
            <p class="text-yellow-100 text-sm">Pesanan <strong class="font-mono">{{ $order->order_code }}</strong></p>
        </div>

        <div class="px-6 pt-6 pb-8 -mt-2 relative z-10">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <form action="{{ route('user.testimonials.store', $order) }}" method="POST">
                    @csrf

                    {{-- Star Rating --}}
                    <div class="mb-7" x-data="{ rating: 0, hover: 0 }">
                        <label class="block text-sm font-semibold text-gray-700 mb-4 text-center">Pilih Rating</label>
                        <div class="flex justify-center gap-2 mb-3">
                            @for($i = 1; $i <= 5; $i++)
                            <button type="button"
                                    @click="rating = {{ $i }}"
                                    @mouseover="hover = {{ $i }}"
                                    @mouseleave="hover = 0"
                                    class="focus:outline-none transition-all duration-150 hover:scale-125 active:scale-110">
                                <svg class="w-11 h-11 transition-colors duration-150"
                                     :class="(hover || rating) >= {{ $i }} ? 'text-yellow-400 drop-shadow-sm' : 'text-gray-200'"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </button>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" :value="rating" required>
                        <p class="text-center text-sm font-bold transition-all"
                           :class="rating > 0 ? 'text-yellow-600' : 'text-gray-400'"
                           x-text="rating > 0 ? ['', 'Buruk 😞', 'Kurang 😐', 'Cukup 🙂', 'Bagus 😊', 'Sangat Bagus! 🤩'][rating] : 'Pilih rating bintang'">
                        </p>
                        @error('rating')
                            <p class="text-red-500 text-xs text-center mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Komentar --}}
                    <div class="mb-6">
                        <label for="comment" class="block text-sm font-semibold text-gray-700 mb-1.5">Ceritakan pengalaman Anda <span class="text-gray-400 font-normal">(opsional)</span></label>
                        <textarea name="comment" id="comment" rows="4"
                                  class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 resize-none transition-colors"
                                  placeholder="Jahitannya rapi, pengiriman cepat, puas banget!"
                                  maxlength="500">{{ old('comment') }}</textarea>
                        @error('comment')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 px-6 bg-yellow-500 text-white rounded-xl font-bold text-sm hover:bg-yellow-600 transition-colors shadow-sm flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        Kirim Rating
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
