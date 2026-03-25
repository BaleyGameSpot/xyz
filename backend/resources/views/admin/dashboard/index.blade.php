@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
    .chart-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; }
    .activity-table td, .activity-table th { padding: 0.6rem 0.75rem; font-size: 0.82rem; }
    .progress { background-color: var(--bg-elevated); }
</style>
@endpush

@section('content')

{{-- ─── Stat Cards ──────────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    {{-- Total Users --}}
    <div class="col-6 col-xl-3">
        <div class="stat-card blue">
            <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value">{{ number_format($stats['total_users'] ?? 0) }}</div>
            <div class="stat-label">Total Users</div>
            @if(isset($stats['users_change']))
                <div class="stat-change {{ $stats['users_change'] >= 0 ? 'up' : 'down' }}">
                    <i class="bi bi-arrow-{{ $stats['users_change'] >= 0 ? 'up' : 'down' }}-short"></i>
                    {{ abs($stats['users_change']) }}% vs last month
                </div>
            @endif
        </div>
    </div>

    {{-- Active Subscriptions --}}
    <div class="col-6 col-xl-3">
        <div class="stat-card green">
            <div class="stat-icon green"><i class="bi bi-patch-check-fill"></i></div>
            <div class="stat-value">{{ number_format($stats['active_subscriptions'] ?? 0) }}</div>
            <div class="stat-label">Active Subscriptions</div>
            @if(isset($stats['subs_change']))
                <div class="stat-change {{ $stats['subs_change'] >= 0 ? 'up' : 'down' }}">
                    <i class="bi bi-arrow-{{ $stats['subs_change'] >= 0 ? 'up' : 'down' }}-short"></i>
                    {{ abs($stats['subs_change']) }}% vs last month
                </div>
            @endif
        </div>
    </div>

    {{-- Signals Today --}}
    <div class="col-6 col-xl-3">
        <div class="stat-card purple">
            <div class="stat-icon purple"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-value">{{ number_format($stats['signals_today'] ?? 0) }}</div>
            <div class="stat-label">Signals Today</div>
            @if(isset($stats['signals_total']))
                <div class="stat-change" style="color:var(--text-secondary);">
                    {{ number_format($stats['signals_total']) }} total signals
                </div>
            @endif
        </div>
    </div>

    {{-- Win Rate --}}
    <div class="col-6 col-xl-3">
        <div class="stat-card gold">
            <div class="stat-icon gold"><i class="bi bi-trophy-fill"></i></div>
            <div class="stat-value">{{ number_format($stats['win_rate'] ?? 0, 1) }}%</div>
            <div class="stat-label">Overall Win Rate</div>
            @if(isset($stats['total_closed']))
                <div class="stat-change" style="color:var(--text-secondary);">
                    {{ number_format($stats['total_closed']) }} closed signals
                </div>
            @endif
        </div>
    </div>

</div>

{{-- ─── Secondary Stats ──────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div style="font-size:1.4rem;font-weight:700;color:var(--accent-green);">
                {{ number_format($stats['revenue_month'] ?? 0) }}
                <span style="font-size:0.9rem;">USDT</span>
            </div>
            <div class="text-muted" style="font-size:0.75rem;">Revenue This Month</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div style="font-size:1.4rem;font-weight:700;color:var(--accent-gold);">
                {{ number_format($stats['pending_payments'] ?? 0) }}
            </div>
            <div class="text-muted" style="font-size:0.75rem;">Pending Payments</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div style="font-size:1.4rem;font-weight:700;color:var(--accent-blue);">
                {{ number_format($stats['signals_pending'] ?? 0) }}
            </div>
            <div class="text-muted" style="font-size:0.75rem;">Pending Signals</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div style="font-size:1.4rem;font-weight:700;color:#9C27B0;">
                {{ number_format($stats['new_users_week'] ?? 0) }}
            </div>
            <div class="text-muted" style="font-size:0.75rem;">New Users (7 Days)</div>
        </div>
    </div>
</div>

{{-- ─── Charts Row ───────────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    {{-- Line chart: Signals per day --}}
    <div class="col-12 col-lg-7">
        <div class="chart-card p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <div class="fw-600" style="font-size:0.9rem;">Signals Generated</div>
                    <div class="text-muted" style="font-size:0.75rem;">Last 30 days</div>
                </div>
                <span class="badge-custom badge-active">Live</span>
            </div>
            <div class="chart-container" style="height:220px;">
                <canvas id="signalsLineChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Doughnut: Win/Loss --}}
    <div class="col-12 col-md-6 col-lg-3">
        <div class="chart-card p-3 h-100">
            <div class="fw-600 mb-1" style="font-size:0.9rem;">Win / Loss Ratio</div>
            <div class="text-muted mb-3" style="font-size:0.75rem;">All time</div>
            <div class="chart-container" style="height:180px;">
                <canvas id="winLossDoughnut"></canvas>
            </div>
            <div class="d-flex justify-content-center gap-3 mt-3">
                <div class="text-center">
                    <div class="fw-700" style="color:var(--accent-green);font-size:1.1rem;">
                        {{ $stats['total_wins'] ?? 0 }}
                    </div>
                    <div class="text-muted" style="font-size:0.7rem;">Wins</div>
                </div>
                <div class="text-center">
                    <div class="fw-700" style="color:var(--accent-red);font-size:1.1rem;">
                        {{ $stats['total_losses'] ?? 0 }}
                    </div>
                    <div class="text-muted" style="font-size:0.7rem;">Losses</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bar chart: Signals by pair --}}
    <div class="col-12 col-md-6 col-lg-2">
        <div class="chart-card p-3 h-100">
            <div class="fw-600 mb-1" style="font-size:0.9rem;">By Pair</div>
            <div class="text-muted mb-3" style="font-size:0.75rem;">Top pairs</div>
            <div class="chart-container" style="height:200px;">
                <canvas id="pairBarChart"></canvas>
            </div>
        </div>
    </div>

</div>

{{-- ─── Recent Activity Tables ───────────────────────────────── --}}
<div class="row g-3">

    {{-- Latest Signals --}}
    <div class="col-12 col-xl-5">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-graph-up-arrow me-2" style="color:var(--accent-blue);"></i>Latest Signals</span>
                <a href="{{ route('admin.signals.index') }}" class="btn btn-sm btn-outline-secondary">
                    View All <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table activity-table mb-0">
                    <thead>
                        <tr>
                            <th>Pair</th>
                            <th>Type</th>
                            <th>Entry</th>
                            <th>Conf%</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentSignals ?? [] as $signal)
                        <tr>
                            <td class="fw-600">{{ $signal->pair }}</td>
                            <td>
                                <span class="badge-custom {{ $signal->type === 'BUY' ? 'badge-buy' : 'badge-sell' }}">
                                    {{ $signal->type }}
                                </span>
                            </td>
                            <td>{{ number_format($signal->entry_price, 4) }}</td>
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
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-4 d-block mb-1"></i>No signals yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Latest Registrations --}}
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-person-plus me-2" style="color:var(--accent-green);"></i>New Users</span>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
                    View All <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table activity-table mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Plan</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentUsers ?? [] as $user)
                        <tr>
                            <td>
                                <div class="fw-600" style="font-size:0.82rem;">{{ Str::limit($user->name, 18) }}</div>
                                <div class="text-muted" style="font-size:0.7rem;">{{ Str::limit($user->email, 22) }}</div>
                            </td>
                            <td>
                                @if($user->activeSubscription)
                                    <span class="badge-custom badge-active" style="font-size:0.65rem;">
                                        {{ $user->activeSubscription->package->name ?? 'N/A' }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size:0.75rem;">Free</span>
                                @endif
                            </td>
                            <td class="text-muted" style="font-size:0.75rem;">
                                {{ $user->created_at->diffForHumans(null, true) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                <i class="bi bi-people fs-4 d-block mb-1"></i>No users yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent Payments --}}
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-credit-card me-2" style="color:var(--accent-gold);"></i>Payments</span>
                <a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-outline-secondary">
                    View All <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table activity-table mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Amt</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments ?? [] as $payment)
                        <tr>
                            <td style="font-size:0.8rem;">{{ Str::limit($payment->user->name ?? 'N/A', 14) }}</td>
                            <td class="fw-600" style="color:var(--accent-green);font-size:0.82rem;">
                                ${{ number_format($payment->amount, 2) }}
                            </td>
                            <td>
                                @if($payment->status === 'verified')
                                    <span class="badge-custom badge-win" style="font-size:0.65rem;">OK</span>
                                @elseif($payment->status === 'rejected')
                                    <span class="badge-custom badge-loss" style="font-size:0.65rem;">Rej</span>
                                @else
                                    <span class="badge-custom badge-pending" style="font-size:0.65rem;">Pend</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                <i class="bi bi-receipt fs-4 d-block mb-1"></i>No payments
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
// ── Chart defaults ──────────────────────────────────────────────
Chart.defaults.color = '#8B949E';
Chart.defaults.borderColor = '#30363D';
Chart.defaults.font.family = 'Inter';

// ── Signals line chart ──────────────────────────────────────────
(function() {
    const labels = @json($chartData['signal_dates'] ?? []);
    const data   = @json($chartData['signal_counts'] ?? []);

    new Chart(document.getElementById('signalsLineChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Signals',
                data,
                borderColor: '#2979FF',
                backgroundColor: 'rgba(41,121,255,0.08)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: '#2979FF',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: '#30363D' }, ticks: { maxTicksLimit: 8 } },
                y: { grid: { color: '#30363D' }, beginAtZero: true }
            }
        }
    });
})();

