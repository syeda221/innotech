@extends('admin_panel.layout.app')

@section('content')
<style>
    /* ── Sale Return Report Styling ── */
    .sale-return-container {
        padding: 10px 14px;
        background: #f1f5f9;
        min-height: calc(100vh - 75px);
    }
    .sr-filter-label {
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
    .report-table-wrap {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        overflow-x: auto;
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
    .report-table tbody td {
        padding: 8px 10px;
        vertical-align: middle;
    }
    .select2-container--default .select2-selection--multiple {
        border-color: #cbd5e1;
        min-height: 32px;
        font-size: .78rem;
        border-radius: 6px;
        padding: 1px 4px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-size: 11px;
        padding: 0 5px;
        margin-top: 2px;
    }
    
    /* View Navigation Tabs */
    .mode-pills {
        display: flex;
        gap: 6px;
        margin-bottom: 10px;
    }
    .mode-pill {
        padding: 6px 16px;
        border-radius: 20px;
        border: 1px solid #cbd5e1;
        background: #fff;
        font-size: .80rem;
        font-weight: 700;
        color: #64748b;
        cursor: pointer;
        transition: all .15s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }
    .mode-pill:hover {
        background: #f8fafc;
        color: #1e293b;
    }
    .mode-pill.active {
        background: #dc2626;
        color: #fff;
        border-color: #dc2626;
        box-shadow: 0 4px 10px rgba(220, 38, 38, 0.25);
    }

    /* Accordion Drill-down for Customer Summary */
    .customer-header-row {
        cursor: pointer;
        background-color: #f8fafc;
        transition: background-color .15s;
    }
    .customer-header-row:hover {
        background-color: #f1f5f9;
    }
    .customer-detail-subtable {
        background-color: #ffffff;
        font-size: .75rem;
    }
    .customer-detail-subtable thead th {
        background-color: #334155 !important;
        color: #fff !important;
        padding: 6px 8px;
        font-size: 11px;
    }

    /* Mobile Cards */
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
        font-size: 13px;
        font-weight: 800;
    }

    @media print {
        body { background: #ffffff !important; font-size: 11px; }
        .no-print, header, .sidebar, .navbar, footer, .mode-pills { display: none !important; }
        .sale-return-container { padding: 0 !important; background: #fff !important; }
        .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; margin-bottom: 10px !important; }
        .report-table thead th { background-color: #000 !important; color: #fff !important; -webkit-print-color-adjust: exact; }
    }
</style>

<div class="sale-return-container">

    {{-- DESKTOP WEB VIEW FILTER CARD (d-none d-md-block) --}}
    <div class="card border-0 shadow-sm mb-2 no-print d-none d-md-block" style="border-radius: 10px;">
        <div class="card-body py-2 px-3">
            <form id="srFilterFormDesktop">
                
                {{-- Top Section: Title, Dates, Action Buttons --}}
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2 border-bottom">
                    
                    {{-- 1. LEFT: Title Badge --}}
                    <div class="d-flex align-items-center me-3">
                        <span class="fw-bold text-dark fs-6 text-nowrap" style="letter-spacing: -0.2px;">
                            <i class="fas fa-undo-alt text-danger me-2"></i>Sale Return Detailed Report
                        </span>
                    </div>

                    {{-- 2. MID: Date Range Inputs --}}
                    <div class="d-flex align-items-center gap-2 me-auto flex-wrap">
                        <label for="filterFromDateDesk" class="sr-filter-label mb-0 ms-1 me-1">From:</label>
                        <input type="date" name="from_date" id="filterFromDateDesk" class="form-control form-control-sm fw-bold" value="{{ date('Y-m-01') }}" style="height: 32px; width: 135px; font-size: .78rem; border-radius: 6px;">
                        
                        <label for="filterToDateDesk" class="sr-filter-label mb-0 ms-3 me-1">To:</label>
                        <input type="date" name="to_date" id="filterToDateDesk" class="form-control form-control-sm fw-bold" value="{{ date('Y-m-d') }}" style="height: 32px; width: 135px; font-size: .78rem; border-radius: 6px;">
                    </div>

                    {{-- 3. LAST: Action Buttons --}}
                    <div class="d-flex align-items-center ms-auto" style="gap: 8px !important;">
                        <button type="submit" class="btn btn-danger btn-sm px-3 fw-bold d-inline-flex align-items-center btnFilterTrigger" style="height: 32px; border-radius: 6px; font-size: .78rem;">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <button type="button" class="btn btn-light border btn-sm px-3 fw-bold text-secondary d-inline-flex align-items-center btnResetTrigger" style="height: 32px; border-radius: 6px; font-size: .78rem;">
                            <i class="fas fa-undo me-1"></i> Reset
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm px-3 fw-bold d-inline-flex align-items-center btnExportExcel" style="height: 32px; border-radius: 6px; font-size: .78rem;">
                            <i class="fas fa-file-excel me-1"></i> Excel
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-bold d-inline-flex align-items-center btnPrintReport" style="height: 32px; border-radius: 6px; font-size: .78rem;">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>
                </div>

                {{-- Location Hierarchy Row --}}
                <div class="row g-2 mb-2 pb-2 border-bottom">
                    <div class="col-md-3">
                        <label class="sr-filter-label mb-1">Province:</label>
                        <select name="province" id="filterProvinceDesk" class="form-select form-select-sm provinceLocInput" style="height: 32px; font-size: .78rem; border-radius: 6px;">
                            <option value="">All Provinces</option>
                            @if(class_exists('\App\Services\LocationService'))
                                @foreach(app(\App\Services\LocationService::class)->getProvinces() as $p)
                                    <option value="{{ $p }}">{{ $p }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="sr-filter-label mb-1">City:</label>
                        <select name="city" id="filterCityDesk" class="form-select form-select-sm cityLocInput" style="height: 32px; font-size: .78rem; border-radius: 6px;">
                            <option value="">All Cities</option>
                            @if(class_exists('\App\Services\LocationService'))
                                @foreach(app(\App\Services\LocationService::class)->getCities() as $c)
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="sr-filter-label mb-1">Area / Sector:</label>
                        <select name="area" id="filterAreaDesk" class="form-select form-select-sm areaLocInput" style="height: 32px; font-size: .78rem; border-radius: 6px;">
                            <option value="">All Areas</option>
                            @if(class_exists('\App\Services\LocationService'))
                                @foreach(app(\App\Services\LocationService::class)->getAreas() as $a)
                                    <option value="{{ $a }}">{{ $a }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="sr-filter-label mb-1">Warehouse:</label>
                        <select name="warehouse_id" id="filterWarehouseDesk" class="form-select form-select-sm" style="height: 32px; font-size: .78rem; border-radius: 6px;">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->warehouse_name ?? $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Multi-Select Dropdowns: Customer, Product, Category, Brand --}}
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="sr-filter-label mb-1">Customer (Multi):</label>
                        <select name="customer_id[]" id="filterCustomerDesk" class="form-select form-select-sm select2-multi filterCustomerSelect" multiple data-placeholder="All Customers">
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" data-province="{{ $c->province }}" data-city="{{ $c->city }}" data-area="{{ $c->area }}">{{ $c->customer_id ?? 'CUST' }} - {{ $c->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="sr-filter-label mb-1">Product (Multi):</label>
                        <select name="product_id[]" id="filterProductDesk" class="form-select form-select-sm select2-multi filterProductSelect" multiple data-placeholder="All Products">
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->item_code }} - {{ $p->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="sr-filter-label mb-1">Category (Multi):</label>
                        <select name="category_id[]" id="filterCategoryDesk" class="form-select form-select-sm select2-multi filterCategorySelect" multiple data-placeholder="All Categories">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="sr-filter-label mb-1">Brand (Multi):</label>
                        <select name="brand_id[]" id="filterBrandDesk" class="form-select form-select-sm select2-multi filterBrandSelect" multiple data-placeholder="All Brands">
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- MOBILE VIEW FILTER CARD (d-md-none) --}}
    <div class="card border-0 shadow-sm mb-3 no-print d-md-none mt-2" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form id="srFilterFormMobile">
                <div class="row g-2">
                    <div class="col-4 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Province</label>
                        <select name="province" id="filterProvinceMob" class="form-select form-select-sm provinceLocInput" style="font-size: 11px;">
                            <option value="">All</option>
                            @if(class_exists('\App\Services\LocationService'))
                                @foreach(app(\App\Services\LocationService::class)->getProvinces() as $p)
                                    <option value="{{ $p }}">{{ $p }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-4 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">City</label>
                        <select name="city" id="filterCityMob" class="form-select form-select-sm cityLocInput" style="font-size: 11px;">
                            <option value="">All</option>
                            @if(class_exists('\App\Services\LocationService'))
                                @foreach(app(\App\Services\LocationService::class)->getCities() as $c)
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-4 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Area</label>
                        <select name="area" id="filterAreaMob" class="form-select form-select-sm areaLocInput" style="font-size: 11px;">
                            <option value="">All</option>
                            @if(class_exists('\App\Services\LocationService'))
                                @foreach(app(\App\Services\LocationService::class)->getAreas() as $a)
                                    <option value="{{ $a }}">{{ $a }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-12 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Customer</label>
                        <select name="customer_id[]" class="form-select form-select-sm select2-multi filterCustomerSelect" multiple data-placeholder="-- All Customers --">
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->customer_id ?? 'CUST' }} - {{ $c->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Product</label>
                        <select name="product_id[]" class="form-select form-select-sm select2-multi filterProductSelect" multiple data-placeholder="-- All Products --">
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->item_code }} - {{ $p->item_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">From Date</label>
                        <input type="date" name="from_date" id="filterFromDateMob" class="form-control form-control-sm" value="{{ date('Y-m-01') }}" style="font-size: 11px;">
                    </div>
                    <div class="col-6 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">To Date</label>
                        <input type="date" name="to_date" id="filterToDateMob" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" style="font-size: 11px;">
                    </div>

                    <div class="col-12 my-2">
                        <button type="submit" class="btn btn-danger w-100 py-2 fw-bold rounded-3 shadow-sm btnFilterTrigger" style="font-size: 13px;">
                            <i class="fas fa-search me-1"></i> Filter
                        </button>
                    </div>

                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-center gap-2 pt-1">
                            <button type="button" class="btn btn-light border btn-sm flex-fill fw-bold text-secondary btnResetTrigger" style="font-size: 11px; margin-right: 4px;">
                                <i class="fas fa-undo me-1"></i> Reset
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm flex-fill fw-bold btnExportExcel" style="font-size: 11px; margin-right: 4px;">
                                <i class="fas fa-file-excel me-1"></i> Excel
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

    {{-- DESKTOP SUMMARY METRIC BAR (d-none d-md-block) --}}
    <div class="card border-0 shadow-sm mb-2 d-none d-md-block" style="border-radius: 10px; background: #ffffff;">
        <div class="card-body p-2">
            <div class="summary-pill-bar">
                
                <div class="stat-pill" style="background: #f8fafc; border-color: #cbd5e1;">
                    <div class="stat-label text-muted">Returns</div>
                    <div class="stat-val text-dark" id="pillTotalReturns">0</div>
                </div>

                <div class="stat-pill" style="background: #f0f9ff; border-color: #bae6fd;">
                    <div class="stat-label text-primary">Customers</div>
                    <div class="stat-val text-primary" id="pillTotalCustomers">0</div>
                </div>

                <div class="stat-pill" style="background: #f8fafc; border-color: #cbd5e1;">
                    <div class="stat-label text-secondary">Products</div>
                    <div class="stat-val text-secondary" id="pillTotalProducts">0</div>
                </div>

                <div class="stat-pill" style="background: #fffbeb; border-color: #fde047;">
                    <div class="stat-label" style="color: #b45309;">Ret Pieces</div>
                    <div class="stat-val text-warning" id="pillReturnedPieces">0</div>
                </div>

                <div class="stat-pill" style="background: #f0fdf4; border-color: #86efac;">
                    <div class="stat-label text-success">Ret Cartons</div>
                    <div class="stat-val text-success" id="pillReturnedCartons">0</div>
                </div>

                <div class="stat-pill" style="background: #fef2f2; border-color: #fca5a5;">
                    <div class="stat-label text-danger">Total Return Amount</div>
                    <div class="stat-val text-danger" id="pillTotalAmount">Rs 0.00</div>
                </div>

            </div>
        </div>
    </div>

    {{-- MOBILE SUMMARY METRIC GRID (2 Columns d-md-none) --}}
    <div class="row g-2 mb-3 d-md-none no-print px-1">
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-undo text-danger me-1"></i>Returns</span>
                <div class="mob-metric-val text-dark mt-1" id="mobMetricReturns">0</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-users text-primary me-1"></i>Customers</span>
                <div class="mob-metric-val text-primary mt-1" id="mobMetricCustomers">0</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-cubes text-warning me-1"></i>Ret Pieces</span>
                <div class="mob-metric-val text-warning mt-1" id="mobMetricPieces">0</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-money-bill-wave text-danger me-1"></i>Return Amount</span>
                <div class="mob-metric-val text-danger mt-1" id="mobMetricAmount">Rs 0.00</div>
            </div>
        </div>
    </div>

    {{-- 3 ANALYTICAL TAB MODE BUTTONS --}}
    <div class="d-flex align-items-center justify-content-between mb-2 no-print flex-wrap gap-2">
        <div class="mode-pills mb-0">
            <button type="button" class="mode-pill active" data-tab="detailed">
                <i class="fas fa-list-ul"></i> Detailed Returns (Line-Item)
            </button>
            <button type="button" class="mode-pill" data-tab="customer">
                <i class="fas fa-users"></i> Customer-Wise Summary
            </button>
            <button type="button" class="mode-pill" data-tab="product">
                <i class="fas fa-box-open"></i> Product-Wise Summary
            </button>
        </div>
        <div class="text-muted small fw-semibold" id="reportResultCountBadge">
            Showing records
        </div>
    </div>

    {{-- TAB 1: DETAILED VIEW (Line Items Table) --}}
    <div id="tabContentDetailed" class="tab-content-pane">
        <div class="report-table-wrap shadow-sm">
            <table class="table report-table table-hover table-striped align-middle" id="tableDetailed">
                <thead>
                    <tr>
                        <th style="width: 45px;" class="text-center">#</th>
                        <th>Return Date</th>
                        <th>Return Voucher</th>
                        <th>Orig. Invoice</th>
                        <th>Customer</th>
                        <th>Location</th>
                        <th>Product & Variant</th>
                        <th>Warehouse</th>
                        <th class="text-center">Cartons</th>
                        <th class="text-center">Total Pieces</th>
                        <th class="text-end">Return Price</th>
                        <th class="text-end">Line Total (Rs)</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody id="tbodyDetailed">
                    <tr>
                        <td colspan="13" class="text-center py-4 text-muted">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2 text-secondary"></i>
                            <p class="mb-0 small fw-bold">Loading Sale Return Data…</p>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="bg-light fw-bold" id="tfootDetailed" style="display: none;">
                    <tr>
                        <td colspan="8" class="text-end">Total:</td>
                        <td class="text-center text-success" id="footDetCartons">0.00</td>
                        <td class="text-center text-warning" id="footDetPieces">0.00</td>
                        <td></td>
                        <td class="text-end text-danger" id="footDetAmount">Rs 0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- TAB 2: CUSTOMER-WISE SUMMARY --}}
    <div id="tabContentCustomer" class="tab-content-pane" style="display: none;">
        <div class="report-table-wrap shadow-sm">
            <table class="table report-table table-hover align-middle" id="tableCustomer">
                <thead>
                    <tr>
                        <th style="width: 35px;" class="text-center"></th>
                        <th style="width: 45px;" class="text-center">#</th>
                        <th>Customer Code</th>
                        <th>Customer Name</th>
                        <th>Mobile</th>
                        <th>Location (Area, City, Province)</th>
                        <th class="text-center">Return Invoices</th>
                        <th class="text-center">Total Pieces</th>
                        <th class="text-center">Total Cartons</th>
                        <th class="text-end">Total Return Amount (Rs)</th>
                    </tr>
                </thead>
                <tbody id="tbodyCustomer">
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">No data available.</td>
                    </tr>
                </tbody>
                <tfoot class="bg-light fw-bold" id="tfootCustomer" style="display: none;">
                    <tr>
                        <td colspan="6" class="text-end">Total:</td>
                        <td class="text-center text-primary" id="footCustInvoices">0</td>
                        <td class="text-center text-warning" id="footCustPieces">0.00</td>
                        <td class="text-center text-success" id="footCustCartons">0.00</td>
                        <td class="text-end text-danger" id="footCustAmount">Rs 0.00</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- TAB 3: PRODUCT-WISE SUMMARY --}}
    <div id="tabContentProduct" class="tab-content-pane" style="display: none;">
        <div class="report-table-wrap shadow-sm">
            <table class="table report-table table-hover table-striped align-middle" id="tableProduct">
                <thead>
                    <tr>
                        <th style="width: 45px;" class="text-center">#</th>
                        <th>SKU</th>
                        <th>Product & Variant</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th class="text-center">Return Invoices</th>
                        <th class="text-center">Total Pieces</th>
                        <th class="text-center">Total Cartons</th>
                        <th class="text-end">Avg Return Price</th>
                        <th class="text-end">Total Return Value (Rs)</th>
                        <th>Returning Customers (Qty)</th>
                    </tr>
                </thead>
                <tbody id="tbodyProduct">
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">No data available.</td>
                    </tr>
                </tbody>
                <tfoot class="bg-light fw-bold" id="tfootProduct" style="display: none;">
                    <tr>
                        <td colspan="5" class="text-end">Total:</td>
                        <td class="text-center text-primary" id="footProdInvoices">0</td>
                        <td class="text-center text-warning" id="footProdPieces">0.00</td>
                        <td class="text-center text-success" id="footProdCartons">0.00</td>
                        <td></td>
                        <td class="text-end text-danger" id="footProdAmount">Rs 0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    let reportData = null;
    let activeTab = 'detailed';

    // Initialize Select2 Multi-Select
    if ($.fn.select2) {
        $('.select2-multi').select2({
            placeholder: "Select (All)",
            allowClear: true,
            width: '100%'
        });
    }

    // Sync Desktop & Mobile Dates & Locations
    $('#filterFromDateDesk').on('change', function() { $('#filterFromDateMob').val($(this).val()); });
    $('#filterFromDateMob').on('change', function() { $('#filterFromDateDesk').val($(this).val()); });
    $('#filterToDateDesk').on('change', function() { $('#filterToDateMob').val($(this).val()); });
    $('#filterToDateMob').on('change', function() { $('#filterToDateDesk').val($(this).val()); });
    $('.provinceLocInput').on('change input', function() { $('.provinceLocInput').val($(this).val()); });
    $('.cityLocInput').on('change input', function() { $('.cityLocInput').val($(this).val()); });
    $('.areaLocInput').on('change input', function() { $('.areaLocInput').val($(this).val()); });

    // Tab Switching
    $('.mode-pill').on('click', function(e) {
        e.preventDefault();
        $('.mode-pill').removeClass('active');
        $(this).addClass('active');
        activeTab = $(this).data('tab');

        $('.tab-content-pane').hide();
        if (activeTab === 'detailed') {
            $('#tabContentDetailed').show();
        } else if (activeTab === 'customer') {
            $('#tabContentCustomer').show();
        } else if (activeTab === 'product') {
            $('#tabContentProduct').show();
        }
        updateBadgeCount();
    });

    function updateBadgeCount() {
        if (!reportData) return;
        let count = 0;
        if (activeTab === 'detailed') count = (reportData.detailed_items || []).length;
        else if (activeTab === 'customer') count = (reportData.customer_summary || []).length;
        else if (activeTab === 'product') count = (reportData.product_summary || []).length;
        $('#reportResultCountBadge').text(`Showing ${count} records (${activeTab})`);
    }

    // Load Data
    function loadReportData() {
        $('#tbodyDetailed').html(`
            <tr>
                <td colspan="13" class="text-center py-4 text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2 text-danger"></i>
                    <p class="mb-0 small fw-bold">Fetching Sale Return Data…</p>
                </td>
            </tr>
        `);
        $('#tbodyCustomer').html(`<tr><td colspan="10" class="text-center py-3 text-muted">Loading…</td></tr>`);
        $('#tbodyProduct').html(`<tr><td colspan="11" class="text-center py-3 text-muted">Loading…</td></tr>`);

        let formData = window.innerWidth < 768 ? $('#srFilterFormMobile').serialize() : $('#srFilterFormDesktop').serialize();

        $.ajax({
            url: "{{ route('report.sale_return.fetch') }}",
            type: "GET",
            data: formData,
            success: function(res) {
                reportData = res;
                if (!res.success) {
                    $('#tbodyDetailed').html('<tr><td colspan="13" class="text-center py-3 text-danger">Failed to load data.</td></tr>');
                    return;
                }

                // 1. Update Metrics
                let s = res.summary || {};
                $('#pillTotalReturns, #mobMetricReturns').text(s.total_returns || 0);
                $('#pillTotalCustomers, #mobMetricCustomers').text(s.total_customers || 0);
                $('#pillTotalProducts').text(s.total_products || 0);
                $('#pillReturnedPieces, #mobMetricPieces').text(s.total_pieces || '0.00');
                $('#pillReturnedCartons').text(s.total_boxes || '0.00');
                $('#pillTotalAmount, #mobMetricAmount').text('Rs ' + (s.total_amount || '0.00'));

                // 2. Render Tab 1: Detailed Items
                renderDetailedTable(res.detailed_items || []);

                // 3. Render Tab 2: Customer Summary
                renderCustomerTable(res.customer_summary || []);

                // 4. Render Tab 3: Product Summary
                renderProductTable(res.product_summary || []);

                updateBadgeCount();
            },
            error: function(err) {
                $('#tbodyDetailed').html('<tr><td colspan="13" class="text-center py-3 text-danger">Error fetching return records. Please try again.</td></tr>');
            }
        });
    }

    function renderDetailedTable(items) {
        let $tbody = $('#tbodyDetailed');
        $tbody.empty();
        if (items.length === 0) {
            $tbody.html('<tr><td colspan="13" class="text-center py-4 text-muted fw-bold">No sale return records found for this criteria.</td></tr>');
            $('#tfootDetailed').hide();
            return;
        }

        let totalCartons = 0;
        let totalPieces = 0;
        let totalAmt = 0;

        items.forEach((r, idx) => {
            totalCartons += parseFloat(r.boxes || 0);
            totalPieces  += parseFloat(r.qty_pcs || 0);
            totalAmt     += parseFloat(r.line_total || 0);

            let locStr = r.location ? `<span class="badge bg-light text-secondary border" style="font-size:10px;"><i class="fas fa-map-marker-alt me-1 text-danger"></i>${r.location}</span>` : '<span class="text-muted">—</span>';

            $tbody.append(`
                <tr>
                    <td class="text-center text-muted fw-bold">${idx + 1}</td>
                    <td class="fw-semibold text-nowrap">${r.return_date}</td>
                    <td>
                        <span class="badge bg-danger-subtle text-danger border border-danger fw-bold" style="font-size: 11px;">
                            ${r.return_invoice}
                        </span>
                    </td>
                    <td>
                        ${r.original_invoice !== '-' ? '<span class="badge bg-light text-dark border">' + r.original_invoice + '</span>' : '<span class="text-muted">—</span>'}
                    </td>
                    <td>
                        <div class="fw-bold text-dark">${r.customer_name}</div>
                        <small class="text-muted"><i class="fas fa-phone-alt me-1"></i>${r.customer_mobile}</small>
                    </td>
                    <td>${locStr}</td>
                    <td>
                        <div class="fw-bold text-dark">${r.product_name}</div>
                        <small class="text-muted">SKU: ${r.product_code || '—'} | ${r.brand_name}</small>
                    </td>
                    <td><span class="badge bg-light text-dark border">${r.warehouse_name}</span></td>
                    <td class="text-center fw-bold text-success">${Number(r.boxes || 0).toFixed(2)}</td>
                    <td class="text-center fw-bold text-warning">${Number(r.qty_pcs || 0).toFixed(2)}</td>
                    <td class="text-end font-monospace">Rs ${Number(r.unit_price || 0).toFixed(2)}</td>
                    <td class="text-end fw-bold text-danger font-monospace">Rs ${Number(r.line_total || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td><small class="text-muted">${r.remarks || '-'}</small></td>
                </tr>
            `);
        });

        $('#footDetCartons').text(totalCartons.toFixed(2));
        $('#footDetPieces').text(totalPieces.toFixed(2));
        $('#footDetAmount').text('Rs ' + totalAmt.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#tfootDetailed').show();
    }

    function renderCustomerTable(customers) {
        let $tbody = $('#tbodyCustomer');
        $tbody.empty();
        if (customers.length === 0) {
            $tbody.html('<tr><td colspan="10" class="text-center py-4 text-muted fw-bold">No customer return records found.</td></tr>');
            $('#tfootCustomer').hide();
            return;
        }

        let totInvoices = 0;
        let totPieces = 0;
        let totCartons = 0;
        let totAmt = 0;

        customers.forEach((c, idx) => {
            totInvoices += c.total_invoices_count || 0;
            totPieces   += parseFloat(c.total_pieces || 0);
            totCartons  += parseFloat(c.total_boxes || 0);
            totAmt      += parseFloat(c.total_amount || 0);

            let locStr = c.location ? `<i class="fas fa-map-marker-alt me-1 text-danger"></i>${c.location}` : '—';
            let subRows = (c.items || []).map((it, sIdx) => `
                <tr>
                    <td class="text-center text-muted">${sIdx + 1}</td>
                    <td class="fw-semibold text-danger">${it.return_invoice}</td>
                    <td>${it.return_date}</td>
                    <td class="fw-bold">${it.product_name}</td>
                    <td><span class="badge bg-light text-dark border">${it.warehouse}</span></td>
                    <td class="text-center fw-bold text-warning">${Number(it.qty_pcs || 0).toFixed(2)}</td>
                    <td class="text-end font-monospace">Rs ${Number(it.unit_price || 0).toFixed(2)}</td>
                    <td class="text-end font-monospace fw-bold text-danger">Rs ${Number(it.line_total || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td><small class="text-muted">${it.remarks}</small></td>
                </tr>
            `).join('');

            $tbody.append(`
                <tr class="customer-header-row" data-bs-toggle="collapse" data-bs-target="#custCollapse_${idx}" aria-expanded="false">
                    <td class="text-center"><i class="fas fa-chevron-right text-secondary transition-icon"></i></td>
                    <td class="text-center fw-bold text-muted">${idx + 1}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${c.customer_code}</span></td>
                    <td class="fw-bold text-dark fs-6">${c.customer_name}</td>
                    <td>${c.customer_mobile}</td>
                    <td class="small text-secondary">${locStr}</td>
                    <td class="text-center fw-bold text-primary">${c.total_invoices_count}</td>
                    <td class="text-center fw-bold text-warning">${Number(c.total_pieces || 0).toFixed(2)}</td>
                    <td class="text-center fw-bold text-success">${Number(c.total_boxes || 0).toFixed(2)}</td>
                    <td class="text-end fw-bold text-danger font-monospace">Rs ${Number(c.total_amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                </tr>
                <tr class="collapse" id="custCollapse_${idx}">
                    <td colspan="10" class="p-0 border-0">
                        <div class="p-3 bg-light border-bottom">
                            <h6 class="fw-bold text-secondary mb-2" style="font-size: 12px;">
                                <i class="fas fa-boxes me-1 text-danger"></i> Return Items for ${c.customer_name}:
                            </h6>
                            <table class="table table-sm customer-detail-subtable table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 35px;">#</th>
                                        <th>Return #</th>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>Warehouse</th>
                                        <th class="text-center">Returned Qty</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Total Amount</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>${subRows}</tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            `);
        });

        $('#footCustInvoices').text(totInvoices);
        $('#footCustPieces').text(totPieces.toFixed(2));
        $('#footCustCartons').text(totCartons.toFixed(2));
        $('#footCustAmount').text('Rs ' + totAmt.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#tfootCustomer').show();
    }

    function renderProductTable(products) {
        let $tbody = $('#tbodyProduct');
        $tbody.empty();
        if (products.length === 0) {
            $tbody.html('<tr><td colspan="11" class="text-center py-4 text-muted fw-bold">No product return records found.</td></tr>');
            $('#tfootProduct').hide();
            return;
        }

        let totInvoices = 0;
        let totPieces = 0;
        let totCartons = 0;
        let totAmt = 0;

        products.forEach((p, idx) => {
            totInvoices += p.total_invoices_count || 0;
            totPieces   += parseFloat(p.total_pieces || 0);
            totCartons  += parseFloat(p.total_boxes || 0);
            totAmt      += parseFloat(p.total_amount || 0);

            $tbody.append(`
                <tr>
                    <td class="text-center fw-bold text-muted">${idx + 1}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${p.product_code || '—'}</span></td>
                    <td class="fw-bold text-dark">${p.product_name}</td>
                    <td><span class="badge bg-light text-secondary border">${p.category_name}</span></td>
                    <td><span class="badge bg-light text-secondary border">${p.brand_name}</span></td>
                    <td class="text-center fw-bold text-primary">${p.total_invoices_count}</td>
                    <td class="text-center fw-bold text-warning">${Number(p.total_pieces || 0).toFixed(2)}</td>
                    <td class="text-center fw-bold text-success">${Number(p.total_boxes || 0).toFixed(2)}</td>
                    <td class="text-end font-monospace">Rs ${Number(p.avg_price || 0).toFixed(2)}</td>
                    <td class="text-end fw-bold text-danger font-monospace">Rs ${Number(p.total_amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td><small class="text-secondary">${p.customers_display || '—'}</small></td>
                </tr>
            `);
        });

        $('#footProdInvoices').text(totInvoices);
        $('#footProdPieces').text(totPieces.toFixed(2));
        $('#footProdCartons').text(totCartons.toFixed(2));
        $('#footProdAmount').text('Rs ' + totAmt.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#tfootProduct').show();
    }

    // Toggle chevron icon on collapse
    $(document).on('show.bs.collapse', '.collapse', function() {
        $(this).prev('.customer-header-row').find('.transition-icon').removeClass('fa-chevron-right').addClass('fa-chevron-down text-danger');
    });
    $(document).on('hide.bs.collapse', '.collapse', function() {
        $(this).prev('.customer-header-row').find('.transition-icon').removeClass('fa-chevron-down text-danger').addClass('fa-chevron-right text-secondary');
    });

    // Filter Form Submits
    $('#srFilterFormDesktop, #srFilterFormMobile').on('submit', function(e) {
        e.preventDefault();
        loadReportData();
    });

    // Reset Buttons
    $('.btnResetTrigger').on('click', function() {
        $('#srFilterFormDesktop')[0].reset();
        $('#srFilterFormMobile')[0].reset();
        if ($.fn.select2) {
            $('.select2-multi').val(null).trigger('change');
        }
        $('#filterFromDateDesk, #filterFromDateMob').val("{{ date('Y-m-01') }}");
        $('#filterToDateDesk, #filterToDateMob').val("{{ date('Y-m-d') }}");
        loadReportData();
    });

    // Print Button
    $('.btnPrintReport').on('click', function() {
        window.print();
    });

    // Excel / CSV Export
    $('.btnExportExcel').on('click', function() {
        if (!reportData) {
            alert('No data to export.');
            return;
        }

        let csv = '';
        let fileName = `Sale_Return_Report_${activeTab}_` + new Date().toISOString().slice(0,10) + '.csv';

        if (activeTab === 'detailed') {
            csv = 'Sr,Return Date,Return Voucher,Original Invoice,Customer Code,Customer Name,Mobile,Location,Product SKU,Product Name,Warehouse,Cartons,Total Pieces,Unit Price,Line Total,Remarks\n';
            (reportData.detailed_items || []).forEach((r, idx) => {
                let row = [
                    idx + 1,
                    `"${r.return_date}"`,
                    `"${r.return_invoice}"`,
                    `"${r.original_invoice}"`,
                    `"${r.customer_code}"`,
                    `"${(r.customer_name || '').replace(/"/g, '""')}"`,
                    `"${r.customer_mobile}"`,
                    `"${(r.location || '').replace(/"/g, '""')}"`,
                    `"${r.product_code}"`,
                    `"${(r.product_name || '').replace(/"/g, '""')}"`,
                    `"${(r.warehouse_name || '').replace(/"/g, '""')}"`,
                    r.boxes || 0,
                    r.qty_pcs || 0,
                    r.unit_price || 0,
                    r.line_total || 0,
                    `"${(r.remarks || '').replace(/"/g, '""')}"`
                ];
                csv += row.join(',') + '\n';
            });
        } else if (activeTab === 'customer') {
            csv = 'Sr,Customer Code,Customer Name,Mobile,Location,Return Invoices Count,Total Pieces,Total Cartons,Total Return Amount\n';
            (reportData.customer_summary || []).forEach((c, idx) => {
                let row = [
                    idx + 1,
                    `"${c.customer_code}"`,
                    `"${(c.customer_name || '').replace(/"/g, '""')}"`,
                    `"${c.customer_mobile}"`,
                    `"${(c.location || '').replace(/"/g, '""')}"`,
                    c.total_invoices_count,
                    c.total_pieces,
                    c.total_boxes,
                    c.total_amount
                ];
                csv += row.join(',') + '\n';
            });
        } else if (activeTab === 'product') {
            csv = 'Sr,SKU,Product Name,Category,Brand,Return Invoices Count,Total Pieces,Total Cartons,Avg Return Price,Total Return Value,Returning Customers\n';
            (reportData.product_summary || []).forEach((p, idx) => {
                let row = [
                    idx + 1,
                    `"${p.product_code}"`,
                    `"${(p.product_name || '').replace(/"/g, '""')}"`,
                    `"${(p.category_name || '').replace(/"/g, '""')}"`,
                    `"${(p.brand_name || '').replace(/"/g, '""')}"`,
                    p.total_invoices_count,
                    p.total_pieces,
                    p.total_boxes,
                    p.avg_price,
                    p.total_amount,
                    `"${(p.customers_display || '').replace(/"/g, '""')}"`
                ];
                csv += row.join(',') + '\n';
            });
        }

        let blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        let link = document.createElement("a");
        let url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", fileName);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Initial Load
    loadReportData();
});
</script>
@endsection
