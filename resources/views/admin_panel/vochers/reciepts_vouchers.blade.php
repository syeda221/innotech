@extends('admin_panel.layout.app')
@section('content')
 <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/css/bootstrap-icons.min.css') }}">

   
    <style>
        :root {
            --rv-primary: #4f46e5;
            --rv-bg: #f8fafc;
            --rv-border: #e2e8f0;
            --rv-text: #1e293b;
            --rv-muted: #64748b;
        }

        .rv-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -1px rgba(0,0,0,0.04);
            border: 1px solid var(--rv-border);
            padding: 24px;
        }

        .rv-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--rv-border);
        }

        .rv-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--rv-text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rv-title i {
            color: var(--rv-primary);
            background: #e0e7ff;
            padding: 8px 10px;
            border-radius: 10px;
        }

        .rv-section-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--rv-primary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 16px 0 8px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .rv-section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--rv-border);
        }

        .rv-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--rv-muted);
            margin-bottom: 4px;
        }

        .rv-input {
            background: #fff;
            border: 1px solid var(--rv-border);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.9rem;
            color: var(--rv-text);
            transition: all 0.2s ease;
            width: 100%;
        }
        .rv-input:focus {
            border-color: var(--rv-primary);
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
            outline: none;
        }
        .rv-input::placeholder { color: #cbd5e1; }

        select.rv-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .rv-table {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--rv-border);
        }
        .rv-table thead th {
            background: #1e293b;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 10px 12px;
            text-align: center;
            border: none;
        }
        .rv-table tbody td {
            padding: 8px 10px;
            vertical-align: middle;
            border-color: var(--rv-border);
        }
        .rv-table tfoot td {
            padding: 10px 12px;
            background: #f1f5f9;
            font-weight: 700;
        }

        .btn-rv-primary {
            background: var(--rv-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 28px;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(79,70,229,0.3);
            transition: all 0.2s;
        }
        .btn-rv-primary:hover {
            background: #4338ca;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(79,70,229,0.4);
        }

        .btn-rv-secondary {
            background: #f1f5f9;
            color: var(--rv-text);
            border: 1px solid var(--rv-border);
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-rv-secondary:hover { background: #e2e8f0; color: var(--rv-text); }

        .btn-rv-add {
            background: #ecfdf5;
            color: #059669;
            border: 1px dashed #10b981;
            border-radius: 8px;
            padding: 6px 16px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .btn-rv-add:hover { background: #d1fae5; }

        .balance-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
        }
        .balance-dr { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }
        .balance-cr { background: #f0fdf4; color: #16a34a; border: 1px solid #86efac; }

        /* Select2 RV Custom Styling */
        .select2-container--default .select2-selection--single {
            background-color: #fff !important;
            border: 1px solid var(--rv-border) !important;
            border-radius: 8px !important;
            height: 38px !important;
            padding: 4px 8px !important;
            font-size: 0.9rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
            color: var(--rv-text) !important;
            padding-left: 2px !important;
            font-weight: 600;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 8px !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--rv-primary) !important;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1) !important;
            outline: none !important;
        }
        .select2-dropdown {
            border: 1px solid var(--rv-border) !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1) !important;
            z-index: 9999 !important;
        }
        .select2-search--dropdown .select2-search__field {
            border: 1px solid var(--rv-border) !important;
            border-radius: 6px !important;
            padding: 6px 10px !important;
            outline: none !important;
        }

        /* Disable number input spin buttons / up-down arrows */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }
        input[type=number] { 
            -moz-appearance: textfield; 
            appearance: textfield;
        }

        /* Premium Cheque Details Box inside Table */
        .cheque-details-row td {
            background: #f8fafc !important;
            padding: 10px 14px !important;
            border-top: none !important;
        }

        .chq-card-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-left: 4px solid var(--rv-primary) !important;
            border-radius: 12px;
            padding: 14px 18px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
            transition: all 0.2s ease;
        }

        .chq-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
            flex-wrap: wrap;
            gap: 8px;
        }

        .chq-card-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chq-card-title .icon-holder {
            width: 32px;
            height: 32px;
            background: #e0e7ff;
            color: var(--rv-primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .chq-badge-status {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            font-size: 0.73rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .chq-badge-alert {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            font-size: 0.73rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .chq-field-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .chq-input-group {
            display: flex;
            align-items: stretch;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            transition: all 0.2s ease;
            overflow: hidden;
        }

        .chq-input-group:focus-within {
            border-color: var(--rv-primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        .chq-input-group .input-icon {
            padding: 8px 12px;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .chq-input-group input {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            padding: 8px 12px;
            font-size: 0.9rem;
            color: #1e293b;
            width: 100%;
            background: transparent;
        }

        .chq-input-group input::placeholder {
            color: #94a3b8;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner" style="padding: 10px;">
            <div class="container-fluid p-0">

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 10px;">
                        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('store_rec_vochers') }}" method="POST" id="receiptForm">
                    @csrf

                    <div class="rv-card">
                        <!-- Header -->
                        <div class="rv-header">
                            <div class="rv-title">
                                <i class="bi bi-receipt-cutoff"></i> Receipt Voucher
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('all_recepit_vochers') }}" class="btn btn-rv-secondary">
                                    <i class="bi bi-list-ul me-1"></i> All Vouchers
                                </a>
                            </div>
                        </div>

                        <!-- Row 1: Voucher Info -->
                        <div class="rv-section-label">Voucher Details</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-2">
                                <label class="rv-label">RVID</label>
                                <input type="text" class="rv-input" style="background: #f1f5f9;" name="rvid"
                                    value="{{ $nextRvid }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="rv-label">Receipt Date</label>
                                <input type="date" name="receipt_date" class="rv-input"
                                    value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-2">
                                <label class="rv-label">Entry Date</label>
                                <input type="date" name="entry_date" class="rv-input"
                                    value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-6">
                                <label class="rv-label">Remarks <small class="text-muted fw-normal">(Optional)</small></label>
                                <input type="text" name="remarks" class="rv-input" id="remarks" placeholder="Auto-generated if left blank">
                            </div>
                        </div>

                        <!-- Row 2: Party Selection -->
                        <div class="rv-section-label">Received From</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="rv-label">Type</label>
                                <select name="vendor_type" class="rv-input" id="partyType">
                                    <option value="customer" selected>Customer</option>
                                    <option value="walkin">Walk-in</option>
                                    <option value="vendor">Vendor</option>/
                                    @foreach ($AccountHeads as $head)
                                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="rv-label">Party / Account</label>
                                <select name="vendor_id" class="rv-input" id="partyId">
                                    <option disabled selected>Select Party</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="rv-label">Mobile</label>
                                <input type="text" name="tel" id="tel" class="rv-input" style="background: #f1f5f9;" readonly>
                            </div>
                             <div class="col-2">
        <label class="rv-label">Current Balance</label>
        <div id="balanceDisplay" class="balance-badge balance-dr p-2 text-center" style="width:100%;">0.00 Dr</div>
        <input type="hidden" id="openingBal">
    </div>
                        </div>

                        <!-- Invoice Settlement Section (Auto FIFO / Manual) -->
                        <div id="invoiceSettlementSection" class="mb-4" style="display: none;">
                            <div class="card border-0 shadow-sm" style="background: #ffffff; border: 1.5px solid #cbd5e1 !important; border-radius: 12px; overflow: hidden;">
                                <div class="card-header bg-white py-3 px-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 34px; height: 34px; background: #e0e7ff; color: #4338ca; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                                            <i class="bi bi-receipt"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">Outstanding Sales Invoices Settlement</span>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2" id="invCountBadge">0 Invoices</span>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" id="invTotalDueBadge">Total Due: Rs. 0.00</span>
                                        </div>
                                    </div>
                                    
                                    <!-- 2 Mode Options -->
                                    <div class="d-flex align-items-center gap-2 bg-light p-1 rounded-3 border">
                                        <span class="text-muted small fw-bold px-2"><i class="bi bi-sliders me-1"></i> Allocation Mode:</span>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="allocation_mode" id="allocModeFifo" value="fifo" checked autocomplete="off">
                                            <label class="btn btn-outline-primary fw-semibold px-3" for="allocModeFifo" title="Automatically distributes receipt amount to oldest invoices first (FIFO)">
                                                <i class="bi bi-lightning-charge-fill me-1 text-warning"></i> Auto FIFO
                                            </label>

                                            <input type="radio" class="btn-check" name="allocation_mode" id="allocModeManual" value="manual" autocomplete="off">
                                            <label class="btn btn-outline-primary fw-semibold px-3" for="allocModeManual" title="Select specific invoices and customize receipt allocation amount">
                                                <i class="bi bi-check2-square me-1"></i> Manual Selection
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0" id="invoicesAllocationTable" style="font-size: 0.88rem;">
                                            <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                                <tr>
                                                    <th class="text-center" style="width: 45px;">
                                                        <input type="checkbox" id="selectAllInvoices" class="form-check-input" style="cursor: pointer;" title="Select / Deselect All">
                                                    </th>
                                                    <th>Invoice #</th>
                                                    <th>Date</th>
                                                    <th class="text-end">Total Bill</th>
                                                    <th class="text-end">Already Paid</th>
                                                    <th class="text-end text-danger fw-bold">Remaining Due</th>
                                                    <th class="text-end" style="width: 170px;">This Receipt (Rs.)</th>
                                                    <th class="text-end text-muted">New Balance</th>
                                                </tr>
                                            </thead>
                                            <tbody id="invoicesTableBody">
                                                <!-- Dynamic rows populated via AJAX -->
                                            </tbody>
                                            <tfoot class="table-light fw-bold" style="border-top: 2px solid #e2e8f0;">
                                                <tr>
                                                    <td colspan="5" class="text-end">Total Settled Invoices:</td>
                                                    <td class="text-end text-danger font-monospace" id="tableTotalDueSum">Rs. 0.00</td>
                                                    <td class="text-end font-monospace text-success fw-bold" id="tableTotalAllocatedSum" style="font-size: 0.95rem;">Rs. 0.00</td>
                                                    <td class="text-end font-monospace text-muted" id="tableTotalRemainingSum">Rs. 0.00</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <div id="noInvoicesNotice" class="p-3 text-center text-muted" style="display: none;">
                                        <i class="bi bi-info-circle me-1 text-primary"></i> No unpaid invoices found for this customer. Payment will be credited directly to customer ledger.
                                    </div>
                                </div>
                                <div class="card-footer bg-light py-2 px-3 border-top d-flex justify-content-between align-items-center" id="invoiceSettlementFooter">
                                    <small class="text-muted" id="allocationModeHint">
                                        <i class="bi bi-info-circle me-1 text-primary"></i> <strong>Auto FIFO Mode:</strong> Entered receipt amount is automatically distributed to oldest unpaid sales first.
                                    </small>
                                    <button type="button" class="btn btn-sm btn-outline-success fw-bold" id="btnSyncToReceiptAmount" style="display: none;">
                                        <i class="bi bi-arrow-repeat me-1"></i> Apply Settled Total to Amount
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Rows -->
                        <div class="rv-section-label">Payment Details</div>
                        <div class="rv-table">
                            <table class="table table-bordered align-middle mb-0" id="voucherTable">
                                <thead>
                                    <tr>
                                        <th style="width: 28%;">Account Head</th>
                                        <th style="width: 32%;">Account</th>
                                        <th style="width: 24%;">Amount</th>
                                        <th style="width: 16%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="voucher-row">
                                        <td>
                                            <select name="row_account_head[]" class="rv-input rowAccountHead">
                                                <option value="">Select Head</option>
                                                @foreach ($AccountHeads as $head)
                                                    <option value="{{ $head->id }}">{{ $head->name }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="row_cheque_no[]" class="row-cheque-no" value="">
                                            <input type="hidden" name="row_cheque_bank[]" class="row-cheque-bank" value="">
                                            <input type="hidden" name="row_cheque_date[]" class="row-cheque-date" value="">
                                        </td>
                                        <td>
                                            <select name="row_account_id[]" class="rv-input rowAccountSub">
                                                <option disabled selected>Select Account</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="amount[]"
                                                class="rv-input text-end fw-bold amount" placeholder="0.00"
                                                style="font-size: 1rem;">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm removeRow" title="Remove">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="cheque-details-row" style="display: none;">
                                        <td colspan="4" class="border-top-0">
                                            <div class="chq-card-box">
                                                <div class="chq-card-header">
                                                    <div class="chq-card-title">
                                                        <span class="icon-holder">
                                                            <i class="bi bi-credit-card-2-front"></i>
                                                        </span>
                                                        <div>
                                                            <span>Cheque Receiving Details</span>
                                                            <span class="chq-badge-status ms-2">
                                                                <i class="bi bi-check2-circle"></i> Auto-saved to Cheque Management
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span class="chq-badge-alert">
                                                            <i class="bi bi-bell"></i> 3-Day & Due Date Alerts Active
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="chq-field-label">
                                                            <i class="bi bi-hash text-primary"></i> Cheque Number <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="chq-input-group">
                                                            <span class="input-icon"><i class="bi bi-upc-scan"></i></span>
                                                            <input type="text" class="chq-no-field" placeholder="e.g. 00482910">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="chq-field-label">
                                                            <i class="bi bi-bank text-primary"></i> Customer Bank Name <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="chq-input-group">
                                                            <span class="input-icon"><i class="bi bi-building"></i></span>
                                                            <input type="text" class="chq-bank-field" placeholder="e.g. Meezan Bank, HBL, MCB">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="chq-field-label">
                                                            <i class="bi bi-calendar-event text-primary"></i> Cheque / Due Date (PDC) <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="chq-input-group">
                                                            <span class="input-icon"><i class="bi bi-calendar3"></i></span>
                                                            <input type="date" class="chq-date-field" value="{{ now()->toDateString() }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold" style="font-size: 1rem; vertical-align: middle;">Total Amount:</td>
                                        <td style="vertical-align: middle;">
                                            <input type="text" name="total_amount"
                                                class="rv-input text-end fw-bold" id="totalAmount" readonly
                                                value="0.00" style="background: #f0fdf4; border-color: #86efac; font-size: 1.1rem; color: #16a34a;">
                                        </td>
                                        <td class="text-center p-1" style="vertical-align: middle;">
                                            <button type="submit" class="btn btn-rv-primary w-100 py-2 px-2 fw-bold d-flex align-items-center justify-content-center" id="btnSavePrint" style="font-size: 0.88rem; white-space: nowrap; height: 38px; border-radius: 8px;">
                                                <i class="bi bi-printer me-1"></i> Save (Print)
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <button type="button" class="btn btn-rv-add mt-3" id="addNewRow">
                            <i class="bi bi-plus-circle me-1"></i> Add Another Account
                        </button>

                        {{-- Hidden fields for backward compatibility --}}
                        <input type="hidden" name="narration_id[]" value="">
                        <input type="hidden" name="narration_text[]" value="">
                        <input type="hidden" name="reference_no[]" value="">
                        <input type="hidden" name="discount_value[]" value="0">
                        <input type="hidden" name="rate[]" value="0">

                    </div><!-- /rv-card -->

                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {

            // Initialize Searchable Party Select2
            function initPartySelect2() {
                $('#partyId').select2({
                    placeholder: 'Search by Name or Code...',
                    allowClear: true,
                    width: '100%',
                    minimumInputLength: 0,
                    ajax: {
                        url: '{{ route("party.list") }}',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                type: $('#partyType').val(),
                                search: params.term || ''
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.map(function(item) {
                                    return {
                                        id: item.id,
                                        text: item.text,
                                        party: item
                                    };
                                })
                            };
                        },
                        cache: false
                    },
                    templateResult: function(item) {
                        if (item.loading) return item.text;
                        if (!item.party) return item.text;
                        const p = item.party;
                        const code = p.customer_id || p.account_code || '';
                        const name = p.customer_name || p.name || p.title || item.text;
                        const bal = p.closing_balance !== undefined ? parseFloat(p.closing_balance) : 0;
                        const balFormatted = Math.abs(bal).toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2});
                        const balType = bal >= 0 ? (p.customer_name ? 'Dr' : 'Cr') : (p.customer_name ? 'Cr' : 'Dr');
                        
                        return $(`<div class="d-flex justify-content-between align-items-center py-1">
                            <div>
                                <div class="fw-bold text-dark">${name}</div>
                                <div class="d-flex gap-2 align-items-center">
                                    ${code ? '<small class="text-primary fw-semibold">' + code + '</small>' : ''}
                                    ${p.mobile ? '<small class="text-muted"><i class="bi bi-telephone me-1"></i>' + p.mobile + '</small>' : ''}
                                </div>
                            </div>
                            <div class="text-end ms-2">
                                <span class="badge ${bal >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'} border px-2 py-1" style="font-size: 0.75rem;">
                                    Bal: ${balFormatted} ${balType}
                                </span>
                            </div>
                        </div>`);
                    },
                    templateSelection: function(item) {
                        if (!item.party) return item.text;
                        const p = item.party;
                        const name = p.customer_name || p.name || p.title || item.text;
                        const code = p.customer_id || p.account_code ? ` [${p.customer_id || p.account_code}]` : '';
                        const bal = p.closing_balance !== undefined ? parseFloat(p.closing_balance) : 0;
                        const balFormatted = Math.abs(bal).toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2});
                        const balType = bal >= 0 ? (p.customer_name ? 'Dr' : 'Cr') : (p.customer_name ? 'Cr' : 'Dr');
                        return `${name}${code} (Bal: ${balFormatted} ${balType})`;
                    }
                });
            }

            initPartySelect2();

            // Header Party Type Change → Reset & Clear
            $('#partyType').on('change', function() {
                $('#partyId').val(null).trigger('change');
                $('#tel').val('');
                updateBalance(0);
                $('#invoiceSettlementSection').slideUp();
                currentInvoices = [];
            });

            // Party selected → load details & update balance
            $('#partyId').on('select2:select', function(e) {
                let item = e.params.data.party || e.params.data;
                if (!item) return;

                $('#tel').val(item.mobile || item.phone || '');
                let bal = parseFloat(item.closing_balance) || 0;
                updateBalance(bal);

                // Auto-set remarks
                let partyName = item.customer_name || item.name || item.title || '';
                if (!$('#remarks').val() && partyName) {
                    $('#remarks').val('Receipt from ' + partyName);
                }

                // Check and load pending customer sales invoices
                let pType = $('#partyType').val();
                if (pType === 'customer' || pType === 'walkin') {
                    loadCustomerInvoices(item.id);
                } else {
                    $('#invoiceSettlementSection').slideUp();
                    currentInvoices = [];
                }
            });

            $('#partyId').on('select2:clear', function() {
                $('#tel').val('');
                updateBalance(0);
                $('#invoiceSettlementSection').slideUp();
                currentInvoices = [];
            });

            // =========================================================
            // INVOICE SETTLEMENT LOGIC (Auto FIFO & Manual Selection)
            // =========================================================
            let currentInvoices = [];

            function loadCustomerInvoices(customerId) {
                if (!customerId) return;
                $('#invoicesTableBody').html('<tr><td colspan="8" class="text-center py-3 text-muted"><span class="spinner-border spinner-border-sm me-1 text-primary"></span> Loading outstanding invoices...</td></tr>');
                $('#invoiceSettlementSection').slideDown();
                $('#noInvoicesNotice').hide();
                $('#invoicesAllocationTable').show();

                $.get('{{ url("vouchers/customer-unpaid-invoices") }}/' + customerId, function(res) {
                    if (res && res.success && res.invoices && res.invoices.length > 0) {
                        currentInvoices = res.invoices;
                        $('#invCountBadge').text(res.count + (res.count === 1 ? ' Invoice' : ' Invoices'));
                        $('#invTotalDueBadge').text('Total Due: Rs. ' + parseFloat(res.total_due).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#tableTotalDueSum').text('Rs. ' + parseFloat(res.total_due).toFixed(2));
                        renderInvoicesTable();
                        recalculateInvoices();
                    } else {
                        currentInvoices = [];
                        $('#invCountBadge').text('0 Invoices');
                        $('#invTotalDueBadge').text('Total Due: Rs. 0.00');
                        $('#invoicesAllocationTable').hide();
                        $('#noInvoicesNotice').show();
                        $('#tableTotalAllocatedSum').text('Rs. 0.00');
                        $('#tableTotalRemainingSum').text('Rs. 0.00');
                    }
                }).fail(function() {
                    $('#invoicesAllocationTable').hide();
                    $('#noInvoicesNotice').show().html('<i class="bi bi-exclamation-triangle text-danger me-1"></i> Failed to load customer invoices.');
                });
            }

            function renderInvoicesTable() {
                let mode = $('input[name="allocation_mode"]:checked').val() || 'fifo';
                let html = '';

                currentInvoices.forEach(function(inv, idx) {
                    let isReadonly = (mode === 'fifo') ? 'readonly' : '';
                    let isChecked = (mode === 'fifo');
                    html += `
                    <tr class="invoice-row" data-index="${idx}" data-sale-id="${inv.id}" data-due="${inv.due_amount}">
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input inv-checkbox" data-index="${idx}" value="${inv.id}" ${isChecked ? 'checked' : ''} ${mode === 'fifo' ? 'disabled' : ''} style="cursor: pointer;">
                            <input type="hidden" name="invoice_ids[]" class="inv-id-input" value="${inv.id}">
                        </td>
                        <td>
                            <span class="badge bg-light text-primary border font-monospace fw-bold" style="font-size: 0.85rem;">${inv.invoice_no}</span>
                        </td>
                        <td class="text-nowrap small text-muted">${inv.date}</td>
                        <td class="text-end font-monospace">Rs. ${parseFloat(inv.total_amount).toFixed(2)}</td>
                        <td class="text-end font-monospace text-muted">Rs. ${parseFloat(inv.paid_amount).toFixed(2)}</td>
                        <td class="text-end font-monospace text-danger fw-bold">Rs. ${parseFloat(inv.due_amount).toFixed(2)}</td>
                        <td class="text-end">
                            <input type="number" step="0.01" min="0" max="${inv.due_amount}" 
                                name="invoice_allocated[]" 
                                class="form-control form-control-sm text-end font-monospace fw-bold inv-alloc-input" 
                                data-index="${idx}" 
                                data-due="${inv.due_amount}" 
                                value="0.00" 
                                ${isReadonly} 
                                style="background: ${mode === 'fifo' ? '#f8fafc' : '#ffffff'}; border-color: #cbd5e1;">
                        </td>
                        <td class="text-end font-monospace text-muted inv-remaining-preview">Rs. ${parseFloat(inv.due_amount).toFixed(2)}</td>
                    </tr>`;
                });

                $('#invoicesTableBody').html(html);
            }

            function recalculateInvoices() {
                let mode = $('input[name="allocation_mode"]:checked').val() || 'fifo';
                let totalVoucherAmt = parseFloat($('#totalAmount').val()) || 0;

                if (mode === 'fifo') {
                    $('#allocationModeHint').html('<i class="bi bi-info-circle me-1 text-primary"></i> <strong>Auto FIFO Mode:</strong> Entered receipt amount is automatically distributed to oldest unpaid sales first.');
                    $('#btnSyncToReceiptAmount').hide();

                    let remainingVoucherAmt = totalVoucherAmt;
                    let totalAllocatedSum = 0;
                    let totalRemainingSum = 0;

                    $('.invoice-row').each(function() {
                        let due = parseFloat($(this).data('due')) || 0;
                        let allocated = 0;

                        if (remainingVoucherAmt > 0) {
                            allocated = Math.min(remainingVoucherAmt, due);
                            remainingVoucherAmt -= allocated;
                        }

                        let newRemaining = Math.max(0, due - allocated);
                        totalAllocatedSum += allocated;
                        totalRemainingSum += newRemaining;

                        let $chk = $(this).find('.inv-checkbox');
                        let $idInput = $(this).find('.inv-id-input');
                        let $allocInput = $(this).find('.inv-alloc-input');
                        let $prev = $(this).find('.inv-remaining-preview');

                        $allocInput.prop('readonly', true).css('background', '#f8fafc');

                        if (allocated > 0) {
                            $chk.prop('checked', true).prop('disabled', true);
                            $idInput.prop('disabled', false);
                            $allocInput.prop('disabled', false).val(allocated.toFixed(2)).css('color', '#16a34a');
                            $(this).addClass('table-success-subtle');
                        } else {
                            $chk.prop('checked', false).prop('disabled', true);
                            $idInput.prop('disabled', true);
                            $allocInput.prop('disabled', false).val('0.00').css('color', '#64748b');
                            $(this).removeClass('table-success-subtle');
                        }

                        if (newRemaining <= 0.001 && allocated > 0) {
                            $prev.html('<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2"></i> Fully Paid</span>');
                        } else {
                            $prev.text('Rs. ' + newRemaining.toFixed(2));
                        }
                    });

                    $('#tableTotalAllocatedSum').text('Rs. ' + totalAllocatedSum.toFixed(2));
                    $('#tableTotalRemainingSum').text('Rs. ' + totalRemainingSum.toFixed(2));

                } else {
                    // MANUAL SELECTION MODE
                    $('#allocationModeHint').html('<i class="bi bi-pencil-square me-1 text-primary"></i> <strong>Manual Mode:</strong> Check specific invoices and edit the receipt amount allocated to each.');
                    $('#btnSyncToReceiptAmount').show();

                    let totalAllocatedSum = 0;
                    let totalRemainingSum = 0;

                    $('.invoice-row').each(function() {
                        let due = parseFloat($(this).data('due')) || 0;
                        let $chk = $(this).find('.inv-checkbox');
                        let $idInput = $(this).find('.inv-id-input');
                        let $allocInput = $(this).find('.inv-alloc-input');
                        let $prev = $(this).find('.inv-remaining-preview');

                        $chk.prop('disabled', false);

                        if ($chk.is(':checked')) {
                            $allocInput.prop('readonly', false).prop('disabled', false).css('background', '#ffffff').css('color', '#16a34a');
                            $idInput.prop('disabled', false);

                            let currentAlloc = parseFloat($allocInput.val()) || 0;
                            if (currentAlloc <= 0) {
                                currentAlloc = due;
                                $allocInput.val(due.toFixed(2));
                            } else if (currentAlloc > due) {
                                currentAlloc = due;
                                $allocInput.val(due.toFixed(2));
                            }

                            let newRemaining = Math.max(0, due - currentAlloc);
                            totalAllocatedSum += currentAlloc;
                            totalRemainingSum += newRemaining;

                            if (newRemaining <= 0.001 && currentAlloc > 0) {
                                $prev.html('<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2"></i> Fully Paid</span>');
                            } else {
                                $prev.text('Rs. ' + newRemaining.toFixed(2));
                            }
                            $(this).addClass('table-success-subtle');
                        } else {
                            $allocInput.prop('readonly', true).val('0.00').css('background', '#f8fafc').css('color', '#64748b');
                            $idInput.prop('disabled', true);
                            totalRemainingSum += due;
                            $prev.text('Rs. ' + due.toFixed(2));
                            $(this).removeClass('table-success-subtle');
                        }
                    });

                    $('#tableTotalAllocatedSum').text('Rs. ' + totalAllocatedSum.toFixed(2));
                    $('#tableTotalRemainingSum').text('Rs. ' + totalRemainingSum.toFixed(2));
                }
            }

            // Allocation Mode Switch Change
            $('input[name="allocation_mode"]').on('change', function() {
                let mode = $(this).val();
                if (mode === 'manual') {
                    $('.inv-checkbox').prop('disabled', false);
                    $('.inv-alloc-input').prop('readonly', false).css('background', '#ffffff');
                } else {
                    $('.inv-checkbox').prop('disabled', true);
                    $('.inv-alloc-input').prop('readonly', true).css('background', '#f8fafc');
                }
                recalculateInvoices();
            });

            // Checkbox click in Manual Mode
            $(document).on('change', '.inv-checkbox', function() {
                let $row = $(this).closest('.invoice-row');
                let due = parseFloat($row.data('due')) || 0;
                let $allocInput = $row.find('.inv-alloc-input');

                if ($(this).is(':checked')) {
                    $allocInput.val(due.toFixed(2));
                } else {
                    $allocInput.val('0.00');
                }
                recalculateInvoices();
            });

            // Select All Invoices in Manual Mode
            $('#selectAllInvoices').on('change', function() {
                let isChecked = $(this).is(':checked');
                let mode = $('input[name="allocation_mode"]:checked').val() || 'fifo';
                if (mode === 'manual') {
                    $('.inv-checkbox').each(function() {
                        $(this).prop('checked', isChecked);
                        let $row = $(this).closest('.invoice-row');
                        let due = parseFloat($row.data('due')) || 0;
                        $row.find('.inv-alloc-input').val(isChecked ? due.toFixed(2) : '0.00');
                    });
                    recalculateInvoices();
                }
            });

            // Manual Allocation Input changes
            $(document).on('input change', '.inv-alloc-input', function() {
                let due = parseFloat($(this).data('due')) || 0;
                let val = parseFloat($(this).val()) || 0;

                if (val < 0) {
                    $(this).val('0.00');
                } else if (val > due) {
                    $(this).val(due.toFixed(2));
                }
                recalculateInvoices();
            });

            // Match Manual Settled Total to Voucher Payment Amount button
            $('#btnSyncToReceiptAmount').on('click', function() {
                let totalAllocated = 0;
                $('.invoice-row').each(function() {
                    if ($(this).find('.inv-checkbox').is(':checked')) {
                        totalAllocated += parseFloat($(this).find('.inv-alloc-input').val()) || 0;
                    }
                });

                if (totalAllocated > 0) {
                    let $firstAmount = $('.voucher-row:first').find('.amount');
                    $firstAmount.val(totalAllocated.toFixed(2));
                    calculateTotal();
                }
            });

            function updateBalance(bal) {
                let $badge = $('#balanceDisplay');
                let formatted = Math.abs(bal).toFixed(2);
                if (bal >= 0) {
                    $badge.removeClass('balance-cr').addClass('balance-dr');
                    $badge.html(formatted + ' <small>Dr</small>');
                } else {
                    $badge.removeClass('balance-dr').addClass('balance-cr');
                    $badge.html(formatted + ' <small>Cr</small>');
                }
            }

            // Row Logic (destination accounts)
            $(document).on('change', '.rowAccountHead', function() {
                let $headSelect = $(this);
                let headId = $headSelect.val();
                let headText = $headSelect.find('option:selected').text().trim().toLowerCase();
                let $row = $headSelect.closest('tr.voucher-row');
                let $chqRow = $row.next('tr.cheque-details-row');
                let $subSelect = $row.find('.rowAccountSub');

                if (!headId) {
                    $subSelect.html('<option value="">Select Account</option>');
                    $chqRow.hide();
                    $row.find('.row-cheque-no').val('');
                    $row.find('.row-cheque-bank').val('');
                    $row.find('.row-cheque-date').val('');
                    return;
                }

                let isCheque = headText.indexOf('cheque') !== -1;

                $.get('{{ url("get-accounts-by-head") }}/' + headId, function(res) {
                    let html = '<option value="">Select Account</option>';
                    res.forEach(acc => {
                        html += `<option value="${acc.id}" data-title="${acc.title.toLowerCase()}">${acc.title}</option>`;
                    });
                    $subSelect.html(html);

                    if (res.length === 1) {
                        $subSelect.val(res[0].id).trigger('change');
                    }

                    if (isCheque) {
                        $chqRow.show();
                        let receiptDate = $('input[name="receipt_date"]').val() || '{{ now()->toDateString() }}';
                        if (!$chqRow.find('.chq-date-field').val()) {
                            $chqRow.find('.chq-date-field').val(receiptDate);
                        }
                        $row.find('.row-cheque-no').val($chqRow.find('.chq-no-field').val());
                        $row.find('.row-cheque-bank').val($chqRow.find('.chq-bank-field').val());
                        $row.find('.row-cheque-date').val($chqRow.find('.chq-date-field').val());
                    } else {
                        let subText = $subSelect.find('option:selected').text().trim().toLowerCase();
                        if (subText.indexOf('cheque') === -1) {
                            $chqRow.hide();
                            $row.find('.row-cheque-no').val('');
                            $row.find('.row-cheque-bank').val('');
                            $row.find('.row-cheque-date').val('');
                        }
                    }
                });
            });

            $(document).on('change', '.rowAccountSub', function() {
                let $subSelect = $(this);
                let subText = $subSelect.find('option:selected').text().trim().toLowerCase();
                let $row = $subSelect.closest('tr.voucher-row');
                let $chqRow = $row.next('tr.cheque-details-row');

                if (subText.indexOf('cheque') !== -1) {
                    $chqRow.show();
                    let receiptDate = $('input[name="receipt_date"]').val() || '{{ now()->toDateString() }}';
                    if (!$chqRow.find('.chq-date-field').val()) {
                        $chqRow.find('.chq-date-field').val(receiptDate);
                    }
                    $row.find('.row-cheque-no').val($chqRow.find('.chq-no-field').val());
                    $row.find('.row-cheque-bank').val($chqRow.find('.chq-bank-field').val());
                    $row.find('.row-cheque-date').val($chqRow.find('.chq-date-field').val());
                }
            });

            // Sync cheque inputs to row hidden inputs
            $(document).on('input change', '.chq-no-field', function() {
                let $chqRow = $(this).closest('tr.cheque-details-row');
                let $row = $chqRow.prev('tr.voucher-row');
                $row.find('.row-cheque-no').val($(this).val());
            });

            $(document).on('input change', '.chq-bank-field', function() {
                let $chqRow = $(this).closest('tr.cheque-details-row');
                let $row = $chqRow.prev('tr.voucher-row');
                $row.find('.row-cheque-bank').val($(this).val());
            });

            function updatePdcHint($input) {
                let val = $input.val();
                let $col = $input.closest('.col-md-4');
                $col.find('.pdc-hint').remove();

                if (!val) return;

                let parts = val.split('-');
                if (parts.length !== 3) return;

                let selected = new Date(parts[0], parts[1] - 1, parts[2]);
                let today = new Date();
                today.setHours(0, 0, 0, 0);
                selected.setHours(0, 0, 0, 0);

                let diffTime = selected.getTime() - today.getTime();
                let diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));

                if (diffDays > 0) {
                    let badge = diffDays <= 3 
                        ? '<span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1 mt-1 d-inline-flex align-items-center gap-1" style="font-size:0.75rem;"><i class="bi bi-bell-fill text-warning"></i> PDC (Due in ' + diffDays + ' day' + (diffDays > 1 ? 's' : '') + ' - Advance Alert active)</span>'
                        : '<span class="badge bg-info-subtle text-primary border border-info px-2 py-1 mt-1 d-inline-flex align-items-center gap-1" style="font-size:0.75rem;"><i class="bi bi-clock-history"></i> PDC (Maturity in ' + diffDays + ' days)</span>';
                    $col.append('<div class="pdc-hint">' + badge + '</div>');
                } else if (diffDays === 0) {
                    $col.append('<div class="pdc-hint"><span class="badge bg-success-subtle text-success border border-success px-2 py-1 mt-1 d-inline-flex align-items-center gap-1" style="font-size:0.75rem;"><i class="bi bi-check-circle-fill"></i> Due Today (Deposit in Bank)</span></div>');
                } else {
                    $col.append('<div class="pdc-hint"><span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1 mt-1 d-inline-flex align-items-center gap-1" style="font-size:0.75rem;"><i class="bi bi-exclamation-octagon"></i> Backdated Cheque (' + Math.abs(diffDays) + ' days ago)</span></div>');
                }
            }

            $(document).on('input change', '.chq-date-field', function() {
                let $chqRow = $(this).closest('tr.cheque-details-row');
                let $row = $chqRow.prev('tr.voucher-row');
                $row.find('.row-cheque-date').val($(this).val());
                updatePdcHint($(this));
            });

            // Totals
            function calculateTotal() {
                let total = 0;
                $('.amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#totalAmount').val(total.toFixed(2));
                recalculateInvoices();
            }
            $(document).on('input', '.amount', function() {
                calculateTotal();
            });

            // Prevent arrow up/down keys and scroll wheel from changing amount / number values
            $(document).on('keydown', 'input[type="number"], .amount', function(e) {
                if (e.key === 'ArrowUp' || e.key === 'ArrowDown' || e.keyCode === 38 || e.keyCode === 40) {
                    e.preventDefault();
                }
            });

            $(document).on('wheel', 'input[type="number"], .amount', function(e) {
                $(this).blur();
            });

            // Add Row
            $('#addNewRow').on('click', function() {
                let receiptDate = $('input[name="receipt_date"]').val() || '{{ now()->toDateString() }}';
                let newRow = `
                <tr class="voucher-row">
                    <td>
                        <select name="row_account_head[]" class="rv-input rowAccountHead">
                            <option value="">Select Head</option>
                            @foreach ($AccountHeads as $head)
                                <option value="{{ $head->id }}">{{ $head->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="row_cheque_no[]" class="row-cheque-no" value="">
                        <input type="hidden" name="row_cheque_bank[]" class="row-cheque-bank" value="">
                        <input type="hidden" name="row_cheque_date[]" class="row-cheque-date" value="">
                    </td>
                    <td>
                        <select name="row_account_id[]" class="rv-input rowAccountSub">
                            <option disabled selected>Select Account</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="amount[]" class="rv-input text-end fw-bold amount" placeholder="0.00" style="font-size: 1rem;">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm removeRow"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr class="cheque-details-row" style="display: none;">
                    <td colspan="4" class="border-top-0">
                        <div class="chq-card-box">
                            <div class="chq-card-header">
                                <div class="chq-card-title">
                                    <span class="icon-holder">
                                        <i class="bi bi-credit-card-2-front"></i>
                                    </span>
                                    <div>
                                        <span>Cheque Receiving Details</span>
                                        <span class="chq-badge-status ms-2">
                                            <i class="bi bi-check2-circle"></i> Auto-saved to Cheque Management
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <span class="chq-badge-alert">
                                        <i class="bi bi-bell"></i> 3-Day & Due Date Alerts Active
                                    </span>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="chq-field-label">
                                        <i class="bi bi-hash text-primary"></i> Cheque Number <span class="text-danger">*</span>
                                    </label>
                                    <div class="chq-input-group">
                                        <span class="input-icon"><i class="bi bi-upc-scan"></i></span>
                                        <input type="text" class="chq-no-field" placeholder="e.g. 00482910">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="chq-field-label">
                                        <i class="bi bi-bank text-primary"></i> Customer Bank Name <span class="text-danger">*</span>
                                    </label>
                                    <div class="chq-input-group">
                                        <span class="input-icon"><i class="bi bi-building"></i></span>
                                        <input type="text" class="chq-bank-field" placeholder="e.g. Meezan Bank, HBL, MCB">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="chq-field-label">
                                        <i class="bi bi-calendar-event text-primary"></i> Cheque / Due Date (PDC) <span class="text-danger">*</span>
                                    </label>
                                    <div class="chq-input-group">
                                        <span class="input-icon"><i class="bi bi-calendar3"></i></span>
                                        <input type="date" class="chq-date-field" value="${receiptDate}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
                $('#voucherTable tbody').append(newRow);
            });

            $(document).on('click', '.removeRow', function() {
                if ($('#voucherTable tbody tr.voucher-row').length > 1) {
                    let $row = $(this).closest('tr.voucher-row');
                    $row.next('tr.cheque-details-row').remove();
                    $row.remove();
                    calculateTotal();
                }
            });

            // Handle Save (Print) Form Submission
            $('#receiptForm').on('submit', function(e) {
                e.preventDefault();

                // Ensure invoice inputs are enabled for allocated invoices so serialize() picks them up
                $('.invoice-row').each(function() {
                    let isChecked = $(this).find('.inv-checkbox').is(':checked');
                    let allocVal = parseFloat($(this).find('.inv-alloc-input').val()) || 0;
                    if (isChecked && allocVal > 0) {
                        $(this).find('.inv-id-input').prop('disabled', false);
                        $(this).find('.inv-alloc-input').prop('disabled', false);
                    } else {
                        $(this).find('.inv-id-input').prop('disabled', true);
                        $(this).find('.inv-alloc-input').prop('disabled', true);
                    }
                });

                let partyType = $('#partyType').val();
                let partyId = $('#partyId').val();

                if (!partyId && (partyType === 'customer' || partyType === 'vendor' || partyType === 'walkin')) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Party Required',
                        text: 'Please select a Party / Account first.'
                    });
                    return;
                }

                let totalAmt = parseFloat($('#totalAmount').val()) || 0;
                if (totalAmt <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Amount Required',
                        text: 'Please enter a valid Amount greater than 0.'
                    });
                    return;
                }

                let missingCheque = false;
                $('#voucherTable tbody tr.voucher-row').each(function() {
                    let headText = $(this).find('.rowAccountHead option:selected').text().trim().toLowerCase();
                    let accText = $(this).find('.rowAccountSub option:selected').text().trim().toLowerCase();
                    let isCheque = headText.indexOf('cheque') !== -1 || accText.indexOf('cheque') !== -1;
                    let chqNo = $(this).find('.row-cheque-no').val().trim();
                    let chqBank = $(this).find('.row-cheque-bank').val().trim();

                    if (isCheque) {
                        if (!chqNo || !chqBank) {
                            missingCheque = true;
                            return false;
                        }
                    }
                });

                if (missingCheque) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cheque Details Required',
                        text: 'Please enter Cheque Number and Customer Bank Name for the Cheque row.'
                    });
                    return;
                }

                let $btn = $('#btnSavePrint');
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(res) {
                        if (res && res.success) {
                            // Redirect directly to the thermal invoice print page
                            window.location.href = res.print_url;
                        } else {
                            $btn.prop('disabled', false).html('<i class="bi bi-printer me-1"></i> Save (Print)');
                            Swal.fire('Error', res.message || 'Failed to save voucher.', 'error');
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html('<i class="bi bi-printer me-1"></i> Save (Print)');
                        let msg = 'Failed to save voucher.';
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
