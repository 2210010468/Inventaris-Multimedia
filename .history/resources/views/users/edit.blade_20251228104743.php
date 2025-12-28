<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <form action="{{ route('users.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-gray-700">Nama Lengkap</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-bold text-gray-700">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-bold text-gray-700">Role (Jabatan)</label>
                            <select name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin (Staff)</option>
                                <option value="head" {{ $user->role == 'head' ? 'selected' : '' }}>Head (Kepala)</option>
                            </select>
                        </div>

                        <hr class="my-6 border-gray-200">
                        <p class="text-sm text-gray-500 mb-4 italic">* Kosongkan password jika tidak ingin menggantinya.</p>

                        <div class="mb-4">
                            <label class="block text-sm font-bold text-gray-700">Password Baru (Opsional)</label>
                            <input type="password" name="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div class="flex justify-end gap-2">
                            <a href="{{ route('users.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm">Batal</a>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-bold hover:bg-blue-700">Update User</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>