<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- ========================================== --}}
            {{-- TAMPILAN KHUSUS KEPALA (MONITORING) --}}
            {{-- ========================================== --}}
            @if(auth()->user()->isHead())
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-700 mb-4">Ringkasan Laporan (Kepala)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-500">
                            <div class="text-gray-500 text-sm">Peminjaman Bulan Ini</div>
                            <div class="text-3xl font-bold text-gray-800">{{ $data['monthly_borrowings'] }}</div>
                            <div class="text-xs text-gray-400 mt-1">Transaksi</div>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-yellow-500">
                            <div class="text-gray-500 text-sm">Sedang Dipinjam</div>
                            <div class="text-3xl font-bold text-gray-800">{{ $data['active_borrowings'] }}</div>
                            <div class="text-xs text-gray-400 mt-1">Transaksi Aktif</div>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-red-500">
                            <div class="text-gray-500 text-sm">Terlambat Kembali</div>
                            <div class="text-3xl font-bold text-red-600">{{ $data['overdue_items'] }}</div>
                            <div class="text-xs text-gray-400 mt-1">Perlu ditindaklanjuti</div>
                        </div>
                    </div>
                </div>

                {{-- Tabel Ringkas Aktivitas Terakhir --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h4 class="font-bold text-gray-700 mb-4">5 Aktivitas Terakhir</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2">Peminjam</th>
                                        <th class="px-4 py-2">Tanggal</th>
                                        <th class="px-4 py-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data['recent_activities'] as $activity)
                                        <tr class="border-b">
                                            <td class="px-4 py-2 font-medium text-gray-900">{{ $activity->borrower->name }}</td>
                                            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($activity->borrow_date)->format('d M Y') }}</td>
                                            <td class="px-4 py-2">
                                                @if($activity->borrowing_status == 'active')
                                                    <span class="text-yellow-600 font-bold">Dipinjam</span>
                                                @else
                                                    <span class="text-green-600 font-bold">Kembali</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="px-4 py-2 text-center">Belum ada data.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="{{ route('borrowings.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Lihat Semua Data →</a>
                        </div>
                    </div>
                </div>

            {{-- ========================================== --}}
            {{-- TAMPILAN KHUSUS ADMIN (OPERASIONAL) --}}
            {{-- ========================================== --}}
            @else
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-700 mb-4">Panel Operasional (Admin)</h3>
                    
                    {{-- Statistik Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                        <div class="bg-indigo-600 rounded-lg p-4 text-white shadow-lg">
                            <div class="text-indigo-100 text-sm">Total Barang/Alat</div>
                            <div class="text-2xl font-bold">{{ $data['total_tools'] }}</div>
                        </div>
                        <div class="bg-gray-700 rounded-lg p-4 text-white shadow-lg">
                            <div class="text-gray-200 text-sm">Total User</div>
                            <div class="text-2xl font-bold">{{ $data['total_users'] }}</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                            <div class="text-gray-500 text-sm">Sedang Dipinjam</div>
                            <div class="text-2xl font-bold text-gray-800">{{ $data['active_borrowings'] }}</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                            <div class="text-gray-500 text-sm">Dikembalikan Hari Ini</div>
                            <div class="text-2xl font-bold text-green-600">{{ $data['returned_today'] }}</div>
                        </div>
                    </div>

                    {{-- Quick Actions (Menu Cepat) --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h4 class="font-bold text-gray-700 mb-4">Menu Cepat</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            
                            {{-- Tombol Transaksi --}}
                            <a href="{{ route('borrowings.create') }}" class="flex items-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 border border-indigo-200 transition">
                                <div class="p-3 bg-indigo-500 rounded-full text-white mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-bold text-indigo-900">Peminjaman Baru</p>
                                    <p class="text-xs text-indigo-600">Input transaksi</p>
                                </div>
                            </a>

                            {{-- Tombol Daftar Barang --}}
                            {{-- Pastikan route 'tools.index' ada, atau hapus tombol ini --}}
                            <a href="#" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 border border-gray-200 transition">
                                <div class="p-3 bg-gray-500 rounded-full text-white mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900">Data Alat</p>
                                    <p class="text-xs text-gray-500">Kelola inventaris</p>
                                </div>
                            </a>

                            {{-- Tombol Laporan --}}
                            <a href="{{ route('borrowings.index') }}" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 border border-green-200 transition">
                                <div class="p-3 bg-green-500 rounded-full text-white mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-bold text-green-900">Riwayat Transaksi</p>
                                    <p class="text-xs text-green-600">Lihat semua data</p>
                                </div>
                            </a>

                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>