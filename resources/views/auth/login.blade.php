<x-auth-layout>

    <!-- Styling for the improved form -->
    <style>
        body {
            position: relative;
            background-image: url('customs/images/background.jpeg');
            background-size: cover;
            background-repeat: no-repeat;
            font-family: 'Poppins', sans-serif;
            color: #333;
        }

        .form-control {
            border: 1px solid #ccc;
            border-radius: 0.375rem;
            background-color: #ffffffcc;
            /* Semi-transparent white */
        }

        .btn-primary {
            background-image: linear-gradient(to right, #0062E6, #33AEFF);
            border: none;
            border-radius: 0.375rem;
        }

        .btn-primary:hover {
            background-image: linear-gradient(to right, #004dbf, #29a6ff);
        }

    </style>

    <!--begin::Signin Form-->
    <form method="POST" action="{{ route('login') }}" class="form w-100" novalidate="novalidate" id="kt_sign_in_form">
        @csrf

        <!--begin::Heading-->
        <div class="text-center mb-5">
            <h1 class="text-dark mb-3">Nusa Evo</h1>
            <p class="text-muted">Welcome back! Please log in to your account.</p>
        </div>


        <!--begin::Input group-->
        <div class="form-group mb-4">
            <input class="form-control form-control-lg" type="text" name="code" autocomplete="off" placeholder="Username" required autofocus />
        </div>
        <!--end::Input group-->

        <!--begin::Input group-->
        {{--<div class="form-group mb-4">
             <x-ui-text-field model="" label="" model="password" action="Edit" type="password" span="Full" placeHolder="" />
        </div>--}}
        <!-- Password Input Group with Toggle -->
        <div class="form-group mb-4 position-relative">
            <input class="form-control form-control-lg" type="password" name="password" id="password" autocomplete="off" placeholder="Password" required />
            <!-- Toggle Button -->
            <span class="password-toggle" onclick="togglePasswordVisibility()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">Show</span>
        </div>

        <!--end::Input group-->
        @error('code')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
        <!--begin::Actions-->
        <div class="text-center">
            <button type="submit" id="kt_sign_in_submit" class="btn btn-lg btn-primary w-100">Log in</button>
        </div>
        <!--end::Actions-->
    </form>
</x-auth-layout>

<script>
    function togglePasswordVisibility() {
        var passwordInput = document.getElementById('password');
        var toggleButton = document.querySelector('.password-toggle');

        // Check the type of the password field
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleButton.textContent = 'Hide'; // Update button text/icon
        } else {
            passwordInput.type = 'password';
            toggleButton.textContent = 'Show'; // Update button text/icon
        }
    }
</script>
