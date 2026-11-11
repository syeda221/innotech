@extends('admin_panel.layout.app')

@section('title', 'Vendor - Ledger')

@section('content')
<style>
    /* Standardized Sale Report Pattern Styling */
    .sale-report-container {
        padding: 10px 14px;
        background: #f1f5f9;
        min-height: calc(100vh - 75px);
    }
    .sale-filter-label {
        margin-right: 4px !important;
        margin-bottom: 0 !important;
        white-space: nowrap;
        font-weight: 700;
        font-size: .78rem;
        color: #475569;
    }
    .summary-pill-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        overflow-x: auto;
        white-space: nowrap;
    }
    .stat-pill {
        flex: 1 1 0px;
        padding: 6px 10px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        text-align: center;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .stat-pill .stat-label {
        font-size: .60rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 1px;
    }
    .stat-pill .stat-val {
        font-size: .88rem;
        font-weight: 800;
        line-height: 1.2;
    }

    /* Mobile Cards Styling */
    .mob-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
    }
    .mob-metric-card {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        padding: 10px 6px;
        text-align: center;
        height: 100%;
    }
    .mob-metric-label {
        font-size: 11px;
        color: #64748b;
        font-weight: 600;
    }
    .mob-metric-val {
        font-size: 14px;
        font-weight: 800;
    }

    /* Table Styling with Sticky Header */
    .sale-table-wrap {
        height: calc(100vh - 250px);
        max-height: calc(100vh - 250px);
        min-height: 380px;
        overflow-y: auto;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
    }
    .report-table {
        font-size: .78rem;
        margin-bottom: 0;
    }
    .report-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #1e293b !important;
        color: #ffffff !important;
        font-size: .75rem;
        font-weight: 700;
        padding: 9px 10px;
        border-bottom: 2px solid #334155;
        white-space: nowrap;
    }

    .balance-positive {
        color: #16a34a;
        font-weight: bold;
    }
    .balance-negative {
        color: #dc2626;
        font-weight: bold;
    }

    @media print {
        body { background: #ffffff !important; font-size: 12px; color: #000; margin: 0; padding: 0; }
        .no-print, .rt_nav_header, header, .sidebar, .navbar, footer, #ledgerFormDesk, #ledgerFormMob, .summary-pill-bar, #ledgerHeader, .sale-table-wrap, #ledgerMobileContainer, .d-md-none.no-print { display: none !important; }
        .sale-report-container { padding: 0 !important; background: #fff !important; min-height: auto; }
        .card { border: none !important; box-shadow: none !important; margin: 0 !important; padding: 0 !important; }
        .print-container { display: block !important; width: 100%; }
        
        .print-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .print-table th { border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 8px 4px; font-weight: bold; text-align: center; }
        .print-table td { border-bottom: 1px solid #eaeaea; padding: 6px 4px; text-align: center; }
        .print-table tr:last-child td { border-bottom: 2px solid #000; }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        .fw-bold { font-weight: bold !important; }
    }
</style>

<div class="sale-report-container">

    {{-- DESKTOP FILTER HEADER CARD (d-none d-md-block Standard Pattern) --}}
    <div class="card border-0 shadow-sm mb-2 no-print d-none d-md-block" style="border-radius: 10px;">
        <div class="card-body py-2 px-3">
            <form id="ledgerFormDesk">
                
                {{-- Top Section: Left Title, Mid Dates with Gap, Last Buttons --}}
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-2 pb-2 border-bottom">
                    
                    {{-- Left Title --}}
                    <div class="d-flex align-items-center me-3">
                        <span class="fw-bold text-dark fs-6 text-nowrap" style="letter-spacing: -0.2px;">
                            <i class="fas fa-truck text-primary me-2"></i>Vendor Ledger Report
                        </span>
                    </div>

                    {{-- Mid Dates with Explicit Spacing Gap --}}
                    <div class="d-flex align-items-center me-auto flex-wrap" style="gap: 16px !important;">
                        <div class="d-flex align-items-center gap-1">
                            <label for="start_date_desk" class="sale-filter-label mb-0 ms-1 me-1">Start:</label>
                            <input type="date" name="start_date" id="start_date_desk" class="form-control form-control-sm fw-bold startDateInput" value="2000-01-01" style="height: 32px; width: 135px; font-size: .78rem; border-radius: 6px;">
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <label for="end_date_desk" class="sale-filter-label mb-0 ms-2 me-1">End:</label>
                            <input type="date" name="end_date" id="end_date_desk" class="form-control form-control-sm fw-bold endDateInput" value="{{ date('Y-m-d') }}" style="height: 32px; width: 135px; font-size: .78rem; border-radius: 6px;">
                        </div>
                    </div>

                    {{-- Last Buttons with X-Axis Gap --}}
                    <div class="d-flex align-items-center ms-auto" style="gap: 10px !important;">
                        <button type="button" class="btn btn-primary btn-sm px-3 fw-bold d-inline-flex align-items-center btnSearchTrigger" style="height: 32px; border-radius: 6px; font-size: .78rem; margin-right: 8px !important;">
                            <i class="fas fa-filter me-1"></i> Generate
                        </button>
                        <button type="button" class="btn btn-light border btn-sm px-3 fw-bold text-secondary d-inline-flex align-items-center btnResetTrigger" style="height: 32px; border-radius: 6px; font-size: .78rem; margin-right: 8px !important;">
                            <i class="fas fa-undo me-1"></i> Reset
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-bold d-inline-flex align-items-center btnPrintReport" style="height: 32px; border-radius: 6px; font-size: .78rem;">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>
                </div>

                {{-- Bottom Section: Vendor, Quick Filter --}}
                <div class="row g-2">
                    <div class="col-md-7">
                        <label for="vendor_id_desk" class="sale-filter-label mb-1">Vendor:</label>
                        <select name="vendor_id" id="vendor_id_desk" class="form-select form-select-sm select2 vendorSelect">
                            <option value="all">-- All Vendors --</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="quick_filter_desk" class="sale-filter-label mb-1 d-block">Quick Filter:</label>
                        <select id="quick_filter_desk" class="form-control form-control-sm quickFilterSelect" style="height: 34px !important; font-size: .78rem; border-radius: 6px !important; border: 1px solid #cbd5e1 !important; background-color: #ffffff !important;">
                            <option value="custom">Custom Range</option>
                            <option value="daily">Daily (Today)</option>
                            <option value="weekly">Weekly (This Week)</option>
                            <option value="monthly">Monthly (This Month)</option>
                            <option value="yearly">Yearly (This Year)</option>
                        </select>
                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- MOBILE FILTER HEADER CARD (d-md-none With Top Margin to Prevent Navbar Overlap) --}}
    <div class="card border-0 shadow-sm mb-3 no-print d-md-none mt-2" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form id="ledgerFormMob">
                <div class="row g-2">
                    <div class="col-12 mb-1">
                        <span class="fw-bold text-dark fs-6">
                            <i class="fas fa-truck text-primary me-2"></i>Vendor Ledger
                        </span>
                    </div>

                    {{-- 1. Vendor --}}
                    <div class="col-12 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Vendor</label>
                        <select name="vendor_id" class="form-select form-select-sm select2 vendorSelect">
                            <option value="all">-- All Vendors --</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 2. Quick Filter --}}
                    <div class="col-12 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Quick Filter</label>
                        <select class="form-control form-control-sm quickFilterSelect" style="font-size: 11px; height: 34px !important; border-radius: 6px !important; border: 1px solid #cbd5e1 !important; background-color: #ffffff !important;">
                            <option value="custom">Custom Range</option>
                            <option value="daily">Daily (Today)</option>
                            <option value="weekly">Weekly (This Week)</option>
                            <option value="monthly">Monthly (This Month)</option>
                            <option value="yearly">Yearly (This Year)</option>
                        </select>
                    </div>

                    {{-- 3. Start & End Date --}}
                    <div class="col-6 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Start Date</label>
                        <input type="date" name="start_date" id="start_date_mob" class="form-control form-control-sm startDateInput" value="2000-01-01" style="font-size: 11px;">
                    </div>
                    <div class="col-6 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">End Date</label>
                        <input type="date" name="end_date" id="end_date_mob" class="form-control form-control-sm endDateInput" value="{{ date('Y-m-d') }}" style="font-size: 11px;">
                    </div>

                    {{-- 4. Full Width Generate Button --}}
                    <div class="col-12 my-2">
                        <button type="button" class="btn btn-primary w-100 py-2 fw-bold rounded-3 shadow-sm btnSearchTrigger" style="background-color: #3b82f6; border-color: #3b82f6; font-size: 13px;">
                            <i class="fas fa-filter me-1"></i> Generate Report
                        </button>
                    </div>

                    {{-- 5. Centralized Reset & Print Actions With Horizontal Gap --}}
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-center gap-2 pt-1" style="gap: 10px !important;">
                            <button type="button" class="btn btn-light border btn-sm flex-fill fw-bold text-secondary btnResetTrigger" style="font-size: 11px; margin-right: 8px !important;">
                                <i class="fas fa-undo me-1"></i> Reset
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill fw-bold btnPrintReport" style="font-size: 11px;">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- DESKTOP SUMMARY METRIC PILL BAR (d-none d-md-block) --}}
    <div class="card border-0 shadow-sm mb-2 d-none d-md-block" style="border-radius: 10px; background: #ffffff;">
        <div class="card-body p-2">
            <div class="summary-pill-bar">
                
                <div class="stat-pill" style="background: #f8fafc; border-color: #cbd5e1;">
                    <div class="stat-label text-muted">Opening Balance</div>
                    <div class="stat-val text-dark" id="pillOpeningBalance">Rs 0.00</div>
                </div>

                <div class="stat-pill" style="background: #f0fdf4; border-color: #86efac;">
                    <div class="stat-label text-success">Total Debit (Dr)</div>
                    <div class="stat-val text-success" id="pillTotalDebit">Rs 0.00</div>
                </div>

                <div class="stat-pill" style="background: #fef2f2; border-color: #fca5a5;">
                    <div class="stat-label text-danger">Total Credit (Cr)</div>
                    <div class="stat-val text-danger" id="pillTotalCredit">Rs 0.00</div>
                </div>

                <div class="stat-pill" style="background: #f0f9ff; border-color: #bae6fd;">
                    <div class="stat-label text-info">Net Closing Balance</div>
                    <div class="stat-val text-primary" id="pillClosingBalance">Rs 0.00</div>
                </div>

            </div>
        </div>
    </div>

    {{-- MOBILE SUMMARY METRIC GRID (2 Columns col-6 d-md-none) --}}
    <div class="row g-2 mb-3 d-md-none no-print px-1">
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-wallet text-muted me-1"></i>Opening</span>
                <div class="mob-metric-val text-dark mt-1" id="mobPillOpeningBalance">Rs 0.00</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-arrow-up text-success me-1"></i>Debit (Dr)</span>
                <div class="mob-metric-val text-success mt-1" id="mobPillTotalDebit">Rs 0.00</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-arrow-down text-danger me-1"></i>Credit (Cr)</span>
                <div class="mob-metric-val text-danger mt-1" id="mobPillTotalCredit">Rs 0.00</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-balance-scale text-primary me-1"></i>Closing</span>
                <div class="mob-metric-val text-primary mt-1" id="mobPillClosingBalance">Rs 0.00</div>
            </div>
        </div>
    </div>

    {{-- Report Content Box --}}
    <div id="loader" style="display:none; text-align:center; padding: 20px;">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="small text-muted mt-2">Generating Vendor Ledger Report…</div>
    </div>

    <div id="ledgerBox" style="display:none;">
        
        {{-- Report Sub-Header --}}
        <div class="card border-0 shadow-sm mb-2 rounded-3 bg-white">
            <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2" id="ledgerHeader">
            </div>
        </div>

        {{-- DESKTOP TABLE VIEW (d-none d-md-block) --}}
        <div class="card border-0 shadow-sm mb-3 rounded-3 bg-white d-none d-md-block">
            <div class="card-body p-0">
                <div class="sale-table-wrap">
                    <table class="table table-bordered table-hover align-middle mb-0 report-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;">Date</th>
                                <th style="width: 12%;">Ref / Invoice</th>
                                <th style="width: 18%;">Vendor</th>
                                <th>Description</th>
                                <th style="width: 12%;">Debit (Dr)</th>
                                <th style="width: 12%;">Credit (Cr)</th>
                                <th style="width: 14%;">Balance</th>
                            </tr>
                        </thead>
                        <tbody id="ledgerBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- MOBILE CARDS CONTAINER (d-md-none) --}}
        <div class="d-md-none no-print" id="ledgerMobileContainer">
        </div>

        {{-- PRINT ONLY CONTAINER --}}
        <div class="d-none print-container">
            <div style="text-align: center; margin-bottom: 15px;">
                <h2 style="font-weight: 900; margin: 0; font-size: 20px; text-transform: uppercase;">{{ \App\Models\Setting::get('company_name', 'WHITE DIAMOND (PACKAGES PRIVATE LIMITED)') }}</h2>
                <p style="margin: 0; font-size: 11px;">{{ \App\Models\Setting::get('company_address', 'Hanif Garden Dry Port Road Near Soha Mall Faisalabad') }}</p>
                <p style="margin: 0; font-size: 11px;">Tel: {{ \App\Models\Setting::get('company_phone', '+92 3216293333 | +92 300 7995500') }} | Web: {{ \App\Models\Setting::get('company_website', 'whitediamondpack.com') }}</p>
                <h5 style="font-weight: 800; margin-top: 15px; margin-bottom: 5px; text-transform: uppercase;">VENDOR LEDGER</h5>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 11px;">
                <div style="font-weight: bold;">
                    Account No : - <br>
                    <span id="printVendName" style="text-transform: uppercase; font-size: 12px;">ALL VENDORS</span>
                </div>
                <div style="text-align: right; font-weight: bold;">
                    Date : <span id="printDateRangeVal">All</span> <br>
                    Currency : PKR <br>
                    Total Due : PKR <span id="printTotalDueVal">0.00</span>
                </div>
            </div>

            <table class="print-table">
                <thead>
                    <tr>
                        <th class="text-left" style="width: 10%;">Date</th>
                        <th class="text-left" style="width: 14%;">Type</th>
                        <th class="text-left" style="width: 12%;">Invoice No.</th>
                        <th class="text-left" style="width: 24%;">Details</th>
                        <th class="text-center" style="width: 12%;">Vendor Invoice</th>
                        <th class="text-right" style="width: 9%;">Debit</th>
                        <th class="text-right" style="width: 9%;">Credit</th>
                        <th class="text-right" style="width: 10%;">Balance</th>
                    </tr>
                </thead>
                <tbody id="printLedgerBody">
                </tbody>
            </table>
        </div>

    </div>

