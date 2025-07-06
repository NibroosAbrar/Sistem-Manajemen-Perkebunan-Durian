<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Symadu</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #eef2ff;
            position: relative;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Vibrant gradient background */
        .gradient-bg {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: linear-gradient(135deg, #ebf9eb 0%, #d4edda 100%);
            opacity: 0.9;
            z-index: -3;
        }

        /* Colorful mesh gradient overlay */
        .mesh-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 20% 20%, rgba(76, 175, 80, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 80% 30%, rgba(39, 147, 61, 0.07) 0%, transparent 30%),
                radial-gradient(circle at 40% 70%, rgba(129, 199, 132, 0.09) 0%, transparent 40%),
                radial-gradient(circle at 70% 80%, rgba(200, 230, 201, 0.08) 0%, transparent 35%);
            opacity: 1;
            z-index: -2;
        }

        /* Modern geometric pattern */
        .geo-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            opacity: 0.4;
            background-image:
                linear-gradient(45deg, rgba(46, 125, 50, 0.04) 25%, transparent 25%),
                linear-gradient(-45deg, rgba(46, 125, 50, 0.04) 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, rgba(46, 125, 50, 0.04) 75%),
                linear-gradient(-45deg, transparent 75%, rgba(46, 125, 50, 0.04) 75%);
            background-size: 30px 30px;
            background-position: 0 0, 0 15px, 15px -15px, -15px 0px;
            animation: pattern-shift 40s linear infinite;
        }

        /* Floating blobs */
        .blobs-container {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            z-index: -1;
            pointer-events: none;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.15;
        }

        .blob-1 {
            background: linear-gradient(135deg, #43a047 0%, #66bb6a 100%);
            width: 450px;
            height: 450px;
            top: -150px;
            right: -100px;
            animation: float-blob1 30s infinite alternate ease-in-out;
        }

        .blob-2 {
            background: linear-gradient(135deg, #2e7d32 0%, #43a047 100%);
            width: 350px;
            height: 350px;
            bottom: -100px;
            left: -50px;
            animation: float-blob2 25s infinite alternate-reverse ease-in-out;
        }

        .blob-3 {
            background: linear-gradient(135deg, #66bb6a 0%, #81c784 100%);
            width: 250px;
            height: 250px;
            top: 40%;
            left: 25%;
            animation: float-blob3 20s infinite alternate ease-in-out;
        }

        /* Sparkling dots */
        .sparkles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
            pointer-events: none;
        }

        .sparkle {
            position: absolute;
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background-color: rgba(46, 125, 50, 0.35);
        }

        .sparkle:nth-child(1) { left: 10%; top: 20%; opacity: 0.7; animation: twinkle 4s infinite ease-in-out 1s; }
        .sparkle:nth-child(2) { left: 20%; top: 40%; opacity: 0.6; animation: twinkle 5s infinite ease-in-out 2s; }
        .sparkle:nth-child(3) { left: 30%; top: 60%; opacity: 0.8; animation: twinkle 6s infinite ease-in-out 0s; }
        .sparkle:nth-child(4) { left: 40%; top: 80%; opacity: 0.5; animation: twinkle 7s infinite ease-in-out 3s; }
        .sparkle:nth-child(5) { left: 50%; top: 10%; opacity: 0.7; animation: twinkle 5s infinite ease-in-out 1s; }
        .sparkle:nth-child(6) { left: 60%; top: 30%; opacity: 0.6; animation: twinkle 4s infinite ease-in-out 2s; }
        .sparkle:nth-child(7) { left: 70%; top: 50%; opacity: 0.8; animation: twinkle 6s infinite ease-in-out 0s; }
        .sparkle:nth-child(8) { left: 80%; top: 70%; opacity: 0.5; animation: twinkle 7s infinite ease-in-out 3s; }
        .sparkle:nth-child(9) { left: 90%; top: 90%; opacity: 0.7; animation: twinkle 5s infinite ease-in-out 1s; }
        .sparkle:nth-child(10) { left: 15%; top: 35%; opacity: 0.6; animation: twinkle 4s infinite ease-in-out 2s; }
        .sparkle:nth-child(11) { left: 25%; top: 55%; opacity: 0.8; animation: twinkle 6s infinite ease-in-out 0s; }
        .sparkle:nth-child(12) { left: 35%; top: 75%; opacity: 0.5; animation: twinkle 7s infinite ease-in-out 3s; }
        .sparkle:nth-child(13) { left: 45%; top: 15%; opacity: 0.7; animation: twinkle 5s infinite ease-in-out 1s; }
        .sparkle:nth-child(14) { left: 55%; top: 45%; opacity: 0.6; animation: twinkle 4s infinite ease-in-out 2s; }
        .sparkle:nth-child(15) { left: 65%; top: 65%; opacity: 0.8; animation: twinkle 6s infinite ease-in-out 0s; }

        /* Professional card styling */
        .card-login {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            box-shadow:
                0 10px 25px rgba(0, 0, 0, 0.03),
                0 5px 10px rgba(0, 0, 0, 0.02),
                0 1px 3px rgba(0, 0, 0, 0.03),
                0 20px 40px rgba(67, 160, 71, 0.06);
            overflow: hidden;
            position: relative;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.7);
        }

        .card-login::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #2e7d32, #66bb6a, #81c784);
            z-index: 1;
        }

        .card-login:hover {
            transform: translateY(-6px);
            box-shadow:
                0 15px 35px rgba(0, 0, 0, 0.08),
                0 5px 15px rgba(0, 0, 0, 0.05),
                0 20px 40px rgba(67, 160, 71, 0.12);
        }

        /* Form elements styling */
        .input-field {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background-color: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(209, 213, 219, 0.8);
        }

        .input-field:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 160, 71, 0.15);
            background-color: white;
            border-color: rgba(67, 160, 71, 0.3);
        }

        .btn-login {
            background: linear-gradient(135deg, #2e7d32 0%, #43a047 100%);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .btn-login::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: 0.5s;
        }

        .btn-login:hover::after {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(46, 125, 50, 0.25);
        }

        .form-checkbox {
            border-radius: 4px;
            width: 1.1rem;
            height: 1.1rem;
        }

        .brand-title {
            background: linear-gradient(135deg, #2e7d32 0%, #66bb6a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            display: inline-block;
        }

        /* Animations */
        @keyframes float-blob1 {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(40px, 60px) rotate(8deg); }
        }

        @keyframes float-blob2 {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(50px, -30px) rotate(-8deg); }
        }

        @keyframes float-blob3 {
            0% { transform: translate(0, 0) scale(1) rotate(0deg); }
            50% { transform: translate(30px, 20px) scale(1.1) rotate(5deg); }
            100% { transform: translate(-20px, -10px) scale(1) rotate(-5deg); }
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.2; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.5); }
        }

        @keyframes pattern-shift {
            0% { background-position: 0 0, 0 15px, 15px -15px, -15px 0px; }
            100% { background-position: 300px 300px, 300px 315px, 315px 285px, 285px 300px; }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="gradient-bg"></div>
    <div class="mesh-gradient"></div>
    <div class="geo-pattern"></div>

    <div class="blobs-container">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <div class="sparkles">
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
    </div>

    <div class="w-full max-w-md card-login p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold brand-title">Welcome to Symadu</h1>
            <p class="text-gray-600 mt-2">Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        <!-- Form Login -->
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <strong>{{ $errors->first() }}</strong>
                </div>
            @endif

            <div>
                <label for="login" class="block text-sm font-medium text-gray-700 mb-1">Username atau Email<span class="text-red-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                    </div>
                    <input type="text" name="login" id="login"
                        class="input-field w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        placeholder="Masukkan username atau email" required value="{{ $guestCredentials['login'] ?? '' }}">
                </div>
            </div>

            <div class="relative">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi<span class="text-red-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="password" name="password" id="password"
                        class="input-field w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        placeholder="Masukkan kata sandi" required value="{{ $guestCredentials['password'] ?? '' }}">
                    <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-600 focus:outline-none">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input type="checkbox" class="form-checkbox text-green-600 border-gray-300 rounded focus:ring-0">
                    <span class="ml-2 text-sm text-gray-700">Ingat saya</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-sm text-green-600 hover:text-green-800 hover:underline transition-colors">Lupa kata sandi?</a>
            </div>

            <button type="submit"
                class="btn-login w-full text-white py-3 px-4 rounded-lg font-medium text-base focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Masuk
            </button>
        </form>

        <div class="mt-8 text-center">
            <p class="text-gray-600">
                Belum punya akun? <a href="{{ route('register') }}" class="text-green-600 font-semibold hover:text-green-800 hover:underline transition-colors">Buat Akun</a>
            </p>
        </div>
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
