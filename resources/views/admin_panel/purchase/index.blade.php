@extends('admin_panel.layout.app')

@section('content')
    <style>
        /* Premium ERP Card & Container Styling */
        .premium-card {
            border: 2px solid #cbd5e1 !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
            background-color: #ffffff;
            margin-top: 10px;
        }
        
        /* Clean & Bold Filter Panel */
        .filter-panel {
            background-color: #f8fafc !important;
            border: 2px dashed #94a3b8 !important;
            border-radius: 10px !important;
            padding: 18px !important;
        }
        
        .filter-panel label {
            font-size: 12px;
            font-weight: 700 !important;
            color: #475569 !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-panel .form-control,
        .filter-panel .form-select {
            border: 2px solid #cbd5e1 !important;
            border-radius: 6px !important;
            font-weight: 500 !important;
            color: #1e293b !important;
            transition: all 0.2s ease-in-out;
            height: 38px !important;
        }
        
        .filter-panel .form-control:focus,
        .filter-panel .form-select:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }
        
        /* Elegant & Bold Buttons */
        .btn-premium-primary {
            background-color: #2563eb !important;
            border: 2px solid #1d4ed8 !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            border-radius: 6px !important;
            height: 38px !important;
            padding: 0 16px !important;
            transition: all 0.2s;
        }
        .btn-premium-primary:hover {
            background-color: #1d4ed8 !important;
            transform: translateY(-1px);
        }
        
        .btn-premium-secondary {
            background-color: #ffffff !important;
            border: 2px solid #cbd5e1 !important;
            color: #475569 !important;
            font-weight: 600 !important;
            border-radius: 6px !important;
            height: 38px !important;
            padding: 0 16px !important;
            transition: all 0.2s;
        }
        .btn-premium-secondary:hover {
            background-color: #f1f5f9 !important;
            border-color: #94a3b8 !important;
            color: #1e293b !important;
        }

        /* Compact Button Styling for Action List */
        .btn-xs {
            padding: 4px 8px !important;
            font-size: 11px !important;
            border-radius: 4px !important;
            font-weight: 700 !important;
            line-height: 1.2 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            margin: 2px !important;
        }
        .btn-xs i {
            font-size: 10px !important;
        }
        .btn-xs:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.08) !important;
        }

        /* Premium Action Dropdown Button */
        .btn-premium-action {
            background-color: #f8fafc !important;
            border: 2px solid #cbd5e1 !important;
            color: #475569 !important;
            font-weight: 700 !important;
            border-radius: 6px !important;
            height: 32px !important;
            padding: 0 12px !important;
            font-size: 11px !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.2s ease-in-out !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
        }
        .btn-premium-action:hover, 
        .btn-premium-action:focus, 
        .btn-premium-action[aria-expanded="true"] {
            background-color: #f1f5f9 !important;
            border-color: #94a3b8 !important;
            color: #1e293b !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.08) !important;
        }
        .btn-premium-action::after {
            margin-left: 6px !important;
            vertical-align: middle !important;
        }

        /* Premium Dropdown Menu Customizations */
        .dropdown-menu {
            border: 2px solid #e2e8f0 !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            padding: 6px 0 !important;
            z-index: 1050 !important;
        }
        .dropdown-item {
            font-size: 12px !important;
            font-weight: 600 !important;
            color: #475569 !important;
            padding: 8px 16px !important;
            transition: all 0.15s ease-in-out !important;
        }
        .dropdown-item:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }
        .dropdown-divider {
            border-top: 2px solid #e2e8f0 !important;
            margin: 6px 0 !important;
        }

        /* Premium Bold Table Styling */
        .premium-table {
            border: 2px solid #475569 !important; /* Bold outer border */
            border-radius: 8px !important;
            overflow: visible !important;
        }
        
        .premium-table thead th {
            background-color: #f1f5f9 !important;
            color: #1e293b !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            border-bottom: 3px solid #475569 !important; /* Thick bottom border under headers */
            border-right: 2px solid #cbd5e1 !important;
            padding: 12px 10px !important;
        }
        
        .premium-table thead th:last-child {
            border-right: none !important;
        }
        
        .premium-table tbody td {
            border: 2px solid #e2e8f0 !important; /* Bold borders between all cells */
            padding: 12px 10px !important;
            font-size: 13px !important;
            color: #334155 !important;
            background-color: #ffffff;
        }
        
        .premium-table tbody tr:hover td {
            background-color: #f8fafc !important; /* Elegant row hover background */
        }
        
        /* Table Responsive border fix */
        .table-responsive {
            border-radius: 8px !important;
            overflow: visible !important;
        }

        /* Custom styled controls for inline Bulk Discount Card */
        .btn-circle-custom {
            width: 32px !important;
            height: 32px !important;
            border-radius: 50% !important;
            border: 1px solid #cbd5e1 !important;
            color: #475569 !important;
            background-color: transparent !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.2s;
        }
        .btn-circle-custom:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }

        /* Mobile Breakpoints (< 768px) */
        @media (max-width: 768px) {
            .purch-hdr-actions {
                display: grid !important;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                width: 100%;
            }
            .purch-hdr-actions .btn {
                width: 100%;
                justify-content: center;
                height: 38px;
                font-size: 0.8rem;
            }
            .purch-status-pills {
                display: flex !important;
                gap: 6px;
                overflow-x: auto;
                padding-bottom: 6px;
                -webkit-overflow-scrolling: touch;
            }
            .purch-status-pills .btn {
                flex: 0 0 auto;
                white-space: nowrap;
            }
            .table-responsive {
                display: none !important;
            }
        }
        @media (min-width: 769px) {
            .purch-hdr-actions {
                display: flex;
                gap: 8px;
            }
            .purch-status-pills {
                display: flex;
                gap: 8px;
            }
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid py-4">

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">Purchase Management</h4>
                        <p class="text-muted mb-0 small">View and manage your purchase invoices</p>
                    </div>
                    <div class="purch-hdr-actions">
                        <a class="btn btn-outline-danger px-3 shadow-sm fw-medium d-inline-flex align-items-center gap-1 justify-content-center"
                            href="{{ route('purchase.return.index') }}" style="border-radius: 8px;">
                            <i class="fas fa-undo"></i> Returns
                        </a>

                        <button type="button" id="btnOpenPurchaseSettings" class="btn btn-secondary px-3 shadow-sm fw-semibold d-inline-flex align-items-center gap-1 justify-content-center text-white" data-toggle="modal" data-target="#purchaseGstSettingModal" data-bs-toggle="modal" data-bs-target="#purchaseGstSettingModal" style="border-radius: 8px; height: 38px; cursor: pointer;" title="Purchase GST Settings">
                            <i class="fas fa-cog mr-1 me-1 text-white"></i> Settings
                        </button>

                        @can('purchases.create')
                            <a class="btn btn-success px-3 shadow-sm fw-medium d-inline-flex align-items-center gap-1 justify-content-center"
                                href="{{ route('purchase.quick_create') }}" style="border-radius: 8px;">
                                <i class="fas fa-bolt"></i> Quick Purchase
                            </a>
                            <a class="btn btn-primary px-3 shadow-sm fw-medium d-inline-flex align-items-center gap-1 justify-content-center"
                                href="{{ route('add_purchase') }}" style="border-radius: 8px;">
                                <i class="fas fa-plus"></i> Add Purchase
                            </a>
                        @endcan
                    </div>
                </div>

                {{-- Status Filters --}}
                <div class="mb-4 purch-status-pills">
                    <a href="{{ route('Purchase.home', ['status' => 'all']) }}"
                        class="btn btn-sm {{ request('status') == 'all' || !request('status') ? 'btn-secondary' : 'btn-outline-secondary' }} rounded-3 shadow-sm px-3">
                        All
                    </a>
                    <a href="{{ route('Purchase.home', ['status' => 'approved']) }}"
                        class="btn btn-sm {{ request('status') == 'approved' ? 'btn-success' : 'btn-outline-success' }} rounded-3 shadow-sm px-3">
                        Approved
                    </a>
                    <a href="{{ route('Purchase.home', ['status' => 'draft']) }}"
                        class="btn btn-sm {{ request('status') == 'draft' ? 'btn-warning text-dark' : 'btn-outline-warning' }} rounded-3 shadow-sm px-3">
                        Draft
                    </a>
                    <a href="{{ route('Purchase.home', ['status' => 'Returned']) }}"
                        class="btn btn-sm {{ request('status') == 'Returned' ? 'btn-danger' : 'btn-outline-danger' }} rounded-3 shadow-sm px-3">
                        Returned
                    </a>
                </div>

                <div class="card premium-card">
                    <div class="card-body p-4">
                        @if (session('success'))
                            <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 mb-4">
                                <i class="fas fa-check-circle"></i>
                                <span>{{ session('success') }}</span>
                                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        {{-- AJAX Filter Panel --}}
                        <div class="card filter-panel mb-4">
                            <div class="card-body p-0">
                                <form id="filterForm" class="row g-3 align-items-end" autocomplete="off">
                                    <div class="col-md-2">
                                        <label class="form-label mb-1">Quick Filter</label>
                                        <select id="quick_filter" class="form-select">
                                            <option value="custom">Custom Range</option>
                                            <option value="daily">Daily (Today)</option>
                                            <option value="weekly">Weekly (This Week)</option>
                                            <option value="monthly">Monthly (This Month)</option>
                                            <option value="yearly">Yearly (This Year)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label mb-1">From Date</label>
                                        <input type="text" class="form-control datepicker-custom bg-white" name="from_date" id="filter_from_date" placeholder="dd/mm/yyyy">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label mb-1">To Date</label>
                                        <input type="text" class="form-control datepicker-custom bg-white" name="to_date" id="filter_to_date" placeholder="dd/mm/yyyy">
                                    </div>
                                    <!-- <div class="col-md-2">
                                        <label class="form-label mb-1">Mobile No</label>
                                        <input type="text" class="form-control" name="mobile_no" id="filter_mobile_no" placeholder="Search mobile...">
                                    </div> -->
                                    <div class="col-md-1">
                                        <label class="form-label mb-1">Bill#</label>
                                        <input type="text" class="form-control" name="bill_no" id="filter_bill_no" placeholder="GRN-...">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label mb-1">M.Bill#</label>
                                        <input type="text" class="form-control" name="reference" id="filter_reference" placeholder="M.Bill...">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label mb-1">Vendor</label>
                                        <select class="form-select" name="vendor_id" id="filter_vendor_id">
                                            <option value="">All Vendors</option>
                                            @foreach ($vendors as $v)
                                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!-- <div class="col-md-2">
                                        <label class="form-label mb-1">Order By</label>
                                        <select class="form-select" name="order_by" id="filter_order_by">
                                            <option value="purchase_date">Invoice Date</option>
                                            <option value="invoice_no">Invoice No</option>
                                        </select>
                                    </div> -->
                                    <div class="col-md-2 d-flex gap-2">
                                        <button type="button" class="btn btn-premium-secondary w-50" id="btnReset">
                                            <i class="fas fa-undo me-1"></i>Reset
                                        </button>
                                        <button type="submit" class="btn btn-premium-primary w-50" id="btnSearch">
                                            <i class="fas fa-search me-1"></i>Search
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="purchase-table" class="table table-hover align-middle datanew premium-table" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-3 ps-3 rounded-start text-secondary fw-semibold text-uppercase small" style="width: 40px; vertical-align: middle;">
                                            <input type="checkbox" id="selectAllPurchases" style="cursor: pointer; width: 16px; height: 16px; margin: 0 auto !important;">
                                        </th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small" style="width: 50px;">SR#</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Date</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Invoice No</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Vendor inv#</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Remarks</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Status</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Vendor</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Warehouse</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Inline Disc</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Add. Disc</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Net Amount</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Paid</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Due</th>
                                        <th class="py-3 pe-3 rounded-end text-secondary fw-semibold text-uppercase small text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="purchaseTableBody">
                                    @include('admin_panel.purchase.partials.purchase_table_body')
                                </tbody>
                            </table>
                        </div>

                        <!-- Bulk Discount Card (Sticky/Floating action bar below table) -->
                        <div id="bulk-discount-bar" class="d-none card mt-4 shadow-lg border-2" style="border: 2px solid #2563eb !important; border-radius: 14px; background-color: #ffffff; position: sticky; bottom: 20px; z-index: 1040; box-shadow: 0 10px 35px rgba(37, 99, 235, 0.18) !important;">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex flex-column">
                                    <!-- Header Row -->
                                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                        <div class="d-flex align-items-center gap-2" style="color: #2563eb; font-size: 16px; font-weight: 700;">
                                            <i class="fas fa-tag me-1"></i>
                                            <span>Additional Discount for <span id="selected-purchases-count-text" class="badge bg-primary text-white rounded-pill px-2">0</span> Selected Purchases</span>
                                        </div>
                                        <button type="button" id="btn-minimize-bulk-bar" class="btn btn-sm btn-outline-secondary rounded-circle py-0 px-2" title="Close" style="width: 28px; height: 28px; line-height: 1;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Discount Type Toggle & Input -->
                                    <div class="row g-3 align-items-end mb-3">
                                        <div class="col-md-4 col-12">
                                            <label class="form-label mb-1 text-secondary font-weight-bold fw-bold small">DISCOUNT TYPE</label>
                                            <div class="btn-group w-100" role="group">
                                                <button type="button" class="btn btn-sm btn-primary fw-bold btn-discount-type active" data-type="percentage">
                                                    <i class="fas fa-percent me-1"></i> Percentage (%)
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary fw-bold btn-discount-type" data-type="fixed">
                                                    <i class="fas fa-money-bill-wave me-1"></i> Fixed (Rs.)
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-12">
                                            <label class="form-label mb-1 text-secondary font-weight-bold fw-bold small" id="bulk-discount-label">DISCOUNT PERCENTAGE (%)</label>
                                            <div class="input-group">
                                                <input type="number" id="bulk-discount-input" min="0" max="100" step="0.1" class="form-control" placeholder="Enter discount percentage (e.g. 5 for 5%)" style="border: 2px solid #cbd5e1 !important; border-radius: 8px 0 0 8px !important; height: 40px; font-size: 14px; font-weight: 600;">
                                                <span class="input-group-text fw-bold text-primary bg-light" id="bulk-discount-addon" style="border: 2px solid #cbd5e1; border-left: none; border-radius: 0 8px 8px 0 !important; padding: 0 16px;">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Footer Actions -->
                                    <div class="d-flex flex-wrap align-items-center justify-content-between pt-2 border-top gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button" id="btn-save-bulk-discount" class="btn btn-premium-primary px-4 py-2 d-flex align-items-center" style="height: 38px; font-weight: 700; border-radius: 8px;">
                                                <i class="fas fa-check me-2 mr-2"></i> Apply Additional Discount
                                            </button>
                                            <button type="button" id="btn-cancel-bulk-discount" class="btn btn-premium-secondary px-3 py-2" style="height: 38px; font-weight: 600; border-radius: 8px;">
                                                Deselect All
                                            </button>
                                        </div>
                                        <div class="text-muted small fw-semibold">
                                            <span id="selected-ratio-text">0 of 0</span> purchases selected
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Purchase GST & Quick Settings Modal -->
    <style>
        .gst-toggle-switch {
            position: relative;
            display: inline-block;
            width: 46px;
            height: 24px;
            margin-bottom: 0;
            vertical-align: middle;
            cursor: pointer;
            user-select: none;
            flex-shrink: 0;
        }
        .gst-toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }
        .gst-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: 0.25s ease;
            border-radius: 24px;
            box-sizing: border-box;
        }
        .gst-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            top: 3px;
            background-color: #ffffff;
            transition: 0.25s ease;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
            box-sizing: border-box;
        }
        .gst-toggle-switch input:checked + .gst-slider {
            background-color: #2563eb;
        }
        .gst-toggle-switch input:checked + .gst-slider:before {
            left: calc(100% - 21px);
            transform: none;
        }
    </style>

    <div class="modal fade" id="purchaseGstSettingModal" tabindex="-1" role="dialog" aria-labelledby="purchaseGstSettingModalLabel" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog" role="document" style="max-width: 460px; margin-top: 60px;">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden; background: #fff;">
                <div class="modal-header border-bottom bg-light px-4 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="purchaseGstSettingModalLabel" style="font-size: 16px;">
                        <i class="fas fa-cog text-primary mr-2 me-2"></i> Purchase GST Settings
                    </h5>
                    <button type="button" class="close text-dark" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="font-size: 26px; line-height: 1; opacity: 0.7; background: transparent; border: none; outline: none; cursor: pointer; padding: 0;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="purchaseGstSettingForm">
                    @csrf
                    <div class="modal-body p-4 text-start">
                        <div class="p-3 rounded border bg-light mb-3 d-flex justify-content-between align-items-center gap-3">
                            <div>
                                <label class="fw-bold text-dark mb-0 d-flex align-items-center" for="modalPurchaseGstEnabled" style="font-size: 13.5px; cursor: pointer;">
                                    <i class="fas fa-percent text-primary mr-1 me-1"></i> Enable Purchase GST (%)
                                </label>
                                <small class="text-muted d-block mt-1" style="font-size: 11.5px; line-height: 1.35;">
                                    Active hone par Purchase Create aur Edit screen par GST% inline column calculate hoga.
                                </small>
                            </div>
                            <label class="gst-toggle-switch flex-shrink-0">
                                <input type="checkbox" id="modalPurchaseGstEnabled" name="purchase_gst_enabled" value="1" {{ ($purchaseGstEnabled ?? true) ? "checked" : "" }}>
                                <span class="gst-slider"></span>
                            </label>
                        </div>

                        <div class="form-group mb-2">
                            <label class="fw-bold text-secondary mb-1 d-flex align-items-center" style="font-size: 13px;">
                                <i class="fas fa-calculator text-primary mr-1 me-1"></i> Default Purchase GST Rate (%)
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control text-end fw-bold" id="modalPurchaseGstPercent" name="purchase_gst_percent" value="{{ (float)($purchaseGstPercent ?? 18) }}" placeholder="18.00" style="font-size: 15px; height: 42px;">
                                <div class="input-group-append">
                                    <span class="input-group-text bg-light fw-bold text-muted px-3" style="font-size: 14px;">%</span>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11.5px;">Standard default rate e.g. 18% (100 + 18% = 118).</small>
                        </div>
                    </div>
                    <div class="modal-footer px-4 py-3 bg-light border-top d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-secondary px-3" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold" id="btnSavePurchaseSettings">
                            <i class="fas fa-check mr-1 me-1"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')

    <script>
        $(document).ready(function() {

            // Open Purchase Settings Modal on click (Universal BS4 + BS5 compatibility)
            $(document).on("click", "#btnOpenPurchaseSettings", function(e) {
                e.preventDefault();
                $("#purchaseGstSettingModal").modal("show");
            });

            // Close handler
            $(document).on("click", "#purchaseGstSettingModal [data-dismiss=\"modal\"], #purchaseGstSettingModal [data-bs-dismiss=\"modal\"], #purchaseGstSettingModal .close", function() {
                $("#purchaseGstSettingModal").modal("hide");
            });

            // Purchase GST Quick Settings Save Handler
            $("#purchaseGstSettingForm").on("submit", function(e) {
                e.preventDefault();
                const $btn = $("#btnSavePurchaseSettings");
                const origHtml = $btn.html();
                $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

                $.ajax({
                    url: "{{ route('purchase.settings.update') }}",
                    type: "POST",
                    data: $(this).serialize(),
                    headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
                    success: function(res) {
                        $btn.prop("disabled", false).html(origHtml);
                        if (res.success) {
                            $("#purchaseGstSettingModal").modal("hide");
                            if (typeof Swal !== "undefined") {
                                Swal.fire({
                                    icon: "success",
                                    title: "Settings Saved!",
                                    text: res.message || "Purchase GST settings updated.",
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                alert(res.message || "Settings saved!");
                            }
                        }
                    },
                    error: function(xhr) {
                        $btn.prop("disabled", false).html(origHtml);
                        let msg = "Failed to update settings.";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        if (typeof Swal !== "undefined") {
                            Swal.fire("Error", msg, "error");
                        } else {
                            alert(msg);
                        }
                    }
                });
            });

            // Function to initialize DataTable
            function initDataTable() {
                if ($.fn.DataTable.isDataTable('.datanew')) {
                    $('.datanew').DataTable().destroy();
                }
                $('.datanew').DataTable({
                    "pageLength": 10,
                    "order": [],
                    "columnDefs": [
                        { "orderable": false, "targets": 0 }
                    ],
                    "language": {
                        "search": "",
                        "searchPlaceholder": "Search purchases..."
                    },
                    "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                });
            }

            // Initial call
            initDataTable();

            // Submit form via AJAX
            // Quick Filter Logic
            $(document).on('change', '#quick_filter', function() {
                let val = $(this).val();
                let today = new Date();
                let start = new Date();
                let end = new Date();

                if (val === 'daily') {
                    // Start and end are both today
                } else if (val === 'weekly') {
                    // Start is first day of current week (let's use Monday)
                    let day = today.getDay(); // 0 is Sunday, 1 is Monday
                    let diff = today.getDate() - day + (day === 0 ? -6 : 1);
                    start.setDate(diff);
                } else if (val === 'monthly') {
                    // Start is 1st of current month
                    start.setDate(1);
                } else if (val === 'yearly') {
                    // Start is Jan 1st of current year
                    start.setMonth(0, 1);
                } else if (val === 'custom') {
                    return; // Don't change dates for custom
                }

                let pickerFrom = document.getElementById('filter_from_date')._flatpickr;
                let pickerTo = document.getElementById('filter_to_date')._flatpickr;
                if(pickerFrom) pickerFrom.setDate(start);
                else $("#filter_from_date").val(start.toISOString().split('T')[0]);
                
                if(pickerTo) pickerTo.setDate(end);
                else $("#filter_to_date").val(end.toISOString().split('T')[0]);
                
                $('#filterForm').trigger('submit');
            });

            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#btnSearch');
                const origHtml = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Searching...');

                $.ajax({
                    url: '{{ route("Purchase.home") }}',
                    method: 'GET',
                    data: $(this).serialize(),
                    success: function(response) {
                        $btn.prop('disabled', false).html(origHtml);
                        
                        // 1. Destroy DataTable first to release DOM bindings
                        if ($.fn.DataTable.isDataTable('.datanew')) {
                            $('.datanew').DataTable().destroy();
                        }
                        
                        // 2. Safely replace the table body HTML
                        $('#purchaseTableBody').html(response.html);
                        
                        // 3. Re-initialize the DataTable on the new HTML
                        $('.datanew').DataTable({
                            "pageLength": 10,
                            "order": [],
                            "columnDefs": [
                                { "orderable": false, "targets": 0 }
                            ],
                            "language": {
                                "search": "",
                                "searchPlaceholder": "Search purchases..."
                            },
                            "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                                "<'row'<'col-sm-12'tr>>" +
                                "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                        });
                    },
                    error: function(err) {
                        $btn.prop('disabled', false).html(origHtml);
                        Swal.fire('Error', 'Failed to retrieve filtered list.', 'error');
                    }
                });
            });

            // Reset form
            $('#btnReset').on('click', function() {
                $('#filterForm')[0].reset();
                $('#filterForm').trigger('submit');
            });

            // Confirm Purchase Action
            $(document).on('click', '.confirm-purchase-btn', function(e) {
                e.preventDefault();
                let url = $(this).attr('href');

                Swal.fire({
                    title: "Confirm Purchase?",
                    text: "This will finalize the purchase, update stocks, and post ledgers.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#28a745",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "Yes, Confirm it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            method: "GET",
                            success: function(response) {
                                if (response.invoice_url) {
                                    window.open(response.invoice_url, '_blank');
                                }
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Confirmed!',
                                    text: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function(xhr) {
                                let msg = 'Something went wrong.';
                                if (xhr.responseJSON && xhr.responseJSON.message) msg =
                                    xhr.responseJSON.message;
                                Swal.fire('Error', msg, 'error');
                            }
                        });
                    }
                });
            });

            // Delete Confirmation
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                let form = $(this).closest("form");

                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#dc3545",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Helper to get checkboxes across DataTables and DOM
            function getPurchaseCheckboxes() {
                var table = $('#purchase-table');
                if ($.fn.DataTable.isDataTable(table[0])) {
                    var dt = table.DataTable();
                    return dt.$('input.select-purchase-row');
                }
                return table.find('input.select-purchase-row');
            }

            function updateBulkDiscountBar() {
                let $boxes = getPurchaseCheckboxes();
                let $checked = $boxes.filter(':checked');
                let count = $checked.length;
                let total = $boxes.length;

                // Visual row highlighting
                $boxes.closest('tr').removeClass('table-active bg-light');
                $checked.closest('tr').addClass('table-active bg-light');

                if (count > 0) {
                    $('#selected-purchases-count-text').text(count);
                    $('#selected-ratio-text').text(count + ' of ' + total);
                    $('#bulk-discount-bar').removeClass('d-none');
                } else {
                    $('#bulk-discount-bar').addClass('d-none');
                }
            }

            $(document).on('change', '.select-purchase-row', function() {
                updateBulkDiscountBar();
                let $boxes = getPurchaseCheckboxes();
                let allChecked = $boxes.length > 0 && $boxes.length === $boxes.filter(':checked').length;
                $('#selectAllPurchases').prop('checked', allChecked);
            });

            $(document).on('click', '#selectAllPurchases', function(e) {
                e.stopPropagation();
            });

            $(document).on('change', '#selectAllPurchases', function() {
                let isChecked = $(this).is(':checked');
                let $boxes = getPurchaseCheckboxes();
                $boxes.prop('checked', isChecked);
                updateBulkDiscountBar();
            });

            // Cancel / Deselect button functionality
            $(document).on('click', '#btn-cancel-bulk-discount, #btn-minimize-bulk-bar', function() {
                let $boxes = getPurchaseCheckboxes();
                $boxes.prop('checked', false);
                $('#selectAllPurchases').prop('checked', false);
                updateBulkDiscountBar();
            });

            // Discount Type Toggle (Percentage % vs Fixed Rs.)
            let currentDiscountType = 'percentage';
            $(document).on('click', '.btn-discount-type', function() {
                $('.btn-discount-type').removeClass('active btn-primary').addClass('btn-outline-primary');
                $(this).addClass('active btn-primary').removeClass('btn-outline-primary');
                currentDiscountType = $(this).data('type');
                
                if (currentDiscountType === 'fixed') {
                    $('#bulk-discount-label').text('DISCOUNT AMOUNT (RS.)');
                    $('#bulk-discount-addon').text('Rs.');
                    $('#bulk-discount-input').attr({
                        'placeholder': 'Enter discount amount (e.g. 500 for Rs. 500)',
                        'max': ''
                    });
                } else {
                    $('#bulk-discount-label').text('DISCOUNT PERCENTAGE (%)');
                    $('#bulk-discount-addon').text('%');
                    $('#bulk-discount-input').attr({
                        'placeholder': 'Enter discount percentage (e.g. 5 for 5%)',
                        'max': '100'
                    });
                }
            });

            // Recheck on AJAX table redraw
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (settings && settings.url && (settings.url.indexOf('Purchase') !== -1 || settings.url.indexOf('purchase') !== -1)) {
                    $('#selectAllPurchases').prop('checked', false);
                    updateBulkDiscountBar();
                }
            });

            $(document).on('draw.dt', function() {
                updateBulkDiscountBar();
                let $boxes = getPurchaseCheckboxes();
                let allChecked = $boxes.length > 0 && $boxes.length === $boxes.filter(':checked').length;
                $('#selectAllPurchases').prop('checked', allChecked);
            });

            // Save bulk additional discount
            $(document).on('click', '#btn-save-bulk-discount', function() {
                let $boxes = getPurchaseCheckboxes();
                let selectedIds = $boxes.filter(':checked').map(function() {
                    return $(this).val();
                }).get();
                let discountValue = parseFloat($('#bulk-discount-input').val());

                if (selectedIds.length === 0) {
                    Swal.fire('Error', 'Please select at least one purchase.', 'error');
                    return;
                }
                if (isNaN(discountValue) || discountValue < 0 || (currentDiscountType === 'percentage' && discountValue > 100)) {
                    let errMsg = currentDiscountType === 'percentage'
                        ? 'Please enter a valid discount percentage (0 to 100).'
                        : 'Please enter a valid discount amount (0 or more).';
                    Swal.fire('Error', errMsg, 'error');
                    return;
                }

                let btn = $(this);
                let origHtml = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

                $.ajax({
                    url: '{{ route("purchases.bulk-additional-discount") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        purchase_ids: selectedIds,
                        discount_value: discountValue,
                        discount_percentage: discountValue,
                        discount_type: currentDiscountType
                    },
                    success: function(response) {
                        btn.prop('disabled', false).html(origHtml);
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Failed to save discount.', 'error');
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html(origHtml);
                        let msg = 'Failed to save discount.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });
        });
    </script>
@endsection
