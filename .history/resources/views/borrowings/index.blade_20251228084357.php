<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Peminjaman') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- 1. PESAN SUKSES (Alert) --}}
            @if (session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 shadow-sm" role="alert">
                    <p class="font-bold">Berhasil!</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            {{-- 2. KONTAINER UTAMA (Putih) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- A. BAGIAN ATAS: FILTER & TOMBOL TAMBAH --}}
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                        
                        {{-- Form Filter --}}
                        <form action="{{ route('borrowings.index') }}" method="GET" class="flex flex-col md:flex-row gap-2 w-full md:w-auto items-center">
                            
                            {{-- Input Search --}}
                            <input type="text" name="search" value="{{ request('search') }}" 
                                class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm w-full md:w-auto"
                                placeholder="Cari Nama / ID...">

                            {{-- Dropdown Status --}}
                            <select name="status" class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm w-full md:w-auto">
                                <option value="">- Semua Status -</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Dipinjam</option>
                                <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>Kembali</option>
                            </select>

                            {{-- Dropdown Periode --}}
                            <select name="period" class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm w-full md:w-auto">
                                <option value="all" {{ request('period') == 'all' || request('period') == null ? 'selected' : '' }}>- Semua Periode -</option>
                                <option value="week" {{ request('period') == 'week' ? 'selected' : '' }}>Minggu Terakhir</option>
                                <option value="month" {{ request('period') == 'month' ? 'selected' : '' }}>Bulan Terakhir</option>
                            </select>

                            {{-- Tombol Filter --}}
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm w-full md:w-auto">
                                Filter
                            </button>

                            {{-- Tombol Reset (Hanya muncul jika ada filter) --}}
                            @if(request('search') || request('status') || (request('period') && request('period') !== 'all'))
                                <a href="{{ route('borrowings.index') }}" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 text-sm transition flex items-center justify-center w-full md:w-auto">
                                    Reset
                                </a>
                            @endif
                        </form>

                        {{-- Tombol Tambah (Di Kanan) --}}
                        {{-- HANYA NON-KEPALA YANG BISA NAMBAH --}}
                        @auth
                            @if(!auth()->user()->isHead())
                                <a href="{{ route('borrowings.create') }}" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow-sm text-center text-sm">
                                    + Peminjaman Baru
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
                                    <th class="px-4 py-3">Peminjam</th>
                                    <th class="px-4 py-3">Tgl Pinjam</th>
                                    <th class="px-4 py-3">Rencana Kembali</th>
                                    <th class="px-4 py-3">Barang</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($borrowings as $index => $borrowing)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-center font-medium text-gray-500">
                                            {{ $borrowings->firstItem() + $index }}
                                        </td>
                                        
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-gray-800">{{ $borrowing->borrower->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $borrowing->borrower->code }}</div>
                                        </td>

                                        <td class="px-4 py-3">
                                            {{ \Carbon\Carbon::parse($borrowing->borrow_date)->format('d M Y') }}
                                        </td>
                                        
                                        <td class="px-4 py-3 text-red-600 font-medium">
                                            {{ \Carbon\Carbon::parse($borrowing->planned_return_date)->format('d M Y') }}
                                        </td>

                                        <td class="px-4 py-3">
                                            <ul class="list-disc list-inside text-gray-700">
                                                @foreach($borrowing->items as $item)
                                                    <li>{{ $item->tool->tool_name ?? 'Alat Dihapus' }}</li>
                                                @endforeach
                                            </ul>
                                            @if($borrowing->notes)
                                                <div class="text-xs text-gray-500 italic mt-1">"{{ $borrowing->notes }}"</div>
                                            @endif
                                        </td>

                                        {{-- KOLOM STATUS --}}
                                        <td class="px-4 py-3 text-center">
                                            @if($borrowing->borrowing_status == 'active')
                                                <span class="bg-yellow-100 text-yellow-800 py-1 px-2 rounded-full text-xs font-bold border border-yellow-200">
                                                    Sedang Dipinjam
                                                </span>
                                            @else
                                                <span class="bg-green-100 text-green-800 py-1 px-2 rounded-full text-xs font-bold border border-green-200">
                                                    Dikembalikan
                                                </span>
                                                <div class="text-[10px] text-gray-400 mt-1">
                                                    {{ $borrowing->actual_return_date ? \Carbon\Carbon::parse($borrowing->actual_return_date)->format('d/m/Y') : '-' }}
                                                </div>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            <div class="flex justify-center items-center space-x-2">
                                                
                                                @auth
                                                    @if(!auth()->user()->isHead())
                                                        {{-- LOGIKA UNTUK ADMIN / STAFF --}}
                                                        
                                                        {{-- 1. Tombol Edit (Hanya muncul jika status 'active' / belum dikembalikan) --}}
                                                        @if($borrowing->borrowing_status == 'active')
                                                            <button onclick="toggleModal('modal-edit-{{ $borrowing->id }}')" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-1 rounded" title="Edit Data">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                            </button>
                                                        @endif

                                                        {{-- 2. Logika Tombol Kembalikan / Hapus --}}
                                                        @if($borrowing->borrowing_status == 'active')
                                                            {{-- Tombol Kembalikan --}}
                                                            <form action="{{ route('borrowings.return', $borrowing->id) }}" method="POST" onsubmit="return confirm('Apakah barang fisik sudah diterima kembali dan dicek kondisinya?');">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit" class="text-blue-600 hover:text-blue-900 bg-blue-50 p-1 rounded" title="Proses Pengembalian">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        @else
                                                            {{-- Tombol Hapus (Hanya muncul jika sudah dikembalikan) --}}
                                                            <form action="{{ route('borrowings.destroy', $borrowing->id) }}" method="POST" onsubmit="return confirm('Hapus riwayat ini? Data tidak bisa dikembalikan.');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 p-1 rounded" title="Hapus Riwayat">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        @endif

                                                    @else
                                                        {{-- TAMPILAN UNTUK KEPALA (READ ONLY) --}}
                                                        <span class="text-gray-400 text-xs italic">Read-only</span>
                                                    @endif
                                                @endauth
                                            </div>

                                            {{-- MODAL EDIT --}}
                                            {{-- Biarkan modal tetap di-render untuk mencegah error JS, tapi tombol triggernya di atas sudah disembunyikan untuk Kepala --}}
                                            @if($borrowing->borrowing_status == 'active')
                                                <div id="modal-edit-{{ $borrowing->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto text-left" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="toggleModal('modal-edit-{{ $borrowing->id }}')"></div>
                                                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                                        
                                                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                                            <form action="{{ route('borrowings.update', $borrowing->id) }}" method="POST">
                                                                @csrf
                                                                @method('PUT')
                                                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Peminjaman</h3>
                                                                    
                                                                    <div class="mb-4">
                                                                        <label class="block text-gray-700 text-sm font-bold mb-2">Nama Peminjam</label>
                                                                        <input type="text" value="{{ $borrowing->borrower->name }}" disabled class="bg-gray-200 border border-gray-300 text-gray-700 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed">
                                                                    </div>

                                                                    <div class="mb-4">
                                                                        <label class="block text-gray-700 text-sm font-bold mb-2">Rencana Kembali (Baru)</label>
                                                                        <input type="date" name="planned_return_date" value="{{ $borrowing->planned_return_date }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                                                    </div>

                                                                    <div class="mb-2">
                                                                        <label class="block text-gray-700 text-sm font-bold mb-2">Catatan</label>
                                                                        <textarea name="notes" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">{{ $borrowing->notes }}</textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                                    @auth
                                                                        @if(!auth()->user()->isHead())
                                                                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                                                                                Simpan
                                                                            </button>
                                                                        @endif
                                                                    @endauth

                                                                    <button type="button" onclick="toggleModal('modal-edit-{{ $borrowing->id }}')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                                                        Batal
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-8 text-gray-400">
                                            Tidak ada data peminjaman yang ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- C. PAGINATION --}}
                    <div class="mt-6">
                        {{ $borrowings->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Script untuk Modal --}}
    <script>
        function toggleModal(modalID){
            const modal = document.getElementById(modalID);
            const body = document.querySelector('body');
            
            modal.classList.toggle("hidden");
            
            if (!modal.classList.contains('hidden')) {
                body.style.overflow = 'hidden';
            } else {
                body.style.overflow = 'auto';
            }
        }
    </script>
</x-app-layout>