<!--begin::Card-->
<div class="card card-flush w-lg-650px py-5">
    <!--begin::Card body-->
    <div class="card-body py-15 py-lg-20">

        <!--begin::Page bg image-->
        <style>
            body {}

            [data-bs-theme="dark"] body {
                background-image: url('{{ image('auth/bg7-dark.jpg') }}');
            }


            body {
                font-family: 'Arial', sans-serif;
                color: #333;
                background-image: url('{{ image('auth/bg7.jpg') }}');
                text-align: center;
                padding: 50px;
            }

            h1 {
                color: #e74c3c;
            }

            p {
                font-size: 18px;
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

        </style>
        <!--end::Page bg image-->

        <!--begin::Title-->
        <div class="container">
            <h1>{{ $errorCode }} - {{ $errorTitle }}</h1>
            <p>{{ $errorMessage }}</p>
            <a href="{{ url('/') }}">Back to Home</a>
            @if ($errorCode === 431)
            <a href="{{ route('TrdJewel1.Master.Currency.Detail', ['action' => encryptWithSessionKey('Create')]) }}" class="btn-secondary">Go to Master Currency</a>
            @endif
        </div>

    </div>
    <!--end::Card body-->
</div>
<!--end::Card-->

