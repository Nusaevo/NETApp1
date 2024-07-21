<div>
    <x-base-layout>
        <div class="container">
            <div class="card mt-5 bg-dark text-white">
                <div class="card-header text-white">
                    <h1 class="mt-3 text-white">Welcome, {{ auth()->user()->name }}</h1>
                </div>
                <div class="card-body">
                    <p class="card-text lead">
                        Thank you for joining our platform. We're excited to have you here!
                    </p>
                </div>
            </div>
        </div>
    </x-base-layout>
</div>
