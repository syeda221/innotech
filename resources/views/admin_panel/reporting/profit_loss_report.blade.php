@extends('admin_panel.layout.app')

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

    @media print {
        body { background: #ffffff !important; font-size: 11px; }
        .no-print, header, .sidebar, .navbar, footer { display: none !important; }
        .sale-report-container { padding: 0 !important; background: #fff !important; }
        .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; margin-bottom: 10px !important; }
    }
</style>

<div class="sale-report-container">

    {{-- DESKTOP FILTER HEADER CARD (d-none d-md-block Standard Pattern) --}}
    <div class="card border-0 shadow-sm mb-2 no-print d-none d-md-block" style="border-radius: 10px;">
        <div class="card-body py-2 px-3">
            <form id="profitLossFormDesk">
                
                {{-- Top Section: Left Title, Mid Dates with Gap, Last Buttons --}}
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-2 pb-2 border-bottom">
                    
                    {{-- Left Title --}}
                    <div class="d-flex align-items-center me-3">
                        <span class="fw-bold text-dark fs-6 text-nowrap" style="letter-spacing: -0.2px;">
                            <i class="fas fa-chart-pie text-primary me-2"></i>Profit & Loss Analysis
                        </span>
                    </div>

                    {{-- Mid Dates with Explicit Spacing Gap --}}
                    <div class="d-flex align-items-center me-auto flex-wrap" style="gap: 16px !important;">
                        <div class="d-flex align-items-center gap-1">
                            <label for="start_date_desk" class="sale-filter-label mb-0 ms-1 me-1">From:</label>
                            <input type="datetime-local" id="start_date_desk" class="form-control form-control-sm fw-bold startDateInput" value="{{ date('Y-m-d\T00:00') }}" style="height: 32px; width: 175px; font-size: .78rem; border-radius: 6px;">
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <label for="end_date_desk" class="sale-filter-label mb-0 ms-2 me-1">To:</label>
                            <input type="datetime-local" id="end_date_desk" class="form-control form-control-sm fw-bold endDateInput" value="{{ date('Y-m-d\T23:59') }}" style="height: 32px; width: 175px; font-size: .78rem; border-radius: 6px;">
                        </div>
                    </div>

                    {{-- Last Buttons with X-Axis Gap --}}
                    <div class="d-flex align-items-center ms-auto" style="gap: 10px !important;">
                        <button type="button" class="btn btn-primary btn-sm px-3 fw-bold d-inline-flex align-items-center btnSearchTrigger" style="height: 32px; border-radius: 6px; font-size: .78rem; margin-right: 8px !important;">
                            <i class="fas fa-search me-1"></i> Search
                        </button>
                        <button type="button" class="btn btn-light border btn-sm px-3 fw-bold text-secondary d-inline-flex align-items-center btnResetTrigger" style="height: 32px; border-radius: 6px; font-size: .78rem; margin-right: 8px !important;">
                            <i class="fas fa-undo me-1"></i> Reset
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-bold d-inline-flex align-items-center btnPrintReport" style="height: 32px; border-radius: 6px; font-size: .78rem;">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>
                </div>

                {{-- Bottom Section: Category, Customer, Product --}}
                <div class="row g-2">
                    <div class="col-md-4">
                        <label for="category_id_desk" class="sale-filter-label mb-1">Category:</label>
                        <select id="category_id_desk" class="form-select form-select-sm select2 categorySelect">
                            <option value="all">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="customer_id_desk" class="sale-filter-label mb-1">Customer:</label>
                        <select id="customer_id_desk" class="form-select form-select-sm select2 customerSelect">
                            <option value="all">All Customers</option>
                            @foreach($customers as $cus)
                                <option value="{{ $cus->id }}">{{ $cus->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="product_id_desk" class="sale-filter-label mb-1">Product:</label>
                        <select id="product_id_desk" class="form-select form-select-sm select2 productSelect">
                            <option value="all">All Products</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->item_code }} - {{ $prod->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- MOBILE FILTER HEADER CARD (d-md-none With Top Margin) --}}
    <div class="card border-0 shadow-sm mb-3 no-print d-md-none mt-2" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form id="profitLossFormMob">
                <div class="row g-2">
                    <div class="col-12 mb-1">
                        <span class="fw-bold text-dark fs-6">
                            <i class="fas fa-chart-pie text-primary me-2"></i>Profit & Loss
                        </span>
                    </div>

                    {{-- 1. Start & End Date --}}
                    <div class="col-6 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">From</label>
                        <input type="datetime-local" id="start_date_mob" class="form-control form-control-sm startDateInput" value="{{ date('Y-m-d\T00:00') }}" style="font-size: 11px;">
                    </div>
                    <div class="col-6 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">To</label>
                        <input type="datetime-local" id="end_date_mob" class="form-control form-control-sm endDateInput" value="{{ date('Y-m-d\T23:59') }}" style="font-size: 11px;">
                    </div>

                    {{-- 2. Category --}}
                    <div class="col-12 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Category</label>
                        <select class="form-select form-select-sm select2 categorySelect">
                            <option value="all">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 3. Customer --}}
                    <div class="col-12 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Customer</label>
                        <select class="form-select form-select-sm select2 customerSelect">
                            <option value="all">All Customers</option>
                            @foreach($customers as $cus)
                                <option value="{{ $cus->id }}">{{ $cus->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 4. Product --}}
                    <div class="col-12 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Product</label>
                        <select class="form-select form-select-sm select2 productSelect">
                            <option value="all">All Products</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->item_code }} - {{ $prod->item_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 5. Full Width Search Button --}}
                    <div class="col-12 my-2">
                        <button type="button" class="btn btn-primary w-100 py-2 fw-bold rounded-3 shadow-sm btnSearchTrigger" style="background-color: #3b82f6; border-color: #3b82f6; font-size: 13px;">
                            <i class="fas fa-search me-1"></i> Search Report
                        </button>
                    </div>

                    {{-- 6. Centralized Reset & Print Actions With Horizontal Gap --}}
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
                
                <div class="stat-pill" style="background: #f0fdf4; border-color: #86efac;">
                    <div class="stat-label text-success">Gross Profit</div>
                    <div class="stat-val text-success" id="cardGrossProfit">Rs 0.00</div>
                </div>

                <div class="stat-pill" style="background: #fef2f2; border-color: #fca5a5;">
                    <div class="stat-label text-danger">Total Expenses</div>
                    <div class="stat-val text-danger" id="cardExpenses">Rs 0.00</div>
                </div>

                <div class="stat-pill" style="background: #f0f9ff; border-color: #bae6fd;">
                    <div class="stat-label text-primary">Net Profit / Loss</div>
                    <div class="stat-val text-primary" id="cardNetProfit">Rs 0.00</div>
                </div>

            </div>
        </div>
    </div>

    {{-- MOBILE SUMMARY METRIC GRID (2 Columns + Full-Width Net Profit d-md-none) --}}
    <div class="row g-2 mb-3 d-md-none no-print px-1">
        <div class="col-6">
            <div class="mob-metric-card p-2">
                <span class="mob-metric-label" style="font-size: 11px;"><i class="fas fa-arrow-up text-success me-1"></i>Gross Profit</span>
                <div class="mob-metric-val text-success mt-1" id="mobCardGrossProfit" style="font-size: 14px;">Rs 0</div>
            </div>
        </div>
        <div class="col-6">
            <div class="mob-metric-card p-2">
                <span class="mob-metric-label" style="font-size: 11px;"><i class="fas fa-arrow-down text-danger me-1"></i>Expenses</span>
                <div class="mob-metric-val text-danger mt-1" id="mobCardExpenses" style="font-size: 14px;">Rs 0</div>
            </div>
        </div>
        <div class="col-12 mt-2">
            <div class="mob-metric-card p-2" style="background: #f0f9ff; border-color: #bae6fd;">
                <span class="mob-metric-label text-primary" style="font-size: 11px;"><i class="fas fa-balance-scale me-1"></i>Net Profit / Loss</span>
                <div class="mob-metric-val text-primary mt-1" id="mobCardNetProfit" style="font-size: 15px;">Rs 0</div>
            </div>
        </div>
    </div>

    <div id="loader" style="display:none; text-align:center; padding: 20px;">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="small text-muted mt-2">Calculating Profit & Loss Data…</div>
    </div>

    {{-- Content Layout --}}
    <div class="row g-3 mb-3">
        
        {{-- Item Profitability Table --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark fs-6"><i class="fas fa-table me-2 text-primary"></i>Item Profitability</span>
                </div>
                <div class="card-body p-0">
                    
                    {{-- DESKTOP TABLE VIEW (d-none d-md-block) --}}
                    <div class="d-none d-md-block">
                        <div class="sale-table-wrap border-0 rounded-0">
                            <table id="profitTable" class="table table-bordered table-hover align-middle mb-0 report-table" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">CODE</th>
                                        <th>PRODUCT</th>
                                        <th class="text-center" style="width: 75px;">QTY</th>
                                        <th class="text-center" style="width: 80px;">RET</th>
                                        <th class="text-end" style="width: 110px;">REVENUE</th>
                                        <th class="text-end" style="width: 110px;">COGS</th>
                                        <th class="text-end" style="width: 120px;">PROFIT</th>
                                    </tr>
                                </thead>
                                <tbody id="profitBody"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- MOBILE CARDS CONTAINER (d-md-none) --}}
                    <div class="d-md-none p-2" id="mobProfitContainer">
                    </div>

                </div>
            </div>
        </div>

        {{-- Top 5 Profit Share Chart --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-header bg-light py-2 px-3">
                    <span class="fw-bold text-dark fs-6"><i class="fas fa-chart-pie me-2 text-purple" style="color: #8b5cf6;"></i>Top 5 Profit Share</span>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center p-3">
                    <div id="noChartData" class="text-muted small py-4">Fetch data to see chart</div>
                    <canvas id="profitChart" style="max-height: 250px; width:100%; display:none;"></canvas>
                </div>
            </div>
        </div>

    </div>

    {{-- Top 10 Customers --}}
    <div class="card border-0 shadow-sm mb-3 rounded-3 bg-white">
        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark fs-6"><i class="fas fa-trophy me-2 text-warning"></i>Top 10 Customers by Profit</span>
            <span class="badge bg-secondary text-white small" id="customerCount">0 customers</span>
        </div>
        <div class="card-body p-0">
            
            {{-- DESKTOP TABLE VIEW (d-none d-md-block) --}}
            <div class="d-none d-md-block">
                <div class="sale-table-wrap border-0 rounded-0" style="height: auto; max-height: 350px;">
                    <table class="table table-bordered table-hover align-middle mb-0 report-table" id="topCustomersTableContent">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 40px;">#</th>
                                <th>CUSTOMER NAME</th>
                                <th class="text-end" style="width: 140px;">REVENUE</th>
                                <th class="text-end" style="width: 150px;">LEDGER BALANCE</th>
                                <th class="text-end" style="width: 150px;">NET PROFIT</th>
                            </tr>
                        </thead>
                        <tbody id="topCustomersBody">
                            <tr><td colspan="5" class="text-center text-muted py-4 small">Fetch data to see top customers</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- MOBILE CARDS CONTAINER (d-md-none) --}}
            <div class="d-md-none p-2" id="mobTopCustomersContainer">
            </div>

        </div>
    </div>

    {{-- Invoice-wise Profit --}}
    <div class="card border-0 shadow-sm mb-3 rounded-3 bg-white">
        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-bold text-dark fs-6"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Invoice-wise Profit</span>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary text-white small" id="invoiceCount">0 invoices</span>
                <span class="badge bg-success text-white small" id="invoiceTotalProfitBadge">Profit: Rs 0</span>
            </div>
        </div>
        <div class="card-body p-0">

            {{-- DESKTOP TABLE VIEW (d-none d-md-block) --}}
            <div class="d-none d-md-block">
                <div class="sale-table-wrap border-0 rounded-0" style="height: auto; max-height: 460px;">
                    <table id="invoiceProfitTable" class="table table-bordered table-hover align-middle mb-0 report-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th style="width: 130px;">INVOICE #</th>
                                <th style="width: 155px;">DATE</th>
                                <th>CUSTOMER</th>
                                <th class="text-center" style="width: 85px;">ITEMS</th>
                                <th class="text-end" style="width: 125px;">REVENUE</th>
                                <th class="text-end" style="width: 125px;">COGS</th>
                                <th class="text-end" style="width: 155px;">NET PROFIT</th>
                            </tr>
                        </thead>
                        <tbody id="invoiceProfitBody"></tbody>
                    </table>
                </div>
            </div>

            {{-- MOBILE CARDS CONTAINER (d-md-none) --}}
            <div class="d-md-none p-2" id="mobInvoiceProfitContainer">
            </div>

        </div>
    </div>

