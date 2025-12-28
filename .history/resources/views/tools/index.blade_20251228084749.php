<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Inventaris Alat') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- 1. PESAN SUKSES --}}
            @if (session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 shadow-sm" role="alert">
                    <p class="font-bold">Berhasil!</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            {{-- 2. KONTAINER UTAMA --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- A. BAGIAN FILTER & SEARCH --}}
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                        
                        {{-- Form Filter --}}
                        <form action="{{ route('tools.index') }}" method="GET" class="flex flex-col md:flex-row gap-2 w-full md:w-auto items-center">
                            
                            {{-- Input Search --}}
                            <input type="text" name="search" value="{{ request('search') }}" 
                                class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm w-full md:w-auto"
                                placeholder="Cari Nama / Kode...">

                            {{-- Dropdown Status Ketersediaan --}}
                            <select name="status" class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm w-full md:w-auto">
                                <option value="">- Semua Status -</option>
                                <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Tersedia</option>
                                <option value="borrowed" {{ request('status') == 'borrowed' ? 'selected' : '' }}>Dipinjam</option>
                                <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="missing" {{ request('status') == 'missing' ? 'selected' : '' }}>Hilang/Rusak</option>
                            </select>

                            {{-- Dropdown Kategori --}}
                            <select name="category_id" class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm w-full md:w-auto">
                                <option value="all">- Semua Kategori -</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->category_name }}
                                    </option>
                                @endforeach
                            </select>

                            {{-- Tombol Filter --}}
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm w-full md:w-auto">
                                Filter
                            </button>

                            {{-- Tombol Reset --}}
                            @if(request('search') || request('status') || request('category_id'))
                                <a href="{{ route('tools.index') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700 text-sm border border-gray-300 rounded-md text-center w-full md:w-auto">
                                    Reset
                                </a>
                            @endif
                        </form>

                        {{-- LOGIKA 1: Sembunyikan Tombol Tambah jika role = kepala --}}
                        @if(auth()->user()->role !== 'kepala')
                            <a href="{{ route('tools.create') }}" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow-sm text-center text-sm">
                                + Tambah Alat
                            </a>
                        @endif
                    </div>

                    {{-- B. TABEL DATA --}}
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full table-auto text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600 uppercase font-medium">
                                <tr>
                                    <th class="px-4 py-3 text-center">No</th>
                                    <th class="px-4 py-3">Kode</th>
                                    <th class="px-4 py-3">Nama Alat</th>
                                    <th class="px-4 py-3">Kategori</th>
                                    <th class="px-4 py-3 text-center">Kondisi</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    
                                    {{-- LOGIKA 2: Sembunyikan Header Kolom Aksi jika role = kepala --}}
                                    @if(auth()->user()->role !== 'kepala')
                                        <th class="px-4 py-3 text-center">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($tools as $index => $tool)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-center font-medium text-gray-500">
                                            {{ $tools->firstItem() + $index }}
                                        </td>
                                        
                                        <td class="px-4 py-3 font-mono text-xs text-gray-500">
                                            {{ $tool->tool_code }}
                                        </td>

                                        <td class="px-4 py-3 font-bold text-gray-800">
                                            {{ $tool->tool_name }}
                                        </td>

                                        <td class="px-4 py-3">
                                            <span class="bg-gray-100 text-gray-600 py-1 px-2 rounded text-xs">
                                                {{ $tool->category->category_name ?? '-' }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            <span class="
                                                px-2 py-1 rounded text-xs font-semibold
                                                {{ $tool->current_condition == 'Baik' ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50' }}
                                            ">
                                                {{ $tool->current_condition }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            @if($tool->availability_status == 'available')
                                                <span class="bg-green-100 text-green-800 py-1 px-2 rounded-full text-xs font-bold border border-green-200">
                                                    Tersedia
                                                </span>
                                            @elseif($tool->availability_status == 'borrowed')
                                                <span class="bg-yellow-100 text-yellow-800 py-1 px-2 rounded-full text-xs font-bold border border-yellow-200">
                                                    Dipinjam
                                                </span>
                                            @elseif($tool->availability_status == 'maintenance')
                                                <span class="bg-gray-100 text-gray-800 py-1 px-2 rounded-full text-xs font-bold border border-gray-200">
                                                    Maintenance
                                                </span>
                                            @else 
                                                 <span class="bg-red-100 text-red-800 py-1 px-2 rounded-full text-xs font-bold border border-red-200">
                                                    {{ ucfirst($tool->availability_status) }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- LOGIKA 3: Sembunyikan Tombol Edit & Hapus jika role = kepala --}}
                                        @if(auth()->user()->role !== 'kepala')
                                            <td class="px-4 py-3 text-center">
                                                <div class="flex justify-center items-center space-x-2">
                                                    {{-- Tombol Edit --}}
                                                    <a href="{{ route('tools.edit', $tool->id) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-1 rounded">
                                                        Edit
                                                    </a>
                                                    
                                                    {{-- Tombol Hapus --}}
                                                    <form action="{{ route('tools.destroy', $tool->id) }}" method="POST" onsubmit="return confirm('Yakin hapus alat ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 p-1 rounded">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        {{-- Sesuaikan colspan agar rapi. Jika admin 7 kolom, jika kepala 6 kolom --}}
                                        <td colspan="{{ auth()->user()->role !== 'kepala' ? '7' : '6' }}" class="text-center py-8 text-gray-400">
                                            Data alat tidak ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- C. PAGINATION --}}
                    <div class="mt-6">
                        {{ $tools->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>