</div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            if ($('.select2').length > 0) {
                $('.select2').select2({ width: '100%' });
            }

            // Sync Inputs between Desktop & Mobile
            $('.startDateInput').on('change', function() { $('.startDateInput').val($(this).val()); });
            $('.endDateInput').on('change', function() { $('.endDateInput').val($(this).val()); });
            $('.vendorSelect').on('change', function() { $('.vendorSelect').val($(this).val()); });
            $('.quickFilterSelect').on('change', function() {
                let val = $(this).val();
                $('.quickFilterSelect').val(val);
                let today = new Date();
                let start = new Date();
                let end = new Date();

                if (val === 'daily') {
                } else if (val === 'weekly') {
                    let day = today.getDay();
                    let diff = today.getDate() - day + (day === 0 ? -6 : 1);
                    start.setDate(diff);
                } else if (val === 'monthly') {
                    start.setDate(1);
                } else if (val === 'yearly') {
                    start.setMonth(0, 1);
                } else if (val === 'custom') {
                    return;
                }

                let formatDate = (d) => {
                    let m = '' + (d.getMonth() + 1), day = '' + d.getDate(), y = d.getFullYear();
                    if (m.length < 2) m = '0' + m;
                    if (day.length < 2) day = '0' + day;
                    return [y, m, day].join('-');
                };

                $('.startDateInput').val(formatDate(start));
                $('.endDateInput').val(formatDate(end));
                loadLedger();
            });

            // Auto-load ledger on page load
            loadLedger();

            $(document).on('click', '.btnSearchTrigger', function() {
                loadLedger();
            });

            $('.btnResetTrigger').on('click', function() {
                $('.startDateInput').val('2000-01-01');
                $('.endDateInput').val('{{ date("Y-m-d") }}');
                $('.vendorSelect').val('all').trigger('change');
                $('.quickFilterSelect').val('custom');
                loadLedger();
            });

            function updateReportTitle(vendorName) {
                let name = vendorName;
                if (!name) {
                    if ($('#printVendName').length && $('#printVendName').text().trim()) {
                        name = $('#printVendName').text().trim();
                    } else if ($('.vendorSelect option:selected').length) {
                        name = $('.vendorSelect option:selected').text().replace(/^--\s*|\s*--$/g, '').trim();
                    }
                }
                if (!name) {
                    name = 'Vendor';
                }
                let cleanName = name.replace(/[\/\\:*?"<>|]/g, ' ').replace(/\s+/g, ' ').trim();
                let finalTitle = cleanName + ' - Ledger';
                document.title = finalTitle;
                return finalTitle;
            }

            $('.btnPrintReport').on('click', function() {
                updateReportTitle();
                window.print();
            });

            window.addEventListener('beforeprint', function() {
                updateReportTitle();
            });

            function loadLedger() {
                let vid = $(".vendorSelect").val();
                let start = $(".startDateInput").val() || '2000-01-01';
                let end = $(".endDateInput").val() || '{{ date("Y-m-d") }}';

                $("#loader").show();
                $("#ledgerBox").hide();

                $.get("{{ route('report.vendor.ledger.fetch') }}", {
                    vendor_id: vid || 'all',
                    start_date: start,
                    end_date: end
                }, function(res) {
                    $("#loader").hide();
                    $("#ledgerBox").show();

                    let displayStart = formatDisplayDate(start);
                    let displayEnd = formatDisplayDate(end);

                    updateReportTitle(res.vendor ? res.vendor.name : null);

                    window.currentLedgerResponse = res;
                    let bothBadge = '';
                    let navTabs = '';
                    if (res.is_both) {
                        bothBadge = `<span class="badge bg-primary text-white me-2 px-2 py-1"><i class="fas fa-sync-alt me-1"></i>Both: Vendor &amp; Customer</span>`;
                        navTabs = `
                            <ul class="nav nav-pills mt-2" id="partyLedgerTabs" role="tablist" style="font-size: 0.85rem;">
                                <li class="nav-item">
                                    <button class="nav-link active py-1 px-3 fw-bold" id="btnTabVendorOnly" type="button"><i class="fas fa-truck me-1"></i>Vendor Statement (Purchases &amp; Payments)</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-1 px-3 fw-bold text-primary" id="btnTabCombinedParty" type="button" style="background:#eef2ff;"><i class="fas fa-layer-group me-1"></i>Combined Consolidated Statement (Sales &amp; Purchases Net)</button>
                                </li>
                            </ul>
                        `;
                    }

                    // Build Header
                    $("#ledgerHeader").html(`
                        <div class="w-100">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">${res.vendor.name} ${bothBadge}</h6>
                                    <small class="text-muted">Period: <strong>${displayStart}</strong> to <strong>${displayEnd}</strong></small>
                                </div>
                                <div>
                                     <span class="badge bg-primary text-white p-2 shadow-sm font-monospace">Statement of Account</span>
                                </div>
                            </div>
                            ${navTabs}
                        </div>
                    `);

                    let totalDebit = 0;
                    let totalCredit = 0;
                    let lastBalance = parseFloat(res.opening_balance);

                    // Update Top Metrics (Desktop & Mobile)
                    let formattedOpening = 'Rs ' + lastBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    $('#pillOpeningBalance, #mobPillOpeningBalance').text(formattedOpening);

                    // Desktop Opening Row
                    let html = `
                        <tr class="bg-light fw-bold">
                            <td class="text-center">-</td>
                            <td class="text-center">-</td>
                            <td class="text-center">-</td>
                            <td class="text-start">Opening Balance (B/F)</td>
                            <td class="text-end">-</td>
                            <td class="text-end">-</td>
                            <td class="text-end text-dark">
                                Rs ${lastBalance.toLocaleString(undefined, {minimumFractionDigits: 2})} 
                            </td>
                        </tr>
                    `;

                    let mobHtml = `
                        <div class="mob-card p-2.5 p-2 mb-2 bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-dark" style="font-size: 12.5px;">Opening Balance (B/F)</strong>
                                <strong class="text-dark" style="font-size: 13px;">Rs ${lastBalance.toLocaleString(undefined, {minimumFractionDigits: 2})}</strong>
                            </div>
                        </div>
                    `;

                    res.transactions.forEach((t, i) => {
                        let debit = t.debit && t.debit > 0 ? parseFloat(t.debit) : 0;
                        let credit = t.credit && t.credit > 0 ? parseFloat(t.credit) : 0;
                        totalDebit += debit;
                        totalCredit += credit;
                        lastBalance = parseFloat(t.balance);

                        let balLabel = lastBalance >= 0 ? 'Cr' : 'Dr';
                        let balClass = lastBalance >= 0 ? 'balance-negative' : 'balance-positive';
                        let vendName = t.vendor_name || '-';

                        let invoiceBadge = t.invoice_badges_html 
                            ? t.invoice_badges_html 
                            : (t.invoice_url 
                                ? `<a href="${t.invoice_url}" target="_blank" class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace text-decoration-none py-1 px-2 shadow-sm" title="View & Print Invoice" style="cursor: pointer;"><i class="fas fa-file-invoice me-1"></i>${t.invoice} <i class="fas fa-external-link-alt ms-1" style="font-size: 8.5px;"></i></a>`
                                : `<span class="badge bg-light text-primary border font-monospace">${t.invoice ?? '-'}</span>`);

                        let mobInvoiceBadge = t.invoice_badges_html 
                            ? t.invoice_badges_html 
                            : (t.invoice_url 
                                ? `<a href="${t.invoice_url}" target="_blank" class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace text-decoration-none" title="View & Print Invoice"><i class="fas fa-file-invoice me-1"></i>${t.invoice} <i class="fas fa-external-link-alt ms-1" style="font-size: 8.5px;"></i></a>`
                                : `<span class="badge bg-light text-primary border font-monospace">${t.invoice ?? 'REF'}</span>`);

                        // Desktop Row
                        html += `
                            <tr>
                                <td class="text-center small text-nowrap">${t.date}</td>
                                <td class="text-center">${invoiceBadge}</td>
                                <td class="fw-bold text-dark">${vendName}</td>
                                <td class="text-start">${t.description}</td>
                                <td class="text-end text-success fw-semibold">${debit > 0 ? 'Rs ' + debit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</td>
                                <td class="text-end text-danger fw-semibold">${credit > 0 ? 'Rs ' + credit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</td>
                                <td class="text-end fw-bold ${balClass}">
                                    Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} 
                                    <small class="text-muted" style="font-size:0.75em">${balLabel}</small>
                                </td>
                            </tr>
                        `;

                        // Mobile Card
                        mobHtml += `
                            <div class="mob-card p-2.5 p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    ${mobInvoiceBadge}
                                    <small class="text-muted" style="font-size: 10.5px;">${t.date}</small>
                                </div>
                                <div class="mb-1">
                                    <strong class="text-dark d-block" style="font-size: 12.5px;">${vendName}</strong>
                                    <small class="text-muted d-block" style="font-size: 11px;">${t.description}</small>
                                </div>
                                <div class="border-top pt-2 mt-1">
                                    <div class="row g-1 text-center" style="font-size: 11px;">
                                        <div class="col-4 border-end">
                                            <span class="text-muted d-block" style="font-size: 10px;">Debit (Dr)</span>
                                            <strong class="text-success">${debit > 0 ? 'Rs ' + debit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</strong>
                                        </div>
                                        <div class="col-4 border-end">
                                            <span class="text-muted d-block" style="font-size: 10px;">Credit (Cr)</span>
                                            <strong class="text-danger">${credit > 0 ? 'Rs ' + credit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</strong>
                                        </div>
                                        <div class="col-4">
                                            <span class="text-muted d-block" style="font-size: 10px;">Balance</span>
                                            <strong class="${balClass}">Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} <small>${balLabel}</small></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    // Totals Row
                    html += `
                        <tr class="fw-bold bg-light">
                            <td colspan="4" class="text-end text-dark">Totals:</td>
                            <td class="text-end text-success">Rs ${totalDebit.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                            <td class="text-end text-danger">Rs ${totalCredit.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                            <td class="text-end ${lastBalance >= 0 ? 'balance-negative' : 'balance-positive'}">Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} ${lastBalance >= 0 ? 'Cr' : 'Dr'}</td>
                        </tr>
                    `;

                    $("#ledgerBody").html(html);
                    $("#ledgerMobileContainer").html(mobHtml);

                    // Update Top Summary Pills (Desktop & Mobile)
                    let formattedDebit   = 'Rs ' + totalDebit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    let formattedCredit  = 'Rs ' + totalCredit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    let formattedClosing = 'Rs ' + Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ' + (lastBalance >= 0 ? 'Cr' : 'Dr');

                    $('#pillTotalDebit, #mobPillTotalDebit').text(formattedDebit);
                    $('#pillTotalCredit, #mobPillTotalCredit').text(formattedCredit);
                    $('#pillClosingBalance, #mobPillClosingBalance').text(formattedClosing);

                    // --- POPULATE PRINT LAYOUT ---
                    $('#printVendName').text(res.vendor.name);
                    $('#printDateRangeVal').text(displayStart + ' to ' + displayEnd);
                    $('#printTotalDueVal').text(lastBalance.toLocaleString(undefined, {minimumFractionDigits: 2}));

                    let printHtml = `
                        <tr>
                            <td class="text-left fw-bold">-</td>
                            <td class="text-left fw-bold">Opening Balance</td>
                            <td class="text-center fw-bold">-</td>
                            <td class="text-left fw-bold">Opening Balance (B/F)</td>
                            <td class="text-center fw-bold">-</td>
                            <td class="text-right fw-bold">-</td>
                            <td class="text-right fw-bold">-</td>
                            <td class="text-right fw-bold">${parseFloat(res.opening_balance).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        </tr>
                    `;

                    res.transactions.forEach((t) => {
                        let debit = t.debit && t.debit > 0 ? parseFloat(t.debit) : 0;
                        let credit = t.credit && t.credit > 0 ? parseFloat(t.credit) : 0;
                        let bal = parseFloat(t.balance);

                        // Extract Type and Description
                        let type = 'Journal Entry';
                        let refDesc = t.description || '';
                        
                        if (refDesc.toLowerCase().includes('payment') || refDesc.toLowerCase().includes('receipt') || t.source_type === 'Payment') {
                            type = 'Payment';
                        } else if (refDesc.toLowerCase().includes('purchase return') || t.source_type === 'PurchaseReturn') {
                            type = 'Purchase Return';
                        } else if (refDesc.toLowerCase().includes('purchase invoice') || t.source_type === 'Purchase') {
                            type = 'Purchase Invoice';
                        } else if (refDesc.toLowerCase().includes('sale invoice')) {
                            type = 'Sale Invoice';
                        }
                        
                        // Look for A/C: bank name
                        let acMatch = refDesc.match(/\[A\/C:\s*([^\]]+)\]/);
                        if (acMatch) {
                            refDesc = refDesc.replace(acMatch[0], '').trim();
                        }
                        
                        // Format date to DD-MMM-YYYY (e.g. 14-Sep-2026)
                        let dStr = t.date;
                        if (t.sort_date) {
                            let dObj = new Date(t.sort_date);
                            if (!isNaN(dObj)) {
                                let months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                                dStr = ('0' + dObj.getDate()).slice(-2) + '-' + months[dObj.getMonth()] + '-' + dObj.getFullYear();
                            }
                        }

                        let invNo = (t.invoice_no && t.invoice_no !== '-') ? t.invoice_no : (t.invoice && t.invoice !== '-' ? t.invoice : '-');
                        let vendorInv = (t.vendor_invoice_no && t.vendor_invoice_no !== '') ? t.vendor_invoice_no : '-';

                        printHtml += `
                            <tr>
                                <td class="text-left">${dStr}</td>
                                <td class="text-left">${type}</td>
                                <td class="text-left font-monospace">${invNo}</td>
                                <td class="text-left">${refDesc}</td>
                                <td class="text-center font-monospace">${vendorInv}</td>
                                <td class="text-right">${debit > 0 ? debit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</td>
                                <td class="text-right">${credit > 0 ? credit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</td>
                                <td class="text-right">${bal.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                            </tr>
                        `;
                    });

                    printHtml += `
                        <tr>
                            <td colspan="5" class="text-right fw-bold">Total:</td>
                            <td class="text-right fw-bold">${totalDebit.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                            <td class="text-right fw-bold">${totalCredit.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                            <td class="text-right fw-bold">${lastBalance.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        </tr>
                    `;
                    $('#printLedgerBody').html(printHtml);
                    // -----------------------------
                }).fail(function() {
                    $("#loader").hide();
                    alert("Error loading report data.");
                });
            }

            function formatDisplayDate(dateStr) {
                if (!dateStr) return '-';
                let d = new Date(dateStr);
                let months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                return d.getDate().toString().padStart(2, '0') + '-' + months[d.getMonth()] + '-' + d.getFullYear();
            }
            // Toggle between Vendor Ledger and Combined Consolidated Ledger
            $(document).on('click', '#btnTabVendorOnly', function() {
                $('#partyLedgerTabs button').removeClass('active');
                $(this).addClass('active');
                if (window.currentLedgerResponse) {
                    renderVendorView(window.currentLedgerResponse);
                }
            });

            $(document).on('click', '#btnTabCombinedParty', function() {
                $('#partyLedgerTabs button').removeClass('active');
                $(this).addClass('active');
                if (window.currentLedgerResponse && window.currentLedgerResponse.combined_ledger) {
                    renderCombinedView(window.currentLedgerResponse.combined_ledger);
                }
            });

            function renderCombinedView(combined) {
                let lastBalance = parseFloat(combined.opening_balance || 0);
                let totalDebit = 0;
                let totalCredit = 0;

                let formattedOpening = 'Rs ' + Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ' + (lastBalance >= 0 ? '(Dr - Lene)' : '(Cr - Dene)');
                $('#pillOpeningBalance, #mobPillOpeningBalance').text(formattedOpening);

                let html = `
                    <tr class="bg-light fw-bold">
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center"><span class="badge bg-secondary">Combined</span></td>
                        <td class="text-start">Net Opening Balance (B/F)</td>
                        <td class="text-end">-</td>
                        <td class="text-end">-</td>
                        <td class="text-end text-dark">
                            Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} <small class="text-muted">${lastBalance >= 0 ? 'Dr (Lene)' : 'Cr (Dene)'}</small>
                        </td>
                    </tr>
                `;

                let mobHtml = `
                    <div class="mob-card p-2.5 p-2 mb-2 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-dark" style="font-size: 12.5px;">Net Opening Balance (B/F)</strong>
                            <strong class="text-dark" style="font-size: 13px;">Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} ${lastBalance >= 0 ? 'Dr' : 'Cr'}</strong>
                        </div>
                    </div>
                `;

                (combined.transactions || []).forEach(t => {
                    let debit = t.debit && t.debit > 0 ? parseFloat(t.debit) : 0;
                    let credit = t.credit && t.credit > 0 ? parseFloat(t.credit) : 0;
                    totalDebit += debit;
                    totalCredit += credit;
                    lastBalance = parseFloat(t.balance);

                    let balLabel = lastBalance >= 0 ? 'Dr (Lene hain)' : 'Cr (Dene hain)';
                    let balClass = lastBalance >= 0 ? 'balance-positive' : 'balance-negative';
                    let typeBadge = t.type === 'Customer' ? '<span class="badge bg-primary">Sale / Cust</span>' : '<span class="badge bg-warning text-dark">Purch / Vend</span>';

                    html += `
                        <tr>
                            <td class="text-center small text-nowrap">${t.date}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border">${t.ref_no}</span></td>
                            <td class="text-center">${typeBadge}</td>
                            <td class="text-start">${t.description}</td>
                            <td class="text-end text-danger fw-semibold">${debit > 0 ? 'Rs ' + debit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</td>
                            <td class="text-end text-success fw-semibold">${credit > 0 ? 'Rs ' + credit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</td>
                            <td class="text-end fw-bold ${balClass}">
                                Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} 
                                <small class="d-block text-muted" style="font-size:0.75em">${balLabel}</small>
                            </td>
                        </tr>
                    `;

                    mobHtml += `
                        <div class="mob-card p-2.5 p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>${typeBadge} <span class="badge bg-light text-dark border ms-1">${t.ref_no}</span></div>
                                <small class="text-muted" style="font-size: 10.5px;">${t.date}</small>
                            </div>
                            <div class="mb-1">
                                <small class="text-muted d-block" style="font-size: 11px;">${t.description}</small>
                            </div>
                            <div class="border-top pt-2 mt-1">
                                <div class="row g-1 text-center" style="font-size: 11px;">
                                    <div class="col-4 border-end">
                                        <span class="text-muted d-block" style="font-size: 10px;">Debit (Dr)</span>
                                        <strong class="text-danger">${debit > 0 ? 'Rs ' + debit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</strong>
                                    </div>
                                    <div class="col-4 border-end">
                                        <span class="text-muted d-block" style="font-size: 10px;">Credit (Cr)</span>
                                        <strong class="text-success">${credit > 0 ? 'Rs ' + credit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</strong>
                                    </div>
                                    <div class="col-4">
                                        <span class="text-muted d-block" style="font-size: 10px;">Net Balance</span>
                                        <strong class="${balClass}">Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} <small>${lastBalance >= 0 ? 'Dr' : 'Cr'}</small></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += `
                    <tr class="fw-bold bg-light">
                        <td colspan="4" class="text-end text-dark">Combined Totals:</td>
                        <td class="text-end text-danger">Rs ${totalDebit.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-end text-success">Rs ${totalCredit.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-end ${lastBalance >= 0 ? 'balance-positive' : 'balance-negative'}">Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} ${lastBalance >= 0 ? 'Dr (Lene)' : 'Cr (Dene)'}</td>
                    </tr>
                `;

                $("#ledgerBody").html(html);
                $("#ledgerMobileContainer").html(mobHtml);

                $('#pillTotalDebit, #mobPillTotalDebit').text('Rs ' + totalDebit.toLocaleString(undefined, {minimumFractionDigits: 2}));
                $('#pillTotalCredit, #mobPillTotalCredit').text('Rs ' + totalCredit.toLocaleString(undefined, {minimumFractionDigits: 2}));
                $('#pillClosingBalance, #mobPillClosingBalance').text('Rs ' + Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ' + (lastBalance >= 0 ? 'Dr (Lene)' : 'Cr (Dene)'));
            }

            function renderVendorView(res) {
                let totalDebit = 0;
                let totalCredit = 0;
                let lastBalance = parseFloat(res.opening_balance);

                let formattedOpening = 'Rs ' + lastBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                $('#pillOpeningBalance, #mobPillOpeningBalance').text(formattedOpening);

                let html = `
                    <tr class="bg-light fw-bold">
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-start">Opening Balance (B/F)</td>
                        <td class="text-end">-</td>
                        <td class="text-end">-</td>
                        <td class="text-end text-dark">
                            Rs ${lastBalance.toLocaleString(undefined, {minimumFractionDigits: 2})} 
                        </td>
                    </tr>
                `;

                let mobHtml = `
                    <div class="mob-card p-2.5 p-2 mb-2 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-dark" style="font-size: 12.5px;">Opening Balance (B/F)</strong>
                            <strong class="text-dark" style="font-size: 13px;">Rs ${lastBalance.toLocaleString(undefined, {minimumFractionDigits: 2})}</strong>
                        </div>
                    </div>
                `;

                res.transactions.forEach((t, i) => {
                    let debit = t.debit && t.debit > 0 ? parseFloat(t.debit) : 0;
                    let credit = t.credit && t.credit > 0 ? parseFloat(t.credit) : 0;
                    totalDebit += debit;
                    totalCredit += credit;
                    lastBalance = parseFloat(t.balance);

                    let balLabel = lastBalance >= 0 ? 'Cr' : 'Dr';
                    let balClass = lastBalance >= 0 ? 'balance-negative' : 'balance-positive';
                    let vendName = t.vendor_name || '-';

                    let invoiceBadge = t.invoice_badges_html 
                        ? t.invoice_badges_html 
                        : (t.invoice_url 
                            ? `<a href="${t.invoice_url}" target="_blank" class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace text-decoration-none py-1 px-2 shadow-sm" title="View & Print Invoice" style="cursor: pointer;"><i class="fas fa-file-invoice me-1"></i>${t.invoice} <i class="fas fa-external-link-alt ms-1" style="font-size: 8.5px;"></i></a>`
                            : `<span class="badge bg-light text-primary border font-monospace">${t.invoice ?? '-'}</span>`);

                    let mobInvoiceBadge = t.invoice_badges_html 
                        ? t.invoice_badges_html 
                        : (t.invoice_url 
                            ? `<a href="${t.invoice_url}" target="_blank" class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace text-decoration-none" title="View & Print Invoice"><i class="fas fa-file-invoice me-1"></i>${t.invoice} <i class="fas fa-external-link-alt ms-1" style="font-size: 8.5px;"></i></a>`
                            : `<span class="badge bg-light text-primary border font-monospace">${t.invoice ?? 'REF'}</span>`);

                    html += `
                        <tr>
                            <td class="text-center small text-nowrap">${t.date}</td>
                            <td class="text-center">${invoiceBadge}</td>
                            <td class="fw-bold text-dark">${vendName}</td>
                            <td class="text-start">${t.description}</td>
                            <td class="text-end text-danger fw-semibold">${debit > 0 ? 'Rs ' + debit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</td>
                            <td class="text-end text-success fw-semibold">${credit > 0 ? 'Rs ' + credit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</td>
                            <td class="text-end fw-bold ${balClass}">
                                Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} 
                                <small class="text-muted" style="font-size:0.75em">${balLabel}</small>
                            </td>
                        </tr>
                    `;

                    mobHtml += `
                        <div class="mob-card p-2.5 p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                ${mobInvoiceBadge}
                                <small class="text-muted" style="font-size: 10.5px;">${t.date}</small>
                            </div>
                            <div class="mb-1">
                                <strong class="text-dark d-block" style="font-size: 12.5px;">${vendName}</strong>
                                <small class="text-muted d-block" style="font-size: 11px;">${t.description}</small>
                            </div>
                            <div class="border-top pt-2 mt-1">
                                <div class="row g-1 text-center" style="font-size: 11px;">
                                    <div class="col-4 border-end">
                                        <span class="text-muted d-block" style="font-size: 10px;">Debit (Dr)</span>
                                        <strong class="text-danger">${debit > 0 ? 'Rs ' + debit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</strong>
                                    </div>
                                    <div class="col-4 border-end">
                                        <span class="text-muted d-block" style="font-size: 10px;">Credit (Cr)</span>
                                        <strong class="text-success">${credit > 0 ? 'Rs ' + credit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</strong>
                                    </div>
                                    <div class="col-4">
                                        <span class="text-muted d-block" style="font-size: 10px;">Balance</span>
                                        <strong class="${balClass}">Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} <small>${balLabel}</small></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += `
                    <tr class="fw-bold bg-light">
                        <td colspan="4" class="text-end text-dark">Totals:</td>
                        <td class="text-end text-danger">Rs ${totalDebit.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-end text-success">Rs ${totalCredit.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-end ${lastBalance >= 0 ? 'balance-negative' : 'balance-positive'}">Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} ${lastBalance >= 0 ? 'Cr' : 'Dr'}</td>
                    </tr>
                `;

                $("#ledgerBody").html(html);
                $("#ledgerMobileContainer").html(mobHtml);

                let formattedDebit   = 'Rs ' + totalDebit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                let formattedCredit  = 'Rs ' + totalCredit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                let formattedClosing = 'Rs ' + Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ' + (lastBalance >= 0 ? 'Cr' : 'Dr');

                $('#pillTotalDebit, #mobPillTotalDebit').text(formattedDebit);
                $('#pillTotalCredit, #mobPillTotalCredit').text(formattedCredit);
                $('#pillClosingBalance, #mobPillClosingBalance').text(formattedClosing);
            }
        });
    </script>
@endsection
