<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Pengajuan Pembelian') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- PESAN SUKSES / ERROR --}}
            @if (session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 shadow-sm" role="alert">
                    <p class="font-bold">Berhasil!</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 shadow-sm" role="alert">
                    <p class="font-bold">Gagal!</p>
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- FILTER & TOMBOL TAMBAH --}}
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                        
                        <form action="{{ route('purchases.request') }}" method="GET" class="flex flex-col md:flex-row gap-2 w-full md:w-auto items-center">
                            
                            <input type="text" name="search" value="{{ request('search') }}" 
                                class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm w-full md:w-48"
                                placeholder="Cari Kode / Barang...">

                            <select name="status" class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm w-full md:w-auto">
                                <option value="all">- Semua Status -</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option> <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                            </select>

                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm w-full md:w-auto">
                                Filter
                            </button>

                            @if(request('search') || (request('status') && request('status') != 'all'))
                                <a href="{{ route('purchases.request') }}" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 text-sm transition flex items-center justify-center w-full md:w-auto">
                                    Reset
                                </a>
                            @endif
                        </form>

                        @auth
                            @if(!in_array(auth()->user()->role, ['kepala', 'head']))
                                <a href="{{ route('purchases.create') }}" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow-sm text-center text-sm">
                                    + Ajukan Pembelian
                                </a>
                            @endif
                        @endauth
                    </div>

                    {{-- TABEL DATA --}}
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full table-auto text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600 uppercase font-medium">
                                <tr>
                                    <th class="px-4 py-3 text-center">No</th>
                                    <th class="px-4 py-3">Tanggal & Kode</th>
                                    <th class="px-4 py-3">Barang & Vendor</th>
                                    <th class="px-4 py-3 text-right">Total (Rp)</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($purchases as $index => $purchase)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-center font-medium text-gray-500">
                                            {{ $purchases->firstItem() + $index }}
                                        </td>
                                        
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-gray-800">{{ $purchase->purchase_code }}</div>
                                            <div class="text-xs text-gray-500">
                                                {{ \Carbon\Carbon::parse($purchase->date)->format('d M Y') }}
                                            </div>
                                        </td>

                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-indigo-600">{{ $purchase->tool_name }}</div>
                                            <div class="text-xs text-gray-500">
                                                Qty: {{ $purchase->quantity }} | Vendor: {{ $purchase->vendor->name }}
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 text-right font-medium text-gray-700">
                                            {{ number_format($purchase->subtotal, 0, ',', '.') }}
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            @if($purchase->status == 'pending')
                                                <span class="bg-yellow-100 text-yellow-800 py-1 px-2 rounded-full text-xs font-bold border border-yellow-200">
                                                    Menunggu
                                                </span>
                                            @elseif($purchase->status == 'approved')
                                                <span class="bg-green-100 text-green-800 py-1 px-2 rounded-full text-xs font-bold border border-green-200">
                                                    Disetujui
                                                </span>
                                            @elseif($purchase->status == 'completed') 
                                                {{-- TAMBAHAN UNTUK STATUS SELESAI --}}
                                                <span class="bg-blue-100 text-blue-800 py-1 px-2 rounded-full text-xs font-bold border border-blue-200">
                                                    Selesai
                                                </span>
                                            @elseif($purchase->status == 'rejected')
                                                <span class="bg-red-100 text-red-800 py-1 px-2 rounded-full text-xs font-bold border border-red-200">
                                                    Ditolak
                                                </span>
                                                @if($purchase->rejection_note)
                                                    <div class="text-[10px] text-red-500 mt-1 max-w-[120px] mx-auto truncate" title="{{ $purchase->rejection_note }}">
                                                        Note: {{ $purchase->rejection_note }}
                                                    </div>
                                                @endif
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            <div class="flex justify-center items-center space-x-2">
                                                
                                                @if(in_array(auth()->user()->role, ['kepala', 'head']))
                                                    @if($purchase->status == 'pending')
                                                        <form action="{{ route('purchases.approve', $purchase->id) }}" method="POST">
                                                            @csrf @method('PATCH')
                                                            <button type="submit" class="text-green-600 hover:text-green-900 bg-green-50 p-1 rounded border border-green-200" title="Setujui" onclick="return confirm('Setujui pengajuan ini?')">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                        
                                                        <button type="button" class="text-red-600 hover:text-red-900 bg-red-50 p-1 rounded border border-red-200" title="Tolak" onclick="rejectPurchase({{ $purchase->id }})">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>

                                                        <form id="reject-form-{{ $purchase->id }}" action="{{ route('purchases.reject', $purchase->id) }}" method="POST" class="hidden">
                                                            @csrf @method('PATCH')
                                                            <input type="hidden" name="note" id="note-{{ $purchase->id }}">
                                                        </form>
                                                    @else
                                                        {{-- Indikator kalau sudah selesai/locked buat Kepala --}}
                                                        <span class="text-gray-400 text-xs italic">
                                                            {{ $purchase->status == 'completed' ? 'Tuntas' : 'Locked' }}
                                                        </span>
                                                    @endif

                                                @else
                                                    {{-- STAFF --}}
                                                    @if($purchase->status == 'pending')
                                                        <form action="{{ route('purchases.destroy', $purchase->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan pengajuan ini?');">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 p-1 rounded border border-red-200" title="Batalkan">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="text-gray-400 text-xs italic">
                                                            {{ $purchase->status == 'completed' ? 'Tuntas' : 'Locked' }}
                                                        </span>
                                                    @endif
                                                @endif

                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-8 text-gray-400">
                                            Tidak ada data pengajuan yang ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $purchases->withQueryString()->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        function rejectPurchase(id) {
            let reason = prompt("Masukkan alasan penolakan:");
            if (reason !== null && reason.trim() !== "") {
                document.getElementById('note-' + id).value = reason;
                document.getElementById('reject-form-' + id).submit();
            }
        }
    </script>
</x-app-layout>