@extends('admin.layouts.app')

@section('title', 'Payments')
@section('page-title', 'Payments')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">Home</a></li>
    <li class="breadcrumb-item active" style="color:var(--text-secondary);">Payments</li>
@endsection

@section('content')

{{-- ─── Stats Bar ───────────────────────────────────────────── --}}
<div class="row g-2 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fw-700" style="font-size:1.3rem;color:var(--accent-gold);">
                {{ $paymentStats['pending'] ?? 0 }}
            </div>
            <div class="text-muted" style="font-size:0.75rem;">Pending Review</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fw-700" style="font-size:1.3rem;color:var(--accent-green);">
                {{ $paymentStats['verified'] ?? 0 }}
            </div>
            <div class="text-muted" style="font-size:0.75rem;">Verified</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fw-700" style="font-size:1.3rem;color:var(--accent-red);">
                {{ $paymentStats['rejected'] ?? 0 }}
            </div>
            <div class="text-muted" style="font-size:0.75rem;">Rejected</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fw-700" style="font-size:1.3rem;color:var(--accent-green);">
                ${{ number_format($paymentStats['total_revenue'] ?? 0, 2) }}
            </div>
            <div class="text-muted" style="font-size:0.75rem;">Total Revenue</div>
        </div>
    </div>
</div>