// ── Win/Loss doughnut ───────────────────────────────────────────
(function() {
    const wins   = {{ $stats['total_wins']   ?? 0 }};
    const losses = {{ $stats['total_losses'] ?? 0 }};

    new Chart(document.getElementById('winLossDoughnut'), {
        type: 'doughnut',
        data: {
            labels: ['Wins', 'Losses'],
            datasets: [{
                data: [wins, losses],
                backgroundColor: ['rgba(0,200,83,0.8)', 'rgba(255,23,68,0.8)'],
                borderColor: ['#00C853', '#FF1744'],
                borderWidth: 1,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: {
                    label: (ctx) => ` ${ctx.label}: ${ctx.parsed}`
                }}
            }
        }
    });
})();

// ── Signals by pair bar chart ───────────────────────────────────
(function() {
    const pairs  = @json($chartData['pair_labels'] ?? []);
    const counts = @json($chartData['pair_counts'] ?? []);

    new Chart(document.getElementById('pairBarChart'), {
        type: 'bar',
        data: {
            labels: pairs,
            datasets: [{
                label: 'Signals',
                data: counts,
                backgroundColor: 'rgba(41,121,255,0.7)',
                borderColor: '#2979FF',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: '#30363D' }, beginAtZero: true },
                y: { grid: { display: false }, ticks: { font: { size: 10 } } }
            }
        }
    });
})();
</script>
@endpush
