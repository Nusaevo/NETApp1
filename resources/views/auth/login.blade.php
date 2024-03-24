<x-auth-layout>

    <!--begin::Signin Form-->
    <form method="POST" action="{{ theme()->getPageUrl('login') }}" class="form w-100" novalidate="novalidate" id="kt_sign_in_form">
    @csrf

    <!--begin::Heading-->
        <div class="text-center mb-10">
            <!--begin::Title-->
            <h1 class="text-dark mb-3">
                POS Online System
            </h1>
            <!--end::Title-->
        </div>
        <!--begin::Heading-->

        <div class="mb-10 bg-light-info p-8 rounded"><div class="text-info"> Developer Mode Enabled. </div></div>

        <!--begin::Input group-->
        <div class="fv-row mb-5">
            <!--begin::Input-->
            <input class="form-control form-control-lg form-control-solid" type="text" name="code" autocomplete="off" placeholder="Username" required autofocus/>
            <!--end::Input-->
        </div>
        <!--end::Input group-->

        <!--begin::Input group-->
        <div class="fv-row mb-5">
            <!--begin::Input-->
            <input class="form-control form-control-lg form-control-solid" type="password" name="password" autocomplete="off" placeholder="Password" required/>
            <!--end::Input-->
        </div>
        <!--end::Input group-->

        <!--begin::Actions-->
        <div class="text-center">
            <!--begin::Submit button-->
            <button type="submit" id="kt_sign_in_submit" class="btn btn-lg btn-primary w-100 mb-3">
                @include('partials.general._button-indicator', ['label' => __('Log in')])
            </button>
            <!--end::Submit button-->
        </div>
        <!--end::Actions-->
    </form>
    <!--end::Signin Form-->

</x-auth-layout>

<style>
    /* CSS untuk latar belakang dengan warna biru */
    body {
        background-color: #3498db; /* Warna latar belakang biru */
        font-family: Arial, sans-serif; /* Ubah jenis font sesuai kebutuhan Anda */
    }

    /* Menyesuaikan tampilan form */
    .form {
        max-width: 400px;
        margin: 0 auto;
        padding: 20px;
        background-color: #ffffff; /* Warna latar belakang form */
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    /* Menyesuaikan tampilan input */
    .form-control {
        border-color: #ced4da;
        border-radius: 5px;
    }

    /* Menyesuaikan tampilan tombol */
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }

    /* Menyesuaikan warna teks */
    .text-dark {
        color: #333;
    }
</style>
