@extends('admin_panel.layout.app')
@section('content')
      <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/css/bootstrap-icons.min.css') }}">

    <style>
        :root {
            --pv-primary: #4f46e5;
            --pv-bg: #f8fafc;
            --pv-border: #e2e8f0;
            --pv-text: #1e293b;
            --pv-muted: #64748b;
        }

        .pv-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -1px rgba(0,0,0,0.04);
            border: 1px solid var(--pv-border);
            padding: 24px;
        }

        .pv-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--pv-border);
        }

        .pv-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--pv-text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pv-title i {
            color: var(--pv-primary);
            background: #e0e7ff;
            padding: 8px 10px;
            border-radius: 10px;
        }

        .pv-section-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--pv-primary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 16px 0 8px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pv-section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--pv-border);
        }

        .pv-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--pv-muted);
            margin-bottom: 4px;
        }

        .pv-input {
            background: #fff;
            border: 1px solid var(--pv-border);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.9rem;
            color: var(--pv-text);
            transition: all 0.2s ease;
            width: 100%;
        }
        .pv-input:focus {
            border-color: var(--pv-primary);
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
            outline: none;
        }
        .pv-input::placeholder { color: #cbd5e1; }

        select.pv-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .pv-table {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--pv-border);
        }
        .pv-table thead th {
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
        .pv-table tbody td {
            padding: 8px 10px;
            vertical-align: middle;
            border-color: var(--pv-border);
        }
        .pv-table tfoot td {
            padding: 10px 12px;
            background: #f1f5f9;
            font-weight: 700;
        }

        .btn-pv-primary {
            background: var(--pv-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 28px;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(79,70,229,0.3);
            transition: all 0.2s;
        }
        .btn-pv-primary:hover {
            background: #4338ca;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(79,70,229,0.4);
        }

        .btn-pv-secondary {
            background: #f1f5f9;
            color: var(--pv-text);
            border: 1px solid var(--pv-border);
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-pv-secondary:hover { background: #e2e8f0; color: var(--pv-text); }

        .btn-pv-add {
            background: #ecfdf5;
            color: #059669;
            border: 1px dashed #10b981;
            border-radius: 8px;
            padding: 6px 16px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .btn-pv-add:hover { background: #d1fae5; }

        .balance-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
        }
        .balance-dr { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }
        .balance-cr { background: #f0fdf4; color: #16a34a; border: 1px solid #86efac; }

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
        /* Select2 PV Custom Styling */
        .select2-container--default .select2-selection--single {
            background-color: #fff !important;
            border: 1px solid var(--pv-border) !important;
            border-radius: 8px !important;
            height: 38px !important;
            padding: 4px 8px !important;
            font-size: 0.9rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
            color: var(--pv-text) !important;
            padding-left: 2px !important;
            font-weight: 500;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 8px !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--pv-primary) !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1) !important;
        }
        .select2-dropdown {
            border: 1px solid var(--pv-border) !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
            z-index: 99999 !important;
        }
        .select2-search--dropdown .select2-search__field {
            border: 1px solid var(--pv-border) !important;
            border-radius: 6px !important;
            padding: 6px 10px !important;
            font-size: 0.85rem !important;
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

                <form action="{{ route('store_Pay_vochers') }}" method="POST" id="paymentForm">
                    @csrf

                    <div class="pv-card">
                        <!-- Header -->
                        <div class="pv-header">
                            <div class="pv-title">
                                <i class="bi bi-cash-stack"></i> Payment Voucher
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('all_Payment_vochers') }}" class="btn btn-pv-secondary">
                                    <i class="bi bi-list-ul me-1"></i> All Vouchers
                                </a>
                                <button type="submit" class="btn btn-pv-primary">
                                    <i class="bi bi-check-lg me-1"></i> Save Voucher
                                </button>
                            </div>
                        </div>

                        <!-- Row 1: Voucher Info -->
                        <div class="pv-section-label">Voucher Details</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-2">
                                <label class="pv-label">PVID</label>
                                <input type="text" class="pv-input" style="background: #f1f5f9;" name="pvid"
                                    value="{{ $nextPVID }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="pv-label">Receipt Date</label>
                                <input type="date" name="receipt_date" class="pv-input"
                                    value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-2">
                                <label class="pv-label">Entry Date</label>
                                <input type="date" name="entry_date" class="pv-input"
                                    value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-6">
                                <label class="pv-label">Remarks <small class="text-muted fw-normal">(Optional)</small></label>
                                <input type="text" name="remarks" class="pv-input" id="remarks" placeholder="Auto-generated if left blank">
                            </div>
                        </div>

                        <!-- Row 2: Pay From (Source Account) -->
                        <div class="pv-section-label">Pay From (Source Account)</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="pv-label">Account Head</label>
                                <select name="header_account_head" class="pv-input" id="payFromHead">
                                    <option value="">Select Head</option>
                                    @foreach ($AccountHeads as $head)
                                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="pv-label">Account (Cash / Bank)</label>
                                <select name="header_account_id" class="pv-input" id="payFromAccount">
                                    <option value="" disabled selected>Select Account</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="pv-label">Account Code</label>
                                <input type="text" id="accountCode" class="pv-input" style="background: #f1f5f9;" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="pv-label">Account Balance</label>
                                <div id="sourceBalanceDisplay" class="balance-badge balance-cr p-2 text-center" style="width:100%;">0.00 <small>Cr</small></div>
                            </div>
                        </div>

                        <!-- Payment Destination Rows -->
                        <div class="pv-section-label">Pay To (Destination)</div>
                        <div class="pv-table">
                            <table class="table table-bordered align-middle mb-0" id="voucherTable">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;">Type</th>
                                        <th style="width: 35%;">Party / Account</th>
                                        <th style="width: 25%;">Amount</th>
                                        <th style="width: 15%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select name="vendor_type[]" class="pv-input rowType">
                                                <option value="" disabled>Select Type</option>
                                                <option value="vendor" selected>Vendor</option>
                                                <option value="customer">Customer</option>
                                                <option value="walkin">Walk-in</option>
                                                @foreach ($AccountHeads as $head)
                                                    <option value="{{ $head->id }}">{{ $head->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="vendor_id[]" class="pv-input rowParty">
                                                <option value="" disabled selected>Select Party</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="amount[]"
                                                class="pv-input text-end fw-bold amount" placeholder="0.00"
                                                style="font-size: 1rem;">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm removeRow" title="Remove">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold" style="font-size: 1rem;">Total Amount:</td>
                                        <td>
                                            <input type="text" name="total_amount"
                                                class="pv-input text-end fw-bold" id="totalAmount" readonly
                                                value="0.00" style="background: #fef2f2; border-color: #fca5a5; font-size: 1.1rem; color: #dc2626;">
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <button type="button" class="btn btn-pv-add mt-3" id="addNewRow">
                            <i class="bi bi-plus-circle me-1"></i> Add Another Row
                        </button>

                        <!-- Purchase Bills Settlement Section (Auto FIFO / Manual) -->
                        <div id="purchaseSettlementSection" class="mt-4 mb-3" style="display: none;">
                            <div class="card border-0 shadow-sm" style="background: #ffffff; border: 1.5px solid #cbd5e1 !important; border-radius: 12px; overflow: hidden;">
                                <div class="card-header bg-white py-3 px-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 34px; height: 34px; background: #fee2e2; color: #dc2626; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                                            <i class="bi bi-file-earmark-spreadsheet"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">Outstanding Purchase Bills Settlement</span>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2" id="purCountBadge">0 Bills</span>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" id="purTotalDueBadge">Total Due: Rs. 0.00</span>
                                        </div>
                                    </div>
                                    
                                    <!-- 2 Mode Options -->
                                    <div class="d-flex align-items-center gap-2 bg-light p-1 rounded-3 border">
                                        <span class="text-muted small fw-bold px-2"><i class="bi bi-sliders me-1"></i> Allocation Mode:</span>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="allocation_mode" id="purAllocModeFifo" value="fifo" checked autocomplete="off">
                                            <label class="btn btn-outline-primary fw-semibold px-3" for="purAllocModeFifo" title="Automatically distributes payment amount to oldest purchase bills first (FIFO)">
                                                <i class="bi bi-lightning-charge-fill me-1 text-warning"></i> Auto FIFO
                                            </label>

                                            <input type="radio" class="btn-check" name="allocation_mode" id="purAllocModeManual" value="manual" autocomplete="off">
                                            <label class="btn btn-outline-primary fw-semibold px-3" for="purAllocModeManual" title="Select specific purchase bills and customize payment allocation amount">
                                                <i class="bi bi-check2-square me-1"></i> Manual Selection
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0" id="purchasesAllocationTable" style="font-size: 0.88rem;">
                                            <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                                <tr>
                                                    <th class="text-center" style="width: 45px;">
                                                        <input type="checkbox" id="selectAllPurchases" class="form-check-input" style="cursor: pointer;" title="Select / Deselect All">
                                                    </th>
                                                    <th>Bill / Inv #</th>
                                                    <th>PO #</th>
                                                    <th>Date</th>
                                                    <th class="text-end">Bill Total</th>
                                                    <th class="text-end">Already Paid</th>
                                                    <th class="text-end text-danger fw-bold">Remaining Due</th>
                                                    <th class="text-end" style="width: 170px;">This Payment (Rs.)</th>
                                                    <th class="text-end text-muted">New Balance</th>
                                                </tr>
                                            </thead>
                                            <tbody id="purchasesTableBody">
                                                <!-- Dynamic rows populated via AJAX -->
                                            </tbody>
                                            <tfoot class="table-light fw-bold" style="border-top: 2px solid #e2e8f0;">
                                                <tr>
                                                    <td colspan="6" class="text-end">Total Settled Bills:</td>
                                                    <td class="text-end text-danger font-monospace" id="tablePurTotalDueSum">Rs. 0.00</td>
                                                    <td class="text-end font-monospace text-success fw-bold" id="tablePurTotalAllocatedSum" style="font-size: 0.95rem;">Rs. 0.00</td>
                                                    <td class="text-end font-monospace text-muted" id="tablePurTotalRemainingSum">Rs. 0.00</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <div id="noPurchasesNotice" class="p-3 text-center text-muted" style="display: none;">
                                        <i class="bi bi-info-circle me-1 text-primary"></i> No unpaid purchase bills found for this vendor. Payment will be debited directly to vendor ledger.
                                    </div>
                                </div>
                                <div class="card-footer bg-light py-2 px-3 border-top d-flex justify-content-between align-items-center" id="purchaseSettlementFooter">
                                    <small class="text-muted" id="purAllocationModeHint">
                                        <i class="bi bi-info-circle me-1 text-primary"></i> <strong>Auto FIFO Mode:</strong> Entered payment amount is automatically distributed to oldest unpaid purchases first.
                                    </small>
                                    <button type="button" class="btn btn-sm btn-outline-success fw-bold" id="btnSyncToPaymentAmount" style="display: none;">
                                        <i class="bi bi-arrow-repeat me-1"></i> Apply Settled Total to Amount
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Hidden fields for backward compatibility --}}
                        <input type="hidden" name="narration_id[]" value="">
                        <input type="hidden" name="narration_text[]" value="">
                        <input type="hidden" name="reference_no[]" value="">
                        <input type="hidden" name="discount_value[]" value="0">
                        <input type="hidden" name="rate[]" value="0">

                    </div><!-- /pv-card -->

                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {

            // Initialize Select2 helper
            function initPartySelect2($element) {
                $element.select2({
                    placeholder: "Select Party",
                    allowClear: true,
                    width: '100%'
                });
            }

            function initTypeSelect2($element) {
                $element.select2({
                    placeholder: "Select Type",
                    allowClear: true,
                    width: '100%'
                });
            }

            // Initial Select2 on page load
            $('#payFromHead').select2({ placeholder: "Select Head", allowClear: true, width: '100%' });
            $('#payFromAccount').select2({ placeholder: "Select Account", allowClear: true, width: '100%' });
            initTypeSelect2($('.rowType'));
            initPartySelect2($('.rowParty'));
            $('.rowType').trigger('change');

            // Header Source Account Logic
            $('#payFromHead').on('change', function() {
                let headId = $(this).val();
                let $accSelect = $('#payFromAccount');
                $accSelect.html('<option value="">Loading...</option>').trigger('change');
                $('#accountCode').val('');
                updateSourceBalance(0);

                if (headId) {
                    $.get('{{ url("get-accounts-by-head") }}/' + headId, function(data) {
                        $accSelect.empty().append('<option value="" disabled selected>Select Account</option>');
                        data.forEach(function(acc) {
                            $accSelect.append(
                                `<option value="${acc.id}" data-code="${acc.account_code || ''}" data-bal="${acc.current_balance || 0}">${acc.title}</option>`
                            );
                        });
                        $accSelect.val('').trigger('change');
                    });
                } else {
                    $accSelect.empty().append('<option value="" disabled selected>Select Account</option>').trigger('change');
                }
            });

            $('#payFromAccount').on('change', function() {
                let $opt = $(this).find(':selected');
                $('#accountCode').val($opt.data('code') || '');
                let bal = parseFloat($opt.data('bal')) || 0;
                updateSourceBalance(bal);
            });

            function updateSourceBalance(bal) {
                let $badge = $('#sourceBalanceDisplay');
                let formatted = Math.abs(bal).toFixed(2);
                if (bal >= 0) {
                    $badge.removeClass('balance-dr').addClass('balance-cr');
                    $badge.html(formatted + ' <small>Cr</small>');
                } else {
                    $badge.removeClass('balance-cr').addClass('balance-dr');
                    $badge.html(formatted + ' <small>Dr</small>');
                }
            }

            // Row Logic (Destination Parties)
            $(document).on('change', '.rowType', function() {
                let type = $(this).val();
                let $row = $(this).closest('tr');
                let $select = $row.find('.rowParty');

                $select.html('<option value="">Loading...</option>').trigger('change');

                if (type === 'vendor' || type === 'customer' || type === 'walkin') {
                    $.get('{{ route("party.list") }}?type=' + type, function(data) {
                        $select.empty().append('<option value="" disabled selected>Select Party</option>');
                        data.forEach(function(item) {
                            $select.append(
                                `<option value="${item.id}">${item.text}</option>`
                            );
                        });
                        $select.val('').trigger('change');
                    });
                } else if (type) {
                    $.get('{{ url("get-accounts-by-head") }}/' + type, function(data) {
                        $select.empty().append('<option value="" disabled selected>Select Account</option>');
                        data.forEach(function(acc) {
                            $select.append(
                                `<option value="${acc.id}">${acc.title}</option>`
                            );
                        });
                        $select.val('').trigger('change');
                    });
                } else {
                    $select.empty().append('<option value="" disabled selected>Select Party</option>').trigger('change');
                }
            });

            // Totals
            function calculateTotal() {
                let total = 0;
                $('.amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#totalAmount').val(total.toFixed(2));
                recalculatePurchases();
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
                let $newRow = $(`
                    <tr>
                        <td>
                            <select name="vendor_type[]" class="pv-input rowType">
                                <option value="" disabled selected>Select Type</option>
                                <option value="vendor">Vendor</option>
                                <option value="customer">Customer</option>
                                <option value="walkin">Walk-in</option>
                                @foreach ($AccountHeads as $head)
                                    <option value="{{ $head->id }}">{{ $head->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="vendor_id[]" class="pv-input rowParty">
                                <option value="" disabled selected>Select Party</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="amount[]" class="pv-input text-end fw-bold amount" placeholder="0.00" style="font-size: 1rem;">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm removeRow"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                `);

                $('#voucherTable tbody').append($newRow);
                initTypeSelect2($newRow.find('.rowType'));
                initPartySelect2($newRow.find('.rowParty'));
            });

            $(document).on('click', '.removeRow', function() {
                if ($('#voucherTable tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    calculateTotal();
                    checkAnyVendorSelected();
                }
            });

            // =========================================================
            // PURCHASE BILLS SETTLEMENT LOGIC (Auto FIFO & Manual Selection)
            // =========================================================
            let currentPurchases = [];
            let activeVendorId = null;

            function checkAnyVendorSelected() {
                let foundVendorId = null;
                $('#voucherTable tbody tr').each(function() {
                    let type = $(this).find('.rowType').val();
                    if (type === 'vendor') {
                        let vId = $(this).find('.rowParty').val();
                        if (vId) {
                            foundVendorId = vId;
                            return false; // break
                        }
                    }
                });

                if (foundVendorId) {
                    if (foundVendorId !== activeVendorId) {
                        loadVendorPurchases(foundVendorId);
                    }
                } else {
                    activeVendorId = null;
                    currentPurchases = [];
                    $('#purchaseSettlementSection').slideUp();
                }
            }

            $(document).on('change', '.rowParty', function() {
                checkAnyVendorSelected();
            });

            $(document).on('change', '.rowType', function() {
                setTimeout(checkAnyVendorSelected, 400);
            });

            function loadVendorPurchases(vendorId) {
                if (!vendorId) return;
                activeVendorId = vendorId;
                $('#purchasesTableBody').html('<tr><td colspan="9" class="text-center py-3 text-muted"><span class="spinner-border spinner-border-sm me-1 text-danger"></span> Loading pending purchase bills...</td></tr>');
                $('#purchaseSettlementSection').slideDown();
                $('#noPurchasesNotice').hide();
                $('#purchasesAllocationTable').show();

                $.get('{{ url("vouchers/vendor-unpaid-purchases") }}/' + vendorId, function(res) {
                    if (res && res.success && res.purchases && res.purchases.length > 0) {
                        currentPurchases = res.purchases;
                        $('#purCountBadge').text(res.count + (res.count === 1 ? ' Bill' : ' Bills'));
                        $('#purTotalDueBadge').text('Total Due: Rs. ' + parseFloat(res.total_due).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#tablePurTotalDueSum').text('Rs. ' + parseFloat(res.total_due).toFixed(2));
                        renderPurchasesTable();
                        recalculatePurchases();
                    } else {
                        currentPurchases = [];
                        $('#purCountBadge').text('0 Bills');
                        $('#purTotalDueBadge').text('Total Due: Rs. 0.00');
                        $('#purchasesAllocationTable').hide();
                        $('#noPurchasesNotice').show();
                        $('#tablePurTotalAllocatedSum').text('Rs. 0.00');
                        $('#tablePurTotalRemainingSum').text('Rs. 0.00');
                    }
                }).fail(function() {
                    $('#purchasesAllocationTable').hide();
                    $('#noPurchasesNotice').show().html('<i class="bi bi-exclamation-triangle text-danger me-1"></i> Failed to load purchase bills.');
                });
            }

            function renderPurchasesTable() {
                let mode = $('input[name="allocation_mode"]:checked').val() || 'fifo';
                let html = '';

                currentPurchases.forEach(function(pur, idx) {
                    let isReadonly = (mode === 'fifo') ? 'readonly' : '';
                    let isChecked = (mode === 'fifo');
                    html += `
                    <tr class="purchase-row" data-index="${idx}" data-purchase-id="${pur.id}" data-due="${pur.due_amount}">
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input pur-checkbox" data-index="${idx}" value="${pur.id}" ${isChecked ? 'checked' : ''} ${mode === 'fifo' ? 'disabled' : ''} style="cursor: pointer;">
                            <input type="hidden" name="purchase_ids[]" class="pur-id-input" value="${pur.id}">
                        </td>
                        <td>
                            <span class="badge bg-light text-primary border font-monospace fw-bold" style="font-size: 0.85rem;">${pur.invoice_no}</span>
                        </td>
                        <td class="font-monospace small text-muted">${pur.po_no || '-'}</td>
                        <td class="text-nowrap small text-muted">${pur.date}</td>
                        <td class="text-end font-monospace">Rs. ${parseFloat(pur.total_amount).toFixed(2)}</td>
                        <td class="text-end font-monospace text-muted">Rs. ${parseFloat(pur.paid_amount).toFixed(2)}</td>
                        <td class="text-end font-monospace text-danger fw-bold">Rs. ${parseFloat(pur.due_amount).toFixed(2)}</td>
                        <td class="text-end">
                            <input type="number" step="0.01" min="0" max="${pur.due_amount}" 
                                name="purchase_allocated[]" 
                                class="form-control form-control-sm text-end font-monospace fw-bold pur-alloc-input" 
                                data-index="${idx}" 
                                data-due="${pur.due_amount}" 
                                value="0.00" 
                                ${isReadonly} 
                                style="background: ${mode === 'fifo' ? '#f8fafc' : '#ffffff'}; border-color: #cbd5e1;">
                        </td>
                        <td class="text-end font-monospace text-muted pur-remaining-preview">Rs. ${parseFloat(pur.due_amount).toFixed(2)}</td>
                    </tr>`;
                });

                $('#purchasesTableBody').html(html);
            }

            function recalculatePurchases() {
                let mode = $('input[name="allocation_mode"]:checked').val() || 'fifo';
                let totalVoucherAmt = parseFloat($('#totalAmount').val()) || 0;

                if (mode === 'fifo') {
                    $('#purAllocationModeHint').html('<i class="bi bi-info-circle me-1 text-primary"></i> <strong>Auto FIFO Mode:</strong> Entered payment amount is automatically distributed to oldest unpaid purchase bills first.');
                    $('#btnSyncToPaymentAmount').hide();

                    let remainingVoucherAmt = totalVoucherAmt;
                    let totalAllocatedSum = 0;
                    let totalRemainingSum = 0;

                    $('.purchase-row').each(function() {
                        let due = parseFloat($(this).data('due')) || 0;
                        let allocated = 0;

                        if (remainingVoucherAmt > 0) {
                            allocated = Math.min(remainingVoucherAmt, due);
                            remainingVoucherAmt -= allocated;
                        }

                        let newRemaining = Math.max(0, due - allocated);
                        totalAllocatedSum += allocated;
                        totalRemainingSum += newRemaining;

                        let $chk = $(this).find('.pur-checkbox');
                        let $idInput = $(this).find('.pur-id-input');
                        let $allocInput = $(this).find('.pur-alloc-input');
                        let $prev = $(this).find('.pur-remaining-preview');

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

                    $('#tablePurTotalAllocatedSum').text('Rs. ' + totalAllocatedSum.toFixed(2));
                    $('#tablePurTotalRemainingSum').text('Rs. ' + totalRemainingSum.toFixed(2));

                } else {
                    // MANUAL SELECTION MODE
                    $('#purAllocationModeHint').html('<i class="bi bi-pencil-square me-1 text-primary"></i> <strong>Manual Mode:</strong> Check specific purchase bills and customize the amount allocated to each.');
                    $('#btnSyncToPaymentAmount').show();

                    let totalAllocatedSum = 0;
                    let totalRemainingSum = 0;

                    $('.purchase-row').each(function() {
                        let due = parseFloat($(this).data('due')) || 0;
                        let $chk = $(this).find('.pur-checkbox');
                        let $idInput = $(this).find('.pur-id-input');
                        let $allocInput = $(this).find('.pur-alloc-input');
                        let $prev = $(this).find('.pur-remaining-preview');

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

                    $('#tablePurTotalAllocatedSum').text('Rs. ' + totalAllocatedSum.toFixed(2));
                    $('#tablePurTotalRemainingSum').text('Rs. ' + totalRemainingSum.toFixed(2));
                }
            }

            // Allocation Mode Switch Change
            $('input[name="allocation_mode"]').on('change', function() {
                let mode = $(this).val();
                if (mode === 'manual') {
                    $('.pur-checkbox').prop('disabled', false);
                    $('.pur-alloc-input').prop('readonly', false).css('background', '#ffffff');
                } else {
                    $('.pur-checkbox').prop('disabled', true);
                    $('.pur-alloc-input').prop('readonly', true).css('background', '#f8fafc');
                }
                recalculatePurchases();
            });

            // Checkbox click in Manual Mode
            $(document).on('change', '.pur-checkbox', function() {
                let $row = $(this).closest('.purchase-row');
                let due = parseFloat($row.data('due')) || 0;
                let $allocInput = $row.find('.pur-alloc-input');

                if ($(this).is(':checked')) {
                    $allocInput.val(due.toFixed(2));
                } else {
                    $allocInput.val('0.00');
                }
                recalculatePurchases();
            });

            // Select All Purchases in Manual Mode
            $('#selectAllPurchases').on('change', function() {
                let isChecked = $(this).is(':checked');
                let mode = $('input[name="allocation_mode"]:checked').val() || 'fifo';
                if (mode === 'manual') {
                    $('.pur-checkbox').each(function() {
                        $(this).prop('checked', isChecked);
                        let $row = $(this).closest('.purchase-row');
                        let due = parseFloat($row.data('due')) || 0;
                        $row.find('.pur-alloc-input').val(isChecked ? due.toFixed(2) : '0.00');
                    });
                    recalculatePurchases();
                }
            });

            // Manual Allocation Input changes
            $(document).on('input change', '.pur-alloc-input', function() {
                let due = parseFloat($(this).data('due')) || 0;
                let val = parseFloat($(this).val()) || 0;

                if (val < 0) {
                    $(this).val('0.00');
                } else if (val > due) {
                    $(this).val(due.toFixed(2));
                }
                recalculatePurchases();
            });

            // Match Manual Settled Total to Voucher Payment Row Amount button
            $('#btnSyncToPaymentAmount').on('click', function() {
                let totalAllocated = 0;
                $('.purchase-row').each(function() {
                    if ($(this).find('.pur-checkbox').is(':checked')) {
                        totalAllocated += parseFloat($(this).find('.pur-alloc-input').val()) || 0;
                    }
                });

                if (totalAllocated > 0) {
                    let $firstAmount = $('#voucherTable tbody tr:first').find('.amount');
                    $firstAmount.val(totalAllocated.toFixed(2));
                    calculateTotal();
                }
            });

            // Before submit, ensure inputs for allocated purchases are enabled
            $('#paymentForm').on('submit', function() {
                $('.purchase-row').each(function() {
                    let isChecked = $(this).find('.pur-checkbox').is(':checked');
                    let allocVal = parseFloat($(this).find('.pur-alloc-input').val()) || 0;
                    if (isChecked && allocVal > 0) {
                        $(this).find('.pur-id-input').prop('disabled', false);
                        $(this).find('.pur-alloc-input').prop('disabled', false);
                    } else {
                        $(this).find('.pur-id-input').prop('disabled', true);
                        $(this).find('.pur-alloc-input').prop('disabled', true);
                    }
                });
            });
        });
    </script>
@endsection
