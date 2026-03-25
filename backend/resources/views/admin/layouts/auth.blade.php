<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Login') — Chinar Signals</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark:     #0D1117;
            --bg-card:     #161B22;
            --bg-elevated: #21262D;
            --text-primary:   #E6EDF3;
            --text-secondary: #8B949E;
            --accent-green: #00C853;
            --accent-red:   #FF1744;
            --accent-gold:  #FFD700;
            --accent-blue:  #2979FF;
            --border:       #30363D;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Animated background grid */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(41,121,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(41,121,255,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }

        .auth-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 1.5rem;
        }

        .auth-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 24px 64px rgba(0,0,0,0.5);
        }

        .auth-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
            text-align: center;
        }

        .logo-icon {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, var(--accent-blue), #6C47FF);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 8px 24px rgba(41,121,255,0.3);
        }

        .logo-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .logo-subtitle {
            font-size: 0.8rem;
            color: var(--text-secondary);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.4rem;
        }

        .form-control {
            background-color: var(--bg-elevated);
            border-color: var(--border);
            color: var(--text-primary);
            padding: 0.7rem 1rem;
            border-radius: 8px;
        }

        .form-control:focus {
            background-color: var(--bg-elevated);
            border-color: var(--accent-blue);
            color: var(--text-primary);
            box-shadow: 0 0 0 3px rgba(41,121,255,0.15);
        }

        .form-control::placeholder { color: var(--text-secondary); }

        .input-group-text {
            background-color: var(--bg-elevated);
            border-color: var(--border);
            color: var(--text-secondary);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--accent-blue), #6C47FF);
            border: none;
            color: #fff;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            width: 100%;
            transition: opacity 0.15s ease, transform 0.15s ease;
        }

        .btn-login:hover { opacity: 0.9; transform: translateY(-1px); color: #fff; }
        .btn-login:active { transform: translateY(0); }

        .alert-danger {
            background: rgba(255,23,68,0.1);
            border-color: rgba(255,23,68,0.3);
            color: var(--accent-red);
            border-radius: 8px;
            font-size: 0.875rem;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.25rem 0;
            color: var(--text-secondary);
            font-size: 0.75rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-top: 1px solid var(--border);
        }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        @yield('content')

        <div class="auth-footer">
            © {{ date('Y') }} Chinar Signals &nbsp;·&nbsp; All rights reserved
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
