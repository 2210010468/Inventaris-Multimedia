<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Jenis Perawatan & Perbaikan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Alert --}}
            @if (session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    {{-- A. BAGIAN FILTER & SEARCH --}}
                    {{-- Menggunakan class flex justify-between agar Filter di Kiri dan Tombol Tambah di Kanan --}}
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                        
                        {{-- Form Filter (Kiri) --}}
                        <form action="{{ route('maintenance-types.index') }}" method="GET" class="flex flex-col md:flex-row gap-2 w-full md:w-auto items-center">
                            
                            {{-- Input Search --}}
                            <input type="text" name="search" value="{{ request('search') }}" 
                                class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm w-full md:w-auto"
                                placeholder="Cari Jenis / Deskripsi...">

                            {{-- Tombol Filter --}}
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm w-full md:w-auto">
                                Filter
                            </button>

                            {{-- Tombol Reset --}}
                            @if(request('search'))
                                <a href="{{ route('maintenance-types.index') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700 text-sm border border-gray-300 rounded-md text-center w-full md:w-auto">
                                    Reset
                                </a>
                            @endif
                        </form>
                        
                        {{-- Tombol Trigger Modal Tambah (Kanan) --}}
                        @auth
                            @if(!auth()->user()->isHead())
                                <button onclick="toggleModal('modal-create')" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow-sm text-center text-sm">
                                    + Tambah Jenis Baru
                                </button>
                            @endif
                        @endauth
                    </div>

                    {{-- B. TABEL DATA --}}
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full table-auto text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600 uppercase font-medium">
                                <tr>
                                    <th class="px-4 py-3 text-center w-16">No</th>
                                    <th class="px-4 py-3">Nama Jenis</th>
                                    <th class="px-4 py-3">Deskripsi</th>
                                    <th class="px-4 py-3 text-center w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($maintenanceTypes as $index => $type)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-center font-medium text-gray-500">
                                            {{ $loop->iteration }}
                                        </td>
                                        <td class="px-4 py-3 font-bold text-gray-800">
                                            {{ $type->name }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">
                                            {{ $type->description ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @auth
                                                @if(!auth()->user()->isHead())
                                                    <div class="flex justify-center items-center space-x-2">
                                                        {{-- Tombol Edit (Style Badge) --}}
                                                        <button onclick="toggleModal('modal-edit-{{ $type->id }}')" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-1 rounded">
                                                            Edit
                                                        </button>
                                                        
                                                        {{-- Tombol Hapus (Style Badge) --}}
                                                        <form action="{{ route('maintenance-types.destroy', $type->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus jenis ini?');">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 p-1 rounded">
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    </div>

                                                    {{-- MODAL EDIT (Inline per Item) --}}
                                                    <div id="modal-edit-{{ $type->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-hidden="true">
                                                        <div class="flex items-center justify-center min-h-screen px-4 text-center">
                                                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" onclick="toggleModal('modal-edit-{{ $type->id }}')"></div>
                                                            <div class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-md sm:w-full">
                                                                <form action="{{ route('maintenance-types.update', $type->id) }}" method="POST">
                                                                    @csrf @method('PUT')
                                                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                                                                        <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Jenis Perawatan</h3>
                                                                        <div class="mb-3">
                                                                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Jenis</label>
                                                                            <input type="text" name="name" value="{{ $type->name }}" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                                        </div>
                                                                        <div>
                                                                            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                                                                            <textarea name="description" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ $type->description }}</textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="bg-gray-50 px-4 py-3 flex flex-row-reverse gap-2">
                                                                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm">Simpan Perubahan</button>
                                                                        <button type="button" onclick="toggleModal('modal-edit-{{ $type->id }}')" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 text-sm">Batal</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    {{-- End Modal Edit --}}

                                                @else
                                                    <span class="text-gray-400 text-xs italic">Read Only</span>
                                                @endif
                                            @endauth
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-8 text-gray-400">
                                            Data jenis perawatan tidak ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- C. PAGINATION --}}
                    {{-- Pastikan di Controller menggunakan ->paginate() agar links ini muncul --}}
                    <div class="mt-6">
                        @if($maintenanceTypes instanceof \Illuminate\Pagination\LengthAwarePaginator)
                            {{ $maintenanceTypes->links() }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL CREATE --}}
    <div id="modal-create" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-hidden="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" onclick="toggleModal('modal-create')"></div>
            <div class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-md sm:w-full">
                <form action="{{ route('maintenance-types.store') }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Tambah Jenis Baru</h3>
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Jenis</label>
                            <input type="text" name="name" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Contoh: Service Rutin">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                            <textarea name="description" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Keterangan singkat..."></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 flex flex-row-reverse gap-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm">Simpan</button>
                        <button type="button" onclick="toggleModal('modal-create')" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 text-sm">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.toggle('hidden');
            }
        }
    </script>
</x-app-layout>