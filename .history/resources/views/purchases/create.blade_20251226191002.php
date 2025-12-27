<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pengajuan Pembelian Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <strong>Error Input:</strong>
                    <ul class="mt-2 list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('purchases.store') }}" method="POST">
                @csrf
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-6 border-b pb-2">Formulir Pengajuan</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tanggal Pengajuan</label>
                                    <input type="date" name="date" value="{{ date('Y-m-d') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Supplier (Vendor)</label>
                                    <select name="vendor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                                        <option value="">-- Pilih Vendor --</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nama Barang / Alat</label>
                                    <input type="text" name="tool_name" value="{{ old('tool_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: Bor Listrik Makita" required>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Kategori Alat</label>
                                    <select name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                                {{ $cat->category_name ?? $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Spesifikasi Detail</label>
                                <textarea name="specification" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Warna, Daya, Ukuran, dll...">{{ old('specification') }}</textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Jumlah (Qty)</label>
                                <input type="number" name="quantity" id="qty" value="{{ old('quantity', 1) }}" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required oninput="calculateTotal()">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Estimasi Harga Satuan (Rp)</label>
                                <input type="number" name="unit_price" id="price" value="{{ old('unit_price') }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required oninput="calculateTotal()">
                            </div>

                            <div class="md:col-span-2 bg-gray-50 p-4 rounded text-right border">
                                <span class="text-gray-600 font-bold mr-2">ESTIMASI TOTAL:</span>
                                <span class="text-2xl font-bold text-blue-600" id="totalDisplay">Rp 0</span>
                            </div>

                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <a href="{{ route('purchases.request') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Batal</a>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded font-bold hover:bg-blue-700 shadow">AJUKAN PEMBELIAN</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function calculateTotal() {
            const qty = parseFloat(document.getElementById('qty').value) || 0;
            const price = parseFloat(document.getElementById('price').value) || 0;
            const total = qty * price;
            document.getElementById('totalDisplay').innerText = "Rp " + total.toLocaleString('id-ID');
        }
    </script>
</x-app-layout>