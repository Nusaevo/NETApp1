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

        .form-wrapper {
            background-color: #ffffff;
            border-radius: 0.375rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: 100px auto;
        }

        .form-control {
            border: 1px solid #ccc;
            border-radius: 0.375rem;
            background-color: #ffffffcc; /* Semi-transparent white */
        }

        .btn-primary {
            background-image: linear-gradient(to right, #0062E6, #33AEFF);
            border: none;
            border-radius: 0.375rem;
        }

        .btn-primary:hover {
            background-image: linear-gradient(to right, #004dbf, #29a6ff);
        }

        .lds-ripple {
            display: inline-block;
            position: relative;
            width: 64px;
            height: 64px;
        }

        .lds-ripple div {
            position: absolute;
            border: 4px solid #1c4c5b;
            opacity: 1;
            border-radius: 50%;
            animation: lds-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
        }

        .lds-ripple div:nth-child(2) {
            animation-delay: -0.5s;
        }

        @keyframes lds-ripple {
            0% {
                top: 28px;
                left: 28px;
                width: 0;
                height: 0;
                opacity: 1;
            }

            100% {
                top: -1px;
                left: -1px;
                width: 58px;
                height: 58px;
                opacity: 0;
            }
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }

        .custom-loading-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            display: none;
        }

        .custom-loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>

    <!--begin::Signin Form-->
    <div class="form-wrapper">
        <form method="POST" action="{{ route('login') }}" class="form w-100" novalidate="novalidate" id="kt_sign_in_form" onsubmit="showLoader()">
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

            <!-- Password Input Group with Toggle -->
            <div class="form-group mb-4 position-relative">
                <input class="form-control form-control-lg" type="password" name="password" id="password" autocomplete="off" placeholder="Password" required />
                <!-- Toggle Button -->
                <span class="password-toggle" role="button" tabindex="0" onclick="togglePasswordVisibility()" onkeydown="handleKeyDown(event)" onkeypress="handleKeyPress(event)" onkeyup="handleKeyUp(event)">
                    Show
                </span>
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
    </div>
    <!--end::Signin Form-->

    <!-- Loader -->
    <div id="custom-loading-container" class="custom-loading-container">
        <div class="custom-loading-spinner lds-ripple">
            <div></div>
            <div></div>
        </div>
    </div>

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

    function showLoader() {
        var loadingContainer = document.getElementById('custom-loading-container');
        var submitButton = document.getElementById('kt_sign_in_submit');

        loadingContainer.style.display = 'flex'; // Show the loader
        submitButton.disabled = true; // Disable the button
        submitButton.innerHTML = 'Loading...'; // Change button text
    }
</script>
