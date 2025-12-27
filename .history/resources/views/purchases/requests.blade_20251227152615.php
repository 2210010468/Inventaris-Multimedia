<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Pengajuan Pembelian') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Flash Messages --}}
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
                    
                    {{-- HEADER: Title, Filter, & Button --}}
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        
                        {{-- Judul --}}
                        <h3 class="text-lg font-medium text-gray-900 whitespace-nowrap">Status Permohonan</h3>

                        {{-- Area Kanan: Filter + Tombol Tambah --}}
                        <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto items-center">
                            
                            {{-- FORM FILTER & PENCARIAN --}}
                            <form action="{{ route('purchases.request') }}" method="GET" class="flex gap-2 w-full md:w-auto">
                                <select name="status" class="rounded-md border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500 py-2">
                                    <option value="all">Semua Status</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                                </select>
                                
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kode/Barang..." class="rounded-md border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500 py-2">
                                
                                <button type="submit" class="bg-gray-800 text-white px-3 py-2 rounded-md text-sm hover:bg-gray-700">
                                    Cari
                                </button>
                            </form>

                            {{-- TOMBOL TAMBAH (Hanya user biasa) --}}
                            @auth
                                @if(!in_array(auth()->user()->role, ['kepala', 'head']))
                                    <a href="{{ route('purchases.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow text-sm whitespace-nowrap">
                                        + Baru
                                    </a>
                                @endif
                            @endauth
                        </div>
                    </div>

                    {{-- TABLE --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal & Kode</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Barang</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Estimasi Total</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($purchases as $purchase)
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="font-bold">{{ $purchase->purchase_code }}</div>
                                        <div class="text-gray-500 text-xs">
                                            {{ \Carbon\Carbon::parse($purchase->date)->format('d M Y') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-900">
                                        <div class="font-semibold text-blue-600">{{ $purchase->tool_name }}</div>
                                        <div class="text-gray-500 text-xs">
                                            {{ $purchase->category->category_name ?? '-' }} | Qty: {{ $purchase->quantity }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-500">
                                        {{ $purchase->vendor->name }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        Rp {{ number_format($purchase->subtotal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        @php
                                            $colors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'approved' => 'bg-green-100 text-green-800',
                                                'rejected' => 'bg-red-100 text-red-800',
                                            ];
                                            $statusLabel = [
                                                'pending' => 'Menunggu',
                                                'approved' => 'Disetujui',
                                                'rejected' => 'Ditolak',
                                            ];
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $colors[$purchase->status] ?? 'bg-gray-100' }}">
                                            {{ $statusLabel[$purchase->status] ?? $purchase->status }}
                                        </span>
                                        @if($purchase->status == 'rejected' && $purchase->rejection_note)
                                            <div class="text-xs text-red-500 mt-1 max-w-[150px] truncate" title="{{ $purchase->rejection_note }}">
                                                Note: {{ $purchase->rejection_note }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        
                                        {{-- LOGIC KEPALA --}}
                                        @if(in_array(auth()->user()->role, ['kepala', 'head']))
                                            @if($purchase->status == 'pending')
                                                <div class="flex justify-center gap-2">
                                                    <form action="{{ route('purchases.approve', $purchase->id) }}" method="POST">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700" onclick="return confirm('Setujui pengajuan ini?')">Setuju</button>
                                                    </form>
                                                    <button type="button" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700" onclick="rejectPurchase({{ $purchase->id }})">Tolak</button>
                                                </div>
                                                <form id="reject-form-{{ $purchase->id }}" action="{{ route('purchases.reject', $purchase->id) }}" method="POST" class="hidden">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="note" id="note-{{ $purchase->id }}">
                                                </form>
                                            @else
                                                <span class="text-gray-400 text-xs">-</span>
                                            @endif
                                        
                                        {{-- LOGIC STAFF --}}
                                        @else
                                            @if($purchase->status == 'pending')
                                                <form action="{{ route('purchases.destroy', $purchase->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Batalkan pengajuan ini?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 font-bold">Batal</button>
                                                </form>
                                            @else
                                                <span class="text-gray-400 text-xs">Locked</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Data tidak ditemukan.</td>
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