{{-- ─── Filters ─────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form action="{{ route('admin.payments.index') }}" method="GET">
            <div class="row g-2 align-items-end">

                <div class="col-12 col-md-3">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control"
                               placeholder="User name, TX hash…"
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                        <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Verified</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Crypto</label>
                    <select name="crypto" class="form-select" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach(['USDT_TRC20','USDT_ERC20','BTC','ETH','BNB'] as $crypto)
                            <option value="{{ $crypto }}" {{ request('crypto') === $crypto ? 'selected' : '' }}>
                                {{ $crypto }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Package</label>
                    <select name="package_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Packages</option>
                        @foreach($packages ?? [] as $pkg)
                            <option value="{{ $pkg->id }}" {{ request('package_id') == $pkg->id ? 'selected' : '' }}>
                                {{ $pkg->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- ─── Pending Payments Alert ──────────────────────────────── --}}
@if(($paymentStats['pending'] ?? 0) > 0)
<div class="alert alert-warning mb-4 d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 fs-5"></i>
    <div>
        <strong>{{ $paymentStats['pending'] }} payment(s)</strong> are awaiting manual verification.
        <a href="{{ route('admin.payments.index', ['status' => 'pending']) }}"
           class="alert-link ms-2">View pending payments &rarr;</a>
    </div>
</div>
@endif

{{-- ─── Table ───────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>
            Showing <strong>{{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }}</strong>
            of <strong>{{ $payments->total() }}</strong> payments
        </span>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Package</th>
                    <th>Amount</th>
                    <th>Crypto</th>
                    <th>TX Hash</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th style="width:150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr class="{{ $payment->status === 'pending' ? 'table-warning bg-opacity-10' : '' }}"
                    style="{{ $payment->status === 'pending' ? 'background:rgba(255,215,0,0.04)!important;' : '' }}">
                    <td class="text-muted" style="font-size:0.8rem;">#{{ $payment->id }}</td>
                    <td>
                        <a href="{{ route('admin.users.show', $payment->user_id) }}"
                           class="text-decoration-none fw-600" style="color:var(--accent-blue);font-size:0.875rem;">
                            {{ $payment->user->name ?? 'N/A' }}
                        </a>
                        <div class="text-muted" style="font-size:0.72rem;">
                            {{ $payment->user->email ?? '' }}
                        </div>
                    </td>
                    <td style="font-size:0.875rem;">{{ $payment->package->name ?? '—' }}</td>
                    <td>
                        <span class="fw-700" style="color:var(--accent-green);">
                            ${{ number_format($payment->amount, 2) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-secondary bg-opacity-25 text-secondary" style="font-size:0.72rem;">
                            {{ $payment->crypto_type }}
                        </span>
                    </td>
                    <td>
                        @if($payment->tx_hash)
                        <div class="d-flex align-items-center gap-1">
                            <span
                                class="text-muted"
                                style="font-size:0.72rem;font-family:monospace;cursor:pointer;"
                                title="{{ $payment->tx_hash }}"
                                onclick="copyTx('{{ $payment->tx_hash }}')"
                            >
                                {{ substr($payment->tx_hash, 0, 14) }}…
                            </span>
                            <button
                                type="button"
                                class="btn btn-sm p-0"
                                style="color:var(--text-secondary);background:none;border:none;"
                                onclick="copyTx('{{ $payment->tx_hash }}')"
                                title="Copy TX hash"
                            >
                                <i class="bi bi-clipboard" style="font-size:0.75rem;"></i>
                            </button>
                        </div>
                        @else
                            <span class="text-muted" style="font-size:0.8rem;">No hash</span>
                        @endif
                    </td>
                    <td>
                        @if($payment->status === 'verified')
                            <span class="badge-custom badge-win">
                                <i class="bi bi-check2-circle"></i> Verified
                            </span>
                        @elseif($payment->status === 'rejected')
                            <span class="badge-custom badge-loss">
                                <i class="bi bi-x-circle"></i> Rejected
                            </span>
                        @else
                            <span class="badge-custom badge-pending">
                                <i class="bi bi-hourglass-split"></i> Pending
                            </span>
                        @endif
                    </td>
                    <td class="text-muted" style="font-size:0.78rem;">
                        {{ $payment->created_at->format('d M Y') }}<br>
                        <span style="font-size:0.7rem;">{{ $payment->created_at->format('H:i') }}</span>
                    </td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">

                            {{-- View TX on blockchain --}}
                            @if($payment->tx_hash)
                            <a href="#" target="_blank"
                               class="btn btn-sm btn-outline-secondary" title="View on Blockchain">
                                <i class="bi bi-box-arrow-up-right" style="font-size:0.8rem;"></i>
                            </a>
                            @endif

                            {{-- Verify --}}
                            @if($payment->status === 'pending')
                            <form action="{{ route('admin.payments.verify', $payment->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-success"
                                    title="Verify Payment"
                                    onclick="return confirm('Verify this payment and activate subscription for {{ addslashes($payment->user->name ?? '') }}?')">
                                    <i class="bi bi-check-circle-fill" style="font-size:0.8rem;"></i>
                                    <span class="d-none d-xl-inline ms-1">Verify</span>
                                </button>
                            </form>

                            {{-- Reject --}}
                            <button
                                type="button"
                                class="btn btn-sm btn-danger"
                                title="Reject Payment"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectModal"
                                data-payment-id="{{ $payment->id }}"
                                data-user-name="{{ $payment->user->name ?? 'User' }}"
                            >
                                <i class="bi bi-x-circle-fill" style="font-size:0.8rem;"></i>
                                <span class="d-none d-xl-inline ms-1">Reject</span>
                            </button>
                            @endif

                            {{-- Reopen if already acted --}}
                            @if(in_array($payment->status, ['verified', 'rejected']))
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                title="Payment details"
                                data-bs-toggle="modal"
                                data-bs-target="#detailModal"
                                data-payment="{{ json_encode($payment) }}"
                            >
                                <i class="bi bi-eye" style="font-size:0.8rem;"></i>
                            </button>
                            @endif

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <i class="bi bi-receipt fs-2 d-block mb-2 text-muted"></i>
                        <div class="text-muted">No payments found.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div class="card-footer d-flex align-items-center justify-content-between py-3">
        <div class="text-muted" style="font-size:0.8rem;">
            Page {{ $payments->currentPage() }} of {{ $payments->lastPage() }}
        </div>
        {{ $payments->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

{{-- ─── Reject Modal ────────────────────────────────────────── --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--bg-card);border-color:var(--border);">
            <div class="modal-header" style="border-color:var(--border);">
                <h5 class="modal-title" id="rejectModalLabel">
                    <i class="bi bi-x-circle me-2" style="color:var(--accent-red);"></i>
                    Reject Payment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size:0.875rem;">
                        Rejecting payment from: <strong id="rejectUserName" class="text-primary"></strong>
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <select name="reject_reason" class="form-select" required>
                            <option value="">Select reason…</option>
                            <option value="invalid_tx">Invalid transaction hash</option>
                            <option value="amount_mismatch">Amount mismatch</option>
                            <option value="duplicate">Duplicate payment</option>
                            <option value="unconfirmed">Transaction unconfirmed</option>
                            <option value="fraud">Suspected fraud</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Additional Notes</label>
                        <textarea name="reject_note" class="form-control" rows="3"
                            placeholder="Optional note to the user…"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-color:var(--border);">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i>Reject Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─── Detail Modal ────────────────────────────────────────── --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--bg-card);border-color:var(--border);">
            <div class="modal-header" style="border-color:var(--border);">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                {{-- Populated via JS --}}
            </div>
            <div class="modal-footer" style="border-color:var(--border);">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Reject modal
    document.getElementById('rejectModal').addEventListener('show.bs.modal', function(e) {
        const btn = e.relatedTarget;
        document.getElementById('rejectUserName').textContent = btn.dataset.userName;
        document.getElementById('rejectForm').action =
            `/admin/payments/${btn.dataset.paymentId}/reject`;
    });

    // Detail modal
    document.getElementById('detailModal').addEventListener('show.bs.modal', function(e) {
        const payment = JSON.parse(e.relatedTarget.dataset.payment);
        const body    = document.getElementById('detailModalBody');
        body.innerHTML = `
            <div style="font-size:0.875rem;">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Payment ID</span>
                    <strong>#${payment.id}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Amount</span>
                    <strong style="color:var(--accent-green);">$${parseFloat(payment.amount).toFixed(2)}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Crypto</span>
                    <strong>${payment.crypto_type}</strong>
                </div>
                <div class="mb-2">
                    <div class="text-muted mb-1">TX Hash</div>
                    <code style="font-size:0.75rem;word-break:break-all;color:var(--accent-blue);">${payment.tx_hash || '—'}</code>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Status</span>
                    <strong>${payment.status}</strong>
                </div>
                ${payment.reject_reason ? `
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Reject Reason</span>
                    <strong style="color:var(--accent-red);">${payment.reject_reason}</strong>
                </div>` : ''}
                ${payment.reject_note ? `
                <div class="mb-2">
                    <div class="text-muted mb-1">Reject Note</div>
                    <p class="mb-0">${payment.reject_note}</p>
                </div>` : ''}
            </div>
        `;
    });

    // Copy TX hash
    function copyTx(hash) {
        navigator.clipboard.writeText(hash).then(() => {
            // Brief visual feedback
            const toast = document.createElement('div');
            toast.textContent = 'TX hash copied!';
            toast.style.cssText = `
                position:fixed;bottom:20px;right:20px;
                background:var(--bg-elevated);color:var(--text-primary);
                padding:8px 16px;border-radius:8px;font-size:0.8rem;
                border:1px solid var(--border);z-index:9999;
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        });
    }
</script>
@endpush
