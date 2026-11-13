@extends('admin_panel.layout.app')

@section('content')
<style>
    /* Compact Sale Report Terminal Styling */
    .sale-report-container {
        padding: 10px 14px;
        background: #f1f5f9;
        min-height: calc(100vh - 75px);
    }

    /* Filter Form Spacing */
    .sale-filter-form {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px !important;
    }
    .sale-filter-group {
        display: flex;
        align-items: center;
    }
    .sale-filter-label {
        margin-right: 6px !important;
        margin-bottom: 0 !important;
        white-space: nowrap;
        font-weight: 700;
        font-size: .78rem;
        color: #475569;
    }
    
    /* Single Horizontal Line Summary Metrics Bar */
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

    /* Scrollable Table Wrapper with Sticky Header */
    .sale-table-wrap {
        height: calc(100vh - 250px);
        max-height: calc(100vh - 250px);
        min-height: 380px;
        overflow-y: auto;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
    }
    
    .sale-table-wrap::-webkit-scrollbar { width: 10px; height: 10px; }
    .sale-table-wrap::-webkit-scrollbar-track { background: #f1f5f9; }
    .sale-table-wrap::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 5px; }
    .sale-table-wrap::-webkit-scrollbar-thumb:hover { background: #64748b; }

    #saleReport {
        font-size: .78rem;
        margin-bottom: 0;
    }

    #saleReport thead th {
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

    #saleReport th:nth-child(11),
    #saleReport td:nth-child(11) {
        max-width: 135px !important;
        width: 135px !important;
        font-size: .72rem;
        word-break: break-word;
        white-space: normal;
    }

    @media print {
        body { background: #ffffff !important; font-size: 11px; }
        .no-print, header, .sidebar, .navbar, footer { display: none !important; }
        .sale-report-container { padding: 0 !important; background: #fff !important; }
        .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; margin-bottom: 10px !important; }
        .sale-table-wrap { height: auto !important; max-height: none !important; overflow: visible !important; border: none !important; }
        #saleReport th, #saleReport td { border: 1px solid #cbd5e1 !important; }
    }
</style>

<div class="sale-report-container">
    
    {{-- DESKTOP FILTER HEADER BAR (d-none d-md-block) --}}
    <div class="card border-0 shadow-sm mb-2 d-none d-md-block no-print" style="border-radius: 10px;">
        <div class="card-body py-2 px-3">
            <form id="SaleFilterFormDesk" class="sale-filter-form" onsubmit="return false;">
                
                {{-- Title Badge --}}
                <div class="sale-filter-group me-1">
                    <span class="fw-bold text-dark fs-6" style="letter-spacing: -0.2px; font-weight:700; white-space:nowrap;">
                        <i class="fas fa-chart-line text-primary me-1"></i>Sale Report
                    </span>
                </div>

                {{-- Start Date --}}
                <div class="sale-filter-group">
                    <label for="start_date_desk" class="sale-filter-label">Start:</label>
                    <input type="date" name="start_date" id="start_date_desk" value="{{ $defaultStartDate ?? date('Y-01-01') }}" class="form-control form-control-sm fw-bold startDateInput" style="height: 34px; width: 135px; font-size: .78rem; border-radius: 6px;">
                </div>

                {{-- End Date --}}
                <div class="sale-filter-group">
                    <label for="end_date_desk" class="sale-filter-label">End:</label>
                    <input type="date" name="end_date" id="end_date_desk" value="{{ date('Y-m-d') }}" class="form-control form-control-sm fw-bold endDateInput" style="height: 34px; width: 135px; font-size: .78rem; border-radius: 6px;">
                </div>

                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-0 fw-semibold btnQuickDateSale" data-range="all" style="font-size: .70rem; height: 34px;" title="All Records">All Time</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-0 fw-semibold btnQuickDateSale" data-range="year" style="font-size: .70rem; height: 34px;" title="This Year">Year</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-0 fw-semibold btnQuickDateSale" data-range="month" style="font-size: .70rem; height: 34px;" title="This Month">Month</button>
                </div>

                                {{-- Province Filter --}}
                <div class="sale-filter-group">
                    <label for="province_desk" class="sale-filter-label">Province:</label>
                    <select name="province" id="province_desk" class="form-select form-select-sm provinceInput" style="height: 34px; width: 110px; font-size: .78rem; border-radius: 6px;">
                        <option value="">All</option>
                        @foreach(app(\App\Services\LocationService::class)->getProvinces() as $p)
                            <option value="{{ $p }}">{{ $p }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- City Filter --}}
                <div class="sale-filter-group">
                    <label for="city_desk" class="sale-filter-label">City:</label>
                    <select name="city" id="city_desk" class="form-select form-select-sm cityInput" style="height: 34px; width: 115px; font-size: .78rem; border-radius: 6px;">
                        <option value="">All Cities</option>
                        @foreach(app(\App\Services\LocationService::class)->getCities() as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Area Filter --}}
                <div class="sale-filter-group">
                    <label for="area_desk" class="sale-filter-label">Area:</label>
                    <select name="area" id="area_desk" class="form-select form-select-sm areaInput" style="height: 34px; width: 115px; font-size: .78rem; border-radius: 6px;">
                        <option value="">All Areas</option>
                        @foreach(app(\App\Services\LocationService::class)->getAreas() as $a)
                            <option value="{{ $a }}">{{ $a }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Customer Filter --}}
                <div class="sale-filter-group">
                    <label for="customer_id_desk" class="sale-filter-label">Customer:</label>
                    <select id="customer_id_desk" class="form-select form-select-sm fw-bold customerInput" style="height: 34px; width: 150px; font-size: .78rem; border-radius: 6px;">
                        <option value="all">All Customers</option>
                        @if(isset($customers) && count($customers) > 0)
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" data-province="{{ $c->province }}" data-city="{{ $c->city }}" data-area="{{ $c->area }}">{{ $c->customer_name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                {{-- Search Input --}}
                <div class="flex-grow-1" style="min-width: 180px;">
                    <div class="position-relative">
                        <i class="fas fa-search position-absolute text-muted" style="left: 10px; top: 50%; transform: translateY(-50%); font-size: 12px; pointer-events: none;"></i>
                        <input type="text" class="form-control form-control-sm searchProductInput" placeholder="Search Product / Invoice / Customer…" style="height: 34px; font-size: .80rem; border-radius: 6px; padding-left: 30px;">
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-primary btn-sm px-3 fw-bold d-inline-flex align-items-center btnSearchTrigger" style="height: 34px; border-radius: 6px; font-size: .80rem;">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm px-3 fw-bold d-inline-flex align-items-center btnExportCsvTrigger" style="height: 34px; border-radius: 6px; font-size: .80rem;">
                        <i class="fas fa-file-csv me-1"></i> CSV
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MOBILE FILTER HEADER BAR (d-md-none) --}}
    <div class="card border-0 shadow-sm mb-3 no-print d-md-none mt-2" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form id="SaleFilterFormMob" onsubmit="return false;">
                <div class="row g-2">
                    <div class="col-12 mb-1">
                        <span class="fw-bold text-dark fs-6">
                            <i class="fas fa-chart-line text-primary me-2"></i>Sale Report
                        </span>
                    </div>
                    <div class="col-6 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Start Date</label>
                        <input type="date" name="start_date" id="start_date_mob" value="{{ $defaultStartDate ?? date('Y-01-01') }}" class="form-control form-control-sm startDateInput" style="font-size: 11px;">
                    </div>
                    <div class="col-6 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">End Date</label>
                        <input type="date" name="end_date" id="end_date_mob" value="{{ date('Y-m-d') }}" class="form-control form-control-sm endDateInput" style="font-size: 11px;">
                    </div>
                                        <div class="col-4 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Province</label>
                        <select name="province" id="province_mob" class="form-select form-select-sm provinceInput" style="font-size: 11px;">
                            <option value="">All</option>
                            @foreach(app(\App\Services\LocationService::class)->getProvinces() as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">City</label>
                        <select name="city" id="city_mob" class="form-select form-select-sm cityInput" style="font-size: 11px;">
                            <option value="">All</option>
                            @foreach(app(\App\Services\LocationService::class)->getCities() as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Area</label>
                        <select name="area" id="area_mob" class="form-select form-select-sm areaInput" style="font-size: 11px;">
                            <option value="">All</option>
                            @foreach(app(\App\Services\LocationService::class)->getAreas() as $a)
                                <option value="{{ $a }}">{{ $a }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Customer</label>
                        <select id="customer_id_mob" class="form-select form-select-sm customerInput" style="font-size: 11px;">
                            <option value="all">All Customers</option>
                            @if(isset($customers) && count($customers) > 0)
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" data-province="{{ $c->province }}" data-city="{{ $c->city }}" data-area="{{ $c->area }}">{{ $c->customer_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-12 mb-2">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Search</label>
                        <input type="text" class="form-control form-control-sm searchProductInput" placeholder="Search Product / Invoice / Customer…" style="font-size: 11px;">
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-primary w-100 py-1.5 fw-bold rounded-2 shadow-sm btnSearchTrigger" style="font-size: 12px;">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-outline-danger w-100 py-1.5 btn-sm fw-bold btnExportCsvTrigger" style="font-size: 12px;">
                            <i class="fas fa-file-csv me-1"></i> CSV
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- DESKTOP SUMMARY PILL METRICS BAR (d-none d-md-block) --}}
    <div class="card border-0 shadow-sm mb-2 d-none d-md-block" style="border-radius: 10px; background: #ffffff;">
        <div class="card-body p-2">
            <div class="summary-pill-bar custom-scroll">
                
                <div class="stat-pill" style="background: #f8fafc; border-color: #cbd5e1;">
                    <div class="stat-label text-muted">Invoices</div>
                    <div class="stat-val text-dark" id="pillTotalInvoices">0</div>
                </div>

                <div class="stat-pill" style="background: #f0f9ff; border-color: #bae6fd;">
                    <div class="stat-label text-info">Total Qty</div>
                    <div class="stat-val text-info" id="pillTotalQty">0 Pcs</div>
                </div>

                <div class="stat-pill" style="background: #f8fafc; border-color: #cbd5e1;">
                    <div class="stat-label text-secondary">Gross Sale</div>
                    <div class="stat-val text-secondary" id="pillGrossSale">Rs 0</div>
                </div>

                <div class="stat-pill" style="background: #fef2f2; border-color: #fca5a5;">
                    <div class="stat-label text-danger">Returns</div>
                    <div class="stat-val text-danger" id="pillTotalReturns">Rs 0</div>
                </div>

                <div class="stat-pill" style="background: #f0fdf4; border-color: #86efac;">
                    <div class="stat-label text-success">Net Sale</div>
                    <div class="stat-val text-success" id="pillNetSale">Rs 0</div>
                </div>

                <div class="stat-pill" style="background: #eff6ff; border-color: #93c5fd;">
                    <div class="stat-label text-primary">Gross Profit</div>
                    <div class="stat-val text-primary" id="pillGrossProfit">Rs 0</div>
                </div>

                <div class="stat-pill" style="background: #fffbeb; border-color: #fde047;">
                    <div class="stat-label" style="color: #b45309;">Expense</div>
                    <div class="stat-val" style="color: #d97706;" id="pillExpenses">Rs 0</div>
                </div>

                <div class="stat-pill" style="background: #ecfdf5; border-color: #34d399;">
                    <div class="stat-label" style="color: #047857;">Current Profit</div>
                    <div class="stat-val" style="color: #059669;" id="pillCurrentProfit">Rs 0</div>
                </div>

            </div>
        </div>
    </div>

    {{-- MOBILE SUMMARY METRIC GRID (2 Columns col-6 d-md-none) --}}
    <div class="row g-2 mb-3 d-md-none no-print px-1">
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-file-invoice text-muted me-1"></i>Invoices</span>
                <div class="mob-metric-val text-dark mt-1" id="mobPillTotalInvoices">0</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-box text-info me-1"></i>Total Qty</span>
                <div class="mob-metric-val text-info mt-1" id="mobPillTotalQty">0 Pcs</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-wallet text-secondary me-1"></i>Gross Sale</span>
                <div class="mob-metric-val text-secondary mt-1" id="mobPillGrossSale">Rs 0</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-undo text-danger me-1"></i>Returns</span>
                <div class="mob-metric-val text-danger mt-1" id="mobPillTotalReturns">Rs 0</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-check-circle text-success me-1"></i>Net Sale</span>
                <div class="mob-metric-val text-success mt-1" id="mobPillNetSale">Rs 0</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-chart-line text-primary me-1"></i>Total Profit</span>
                <div class="mob-metric-val text-primary mt-1" id="mobPillGrossProfit">Rs 0</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-minus-circle text-warning me-1"></i>Expense</span>
                <div class="mob-metric-val text-warning mt-1" id="mobPillExpenses">Rs 0</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-coins text-success me-1"></i>Current Profit</span>
                <div class="mob-metric-val text-success mt-1" id="mobPillCurrentProfit">Rs 0</div>
            </div>
        </div>
    </div>

    {{-- DESKTOP TABLE CONTAINER (d-none d-md-block) --}}
    <div class="card border-0 shadow-sm d-none d-md-block" style="border-radius: 8px;">
        <div class="card-body p-0">
            <div class="loader text-center py-5" style="display:none;">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="small text-muted mt-2 fw-bold">Loading sales data…</div>
            </div>

            <div class="sale-table-wrap">
                <table class="table table-bordered table-hover align-middle" id="saleReport">
                    <thead>
                        <tr>
                            <th style="width:40px;" class="text-center">#</th>
                            <th style="width:130px;">Date &amp; Time</th>
                            <th style="width:110px;">Invoice</th>
                            <th style="width:120px;">Customer</th>
                            <th style="width:90px;">Ref / M.Bill</th>
                            <th>Products</th>
                            <th style="width:75px;" class="text-center">Qty</th>
                            <th style="width:85px;" class="text-end">Price</th>
                            <th style="width:90px;" class="text-end">Total</th>
                            <th style="width:95px;" class="text-end">Net</th>
                            <th style="width:135px;" class="text-end">Returns</th>
                        </tr>
                    </thead>
                    <tbody id="saleBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MOBILE SALE CARDS CONTAINER (d-md-none) --}}
    <div class="d-md-none" id="saleMobileContainer">
        <div class="text-center py-4 text-muted card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body py-4">
                <i class="fas fa-spinner fa-spin fa-2x mb-2 text-secondary"></i>
                <p class="mb-0 small fw-bold">Loading sales data…</p>
            </div>
        </div>
    </div>

</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {

        let currentExpenses = 0;
        let currentCogs = 0;

        // Sync Filter Inputs between Desktop & Mobile
        $('.startDateInput').on('change', function() { $('.startDateInput').val($(this).val()); });
        $('.endDateInput').on('change', function() { $('.endDateInput').val($(this).val()); });
        $('.provinceInput').on('change input', function() { $('.provinceInput').val($(this).val()); });
        $('.cityInput').on('change input', function() { $('.cityInput').val($(this).val()); });
        $('.areaInput').on('change input', function() { $('.areaInput').val($(this).val()); });
        $('.customerInput').on('change', function() { $('.customerInput').val($(this).val()); });
        $('.searchProductInput').on('input', function() { $('.searchProductInput').val($(this).val()); });

        // Core Load Report Function
        function loadSaleReport() {
            let start = $('#start_date_desk').val() || $('#start_date_mob').val();
            let end   = $('#end_date_desk').val() || $('#end_date_mob').val();
            let customer = $('#customer_id_desk').val() || $('#customer_id_mob').val() || 'all';

            $(".loader").show();
            $(".sale-table-wrap").hide();
            $('#saleMobileContainer').html('<div class="text-center py-4 text-muted card border-0 shadow-sm rounded-3 bg-white"><div class="card-body py-4"><i class="fas fa-spinner fa-spin fa-2x mb-2 text-primary"></i><p class="mb-0 small fw-bold">Loading sales data…</p></div></div>');

            $.ajax({
                url: "{{ route('report.sale.fetch') }}",
                type: "GET",
                data: {
                    start_date: start,
                    end_date: end,
                    customer_id: customer,
                    province: $('#province_desk').val() || $('#province_mob').val() || '',
                    city: $('#city_desk').val() || $('#city_mob').val() || '',
                    area: $('#area_desk').val() || $('#area_mob').val() || ''
                },
                success: function(res) {
                    $(".loader").hide();
                    $(".sale-table-wrap").show();

                    let salesData = Array.isArray(res) ? res : (res.sales || []);
                    let summary   = res.summary || {};
                    currentExpenses = parseFloat(summary.expenses) || 0;
                    currentCogs     = parseFloat(summary.cogs) || 0;

                    let html = "";
                    let mobHtml = "";
                    let grandQty = 0,
                        grandTotal = 0,
                        grandNet = 0,
                        grandReturn = 0,
                        grandNetAfterReturn = 0;

                    if (salesData.length === 0) {
                        html = `<tr><td colspan="11" class="text-center text-muted py-5 fw-bold"><i class="fas fa-folder-open fa-2x d-block mb-2 text-secondary"></i>No sales records found for the selected date range.</td></tr>`;
                        mobHtml = `<div class="card border-0 shadow-sm rounded-3 text-center py-4 bg-white"><div class="card-body py-4 text-muted"><i class="fas fa-folder-open fa-2x mb-2 text-secondary"></i><p class="small fw-bold mb-0">No Sales Data Found</p></div></div>`;
                        $('#saleBody').html(html);
                        $('#saleMobileContainer').html(mobHtml);
                        updateSingleLineSummary(0, 0, 0, 0, 0);
                        return;
                    }

                    salesData.forEach((s, i) => {
                        let products = s.product ? s.product.split(',').join('<br>') : '-';
                        let qtyArr = s.qty ? s.qty.split(',') : ['0'];
                        let qtyPiecesArr = s.total_pieces ? s.total_pieces.split(',') : (s.qty_decimal ? s.qty_decimal.split(',') : qtyArr);
                        let price = s.per_price ? s.per_price.split(',').join('<br>') : '-';
                        let total = s.per_total ? s.per_total.split(',').join('<br>') : '-';

                        let rowQty = qtyPiecesArr.reduce((a, b) => parseFloat(a) + parseFloat(b || 0), 0);
                        grandQty += rowQty;

                        let rowTotal = s.per_total ? s.per_total.split(',').reduce((a, b) => parseFloat(a) + parseFloat(b || 0), 0) : 0;
                        grandTotal += parseFloat(rowTotal);
                        
                        let saleNet = parseFloat(s.total_net || 0);
                        grandNet += saleNet;

                        let returnHtml = "";
                        let returnTotal = 0;
                        if (s.returns && s.returns.length > 0) {
                            s.returns.forEach(r => {
                                let rAmt = parseFloat(r.per_total || 0);
                                returnHtml += `<span class="text-danger fw-semibold">${r.product} (${r.qty}) - Rs ${rAmt.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</span><br>`;
                                returnTotal += rAmt;
                            });
                        }
                        if (returnTotal === 0 && s.total_returns) {
                            returnTotal = parseFloat(s.total_returns || 0);
                        }
                        grandReturn += returnTotal;

                        let rowNetAfterReturn = Math.max(0, saleNet - returnTotal);
                        grandNetAfterReturn += rowNetAfterReturn;

                        let invDisplay = s.invoice_no || ('INVSLE-' + s.id);

                        // Net cell formatting (reflects deduction if return exists)
                        let netCellHtml = '';
                        if (returnTotal > 0) {
                            netCellHtml = `<div class="fw-bold text-success">Rs ${rowNetAfterReturn.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</div>
                                           <small class="text-muted text-decoration-line-through d-block" style="font-size:10px;" title="Original Sale Net: Rs ${saleNet.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}">Rs ${saleNet.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</small>`;
                        } else {
                            netCellHtml = `<span class="fw-bold text-dark">Rs ${saleNet.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</span>`;
                        }

                        let returnCellHtml = returnTotal > 0 ? 
                            `<span class="text-danger fw-bold d-block">-Rs ${returnTotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</span><small class="text-danger opacity-75 d-block" style="font-size:10px;">${returnHtml}</small>` : 
                            `<span class="text-muted">-</span>`;

                        // Desktop Row
                        html += `<tr data-qty="${rowQty}" data-total="${rowTotal}" data-net="${rowNetAfterReturn}" data-original-net="${saleNet}" data-return="${returnTotal}">
                            <td class="text-center text-muted fw-bold">${i+1}</td>
                            <td class="small text-nowrap">${s.created_at}</td>
                            <td class="font-monospace fw-bold text-primary">${invDisplay}</td>
                            ${(() => {
    let locBadge = '';
    if (s.customer_city || s.customer_area) {
        let locStr = (s.customer_city || '') + (s.customer_area ? ' (' + s.customer_area + ')' : '');
        locBadge = `<br><span class="badge bg-light text-muted border px-1 py-0" style="font-size: 10px;"><i class="fas fa-map-marker-alt text-primary me-1"></i>${locStr}</span>`;
    }
    return `<td class="fw-semibold text-dark">${s.customer_name ?? '-'}${locBadge}</td>`;
})()}
                            <td class="small text-muted">${s.reference ?? '-'}</td>
                            <td>${products}</td>
                            <td class="fw-semibold text-center">${qtyArr.join('<br>')}</td>
                            <td class="text-end">${price}</td>
                            <td class="text-end">${total}</td>
                            <td class="text-end" data-export-val="${rowNetAfterReturn}">${netCellHtml}</td>
                            <td class="text-end" data-export-val="${returnTotal}">${returnCellHtml}</td>
                        </tr>`;

                        // Mobile Card
                        mobHtml += `
                        <div class="mob-card p-2.5 p-2 mb-2 mob-sale-card" data-search="${(s.product + ' ' + invDisplay + ' ' + (s.customer_name||'') + ' ' + (s.reference||'')).toLowerCase()}">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-light text-muted border" style="font-size: 10px;">#${i+1}</span>
                                    <span class="badge bg-light text-primary border font-monospace fw-bold" style="font-size: 11px;">${invDisplay}</span>
                                </div>
                                <small class="text-muted" style="font-size: 10.5px;">${s.created_at}</small>
                            </div>
                            <div class="mb-1">
                                <strong class="text-dark d-block" style="font-size: 12.5px;">${s.customer_name ?? 'Walking Customer'}</strong>
                                <small class="text-muted" style="font-size: 10.5px;">Ref: ${s.reference ?? '-'}</small>
                            </div>
                            <div class="bg-light rounded p-2 mb-1" style="font-size: 11px;">
                                <span class="fw-semibold text-secondary d-block mb-1">Products:</span>
                                <div>${products}</div>
                            </div>
                            ${returnTotal > 0 ? `
                            <div class="bg-danger bg-opacity-10 rounded p-1.5 px-2 mb-1 border border-danger border-opacity-25" style="font-size: 11px;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-danger"><i class="fas fa-undo me-1"></i>Returned:</span>
                                    <strong class="text-danger">-Rs ${returnTotal.toLocaleString(undefined, {minimumFractionDigits:2})}</strong>
                                </div>
                            </div>` : ''}
                            <div class="border-top pt-2">
                                <div class="row g-1 text-center" style="font-size: 11px;">
                                    <div class="col-4 border-end">
                                        <span class="text-muted d-block" style="font-size: 10px;">Qty</span>
                                        <strong class="text-dark">${rowQty}</strong>
                                    </div>
                                    <div class="col-4 border-end">
                                        <span class="text-muted d-block" style="font-size: 10px;">Gross</span>
                                        <strong class="text-dark">Rs ${parseFloat(rowTotal).toLocaleString(undefined, {minimumFractionDigits:2})}</strong>
                                    </div>
                                    <div class="col-4">
                                        <span class="text-muted d-block" style="font-size: 10px;">Net Sale</span>
                                        <strong class="text-success">Rs ${rowNetAfterReturn.toLocaleString(undefined, {minimumFractionDigits:2})}</strong>
                                        ${returnTotal > 0 ? `<small class="text-muted text-decoration-line-through d-block" style="font-size:9px;">Rs ${saleNet.toLocaleString(undefined, {minimumFractionDigits:2})}</small>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    });

                    // Grand total row inside desktop table
                    html += `<tr class="fw-bold bg-light" id="grandTotalRow" style="border-top: 2px solid #cbd5e1;">
                        <td colspan="6" class="text-end">Grand Total:</td>
                        <td id="grandQty" class="text-center">${grandQty.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                        <td>-</td>
                        <td id="grandTotal" class="text-end">Rs ${grandTotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                        <td id="grandNet" class="text-end text-success fs-6">Rs ${grandNetAfterReturn.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                        <td id="grandReturn" class="text-end text-danger">Rs ${grandReturn.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                    </tr>`;

                    $('#saleBody').html(html);
                    $('#saleMobileContainer').html(mobHtml);

                    updateSingleLineSummary(salesData.length, grandQty, grandTotal, grandReturn, grandNetAfterReturn);
                },
                error: function(xhr) {
                    $(".loader").hide();
                    $(".sale-table-wrap").show();
                    $('#saleBody').html(`<tr><td colspan="11" class="text-center text-danger py-4 fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> Failed to load sales data. Please check connection and try again.</td></tr>`);
                }
            });
        }

        $(document).on('click', '.btnQuickDateSale', function() {
            let range = $(this).data('range');
            let today = '{{ date("Y-m-d") }}';
            let startOfYear = '{{ $defaultStartDate ?? date("Y-01-01") }}';
            let startOfMonth = '{{ date("Y-m-01") }}';
            
            if (range === 'all') {
                $('.startDateInput').val('');
                $('.endDateInput').val('');
            } else if (range === 'year') {
                $('.startDateInput').val(startOfYear);
                $('.endDateInput').val(today);
            } else if (range === 'month') {
                $('.startDateInput').val(startOfMonth);
                $('.endDateInput').val(today);
            }
            $('.btnQuickDateSale').removeClass('active btn-secondary text-white').addClass('btn-outline-secondary');
            $(this).addClass('active btn-secondary text-white').removeClass('btn-outline-secondary');
            loadSaleReport();
        });

        // Trigger on Filter Click
        $(document).on('click', '.btnSearchTrigger', function() {
            $('.searchProductInput').val('');
            loadSaleReport();
        });

        // Trigger on Date or Customer Change
        $('.startDateInput, .endDateInput, .customerInput').on('change', function() {
            loadSaleReport();
        });

        // INITIAL LOAD ON PAGE READY
        loadSaleReport();

        // Function to update Summary Metrics (Desktop & Mobile)
        function updateSingleLineSummary(count, qty, gross, returns, net) {
            let grossProfit   = net - currentCogs;
            let currentProfit = grossProfit - currentExpenses;

            let formattedCount = count.toLocaleString();
            let formattedQty   = qty.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + ' Pcs';
            let formattedGross = 'Rs ' + gross.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
            let formattedRet   = 'Rs ' + returns.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
            let formattedNet   = 'Rs ' + net.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
            let formattedGP    = 'Rs ' + grossProfit.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
            let formattedExp   = 'Rs ' + currentExpenses.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
            let formattedNP    = 'Rs ' + currentProfit.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});

            // Desktop Pills
            $('#pillTotalInvoices').text(formattedCount);
            $('#pillTotalQty').text(formattedQty);
            $('#pillGrossSale').text(formattedGross);
            $('#pillTotalReturns').text(formattedRet);
            $('#pillNetSale').text(formattedNet);
            $('#pillGrossProfit').text(formattedGP);
            $('#pillExpenses').text(formattedExp);
            $('#pillCurrentProfit').text(formattedNP);

            // Mobile Cards
            $('#mobPillTotalInvoices').text(formattedCount);
            $('#mobPillTotalQty').text(formattedQty);
            $('#mobPillGrossSale').text(formattedGross);
            $('#mobPillTotalReturns').text(formattedRet);
            $('#mobPillNetSale').text(formattedNet);
            $('#mobPillGrossProfit').text(formattedGP);
            $('#mobPillExpenses').text(formattedExp);
            $('#mobPillCurrentProfit').text(formattedNP);
        }

        // Real-time Search Filter Handler
        $(document).on('input', '.searchProductInput', function() {
            let val = $(this).val().toLowerCase();

            // Desktop Table Filter
            $('#saleBody tr').each(function() {
                if ($(this).attr('id') === 'grandTotalRow') return;
                let text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(val) > -1);
            });

            // Mobile Card Filter
            $('.mob-sale-card').each(function() {
                let searchStr = $(this).attr('data-search') || $(this).text().toLowerCase();
                $(this).toggle(searchStr.indexOf(val) > -1);
            });
        });

        // Export CSV Handler
        $(document).on('click', '.btnExportCsvTrigger', function() {
            let csv = [];
            csv.push(['#', 'Date & Time', 'Invoice', 'Customer', 'Reference', 'Products', 'Qty', 'Price', 'Total', 'Net Amount', 'Returns'].join(','));
            $('#saleBody tr').each(function() {
                if ($(this).attr('id') === 'grandTotalRow') return;
                let row = [];
                $(this).find('td').each(function() {
                    let exportVal = $(this).attr('data-export-val');
                    let text = exportVal !== undefined ? exportVal : $(this).text().trim().replace(/,/g, '').replace(/\s+/g, ' ');
                    row.push('"' + text + '"');
                });
                if (row.length) csv.push(row.join(','));
            });
            let blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
            let link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'Sale_Report.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

    });
</script>
@include('admin_panel.partials.location_selector_script')
<script>
    $(document).ready(function() {
        setupLocationCascade({
            province: '.provinceInput',
            city: '.cityInput',
            area: '.areaInput',
            customer: '.customerInput'
        });
    });
</script>
@endsection