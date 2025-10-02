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

        .otp-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .otp-card {
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
            max-width: 440px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .otp-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #495057, #6c757d);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }

        .otp-title {
            font-size: 28px;
            font-weight: 700;
            color: #495057;
            margin-bottom: 8px;
        }

        .otp-subtitle {
            color: #6c757d;
            font-size: 16px;
            font-weight: 400;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .otp-input-container {
            margin-bottom: 30px;
        }

        .otp-inputs {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 20px;
        }

        .otp-input {
            width: 50px;
            height: 60px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            color: #495057;
            background: #ffffff;
            transition: all 0.3s ease;
        }

        .otp-input:focus {
            outline: none;
            border-color: #6c757d;
            box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.25);
            transform: scale(1.05);
        }

        .otp-input.filled {
            border-color: #28a745;
            background: #f8fff9;
        }

        .otp-input.error {
            border-color: #dc3545;
            background: #fff8f8;
        }

        .hidden-input {
            position: absolute;
            left: -9999px;
            opacity: 0;
        }

        .btn-verify {
            width: 100%;
            padding: 16px;
            background: #495057;
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .btn-verify:hover {
            background: #343a40;
            box-shadow: 0 4px 12px rgba(73, 80, 87, 0.3);
        }

        .btn-verify:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-resend {
            background: transparent;
            border: 2px solid #6c757d;
            color: #6c757d;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-resend:hover:not(:disabled) {
            background: #6c757d;
            color: white;
            text-decoration: none;
        }

        .btn-resend:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: #f8f9fa;
            color: #6c757d;
            border-color: #dee2e6;
        }

        .btn-resend:disabled:hover {
            background: #f8f9fa;
            color: #6c757d;
        }

        .alert {
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .timer-display {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
        }

        /* Loading Animation */
        .loader-dots {
            display: inline-block;
            position: relative;
            width: 20px;
            height: 20px;
            margin-left: 10px;
        }

        .loader-dots div {
            position: absolute;
            top: 8px;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: white;
            animation-timing-function: cubic-bezier(0, 1, 1, 0);
        }

        .loader-dots div:nth-child(1) {
            left: 2px;
            animation: lds-ellipsis1 0.6s infinite;
        }

        .loader-dots div:nth-child(2) {
            left: 2px;
            animation: lds-ellipsis2 0.6s infinite;
        }

        .loader-dots div:nth-child(3) {
            left: 8px;
            animation: lds-ellipsis2 0.6s infinite;
        }

        .loader-dots div:nth-child(4) {
            left: 14px;
            animation: lds-ellipsis3 0.6s infinite;
        }

        @keyframes lds-ellipsis1 {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }

        @keyframes lds-ellipsis3 {
            0% { transform: scale(1); }
            100% { transform: scale(0); }
        }

        @keyframes lds-ellipsis2 {
            0% { transform: translate(0, 0); }
            100% { transform: translate(6px, 0); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .otp-card {
                padding: 30px 25px;
                margin: 10px;
            }

            .otp-inputs {
                gap: 8px;
            }

            .otp-input {
                width: 45px;
                height: 55px;
                font-size: 20px;
            }

            .otp-title {
                font-size: 24px;
            }
        }

        /* Success Animation */
        @keyframes checkmark {
            0% { stroke-dasharray: 0 50; }
            100% { stroke-dasharray: 50 0; }
        }

        .success-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #28a745;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .checkmark {
            width: 30px;
            height: 30px;
            stroke: white;
            stroke-width: 3;
            fill: none;
            animation: checkmark 0.6s ease-in-out;
        }
    </style>

    <div class="otp-container">
        <div class="otp-card">
            <div class="otp-icon">
                <i class="bi bi-shield-lock"></i>
            </div>

            <h1 class="otp-title">Verifikasi OTP</h1>
            <p class="otp-subtitle">
                Masukkan kode OTP 6 digit yang telah dikirim ke email terdaftar untuk mengakses TrdTire1
            </p>

            @if(session('message'))
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>{{ session('message') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('auth.otp.verify') }}" id="otpForm">
                @csrf

                <div class="otp-input-container">
                    <div class="otp-inputs" id="otpInputs">
                        <input type="text" class="otp-input" maxlength="1" data-index="0">
                        <input type="text" class="otp-input" maxlength="1" data-index="1">
                        <input type="text" class="otp-input" maxlength="1" data-index="2">
                        <input type="text" class="otp-input" maxlength="1" data-index="3">
                        <input type="text" class="otp-input" maxlength="1" data-index="4">
                        <input type="text" class="otp-input" maxlength="1" data-index="5">
                    </div>

                    <!-- Hidden input to store the complete OTP -->
                    <input type="hidden" name="otp" id="otpValue" required>

                    @error('otp')
                        <div class="alert alert-danger">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="timer-display" id="timerDisplay">
                    OTP kadaluarsa dalam 5 menit. Jika kadaluarsa, tekan kirim ulang OTP.
                </div>

                <button type="submit" class="btn-verify" id="verifyBtn">
                    <span id="verifyText">Verifikasi OTP</span>
                    <span id="loadingText" style="display: none;">
                        Memverifikasi
                        <div class="loader-dots">
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </span>
                </button>

                <div>
                    <p style="color: #6c757d; font-size: 14px; margin-bottom: 15px;">
                        Tidak menerima OTP?
                    </p>
                    <button type="button" class="btn-resend" id="resendBtn">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        <span id="resendText">Kirim Ulang OTP</span>
                        <span id="resendCountdown" style="display: none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const otpInputs = document.querySelectorAll('.otp-input');
            const otpValue = document.getElementById('otpValue');
            const verifyBtn = document.getElementById('verifyBtn');
            const form = document.getElementById('otpForm');

            // Focus first input
            otpInputs[0].focus();

            // Handle OTP input
            otpInputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    // Only allow numbers
                    this.value = this.value.replace(/[^0-9]/g, '');

                    if (this.value) {
                        this.classList.add('filled');
                        this.classList.remove('error');

                        // Move to next input
                        if (index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }
                    } else {
                        this.classList.remove('filled');
                    }

                    updateOtpValue();
                });

                input.addEventListener('keydown', function(e) {
                    // Handle backspace
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        otpInputs[index - 1].focus();
                        otpInputs[index - 1].value = '';
                        otpInputs[index - 1].classList.remove('filled');
                        updateOtpValue();
                    }

                    // Handle paste
                    if (e.ctrlKey && e.key === 'v') {
                        e.preventDefault();
                        navigator.clipboard.readText().then(text => {
                            const digits = text.replace(/[^0-9]/g, '').slice(0, 6);
                            fillOtpInputs(digits);
                        });
                    }
                });

                input.addEventListener('focus', function() {
                    this.select();
                });
            });

            function updateOtpValue() {
                const otp = Array.from(otpInputs).map(input => input.value).join('');
                otpValue.value = otp;

                // Enable/disable verify button
                verifyBtn.disabled = otp.length !== 6;

                // Auto submit when complete
                if (otp.length === 6) {
                    setTimeout(() => {
                        form.submit();
                    }, 300);
                }
            }

            function fillOtpInputs(digits) {
                otpInputs.forEach((input, index) => {
                    if (digits[index]) {
                        input.value = digits[index];
                        input.classList.add('filled');
                    }
                });
                updateOtpValue();
            }

            // Resend button cooldown handling
            let resendCooldown = 0;
            const resendBtn = document.getElementById('resendBtn');
            const resendText = document.getElementById('resendText');
            const resendCountdown = document.getElementById('resendCountdown');

            // Check if there's a stored cooldown time
            const storedCooldown = localStorage.getItem('otpResendCooldown');
            if (storedCooldown) {
                const cooldownEnd = parseInt(storedCooldown);
                const now = Date.now();
                if (cooldownEnd > now) {
                    resendCooldown = Math.ceil((cooldownEnd - now) / 1000);
                    startResendCooldown();
                }
            }

            function startResendCooldown() {
                if (resendCooldown <= 0) return;

                resendBtn.disabled = true;
                resendText.style.display = 'none';
                resendCountdown.style.display = 'inline';
                resendCountdown.textContent = `Tunggu ${resendCooldown} detik`;

                const cooldownInterval = setInterval(() => {
                    resendCooldown--;
                    resendCountdown.textContent = `Tunggu ${resendCooldown} detik`;

                    if (resendCooldown <= 0) {
                        clearInterval(cooldownInterval);
                        resendBtn.disabled = false;
                        resendText.style.display = 'inline';
                        resendCountdown.style.display = 'none';
                        localStorage.removeItem('otpResendCooldown');
                    }
                }, 1000);
            }

            // Handle resend button click
            resendBtn.addEventListener('click', function(e) {
                if (resendCooldown > 0 || resendBtn.disabled) {
                    e.preventDefault();
                    return;
                }

                // Set 30 second cooldown
                resendCooldown = 30;
                const cooldownEnd = Date.now() + (30 * 1000);
                localStorage.setItem('otpResendCooldown', cooldownEnd.toString());
                startResendCooldown();

                // Proceed with the redirect after setting cooldown
                setTimeout(() => {
                    window.location.href = '{{ route('auth.otp.resend') }}';
                }, 100);
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                const verifyText = document.getElementById('verifyText');
                const loadingText = document.getElementById('loadingText');

                verifyBtn.disabled = true;
                verifyText.style.display = 'none';
                loadingText.style.display = 'inline-block';
            });

            // Error handling
            @if($errors->has('otp'))
                otpInputs.forEach(input => {
                    input.classList.add('error');
                });
                otpInputs[0].focus();
            @endif
        });
    </script>

    <!-- Add Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</x-auth-layout>
