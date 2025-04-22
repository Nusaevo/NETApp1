<!--begin::Card-->
<div class="card card-flush w-lg-650px py-5">
    <!--begin::Card body-->
    <div class="card-body py-15 py-lg-20">

        <!--begin::Page bg image-->
        <style>
            body {}

            [data-bs-theme="dark"] body::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-image: url('{{ asset('customs/images/background_error.jpg') }}');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                opacity: 0.3;
                /* Menambah opacity pada background image */
                z-index: -1;
                /* Pastikan overlay berada di belakang konten */
            }

            body::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-image: url('{{ asset('customs/images/background_error.jpg') }}');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                opacity: 0.3;
                /* Menambah opacity pada background image */
                z-index: -1;
                /* Pastikan overlay berada di belakang konten */
            }

            body {
                position: relative;
                font-family: 'Arial', sans-serif;
                color: #333;
                text-align: center;
                padding: 50px;
                overflow: hidden;
                /* Untuk menghindari masalah layout */
            }

            h1 {
                color: #e74c3c;
                /* Warna merah */
                font-size: 36px;
                /* Ukuran font lebih besar */
                font-weight: bold;
                /* Tebal */
                margin-bottom: 20px;
                text-transform: uppercase;
                /* Huruf kapital semua */
            }

            p {
                font-size: 18px;
                color: #e74c3c;
                /* Warna merah untuk pesan */
                font-weight: bold;
            }

            a {
                display: inline-block;
                padding: 10px 20px;
                background-color: #3498db;
                color: #fff;
                text-decoration: none;
                border-radius: 5px;
                font-weight: bold;
                transition: background-color 0.3s ease;
                margin: 5px;
            }

            a:hover {
                background-color: #2980b9;
            }

            .container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .container a.btn,
            .container button.btn {
                display: inline-flex;
                align-items: center;
                gap: .5rem;
                padding: .75rem 1.5rem;
                font-weight: 600;
                border-radius: 2rem;
                text-decoration: none;
                transition: transform .1s ease, box-shadow .15s ease;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            }

            .container a.btn:hover,
            .container button.btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            .btn-primary {
                background: linear-gradient(135deg, #4e73df, #224abe);
                color: #fff;
                border: none;
            }

            .btn-primary:hover {
                background: linear-gradient(135deg, #3b5acf, #1a33a8);
            }

            /* Secondary */
            .btn-secondary {
                background: #6c757d;
                color: #fff;
                border: none;
            }

            .btn-secondary:hover {
                background: #5a6268;
            }

            /* Danger */
            .btn-danger {
                background: #e74c3c;
                color: #fff;
                border: none;
            }

            .btn-danger:hover {
                background: #c0392b;
            }
        </style>


        <!--end::Page bg image-->

        <!--begin::Title-->
        <div class="container">
            <h1 class="text-danger text-uppercase fw-bold mb-3">{{ $errorCode }} – {{ $errorTitle }}</h1>
            <p class="fs-5 text-danger fw-semibold mb-4">{{ $errorMessage }}</p>

            {{-- Back to Home hanya kalau bukan 403 --}}
            @if ($errorCode !== 403)
                <a href="{{ url('/') }}" class="btn btn-primary me-2">
                    <i class="bi bi-house-door-fill me-1"></i> Back to Home
                </a>
            @endif

            {{-- Link khusus 422 --}}
            @if ($errorCode === 422)
                <a href="{{ route('TrdJewel1.Master.Currency.Detail', ['action' => encryptWithSessionKey('Create')]) }}"
                    class="btn btn-secondary me-2">
                    <i class="bi bi-currency-exchange me-1"></i> Go to Master Currency
                </a>
            @endif

            {{-- Logout hanya saat 403 dan kalau user auth --}}
            @if ($errorCode === 403 && auth()->check())
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                    </button>
                </form>
            @endif

        </div>
    </div>
    <!--end::Card body-->
</div>
<!--end::Card-->
