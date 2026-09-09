@extends('layouts.guest')

@section('title', 'Masuk — IceSum')

@section('content')
    <div class="rounded-xl border border-slate-200 bg-white px-6 py-8 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <form class="space-y-6" method="POST" action="{{ route('login') }}">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Alamat
                    Email</label>
                <div class="relative mt-1 rounded-lg shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                        class="block w-full rounded-lg border border-slate-200 py-2.5 pl-10 pr-3 text-sm transition duration-150 ease-in-out focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        placeholder="admin@perusahaan.com">
                </div>
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Kata
                    Sandi</label>
                <div class="relative mt-1 rounded-lg shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 11c0-1.1.9-2 2-2m-6 2V7a4 4 0 118 0v4m-9 0h10a2 2 0 012 2v7a2 2 0 01-2 2H7a2 2 0 01-2-2v-7a2 2 0 012-2z">
                            </path>
                        </svg>
                    </div>
                    <input type="password" name="password" id="password" required
                        class="block w-full rounded-lg border border-slate-200 py-2.5 pl-10 pr-10 text-sm transition duration-150 ease-in-out focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        placeholder="********">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <button type="button" onclick="togglePassword()"
                            class="text-slate-400 hover:text-slate-600 focus:outline-none"
                            aria-label="Tampilkan kata sandi">
                            <svg id="eye-icon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                </path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember-me" name="remember" type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary">
                    <label for="remember-me" class="ml-2 block text-sm text-slate-600 dark:text-slate-400">Ingat
                        Saya</label>
                </div>
                <div class="text-sm">
                    <a href="#" class="font-medium text-primary hover:text-blue-700">Lupa Password?</a>
                </div>
            </div>

            <div>
                <button type="submit"
                    class="flex w-full justify-center rounded-lg border border-transparent bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 hover:bg-blue-700">
                    Masuk ke Sistem
                </button>
            </div>
        </form>
    </div>

    <p class="mt-6 text-center text-xs text-slate-400">
        <a href="https://kodebagus.com" target="_blank" rel="noopener noreferrer" class="text-primary hover:text-blue-700">
            KodeBagus
        </a> 2026 All Rights Reserved.
    </p>
@endsection

@push('scripts')
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML =
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML =
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
            }
        }
    </script>
@endpush
