<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Perawatan & Perbaikan Alat') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- 1. PESAN SUKSES / ERROR --}}
            @if (session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 shadow-sm" role="alert">
                    <p class="font-bold">Berhasil!</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 shadow-sm" role="alert">
                    <p class="font-bold">Gagal!</p>
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            {{-- 2. KONTAINER UTAMA --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- A. BAGIAN ATAS: FILTER & TOMBOL TAMBAH --}}
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                        
                        {{-- Form Filter (Inline / Satu Baris) --}}
                        <form action="{{ route('maintenances.index') }}" method="GET" class="flex flex-col md:flex-row gap-2 w-full md:w-auto items-center">
                            
                            {{-- Input Search --}}
                            <input type="text" name="search" value="{{ request('search') }}" 
                                class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm w-full md:w-48"
                                placeholder="Cari Alat / Masalah...">

                            {{-- Dropdown Status --}}
                            <select name="status" class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm w-full md:w-auto">
                                <option value="">- Semua Status -</option>
                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Sedang Proses</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                            </select>

                            {{-- Dropdown Jenis Perawatan --}}
                            <select name="type_id" class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm w-full md:w-auto">
                                <option value="">- Semua Jenis -</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}" {{ request('type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>

                            {{-- Tombol Filter --}}
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm w-full md:w-auto">
                                Filter
                            </button>

                            {{-- Tombol Reset --}}
                            @if(request('search') || request('status') || request('type_id'))
                                <a href="{{ route('maintenances.index') }}" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 text-sm transition flex items-center justify-center w-full md:w-auto">
                                    Reset
                                </a>
                            @endif
                        </form>

                        {{-- Tombol Tambah (Di Kanan) --}}
                        @auth
                            @if(!auth()->user()->isHead())
                                <a href="{{ route('maintenances.create') }}" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow-sm text-center text-sm">
                                    + Catat Perbaikan
                                </a>
                            @endif
                        @endauth
                    </div>

                    {{-- B. TABEL DATA --}}
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full table-auto text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600 uppercase font-medium">
                                <tr>
                                    <th class="px-4 py-3 text-center">No</th>
                                    <th class="px-4 py-3">Nama Alat</th>
                                    <th class="px-4 py-3">Jenis</th>
                                    <th class="px-4 py-3">Masalah / Catatan</th>
                                    <th class="px-4 py-3">Tgl Mulai</th>
                                    <th class="px-4 py-3">Biaya</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($maintenances as $index => $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-center font-medium text-gray-500">
                                            {{ $maintenances->firstItem() + $index }}
                                        </td>
                                        
                                        <td class="px-4 py-3 font-bold text-gray-800">
                                            {{ $item->tool->tool_name ?? 'Alat Dihapus' }}
                                        </td>

                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $item->type->name ?? 'Umum' }}
                                            </span>
                                        </td>
                                        
                                        <td class="px-4 py-3">
                                            <div class="text-gray-900">{{ Str::limit($item->note, 40) }}</div>
                                            <div class="text-xs text-gray-500 mt-1">Oleh: {{ $item->user->name ?? '-' }}</div>
                                        </td>

                                        <td class="px-4 py-3 text-gray-600">
                                            {{ \Carbon\Carbon::parse($item->start_date)->format('d M Y') }}
                                        </td>

                                        <td class="px-4 py-3 font-medium text-gray-700">
                                            Rp {{ number_format($item->cost, 0, ',', '.') }}
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            @if($item->status == 'in_progress')
                                                <span class="bg-yellow-100 text-yellow-800 py-1 px-2 rounded-full text-xs font-bold border border-yellow-200">
                                                    Proses
                                                </span>
                                            @else
                                                <span class="bg-green-100 text-green-800 py-1 px-2 rounded-full text-xs font-bold border border-green-200">
                                                    Selesai
                                                </span>
                                                <div class="text-[10px] text-gray-400 mt-1">
                                                    {{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d/m/Y') : '' }}
                                                </div>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            <div class="flex justify-center items-center space-x-2">
                                                @auth
                                                    @if(!auth()->user()->isHead())
                                                        {{-- Tombol Edit --}}
                                                        <a href="{{ route('maintenances.edit', $item->id) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-1 rounded" title="Edit / Selesaikan">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </a>

                                                        {{-- Tombol Hapus --}}
                                                        <form action="{{ route('maintenances.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus data perbaikan ini? Status alat akan dikembalikan.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 p-1 rounded" title="Hapus">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="text-gray-400 text-xs italic">Read-only</span>
                                                    @endif
                                                @endauth
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-8 text-gray-400">
                                            Tidak ada data perbaikan yang ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- C. PAGINATION --}}
                    <div class="mt-6">
                        {{ $maintenances->withQueryString()->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>