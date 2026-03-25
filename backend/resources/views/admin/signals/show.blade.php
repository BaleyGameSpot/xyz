@extends('admin.layouts.app')

@section('title', 'Signal — ' . $signal->pair)
@section('page-title', 'Signal Details')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.signals.index') }}" class="text-decoration-none text-muted">Signals</a></li>
    <li class="breadcrumb-item active" style="color:var(--text-secondary);">{{ $signal->pair }} #{{ $signal->id }}</li>
@endsection

@section('content')

<div class="row g-3">

    {{-- ─── Left: Signal Card ─────────────────────────────────── --}}
    <div class="col-12 col-lg-4">

        {{-- Main Signal Card --}}
        <div class="card mb-3" style="border-top:3px solid {{ $signal->type === 'BUY' ? 'var(--accent-green)' : 'var(--accent-red)' }};">
            <div class="card-body">

                {{-- Header --}}
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h4 class="fw-700 mb-1">{{ $signal->pair }}</h4>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge-custom {{ $signal->type === 'BUY' ? 'badge-buy' : 'badge-sell' }}">
                                <i class="bi bi-arrow-{{ $signal->type === 'BUY' ? 'up' : 'down' }}-short"></i>
                                {{ $signal->type }}
                            </span>
                            <span class="badge bg-secondary bg-opacity-25 text-secondary"
                                  style="font-size:0.72rem;">{{ $signal->timeframe }}</span>
                        </div>
                    </div>
                    <div class="text-end">
                        @if($signal->status === 'win')
                            <span class="badge-custom badge-win" style="font-size:0.8rem;">
                                <i class="bi bi-trophy-fill"></i> Win
                            </span>
                        @elseif($signal->status === 'loss')
                            <span class="badge-custom badge-loss" style="font-size:0.8rem;">
                                <i class="bi bi-x-circle-fill"></i> Loss
                            </span>
                        @else
                            <span class="badge-custom badge-pending" style="font-size:0.8rem;">
                                <i class="bi bi-clock"></i> Pending
                            </span>
                        @endif
                        <div class="text-muted mt-1" style="font-size:0.72rem;">#{{ $signal->id }}</div>
                    </div>
                </div>

                {{-- Price Levels --}}
                <div class="p-3 rounded mb-3" style="background:var(--bg-elevated);">
                    <div class="row text-center g-2">
                        <div class="col-4">
                            <div class="text-muted" style="font-size:0.68rem;text-transform:uppercase;">Entry</div>
                            <div class="fw-700 mt-1" style="font-size:1.05rem;">
                                {{ number_format($signal->entry_price, 4) }}
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted" style="font-size:0.68rem;text-transform:uppercase;">Stop Loss</div>
                            <div class="fw-700 mt-1" style="font-size:1.05rem;color:var(--accent-red);">
                                {{ number_format($signal->stop_loss, 4) }}
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted" style="font-size:0.68rem;text-transform:uppercase;">TP 1</div>
                            <div class="fw-700 mt-1" style="font-size:1.05rem;color:var(--accent-green);">
                                {{ isset($signal->take_profits[0]) ? number_format($signal->take_profits[0], 4) : '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Take Profits --}}
                @if(count($signal->take_profits ?? []) > 1)
                <div class="mb-3">
                    <div class="text-muted mb-2" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">
                        Take Profit Levels
                    </div>
                    @foreach($signal->take_profits as $i => $tp)
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted" style="font-size:0.8rem;">TP {{ $i + 1 }}</span>
                        <span class="fw-600" style="color:var(--accent-green);font-size:0.875rem;">
                            {{ number_format($tp, 4) }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Confidence --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted" style="font-size:0.8rem;">Confidence Score</span>
                        <span class="fw-700" style="color:var(--accent-gold);">{{ $signal->confidence_score }}%</span>
                    </div>
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar" style="
                            width:{{ $signal->confidence_score }}%;
                            background:{{ $signal->confidence_score >= 70 ? 'var(--accent-green)' : ($signal->confidence_score >= 50 ? 'var(--accent-gold)' : 'var(--accent-red)') }};
                        "></div>
                    </div>
                </div>

                {{-- Meta --}}
                <div style="font-size:0.8rem;">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Generated</span>
                        <span>{{ $signal->created_at->format('d M Y, H:i') }}</span>
                    </div>
                    @if($signal->closed_at)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Closed</span>
                        <span>{{ $signal->closed_at->format('d M Y, H:i') }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Notifications Sent</span>
                        <span>{{ $signal->notifications_sent ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mark Win / Loss --}}
        @if($signal->status === 'pending')
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-pencil-square me-2" style="color:var(--accent-gold);"></i>Update Outcome
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <form action="{{ route('admin.signals.mark', $signal->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="win">
                            <button type="submit" class="btn btn-success w-100"
                                onclick="return confirm('Mark this signal as WIN?')">
                                <i class="bi bi-trophy-fill me-1"></i>Mark Win
                            </button>
                        </form>
                    </div>
                    <div class="col-6">
                        <form action="{{ route('admin.signals.mark', $signal->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="loss">
                            <button type="submit" class="btn btn-danger w-100"
                                onclick="return confirm('Mark this signal as LOSS?')">
                                <i class="bi bi-x-circle-fill me-1"></i>Mark Loss
                            </button>
                        </form>
                    </div>
                </div>
                <div class="text-muted mt-2" style="font-size:0.75rem;">
                    This will send push notifications to all affected users.
                </div>
            </div>
        </div>
        @elseif($signal->status !== 'pending')
        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.signals.mark', $signal->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="pending">
                    <button type="submit" class="btn btn-outline-secondary w-100"
                        onclick="return confirm('Reset this signal to PENDING?')">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset to Pending
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Pair Performance --}}
        <div class="card">
            <div class="card-header">
                <i class="bi bi-bar-chart me-2" style="color:var(--accent-blue);"></i>
                {{ $signal->pair }} Performance
            </div>
            <div class="card-body">
                @php $perf = $pairPerformance ?? []; @endphp
                <div class="row text-center g-2 mb-3">
                    <div class="col-4">
                        <div class="text-muted" style="font-size:0.7rem;">Total</div>
                        <div class="fw-700">{{ $perf['total'] ?? 0 }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted" style="font-size:0.7rem;">Wins</div>
                        <div class="fw-700" style="color:var(--accent-green);">{{ $perf['wins'] ?? 0 }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted" style="font-size:0.7rem;">Losses</div>
                        <div class="fw-700" style="color:var(--accent-red);">{{ $perf['losses'] ?? 0 }}</div>
                    </div>
                </div>
                @if(isset($perf['win_rate']))
                <div class="mb-1 d-flex justify-content-between" style="font-size:0.8rem;">
                    <span class="text-muted">Win Rate</span>
                    <span class="fw-700" style="color:var(--accent-gold);">{{ $perf['win_rate'] }}%</span>
                </div>
                <div class="progress" style="height:5px;">
                    <div class="progress-bar"
                         style="width:{{ $perf['win_rate'] }}%;background:var(--accent-gold);"></div>
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ─── Right: Indicator Breakdown ─────────────────────── --}}
    <div class="col-12 col-lg-8">

        {{-- Reason / Indicator Breakdown --}}
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-cpu me-2" style="color:var(--accent-blue);"></i>
                Signal Analysis — Indicator Breakdown
            </div>
            <div class="card-body">
                @if($signal->indicators && count($signal->indicators))
                <div class="row g-3">
                    @foreach($signal->indicators as $indicator)
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded" style="background:var(--bg-elevated);">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="fw-600" style="font-size:0.875rem;">{{ $indicator['name'] }}</div>
                                <span class="badge-custom {{ ($indicator['signal'] ?? '') === 'BUY' ? 'badge-buy' : (($indicator['signal'] ?? '') === 'SELL' ? 'badge-sell' : 'badge-pending') }}"
                                      style="font-size:0.65rem;">
                                    {{ $indicator['signal'] ?? 'NEUTRAL' }}
                                </span>
                            </div>
                            @if(isset($indicator['value']))
                            <div class="d-flex justify-content-between mb-1" style="font-size:0.8rem;">
                                <span class="text-muted">Value</span>
                                <span class="fw-600">{{ $indicator['value'] }}</span>
                            </div>
                            @endif
                            @if(isset($indicator['weight']))
                            <div class="mb-1">
                                <div class="d-flex justify-content-between mb-1" style="font-size:0.75rem;">
                                    <span class="text-muted">Weight</span>
                                    <span style="color:var(--accent-gold);">{{ $indicator['weight'] }}%</span>
                                </div>
                                <div class="progress" style="height:3px;">
                                    <div class="progress-bar"
                                         style="width:{{ $indicator['weight'] }}%;background:var(--accent-gold);"></div>
                                </div>
                            </div>
                            @endif
                            @if(isset($indicator['description']))
                            <div class="text-muted mt-1" style="font-size:0.75rem;">
                                {{ $indicator['description'] }}
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-cpu fs-3 d-block mb-2"></i>
                    <div>No indicator breakdown available for this signal.</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Raw Reason Text --}}
        @if($signal->reason)
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-file-text me-2" style="color:var(--text-secondary);"></i>
                Analysis Notes
            </div>
            <div class="card-body">
                <p class="mb-0" style="font-size:0.875rem;color:var(--text-secondary);line-height:1.7;">
                    {{ $signal->reason }}
                </p>
            </div>
        </div>
        @endif

        {{-- Affected Users --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>
                    <i class="bi bi-people me-2" style="color:var(--accent-green);"></i>
                    Users Who Received This Signal
                </span>
                <span class="badge bg-secondary bg-opacity-25 text-secondary">
                    {{ count($affectedUsers ?? []) }} users
                </span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Package</th>
                            <th>Delivered</th>
                            <th>Opened</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($affectedUsers ?? [] as $u)
                        <tr>
                            <td>
                                <a href="{{ route('admin.users.show', $u->id) }}"
                                   class="text-decoration-none fw-600" style="color:var(--accent-blue);">
                                    {{ $u->name }}
                                </a>
                            </td>
                            <td class="text-muted" style="font-size:0.8rem;">{{ $u->email }}</td>
                            <td>
                                @if($u->activeSubscription)
                                    <span class="badge-custom badge-active" style="font-size:0.65rem;">
                                        {{ $u->activeSubscription->package->name ?? 'N/A' }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size:0.75rem;">Free</span>
                                @endif
                            </td>
                            <td>
                                @if($u->pivot->delivered_at ?? false)
                                    <span style="color:var(--accent-green);font-size:0.8rem;">
                                        <i class="bi bi-check2"></i>
                                        {{ \Carbon\Carbon::parse($u->pivot->delivered_at)->format('H:i') }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size:0.8rem;">—</span>
                                @endif
                            </td>
                            <td>
                                @if($u->pivot->opened_at ?? false)
                                    <span style="color:var(--accent-gold);font-size:0.8rem;">
                                        <i class="bi bi-eye"></i>
                                        {{ \Carbon\Carbon::parse($u->pivot->opened_at)->format('H:i') }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size:0.8rem;">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-people fs-4 d-block mb-1"></i>No users received this signal
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
