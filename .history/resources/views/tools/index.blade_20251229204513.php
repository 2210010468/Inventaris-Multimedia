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

                        {{-- Tombol Tambah (DILINDUNGI ROLE) --}}
                        @auth
                            @if(!auth()->user()->isHead())
                                <a href="{{ route('tools.create') }}" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow-sm text-center text-sm">
                                    + Tambah Alat
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
                                    <th class="px-4 py-3">Kode</th>
                                    <th class="px-4 py-3">Nama Alat</th>
                                    <th class="px-4 py-3">Kategori</th>
                                    <th class="px-4 py-3 text-center">Kondisi</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($tools as $index => $tool)
                                    <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                                        <td class="px-4 py-3 text-center font-medium text-gray-500">
                                            {{ $tools->firstItem() + $index }}
                                        </td>
                                        
                                        {{-- KODE --}}
                                        <td class="px-4 py-3 font-mono text-xs text-indigo-600 font-bold">
                                            {{ $tool->tool_code }}
                                        </td>

                                        {{-- NAMA --}}
                                        <td class="px-4 py-3 font-bold text-gray-800">
                                            {{ $tool->tool_name }}
                                            <div class="text-xs text-gray-400 font-normal">
                                                {{ $tool->brand ?? 'Tanpa Merk' }} {{-- Tampilkan Merk kecil di bawah nama --}}
                                            </div>
                                        </td>

                                        {{-- KATEGORI --}}
                                        <td class="px-4 py-3">
                                            <span class="bg-gray-100 text-gray-600 py-1 px-2 rounded text-xs">
                                                {{ $tool->category->category_name ?? '-' }}
                                            </span>
                                        </td>

                                        {{-- KONDISI --}}
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-1 rounded text-xs font-semibold {{ $tool->current_condition == 'Baik' ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50' }}">
                                                {{ $tool->current_condition }}
                                            </span>
                                        </td>

                                        {{-- STATUS --}}
                                        <td class="px-4 py-3 text-center">
                                            @if($tool->availability_status == 'available')
                                                <span class="bg-green-100 text-green-800 py-1 px-2 rounded-full text-xs font-bold border border-green-200">Tersedia</span>
                                            @elseif($tool->availability_status == 'borrowed')
                                                <span class="bg-yellow-100 text-yellow-800 py-1 px-2 rounded-full text-xs font-bold border border-yellow-200">Dipinjam</span>
                                            @elseif($tool->availability_status == 'maintenance')
                                                <span class="bg-gray-100 text-gray-800 py-1 px-2 rounded-full text-xs font-bold border border-gray-200">Maintenance</span>
                                            @else 
                                                <span class="bg-red-100 text-red-800 py-1 px-2 rounded-full text-xs font-bold border border-red-200">{{ ucfirst($tool->availability_status) }}</span>
                                            @endif
                                        </td>

                                        {{-- AKSI --}}
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex justify-center items-center space-x-2">
                                                
                                                {{-- 1. TOMBOL DETAIL (MATA) - UNTUK SEMUA ROLE --}}
                                                <button type="button" onclick="document.getElementById('modal-detail-{{ $tool->id }}').classList.remove('hidden')" 
                                                    class="text-blue-600 hover:text-blue-900 bg-blue-50 p-1 rounded hover:bg-blue-100" title="Lihat Detail">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>

                                                @auth
                                                    @if(!auth()->user()->isHead())
                                                        {{-- Edit --}}
                                                        <a href="{{ route('tools.edit', $tool->id) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-1 rounded hover:bg-indigo-100" title="Edit">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </a>
                                                        
                                                        {{-- Hapus --}}
                                                        <form action="{{ route('tools.destroy', $tool->id) }}" method="POST" onsubmit="return confirm('Yakin hapus alat ini?');">
                                                            @csrf @method('DELETE')
                                                            <button type="button" 
                                                                    onclick="openDeleteModal('{{ $tool->id }}', '{{ $tool->tool_name }}', '{{ $tool->tool_code }}')" 
                                                                    class="text-red-600 hover:text-red-900 ml-2">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endauth
                                            </div>

                                            {{-- --- MODAL DETAIL (Ditaruh di sini agar loop ID-nya jalan) --- --}}
                                            <div id="modal-detail-{{ $tool->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="document.getElementById('modal-detail-{{ $tool->id }}').classList.add('hidden')"></div>

                                                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                                                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                            <div class="sm:flex sm:items-start">
                                                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                                                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                </div>
                                                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                                                        Detail Aset: {{ $tool->tool_code }}
                                                                    </h3>
                                                                    <div class="mt-4 space-y-3 text-sm text-gray-500">
                                                                        <div class="grid grid-cols-3 gap-2 border-b pb-2">
                                                                            <span class="font-bold">Nama Barang:</span>
                                                                            <span class="col-span-2">{{ $tool->tool_name }}</span>
                                                                        </div>
                                                                        <div class="grid grid-cols-3 gap-2 border-b pb-2">
                                                                            <span class="font-bold">Merk / Tipe:</span>
                                                                            <span class="col-span-2 text-gray-900">{{ $tool->brand ?? '-' }}</span>
                                                                        </div>
                                                                        <div class="grid grid-cols-3 gap-2 border-b pb-2">
                                                                            <span class="font-bold">Tahun Perolehan:</span>
                                                                            <span class="col-span-2">{{ $tool->purchase_date ? \Carbon\Carbon::parse($tool->purchase_date)->translatedFormat('d F Y') : '-' }}</span>
                                                                        </div>
                                                                        <div class="grid grid-cols-3 gap-2 border-b pb-2">
                                                                            <span class="font-bold">Kategori:</span>
                                                                            <span class="col-span-2">{{ $tool->category->category_name ?? '-' }}</span>
                                                                        </div>
                                                                        <div class="grid grid-cols-3 gap-2 border-b pb-2">
                                                                            <span class="font-bold">Kondisi Saat Ini:</span>
                                                                            <span class="col-span-2">{{ $tool->current_condition }}</span>
                                                                        </div>
                                                                        <div class="grid grid-cols-3 gap-2">
                                                                            <span class="font-bold">Sumber:</span>
                                                                            <span class="col-span-2">
                                                                                @if($tool->purchase_item_id)
                                                                                    <span class="text-indigo-600">Hasil Pengadaan (Pembelian)</span>
                                                                                @else
                                                                                    <span class="text-gray-500">Input Manual / Hibah</span>
                                                                                @endif
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                            <button type="button" onclick="document.getElementById('modal-detail-{{ $tool->id }}').classList.add('hidden')" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                                                Tutup
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- END MODAL --}}

                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-8 text-gray-400">
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
    {{-- MODAL HAPUS BARANG (BARANG MUSNAH) --}}
