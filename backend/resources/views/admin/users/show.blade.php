@extends('admin.layouts.app')

@section('title', 'User — ' . $user->name)
@section('page-title', 'User Details')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}" class="text-decoration-none text-muted">Users</a></li>
    <li class="breadcrumb-item active" style="color:var(--text-secondary);">{{ $user->name }}</li>
@endsection

@section('content')

<div class="row g-3">

    {{-- ─── Left Column: Profile + Actions ───────────────────── --}}
    <div class="col-12 col-lg-4">

        {{-- Profile Card --}}
        <div class="card mb-3">
            <div class="card-body text-center py-4">
                {{-- Avatar --}}
                <div class="mx-auto mb-3" style="
                    width:72px;height:72px;border-radius:50%;
                    background:linear-gradient(135deg,var(--accent-blue),#6C47FF);
                    display:flex;align-items:center;justify-content:center;
                    font-size:1.6rem;font-weight:700;color:#fff;
                ">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>

                <h5 class="mb-0 fw-700">{{ $user->name }}</h5>
                <div class="text-muted mb-2" style="font-size:0.85rem;">{{ $user->email }}</div>

                @if($user->is_blocked)
                    <span class="badge-custom badge-blocked">
                        <i class="bi bi-slash-circle"></i> Blocked
                    </span>
                @else
                    <span class="badge-custom badge-active">
                        <i class="bi bi-check-circle"></i> Active
                    </span>
                @endif

                <hr>

                {{-- Details --}}
                <div class="text-start">
                    <div class="mb-2 d-flex justify-content-between" style="font-size:0.85rem;">
                        <span class="text-muted">User ID</span>
                        <span class="fw-600">#{{ $user->id }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between" style="font-size:0.85rem;">
                        <span class="text-muted">Phone</span>
                        <span>{{ $user->phone ?? '—' }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between" style="font-size:0.85rem;">
                        <span class="text-muted">FCM Token</span>
                        <span class="text-muted" style="font-size:0.75rem;">
                            {{ $user->fcm_token ? substr($user->fcm_token,0,12).'…' : '—' }}
                        </span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between" style="font-size:0.85rem;">
                        <span class="text-muted">Joined</span>
                        <span>{{ $user->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between" style="font-size:0.85rem;">
                        <span class="text-muted">Last Active</span>
                        <span>{{ $user->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Block/Unblock --}}
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-shield me-2" style="color:var(--accent-red);"></i>Account Actions
            </div>
            <div class="card-body">
                <form action="{{ route('admin.users.toggle-block', $user->id) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <button
                        type="submit"
                        class="btn {{ $user->is_blocked ? 'btn-success' : 'btn-danger' }} w-100"
                        onclick="return confirm('{{ $user->is_blocked ? 'Unblock' : 'Block' }} {{ $user->name }}?')"
                    >
                        <i class="bi bi-{{ $user->is_blocked ? 'unlock' : 'lock' }} me-2"></i>
                        {{ $user->is_blocked ? 'Unblock User' : 'Block User' }}
                    </button>
                </form>
                <p class="text-muted mb-0" style="font-size:0.75rem;">
                    {{ $user->is_blocked
                        ? 'User is currently blocked. Unblocking will restore access.'
                        : 'Blocking will revoke all active sessions and API access.' }}
                </p>
            </div>
        </div>

        {{-- Assign Subscription --}}
        <div class="card">
            <div class="card-header">
                <i class="bi bi-patch-plus me-2" style="color:var(--accent-green);"></i>Assign Subscription
            </div>
            <div class="card-body">
                <form action="{{ route('admin.users.assign-subscription') }}" method="POST">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $user->id }}">

                    <div class="mb-3">
                        <label class="form-label">Package</label>
                        <select name="package_id" class="form-select" required>
                            <option value="">Select package…</option>
                            @foreach($packages ?? [] as $pkg)
                                <option value="{{ $pkg->id }}">
                                    {{ $pkg->name }} — ${{ $pkg->price }}/mo
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Duration (days)</label>
                        <input type="number" name="days" class="form-control" value="30" min="1" max="365" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="2"
                            placeholder="Manual assignment reason…"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-patch-check me-1"></i>Assign Subscription
                    </button>
                </form>
            </div>
        </div>

    </div>

    {{-- ─── Right Column: Tabs ─────────────────────────────── --}}
    <div class="col-12 col-lg-8">

        {{-- Current Subscription Banner --}}
        @if($user->activeSubscription)
        <div class="card mb-3" style="border-top:3px solid var(--accent-green);">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <div class="fw-700 mb-1" style="color:var(--accent-green);">
                            <i class="bi bi-patch-check-fill me-2"></i>Active Subscription
                        </div>
                        <div class="text-muted" style="font-size:0.85rem;">
                            Plan: <strong class="text-primary">{{ $user->activeSubscription->package->name ?? 'N/A' }}</strong>
                            &nbsp;·&nbsp;
                            Expires: <strong class="text-primary">
                                {{ $user->activeSubscription->expires_at?->format('d M Y') ?? 'Lifetime' }}
                            </strong>
                            &nbsp;·&nbsp;
                            <span class="text-muted">
                                ({{ $user->activeSubscription->expires_at?->diffForHumans() ?? '' }})
                            </span>
                        </div>
                    </div>
                    <form action="{{ route('admin.subscriptions.revoke', $user->activeSubscription->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('Revoke this subscription?')">
                            <i class="bi bi-x-circle me-1"></i>Revoke
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @else
        <div class="alert alert-warning mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            This user has no active subscription.
        </div>
        @endif

        {{-- Tabs --}}
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" style="border-bottom:none;padding:0 1rem;">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-subs"
                           style="color:var(--text-secondary);border:none;border-bottom:2px solid transparent;padding:0.85rem 1rem;">
                            <i class="bi bi-patch-check me-1"></i>Subscriptions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-signals"
                           style="color:var(--text-secondary);border:none;border-bottom:2px solid transparent;padding:0.85rem 1rem;">
                            <i class="bi bi-graph-up me-1"></i>Signals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-payments"
                           style="color:var(--text-secondary);border:none;border-bottom:2px solid transparent;padding:0.85rem 1rem;">
                            <i class="bi bi-credit-card me-1"></i>Payments
                        </a>
                    </li>
                </ul>
            </div>
            <div class="tab-content">

                {{-- ── Subscriptions Tab ──────────────────────── --}}
                <div class="tab-pane fade show active" id="tab-subs">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th>Started</th>
                                    <th>Expires</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($user->subscriptions ?? [] as $sub)
                                <tr>
                                    <td class="fw-600">{{ $sub->package->name ?? '—' }}</td>
                                    <td class="text-muted" style="font-size:0.8rem;">
                                        {{ $sub->starts_at?->format('d M Y') ?? '—' }}
                                    </td>
                                    <td style="font-size:0.8rem;">
                                        @if($sub->expires_at && $sub->expires_at->isPast())
                                            <span style="color:var(--accent-red);">
                                                {{ $sub->expires_at->format('d M Y') }}
                                            </span>
                                        @else
                                            {{ $sub->expires_at?->format('d M Y') ?? 'Lifetime' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($sub->is_manual)
                                            <span class="badge-custom" style="background:rgba(156,39,176,0.15);color:#9C27B0;border:1px solid rgba(156,39,176,0.3);">
                                                Manual
                                            </span>
                                        @else
                                            <span class="badge-custom badge-active">Paid</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($sub->is_active)
                                            <span class="badge-custom badge-active">Active</span>
                                        @else
                                            <span class="badge-custom badge-blocked">Expired</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-patch-minus fs-4 d-block mb-1"></i>No subscriptions
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Signals Tab ─────────────────────────────── --}}
                <div class="tab-pane fade" id="tab-signals">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Pair</th>
                                    <th>Type</th>
                                    <th>Timeframe</th>
                                    <th>Confidence</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($userSignals ?? [] as $signal)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.signals.show', $signal->id) }}"
                                           class="fw-600 text-decoration-none" style="color:var(--accent-blue);">
                                            {{ $signal->pair }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge-custom {{ $signal->type === 'BUY' ? 'badge-buy' : 'badge-sell' }}">
                                            {{ $signal->type }}
                                        </span>
                                    </td>
                                    <td class="text-muted" style="font-size:0.8rem;">{{ $signal->timeframe }}</td>
                                    <td>
                                        <span style="color:var(--accent-gold);">{{ $signal->confidence_score }}%</span>
                                    </td>
                                    <td>
                                        @if($signal->status === 'win')
                                            <span class="badge-custom badge-win">Win</span>
                                        @elseif($signal->status === 'loss')
                                            <span class="badge-custom badge-loss">Loss</span>
                                        @else
                                            <span class="badge-custom badge-pending">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-muted" style="font-size:0.8rem;">
                                        {{ $signal->created_at->format('d M, H:i') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-graph-up fs-4 d-block mb-1"></i>No signals viewed
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Payments Tab ─────────────────────────────── --}}
                <div class="tab-pane fade" id="tab-payments">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th>Amount</th>
                                    <th>Crypto</th>
                                    <th>TX Hash</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($user->payments ?? [] as $payment)
                                <tr>
                                    <td class="fw-600" style="font-size:0.875rem;">
                                        {{ $payment->package->name ?? '—' }}
                                    </td>
                                    <td style="color:var(--accent-green);font-weight:600;">
                                        ${{ number_format($payment->amount, 2) }}
                                    </td>
                                    <td class="text-muted" style="font-size:0.8rem;">{{ $payment->crypto_type }}</td>
                                    <td>
                                        @if($payment->tx_hash)
                                            <span
                                                class="text-muted"
                                                style="font-size:0.72rem;font-family:monospace;cursor:pointer;"
                                                title="{{ $payment->tx_hash }}"
                                                onclick="navigator.clipboard.writeText('{{ $payment->tx_hash }}')"
                                            >
                                                {{ substr($payment->tx_hash, 0, 12) }}…
                                                <i class="bi bi-clipboard ms-1"></i>
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->status === 'verified')
                                            <span class="badge-custom badge-win">Verified</span>
                                        @elseif($payment->status === 'rejected')
                                            <span class="badge-custom badge-loss">Rejected</span>
                                        @else
                                            <span class="badge-custom badge-pending">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-muted" style="font-size:0.8rem;">
                                        {{ $payment->created_at->format('d M Y') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-receipt fs-4 d-block mb-1"></i>No payments
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>{{-- /tab-content --}}
        </div>{{-- /card --}}

    </div>{{-- /right col --}}
</div>{{-- /row --}}

@endsection

@push('scripts')
<script>
    // Style active nav-link in tabs
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(t => {
                t.style.color = 'var(--text-secondary)';
                t.style.borderBottom = '2px solid transparent';
            });
            e.target.style.color = 'var(--accent-blue)';
            e.target.style.borderBottom = '2px solid var(--accent-blue)';
        });
    });
    // Set initial active tab style
    const firstTab = document.querySelector('[data-bs-toggle="tab"]');
    if (firstTab) {
        firstTab.style.color = 'var(--accent-blue)';
        firstTab.style.borderBottom = '2px solid var(--accent-blue)';
    }
</script>
@endpush
