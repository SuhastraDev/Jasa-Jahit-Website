@extends('layouts.admin')
@section('page-title', 'Testimoni')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kelola Testimoni</h1>
            <p class="text-gray-500 text-sm mt-0.5">Moderasi ulasan pelanggan sebelum ditampilkan di landing page.</p>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-500 bg-white border border-gray-100 px-4 py-2 rounded-xl shadow-sm self-start sm:self-auto">
            <svg class="w-4 h-4 text-yellow-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            <span><strong class="text-gray-900">{{ $testimonials->where('is_approved', true)->count() }}</strong> disetujui</span>
            <span class="text-gray-300">·</span>
            <span><strong class="text-orange-600">{{ $testimonials->where('is_approved', false)->count() }}</strong> menunggu</span>
        </div>
    </div>

    @if($testimonials->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Belum ada testimoni</h3>
            <p class="text-gray-500 text-sm">Testimoni akan muncul setelah pelanggan mengirimkan ulasan.</p>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50">
                            <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Layanan</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Rating</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Komentar</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 sm:px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($testimonials as $item)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-4 sm:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 sm:w-9 sm:h-9 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0">
                                        {{ strtoupper(substr($item->user->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-gray-900 text-sm">{{ $item->user->name }}</div>
                                        <div class="text-xs text-gray-400 truncate max-w-[120px] sm:max-w-none">{{ $item->user->email }}</div>
                                        {{-- Rating tampil mobile --}}
                                        <div class="flex items-center gap-0.5 mt-0.5 sm:hidden">
                                            @for($i=1;$i<=5;$i++)
                                                <svg class="w-3 h-3 {{ $i<=$item->rating ? 'text-yellow-400 fill-yellow-400' : 'text-gray-200 fill-gray-200' }}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4 hidden md:table-cell">
                                <span class="text-sm text-gray-700 whitespace-nowrap">{{ $item->order->service->name }}</span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 hidden sm:table-cell">
                                <div class="flex items-center gap-1">
                                    @for($i=1; $i<=5; $i++)
                                        <svg class="w-4 h-4 {{ $i <= $item->rating ? 'text-yellow-400 fill-yellow-400' : 'text-gray-200 fill-gray-200' }}" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                    <span class="text-xs text-gray-500 ml-1">{{ $item->rating }}/5</span>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4 hidden lg:table-cell max-w-xs">
                                <p class="text-sm text-gray-600 italic line-clamp-2">"{{ $item->comment }}"</p>
                            </td>
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                @if($item->is_approved)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-700 border border-green-200 text-xs font-semibold rounded-full">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                        <span class="hidden sm:inline">Disetujui</span>
                                        <span class="sm:hidden">OK</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 text-orange-700 border border-orange-200 text-xs font-semibold rounded-full">
                                        <span class="w-1.5 h-1.5 bg-orange-400 rounded-full animate-pulse"></span>
                                        <span class="hidden sm:inline">Menunggu</span>
                                        <span class="sm:hidden">Pending</span>
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if(!$item->is_approved)
                                        <form action="{{ route('admin.testimonials.update', $item->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="p-1.5 sm:px-3 sm:py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors sm:inline-flex sm:items-center sm:gap-1 sm:text-xs sm:font-semibold" title="Setujui">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                <span class="hidden sm:inline">Setujui</span>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.testimonials.destroy', $item->id) }}" method="POST"
                                          onsubmit="return confirm('Hapus testimoni dari {{ addslashes($item->user->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 sm:px-3 sm:py-1.5 border border-red-200 text-red-600 hover:bg-red-50 rounded-lg transition-colors sm:inline-flex sm:items-center sm:gap-1 sm:text-xs sm:font-semibold" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            <span class="hidden sm:inline">Hapus</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
