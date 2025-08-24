<div>
    <style>
        /* Modern Welcome Card Design - Similar to other cards */
        .welcome-card-container {
            padding: 20px;
        }

        .main-welcome-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .card-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 30px 40px;
            text-align: center;
            position: relative;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='9' cy='9' r='1'/%3E%3Ccircle cx='49' cy='49' r='1'/%3E%3Ccircle cx='29' cy='29' r='1'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .welcome-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 1;
        }

        .welcome-avatar i {
            font-size: 36px;
            color: white;
        }

        .welcome-title {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 10px 0;
            position: relative;
            z-index: 1;
        }

        .welcome-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin: 0;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }

        .card-body {
            padding: 40px;
        }

        .current-app-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        .current-app-info .label {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .current-app-info .value {
            font-size: 18px;
            color: #1e293b;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .welcome-message {
            color: #475569;
            line-height: 1.6;
            margin-bottom: 30px;
            text-align: center;
            font-size: 16px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
        }

        .btn-primary-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.4);
            color: white;
            text-decoration: none;
        }

        .btn-secondary-custom {
            background: white;
            color: #475569;
            border: 2px solid #e2e8f0;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-secondary-custom:hover {
            border-color: #3b82f6;
            color: #3b82f6;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-color: #3b82f6;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: white;
            font-size: 24px;
        }

        .stat-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .stat-description {
            font-size: 14px;
            color: #64748b;
            line-height: 1.5;
        }

        /* Dark theme support */
        [data-bs-theme="dark"] .main-welcome-card {
            background: #1e293b;
            border-color: #334155;
        }

        [data-bs-theme="dark"] .current-app-info {
            background: #334155;
            border-color: #475569;
        }

        [data-bs-theme="dark"] .current-app-info .label {
            color: #94a3b8;
        }

        [data-bs-theme="dark"] .current-app-info .value {
            color: #f1f5f9;
        }

        [data-bs-theme="dark"] .welcome-message {
            color: #cbd5e1;
        }

        [data-bs-theme="dark"] .stat-card {
            background: #1e293b;
            border-color: #334155;
        }

        [data-bs-theme="dark"] .stat-title {
            color: #f1f5f9;
        }

        [data-bs-theme="dark"] .stat-description {
            color: #94a3b8;
        }

        [data-bs-theme="dark"] .btn-secondary-custom {
            background: #334155;
            color: #f1f5f9;
            border-color: #475569;
        }

        [data-bs-theme="dark"] .btn-secondary-custom:hover {
            border-color: #3b82f6;
            color: #3b82f6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .welcome-card-container {
                padding: 15px;
            }

            .card-header {
                padding: 25px 20px;
            }

            .card-body {
                padding: 25px 20px;
            }

            .welcome-title {
                font-size: 24px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-primary-custom,
            .btn-secondary-custom {
                width: 100%;
                max-width: 200px;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="welcome-card-container">
        <!-- Main Welcome Card -->
        <div class="main-welcome-card">
            <div class="card-header">
                <div class="welcome-avatar">
                    <i class="bi bi-person-circle"></i>
                </div>
                <h1 class="welcome-title">Welcome, {{ auth()->user()->name }}! 👋</h1>
                <p class="welcome-subtitle">Selamat datang kembali di dashboard aplikasi</p>
            </div>

            <div class="card-body">

                <!-- Welcome Message -->
                <p class="welcome-message">
                    Sistem telah siap digunakan dan semua modul tersedia untuk membantu Anda mengelola data dan informasi dengan lebih efisien. Mulai jelajahi fitur-fitur yang tersedia!
                </p>
            </div>
        </div>

    </div>

    <script>
        function showApplicationSwitcher() {
            // Trigger the application switcher if it exists
            const appSwitcher = document.querySelector('#applicationSelect');
            if (appSwitcher) {
                // Focus and open the Select2 dropdown
                $(appSwitcher).select2('open');
            } else {
                // If no application switcher, show a message
                alert('Application switcher is not available on this page.');
            }
        }
    </script>
</div>