<div id="deleteModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeDeleteModal()"></div>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            {{-- Form Delete mengarah ke method destroy --}}
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Konfirmasi Penghapusan Aset</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Anda akan menghapus: <strong id="delToolName"></strong> (<span id="delToolCode"></span>).<br>
                        Data akan dipindahkan ke "Inventaris Tak Terpakai" (Trash).
                    </p>

                    {{-- DROPDOWN ALASAN --}}
                    <label class="block text-sm font-bold text-gray-700 mb-1">Alasan Penghapusan / Pemusnahan:</label>
                    <select name="disposal_reason" required class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                        <option value="" disabled selected>-- Pilih Alasan --</option>
                        <option value="Rusak Total">Rusak Total / Mati Total</option>
                        <option value="Hilang">Hilang</option>
                        <option value="Dijual">Dijual / Lelang</option>
                        <option value="Hibah">Dihibahkan</option>
                        <option value="Kadaluarsa">Kadaluarsa / Usang (Obsolete)</option>
                    </select>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:w-auto sm:text-sm">
                        Confirm & Delete
                    </button>
                    <button type="button" onclick="closeDeleteModal()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openDeleteModal(id, name, code) {
        // Sesuaikan URL ini dengan route resource tools Anda
        document.getElementById('deleteForm').action = "/tools/" + id; 
        document.getElementById('delToolName').innerText = name;
        document.getElementById('delToolCode').innerText = code;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
</script>
</x-app-layout>