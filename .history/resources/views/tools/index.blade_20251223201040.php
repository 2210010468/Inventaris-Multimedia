<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Inventaris Alat') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Pesan Sukses --}}
            @if (session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    {{-- Header: Pencarian & Tombol Tambah --}}
                    <div class="flex justify-between items-center mb-6">
                        <form action="{{ route('tools.index') }}" method="GET" class="flex gap-2">
                            <input type="text" name="search" placeholder="Cari nama atau kode..." class="border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md text-sm hover:bg-gray-700">Cari</button>
                        </form>
                        
                        @auth
                            @if(!auth()->user()->isHead())
                                <a href="{{ route('tools.create') }}" class="bg-indigo-600 text-white font-bold py-2 px-4 rounded shadow-sm text-sm hover:bg-indigo-700">
                                    + Tambah Alat
                                </a>
                            @endif
                        @endauth
                    </div>

                    {{-- Tabel Data --}}
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full table-auto text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600 uppercase font-medium">
                                <tr>
                                    <th class="px-4 py-3 text-center">No</th>
                                    <th class="px-4 py-3">Kode</th> {{-- Kolom Kode --}}
                                    <th class="px-4 py-3">Nama Alat</th>
                                    <th class="px-4 py-3">Kategori</th>
                                    <th class="px-4 py-3">Kondisi</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($tools as $index => $tool)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3 text-center">{{ $tools->firstItem() + $index }}</td>
                                        
                                        {{-- Tampilkan Kode Alat --}}
                                        <td class="px-4 py-3 font-mono text-gray-500">{{ $tool->tool_code ?? '-' }}</td>
                                        
                                        <td class="px-4 py-3 font-bold text-gray-800">{{ $tool->tool_name }}</td>
                                        <td class="px-4 py-3">{{ $tool->category->category_name ?? 'Tak Berkategori' }}</td>
                                        <td class="px-4 py-3">
                                            @if($tool->current_condition == 'Baik')
                                                <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">Baik</span>
                                            @else
                                                <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded">Rusak</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                             @if($tool->availability_status == 'available')
                                                <span class="text-green-600 font-semibold">Tersedia</span>
                                             @elseif($tool->availability_status == 'borrowed')
                                                <span class="text-orange-600 font-semibold">Dipinjam</span>
                                             @else
                                                <span class="text-gray-600 font-semibold">Maintenance</span>
                                             @endif
                                        </td>
                                        <td class="px-4 py-3 text-center flex justify-center gap-2">
                                            @auth
                                                @if(!auth()->user()->isHead())
                                                    {{-- Tombol Edit --}}
                                                    <button onclick="document.getElementById('modal-edit-{{ $tool->id }}').classList.remove('hidden')" 
                                                        class="text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded transition">
                                                        Edit
                                                    </button>

                                                    {{-- Tombol Hapus --}}
                                                    <form action="{{ route('tools.destroy', $tool->id) }}" method="POST" onsubmit="return confirm('Yakin hapus data ini?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="text-red-600 bg-red-50 hover:bg-red-100 px-3 py-1 rounded transition">Hapus</button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-500 text-sm">(Tidak ada aksi)</span>
                                                @endif
                                            @endauth
                                        </td>
                                    </tr>

                                    {{-- ========================================== --}}
                                    {{-- MODAL POPUP EDIT --}}
                                    {{-- ========================================== --}}
                                    <div id="modal-edit-{{ $tool->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                            
                                            {{-- Backdrop Gelap --}}
                                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="document.getElementById('modal-edit-{{ $tool->id }}').classList.add('hidden')"></div>
                                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                                            {{-- Kotak Putih Modal --}}
                                            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                                
                                                <form action="{{ route('tools.update', $tool->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    
                                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 border-b pb-2">Edit Data Alat</h3>
                                                        
                                                        <div class="grid gap-4">
                                                            
                                                            {{-- 1. INPUT KODE ALAT (INI YANG ANDA CARI) --}}
                                                            <div>
                                                                <label class="block text-gray-700 text-sm font-bold mb-1">Kode Alat</label>
                                                                <input type="text" name="tool_code" value="{{ $tool->tool_code }}" 
                                                                       class="shadow-sm border-gray-300 rounded-md w-full focus:border-indigo-500 focus:ring-indigo-500 bg-gray-100 text-gray-600 cursor-not-allowed" 
                                                                       readonly>
                                                                <p class="text-xs text-gray-500 mt-1">*Kode alat sebaiknya tidak diubah.</p>
                                                            </div>

                                                            {{-- 2. Nama Alat --}}
                                                            <div>
                                                                <label class="block text-gray-700 text-sm font-bold mb-1">Nama Alat</label>
                                                                <input type="text" name="tool_name" value="{{ $tool->tool_name }}" class="shadow-sm border-gray-300 rounded-md w-full focus:border-indigo-500 focus:ring-indigo-500" required>
                                                            </div>

                                                            {{-- 3. Kategori --}}
                                                            <div>
                                                                <label class="block text-gray-700 text-sm font-bold mb-1">Kategori</label>
                                                                <select name="category_id" class="shadow-sm border-gray-300 rounded-md w-full focus:border-indigo-500 focus:ring-indigo-500">
                                                                    @foreach($categories as $cat)
                                                                        <option value="{{ $cat->id }}" {{ $tool->category_id == $cat->id ? 'selected' : '' }}>
                                                                            {{ $cat->category_name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            {{-- 4. Kondisi --}}
                                                            <div>
                                                                <label class="block text-gray-700 text-sm font-bold mb-1">Kondisi</label>
                                                                <select name="current_condition" class="shadow-sm border-gray-300 rounded-md w-full focus:border-indigo-500 focus:ring-indigo-500">
                                                                    <option value="Baik" {{ $tool->current_condition == 'Baik' ? 'selected' : '' }}>Baik</option>
                                                                    <option value="Rusak" {{ $tool->current_condition == 'Rusak' ? 'selected' : '' }}>Rusak</option>
                                                                </select>
                                                            </div>

                                                            {{-- 5. Status --}}
                                                            <div>
                                                                <label class="block text-gray-700 text-sm font-bold mb-1">Status Ketersediaan</label>
                                                                <select name="availability_status" class="shadow-sm border-gray-300 rounded-md w-full focus:border-indigo-500 focus:ring-indigo-500">
                                                                    <option value="available" {{ $tool->availability_status == 'available' ? 'selected' : '' }}>Tersedia</option>
                                                                    <option value="borrowed" {{ $tool->availability_status == 'borrowed' ? 'selected' : '' }}>Dipinjam</option>
                                                                    <option value="maintenance" {{ $tool->availability_status == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                                                </select>
                                                            </div>

                                                        </div>
                                                    </div>

                                                    {{-- Tombol Aksi --}}
                                                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                                                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:w-auto sm:text-sm">
                                                            Simpan Perubahan
                                                        </button>
                                                        <button type="button" onclick="document.getElementById('modal-edit-{{ $tool->id }}').classList.add('hidden')" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:w-auto sm:text-sm">
                                                            Batal
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- END MODAL --}}

                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                            Belum ada data alat. Silakan tambah data baru.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    {{-- Pagination --}}
                    <div class="mt-4">
                        {{ $tools->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
    <script>
        function confirmDelete(form, id) {
            const submitBtn = form.querySelector('button[type="submit"]');
            const hasHistory = submitBtn && submitBtn.dataset.hasHistory === '1';

            if (!hasHistory) {
                return confirm('Yakin hapus data ini?');
            }

            // Jika ada riwayat, minta konfirmasi khusus
            const ok = confirm('Alat ini memiliki riwayat peminjaman. Menghapus paksa akan menghapus juga riwayat tersebut. Lanjutkan hapus paksa?');
            if (ok) {
                const input = form.querySelector('input[name="force"]');
                if (input) input.value = 1;
                return true;
            }
            return false;
        }
    </script>
</x-app-layout>