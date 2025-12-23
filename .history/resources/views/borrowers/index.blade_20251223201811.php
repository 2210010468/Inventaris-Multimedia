<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Data Peminjam (Siswa/Guru)') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 shadow-sm">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                        <form action="{{ route('borrowers.index') }}" method="GET" class="flex gap-2 w-full md:w-auto">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Nama / NIS..." class="border-gray-300 rounded-md shadow-sm text-sm">
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm">Cari</button>
                        </form>
                        @auth
                            @if(!auth()->user()->isHead())
                                <a href="{{ route('borrowers.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow-sm text-sm">+ Tambah Peminjam</a>
                            @endif
                        @endauth
                    </div>

                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full table-auto text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600 uppercase font-medium">
                                <tr>
                                    <th class="px-4 py-3 text-center">No</th>
                                    <th class="px-4 py-3">Foto</th>
                                    <th class="px-4 py-3">Identitas (NIS/NIP)</th>
                                    <th class="px-4 py-3">Nama Lengkap</th>
                                    <th class="px-4 py-3">Kontak</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($borrowers as $index => $b)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-center">{{ $borrowers->firstItem() + $index }}</td>
                                        <td class="px-4 py-3">
                                            @if($b->photo)
                                                <div class="inline-block border rounded-md overflow-hidden" style="height:50px;">
                                                    <img src="{{ asset('storage/'.$b->photo) }}" alt="Foto" class="h-28 w-20 object-cover">
                                                </div>
                                            @else
                                                <div class="inline-block border rounded-md bg-gray-100 h-28 w-20 flex items-center justify-center text-xs text-gray-400">No Photo</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-mono text-gray-600">{{ $b->code }}</td>
                                        <td class="px-4 py-3 font-bold text-gray-800">{{ $b->name }}</td>
                                        <td class="px-4 py-3">{{ $b->phone ?? '-' }}</td>
                                        <td class="px-4 py-3 text-center flex justify-center gap-2">
                                            @auth
                                                @if(!auth()->user()->isHead())
                                                    <a href="{{ route('borrowers.edit', $b->id) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-1 rounded">Edit</a>
                                                    <form action="{{ route('borrowers.destroy', $b->id) }}" method="POST" onsubmit="return confirm('Hapus data ini?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 p-1 rounded">Hapus</button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-500">(Tidak ada aksi)</span>
                                                @endif
                                            @endauth
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-6 text-gray-500">Data peminjam belum ada.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $borrowers->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>