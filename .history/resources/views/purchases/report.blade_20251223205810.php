<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laporan Pembelian') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 printable">
                <form method="GET" class="flex gap-3 items-end mb-4">
                    <div>
                        <label class="block text-sm text-gray-700">Dari Tanggal</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="mt-1 rounded border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="mt-1 rounded border-gray-300">
                    </div>
                    <div>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Filter</button>
                    </div>
                    <div class="ml-auto">
                        <a href="?{{ http_build_query(array_merge(request()->all(), ['format' => 'csv'])) }}" class="px-4 py-2 bg-green-600 text-white rounded mr-2">Download CSV</a>
                        <button type="button" onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white rounded">Cetak</button>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm table-auto">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 text-left">Tanggal</th>
                                <th class="px-3 py-2 text-left">Kode</th>
                                <th class="px-3 py-2 text-left">Vendor</th>
                                <th class="px-3 py-2 text-left">Total</th>
                                <th class="px-3 py-2 text-left">Status</th>
                                <th class="px-3 py-2 text-left">Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $purchase)
                                <tr>
                                    <td class="px-3 py-2">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') }}</td>
                                    <td class="px-3 py-2">{{ $purchase->purchase_code }}</td>
                                    <td class="px-3 py-2">{{ $purchase->vendor->name }}</td>
                                    <td class="px-3 py-2">Rp {{ number_format($purchase->total_amount,0,',','.') }}</td>
                                    <td class="px-3 py-2">{{ ucfirst($purchase->status) }}</td>
                                    <td class="px-3 py-2">{{ $purchase->user->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-4 text-center text-gray-500">Tidak ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            .printable, .printable * { visibility: visible; }
        }
    </style>
</x-app-layout>
