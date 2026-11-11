@extends('admin_panel.layout.app')

@section('content')
    <style>
        /* ==================== POS / SALE STYLE COMPACT ERP THEME ==================== */
        :root {
            --pos-blue: #2563EB;
            --pos-blue-hover: #1D4ED8;
            --pos-blue-soft: #EFF6FF;
            --pos-green: #16A34A;
            --pos-green-soft: #F0FDF4;
            --pos-red: #DC2626;
            --pos-red-soft: #FEF2F2;
            --pos-orange: #F59E0B;
            --pos-orange-soft: #FFFBEB;
            --pos-text: #0F172A;
            --pos-muted: #64748B;
            --pos-border: #CBD5E1;
            --pos-border-strong: #94A3B8;
            --pos-bg: #F1F5F9;
            --pos-card: #FFFFFF;
            --pos-radius: 8px;
            --pos-radius-lg: 12px;
            --pos-shadow-sm: 0 1px 3px rgba(0,0,0,.05);
            --pos-shadow-md: 0 4px 14px rgba(15,23,42,.08);
            --pos-input-h: 38px;
        }

        body {
            background-color: var(--pos-bg) !important;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            color: var(--pos-text) !important;
            -webkit-font-smoothing: antialiased;
        }

        .purchase-page {
            max-width: 1560px;
            margin: 0 auto;
        }

        /* ---------- CARDS & CONTAINERS ---------- */
        .sale-card {
            background: var(--pos-card);
            border: 1px solid var(--pos-border);
            border-radius: var(--pos-radius-lg);
            box-shadow: var(--pos-shadow-sm);
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .field-label {
            display: block;
            font-size: 11.5px;
            font-weight: 700;
            color: var(--pos-muted);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: .3px;
            line-height: 1.2;
        }

        /* ---------- TOP FORM CONTROLS ---------- */
        .purchase-page .form-control,
        .purchase-page .form-select {
            height: var(--pos-input-h);
            border: 1px solid var(--pos-border);
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--pos-text);
            background-color: #ffffff;
            box-shadow: none;
            transition: border-color .12s ease, box-shadow .12s ease;
        }
        .purchase-page .form-control::placeholder {
            color: #94A3B8;
            font-weight: 400;
        }
        .purchase-page .form-control:focus,
        .purchase-page .form-select:focus,
        .purchase-page .form-control:focus-visible {
            border: 2px solid var(--pos-blue) !important;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .15) !important;
            outline: none !important;
            background-color: #ffffff !important;
        }
        .purchase-page .input-readonly,
        .purchase-page input[readonly] {
            background-color: #F8FAFC !important;
            color: #475569 !important;
            border-color: #E2E8F0 !important;
            cursor: default;
            font-weight: 600;
        }

        /* Select2 (Vendor) */
        #vendorInputWrapper .select2-container--default .select2-selection--single {
            height: var(--pos-input-h) !important;
            border: 1px solid var(--pos-border) !important;
            border-radius: 6px !important;
            background-color: #ffffff !important;
            padding: 0 !important;
        }
        #vendorInputWrapper .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px !important;
            padding-left: 10px !important;
            font-size: 13.5px !important;
            font-weight: 600 !important;
            color: var(--pos-text) !important;
        }
        #vendorInputWrapper .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 6px !important;
        }
        #vendorInputWrapper .select2-container--default.select2-container--focus .select2-selection--single,
        #vendorInputWrapper .select2-container--default.select2-container--open .select2-selection--single {
            border: 2px solid var(--pos-blue) !important;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .15) !important;
        }

        .customer-input-group {
            display: flex;
            flex-wrap: nowrap !important;
            align-items: center;
            width: 100%;
        }
        .customer-input-group #vendorInputWrapper {
            flex: 1 1 auto;
            min-width: 0;
        }
        .customer-input-group #btnOpenAddVendorModal,
        .customer-input-group #btnOpenVendorDetailsModal {
            height: var(--pos-input-h);
            border-radius: 0 6px 6px 0 !important;
            border-left: 0 !important;
            padding: 0 12px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .customer-input-group #btnOpenAddVendorModal {
            border-radius: 0 !important;
        }

        /* ---------- PAGE HEADER ---------- */
        .sale-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 12px;
        }
        .sale-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sale-title-ic {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: var(--pos-blue-soft);
            color: var(--pos-blue);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
            border: 1px solid #BFDBFE;
        }
        .sale-title-main h5 {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -.3px;
            color: var(--pos-text);
            margin-bottom: 2px;
        }
        .sale-subtitle {
            font-size: 12.5px;
            color: var(--pos-muted);
        }

        .btn-icon-back {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--pos-border);
            background: #ffffff;
            color: var(--pos-muted);
            font-size: 14px;
            flex-shrink: 0;
            transition: all .15s ease;
            text-decoration: none;
        }
        .btn-icon-back:hover {
            background: #F1F5F9;
            color: var(--pos-text);
            border-color: var(--pos-border-strong);
        }

        /* ---------- STRICT EXCEL GRID TABLE (NO SPACE, NO GAP) ---------- */
        .items-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 11px;
            background: var(--pos-blue-soft);
            color: var(--pos-blue);
            font-size: 11.5px;
            font-weight: 800;
        }

        .pos-table-wrap {
            overflow-x: auto;
            border: 1px solid var(--pos-border);
            border-radius: 6px;
            background: #ffffff;
        }

        .sales-table {
            width: 100%;
            border-collapse: collapse !important;
            border-spacing: 0 !important;
            margin: 0 !important;
            font-size: 13px;
        }

        .sales-table thead th {
            background: #F8FAFC !important;
            color: #475569 !important;
            font-size: 11.5px !important;
            font-weight: 800 !important;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 8px 6px !important;
            border-bottom: 1px solid var(--pos-border) !important;
            border-right: 1px solid var(--pos-border) !important;
            vertical-align: middle;
            white-space: nowrap;
        }
        .sales-table thead th:last-child {
            border-right: none !important;
        }

        .sales-table tbody td {
            padding: 0 !important;
            margin: 0 !important;
            border-bottom: 1px solid #E2E8F0 !important;
            border-right: 1px solid #E2E8F0 !important;
            vertical-align: middle;
            background: #ffffff;
            height: 34px !important;
        }
        .sales-table tbody td:last-child {
            border-right: none !important;
        }

        .sales-table tbody tr:hover td {
            background-color: #F8FAFC;
        }

        .row-index-cell {
            font-size: 11.5px;
            font-weight: 700;
            color: #64748B;
            text-align: center;
            background: #F8FAFC !important;
            width: 36px;
            user-select: none;
        }

        /* STRICT EXCEL GRID CELLS: 0 border radius, 0 margin */
        .sales-table tbody .form-control,
        .sales-table tbody .form-select {
            height: 34px !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 2px 6px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            background: transparent !important;
            box-shadow: none !important;
            color: #0F172A !important;
            width: 100% !important;
            margin: 0 !important;
        }

        .sales-table tbody .form-control:focus,
        .sales-table tbody .form-select:focus,
        .sales-table tbody .form-control:focus-visible {
            border: 2px solid var(--pos-blue) !important;
            background: #ffffff !important;
            box-shadow: inset 0 0 0 1px var(--pos-blue) !important;
            outline: none !important;
            z-index: 2;
            position: relative;
        }

        .sales-table tbody input[readonly],
        .sales-table tbody .input-readonly {
            background: #F8FAFC !important;
            color: #475569 !important;
            cursor: default !important;
            font-weight: 600 !important;
        }

        /* Product Description Cell */
        .sales-table tbody .col-product {
            min-width: 260px;
        }

        .sales-table tbody .select2-container .select2-selection--single {
            height: 34px !important;
            border: none !important;
            border-radius: 0 !important;
            background: transparent !important;
            padding: 0 !important;
        }
        .sales-table tbody .select2-container--open .select2-selection--single,
        .sales-table tbody .select2-container--focus .select2-selection--single {
            border: 2px solid var(--pos-blue) !important;
            background: #ffffff !important;
            outline: none !important;
        }
        .sales-table tbody .select2-container .select2-selection__rendered {
            line-height: 34px !important;
            padding-left: 6px !important;
            padding-right: 20px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            color: #0F172A !important;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .sales-table tbody .select2-container .select2-selection__arrow {
            height: 34px !important;
            right: 4px !important;
        }

        .unit-toggle-btn {
            height: 34px !important;
            width: 100% !important;
            border-radius: 0 !important;
            border: none !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 !important;
            margin: 0 !important;
        }
        .remove-row {
            height: 34px !important;
            width: 100% !important;
            border: none !important;
            border-radius: 0 !important;
            color: #DC2626 !important;
            background: transparent !important;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 !important;
            margin: 0 !important;
            cursor: pointer;
        }
        .remove-row:hover {
            background: #FEF2F2 !important;
            color: #991B1B !important;
        }

        /* ---------- SUMMARY CARD (IDENTICAL TO SALE SCREEN) ---------- */
        .s-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 6px;
            padding: 4px 0;
            font-size: 12px;
            border-bottom: 1px solid #F1F5F9;
        }
        .s-label {
            color: #64748B;
            font-weight: 600;
        }
        .s-val {
            font-weight: 700;
            color: #0F172A;
            font-variant-numeric: tabular-nums;
        }
        .s-row.net {
            padding: 6px 10px;
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-radius: 6px;
            margin: 6px 0;
        }
        .net-label {
            font-size: 13px;
            font-weight: 800;
            color: #1E3A8A;
            text-transform: uppercase;
        }
        .net-val {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -.3px;
            color: #2563EB;
            font-variant-numeric: tabular-nums;
        }
        .paid-val {
            font-weight: 800;
            font-size: 13px;
            color: #16A34A;
            font-variant-numeric: tabular-nums;
        }
        .change-val {
            font-weight: 700;
            font-size: 13px;
            font-variant-numeric: tabular-nums;
        }
        .change-val.text-danger {
            color: var(--pos-red) !important;
        }
        .discount-input {
            width: 120px;
            flex-shrink: 0;
        }
        .discount-input input {
            height: 28px !important;
            font-size: 12px !important;
            font-weight: 700;
        }
        .discount-input .input-group-text {
            height: 28px !important;
            font-size: 11px !important;
            padding: 0 8px !important;
        }

        /* Modal Close Button Styling for Bootstrap 4 / 5 */
        .modal .close,
        .modal .btn-close {
            background: transparent !important;
            border: 0 !important;
            font-size: 1.5rem !important;
            font-weight: 700 !important;
            line-height: 1 !important;
            color: #475569 !important;
            opacity: 0.75 !important;
            padding: 2px 8px !important;
            cursor: pointer !important;
            text-shadow: none !important;
            box-shadow: none !important;
        }
        .modal .close:hover,
        .modal .btn-close:hover {
            color: #0F172A !important;
            opacity: 1 !important;
        }
    </style>

    <div class="container-fluid px-3 px-lg-4 pt-3 pb-4 purchase-page">
        <div id="alertBox" class="alert d-none" role="alert"></div>

        <form id="purchaseForm" action="{{ route('purchase.update', $purchase->id) }}" method="POST" autocomplete="off">
            @csrf
            @method('PUT')

            {{-- ============================ PAGE HEADER ============================ --}}
            <div class="sale-header">
                <div class="sale-header-left">
                    <a href="{{ route('Purchase.home') }}" class="btn-icon-back" title="Back to Purchase List">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div class="sale-title-ic">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="sale-title-main">
                        <h5 class="header-text mb-0">Edit Purchase #{{ $purchase->invoice_no }}</h5>
                        <div class="sale-subtitle">Update purchase invoice, stock &amp; batches/IMEIs</div>
                    </div>
                </div>

                {{-- Header Right: Invoice No, Bill No & Date --}}
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    {{-- System Inv # --}}
                    <div class="d-flex align-items-center gap-2">
                        <label class="field-label mb-0 fw-bold text-secondary">Inv #:</label>
                        <input type="text" class="form-control text-center fw-bold input-readonly" name="invoice_no" value="{{ $purchase->invoice_no }}" readonly style="width: 140px; height: 38px;">
                    </div>

                    {{-- Vendor Inv # --}}
                    <div class="d-flex align-items-center gap-2">
                        <label class="field-label mb-0 fw-bold text-secondary">Vendor Inv #:</label>
                        <input type="text" class="form-control text-center fw-bold" name="purchase_order_no" placeholder="Vendor Inv #" value="{{ $purchase->purchase_order_no }}" style="width: 140px; height: 38px;">
                    </div>

                    {{-- Date --}}
                    <div class="d-flex align-items-center gap-2">
                        <label class="field-label mb-0 fw-bold text-secondary">Date:</label>
                        <input type="date" name="purchase_date" class="form-control text-center fw-bold" value="{{ $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : date('Y-m-d') }}" style="width: 150px; height: 38px;">
                    </div>
                </div>
            </div>

            {{-- ============================ VENDOR INFORMATION (COMPACT SINGLE ROW) ============================ --}}
            <div class="sale-card mb-2 p-2 px-3">
                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    {{-- Vendor Select Group --}}
                    <div class="d-flex align-items-center gap-2" style="max-width: 440px; flex: 1 1 300px;">
                        <label class="field-label mb-0 text-nowrap fw-bold">Vendor <span class="text-danger">*</span>:</label>
                        <div class="input-group input-group-sm customer-input-group flex-nowrap" style="min-width: 0; flex: 1;">
                            <div id="vendorInputWrapper" style="min-width: 0; flex: 1;">
                                <select class="form-select select2" id="vendorSelect" name="vendor_id" style="width:100%">
                                    <option value="" disabled>Select Vendor...</option>
                                    @foreach ($Vendor as $v)
                                        <option value="{{ $v->id }}" 
                                                data-phone="{{ $v->phone }}" 
                                                data-address="{{ $v->address }}"
                                                data-email="{{ $v->email ?? '' }}"
                                                data-ntn="{{ $v->ntn_number ?? '' }}"
                                                data-strn="{{ $v->strn_number ?? '' }}"
                                                data-cnic="{{ $v->cnic ?? '' }}"
                                                {{ $purchase->vendor_id == $v->id ? 'selected' : '' }}>
                                            {{ $v->name }} {{ $v->phone ? '(' . $v->phone . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" id="btnOpenAddVendorModal"
                                    class="btn btn-outline-primary"
                                    data-bs-toggle="modal" data-bs-target="#addVendorModal"
                                    data-toggle="modal" data-target="#addVendorModal"
                                    title="Quick Add Vendor">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" id="btnOpenVendorDetailsModal"
                                    class="btn btn-outline-info"
                                    data-bs-toggle="modal" data-bs-target="#vendorDetailsModal"
                                    data-toggle="modal" data-target="#vendorDetailsModal"
                                    title="View Vendor Details">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Warehouse Select --}}
                    @php
                        $user = auth()->user();
                        $canSelectWarehouse = $user && (
                            (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) ||
                            (method_exists($user, 'hasRole') && ($user->hasRole('Super Admin') || $user->hasRole('admin') || $user->hasRole('superadmin'))) ||
                            $user->can('warehouse.view')
                        );
                        $userWarehouseId = $user ? ($user->warehouse_id ?? 1) : 1;
                    @endphp
                    @if($canSelectWarehouse)
                    <div class="d-flex align-items-center gap-2">
                        <label class="field-label mb-0 text-nowrap fw-bold"><i class="fas fa-warehouse text-primary me-1"></i>Warehouse:</label>
                        <select class="form-select form-select-sm fw-bold border-primary shadow-sm" name="warehouse_id" id="warehouseSelect" style="min-width: 170px;">
                            @foreach ($Warehouse as $w)
                                <option value="{{ $w->id }}" {{ $w->id == $purchase->warehouse_id ? 'selected' : '' }}>
                                    {{ $w->warehouse_name ?? $w->name ?? ('Warehouse ' . $w->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @else
                        <input type="hidden" name="warehouse_id" id="warehouseSelect" value="{{ $purchase->warehouse_id ?? $userWarehouseId }}">
                    @endif

                    {{-- Previous Balance --}}
                    <div class="d-flex align-items-center gap-2 px-3 py-1 bg-light border rounded-2 ms-auto">
                        <span class="field-label mb-0 text-muted" style="font-size:11px;">Prev Balance:</span>
                        <span class="fw-bold text-danger fs-6" id="vbPrevBal">0.00</span>
                    </div>
                </div>
            </div>

            {{-- ============================ ITEMS SECTION (EXCEL GRID) ============================ --}}
            <div class="sale-card mb-2 p-2">
                <div class="d-flex justify-content-between align-items-center mb-1 px-1">
                    <div class="d-flex align-items-center gap-2">
                        <span class="items-count" id="itemsRowCount" title="Total Items">{{ $purchase->items->count() }}</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-success btn-sm py-0 px-2 fw-bold" data-bs-toggle="modal" data-bs-target="#quickAddProductModal" data-toggle="modal" data-target="#quickAddProductModal" title="Quick Add Product">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>

                <div class="pos-table-wrap">
                    <table class="sales-table" id="purchaseTable">
                        <thead>
                            <tr>
                                <th style="width: 36px;" class="text-center">#</th>
                                <th class="col-product">Product Description</th>
                                @if($canSelectWarehouse)
                                    <th style="width: 140px; min-width: 130px;">Warehouse</th>
                                @endif
                                <th style="width: 80px;">Unit</th>
                                <th style="width: 90px;">Qty</th>
                                <th style="width: 120px;">Price</th>
                                <th style="width: 90px;">Disc %</th>
                                <th style="width: 100px;">Disc Amt</th>
                                @if($purchaseGstEnabled ?? true)
                                    <th style="width: 95px;" class="col-purchase-gst text-center">GST %</th>
                                @endif
                                <th style="width: 120px;">Subtotal</th>
                                <th style="width: 36px;" class="text-center"><i class="fas fa-times text-danger"></i></th>
                            </tr>
                        </thead>
                        <tbody id="purchaseTableBody">
                            @foreach ($purchase->items as $item)
                                @php
                                    $sizeMode = $item->size_mode ?? 'by_pieces';
                                    $ppb = (float) ($item->pieces_per_box > 0 ? $item->pieces_per_box : 1);
                                    $displayQty = (float) $item->qty;
                                    $uVal = strtolower($item->unit ?? 'pcs');
                                    $isCtn = in_array($uVal, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl']) || in_array($sizeMode, ['by_cartons', 'by_bandal']);
                                    if ($isCtn && ($item->loose_qty > 0 || $item->boxes_qty > 0)) {
                                        $b = (int) $item->boxes_qty;
                                        $l = (int) $item->loose_qty;
                                        $displayQty = $l > 0 ? ($b . '.' . $l) : $b;
                                    }

                                    $baseProductName = $item->product->item_name ?? 'Product';
                                    $variantNameDisplay = $baseProductName;
                                    $rawVariantData = $item->color ?? '';
                                    $unitName = !empty($item->unit) ? $item->unit : ($item->product->unit->name ?? 'Pcs');

                                    if (!empty($item->color)) {
                                        $decodedColor = base64_decode($item->color, true);
                                        $vData = ($decodedColor !== false) ? json_decode($decodedColor, true) : null;
                                        if (!$vData) $vData = json_decode($item->color, true);
                                        if (is_array($vData)) {
                                            $vName = trim($vData['name'] ?? ($vData['variant_name'] ?? ''));
                                            if ($vName !== '') {
                                                $variantNameDisplay = (stripos($vName, $baseProductName) !== false) ? $vName : ($baseProductName . ' — ' . $vName);
                                            }
                                        }
                                    }

                                    $optionVal = $item->product_id;
                                    if (!empty($rawVariantData)) {
                                        $encodedVar = (base64_decode($rawVariantData, true) !== false) ? $rawVariantData : base64_encode($rawVariantData);
                                        $optionVal = $item->product_id . '|variant|' . $encodedVar;
                                    }

                                    $gross = $item->line_total + $item->item_discount;
                                    $dPct = $gross > 0 ? ($item->item_discount / $gross) * 100 : 0;
                                    $btnClass = $isCtn ? 'btn-outline-success' : (in_array($uVal, ['kg', 'gm']) ? 'btn-outline-primary' : 'btn-outline-info');
                                @endphp
                                <tr data-sizemode="{{ $sizeMode }}" data-pieces_per_m2="{{ $item->pieces_per_m2 }}">
                                    <td class="row-index-cell text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <select class="form-select product-select2" name="product_id[]">
                                            <option value="{{ $optionVal }}" selected>
                                                {{ $variantNameDisplay }} ({{ $item->product->item_code ?? 'SKU' }})
                                            </option>
                                        </select>
                                        {{-- Hidden Snapshots --}}
                                        <input type="hidden" name="size_mode[]" class="hidden-size-mode" value="{{ $sizeMode }}">
                                        <input type="hidden" name="pieces_per_box[]" class="hidden-pieces-per-box" value="{{ $ppb }}">
                                        <input type="hidden" name="pieces_per_m2[]" class="hidden-pieces-per-m2" value="{{ $item->pieces_per_m2 }}">
                                        <input type="hidden" name="price_per_carton[]" class="hidden-price-per-carton" value="{{ $item->price_per_carton ?? 0 }}">
                                        <input type="hidden" name="boxes_qty[]" class="hidden-boxes-qty" value="{{ $item->boxes_qty ?? 0 }}">
                                        <input type="hidden" name="loose_qty[]" class="hidden-loose-qty" value="{{ $item->loose_qty ?? 0 }}">
                                        <input type="hidden" name="length[]" class="hidden-length" value="{{ $item->length }}">
                                        <input type="hidden" name="width[]" class="hidden-width" value="{{ $item->width }}">
                                        <input type="hidden" name="color[]" class="hidden-variant-data" value="{{ $rawVariantData }}">
                                        <input type="hidden" class="product-id-hidden" value="{{ $item->product_id }}">
                                        <input type="hidden" class="batch-id-hidden" name="batch_id[]" value="{{ $item->batch_id ?? '' }}">
                                        <input type="hidden" class="batch-no-hidden" name="batch_no[]" value="{{ $item->batch_no ?? '' }}">
                                        <input type="hidden" class="mfg-date-hidden" name="mfg_date[]" value="{{ $item->mfg_date ?? '' }}">
                                        <input type="hidden" class="exp-date-hidden" name="exp_date[]" value="{{ $item->exp_date ?? '' }}">
                                        <input type="hidden" class="serials-hidden" name="serials[]" value="{{ isset($item->serials) ? (is_array($item->serials) ? json_encode($item->serials) : $item->serials) : '' }}">
                                        <input type="hidden" class="tracking-type-hidden" name="tracking_type[]" value="{{ $item->product->tracking_type ?? 'simple' }}">
                                        <div class="row-tracking-badges d-flex flex-wrap gap-1 px-1 py-0">
                                            @if(!empty($item->batch_no))
                                                <span class="badge bg-info text-dark tracking-badge" style="cursor:pointer; font-size:0.7rem;"><i class="fas fa-layer-group me-1"></i>Batch: {{ $item->batch_no }}</span>
                                            @endif
                                            @if(!empty($item->serials) && count((array)$item->serials) > 0)
                                                <span class="badge bg-secondary text-light tracking-badge" style="cursor:pointer; font-size:0.7rem;" title="{{ implode(', ', (array)$item->serials) }}"><i class="fas fa-barcode me-1"></i>{{ count((array)$item->serials) }} IMEI(s)</span>
                                            @endif
                                        </div>
                                    </td>
                                    @if($canSelectWarehouse)
                                    <td>
                                        <select name="warehouse_id[]" class="form-select form-select-sm row-warehouse-select fw-semibold border-secondary" style="height: 31px; font-size: 0.8rem;">
                                            @foreach ($Warehouse as $w)
                                                <option value="{{ $w->id }}" {{ ($item->warehouse_id ?: $purchase->warehouse_id) == $w->id ? 'selected' : '' }}>
                                                    {{ $w->warehouse_name ?? $w->name ?? ('Warehouse ' . $w->id) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    @else
                                        <input type="hidden" name="warehouse_id[]" class="row-warehouse-select" value="{{ $item->warehouse_id ?: ($purchase->warehouse_id ?? $userWarehouseId) }}">
                                    @endif
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-sm {{ $btnClass }} fw-bold unit-toggle-btn" data-unit="{{ $unitName }}" title="Click to toggle unit (Carton ↔ Pcs / Kg ↔ Gm)">{{ $unitName }}</button>
                                        <input type="hidden" name="unit[]" class="unit-input-val" value="{{ $unitName }}">
                                    </td>
                                    <td>
                                        <input type="number" step="any" min="0.0001" name="qty[]" class="form-control text-center main-qty-input fw-bold" value="{{ $displayQty }}" placeholder="Qty">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="price[]" class="form-control text-end price fw-semibold" value="{{ (float) $item->price }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="item_discount[]" class="form-control text-end item-disc-percent" value="{{ round($dPct, 2) }}">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control text-end input-readonly item-disc-amt" value="{{ number_format($item->item_discount, 2, '.', '') }}" readonly tabindex="-1">
                                    </td>
                                    @if($purchaseGstEnabled ?? true)
                                    <td class="col-purchase-gst">
                                        <input type="number" step="0.01" min="0" max="100" name="gst_percent[]" class="form-control text-end item-gst-percent fw-semibold" value="{{ (float)($item->gst_percent ?? ($defaultGstPercent ?? 18)) }}" placeholder="18">
                                        <input type="hidden" name="gst_amount[]" class="item-gst-amt" value="{{ number_format($item->gst_amount ?? 0, 2, '.', '') }}">
                                        <input type="hidden" name="exclusive_gst_amount[]" class="item-excl-gst-amt" value="{{ number_format($item->exclusive_gst_amount ?? 0, 2, '.', '') }}">
                                    </td>
                                    @else
                                    <input type="hidden" name="gst_percent[]" class="item-gst-percent" value="{{ (float)($item->gst_percent ?? 0) }}">
                                    <input type="hidden" name="gst_amount[]" class="item-gst-amt" value="{{ number_format($item->gst_amount ?? 0, 2, '.', '') }}">
                                    <input type="hidden" name="exclusive_gst_amount[]" class="item-excl-gst-amt" value="{{ number_format($item->exclusive_gst_amount ?? 0, 2, '.', '') }}">
                                    @endif
                                    <td>
                                        <input type="number" class="form-control text-end input-readonly row-total fw-bold" value="{{ number_format($item->line_total, 2, '.', '') }}" readonly tabindex="-1">
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="remove-row" title="Remove Item"><i class="fas fa-times"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="{{ $canSelectWarehouse ? (($purchaseGstEnabled ?? true) ? 9 : 8) : (($purchaseGstEnabled ?? true) ? 8 : 7) }}" class="text-end fw-bold text-muted py-2 pe-2" style="background:#F8FAFC;">Total Item Amount:</td>
                                <td class="text-end fw-bold fs-6 text-dark py-2 pe-2" style="background:#F8FAFC;"><span id="totalAmount">0.00</span></td>
                                <td style="background:#F8FAFC;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- ============================ PAYMENT & ORDER SUMMARY ============================ --}}
            <div class="row g-2 align-items-start">
                {{-- Left: Payment / Receipt Voucher & Notes --}}
                <div class="col-lg-7 col-md-12">
                    <div class="sale-card p-3 mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="card-title"><i class="fas fa-money-check-alt text-primary me-2"></i>Payment Accounts</span>
                            <div class="d-flex gap-1 align-items-center">
                                <button type="button" class="btn btn-outline-success btn-sm py-0 px-2 fw-semibold" id="btnPayFull" title="Pay full bill amount now">
                                    <i class="fas fa-check-circle me-1"></i> Pay Full
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 fw-semibold" id="btnPayLater" title="Pay later (Clear payments, 0 paid)">
                                    <i class="fas fa-clock me-1"></i> Pay Later
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 fw-bold" id="btnAddPayment">
                                    <i class="fas fa-plus me-1"></i> Add Account
                                </button>
                            </div>
                        </div>
                        <div id="paymentWrapper">
                            @if (isset($existingPayments) && $existingPayments->isNotEmpty())
                                @foreach ($existingPayments as $pIndex => $pDetail)
                                    <div class="d-flex gap-2 align-items-center mb-2 payment-row flex-wrap">
                                        <select class="form-select rv-account" name="payment_account_id[]" style="max-width: 320px; flex: 1;">
                                            <option value="" disabled>Select Account</option>
                                            @foreach ($accounts as $acc)
                                                <option value="{{ $acc->id }}" {{ $acc->id == $pDetail->account_id ? 'selected' : '' }}>
                                                    {{ $acc->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="number" class="form-control text-end payment-amount fw-bold" name="payment_amount[]" value="{{ (float) $pDetail->credit }}" placeholder="Amount" style="width:140px" step="0.01">
                                        @if ($loop->first)
                                            <button type="button" class="btn btn-outline-danger remove-payment" title="Remove" style="display:none;"><i class="fas fa-trash"></i></button>
                                        @else
                                            <button type="button" class="btn btn-outline-danger remove-payment" title="Remove"><i class="fas fa-trash"></i></button>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="d-flex gap-2 align-items-center mb-2 payment-row flex-wrap">
                                    <select class="form-select rv-account" name="payment_account_id[]" style="max-width: 320px; flex: 1;">
                                        <option value="" disabled>Select Account</option>
                                        @foreach ($accounts as $acc)
                                            <option value="{{ $acc->id }}" {{ (str_contains(strtolower($acc->title), 'cash') || $loop->first) ? 'selected' : '' }}>{{ $acc->title }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" class="form-control text-end payment-amount fw-bold" name="payment_amount[]" placeholder="0.00" value="0.00" style="width:140px" step="0.01">
                                    <button type="button" class="btn btn-outline-danger remove-payment" title="Remove" style="display:none;"><i class="fas fa-trash"></i></button>
                                </div>
                            @endif
                        </div>

                        <div class="mt-3">
                            <label class="field-label mb-1">Remarks / Note</label>
                            <input type="text" class="form-control" name="note" id="remarks" placeholder="Optional notes about this purchase..." value="{{ $purchase->note }}">
                        </div>
                    </div>
                </div>

                {{-- Right: Order Summary --}}
                <div class="col-lg-5 col-md-12">
                    <div class="sale-card p-3">
                        <div class="card-title mb-2 pb-1 border-bottom d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-receipt text-primary me-2"></i>Order Summary</span>
                            <span class="badge bg-light text-secondary border">Purchase #{{ $purchase->invoice_no }}</span>
                        </div>

                        <div class="s-row">
                            <span class="s-label">Total Gross</span>
                            <span class="s-val" id="tSub">0.00</span>
                            <input type="hidden" name="subtotal" id="subtotalInput" value="{{ $purchase->subtotal }}">
                        </div>

                        <div class="s-row">
                            <span class="s-label">Item Discounts</span>
                            <span class="s-val text-success" id="tLineDisc">0.00</span>
                        </div>

                        @if($purchaseGstEnabled ?? true)
                        <div class="s-row" id="rowSummaryExclGst">
                            <span class="s-label fw-bold text-dark">Exclusive GST Amount</span>
                            <span class="s-val fw-bold text-dark" id="tExclusiveGst">{{ number_format($purchase->total_exclusive_gst ?? 0, 2) }}</span>
                            <input type="hidden" name="total_exclusive_gst" id="exclusiveGstInput" value="{{ $purchase->total_exclusive_gst ?? 0 }}">
                        </div>
                        <div class="s-row" id="rowSummaryGstAmt">
                            <span class="s-label text-success fw-bold">GST Amount</span>
                            <span class="s-val text-success fw-bold" id="tGstAmount">{{ number_format($purchase->total_gst ?? 0, 2) }}</span>
                            <input type="hidden" name="total_gst" id="totalGstInput" value="{{ $purchase->total_gst ?? 0 }}">
                        </div>
                        @endif

                        <div class="s-row">
                            <span class="s-label">Bill Discount</span>
                            <div class="d-flex align-items-center gap-1 discount-input">
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" class="form-control text-end fw-bold" id="billDiscountPct" placeholder="0" style="max-width: 45px;">
                                    <span class="input-group-text">%</span>
                                </div>
                                <input type="number" step="0.01" class="form-control text-end fw-bold" id="billDiscount" value="{{ (float)$purchase->discount }}">
                                <input type="hidden" name="discount" id="discountInput" value="{{ (float)$purchase->discount }}">
                            </div>
                        </div>

                        <div class="s-row">
                            <span class="s-label">Extra Cost</span>
                            <input type="number" step="0.01" class="form-control form-control-sm text-end fw-bold" name="extra_cost" id="extraCost" value="{{ (float)$purchase->extra_cost }}" style="width: 120px;">
                        </div>

                        <div class="s-row net">
                            <span class="net-label">Net Payable</span>
                            <span class="net-val" id="tPayable">0.00</span>
                            <input type="hidden" name="net_amount" id="netAmountInput" value="{{ $purchase->net_amount }}">
                        </div>

                        <div class="s-row">
                            <span class="s-label text-muted">Previous Balance</span>
                            <span class="s-val text-danger" id="tPrev">0.00</span>
                        </div>

                        <div class="s-row">
                            <span class="s-label">Total Payable</span>
                            <span class="s-val fw-bold" id="tTotalPayable">0.00</span>
                        </div>

                        <div class="s-row">
                            <span class="s-label text-success">Total Paid</span>
                            <span class="paid-val" id="totalPaid">0.00</span>
                        </div>

                        <div class="s-row border-0 pt-1">
                            <span class="s-label fw-bold">Balance Due</span>
                            <span class="change-val text-danger fw-bold fs-6" id="tBalanceDue">0.00</span>
                        </div>

                        <div class="mt-3 pt-2 border-top text-end">
                            <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm" id="btnUpdatePurchase">
                                <i class="fas fa-save me-1"></i> Update Purchase
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Quick Add Vendor Modal -->
    <div class="modal fade" id="addVendorModal" tabindex="-1" aria-labelledby="addVendorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-bottom-0 py-2 px-3">
                    <h6 class="modal-title fw-bold text-white mb-0" id="addVendorModalLabel">
                        <i class="fas fa-truck me-2"></i>Quick Add Vendor
                    </h6>
                    <button type="button" class="close btn-close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="color:#ffffff !important; opacity:0.9;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="quickAddVendorForm">
                    @csrf
                    <div class="modal-body p-3">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required placeholder="Enter vendor name">
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-bold small text-muted">Phone Number</label>
                                <input type="text" class="form-control" name="phone" placeholder="0300-1234567">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-bold small text-muted">Email Address</label>
                                <input type="email" class="form-control" name="email" placeholder="vendor@example.com">
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-bold small text-muted">CNIC Number</label>
                                <input type="text" class="form-control" name="cnic" placeholder="Optional">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-bold small text-muted">Opening Balance</label>
                                <input type="number" step="0.01" class="form-control" name="opening_balance" value="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-bold small text-muted">NTN Number</label>
                                <input type="text" class="form-control" name="ntn_number" placeholder="Optional">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-bold small text-muted">STRN Number</label>
                                <input type="text" class="form-control" name="strn_number" placeholder="Optional">
                            </div>
                        </div>
                                                <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Province</label>
                                <select class="form-select form-control" name="province">
                                    <option value="">Optional</option>
                                    @foreach(app(\App\Services\LocationService::class)->getProvinces() as $p)
                                        <option value="{{ $p }}">{{ $p }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">City</label>
                                <div class="input-group">
                                    <select class="form-select form-control" name="city">
                                        <option value="">Optional</option>
                                        @foreach(app(\App\Services\LocationService::class)->getCities() as $c)
                                            <option value="{{ $c }}">{{ $c }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-quick-add-city px-2" title="Add New City">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Area</label>
                                <div class="input-group">
                                    <select class="form-select form-control" name="area">
                                        <option value="">Optional</option>
                                        @foreach(app(\App\Services\LocationService::class)->getAreas() as $a)
                                            <option value="{{ $a }}">{{ $a }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-quick-add-area px-2" title="Add New Area">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
<div class="mb-2">
                            <label class="form-label fw-bold small text-muted">Address</label>
                            <textarea class="form-control" name="address" rows="2" placeholder="Optional"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top p-2 px-3">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold" id="btnQuickSaveVendor">Save Vendor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Vendor Full Details -->
    <div class="modal fade" id="vendorDetailsModal" tabindex="-1" aria-labelledby="vendorDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header bg-light border-bottom py-2 px-3">
                    <h6 class="modal-title fw-bold text-dark mb-0" id="vendorDetailsModalLabel">
                        <i class="fas fa-info-circle text-primary me-2"></i>Vendor Details
                    </h6>
                    <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3 small">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <span class="cb-avatar"><i class="fas fa-truck text-white"></i></span>
                        <div>
                            <div class="fw-bold text-dark fs-6" id="modalVdName">No Vendor Selected</div>
                            <div class="text-muted" id="modalVdContact">—</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Phone:</span>
                        <strong id="modalVdPhone">—</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Email:</span>
                        <strong id="modalVdEmail">—</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">NTN:</span>
                        <strong id="modalVdNtn">—</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">STRN:</span>
                        <strong id="modalVdStrn">—</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">CNIC:</span>
                        <strong id="modalVdCnic">—</strong>
                    </div>
                    <div class="py-1 border-bottom">
                        <span class="text-muted d-block mb-1">Address:</span>
                        <div class="fw-semibold text-dark" id="modalVdAddress">—</div>
                    </div>
                    <div class="d-flex justify-content-between pt-2">
                        <span class="text-muted fw-bold">Previous Balance:</span>
                        <strong class="text-danger fs-6" id="modalVdPrevBal">0.00</strong>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top p-2 px-3 text-end">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Enter / Restock Batch Details for Purchase -->
    <div class="modal fade" id="modalSelectBatch" tabindex="-1" aria-labelledby="modalSelectBatchLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom bg-light px-3 py-2">
                    <h6 class="modal-title fw-bold text-dark mb-0" id="modalSelectBatchLabel">
                        <i class="fas fa-layer-group text-primary me-1"></i> Enter / Restock Batch Details
                    </h6>
                    <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <input type="hidden" id="activeBatchRowIndex" value="">
                    <div class="alert alert-light border py-1 px-2 mb-3 small d-flex align-items-center gap-2">
                        <i class="fas fa-box text-primary"></i>
                        <span id="modalBatchProductName" class="fw-bold text-dark">Selected Product</span>
                    </div>

                    <!-- Existing Batches Restock Dropdown -->
                    <div class="mb-3" id="existingBatchContainer" style="display:none;">
                        <label class="form-label fw-bold small text-muted">Restock Existing Batch (Optional)</label>
                        <select class="form-select form-select-sm" id="selectExistingBatch">
                            <option value="">-- Create New Batch Below --</option>
                        </select>
                        <div class="form-text" style="font-size:0.75rem;">Purana batch select karein ya neeche naya batch number darj karein.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Batch Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm font-monospace fw-bold" id="inputBatchNo" placeholder="e.g. BATCH-2026-001">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">MFG Date</label>
                            <input type="date" class="form-control form-control-sm" id="inputBatchMfgDate">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">Expiry Date</label>
                            <input type="date" class="form-control form-control-sm" id="inputBatchExpiryDate">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top p-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm fw-bold px-3" id="btnSaveBatchInfo">
                        <i class="fas fa-check me-1"></i> Save Batch Info
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Enter / Scan Serial & IMEI Numbers for Purchase -->
    <div class="modal fade" id="modalSelectSerial" tabindex="-1" aria-labelledby="modalSelectSerialLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom bg-light px-3 py-2">
                    <h6 class="modal-title fw-bold text-dark mb-0" id="modalSelectSerialLabel">
                        <i class="fas fa-barcode text-success me-1"></i> Add / Scan Serial &amp; IMEI Numbers
                    </h6>
                    <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <input type="hidden" id="activeSerialRowIndex" value="">
                    <div class="alert alert-light border py-1 px-2 mb-3 small d-flex align-items-center gap-2">
                        <i class="fas fa-box text-success"></i>
                        <span id="modalSerialProductName" class="fw-bold text-dark">Selected Product</span>
                    </div>
                    
                    <!-- Single Barcode Scan Input -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Scan / Type Single IMEI (Press Enter to Add)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light"><i class="fas fa-barcode"></i></span>
                            <input type="text" class="form-control font-monospace" id="inputSingleSerial" placeholder="Scan barcode or type IMEI number...">
                            <button type="button" class="btn btn-primary fw-bold" id="btnAddSingleSerial">Add IMEI</button>
                        </div>
                    </div>

                    <!-- Bulk Paste Input -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Or Paste Multiple IMEIs (One per line or comma separated)</label>
                        <textarea class="form-control font-monospace form-control-sm" id="inputBulkSerials" rows="3" placeholder="860000000000001&#10;860000000000002&#10;860000000000003"></textarea>
                        <div class="text-end mt-1">
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="btnApplyBulkSerials">
                                <i class="fas fa-plus-circle me-1"></i> Add Bulk IMEIs
                            </button>
                        </div>
                    </div>

                    <!-- List of Entered IMEIs -->
                    <div class="border rounded p-2 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                            <span class="fw-bold small text-secondary">Entered IMEIs List (<span id="purchaseSerialCount" class="text-success fs-6">0</span>)</span>
                            <button type="button" class="btn btn-link text-danger p-0 small text-decoration-none" id="btnClearAllSerials">Clear All</button>
                        </div>
                        <div id="enteredSerialsContainer" class="d-flex flex-wrap gap-1 overflow-auto p-1 bg-white border rounded" style="max-height: 180px; min-height: 80px;">
                            <div class="text-muted text-center w-100 py-3 small">No IMEIs added yet. Scan barcode or paste IMEIs above.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top p-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success btn-sm fw-bold px-3" id="btnApplySelectedSerials">
                        <i class="fas fa-check me-1"></i> Apply IMEIs to Purchase
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/select2/js/select2.min.js') }}"></script>

    {{-- Quick Add Product Modal --}}
    @include('admin_panel.partials.quick_add_product_modal')

    <script>
        $(document).ready(function() {
            // Init Select2 for static inputs
            $('.select2').select2({
                width: '100%',
                dropdownParent: $(document.body)
            });

            // Universal modal dismiss handler (ensures close & cancel buttons always work in Bootstrap 4 / 5)
            $(document).on('click', '[data-dismiss="modal"], [data-bs-dismiss="modal"], .modal .close, .modal .btn-close', function(e) {
                const $modal = $(this).closest('.modal');
                if ($modal.length) {
                    $modal.modal('hide');
                }
            });

            // Initialize existing row select2
            $('#purchaseTableBody tr').each(function() {
                initProductSelect2($(this).find('.product-select2'));
            });

            // Vendor Select Logic & Modal Population
            $('#vendorSelect').on('change', function() {
                const vendorId = $(this).val();
                if (!vendorId) {
                    $('#vbPrevBal').text('0.00');
                    $('#modalVdName').text('No Vendor Selected');
                    $('#modalVdContact').text('—');
                    $('#modalVdPhone').text('—');
                    $('#modalVdEmail').text('—');
                    $('#modalVdNtn').text('—');
                    $('#modalVdStrn').text('—');
                    $('#modalVdCnic').text('—');
                    $('#modalVdAddress').text('—');
                    $('#modalVdPrevBal').text('0.00');
                    $('#tPrev').text('0.00');
                    recalcAll();
                    return;
                }

                const $selectedOpt = $(this).find(':selected');
                const name = $selectedOpt.text().split('(')[0].trim();
                const phone = $selectedOpt.data('phone') || '—';
                const address = $selectedOpt.data('address') || '—';
                const email = $selectedOpt.data('email') || '—';
                const ntn = $selectedOpt.data('ntn') || '—';
                const strn = $selectedOpt.data('strn') || '—';
                const cnic = $selectedOpt.data('cnic') || '—';

                $('#modalVdName').text(name);
                $('#modalVdContact').text(phone);
                $('#modalVdPhone').text(phone);
                $('#modalVdEmail').text(email);
                $('#modalVdNtn').text(ntn);
                $('#modalVdStrn').text(strn);
                $('#modalVdCnic').text(cnic);
                $('#modalVdAddress').text(address);

                // Fetch Vendor Info & Ledger
                $.get(`/vendor/${vendorId}/ledger-json`, function(data) {
                    const prevBal = parseFloat(data.current_balance) || 0;
                    $('#vbPrevBal').text(prevBal.toFixed(2));
                    $('#modalVdPrevBal').text(prevBal.toFixed(2));
                    $('#tPrev').text(prevBal.toFixed(2));
                    if (data.vendor) {
                        if (data.vendor.ntn_number) $('#modalVdNtn').text(data.vendor.ntn_number);
                        if (data.vendor.strn_number) $('#modalVdStrn').text(data.vendor.strn_number);
                        if (data.vendor.cnic) $('#modalVdCnic').text(data.vendor.cnic);
                        if (data.vendor.email) $('#modalVdEmail').text(data.vendor.email);
                        if (data.vendor.address) $('#modalVdAddress').text(data.vendor.address);
                    }
                    recalcAll();
                }).fail(function() {
                    $('#vbPrevBal').text('0.00');
                    $('#modalVdPrevBal').text('0.00');
                    $('#tPrev').text('0.00');
                    recalcAll();
                });
            });

            // Trigger vendor change on load to fetch previous balance
            if ($('#vendorSelect').val()) {
                $('#vendorSelect').trigger('change');
            }

            // Quick Add Vendor Submission via AJAX
            $('#quickAddVendorForm').on('submit', function(e) {
                e.preventDefault();
                let $btn = $('#btnQuickSaveVendor');
                let originalText = $btn.text();
                $btn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: "{{ route('vendors.store.ajax') }}",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        $btn.prop('disabled', false).text(originalText);
                        $('#addVendorModal').modal('hide');
                        $('#quickAddVendorForm')[0].reset();

                        Swal.fire({
                            icon: 'success',
                            title: 'Vendor Added!',
                            text: response.message || 'Vendor created successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            let vendor = response.vendor || response.data || response;
                            let vendorId = vendor.id;
                            let vendorName = vendor.name + (vendor.phone ? ' (' + vendor.phone + ')' : '');

                            if (vendorId) {
                                let newOption = new Option(vendorName, vendorId, false, true);
                                $(newOption).attr('data-phone', vendor.phone || '')
                                            .attr('data-address', vendor.address || '')
                                            .attr('data-email', vendor.email || '')
                                            .attr('data-ntn', vendor.ntn_number || '')
                                            .attr('data-strn', vendor.strn_number || '')
                                            .attr('data-cnic', vendor.cnic || '');
                                $('#vendorSelect').append(newOption).trigger('change');
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
                const canSelectWh = {{ $canSelectWarehouse ? 'true' : 'false' }};
                const userDefaultWh = {{ $userWarehouseId ?? 1 }};
                const curHeaderWh = $('#warehouseSelect').val() || userDefaultWh;
                let whCellHtml = `<input type="hidden" name="warehouse_id[]" class="row-warehouse-select" value="${userDefaultWh}">`;

                if (canSelectWh) {
                    let whOptionsHtml = '';
                    @if(isset($Warehouse) && count($Warehouse) > 0)
                        @foreach($Warehouse as $w)
                            whOptionsHtml += `<option value="{{ $w->id }}" ${curHeaderWh == '{{ $w->id }}' ? 'selected' : ''}>{{ addslashes($w->warehouse_name ?? $w->name ?? ('Warehouse ' . $w->id)) }}</option>`;
                        @endforeach
                    @endif
                    whCellHtml = `<td>
                        <select name="warehouse_id[]" class="form-select form-select-sm row-warehouse-select fw-semibold border-secondary" style="height: 31px; font-size: 0.8rem;">
                            ${whOptionsHtml}
                        </select>
                    </td>`;
                }

                const html = `
                <tr>
                    <td class="row-index-cell text-center">1</td>
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
                        <input type="hidden" class="product-id-hidden" value="">
                        <input type="hidden" class="batch-id-hidden" name="batch_id[]" value="">
                        <input type="hidden" class="batch-no-hidden" name="batch_no[]" value="">
                        <input type="hidden" class="mfg-date-hidden" name="mfg_date[]" value="">
                        <input type="hidden" class="exp-date-hidden" name="exp_date[]" value="">
                        <input type="hidden" class="serials-hidden" name="serials[]" value="">
                        <input type="hidden" class="tracking-type-hidden" name="tracking_type[]" value="">
                        <div class="row-tracking-badges d-flex flex-wrap gap-1 px-1 py-0"></div>
                    </td>
                    ${whCellHtml}
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-outline-info fw-bold unit-toggle-btn" data-unit="Pcs" title="Click to toggle unit (Carton ↔ Pcs / Kg ↔ Gm)">Pcs</button>
                        <input type="hidden" name="unit[]" class="unit-input-val" value="Pcs">
                    </td>
                    <td>
                        <input type="number" step="any" min="0.01" name="qty[]" class="form-control text-center main-qty-input fw-bold" value="1" placeholder="Qty">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="price[]" class="form-control text-end price fw-semibold" value="0">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="item_discount[]" class="form-control text-end item-disc-percent" value="0">
                    </td>
                    <td>
                        <input type="number" class="form-control text-end input-readonly item-disc-amt" value="0.00" readonly tabindex="-1">
                    </td>
                    @if($purchaseGstEnabled ?? true)
                    <td class="col-purchase-gst">
                        <input type="number" step="0.01" min="0" max="100" name="gst_percent[]" class="form-control text-end item-gst-percent fw-semibold" value="{{ (float)($defaultGstPercent ?? 18) }}" placeholder="18">
                        <input type="hidden" name="gst_amount[]" class="item-gst-amt" value="0.00">
                        <input type="hidden" name="exclusive_gst_amount[]" class="item-excl-gst-amt" value="0.00">
                    </td>
                    @else
                    <input type="hidden" name="gst_percent[]" class="item-gst-percent" value="0">
                    <input type="hidden" name="gst_amount[]" class="item-gst-amt" value="0.00">
                    <input type="hidden" name="exclusive_gst_amount[]" class="item-excl-gst-amt" value="0.00">
                    @endif
                    <td>
                        <input type="number" class="form-control text-end input-readonly row-total fw-bold" value="0.00" readonly tabindex="-1">
                    </td>
                    <td class="text-center align-middle">
                        <button type="button" class="remove-row" title="Remove Item"><i class="fas fa-times"></i></button>
                    </td>
                </tr>`;

                const $newRow = $(html);
                $('#purchaseTableBody').append($newRow);
                initProductSelect2($newRow.find('.product-select2'));
                updateRowIndexes();
                return $newRow;
            }
            window.addBlankRow = addBlankRow;

            function initProductSelect2($el) {
                $el.select2({
                    placeholder: 'Type Product Name or Code...',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(document.body),
                    ajax: {
                        url: "{{ route('products.ajax.search') }}",
                        dataType: 'json',
                        delay: 200,
                        data: function(params) {
                            return {
                                q: params.term || '',
                                page: params.page || 1
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            const items = data.results || (Array.isArray(data) ? data : []);
                            return {
                                results: items,
                                pagination: data.pagination || false
                            };
                        },
                        cache: true
                    },
                    templateResult: formatProduct,
                    templateSelection: formatProductSelection
                });

                $el.on('select2:open', function() {
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
                    
                    let pid = data.id;
                    if (typeof pid === 'string' && pid.includes('|variant|')) {
                        pid = pid.split('|variant|')[0];
                    }
                    $row.find('.product-id-hidden').val(pid);

                    let unitName = data.unit_name || 'Pcs';
                    const ppb = parseFloat(data.pieces_per_box) || parseFloat(data.ppb) || 1;
                    const isCartonMode = (['by_cartons', 'by_bandal'].includes(data.size_mode) || unitName.toLowerCase() === 'carton' || unitName.toLowerCase() === 'ctn' || ppb > 1);

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

                    recalcRow($row);
                    recalcAll();

                    // Check and open Batch / Serial tracking modal if applicable, or focus Qty directly
                    checkAndOpenProductTracking($row, pid);
                });
            }

            function formatProduct(repo) {
                if (repo.loading) return repo.text;
                let name = repo.name || repo.text || '';
                let sku = repo.sku || 'N/A';
                let unit = repo.unit_name || 'Pcs';
                let stockVal = parseFloat(repo.stock_pieces !== undefined ? repo.stock_pieces : repo.stock) || 0;

                return $(`
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <div>
                            <div class="fw-bold text-dark" style="font-size:0.86rem;">${name}</div>
                            <div class="text-muted" style="font-size:0.75rem;">SKU: <span class="font-monospace">${sku}</span> | Unit: ${unit}</div>
                        </div>
                        <div class="text-end">
                            <span class="badge ${stockVal > 0 ? 'bg-success' : 'bg-secondary'} rounded-pill" style="font-size:0.72rem;">Stock: ${repo.stock || stockVal}</span>
                        </div>
                    </div>
                `);
            }

            function formatProductSelection(repo) {
                return repo.text || repo.name || 'Select Product...';
            }

            function updateRowIndexes() {
                $('#purchaseTableBody tr').each(function(index) {
                    $(this).find('.row-index-cell').text(index + 1);
                });
                $('#itemsRowCount').text($('#purchaseTableBody tr').length);
            }

            // Remove Row
            $('#warehouseSelect').on('change', function() {
                const newWh = $(this).val();
                $('.row-warehouse-select').val(newWh);
            });

            $(document).on('click', '.remove-row', function() {
                if ($('#purchaseTableBody tr').length <= 1) {
                    addBlankRow();
                }
                $(this).closest('tr').remove();
                updateRowIndexes();
                recalcAll();
            });

            // Unit Toggle Button
            $(document).on('click', '.unit-toggle-btn', function() {
                const $btn = $(this);
                const $row = $btn.closest('tr');
                const sizeMode = $row.data('sizemode') || $row.find('.hidden-size-mode').val();
                let currentUnit = $btn.attr('data-unit') || 'Pcs';
                const packQty = parseFloat($row.find('.hidden-pieces-per-box').val()) || parseFloat($row.data('pieces_per_box')) || 1;
                const $priceInp = $row.find('.price');
                const curPrice = parseFloat($priceInp.val()) || 0;

                const isCartonMode = (['by_cartons', 'by_bandal'].includes(sizeMode) || packQty > 1);

                if (isCartonMode) {
                    if (['carton', 'ctn', 'bundal', 'box'].includes(currentUnit.toLowerCase())) {
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

            // Grid Inputs -> Recalculate
            $('#purchaseTableBody').on('input', '.main-qty-input, .price, .item-disc-percent, .item-gst-percent', function() {
                recalcRow($(this).closest('tr'));
                recalcAll();
            });

            $('#billDiscount, #billDiscountPct, #extraCost').on('input', function() {
                recalcAll();
            });

            // ==================== EXCEL KEYBOARD NAVIGATION (ENTER -> NEXT CELL / NEXT ROW) ====================
            $(document).on('keydown', '.sales-table input', function(e) {
                if (e.which === 13) { // ENTER KEY
                    e.preventDefault();
                    const $input = $(this);
                    const $row = $input.closest('tr');

                    if ($input.hasClass('main-qty-input')) {
                        // Qty -> Price
                        $row.find('.price').focus().select();
                    } else if ($input.hasClass('price')) {
                        // Price -> Disc %
                        $row.find('.item-disc-percent').focus().select();
                    } else if ($input.hasClass('item-disc-percent')) {
                        if ($row.find('.item-gst-percent').length) {
                            $row.find('.item-gst-percent').focus().select();
                        } else {
                            const $nextRow = $row.next('tr');
                            if ($nextRow.length) {
                                $nextRow.find('.product-select2').select2('open');
                            } else {
                                const $newRow = addBlankRow();
                                setTimeout(() => { $newRow.find('.product-select2').select2('open'); }, 50);
                            }
                        }
                    } else if ($input.hasClass('item-gst-percent')) {
                        // GST % -> Next Row's Product or Create New Row
                        const $nextRow = $row.next('tr');
                        if ($nextRow.length) {
                            $nextRow.find('.product-select2').select2('open');
                        } else {
                            const $newRow = addBlankRow();
                            setTimeout(() => {
                                $newRow.find('.product-select2').select2('open');
                            }, 50);
                        }
                    }
                }
            });

            function recalcRow($row) {
                const qtyStr = $row.find('.main-qty-input').val() || '0';
                const qty = parseFloat(qtyStr) || 0;
                const price = parseFloat($row.find('.price').val()) || 0;
                const discPct = parseFloat($row.find('.item-disc-percent').val()) || 0;

                const unitVal = ($row.find('.unit-input-val').val() || '').toLowerCase();
                const ppb = parseFloat($row.find('.hidden-pieces-per-box').val()) || 1;
                const pieces_per_m2 = parseFloat($row.find('.hidden-pieces-per-m2').val()) || 0;
                const sizeMode = $row.find('.hidden-size-mode').val();

                let gross = 0;
                const isPiece = (unitVal === 'pcs' || unitVal === 'piece' || unitVal === 'pieces');
                const isCarton = (unitVal === 'carton' || unitVal === 'ctn' || unitVal === 'box' || (!isPiece && ['by_cartons', 'by_bandal'].includes(sizeMode)));

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
                const exclGst = Math.max(0, gross - discAmt);
                const gstPct = parseFloat($row.find('.item-gst-percent').val()) || 0;
                const gstAmt = exclGst * (gstPct / 100);
                const lineTotal = exclGst + gstAmt;

                $row.find('.item-disc-amt').val(discAmt.toFixed(2));
                $row.find('.item-excl-gst-amt').val(exclGst.toFixed(2));
                $row.find('.item-gst-amt').val(gstAmt.toFixed(2));
                $row.find('.row-total').val(lineTotal.toFixed(2));
            }

            function recalcAll() {
                let subtotal = 0;
                let totalInlineDiscount = 0;
                let totalExclusiveGst = 0;
                let totalGstAmount = 0;

                $('#purchaseTableBody tr').each(function() {
                    const total = parseFloat($(this).find('.row-total').val()) || 0;
                    const rowDiscAmt = parseFloat($(this).find('.item-disc-amt').val()) || 0;
                    const rowExclGst = parseFloat($(this).find('.item-excl-gst-amt').val()) || 0;
                    const rowGstAmt = parseFloat($(this).find('.item-gst-amt').val()) || 0;

                    subtotal += total;
                    totalInlineDiscount += rowDiscAmt;
                    totalExclusiveGst += rowExclGst;
                    totalGstAmount += rowGstAmt;
                });

                const grossSubtotal = totalExclusiveGst + totalInlineDiscount;

                $('#tSub').text(grossSubtotal.toFixed(2));
                $('#subtotalInput').val(subtotal.toFixed(2));
                $('#tLineDisc').text(totalInlineDiscount.toFixed(2));
                $('#tExclusiveGst').text(totalExclusiveGst.toFixed(2));
                $('#exclusiveGstInput').val(totalExclusiveGst.toFixed(2));
                $('#tGstAmount').text(totalGstAmount.toFixed(2));
                $('#totalGstInput').val(totalGstAmount.toFixed(2));

                let additionalDiscount = parseFloat($('#discountInput').val()) || 0;
                let billDiscVal = parseFloat($('#billDiscount').val());

                if ($(document.activeElement).is('#billDiscount')) {
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
                const net = Math.max(0, subtotal - additionalDiscount + extraCost);

                $('#tPayable').text(net.toFixed(2));
                $('#netAmountInput').val(net.toFixed(2));
                $('#totalAmount').text(subtotal.toFixed(2));

                const prevBal = parseFloat($('#tPrev').text()) || 0;
                const totalPaid = parseFloat($('#totalPaid').text()) || 0;
                const totalPayable = prevBal + net;
                const balanceDue = totalPayable - totalPaid;
                
                $('#tTotalPayable').text(totalPayable.toFixed(2));
                $('#tBalanceDue').text(balanceDue.toFixed(2));
            }

            // Payment rows logic
            $('#btnAddPayment').on('click', function() {
                const row = `
                <div class="d-flex gap-2 align-items-center mb-2 payment-row flex-wrap">
                    <select class="form-select rv-account" name="payment_account_id[]" style="max-width: 320px; flex: 1;">
                        <option value="" selected disabled>Select Account</option>
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                        @endforeach
                    </select>
                    <input type="number" class="form-control text-end payment-amount fw-bold" name="payment_amount[]" placeholder="Amount" style="width:140px" step="0.01">
                    <button type="button" class="btn btn-outline-danger remove-payment" title="Remove"><i class="fas fa-trash"></i></button>
                </div>`;
                $('#paymentWrapper').append(row);
            });

            $(document).on('click', '.remove-payment', function() {
                $(this).closest('.payment-row').remove();
                recalcPayments();
            });

            $(document).on('input', '.payment-amount', function() {
                recalcPayments();
            });

            function recalcPayments() {
                let total = 0;
                $('.payment-amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#totalPaid').text(total.toFixed(2));
                recalcAll();
            }

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

            // Quick Pay Full & Pay Later actions
            $('#btnPayFull').on('click', function() {
                const net = parseFloat($('#netAmountInput').val()) || 0;
                const $firstRow = $('#paymentWrapper .payment-row').first();
                if ($firstRow.length) {
                    $firstRow.find('.payment-amount').val(net.toFixed(2));
                    // Remove extra payment rows if any
                    $('#paymentWrapper .payment-row').not(':first').remove();
                    // Select first available account if empty
                    const $acc = $firstRow.find('.rv-account');
                    if (!$acc.val()) {
                        $acc.find('option:not([disabled])').first().prop('selected', true);
                    }
                }
                recalcPayments();
            });

            $('#btnPayLater').on('click', function() {
                $('.payment-amount').val('0.00');
                recalcPayments();
            });

            $('#purchaseForm').on('submit', function(e) {
                normalizeDiscountInput();

                let hasPaymentError = false;
                let totalPaid = 0;
                const netAmount = parseFloat($('#netAmountInput').val()) || 0;

                $('.payment-row').each(function(index) {
                    const $row = $(this);
                    const $acc = $row.find('.rv-account');
                    const $amt = $row.find('.payment-amount');
                    const accVal = $acc.val();
                    const amtVal = parseFloat($amt.val() || '0') || 0;

                    if (amtVal < 0) {
                        hasPaymentError = true;
                        $amt.addClass('is-invalid border-danger').focus();
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Invalid Payment Amount',
                                text: 'Payment amount cannot be negative in payment row #' + (index + 1) + '.'
                            });
                        } else {
                            alert('Payment amount cannot be negative in payment row #' + (index + 1) + '.');
                        }
                        return false;
                    }

                    if (amtVal > 0) {
                        if (!accVal || accVal === '') {
                            hasPaymentError = true;
                            $acc.addClass('is-invalid border-danger').focus();
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Payment Account Missing',
                                    text: 'When paying now, please select a valid payment account for payment row #' + (index + 1) + '.'
                                });
                            } else {
                                alert('When paying now, please select a valid payment account for payment row #' + (index + 1) + '.');
                            }
                            return false;
                        } else {
                            $acc.removeClass('is-invalid border-danger');
                            $amt.removeClass('is-invalid border-danger');
                        }
                        totalPaid += amtVal;
                    } else {
                        $acc.removeClass('is-invalid border-danger');
                        $amt.removeClass('is-invalid border-danger');
                    }
                });

                if (hasPaymentError) {
                    e.preventDefault();
                    return false;
                }

                if (totalPaid > netAmount && netAmount > 0) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Overpayment Not Allowed',
                            text: 'Total paid amount (Rs ' + totalPaid.toFixed(2) + ') cannot exceed Net Bill Amount (Rs ' + netAmount.toFixed(2) + ').'
                        });
                    } else {
                        alert('Total paid amount cannot exceed Net Bill Amount.');
                    }
                    e.preventDefault();
                    return false;
                }
            });

            // --- Product Tracking for Purchase (Exact Persistence: Simple / Batch / Serial) ---
            window.checkAndOpenProductTracking = function($row, productId, warehouseId) {
                if (!productId) return;
                warehouseId = warehouseId || $row.find('.row-warehouse-select').val() || $('#warehouseSelect').val() || 1;

                const url = '{{ route("product.tracking_info", ["id" => "__ID__"]) }}'.replace('__ID__', productId);
                $.get(url, { warehouse_id: warehouseId }).done(function(res) {
                    if (!res.success) return;

                    const trackingType = res.tracking_type || 'simple';
                    $row.find('.tracking-type-hidden').val(trackingType);

                    if (trackingType === 'batch') {
                        openBatchSelectModal($row, res.all_batches || [], res.product_name);
                    } else if (trackingType === 'serial') {
                        openSerialSelectModal($row, res.product_name);
                    } else {
                        // Simple Product or Unlocked Product
                        let trackingHtml = '<span class="badge bg-light text-muted border tracking-badge me-1" style="font-size:0.68rem;"><i class="fas fa-box me-1"></i>Simple</span>';
                        if (!res.has_transactions) {
                            trackingHtml += `<button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1 btn-make-serial" style="font-size:10px;" title="Track this product by Serial / IMEI"><i class="fas fa-barcode"></i> Serial</button> `;
                            trackingHtml += `<button type="button" class="btn btn-outline-info btn-xs py-0 px-1 btn-make-batch" style="font-size:10px;" title="Track this product by Batch"><i class="fas fa-layer-group"></i> Batch</button>`;
                        }
                        $row.find('.row-tracking-badges').html(trackingHtml);
                        $row.find('.main-qty-input').focus().select();
                    }
                });
            };

            // Switch to Serial or Batch directly from row
            $(document).on('click', '.btn-make-serial', function(e) {
                e.preventDefault();
                const $row = $(this).closest('tr');
                const pName = $row.find('.product-select2 option:selected').text().split('(')[0].trim() || 'Selected Product';
                $row.find('.tracking-type-hidden').val('serial');
                openSerialSelectModal($row, pName);
            });

            $(document).on('click', '.btn-make-batch', function(e) {
                e.preventDefault();
                const $row = $(this).closest('tr');
                const pName = $row.find('.product-select2 option:selected').text().split('(')[0].trim() || 'Selected Product';
                $row.find('.tracking-type-hidden').val('batch');
                openBatchSelectModal($row, [], pName);
            });

            // Open Purchase Batch Modal
            function openBatchSelectModal($row, allBatches, productName) {
                const rowIndex = $('#purchaseTableBody tr').index($row);
                $('#activeBatchRowIndex').val(rowIndex);

                if (productName) {
                    $('#modalBatchProductName').text(productName);
                }

                // Populate Existing Batches Restock Dropdown
                const $select = $('#selectExistingBatch');
                $select.empty().append('<option value="">-- Create New Batch Below --</option>');
                
                if (allBatches && allBatches.length > 0) {
                    $('#existingBatchContainer').show();
                    allBatches.forEach(b => {
                        const optText = b.batch_no + ' (Exp: ' + (b.expiry_date || 'N/A') + ', Qty: ' + b.qty + ')';
                        const $opt = $('<option></option>')
                            .val(b.batch_no)
                            .text(optText)
                            .attr('data-mfg', b.mfg_date || '')
                            .attr('data-exp', b.expiry_date || '');
                        $select.append($opt);
                    });
                } else {
                    $('#existingBatchContainer').hide();
                }

                // Pre-fill existing values from row if any
                const existingBatchNo = $row.find('.batch-no-hidden').val() || '';
                const existingMfg = $row.find('.mfg-date-hidden').val() || '';
                const existingExp = $row.find('.exp-date-hidden').val() || '';

                $('#inputBatchNo').val(existingBatchNo);
                $('#inputBatchMfgDate').val(existingMfg);
                $('#inputBatchExpiryDate').val(existingExp);

                if (existingBatchNo && $select.find(`option[value="${existingBatchNo}"]`).length) {
                    $select.val(existingBatchNo);
                } else {
                    $select.val('');
                }

                $('#modalSelectBatch').modal('show');
                setTimeout(() => $('#inputBatchNo').focus(), 300);
            }

            // When user picks an existing batch from dropdown
            $(document).on('change', '#selectExistingBatch', function() {
                const bVal = $(this).val();
                if (bVal) {
                    const $opt = $(this).find(':selected');
                    $('#inputBatchNo').val(bVal);
                    $('#inputBatchMfgDate').val($opt.attr('data-mfg') || '');
                    $('#inputBatchExpiryDate').val($opt.attr('data-exp') || '');
                }
            });

            // Save Batch Info Button
            $(document).on('click', '#btnSaveBatchInfo', function() {
                const batchNo = $('#inputBatchNo').val().trim();
                const mfgDate = $('#inputBatchMfgDate').val();
                const expDate = $('#inputBatchExpiryDate').val();
                const rowIndex = $('#activeBatchRowIndex').val();
                const $row = $('#purchaseTableBody tr').eq(rowIndex);

                if (!batchNo) {
                    Swal.fire('Required', 'Please enter a Batch Number.', 'warning');
                    return;
                }

                if ($row.length) {
                    $row.find('.batch-no-hidden').val(batchNo);
                    $row.find('.mfg-date-hidden').val(mfgDate);
                    $row.find('.exp-date-hidden').val(expDate);

                    // Update tracking badges display on row
                    let expText = expDate ? ` (Exp: ${expDate})` : '';
                    let badgeHtml = `<span class="badge bg-info text-dark tracking-badge" style="cursor:pointer; font-size:0.72rem;" title="Click to edit batch"><i class="fas fa-layer-group me-1"></i>Batch: ${batchNo}${expText}</span>`;
                    $row.find('.row-tracking-badges').html(badgeHtml);

                    $('#modalSelectBatch').modal('hide');
                    $row.find('.main-qty-input').focus().select();
                }
            });

            // Open Purchase Serial / IMEI Modal
            let currentPurchaseSerials = [];
            function openSerialSelectModal($row, productName) {
                const rowIndex = $('#purchaseTableBody tr').index($row);
                $('#activeSerialRowIndex').val(rowIndex);

                if (productName) {
                    $('#modalSerialProductName').text(productName);
                }

                // Pre-selected/entered serials if any
                currentPurchaseSerials = [];
                try {
                    const raw = $row.find('.serials-hidden').val();
                    if (raw) {
                        currentPurchaseSerials = Array.isArray(raw) ? raw : JSON.parse(raw);
                    }
                } catch(e) {}

                renderEnteredSerialsList();
                $('#modalSelectSerial').modal('show');
                setTimeout(() => $('#inputSingleSerial').focus(), 300);
            }

            function renderEnteredSerialsList() {
                let html = '';
                if (!currentPurchaseSerials || currentPurchaseSerials.length === 0) {
                    html = '<div class="text-muted text-center w-100 py-3 small">No IMEIs added yet. Scan barcode or paste IMEIs above.</div>';
                } else {
                    currentPurchaseSerials.forEach((sNum, idx) => {
                        html += `
                            <span class="badge bg-light text-dark border p-2 d-flex align-items-center gap-1 font-monospace" style="font-size:0.82rem;">
                                <i class="fas fa-barcode text-success"></i> ${sNum}
                                <i class="fas fa-times text-danger remove-serial-item ms-1" data-index="${idx}" style="cursor:pointer;" title="Remove IMEI"></i>
                            </span>`;
                    });
                }
                $('#enteredSerialsContainer').html(html);
                $('#purchaseSerialCount').text(currentPurchaseSerials.length);
            }

            // Single Serial Add / Scan (on Enter or Button click)
            function addSingleSerialFromInput() {
                const sNum = $('#inputSingleSerial').val().trim();
                if (!sNum) return;

                if (currentPurchaseSerials.includes(sNum)) {
                    Swal.fire('Duplicate', 'This IMEI / Serial number is already added.', 'warning');
                    $('#inputSingleSerial').val('').focus();
                    return;
                }

                currentPurchaseSerials.push(sNum);
                renderEnteredSerialsList();
                $('#inputSingleSerial').val('').focus();
            }

            $(document).on('keydown keypress', '#inputSingleSerial', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    e.stopPropagation();
                    addSingleSerialFromInput();
                }
            });

            $(document).on('click', '#btnAddSingleSerial', function() {
                addSingleSerialFromInput();
            });

            // Bulk Serial Add
            $(document).on('click', '#btnApplyBulkSerials', function() {
                const text = $('#inputBulkSerials').val();
                if (!text || !text.trim()) return;

                // Split by newline or comma
                const items = text.split(/[\n,]+/).map(s => s.trim()).filter(s => s.length > 0);

                items.forEach(sNum => {
                    if (!currentPurchaseSerials.includes(sNum)) {
                        currentPurchaseSerials.push(sNum);
                    }
                });

                renderEnteredSerialsList();
                $('#inputBulkSerials').val('');
            });

            // Remove Single Serial Badge
            $(document).on('click', '.remove-serial-item', function() {
                const idx = $(this).data('index');
                if (idx !== undefined) {
                    currentPurchaseSerials.splice(idx, 1);
                    renderEnteredSerialsList();
                }
            });

            // Clear All Serials
            $(document).on('click', '#btnClearAllSerials', function() {
                currentPurchaseSerials = [];
                renderEnteredSerialsList();
            });

            // Apply Selected Serials Button
            $(document).on('click', '#btnApplySelectedSerials', function() {
                const rowIndex = parseInt($('#activeSerialRowIndex').val(), 10);
                const $row = $('#purchaseTableBody tr').eq(rowIndex);

                if ($row.length) {
                    $row.find('.serials-hidden').val(JSON.stringify(currentPurchaseSerials));
                    $row.find('.tracking-type-hidden').val('serial');

                    // Auto update qty in row based on entered IMEIs count
                    if (currentPurchaseSerials.length > 0) {
                        $row.find('.main-qty-input').val(currentPurchaseSerials.length);
                        recalcRow($row);
                        recalcAll();

                        let badgeHtml = `<span class="badge bg-secondary text-light tracking-badge" style="cursor:pointer; font-size:0.72rem;" title="${currentPurchaseSerials.join(', ')}"><i class="fas fa-barcode me-1"></i>${currentPurchaseSerials.length} IMEI(s)</span>`;
                        $row.find('.row-tracking-badges').html(badgeHtml);
                    } else {
                        $row.find('.row-tracking-badges').html('<span class="badge bg-light text-muted border tracking-badge" style="cursor:pointer; font-size:0.68rem;" title="Click to add IMEIs"><i class="fas fa-barcode me-1"></i>Add Serial</span>');
                    }

                    $('#modalSelectSerial').modal('hide');
                    $row.find('.price').focus().select();
                }
            });

            // Re-open Batch or Serial Modal on clicking tracking badge
            $(document).on('click', '.tracking-badge', function() {
                const $row = $(this).closest('tr');
                const tType = $row.find('.tracking-type-hidden').val();
                const pName = $row.find('.product-select2 option:selected').text().split('(')[0].trim() || 'Selected Product';
                if (tType === 'serial') {
                    openSerialSelectModal($row, pName);
                } else if (tType === 'batch') {
                    const pid = $row.find('.product-id-hidden').val();
                    checkAndOpenProductTracking($row, pid);
                }
            });

            // Initial Calculations on page load
            recalcPayments();
            recalcAll();
        });
    </script>
@include('admin_panel.partials.location_selector_script')
@endsection
