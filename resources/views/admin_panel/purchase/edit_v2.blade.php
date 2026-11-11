@extends('admin_panel.layout.app')

@section('content')
   <link href="{{ asset('assets/vendors/bootstrap5/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendors/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/vendors/bootstrap-icons/css/bootstrap-icons.min.css') }}" rel="stylesheet">
    
    <style>
        /* 💎 PREMIUM MODERN ERP THEME FOR TRANSACTION ENTRY 💎 */
        body {
            background-color: #f8fafc;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        /* Containers & Cards */
        .main-container {
            border: 2px solid #475569 !important; /* Bold outer border */
            border-radius: 12px !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05) !important;
            background-color: #ffffff !important;
            padding: 24px !important;
            font-size: .85rem;
            max-width: 99%;
        }
        
        .card-panel {
            background-color: #f8fafc !important;
            border: 2px solid #cbd5e1 !important; /* Bold panel borders */
            border-radius: 10px !important;
            padding: 20px !important;
            height: 100%;
            transition: all 0.2s;
        }
        
        .card-panel:hover {
            border-color: #94a3b8 !important;
        }
        
        .summary-card {
            background-color: #f1f5f9 !important;
            border: 2px solid #cbd5e1 !important; /* Bold summary borders */
            border-radius: 10px !important;
            padding: 20px !important;
        }
        
        /* Bold Section Titles */
        .section-title {
            font-weight: 800 !important;
            text-transform: uppercase;
            font-size: 0.8rem !important;
            letter-spacing: 1px !important;
            color: #1e293b !important;
            margin-bottom: 16px !important;
            border-left: 4px solid #2563eb !important;
            padding-left: 10px !important;
        }
        
        /* Clean inputs with bold borders */
        .form-control,
        .form-select,
        .select2-container--default .select2-selection--single {
            border: 2px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 6px 12px !important;
            font-weight: 500 !important;
            color: #1e293b !important;
            background-color: #ffffff !important;
            transition: all 0.2s ease-in-out !important;
            height: auto !important;
            font-size: 0.85rem !important;
        }
        
        .form-control:focus,
        .form-select:focus,
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15) !important;
            outline: none !important;
        }
        
        /* Read-only fields */
        .input-readonly {
            background-color: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
            color: #475569 !important;
            font-weight: 600 !important;
            cursor: not-allowed !important;
        }
        
        /* Elegant & Bold Buttons */
        .btn-action-primary {
            background-color: #2563eb !important;
            border: 2px solid #1d4ed8 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            border-radius: 8px !important;
            padding: 8px 20px !important;
            transition: all 0.2s;
            font-size: 0.85rem !important;
        }
        .btn-action-primary:hover {
            background-color: #1d4ed8 !important;
            transform: translateY(-1px);
            color: #ffffff !important;
        }
        
        .btn-action-secondary {
            background-color: #ffffff !important;
            border: 2px solid #cbd5e1 !important;
            color: #475569 !important;
            font-weight: 700 !important;
            border-radius: 8px !important;
            padding: 8px 20px !important;
            transition: all 0.2s;
            font-size: 0.85rem !important;
        }
        .btn-action-secondary:hover {
            background-color: #f1f5f9 !important;
            color: #1e293b !important;
        }
        
        /* Transaction Grid / Table */
        .table-responsive {
            border: 1px solid #cbd5e1 !important; /* Elegant outer border */
            border-radius: 8px !important;
            overflow-x: auto !important;
            overflow-y: visible !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
            min-height: 200px;
            background-color: #ffffff;
        }
        
        .sales-table {
            border-collapse: collapse !important;
            margin-bottom: 0 !important;
            min-width: 1000px;
        }
        
        .sales-table thead th {
            background-color: #f8fafc !important; /* Light clean header */
            color: #0f172a !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 11px !important;
            letter-spacing: 0.5px;
            padding: 10px 8px !important;
            border: 1px solid #cbd5e1 !important;
            border-bottom: 2px solid #94a3b8 !important; /* Thick header separator border */
            vertical-align: middle !important;
            text-align: center;
        }

        .sales-table thead th.col-product {
            text-align: left !important;
            padding-left: 12px !important;
        }
        
        .sales-table tbody td {
            border: 1px solid #cbd5e1 !important; /* Flat interior cell borders */
            padding: 0 !important; /* Zero padding to let input fill cell completely */
            background-color: #ffffff;
            vertical-align: middle !important;
        }

        /* ⚡ FLAT BORDERLESS GRID INPUTS ⚡ */
        .sales-table tbody .form-control,
        .sales-table tbody .form-select {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            height: 38px !important; /* Uniform height */
            margin: 0 !important;
            padding: 6px 8px !important;
            width: 100% !important;
            background-color: transparent !important;
            text-align: center; /* Center-align text in grid inputs */
            color: #1e293b !important;
            font-weight: 500 !important;
            font-size: 0.82rem !important;
        }

        .sales-table tbody td.col-product .form-select {
            text-align: left !important;
            padding-left: 12px !important;
        }

        /* Calculations and Read-Only cells get a neat slate tone background */
        .sales-table tbody .input-readonly,
        .sales-table tbody input[readonly],
        .sales-table tbody select[disabled] {
            background-color: #f1f5f9 !important;
            cursor: not-allowed !important;
            color: #475569 !important;
            font-weight: 600 !important;
        }

        /* Subtle focus highlight inside cell */
        .sales-table tbody .form-control:focus,
        .sales-table tbody .form-select:focus {
            outline: none !important;
            background-color: #f8fafc !important;
            box-shadow: inset 0 0 0 2px #2563eb !important;
        }

        /* Select2 Specific flat borderless styling */
        .sales-table tbody .select2-container--default .select2-selection--single {
            height: 38px !important;
            padding: 0 !important;
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            background-color: transparent !important;
            display: flex;
            align-items: center;
        }

        .sales-table tbody .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px !important;
            padding-left: 12px !important;
            padding-right: 20px !important;
            font-size: 0.82rem !important;
            color: #1e293b !important;
            font-weight: 500 !important;
            text-align: left !important;
        }

        .sales-table tbody .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px !important;
            right: 8px !important;
        }

        /* Select2 Focus state */
        .sales-table tbody .select2-container--default.select2-container--focus .select2-selection--single {
            background-color: #f8fafc !important;
            box-shadow: inset 0 0 0 2px #2563eb !important;
        }

        /* Elegant flat block layout for discount input + toggle */
        .sales-table tbody .discount-wrapper {
            display: flex !important;
            align-items: stretch !important;
            width: 100% !important;
            height: 38px !important;
            gap: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .sales-table tbody .discount-wrapper .discount-value {
            flex-grow: 1 !important;
            border: none !important;
            border-radius: 0 !important;
            height: 100% !important;
            text-align: center;
            background-color: transparent !important;
            padding: 6px 8px !important;
        }

        .sales-table tbody .discount-wrapper .discount-toggle {
            border: none !important;
            border-radius: 0 !important;
            background-color: #e2e8f0 !important;
            color: #475569 !important;
            font-weight: 700 !important;
            font-size: 0.75rem !important;
            width: 32px !important;
            min-width: 32px !important;
            height: 100% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0 !important;
            cursor: pointer !important;
            transition: background-color 0.2s !important;
        }

        .sales-table tbody .discount-wrapper .discount-toggle:hover {
            background-color: #cbd5e1 !important;
            color: #0f172a !important;
        }
        
        .sales-table tfoot td {
            background-color: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            border-top: 2px solid #94a3b8 !important; /* Thick tfoot separator */
            padding: 8px 10px !important;
            font-weight: 700 !important;
            color: #0f172a !important;
        }
        
        /* Row hover */
        .sales-table tbody tr:hover td {
            background-color: #f8fafc !important;
        }
        
        /* Column Widths & Layout */
        body {
            overflow-x: hidden !important;
        }

        .main-container {
            border: 2px solid #475569 !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05) !important;
            background-color: #ffffff !important;
            padding: 18px !important;
            font-size: .85rem;
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden !important;
        }

        .table-responsive {
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            overflow-x: hidden !important;
            overflow-y: visible !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03) !important;
            min-height: 150px;
            background-color: #ffffff;
        }

        .sales-table {
            border-collapse: collapse !important;
            margin-bottom: 0 !important;
            width: 100% !important;
            table-layout: auto !important;
        }

        .col-product { width: 36%; }
        .col-unit { width: 10%; text-align: center; }
        .col-qty { width: 12%; text-align: center; }
        .col-price { width: 13%; text-align: right; }
        .col-disc { width: 8%; text-align: right; }
        .col-disc-amt { width: 9%; text-align: right; }
        .col-amount { width: 12%; text-align: right; }
        .col-action { width: 4%; text-align: center; }

        /* Product Search Dropdown */
        .search-results {
            position: absolute;
            background: white;
            border: 2px solid #cbd5e1;
            z-index: 1000;
            max-height: 250px;
            overflow-y: auto;
            width: 100%;
            list-style: none;
            padding: 0;
            margin: 0;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .search-result-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.1s;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover,
        .search-result-item.active {
            background-color: #e2e8f0;
            color: #1e293b;
        }
    </style>

    <div class="container-fluid py-2 px-1">
        <div class="main-container bg-white border shadow-sm mx-auto p-3 rounded-3">

            <div id="alertBox" class="alert d-none mb-3" role="alert"></div>

            <form id="purchaseForm" action="{{ route('store.Purchase') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" id="action" name="action" value="purchase">

                {{-- TOP HEADER & INVOICE / VENDOR CARD --}}
                <div class="card-panel shadow-sm mb-3 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('Purchase.home') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to List
                            </a>
                            <h4 class="header-text text-dark fw-bold mb-0 ms-2">Purchase Entry</h4>
                        </div>
                        <div>
                            <span class="badge bg-light text-secondary border px-3 py-2 fs-6 fw-semibold" id="entryDate">
                                Date: {{ date('d/m/Y') }}
                            </span>
                        </div>
                    </div>

                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label fw-bold mb-1 text-muted small">System No.</label>
                            <input type="text" class="form-control input-readonly" name="invoice_no" value="{{ $nextInvoice ?? 'NEW' }}" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold mb-1 text-muted small">Vendor Inv #</label>
                            <input type="text" class="form-control" name="purchase_order_no" placeholder="Vendor Ref #" value="{{ $purchase->purchase_order_no ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold mb-1 text-muted small">Select Vendor</label>
                            <div class="d-flex align-items-center gap-1">
                                <div class="flex-grow-1">
                                    <select class="form-select select2" id="vendorSelect" name="vendor_id">
                                        <option value="" selected disabled>Select Vendor</option>
                                        @foreach ($Vendor as $v)
                                            <option value="{{ $v->id }}" data-phone="{{ $v->phone }}" data-address="{{ $v->address }}">{{ $v->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addVendorModal" style="padding: 0.38rem 0.75rem;" title="Add New Vendor">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold mb-1 text-muted small">Date</label>
                            <input type="text" name="purchase_date" class="form-control datepicker-custom" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold mb-1 text-muted small">M.Bill / Remarks</label>
                            <input type="text" class="form-control" name="note" id="remarks" placeholder="Optional notes...">
                        </div>
                    </div>

                    <!-- TOP VENDOR DETAILS & HISTORY STRIP -->
                    <div id="vendorInfoCard" class="mt-3 p-2 border rounded-3 bg-light d-none">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 px-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-telephone-fill text-primary"></i>
                                <span class="fw-bold text-muted small">Mobile:</span>
                                <span class="fw-semibold text-dark small" id="vi_mobile">—</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-geo-alt-fill text-primary"></i>
                                <span class="fw-bold text-muted small">Address:</span>
                                <span class="fw-semibold text-dark small" id="vi_address">—</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger-subtle text-danger border border-danger fs-6 px-3 py-1">
                                    Previous Balance: Rs. <span id="vi_prev_bal">0.00</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="warehouse_id" value="{{ $Warehouse->first()->id ?? 1 }}">
                </div>

                {{-- PURCHASE ITEMS (FULL WIDTH) --}}
                <div class="card-panel shadow-sm p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0">Purchase Items</div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-success px-3 shadow-sm" data-toggle="modal" data-target="#quickAddProductModal">
                                <i class="bi bi-plus-circle me-1"></i>Quick Add Product
                            </button>
                            <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm" id="btnAdd">
                                <i class="bi bi-plus-lg"></i> Add Row
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive border rounded-3 bg-white">
                        <table class="table table-bordered sales-table mb-0" id="purchaseTable">
                            <thead>
                                <tr>
                                    <th class="col-product">Product</th>
                                    <th class="col-unit">Unit</th>
                                    <th class="col-qty">Qty</th>
                                    <th class="col-price">Purchase Price</th>
                                    <th class="col-disc">Disc %</th>
                                    <th class="col-disc-amt">Disc Amt</th>
                                    <th class="col-amount">Amount</th>
                                    <th class="col-action">Action</th>
                                </tr>
                            </thead>
                            <tbody id="purchaseTableBody">
                                <!-- Rows added via JS -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-end fw-bold text-muted">Total Amount:</td>
                                    <td class="text-end fw-bold fs-6 text-dark"><span id="totalAmount">0.00</span>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Totals + Summary --}}
                <div class="row g-3 mt-1">
                    <div class="col-lg-7">
                        <div class="card-panel shadow-sm">
                            <div class="section-title mb-3">Payment / Receipt Voucher</div>
                            <div id="paymentWrapper" class="border rounded p-3 bg-light mb-3">
                                <div class="d-flex gap-2 align-items-center mb-2 payment-row flex-wrap">
                                    <select class="form-select rv-account" name="payment_account_id[]"
                                        style="max-width: 300px; flex-grow: 1;">
                                        @foreach ($accounts as $acc)
                                            <option value="{{ $acc->id }}" {{ (str_contains(strtolower($acc->title), 'cash') || $loop->first) ? 'selected' : '' }}>{{ $acc->title }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" class="form-control text-end payment-amount"
                                        name="payment_amount[]" placeholder="Amount" style="width:140px">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddPayment">
                                        <i class="bi bi-plus"></i> Add
                                    </button>
                                </div>
                                <!-- Additional rows will be appended here -->
                            </div>
                            <div class="text-end">
                                <span class="me-2 fw-bold text-muted">Total Paid:</span>
                                <span class="fw-bold fs-6 text-success" id="totalPaid">0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="bg-white shadow-sm rounded-3 p-3 h-100 border">
                            <div class="section-title mb-3">Summary</div>
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="row py-1 align-items-center">
                                    <div class="col-7 text-muted fw-medium">Total Qty</div>
                                    <div class="col-5 text-end"><span id="tQty" class="fw-bold">0</span></div>
                                </div>
                                <div class="row py-1 align-items-center">
                                    <div class="col-7 text-muted fw-medium">Sub-Total</div>
                                    <div class="col-5 text-end fw-bold"><span id="tSub">0.00</span></div>
                                </div>
                                <div class="row py-1 align-items-center">
                                <div class="col-7 text-muted fw-medium">Bill Discount</div>
                                <div class="col-5 text-end d-flex gap-1">
                                    <input type="number" class="form-control text-end form-control-sm"
                                        id="billDiscountPct" placeholder="%" style="width: 70px;" step="0.01">
                                    <input type="number" class="form-control text-end form-control-sm"
                                        id="billDiscount" value="0" step="0.01">
                                    <input type="hidden" name="discount" id="discountInput" value="0">
                                </div>
                            </div>
                                <div class="row py-1 align-items-center">
                                    <div class="col-7 text-muted fw-medium">Extra Cost</div>
                                    <div class="col-5 text-end">
                                        <input type="number" class="form-control text-end form-control-sm"
                                            name="extra_cost" id="extraCost" value="0">
                                    </div>
                                </div>
                                <div class="row py-1 align-items-center">
                                    <div class="col-7 text-danger fw-medium">Previous Balance</div>
                                    <div class="col-5 text-end text-danger fw-bold"><span id="tPrev">0.00</span></div>
                                </div>
                                <hr class="my-2 border-secondary">
                                <div class="row py-2">
                                    <div class="col-6 fw-bold fs-5 text-primary">Current Bill</div>
                                    <div class="col-6 text-end fw-bold fs-5 text-primary"><span id="tPayable">0.00</span></div>
                                </div>
                                <div class="row py-2 bg-warning-subtle rounded-2">
                                    <div class="col-6 fw-bold fs-5 text-dark">Total Payable</div>
                                    <div class="col-6 text-end fw-bold fs-5 text-dark"><span id="tTotalPayable">0.00</span></div>
                                </div>
                                <input type="hidden" name="net_amount" id="netAmountInput" value="0">
                                <input type="hidden" name="subtotal" id="subtotalInput" value="0">
                            </div>
                        </div>
                    </div>
                </div>


                {{-- Buttons --}}
                <div class="d-flex flex-wrap gap-3 justify-content-end p-3 mt-3 border-top bg-light rounded-bottom">
                    <button type="button" class="btn btn-action-secondary"
                        onclick="window.location.reload()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                    {{-- New Save Only Button --}}
                    <button type="button" class="btn btn-action-primary bg-info border-info text-white" id="btnSaveOnly">
                        <i class="bi bi-save me-1"></i> Save Purchase
                    </button>
                    {{-- Existing Submit (Confirm) --}}
                    <button type="button" class="btn btn-action-primary bg-success border-success text-white" id="btnConfirm">
                        <i class="bi bi-check-circle me-1"></i> Confirm Purchase
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Add Vendor Modal -->
    <div class="modal fade" id="addVendorModal" tabindex="-1" aria-labelledby="addVendorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0 pb-2">
                    <h5 class="modal-title fw-bold" id="addVendorModalLabel">Add New Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quickAddVendorForm">
                    @csrf
                    <div class="modal-body pt-2">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Vendor Name</label>
                            <input type="text" class="form-control" name="name" required placeholder="Enter vendor name">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted">Phone Number</label>
                                <input type="text" class="form-control" name="phone" placeholder="Optional">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted">Email Address</label>
                                <input type="email" class="form-control" name="email" placeholder="Optional">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted">CNIC Number</label>
                                <input type="text" class="form-control" name="cnic" placeholder="Optional">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted">Opening Balance</label>
                                <input type="number" step="0.01" class="form-control" name="opening_balance" value="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted">NTN Number</label>
                                <input type="text" class="form-control" name="ntn_number" placeholder="Optional">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted">STRN Number</label>
                                <input type="text" class="form-control" name="strn_number" placeholder="Optional">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold small text-muted">Address</label>
                            <textarea class="form-control" name="address" rows="2" placeholder="Optional"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold" id="btnQuickSaveVendor">Save Vendor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

  <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/select2/js/select2.min.js') }}"></script>

    {{-- Quick Add Product Modal --}}
    @include('admin_panel.partials.quick_add_product_modal')

    <script>
        $(document).ready(function() {
            // Init Select2
            $('.select2').select2({
                width: '100%',
                dropdownParent: $(document.body)
            });

            $(document).on('select2:open', function() {
                setTimeout(function() {
                    const searchInput = document.querySelector('.select2-container--open .select2-search__field');
                    if (searchInput) {
                        searchInput.focus();
                    }
                }, 50);
            });

            // Vendor Select Logic
            $('#vendorSelect').on('change', function() {
                const vendorId = $(this).val();
                if (!vendorId) {
                    $('#vendorInfoCard').addClass('d-none');
                    return;
                }

                // Fetch Vendor Info & Ledger
                $.get(`/vendor/${vendorId}/ledger-json`, function(data) {
                    // Update Info Card
                    $('#vi_mobile').text(data.vendor.phone || '—');
                    $('#vi_address').text(data.vendor.address || '—');
                    $('#vi_prev_bal').text(parseFloat(data.current_balance).toFixed(2));
                    $('#vendorInfoCard').removeClass('d-none');

                    // Update Summary
                    $('#tPrev').text(parseFloat(data.current_balance).toFixed(2));
                    recalcAll();
                });
            });

            // Add First Row
            addBlankRow();

            // Add Row Button
            $('#btnAdd').click(function() {
                addBlankRow();
            });

            // Remove Row
            $(document).on('click', '.remove-row', function() {
                if ($('#purchaseTableBody tr').length > 1) {
                    $(this).closest('tr').remove();
                    recalcAll();
                }
            });

            // Inputs -> Calc
            $('#purchaseTableBody').on('input', '.main-qty-input, .price, .item-disc-percent', function() {
                recalcRow($(this).closest('tr'));
                recalcAll();
            });

            // Summary Inputs
            $('#billDiscount, #billDiscountPct, #extraCost').on('input', function() {
                recalcAll();
            });

            function normalizeDiscountInput() {
                let totalInlineDiscount = 0;
                $('#purchaseTableBody tr').each(function() {
                    const rowDiscAmt = parseFloat($(this).find('.item-disc-amt').val()) || 0;
                    totalInlineDiscount += rowDiscAmt;
                });

                let billDiscVal = parseFloat($('#billDiscount').val());
                if (isNaN(billDiscVal) || billDiscVal < totalInlineDiscount) {
                    $('#billDiscount').val(totalInlineDiscount.toFixed(2));
                }
                recalcAll();
            }

            $('#billDiscount, #billDiscountPct').on('blur', function() {
                normalizeDiscountInput();
            });

            $('#purchaseForm').on('submit', function() {
                normalizeDiscountInput();
            });

            // Payment Row Add
            $('#btnAddPayment').click(function() {
                const html = `
                    <div class="d-flex gap-2 align-items-center mb-2 payment-row flex-wrap">
                        <select class="form-select rv-account" name="payment_account_id[]" style="max-width: 300px; flex-grow: 1;">
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ (str_contains(strtolower($acc->title), 'cash') || $loop->first) ? 'selected' : '' }}>{{ $acc->title }}</option>
                            @endforeach
                        </select>
                        <input type="number" class="form-control text-end payment-amount" name="payment_amount[]" placeholder="Amount" style="width:140px">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-payment">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>`;
                $('#paymentWrapper').append(html);
            });

            $(document).on('click', '.remove-payment', function() {
                $(this).closest('.payment-row').remove();
                calcTotalPaid();
            });

            $(document).on('input', '.payment-amount', function() {
                calcTotalPaid();
            });

            function calcTotalPaid() {
                let total = 0;
                $('.payment-amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#totalPaid').text(total.toFixed(2));
                recalcAll(); // Trigger summary update
            }


            // --- SAVE ONLY AJAX ---
            // --- Submit Logic (AJAX for both Save & Confirm) ---

            // 1. Save (Draft)
            $('#btnSaveOnly').click(function(e) {
                e.preventDefault();
                normalizeDiscountInput();
                let $btn = $(this);
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

                $('#action').val('save_only'); // Set action

                $.ajax({
                    url: "{{ route('store.Purchase') }}",
                    method: "POST",
                    data: $('#purchaseForm').serialize(),
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved!',
                            text: 'Purchase saved as draft successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = "{{ route('Purchase.home') }}";
                        });
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(
                            '<i class="bi bi-save"></i> Save Purchase');
                        let msg = 'Something went wrong.';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON
                            .message;
                        // Validation errors
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            let errors = Object.values(xhr.responseJSON.errors).flat().join(
                                '\n');
                            msg += '\n' + errors;
                        }
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });

            // 2. Confirm (Approved)
            $('#btnConfirm').click(function(e) {
                e.preventDefault();
                normalizeDiscountInput();

                Swal.fire({
                    title: 'Confirm Purchase?',
                    text: "This will update stock and accounts. You cannot revert this directly.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Confirm it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let $btn = $('#btnConfirm');
                        $btn.prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-2"></span>Processing...'
                        );

                        $('#action').val('approved'); // Set action

                        $.ajax({
                            url: "{{ route('store.Purchase') }}",
                            method: "POST",
                            data: $('#purchaseForm').serialize(),
                            success: function(response) {
                                // Open Invoice in New Tab
                                if (response.invoice_url) {
                                    window.open(response.invoice_url, '_blank');
                                }

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Confirmed!',
                                    text: 'Purchase confirmed and processed successfully.',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = response
                                        .redirect_url ||
                                        "{{ route('Purchase.home') }}";
                                });
                            },
                            error: function(xhr) {
                                $btn.prop('disabled', false).html(
                                    '<i class="bi bi-check-circle"></i> Confirm Purchase'
                                );
                                let msg = 'Something went wrong.';
                                if (xhr.responseJSON && xhr.responseJSON.message) msg =
                                    xhr.responseJSON.message;
                                if (xhr.responseJSON && xhr.responseJSON.errors) {
                                    let errors = Object.values(xhr.responseJSON.errors)
                                        .flat().join('\n');
                                    msg += '\n' + errors;
                                }
                                Swal.fire('Error', msg, 'error');
                            }
                        });
                    }
                });
            });

            // --- QUICK ADD VENDOR AJAX ---
            $('#quickAddVendorForm').on('submit', function(e) {
                e.preventDefault();
                let $btn = $('#btnQuickSaveVendor');
                let originalText = $btn.text();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

                $.ajax({
                    url: "{{ route('vendors.store.ajax') }}",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        $btn.prop('disabled', false).text(originalText);
                        
                        let vendorId = null;
                        let vendorName = $('#quickAddVendorForm input[name="name"]').val();
                        
                        if (response.vendor && response.vendor.id) {
                            vendorId = response.vendor.id;
                            vendorName = response.vendor.name;
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Vendor Added',
                            text: 'The vendor has been created successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            $('#addVendorModal').modal('hide');
                            $('#quickAddVendorForm')[0].reset();
                            
                            if (vendorId) {
                                let newOption = new Option(vendorName, vendorId, false, true);
                                $('#vendorSelect').append(newOption).trigger('change');
                            } else {
                                window.location.reload();
                            }
                        });
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).text(originalText);
                        let msg = 'Error adding vendor.';
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                        }
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });

            function addBlankRow() {
                const html = `
                <tr>
                    <td>
                        <select class="form-select product-select2" name="product_id[]"></select>
                        <!-- Hidden fields for product data snapshot -->
                        <input type="hidden" name="size_mode[]" class="hidden-size-mode" value="">
                        <input type="hidden" name="pieces_per_box[]" class="hidden-pieces-per-box" value="1">
                        <input type="hidden" name="pieces_per_m2[]" class="hidden-pieces-per-m2" value="0">
                        <input type="hidden" name="price_per_carton[]" class="hidden-price-per-carton" value="0">
                        <input type="hidden" name="boxes_qty[]" class="hidden-boxes-qty" value="0">
                        <input type="hidden" name="loose_qty[]" class="hidden-loose-qty" value="0">
                        <input type="hidden" name="length[]" class="hidden-length" value="">
                        <input type="hidden" name="width[]" class="hidden-width" value="">
                        <input type="hidden" name="color[]" class="hidden-variant-data" value="">
                    </td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-outline-info fw-bold unit-toggle-btn py-0 px-2" data-unit="Pcs" title="Click to toggle unit (Carton ↔ Pcs / Kg ↔ Gm)" style="font-size:0.75rem; min-width: 55px; cursor: pointer;">Pcs</button>
                        <input type="hidden" name="unit[]" class="unit-input-val" value="Pcs">
                    </td>
                    <td>
                        <input type="number" step="any" min="0.01" name="qty[]" class="form-control text-center main-qty-input" value="1" placeholder="Qty">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="price[]" class="form-control text-end price" value="0">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="item_discount[]" class="form-control text-end item-disc-percent" value="0">
                    </td>
                    <td>
                        <input type="number" class="form-control text-end input-readonly item-disc-amt" value="0.00" readonly>
                    </td>
                    <td>
                        <input type="number" class="form-control text-end input-readonly row-total" value="0.00" readonly>
                    </td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x-lg"></i></button>
                    </td>
                </tr>
                `;
                const $row = $(html);
                $('#purchaseTableBody').append($row);
                initProductSelect2($row.find('.product-select2'));
            }

            function initProductSelect2($el) {
                if (!$el || !$el.length) return;
                if ($el.hasClass('select2-hidden-accessible')) {
                    try { $el.select2('destroy'); } catch(e) {}
                }
                $el.select2({
                    placeholder: 'Search Product (Name / SKU / Barcode)',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(document.body),
                    ajax: {
                        url: '{{ route('products.ajax.search') }}',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term,
                                page: params.page || 1
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.results || [],
                                pagination: {
                                    more: (data.pagination && data.pagination.more) ? true : false
                                }
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 0,
                    escapeMarkup: function(markup) {
                        return markup;
                    },
                    templateResult: formatProduct,
                    templateSelection: formatSelection
                });

                $el.off('select2:open').on('select2:open', function() {
                    setTimeout(function() {
                        const searchInput = document.querySelector('.select2-container--open .select2-search__field');
                        if (searchInput) {
                            searchInput.focus();
                        }
                    }, 50);
                });

                $el.on('select2:select', function(e) {
                    const data = e.params.data;
                    const $row = $(this).closest('tr');

                    let unitName = data.unit_name || 'Pcs';
                    const ppb = parseFloat(data.pieces_per_box) || 1;
                    const isCartonMode = (data.size_mode === 'by_cartons' || unitName.toLowerCase() === 'carton' || unitName.toLowerCase() === 'ctn' || ppb > 1);

                    // Dynamic Unit & Style
                    if (isCartonMode) {
                        unitName = (data.size_mode === 'by_bandal') ? 'Bundal' : 'Carton';
                        $row.find('.unit-toggle-btn')
                            .removeClass('btn-outline-primary btn-outline-info')
                            .addClass('btn-outline-success')
                            .attr('data-unit', unitName)
                            .text(unitName);
                        $row.find('.unit-input-val').val(unitName);
                    } else if (data.size_mode === 'by_kg' || data.size_mode === 'by_gm') {
                        unitName = 'Kg';
                        $row.find('.unit-toggle-btn')
                            .removeClass('btn-outline-info btn-outline-success')
                            .addClass('btn-outline-primary')
                            .attr('data-unit', 'Kg')
                            .text('Kg');
                        $row.find('.unit-input-val').val('Kg');
                    } else {
                        $row.find('.unit-toggle-btn')
                            .removeClass('btn-outline-primary btn-outline-success')
                            .addClass('btn-outline-info')
                            .attr('data-unit', unitName)
                            .text(unitName);
                        $row.find('.unit-input-val').val(unitName);
                    }

                    // Prices
                    const pPiece = parseFloat(data.purchase_price_per_piece) || parseFloat(data.trade_price) || 0;
                    const pBox = parseFloat(data.purchase_price_per_box) || (pPiece * ppb);
                    const pM2 = parseFloat(data.purchase_price_per_m2) || 0;
                    const sizeMode = data.size_mode || 'std';

                    // Snapshot Data Population
                    $row.find('.hidden-size-mode').val(data.size_mode || '');
                    $row.find('.hidden-pieces-per-box').val(ppb);
                    $row.find('.hidden-pieces-per-m2').val(data.pieces_per_m2 || 0);
                    $row.find('.hidden-price-per-carton').val(pBox);
                    $row.find('.hidden-length').val(data.length || '');
                    $row.find('.hidden-width').val(data.width || '');
                    $row.find('.hidden-variant-data').val(data.variant_data || '');

                    $row.data('sizemode', data.size_mode);
                    $row.data('pieces_per_m2', Number(data.pieces_per_m2) || 0);
                    $row.data('p_price_piece', pPiece);
                    $row.data('p_price_box', pBox);
                    $row.data('pieces_per_box', ppb);

                    // Discount
                    $row.find('.item-disc-percent').val(data.purchase_discount_percent || 0);

                    // Determine initial price based on selected unit mode
                    let finalPrice = pPiece;
                    if (sizeMode === 'by_size') {
                        finalPrice = pM2;
                    } else if (isCartonMode) {
                        finalPrice = pBox > 0 ? pBox : (pPiece * ppb);
                    } else {
                        finalPrice = pPiece;
                    }

                    $row.find('.price').val(finalPrice % 1 === 0 ? finalPrice : finalPrice.toFixed(2));
                    $row.find('.main-qty-input').focus().select();

                    recalcRow($row);
                    recalcAll();
                });
            }

            function formatProduct(repo) {
                if (repo.loading) return repo.text;
                let name = repo.name || repo.text || '';
                let sku = repo.sku || 'N/A';
                let unit = repo.unit_name || 'Pcs';
                let stock = repo.stock !== undefined ? repo.stock : 0;
                let stockVal = parseFloat(repo.stock_pieces !== undefined ? repo.stock_pieces : repo.stock) || 0;

                if (repo.element) {
                    const $el = $(repo.element);
                    if ($el.val() === '') return repo.text;
                    sku = $el.data('sku') || sku;
                    stock = $el.data('stock') !== undefined ? $el.data('stock') : stock;
                    stockVal = parseFloat(stock) || 0;
                    name = $el.data('name') || name || $el.text();
                }

                let badgeClass = stockVal > 0 ? 'bg-success' : 'bg-danger';

                return '<div class="d-flex align-items-center justify-content-between w-100 py-1" style="color: #0f172a;">' +
                    '<div>' +
                        '<div class="fw-bold" style="color: #0f172a;">' + name + '</div>' +
                        '<small class="text-muted" style="font-size: 11px;">SKU: ' + sku + ' | Unit: ' + unit + '</small>' +
                    '</div>' +
                    '<div>' +
                        '<span class="badge ' + badgeClass + ' rounded-pill px-2 py-1">Stock: ' + stock + '</span>' +
                    '</div>' +
                '</div>';
            }

            function formatSelection(repo) {
                if (repo.element) {
                    const $el = $(repo.element);
                    if (!$el.val()) return repo.text || '';
                    const name = $el.data('name') || repo.name || repo.text || '';
                    const sku = $el.data('sku') || repo.sku || '';
                    return sku ? (name + ' (SKU: ' + sku + ')') : name;
                }
                return repo.name || repo.text;
            }

            // Unit Toggle Handler (Carton ↔ Pcs / Kg ↔ Gm)
            $(document).on('click', '.unit-toggle-btn', function() {
                const $btn = $(this);
                const $row = $btn.closest('tr');
                const sizeMode = $row.data('sizemode') || $row.find('.hidden-size-mode').val();
                const packQty = parseFloat($row.find('.hidden-pieces-per-box').val()) || parseFloat($row.data('pieces_per_box')) || 1;
                let currentUnit = ($btn.attr('data-unit') || $btn.text() || '').trim();
                const $priceInp = $row.find('.price');
                let curPrice = parseFloat($priceInp.val()) || 0;

                const isCartonOrPcs = (sizeMode === 'by_cartons' || packQty > 1 || ['carton', 'ctn', 'pcs', 'pc', 'piece'].includes(currentUnit.toLowerCase()));

                if (isCartonOrPcs) {
                    if (['carton', 'ctn', 'bandal', 'bundal', 'bndl'].includes(currentUnit.toLowerCase())) {
                        // Switch from Carton to Pcs
                        currentUnit = 'Pcs';
                        $btn.text('Pcs')
                            .removeClass('btn-outline-success btn-outline-primary')
                            .addClass('btn-outline-info')
                            .attr('data-unit', 'Pcs');
                        $row.find('.unit-input-val').val('Pcs');

                        if (packQty > 1 && curPrice > 0) {
                            let piecePrice = curPrice / packQty;
                            $priceInp.val(piecePrice % 1 === 0 ? piecePrice : piecePrice.toFixed(2));
                        }
                    } else {
                        // Switch from Pcs to Carton
                        currentUnit = (sizeMode === 'by_bandal') ? 'Bundal' : 'Carton';
                        $btn.text(currentUnit)
                            .removeClass('btn-outline-info btn-outline-primary')
                            .addClass('btn-outline-success')
                            .attr('data-unit', currentUnit);
                        $row.find('.unit-input-val').val(currentUnit);

                        if (packQty > 1 && curPrice > 0) {
                            let cartonPrice = curPrice * packQty;
                            $priceInp.val(cartonPrice % 1 === 0 ? cartonPrice : cartonPrice.toFixed(2));
                        }
                    }
                    recalcRow($row);
                    recalcAll();
                } else if (sizeMode === 'by_kg' || sizeMode === 'by_gm') {
                    if (currentUnit.toLowerCase() === 'kg') {
                        currentUnit = 'Gm';
                        $btn.text('Gm').removeClass('btn-outline-primary').addClass('btn-outline-info').attr('data-unit', 'Gm');
                    } else {
                        currentUnit = 'Kg';
                        $btn.text('Kg').removeClass('btn-outline-info').addClass('btn-outline-primary').attr('data-unit', 'Kg');
                    }
                    $row.find('.unit-input-val').val(currentUnit);
                    recalcRow($row);
                    recalcAll();
                }
            });

            function recalcRow($row) {
                const qtyStr = ($row.find('.main-qty-input').val() || '').toString();
                const qty = parseFloat(qtyStr) || 0;
                const price = parseFloat($row.find('.price').val()) || 0;
                const discPct = parseFloat($row.find('.item-disc-percent').val()) || 0;
                const sizeMode = $row.data('sizemode') || $row.find('.hidden-size-mode').val();
                const unitVal = ($row.find('.unit-input-val').val() || '').toLowerCase();
                const pieces_per_m2 = parseFloat($row.data('pieces_per_m2')) || 0;
                const ppb = parseFloat($row.find('.hidden-pieces-per-box').val()) || parseFloat($row.data('pieces_per_box')) || 1;

                let gross = 0;
                const isPiece = (unitVal === 'pcs' || unitVal === 'pc' || unitVal === 'piece');
                const isCarton = (unitVal === 'carton' || unitVal === 'ctn' || unitVal === 'box' || (!isPiece && sizeMode === 'by_cartons'));

                if (sizeMode === 'by_size') {
                    gross = (pieces_per_m2 || 1) * qty * price;
                } else if (unitVal === 'gm' || unitVal === 'g') {
                    gross = (qty / 1000.0) * price;
                } else if (isPiece) {
                    gross = qty * price;
                    const bQty = ppb > 1 ? Math.floor(qty / ppb) : 0;
                    $row.find('.hidden-boxes-qty').val(bQty);
                    $row.find('.hidden-loose-qty').val(qty);
                } else if (isCarton) {
                    let s = qtyStr.trim();
                    if (s.startsWith('.')) s = '0' + s;
                    if (s.includes('.')) {
                        const parts = s.split('.');
                        const boxes = parseInt(parts[0]) || 0;
                        const loose = parseInt(parts[1]) || 0;
                        const piecePrice = ppb > 0 ? (price / ppb) : price;
                        gross = (boxes * price) + (loose * piecePrice);
                        $row.find('.hidden-boxes-qty').val(boxes);
                        $row.find('.hidden-loose-qty').val(loose);
                    } else {
                        const boxes = parseInt(s) || 0;
                        gross = boxes * price;
                        $row.find('.hidden-boxes-qty').val(boxes);
                        $row.find('.hidden-loose-qty').val(0);
                    }
                } else {
                    gross = qty * price;
                }

                const discAmt = gross * (discPct / 100);
                const lineTotal = Math.max(0, gross - discAmt);

                $row.find('.item-disc-amt').val(discAmt.toFixed(2));
                $row.find('.row-total').val(lineTotal.toFixed(2));
            }

            function recalcAll() {
                let totalQty = 0;
                let subtotal = 0;
                let totalInlineDiscount = 0;

                $('#purchaseTableBody tr').each(function() {
                    const qty = parseFloat($(this).find('.main-qty-input').val()) || 0;
                    const total = parseFloat($(this).find('.row-total').val()) || 0;
                    const rowDiscAmt = parseFloat($(this).find('.item-disc-amt').val()) || 0;

                    totalQty += qty;
                    subtotal += total;
                    totalInlineDiscount += rowDiscAmt;
                });

                const grossSubtotal = subtotal + totalInlineDiscount;

                $('#tQty').text(totalQty.toFixed(2));
                $('#tSub').text(subtotal.toFixed(2));
                $('#subtotalInput').val(subtotal.toFixed(2));

                let additionalDiscount = parseFloat($('#discountInput').val()) || 0;
                let billDiscVal = parseFloat($('#billDiscount').val());

                if ($(document.activeElement).is('#billDiscount') || $(document.activeElement).is('#billDiscountPct')) {
                    if ($(document.activeElement).is('#billDiscountPct')) {
                        const pct = parseFloat($('#billDiscountPct').val()) || 0;
                        billDiscVal = grossSubtotal * (pct / 100);
                        $('#billDiscount').val(billDiscVal.toFixed(2));
                    }
                    if (!isNaN(billDiscVal)) {
                        additionalDiscount = Math.max(0, billDiscVal - totalInlineDiscount);
                    } else {
                        additionalDiscount = 0;
                    }
                } else {
                    billDiscVal = totalInlineDiscount + additionalDiscount;
                    $('#billDiscount').val(billDiscVal.toFixed(2));
                }
                
                const pct = grossSubtotal > 0 ? (billDiscVal / grossSubtotal) * 100 : 0;
                $('#billDiscountPct').val(pct.toFixed(2));
                $('#discountInput').val(additionalDiscount.toFixed(2));

                const extraCost = parseFloat($('#extraCost').val()) || 0;
                const net = subtotal - additionalDiscount + extraCost;

                $('#tPayable').text(net.toFixed(2));
                $('#netAmountInput').val(net.toFixed(2));
                $('#totalAmount').text(subtotal.toFixed(2));

                const prevBal = parseFloat($('#tPrev').text()) || 0;
                const totalPaid = parseFloat($('#totalPaid').text()) || 0;
                const totalPayable = (prevBal + net) - totalPaid;
                
                $('#tTotalPayable').text(totalPayable.toFixed(2));
            }
        });
    </script>
@endsection
