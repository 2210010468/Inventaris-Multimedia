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
                    
                    {{-- HEADER & TOMBOL TAMBAH --}}
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <h3 class="text-lg font-medium text-gray-900">Status Permohonan</h3>
                        @auth
                            @if(!in_array(auth()->user()->role, ['kepala', 'head']))
                                <a href="{{ route('purchases.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition">
                                    + Ajukan Pembelian Baru
                                </a>
                            @endif
                        @endauth
                    </div>

                    {{-- FILTER SECTION --}}
                    <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <form action="{{ route('purchases.request') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            
                            {{-- Filter 1: Search --}}
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                                <input type="text" name="search" value="{{ request('search') }}" 
                                       placeholder="Kode / Nama Barang..." 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            {{-- Filter 2: Status --}}
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="all">Semua Status</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu (Pending)</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak (Rejected)</option>
                                </select>
                            </div>

                            {{-- Filter 3: Tanggal (Start & End) --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                                <input type="date" name="start_date" value="{{ request('start_date') }}" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                                <input type="date" name="end_date" value="{{ request('end_date') }}" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            {{-- Tombol Filter --}}
                            <div class="md:col-span-4 flex justify-end gap-2">
                                <a href="{{ route('purchases.request') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition">
                                    Reset
                                </a>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                                    Terapkan Filter
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- TABEL DATA --}}
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
                                            <div class="text-xs text-red-500 mt-1 max-w-[150px] truncate cursor-help" title="{{ $purchase->rejection_note }}">
                                                Note: {{ $purchase->rejection_note }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        
                                        {{-- LOGIC TOMBOL UNTUK KEPALA --}}
                                        @if(in_array(auth()->user()->role, ['kepala', 'head']))
                                            @if($purchase->status == 'pending')
                                                <div class="flex justify-center gap-2">
                                                    <form action="{{ route('purchases.approve', $purchase->id) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700" onclick="return confirm('Setujui pengajuan ini?')">Setuju</button>
                                                    </form>
                                                    
                                                    <button type="button" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700" 
                                                        onclick="rejectPurchase({{ $purchase->id }})">
                                                        Tolak
                                                    </button>
                                                </div>
                                                
                                                <form id="reject-form-{{ $purchase->id }}" action="{{ route('purchases.reject', $purchase->id) }}" method="POST" class="hidden">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="note" id="note-{{ $purchase->id }}">
                                                </form>
                                            @endif

                                        {{-- LOGIC UNTUK ADMIN/STAFF --}}
                                        @else
                                            @if($purchase->status == 'pending')
                                                <form action="{{ route('purchases.destroy', $purchase->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Batalkan pengajuan ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Batal</button>
                                                </form>
                                            @else
                                                <span class="text-gray-400 text-xs">Locked</span>
                                            @endif
                                        @endif

                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        Tidak ada data pengajuan yang sesuai filter.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- PAGINATION LINKS --}}
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