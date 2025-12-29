<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inventaris Tak Terpakai') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Alert Success --}}
            @if(session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    {{-- HEADER: PENCARIAN & FILTER --}}
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                        
                        {{-- Form Filter --}}
                        <form action="{{ route('tools.trash') }}" method="GET" class="flex flex-col md:flex-row gap-2 w-full md:w-auto items-center">
                            
                            {{-- Input Search --}}
                            <input type="text" name="search" value="{{ request('search') }}" 
                                class="border-gray-300 rounded-md shadow-sm text-sm w-full md:w-64 focus:ring-red-500 focus:border-red-500"
                                placeholder="Cari Nama / Kode...">

                            {{-- Dropdown Kategori --}}
                            <select name="category_id" class="border-gray-300 rounded-md shadow-sm text-sm w-full md:w-auto focus:ring-red-500 focus:border-red-500">
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

                            {{-- Tombol Reset (Muncul jika sedang memfilter) --}}
                            @if(request('search') || (request('category_id') && request('category_id') != 'all'))
                                <a href="{{ route('tools.trash') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700 text-sm border border-gray-300 rounded-md text-center w-full md:w-auto">
                                    Reset
                                </a>
                            @endif
                        </form>

                        {{-- Link Kembali --}}
                        <a href="{{ route('tools.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-bold whitespace-nowrap">
                            &larr; Kembali ke Daftar Aktif
                        </a>
                    </div>

                    {{-- TABEL DATA --}}
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full table-auto text-sm text-left">
                            <thead class="bg-gray-100 text-gray-600 uppercase font-medium">
                                <tr>
                                    <th class="px-4 py-3">Kode</th>
                                    <th class="px-4 py-3">Nama Alat</th>
                                    <th class="px-4 py-3">Kategori</th>
                                    <th class="px-4 py-3">Tgl Dihapus</th>
                                    <th class="px-4 py-3">Alasan Musnah</th> {{-- KOLOM BARU --}}
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($tools as $tool)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-mono">{{ $tool->tool_code }}</td>
                                        <td class="px-4 py-3 font-bold">{{ $tool->tool_name }}</td>
                                        <td class="px-4 py-3">{{ $tool->category->category_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-red-600 text-xs">
                                            {{ $tool->deleted_at->format('d M Y') }}
                                        </td>
                                        
                                        {{-- TAMPILKAN ALASAN DI SINI --}}
                                        <td class="px-4 py-3">
                                            <span class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded">
                                                {{ $tool->disposal_reason ?? 'Tidak ada alasan' }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            {{-- (Tombol Restore tetap sama seperti sebelumnya) --}}
                                            @auth
                                                @if(!auth()->user()->isHead())
                                                    <form action="{{ route('tools.restore', $tool->id) }}" method="POST" onsubmit="return confirm('Kembalikan alat ini ke daftar aktif?');">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="bg-green-100 text-green-700 px-3 py-1 rounded-md hover:bg-green-200 text-xs font-bold transition">
                                                            Pulihkan
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-400 text-xs italic">Read-only</span>
                                                @endif
                                            @endauth
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-8 text-gray-400">
                                            Tidak ada data sampah ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- PAGINATION --}}
                    <div class="mt-4">
                        {{ $tools->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>