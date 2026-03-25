@extends('admin.layouts.app')

@section('title', 'Edit Package — ' . $package->name)
@section('page-title', 'Edit Package')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.packages.index') }}" class="text-decoration-none text-muted">Packages</a></li>
    <li class="breadcrumb-item active" style="color:var(--text-secondary);">Edit: {{ $package->name }}</li>
@endsection

@section('content')

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>
                    <i class="bi bi-pencil me-2" style="color:var(--accent-gold);"></i>
                    Editing: <strong>{{ $package->name }}</strong>
                </span>
                <div class="d-flex align-items-center gap-2">
                    @if($package->is_active)
                        <span class="badge-custom badge-active" style="font-size:0.65rem;">Active</span>
                    @else
                        <span class="badge-custom badge-blocked" style="font-size:0.65rem;">Inactive</span>
                    @endif
                    <span class="text-muted" style="font-size:0.75rem;">
                        {{ $package->activeSubscriptions()->count() }} subscribers
                    </span>
                </div>
            </div>
            <div class="card-body">

                @if($errors->any())
                <div class="alert alert-danger mb-4">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('admin.packages.update', $package->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- ── Basic Info ───────────────────────────────── --}}
                    <div class="mb-4 pb-4" style="border-bottom:1px solid var(--border);">
                        <h6 class="fw-600 mb-3" style="color:var(--accent-blue);">
                            <i class="bi bi-info-circle me-2"></i>Basic Information
                        </h6>

                        <div class="mb-3">
                            <label for="name" class="form-label">Package Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $package->name) }}"
                                placeholder="e.g. Basic, Pro, Premium"
                                required
                                maxlength="100"
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea
                                id="description"
                                name="description"
                                class="form-control @error('description') is-invalid @enderror"
                                rows="3"
                                placeholder="Describe what subscribers get with this package…"
                            >{{ old('description', $package->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- ── Pricing ──────────────────────────────────── --}}
                    <div class="mb-4 pb-4" style="border-bottom:1px solid var(--border);">
                        <h6 class="fw-600 mb-3" style="color:var(--accent-green);">
                            <i class="bi bi-currency-dollar me-2"></i>Pricing
                        </h6>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="price" class="form-label">Monthly Price (USD) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input
                                        type="number"
                                        id="price"
                                        name="price"
                                        class="form-control @error('price') is-invalid @enderror"
                                        value="{{ old('price', $package->price) }}"
                                        step="0.01"
                                        min="0"
                                        required
                                    >
                                    @error('price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="promo_price" class="form-label">
                                    Promo Price (USD)
                                    <span class="text-muted ms-1" style="font-size:0.75rem;">(optional)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input
                                        type="number"
                                        id="promo_price"
                                        name="promo_price"
                                        class="form-control @error('promo_price') is-invalid @enderror"
                                        value="{{ old('promo_price', $package->promo_price) }}"
                                        step="0.01"
                                        min="0"
                                        placeholder="Leave empty to remove promo"
                                    >
                                    @error('promo_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Limits ───────────────────────────────────── --}}
                    <div class="mb-4 pb-4" style="border-bottom:1px solid var(--border);">
                        <h6 class="fw-600 mb-3" style="color:var(--accent-gold);">
                            <i class="bi bi-sliders me-2"></i>Limits & Features
                        </h6>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="pairs_limit" class="form-label">
                                    Trading Pairs Limit
                                    <span class="text-muted ms-1" style="font-size:0.75rem;">(0 = unlimited)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-diagram-2"></i></span>
                                    <input
                                        type="number"
                                        id="pairs_limit"
                                        name="pairs_limit"
                                        class="form-control @error('pairs_limit') is-invalid @enderror"
                                        value="{{ old('pairs_limit', $package->pairs_limit) }}"
                                        min="0"
                                        max="999"
                                    >
                                    @error('pairs_limit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="daily_signals_limit" class="form-label">
                                    Daily Signals Limit
                                    <span class="text-muted ms-1" style="font-size:0.75rem;">(0 = unlimited)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-graph-up-arrow"></i></span>
                                    <input
                                        type="number"
                                        id="daily_signals_limit"
                                        name="daily_signals_limit"
                                        class="form-control @error('daily_signals_limit') is-invalid @enderror"
                                        value="{{ old('daily_signals_limit', $package->daily_signals_limit) }}"
                                        min="0"
                                        max="999"
                                    >
                                    @error('daily_signals_limit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Timeframes --}}
                        <div>
                            <label class="form-label">Allowed Timeframes <span class="text-danger">*</span></label>
                            <div class="p-3 rounded" style="background:var(--bg-elevated);">
                                <div class="row g-2">
                                    @php
                                        $timeframes = ['M1','M5','M15','M30','H1','H4','D1','W1'];
                                        $currentTf  = old('timeframes', $package->timeframes ?? []);
                                    @endphp
                                    @foreach($timeframes as $tf)
                                    <div class="col-6 col-md-3">
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="timeframes[]"
                                                value="{{ $tf }}"
                                                id="tf_{{ $tf }}"
                                                {{ in_array($tf, $currentTf) ? 'checked' : '' }}
                                                style="border-color:var(--border);"
                                            >
                                            <label class="form-check-label" for="tf_{{ $tf }}"
                                                   style="font-size:0.875rem;cursor:pointer;">
                                                {{ $tf }}
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @error('timeframes')
                                    <div class="text-danger mt-2" style="font-size:0.8rem;">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    onclick="document.querySelectorAll('[name=\'timeframes[]\']').forEach(c=>c.checked=true)">
                                    Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    onclick="document.querySelectorAll('[name=\'timeframes[]\']').forEach(c=>c.checked=false)">
                                    Clear
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- ── Options ──────────────────────────────────── --}}
                    <div class="mb-4">
                        <h6 class="fw-600 mb-3" style="color:var(--text-secondary);">
                            <i class="bi bi-toggles me-2"></i>Options
                        </h6>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        id="is_active"
                                        name="is_active"
                                        value="1"
                                        {{ old('is_active', $package->is_active) ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label" for="is_active" style="font-size:0.875rem;">
                                        <strong>Active</strong>
                                        <span class="text-muted ms-1">— visible to users</span>
                                    </label>
                                </div>
                                @if($package->activeSubscriptions()->count() > 0)
                                <div class="text-muted mt-1" style="font-size:0.75rem;">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Deactivating will not affect existing subscribers.
                                </div>
                                @endif
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        id="is_popular"
                                        name="is_popular"
                                        value="1"
                                        {{ old('is_popular', $package->is_popular) ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label" for="is_popular" style="font-size:0.875rem;">
                                        <strong>Mark as Popular</strong>
                                        <span class="text-muted ms-1">— highlighted in app</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Buttons ──────────────────────────────────── --}}
                    <div class="d-flex gap-2 justify-content-between">
                        <form action="{{ route('admin.packages.destroy', $package->id) }}" method="POST"
                              class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger"
                                onclick="return confirm('Delete package \'{{ $package->name }}\'? This cannot be undone.')">
                                <i class="bi bi-trash me-1"></i>Delete Package
                            </button>
                        </form>

                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Save Changes
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

@endsection
