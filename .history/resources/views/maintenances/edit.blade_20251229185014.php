<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Proses Maintenance') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- KOLOM KIRI: EDIT DATA --}}
                <div class="bg-white p-6 shadow-sm rounded-lg">
                    <h3 class="font-bold text-lg mb-4 border-b pb-2">Edit Informasi</h3>
                    
                    <form action="{{ route('maintenances.update', $maintenance->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Info Alat --}}
                        <div class="mb-4 bg-gray-50 p-3 rounded">
                            <label class="block text-xs font-bold text-gray-500 uppercase">Nama Alat</label>
                            <div class="text-lg font-bold text-gray-800">{{ $maintenance->tool->tool_name }}</div>
                            <div class="text-xs text-gray-400">Kode: {{ $maintenance->tool->tool_code }}</div>
                        </div>

                        {{-- Tanggal Mulai --}}
                        <div class="mb-3">
                            <label class="block text-sm font-bold text-gray-700">Tanggal Masuk</label>
                            <input type="date" name="start_date" value="{{ $maintenance->start_date }}" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                        </div>

                        {{-- Jenis Maintenance --}}
                        <div class="mb-3">
                            <label class="block text-sm font-bold text-gray-700">Jenis Perbaikan</label>
                            <select name="maintenance_type_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}" {{ $maintenance->maintenance_type_id == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Action Taken --}}
                        <div class="mb-3">
                            <label class="block text-sm font-bold text-gray-700">Tindakan Perbaikan</label>
                            <textarea name="action_taken" rows="3" placeholder="Apa yang diperbaiki/diganti?" class="w-full border-gray-300 rounded-md shadow-sm text-sm">{{ $maintenance->action_taken }}</textarea>
                        </div>

                        {{-- Catatan Kerusakan --}}
                        <div class="mb-3">
                            <label class="block text-sm font-bold text-gray-700">Catatan Kerusakan</label>
                            <textarea name="note" rows="2" class="w-full border-gray-300 rounded-md shadow-sm text-sm">{{ $maintenance->note }}</textarea>
                        </div>

                        @if($maintenance->status != 'completed')
                        <div class="mt-4 text-right">
                            <button type="submit" class="text-sm bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 shadow">
                                Simpan Detail
                            </button>
                        </div>
                        @endif
                    </form>
                </div>

                {{-- KOLOM KANAN: PENYELESAIAN --}}
                <div class="bg-white p-6 shadow-sm rounded-lg border-2 {{ $maintenance->status == 'completed' ? 'border-green-400' : 'border-gray-100' }}">
                    <h3 class="font-bold text-lg mb-4 border-b pb-2">
                        {{ $maintenance->status == 'completed' ? 'Detail Penyelesaian' : 'Selesaikan Perbaikan' }}
                    </h3>

                    @if($maintenance->status != 'completed')
                        {{-- FORM PENYELESAIAN --}}
                        <form action="{{ route('maintenances.update', $maintenance->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="bg-yellow-50 p-3 rounded-md mb-4 text-xs text-yellow-800 border border-yellow-200">
                                <p class="font-bold">Info Status</p>
                                <p>Status saat ini: <b>SEDANG PROSES</b> (In Progress).</p>
                                <p class="mt-1">Klik tombol di bawah HANYA jika perbaikan sudah tuntas.</p>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700">Tanggal Selesai</label>
                                <input type="date" name="end_date" value="{{ date('Y-m-d') }}" class="w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700">Biaya Perbaikan (Rp)</label>
                                <input type="number" name="cost" value="{{ $maintenance->cost > 0 ? $maintenance->cost : 0 }}" min="0" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="0">
                            </div>

                            <div class="mt-6">
                                <button type="submit" name="complete_maintenance" value="1" class="w-full bg-green-600 text-white font-bold py-3 px-4 rounded hover:bg-green-700 shadow-lg transform transition hover:scale-105">
                                    ✅ Selesai & Stok Kembali
                                </button>
                            </div>
                        </form>
                    @else
                        {{-- TAMPILAN SUDAH SELESAI --}}
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 text-sm">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500">Tindakan</label>
                                    <div class="text-gray-900">{{ $maintenance->action_taken ?? '-' }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500">Tanggal Selesai</label>
                                    <div class="text-gray-900 font-medium">{{ \Carbon\Carbon::parse($maintenance->end_date)->format('d F Y') }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500">Total Biaya</label>
                                    <div class="text-green-600 font-bold text-lg">Rp {{ number_format($maintenance->cost, 0, ',', '.') }}</div>
                                </div>
                            </div>
                            
                            <div class="bg-green-100 p-3 rounded text-center text-green-800 font-bold mt-4 border border-green-200">
                                Status: SELESAI
                            </div>
                            <div class="text-center mt-4">
                                <a href="{{ route('maintenances.index') }}" class="text-gray-500 underline text-sm hover:text-indigo-600">Kembali ke daftar</a>
                            </div>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-app-layout>