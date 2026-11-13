@php
    $isQuotation = (($sale->sale_status ?? '') === 'quotation' || !empty($isQuotation));
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($isQuotation ?? ($sale->sale_status === "quotation")) ? "Quotation" : (($isEstimate ?? false) ? "Estimate" : "Invoice") }} - {{ $sale->invoice_no }}</title>

    <link href="{{ asset('assets/vendors/bootstrap5/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendors/font-awesome/css/all.min.css') }}" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-navy: #1e3a5f;
            --primary-dark: #0f172a;
            --slate-800: #1e293b;
            --slate-700: #334155;
            --slate-600: #475569;
            --slate-500: #64748b;
            --slate-400: #94a3b8;
            --slate-300: #cbd5e1;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
            --slate-50: #f8fafc;
            --brand-accent: #0284c7;
            --brand-accent-light: #f0f9ff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Action Buttons - Professional Full-Width Sticky Navbar */
        .print-btn-container {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            background: #ffffff;
            border-bottom: 1px solid #cbd5e1;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
            padding: 8px 24px;
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .print-btn-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .print-btn-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .print-btn-container .btn {
            font-size: 12px;
            font-weight: 700;
            padding: 6px 13px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
            transition: all 0.2s ease;
        }

        .print-btn-container .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }

        .print-btn-container .btn-group {
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
            border-radius: 6px;
            display: inline-flex;
        }

        .print-btn-container .btn-group .btn {
            box-shadow: none !important;
            border-radius: 0 !important;
            transform: none !important;
        }

        .print-btn-container .btn-group > .btn:first-child {
            border-top-left-radius: 6px !important;
            border-bottom-left-radius: 6px !important;
        }

        .print-btn-container .btn-group > .btn:last-child {
            border-top-right-radius: 6px !important;
            border-bottom-right-radius: 6px !important;
        }

        .print-btn-container .dropdown-menu {
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.18) !important;
            border: 1px solid #e2e8f0 !important;
            padding: 6px !important;
            z-index: 1060;
        }

        .print-btn-container .dropdown-item {
            font-size: 11.5px;
            padding: 8px 12px;
            border-radius: 5px;
            transition: background 0.15s ease;
        }

        .print-btn-container .dropdown-item:hover {
            background-color: #f1f5f9;
        }

        @media print {
            body.print-letterhead-mode .company-header-wrapper {
                display: none !important;
            }
        }

        /* A4 Invoice Screen Container */
        .invoice-container {
            max-width: 210mm;
            min-height: auto;
            margin: 20px auto 40px auto;
            background: #ffffff;
            padding: 22px 26px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            border-radius: 6px;
            position: relative;
            color: #0f172a;
        }

        /* Company Brand Header */
        .company-header-wrapper {
            text-align: center;
            padding-bottom: 8px;
            margin-bottom: 6px;
            border-bottom: 1.5px solid var(--primary-navy);
        }

        .company-header-wrapper .company-logo {
            max-height: 52px;
            max-width: 220px;
            width: auto;
            height: auto;
            object-fit: contain;
            margin-bottom: 4px;
        }

        .company-header-wrapper .company-brand {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary-navy);
            letter-spacing: 0.8px;
            text-transform: uppercase;
            line-height: 1.15;
            margin-bottom: 2px;
        }

        .company-header-wrapper .company-meta {
            font-size: 10px;
            color: var(--slate-600);
            font-weight: 500;
            margin: 0 auto;
            max-width: 650px;
            line-height: 1.35;
        }

        /* Invoice Badge - Executive Pill */
        .invoice-title-wrapper {
            text-align: center;
            margin: 6px 0 8px 0;
        }
        .invoice-title-wrapper.d-none {
            display: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .invoice-badge-title {
            display: inline-block;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--primary-navy);
            padding: 3px 24px;
            background: var(--slate-50);
            border: 1.5px solid var(--primary-navy);
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        }

        /* Two-Column Structured Meta Cards - Executive Framing */
        .header-meta-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin-bottom: 8px;
        }

        .header-meta-card {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            background: #ffffff;
            border: 1px solid var(--slate-200);
            border-top: 2.5px solid var(--primary-navy);
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        }

        .header-meta-card .card-head {
            background: var(--slate-50);
            color: var(--primary-navy);
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 4px 10px;
            border-bottom: 1px solid var(--slate-200);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .header-meta-card .card-head i {
            color: var(--brand-accent);
            font-size: 10px;
        }

        .header-meta-card .card-body-meta {
            padding: 6px 10px;
            background: #ffffff;
        }

        /* Pixel-perfect Meta Table inside Cards */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 10px;
            line-height: 1.3;
        }

        .meta-table .lbl {
            width: 125px;
            font-weight: 600;
            color: var(--slate-500);
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.3px;
        }

        .meta-table .col {
            width: 12px;
            font-weight: 600;
            color: var(--slate-400);
            text-align: center;
        }

        .meta-table .val {
            font-weight: 600;
            color: var(--slate-800);
            text-transform: uppercase;
            word-break: break-word;
        }

        .meta-table .val.highlight {
            font-weight: 800;
            color: var(--primary-dark);
            font-size: 10.5px;
        }

        /* Main Invoice Items Table - Executive Accounting Table */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 10px;
        }

        .invoice-table th {
            background-color: var(--slate-50) !important;
            color: var(--slate-700) !important;
            font-weight: 700;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 5px 4px;
            border: 1px solid var(--slate-200);
            border-top: 1.5px solid var(--slate-300);
            border-bottom: 2px solid var(--primary-navy);
            text-align: center;
            vertical-align: middle;
            line-height: 1.25;
        }

        .invoice-table td {
            border: 1px solid var(--slate-200);
            padding: 4.5px 4px;
            vertical-align: middle;
            font-size: 10px;
            color: var(--slate-800);
            line-height: 1.25;
        }

        .invoice-table tbody tr:nth-of-type(even) {
            background-color: #fafbfc;
        }

        .invoice-table tbody tr:hover {
            background-color: #f1f5f9;
        }

        .invoice-table tfoot tr {
            background-color: var(--slate-50) !important;
            color: var(--primary-navy) !important;
        }

        .invoice-table tfoot td {
            background-color: var(--slate-50) !important;
            color: var(--primary-navy) !important;
            border: 1px solid var(--slate-200);
            border-top: 2px solid var(--primary-navy);
            border-bottom: 2px solid var(--primary-navy);
            padding: 5px 4px;
            font-weight: 800;
            font-size: 10px;
        }

        /* Number Column Alignment & Font */
        .text-end {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .text-center {
            text-align: center;
        }

        .text-start {
            text-align: left;
        }

        /* Product Description Details */
        .item-main-title {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 10.5px;
            line-height: 1.25;
        }

        .item-sub-sn {
            font-size: 9px;
            font-weight: 700;
            color: var(--brand-accent);
            margin-top: 1px;
        }

        .item-sub-desc {
            font-size: 8.5px;
            color: var(--slate-500);
            margin-top: 1px;
            line-height: 1.2;
        }

        /* Exchange Returns Table - Soft Rose Header */
        .returns-wrapper {
            margin-top: 8px;
        }

        .returns-header {
            font-size: 9.5px;
            font-weight: 800;
            color: #9f1239;
            background: #ffe4e6;
            border: 1px solid #fecdd3;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .returns-wrapper .invoice-table th {
            background-color: #fff1f2 !important;
            color: #9f1239 !important;
            border: 1px solid #fecdd3;
            border-bottom: 2px solid #e11d48;
        }

        /* Footer & Summary Section */
        .invoice-bottom-grid {
            display: flex;
            gap: 12px;
            margin-top: 8px;
            align-items: flex-start;
        }

        .invoice-bottom-left {
            flex: 1 1 58%;
        }

        .invoice-bottom-right {
            flex: 1 1 42%;
        }

        /* Amount in Words Callout - Executive Accent */
        .words-card {
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-left: 3px solid var(--brand-accent);
            border-radius: 4px;
            padding: 5px 10px;
            margin-bottom: 6px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
        }

        .words-card .words-label {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--brand-accent);
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 1px;
        }

        .words-card .words-label i {
            color: var(--brand-accent);
        }

        .words-card .words-value {
            font-size: 10.5px;
            font-weight: 700;
            color: var(--primary-dark);
            text-transform: capitalize;
            line-height: 1.3;
        }

        /* Terms & Conditions Card - Executive Card */
        .terms-card {
            background: #ffffff;
            border: 1px solid var(--slate-200);
            border-top: 2.5px solid var(--primary-navy);
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 6px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
        }

        .terms-card .terms-title {
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--primary-navy);
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .terms-card .terms-content {
            font-size: 9px;
            color: var(--slate-700);
            line-height: 1.35;
        }

        .terms-card .term-block {
            margin-bottom: 4px;
        }

        .terms-card .term-block:last-child {
            margin-bottom: 0;
        }

        .terms-card .term-heading-label {
            font-size: 9px;
            font-weight: 800;
            color: var(--primary-navy);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1px;
        }

        .terms-card .term-points-wrapper {
            padding-left: 2px;
        }

        .terms-card .term-point-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5px;
            font-size: 8.5px;
            color: var(--slate-600);
            line-height: 1.3;
        }

        .terms-card .term-point-item:last-child {
            margin-bottom: 0;
        }

        .terms-card .term-bullet {
            color: var(--brand-accent);
            font-size: 11px;
            font-weight: 900;
            line-height: 1.1;
            margin-right: 5px;
            flex-shrink: 0;
        }

        .terms-card .term-point-text {
            flex: 1;
        }

        /* Signature Area */
        .signature-section {
            margin-top: 14px;
        }

        .signature-line-box {
            width: 170px;
            border-top: 1.5px solid var(--primary-navy);
            text-align: center;
            padding-top: 4px;
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--primary-navy);
        }

        .printed-timestamp {
            font-size: 8.5px;
            color: var(--slate-500);
            margin-top: 2px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Totals Summary Card - Executive Frame */
        .totals-card {
            border: 1px solid var(--slate-200);
            border-top: 2.5px solid var(--primary-navy);
            border-radius: 5px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        }

        .totals-card .card-head {
            background: var(--slate-50);
            padding: 4px 10px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--primary-navy);
            border-bottom: 1px solid var(--slate-200);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .totals-card .card-head i {
            color: var(--brand-accent);
            font-size: 10px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 3.5px 10px;
            font-size: 10px;
            border-bottom: 1px solid var(--slate-100);
            color: var(--slate-600);
        }

        .totals-table td.lbl {
            font-weight: 600;
        }

        .totals-table td.val {
            text-align: right;
            font-weight: 700;
            color: var(--primary-dark);
            font-variant-numeric: tabular-nums;
        }

        .totals-table tr.total-row td {
            background: var(--slate-50);
            color: var(--primary-navy) !important;
            font-weight: 900;
            font-size: 11.5px;
            padding: 5px 10px;
            border-top: 2px solid var(--primary-navy);
            border-bottom: 2px solid var(--primary-navy);
        }

        .totals-table tr.total-row td.val {
            color: var(--primary-navy) !important;
            font-size: 13px;
        }

        @if(!empty($isQuotation))
        /* Spacious & Elegant Typography for Quotations (Uncongested 5-Column Layout) */
        .header-meta-grid {
            border-spacing: 14px 0;
            margin-bottom: 12px;
        }
        .header-meta-card .card-head {
            padding: 6px 12px;
            font-size: 10.5px;
        }
        .header-meta-card .card-body-meta {
            padding: 8px 12px;
        }
        .meta-table td {
            padding: 3.5px 0;
            font-size: 10.5px;
        }
        .invoice-table {
            margin-top: 10px;
        }
        .invoice-table th {
            padding: 8px 10px;
            font-size: 10.5px;
            letter-spacing: 0.6px;
        }
        .invoice-table td {
            padding: 8px 10px;
            font-size: 11px;
            line-height: 1.35;
        }
        .invoice-table tfoot td {
            padding: 8px 10px;
            font-size: 11px;
        }
        .item-main-title {
            font-size: 11.5px;
            font-weight: 700;
        }
        .invoice-bottom-grid {
            margin-top: 14px;
            gap: 16px;
        }
        .totals-card .card-head {
            padding: 6px 12px;
            font-size: 10.5px;
        }
        .totals-table td {
            padding: 5px 10px;
            font-size: 11px;
        }
        .totals-table tr.total-row td {
            padding: 8px 10px;
            font-size: 12px;
        }
        .totals-table tr.total-row td.val {
            font-size: 13.5px;
        }
        .terms-card {
            padding: 8px 12px;
            margin-bottom: 10px;
        }
        .signature-section {
            margin-top: 18px;
        }
        @endif

        /* Thermal Receipt (Hidden on Screen) */
        .receipt-container {
            display: none;
        }

        /* Screen-specific Utilities */
        @media screen {
            .screen-only {
                display: block;
            }
        }

        /* Default Print: Standard A4 Invoice */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0; /* Hides browser default URL, date, page title & page number */
            }

            html, body {
                width: 100% !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                color: #0f172a !important;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .print-btn-container,
            .no-print {
                display: none !important;
            }

            .screen-only {
                display: block !important;
            }

            .receipt-container {
                display: none !important;
            }

            .invoice-container {
                margin: 0 !important;
                padding: 6mm 8mm !important;
                min-height: auto !important;
                height: auto !important;
                box-shadow: none !important;
                max-width: 100% !important;
                width: 100% !important;
                border-radius: 0 !important;
                border: none !important;
            }

            /* Clean Print Headers & Footers - Zero Heavy Black Ink Blocks */
            .invoice-title-wrapper.d-none {
                display: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .invoice-badge-title {
                background: #f8fafc !important;
                color: #1e3a5f !important;
                border: 1.5px solid #1e3a5f !important;
                padding: 2px 18px !important;
                font-size: 11.5px !important;
            }

            .header-meta-card {
                border: 1px solid #cbd5e1 !important;
                border-top: 2.5px solid #1e3a5f !important;
            }

            .header-meta-card .card-head {
                background-color: #f8fafc !important;
                color: #1e3a5f !important;
                border-bottom: 1px solid #cbd5e1 !important;
                padding: 3px 8px !important;
                font-size: 9.5px !important;
            }

            .header-meta-card .card-body-meta {
                padding: 4px 8px !important;
            }

            .meta-table td {
                padding: 1px 0 !important;
                font-size: 9.5px !important;
            }

            .invoice-table {
                margin-top: 5px !important;
                font-size: 9.5px !important;
            }

            .invoice-table th {
                background-color: #f8fafc !important;
                color: #334155 !important;
                border: 1px solid #cbd5e1 !important;
                border-top: 1.5px solid #cbd5e1 !important;
                border-bottom: 2px solid #1e3a5f !important;
                padding: 4px 3px !important;
                font-size: 9px !important;
            }

            .invoice-table td {
                border: 1px solid #e2e8f0 !important;
                color: #1e293b !important;
                padding: 3.5px 3px !important;
                font-size: 9.5px !important;
            }

            .invoice-table tbody tr:nth-of-type(even) {
                background-color: #fafbfc !important;
            }

            .invoice-table tfoot tr,
            .invoice-table tfoot td {
                background-color: #f8fafc !important;
                color: #1e3a5f !important;
                border: 1px solid #cbd5e1 !important;
                border-top: 2px solid #1e3a5f !important;
                border-bottom: 2px solid #1e3a5f !important;
                padding: 4px 3px !important;
                font-size: 9.5px !important;
            }

            .totals-card {
                border: 1px solid #cbd5e1 !important;
                border-top: 2.5px solid #1e3a5f !important;
            }

            .totals-card .card-head {
                background-color: #f8fafc !important;
                color: #1e3a5f !important;
                border-bottom: 1px solid #cbd5e1 !important;
                padding: 3px 8px !important;
                font-size: 9.5px !important;
            }

            .totals-table td {
                padding: 2.5px 8px !important;
                font-size: 9.5px !important;
            }

            .totals-table tr.total-row td {
                background-color: #f8fafc !important;
                color: #1e3a5f !important;
                border-top: 2px solid #1e3a5f !important;
                border-bottom: 2px solid #1e3a5f !important;
                padding: 4px 8px !important;
                font-size: 11px !important;
            }

            .totals-table tr.total-row td.val {
                color: #1e3a5f !important;
                font-size: 12px !important;
            }

            .words-card {
                border: 1px solid #cbd5e1 !important;
                border-left: 3px solid #0284c7 !important;
                background-color: #f8fafc !important;
                padding: 4px 8px !important;
                margin-bottom: 4px !important;
            }

            .terms-card {
                border: 1px solid #cbd5e1 !important;
                border-top: 2.5px solid #1e3a5f !important;
                padding: 4px 8px !important;
                margin-bottom: 4px !important;
            }

            .signature-section {
                margin-top: 10px !important;
            }

            /* Professional Page Break Rules */
            .header-meta-card,
            .totals-card,
            .words-card,
            .terms-card,
            .signature-section {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            /* Letterhead Mode */
            body.print-letterhead-mode .company-header-wrapper {
                display: none !important;
            }

            /* Thermal Receipt Mode (when explicitly triggered via printReceipt()) */
            body.print-receipt-mode .screen-only {
                display: none !important;
            }

            body.print-receipt-mode .receipt-container {
                display: block !important;
                box-shadow: none !important;
                border: none !important;
                margin: 0 auto !important;
                padding: 3mm 2mm 5mm 2mm !important;
                width: 76mm !important;
                max-width: 76mm !important;
                background: #fff !important;
                color: #000 !important;
                font-size: 11px !important;
                line-height: 1.3 !important;
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
            }

            .receipt-container h1,
            .receipt-container h2,
            .receipt-container h3,
            .receipt-container p {
                margin: 0;
                padding: 0;
            }

            .receipt-container .company-name {
                font-size: 18px !important;
                font-weight: 700 !important;
                text-align: center !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                margin-bottom: 2px !important;
                color: #000 !important;
            }

            .receipt-container .company-info {
                font-size: 11px !important;
                text-align: center !important;
                color: #000 !important;
                font-weight: 400 !important;
                line-height: 1.35 !important;
                margin-bottom: 4px !important;
            }

            .receipt-container .policy-banner {
                background-color: #000 !important;
                color: #fff !important;
                text-align: center !important;
                font-size: 10px !important;
                font-weight: bold !important;
                padding: 3px 5px !important;
                margin: 4px 0 !important;
                border-radius: 3px !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .receipt-container .divider {
                border-top: 1px dashed #000 !important;
                margin: 4px 0 !important;
            }

            .receipt-container .meta-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 2px !important;
                margin-bottom: 2px !important;
                color: #000 !important;
            }

            .receipt-container .meta-row {
                display: flex !important;
                justify-content: space-between !important;
                font-size: 10.5px !important;
                font-weight: 400 !important;
            }

            .receipt-container .items-table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 10.5px !important;
                color: #000 !important;
                margin: 3px 0 !important;
            }

            .receipt-container .items-table th {
                border-top: 1px dashed #000 !important;
                border-bottom: 1px dashed #000 !important;
                padding: 3px 1px !important;
                text-align: left !important;
                font-weight: 700 !important;
                font-size: 10px !important;
                text-transform: uppercase !important;
            }

            .receipt-container .items-table td {
                padding: 3px 1px !important;
                vertical-align: top !important;
                border-bottom: 1px dashed #ccc !important;
                font-size: 10.5px !important;
            }

            .receipt-container .item-name {
                font-weight: 600 !important;
                font-size: 10.5px !important;
                color: #000 !important;
                display: block !important;
            }

            .receipt-container .item-variant {
                font-size: 9.5px !important;
                color: #222 !important;
                font-weight: 400 !important;
                margin-top: 1px !important;
                display: block !important;
            }

            .receipt-container .totals-section {
                font-size: 10.5px !important;
                margin-top: 4px !important;
                color: #000 !important;
            }

            .receipt-container .tot-row {
                display: flex !important;
                justify-content: space-between !important;
                padding: 1.5px 0 !important;
            }

            .receipt-container .tot-row.grand-total {
                font-size: 12px !important;
                font-weight: 800 !important;
                margin: 3px 0 !important;
                padding: 4px 0 !important;
                border-top: 1.5px solid #000 !important;
                border-bottom: 1.5px solid #000 !important;
                color: #000 !important;
            }

            .receipt-container .balance-section {
                font-size: 10.5px !important;
                margin-top: 3px !important;
                border-top: 1px dashed #000 !important;
                padding-top: 3px !important;
            }

            .receipt-container .balance-section .tot-row.closing-bal {
                font-weight: 700 !important;
                background-color: #f8f9fa !important;
                padding: 3px 2px !important;
                border: 1px solid #000 !important;
                margin-top: 2px !important;
            }

            .receipt-container .footer {
                text-align: center !important;
                margin-top: 10px !important;
                color: #000 !important;
            }

            .receipt-container .footer p {
                font-size: 10px !important;
                font-weight: 600 !important;
                margin-bottom: 2px !important;
            }

            .receipt-container .footer .soft-credit {
                font-size: 8.5px !important;
                color: #444 !important;
                margin-top: 2px !important;
                font-weight: 400 !important;
            }
        }
    </style>
</head>

<body>

@php
    $showTaxColumns = (\App\Models\Setting::get('print_tax_breakdown', '1') == '1') && 
        \App\Models\SaleFieldConfiguration::isVisibleInPrint('print_tax_breakdown', true) && 
        (\App\Models\SaleFieldConfiguration::isEnabled('sales_tax', true) || (float)($sale->total_sales_tax ?? 0) > 0);
    $showCustomerBalance = (\App\Models\Setting::get('summary_customer_balance', '1') == '1') &&
        \App\Models\SaleFieldConfiguration::isVisibleInPrint('summary_customer_balance', true) &&
        \App\Models\SaleFieldConfiguration::isEnabled('summary_customer_balance', true);

    // Field-by-field display toggles
    $showInvNo       = \App\Models\Setting::get('inv_show_invoice_no', '1') == '1';
    $showInvDate     = \App\Models\Setting::get('inv_show_invoice_date', '1') == '1';
    $showSalesRef    = \App\Models\Setting::get('inv_show_sales_ref', '1') == '1';
    $showPoDetails   = \App\Models\Setting::get('inv_show_po_details', '1') == '1';
    $showCompTax     = \App\Models\Setting::get('inv_show_comp_tax', '1') == '1';
    $showCompPhone   = \App\Models\Setting::get('inv_show_comp_contact', '1') == '1';

    $showCustAddress = \App\Models\Setting::get('inv_show_cust_address', '1') == '1';
    $showCustLicense = \App\Models\Setting::get('inv_show_cust_license', '1') == '1';
    $showCustContact = \App\Models\Setting::get('inv_show_cust_contact', '1') == '1';
    $showCustTax     = \App\Models\Setting::get('inv_show_cust_tax', '1') == '1';

    $showBatchCol    = \App\Models\Setting::get('inv_show_batch_column', '1') == '1';
    $showDiscCol     = \App\Models\Setting::get('inv_show_disc_column', '1') == '1';
    $showAmountWords = \App\Models\Setting::get('inv_show_amount_in_words', '1') == '1';
    $showHeadingOnSimple = \App\Models\Setting::get('inv_show_heading_on_simple', '0') == '1';

    $isQuotation = ($sale->sale_status === 'quotation' || ($isQuotation ?? false));
    $isEst = ($isEstimate ?? false);

    // Determine if sale is a Warranty Invoice (contains serial numbers / IMEIs or warranty tracking)
    $hasWarranty = false;
    if (request()->query('type') === 'warranty' || request()->query('warranty') == '1') {
        $hasWarranty = true;
    } elseif (request()->query('type') === 'simple' || request()->query('warranty') == '0') {
        $hasWarranty = false;
    } else {
        if (!empty($saleItems)) {
            foreach ($saleItems as $it) {
                $rawSerials = $it['serials'] ?? null;
                if (!empty($rawSerials)) {
                    if (is_array($rawSerials) && count($rawSerials) > 0) {
                        $hasWarranty = true;
                        break;
                    } elseif (is_string($rawSerials)) {
                        $dec = json_decode($rawSerials, true);
                        if (is_array($dec) && count($dec) > 0) {
                            $hasWarranty = true;
                            break;
                        } elseif (strlen(trim($rawSerials)) > 0 && trim($rawSerials) !== '[]') {
                            $hasWarranty = true;
                            break;
                        }
                    }
                }
            }
        }
        if (!$hasWarranty && isset($sale) && $sale->items) {
            foreach ($sale->items as $sItem) {
                if (!empty($sItem->serials)) {
                    $hasWarranty = true;
                    break;
                }
                if ($sItem->product && $sItem->product->tracking_type === 'serial') {
                    $hasWarranty = true;
                    break;
                }
            }
        }
    }

    $badgeTitle = '';
    if ($isQuotation) {
        $badgeTitle = 'QUOTATION';
    } elseif ($isEst) {
        $badgeTitle = 'ESTIMATE';
    } elseif ($hasWarranty) {
        $badgeTitle = 'INVOICE';
    } elseif ($showHeadingOnSimple) {
        $badgeTitle = 'INVOICE';
    } else {
        $badgeTitle = ''; // Simple Sale: heading mein kuch na aye
    }
@endphp

    <!-- Print / Action Buttons - Top Navbar -->
    <div class="print-btn-container no-print">
        <div class="print-btn-left">
            <a href="javascript:void(0)" onclick="handleGoBack()" class="btn btn-secondary" style="background: #334155; border-color: #334155; color: #ffffff;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <span class="fw-bold text-dark d-none d-md-inline" style="font-size: 13px;">
                <i class="fas fa-file-invoice text-primary me-1"></i> {{ $sale->invoice_no }}
            </span>
        </div>

        <div class="print-btn-right">
            <button onclick="printReceipt()" class="btn btn-primary" title="Print 80mm Thermal Receipt">
                <i class="fas fa-receipt"></i> Print Receipt
            </button>

            @if(!$isQuotation && !$isEst)
                <!-- Quick Heading Switcher -->
                <button type="button" class="btn btn-outline-secondary" id="btnToggleInvoiceHeading" onclick="toggleInvoiceHeading()" title="Click to toggle INVOICE heading on/off for this print">
                    <i class="fas fa-heading"></i> Heading: <span id="headingStatusText" class="{{ !empty($badgeTitle) ? 'fw-bold text-success' : 'text-muted' }}">{{ !empty($badgeTitle) ? $badgeTitle : 'None (Simple)' }}</span>
                </button>
            @endif

            <!-- Split Dropdown: Simple Print & Letterhead Print -->
            <div class="btn-group">
                <button type="button" onclick="printA4(false)" class="btn btn-success" title="Simple Print (Company details & header included)">
                    <i class="fas fa-file-invoice"></i> Print (Simple)
                </button>
                <button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" title="Print Options">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" onclick="printA4(false)">
                            <i class="fas fa-file-invoice text-success fa-fw" style="font-size: 14px;"></i>
                            <div>
                                <div class="fw-bold text-dark">Print (Simple)</div>
                                <div class="text-muted" style="font-size: 10px;">Company details & header included</div>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" onclick="printA4(true)">
                            <i class="fas fa-file-alt text-primary fa-fw" style="font-size: 14px;"></i>
                            <div>
                                <div class="fw-bold text-dark">Print (Letterhead)</div>
                                <div class="text-muted" style="font-size: 10px;">Hide header, ~2 in top & bottom margin</div>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Direct 1-Click Letterhead Button -->
            <button type="button" onclick="printA4(true)" class="btn btn-info text-white" title="Letterhead Print (~2 in margin for pre-printed stationary)">
                <i class="fas fa-file-alt"></i> Print (Letterhead)
            </button>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- SCREEN & A4 VIEW -->
    <!-- ========================================== -->
    <div class="invoice-container screen-only">

        <!-- Modern Corporate Header -->
        @if(!($isEstimate ?? false))
            @php
                $companyName = \App\Models\Setting::get('company_name', config('app.name', 'Innot Tech'));
                $companyAddress = \App\Models\Setting::get('company_address');
                $companyPhone = \App\Models\Setting::get('company_phone');
                $companyLogo = \App\Models\Setting::get('company_logo') ?? \App\Models\Setting::get('web_site_logo');
            @endphp

            <div class="company-header-wrapper">
                @if(!empty($companyLogo) && (file_exists(public_path($companyLogo)) || file_exists(base_path($companyLogo))))
                    <div>
                        <img src="{{ asset($companyLogo) }}" alt="{{ $companyName }}" class="company-logo">
                    </div>
                @endif
                <div class="company-brand">{{ $companyName }}</div>
                <div class="company-meta">
                    @if(!empty($companyAddress))
                        <span>{{ $companyAddress }}</span>
                    @endif
                    @if(!empty($companyAddress) && !empty($companyPhone))
                        <span class="mx-1">•</span>
                    @endif
                    @if(!empty($companyPhone))
                        <span class="fw-bold"><i class="fas fa-phone-alt me-1" style="font-size: 10px;"></i>{{ $companyPhone }}</span>
                    @endif
                </div>
            </div>
        @endif

        <!-- Document Title Badge -->
        <div class="invoice-title-wrapper {{ empty($badgeTitle) ? 'd-none' : '' }}" id="invoiceTitleBadgeWrapper">
            <span class="invoice-badge-title" id="invoiceBadgeTitle">{{ $badgeTitle }}</span>
        </div>

        <!-- Customer & Shipper Information Header (Structured 2-Column Cards) -->
        @if(!($isEstimate ?? false))
            @php
                $ntnDef = \App\Models\CustomerFieldDefinition::where('field_key', 'ntn_number')->first();
                $isNtnEnabled = $ntnDef ? ($ntnDef->is_enabled && $ntnDef->visible_in_view && $ntnDef->canView()) : false;

                $strnDef = \App\Models\CustomerFieldDefinition::where('field_key', 'strn_number')->first();
                $isStrnEnabled = $strnDef ? ($strnDef->is_enabled && $strnDef->visible_in_view && $strnDef->canView()) : false;

                $cnicDef = \App\Models\CustomerFieldDefinition::where('field_key', 'cnic')->first();
                $isCnicEnabled = $cnicDef ? ($cnicDef->is_enabled && $cnicDef->visible_in_view && $cnicDef->canView()) : true;

                $relCust = $sale->customer_relation;
                $custLicense = $relCust ? ($relCust->getCustomValue('drug_license_no') ?? $relCust->getCustomValue('license_no') ?? $relCust->license_no ?? $relCust->drug_license_no ?? null) : null;
                $custNtn = ($isNtnEnabled && $relCust) ? ($relCust->getCustomValue('ntn_number') ?? $relCust->ntn_number ?? null) : null;
                $custStrn = ($isStrnEnabled && $relCust) ? ($relCust->getCustomValue('strn_number') ?? $relCust->strn_number ?? null) : null;
                $custCnic = ($isCnicEnabled && $relCust) ? ($relCust->getCustomValue('cnic') ?? $relCust->cnic ?? null) : null;
                $custMobile = $sale->walkin_mobile ?? ($relCust->mobile ?? $relCust->phone ?? null);
                $custContactPerson = $relCust ? ($relCust->contact_person ?? $relCust->getCustomValue('contact_person') ?? null) : null;

                $compNtn = \App\Models\Setting::get('company_ntn', null);
                $compStrn = \App\Models\Setting::get('company_strn', null);
                $compPhone = \App\Models\Setting::get('company_phone', null);

                $salesOfficerName = $sale->salesOfficer->name ?? ($relCust->salesOfficer->name ?? null);
            @endphp

            <div class="header-meta-grid">
                <!-- CUSTOMER INFORMATION CARD -->
                <div class="header-meta-card">
                    <div class="card-head">
                        <i class="fas fa-user-tie"></i> CUSTOMER INFORMATION
                    </div>
                    <div class="card-body-meta">
                        <table class="meta-table">
                            <tr>
                                <td class="lbl">Customer Name</td>
                                <td class="col">:</td>
                                <td class="val highlight">{{ $sale->walkin_name ?? ($relCust->customer_name ?? 'WALKING CUSTOMER') }}</td>
                            </tr>

                            @if($showCustAddress && !empty($relCust->address))
                            <tr>
                                <td class="lbl">Address</td>
                                <td class="col">:</td>
                                <td class="val">{{ $relCust->address }}</td>
                            </tr>
                            @endif

                            @if($showCustLicense && !empty($custLicense))
                            <tr>
                                <td class="lbl">Drug License No</td>
                                <td class="col">:</td>
                                <td class="val">{{ $custLicense }}</td>
                            </tr>
                            @endif

                            @if($showPoDetails && $sale->po_no)
                            <tr>
                                <td class="lbl">Purchase Order No</td>
                                <td class="col">:</td>
                                <td class="val">{{ $sale->po_no }}</td>
                            </tr>
                            @endif

                            @if($showPoDetails && $sale->po_date)
                            <tr>
                                <td class="lbl">Purchase Order Date</td>
                                <td class="col">:</td>
                                <td class="val">{{ \Carbon\Carbon::parse($sale->po_date)->format('d-F-Y') }}</td>
                            </tr>
                            @endif

                            @if($showCustTax)
                                @if(!empty($custNtn) && $isNtnEnabled)
                                <tr>
                                    <td class="lbl">NTN Number</td>
                                    <td class="col">:</td>
                                    <td class="val">{{ $custNtn }}</td>
                                </tr>
                                @endif

                                @if(!empty($custStrn) && $isStrnEnabled)
                                <tr>
                                    <td class="lbl">STRN Number</td>
                                    <td class="col">:</td>
                                    <td class="val">{{ $custStrn }}</td>
                                </tr>
                                @elseif(!empty($custCnic) && $isCnicEnabled)
                                <tr>
                                    <td class="lbl">CNIC Number</td>
                                    <td class="col">:</td>
                                    <td class="val">{{ $custCnic }}</td>
                                </tr>
                                @endif
                            @endif

                            @if($showCustContact && !empty($custMobile))
                            <tr>
                                <td class="lbl">Contact Details</td>
                                <td class="col">:</td>
                                <td class="val">
                                    @if($custContactPerson)
                                        {{ $custContactPerson }} ({{ $custMobile }})
                                    @else
                                        {{ $custMobile }}
                                    @endif
                                </td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- SHIPPER / INVOICE INFORMATION CARD -->
                <div class="header-meta-card">
                    <div class="card-head">
                        <i class="fas fa-file-invoice"></i> INVOICE DETAILS
                    </div>
                    <div class="card-body-meta">
                        <table class="meta-table">
                            @if($showInvNo)
                            <tr>
                                <td class="lbl">{{ ($sale->sale_status === 'quotation' || ($isQuotation ?? false)) ? 'Quotation Number' : 'Invoice Number' }}</td>
                                <td class="col">:</td>
                                <td class="val highlight">{{ $sale->invoice_no }}</td>
                            </tr>
                            @endif

                            @if($showInvDate)
                            <tr>
                                <td class="lbl">{{ ($sale->sale_status === 'quotation' || ($isQuotation ?? false)) ? 'Quotation Date' : 'Invoice Date' }}</td>
                                <td class="col">:</td>
                                <td class="val">{{ $sale->created_at ? $sale->created_at->format('d-F-Y') : date('d-F-Y') }}</td>
                            </tr>
                            @endif

                            @if($showCompTax)
                                @if(!empty($compNtn) && $isNtnEnabled)
                                <tr>
                                    <td class="lbl">NTN Number</td>
                                    <td class="col">:</td>
                                    <td class="val">{{ $compNtn }}</td>
                                </tr>
                                @endif

                                @if(!empty($compStrn) && $isStrnEnabled)
                                <tr>
                                    <td class="lbl">STRN Number</td>
                                    <td class="col">:</td>
                                    <td class="val">{{ $compStrn }}</td>
                                </tr>
                                @endif
                            @endif

                            @if($showSalesRef && (!empty($salesOfficerName) || !empty($sale->reference)))
                            <tr>
                                <td class="lbl">Sales Reference</td>
                                <td class="col">:</td>
                                <td class="val">{{ $salesOfficerName ?: $sale->reference }}</td>
                            </tr>
                            @endif

                            @if($showCompPhone && !empty($compPhone))
                            <tr>
                                <td class="lbl">Contact Details</td>
                                <td class="col">:</td>
                                <td class="val">{{ $compPhone }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="text-end mb-2">
                <span class="badge bg-light text-dark border p-2 font-monospace">
                    Date: {{ $sale->created_at ? $sale->created_at->format('d-F-Y') : date('d-F-Y') }}
                </span>
            </div>
        @endif

        <!-- Return / Special Note -->
        @if ($sale->return_note)
            <div class="alert alert-secondary py-1 px-3 mb-2" style="font-size: 10.5px; border-left: 3px solid var(--primary-navy);">
                <strong>Note:</strong> {{ $sale->return_note }}
            </div>
        @endif

        <!-- ========================================== -->
        <!-- MAIN INVOICE TABLE -->
        <!-- ========================================== -->
        <table class="invoice-table">
            <thead>
                <tr>
                    @if(!empty($isQuotation))
                        <th style="width: 6%;" class="text-center">Sr#</th>
                        <th class="text-start">Product Name</th>
                        <th style="width: 15%;" class="text-end">Rate</th>
                        <th style="width: 12%;" class="text-center">Qty</th>
                        <th style="width: 18%;" class="text-end">Amount</th>
                    @elseif($showTaxColumns)
                        <th style="width: 4%;" class="text-center">S.No</th>
                        <th class="text-start">Description of Product</th>
                        @if($showBatchCol)
                            <th style="width: 10%;" class="text-center">Batch / Sr. No</th>
                        @endif
                        <th style="width: 6%;" class="text-center">Quantity</th>
                        <th style="width: 8%;" class="text-end">Rate</th>
                        <th style="width: 9%;" class="text-end">Gross Amount</th>
                        @if($showDiscCol)
                            <th style="width: 5%;" class="text-end">Disc %</th>
                        @endif
                        <th style="width: 10%;" class="text-end">Exclusive GST</th>
                        <th style="width: 7%;" class="text-end">Sales Tax</th>
                        <th style="width: 7%;" class="text-end">Further Tax {{ ((float)($sale->total_further_tax ?? 0) > 0) ? '@3%' : '(0%)' }}</th>
                        <th style="width: 8%;" class="text-end">Net Amount</th>
                    @else
                        <th style="width: 5%;" class="text-center">S.No</th>
                        <th class="text-start">Description of Product</th>
                        @if($showBatchCol)
                            <th style="width: 13%;" class="text-center">Batch / Sr. No</th>
                        @endif
                        <th style="width: 8%;" class="text-center">Quantity</th>
                        <th style="width: 10%;" class="text-end">Rate</th>
                        <th style="width: 12%;" class="text-end">Gross Amount</th>
                        @if($showDiscCol)
                            <th style="width: 5%;" class="text-end">Disc %</th>
                        @endif
                        <th style="width: 8%;" class="text-end">Net Amount</th>
                    @endif
                </tr>
            </thead>

            @php
                $sumTotQty = 0;
                $sumGrossAmt = 0;
                $sumExclGst = 0;
                $sumSalesTax = 0;
                $sumFurtherTax = 0;
                $sumNetTotal = 0;
            @endphp

            <tbody>
                @foreach ($saleItems as $item)
                    @php
                        $vName = $item['variant_name'] ?? '';
                        $vSize = (!empty($item['size_val']) && $item['size_val'] !== '-') ? $item['size_val'] : '';
                        $vColor = (!empty($item['color_val']) && $item['color_val'] !== '-') ? $item['color_val'] : '';
                        
                        $vExtra = [];
                        if ($vSize) $vExtra[] = $vSize;
                        if ($vColor) $vExtra[] = $vColor;
                        $vExtraStr = count($vExtra) > 0 ? ' (' . implode(', ', $vExtra) . ')' : '';

                        $productTitle = $item['item_name'];
                        if ($vName && strtolower(trim($vName)) !== strtolower(trim($productTitle))) {
                            $productTitle .= ' — ' . $vName;
                        }
                        $productTitle .= $vExtraStr;

                        $batchNo = $item['batch_no'] ?? '';
                        $packing = (float)($item['pieces_per_box'] ?? 1);
                        if ($packing <= 0) $packing = 1;

                        $itemQty = (float)($item['qty_box'] ?? $item['qty'] ?? $item['total_pieces'] ?? 0);
                        $itemPrice = (float)($item['price'] ?? 0);
                        
                        $itemGross = (float)($item['gross_amount'] ?? 0);
                        if ($itemGross <= 0) {
                            $itemGross = $itemQty * $itemPrice;
                        }

                        $discPct = (float)($item['discount_percent'] ?? 0);
                        $exclGst = (float)($item['exclusive_gst_amount'] ?? 0);
                        $salesTax = (float)($item['sales_tax_amount'] ?? 0);
                        $furtherTax = (float)($item['further_tax_amount'] ?? 0);
                        $netAmt = (float)($item['total'] ?? 0);

                        $sumTotQty += $itemQty;
                        $sumGrossAmt += $itemGross;
                        $sumExclGst += $exclGst;
                        $sumSalesTax += $salesTax;
                        $sumFurtherTax += $furtherTax;
                        $sumNetTotal += $netAmt;
                    @endphp

                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>

                        <td class="text-start">
                            <div class="item-main-title">{{ $productTitle }}</div>

                            @php
                                $subDescriptions = [];
                                $rawModel = $item['model'] ?? ($item['product']['model'] ?? null);
                                if (!empty($rawModel)) {
                                    $decodedModel = json_decode($rawModel, true);
                                    if (is_array($decodedModel)) {
                                        $subDescriptions = $decodedModel;
                                    } else {
                                        $subDescriptions = array_filter(array_map('trim', explode("\n", $rawModel)));
                                    }
                                }

                                $itemSerialsRaw = $item['serials'] ?? null;
                                $itemSerialsList = [];
                                if (!empty($itemSerialsRaw)) {
                                    if (is_array($itemSerialsRaw)) {
                                        $itemSerialsList = $itemSerialsRaw;
                                    } elseif (is_string($itemSerialsRaw)) {
                                        $decodedSerials = json_decode($itemSerialsRaw, true);
                                        if (is_array($decodedSerials)) {
                                            $itemSerialsList = $decodedSerials;
                                        } else {
                                            $itemSerialsList = array_filter(array_map('trim', explode(',', $itemSerialsRaw)));
                                        }
                                    }
                                }
                            @endphp

                            @if(!empty($batchNo))
                                <div class="item-sub-batch" style="font-size: 11px; font-weight: 700; color: #1e40af; margin-top: 2px;">
                                    <i class="fas fa-layer-group me-1"></i>Batch: {{ $batchNo }}
                                    @if(!empty($item['exp_date']))
                                        <span class="text-muted ms-1" style="font-weight: normal; font-size: 10px;">(Exp: {{ \Carbon\Carbon::parse($item['exp_date'])->format('d-M-Y') }})</span>
                                    @endif
                                </div>
                            @endif

                            @if(!empty($itemSerialsList) && count($itemSerialsList) > 0)
                                <div class="item-sub-sn">
                                    <i class="fas fa-barcode me-1"></i>S/N: {{ implode(', ', $itemSerialsList) }}
                                </div>
                            @endif

                            @if(!empty($subDescriptions) && count($subDescriptions) > 0)
                                <div class="item-sub-desc">
                                    @foreach($subDescriptions as $sd)
                                        @if(trim($sd))
                                            <div><span class="me-1 text-muted">•</span>{{ trim($sd) }}</div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>

                        @if(!empty($isQuotation))
                            <td class="text-end">
                                {{ number_format($itemPrice, 2) }}
                            </td>
                            <td class="text-center fw-bold">
                                {{ $itemQty == (int)$itemQty ? (int)$itemQty : number_format($itemQty, 2) }}
                            </td>
                            <td class="text-end fw-bold">
                                {{ number_format($itemGross, 2) }}
                            </td>
                        @else
                            @if($showBatchCol)
                            <td class="text-center">
                                @if(!empty($batchNo))
                                    <span class="badge bg-light text-dark border">{{ $batchNo }}</span>
                                @elseif(!empty($itemSerialsList))
                                    <span class="badge bg-light text-dark border">S/N</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            @endif

                            <td class="text-center fw-bold">
                                {{ $itemQty == (int)$itemQty ? (int)$itemQty : number_format($itemQty, 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($itemPrice, 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($itemGross, 2) }}
                            </td>

                            @if($showDiscCol)
                            <td class="text-end">
                                {{ $discPct > 0 ? number_format($discPct, 2).'%' : '0.00' }}
                            </td>
                            @endif

                            @if($showTaxColumns)
                                <td class="text-end">
                                    {{ number_format($exclGst, 2) }}
                                </td>

                                <td class="text-end">
                                    {{ number_format($salesTax, 2) }}
                                </td>

                                <td class="text-end">
                                    {{ number_format($furtherTax, 2) }}
                                </td>
                            @endif

                            <td class="text-end fw-bold">
                                {{ number_format($netAmt, 2) }}
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    @if(!empty($isQuotation))
                        <td colspan="2" class="text-end fw-bold">
                            TOTAL:
                        </td>
                        <td class="text-end fw-bold"></td>
                        <td class="text-center fw-bold">
                            {{ $sumTotQty == (int)$sumTotQty ? (int)$sumTotQty : number_format($sumTotQty, 2) }}
                        </td>
                        <td class="text-end fw-bold">
                            Rs. {{ number_format($sumGrossAmt, 2) }}
                        </td>
                    @else
                        <td colspan="{{ $showBatchCol ? 3 : 2 }}" class="text-end fw-bold">
                            TOTAL:
                        </td>

                        <td class="text-center fw-bold">
                            {{ $sumTotQty == (int)$sumTotQty ? (int)$sumTotQty : number_format($sumTotQty, 2) }}
                        </td>

                        <td class="text-end fw-bold"></td>

                        <td class="text-end fw-bold">
                            {{ number_format($sumGrossAmt, 2) }}
                        </td>

                        @if($showDiscCol)
                        <td class="text-end fw-bold">
                            0.00
                        </td>
                        @endif

                        @if($showTaxColumns)
                            <td class="text-end fw-bold">
                                {{ number_format($sumExclGst, 2) }}
                            </td>

                            <td class="text-end fw-bold">
                                {{ number_format($sumSalesTax, 2) }}
                            </td>

                            <td class="text-end fw-bold">
                                {{ number_format($sumFurtherTax, 2) }}
                            </td>
                        @endif

                        <td class="text-end fw-bold">
                            {{ number_format($sumNetTotal, 2) }}
                        </td>
                    @endif
                </tr>
            </tfoot>
        </table>

        @php
            $totalCartonsCount = 0;
            $totalLooseCount = 0;
            $totalPiecesCount = 0;

            foreach ($saleItems as $it) {
                $ppb = (float)($it['pieces_per_box'] ?? 1);
                if ($ppb <= 0) $ppb = 1;
                $tp = (float)($it['total_pieces'] ?? 0);
                $totalPiecesCount += $tp;

                $vU = strtolower($it['variant_unit'] ?? '');
                $sM = $it['size_mode'] ?? 'std';

                if (in_array($sM, ['by_cartons', 'by_bandal']) || $vU === 'carton' || $vU === 'ctn') {
                    $b = floor($tp / $ppb);
                    $l = $tp % $ppb;
                    $totalCartonsCount += $b;
                    $totalLooseCount += $l;
                }
            }
        @endphp

        <!-- ========================================== -->
        <!-- EXCHANGE RETURN SECTION -->
        <!-- ========================================== -->
        @php
            $exchangeReturn = \App\Models\SaleReturn::with('items.product')
                ->where('remarks', 'LIKE', '%Invoice #'.$sale->invoice_no.'%')
                ->first();

            $exchangeReturnedAmount = 0;
            if ($exchangeReturn) {
                $exchangeReturnedAmount = $exchangeReturn->items->sum('line_total');
            }
        @endphp

        @if(!$isQuotation && $exchangeReturn && $exchangeReturn->items->count() > 0)
            <div class="returns-wrapper">
                <div class="returns-header">
                    <i class="fas fa-undo-alt"></i> Returned Items (Exchange)
                </div>

                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;" class="text-start">S.No</th>
                            <th style="width: 45%;" class="text-start">Description</th>
                            <th style="width: 12%;" class="text-center">Qty</th>
                            <th style="width: 10%;" class="text-center">UOM</th>
                            <th style="width: 12%;" class="text-end">Price</th>
                            <th style="width: 16%;" class="text-end">Net Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($exchangeReturn->items as $retItem)
                            <tr>
                                <td class="text-start">{{ $loop->iteration }}</td>

                                <td class="text-start">
                                    <div class="fw-bold">{{ $retItem->product->item_name ?? 'Unknown' }}</div>
                                    @php
                                        $retColorStr = '';
                                        if (!empty($retItem->color)) {
                                            $decoded = base64_decode($retItem->color, true);
                                            if ($decoded !== false && is_string($decoded) && str_starts_with(trim($decoded), '{')) {
                                                $parsed = json_decode($decoded, true);
                                                if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                                                    $parts = [];
                                                    if (!empty($parsed['size']) && $parsed['size'] !== '-') $parts[] = $parsed['size'];
                                                    if (!empty($parsed['color']) && $parsed['color'] !== '-') $parts[] = $parsed['color'];
                                                    $retColorStr = implode(' | ', $parts);
                                                }
                                            }
                                        }
                                    @endphp
                                    @if($retColorStr)
                                        <span class="badge bg-light text-dark border p-1" style="font-size: 9px;">
                                            {{ $retColorStr }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center fw-bold">{{ (float)$retItem->qty }} Pcs</td>
                                <td class="text-center">Pieces</td>
                                <td class="text-end">{{ number_format($retItem->price, 2) }}</td>
                                <td class="text-end fw-bold text-danger">- {{ number_format($retItem->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- ========================================== -->
        <!-- BOTTOM SECTION: WORDS, TERMS & TOTALS -->
        <!-- ========================================== -->
        <div class="invoice-bottom-grid">

            <!-- Left: Amount in Words, Terms & Signature -->
            <div class="invoice-bottom-left">

                <!-- Amount in Words -->
                @if($showAmountWords && !empty($sale->total_amount_Words))
                    <div class="words-card">
                        <div class="words-label">
                            <i class="fas fa-money-bill-wave"></i> Amount in Words:
                        </div>
                        <div class="words-value">
                            {{ ucwords(strtolower($sale->total_amount_Words)) }} Only
                        </div>
                    </div>
                @endif

                <!-- Terms & Conditions -->
                @php
                    $showInvoiceTerms = \App\Models\Setting::get('show_invoice_terms', '1') == '1';
                    $saleTermsList = null;
                    if ($showInvoiceTerms) {
                        if (!is_null($sale->selected_terms)) {
                            $selectedTermIds = is_array($sale->selected_terms)
                                ? $sale->selected_terms
                                : (json_decode($sale->selected_terms, true) ?? []);
                            if (!empty($selectedTermIds)) {
                                $saleTermsList = \App\Models\TermsAndCondition::whereIn('id', $selectedTermIds)->active()->ordered()->get();
                            } else {
                                $saleTermsList = collect();
                            }
                        } else {
                            $activeTerms = \App\Models\TermsAndCondition::active()->ordered()->get();
                            if ($activeTerms->count() > 0) {
                                $saleTermsList = $activeTerms;
                            }
                        }
                    }
                @endphp

                @if($saleTermsList && $saleTermsList->count() > 0)
                    <div class="terms-card">
                        <div class="terms-title">
                            <i class="fas fa-clipboard-list"></i> Terms & Conditions:
                        </div>
                        <div class="terms-content">
                            @foreach($saleTermsList as $st)
                                @php
                                    $rawParagraph = trim($st->paragraph ?? '');
                                    $rawLines = preg_split('/\r\n|\n|\r/', $rawParagraph);
                                    $points = [];
                                    foreach ($rawLines as $line) {
                                        $trimmed = trim($line);
                                        if (strlen($trimmed) > 0) {
                                            $clean = preg_replace('/^[\s\-\*\•\–\—\.]+\s*/u', '', $trimmed);
                                            $points[] = !empty($clean) ? $clean : $trimmed;
                                        }
                                    }
                                @endphp
                                <div class="term-block">
                                    <div class="term-heading-label">
                                        {{ $st->heading }}:
                                    </div>
                                    <div class="term-points-wrapper">
                                        @if(count($points) > 0)
                                            @foreach($points as $pt)
                                                <div class="term-point-item">
                                                    <span class="term-bullet">•</span>
                                                    <span class="term-point-text">{{ $pt }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="term-point-item">
                                                <span class="term-bullet">•</span>
                                                <span class="term-point-text">{{ $rawParagraph }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(\App\Models\Setting::get('show_invoice_signature', '1') == '1' && ($saleTermsList && $saleTermsList->count() > 0))
                <!-- Authorized Signature -->
                <div class="signature-section">
                    <div class="signature-line-box">
                        Authorized Signature
                    </div>
                </div>
                @endif

            </div>

            <!-- Right: Totals Summary Card -->
            <div class="invoice-bottom-right">

                <div class="totals-card">
                    <div class="card-head">
                        <i class="fas fa-calculator"></i> {{ $isQuotation ? 'Quotation Summary' : 'Invoice Summary' }}
                    </div>

                    <table class="totals-table">
                        @php
                            $grossTotal = collect($saleItems)->sum('total');
                            $totalDisc = collect($saleItems)->sum('discount_amount');
                            $extraDisc = (float)($sale->total_extradiscount ?? 0);
                            $netBill = (float)($sale->total_net ?? 0);
                            $paidAmount = (float)($sale->cash ?? 0);

                            $sumExclusiveGst = (float)($sale->total_exclusive_gst ?? collect($saleItems)->sum('exclusive_gst_amount'));
                            $sumSalesTax = (float)($sale->total_sales_tax ?? collect($saleItems)->sum('sales_tax_amount'));
                            $sumFurtherTax = (float)($sale->total_further_tax ?? collect($saleItems)->sum('further_tax_amount'));

                            $finalPayable = $isQuotation ? (float)$sumGrossAmt : ($netBill - (float)$exchangeReturnedAmount);
                        @endphp

                        @if ($isQuotation)
                            <tr>
                                <td class="lbl">Total Items</td>
                                <td class="val">{{ count($saleItems) }}</td>
                            </tr>
                            <tr>
                                <td class="lbl">Total Quantity</td>
                                <td class="val">{{ $sumTotQty == (int)$sumTotQty ? (int)$sumTotQty : number_format($sumTotQty, 2) }}</td>
                            </tr>
                            @if ($sale->freight_charges > 0)
                                <tr>
                                    <td class="lbl">Freight Charges {{ $sale->freight_type === 'add' ? '(+)' : '(-)' }}</td>
                                    <td class="val">{{ $sale->freight_type === 'add' ? '+' : '-' }} {{ number_format($sale->freight_charges, 2) }}</td>
                                </tr>
                            @endif
                            <tr class="total-row">
                                <td class="lbl">
                                    GRAND TOTAL
                                </td>
                                <td class="val">
                                    Rs. {{ number_format($sumGrossAmt + ($sale->freight_charges > 0 && $sale->freight_type === 'add' ? $sale->freight_charges : 0), 2) }}
                                </td>
                            </tr>
                        @else

                        @if ($totalCartonsCount > 0)
                            <tr>
                                <td class="lbl">Total Cartons / Bundals</td>
                                <td class="val">
                                    @if ($totalLooseCount > 0)
                                        {{ $totalCartonsCount }} Ctn/Bndl + {{ $totalLooseCount }} Pcs
                                    @else
                                        {{ $totalCartonsCount }} Ctn/Bndl
                                    @endif
                                </td>
                            </tr>
                        @endif

                        @if ($totalDisc > 0 || $extraDisc > 0)
                            <tr>
                                <td class="lbl">Gross Total</td>
                                <td class="val">{{ number_format($grossTotal + $totalDisc, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="lbl text-danger">Total Discount</td>
                                <td class="val text-danger">- {{ number_format($totalDisc + $extraDisc, 2) }}</td>
                            </tr>
                        @endif

                        @if ($sumExclusiveGst > 0)
                            <tr>
                                <td class="lbl">Subtotal</td>
                                <td class="val">{{ number_format($sumExclusiveGst, 2) }}</td>
                            </tr>
                        @endif

                        @if ($sumSalesTax > 0)
                            <tr>
                                <td class="lbl text-success">Sales Tax (@18%)</td>
                                <td class="val text-success">+ {{ number_format($sumSalesTax, 2) }}</td>
                            </tr>
                        @endif

                        @if ($sumFurtherTax > 0)
                            <tr>
                                <td class="lbl text-primary">Further Tax (@3%)</td>
                                <td class="val text-primary">+ {{ number_format($sumFurtherTax, 2) }}</td>
                            </tr>
                        @endif

                        @if ($sale->freight_charges > 0)
                            <tr>
                                <td class="lbl">Freight Charges {{ $sale->freight_type === 'add' ? '(+)' : '(-)' }}</td>
                                <td class="val">{{ $sale->freight_type === 'add' ? '+' : '-' }} {{ number_format($sale->freight_charges, 2) }}</td>
                            </tr>
                        @endif

                        @if ($exchangeReturnedAmount > 0)
                            <tr>
                                <td class="lbl text-danger">Return Value</td>
                                <td class="val text-danger">- {{ number_format($exchangeReturnedAmount, 2) }}</td>
                            </tr>
                        @endif

                        <!-- Grand Total Highlight Row -->
                        <tr class="total-row">
                            <td class="lbl">
                                {{ $finalPayable < 0 ? 'REFUND TO CUSTOMER' : 'GRAND TOTAL' }}
                            </td>
                            <td class="val">
                                Rs. {{ number_format(abs($finalPayable), 2) }}
                            </td>
                        </tr>

                        @if ($showCustomerBalance && !empty($sale->customer_id))
                            @php
                                $prevBal = (float)($previousBalance ?? 0);
                                $totalClosingBal = (float)($currentBalance ?? ($prevBal + $finalPayable - $paidAmount));
                            @endphp
                            @if ($paidAmount > 0)
                                <tr>
                                    <td class="lbl text-success">Paid / Received</td>
                                    <td class="val text-success font-monospace">- Rs. {{ number_format($paidAmount, 2) }}</td>
                                </tr>
                            @endif
                            <tr style="border-top: 1px solid #cbd5e1;">
                                <td class="lbl text-muted">Previous Balance</td>
                                <td class="val text-dark font-monospace">
                                    Rs. {{ number_format(abs($prevBal), 2) }} {{ $prevBal >= 0 ? 'Dr' : 'Cr' }}
                                </td>
                            </tr>
                            <tr style="background: #f8fafc; border-top: 1.5px solid var(--primary-navy);">
                                <td class="lbl fw-bold" style="font-size: 12px; color: var(--primary-navy);">
                                    Total Balance
                                </td>
                                <td class="val fw-bold font-monospace" style="font-size: 13.5px; color: {{ $totalClosingBal >= 0 ? '#b91c1c' : '#15803d' }};">
                                    Rs. {{ number_format(abs($totalClosingBal), 2) }} {{ $totalClosingBal >= 0 ? 'Dr' : 'Cr' }}
                                </td>
                            </tr>
                        @endif
                        @endif
                    </table>
                </div>

            </div>

        </div>

    </div>

    <!-- ========================================== -->
    <!-- THERMAL RECEIPT (80mm) -->
    <!-- ========================================== -->
    <div class="receipt-container">
        <!-- Header -->
        @php
            $receiptLogo = \App\Models\Setting::get('company_logo') ?? \App\Models\Setting::get('web_site_logo');
        @endphp
        @if(!empty($receiptLogo) && (file_exists(public_path($receiptLogo)) || file_exists(base_path($receiptLogo))))
            <div style="text-align: center; margin-bottom: 6px;">
                <img src="{{ asset($receiptLogo) }}" alt="Logo" style="max-height: 65px; max-width: 85%; object-fit: contain;">
            </div>
        @endif
        <div class="company-name">
            {{ \App\Models\Setting::get('company_name', config('app.name', 'Innot Tech')) }}
        </div>

        <div class="company-info">
            @if(\App\Models\Setting::get('company_address'))
                <div>{{ \App\Models\Setting::get('company_address') }}</div>
            @endif
            @if(\App\Models\Setting::get('company_phone'))
                <div>Ph: {{ \App\Models\Setting::get('company_phone') }}</div>
            @endif
        </div>

        @if(
            \App\Models\Setting::get('facebook_link') ||
            \App\Models\Setting::get('tiktok_link') ||
            \App\Models\Setting::get('instagram_link') ||
            \App\Models\Setting::get('website_link')
        )
            <div style="text-align: left; font-size: 10px; line-height: 1.4; word-wrap: break-word; overflow-wrap: break-word; margin-top: 4px;">
                @if(\App\Models\Setting::get('facebook_link'))
                    <div style="margin-bottom: 2px;"><strong>Facebook:</strong> {{ \App\Models\Setting::get('facebook_link') }}</div>
                @endif
                @if(\App\Models\Setting::get('tiktok_link'))
                    <div style="margin-bottom: 2px;"><strong>TikTok:</strong> {{ \App\Models\Setting::get('tiktok_link') }}</div>
                @endif
                @if(\App\Models\Setting::get('instagram_link'))
                    <div style="margin-bottom: 2px;"><strong>Instagram:</strong> {{ \App\Models\Setting::get('instagram_link') }}</div>
                @endif
                @if(\App\Models\Setting::get('website_link'))
                    <div style="margin-bottom: 2px;"><strong>Website:</strong> {{ \App\Models\Setting::get('website_link') }}</div>
                @endif
            </div>
        @endif

        <div class="policy-banner">
            No Return, Only Exchange in 3 days
        </div>

        <div class="divider"></div>

        <!-- Meta -->
        <div class="meta-grid">
            <div class="meta-row">
                <span class="meta-label">Invoice #:</span>
                <span class="meta-value">{{ $sale->invoice_no }}</span>
            </div>

            <div class="meta-row">
                <span class="meta-label">Date:</span>
                <span class="meta-value">{{ $sale->created_at ? $sale->created_at->format('d/m/Y h:i A') : date('d/m/Y h:i A') }}</span>
            </div>

            <div class="meta-row">
                <span class="meta-label">Customer:</span>
                <span class="meta-value">{{ Str::limit($sale->walkin_name ?? ($sale->customer_relation->customer_name ?? 'Walking Customer'), 22) }}</span>
            </div>

            @if(!empty($custNtn) && $isNtnEnabled)
                <div class="meta-row">
                    <span class="meta-label">NTN:</span>
                    <span class="meta-value">{{ $custNtn }}</span>
                </div>
            @endif

            @if(!empty($custStrn) && $isStrnEnabled)
                <div class="meta-row">
                    <span class="meta-label">STRN:</span>
                    <span class="meta-value">{{ $custStrn }}</span>
                </div>
            @endif

            @if (auth()->check())
                <div class="meta-row">
                    <span class="meta-label">Salesperson:</span>
                    <span class="meta-value">{{ auth()->user()->name }}</span>
                </div>
            @endif

            @if($sale->reference)
                <div class="meta-row">
                    <span class="meta-label">Remarks:</span>
                    <span class="meta-value">{{ $sale->reference }}</span>
                </div>
            @endif
        </div>

        <div class="divider"></div>

        <!-- Thermal Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 6%;">S.</th>
                    <th style="width: 53%;">Description</th>
                    <th style="width: 10%;" class="text-center">Qty</th>
                    <th style="width: 13%;" class="text-end">Rate</th>
                    <th style="width: 18%;" class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($saleItems as $item)
                    @php
                        $sizeMode = $item['size_mode'] ?? 'std';
                        $totalPieces = (int)$item['total_pieces'];
                        $variantUnit = strtolower($item['variant_unit'] ?? '');
                        $weightGrams = (float)($item['weight_per_piece'] ?? 0);
                        $qtyDisplay = $totalPieces . ' Pcs';

                        if ($variantUnit === 'pcs' || $variantUnit === 'piece' || $variantUnit === 'pieces') {
                            $qtyDisplay = $totalPieces . ' Pcs';
                            if ($weightGrams > 0) {
                                $qtyDisplay .= ' (' . ($weightGrams == (int)$weightGrams ? (int)$weightGrams : $weightGrams) . 'g)';
                            }
                        } elseif (in_array($sizeMode, ['by_kg', 'by_gm', 'by_feet', 'by_meter'])) {
                            $uomLabel = match($sizeMode) {
                                'by_kg' => 'Kg',
                                'by_gm' => 'Gm',
                                'by_feet' => 'Ft',
                                'by_meter' => 'Mtr',
                                default => '',
                            };
                            $qtyVal = (float)($item['qty_box'] ?? $item['qty'] ?? $totalPieces);
                            $qtyDisplay = ($qtyVal == (int)$qtyVal ? (int)$qtyVal : number_format($qtyVal, 3)) . ' ' . $uomLabel;
                        } elseif (in_array($sizeMode, ['by_cartons', 'by_bandal']) || $sizeMode == 'by_size') {
                            $piecesPerBox = (int)($item['pieces_per_box'] ?? 1);
                            if ($piecesPerBox <= 0) $piecesPerBox = 1;
                            $boxes = floor($totalPieces / $piecesPerBox);
                            $loose = $totalPieces % $piecesPerBox;
                            if ($boxes > 0 && $loose > 0) {
                                $qtyDisplay = "$boxes.$loose";
                            } elseif ($boxes > 0) {
                                $qtyDisplay = $boxes;
                            } else {
                                $qtyDisplay = $loose;
                            }
                        }
                    @endphp

                    <tr>
                        <td style="width: 6%;">{{ $loop->iteration }}</td>
                        <td style="width: 53%;">
                            @php
                                $tVName = $item['variant_name'] ?? '';
                                $tVSize = (!empty($item['size_val']) && $item['size_val'] !== '-') ? $item['size_val'] : '';
                                $tVColor = (!empty($item['color_val']) && $item['color_val'] !== '-') ? $item['color_val'] : '';
                                
                                $tVExtra = [];
                                if ($tVSize) $tVExtra[] = $tVSize;
                                if ($tVColor) $tVExtra[] = $tVColor;
                                $tVExtraStr = count($tVExtra) > 0 ? ' (' . implode(', ', $tVExtra) . ')' : '';

                                $tProductTitle = $item['item_name'];
                                if ($tVName && strtolower(trim($tVName)) !== strtolower(trim($tProductTitle))) {
                                    $tProductTitle .= ' — ' . $tVName;
                                }
                                $tProductTitle .= $tVExtraStr;
                            @endphp

                            <span class="item-name">{{ $tProductTitle }}</span>

                            @php
                                $tBatchNo = $item['batch_no'] ?? null;
                                $tSerialsRaw = $item['serials'] ?? null;
                                $tSerialsList = [];
                                if (!empty($tSerialsRaw)) {
                                    if (is_array($tSerialsRaw)) {
                                        $tSerialsList = $tSerialsRaw;
                                    } elseif (is_string($tSerialsRaw)) {
                                        $decodedT = json_decode($tSerialsRaw, true);
                                        if (is_array($decodedT)) {
                                            $tSerialsList = $decodedT;
                                        } else {
                                            $tSerialsList = array_filter(array_map('trim', explode(',', $tSerialsRaw)));
                                        }
                                    }
                                }
                            @endphp

                            @if(!empty($tBatchNo))
                                <span class="item-variant" style="font-weight: 600; color: #000; font-size: 9px; display: block; margin-top: 1px;">
                                    Batch: {{ $tBatchNo }}
                                </span>
                            @endif
                            @if(!empty($tSerialsList))
                                <span class="item-variant" style="font-weight: 600; color: #000; font-size: 9px; display: block; word-break: break-all; margin-top: 1px;">
                                    S/N: {{ implode(', ', $tSerialsList) }}
                                </span>
                            @endif
                        </td>
                        <td style="width: 10%;" class="text-center">{{ $qtyDisplay }}</td>
                        <td style="width: 13%;" class="text-end">{{ number_format($item['price'], 0) }}</td>
                        <td style="width: 18%;" class="text-end">{{ number_format($item['total'], 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Returned Items (Thermal) -->
        @if(!$isQuotation && $exchangeReturn && $exchangeReturn->items->count() > 0)
            <div class="divider" style="border-top: 1px dashed #000; margin: 4px 0;"></div>
            <div style="font-weight: bold; font-size: 11px; margin-bottom: 2px; color: #000;">Returned Items:</div>
            <table class="items-table" style="margin-bottom: 4px;">
                <tbody>
                    @foreach ($exchangeReturn->items as $retItem)
                        <tr>
                            <td style="width: 6%;">{{ $loop->iteration }}</td>
                            <td style="width: 53%;">
                                @if($retItem->is_manual || empty($retItem->product_id))
                                    <span class="item-name">{{ $retItem->product_name }} (Manual)</span>
                                @else
                                    <span class="item-name">{{ $retItem->product->item_name ?? 'Unknown' }}</span>
                                @endif
                            </td>
                            <td style="width: 10%;" class="text-center">{{ (float)$retItem->qty }}</td>
                            <td style="width: 13%;" class="text-end">{{ number_format($retItem->price, 0) }}</td>
                            <td style="width: 18%;" class="text-end">- {{ number_format($retItem->line_total, 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- Totals (Thermal) -->
        <div class="totals-section">
            <div class="tot-row">
                <span>Sub Total:</span>
                <span>{{ number_format($sale->total_bill_amount, 0) }}</span>
            </div>

            @if ($sale->total_extradiscount > 0)
                <div class="tot-row">
                    <span>Discount:</span>
                    <span>- {{ number_format($sale->total_extradiscount, 0) }}</span>
                </div>
            @endif

            @if ($sale->freight_charges > 0)
                <div class="tot-row">
                    <span>Freight {{ $sale->freight_type === 'add' ? '(Added)' : '(Deducted)' }}:</span>
                    <span>{{ $sale->freight_type === 'add' ? '+' : '-' }} {{ number_format($sale->freight_charges, 0) }}</span>
                </div>
            @endif

            @if ($exchangeReturnedAmount > 0)
                <div class="tot-row">
                    <span>Return Value:</span>
                    <span>- {{ number_format($exchangeReturnedAmount, 0) }}</span>
                </div>
            @endif

            <div class="tot-row grand-total">
                @if($finalPayable < 0)
                    <span>REFUND TO CUSTOMER:</span>
                    <span>{{ number_format(abs($finalPayable), 0) }}</span>
                @else
                    <span>TOTAL PAYABLE:</span>
                    <span>{{ number_format($finalPayable, 0) }}</span>
                @endif
            </div>

            @if ($showCustomerBalance && !empty($sale->customer_id))
                @php
                    $prevBalThermal = (float)($previousBalance ?? 0);
                    $totalClosingThermal = (float)($currentBalance ?? ($prevBalThermal + $finalPayable - $paidAmount));
                @endphp
                <div class="divider" style="margin: 4px 0;"></div>
                @if ($paidAmount > 0)
                    <div class="tot-row">
                        <span>Paid / Received:</span>
                        <span>- {{ number_format($paidAmount, 0) }}</span>
                    </div>
                @endif
                <div class="tot-row">
                    <span>Previous Balance:</span>
                    <span>{{ number_format(abs($prevBalThermal), 0) }} {{ $prevBalThermal >= 0 ? 'Dr' : 'Cr' }}</span>
                </div>
                <div class="tot-row grand-total" style="font-size: 11.5px; border-top: 1px dashed #000; padding-top: 2px; margin-top: 2px;">
                    <span>TOTAL BALANCE:</span>
                    <span>{{ number_format(abs($totalClosingThermal), 0) }} {{ $totalClosingThermal >= 0 ? 'Dr' : 'Cr' }}</span>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for shopping with us!</p>
            <div class="soft-credit">
                Powered by Prowave Technologies<br>
                📞 +92 317 3836 223
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- JAVASCRIPT & A4 PRINT HANDLER -->
    <!-- ========================================== -->
    <script>
        function printReceipt() {
            cleanupPrintStyles();
            let style = document.createElement('style');
            style.id = 'dynamic-print-style';
            style.innerHTML = `
                @media print {
                    @page {
                        size: 80mm auto;
                        margin: 0;
                    }
                }
            `;
            document.head.appendChild(style);
            document.body.classList.add('print-receipt-mode');

            window.print();

            setTimeout(() => {
                cleanupPrintStyles();
            }, 1000);
        }

        function printA4(isLetterhead = false) {
            cleanupPrintStyles();
            let style = document.createElement('style');
            style.id = 'dynamic-print-style';

            if (isLetterhead) {
                document.body.classList.add('print-letterhead-mode');
                style.innerHTML = `
                    @media print {
                        @page { 
                            size: A4 portrait; 
                            margin: 0; 
                        }
                        .invoice-container {
                            padding: 45mm 8mm 20mm 8mm !important;
                        }
                        .company-header-wrapper {
                            display: none !important;
                        }
                    }
                `;
            } else {
                style.innerHTML = `
                    @media print {
                        @page { 
                            size: A4 portrait; 
                            margin: 0; 
                        }
                        .invoice-container {
                            padding: 6mm 8mm !important;
                        }
                    }
                `;
            }
            document.head.appendChild(style);

            window.print();

            setTimeout(() => {
                cleanupPrintStyles();
            }, 1000);
        }

        function cleanupPrintStyles() {
            let s = document.getElementById('dynamic-print-style');
            if (s) {
                s.remove();
            }
            let oldA4 = document.getElementById('a4-print-style');
            if (oldA4) {
                oldA4.remove();
            }
            document.body.classList.remove('print-letterhead-mode', 'print-receipt-mode');
        }

        window.onafterprint = function() {
            cleanupPrintStyles();
        };

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === 'letterhead' || urlParams.get('letterhead') === '1') {
                setTimeout(function() {
                    printA4(true);
                }, 400);
            } else if (urlParams.get('print') === 'a4' || urlParams.get('print') === 'simple') {
                setTimeout(function() {
                    printA4(false);
                }, 400);
            } else if (urlParams.get('print') === 'receipt') {
                setTimeout(function() {
                    printReceipt();
                }, 400);
            }
        });

        function toggleInvoiceHeading() {
            var wrapper = document.getElementById('invoiceTitleBadgeWrapper');
            var badge = document.getElementById('invoiceBadgeTitle');
            var statusText = document.getElementById('headingStatusText');
            if (!wrapper || !badge || !statusText) return;

            if (wrapper.classList.contains('d-none')) {
                wrapper.classList.remove('d-none');
                badge.innerText = 'INVOICE';
                statusText.innerText = 'INVOICE';
                statusText.className = 'fw-bold text-success';
            } else {
                wrapper.classList.add('d-none');
                badge.innerText = '';
                statusText.innerText = 'None (Simple)';
                statusText.className = 'text-muted';
            }
        }

        function handleGoBack() {
            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.get('from') === 'pos' || (document.referrer && document.referrer.indexOf('/pos') !== -1)) {
                window.location.href = "{{ route('pos.index') }}";
                return;
            }

            if (window.opener && !window.opener.closed) {
                window.close();
                setTimeout(function() {
                    window.location.href = "{{ route('sale.index') }}";
                }, 150);
                return;
            }

            if (window.history.length > 1 && document.referrer && document.referrer.indexOf(window.location.host) !== -1 && !document.referrer.includes('/sales/store')) {
                window.history.back();
                return;
            }

            window.location.href = "{{ route('sale.index') }}";
        }
    </script>

    <script src="{{ asset('assets/vendors/bootstrap5/js/bootstrap.bundle.min.js') }}"></script>
</body>

</html>
