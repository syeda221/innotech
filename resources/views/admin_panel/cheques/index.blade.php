@extends('admin_panel.layout.app')

@section('content')
<style>
    .cheque-mgmt-page {
        padding: 4px 0 24px;
    }
    .cheque-page-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        margin-bottom: 20px;
    }
    .cheque-page-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .cheque-title-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: #eff6ff;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .cheque-page-title h4 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
    }
    .cheque-page-title p {
        margin: 2px 0 0;
        font-size: 0.85rem;
        color: #64748b;
    }
    .cheque-header-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
    }
    .cheque-header-actions .btn {
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 8px 15px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    /* Stat Cards */
    .cheque-stats-row {
        margin-bottom: 18px;
    }
    .cheque-stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 18px;
        height: 100%;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .cheque-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.07);
    }
    .cheque-stat-card.accent-warning { border-left: 4px solid #f59e0b; }
    .cheque-stat-card.accent-info    { border-left: 4px solid #0ea5e9; }
    .cheque-stat-card.accent-danger  { border-left: 4px solid #ef4444; }
    .cheque-stat-card.accent-success { border-left: 4px solid #10b981; }
    .cheque-stat-card.due-highlight  { background: #fffbeb; }

    .cheque-stat-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        color: #64748b;
        display: block;
        margin-bottom: 4px;
    }
    .cheque-stat-value {
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1.15;
        margin: 0;
    }
    .cheque-stat-value small {
        font-size: 0.85rem;
        font-weight: 600;
        color: #64748b;
    }
    .cheque-stat-amount {
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        margin-top: 4px;
    }
    .cheque-stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }
    .cheque-stat-icon.icon-warning { background: #fef3c7; color: #d97706; }
    .cheque-stat-icon.icon-info    { background: #e0f2fe; color: #0284c7; }
    .cheque-stat-icon.icon-danger  { background: #fee2e2; color: #dc2626; }
    .cheque-stat-icon.icon-success { background: #dcfce7; color: #16a34a; }

    /* Filter Bar */
    .cheque-filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 14px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        margin-bottom: 16px;
    }
    .cheque-filter-bar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }
    .cheque-filter-pills {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .cheque-filter-pills .nav-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #475569;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .cheque-filter-pills .nav-link:hover {
        background: #eff6ff;
        color: #1d4ed8;
        border-color: #bfdbfe;
    }
    .cheque-filter-pills .nav-link.active {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    }
    .cheque-pill-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1;
    }
    .cheque-filter-pills .nav-link.active .cheque-pill-count {
        background: rgba(255, 255, 255, 0.22) !important;
        color: #ffffff !important;
    }
    .cheque-search-form {
        min-width: 260px;
        flex: 0 1 320px;
    }
    .cheque-search-form .form-control {
        height: 36px;
        border-radius: 8px 0 0 8px;
        border-color: #cbd5e1;
        font-size: 0.84rem;
    }
    .cheque-search-form .btn {
        height: 36px;
        border-radius: 0 8px 8px 0;
    }

    /* Table Card */
    .cheque-table-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }
    .cheque-table-card .table {
        margin-bottom: 0;
    }
    .cheque-table-card .table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-top: none;
        border-bottom: 1.5px solid #e2e8f0;
        padding: 12px 14px;
        white-space: nowrap;
    }
    .cheque-table-card .table tbody td {
        padding: 12px 14px;
        vertical-align: middle;
        border-color: #f1f5f9;
        font-size: 0.86rem;
        color: #1e293b;
    }
    .cheque-table-card .table tbody tr:hover {
        background: #f8fafc;
    }
    .cheque-no-link {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 700;
        color: #2563eb;
        font-size: 0.84rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        word-break: break-all;
    }
    .cheque-bank-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 6px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #334155;
        font-size: 0.78rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .cheque-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .cheque-actions-group {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
        white-space: nowrap;
    }
    .cheque-actions-group .btn {
        border-radius: 7px;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 5px 10px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .cheque-realized-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        border-radius: 7px;
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
        font-size: 0.78rem;
        font-weight: 700;
    }
</style>

<div class="main-content cheque-mgmt-page">
    <div class="main-content-inner">
        <div class="container-fluid">

            <!-- Page Title & Actions -->
            <div class="cheque-page-header">
                <div class="cheque-page-title">
                    <div class="cheque-title-icon">
                        <i class="fas fa-money-check-alt"></i>
                    </div>
                    <div>
                        <h4>Cheque Management</h4>
                        <p>Track received cheques, due date alerts &amp; automated bank clearance</p>
                    </div>
                </div>
                <div class="cheque-header-actions">
                    <a href="{{ route('customer.payments') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Customer Payments
                    </a>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#receiveChequeModal">
                        <i class="fas fa-plus"></i> Receive New Cheque
                    </button>
                </div>
            </div>

            <!-- Flash Messages -->
            @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-check-circle mr-1"></i> Success!</strong> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            @endif

            @if (isset($errors) && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0 pl-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            @endif

            <!-- Daily Alerts & Stat Cards -->
            <div class="row cheque-stats-row">
                <!-- In Hand -->
                <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
                    <div class="cheque-stat-card accent-warning">
                        <div>
                            <span class="cheque-stat-label">In-Hand (Tijori/Drawer)</span>
                            <h4 class="cheque-stat-value text-warning">{{ $stats['in_hand_count'] }} <small>Cheques</small></h4>
                            <div class="cheque-stat-amount">Rs. {{ number_format($stats['in_hand_amount'], 2) }}</div>
                        </div>
                        <div class="cheque-stat-icon icon-warning">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>

                <!-- Due Today -->
                <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
                    <div class="cheque-stat-card accent-info {{ $stats['due_today_count'] > 0 ? 'due-highlight' : '' }}">
                        <div>
                            <span class="cheque-stat-label">Due Today (Bank Mein Jama)</span>
                            <h4 class="cheque-stat-value text-info">{{ $stats['due_today_count'] }} <small>Cheques</small></h4>
                            <div class="cheque-stat-amount">Rs. {{ number_format($stats['due_today_amount'], 2) }}</div>
                        </div>
                        <div class="cheque-stat-icon icon-info">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                </div>

                <!-- Overdue -->
                <div class="col-xl-3 col-sm-6 mb-3 mb-sm-0">
                    <div class="cheque-stat-card accent-danger">
                        <div>
                            <span class="cheque-stat-label">Overdue Cheques</span>
                            <h4 class="cheque-stat-value text-danger">{{ $stats['overdue_count'] }} <small>Cheques</small></h4>
                            <div class="cheque-stat-amount">Rs. {{ number_format($stats['overdue_amount'], 2) }}</div>
                        </div>
                        <div class="cheque-stat-icon icon-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>

                <!-- Cleared in Bank -->
                <div class="col-xl-3 col-sm-6">
                    <div class="cheque-stat-card accent-success">
                        <div>
                            <span class="cheque-stat-label">Cleared (Bank Received)</span>
                            <h4 class="cheque-stat-value text-success">{{ $stats['cleared_count'] }} <small>Cheques</small></h4>
                            <div class="cheque-stat-amount">Rs. {{ number_format($stats['cleared_amount'], 2) }}</div>
                        </div>
                        <div class="cheque-stat-icon icon-success">
                            <i class="fas fa-check-double"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters & Tabs -->
            <div class="cheque-filter-card">
                <div class="cheque-filter-bar">
                    <ul class="cheque-filter-pills">
                        <li>
                            <a class="nav-link {{ $status === 'all' && $filterDue === 'all' ? 'active' : '' }}" href="{{ route('cheques.index') }}">
                                All Cheques
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ $status === 'in_hand' ? 'active' : '' }}" href="{{ route('cheques.index', ['status' => 'in_hand']) }}">
                                In-Hand <span class="cheque-pill-count badge-secondary">{{ $stats['in_hand_count'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ $filterDue === 'today' ? 'active' : '' }}" href="{{ route('cheques.index', ['due' => 'today']) }}">
                                Due Today <span class="cheque-pill-count badge-warning text-dark">{{ $stats['due_today_count'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ $filterDue === 'upcoming' ? 'active' : '' }}" href="{{ route('cheques.index', ['due' => 'upcoming']) }}">
                                Upcoming (3 Days) <span class="cheque-pill-count badge-info">{{ $stats['upcoming_count'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ $filterDue === 'pdc' ? 'active' : '' }}" href="{{ route('cheques.index', ['due' => 'pdc']) }}">
                                PDC (Post-Dated) <span class="cheque-pill-count badge-primary">{{ $stats['pdc_count'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ $filterDue === 'overdue' ? 'active' : '' }}" href="{{ route('cheques.index', ['due' => 'overdue']) }}">
                                Overdue <span class="cheque-pill-count badge-danger">{{ $stats['overdue_count'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ $status === 'cleared' ? 'active' : '' }}" href="{{ route('cheques.index', ['status' => 'cleared']) }}">
                                Cleared <span class="cheque-pill-count badge-success">{{ $stats['cleared_count'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ $status === 'bounced' ? 'active' : '' }}" href="{{ route('cheques.index', ['status' => 'bounced']) }}">
                                Bounced <span class="cheque-pill-count badge-danger">{{ $stats['bounced_count'] }}</span>
                            </a>
                        </li>
                    </ul>

                    <form action="{{ route('cheques.index') }}" method="GET" class="cheque-search-form">
                        @if($status !== 'all') <input type="hidden" name="status" value="{{ $status }}"> @endif
                        @if($filterDue !== 'all') <input type="hidden" name="due" value="{{ $filterDue }}"> @endif
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search Cheque No, Bank, Party..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary" type="submit" title="Search"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cheques Table -->
            <div class="cheque-table-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cheque No</th>
                                <th>Customer / Party</th>
                                <th>Customer Bank</th>
                                <th>Due / Maturity Date</th>
                                <th class="text-right">Amount</th>
                                <th>Status</th>
                                <th>Deposit / Cleared To</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cheques as $key => $c)
                            <tr>
                                <td class="text-muted font-weight-bold">{{ $cheques->firstItem() + $key }}</td>
                                <td>
                                    <span class="cheque-no-link">
                                        <i class="fas fa-money-check"></i> #{{ $c->cheque_no }}
                                    </span>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $c->customer->customer_name ?? 'N/A' }}</div>
                                    @if($c->note)
                                        <div class="small text-muted" title="{{ $c->note }}">{{ Str::limit($c->note, 38) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="cheque-bank-pill">
                                        <i class="fas fa-university text-secondary"></i> {{ $c->bank_name }}
                                    </span>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $c->cheque_date ? $c->cheque_date->format('d M, Y') : '-' }}</div>
                                    @php $badge = $c->due_badge; @endphp
                                    <span class="badge {{ $badge['class'] }} mt-1">{{ $badge['text'] }}</span>
                                </td>
                                <td class="text-right">
                                    <span class="font-weight-bold text-dark" style="font-size: 0.95rem;">Rs. {{ number_format($c->amount, 2) }}</span>
                                </td>
                                <td>
                                    @if($c->status === 'in_hand')
                                        <span class="cheque-status-badge badge-warning text-dark"><i class="fas fa-hand-holding-usd"></i> In-Hand</span>
                                    @elseif($c->status === 'cleared')
                                        @php
                                            $isCashAcc = $c->depositAccount && stripos($c->depositAccount->title, 'cash') !== false;
                                        @endphp
                                        <span class="cheque-status-badge badge-success">
                                            <i class="fas {{ $isCashAcc ? 'fa-money-bill-wave' : 'fa-check-circle' }}"></i>
                                            {{ $isCashAcc ? 'Encashed (Cash)' : 'Cleared (Bank)' }}
                                        </span>
                                        <div class="small text-muted mt-1">{{ $c->cleared_at ? $c->cleared_at->format('d M, Y') : '' }}</div>
                                    @elseif($c->status === 'bounced')
                                        <span class="cheque-status-badge badge-danger"><i class="fas fa-times-circle"></i> Bounced</span>
                                        @if($c->bounce_reason)
                                            <div class="small text-danger mt-1 font-weight-bold">{{ $c->bounce_reason }}</div>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($c->depositAccount)
                                        <span class="cheque-bank-pill text-success" style="background:#f0fdf4; border-color:#bbf7d0;">
                                            @if(stripos($c->depositAccount->title, 'cash') !== false)
                                                <i class="fas fa-money-bill-wave text-success"></i> {{ $c->depositAccount->title }}
                                            @else
                                                <i class="fas fa-university text-primary"></i> {{ $c->depositAccount->title }}
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-muted small">In Hand</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="cheque-actions-group">
                                        @if($c->status !== 'cleared')
                                            <button type="button" class="btn btn-sm btn-success" title="Clear in Bank or Encash to Cash"
                                                    onclick="openClearModal('{{ $c->id }}', '{{ $c->cheque_no }}', '{{ $c->amount }}', '{{ $c->bank_name }}')">
                                                <i class="fas fa-check-double"></i> Clear / Cash
                                            </button>

                                            @if($c->status !== 'bounced')
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Mark Bounced"
                                                        onclick="openBounceModal('{{ $c->id }}', '{{ $c->cheque_no }}', '{{ $c->amount }}')">
                                                    <i class="fas fa-times"></i> Bounce
                                                </button>
                                            @endif
                                        @else
                                            <span class="cheque-realized-pill">
                                                <i class="fas fa-check-double"></i> Realized
                                            </span>
                                        @endif

                                        <form action="{{ route('cheques.destroy', $c->id) }}" method="POST" style="display:inline-block; margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Are you sure to delete this cheque record?')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-3x mb-2 d-block text-secondary"></i>
                                    No cheques found for this filter.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($cheques->hasPages())
                <div class="d-flex justify-content-end p-3 border-top bg-light">
                    {{ $cheques->links() }}
                </div>
                @endif
            </div>

        </div>
    </div>
</div>

<!-- Modal: Receive Cheque (Simplified 4 Fields: No, Bank Name, Date, Amount) -->
<div class="modal fade" id="receiveChequeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('cheques.store') }}" method="POST" class="w-100">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-money-check mr-2 text-primary"></i> Receive New Cheque</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Customer / Party <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-control" required>
                            <option value="">Select Customer</option>
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}">{{ $cust->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Cheque Number <span class="text-danger">*</span></label>
                            <input type="text" name="cheque_no" class="form-control" placeholder="e.g. 1029384" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Customer Bank Name <span class="text-danger">*</span></label>
                            <input type="text" name="bank_name" class="form-control" placeholder="e.g. Meezan Bank, HBL, MCB" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Cheque / Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="cheque_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            <small class="text-muted">Jis din bank mein lagega</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Note / Reference</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary font-weight-bold">Save Cheque</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Mark as Cleared in Bank or Encashed to Cash -->
<div class="modal fade" id="clearChequeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="clearChequeForm" method="POST" class="w-100">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-check-double mr-2"></i> Clear / Encash Cheque</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Cheque:</strong> <span id="clear_cheque_title">-</span>
                            </div>
                            <div class="text-right">
                                <strong>Amount:</strong> <span class="font-weight-bold text-success" style="font-size:1rem;">Rs. <span id="clear_cheque_amount">0.00</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Deposit / Encash Into <span class="text-danger">*</span></label>
                        <select name="deposit_account_id" class="form-control" required>
                            <option value="">-- Choose Account (Bank or Cash) --</option>
                            @if(isset($cashAccounts) && $cashAccounts->isNotEmpty())
                                <optgroup label="💵 Cash Accounts (Encash to Cash in Hand)">
                                    @foreach($cashAccounts as $acc)
                                        <option value="{{ $acc->id }}">💵 {{ $acc->title }} (Current Bal: Rs. {{ number_format($acc->current_balance ?? 0, 2) }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                <optgroup label="🏦 Bank Accounts (Deposit in Bank)">
                                    @foreach($bankAccounts as $acc)
                                        <option value="{{ $acc->id }}">🏦 {{ $acc->title }} (Current Bal: Rs. {{ number_format($acc->current_balance ?? 0, 2) }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle mr-1"></i> Cheque bank account mein jama karein ya counter se <strong>Cash in Hand</strong> nikalwayein (Selected account ka balance barh jayega).
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Clearance / Encashed Date <span class="text-danger">*</span></label>
                        <input type="date" name="cleared_at" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Clearing Remarks / Reference</label>
                        <input type="text" name="clearing_note" class="form-control" placeholder="Optional deposit slip no, counter receipt or transaction ID">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success font-weight-bold">
                        <i class="fas fa-check-circle mr-1"></i> Confirm &amp; Update Balance
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Mark as Bounced -->
<div class="modal fade" id="bounceChequeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="bounceChequeForm" method="POST" class="w-100">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-times-circle mr-2"></i> Mark Cheque as Bounced</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger py-2">
                        <strong>Cheque:</strong> <span id="bounce_cheque_title">-</span><br>
                        <strong>Amount:</strong> Rs. <span id="bounce_cheque_amount">0.00</span><br>
                        <small class="text-dark"><strong>Note:</strong> Customer ki udhaar balance wapas barh jayegi (Reversal).</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Bounce Date <span class="text-danger">*</span></label>
                        <input type="date" name="bounced_at" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Bounce Reason <span class="text-danger">*</span></label>
                        <select name="bounce_reason" class="form-control" required>
                            <option value="Insufficient Balance">Insufficient Balance (Fund nahi tha)</option>
                            <option value="Signature Mismatch">Signature Mismatch (Dastakhat nahi milte)</option>
                            <option value="Post Dated / Stale Cheque">Date Issue (Post Dated / Stale Cheque)</option>
                            <option value="Payment Stopped by Drawer">Payment Stopped by Drawer (Customer ne roki)</option>
                            <option value="Account Closed">Account Closed</option>
                            <option value="Other">Other / Miscellaneous</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger font-weight-bold">Confirm Bounce &amp; Reverse Balance</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openClearModal(id, chequeNo, amount, bank) {
        $('#clear_cheque_title').text('#' + chequeNo + ' (' + bank + ')');
        $('#clear_cheque_amount').text(parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('#clearChequeForm').attr('action', '/cheques/' + id + '/clear');
        $('#clearChequeModal').modal('show');
    }

    function openBounceModal(id, chequeNo, amount) {
        $('#bounce_cheque_title').text('#' + chequeNo);
        $('#bounce_cheque_amount').text(parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('#bounceChequeForm').attr('action', '/cheques/' + id + '/bounce');
        $('#bounceChequeModal').modal('show');
    }
</script>
@endsection
