@extends('admin.layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">Home</a></li>
    <li class="breadcrumb-item active" style="color:var(--text-secondary);">Users</li>
@endsection

@section('content')

{{-- ─── Filters & Search ────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form action="{{ route('admin.users.index') }}" method="GET" id="filterForm">
            <div class="row g-2 align-items-end">

                {{-- Search --}}
                <div class="col-12 col-md-4">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Name, email, phone…"
                            value="{{ request('search') }}"
                        >
                    </div>
                </div>

                {{-- Status filter --}}
                <div class="col-6 col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="blocked"  {{ request('status') === 'blocked'  ? 'selected' : '' }}>Blocked</option>
                    </select>
                </div>

                {{-- Subscription filter --}}
                <div class="col-6 col-md-2">
                    <label class="form-label">Subscription</label>
                    <select name="subscription" class="form-select" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="active"   {{ request('subscription') === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="expired"  {{ request('subscription') === 'expired'  ? 'selected' : '' }}>Expired</option>
                        <option value="none"     {{ request('subscription') === 'none'     ? 'selected' : '' }}>None</option>
                    </select>
                </div>

                {{-- Sort --}}
                <div class="col-6 col-md-2">
                    <label class="form-label">Sort By</label>
                    <select name="sort" class="form-select" onchange="this.form.submit()">
                        <option value="newest"  {{ request('sort') === 'newest'  ? 'selected' : '' }}>Newest</option>
                        <option value="oldest"  {{ request('sort') === 'oldest'  ? 'selected' : '' }}>Oldest</option>
                        <option value="name"    {{ request('sort') === 'name'    ? 'selected' : '' }}>Name A–Z</option>
                    </select>
                </div>

                {{-- Actions --}}
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- ─── Results Summary ─────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="text-muted" style="font-size:0.85rem;">
        Showing <strong class="text-primary">{{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}</strong>
        of <strong class="text-primary">{{ $users->total() }}</strong> users
    </div>
    <div class="d-flex gap-2">
        {{-- Bulk action form --}}
        <form action="{{ route('admin.users.bulk') }}" method="POST" id="bulkForm">
            @csrf
            <input type="hidden" name="action" id="bulkAction">
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" id="bulkSelect" style="width:auto;">
                    <option value="">Bulk Action</option>
                    <option value="block">Block Selected</option>
                    <option value="unblock">Unblock Selected</option>
                </select>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyBulk()">
                    Apply
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ─── Table ───────────────────────────────────────────────── --}}
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0" id="usersTable">
            <thead>
                <tr>
                    <th style="width:40px;">
                        <input type="checkbox" class="form-check-input" id="selectAll" onclick="toggleAll(this)">
                    </th>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Subscription</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th style="width:130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <input
                            type="checkbox"
                            class="form-check-input row-check"
                            name="user_ids[]"
                            value="{{ $user->id }}"
                            form="bulkForm"
                        >
                    </td>
                    <td class="text-muted" style="font-size:0.8rem;">#{{ $user->id }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="admin-avatar" style="width:32px;height:32px;font-size:0.75rem;flex-shrink:0;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-600" style="font-size:0.875rem;">{{ $user->name }}</div>
                                <div class="text-muted" style="font-size:0.72rem;">{{ $user->phone ?? '—' }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:0.875rem;">{{ $user->email }}</td>
                    <td>
                        @if($user->activeSubscription)
                            <span class="badge-custom badge-active">
                                {{ $user->activeSubscription->package->name ?? 'Unknown' }}
                            </span>
                        @else
                            <span class="text-muted" style="font-size:0.8rem;">Free</span>
                        @endif
                    </td>
                    <td style="font-size:0.8rem;">
                        @if($user->activeSubscription)
                            @php $expiry = $user->activeSubscription->expires_at; @endphp
                            @if($expiry && $expiry->isPast())
                                <span style="color:var(--accent-red);">
                                    <i class="bi bi-exclamation-circle me-1"></i>Expired
                                </span>
                            @elseif($expiry && $expiry->diffInDays() <= 3)
                                <span style="color:var(--accent-gold);">
                                    <i class="bi bi-clock me-1"></i>{{ $expiry->format('d M Y') }}
                                </span>
                            @else
                                <span class="text-muted">{{ $expiry ? $expiry->format('d M Y') : '—' }}</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($user->is_blocked)
                            <span class="badge-custom badge-blocked">
                                <i class="bi bi-slash-circle"></i> Blocked
                            </span>
                        @else
                            <span class="badge-custom badge-active">
                                <i class="bi bi-check-circle"></i> Active
                            </span>
                        @endif
                    </td>
                    <td class="text-muted" style="font-size:0.8rem;">
                        {{ $user->created_at->format('d M Y') }}
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            {{-- View --}}
                            <a href="{{ route('admin.users.show', $user->id) }}"
                               class="btn btn-sm btn-outline-secondary"
                               title="View User">
                                <i class="bi bi-eye"></i>
                            </a>

                            {{-- Block/Unblock --}}
                            <form action="{{ route('admin.users.toggle-block', $user->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button
                                    type="submit"
                                    class="btn btn-sm {{ $user->is_blocked ? 'btn-outline-success' : 'btn-outline-danger' }}"
                                    title="{{ $user->is_blocked ? 'Unblock' : 'Block' }}"
                                    onclick="return confirm('{{ $user->is_blocked ? 'Unblock' : 'Block' }} this user?')"
                                >
                                    <i class="bi bi-{{ $user->is_blocked ? 'unlock' : 'lock' }}"></i>
                                </button>
                            </form>

                            {{-- Assign Subscription --}}
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                title="Assign Subscription"
                                data-bs-toggle="modal"
                                data-bs-target="#assignSubModal"
                                data-user-id="{{ $user->id }}"
                                data-user-name="{{ $user->name }}"
                            >
                                <i class="bi bi-patch-plus"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <i class="bi bi-people fs-2 d-block mb-2 text-muted"></i>
                        <div class="text-muted">No users found matching your filters.</div>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
                            Clear Filters
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($users->hasPages())
    <div class="card-footer d-flex align-items-center justify-content-between py-3">
        <div class="text-muted" style="font-size:0.8rem;">
            Page {{ $users->currentPage() }} of {{ $users->lastPage() }}
        </div>
        {{ $users->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

{{-- ─── Assign Subscription Modal ──────────────────────────── --}}
<div class="modal fade" id="assignSubModal" tabindex="-1" aria-labelledby="assignSubModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--bg-card);border-color:var(--border);">
            <div class="modal-header" style="border-color:var(--border);">
                <h5 class="modal-title" id="assignSubModalLabel">
                    <i class="bi bi-patch-plus me-2" style="color:var(--accent-blue);"></i>
                    Assign Subscription
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.users.assign-subscription') }}" method="POST">
                @csrf
                <input type="hidden" name="user_id" id="modalUserId">
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size:0.875rem;">
                        Assigning subscription to: <strong id="modalUserName" class="text-primary"></strong>
                    </p>

                    <div class="mb-3">
                        <label class="form-label">Package</label>
                        <select name="package_id" class="form-select" required>
                            <option value="">Select package…</option>
                            @foreach($packages ?? [] as $pkg)
                                <option value="{{ $pkg->id }}">{{ $pkg->name }} — ${{ $pkg->price }}/mo</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Duration (days)</label>
                        <input type="number" name="days" class="form-control" value="30" min="1" max="365" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Note (optional)</label>
                        <textarea name="note" class="form-control" rows="2"
                            placeholder="Manual assignment reason…"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-color:var(--border);">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-patch-check me-1"></i>Assign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Select all checkboxes
    function toggleAll(master) {
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = master.checked);
    }

    // Apply bulk action
    function applyBulk() {
        const action = document.getElementById('bulkSelect').value;
        if (!action) { alert('Please select a bulk action.'); return; }
        const checked = document.querySelectorAll('.row-check:checked');
        if (!checked.length) { alert('Please select at least one user.'); return; }
        if (!confirm(`Apply "${action}" to ${checked.length} user(s)?`)) return;
        document.getElementById('bulkAction').value = action;
        document.getElementById('bulkForm').submit();
    }

    // Assign subscription modal — populate user id/name
    document.getElementById('assignSubModal').addEventListener('show.bs.modal', function(e) {
        const btn = e.relatedTarget;
        document.getElementById('modalUserId').value    = btn.dataset.userId;
        document.getElementById('modalUserName').textContent = btn.dataset.userName;
    });
</script>
@endpush
