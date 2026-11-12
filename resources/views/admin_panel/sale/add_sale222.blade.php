@extends('admin_panel.layout.app')

@section('content')
@php
    $saleConfig = \App\Models\SaleFieldConfiguration::getConfigBundle();
    $flags = $saleConfig['flags'] ?? [];
    $visKey = 'create_visible';
    $vis = $saleConfig[$visKey] ?? [];
@endphp

    <!-- Loader Overlay -->
    <div id="pageLoader"
        class="{{ isset($sale) ? '' : 'd-none' }} position-fixed top-0 start-0 w-100 h-100 d-flex flex-column gap-3 justify-content-center align-items-center"
        style="background: rgba(255,255,255,0.9); z-index: 1055;">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="fw-bold text-primary fs-5">Loading Sale Data...</div>
    </div>
    <link href="{{ asset('assets/vendors/bootstrap5/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendors/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ==================== NEW SALE — CLEAN MODERN ERP/POS UI ==================== */
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

        .sale-page {
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

        .card-title {
            font-size: 14px;
            font-weight: 800;
            color: var(--pos-text);
            text-transform: uppercase;
            letter-spacing: .4px;
            line-height: 1.3;
        }

        /* ---------- LABELS ---------- */
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

        /* ---------- INPUTS & FORM CONTROLS ---------- */
        .sale-page .form-control,
        .sale-page .form-select {
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
        .sale-page .form-control::placeholder {
            color: #94A3B8;
            font-weight: 400;
        }
        .sale-page .form-control:focus,
        .sale-page .form-select:focus,
        .sale-page .form-control:focus-visible {
            border: 2px solid var(--pos-blue) !important;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .15) !important;
            outline: none !important;
            background-color: #ffffff !important;
        }
        .sale-page .input-readonly,
        .sale-page input[readonly] {
            background-color: #F8FAFC !important;
            color: #475569 !important;
            border-color: #E2E8F0 !important;
            cursor: default;
            font-weight: 600;
        }

        /* Select2 (customer) */
        #customerInputWrapper .select2-container--default .select2-selection--single {
            height: var(--pos-input-h) !important;
            border: 1px solid var(--pos-border) !important;
            border-radius: 6px !important;
            background-color: #ffffff !important;
            padding: 0 !important;
        }
        #customerInputWrapper .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px !important;
            padding-left: 10px !important;
            font-size: 13.5px !important;
            font-weight: 600 !important;
            color: var(--pos-text) !important;
        }
        #customerInputWrapper .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 6px !important;
        }
        #customerInputWrapper .select2-container--default.select2-container--focus .select2-selection--single,
        #customerInputWrapper .select2-container--default.select2-container--open .select2-selection--single {
            border: 2px solid var(--pos-blue) !important;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .15) !important;
        }

        /* Grouped Customer Search + Action Buttons (Auto-adaptive) */
        .cust-select-container {
            flex: 1 1 280px;
            min-width: 260px;
            max-width: 440px;
        }
        .customer-input-group {
            display: flex;
            flex-wrap: nowrap !important;
            align-items: center;
            width: 100%;
        }
        .customer-input-group #customerInputWrapper {
            flex: 1 1 auto;
            min-width: 0;
        }
        .customer-input-group #customerInputWrapper .select2-container--default .select2-selection--single {
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            border-right: none !important;
        }
        .customer-input-group #customerInputWrapper .form-control {
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            border-right: none !important;
        }
        .customer-input-group #btnOpenAddCustomerModal {
            border-radius: 0 !important;
            border-left: 1px solid var(--pos-border) !important;
            height: var(--pos-input-h) !important;
            padding: 0 10px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .customer-input-group #btnToggleCustomerInfo {
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
            border-top-right-radius: 6px !important;
            border-bottom-right-radius: 6px !important;
            border-left: 1px solid var(--pos-border) !important;
            height: var(--pos-input-h) !important;
            padding: 0 10px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        /* Select2 Dropdown Clean Modern Styling */
        .select2-dropdown {
            background-color: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.18) !important;
            z-index: 99999 !important;
            overflow: hidden !important;
        }
        .select2-container--default .select2-results {
            background-color: #ffffff !important;
        }
        .select2-container--default .select2-results__options {
            background-color: #ffffff !important;
            max-height: 260px !important;
            padding: 4px 0 !important;
        }
        .select2-container--default .select2-results__option {
            background-color: #ffffff !important;
            color: #0f172a !important;
            padding: 8px 12px !important;
            font-size: 13px !important;
            border-bottom: 1px solid #f1f5f9 !important;
            min-height: 36px !important;
            display: block !important;
        }
        .select2-container--default .select2-results__option--selectable {
            background-color: #ffffff !important;
            color: #0f172a !important;
        }
        .select2-container--default .select2-results__option .text-muted,
        .select2-container--default .select2-results__option small {
            color: #64748b !important;
        }
        .select2-container--default .select2-results__option--highlighted,
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #2563eb !important;
            color: #ffffff !important;
        }
        .select2-container--default .select2-results__option--highlighted .text-muted,
        .select2-container--default .select2-results__option--highlighted small,
        .select2-container--default .select2-results__option--highlighted div,
        .select2-container--default .select2-results__option--highlighted span {
            color: #e2e8f0 !important;
        }
        .select2-container--default .select2-results__message {
            background-color: #ffffff !important;
            color: #475569 !important;
            font-size: 13px !important;
            padding: 10px 14px !important;
            border-bottom: none !important;
        }
        .select2-dropdown .select2-search--dropdown {
            padding: 8px 10px !important;
            background-color: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0 !important;
            display: block !important;
        }
        .select2-dropdown .select2-search__field {
            border: 1.5px solid #2563eb !important;
            border-radius: 6px !important;
            padding: 7px 12px !important;
            font-size: 13px !important;
            background-color: #ffffff !important;
            color: #0f172a !important;
            outline: none !important;
            width: 100% !important;
            box-sizing: border-box !important;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15) !important;
            display: block !important;
        }

        /* ---------- BUTTONS ---------- */
        .sale-page .btn-primary {
            background: var(--pos-blue);
            border-color: var(--pos-blue);
            color: #ffffff;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13.5px;
            box-shadow: 0 1px 2px rgba(37, 99, 235, .2);
        }
        .sale-page .btn-primary:hover,
        .sale-page .btn-primary:focus {
            background: var(--pos-blue-hover);
            border-color: var(--pos-blue-hover);
            color: #ffffff;
        }
        .sale-page .btn-outline-primary {
            color: var(--pos-blue);
            border-color: #93C5FD;
            background: #ffffff;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13.5px;
        }
        .sale-page .btn-outline-primary:hover {
            background: var(--pos-blue-soft);
            color: var(--pos-blue-hover);
            border-color: var(--pos-blue);
        }
        .sale-page .btn-outline-secondary {
            color: var(--pos-muted);
            border-color: var(--pos-border);
            background: #ffffff;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13.5px;
        }
        .sale-page .btn-outline-secondary:hover {
            background: #F1F5F9;
            color: var(--pos-text);
            border-color: var(--pos-border-strong);
        }

        .btn-save-print {
            padding: 8px 18px !important;
            box-shadow: 0 3px 10px -2px rgba(37, 99, 235, .4);
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
        }
        .btn-icon-back:hover {
            background: #F1F5F9;
            color: var(--pos-text);
            border-color: var(--pos-border-strong);
        }

        /* ---------- PAGE HEADER ---------- */
        .sale-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 16px;
        }
        .sale-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sale-title-ic {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--pos-blue-soft);
            color: var(--pos-blue);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
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

        /* ---------- SALE TYPE SEGMENTED TOGGLE ---------- */
        .seg-toggle {
            display: flex;
            height: var(--pos-input-h);
            background: #F1F5F9;
            border: 1px solid var(--pos-border);
            border-radius: 6px;
            padding: 2px;
            width: 100%;
        }
        .seg-toggle .btn {
            flex: 1;
            border-radius: 4px;
            border: none;
            font-size: 12.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 0 8px;
        }
        .seg-toggle .btn.btn-outline-primary {
            background: transparent;
            color: var(--pos-muted);
        }
        .seg-toggle .btn-outline-primary:hover {
            background: rgba(37, 99, 235, .08);
            color: var(--pos-blue);
        }

        /* ---------- INVOICE GROUP ---------- */
        .invoice-group {
            flex-wrap: nowrap;
        }
        .invoice-group .btn-prefix {
            height: var(--pos-input-h);
            border: 1px solid var(--pos-border);
            border-right: none;
            background: #F8FAFC;
            color: var(--pos-text);
            font-weight: 700;
            font-size: 13px;
            border-radius: 6px 0 0 6px;
            padding: 0 10px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .invoice-group .btn-prefix:hover {
            background: #F1F5F9;
        }
        .invoice-group #inputInvoiceNo {
            border-radius: 0;
            border-left: none;
            border-right: none;
            font-family: Consolas, 'JetBrains Mono', monospace;
            font-size: 13.5px;
            font-weight: 700 !important;
        }
        .invoice-group .btn-refresh {
            height: var(--pos-input-h);
            border: 1px solid var(--pos-border);
            border-left: none;
            background: #ffffff;
            color: var(--pos-muted);
            border-radius: 0 6px 6px 0;
            padding: 0 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all .15s ease;
        }
        .invoice-group .btn-refresh:hover {
            background: #F1F5F9;
            color: var(--pos-blue);
        }

        /* ---------- CUSTOMER BALANCE CARD ---------- */
        .cust-bal-card {
            background: #FFFFFF;
            border: 1px solid var(--pos-border);
            border-radius: 8px;
            padding: 8px 10px;
            box-sizing: border-box;
            height: 100%;
            min-height: 104px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }
        .cb-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 5px;
        }
        .cb-id {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }
        .cb-avatar {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: var(--pos-blue);
            color: #FFFFFF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }
        .cb-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--pos-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .cb-code {
            font-size: 11px;
            color: var(--pos-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .cb-extras {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            margin-bottom: 5px;
        }
        .cb-ext {
            background: #F8FAFC;
            border: 1px solid #CBD5E1;
            border-radius: 6px;
            padding: 4px 7px;
        }
        .cb-ext-label {
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: .3px;
            font-weight: 700;
            color: #64748B;
            margin-bottom: 1px;
        }
        .cb-ext-val {
            font-size: 12px;
            font-weight: 700;
            color: var(--pos-text);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .cb-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
        }
        .cb-cell {
            background: #F8FAFC !important;
            border: 1px solid #CBD5E1 !important;
            border-radius: 6px !important;
            padding: 6px 4px !important;
            text-align: center;
        }
        .cb-label {
            font-size: 9.5px !important;
            text-transform: uppercase !important;
            letter-spacing: .4px !important;
            font-weight: 800 !important;
            color: #64748B !important;
            margin-bottom: 2px !important;
            white-space: nowrap !important;
        }
        .cb-value {
            font-size: 13px !important;
            font-weight: 800 !important;
            color: #0F172A !important;
            white-space: nowrap !important;
        }
        .cust-bal-card .text-danger {
            color: var(--pos-red) !important;
        }
        .cust-bal-card .text-success {
            color: var(--pos-green) !important;
        }
        #cc_paid_now {
            color: var(--pos-green) !important;
        }

        /* ---------- ITEMS HEADER ---------- */
        .items-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--pos-text);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .items-count {
            background: var(--pos-blue-soft);
            color: var(--pos-blue);
            font-weight: 700;
            border-radius: 999px;
            padding: 3px 10px;
            font-size: 12px;
        }

        /* ---------- PRODUCT CARDS ---------- */
        .pos-product-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: background .15s ease;
        }
        .pos-product-card:last-child {
            margin-bottom: 0;
        }
        .pos-product-card:hover {
            background: #F8FAFC;
        }
        .pos-product-img {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #F1F5F9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .pos-product-info {
            flex: 1;
            min-width: 0;
        }
        .pos-product-name {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--pos-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .pos-product-sub {
            font-size: 12px;
            color: var(--pos-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .pos-product-price {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--pos-text);
            white-space: nowrap;
        }
        .pos-product-add-btn {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: var(--pos-blue);
            color: #ffffff;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
            cursor: pointer;
            transition: background .15s ease;
        }
        .pos-product-add-btn:hover {
            background: var(--pos-blue-hover);
        }
        .badge-stock-green {
            background-color: var(--pos-green-soft) !important;
            color: #15803D !important;
            font-weight: 700 !important;
            border: 1px solid #BBF7D0 !important;
            padding: 2px 8px !important;
            border-radius: 6px !important;
            font-size: 11.5px !important;
        }

        /* ---------- PRODUCT TABLE (EXCEL GRID STYLE) ---------- */
        .table-responsive {
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            background: #ffffff;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .pos-table-wrap {
            overflow-x: auto;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        .sales-table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-bottom: 0;
            table-layout: auto !important;
            background: #ffffff;
        }

        /* Fluid Auto-Adaptive Column Widths (Matches Image 2 & Auto-expands on column removal) */
        .col-index-th, .sales-table .row-index-cell, .sales-table .row-index {
            width: 3% !important;
            min-width: 34px !important;
            text-align: center !important;
        }
        .col-product-th, .sales-table .col-product {
            width: 24% !important;
            min-width: 210px !important;
            text-align: left !important;
        }
        .c-stock-th, .sales-table .col-stock {
            width: 6% !important;
            min-width: 60px !important;
            text-align: center !important;
        }
        .col-qty-th, .sales-table .col-qty-wrapper {
            width: 8% !important;
            min-width: 75px !important;
        }
        .c-size-th, .sales-table .col-size {
            width: 6% !important;
            min-width: 60px !important;
            text-align: center !important;
        }
        .c-pcs-th, .sales-table .col-pieces {
            width: 6% !important;
            min-width: 60px !important;
        }
        .col-pcs-ctn-th, .sales-table .col-pcs-ctn {
            width: 6% !important;
            min-width: 65px !important;
            text-align: center !important;
        }
        .col-price-th, .sales-table .col-price-p {
            width: 10% !important;
            min-width: 95px !important;
        }
        .c-disc-th, .sales-table .col-disc {
            width: 9% !important;
            min-width: 90px !important;
        }
        .c-st-th, .sales-table .col-st {
            width: 7% !important;
            min-width: 68px !important;
        }
        .c-ft-th, .sales-table .col-ft {
            width: 7% !important;
            min-width: 68px !important;
        }
        .col-amount-th, .sales-table .col-amount {
            width: 10% !important;
            min-width: 95px !important;
            text-align: right !important;
        }
        .col-action-th, .sales-table .col-action {
            width: 4% !important;
            min-width: 38px !important;
            text-align: center !important;
        }
        .sales-table .input-group {
            flex-wrap: nowrap !important;
        }
        .sales-table .input-group > .form-control {
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            border-right: none !important;
        }
        .sales-table .input-group > .btn,
        .sales-table .input-group > .input-group-text {
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
            border-left: 1px solid #CBD5E1 !important;
            background-color: #F8FAFC;
        }
        .sales-table thead th {
            background: #F1F5F9 !important;
            color: #475569 !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 8px 6px !important;
            border: 1px solid #CBD5E1 !important;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }
        .sales-table thead th.col-product {
            text-align: left;
            padding-left: 10px !important;
        }
        .sales-table tbody td {
            padding: 3px 5px !important;
            height: 42px !important;
            border: 1px solid #CBD5E1 !important;
            vertical-align: middle;
            background: #ffffff;
        }
        .sales-table tbody tr:nth-child(even) td {
            background: #FAFCFE;
        }
        .sales-table tbody tr:hover td {
            background: #F1F5F9;
        }
        .row-index-cell {
            font-size: 12px;
            font-weight: 700;
            color: #64748B;
            text-align: center;
            background: #F8FAFC !important;
        }

        /* Table inputs — clean Excel grid cells that highlight with sharp blue outline on focus */
        .sales-table tbody .form-control,
        .sales-table tbody .form-select {
            height: 34px !important;
            border: 1px solid #CBD5E1 !important;
            border-radius: 4px !important;
            padding: 3px 6px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            background: #ffffff !important;
            box-shadow: none !important;
            color: #0F172A !important;
            width: 100% !important;
            transition: border-color .12s ease, box-shadow .12s ease;
        }
        .sales-table tbody .form-control:hover,
        .sales-table tbody .form-select:hover {
            border-color: #94A3B8 !important;
        }
        .sales-table tbody .form-control:focus,
        .sales-table tbody .form-select:focus,
        .sales-table tbody .form-control:focus-visible {
            border: 2px solid #2563EB !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .15) !important;
            outline: none !important;
        }
        .sales-table tbody input[readonly],
        .sales-table tbody .input-readonly {
            background: #F8FAFC !important;
            color: #475569 !important;
            cursor: default !important;
            font-weight: 600 !important;
            border-color: #E2E8F0 !important;
        }
        .sales-table tbody input[readonly]:focus {
            border-color: #CBD5E1 !important;
            box-shadow: none !important;
        }

        /* Stock badge style inside stock cell */
        .stock-badge {
            display: inline-block;
            background: #F1F5F9;
            color: #475569;
            border: 1px solid var(--pos-border);
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            padding: 4px 8px;
            line-height: 1.2;
        }
        .stock-badge.strong {
            background: var(--pos-green-soft);
            color: #15803D;
            border-color: #BBF7D0;
        }

        /* Product select2 inside table - Excel cell style */
        .sales-table tbody .select2-container {
            width: 100% !important;
        }
        .sales-table tbody .select2-container .select2-selection--single {
            height: 34px !important;
            border: 1px solid #CBD5E1 !important;
            border-radius: 4px !important;
            background: #ffffff !important;
            padding: 0 !important;
        }
        .sales-table tbody .select2-container:hover .select2-selection--single {
            border-color: #94A3B8 !important;
        }
        .sales-table tbody .select2-container--focus .select2-selection--single,
        .sales-table tbody .select2-container--open .select2-selection--single {
            border: 2px solid #2563EB !important;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .15) !important;
            background: #ffffff !important;
        }
        .sales-table tbody .select2-container .select2-selection__rendered {
            line-height: 32px !important;
            padding-left: 7px !important;
            padding-right: 18px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            color: #0F172A !important;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .sales-table tbody .select2-container .select2-selection__arrow {
            height: 32px !important;
            right: 4px !important;
        }

        /* Qty cell */
        .qty-cell-flex {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .qty-cell-flex .carton-qty {
            flex: 1;
            min-width: 0;
        }
        .qty-unit-toggle {
            height: 38px !important;
            min-width: 42px !important;
            border-radius: 6px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 6px !important;
        }

        /* Price cell */
        .price-cell-flex {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .price-cell-flex .visible-price {
            flex: 1;
            min-width: 0;
        }
        .price-mode-row-toggle {
            height: 34px !important;
            min-width: 24px !important;
            border-radius: 0 4px 4px 0 !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px !important;
        }

        /* Discount cell */
        .discount-wrapper {
            display: flex;
            align-items: stretch;
            gap: 4px;
        }
        .discount-wrapper .discount-value {
            flex: 1;
            min-width: 0;
            text-align: right;
        }
        .discount-wrapper .discount-toggle {
            width: 24px !important;
            min-width: 24px !important;
            flex-shrink: 0;
            height: 34px !important;
            border: 1px solid var(--pos-border) !important;
            background: #F8FAFC !important;
            color: var(--pos-muted) !important;
            font-weight: 700 !important;
            font-size: 11px !important;
            border-radius: 0 4px 4px 0 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 !important;
            transition: all .15s ease;
        }
        .discount-wrapper .discount-toggle:hover {
            background: #EEF2F7 !important;
            color: var(--pos-blue) !important;
        }

        /* Amount cell */
        .sales-amount {
            font-weight: 800 !important;
            color: var(--pos-text) !important;
            font-size: 14px !important;
        }

        /* Row delete button */
        .sales-table .del-row {
            width: 30px !important;
            height: 30px !important;
            border-radius: 6px;
            border: 1px solid #FECACA;
            background: #ffffff;
            color: var(--pos-red);
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            cursor: pointer;
            transition: all .15s ease;
        }
        .sales-table .del-row:hover {
            background: var(--pos-red);
            border-color: var(--pos-red);
            color: #ffffff;
        }

        /* Responsive: compress controls on smaller screens so the table always fits its container */
        @media (max-width: 1199.98px) {
            .sales-table tbody .form-control,
            .sales-table tbody .form-select {
                height: 34px !important;
                font-size: 12px !important;
                padding: 3px 6px !important;
            }
            .sales-table tbody .select2-container .select2-selection--single {
                height: 34px !important;
            }
            .sales-table tbody .select2-container .select2-selection__rendered {
                line-height: 32px !important;
                font-size: 12px !important;
                padding-left: 6px !important;
                padding-right: 16px !important;
            }
            .sales-table tbody td {
                height: 46px;
                padding: 5px;
            }
            .sales-table thead th {
                padding: 9px 5px;
                font-size: 10px;
            }
            .qty-unit-toggle,
            .price-mode-row-toggle,
            .discount-wrapper .discount-toggle {
                height: 34px !important;
            }
            .qty-unit-toggle {
                min-width: 34px !important;
            }
            .price-mode-row-toggle {
                min-width: 28px !important;
            }
            .sales-table .del-row {
                width: 30px;
                height: 30px;
            }
            .discount-wrapper .discount-toggle {
                width: 28px;
            }
            .stock-badge {
                font-size: 11px;
                padding: 3px 6px;
            }
        }

        /* Phones: drop non-essential columns (#, Stock, Pcs, Pcs/Ctn) instead of scrolling */
        @media (max-width: 767.98px) {
            .sales-table thead th.col-index-th,
            .sales-table thead th.c-stock-th,
            .sales-table thead th.c-pcs-th,
            .sales-table thead th.col-pcs-ctn-th,
            .sales-table tbody td.row-index,
            .sales-table tbody td.col-stock,
            .sales-table tbody td.col-pieces,
            .sales-table tbody td.col-pcs-ctn {
                display: none !important;
            }
            .sales-table thead th {
                white-space: normal;
                line-height: 1.2;
            }
        }

        /* Grid total footer */
        .sales-table tfoot td {
            background: #F8FAFC;
            border-top: 2px solid #CBD5E1 !important;
            border-bottom: 1px solid #CBD5E1 !important;
            border-left: 1px solid #CBD5E1 !important;
            border-right: 1px solid #CBD5E1 !important;
            padding: 12px 14px !important;
        }
        .grid-total-label {
            font-size: 13px !important;
            font-weight: 800 !important;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #0F172A !important;
            text-align: right !important;
            padding-right: 15px !important;
        }
        .grid-total-val {
            font-size: 17px !important;
            font-weight: 800 !important;
            color: #0F172A !important;
            text-align: right !important;
            font-variant-numeric: tabular-nums;
        }

        /* ---------- CHECKOUT EXTRA FIELDS (AUTO-FLEX) ---------- */
        .checkout-extra-fields {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }
        .checkout-extra-fields .wrapper-officer {
            flex: 1.5 1 170px;
            min-width: 140px;
        }
        .checkout-extra-fields .wrapper-po-no {
            flex: 1.2 1 140px;
            min-width: 120px;
        }
        .checkout-extra-fields .wrapper-po-date {
            flex: 1 1 120px;
            min-width: 110px;
        }
        .checkout-extra-fields .wrapper-reference {
            flex: 1.8 1 180px;
            min-width: 150px;
        }
        .checkout-extra-fields .wrapper-credit-days {
            flex: 0.8 1 90px;
            min-width: 80px;
        }

        /* ---------- PAYMENT METHODS ---------- */
        .pay-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            padding-bottom: 4px;
            margin-bottom: 6px;
            border-bottom: 1px solid #E2E8F0;
        }
        .btn-pay-head {
            font-size: 11px !important;
            padding: 2px 6px !important;
            border-radius: 3px !important;
        }
        .rv-row {
            display: flex;
            gap: 6px;
            align-items: center;
            margin-bottom: 4px;
        }
        .rv-row .rv-account {
            flex: 1;
            min-width: 0;
            height: 28px !important;
            font-size: 11.5px !important;
            border-color: #CBD5E1;
            font-weight: 600;
        }
        .rv-row .rv-amount {
            width: 110px;
            flex-shrink: 0;
            height: 28px !important;
            font-size: 11.5px !important;
            text-align: right;
            font-weight: 700;
            border-color: #CBD5E1;
        }
        .btnRemRV {
            width: 28px;
            height: 28px !important;
            border-radius: 4px;
            border: 1px solid var(--pos-border);
            background: #ffffff;
            color: var(--pos-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 11px;
            transition: all .15s ease;
        }
        .btnRemRV:hover {
            background: var(--pos-red-soft);
            color: var(--pos-red);
            border-color: #FECACA;
        }
        .change-row {
            border-top: 1px dashed var(--pos-border);
            margin-top: 6px;
            padding-top: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .change-row .change-label {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--pos-muted);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .change-row .form-select {
            width: 130px;
            height: 28px !important;
            font-size: 11.5px !important;
        }

        /* ---------- ORDER SUMMARY ---------- */
        .s-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 6px;
            padding: 3px 0;
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
            padding: 4px 8px;
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-radius: 4px;
            margin: 3px 0;
        }
        .net-label {
            font-size: 12.5px;
            font-weight: 800;
            color: #1E3A8A;
            text-transform: uppercase;
        }
        .net-val {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -.3px;
            color: #2563EB;
            font-variant-numeric: tabular-nums;
        }
        .paid-val {
            font-weight: 800;
            font-size: 12.5px;
            color: #16A34A;
            font-variant-numeric: tabular-nums;
        }
        .change-val {
            font-weight: 700;
            font-size: 12.5px;
            font-variant-numeric: tabular-nums;
        }
        .change-val.text-success {
            color: var(--pos-green) !important;
        }
        .change-val.text-danger {
            color: var(--pos-red) !important;
        }
        .discount-input {
            width: 110px;
            flex-shrink: 0;
        }
        .discount-input input {
            height: 26px !important;
            font-size: 11.5px !important;
            padding: 1px 4px !important;
            font-weight: 700;
        }
        .discount-input .input-group-text {
            height: 26px !important;
            font-size: 10.5px !important;
            padding: 0 4px !important;
        }

        /* ---------- STICKY BOTTOM ACTION BAR ---------- */
        .sale-bottom-bar {
            position: sticky;
            bottom: 0;
            z-index: 40;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 6px 10px;
            background: #ffffff;
            border: 1px solid #CBD5E1;
            border-radius: 6px;
            box-shadow: 0 -3px 10px -4px rgba(15, 23, 42, .1);
            padding: 6px 12px;
            margin-top: 8px;
        }
        .bb-left {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 11.5px;
            color: #64748B;
            flex-wrap: wrap;
        }
        .bb-left b {
            color: #0F172A;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }
        .bb-left .text-success {
            color: #16A34A !important;
        }
        .btn-ghost {
            border: 1px solid #CBD5E1;
            background: #ffffff;
            color: #475569;
            border-radius: 4px;
            font-weight: 700;
            font-size: 11px;
            padding: 3px 7px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            transition: all .15s ease;
        }
        .btn-ghost:hover {
            background: #F1F5F9;
            color: #0F172A;
            border-color: #94A3B8;
        }
        .bb-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        /* ---------- OFF-CANVAS (Quick Products) ---------- */
        .offcanvas-header {
            border-bottom: 1px solid var(--pos-border);
        }

        /* ---------- VALIDATION STATES ---------- */
        .invalid-input,
        .invalid-select {
            border-color: var(--pos-red) !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, .12) !important;
        }
        .invalid-cell {
            background: #FFF7F7 !important;
            box-shadow: inset 0 0 0 1px rgba(220, 38, 38, .25) !important;
        }
        .invalid-input + .select2-container .select2-selection--single,
        .invalid-select + .select2-container .select2-selection--single {
            border-color: var(--pos-red) !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, .12) !important;
        }

        /* ---------- ALERT BOX ---------- */
        #alertBox {
            border-radius: 10px;
            font-size: 13.5px;
            padding: 12px 16px;
            margin-bottom: 18px;
        }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 1199.98px) {
            .cust-bal-card {
                height: auto;
                min-height: 0;
                max-height: none;
                overflow: visible;
            }
            .cb-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 575.98px) {
            .sale-subtitle {
                display: none;
            }
            .bb-left {
                gap: 12px;
                font-size: 12.5px;
            }
            .bb-actions .btn-outline-secondary,
            .bb-actions .btn-outline-primary {
                display: none;
            }
            .bb-actions .btn-primary {
                width: 100%;
            }
        }
    
    /* Dynamic Field Visibility Controls */
    @if(!($flags['sales_tax'] ?? true) || !($vis['col_sales_tax'] ?? true))
        .col-st, .c-st-th, .c-st, .row-summary-sales-tax { display: none !important; }
    @endif
    @if(!($flags['further_tax'] ?? true) || !($vis['col_further_tax'] ?? true))
        .col-ft, .c-ft-th, .c-ft, .row-summary-further-tax { display: none !important; }
    @endif
    @if(!($flags['exclusive_gst_amount'] ?? true) || !($vis['summary_exclusive_gst'] ?? true))
        .row-summary-exclusive-gst { display: none !important; }
    @endif
    @if(!($vis['col_discount'] ?? true))
        .col-disc, .c-disc-th, .c-disc { display: none !important; }
    @endif
    @if(!($vis['col_stock'] ?? true))
        .col-stock, .c-stock-th, .c-stock { display: none !important; }
    @endif
    @if(!($vis['col_size'] ?? true))
        .col-size, .c-size-th, .c-size { display: none !important; }
    @endif
    @if(!($vis['col_pieces'] ?? true))
        .col-pieces, .c-pcs-th, .c-pcs { display: none !important; }
    @endif
    @if(!($vis['col_pcs_ctn'] ?? true))
        .col-pcs-ctn, .col-pcs-ctn-th, .c-pc { display: none !important; }
    @endif
    @if(!($vis['col_price_mode'] ?? true))
        .price-mode-row-toggle { display: none !important; }
    @endif
    @if(!($vis['summary_gross'] ?? true))
        .row-summary-gross { display: none !important; }
    @endif
    @if(!($vis['summary_line_discount'] ?? true))
        .row-summary-line-discount { display: none !important; }
    @endif
    @if(!($vis['summary_overall_discount'] ?? true))
        .row-summary-overall-discount { display: none !important; }
    @endif
    @if(!($vis['summary_freight'] ?? true))
        .row-summary-freight { display: none !important; }
    @endif
    @if(!($vis['summary_customer_balance'] ?? true))
        .row-summary-customer-balance { display: none !important; }
    @endif
    @if(!($vis['field_credit_days'] ?? true))
        .wrapper-credit-days { display: none !important; }
    @endif
    @if(!($vis['field_reference'] ?? true))
        .wrapper-reference { display: none !important; }
    @endif
    @if(!($vis['field_sale_type'] ?? true))
        .wrapper-sale-type { display: none !important; }
    @endif

</style>

    <div class="container-fluid px-3 px-lg-4 pt-3 pb-4 sale-page">

        <div id="alertBox" class="alert d-none" role="alert"></div>

        <form id="saleForm" autocomplete="off">
            @csrf
            <input type="hidden" id="booking_id" name="booking_id" value="">
            <input type="hidden" id="action" name="action" value="sale">
            <input type="hidden" name="cash" value="0">
            <input type="hidden" id="totalBalance" value="0">
            <input type="hidden" name="total_extra_cost" id="discountAmount" value="0">

            {{-- ============================ PAGE HEADER ============================ --}}
            {{-- ============================ PAGE HEADER ============================ --}}
            <div class="sale-header">
                <div class="sale-header-left">
                    <a href="{{ route('sale.index') }}" class="btn-icon-back" title="Back to Sales List">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div class="sale-title-ic">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="sale-title-main">
                        <h5 class="header-text mb-0">New Sale</h5>
                        <div class="sale-subtitle">Create a new invoice &amp; manage checkout</div>
                    </div>
                </div>

                {{-- Header Right: Invoice No & Date --}}
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    {{-- Invoice No --}}
                    <div class="d-flex align-items-center gap-2">
                        <label class="field-label mb-0 fw-bold text-secondary" for="inputInvoiceNo">Inv #:</label>
                        <div class="input-group invoice-group" style="width: 200px;">
                            <button class="btn btn-prefix dropdown-toggle d-flex align-items-center gap-1"
                                    type="button"
                                    id="btnInvoicePrefix"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                <span id="activePrefixLabel">{{ $activePrefix ?? 'INV' }}</span>
                            </button>
                            <ul class="dropdown-menu shadow-lg p-1 border-0" id="dropdownInvoiceSeriesList" aria-labelledby="btnInvoicePrefix" style="min-width: 160px; font-size: 13px; z-index: 1050;">
                                @if(isset($allSeries) && count($allSeries) > 0)
                                    @foreach($allSeries as $s)
                                        <li>
                                            <a class="dropdown-item fw-bold {{ ($activePrefix ?? 'INV') == $s->prefix ? 'text-success active bg-light' : '' }}"
                                               href="#"
                                               data-prefix="{{ $s->prefix }}"
                                               data-next="{{ $s->next_number }}"
                                               data-padding="{{ $s->padding }}">
                                                @if(($activePrefix ?? 'INV') == $s->prefix) <i class="fas fa-check text-success me-1"></i> @endif
                                                {{ $s->prefix }} <span class="text-muted small font-monospace">({{ $s->padding }}d)</span>
                                            </a>
                                        </li>
                                    @endforeach
                                @else
                                    <li><a class="dropdown-item fw-bold text-success active bg-light" href="#" data-prefix="INV"><i class="fas fa-check text-success me-1"></i> INV (4d)</a></li>
                                @endif
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <a class="dropdown-item fw-bold text-success d-flex align-items-center gap-1" href="#" id="btnOpenAddSeriesModal">
                                        <i class="fas fa-plus-circle me-1"></i> Add Series
                                    </a>
                                </li>
                            </ul>

                            <input type="text" class="form-control text-center fw-bold input-readonly" name="Invoice_no" id="inputInvoiceNo" value="{{ $nextInvoiceNumber }}" readonly style="height: 38px;">

                            <button class="btn btn-refresh"
                                    type="button"
                                    id="btnRefreshInvoiceNo"
                                    title="Regenerate Invoice Number" style="height: 38px;">
                                <i class="fas fa-sync-alt" id="iconRefreshInvoice"></i>
                            </button>
                        </div>
                    </div>

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
                    {{-- Warehouse Selector --}}
                    <div class="d-flex align-items-center gap-2">
                        <label class="field-label mb-0 fw-bold text-secondary" for="headerWarehouseSelect"><i class="fas fa-warehouse text-primary me-1"></i>Warehouse:</label>
                        <select name="warehouse_id" id="headerWarehouseSelect" class="form-select form-select-sm fw-bold border-primary shadow-sm" style="min-width: 170px; height: 38px;">
                            @if(isset($warehouse) && count($warehouse) > 0)
                                @foreach($warehouse as $wh)
                                    <option value="{{ $wh->id }}" {{ ($userWarehouseId ?? 1) == $wh->id ? 'selected' : '' }}>
                                        {{ $wh->warehouse_name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    @else
                        <input type="hidden" name="warehouse_id" id="headerWarehouseSelect" value="{{ $userWarehouseId ?? 1 }}">
                    @endif

                    {{-- Date --}}
                    <div class="d-flex align-items-center gap-2">
                        <label class="field-label mb-0 fw-bold text-secondary" for="displayDateInput">Date:</label>
                        <input type="text" name="sale_date" class="form-control datepicker-custom text-center fw-bold" id="displayDateInput" value="{{ date('Y-m-d') }}" style="width: 130px; height: 38px;">
                    </div>

                    {{-- Screen Configuration Button --}}
                    <div>
                        <a href="{{ route('sales.configuration') }}" target="_blank" class="btn btn-outline-secondary d-flex align-items-center gap-1 px-2" style="height: 38px; border-radius: 8px;" title="Configure Sale Fields & Taxes">
                            <i class="fas fa-sliders-h text-primary"></i>
                            <span class="d-none d-md-inline small fw-bold">Screen Config</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- ============================ SALE INFORMATION CARD ============================ --}}
            <div class="sale-card mb-3 p-2 px-3">
                <div class="row g-2 align-items-center">
                    {{-- Left: Customer Dropdown, Grouped Action Buttons, Sale Type --}}
                    <div class="col-xl-7 col-lg-7 col-md-12">
                        <div class="d-flex align-items-end gap-2 flex-wrap">
                            {{-- Customer Input Group --}}
                            <div class="cust-select-container">
                                <label class="field-label mb-1">Customer</label>
                                <div class="input-group customer-input-group">
                                    <div id="customerInputWrapper" style="min-width: 0;">
                                        <input type="text" class="form-control d-none" name="walkin_name" id="walkinNameInput" value="Walk-in Customer" placeholder="Enter Walk-in Name...">
                                        <select class="form-select" id="customerSelect" name="customer" style="width:100%">
                                            <option value="">Select Customer...</option>
                                            @if(isset($customer) && count($customer) > 0)
                                                @foreach($customer as $c)
                                                    <option value="{{ $c->id }}" 
                                                            data-mobile="{{ $c->mobile }}" 
                                                            data-address="{{ $c->address }}" 
                                                            data-code="{{ $c->customer_id }}"
                                                            data-prev="{{ $c->previous_balance ?? 0 }}" 
                                                            data-range="{{ $c->balance_range ?? 0 }}"
                                                            data-sales-officer="{{ $c->sales_officer_id ?? '' }}">
                                                        {{ $c->customer_id ? $c->customer_id . ' — ' : '' }}{{ $c->customer_name }} {{ $c->mobile ? '(' . $c->mobile . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <button type="button" id="btnOpenAddCustomerModal"
                                            class="btn btn-outline-primary"
                                            data-toggle="modal" data-target="#addCustomerModal"
                                            data-bs-toggle="modal" data-bs-target="#addCustomerModal"
                                            title="Quick Add Customer (Alt+C or F2)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button type="button" id="btnToggleCustomerInfo"
                                            class="btn btn-outline-secondary"
                                            data-toggle="modal" data-target="#modalCustomerDetails"
                                            data-bs-toggle="modal" data-bs-target="#modalCustomerDetails"
                                            title="View Customer Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div id="customerTaxBadgeContainer" class="mt-1 d-none"></div>
                                <div id="customerQuickInfoContainer" class="mt-1 d-flex flex-wrap gap-1 align-items-center d-none" style="font-size: 11px;">
                                    <span class="badge bg-light text-secondary border me-1" id="cqi_address_badge" title="Customer Address">
                                        <i class="fas fa-map-marker-alt text-danger me-1"></i><span id="cqi_address_text">—</span>
                                    </span>
                                    <span class="badge bg-light text-primary border" id="cqi_credit_badge" title="Credit Limit">
                                        <i class="fas fa-shield-alt text-primary me-1"></i>Limit: <span id="cqi_credit_text">Unlimited</span>
                                    </span>
                                </div>
                            </div>

                            {{-- Sale Type --}}
                            <div class="align-self-end mb-0 wrapper-sale-type">
                                <label class="field-label mb-1">Sale Type</label>
                                <div class="seg-toggle" role="group" aria-label="Sale Type">
                                    <button type="button" class="btn btn-primary active text-white" id="btnTypeCustomer">
                                        <i class="fas fa-users me-1"></i> Customer
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" id="btnTypeWalkin">
                                        <i class="fas fa-walking me-1"></i> Walk-in
                                    </button>
                                </div>
                                <select class="d-none" id="partyTypeSelect" name="partyType">
                                    @foreach(\App\Models\CustomerType::orderBy('name')->get() as $type)
                                        <option value="{{ $type->name }}" {{ $type->name === 'Main Customer' ? 'selected' : '' }}>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Right: Balances on single line --}}
                    <div class="col-xl-5 col-lg-5 col-md-12 text-xl-end mt-2 mt-xl-0 d-flex justify-content-xl-end justify-content-start align-items-center">
                        <div class="d-inline-flex align-items-center flex-nowrap gap-2 fw-bold text-nowrap" style="font-size: 12px; white-space: nowrap;">
                            <span class="text-danger">Prev. Due: <span id="cc_prev_bal_val">Rs 0</span> <span id="cc_prev_bal_suffix">Dr</span></span>
                            <span class="text-muted opacity-50">|</span>
                            <span class="text-primary">Current Due: <span id="cc_current_bill">Rs 0</span></span>
                            <span class="text-muted opacity-50">|</span>
                            <span class="text-success">Paid: <span id="cc_paid_now">Rs 0</span></span>
                            <span class="text-muted opacity-50">|</span>
                            <span style="color: #9333EA;">Closing: <span id="cc_closing_bal_val">Rs 0</span> <span id="cc_closing_bal_suffix">Dr</span></span>
                        </div>
                    </div>
                </div>

                <span id="cc_customer_name" class="d-none"></span>
                <span id="ci_code" class="d-none"></span>
                <span id="ci_name" class="d-none">—</span>
                <span id="ci_mobile" class="d-none">—</span>
                <span id="ci_address" class="d-none">—</span>
            </div>

            {{-- Hidden fields for backend --}}
            <input type="hidden" name="is_walkin" id="is_walkin" value="0">
            <input type="hidden" id="address" name="address">
            <input type="hidden" id="tel" name="tel">
            <input type="hidden" id="previousBalance" value="0">
            <input type="hidden" id="rangeBalance" value="0">

            {{-- ============================ ITEMS SECTION ============================ --}}
            <div class="sale-card mb-3 p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="items-title">
                        Items
                        <span class="items-count" id="itemsRowCount">0</span>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-primary px-3" id="btnNewProductHeader">
                            <i class="fas fa-box-open me-1"></i> New Product
                        </button>
                    </div>
                </div>

                <div class="pos-table-wrap">
                    <table class="table sales-table mb-0">
                        <thead>
                            <tr>
                                <th class="col-index-th">#</th>
                                <th class="col-product-th col-product">Product</th>
                                @if($canSelectWarehouse)
                                    <th class="col-warehouse-th text-center" style="width: 140px; min-width: 130px;">Warehouse</th>
                                @endif
                                <th class="c-stock-th">Stock</th>
                                <th class="col-qty-th">Qty</th>
                                <th class="c-size-th">Size</th>
                                <th class="c-pcs-th">Pcs</th>
                                <th class="col-pcs-ctn-th">Pcs/Ctn</th>
                                <th class="col-price-th">Price</th>
                                <th class="c-disc-th">Discount</th>
                                <th class="c-st-th">ST %</th>
                                <th class="c-ft-th">FT %</th>
                                <th class="col-amount-th">Amount</th>
                                <th class="col-action-th">Action</th>
                            </tr>
                        </thead>
                        <tbody id="salesTableBody">
                            <tr>
                                <td class="row-index-cell row-index">1</td>

                                <!-- PRODUCT -->
                                <td class="col-product">
                                    <select class="form-select product" style="width:100%">
                                        <option value=""></option>
                                        @if(isset($allProducts) && count($allProducts) > 0)
                                            @foreach($allProducts as $p)
                                                <option value="{{ $p->id }}" 
                                                    data-sku="{{ $p->item_code }}" 
                                                    data-stock="{{ $p->warehouse_stocks_sum_total_pieces ?? 0 }}"
                                                    data-retail_price="{{ $p->sale_price_per_piece ?? 0 }}"
                                                    data-trade_price="{{ $p->purchase_price_per_piece ?? 0 }}"
                                                    data-wholesale_price="{{ $p->wholesale_price ?? 0 }}"
                                                    data-weight_per_piece="{{ $p->weight_per_piece ?? 0 }}"
                                                    data-pieces_per_box="{{ $p->pieces_per_box ?? 1 }}"
                                                    data-size_mode="{{ $p->size_mode }}"
                                                    data-sale_discount_percent="{{ $p->sale_discount_percent ?? 0 }}"
                                                    data-name="{{ $p->item_name }}">
                                                    {{ $p->item_name }} (SKU: {{ $p->item_code }})
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <input type="hidden" class="product-id-hidden" name="product_id[]">
                                    <input type="hidden" class="variant-data-hidden" name="color[]">
                                     <input type="hidden" class="batch-id-hidden" name="batch_id[]">
                                     <input type="hidden" class="batch-no-hidden" name="batch_no[]">
                                     <input type="hidden" class="serials-hidden" name="serials[]">
                                     <div class="row-tracking-badges mt-1 d-flex flex-wrap gap-1"></div>
                                    <input type="hidden" class="item-code-display">
                                    <input type="hidden" class="size-h">
                                    <input type="hidden" class="size-w">
                                    <input type="hidden" class="size-mode-text">
                                </td>

                                <!-- WAREHOUSE -->
                                @if($canSelectWarehouse)
                                <td class="col-warehouse" style="width: 140px; min-width: 130px;">
                                    <select name="warehouse_id[]" class="form-select form-select-sm warehouse row-warehouse-select fw-semibold border-secondary" style="height: 31px; font-size: 0.8rem;">
                                        @if(isset($warehouse) && count($warehouse) > 0)
                                            @foreach($warehouse as $wh)
                                                <option value="{{ $wh->id }}" {{ ($userWarehouseId ?? 1) == $wh->id ? 'selected' : '' }}>
                                                    {{ $wh->warehouse_name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </td>
                                @else
                                    <input type="hidden" class="warehouse" name="warehouse_id[]" value="{{ $userWarehouseId ?? 1 }}">
                                @endif

                                <!-- STOCK -->
                                <td class="col-stock text-center">
                                    <input type="text" class="form-control stock text-center input-readonly" readonly tabindex="-1">
                                    <input type="hidden" class="variant-stock-value">
                                </td>

                                <!-- QTY -->
                                <td class="col-qty-wrapper">
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="any" class="form-control carton-qty text-start fw-bold" name="carton_qty[]" placeholder="0" min="0" value="">
                                        <button type="button" class="btn btn-outline-primary qty-unit-toggle px-1 py-0 d-none fw-bold" data-unit-mode="main" title="Toggle Unit">Kg</button>
                                    </div>
                                    <input type="hidden" class="hidden-sub-unit-mode" name="sub_unit_mode[]" value="main">
                                </td>

                                <!-- BONUS (Commented out) -->
                                {{-- <td class="col-bonus"> --}}
                                    <input type="hidden" class="bonus-qty" name="bonus_qty[]" value="0">
                                {{-- </td> --}}

                                <!-- SIZE -->
                                <td class="col-size">
                                    <input type="text" class="form-control size-display text-center" name="size_display[]" placeholder="-">
                                    <input type="hidden" class="pack-qty" name="pack_qty[]" value="1">
                                </td>

                                <!-- PCS -->
                                <td class="col-pieces">
                                    <input type="text" class="form-control total-pieces text-end input-readonly fw-semibold" name="total_pieces[]" readonly placeholder="0" tabindex="-1">
                                    <input type="hidden" class="sales-qty" name="qty[]" value="0">
                                </td>

                                <!-- PCS/CTN (always visible; shows value when unit is Carton, "–" otherwise) -->
                                <td class="col-pcs-ctn text-center">
                                    <input type="text" class="form-control pcs-per-ctn text-center input-readonly fw-semibold" readonly tabindex="-1" placeholder="–">
                                </td>

                                <!-- PRICE -->
                                <td class="col-price-p">
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control visible-price text-end fw-semibold" name="visible_price[]" placeholder="0">
                                        <button type="button" class="btn btn-outline-primary price-mode-row-toggle px-2 py-0 fw-bold" data-mode="retail" title="Retail Mode">R</button>
                                    </div>
                                    <input type="hidden" class="price-per-piece" name="price_per_piece[]">
                                    <input type="hidden" class="retail-price">
                                    <input type="hidden" class="wholesale-price">
                                    <input type="hidden" class="weight-per-piece">
                                </td>

                                <!-- DISCOUNT -->
                                <td class="col-disc">
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control discount-value text-end" name="item_disc[]" placeholder="0">
                                        <input type="hidden" class="discount-type-hidden" name="discount_type[]" value="percent">
                                        <button type="button" class="btn btn-outline-secondary discount-toggle px-2 py-0 fw-bold" data-type="percent" tabindex="-1">%</button>
                                    </div>
                                    <input type="hidden" class="discount-amount" value="0">
                                </td>

                                <!-- ST % (Sales Tax @18%) -->
                                <td class="col-st">
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" class="form-control sales-tax-percent text-end" name="sales_tax_percent[]" value="{{ (float)($saleConfig['default_st_rate'] ?? 18) }}" placeholder="18">
                                        <span class="input-group-text px-1 py-0 text-muted" style="font-size: 11px; font-weight: 600;">%</span>
                                    </div>
                                    <input type="hidden" class="sales-tax-amount" name="sales_tax_amount[]" value="0">
                                </td>

                                <!-- FT % (Further Tax @3%) -->
                                <td class="col-ft">
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" class="form-control further-tax-percent text-end" name="further_tax_percent[]" value="{{ (float)($saleConfig['default_ft_rate'] ?? 3) }}" placeholder="3">
                                        <span class="input-group-text px-1 py-0 text-muted" style="font-size: 11px; font-weight: 600;">%</span>
                                    </div>
                                    <input type="hidden" class="further-tax-amount" name="further_tax_amount[]" value="0">
                                </td>

                                <!-- AMOUNT -->
                                <td class="col-amount">
                                    <input type="text" class="form-control sales-amount text-end input-readonly" name="total[]" value="0" readonly tabindex="-1">
                                    <input type="hidden" class="gross-amount" name="gross_amount[]">
                                    <input type="hidden" class="exclusive-gst-amount" name="exclusive_gst_amount[]" value="0">
                                </td>

                                <!-- ACTION -->
                                <td class="col-action text-center">
                                    <button type="button" class="del-row" tabindex="-1" title="Delete Row">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="100" class="p-2 px-3 bg-light border-top">
                                    <div class="d-flex justify-content-end align-items-center gap-3">
                                        <span class="grid-total-label mb-0" style="padding-right:0 !important; font-size: 13px; font-weight: 800; color: #0F172A; text-transform: uppercase; letter-spacing: .5px;">GRID TOTAL:</span>
                                        <span class="grid-total-val mb-0" style="font-size: 17px; font-weight: 800; color: #0F172A;">Rs <span id="totalAmount">0.00</span></span>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- ============================ PAYMENT METHODS & ORDER SUMMARY ============================ --}}
            <div class="row g-2 align-items-start mb-3">
                {{-- LEFT: Payment Methods --}}
                <div class="col-lg-6">
                    <div class="sale-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                            <span class="card-title d-flex align-items-center gap-2 mb-0" style="font-size: 15px;">
                                <i class="fas fa-wallet text-primary"></i> Payment Methods
                            </span>
                            <div class="btn-group btn-group-sm" role="group" id="payModeToggleGroup">
                                <button type="button" class="btn btn-outline-primary btn-sm active fw-bold px-3 py-1" id="btnModeAccounts" onclick="switchPayMode('accounts')">
                                    <i class="fas fa-university me-1"></i> Cash / Bank
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm text-dark fw-bold px-3 py-1" id="btnModeCheque" onclick="switchPayMode('cheque')">
                                    <i class="fas fa-money-check-alt me-1"></i> Cheque
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm fw-bold px-2 py-1" id="btnModeBoth" onclick="switchPayMode('both')" title="Split Payment: Cash/Bank + Cheque">
                                    <i class="fas fa-layer-group me-1"></i> Both (Split)
                                </button>
                            </div>
                        </div>

                        {{-- Panel 1: Cash / Bank Accounts --}}
                        <div id="panel_accounts" class="mb-2">
                            <div id="rvWrapper" class="row g-2">
                                @foreach ($accounts as $acc)
                                    @php
                                        $accTitleLower = strtolower($acc->title);
                                    @endphp
                                    <div class="col-md-6 col-12">
                                        <div class="pay-account-card d-flex align-items-center justify-content-between p-2 rounded-3 border transition-all" style="background: #ffffff; border: 1px solid #E2E8F0;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="acc-icon-badge flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle" style="width: 36px; height: 36px; background: #F1F5F9;">
                                                    @if(str_contains($accTitleLower, 'cash'))
                                                        <span class="fs-5">💵</span>
                                                    @elseif(str_contains($accTitleLower, 'bank'))
                                                        <i class="fas fa-university text-primary fs-6"></i>
                                                    @elseif(str_contains($accTitleLower, 'card'))
                                                        <i class="fas fa-credit-card text-info fs-6"></i>
                                                    @elseif(str_contains($accTitleLower, 'easy') || str_contains($accTitleLower, 'paisa'))
                                                        <span class="fw-bold text-success fs-5" style="font-family: sans-serif;">e</span>
                                                    @elseif(str_contains($accTitleLower, 'jazz'))
                                                        <span class="badge bg-danger rounded-circle p-1" style="font-size: 9px;">Jazz</span>
                                                    @else
                                                        <i class="fas fa-ellipsis-h text-secondary fs-6"></i>
                                                    @endif
                                                </div>
                                                <div class="lh-1">
                                                    <span class="fw-bold fs-6 text-dark d-block">{{ $acc->title }}</span>
                                                </div>
                                            </div>
                                            <div class="rv-row m-0 p-0" style="width: 110px;">
                                                <input type="hidden" class="rv-account" name="receipt_account_id[]" value="{{ $acc->id }}">
                                                <input type="number" step="0.01" class="form-control text-end rv-amount fw-bold" name="receipt_amount[]" placeholder="0.00" style="height: 34px; font-size: 13.5px; border-radius: 6px;">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Panel 2: Dedicated Cheque Payment Details --}}
                        <div id="panel_cheque" class="p-3 rounded-3 border transition-all mb-2 shadow-sm" style="display: none; background: #FFFDF5; border: 1.5px solid #F59E0B !important;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle" style="width: 34px; height: 34px; background: #FEF3C7; color: #D97706;">
                                        <i class="fas fa-money-check-alt"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark d-block" style="font-size: 13.5px;">Cheque Payment Details</span>
                                        <small class="text-muted" style="font-size: 11px;">Customer se cheque receive karne ke liye yeh fields fill karein</small>
                                    </div>
                                </div>
                                <span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-hand-holding-usd me-1"></i> Cheque In-Hand</span>
                            </div>

                            <div class="row g-2 pt-2 border-top">
                                {{-- Cheque Amount --}}
                                <div class="col-md-6 col-12">
                                    <label class="form-label mb-1 fw-bold text-dark" style="font-size: 12px;">Cheque Amount (Rs.) <span class="text-danger">*</span></label>
                                    <div class="rv-row m-0 p-0">
                                        <input type="hidden" class="rv-account" name="receipt_account_id[]" value="{{ $chequeAccount->id ?? '' }}">
                                        <input type="number" step="0.01" class="form-control rv-amount fw-bold text-end" id="sale_cheque_amount" name="receipt_amount[]" placeholder="0.00" value="{{ isset($existingCheque) ? $existingCheque->amount : '' }}" style="height: 36px; font-size: 14px; border: 1.5px solid #F59E0B; background: #ffffff;">
                                    </div>
                                </div>

                                {{-- Cheque Number --}}
                                <div class="col-md-6 col-12">
                                    <label class="form-label mb-1 fw-bold text-dark" style="font-size: 12px;">Cheque Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="sale_cheque_no" id="sale_cheque_no" placeholder="e.g. 1029384" value="{{ isset($existingCheque) ? $existingCheque->cheque_no : '' }}" style="height: 36px; font-size: 13px;">
                                </div>

                                {{-- Customer Bank Name --}}
                                <div class="col-md-6 col-12">
                                    <label class="form-label mb-1 fw-bold text-dark" style="font-size: 12px;">Customer Bank Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="sale_cheque_bank" id="sale_cheque_bank" placeholder="e.g. Meezan Bank, HBL, MCB" value="{{ isset($existingCheque) ? $existingCheque->bank_name : '' }}" style="height: 36px; font-size: 13px;">
                                </div>

                                {{-- Cheque / Due Date --}}
                                <div class="col-md-6 col-12">
                                    <label class="form-label mb-1 fw-bold text-dark" style="font-size: 12px;">Cheque / Due Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="sale_cheque_date" id="sale_cheque_date" value="{{ isset($existingCheque) && $existingCheque->cheque_date ? $existingCheque->cheque_date->format('Y-m-d') : date('Y-m-d') }}" style="height: 36px; font-size: 13px;">
                                    <small class="text-muted" style="font-size: 10.5px;">Jis din bank mein lagana / cash hona hai</small>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 border-top mt-3">
                            <div class="checkout-extra-fields mb-2">
                                {{-- Sales Officer / Person --}}
                                <div class="wrapper-officer">
                                    <label class="field-label mb-1" for="salesOfficerSelect" style="font-size: 11px;">
                                        <i class="fas fa-user-tie text-primary me-1"></i> Sales Officer
                                    </label>
                                    <select class="form-select form-select-sm" name="sales_officer_id" id="salesOfficerSelect" style="height: 34px; font-size: 12px;">
                                        <option value="">Choose Sales Officer...</option>
                                        @if(isset($salesOfficers) && count($salesOfficers) > 0)
                                            @foreach($salesOfficers as $so)
                                                <option value="{{ $so->id }}" {{ (isset($sale) && $sale->sales_officer_id == $so->id) ? 'selected' : '' }}>
                                                    {{ $so->name }} {{ $so->mobile ? '(' . $so->mobile . ')' : '' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                {{-- PO Number (Customer Mail Order / PO) --}}
                                <div class="wrapper-po-no">
                                    <label class="field-label mb-1" for="inputPoNo" style="font-size: 11px;">
                                        <i class="fas fa-receipt text-secondary me-1"></i> PO # (Mail Order)
                                    </label>
                                    <input type="text" class="form-control form-control-sm" name="po_no" id="inputPoNo" placeholder="e.g. PO-786" value="{{ $sale->po_no ?? '' }}" style="height: 34px;">
                                </div>

                                {{-- PO Date --}}
                                <div class="wrapper-po-date">
                                    <label class="field-label mb-1" for="inputPoDate" style="font-size: 11px;">
                                        <i class="fas fa-calendar-alt text-secondary me-1"></i> PO Date
                                    </label>
                                    <input type="date" class="form-control form-control-sm" name="po_date" id="inputPoDate" value="{{ isset($sale) && $sale->po_date ? \Carbon\Carbon::parse($sale->po_date)->format('Y-m-d') : '' }}" style="height: 34px;">
                                </div>

                                {{-- Reference / Remarks --}}
                                <div class="wrapper-reference">
                                    <label class="field-label mb-1" for="remarks" style="font-size: 11px;">Reference / Remarks</label>
                                    <input type="text" class="form-control form-control-sm" name="reference" id="remarks" placeholder="Optional..." value="{{ $sale->reference ?? '' }}" style="height: 34px;">
                                </div>

                                {{-- Credit Days --}}
                                <div class="wrapper-credit-days">
                                    <label class="field-label mb-1" for="creditDaysInput" style="font-size: 11px;">Credit Days</label>
                                    <input type="number" class="form-control form-control-sm text-center" id="creditDaysInput" name="credit_days" placeholder="0" min="0" value="{{ $sale->credit_days ?? '0' }}" style="height: 34px;">
                                </div>
                            </div>
                                                        @php
                                $allTerms = isset($termsAndConditions) ? $termsAndConditions : \App\Models\TermsAndCondition::active()->ordered()->get();
                                $savedTerms = (isset($sale) && !is_null($sale->selected_terms))
                                    ? (is_array($sale->selected_terms) ? $sale->selected_terms : (json_decode($sale->selected_terms, true) ?? []))
                                    : null;
                            @endphp
                            @if($allTerms->count() > 0)
                                <input type="hidden" name="terms_submitted" value="1">
                                <div class="mt-3 pt-2 border-top">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="field-label mb-0 fw-bold text-dark" style="font-size: 12px;">
                                            <i class="fas fa-file-contract text-primary me-1"></i> Print Terms & Conditions on Invoice
                                        </label>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 10px;" id="selectAllTermsBtn">Select All</button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size: 10px;" id="clearAllTermsBtn">Clear</button>
                                        </div>
                                    </div>
                                    <div class="p-2 rounded bg-light border" style="max-height: 180px; overflow-y: auto;">
                                        @foreach($allTerms as $term)
                                            @php
                                                $isChecked = is_null($savedTerms) ? true : in_array($term->id, $savedTerms);
                                            @endphp
                                            <div class="form-check form-check-inline d-flex align-items-start mb-2 pb-1 border-bottom">
                                                <input class="form-check-input me-2 mt-1 term-checkbox" type="checkbox" name="selected_terms[]" value="{{ $term->id }}" id="term_check_{{ $term->id }}" {{ $isChecked ? 'checked' : '' }}>
                                                <label class="form-check-label w-100" for="term_check_{{ $term->id }}" style="cursor: pointer; font-size: 11px;">
                                                    <strong class="text-dark d-block">{{ $term->heading }}</strong>
                                                    <span class="text-muted" style="font-size: 10.5px; line-height: 1.3; display: block;">{{ Str::limit($term->paragraph, 90) }}</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <small class="text-muted" style="font-size: 10px;">Only checked terms will be printed on this invoice/quotation.</small>
                                </div>
                            @endif
                            <div class="change-row mt-2" id="changeAccountRow" style="display:none;">
                                <span class="change-label me-2 fw-semibold text-muted small">
                                    <i class="fas fa-exchange-alt me-1"></i> Change Account
                                </span>
                                <select class="form-select form-select-sm d-inline-block" name="change_account_id" id="changeAccountId" style="width: auto;">
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}" {{ str_contains(strtolower($acc->title), 'cash') ? 'selected' : '' }}>{{ $acc->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Order Summary --}}
                <div class="col-lg-6">
                    <div class="sale-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="card-title d-flex align-items-center gap-2" style="font-size: 15px;">
                                <i class="fas fa-file-alt text-primary"></i> Order Summary
                            </span>
                        </div>

                        <div>
                            <div class="s-row row-summary-gross">
                                <span class="s-label">Subtotal (Gross)</span>
                                <span class="s-val" id="tGross">0.00</span>
                            </div>
                            <div class="s-row row-summary-line-discount">
                                <span class="s-label">Line Discount</span>
                                <span class="s-val" id="tLineDisc">0.00</span>
                            </div>
                            <div class="s-row row-summary-exclusive-gst">
                                <span class="s-label fw-bold text-dark">Exclusive GST Amount</span>
                                <span class="s-val fw-bold text-dark" id="tExclusiveGst">0.00</span>
                            </div>
                            <div class="s-row row-summary-sales-tax">
                                <span class="s-label text-success">Sales Tax (@<span id="labelStRate">{{ (float)($saleConfig['default_st_rate'] ?? 18) }}%</span>)</span>
                                <span class="s-val text-success fw-bold" id="tSalesTax">0.00</span>
                            </div>
                            <div class="s-row row-summary-further-tax">
                                <span class="s-label text-primary">Further Tax (@<span id="labelFtRate">{{ (float)($saleConfig['default_ft_rate'] ?? 3) }}%</span>)</span>
                                <span class="s-val text-primary fw-bold" id="tFurtherTax">0.00</span>
                            </div>
                            <div class="s-row row-summary-overall-discount">
                                <span class="s-label">Discount (Rs)</span>
                                <div class="input-group input-group-sm discount-input">
                                    <input type="number" class="form-control text-end" id="walkinDiscountRs" value="0" placeholder="0">
                                    <span class="input-group-text">Rs</span>
                                </div>
                            </div>
                            <div class="s-row row-summary-freight">
                                <span class="s-label">Freight</span>
                                <div class="input-group input-group-sm discount-input">
                                    <button type="button" class="btn btn-outline-secondary" id="freightTypeToggle" tabindex="-1" style="width: 34px; border-radius: 6px 0 0 6px; font-weight: bold; font-size: 13px; background: #F8FAFC; color: #64748b; border-color: var(--pos-border);">+</button>
                                    <input type="hidden" name="freight_type" id="freightType" value="add">
                                    <input type="number" class="form-control text-end" id="freightCharges" name="freight_charges" value="0" placeholder="0">
                                    <span class="input-group-text">Rs</span>
                                </div>
                            </div>
                            <div class="s-row net my-2 p-2 rounded-3" style="background: #EFF6FF; border: 1px solid #BFDBFE;">
                                <span class="net-label fw-bold text-primary" style="font-size: 14px;">Net Total</span>
                                <span class="net-val fw-extrabold text-primary" id="tSub" style="font-size: 18px;">0.00</span>
                                <span id="walkinNetTotal" class="d-none">0.00</span>
                            </div>
                            <div class="s-row">
                                <span class="s-label">Total Paid</span>
                                <span class="paid-val text-success fw-bold" id="receiptsTotal">0.00</span>
                                <span id="receiptsTotalBadge" style="display:none;">0.00</span>
                                <span id="bottomPaymentsTotal" class="d-none">0.00</span>
                            </div>
                            <div class="s-row">
                                <span class="s-label">Remaining</span>
                                <span class="s-val text-danger fw-bold" id="tPayable">0.00</span>
                            </div>
                            <div class="s-row">
                                <span class="s-label">Change</span>
                                <span class="change-val text-success fw-bold" id="walkinChange">-0.00</span>
                                <span id="bottomChangeVal" class="d-none">-0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================ STICKY BOTTOM ACTION BAR ============================ --}}
            <div class="sale-bottom-bar">
                <div class="bb-left">
                    <span>Items: <b id="footerItemCount">0</b></span>
                    <span>Total: <b>Rs <span id="footerTotal">0.00</span></b></span>
                    <span>Paid: <b class="text-success">Rs <span id="footerPaid">0.00</span></b></span>
                </div>

                <div class="bb-secondary">
                    <button type="button" class="btn-ghost" id="btnPrint"><i class="fas fa-print"></i> A4 Print</button>
                    <button type="button" class="btn-ghost" id="btnEstimate"><i class="fas fa-file-invoice"></i> Estimate</button>
                    <button type="button" class="btn-ghost" id="btnPrint2"><i class="fas fa-receipt"></i> Thermal</button>
                    <button type="button" class="btn-ghost" id="btnDcThermal"><i class="fas fa-truck"></i> DC</button>
                    <button type="button" class="d-none" id="btnPosted">Sale</button>
                </div>

                <div class="bb-actions">
                    <a href="{{ route('sale.index') }}" class="btn btn-outline-secondary px-3">Cancel</a>
                    <button type="button" class="btn btn-outline-warning px-3" id="btnQuotation">
                        <i class="fas fa-file-alt me-1"></i> Quotation
                    </button>
                    <button type="button" class="btn btn-outline-primary px-3" id="btnSave">
                        <i class="fas fa-save me-1"></i> Booking
                    </button>
                    <button type="button" class="btn btn-primary btn-save-print px-3" id="btnSaveAndComplete">
                        <i class="fas fa-print me-1"></i> Save &amp; Print Invoice
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Quick Products Offcanvas Drawer -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="quickProductsOffcanvas" style="width: 360px;">
        <div class="offcanvas-header bg-light py-2 border-bottom">
            <h6 class="offcanvas-title fw-bold text-dark mb-0"><i class="fas fa-th text-primary me-2"></i>Quick Products Panel</h6>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-primary fw-bold" id="btnNewProductFromDrawer">
                    <i class="fas fa-plus me-1"></i> New Product
                </button>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
        </div>
        <div class="offcanvas-body p-2">
            <div class="input-group input-group-sm mb-2">
                <input type="text" class="form-control" id="sidebarProductSearch" placeholder="Search product by name, barcode or SKU...">
                <button class="btn btn-primary px-2" type="button"><i class="fas fa-search"></i></button>
            </div>
            <div class="overflow-auto pe-1" id="sidebarProductContainer" style="max-height: calc(100vh - 120px);">
                @if(isset($recentProducts) && count($recentProducts) > 0)
                    @foreach($recentProducts as $prod)
                        <div class="pos-product-card">
                            <div class="pos-product-img">
                                <i class="fas fa-box text-secondary fs-5"></i>
                            </div>
                            <div class="pos-product-info">
                                <div class="pos-product-name" title="{{ $prod->item_name }}">{{ $prod->item_name }}</div>
                                <div class="pos-product-sub">
                                    <span class="badge-stock-green">{{ $prod->total_pieces ?? 0 }} Pcs</span> Stock
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="pos-product-price">{{ number_format($prod->retail_price ?? 0, 2) }}</div>
                                <button type="button" class="pos-product-add-btn add-product-direct-btn" data-id="{{ $prod->id }}" title="Add to Grid"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" role="dialog" aria-labelledby="addCustomerModalLabel" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background: #2563EB !important; padding: 14px 18px;">
                    <h5 class="modal-title font-weight-bold fw-bold text-white mb-0" id="addCustomerModalLabel" style="font-size: 1rem;">
                        <i class="fas fa-user-plus me-2 mr-2"></i>Quick Customer
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; line-height: 1; opacity: 0.9; cursor: pointer;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="ajaxAddCustomerForm" autocomplete="off">
                        @csrf
                        <input type="hidden" name="party_type" value="customer">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label font-weight-bold fw-bold">Customer Type <span class="text-danger">*</span></label>
                                <select class="form-control form-select" name="customer_type" id="modalCustomerType" required>
                                    @foreach(\App\Models\CustomerType::orderBy('name')->get() as $type)
                                        <option value="{{ $type->name }}" {{ $type->name === 'Main Customer' ? 'selected' : '' }}>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-weight-bold fw-bold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="customer_name" id="modalCustomerName" required placeholder="Customer Name">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-weight-bold fw-bold">Mobile</label>
                                <input type="text" class="form-control" name="mobile" placeholder="0300-1234567">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-weight-bold fw-bold">Opening Balance</label>
                                <input type="number" step="0.01" class="form-control" name="opening_balance" value="0">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-weight-bold fw-bold">Credit Limit</label>
                                <input type="number" step="0.01" class="form-control" name="balance_range" placeholder="0 = Unlimited" value="0">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-weight-bold fw-bold">NTN Number</label>
                                <input type="text" class="form-control" name="ntn_number" placeholder="NTN (e.g. 1234567-8)">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-weight-bold fw-bold">STRN Number</label>
                                <input type="text" class="form-control" name="strn_number" placeholder="STRN (FT Exempt)">
                            </div>
                                                        <div class="col-12 col-md-4">
                                <label class="form-label font-weight-bold fw-bold">Province</label>
                                <select class="form-select form-control" name="province">
                                    <option value="">-- Select Province --</option>
                                    @foreach(app(\App\Services\LocationService::class)->getProvinces() as $p)
                                        <option value="{{ $p }}">{{ $p }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label font-weight-bold fw-bold">City</label>
                                <div class="input-group">
                                    <select class="form-select form-control" name="city">
                                        <option value="">-- Select City --</option>
                                        @foreach(app(\App\Services\LocationService::class)->getCities() as $c)
                                            <option value="{{ $c }}">{{ $c }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-primary btn-quick-add-city px-2" title="Add New City">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label font-weight-bold fw-bold">Area</label>
                                <div class="input-group">
                                    <select class="form-select form-control" name="area">
                                        <option value="">-- Select Area --</option>
                                        @foreach(app(\App\Services\LocationService::class)->getAreas() as $a)
                                            <option value="{{ $a }}">{{ $a }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-primary btn-quick-add-area px-2" title="Add New Area">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
<div class="col-12">
                                <label class="form-label font-weight-bold fw-bold">Address</label>
                                <input type="text" class="form-control" name="address" placeholder="Address">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm fw-bold" id="btnSaveAjaxCustomer">
                        <i class="fas fa-save me-1 mr-1"></i> Save Customer
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Build Product Modal --}}
    @include('admin_panel.partials.quick_build_product_modal')
@endsection

@section('js')
    <script>
    window.saleScreenConfig = @json($saleConfig);
</script>
@include('admin_panel.sale.scripts.shared_logic')

    <script>
        $(document).ready(function() {
        // Sync Warehouse Selection across all item rows
        $(document).on('change', '#headerWarehouseSelect', function() {
            const selectedWh = $(this).val();
            $('.sales-table tbody tr').each(function() {
                $(this).find('.warehouse').val(selectedWh);
            });
        });
    // Terms Select All / Clear Handlers
    $(document).on('click', '#selectAllTermsBtn', function() {
        $('.term-checkbox').prop('checked', true);
    });
    $(document).on('click', '#clearAllTermsBtn', function() {
        $('.term-checkbox').prop('checked', false);
    });
            // --- Initial Setup ---
            $('#salesTableBody tr').each(function() {
                initProductSelect2($(this).find('.product'));
            });
            if ($('#salesTableBody tr').length === 0) {
                addNewRow();
            }
            updateGrandTotals();
            refreshPostedState();

            // --- Check if URL is for Booking Flow ---
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('type') === 'booking') {
                $('.header-text').html('<i class="fas fa-bookmark text-primary me-2"></i>Add Booking');
                $('#action').val('booking');
                $('#btnPosted').addClass('d-none');
                $('#btnHeaderPosted').addClass('d-none');
            }
            if (urlParams.get('type') === 'quotation') {
                $('.header-text').html('<i class="fas fa-file-alt text-warning me-2"></i>Add Quotation');
                $('#action').val('quotation');
                $('#btnPosted').addClass('d-none');
                $('#btnHeaderPosted').addClass('d-none');
                $('#btnSave').addClass('d-none');
                $('#btnHeaderSaveDraft').addClass('d-none');
            }

            // ============================================================
            // CUSTOMER SELECT2 (Name or Code)
            // ============================================================
            function getPartyType() {
                return $('#partyTypeSelect').val() || 'Main Customer';
            }

            window.initCustomerSelect2 = function() {
                try {
                    if ($('#customerSelect').hasClass('select2-hidden-accessible')) {
                        $('#customerSelect').select2('destroy');
                    }
                } catch(e) {}

                $('#customerSelect').select2({
                    placeholder: 'Search by Name, Code, or Mobile...',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(document.body),
                    language: {
                        noResults: function() {
                            return '<div>No customer found. <a href="javascript:void(0)" class="btn btn-sm btn-outline-primary py-0 px-2 mt-1 btn-open-customer-modal" style="font-size:0.75rem;"><i class="fas fa-user-plus"></i> Quick Add Customer</a></div>';
                        }
                    },
                    escapeMarkup: function(markup) {
                        return markup;
                    }
                });
            };

            initCustomerSelect2();

            $(document).on('select2:open', '#customerSelect', function() {
                setTimeout(function() {
                    const searchInput = document.querySelector('.select2-container--open .select2-search__field');
                    if (searchInput) {
                        searchInput.focus();
                    }
                }, 50);
            });

            // Set initial visibility state of Customer Select / Walk-in input
            $('#partyTypeSelect').trigger('change');

            // Party type change → only reset customer if switching to Walk-in
            $(document).on('change', '#partyTypeSelect', function() {
                if ($(this).val() === 'Walking Customer') {
                    $('#customerSelect').val(null).trigger('change');
                    clearCustomerInfo();
                }
            });

            // Customer selected → load details
            $(document).on('change', '#customerSelect', function() {
                const id = $(this).val();
                if (!id) {
                    clearCustomerInfo();
                    if (typeof updateGrandTotals === 'function') updateGrandTotals();
                    return;
                }

                const $opt = $(this).find('option:selected');
                if ($opt.length && $opt.attr('data-mobile') !== undefined) {
                    const addr = $opt.attr('data-address') || '';
                    const mob = $opt.attr('data-mobile') || '';
                    const prev = parseFloat($opt.attr('data-prev') || 0);
                    const range = parseFloat($opt.attr('data-range') || 0);
                    const code = $opt.attr('data-code') || '';
                    const name = $opt.text();

                    $('#address').val(addr);
                    $('#tel').val(mob);
                    $('#previousBalance').val(prev.toFixed(2));
                    $('#rangeBalance').val(range.toFixed(2));

                    $('#ci_code').text(code || '—');
                    $('#ci_name').text(name);
                    $('#ci_mobile').text(mob || '—');
                    $('#ci_address').text(addr || '—');
                    $('#ci_prev_bal').text(prev.toFixed(2));
                    $('#ci_range_bal').text(range.toFixed(2));
                    $('#cc_customer_name').text(name);

                    if (typeof window.updateCustomerDisplayCards === 'function') {
                        window.updateCustomerDisplayCards({
                            code: code,
                            name: name,
                            mobile: mob,
                            address: addr,
                            prev_balance: prev,
                            credit_limit: range
                        });
                    }
                    if (typeof window.populateCustomerModalDetails === 'function') {
                        window.populateCustomerModalDetails();
                    }
                }

                $.get("{{ url('sale/customers') }}/" + id + "?t=" + new Date().getTime(), function(d) {
                    const addr = d.address || '';
                    const mob = d.mobile || '';
                    const prev = parseFloat(d.previous_balance || 0);
                    const range = parseFloat(d.balance_range || 0);
                    const code = d.customer_id || '—';
                    const name = d.customer_name || '—';

                    $('#address').val(addr);
                    $('#tel').val(mob);
                    $('#previousBalance').val(prev.toFixed(2));
                    $('#rangeBalance').val(range.toFixed(2));

                    $('#ci_code').text(code);
                    $('#ci_name').text(name);
                    $('#ci_mobile').text(mob || '—');
                    $('#ci_address').text(addr || '—');
                    $('#ci_prev_bal').text(prev.toFixed(2));
                    $('#ci_range_bal').text(range.toFixed(2));
                    $('#cc_customer_name').text(name);

                    let officerId = d.sales_officer_id || $('#customerSelect').find(':selected').data('sales-officer');
                    if (officerId) {
                        $('#salesOfficerSelect').val(officerId);
                    }

                    if (typeof window.updateCustomerDisplayCards === 'function') {
                        window.updateCustomerDisplayCards({
                            code: code,
                            name: name,
                            mobile: mob,
                            address: addr,
                            prev_balance: prev,
                            credit_limit: range
                        });
                    }
                    if (typeof window.populateCustomerModalDetails === 'function') {
                        window.populateCustomerModalDetails(d);
                    }

                    if (typeof window.applyCustomerTaxStatus === 'function') {
                        window.applyCustomerTaxStatus(d, true);
                    } else if (typeof updateGrandTotals === 'function') {
                        updateGrandTotals();
                    }
                });
            });

            // Customer cleared
            $('#customerSelect').on('select2:clear', function() {
                clearCustomerInfo();
                if (typeof updateGrandTotals === 'function') updateGrandTotals();
            });

            function clearCustomerInfo() {
                $('#address, #tel').val('');
                $('#previousBalance, #rangeBalance').val('0');
                $('#ci_code, #ci_name, #ci_mobile, #ci_address').text('—');
                $('#ci_prev_bal, #ci_range_bal').text('0.00');
                $('#salesOfficerSelect').val('');
                if (typeof window.updateCustomerDisplayCards === 'function') {
                    window.updateCustomerDisplayCards(null);
                }
                if (typeof window.populateCustomerModalDetails === 'function') {
                    window.populateCustomerModalDetails(null);
                }
                if (typeof window.applyCustomerTaxStatus === 'function') {
                    window.applyCustomerTaxStatus(null, true);
                }
            }

            $('#clearCustomerData').on('click', function() {
                $('#customerSelect').val(null).trigger('change');
                clearCustomerInfo();
                if (typeof updateGrandTotals === 'function') updateGrandTotals();
            });

            $('#btnPrint').on('click', function() {
                ensureSaved().then(id => window.open('{{ url('sales') }}/' + id + '/invoice', '_blank'));
            });
            $('#btnEstimate').on('click', function() {
                ensureSaved().then(id => window.open('{{ url('sales') }}/' + id + '/invoice?type=estimate', '_blank'));
            });
            $('#btnPrint2').on('click', function() {
                ensureSaved().then(id => window.open('{{ url('sales') }}/' + id + '/recepit', '_blank'));
            });
            $('#btnDcThermal').on('click', function() {
                ensureSaved().then(id => window.open('{{ url('sales') }}/' + id + '/dc-thermal', '_blank'));
            });

            // ══════════════════════════════════════════════════════════════
            // QUICK CUSTOMER MODAL LOGIC & EVENT HANDLERS (INSTANT 0ms LOAD)
            // ══════════════════════════════════════════════════════════════
            window.selectCreatedCustomer = function(c) {
                if (!c || !c.id) return;

                // 1. Ensure Walk-in mode is turned OFF and Customer mode is ON
                $('#is_walkin').val('0');
                let cType = c.customer_type || 'Main Customer';
                $('#partyTypeSelect').val(cType);
                $('#btnTypeCustomer').addClass('btn-primary active text-white').removeClass('btn-outline-primary');
                $('#btnTypeWalkin').removeClass('btn-primary active text-white').addClass('btn-outline-primary');
                $('#walkinNameInput').addClass('d-none');
                $('#customerSelect').removeClass('d-none');
                $('#receiptVouchersSection').show();
                $('#totalsSection').removeClass('col-lg-12').addClass('col-lg-5');
                $('#totalsWalkinView').addClass('d-none').removeClass('d-flex');
                $('#totalsCustomerView').removeClass('d-none').addClass('d-flex');
                if ($('#receiptVouchersSection .card-body').length && $('#rvWrapper').length) {
                    $('#rvWrapper').appendTo('#receiptVouchersSection .card-body');
                }

                // 2. Format customer attributes
                let cid = String(c.id);
                let code = c.customer_id || ('CUST-' + String(cid).padStart(4, '0'));
                let name = c.customer_name || ('Customer #' + cid);
                let mobile = c.mobile || '';
                let address = c.address || '';
                let prevBal = (c.previous_balance !== undefined && c.previous_balance !== null) 
                    ? parseFloat(c.previous_balance) 
                    : parseFloat(c.opening_balance || 0);
                let creditLimit = parseFloat(c.balance_range || 0);
                let salesOfficer = c.sales_officer_id || '';
                let displayText = (code ? code + ' — ' : '') + name + (mobile ? ' (' + mobile + ')' : '');

                // 3. Immediately set hidden fields & UI text
                $('#address').val(address);
                $('#tel').val(mobile);
                $('#previousBalance').val(prevBal.toFixed(2));
                $('#rangeBalance').val(creditLimit.toFixed(2));
                $('#ci_code').text(code || '—');
                $('#ci_name').text(name);
                $('#ci_mobile').text(mobile || '—');
                $('#ci_address').text(address || '—');
                $('#ci_prev_bal').text(prevBal.toFixed(2));
                $('#ci_range_bal').text(creditLimit.toFixed(2));
                $('#cc_customer_name').text(name || displayText);

                if (salesOfficer) {
                    $('#salesOfficerSelect').val(salesOfficer);
                }

                // 4. Update the Select2 dropdown cleanly and standardly
                let $sel = $('#customerSelect');
                let existingOpt = $sel.find("option[value='" + cid + "']");

                if (existingOpt.length) {
                    existingOpt.text(displayText)
                               .attr('data-mobile', mobile)
                               .attr('data-address', address)
                               .attr('data-code', code)
                               .attr('data-prev', prevBal)
                               .attr('data-range', creditLimit)
                               .attr('data-sales-officer', salesOfficer);
                } else {
                    let newOpt = new Option(displayText, cid, true, true);
                    $(newOpt).attr('data-mobile', mobile)
                             .attr('data-address', address)
                             .attr('data-code', code)
                             .attr('data-prev', prevBal)
                             .attr('data-range', creditLimit)
                             .attr('data-sales-officer', salesOfficer);
                    $sel.append(newOpt);
                }

                // Ensure Select2 is initialized if it was not
                if (!$sel.hasClass('select2-hidden-accessible') && typeof initCustomerSelect2 === 'function') {
                    initCustomerSelect2();
                }

                // Ensure customer select and its Select2 container are visible
                $sel.removeClass('d-none');
                $sel.next('.select2-container').removeClass('d-none').show();
                $('#customerInputWrapper .select2-container').removeClass('d-none').show();

                // Trigger change to update Select2 UI and invoke all change listeners
                $sel.val(cid).trigger('change');

                try { $sel.select2('close'); } catch(e) {}
                $('.select2-search__field').val('');
                // 5. Update Tax badges (STRN / NTN / FT)
                if (typeof window.applyCustomerTaxStatus === 'function') {
                    window.applyCustomerTaxStatus(c, true);
                }

                // 6. Update single-line Header Balances & Grand Totals instantly
                if (typeof updateGrandTotals === 'function') {
                    updateGrandTotals();
                }

                // 7. Update Quick Info Badges on screen (Address, Credit Limit)
                if (typeof window.updateCustomerDisplayCards === 'function') {
                    window.updateCustomerDisplayCards({
                        code: code,
                        name: name,
                        mobile: mobile,
                        address: address,
                        prev_balance: prevBal,
                        credit_limit: creditLimit
                    });
                }

                // 8. Update Customer Details Modal
                if (typeof window.populateCustomerModalDetails === 'function') {
                    window.populateCustomerModalDetails(c);
                }
            };

            window.openCustomerModal = function(initialName = '') {
                $('#ajaxAddCustomerForm')[0].reset();
                let currentParty = $('#partyTypeSelect').val() || 'Main Customer';
                if (currentParty === 'Walking Customer') {
                    currentParty = 'Main Customer';
                }
                $('#modalCustomerType').val(currentParty);
                if (initialName && typeof initialName === 'string') {
                    $('#modalCustomerName').val(initialName.trim());
                }
                
                if (typeof $('#addCustomerModal').modal === 'function') {
                    $('#addCustomerModal').modal('show');
                } else if (window.bootstrap && window.bootstrap.Modal) {
                    let m = bootstrap.Modal.getOrCreateInstance(document.getElementById('addCustomerModal'));
                    m.show();
                }

                setTimeout(function() {
                    $('#modalCustomerName').focus();
                }, 400);
            };

            window.closeCustomerModal = function() {
                try {
                    $('#addCustomerModal').modal('hide');
                } catch(e) {}
                if (window.bootstrap && window.bootstrap.Modal) {
                    let m = bootstrap.Modal.getInstance(document.getElementById('addCustomerModal'));
                    if (m) m.hide();
                }
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
            };

            // Explicit click listener on any button with btn-open-customer-modal or #btnOpenAddCustomerModal
            $(document).on('click', '#btnOpenAddCustomerModal, .btn-open-customer-modal', function(e) {
                e.preventDefault();
                let term = '';
                // If clicked from select2 noResults or search box, grab search term
                if ($('.select2-search__field:visible').length) {
                    term = $('.select2-search__field:visible').val();
                    $('#customerSelect').select2('close');
                }
                openCustomerModal(term);
            });

            // Keyboard shortcut (F2 or Alt+C) to open Quick Customer modal
            $(document).on('keydown', function(e) {
                if ((e.key === 'F2' || (e.altKey && (e.key === 'c' || e.key === 'C'))) && !$('#addCustomerModal').is(':visible')) {
                    e.preventDefault();
                    openCustomerModal();
                }
            });

            // Prevent native submit on enter and trigger AJAX save
            $('#ajaxAddCustomerForm').on('submit', function(e) {
                e.preventDefault();
                $('#btnSaveAjaxCustomer').trigger('click');
            });

            // AJAX Customer Submit
            $('#btnSaveAjaxCustomer').on('click', function(e) {
                if (e) e.preventDefault();
                let form = $('#ajaxAddCustomerForm');
                if (!form[0].checkValidity()) {
                    form[0].reportValidity();
                    return;
                }
                
                let btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                
                $.ajax({
                    url: '{{ route('customers.store') }}',
                    type: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    dataType: 'json',
                    success: function(res) {
                        btn.prop('disabled', false).html('<i class="fas fa-save me-1 mr-1"></i> Save Customer');
                        let customerData = res.customer || res.data || res;
                        if ((res.success || res.ok) && customerData && customerData.id) {
                            closeCustomerModal();
                            form[0].reset();
                            selectCreatedCustomer(customerData);
                            if (typeof showAlert === 'function') {
                                showAlert('success', 'Customer added and selected successfully!');
                            }
                        } else {
                            if (typeof showAlert === 'function') {
                                showAlert('error', res.message || 'Failed to save customer.');
                            } else {
                                alert(res.message || 'Failed to save customer.');
                            }
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html('<i class="fas fa-save me-1 mr-1"></i> Save Customer');
                        let msg = 'Error adding customer. Check inputs.';
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.errors) {
                                let errList = Object.values(xhr.responseJSON.errors).flat();
                                msg = errList.join(' ');
                            } else if (xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                        }
                        if (typeof showAlert === 'function') {
                            showAlert('error', msg);
                        } else {
                            alert(msg);
                        }
                    }
                });
            });

            // ══════════════════════════════════════════════════════════════
            // DYNAMIC INVOICE SERIES & PREFIX GENERATOR LOGIC (INSTANT 0ms)
            // ══════════════════════════════════════════════════════════════
            let currentInvoicePrefix = "{{ $activePrefix ?? 'INV' }}";

            window.fetchNextInvoiceNo = fetchNextInvoiceNo;
            window.switchInvoicePrefix = function(prefix) {
                if (!prefix) return;
                currentInvoicePrefix = prefix;
                $('#activePrefixLabel').text(prefix);
                $('#dropdownInvoiceSeriesList a[data-prefix]').removeClass('text-success active bg-light').find('i.fa-check').remove();
                let $target = $(`#dropdownInvoiceSeriesList a[data-prefix="${prefix}"]`);
                if ($target.length) {
                    $target.addClass('text-success active bg-light').prepend('<i class="fas fa-check text-success me-1"></i>');
                }
                fetchNextInvoiceNo(prefix);
            };

            function fetchNextInvoiceNo(prefix) {
                $('#iconRefreshInvoice').addClass('fa-spin');
                $.ajax({
                    url: "{{ route('invoice_series.generate_no') }}",
                    type: "GET",
                    data: { prefix: prefix },
                    success: function(res) {
                        $('#iconRefreshInvoice').removeClass('fa-spin');
                        if (res.invoice_no) {
                            $('#inputInvoiceNo').val(res.invoice_no);
                        }
                    },
                    error: function() {
                        $('#iconRefreshInvoice').removeClass('fa-spin');
                    }
                });
            }

            // Prefix Selection Handler
            $(document).on('click', '#dropdownInvoiceSeriesList a[data-prefix]', function(e) {
                e.preventDefault();
                let prefix = $(this).data('prefix');
                if (!prefix) return;

                currentInvoicePrefix = prefix;
                $('#activePrefixLabel').text(prefix);

                // Update active highlight in dropdown instantly
                $('#dropdownInvoiceSeriesList a[data-prefix]').removeClass('text-success active bg-light').find('i.fa-check').remove();
                $(this).addClass('text-success active bg-light').prepend('<i class="fas fa-check text-success me-1"></i>');

                fetchNextInvoiceNo(prefix);
            });

            // Refresh Invoice No Handler
            $(document).on('click', '#btnRefreshInvoiceNo', function() {
                fetchNextInvoiceNo(currentInvoicePrefix);
            });

            // Open Add Series Modal
            $(document).on('click', '#btnOpenAddSeriesModal', function(e) {
                e.preventDefault();
                $('#modalAddInvoiceSeries').modal('show');
            });

            // Submit Add Series Form via AJAX
            $('#formAddInvoiceSeries').on('submit', function(e) {
                e.preventDefault();
                let formData = $(this).serialize();
                let btn = $('#btnSaveSeries');

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving…');

                $.ajax({
                    url: "{{ route('invoice_series.store') }}",
                    type: "POST",
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save &amp; Select');
                        
                        if (res.success) {
                            $('#modalAddInvoiceSeries').modal('hide');
                            $('#formAddInvoiceSeries')[0].reset();

                            currentInvoicePrefix = res.prefix;
                            $('#activePrefixLabel').text(res.prefix);
                            $('#inputInvoiceNo').val(res.invoice_no);

                            // Update or insert item in dropdown list instantly
                            let existingItem = $(`#dropdownInvoiceSeriesList a[data-prefix="${res.prefix}"]`);
                            if (existingItem.length > 0) {
                                existingItem.data('next', res.series.next_number).data('padding', res.series.padding);
                            } else {
                                let newItemHtml = `<li>
                                    <a class="dropdown-item fw-bold text-success active bg-light" href="#" data-prefix="${res.prefix}" data-next="${res.series.next_number}" data-padding="${res.series.padding}">
                                        <i class="fas fa-check text-success me-1"></i> ${res.prefix} <span class="text-muted small font-monospace">(${res.series.padding}d)</span>
                                    </a>
                                </li>`;
                                $('#dropdownInvoiceSeriesList li:has(hr)').before(newItemHtml);
                            }

                            // Update active highlight state
                            $('#dropdownInvoiceSeriesList a[data-prefix]').removeClass('text-success active bg-light').find('i.fa-check').remove();
                            $(`#dropdownInvoiceSeriesList a[data-prefix="${res.prefix}"]`).addClass('text-success active bg-light').prepend('<i class="fas fa-check text-success me-1"></i>');

                            if (typeof showAlert === 'function') {
                                showAlert('success', res.message);
                            } else if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'success', title: 'Saved!', text: res.message, timer: 1500, showConfirmButton: false });
                            } else {
                                alert(res.message);
                            }
                        }
                    },
                    error: function(err) {
                        btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save &amp; Select');
                        let msg = 'Error saving series. Please check form inputs.';
                        if (err.responseJSON && err.responseJSON.errors) {
                            msg = Object.values(err.responseJSON.errors).flat().join('\n');
                        } else if (err.responseJSON && err.responseJSON.message) {
                            msg = err.responseJSON.message;
                        }
                        
                        if (typeof showAlert === 'function') {
                            showAlert('error', msg);
                        } else if (typeof Swal !== 'undefined') {
                            Swal.fire('Error', msg, 'error');
                        } else {
                            alert(msg);
                        }
                    }
                });
            });
        });
    </script>

    {{-- New Sale UI additions (footer totals sync, header save-draft, global product search) --}}
    <script>
        $(function() {
            // Footer totals sync (display-only mirror of existing values)
            if (typeof window.updateGrandTotals === 'function') {
                var __baseUGT = window.updateGrandTotals;
                window.updateGrandTotals = function() {
                    __baseUGT();
                    if ($('#footerItemCount').length) $('#footerItemCount').text($('#itemsRowCount').text());
                    if ($('#footerTotal').length) $('#footerTotal').text($('#tSub').text());
                    if ($('#footerPaid').length) $('#footerPaid').text($('#receiptsTotal').text());
                };
                window.updateGrandTotals();
            }

            // Header Save Draft -> existing booking/save flow
            $('#btnHeaderSaveDraft').on('click', function() {
                $('#btnSave').trigger('click');
            });
        });
    </script>

    <!-- Modal: Add / Manage Invoice Series -->
    <div class="modal fade" id="modalAddInvoiceSeries" tabindex="-1" aria-labelledby="modalAddInvoiceSeriesLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom bg-light px-3 py-2">
                    <h6 class="modal-title fw-bold text-dark mb-0" id="modalAddInvoiceSeriesLabel">
                        <i class="fas fa-barcode text-success me-1"></i> Add Invoice Series
                    </h6>
                    <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; line-height: 1; cursor: pointer;"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formAddInvoiceSeries">
                    @csrf
                    <div class="modal-body p-3">
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary mb-1">Prefix (e.g., SQ, POS, INV)</label>
                            <input type="text" name="prefix" id="seriesPrefixInput" class="form-control form-control-sm text-uppercase fw-bold" placeholder="e.g. SQ" required style="letter-spacing: 1px;">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary mb-1">Starting Number (Counter)</label>
                            <input type="number" name="next_number" id="seriesNextNumInput" class="form-control form-control-sm fw-bold text-primary" placeholder="e.g. 50" min="1" value="50" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary mb-1">Padding Length (Zero Digits)</label>
                            <select name="padding" id="seriesPaddingSelect" class="form-select form-select-sm fw-bold">
                                <option value="4">4 Digits (e.g., 0050)</option>
                                <option value="6" selected>6 Digits (e.g., 000050)</option>
                                <option value="8">8 Digits (e.g., 00000050)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top p-2 px-3">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm fw-bold px-3" id="btnSaveSeries">
                            <i class="fas fa-save me-1"></i> Save &amp; Select
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Select Product Batch -->
    <div class="modal fade" id="modalSelectBatch" tabindex="-1" aria-labelledby="modalSelectBatchLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom bg-light px-3 py-2">
                    <h6 class="modal-title fw-bold text-dark mb-0" id="modalSelectBatchLabel">
                        <i class="fas fa-layer-group text-primary me-1"></i> Select Batch for Product
                    </h6>
                    <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; line-height: 1; cursor: pointer;"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body p-3">
                    <input type="hidden" id="activeBatchRowIndex" value="">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0 text-center">
                            <thead class="bg-light">
                                <tr>
                                    <th>Batch #</th>
                                    <th>MFG Date</th>
                                    <th>Expiry Date</th>
                                    <th>Avail. Qty</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="batchModalTableBody">
                                <tr>
                                    <td colspan="5" class="text-muted py-3">Loading available batches...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top p-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Select Product Serials / IMEIs or Sell Without Serial -->
    <div class="modal fade" id="modalSelectSerial" tabindex="-1" aria-labelledby="modalSelectSerialLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom bg-light px-3 py-2">
                    <h6 class="modal-title fw-bold text-dark mb-0" id="modalSelectSerialLabel">
                        <i class="fas fa-barcode text-success me-1"></i> Select Serial / IMEI Numbers
                    </h6>
                    <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; line-height: 1; cursor: pointer;"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body p-3">
                    <input type="hidden" id="activeSerialRowIndex" value="">

                    <!-- Stock Overview Bar (Serials vs Non-Serial) -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-4">
                            <div class="p-2 border rounded bg-light text-center">
                                <span class="d-block text-muted small fw-bold text-uppercase" style="font-size:10px;">Total Available Stock</span>
                                <span class="fw-bold fs-6 text-dark" id="modalTotalStockCount">0</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 border rounded bg-light text-center">
                                <span class="d-block text-muted small fw-bold text-uppercase" style="font-size:10px;">With Serial / IMEI</span>
                                <span class="fw-bold fs-6 text-success" id="modalAvailableSerialsCount">0</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 border rounded bg-light text-center border-warning">
                                <span class="d-block text-muted small fw-bold text-uppercase" style="font-size:10px;">Without Serial Stock</span>
                                <span class="fw-bold fs-6 text-warning" id="modalNonSerialStockCount">0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Option to Sell Without Serial -->
                    <div class="alert alert-warning border d-flex align-items-center justify-content-between p-2 mb-3" id="boxSellWithoutSerial">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-boxes fs-5 text-warning"></i>
                            <div>
                                <span class="fw-bold d-block text-dark small">Bagair Serial ke Sale karna chahte hain?</span>
                                <span class="text-muted" style="font-size:11px;">Aap is product ko without serial bhi sale kar sakte hain (<span id="modalNonSerialStockHint" class="fw-bold">0</span> Qty available without serial).</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-warning btn-sm fw-bold px-3 text-dark" id="btnChooseWithoutSerial">
                            <i class="fas fa-box-open me-1"></i> Sell Without Serial
                        </button>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-bold text-secondary small"><i class="fas fa-list-check me-1"></i> Or Choose / Scan Serial Numbers:</span>
                    </div>

                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="serialSearchInput" placeholder="Scan IMEI barcode or search serial number...">
                    </div>
                    <div class="border rounded p-2 overflow-auto" style="max-height: 250px;" id="serialListContainer">
                        <div class="text-muted text-center py-3">Loading available serial numbers...</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                        <span class="small text-muted">Selected IMEIs: <strong id="selectedSerialCount" class="text-primary fs-6">0</strong></span>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top p-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success btn-sm fw-bold px-3" id="btnApplySelectedSerials">
                        <i class="fas fa-check me-1"></i> Apply Selected IMEIs
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Details Modal -->
    <div class="modal fade" id="modalCustomerDetails" tabindex="-1" aria-labelledby="modalCustomerDetailsLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header bg-light border-bottom p-3">
                    <h5 class="modal-title fw-bold text-primary fs-6 d-flex align-items-center gap-2" id="modalCustomerDetailsLabel">
                        <i class="fas fa-user-circle fs-5"></i> Customer Information
                    </h5>
                    <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; line-height: 1; cursor: pointer;"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border">
                                <label class="text-muted fw-bold d-block" style="font-size: 11px;">CUSTOMER CODE</label>
                                <span class="fw-bold text-dark fs-6" id="ci_modal_code">—</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border">
                                <label class="text-muted fw-bold d-block" style="font-size: 11px;">FULL NAME</label>
                                <span class="fw-bold text-dark fs-6" id="ci_modal_name">—</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border">
                                <label class="text-muted fw-bold d-block" style="font-size: 11px;">MOBILE</label>
                                <span class="fw-bold text-dark fs-6" id="ci_modal_mobile">—</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded border" style="background: #EFF6FF; border-color: #BFDBFE !important;">
                                <label class="text-primary fw-bold d-block" style="font-size: 11px;">CREDIT LIMIT</label>
                                <span class="fw-bold text-primary fs-6" id="ci_modal_credit_limit">Unlimited</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-2 rounded bg-light border">
                                <label class="text-muted fw-bold d-block" style="font-size: 11px;">ADDRESS</label>
                                <span class="fw-bold text-dark" id="ci_modal_address" style="font-size: 13.5px;">—</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded border" style="background: #FEF2F2; border-color: #FCA5A5 !important;">
                                <label class="text-danger fw-bold d-block" style="font-size: 11px;">PREV. DUE</label>
                                <span class="fw-bold text-danger fs-6" id="ci_modal_prev">—</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded border" style="background: #F3E8FF; border-color: #D8B4FE !important;">
                                <label class="fw-bold d-block" style="font-size: 11px; color: #9333EA;">CLOSING BALANCE</label>
                                <span class="fw-bold fs-6" id="ci_modal_closing" style="color: #9333EA;">—</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-2 px-3 border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@include('admin_panel.partials.location_selector_script')
@endsection