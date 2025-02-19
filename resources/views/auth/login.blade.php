<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DuriGeo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h1 class="text-2xl font-bold text-center mb-6" style="color: #4aa87a;">Welcome to DuriGeo</h1>

        <!-- Form Login -->
        <form method="POST" action="{{ route('login') }}">
            @csrf

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <strong>{{ $errors->first() }}</strong>
                </div>
            @endif

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email<span class="text-red-500">*</span></label>
                <input type="email" name="email" id="email"
                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300"
                    placeholder="Masukkan email" required>
            </div>

            <div class="mt-4 relative">
                <label for="password" class="block text-sm font-medium text-gray-700">Kata Sandi<span class="text-red-500">*</span></label>
                <input type="password" name="password" id="password"
                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300"
                    placeholder="Masukkan kata sandi" required>
                <button type="button" id="togglePassword" class="absolute right-3 top-9 text-gray-600">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <div class="flex items-center justify-between mt-2">
                <label class="flex items-center">
                    <input type="checkbox" class="form-checkbox text-green-500">
                    <span class="ml-2 text-sm text-gray-600">Ingat saya</span>
                </label>
                <a href="#" class="text-sm text-green-700 hover:underline">Lupa kata sandi?</a>
            </div>

            <button type="submit"
                class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition mt-4">
                Masuk
            </button>
        </form>

        <p class="mt-4 text-center text-sm">
            Belum punya akun? <a href="{{ route('register') }}" class="text-green-600 font-bold">Buat Akun</a>
        </p>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            var passwordField = document.getElementById('password');
            var icon = this.querySelector('i');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>