</div>
@endsection

@section('js')
<script src="{{ asset('assets/vendors/chartjs/chart.umd.min.js') }}"></script>
<script>
$(document).ready(function() {
    if ($('.select2').length > 0) {
        $('.select2').select2({ width: '100%' });
    }

    // Sync Inputs between Desktop & Mobile
    $('.startDateInput').on('change', function() { $('.startDateInput').val($(this).val()); });
    $('.endDateInput').on('change', function() { $('.endDateInput').val($(this).val()); });
    $('.categorySelect').on('change', function() { $('.categorySelect').val($(this).val()); });
    $('.customerSelect').on('change', function() { $('.customerSelect').val($(this).val()); });
    $('.productSelect').on('change', function() { $('.productSelect').val($(this).val()); });

    var profitTable = $('#profitTable').DataTable({
        paging: true,
        pageLength: 15,
        searching: true,
        ordering: true,
        order: [[6, 'desc']],
        language: { search: '', searchPlaceholder: 'Search items...' },
        columns: [
            { data: 'item_code' },
            { data: 'item_name' },
            { data: 'sold_qty', className: 'text-center' },
            { data: 'returned_qty', className: 'text-center' },
            { data: 'revenue', className: 'text-end' },
            { data: 'cogs', className: 'text-end' },
            { data: 'profit', className: 'text-end' }
        ]
    });

    var invoiceProfitTable = $('#invoiceProfitTable').DataTable({
        paging: true,
        pageLength: 15,
        searching: true,
        ordering: true,
        order: [[6, 'desc']],
        language: { search: '', searchPlaceholder: 'Search invoice, customer...' },
        columns: [
            {
                data: 'invoice_no',
                render: function(data, type, row) {
                    if (type === 'display') {
                        return `<a href="${row.invoice_url}" target="_blank" class="fw-bold text-primary font-monospace text-decoration-none">${escHtml(data)}</a>` +
                               `<div class="text-muted" style="font-size:10px;">${escHtml(row.source)}</div>`;
                    }
                    return data;
                }
            },
            {
                data: 'sale_timestamp',
                render: function(data, type, row) {
                    if (type === 'display') {
                        return `<span class="text-dark" style="font-size:12px;">${escHtml(row.sale_date)}</span>`;
                    }
                    return data;
                }
            },
            {
                data: 'customer_name',
                render: function(data, type, row) {
                    if (type === 'display') {
                        return `<div class="fw-bold text-dark" style="font-size:12.5px;">${escHtml(data)}</div>` +
                               `<div class="text-muted" style="font-size:10px;">${escHtml(row.customer_code)}</div>`;
                    }
                    return data + ' ' + row.customer_code;
                }
            },
            {
                data: 'items_count',
                className: 'text-center',
                render: function(data, type, row) {
                    if (type === 'display') {
                        let retTag = row.has_returns ? `<div class="text-danger" style="font-size:10px;">Ret: Rs ${fmt(row.returned_amount)}</div>` : '';
                        return `<span class="fw-bold text-dark">${data}</span>${retTag}`;
                    }
                    return data;
                }
            },
            {
                data: 'revenue',
                className: 'text-end',
                render: function(data, type) {
                    return type === 'display' ? `<span class="fw-semibold">Rs ${fmt(data)}</span>` : data;
                }
            },
            {
                data: 'cogs',
                className: 'text-end',
                render: function(data, type) {
                    return type === 'display' ? `Rs ${fmt(data)}` : data;
                }
            },
            {
                data: 'profit',
                className: 'text-end',
                render: function(data, type, row) {
                    if (type === 'display') {
                        let isPos = parseFloat(data) >= 0;
                        let color = isPos ? '#059669' : '#dc2626';
                        let bg = isPos ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)';
                        let sign = data < 0 ? '-' : '';
                        return `<span class="badge p-1.5 px-2" style="background:${bg}; color:${color}; font-size:12px; font-weight:700;">${sign}Rs ${fmt(Math.abs(data))}</span>` +
                               `<div class="text-muted" style="font-size:10px;">Margin: ${row.margin}%</div>`;
                    }
                    return data;
                }
            }
        ]
    });

    var myChart = null;

    function escHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, function(m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
        });
    }

    function fmt(n) {
        return Math.round(parseFloat(n)).toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0});
    }

    $('.btnSearchTrigger').click(fetchReport);

    $('.btnResetTrigger').on('click', function() {
        $('.startDateInput').val('{{ date("Y-m-d\T00:00") }}');
        $('.endDateInput').val('{{ date("Y-m-d\T23:59") }}');
        $('.categorySelect, .customerSelect, .productSelect').val('all').trigger('change');
        fetchReport();
    });

    $('.btnPrintReport').on('click', () => window.print());

    function fetchReport() {
        $('#loader').show();

        let startDate = $('.startDateInput').val();
        let endDate   = $('.endDateInput').val();
        let catId     = $('.categorySelect').val();
        let custId    = $('.customerSelect').val();
        let prodId    = $('.productSelect').val();

        $.ajax({
            url: "{{ route('report.profit_loss.fetch') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                start_date: startDate,
                end_date: endDate,
                product_id: prodId,
                category_id: catId,
                customer_id: custId
            },
            success: function(response) {
                $('#loader').hide();
                renderReport(response);
            },
            error: function() {
                $('#loader').hide();
                alert('Could not fetch report data');
            }
        });
    }

    function renderReport(data) {
        // Table
        profitTable.clear();
        let chartLabels = [];
        let chartData = [];
        let mobHtml = '';

        data.products.forEach(function(r, idx) {
            let profitColor = parseFloat(r.profit) >= 0 ? '#059669' : '#dc2626';
            
            let itemNameHtml = `
                <div>
                    <div class="fw-bold text-dark" style="font-size:12.5px;">${r.item_name}</div>
                    ${r.unit_badge ? `<span class="badge bg-light text-secondary border px-1.5 py-0.5 mt-1" style="font-size:10px; font-weight:600;"><i class="fas fa-tag me-1 text-primary"></i>${r.unit_badge}</span>` : ''}
                </div>
            `;

            profitTable.row.add({
                item_code: '<span class="fw-semibold text-primary font-monospace small">' + r.item_code + '</span>',
                item_name: itemNameHtml,
                sold_qty: '<span class="fw-bold text-dark">' + r.sold_qty + '</span>',
                returned_qty: '<span class="text-danger fw-bold">' + (r.returned_qty || 0) + '</span>',
                revenue: 'Rs ' + fmt(r.revenue),
                cogs: 'Rs ' + fmt(r.cogs),
                profit: '<span style="color:' + profitColor + '; font-weight:700;">Rs ' + fmt(r.profit) + '</span>'
            });

            if (chartLabels.length < 5 && parseFloat(r.profit) > 0) {
                chartLabels.push(r.item_name.length > 18 ? r.item_name.substring(0, 18) + '…' : r.item_name);
                chartData.push(r.profit);
            }

            // Mobile Card
            mobHtml += `
                <div class="mob-card p-2.5 p-2 mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-light text-muted border" style="font-size: 10px;">#${idx+1}</span>
                            <span class="badge bg-light text-primary border font-monospace fw-bold" style="font-size: 11px;">${r.item_code}</span>
                            ${r.unit_badge ? `<span class="badge bg-light text-secondary border px-1" style="font-size: 9.5px;">${r.unit_badge}</span>` : ''}
                        </div>
                        <strong style="color:${profitColor}; font-size:12px;">Profit: Rs ${fmt(r.profit)}</strong>
                    </div>
                    <div class="fw-bold text-dark mb-1" style="font-size:12.5px;">${r.item_name}</div>
                    <div class="border-top pt-2 mt-1">
                        <div class="row g-1 text-center" style="font-size: 10.5px;">
                            <div class="col-3 border-end"><span class="text-muted d-block">Qty</span><strong>${r.sold_qty}</strong></div>
                            <div class="col-3 border-end"><span class="text-muted d-block">Ret</span><strong class="text-danger">${r.returned_qty||0}</strong></div>
                            <div class="col-3 border-end"><span class="text-muted d-block">Revenue</span><strong>Rs ${fmt(r.revenue)}</strong></div>
                            <div class="col-3"><span class="text-muted d-block">COGS</span><strong>Rs ${fmt(r.cogs)}</strong></div>
                        </div>
                    </div>
                </div>`;
        });
        profitTable.draw();
        $('#mobProfitContainer').html(mobHtml || '<div class="text-center text-muted py-3 small">No item profitability data</div>');

        // Cards (Desktop & Mobile)
        let formattedGross = 'Rs ' + fmt(data.total_gross_profit);
        let formattedExp   = 'Rs ' + fmt(data.total_expenses);
        let formattedNet   = 'Rs ' + fmt(data.net_profit);

        $('#cardGrossProfit, #mobCardGrossProfit').text(formattedGross);
        $('#cardExpenses, #mobCardExpenses').text(formattedExp);
        $('#cardNetProfit, #mobCardNetProfit').text(formattedNet);

        // Chart
        updateChart(chartLabels, chartData);

        // Top 10 Customers
        renderTopCustomers(data.top_customers || []);

        // Invoice-wise Profit
        renderInvoiceProfits(data.invoice_profits || []);
    }

    function renderInvoiceProfits(invoices) {
        invoiceProfitTable.clear();
        var mobInvHtml = '';
        var totalInvProfit = 0;

        invoices.forEach(function(inv) {
            totalInvProfit += parseFloat(inv.profit || 0);
            invoiceProfitTable.row.add(inv);

            var isPos = parseFloat(inv.profit) >= 0;
            var profitColor = isPos ? '#059669' : '#dc2626';
            var profitBg = isPos ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)';
            var profitSign = inv.profit < 0 ? '-' : '';

            mobInvHtml += `
                <div class="mob-card p-2 mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div>
                            <a href="${inv.invoice_url}" target="_blank" class="badge bg-light text-primary border font-monospace fw-bold text-decoration-none" style="font-size: 11px;">${escHtml(inv.invoice_no)}</a>
                            <span class="text-muted ms-1" style="font-size:10px;">${escHtml(inv.sale_date)}</span>
                        </div>
                        <span class="badge p-1 px-2" style="background:${profitBg}; color:${profitColor}; font-size:11px; font-weight:700;">
                            ${profitSign}Rs ${fmt(Math.abs(inv.profit))}
                        </span>
                    </div>
                    <div class="fw-bold text-dark mb-1" style="font-size:12.5px;">${escHtml(inv.customer_name)} <span class="text-muted fw-normal" style="font-size:10px;">(${escHtml(inv.customer_code)})</span></div>
                    <div class="border-top pt-1 mt-1">
                        <div class="row g-1 text-center" style="font-size: 10.5px;">
                            <div class="col-4 border-end"><span class="text-muted d-block">Revenue</span><strong>Rs ${fmt(inv.revenue)}</strong></div>
                            <div class="col-4 border-end"><span class="text-muted d-block">COGS</span><strong>Rs ${fmt(inv.cogs)}</strong></div>
                            <div class="col-4"><span class="text-muted d-block">Margin</span><strong>${inv.margin}%</strong></div>
                        </div>
                    </div>
                </div>`;
        });

        invoiceProfitTable.draw();
        $('#invoiceCount').text(invoices.length + ' invoices');
        var totalSign = totalInvProfit < 0 ? '-' : '';
        $('#invoiceTotalProfitBadge')
            .text('Profit: ' + totalSign + 'Rs ' + fmt(Math.abs(totalInvProfit)))
            .toggleClass('bg-success', totalInvProfit >= 0)
            .toggleClass('bg-danger', totalInvProfit < 0);

        $('#mobInvoiceProfitContainer').html(mobInvHtml || '<div class="text-center text-muted py-3 small">No invoice data for this period</div>');
    }

    function renderTopCustomers(customers) {
        var html = '';
        var mobCustHtml = '';
        $('#customerCount').text(customers.length + ' customers');

        if (customers.length === 0) {
            html = '<tr><td colspan="5" class="text-center text-muted py-4 small">No customer data for this period</td></tr>';
            $('#topCustomersBody').html(html);
            $('#mobTopCustomersContainer').html('<div class="text-center text-muted py-3 small">No customer data</div>');
            return;
        }

        customers.forEach(function(c, idx) {
            var profitColor = c.profit >= 0 ? '#059669' : '#dc2626';
            var profitSign = c.profit < 0 ? '-' : '';

            html += '<tr>' +
                '<td class="text-center fw-bold text-muted" style="font-size:12px;">' + (idx + 1) + '</td>' +
                '<td>' +
                    '<div class="fw-bold text-dark" style="font-size:13px;">' + c.name + '</div>' +
                    '<div class="text-muted small" style="font-size:10px;">ID: CUST-' + c.id.toString().padStart(4, '0') + '</div>' +
                '</td>' +
                '<td class="text-end fw-semibold" style="font-size:12px;">Rs ' + fmt(c.revenue) + '</td>' +
                '<td class="text-end text-danger fw-semibold" style="font-size:12px;">Rs ' + fmt(c.balance) + '</td>' +
                '<td class="text-end">' +
                    '<span class="badge p-1.5 px-2" style="background: ' + (c.profit >= 0 ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)') + '; color: ' + profitColor + '; font-size:12px; font-weight:700;">' + 
                        profitSign + 'Rs ' + fmt(Math.abs(c.profit)) + 
                    '</span>' +
                '</td>' +
            '</tr>';

            mobCustHtml += `
                <div class="mob-card p-2 mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-light text-muted border" style="font-size:10px;">#${idx+1}</span>
                            <strong class="text-dark" style="font-size:12.5px;">${c.name}</strong>
                        </div>
                        <span class="badge p-1 px-2" style="background:${c.profit >= 0 ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)'}; color:${profitColor}; font-size:11px; font-weight:700;">
                            ${profitSign}Rs ${fmt(Math.abs(c.profit))}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between text-muted" style="font-size:11px;">
                        <span>Revenue: Rs ${fmt(c.revenue)}</span>
                        <span class="text-danger">Balance: Rs ${fmt(c.balance)}</span>
                    </div>
                </div>`;
        });

        $('#topCustomersBody').html(html);
        $('#mobTopCustomersContainer').html(mobCustHtml);
    }

    function updateChart(labels, data) {
        if (myChart) myChart.destroy();

        if (labels.length === 0) {
            $('#profitChart').hide();
            $('#noChartData').show().text('No profit data available');
            return;
        }

        $('#noChartData').hide();
        $('#profitChart').show();

        var ctx = document.getElementById('profitChart');
        myChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#059669', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                cutout: '55%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 10 }, padding: 12 }
                    }
                }
            }
        });
    }

    // Auto load report
    fetchReport();
});
</script>
@endsection
