<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Pengajuan Pembelian') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Flash Message --}}
            @if(session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    {{-- AREA ATAS: TOMBOL TAMBAH & PENCARIAN --}}
                    <div class="flex flex-col md:flex-row justify-between items-center mb-4 space-y-2 md:space-y-0">
                        
                        {{-- Kiri: Tombol Tambah --}}
                        <div>
                            @auth
                                @if(!in_array(auth()->user()->role, ['kepala', 'head']))
                                    <a href="{{ route('purchases.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 disabled:opacity-25 transition">
                                        + Buat Pengajuan
                                    </a>
                                @endif
                            @endauth
                        </div>

                        {{-- Kanan: Form Filter Sederhana --}}
                        <form action="{{ route('purchases.request') }}" method="GET" class="flex gap-2">
                            <select name="status" class="rounded-md border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="all">Semua Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                            </select>
                            
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kode/Barang..." class="rounded-md border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                            
                            <button type="submit" class="px-3 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700">
                                Cari
                            </button>
                        </form>
                    </div>

                    {{-- TABEL DATA --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">No</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total (Rp)</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($purchases as $index => $purchase)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ $purchases->firstItem() + $index }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                        {{ \Carbon\Carbon::parse($purchase->date)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $purchase->purchase_code }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <div class="font-semibold">{{ $purchase->tool_name }}</div>
                                        <div class="text-xs text-gray-500">Qty: {{ $purchase->quantity }} | {{ $purchase->category->category_name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $purchase->vendor->name }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-mono">
                                        {{ number_format($purchase->subtotal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($purchase->status == 'pending')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Menunggu
                                            </span>
                                        @elseif($purchase->status == 'approved')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Disetujui
                                            </span>
                                        @elseif($purchase->status == 'rejected')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Ditolak
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">
                                        
                                        {{-- AKSI KEPALA (Approve/Reject) --}}
                                        @if(in_array(auth()->user()->role, ['kepala', 'head']))
                                            @if($purchase->status == 'pending')
                                                <div class="flex justify-center items-center gap-2">
                                                    <form action="{{ route('purchases.approve', $purchase->id) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-green-600 hover:text-green-900" onclick="return confirm('Setujui pengajuan ini?')" title="Setujui">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                    
                                                    <button type="button" class="text-red-600 hover:text-red-900" onclick="rejectPurchase({{ $purchase->id }})" title="Tolak">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>

                                                {{-- Form Hidden untuk Reject --}}
                                                <form id="reject-form-{{ $purchase->id }}" action="{{ route('purchases.reject', $purchase->id) }}" method="POST" class="hidden">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="note" id="note-{{ $purchase->id }}">
                                                </form>
                                            @else
                                                <span class="text-gray-400 text-xs">-</span>
                                            @endif

                                        {{-- AKSI STAFF/ADMIN (Hapus) --}}
                                        @else
                                            @if($purchase->status == 'pending')
                                                <form action="{{ route('purchases.destroy', $purchase->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan pengajuan ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 text-xs font-bold">
                                                        Batal
                                                    </button>
                                                </form>
                                            @elseif($purchase->status == 'rejected')
                                                 <button type="button" onclick="alert('Alasan Penolakan: {{ $purchase->rejection_note }}')" class="text-blue-600 hover:text-blue-800 text-xs underline">
                                                    Lihat Alasan
                                                 </button>
                                            @else
                                                <span class="text-gray-400 text-xs">Locked</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                        Data tidak ditemukan.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- PAGINATION --}}
                    <div class="mt-4">
                        {{ $purchases->links() }}
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