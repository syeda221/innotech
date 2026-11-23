@extends('admin_panel.layout.app')

@section('content')
<style>
    /* Option 3: Accounting ERP - High Data Density Theme */
    .report-page-container {
        padding: 8px 12px;
        background: #f1f5f9;
        min-height: calc(100vh - 75px);
        font-family: inherit;
    }

    /* Compact Header Bar */
    .erp-header-bar {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 7px 12px;
        margin-bottom: 8px;
    }
    .erp-pill-accent {
        display: inline-block;
        width: 4px;
        height: 18px;
        background: #059669;
        border-radius: 2px;
        margin-right: 8px;
        vertical-align: middle;
    }

    /* Compact Filter Strip */
    .erp-filter-strip {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 6px 10px;
        margin-bottom: 8px;
    }
    .erp-filter-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #475569;
        margin-right: 4px;
        margin-bottom: 0;
        white-space: nowrap;
    }
    .erp-input {
        height: 28px !important;
        font-size: 0.75rem !important;
        padding: 2px 7px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 4px !important;
        background-color: #ffffff;
    }
    .erp-input:focus {
        border-color: #059669 !important;
        box-shadow: 0 0 0 1px rgba(5, 150, 105, 0.25) !important;
    }

    /* Inline Quick Stats Badges */
    .erp-stat-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 8px;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        font-size: 0.73rem;
        font-weight: 600;
        color: #334155;
    }
    .erp-stat-badge .stat-val {
        font-weight: 800;
        font-family: monospace, sans-serif;
    }
    .erp-stat-badge.total-badge {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #991b1b;
    }
    .erp-stat-badge.total-badge .stat-val {
        color: #dc2626;
        font-size: 0.82rem;
    }

    /* Dense ERP Grid */
    .erp-table-wrap {
        height: calc(100vh - 240px);
        max-height: calc(100vh - 240px);
        min-height: 380px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #ffffff;
    }
    .erp-table {
        font-size: 0.74rem;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }
    .erp-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #1e293b !important;
        color: #f8fafc !important;
        font-size: 0.70rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 6px 8px;
        border-bottom: 2px solid #0f172a;
        border-right: 1px solid #334155;
        white-space: nowrap;
    }
    .erp-table thead th:last-child {
        border-right: none;
    }
    .erp-table tbody td {
        padding: 5px 8px;
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #f1f5f9;
    }
    .erp-table tbody td:last-child {
        border-right: none;
    }
    .erp-table tbody tr:hover {
        background-color: #f8fafc;
    }
    .erp-table tbody tr:nth-child(even) {
        background-color: #fcfdfe;
    }
    .erp-table tfoot td {
        position: sticky;
        bottom: 0;
        z-index: 9;
        background-color: #1e293b !important;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 0.78rem;
        padding: 7px 8px;
        border-top: 2px solid #0f172a;
    }
    .erp-table .col-mono {
        font-family: monospace, sans-serif;
    }
    .erp-cat-badge {
        display: inline-block;
        padding: 1px 6px;
        font-size: 0.70rem;
        font-weight: 600;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 3px;
        color: #334155;
    }

    /* Action Buttons */
    .erp-btn-action {
        font-size: 11px;
        height: 22px;
        line-height: 20px;
        padding: 0 6px;
        font-weight: 700;
        border-radius: 3px;
    }

    /* Sidebar Summary */
    .erp-sidebar-card {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #ffffff;
    }
    .erp-sidebar-header {
        background: #1e293b;
        color: #ffffff;
        padding: 7px 10px;
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
    }
    .erp-cat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 10px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.74rem;
    }
    .erp-cat-row:last-child {
        border-bottom: none;
    }
    .erp-cat-row:hover {
        background: #f8fafc;
    }

    @media print {
        .no-print { display: none !important; }
        .report-page-container { background: #fff !important; padding: 0 !important; }
        .erp-table-wrap { height: auto !important; max-height: none !important; overflow: visible !important; border: none !important; }
        .erp-table { font-size: 9px !important; }
        .erp-table thead th { background-color: #1e293b !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .card, .erp-header-bar, .erp-filter-strip, .erp-sidebar-card { border: none !important; box-shadow: none !important; }
    }

    @media (max-width: 767px) {
        .report-page-container { padding: 4px 6px; }
        .erp-table-wrap { height: calc(100vh - 200px); max-height: calc(100vh - 200px); min-height: 250px; }
    }
</style>

<div class="report-page-container">

    {{-- TOP ERP HEADER & TOOLBAR --}}
    <div class="erp-header-bar d-flex justify-content-between align-items-center no-print">
        <div class="d-flex align-items-center">
            <span class="erp-pill-accent"></span>
            <div>
                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.92rem; letter-spacing: -0.2px;">
                    Expense Audit Register
                    <span class="text-muted fw-normal small ms-1" style="font-size: 0.72rem;">| General Expense & Voucher Log</span>
                </h6>
            </div>
        </div>
        <div class="d-flex align-items-center gap-1">
            <button type="button" class="btn btn-outline-secondary btn-sm fw-bold btnPrintReport" style="height: 28px; font-size: 0.74rem; padding: 2px 9px;">
                <i class="fas fa-print me-1 text-secondary"></i> Print
            </button>
            <button type="button" class="btn btn-outline-success btn-sm fw-bold btnExportExcel" style="height: 28px; font-size: 0.74rem; padding: 2px 9px;">
                <i class="fas fa-file-excel me-1 text-success"></i> Excel
            </button>
        </div>
    </div>

    {{-- ===== DESKTOP COMPACT FILTER STRIP (d-none d-md-block) ===== --}}
    <div class="erp-filter-strip d-none d-md-block no-print">
        <form id="filterFormDesktop" class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex align-items-center">
                    <label class="erp-filter-label">From:</label>
                    <input type="date" id="start_date" class="form-control erp-input startDateInput" value="{{ date('Y-m-01') }}" style="width: 135px;">
                </div>
                <div class="d-flex align-items-center">
                    <label class="erp-filter-label">To:</label>
                    <input type="date" id="end_date" class="form-control erp-input endDateInput" value="{{ date('Y-m-d') }}" style="width: 135px;">
                </div>
                <div class="d-flex align-items-center">
                    <label class="erp-filter-label">Category:</label>
                    <select id="category_filter" class="form-select erp-input categorySelect" style="width: 165px;">
                        <option value="all">All Categories</option>
                        @foreach($expenseCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn btn-sm fw-bold btnSearchTrigger" style="height: 28px; font-size: 0.74rem; padding: 2px 12px; background-color: #059669; border-color: #059669; color: #fff;">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
                <button type="button" class="btn btn-light border btn-sm fw-bold text-secondary btnResetTrigger" style="height: 28px; font-size: 0.74rem; padding: 2px 10px;">
                    <i class="fas fa-undo me-1"></i> Reset
                </button>
            </div>

            {{-- Inline KPI Stats --}}
            <div class="d-flex align-items-center gap-2 ms-auto">
                <div class="erp-stat-badge">
                    <span class="text-muted text-uppercase" style="font-size: 0.65rem;">Records:</span>
                    <span class="stat-val text-dark" id="cardTotalRecords">0</span>
                </div>
                <div class="erp-stat-badge">
                    <span class="text-muted text-uppercase" style="font-size: 0.65rem;">Heads:</span>
                    <span class="stat-val text-dark" id="cardTotalCategories">0</span>
                </div>
                <div class="erp-stat-badge total-badge">
                    <span class="text-uppercase" style="font-size: 0.65rem;">Total Spent:</span>
                    <span class="stat-val" id="cardTotalExpenses">Rs 0.00</span>
                </div>
            </div>
        </form>
    </div>

    {{-- ===== MOBILE FILTER CARD (d-md-none) ===== --}}
    <div class="erp-filter-strip d-md-none no-print p-2">
        <form id="filterFormMobile">
            <div class="row g-2">
                <div class="col-6">
                    <label class="erp-filter-label">From</label>
                    <input type="date" id="start_date_mob" class="form-control erp-input startDateInput" value="{{ date('Y-m-01') }}">
                </div>
                <div class="col-6">
                    <label class="erp-filter-label">To</label>
                    <input type="date" id="end_date_mob" class="form-control erp-input endDateInput" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-12">
                    <label class="erp-filter-label">Category</label>
                    <select class="form-select erp-input categorySelect">
                        <option value="all">All Categories</option>
                        @foreach($expenseCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-1 mt-1">
                    <button type="button" class="btn btn-sm fw-bold flex-fill btnSearchTrigger" style="height: 28px; font-size: 0.74rem; background-color: #059669; border-color: #059669; color: #fff;">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <button type="button" class="btn btn-light border btn-sm fw-bold text-secondary flex-fill btnResetTrigger" style="height: 28px; font-size: 0.74rem;">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                </div>
                <div class="col-12 mt-1">
                    <div class="d-flex justify-content-between align-items-center p-1.5 rounded" style="background: #fef2f2; border: 1px solid #fecaca;">
                        <span class="fw-bold text-danger small" style="font-size: 0.72rem;">TOTAL:</span>
                        <span class="fw-bold text-danger font-mono" id="mobCardTotalExpenses" style="font-size: 0.85rem;">Rs 0.00</span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- LOADER --}}
    <div id="loader" style="display:none; text-align:center; padding: 15px;">
        <div class="spinner-border spinner-border-sm text-success" role="status"></div>
        <span class="small text-muted ms-2" style="font-size: 0.75rem;">Retrieving ledger entries…</span>
    </div>

    {{-- Content Layout --}}
    <div class="row g-2 mb-2">

        {{-- Expense Detail Table --}}
        <div class="col-lg-8">
            <div class="erp-table-wrap">
                <table class="erp-table table-hover" id="expenseTable">
                    <thead>
                        <tr>
                            <th style="width: 35px;" class="text-center">#</th>
                            <th style="width: 85px;">Date</th>
                            <th style="width: 90px;">Voucher No</th>
                            <th style="width: 120px;">Category</th>
                            <th style="width: 70px;">Type</th>
                            <th>Party</th>
                            <th>Narration</th>
                            <th>Remarks</th>
                            <th class="text-end" style="width: 105px;">Amount</th>
                            <th class="text-center no-print" style="width: 110px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="expenseTableBody">
                        <tr><td colspan="10" class="text-center text-muted py-4" style="font-size: 0.75rem;"><i class="fas fa-search me-1"></i>Select date range and click Filter to load expenses</td></tr>
                    </tbody>
                    <tfoot id="expenseTableFoot" style="display: none;">
                        <tr>
                            <td colspan="8" class="text-end text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.4px;">Grand Total:</td>
                            <td class="text-end col-mono text-danger fw-bold" id="footGrandTotal" style="font-size: 0.82rem;">Rs 0.00</td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center px-1 mt-1 text-muted" style="font-size: 0.70rem;">
                <span id="tableRecordCount">0 records loaded</span>
                <span>Sorted by Entry Date (Descending)</span>
            </div>
        </div>

        {{-- Category Summary Sidebar --}}
        <div class="col-lg-4">
            <div class="erp-sidebar-card shadow-sm flex flex-col justify-content-between">
                <div>
                    <div class="erp-sidebar-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-chart-pie text-emerald-400 me-1.5"></i>Category Ledger Breakdown</span>
                        <span class="badge bg-slate-700 text-light" style="font-size: 0.65rem;" id="catCountBadge">0 heads</span>
                    </div>
                    <div class="p-0" id="categorySummaryBody" style="max-height: calc(100vh - 300px); overflow-y: auto;">
                        <div class="text-center text-muted py-4" style="font-size: 0.75rem;">
                            <i class="fas fa-inbox me-1"></i> No breakdown data yet
                        </div>
                    </div>
                </div>
                <div class="p-2 border-top d-flex justify-content-between align-items-center" id="categorySummaryFooter" style="display: none !important; background: #f8fafc; border-bottom-left-radius: 5px; border-bottom-right-radius: 5px;">
                    <span class="fw-bold text-dark text-uppercase" style="font-size: 0.72rem;">Total Breakdown:</span>
                    <span class="fw-bold text-danger col-mono" style="font-size: 0.85rem;" id="catGrandTotal">Rs 0.00</span>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('js')
<script>
$(function() {
    function fmt(val) {
        let n = parseFloat(val) || 0;
        return 'Rs ' + n.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function getFilters() {
        let isMobile = $(window).width() < 768;
        return {
            start_date: isMobile ? $('#start_date_mob').val() : $('#start_date').val(),
            end_date: isMobile ? $('#end_date_mob').val() : $('#end_date').val(),
            category_id: isMobile ? $('.categorySelect:visible').val() : $('#category_filter').val(),
        };
    }

    function fetchReport() {
        let filters = getFilters();

        $('#loader').show();
        $('#expenseTableBody').html('');
        $('#expenseTableFoot').hide();
        $('#categorySummaryFooter').css('display', 'none');

        $.ajax({
            url: "{{ route('report.expense.fetch') }}",
            type: 'POST',
            data: filters,
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            success: function(res) {
                $('#loader').hide();

                if (!res.success) {
                    $('#expenseTableBody').html('<tr><td colspan="10" class="text-center text-danger py-4">Error loading data</td></tr>');
                    return;
                }

                // Update summary badges
                let formattedTotal = fmt(res.grand_total);
                $('#cardTotalExpenses, #mobCardTotalExpenses').text(formattedTotal);
                $('#cardTotalRecords').text(res.total_records);
                $('#cardTotalCategories').text(res.category_summary.length);
                $('#catCountBadge').text(res.category_summary.length + ' heads');

                // Build table
                if (res.expenses.length === 0) {
                    $('#expenseTableBody').html('<tr><td colspan="10" class="text-center text-muted py-4"><i class="fas fa-inbox me-2"></i>No expenses found for selected filters</td></tr>');
                    $('#categorySummaryBody').html('<div class="text-center text-muted py-4" style="font-size: .75rem;"><i class="fas fa-chart-pie me-1"></i> No data</div>');
                    return;
                }

                let html = '';
                $.each(res.expenses, function(i, exp) {
                    html += '<tr>';
                    html += '<td class="text-center col-mono text-muted" style="font-size: 0.70rem;">' + (i + 1) + '</td>';
                    html += '<td class="text-nowrap">' + (exp.entry_date || '-') + '</td>';
                    html += '<td class="col-mono fw-bold text-dark">' + (exp.voucher_no || '-') + '</td>';
                    html += '<td><span class="erp-cat-badge">' + (exp.category || '-') + '</span></td>';
                    html += '<td class="text-muted">' + (exp.type || '-') + '</td>';
                    html += '<td class="fw-semibold text-dark">' + (exp.party_name || '-') + '</td>';
                    html += '<td style="max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="' + (exp.narration || '') + '">' + (exp.narration || '-') + '</td>';
                    html += '<td style="max-width: 110px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="' + (exp.remarks || '') + '">' + (exp.remarks || '-') + '</td>';
                    html += '<td class="text-end col-mono fw-bold text-danger">' + fmt(exp.amount) + '</td>';
                    html += '<td class="text-center no-print" style="white-space: nowrap;">';
                    html += '<div class="d-inline-flex align-items-center gap-1">';
                    if (exp.edit_url) {
                        html += '<a href="' + exp.edit_url + '" class="btn btn-outline-primary erp-btn-action" title="Edit Voucher">';
                        html += '<i class="fas fa-edit me-1"></i>Edit';
                        html += '</a>';
                    }
                    if (exp.delete_url) {
                        html += '<button type="button" class="btn btn-outline-danger erp-btn-action btnDeleteExpense" data-url="' + exp.delete_url + '" data-voucherno="' + (exp.voucher_no || '') + '" title="Delete Voucher">';
                        html += '<i class="fas fa-trash-alt me-1"></i>Del';
                        html += '</button>';
                    }
                    if (!exp.edit_url && !exp.delete_url) {
                        html += '<span class="text-muted small">-</span>';
                    }
                    html += '</div>';
                    html += '</td>';
                    html += '</tr>';
                });

                $('#expenseTableBody').html(html);
                $('#expenseTableFoot').show();
                $('#footGrandTotal').text(formattedTotal);
                $('#tableRecordCount').text(res.total_records + ' records loaded');

                // Build category summary
                let catHtml = '';
                $.each(res.category_summary, function(i, cat) {
                    let pct = res.grand_total > 0 ? ((cat.total / res.grand_total) * 100).toFixed(1) : 0;
                    catHtml += '<div class="erp-cat-row">';
                    catHtml += '<div style="flex: 1; padding-right: 8px;">';
                    catHtml += '<div class="d-flex justify-content-between align-items-center mb-0.5">';
                    catHtml += '<span class="fw-bold text-dark" style="font-size: 0.73rem;">' + cat.category + ' <span class="text-muted font-normal" style="font-size: 0.68rem;">(' + cat.count + ')</span></span>';
                    catHtml += '<span class="text-muted" style="font-size: 0.68rem;">' + pct + '%</span>';
                    catHtml += '</div>';
                    catHtml += '<div style="width: 100%; height: 4px; background: #e2e8f0; border-radius: 2px; overflow: hidden;">';
                    catHtml += '<div style="width: ' + pct + '%; height: 100%; background: #059669; border-radius: 2px;"></div>';
                    catHtml += '</div>';
                    catHtml += '</div>';
                    catHtml += '<span class="col-mono fw-bold text-danger" style="font-size: 0.74rem;">' + fmt(cat.total) + '</span>';
                    catHtml += '</div>';
                });
                $('#categorySummaryBody').html(catHtml);
                $('#categorySummaryFooter').css('display', 'flex');
                $('#catGrandTotal').text(formattedTotal);
            },
            error: function(xhr) {
                $('#loader').hide();
                $('#expenseTableBody').html('<tr><td colspan="10" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load data. Please try again.</td></tr>');
            }
        });
    }

    // Search button
    $(document).on('click', '.btnSearchTrigger', function() {
        fetchReport();
    });

    // Reset button
    $(document).on('click', '.btnResetTrigger', function() {
        let today = new Date().toISOString().split('T')[0];
        let firstDay = today.substring(0, 8) + '01';
        $('.startDateInput').val(firstDay);
        $('.endDateInput').val(today);
        $('.categorySelect').val('all');
        // Clear results
        $('#expenseTableBody').html('<tr><td colspan="10" class="text-center text-muted py-4"><i class="fas fa-search me-1"></i>Select date range and click Filter</td></tr>');
        $('#expenseTableFoot').hide();
        $('#categorySummaryBody').html('<div class="text-center text-muted py-4" style="font-size: .75rem;"><i class="fas fa-chart-pie me-1"></i> No data yet</div>');
        $('#categorySummaryFooter').css('display', 'none');
        $('#cardTotalExpenses, #mobCardTotalExpenses').text('Rs 0.00');
        $('#cardTotalRecords').text('0');
        $('#cardTotalCategories').text('0');
        $('#catCountBadge').text('0 heads');
        $('#tableRecordCount').text('0 records loaded');
    });

    // Delete expense voucher
    $(document).on('click', '.btnDeleteExpense', function(e) {
        e.preventDefault();
        let deleteUrl = $(this).data('url');
        let voucherNo = $(this).data('voucherno') || 'this voucher';

        if (!deleteUrl) return;

        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to delete Expense Voucher ' + voucherNo + '? This will reverse accounting entries.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Deleting...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: deleteUrl,
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: (res && res.message) ? res.message : 'Expense Voucher deleted successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        fetchReport();
                    },
                    error: function(xhr) {
                        let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete Expense Voucher.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: msg
                        });
                    }
                });
            }
        });
    });

    // Print button
    $(document).on('click', '.btnPrintReport', function() {
        window.print();
    });

    // Excel Export
    $(document).on('click', '.btnExportExcel', function() {
        let table = document.getElementById('expenseTable');
        if (!table) return;

        let csv = [];
        let rows = table.querySelectorAll('thead tr, tbody tr, tfoot tr');
        rows.forEach(function(row) {
            let cols = row.querySelectorAll('th:not(.no-print), td:not(.no-print)');
            let rowData = [];
            cols.forEach(function(col) {
                let text = col.innerText.replace(/"/g, '""');
                rowData.push('"' + text + '"');
            });
            csv.push(rowData.join(','));
        });

        let csvContent = '\uFEFF' + csv.join('\n');
        let blob = new Blob([csvContent], {type: 'text/csv;charset=utf-8;'});
        let url = URL.createObjectURL(blob);
        let a = document.createElement('a');
        a.href = url;
        a.download = 'Expense_Audit_Register_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        URL.revokeObjectURL(url);
    });

    // Auto-load on page open
    fetchReport();
});
</script>
@endsection
