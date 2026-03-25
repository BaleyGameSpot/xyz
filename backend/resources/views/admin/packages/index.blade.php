@extends('admin.layouts.app')

@section('title', 'Packages')
@section('page-title', 'Subscription Packages')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">Home</a></li>
    <li class="breadcrumb-item active" style="color:var(--text-secondary);">Packages</li>
@endsection

@section('content')

{{-- ─── Header Bar ──────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="text-muted" style="font-size:0.875rem;">
        {{ $packages->count() }} package(s) configured
    </div>
    <a href="{{ route('admin.packages.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Create New Package
    </a>
</div>

{{-- ─── Package Cards ───────────────────────────────────────── --}}
<div class="row g-3">
    @forelse($packages as $package)
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100" style="
            border-top: 3px solid {{ $package->is_active ? 'var(--accent-green)' : 'var(--border)' }};
            {{ !$package->is_active ? 'opacity:0.65;' : '' }}
        ">
            <div class="card-body">

                {{-- Header --}}
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h5 class="fw-700 mb-1">{{ $package->name }}</h5>
                        <div class="d-flex align-items-center gap-2">
                            @if($package->is_active)
                                <span class="badge-custom badge-active" style="font-size:0.65rem;">Active</span>
                            @else
                                <span class="badge-custom badge-blocked" style="font-size:0.65rem;">Inactive</span>
                            @endif
                            @if($package->is_popular)
                                <span class="badge-custom" style="background:rgba(255,215,0,0.15);color:var(--accent-gold);border:1px solid rgba(255,215,0,0.3);font-size:0.65rem;">
                                    <i class="bi bi-star-fill me-1"></i>Popular
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div style="font-size:1.5rem;font-weight:700;color:var(--accent-green);">
                            ${{ number_format($package->price, 2) }}
                        </div>
                        @if($package->promo_price)
                        <div style="font-size:0.75rem;color:var(--text-secondary);text-decoration:line-through;">
                            ${{ number_format($package->price, 2) }}
                        </div>
                        @endif
                        <div class="text-muted" style="font-size:0.7rem;">/ month</div>
                    </div>
                </div>

                {{-- Description --}}
                @if($package->description)
                <p class="text-muted mb-3" style="font-size:0.825rem;line-height:1.5;">
                    {{ Str::limit($package->description, 100) }}
                </p>
                @endif

                {{-- Features --}}
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-diagram-2" style="color:var(--accent-blue);font-size:0.875rem;"></i>
                        <span style="font-size:0.82rem;">
                            <strong>{{ $package->pairs_limit ?? '∞' }}</strong>
                            <span class="text-muted ms-1">trading pairs</span>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-graph-up-arrow" style="color:var(--accent-green);font-size:0.875rem;"></i>
                        <span style="font-size:0.82rem;">
                            <strong>{{ $package->daily_signals_limit ?? '∞' }}</strong>
                            <span class="text-muted ms-1">signals/day</span>
                        </span>
                    </div>
                    @if($package->timeframes && count($package->timeframes))
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-clock" style="color:var(--accent-gold);font-size:0.875rem;margin-top:2px;"></i>
                        <div style="font-size:0.82rem;">
                            <span class="text-muted">Timeframes: </span>
                            @foreach($package->timeframes as $tf)
                                <span class="badge bg-secondary bg-opacity-25 text-secondary me-1" style="font-size:0.65rem;">{{ $tf }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Subscriber count --}}
                <div class="p-2 rounded mb-3 text-center" style="background:var(--bg-elevated);">
                    <div class="fw-700" style="font-size:1.2rem;color:var(--accent-blue);">
                        {{ $package->activeSubscriptions()->count() }}
                    </div>
                    <div class="text-muted" style="font-size:0.72rem;">Active Subscribers</div>
                </div>

                {{-- Actions --}}
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.packages.edit', $package->id) }}"
                       class="btn btn-outline-secondary flex-fill">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>

                    <form action="{{ route('admin.packages.toggle', $package->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="btn {{ $package->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                            title="{{ $package->is_active ? 'Deactivate' : 'Activate' }}">
                            <i class="bi bi-{{ $package->is_active ? 'pause-circle' : 'play-circle' }}"></i>
                        </button>
                    </form>

                    <form action="{{ route('admin.packages.destroy', $package->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="btn btn-outline-danger"
                            title="Delete Package"
                            onclick="return confirm('Delete package \'{{ $package->name }}\'? This cannot be undone.')"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>

            </div>{{-- /card-body --}}
        </div>{{-- /card --}}
    </div>
    @empty
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-box-seam fs-1 d-block mb-3 text-muted"></i>
                <h5 class="text-muted">No packages configured yet</h5>
                <p class="text-muted mb-3">Create your first subscription package to start accepting payments.</p>
                <a href="{{ route('admin.packages.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Create First Package
                </a>
            </div>
        </div>
    </div>
    @endforelse
</div>

@endsection
