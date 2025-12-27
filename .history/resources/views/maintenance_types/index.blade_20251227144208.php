<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Jenis Perawatan & Perbaikan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Alert Notifikasi --}}
            @if (session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 shadow-sm rounded-r">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 text-gray-900">
                    
                    {{-- TOOLBAR: FILTER (KIRI) & TOMBOL TAMBAH (KANAN) --}}
                    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                        
                        {{-- Bagian Kiri: Search & Filter --}}
                        <form method="GET" action="{{ route('maintenance-types.index') }}" class="flex w-full sm:w-auto gap-2">
                            <input type="text" 
                                   name="search" 
                                   value="{{ request('search') }}"
                                   class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm w-full sm:w-64" 
                                   placeholder="Cari Jenis / Deskripsi...">
                            
                            <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md shadow-sm text-sm transition-colors">
                                Filter
                            </button>
                        </form>

                        {{-- Bagian Kanan: Tombol Tambah --}}
                        @auth
                            @if(!auth()->user()->isHead())
                                <button onclick="toggleModal('modal-create')" 
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md shadow-sm text-sm flex items-center gap-2 transition-colors whitespace-nowrap">
                                    <span>+</span> Tambah Jenis Baru
                                </button>
                            @endif
                        @endauth
                    </div>

                    {{-- Tabel Data --}}
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 text-gray-500 uppercase font-medium text-xs tracking-wider">
                                <tr>
                                    <th class="px-6 py-3 text-left w-16">No</th>
                                    <th class="px-6 py-3 text-left">Nama Jenis</th>
                                    <th class="px-6 py-3 text-left">Deskripsi</th>
                                    <th class="px-6 py-3 text-center w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                                @forelse($maintenanceTypes as $index => $type)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">{{ $type->name }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $type->description ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex justify-center gap-2">
                                            @auth
                                                @if(!auth()->user()->isHead())
                                                    {{-- Tombol Edit (Style Badge) --}}
                                                    <button onclick="toggleModal('modal-edit-{{ $type->id }}')" 
                                                            class="bg-indigo-50 text-indigo-600 hover:bg-indigo-100 px-3 py-1 rounded-md text-xs font-medium transition-colors">
                                                        Edit
                                                    </button>

                                                    {{-- Tombol Hapus (Style Badge) --}}
                                                    <form action="{{ route('maintenance-types.destroy', $type->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-100 px-3 py-1 rounded-md text-xs font-medium transition-colors">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-400 text-xs">-</span>
                                                @endif
                                            @endauth
                                        </div>

                                        {{-- MODAL EDIT (Hidden by default) --}}
                                        <div id="modal-edit-{{ $type->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-hidden="true">
                                            <div class="flex items-center justify-center min-h-screen px-4 text-center">
                                                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="toggleModal('modal-edit-{{ $type->id }}')"></div>
                                                <div class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-md sm:w-full z-10">
                                                    <form action="{{ route('maintenance-types.update', $type->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                                                            <div class="flex justify-between items-center mb-4 border-b pb-2">
                                                                <h3 class="text-lg font-medium text-gray-900">Edit Jenis</h3>
                                                                <button type="button" onclick="toggleModal('modal-edit-{{ $type->id }}')" class="text-gray-400 hover:text-gray-500">✕</button>
                                                            </div>
                                                            <div class="mb-4">
                                                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Jenis</label>
                                                                <input type="text" name="name" value="{{ $type->name }}" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                                                                <textarea name="description" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ $type->description }}</textarea>
                                                            </div>
                                                        </div>
                                                        <div class="bg-gray-50 px-4 py-3 flex flex-row-reverse gap-2">
                                                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 shadow-sm text-sm font-medium">Simpan</button>
                                                            <button type="button" onclick="toggleModal('modal-edit-{{ $type->id }}')" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 text-sm font-medium">Batal</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                        Data tidak ditemukan.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL CREATE --}}
    <div id="modal-create" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-hidden="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="toggleModal('modal-create')"></div>
            <div class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-md sm:w-full z-10">
                <form action="{{ route('maintenance-types.store') }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h3 class="text-lg font-medium text-gray-900">Tambah Jenis Baru</h3>
                            <button type="button" onclick="toggleModal('modal-create')" class="text-gray-400 hover:text-gray-500">✕</button>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Jenis</label>
                            <input type="text" name="name" placeholder="Contoh: Service Rutin" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div class="mb-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                            <textarea name="description" placeholder="Keterangan singkat..." rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 flex flex-row-reverse gap-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 shadow-sm text-sm font-medium">Simpan</button>
                        <button type="button" onclick="toggleModal('modal-create')" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 text-sm font-medium">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleModal(modalID) {
            const modal = document.getElementById(modalID);
            if (modal) {
                modal.classList.toggle('hidden');
            }
        }
    </script>
</x-app-layout>