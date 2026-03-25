@extends('admin.layouts.auth')

@section('title', 'Admin Login')

@section('content')
<div class="auth-card">
    <!-- Logo -->
    <div class="auth-logo">
        <div class="logo-icon">📈</div>
        <div class="logo-title">Chinar Signals</div>
        <div class="logo-subtitle">Admin Panel</div>
    </div>

    <!-- Validation Errors -->
    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- Login Form -->
    <form action="{{ route('admin.login.post') }}" method="POST" id="loginForm">
        @csrf

        <!-- Email -->
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="bi bi-envelope"></i>
                </span>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email') }}"
                    placeholder="admin@chinarsignals.com"
                    required
                    autofocus
                    autocomplete="email"
                >
            </div>
        </div>

        <!-- Password -->
        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="bi bi-lock"></i>
                </span>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
                <button
                    type="button"
                    class="input-group-text"
                    style="cursor:pointer;border-left:0;"
                    onclick="togglePassword()"
                    title="Toggle password visibility"
                >
                    <i class="bi bi-eye" id="pwEyeIcon"></i>
                </button>
            </div>
        </div>

        <!-- Remember Me -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="remember"
                    name="remember"
                    {{ old('remember') ? 'checked' : '' }}
                    style="background-color:var(--bg-elevated);border-color:var(--border);"
                >
                <label class="form-check-label" for="remember" style="font-size:0.85rem;color:var(--text-secondary);">
                    Remember me
                </label>
            </div>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-login" id="loginBtn">
            <span id="loginBtnText">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </span>
            <span id="loginSpinner" class="d-none">
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Authenticating…
            </span>
        </button>
    </form>

    <div class="divider">Secured Admin Area</div>

    <div class="text-center" style="font-size:0.8rem;color:var(--text-secondary);">
        <i class="bi bi-shield-lock me-1"></i>
        Access restricted to authorized administrators only
    </div>
</div>

@push('scripts')
<script>
    function togglePassword() {
        const pw   = document.getElementById('password');
        const icon = document.getElementById('pwEyeIcon');
        if (pw.type === 'password') {
            pw.type    = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            pw.type    = 'password';
            icon.className = 'bi bi-eye';
        }
    }

    document.getElementById('loginForm').addEventListener('submit', function () {
        document.getElementById('loginBtnText').classList.add('d-none');
        document.getElementById('loginSpinner').classList.remove('d-none');
    });
</script>
@endpush
@endsection
