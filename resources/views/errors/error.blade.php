<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $errorCode }} Error | {{ config('app.name', 'NetApp1') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom Error Styles -->
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('{{ asset('customs/images/background_error.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.1;
            z-index: -1;
        }

        .error-container {
            max-width: 600px;
            width: 100%;
            margin: 2rem;
        }

        .error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 3rem 2rem;
            text-align: center;
        }

        .error-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            display: block;
        }

        .error-code {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .error-message {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .btn-custom {
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #5a6fd8, #6a4190);
            color: white;
        }

        .btn-secondary-custom {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }

        .btn-secondary-custom:hover {
            background: linear-gradient(135deg, #5a6268, #343a40);
            color: white;
        }

        .btn-danger-custom {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .btn-danger-custom:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            color: white;
        }

        .animation-float {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @media (max-width: 768px) {
            .error-card {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }

            .error-code {
                font-size: 3rem;
            }

            .error-title {
                font-size: 1.25rem;
            }

            .btn-custom {
                padding: 10px 20px;
                font-size: 0.9rem;
                width: 100%;
                justify-content: center;
                margin: 0.25rem 0;
            }
        }

        /* Dark theme support */
        @media (prefers-color-scheme: dark) {
            .error-card {
                background: rgba(33, 37, 41, 0.95);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .error-title {
                color: #e9ecef;
            }

            .error-message {
                color: #adb5bd;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card animation-float">

            @if($errorCode == 404)
                <i class="bi bi-search error-icon text-warning"></i>
            @elseif($errorCode == 403)
                <i class="bi bi-shield-exclamation error-icon text-danger"></i>
            @elseif($errorCode == 500)
                <i class="bi bi-exclamation-triangle error-icon text-danger"></i>
            @elseif($errorCode == 422)
                <i class="bi bi-exclamation-circle error-icon text-warning"></i>
            @else
                <i class="bi bi-exclamation-diamond error-icon text-primary"></i>
            @endif

            <div class="error-code">{{ $errorCode }}</div>
            <h1 class="error-title">{{ $errorTitle }}</h1>
            <p class="error-message">{{ $errorMessage }}</p>

            <div class="action-buttons">
                {{-- Back to Home (selalu tampil kecuali 403) --}}
                @if ($errorCode !== 403)
                    <a href="{{ url('/') }}" class="btn-custom btn-primary-custom">
                        <i class="bi bi-house-door-fill"></i>
                        Back to Home
                    </a>
                @endif

                {{-- Link khusus untuk error 422 --}}
                @if ($errorCode === 422)
                    <a href="{{ route('TrdJewel1.Master.Currency.Detail', ['action' => encryptWithSessionKey('Create')]) }}"
                       class="btn-custom btn-secondary-custom">
                        <i class="bi bi-currency-exchange"></i>
                        Go to Master Currency
                    </a>
                @endif

                {{-- Logout untuk error 403 jika user login --}}
                @if ($errorCode === 403 && auth()->check())
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn-custom btn-danger-custom">
                            <i class="bi bi-box-arrow-right"></i>
                            Sign Out
                        </button>
                    </form>
                @endif

                {{-- Refresh page button --}}
                <button onclick="window.location.reload()" class="btn-custom btn-secondary-custom">
                    <i class="bi bi-arrow-clockwise"></i>
                    Try Again
                </button>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Simple animation script -->
    <script>
        // Add some interactivity
        document.querySelectorAll('.btn-custom').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px) scale(1.02)';
            });

            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-2px) scale(1)';
            });
        });

        // Auto-redirect for some errors after delay
        @if($errorCode === 404)
            setTimeout(function() {
                const homeBtn = document.querySelector('[href="{{ url('/') }}"]');
                if (homeBtn && !document.hidden) {
                    homeBtn.style.animation = 'pulse 1s infinite';
                }
            }, 5000);
        @endif
    </script>
</body>
</html>
