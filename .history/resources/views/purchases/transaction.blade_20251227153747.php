<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transaction') }}
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    {{-- HEADER & FILTER SECTION --}}
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Pending Transactions</h3>
                            <p class="text-sm text-gray-500">Upload bukti pembayaran untuk menyelesaikan transaksi.</p>
                        </div>

                        {{-- FORM FILTER (Search + Date Range) --}}
                        <form action="{{ url()->current() }}" method="GET" class="flex flex-col md:flex-row gap-2 w-full md:w-auto items-center">
                            
                            {{-- Filter Tanggal --}}
                            <div class="flex gap-2">
                                <input type="date" name="start_date" value="{{ request('start_date') }}" class="rounded-md border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500 py-2" title="Tanggal Mulai">
                                <span class="self-center text-gray-400">-</span>
                                <input type="date" name="end_date" value="{{ request('end_date') }}" class="rounded-md border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500 py-2" title="Tanggal Sampai">
                            </div>

                            {{-- Filter Search --}}
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kode / Vendor..." class="rounded-md border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500 py-2 w-full md:w-48">

                            {{-- Tombol --}}
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md text-sm hover:bg-gray-700 w-full md:w-auto">
                                Filter
                            </button>
                            
                            @if(request('search') || request('start_date') || request('end_date'))
                                <a href="{{ url()->current() }}" class="bg-red-500 text-white px-3 py-2 rounded-md text-sm hover:bg-red-600 w-full md:w-auto text-center">
                                    Reset
                                </a>
                            @endif
                        </form>
                    </div>

                    {{-- TABLE --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Date & Ref</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Item Details</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Vendor</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Amount (Approved)</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($purchases as $p)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="font-bold text-blue-600">{{ $p->purchase_code }}</div>
                                        <div class="text-gray-500 text-xs">
                                            {{ \Carbon\Carbon::parse($p->date)->format('d M Y') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-900">
                                        <div class="font-bold text-base">{{ $p->tool_name }}</div>
                                        <div class="text-xs text-gray-500 mb-1">{{ $p->category->category_name ?? '-' }}</div>
                                        @if($p->specification)
                                            <div class="text-xs bg-yellow-50 text-yellow-800 border border-yellow-200 p-1 px-2 rounded inline-block">
                                                {{ $p->specification }}
                                            </div>
                                        @endif
                                        <div class="mt-1 font-semibold text-gray-600 text-xs">Qty: {{ $p->quantity }} Unit</div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-600">
                                        {{ $p->vendor->name }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        <div class="text-gray-400 text-xs">Est. Unit: Rp {{ number_format($p->unit_price, 0, ',', '.') }}</div>
                                        <div class="font-bold text-lg text-gray-800">Rp {{ number_format($p->subtotal, 0, ',', '.') }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <button 
                                            onclick="openModal('{{ $p->id }}', '{{ addslashes($p->tool_name) }}', '{{ $p->unit_price }}')"
                                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-sm text-sm flex items-center justify-center gap-2 mx-auto transition-all transform hover:scale-105">
                                            Process
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        <p class="text-lg font-medium">No Pending Transactions Found</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    {{-- PAGINATION --}}
                    <div class="mt-4">
                        {{ $purchases->withQueryString()->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- MODAL UPLOAD --}}
    <div id="uploadModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                
                <form id="evidenceForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Complete Transaction
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 mb-4">
                                        Upload nota/bukti pembayaran untuk: <br>
                                        <strong id="modalToolName" class="text-gray-900 text-base">Nama Barang</strong>
                                    </p>
                                    <div class="mb-4">
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Upload Invoice / Receipt</label>
                                        <input type="file" name="proof_photo" required class="block w-full text-sm text-gray-500 border border-gray-300 rounded-md p-1" accept="image/*">
                                    </div>
                                    <div class="mb-2">
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Final Price / Unit (Rp)</label>
                                        <input type="number" name="real_price" id="modalPrice" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                        <p class="text-xs text-gray-400 mt-1">Sesuaikan harga jika berbeda dengan estimasi awal.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Confirm & Save</button>
                        <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        function openModal(id, name, price) {
            const form = document.getElementById('evidenceForm');
            // Pastikan route ini sesuai dengan route POST di web.php
            const url = "{{ route('purchases.evidence', ':id') }}"; 
            form.action = url.replace(':id', id);
            
            document.getElementById('modalToolName').innerText = name;
            document.getElementById('modalPrice').value = price;
            document.getElementById('uploadModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('uploadModal').classList.add('hidden');
        }
    </script>
</x-app-layout>