@extends('admin.layouts.app')

@section('title', 'Signals')
@section('page-title', 'Trading Signals')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">Home</a></li>
    <li class="breadcrumb-item active" style="color:var(--text-secondary);">Signals</li>
@endsection

@section('content')

{{-- ─── Stats Bar ───────────────────────────────────────────── --}}
<div class="row g-2 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fw-700" style="font-size:1.3rem;color:var(--accent-blue);">
                {{ number_format($signalStats['total'] ?? 0) }}
            </div>
            <div class="text-muted" style="font-size:0.75rem;">Total Signals</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fw-700" style="font-size:1.3rem;color:var(--accent-gold);">
                {{ number_format($signalStats['pending'] ?? 0) }}
            </div>
            <div class="text-muted" style="font-size:0.75rem;">Pending</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fw-700" style="font-size:1.3rem;color:var(--accent-green);">
                {{ number_format($signalStats['wins'] ?? 0) }}
            </div>
            <div class="text-muted" style="font-size:0.75rem;">Wins</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fw-700" style="font-size:1.3rem;color:var(--accent-red);">
                {{ number_format($signalStats['losses'] ?? 0) }}
            </div>
            <div class="text-muted" style="font-size:0.75rem;">Losses</div>
        </div>
    </div>
</div>

{{-- ─── Filters ─────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form action="{{ route('admin.signals.index') }}" method="GET" id="filterForm">
            <div class="row g-2 align-items-end">

                <div class="col-12 col-md-3">
                    <label class="form-label">Pair</label>
                    <select name="pair" class="form-select" onchange="this.form.submit()">
                        <option value="">All Pairs</option>
                        @foreach($pairs ?? [] as $pair)
                            <option value="{{ $pair }}" {{ request('pair') === $pair ? 'selected' : '' }}>
                                {{ $pair }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Timeframe</label>
                    <select name="timeframe" class="form-select" onchange="this.form.submit()">
                        <option value="">All TF</option>
                        @foreach(['M1','M5','M15','M30','H1','H4','D1','W1'] as $tf)
                            <option value="{{ $tf }}" {{ request('timeframe') === $tf ? 'selected' : '' }}>
                                {{ $tf }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">BUY & SELL</option>
                        <option value="BUY"  {{ request('type') === 'BUY'  ? 'selected' : '' }}>BUY</option>
                        <option value="SELL" {{ request('type') === 'SELL' ? 'selected' : '' }}>SELL</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="win"     {{ request('status') === 'win'     ? 'selected' : '' }}>Win</option>
                        <option value="loss"    {{ request('status') === 'loss'    ? 'selected' : '' }}>Loss</option>
                    </select>
                </div>

                <div class="col-6 col-md-3 d-flex gap-2">
                    <input
                        type="date"
                        name="date"
                        class="form-control"
                        value="{{ request('date') }}"
                        title="Filter by date"
                    >
                    <a href="{{ route('admin.signals.index') }}" class="btn btn-outline-secondary flex-shrink-0">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- ─── Table + Bulk Actions ────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-2">
    <div class="text-muted" style="font-size:0.85rem;">
        Showing <strong class="text-primary">{{ $signals->firstItem() ?? 0 }}–{{ $signals->lastItem() ?? 0 }}</strong>
        of <strong class="text-primary">{{ $signals->total() }}</strong> signals
    </div>
    <form action="{{ route('admin.signals.bulk') }}" method="POST" id="bulkForm">
        @csrf
        <input type="hidden" name="action" id="bulkAction">
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" id="bulkSelect" style="width:auto;">
                <option value="">Bulk Action</option>
                <option value="win">Mark as Win</option>
                <option value="loss">Mark as Loss</option>
                <option value="pending">Reset to Pending</option>
                <option value="delete">Delete</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyBulk()">Apply</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th style="width:40px;">
                        <input type="checkbox" class="form-check-input" onclick="toggleAll(this)">
                    </th>
                    <th>ID</th>
                    <th>Pair</th>
                    <th>TF</th>
                    <th>Type</th>
                    <th>Entry</th>
                    <th>SL</th>
                    <th>TP</th>
                    <th>Conf%</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th style="width:100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($signals as $signal)
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input row-check"
                               name="signal_ids[]" value="{{ $signal->id }}" form="bulkForm">
                    </td>
                    <td class="text-muted" style="font-size:0.8rem;">#{{ $signal->id }}</td>
                    <td>
                        <a href="{{ route('admin.signals.show', $signal->id) }}"
                           class="fw-600 text-decoration-none" style="color:var(--text-primary);">
                            {{ $signal->pair }}
                        </a>
                    </td>
                    <td>
                        <span class="badge bg-secondary bg-opacity-25 text-secondary"
                              style="font-size:0.72rem;">{{ $signal->timeframe }}</span>
                    </td>
                    <td>
                        <span class="badge-custom {{ $signal->type === 'BUY' ? 'badge-buy' : 'badge-sell' }}">
                            @if($signal->type === 'BUY')
                                <i class="bi bi-arrow-up-short"></i>
                            @else
                                <i class="bi bi-arrow-down-short"></i>
                            @endif
                            {{ $signal->type }}
                        </span>
                    </td>
                    <td class="fw-600" style="font-size:0.875rem;">
                        {{ number_format($signal->entry_price, 4) }}
                    </td>
                    <td style="color:var(--accent-red);font-size:0.8rem;">
                        {{ number_format($signal->stop_loss, 4) }}
                    </td>
                    <td style="color:var(--accent-green);font-size:0.8rem;">
                        @foreach($signal->take_profits ?? [] as $i => $tp)
                            @if($i === 0){{ number_format($tp, 4) }}@endif
                        @endforeach
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1">
                            <div class="progress flex-fill" style="height:4px;width:50px;">
                                <div class="progress-bar"
                                     style="width:{{ $signal->confidence_score }}%;
                                            background:{{ $signal->confidence_score >= 70 ? 'var(--accent-green)' : ($signal->confidence_score >= 50 ? 'var(--accent-gold)' : 'var(--accent-red)') }};">
                                </div>
                            </div>
                            <span style="font-size:0.75rem;color:var(--accent-gold);">
                                {{ $signal->confidence_score }}%
                            </span>
                        </div>
                    </td>
                    <td>
                        @if($signal->status === 'win')
                            <span class="badge-custom badge-win"><i class="bi bi-trophy-fill me-1"></i>Win</span>
                        @elseif($signal->status === 'loss')
                            <span class="badge-custom badge-loss"><i class="bi bi-x-circle-fill me-1"></i>Loss</span>
                        @else
                            <span class="badge-custom badge-pending"><i class="bi bi-clock me-1"></i>Pending</span>
                        @endif
                    </td>
                    <td class="text-muted" style="font-size:0.78rem;">
                        {{ $signal->created_at->format('d M, H:i') }}
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.signals.show', $signal->id) }}"
                               class="btn btn-sm btn-outline-secondary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($signal->status === 'pending')
                            <form action="{{ route('admin.signals.mark', $signal->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="win">
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Mark Win">
                                    <i class="bi bi-check2"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.signals.mark', $signal->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="loss">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Mark Loss">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="text-center py-5">
                        <i class="bi bi-graph-up-arrow fs-2 d-block mb-2 text-muted"></i>
                        <div class="text-muted">No signals found matching your filters.</div>
                        <a href="{{ route('admin.signals.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
                            Clear Filters
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($signals->hasPages())
    <div class="card-footer d-flex align-items-center justify-content-between py-3">
        <div class="text-muted" style="font-size:0.8rem;">
            Page {{ $signals->currentPage() }} of {{ $signals->lastPage() }}
        </div>
        {{ $signals->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    function toggleAll(master) {
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = master.checked);
    }

    function applyBulk() {
        const action = document.getElementById('bulkSelect').value;
        if (!action) { alert('Please select a bulk action.'); return; }
        const checked = document.querySelectorAll('.row-check:checked');
        if (!checked.length) { alert('Please select at least one signal.'); return; }
        const msgs = {
            win: 'Mark selected signals as WIN?',
            loss: 'Mark selected signals as LOSS?',
            pending: 'Reset selected signals to PENDING?',
            delete: `Delete ${checked.length} signal(s)? This cannot be undone.`
        };
        if (!confirm(msgs[action] || 'Apply action?')) return;
        document.getElementById('bulkAction').value = action;
        document.getElementById('bulkForm').submit();
    }
</script>
@endpush
