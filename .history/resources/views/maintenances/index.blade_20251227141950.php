<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Perawatan & Perbaikan Alat') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    {{-- HEADER & TOMBOL TAMBAH --}}
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <h3 class="text-lg font-bold">Riwayat Perawatan & Perbaikan</h3>
                        @auth
                            @if(!auth()->user()->isHead())
                                <a href="{{ route('maintenances.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 text-sm font-bold shadow transition">
                                    + Catat Perbaikan Baru
                                </a>
                            @endif
                        @endauth
                    </div>

                    {{-- FILTER & PENCARIAN (BARU DITAMBAHKAN) --}}
                    <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <form method="GET" action="{{ route('maintenances.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            
                            {{-- Input Cari --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Cari Alat / Note</label>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama alat..." 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>

                            {{-- Filter Status --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Status Pengerjaan</label>
                                <select name="status" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">Semua Status</option>
                                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Sedang Perbaikan</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                                </select>
                            </div>

                            {{-- Filter Jenis --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jenis Perawatan</label>
                                <select name="type_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">Semua Jenis</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type->id }}" {{ request('type_id') == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Tombol Filter --}}
                            <div class="flex items-end gap-2">
                                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-bold w-full">
                                    Filter
                                </button>
                                <a href="{{ route('maintenances.index') }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 text-sm font-bold text-center">
                                    Reset
                                </a>
                            </div>
                        </form>
                    </div>
                    {{-- END FILTER --}}

                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto border-collapse border border-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-4 py-2 text-center">No</th>
                                    <th class="border px-4 py-2 text-left">Nama Alat</th>
                                    <th class="border px-4 py-2 text-left">Jenis</th>
                                    <th class="border px-4 py-2 text-left">Masalah / Note</th>
                                    <th class="border px-4 py-2 text-center">Tgl Mulai</th>
                                    <th class="border px-4 py-2 text-center">Status</th>
                                    <th class="border px-4 py-2 text-center">Biaya</th>
                                    <th class="border px-4 py-2 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($maintenances as $key => $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="border px-4 py-2 text-center">{{ $maintenances->firstItem() + $key }}</td>
                                        <td class="border px-4 py-2 font-bold">{{ $item->tool->tool_name ?? 'Alat Terhapus' }}</td>
                                        <td class="border px-4 py-2">
                                            <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                                {{ $item->type->name ?? 'Umum' }}
                                            </span>
                                        </td>
                                        <td class="border px-4 py-2">
                                            {{ Str::limit($item->note, 40) }}
                                            <div class="text-[10px] text-gray-500 mt-1">Oleh: {{ $item->user->name ?? '-' }}</div>
                                        </td>
                                        <td class="border px-4 py-2 text-center">{{ \Carbon\Carbon::parse($item->start_date)->format('d/m/Y') }}</td>
                                        <td class="border px-4 py-2 text-center">
                                            @if($item->status == 'in_progress')
                                                <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-yellow-300">
                                                    Proses
                                                </span>
                                            @else
                                                <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-green-300">
                                                    Selesai
                                                </span>
                                                <div class="text-[10px] text-gray-500 mt-1">
                                                    {{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d/m/Y') : '-' }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="border px-4 py-2 text-right">
                                            Rp {{ number_format($item->cost, 0, ',', '.') }}
                                        </td>
                                        <td class="border px-4 py-2 text-center">
                                            <div class="flex justify-center gap-1">
                                                @auth
                                                    @if(!auth()->user()->isHead())
                                                        <a href="{{ route('maintenances.edit', $item->id) }}" class="bg-blue-100 text-blue-600 p-1.5 rounded hover:bg-blue-200" title="Edit / Selesaikan">
                                                            ✏️
                                                        </a>
                                                        <form action="{{ route('maintenances.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus data perbaikan ini? Status alat akan dikembalikan.');">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="bg-red-100 text-red-600 p-1.5 rounded hover:bg-red-200" title="Hapus">
                                                                🗑️
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="text-gray-400 text-xs">Read-only</span>
                                                    @endif
                                                @endauth
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="border px-4 py-8 text-center text-gray-500">
                                            Tidak ada data perbaikan yang sesuai filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-4">
                            {{ $maintenances->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>