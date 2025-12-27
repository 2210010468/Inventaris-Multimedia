<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Data Supplier (Vendor)') }}
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
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Daftar Supplier</h3>
                        
                        {{-- TOMBOL TAMBAH (Hanya tampil jika bukan HEAD) --}}
                        @auth
                            @if(!auth()->user()->isHead())
                                <a href="{{ route('vendors.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">
                                    + Tambah Vendor
                                </a>
                            @endif
                        @endauth
                    </div>

                    {{-- FORM PENCARIAN (FILTER) --}}
                    <div class="mb-6">
                        <form action="{{ route('vendors.index') }}" method="GET" class="flex gap-2">
                            <input type="text" 
                                   name="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Cari Nama, Email, HP, atau Alamat..." 
                                   class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full md:w-1/3">
                            
                            <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition">
                                Cari
                            </button>

                            @if(request('search'))
                                <a href="{{ route('vendors.index') }}" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition flex items-center">
                                    Reset
                                </a>
                            @endif
                        </form>
                    </div>

                    {{-- TABEL --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Vendor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak (HP/Email)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($vendors as $vendor)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $vendor->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $vendor->phone }} <br>
                                        <span class="text-xs text-blue-500">{{ $vendor->email }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{Str::limit($vendor->address ?? '-', 30) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @auth
                                            @if(!auth()->user()->isHead())
                                                <a href="{{ route('vendors.edit', $vendor->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                                
                                                <form action="{{ route('vendors.destroy', $vendor->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus vendor ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                                </form>
                                            @else
                                                <span class="text-gray-400 text-xs">Read Only</span>
                                            @endif
                                        @endauth
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        Tidak ada data vendor ditemukan.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- PAGINATION LINKS --}}
                    <div class="mt-4">
                        {{ $vendors->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>