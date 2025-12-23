<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Kelola Kategori Alat') }}
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
                    
                    {{-- Header: Pencarian & Tombol Tambah --}}
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                        <form action="{{ route('categories.index') }}" method="GET" class="flex gap-2 w-full md:w-auto">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kategori..." class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm">Cari</button>
                        </form>
                        
                        {{-- Tombol Trigger Modal Tambah --}}
                        @auth
                            @if(!auth()->user()->isHead())
                                <button onclick="toggleModal('modal-create')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow-sm text-sm">
                                    + Tambah Kategori
                                </button>
                            @endif
                        @endauth
                    </div>

                    {{-- Tabel --}}
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full table-auto text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600 uppercase font-medium">
                                <tr>
                                    <th class="px-4 py-3 text-center w-16">No</th>
                                    <th class="px-4 py-3 text-center w-24">Kode</th>
                                    <th class="px-4 py-3">Nama Kategori</th>
                                    <th class="px-4 py-3 text-center w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($categories as $index => $cat)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-center">{{ $categories->firstItem() + $index }}</td>
                                        <td class="px-4 py-3 text-center font-mono text-sm">{{ $cat->code ?? '-' }}</td>
                                        <td class="px-4 py-3 font-medium">{{ $cat->category_name }}</td>
                                        <td class="px-4 py-3 text-center flex justify-center gap-2">
                                            @auth
                                                @if(!auth()->user()->isHead())
                                                    <button onclick="toggleModal('modal-edit-{{ $cat->id }}')" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-1 rounded">Edit</button>
                                                    
                                                    <form action="{{ route('categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Hapus kategori ini?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 p-1 rounded">Hapus</button>
                                                    </form>

                                                    {{-- MODAL EDIT --}}
                                                    @include('categories.modal_edit', ['category' => $cat])
                                                @else
                                                    <span class="text-gray-500">(Tidak ada aksi)</span>
                                                @endif
                                            @endauth
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center py-4">Data kosong.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $categories->links() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL CREATE (Include di sini atau pisah file) --}}
    <div id="modal-create" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-hidden="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" onclick="toggleModal('modal-create')"></div>
            <div class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-md sm:w-full">
                <form action="{{ route('categories.store') }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Tambah Kategori</h3>
                        <div class="mb-3">
                            <input type="text" name="category_name" required class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Elektronik">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kode (prefix)</label>
                            <input type="text" name="code" maxlength="10" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: LPT">
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 flex flex-row-reverse gap-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Simpan</button>
                        <button type="button" onclick="toggleModal('modal-create')" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleModal(id) { document.getElementById(id).classList.toggle('hidden'); }
    </script>
</x-app-layout>