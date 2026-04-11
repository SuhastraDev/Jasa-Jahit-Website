@extends('layouts.public')

@section('content')
<div class="bg-gray-100 py-12">
    <div class="container mx-auto px-6">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Katalog Desain</h2>
        
        <!-- Filters -->
        <div class="flex justify-center space-x-4 mb-8">
            <a href="{{ route('catalogs.public') }}" class="px-4 py-2 rounded-full {{ request('service') == '' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 bg-white hover:bg-gray-200' }} border border-gray-300">Semua</a>
            @foreach($services as $service)
                <a href="{{ route('catalogs.public', ['service' => $service->id]) }}" class="px-4 py-2 rounded-full {{ request('service') == $service->id ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-200' }} border border-gray-300">{{ $service->name }}</a>
            @endforeach
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse($catalogs as $catalog)
                <div class="bg-white rounded-lg shadow-md overflow-hidden transition-transform transform hover:-translate-y-2">
                    @if($catalog->image_path)
                        <img class="w-full h-64 object-cover" src="{{ Storage::url($catalog->image_path) }}" alt="{{ $catalog->name }}">
                    @else
                        <div class="w-full h-64 bg-gray-200 flex items-center justify-center text-gray-400 text-sm">Belum ada gambar</div>
                    @endif
                    <div class="p-4">
                        <span class="text-xs text-blue-500 font-semibold uppercase">{{ $catalog->service->name }}</span>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">{{ $catalog->name }}</h3>
                        <p class="text-gray-600 mt-2 text-sm">{{ Str::limit($catalog->description, 80) }}</p>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500">Belum ada katalog desain tersedia.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-8 flex justify-center">
            {{ $catalogs->links() }}
        </div>
    </div>
</div>
@endsection

