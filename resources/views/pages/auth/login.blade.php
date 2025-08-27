<x-auth-layout>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
            position: relative;
            overflow-x: hidden;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Geometric background patterns */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle at 20% 80%, rgba(108, 117, 125, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(73, 80, 87, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(108, 117, 125, 0.03) 0%, transparent 50%);
            z-index: 0;
        }

        /* Remove moving pattern animation */
        body::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                linear-gradient(45deg, transparent 49%, rgba(108, 117, 125, 0.02) 50%, transparent 51%),
                linear-gradient(-45deg, transparent 49%, rgba(73, 80, 87, 0.02) 50%, transparent 51%);
            background-size: 30px 30px;
            z-index: 0;
        }

        @keyframes movePattern {
            0% { transform: translateX(0) translateY(0); }
            25% { transform: translateX(-15px) translateY(-15px); }
            50% { transform: translateX(-30px) translateY(0); }
            75% { transform: translateX(-15px) translateY(15px); }
            100% { transform: translateX(0) translateY(0); }
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        /* Floating particles - disabled for stability */
        .floating-particles {
            display: none;
        }

        .particle {
            position: absolute;
            background: rgba(108, 117, 125, 0.1);
            border-radius: 50%;
            animation: float-up linear infinite;
        }

        .particle:nth-child(1) {
            left: 10%;
            width: 4px;
            height: 4px;
            animation-duration: 15s;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            left: 20%;
            width: 6px;
            height: 6px;
            animation-duration: 18s;
            animation-delay: 2s;
        }

        .particle:nth-child(3) {
            left: 30%;
            width: 3px;
            height: 3px;
            animation-duration: 12s;
            animation-delay: 4s;
        }

        .particle:nth-child(4) {
            left: 40%;
            width: 5px;
            height: 5px;
            animation-duration: 16s;
            animation-delay: 1s;
        }

        .particle:nth-child(5) {
            left: 50%;
            width: 4px;
            height: 4px;
            animation-duration: 14s;
            animation-delay: 3s;
        }

        .particle:nth-child(6) {
            left: 60%;
            width: 7px;
            height: 7px;
            animation-duration: 20s;
            animation-delay: 5s;
        }

        .particle:nth-child(7) {
            left: 70%;
            width: 3px;
            height: 3px;
            animation-duration: 13s;
            animation-delay: 0.5s;
        }

        .particle:nth-child(8) {
            left: 80%;
            width: 5px;
            height: 5px;
            animation-duration: 17s;
            animation-delay: 2.5s;
        }

        .particle:nth-child(9) {
            left: 90%;
            width: 4px;
            height: 4px;
            animation-duration: 15s;
            animation-delay: 4.5s;
        }

        @keyframes float-up {
            0% {
                bottom: -10px;
                opacity: 0;
                transform: translateX(0px) rotate(0deg);
            }
            10% {
                opacity: 0.3;
            }
            90% {
                opacity: 0.3;
            }
            100% {
                bottom: 110vh;
                opacity: 0;
                transform: translateX(20px) rotate(360deg);
            }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(233, 236, 239, 0.5);
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.1),
                0 2px 8px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            padding: 40px;
            width: 100%;
            max-width: 420px;
            transform: translateY(0);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            display: none;
        }

        .login-card:hover::before {
            display: none;
        }

        .login-card:hover {
            transform: none;
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.1),
                0 2px 8px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-img {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 8px;
            overflow: hidden;
        }

        .logo-img img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .app-title {
            font-size: 32px;
            font-weight: 700;
            color: #495057;
            margin-bottom: 8px;
        }

        .app-subtitle {
            color: #6c757d;
            font-size: 16px;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            display: block;
            color: #495057;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            background: #ffffff;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            color: #495057;
            font-size: 16px;
            font-weight: 400;
            transition: all 0.3s ease;
        }

        .form-control::placeholder {
            color: #adb5bd;
        }

        .form-control:focus {
            outline: none;
            border-color: #6c757d;
            background: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.25);
        }

        .password-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #495057;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: #495057;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            display: none;
        }

        .btn-login:hover::before {
            display: none;
        }

        .btn-login:hover {
            background: #343a40;
            transform: none;
            box-shadow: 0 2px 8px rgba(73, 80, 87, 0.2);
        }

        .btn-login:active {
            transform: none;
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 12px 16px;
            color: #721c24;
            margin-bottom: 20px;
            font-size: 14px;
        }

        /* Loading Animation */
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(73, 80, 87, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .loader {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loader-text {
            color: white;
            font-size: 18px;
            font-weight: 500;
            margin-top: 20px;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 25px;
                margin: 10px;
            }

            .app-title {
                font-size: 28px;
            }

            .logo-img {
                width: 70px;
                height: 70px;
            }
        }
    </style>

    <div class="login-container">
        <!-- Floating particles background -->
        <div class="floating-particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>

        <div class="login-card">
            <div class="logo-section">
                <div class="logo-img">
                    <img src="{{ asset('customs/logos/SysConfig1.png') }}" alt="SysConfig1 Logo">
                </div>
                <h1 class="app-title">Nusaevo</h1>
                <p class="app-subtitle">Advanced Business Management System</p>
            </div>

            <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
                @csrf

                @error('code')
                    <div class="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>{{ $message }}
                    </div>
                @enderror

                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="bi bi-person me-2"></i>Username
                    </label>
                    <input class="form-control"
                           type="text"
                           name="code"
                           id="username"
                           autocomplete="username"
                           placeholder="Enter your username"
                           required
                           autofocus
                           value="{{ old('code') }}" />
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="bi bi-lock me-2"></i>Password
                    </label>
                    <div class="password-group">
                        <input class="form-control"
                               type="password"
                               name="password"
                               id="password"
                               autocomplete="current-password"
                               placeholder="Enter your password"
                               required />
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="bi bi-eye" id="passwordIcon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <span id="loginText">Sign In</span>
                    <span id="loadingText" style="display: none;">
                        <i class="bi bi-arrow-repeat me-2" style="animation: spin 1s linear infinite;"></i>
                        Signing In...
                    </span>
                </button>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loader-overlay" id="loaderOverlay">
        <div style="text-align: center;">
            <div class="loader"></div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'bi bi-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'bi bi-eye';
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');
            const loadingText = document.getElementById('loadingText');
            const loaderOverlay = document.getElementById('loaderOverlay');

            // Show loading state
            loginBtn.disabled = true;
            loginText.style.display = 'none';
            loadingText.style.display = 'inline-block';

            // Show overlay after a short delay
            setTimeout(() => {
                loaderOverlay.style.display = 'flex';
            }, 500);
        });

        // Add floating animation to form elements
        document.addEventListener('DOMContentLoaded', function() {
            const formControls = document.querySelectorAll('.form-control');

            formControls.forEach((control, index) => {
                control.style.animationDelay = `${index * 0.1}s`;
                control.classList.add('animate__animated', 'animate__fadeInUp');
            });
        });

        // Prevent double submission
        let formSubmitted = false;
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (formSubmitted) {
                e.preventDefault();
                return false;
            }
            formSubmitted = true;
        });
    </script>

    <!-- Add animate.css for smooth animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</x-auth-layout>
