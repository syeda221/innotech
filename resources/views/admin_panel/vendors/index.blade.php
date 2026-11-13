@extends('admin_panel.layout.app')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

    .vendor-page {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: #1e293b;
        padding-bottom: 50px;
    }

    /* Page Header */
    .vendor-header {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 22px 28px;
        border-radius: 16px;
        margin-bottom: 22px;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
    }
    .vendor-title {
        font-weight: 800;
        font-size: 1.45rem;
        color: #0f172a;
        margin-bottom: 3px;
        letter-spacing: -0.02em;
    }
    .vendor-sub {
        font-size: 0.85rem;
        color: #64748b;
    }

    /* Action Buttons Hub */
    .vendor-action-hub {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    .btn-vendor-primary {
        background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
        color: #ffffff !important;
        border: none;
        padding: 10px 20px;
        font-size: 0.88rem;
        font-weight: 600;
        border-radius: 10px;
        box-shadow: 0 4px 14px rgba(79, 70, 229, 0.28);
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-vendor-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        color: #ffffff !important;
    }
    .btn-vendor-sub {
        background: #ffffff;
        color: #334155 !important;
        border: 1.5px solid #cbd5e1;
        padding: 9px 16px;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        text-decoration: none;
    }
    .btn-vendor-sub:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a !important;
        transform: translateY(-1px);
    }

    /* Stat Cards */
    .vendor-stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px 22px;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.02);
        display: flex;
        align-items: center;
        gap: 16px;
        height: 100%;
        transition: all 0.2s ease;
    }
    .vendor-stat-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
    }
    .vendor-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .vendor-stat-icon.primary {
        background: #eef2ff;
        color: #4f46e5;
    }
    .vendor-stat-icon.success {
        background: #ecfdf5;
        color: #059669;
    }
    .vendor-stat-icon.info {
        background: #f0fdfa;
        color: #0d9488;
    }
    .vendor-stat-icon.warning {
        background: #fffbeb;
        color: #d97706;
    }
    .vendor-stat-val {
        font-weight: 800;
        font-size: 1.35rem;
        color: #0f172a;
        line-height: 1.2;
    }
    .vendor-stat-lbl {
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 500;
        margin-top: 2px;
    }

    /* Filter Card */
    .vendor-filter-bar {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px 18px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }

    /* Main Table Container */
    .vendor-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
        overflow: hidden;
    }
    .vendor-card-header {
        padding: 18px 24px;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .vendor-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .datanew {
        width: 100% !important;
        margin-bottom: 0 !important;
    }
    .datanew thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 18px;
        white-space: nowrap;
    }
    .datanew tbody td {
        padding: 14px 18px;
        vertical-align: middle;
        font-size: 0.88rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .datanew tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Avatar Circle */
    .vendor-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #eef2ff;
        color: #4f46e5;
        font-weight: 800;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1px solid #e0e7ff;
    }

    /* Action Buttons in Row */
    .vendor-row-actions {
        display: flex;
        gap: 6px;
        justify-content: flex-end;
    }
    .vendor-row-actions .btn-action {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #475569;
        font-size: 0.85rem;
        transition: all 0.15s ease;
        text-decoration: none;
    }
    .vendor-row-actions .btn-action:hover {
        background: #f1f5f9;
        color: #0f172a;
        transform: translateY(-1px);
    }
    .vendor-row-actions .btn-action.btn-ledger:hover {
        background: #eef2ff;
        color: #4f46e5;
        border-color: #c7d2fe;
    }
    .vendor-row-actions .btn-action.btn-edit:hover {
        background: #ecfdf5;
        color: #059669;
        border-color: #a7f3d0;
    }
    .vendor-row-actions .btn-action.btn-delete:hover {
        background: #fef2f2;
        color: #dc2626;
        border-color: #fecaca;
    }

    /* Section Headers inside Modal */
    .modal-section-title {
        font-size: 0.76rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 12px;
        padding-bottom: 6px;
        border-bottom: 1px dashed #e2e8f0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Mobile Cards View (< 768px) */
    .mobile-vendor-cards {
        display: none;
        padding: 14px;
        flex-direction: column;
        gap: 12px;
    }
    .vendor-mcard {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
    }
    .vendor-mcard-hdr {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    .vendor-mcard-title {
        font-weight: 700;
        font-size: 0.98rem;
        color: #0f172a;
    }
    .vendor-mcard-details {
        display: flex;
        flex-direction: column;
        gap: 6px;
        font-size: 0.84rem;
        color: #475569;
        padding: 8px 0;
    }
    .vendor-mcard-actions {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px dashed #e2e8f0;
    }

    /* Responsive Breakpoints */
    @media (max-width: 768px) {
        .vendor-header {
            padding: 18px;
        }
        .vendor-action-hub {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .btn-vendor-primary, .btn-vendor-sub {
            width: 100%;
            justify-content: center;
            height: 42px;
            font-size: 0.8rem;
            padding: 6px;
        }
        .vendor-table-wrap {
            display: none !important;
        }
        .mobile-vendor-cards {
            display: flex;
        }
    }
</style>

<div class="vendor-page container-fluid px-3 px-md-4 pt-3">
    
    {{-- Header Row --}}
    <div class="vendor-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary text-white px-2 py-1" style="font-size: 0.75rem; border-radius: 6px;">ERP Payables</span>
                <span class="text-muted small">&bull; Directory</span>
            </div>
            <h3 class="vendor-title"><i class="fas fa-truck text-primary me-2"></i>Vendor Management</h3>
            <div class="vendor-sub">Central supplier database, opening balances, journal ledgers and payment settlements</div>
        </div>
        <div class="vendor-action-hub">
            @can('vendors.create')
                <button type="button" class="btn-vendor-primary" id="btnAddVendor">
                    <i class="fas fa-plus"></i> Add Vendor
                </button>
            @endcan
            <a href="{{ url('vendors-ledger') }}" class="btn-vendor-sub" title="View Combined Ledger">
                <i class="fas fa-book text-primary"></i> Ledgers
            </a>
            <a href="{{ route('vendor.payments') }}" class="btn-vendor-sub" title="Record or View Payments">
                <i class="fas fa-money-check-alt text-success"></i> Payments
            </a>
            <a href="{{ url('vendor/bilties') }}" class="btn-vendor-sub" title="Logistics &amp; Bilties">
                <i class="fas fa-shipping-fast text-info"></i> Bilties
            </a>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="vendor-stat-card">
                <div class="vendor-stat-icon primary"><i class="fas fa-store"></i></div>
                <div>
                    <div class="vendor-stat-val">{{ $stats['total_vendors'] ?? count($vendors) }}</div>
                    <div class="vendor-stat-lbl">Total Suppliers</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="vendor-stat-card success">
                <div class="vendor-stat-icon success"><i class="fas fa-wallet"></i></div>
                <div>
                    <div class="vendor-stat-val">Rs. {{ number_format($stats['total_opening_balance'] ?? 0, 0) }}</div>
                    <div class="vendor-stat-lbl">Opening Balances</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="vendor-stat-card info">
                <div class="vendor-stat-icon info"><i class="fas fa-sync-alt"></i></div>
                <div>
                    <div class="vendor-stat-val">{{ $stats['both_parties'] ?? 0 }}</div>
                    <div class="vendor-stat-lbl">Dual-Role (Both)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="vendor-stat-card warning">
                <div class="vendor-stat-icon warning"><i class="fas fa-map-marked-alt"></i></div>
                <div>
                    <div class="vendor-stat-val">{{ $stats['total_cities'] ?? 0 }}</div>
                    <div class="vendor-stat-lbl">Cities Covered</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Location & Keyword Single-Line Filter Bar --}}
    <div class="vendor-filter-bar">
        <form method="GET" action="{{ route('vendors.index') }}" class="d-flex align-items-center flex-wrap flex-xl-nowrap gap-2">
            <!-- Keyword Search -->
            <div class="flex-grow-1" style="min-width: 240px;">
                <div class="position-relative">
                    <i class="fas fa-search position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); font-size: 13px;"></i>
                    <input type="text" name="search" class="form-control form-control-sm ps-4" 
                        placeholder="Search vendor name, phone, email, address..." 
                        value="{{ request('search') }}" 
                        style="height: 38px; padding-left: 36px !important; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 0.88rem;">
                </div>
            </div>

            <!-- Party Role -->
            <div style="width: 140px; flex-shrink: 0;">
                <select name="party_type" class="form-select form-select-sm" style="height: 38px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 0.85rem;">
                    <option value="">All Roles</option>
                    <option value="vendor" {{ request('party_type') == 'vendor' ? 'selected' : '' }}>Vendor Only</option>
                    <option value="both" {{ request('party_type') == 'both' ? 'selected' : '' }}>Both (Cust &amp; Vend)</option>
                </select>
            </div>

            <!-- Province -->
            <div style="width: 145px; flex-shrink: 0;">
                <select name="province" id="vend_filter_province" class="form-select form-select-sm" style="height: 38px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 0.85rem;">
                    <option value="">All Provinces</option>
                    @foreach(app(\App\Services\LocationService::class)->getProvinces() as $p)
                        <option value="{{ $p }}" {{ request('province') == $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
            </div>

            <!-- City -->
            <div style="width: 145px; flex-shrink: 0;">
                <select name="city" id="vend_filter_city" class="form-select form-select-sm" style="height: 38px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 0.85rem;">
                    <option value="">All Cities</option>
                    @foreach(app(\App\Services\LocationService::class)->getCities(request('province')) as $c)
                        <option value="{{ $c }}" {{ request('city') == $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Zone -->
            <div style="width: 145px; flex-shrink: 0;">
                <select name="zone" id="vend_filter_zone" class="form-select form-select-sm" style="height: 38px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 0.85rem;">
                    <option value="">All Zones</option>
                    @if(isset($zones))
                        @foreach($zones as $z)
                            <option value="{{ $z->zone }}" {{ request('zone') == $z->zone ? 'selected' : '' }}>{{ $z->zone }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Area -->
            <div style="width: 145px; flex-shrink: 0;">
                <select name="area" id="vend_filter_area" class="form-select form-select-sm" style="height: 38px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 0.85rem;">
                    <option value="">All Areas</option>
                    @foreach(app(\App\Services\LocationService::class)->getAreas(request('city')) as $a)
                        <option value="{{ $a }}" {{ request('area') == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex align-items-center gap-1" style="flex-shrink: 0;">
                <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold" style="height: 38px; border-radius: 8px; white-space: nowrap;">
                    <i class="fas fa-search me-1"></i> Search
                </button>
                <a href="{{ route('vendors.index') }}" class="btn btn-light border btn-sm px-3 text-secondary" title="Reset Filters" style="height: 38px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Main Content Card --}}
    <div class="vendor-card">
        <div class="vendor-card-header">
            <div class="fw-bold text-dark fs-6"><i class="fas fa-list me-2 text-primary"></i>Vendor Directory</div>
            <div class="text-muted small">Showing <strong>{{ count($vendors) }}</strong> records</div>
        </div>

        @if (session()->has('success'))
            <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 mx-4 mt-3 mb-0">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (isset($errors) && $errors->any())
            <div class="alert alert-danger rounded-3 mx-4 mt-3 mb-0">
                <i class="fas fa-exclamation-triangle me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        {{-- Desktop Table View --}}
        <div class="vendor-table-wrap p-3">
            <table class="table table-hover align-middle datanew">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th class="text-start">Vendor / Company</th>
                        <th class="text-start">Contact</th>
                        <th class="text-start">Tax Identifiers</th>
                        <th class="text-start">Opening Balance</th>
                        <th class="text-start">Location</th>
                        <th class="text-start">Address</th>
                        <th class="text-end pe-3" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vendors as $key => $v)
                        <tr id="vendor-row-{{ $v->id }}">
                            <td class="text-center fw-bold text-muted">{{ $key + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="vendor-avatar">
                                        {{ strtoupper(substr($v->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $v->name }}</div>
                                        @if(($v->party_type ?? '') === 'both' || !empty($v->linked_customer_id))
                                            <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-0" style="font-size: 10px;" title="Party linked as both Vendor &amp; Customer">
                                                <i class="fas fa-sync-alt me-1"></i>Both (Cust &amp; Vend)
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted border px-1.5 py-0" style="font-size: 10px;">Vendor</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    @if ($v->phone)
                                        <div>
                                            <a href="tel:{{ $v->phone }}" class="text-decoration-none badge bg-light text-dark border px-2 py-1">
                                                <i class="fas fa-phone-alt me-1 text-primary"></i>{{ $v->phone }}
                                            </a>
                                        </div>
                                    @endif
                                    @if ($v->email)
                                        <div class="small text-muted mt-1">
                                            <i class="fas fa-envelope me-1 text-muted"></i>{{ $v->email }}
                                        </div>
                                    @endif
                                    @if (!$v->phone && !$v->email)
                                        <span class="text-muted small">—</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    @if ($v->cnic)
                                        <div><span class="badge bg-light text-secondary border px-2 py-0.5 font-monospace" style="font-size: 11px;">CNIC: {{ $v->cnic }}</span></div>
                                    @endif
                                    @if ($v->ntn_number)
                                        <div class="mt-1"><span class="badge bg-info-subtle text-info border border-info px-2 py-0.5" style="font-size: 10px;">NTN: {{ $v->ntn_number }}</span></div>
                                    @endif
                                    @if ($v->strn_number)
                                        <div class="mt-1"><span class="badge bg-success-subtle text-success border border-success px-2 py-0.5" style="font-size: 10px;">STRN: {{ $v->strn_number }}</span></div>
                                    @endif
                                    @if (!$v->cnic && !$v->ntn_number && !$v->strn_number)
                                        <span class="text-muted small">—</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold {{ (float)$v->opening_balance > 0 ? 'text-success' : 'text-muted' }}">
                                    Rs. {{ number_format((float) $v->opening_balance, 2) }}
                                </div>
                                <span class="badge bg-light text-muted border px-1.5 py-0" style="font-size: 10px;">Initial</span>
                            </td>
                            <td>
                                @if($v->city || $v->province || $v->zone || $v->area)
                                    <div><i class="fas fa-map-marker-alt text-primary me-1"></i><span class="fw-semibold text-dark">{{ $v->city ?: ($v->province ?: '—') }}</span></div>
                                    @if($v->zone || $v->area)
                                        <small class="text-muted d-block">{{ implode(' • ', array_filter([$v->zone, $v->area])) }}</small>
                                    @endif
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-secondary small" title="{{ $v->address }}">
                                {{ Str::limit($v->address ?? '', 32) ?: '—' }}
                            </td>
                            <td class="text-end pe-3">
                                <div class="vendor-row-actions">
                                    <a href="{{ route('vendor.ledger', $v->id) }}" class="btn-action btn-ledger" title="View Journal Ledger">
                                        <i class="fas fa-book-open"></i>
                                    </a>
                                    @can('vendors.edit')
                                        <button type="button" class="btn-action btn-edit edit-btn" data-id="{{ $v->id }}" title="Edit Vendor">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endcan
                                    @can('vendors.delete')
                                        <button type="button" class="btn-action btn-delete delete-vendor-btn" data-url="{{ url('vendor/delete/' . $v->id) }}" data-name="{{ $v->name }}" title="Delete Vendor">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-truck fa-3x mb-3 text-secondary" style="opacity: 0.3;"></i>
                                    <div class="fw-bold fs-6">No Vendors Found</div>
                                    <small>Try clearing search filters or add a new vendor.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards View (< 768px) --}}
        <div class="mobile-vendor-cards">
            @forelse ($vendors as $key => $v)
                <div class="vendor-mcard" id="mcard-{{ $v->id }}">
                    <div class="vendor-mcard-hdr">
                        <div class="d-flex align-items-center gap-2">
                            <div class="vendor-avatar">
                                {{ strtoupper(substr($v->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="vendor-mcard-title">{{ $v->name }}</div>
                                @if(($v->party_type ?? '') === 'both' || !empty($v->linked_customer_id))
                                    <span class="badge bg-primary text-white" style="font-size: 10px;">Both</span>
                                @endif
                            </div>
                        </div>
                        <span class="badge bg-light text-dark border">#{{ $key + 1 }}</span>
                    </div>

                    <div class="vendor-mcard-details">
                        @if ($v->phone)
                            <div><i class="fas fa-phone me-2 text-primary"></i> <strong>Phone:</strong> {{ $v->phone }}</div>
                        @endif
                        @if ($v->email)
                            <div><i class="fas fa-envelope me-2 text-muted"></i> <strong>Email:</strong> {{ $v->email }}</div>
                        @endif
                        @if ($v->cnic)
                            <div><i class="fas fa-id-card me-2 text-secondary"></i> <strong>CNIC:</strong> {{ $v->cnic }}</div>
                        @endif
                        @if ($v->city || $v->area)
                            <div><i class="fas fa-map-marker-alt me-2 text-primary"></i> <strong>Location:</strong> {{ $v->city }}{{ $v->area ? ' ('.$v->area.')' : '' }}</div>
                        @endif
                        <div><i class="fas fa-wallet me-2 text-success"></i> <strong>Opening Balance:</strong> <span class="text-success fw-bold">Rs. {{ number_format((float) $v->opening_balance, 2) }}</span></div>
                        @if ($v->address)
                            <div><i class="fas fa-home me-2 text-muted"></i> <strong>Address:</strong> {{ Str::limit($v->address ?? '', 50) }}</div>
                        @endif
                    </div>

                    <div class="vendor-mcard-actions">
                        <a href="{{ route('vendor.ledger', $v->id) }}" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">
                            <i class="fas fa-book-open me-1"></i> Ledger
                        </a>
                        @can('vendors.edit')
                            <button type="button" class="btn btn-sm btn-outline-success edit-btn" data-id="{{ $v->id }}" style="border-radius: 8px;">
                                <i class="fas fa-edit me-1"></i> Edit
                            </button>
                        @endcan
                        @can('vendors.delete')
                            <button type="button" class="btn btn-sm btn-outline-danger delete-vendor-btn" data-url="{{ url('vendor/delete/' . $v->id) }}" data-name="{{ $v->name }}" style="border-radius: 8px;">
                                <i class="fas fa-trash-alt me-1"></i> Delete
                            </button>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted">
                    No vendors found.
                </div>
            @endforelse
        </div>
    </div>

</div>

{{-- Add/Edit Vendor Modal (With Instant Inline AJAX Validation) --}}
<div class="modal fade" id="vendorModal" tabindex="-1" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <form action="{{ route('vendors.store.ajax') }}" method="POST" id="vendorForm" novalidate>
                @csrf
                <input type="hidden" id="vendor_id" name="id">

                <div class="modal-header bg-white px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="modal-title fw-bold text-dark mb-0" id="modalTitle">
                        <i class="fas fa-truck text-primary me-2"></i>New Vendor
                    </h5>
                    <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4" style="max-height: 80vh; overflow-y: auto;">
                    {{-- Alert Box for General Exceptions / Top-level Feedback --}}
                    <div class="alert alert-danger d-none py-2 px-3 mb-3 small" id="vendorFormAlert" role="alert">
                        <i class="fas fa-exclamation-circle me-1"></i> <span id="vendorFormAlertMsg"></span>
                    </div>

                    {{-- Section 1: Business Profile --}}
                    <div class="modal-section-title">
                        <i class="fas fa-id-card text-primary"></i> 1. Business Profile &amp; Role
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label for="vname" class="form-label fw-bold text-dark small mb-1">
                                Vendor / Business Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control px-3 py-2" name="name" id="vname" placeholder="Enter vendor name..." required style="border-radius: 10px; border: 1.5px solid #cbd5e1;" />
                            <div class="invalid-feedback text-danger small mt-1" id="err_name"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="vparty_type" class="form-label fw-bold text-dark small mb-1">
                                Role / Category <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-control px-3 py-2" name="party_type" id="vparty_type" style="border-radius: 10px; border: 1.5px solid #cbd5e1;">
                                <option value="vendor" selected>Vendor Only</option>
                                <option value="both">Both (Cust &amp; Vend)</option>
                            </select>
                            <div class="invalid-feedback text-danger small mt-1" id="err_party_type"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="vphone" class="form-label fw-bold text-dark small mb-1">
                                Phone / Mobile
                            </label>
                            <input type="text" class="form-control px-3 py-2" name="phone" id="vphone" placeholder="e.g. 0300-1234567" style="border-radius: 10px; border: 1.5px solid #cbd5e1;" />
                            <div class="invalid-feedback text-danger small mt-1" id="err_phone"></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="vemail" class="form-label fw-bold text-dark small mb-1">Email Address</label>
                            <input type="email" class="form-control px-3 py-2" name="email" id="vemail" placeholder="vendor@example.com" style="border-radius: 10px; border: 1.5px solid #cbd5e1;" />
                            <div class="invalid-feedback text-danger small mt-1" id="err_email"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="vcnic" class="form-label fw-bold text-dark small mb-1">CNIC Number</label>
                            <input type="text" class="form-control px-3 py-2" name="cnic" id="vcnic" placeholder="e.g. 35201-1234567-1" style="border-radius: 10px; border: 1.5px solid #cbd5e1;" />
                            <div class="invalid-feedback text-danger small mt-1" id="err_cnic"></div>
                        </div>
                    </div>

                    {{-- Section 2: Tax Registration --}}
                    <div class="modal-section-title">
                        <i class="fas fa-file-invoice text-primary"></i> 2. Tax &amp; Legal Identifiers
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="vntn" class="form-label fw-bold text-dark small mb-1">NTN Number</label>
                            <input type="text" class="form-control px-3 py-2" name="ntn_number" id="vntn" placeholder="NTN (e.g. 1234567-8)" style="border-radius: 10px; border: 1.5px solid #cbd5e1;" />
                            <div class="invalid-feedback text-danger small mt-1" id="err_ntn_number"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="vstrn" class="form-label fw-bold text-dark small mb-1">STRN Number</label>
                            <input type="text" class="form-control px-3 py-2" name="strn_number" id="vstrn" placeholder="Sales Tax Registration Number" style="border-radius: 10px; border: 1.5px solid #cbd5e1;" />
                            <div class="invalid-feedback text-danger small mt-1" id="err_strn_number"></div>
                        </div>
                    </div>

                    {{-- Section 3: Geographic Details --}}
                    <div class="modal-section-title">
                        <i class="fas fa-map-marker-alt text-primary"></i> 3. Location &amp; Physical Address
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="vprovince" class="form-label fw-bold text-dark small mb-1">Province</label>
                            <select class="form-select form-control px-3 py-2" name="province" id="vprovince" style="border-radius: 10px; border: 1.5px solid #cbd5e1; height: 42px;">
                                <option value="">-- Select Province --</option>
                                @foreach(app(\App\Services\LocationService::class)->getProvinces() as $p)
                                    <option value="{{ $p }}">{{ $p }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback text-danger small mt-1" id="err_province"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="vcity" class="form-label fw-bold text-dark small mb-1">City</label>
                            <div class="input-group">
                                <select class="form-select form-control px-3 py-2" name="city" id="vcity" style="border-radius: 8px 0 0 8px; border: 1.5px solid #cbd5e1; height: 42px;">
                                    <option value="">-- Select City --</option>
                                    @foreach(app(\App\Services\LocationService::class)->getCities() as $c)
                                        <option value="{{ $c }}">{{ $c }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-quick-add-city px-2.5" title="Add New City" style="border-radius: 0 8px 8px 0; border: 1.5px solid #cbd5e1; border-left: none; height: 42px;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback text-danger small mt-1" id="err_city"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="vzone" class="form-label fw-bold text-dark small mb-1">Zone / Region</label>
                            <div class="input-group">
                                <select class="form-select form-control px-3 py-2" name="zone" id="vzone" style="border-radius: 8px 0 0 8px; border: 1.5px solid #cbd5e1; height: 42px;">
                                    <option value="">-- Select Zone --</option>
                                    @if(isset($zones))
                                        @foreach($zones as $z)
                                            <option value="{{ $z->zone }}">{{ $z->zone }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-quick-add-zone px-2.5" title="Add New Zone" style="border-radius: 0 8px 8px 0; border: 1.5px solid #cbd5e1; border-left: none; height: 42px;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback text-danger small mt-1" id="err_zone"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="varea" class="form-label fw-bold text-dark small mb-1">Area / Town</label>
                            <div class="input-group">
                                <select class="form-select form-control px-3 py-2" name="area" id="varea" style="border-radius: 8px 0 0 8px; border: 1.5px solid #cbd5e1; height: 42px;">
                                    <option value="">-- Select Area --</option>
                                    @foreach(app(\App\Services\LocationService::class)->getAreas() as $a)
                                        <option value="{{ $a }}">{{ $a }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-quick-add-area px-2.5" title="Add New Area" style="border-radius: 0 8px 8px 0; border: 1.5px solid #cbd5e1; border-left: none; height: 42px;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback text-danger small mt-1" id="err_area"></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label for="vaddress" class="form-label fw-bold text-dark small mb-1">Full Street Address</label>
                            <textarea class="form-control px-3 py-2" name="address" id="vaddress" rows="2" placeholder="Shop / Office / Street address..." style="border-radius: 10px; border: 1.5px solid #cbd5e1;"></textarea>
                            <div class="invalid-feedback text-danger small mt-1" id="err_address"></div>
                        </div>
                    </div>

                    {{-- Section 4: Financials & Balance --}}
                    <div class="modal-section-title">
                        <i class="fas fa-wallet text-primary"></i> 4. Initial Financials &amp; Opening Balance
                    </div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="opening_balance" class="form-label fw-bold text-dark small mb-1">
                                Opening Balance (Payable to Supplier)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted fw-bold" style="border-radius: 10px 0 0 10px; border: 1.5px solid #cbd5e1; border-right: none;">PKR</span>
                                <input type="number" step="any" class="form-control px-3 py-2 fw-bold" name="opening_balance" id="opening_balance" placeholder="0.00" value="0.00" style="border-radius: 0 10px 10px 0; border: 1.5px solid #cbd5e1;" />
                            </div>
                            {{-- Specific inline error display for opening_balance --}}
                            <div class="invalid-feedback text-danger small mt-1" id="err_opening_balance"></div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Existing ledger liability. Enter 0.00 if there is no previous balance.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light px-4 py-3 border-top d-flex align-items-center justify-content-between">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" data-dismiss="modal" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    @can('vendors.create')
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" id="btnSaveVendor" style="border-radius: 8px; background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); border: none;">
                            <i class="fas fa-check me-1"></i> Save Vendor
                        </button>
                    @endcan
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        if ($.fn.DataTable.isDataTable('.datanew')) {
            $('.datanew').DataTable().destroy();
        }
        $('.datanew').DataTable({
            "pageLength": 10,
            "order": [],
            "language": {
                "search": "",
                "searchPlaceholder": "Filter records..."
            },
            "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        });

        // Function to clear all modal error states
        function clearModalErrors() {
            $('#vendorForm').find('.is-invalid').removeClass('is-invalid');
            $('#vendorForm').find('.invalid-feedback').text('').hide();
            $('#vendorFormAlert').addClass('d-none');
            $('#vendorFormAlertMsg').text('');
        }

        // Open Modal for Create
        $('#btnAddVendor').click(function() {
            $('#vendorForm')[0].reset();
            clearModalErrors();
            $('#vendor_id').val('');
            $('#opening_balance').val('0.00');
            $('#vparty_type').val('vendor');
            $('#vprovince').val('').trigger('change');
            $('#vcity').val('').trigger('change');
            $('#vzone').val('').trigger('change');
            $('#varea').val('').trigger('change');
            $('#modalTitle').html('<i class="fas fa-truck text-primary me-2"></i>New Vendor');
            $('#vendorModal').modal('show');
            setTimeout(function() { $('#vname').focus(); }, 300);
        });

        // Open Modal for Edit
        $(document).on('click', '.edit-btn', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const editUrl = "{{ url('vendor/edit') }}/" + id;

            clearModalErrors();

            $.get(editUrl, function(res) {
                if (res.success && res.vendor) {
                    const v = res.vendor;
                    $('#vendor_id').val(v.id);
                    $('#vname').val(v.name || '');
                    $('#vparty_type').val(v.party_type || 'vendor');
                    $('#vphone').val(v.phone || '');
                    $('#vemail').val(v.email || '');
                    $('#vcnic').val(v.cnic || '');
                    $('#vntn').val(v.ntn_number || '');
                    $('#vstrn').val(v.strn_number || '');
                    $('#opening_balance').val(v.opening_balance !== null ? v.opening_balance : '0.00');
                    $('#vaddress').val(v.address || '');
                    $('#vzone').val(v.zone || '').trigger('change');

                    // Cascade locations
                    $('#vprovince').val(v.province || '').trigger('change');
                    setTimeout(function() {
                        $('#vcity').val(v.city || '').trigger('change');
                        setTimeout(function() {
                            $('#varea').val(v.area || '').trigger('change');
                        }, 120);
                    }, 120);

                    $('#modalTitle').html('<i class="fas fa-edit text-primary me-2"></i>Edit Vendor');
                    $('#vendorModal').modal('show');
                    setTimeout(function() { $('#vname').focus(); }, 300);
                } else {
                    Swal.fire('Error', 'Unable to load vendor details.', 'error');
                }
            }).fail(function() {
                Swal.fire('Error', 'Unable to communicate with server.', 'error');
            });
        });

        // AJAX Form Submission with inline field error display
        $('#vendorForm').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $submitBtn = $form.find('#btnSaveVendor');
            const originalBtnHtml = $submitBtn.html();

            // Clear previous errors
            clearModalErrors();

            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function(response) {
                    $submitBtn.prop('disabled', false).html(originalBtnHtml);
                    if (response.success) {
                        $('#vendorModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Vendor saved successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        $('#vendorFormAlertMsg').text(response.message || 'An error occurred while saving.');
                        $('#vendorFormAlert').removeClass('d-none');
                    }
                },
                error: function(xhr) {
                    $submitBtn.prop('disabled', false).html(originalBtnHtml);
                    let firstErrorField = null;

                    if (xhr.status === 422 && xhr.responseJSON) {
                        const errors = xhr.responseJSON.errors || {};
                        let errorList = [];

                        $.each(errors, function(field, messages) {
                            const msg = Array.isArray(messages) ? messages[0] : messages;
                            errorList.push(msg);

                            // Find corresponding input field
                            let $input = $form.find('[name="' + field + '"]');
                            if ($input.length) {
                                $input.addClass('is-invalid');
                                let $errContainer = $('#err_' + field);
                                if ($errContainer.length) {
                                    $errContainer.text(msg).show();
                                }
                                if (!firstErrorField) {
                                    firstErrorField = $input;
                                }
                            }
                        });

                        // Display top banner in modal
                        const topMsg = xhr.responseJSON.message || errorList[0] || 'Please correct the highlighted fields.';
                        $('#vendorFormAlertMsg').text(topMsg);
                        $('#vendorFormAlert').removeClass('d-none');

                        if (firstErrorField) {
                            firstErrorField.focus();
                        }
                    } else {
                        let errMsg = 'Failed to save vendor. Please check inputs and try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }
                        $('#vendorFormAlertMsg').text(errMsg);
                        $('#vendorFormAlert').removeClass('d-none');
                    }
                }
            });
        });

        // SweetAlert Delete with AJAX
        $(document).on('click', '.delete-vendor-btn', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            const name = $(this).data('name') || 'this vendor';

            Swal.fire({
                title: 'Delete Vendor?',
                text: 'Are you sure you want to delete "' + name + '"? This will remove the vendor record.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json'
                        },
                        success: function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Vendor deleted successfully.',
                                timer: 1400,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        },
                        error: function() {
                            Swal.fire('Error', 'Unable to delete vendor.', 'error');
                        }
                    });
                }
            });
        });
    });
</script>

@include('admin_panel.partials.location_selector_script')
<script>
    $(document).ready(function() {
        setupLocationCascade({
            province: '#vend_filter_province',
            city: '#vend_filter_city',
            area: '#vend_filter_area'
        });
        setupLocationCascade({
            province: '#vprovince',
            city: '#vcity',
            area: '#varea'
        });
    });
</script>
@endsection
