@extends('admin.layouts.app')

@section('title', 'Trading Pairs')
@section('page-title', 'Trading Pairs')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">Home</a></li>
    <li class="breadcrumb-item active" style="color:var(--text-secondary);">Trading Pairs</li>
@endsection

@section('content')

{{-- ─── Stats Row ──────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card blue">
            <div class="stat-icon blue"><i class="bi bi-diagram-2"></i></div>
            <div class="stat-value">{{ $pairs->count() }}</div>
            <div class="stat-label">Total Pairs</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card green">
            <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
            <div class="stat-value">{{ $pairs->where('is_active', true)->count() }}</div>
            <div class="stat-label">Active Pairs</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card gold">
            <div class="stat-icon gold"><i class="bi bi-currency-bitcoin"></i></div>
            <div class="stat-value">{{ $pairs->where('type', 'crypto')->count() }}</div>
            <div class="stat-label">Crypto Pairs</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card purple">
            <div class="stat-icon purple"><i class="bi bi-currency-exchange"></i></div>
            <div class="stat-value">{{ $pairs->where('type', 'forex')->count() }}</div>
            <div class="stat-label">Forex Pairs</div>
        </div>
    </div>
</div>

{{-- ─── Header Bar ──────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="text-muted" style="font-size:0.875rem;">
        {{ $pairs->count() }} pair(s) configured
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pairModal" onclick="openAddModal()">
        <i class="bi bi-plus-circle me-2"></i>Add New Pair
    </button>
</div>

{{-- ─── Filter Tabs ─────────────────────────────────────────────── --}}
<div class="mb-3 d-flex gap-2 flex-wrap">
    <button class="btn btn-sm btn-primary filter-btn active" data-filter="all">All</button>
    <button class="btn btn-sm btn-outline-secondary filter-btn" data-filter="crypto">Crypto</button>
    <button class="btn btn-sm btn-outline-secondary filter-btn" data-filter="forex">Forex</button>
    <button class="btn btn-sm btn-outline-secondary filter-btn" data-filter="active">Active Only</button>
    <button class="btn btn-sm btn-outline-secondary filter-btn" data-filter="inactive">Inactive</button>
</div>

{{-- ─── Pairs Table ──────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-diagram-2 me-2" style="color:var(--accent-blue)"></i>All Trading Pairs</span>
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Search symbol or name…"
               style="max-width:220px;">
    </div>
    <div class="table-responsive">
        <table class="table" id="pairsTable">
            <thead>
                <tr>
                    <th>Symbol</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Exchange</th>
                    <th>Package Access</th>
                    <th>Signals</th>
                    <th>Status</th>
                    <th style="width:130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pairs as $pair)
                <tr class="pair-row"
                    data-type="{{ $pair->type }}"
                    data-active="{{ $pair->is_active ? 'active' : 'inactive' }}"
                    data-search="{{ strtolower($pair->symbol . ' ' . $pair->name) }}">

                    {{-- Symbol --}}
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center rounded"
                                 style="width:34px;height:34px;background:{{ $pair->type === 'crypto' ? 'rgba(255,215,0,0.15)' : 'rgba(41,121,255,0.15)' }};">
                                <i class="bi bi-{{ $pair->type === 'crypto' ? 'currency-bitcoin' : 'currency-exchange' }}"
                                   style="color:{{ $pair->type === 'crypto' ? 'var(--accent-gold)' : 'var(--accent-blue)' }};font-size:1rem;"></i>
                            </div>
                            <span class="fw-700" style="font-family:monospace;font-size:0.9rem;letter-spacing:0.03em;">
                                {{ $pair->symbol }}
                            </span>
                        </div>
                    </td>

                    {{-- Name --}}
                    <td style="color:var(--text-secondary);font-size:0.85rem;">{{ $pair->name }}</td>

                    {{-- Type --}}
                    <td>
                        @if($pair->type === 'crypto')
                            <span class="badge-custom" style="background:rgba(255,215,0,0.15);color:var(--accent-gold);border:1px solid rgba(255,215,0,0.3);">
                                <i class="bi bi-currency-bitcoin"></i> Crypto
                            </span>
                        @else
                            <span class="badge-custom" style="background:rgba(41,121,255,0.15);color:var(--accent-blue);border:1px solid rgba(41,121,255,0.3);">
                                <i class="bi bi-currency-exchange"></i> Forex
                            </span>
                        @endif
                    </td>

                    {{-- Exchange --}}
                    <td style="font-size:0.82rem;color:var(--text-secondary);text-transform:capitalize;">
                        {{ $pair->exchange }}
                    </td>

                    {{-- Package Access --}}
                    <td>
                        @foreach($pair->package_access ?? [] as $slug)
                            @php
                                $color = match($slug) {
                                    'basic'   => 'rgba(41,121,255,0.15)|var(--accent-blue)|rgba(41,121,255,0.3)',
                                    'best'    => 'rgba(156,39,176,0.15)|#9C27B0|rgba(156,39,176,0.3)',
                                    'premium' => 'rgba(255,215,0,0.15)|var(--accent-gold)|rgba(255,215,0,0.3)',
                                    default   => 'rgba(139,148,158,0.15)|var(--text-secondary)|rgba(139,148,158,0.3)',
                                };
                                [$bg, $fg, $border] = explode('|', $color);
                            @endphp
                            <span class="badge-custom me-1"
                                  style="background:{{ $bg }};color:{{ $fg }};border:1px solid {{ $border }};font-size:0.65rem;">
                                {{ ucfirst($slug) }}
                            </span>
                        @endforeach
                    </td>

                    {{-- Signals --}}
                    <td>
                        <span style="color:var(--text-secondary);font-size:0.85rem;">
                            {{ $pair->signals_count }}
                        </span>
                    </td>

                    {{-- Status --}}
                    <td>
                        @if($pair->is_active)
                            <span class="badge-custom badge-active">
                                <i class="bi bi-circle-fill" style="font-size:0.4rem;"></i> Active
                            </span>
                        @else
                            <span class="badge-custom badge-blocked">
                                <i class="bi bi-circle-fill" style="font-size:0.4rem;"></i> Inactive
                            </span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td>
                        <div class="d-flex gap-1">
                            {{-- Edit --}}
                            <button class="btn btn-sm btn-outline-secondary"
                                    title="Edit"
                                    onclick="openEditModal({{ $pair->id }}, '{{ $pair->symbol }}', '{{ addslashes($pair->name) }}', '{{ $pair->type }}', '{{ $pair->exchange }}', {{ $pair->pip_size }}, {{ json_encode($pair->package_access) }}, {{ $pair->is_active ? 'true' : 'false' }})">
                                <i class="bi bi-pencil"></i>
                            </button>

                            {{-- Toggle --}}
                            <form action="{{ route('admin.pairs.toggle', $pair->id) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="btn btn-sm {{ $pair->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                        title="{{ $pair->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="bi bi-{{ $pair->is_active ? 'pause-circle' : 'play-circle' }}"></i>
                                </button>
                            </form>

                            {{-- Delete --}}
                            <form action="{{ route('admin.pairs.destroy', $pair->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Delete"
                                        onclick="return confirm('Delete {{ $pair->symbol }}? This cannot be undone.')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="bi bi-diagram-2 fs-1 d-block mb-3 text-muted"></i>
                        <p class="text-muted mb-2">No trading pairs configured yet.</p>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#pairModal" onclick="openAddModal()">
                            <i class="bi bi-plus-circle me-1"></i>Add First Pair
                        </button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- ─── Add / Edit Modal ──────────────────────────────────────── --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="pairModal" tabindex="-1" aria-labelledby="pairModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border);">

            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h5 class="modal-title fw-700" id="pairModalLabel">Add New Trading Pair</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="pairForm" method="POST">
                @csrf
                <span id="methodField"></span>

                <div class="modal-body">

                    {{-- Symbol + Name row --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Symbol <span class="text-danger">*</span></label>
                            <input type="text" name="symbol" id="f_symbol"
                                   class="form-control" placeholder="e.g. BTCUSDT"
                                   style="text-transform:uppercase;" required>
                            <div class="form-text" style="color:var(--text-secondary);font-size:0.75rem;">
                                Crypto: Binance symbol &bull; Forex: 6-char symbol
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="f_name"
                                   class="form-control" placeholder="e.g. Bitcoin / Tether" required>
                        </div>
                    </div>

                    {{-- Type + Exchange + Pip size row --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" id="f_type" class="form-select" required onchange="onTypeChange(this.value)">
                                <option value="crypto">Crypto</option>
                                <option value="forex">Forex</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Exchange <span class="text-danger">*</span></label>
                            <select name="exchange" id="f_exchange" class="form-select" required>
                                <option value="binance">Binance</option>
                                <option value="forex">Forex (Alpha Vantage)</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pip Size <span class="text-danger">*</span></label>
                            <input type="number" name="pip_size" id="f_pip_size"
                                   class="form-control" step="0.000001" min="0.000001"
                                   placeholder="0.01" required>
                            <div class="form-text" style="color:var(--text-secondary);font-size:0.75rem;" id="pipHint">
                                BTC/ETH/SOL → 0.01
                            </div>
                        </div>
                    </div>

                    {{-- Package Access --}}
                    <div class="mb-3">
                        <label class="form-label">Package Access <span class="text-danger">*</span></label>
                        <div class="p-3 rounded" style="background:var(--bg-elevated);border:1px solid var(--border);">
                            <div class="row g-2">
                                @foreach($packages as $pkg)
                                <div class="col-auto">
                                    <div class="form-check form-check-inline m-0">
                                        <input class="form-check-input pkg-check" type="checkbox"
                                               name="package_access[]"
                                               value="{{ $pkg->slug }}"
                                               id="pkg_{{ $pkg->slug }}">
                                        <label class="form-check-label" for="pkg_{{ $pkg->slug }}"
                                               style="font-size:0.875rem;">
                                            {{ $pkg->name }}
                                            <span class="text-muted" style="font-size:0.75rem;">({{ $pkg->slug }})</span>
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="mt-2 d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        onclick="document.querySelectorAll('.pkg-check').forEach(c => c.checked = true)">
                                    Select All
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        onclick="document.querySelectorAll('.pkg-check').forEach(c => c.checked = false)">
                                    Clear
                                </button>
                            </div>
                        </div>
                        <div class="form-text" style="color:var(--text-secondary);font-size:0.75rem;">
                            Select which subscription plans can access this pair.
                        </div>
                    </div>

                    {{-- Active status --}}
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active"
                               id="f_is_active" value="1" checked>
                        <label class="form-check-label" for="f_is_active" style="font-size:0.875rem;">
                            Active — pair will be visible to users
                        </label>
                    </div>

                    {{-- Info box --}}
                    <div class="alert alert-info mt-3 mb-0 d-flex gap-2 align-items-start" id="cryptoInfo">
                        <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
                        <div style="font-size:0.82rem;">
                            <strong>Crypto pair:</strong> symbol must match exactly on Binance
                            (e.g. <code>BTCUSDT</code>, <code>ETHUSDT</code>).
                            Verify at <code>api.binance.com/api/v3/exchangeInfo</code> first.
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 d-flex gap-2 align-items-start d-none" id="forexInfo">
                        <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
                        <div style="font-size:0.82rem;">
                            <strong>Forex pair:</strong> use 6-char symbols only
                            (e.g. <code>EURUSD</code>, <code>GBPJPY</code>).
                            Data is fetched from Alpha Vantage / TwelveData.
                        </div>
                    </div>

                </div>

                <div class="modal-footer" style="border-top:1px solid var(--border);">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <i class="bi bi-plus-circle me-2"></i>Add Pair
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection


@push('scripts')
<script>
// ── Filter buttons ─────────────────────────────────────────────────
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.filter-btn').forEach(b => {
            b.classList.remove('btn-primary','active');
            b.classList.add('btn-outline-secondary');
        });
        this.classList.add('btn-primary','active');
        this.classList.remove('btn-outline-secondary');

        const filter = this.dataset.filter;
        document.querySelectorAll('.pair-row').forEach(row => {
            let show = true;
            if (filter === 'crypto')   show = row.dataset.type   === 'crypto';
            if (filter === 'forex')    show = row.dataset.type   === 'forex';
            if (filter === 'active')   show = row.dataset.active === 'active';
            if (filter === 'inactive') show = row.dataset.active === 'inactive';
            row.style.display = show ? '' : 'none';
        });
    });
});

// ── Search ─────────────────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.pair-row').forEach(row => {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
});

// ── Type change → swap exchange select + pip hint + info box ───────
function onTypeChange(type) {
    const ex      = document.getElementById('f_exchange');
    const pipHint = document.getElementById('pipHint');
    const ci      = document.getElementById('cryptoInfo');
    const fi      = document.getElementById('forexInfo');

    if (type === 'crypto') {
        ex.value  = 'binance';
        pipHint.textContent = 'BTC/ETH/SOL → 0.01 | XRP/ADA → 0.0001';
        ci.classList.remove('d-none');
        fi.classList.add('d-none');
    } else {
        ex.value  = 'forex';
        pipHint.textContent = 'Most pairs → 0.0001 | JPY pairs → 0.01';
        ci.classList.add('d-none');
        fi.classList.remove('d-none');
    }
}

// ── Open ADD modal ─────────────────────────────────────────────────
function openAddModal() {
    document.getElementById('pairModalLabel').textContent = 'Add New Trading Pair';
    document.getElementById('saveBtn').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Add Pair';

    const form = document.getElementById('pairForm');
    form.action = '{{ route("admin.pairs.store") }}';
    document.getElementById('methodField').innerHTML = '';

    // Clear fields
    form.reset();
    document.getElementById('f_is_active').checked = true;
    document.querySelectorAll('.pkg-check').forEach(c => c.checked = false);
    onTypeChange('crypto');
}

// ── Open EDIT modal ────────────────────────────────────────────────
function openEditModal(id, symbol, name, type, exchange, pipSize, packageAccess, isActive) {
    document.getElementById('pairModalLabel').textContent = 'Edit Pair — ' + symbol;
    document.getElementById('saveBtn').innerHTML = '<i class="bi bi-check-circle me-2"></i>Save Changes';

    const form = document.getElementById('pairForm');
    form.action = '/admin/pairs/' + id;
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';

    document.getElementById('f_symbol').value   = symbol;
    document.getElementById('f_name').value     = name;
    document.getElementById('f_type').value     = type;
    document.getElementById('f_exchange').value = exchange;
    document.getElementById('f_pip_size').value = pipSize;
    document.getElementById('f_is_active').checked = isActive;

    // Package access checkboxes
    document.querySelectorAll('.pkg-check').forEach(c => {
        c.checked = packageAccess.includes(c.value);
    });

    onTypeChange(type);
}
</script>
@endpush
