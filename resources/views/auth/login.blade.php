<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Admin Login - Grafis" />
    <meta name="keywords" content="Admin, Login, Grafis" />
    <meta name="author" content="Grafis" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login Admin - Grafis</title>

    <!--== Favicon ==-->
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon" />

    <!--== Google Fonts ==-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!--== Font Awesome ==-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .bg-animation::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: rotate 20s linear infinite;
        }

        .floating-shapes {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: 1;
        }

        .shape {
            position: absolute;
            opacity: 0.05;
            animation: float 6s ease-in-out infinite;
            color: rgba(255, 255, 255, 0.1);
        }

        .shape:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; font-size: 40px; }
        .shape:nth-child(2) { top: 60%; right: 10%; animation-delay: 2s; font-size: 35px; }
        .shape:nth-child(3) { bottom: 20%; left: 20%; animation-delay: 4s; font-size: 30px; }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(180deg); }
        }

        /* Login Container */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 450px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            transition: all 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 35px 60px rgba(0, 0, 0, 0.3);
        }

        /* Brand Section */
        .brand-section {
            text-align: center;
            margin-bottom: 35px;
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
        }

        .brand-title {
            font-family: 'Poppins', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .brand-subtitle {
            color: #6b7280;
            font-size: 15px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 18px;
            z-index: 5;
            transition: color 0.3s ease;
        }

        .form-control {
            width: 100%;
            padding: 14px 14px 14px 48px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            background: #f9fafb;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-control:focus {
            border-color: #10b981;
            background: white;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-control:focus ~ .input-icon,
        .form-control:focus + .input-icon {
            color: #10b981;
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        /* Remember Checkbox */
        .remember-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            cursor: pointer;
            font-size: 14px;
            color: #4b5563;
        }

        .remember-checkbox input {
            width: 18px;
            height: 18px;
            accent-color: #10b981;
            cursor: pointer;
        }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.35);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        /* Error Messages */
        .error-message {
            color: #dc2626;
            font-size: 13px;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-danger i {
            margin-top: 2px;
        }

        /* Footer */
        .footer-text {
            text-align: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 13px;
        }

        .footer-text span {
            color: #10b981;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 24px;
                border-radius: 20px;
            }

            .brand-logo {
                width: 70px;
                height: 70px;
                font-size: 28px;
            }

            .brand-title {
                font-size: 24px;
            }
        }

        /* Loading State */
        .btn-login.loading {
            pointer-events: none;
        }

        .btn-login.loading .btn-text {
            display: none;
        }

        .btn-login .btn-loading {
            display: none;
        }

        .btn-login.loading .btn-loading {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .fa-spin {
            animation: spin 1s linear infinite;
        }
    </style>
</head>

<body>
    <!-- Animated Background -->
    <div class="bg-animation"></div>
    <div class="floating-shapes">
        <div class="shape">●</div>
        <div class="shape">■</div>
        <div class="shape">🖨</div>
    </div>

    <!-- Login Container -->
    <div class="login-container">
        <div class="login-card">
            <!-- Brand Section -->
            <div class="brand-section">
                <div class="brand-logo">
                    <i class="fas fa-print"></i>
                </div>
                <h1 class="brand-title">GRAFIS</h1>
                <p class="brand-subtitle">Sistem Manajemen Percetakan</p>
            </div>

            <!-- Login Form -->
            <form action="{{ route('login.authenticate') }}" method="POST" id="loginForm">
                @csrf

                @if ($errors->any())
                    <div class="alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            @foreach ($errors->all() as $error)
                                {{ $error }}@if (!$loop->last)<br>@endif
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                <div class="form-group">
                    <label for="login">Email atau NIK</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input 
                            type="text" 
                            id="login" 
                            name="login" 
                            class="form-control"
                            value="{{ old('login') }}"
                            placeholder="Masukkan email atau NIK" 
                            required 
                            autofocus
                        >
                    </div>
                    @error('login')
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Kata Sandi</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control"
                            placeholder="Masukkan kata sandi" 
                            required
                        >
                    </div>
                    @error('password')
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <label class="remember-checkbox">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    Ingat saya
                </label>

                <button type="submit" class="btn-login" id="submitBtn">
                    <span class="btn-text">
                        <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                        Masuk ke Dashboard
                    </span>
                    <span class="btn-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        Memproses...
                    </span>
                </button>
            </form>

            <!-- Footer -->
            <div class="footer-text">
                {{ date('Y') }} &copy; <span>Grafis</span>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const inputs = document.querySelectorAll('.form-control');

            // Form submit handler
            form.addEventListener('submit', function() {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            });

            // Input focus effects
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    const icon = this.parentElement.querySelector('.input-icon');
                    if (icon) icon.style.color = '#10b981';
                });

                input.addEventListener('blur', function() {
                    const icon = this.parentElement.querySelector('.input-icon');
                    if (icon) icon.style.color = '#9ca3af';
                });
            });

            // Card entrance animation
            const loginCard = document.querySelector('.login-card');
            if (window.innerWidth > 768) {
                loginCard.style.opacity = '0';
                loginCard.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    loginCard.style.transition = 'all 0.6s ease';
                    loginCard.style.opacity = '1';
                    loginCard.style.transform = 'translateY(0)';
                }, 100);
            }
        });
    </script>
</body>

</html>


