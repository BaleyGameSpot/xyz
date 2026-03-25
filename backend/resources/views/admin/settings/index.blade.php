@extends('admin.layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">Home</a></li>
    <li class="breadcrumb-item active" style="color:var(--text-secondary);">Settings</li>
@endsection

@section('content')

<div class="row g-4">

    {{-- ─── Left: Settings Tabs ─────────────────────────────── --}}
    <div class="col-12 col-md-3">
        <div class="card">
            <div class="card-body p-2">
                <div class="nav flex-column nav-pills" id="settingsTabs" role="tablist">
                    <button class="nav-link active text-start d-flex align-items-center gap-2 mb-1"
                            data-bs-toggle="pill" data-bs-target="#tab-general" type="button">
                        <i class="bi bi-gear"></i> General
                    </button>
                    <button class="nav-link text-start d-flex align-items-center gap-2 mb-1"
                            data-bs-toggle="pill" data-bs-target="#tab-wallet" type="button">
                        <i class="bi bi-wallet2"></i> Wallet Addresses
                    </button>
                    <button class="nav-link text-start d-flex align-items-center gap-2 mb-1"
                            data-bs-toggle="pill" data-bs-target="#tab-signals" type="button">
                        <i class="bi bi-graph-up-arrow"></i> Signal Settings
                    </button>
                    <button class="nav-link text-start d-flex align-items-center gap-2 mb-1"
                            data-bs-toggle="pill" data-bs-target="#tab-notifications" type="button">
                        <i class="bi bi-bell"></i> Notifications
                    </button>
                    <button class="nav-link text-start d-flex align-items-center gap-2 mb-1"
                            data-bs-toggle="pill" data-bs-target="#tab-security" type="button">
                        <i class="bi bi-shield-lock"></i> Security
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Right: Tab Content ──────────────────────────────── --}}
    <div class="col-12 col-md-9">
        <div class="tab-content">

            {{-- ── General ─────────────────────────────────────── --}}
            <div class="tab-pane fade show active" id="tab-general">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-gear me-2" style="color:var(--accent-blue);"></i>General Settings
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="general">

                            <div class="mb-3">
                                <label class="form-label">App Name</label>
                                <input type="text" name="app_name" class="form-control"
                                       value="{{ $settings['app_name'] ?? 'Chinar Signals' }}"
                                       placeholder="Chinar Signals">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Support Email</label>
                                <input type="email" name="support_email" class="form-control"
                                       value="{{ $settings['support_email'] ?? '' }}"
                                       placeholder="support@chinarsignals.com">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Telegram Channel</label>
                                <div class="input-group">
                                    <span class="input-group-text">t.me/</span>
                                    <input type="text" name="telegram_channel" class="form-control"
                                           value="{{ $settings['telegram_channel'] ?? '' }}"
                                           placeholder="chinar_signals">
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="maintenance_mode" name="maintenance_mode" value="1"
                                           {{ ($settings['maintenance_mode'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="maintenance_mode">
                                        <strong>Maintenance Mode</strong>
                                        <span class="text-muted ms-1">— blocks all API access</span>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Save General Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ── Wallet Addresses ─────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-wallet">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-wallet2 me-2" style="color:var(--accent-green);"></i>
                        Cryptocurrency Wallet Addresses
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            These are the wallet addresses displayed to users for payment.
                            Double-check before saving — incorrect addresses will result in lost funds.
                        </div>

                        <form action="{{ route('admin.settings.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="wallets">

                            @php
                                $wallets = [
                                    'wallet_usdt_trc20' => ['label' => 'USDT (TRC-20 / Tron)', 'icon' => 'bi-currency-exchange', 'color' => 'var(--accent-green)'],
                                    'wallet_usdt_erc20' => ['label' => 'USDT (ERC-20 / Ethereum)', 'icon' => 'bi-currency-exchange', 'color' => 'var(--accent-blue)'],
                                    'wallet_btc'        => ['label' => 'Bitcoin (BTC)', 'icon' => 'bi-currency-bitcoin', 'color' => 'var(--accent-gold)'],
                                    'wallet_eth'        => ['label' => 'Ethereum (ETH)', 'icon' => 'bi-currency-exchange', 'color' => '#627EEA'],
                                    'wallet_bnb'        => ['label' => 'BNB (BSC)', 'icon' => 'bi-currency-exchange', 'color' => '#F3BA2F'],
                                ];
                            @endphp

                            @foreach($wallets as $key => $wallet)
                            <div class="mb-4">
                                <label class="form-label d-flex align-items-center gap-2">
                                    <i class="{{ $wallet['icon'] }}" style="color:{{ $wallet['color'] }};"></i>
                                    {{ $wallet['label'] }}
                                </label>
                                <div class="input-group">
                                    <input
                                        type="text"
                                        name="{{ $key }}"
                                        class="form-control"
                                        value="{{ $settings[$key] ?? '' }}"
                                        placeholder="Enter wallet address…"
                                        style="font-family:monospace;font-size:0.85rem;"
                                    >
                                    @if(!empty($settings[$key]))
                                    <button type="button" class="input-group-text"
                                        onclick="navigator.clipboard.writeText('{{ $settings[$key] ?? '' }}')"
                                        title="Copy address" style="cursor:pointer;">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Save Wallet Addresses
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ── Signal Settings ──────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-signals">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-graph-up-arrow me-2" style="color:var(--accent-blue);"></i>
                        Signal Generation Settings
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="signals">

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Minimum Confidence Score (%)</label>
                                    <input type="number" name="min_confidence" class="form-control"
                                           value="{{ $settings['min_confidence'] ?? 60 }}"
                                           min="0" max="100">
                                    <div class="form-text" style="color:var(--text-secondary);">
                                        Signals below this score will not be sent.
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Max Signals Per Day (global)</label>
                                    <input type="number" name="max_daily_signals" class="form-control"
                                           value="{{ $settings['max_daily_signals'] ?? 20 }}"
                                           min="1" max="100">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Active Trading Pairs</label>
                                <textarea name="active_pairs" class="form-control" rows="4"
                                    placeholder="One pair per line, e.g.:&#10;XAUUSD&#10;EURUSD&#10;BTCUSDT"
                                    style="font-family:monospace;">{{ $settings['active_pairs'] ?? '' }}</textarea>
                                <div class="form-text" style="color:var(--text-secondary);">
                                    One pair per line. Only these pairs will generate signals.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Signal Cron Schedule</label>
                                <input type="text" name="cron_schedule" class="form-control"
                                       value="{{ $settings['cron_schedule'] ?? '*/15 * * * *' }}"
                                       placeholder="*/15 * * * *"
                                       style="font-family:monospace;">
                                <div class="form-text" style="color:var(--text-secondary);">
                                    Cron expression for signal generation frequency.
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="signals_enabled" name="signals_enabled" value="1"
                                           {{ ($settings['signals_enabled'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="signals_enabled">
                                        <strong>Signal Generation Enabled</strong>
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="auto_close_signals" name="auto_close_signals" value="1"
                                           {{ ($settings['auto_close_signals'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="auto_close_signals">
                                        <strong>Auto-close signals</strong>
                                        <span class="text-muted ms-1">— mark as win/loss when TP/SL is hit</span>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Save Signal Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ── Notifications ────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-notifications">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-bell me-2" style="color:var(--accent-gold);"></i>
                        Push Notification Settings
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="notifications">

                            <div class="mb-3">
                                <label class="form-label">Firebase Server Key</label>
                                <div class="input-group">
                                    <input type="password" name="firebase_server_key" class="form-control"
                                           value="{{ $settings['firebase_server_key'] ?? '' }}"
                                           placeholder="AAAA…"
                                           style="font-family:monospace;font-size:0.8rem;"
                                           id="firebaseKey">
                                    <button type="button" class="input-group-text" style="cursor:pointer;"
                                        onclick="toggleFieldVisibility('firebaseKey')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Firebase Project ID</label>
                                <input type="text" name="firebase_project_id" class="form-control"
                                       value="{{ $settings['firebase_project_id'] ?? '' }}"
                                       placeholder="chinar-signals-xxxxx">
                            </div>

                            <div class="mb-4">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="notify_new_signal" name="notify_new_signal" value="1"
                                           {{ ($settings['notify_new_signal'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_new_signal">
                                        Notify users on <strong>new signal</strong>
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="notify_signal_result" name="notify_signal_result" value="1"
                                           {{ ($settings['notify_signal_result'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_signal_result">
                                        Notify users on <strong>signal result</strong> (win/loss)
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="notify_payment_verified" name="notify_payment_verified" value="1"
                                           {{ ($settings['notify_payment_verified'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_payment_verified">
                                        Notify users on <strong>payment verified</strong>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-check-circle me-1"></i>Save Notification Settings
                            </button>
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="sendTestNotification()">
                                <i class="bi bi-send me-1"></i>Send Test Push
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ── Security ─────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-security">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="bi bi-key me-2" style="color:var(--accent-red);"></i>
                        Change Admin Password
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.change-password') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                <input type="password" name="current_password" class="form-control"
                                       placeholder="Current password" required
                                       autocomplete="current-password">
                                @error('current_password')
                                    <div class="text-danger mt-1" style="font-size:0.8rem;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">New Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control"
                                       placeholder="Min 8 characters" required minlength="8"
                                       autocomplete="new-password">
                                @error('password')
                                    <div class="text-danger mt-1" style="font-size:0.8rem;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" name="password_confirmation" class="form-control"
                                       placeholder="Repeat new password" required minlength="8"
                                       autocomplete="new-password">
                            </div>

                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-key me-1"></i>Update Password
                            </button>
                        </form>
                    </div>
                </div>

                {{-- API Token --}}
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-shield-lock me-2" style="color:var(--accent-blue);"></i>
                        API Configuration
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">JWT Secret</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="jwtSecret"
                                       value="{{ $settings['jwt_secret_preview'] ?? '(hidden)' }}"
                                       readonly style="font-family:monospace;font-size:0.8rem;">
                                <button type="button" class="input-group-text" style="cursor:pointer;"
                                    onclick="toggleFieldVisibility('jwtSecret')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <form action="{{ route('admin.settings.regenerate-token') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning"
                                onclick="return confirm('Regenerate JWT secret? All existing tokens will be invalidated and users will be logged out.')">
                                <i class="bi bi-arrow-repeat me-1"></i>Regenerate JWT Secret
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>{{-- /tab-content --}}
    </div>

</div>

@endsection

@push('scripts')
<script>
    // Style active pill
    document.querySelectorAll('#settingsTabs button').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#settingsTabs button').forEach(b => {
                b.style.background   = 'transparent';
                b.style.color        = 'var(--text-secondary)';
            });
            this.style.background = 'rgba(41,121,255,0.12)';
            this.style.color      = 'var(--accent-blue)';
        });
    });

    // Init first tab
    const firstBtn = document.querySelector('#settingsTabs button');
    if (firstBtn) {
        firstBtn.style.background = 'rgba(41,121,255,0.12)';
        firstBtn.style.color      = 'var(--accent-blue)';
    }

    function toggleFieldVisibility(id) {
        const el = document.getElementById(id);
        el.type = el.type === 'password' ? 'text' : 'password';
    }

    function sendTestNotification() {
        if (!confirm('Send a test push notification to your device?')) return;
        fetch('{{ route("admin.settings.test-notification") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Content-Type': 'application/json' }
        })
        .then(r => r.json())
        .then(d => alert(d.message || 'Test notification sent!'))
        .catch(() => alert('Failed to send test notification.'));
    }
</script>
@endpush
