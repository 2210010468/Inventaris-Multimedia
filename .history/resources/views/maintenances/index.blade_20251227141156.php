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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold">Riwayat Perawatan</h3>
                        @auth
                            @if(!auth()->user()->isHead())
                                <a href="{{ route('maintenances.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 text-sm font-bold shadow">
                                    + Catat Perbaikan Baru
                                </a>
                            @endif
                        @endauth
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto border-collapse border border-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-4 py-2 text-center">No</th>
                                    <th class="border px-4 py-2 text-left">Nama Alat</th>
                                    {{-- KOLOM JENIS (Sudah Ditambahkan) --}}
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
                                        <td class="border px-4 py-2 font-bold">{{ $item->tool->tool_name ?? 'Alat Dihapus' }}</td>
                                        
                                        {{-- DATA JENIS --}}
                                        <td class="border px-4 py-2">
                                            <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                                {{ $item->type->name ?? 'Umum' }}
                                            </span>
                                        </td>

                                        <td class="border px-4 py-2">
                                            {{ Str::limit($item->note, 50) }}
                                            <div class="text-xs text-gray-400 mt-1">Pelapor: {{ $item->user->name ?? '-' }}</div>
                                        </td>
                                        <td class="border px-4 py-2 text-center">{{ \Carbon\Carbon::parse($item->start_date)->format('d M Y') }}</td>
                                        <td class="border px-4 py-2 text-center">
                                            @if($item->status == 'in_progress')
                                                <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-yellow-300">
                                                    Sedang Perbaikan
                                                </span>
                                            @else
                                                <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-green-300">
                                                    Selesai
                                                </span>
                                                <div class="text-[10px] text-gray-500 mt-1">
                                                    {{ \Carbon\Carbon::parse($item->end_date)->format('d M Y') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="border px-4 py-2 text-right">
                                            Rp {{ number_format($item->cost, 0, ',', '.') }}
                                        </td>
                                        <td class="border px-4 py-2 text-center">
                                            <div class="flex justify-center gap-2">
                                                @auth
                                                    @if(!auth()->user()->isHead())
                                                        <a href="{{ route('maintenances.edit', $item->id) }}" class="bg-blue-100 text-blue-600 p-1.5 rounded hover:bg-blue-200">
                                                            @if($item->status == 'in_progress') ⚙️ Proses @else 📄 Detail @endif
                                                        </a>

                                                        <form action="{{ route('maintenances.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus?');">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="bg-red-100 text-red-600 p-1.5 rounded hover:bg-red-200">🗑️</button>
                                                        </form>
                                                    @else
                                                        <a class="bg-gray-100 text-gray-600 p-1.5 rounded">📄 Detail</a>
                                                    @endif
                                                @endauth
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="border px-4 py-8 text-center text-gray-500">
                                            Belum ada data riwayat perbaikan.
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