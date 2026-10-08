<div class="staff-card {{ $member->role }}">
    <div class="staff-avatar {{ $member->role }}">
        {{ strtoupper(substr($member->display_name, 0, 2)) }}
    </div>
    <div class="staff-meta">
        <div class="staff-name">{{ $member->display_name }}</div>
        <div class="staff-sub">
            @if($member->display_phone)
                <i class="las la-phone me-1"></i>{{ $member->display_phone }}
                &nbsp;·&nbsp;
            @endif
            <span class="badge" style="background:{{ $member->role === 'supervisor' ? '#eff6ff' : '#f5f3ff' }};color:{{ $member->role === 'supervisor' ? '#2563eb' : '#7c3aed' }};font-size:0.7rem;padding:2px 7px;">
                {{ ucfirst($member->staff_type) }}
            </span>
            &nbsp;·&nbsp;
            <small>Added by {{ $member->addedBy?->name ?? 'N/A' }}</small>
            @if($member->is_pending_approval)
                &nbsp;·&nbsp;
                <span class="badge bg-warning text-dark" style="font-size:0.68rem;">Pending approval</span>
            @endif
        </div>
    </div>
    <div class="staff-actions">
        @if(auth()->user()->role === 'super_admin' || $member->added_by === auth()->id())
        <button class="btn btn-sm btn-outline-danger deleteStaffBtn"
            data-id="{{ $member->id }}" title="Remove">
            <i class="las la-trash"></i>
        </button>
        @endif
    </div>
</div>
