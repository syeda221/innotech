@extends('admin_panel.layout.app')

@section('content')
<style>
    /* Standardized Report Pattern Styling */
    .report-page-container {
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
        height: calc(100vh - 280px);
        max-height: calc(100vh - 280px);
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
        .no-print { display: none !important; }
        .report-page-container { background: #fff !important; padding: 0 !important; }
        .sale-table-wrap { height: auto !important; max-height: none !important; overflow: visible !important; border: none !important; }
        .report-table { font-size: 10px !important; }
        .report-table thead th { background-color: #1e293b !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .card { border: none !important; box-shadow: none !important; }
    }

    @media (max-width: 767px) {
        .report-page-container { padding: 6px 4px; }
        .sale-table-wrap { height: calc(100vh - 180px); max-height: calc(100vh - 180px); min-height: 250px; }
    }

    /* Category Summary Card */
    .cat-summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 10px;
        border-bottom: 1px solid #f1f5f9;
        font-size: .78rem;
    }
    .cat-summary-item:last-child { border-bottom: none; }
    .cat-summary-item .cat-name { font-weight: 600; color: #334155; }
    .cat-summary-item .cat-count { color: #94a3b8; font-size: .7rem; }
    .cat-summary-item .cat-amount { font-weight: 700; color: #dc2626; }
</style>

<div class="report-page-container">

    {{-- PAGE TITLE --}}
    <div class="d-flex justify-content-between align-items-center mb-2 no-print">
        <div>
            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-file-invoice-dollar text-danger me-2"></i>Expense Report</h5>
            <p class="text-muted mb-0 small">View and analyze all expenses with date & category filters</p>
        </div>
    </div>

    {{-- ===== DESKTOP FILTER BAR (d-none d-md-block) ===== --}}
    <div class="card border-0 shadow-sm mb-2 d-none d-md-block no-print" style="border-radius: 10px;">
        <div class="card-body py-2 px-3">
            <form id="filterFormDesktop" class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label class="sale-filter-label">From</label>
                    <input type="date" id="start_date" class="form-control form-control-sm startDateInput" value="{{ date('Y-m-01') }}" style="width: 150px; font-size: .78rem;">
                </div>
                <div>
                    <label class="sale-filter-label">To</label>
                    <input type="date" id="end_date" class="form-control form-control-sm endDateInput" value="{{ date('Y-m-d') }}" style="width: 150px; font-size: .78rem;">
                </div>
                <div>
                    <label class="sale-filter-label">Category</label>
                    <select id="category_filter" class="form-select form-select-sm categorySelect" style="width: 180px; font-size: .78rem;">
                        <option value="all">All Categories</option>
                        @foreach($expenseCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn btn-primary btn-sm fw-bold btnSearchTrigger" style="height: 31px; font-size: .78rem;">
                    <i class="fas fa-search me-1"></i> Search
                </button>
                <button type="button" class="btn btn-light border btn-sm fw-bold text-secondary btnResetTrigger" style="height: 31px; font-size: .78rem;">
                    <i class="fas fa-undo me-1"></i> Reset
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm fw-bold btnPrintReport" style="height: 31px; font-size: .78rem;">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                <button type="button" class="btn btn-outline-success btn-sm fw-bold btnExportExcel" style="height: 31px; font-size: .78rem;">
                    <i class="fas fa-file-excel me-1"></i> Excel
                </button>
            </form>
        </div>
    </div>

    {{-- ===== MOBILE FILTER CARD (d-md-none) ===== --}}
    <div class="card border-0 shadow-sm mb-2 d-md-none no-print" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form id="filterFormMobile">
                <div class="row g-2">
                    <div class="col-6 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">From</label>
                        <input type="date" id="start_date_mob" class="form-control form-control-sm startDateInput" value="{{ date('Y-m-01') }}" style="font-size: 11px;">
                    </div>
                    <div class="col-6 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">To</label>
                        <input type="date" id="end_date_mob" class="form-control form-control-sm endDateInput" value="{{ date('Y-m-d') }}" style="font-size: 11px;">
                    </div>
                    <div class="col-12 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Category</label>
                        <select class="form-select form-select-sm categorySelect" style="font-size: 11px;">
                            <option value="all">All Categories</option>
                            @foreach($expenseCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 my-2">
                        <button type="button" class="btn btn-primary w-100 py-2 fw-bold rounded-3 shadow-sm btnSearchTrigger" style="background-color: #3b82f6; border-color: #3b82f6; font-size: 13px;">
                            <i class="fas fa-search me-1"></i> Search Report
                        </button>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-center gap-2 pt-1" style="gap: 10px !important;">
                            <button type="button" class="btn btn-light border btn-sm flex-fill fw-bold text-secondary btnResetTrigger" style="font-size: 11px; margin-right: 8px !important;">
                                <i class="fas fa-undo me-1"></i> Reset
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill fw-bold btnPrintReport" style="font-size: 11px;">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm flex-fill fw-bold btnExportExcel" style="font-size: 11px;">
                                <i class="fas fa-file-excel me-1"></i> Excel
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
                <div class="stat-pill" style="background: #fef2f2; border-color: #fca5a5;">
                    <div class="stat-label text-danger">Total Expenses</div>
                    <div class="stat-val text-danger" id="cardTotalExpenses">Rs 0.00</div>
                </div>
                <div class="stat-pill" style="background: #fff7ed; border-color: #fed7aa;">
                    <div class="stat-label text-warning">Total Records</div>
                    <div class="stat-val text-warning" id="cardTotalRecords">0</div>
                </div>
                <div class="stat-pill" style="background: #f0fdf4; border-color: #86efac;">
                    <div class="stat-label text-success">Categories</div>
                    <div class="stat-val text-success" id="cardTotalCategories">0</div>
                </div>
            </div>
        </div>
    </div>

    {{-- MOBILE SUMMARY METRIC GRID (d-md-none) --}}
    <div class="row g-2 mb-3 d-md-none no-print px-1">
        <div class="col-4">
            <div class="mob-metric-card p-2">
                <span class="mob-metric-label" style="font-size: 10px;"><i class="fas fa-money-bill-wave text-danger me-1"></i>Total</span>
                <div class="mob-metric-val text-danger mt-1" id="mobCardTotalExpenses" style="font-size: 13px;">Rs 0</div>
            </div>
        </div>
        <div class="col-4">
            <div class="mob-metric-card p-2">
                <span class="mob-metric-label" style="font-size: 10px;"><i class="fas fa-receipt text-warning me-1"></i>Records</span>
                <div class="mob-metric-val text-warning mt-1" id="mobCardTotalRecords" style="font-size: 13px;">0</div>
            </div>
        </div>
        <div class="col-4">
            <div class="mob-metric-card p-2">
                <span class="mob-metric-label" style="font-size: 10px;"><i class="fas fa-tags text-success me-1"></i>Categories</span>
                <div class="mob-metric-val text-success mt-1" id="mobCardTotalCategories" style="font-size: 13px;">0</div>
            </div>
        </div>
    </div>

    <div id="loader" style="display:none; text-align:center; padding: 20px;">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="small text-muted mt-2">Loading Expense Data…</div>
    </div>

    {{-- Content Layout --}}
    <div class="row g-3 mb-3">

        {{-- Expense Detail Table --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark" style="font-size: .85rem;"><i class="fas fa-list me-1"></i> Expense Details</span>
                    <span class="badge bg-secondary" id="tableRecordCount">0 records</span>
                </div>
                <div class="sale-table-wrap">
                    <table class="table table-hover report-table mb-0" id="expenseTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Voucher No</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Party</th>
                                <th>Narration</th>
                                <th>Remarks</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="expenseTableBody">
                            <tr><td colspan="9" class="text-center text-muted py-5"><i class="fas fa-search me-2"></i>Select date range and click Search to load expenses</td></tr>
                        </tbody>
                        <tfoot id="expenseTableFoot" style="display: none;">
                            <tr class="table-dark fw-bold">
                                <td colspan="8" class="text-end">Grand Total:</td>
                                <td class="text-end" id="footGrandTotal">Rs 0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Category Summary Sidebar --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-light py-2 px-3">
                    <span class="fw-bold text-dark" style="font-size: .85rem;"><i class="fas fa-chart-pie me-1 text-danger"></i> Category Wise Summary</span>
                </div>
                <div class="card-body p-0" id="categorySummaryBody" style="max-height: calc(100vh - 320px); overflow-y: auto;">
                    <div class="text-center text-muted py-5" style="font-size: .8rem;">
                        <i class="fas fa-chart-pie me-1"></i> No data yet
                    </div>
                </div>
                <div class="card-footer bg-danger text-white py-2 px-3 d-flex justify-content-between align-items-center" id="categorySummaryFooter" style="display: none !important;">
                    <span class="fw-bold" style="font-size: .82rem;">Grand Total</span>
                    <span class="fw-bold" style="font-size: .95rem;" id="catGrandTotal">Rs 0.00</span>
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
                    $('#expenseTableBody').html('<tr><td colspan="9" class="text-center text-danger py-4">Error loading data</td></tr>');
                    return;
                }

                // Update summary cards
                let formattedTotal = fmt(res.grand_total);
                $('#cardTotalExpenses, #mobCardTotalExpenses').text(formattedTotal);
                $('#cardTotalRecords, #mobCardTotalRecords').text(res.total_records);
                $('#cardTotalCategories, #mobCardTotalCategories').text(res.category_summary.length);

                // Build table
                if (res.expenses.length === 0) {
                    $('#expenseTableBody').html('<tr><td colspan="9" class="text-center text-muted py-5"><i class="fas fa-inbox me-2"></i>No expenses found for selected filters</td></tr>');
                    $('#categorySummaryBody').html('<div class="text-center text-muted py-5" style="font-size: .8rem;"><i class="fas fa-chart-pie me-1"></i> No data</div>');
                    return;
                }

                let html = '';
                $.each(res.expenses, function(i, exp) {
                    html += '<tr>';
                    html += '<td>' + (i + 1) + '</td>';
                    html += '<td class="text-nowrap">' + (exp.entry_date || '-') + '</td>';
                    html += '<td>' + (exp.voucher_no || '-') + '</td>';
                    html += '<td><span class="badge bg-light text-dark border">' + (exp.category || '-') + '</span></td>';
                    html += '<td>' + (exp.type || '-') + '</td>';
                    html += '<td>' + (exp.party_name || '-') + '</td>';
                    html += '<td style="max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="' + (exp.narration || '') + '">' + (exp.narration || '-') + '</td>';
                    html += '<td style="max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="' + (exp.remarks || '') + '">' + (exp.remarks || '-') + '</td>';
                    html += '<td class="text-end fw-bold text-danger">' + fmt(exp.amount) + '</td>';
                    html += '</tr>';
                });

                $('#expenseTableBody').html(html);
                $('#expenseTableFoot').show();
                $('#footGrandTotal').text(formattedTotal);
                $('#tableRecordCount').text(res.total_records + ' records');

                // Build category summary
                let catHtml = '';
                $.each(res.category_summary, function(i, cat) {
                    let pct = res.grand_total > 0 ? ((cat.total / res.grand_total) * 100).toFixed(1) : 0;
                    catHtml += '<div class="cat-summary-item">';
                    catHtml += '<div><span class="cat-name">' + cat.category + '</span> <span class="cat-count">(' + cat.count + ')</span></div>';
                    catHtml += '<div class="d-flex align-items-center gap-2">';
                    catHtml += '<div style="width: 50px; height: 6px; background: #fee2e2; border-radius: 3px; overflow: hidden;"><div style="width: ' + pct + '%; height: 100%; background: #dc2626; border-radius: 3px;"></div></div>';
                    catHtml += '<span class="cat-amount">' + fmt(cat.total) + '</span>';
                    catHtml += '</div>';
                    catHtml += '</div>';
                });
                $('#categorySummaryBody').html(catHtml);
                $('#categorySummaryFooter').css('display', 'flex');
                $('#catGrandTotal').text(formattedTotal);
            },
            error: function(xhr) {
                $('#loader').hide();
                $('#expenseTableBody').html('<tr><td colspan="9" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load data. Please try again.</td></tr>');
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
        $('#expenseTableBody').html('<tr><td colspan="9" class="text-center text-muted py-5"><i class="fas fa-search me-2"></i>Select date range and click Search</td></tr>');
        $('#expenseTableFoot').hide();
        $('#categorySummaryBody').html('<div class="text-center text-muted py-5" style="font-size: .8rem;"><i class="fas fa-chart-pie me-1"></i> No data yet</div>');
        $('#categorySummaryFooter').css('display', 'none');
        $('#cardTotalExpenses, #mobCardTotalExpenses').text('Rs 0.00');
        $('#cardTotalRecords, #mobCardTotalRecords').text('0');
        $('#cardTotalCategories, #mobCardTotalCategories').text('0');
        $('#tableRecordCount').text('0 records');
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
            let cols = row.querySelectorAll('th, td');
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
        a.download = 'Expense_Report_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        URL.revokeObjectURL(url);
    });

    // Auto-load on page open
    fetchReport();
});
</script>
@endsection
