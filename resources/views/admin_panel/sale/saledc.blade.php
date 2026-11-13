<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Challan - {{ $sale->invoice_no }}</title>
    <!-- Use Bootstrap for grid and utilities -->
    <link href="{{ asset('assets/vendors/bootstrap5/css/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --primary-color: #000;
            --accent-color: #000;
            --border-color: #000;
            --text-color: #000;
        }

        body {
            background-color: #f8f9fa;
            color: #000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Pure black override for all text elements */
        .text-primary, .text-danger, .text-success, .text-warning, .text-info, .text-secondary, .text-muted, .text-dark {
            color: #000 !important;
        }

        .invoice-container {
            max-width: 210mm;
            margin: 10px auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            min-height: 297mm;
            position: relative;
            color: #000;
        }

        .company-info {
            text-align: center;
            margin-bottom: 12px;
            color: #000;
        }

        .company-name {
            font-size: 24px;
            font-weight: 800;
            color: #000;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }

        .invoice-title {
            text-align: center;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            color: #000;
            margin: 12px 0 16px 0;
            letter-spacing: 1.5px;
        }

        .info-box {
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            height: 100%;
            border-radius: 6px;
            background-color: #fff;
            color: #000;
        }

        .info-box-header {
            font-weight: 700;
            border-bottom: 1px solid #cbd5e1;
            margin-bottom: 8px;
            padding-bottom: 4px;
            color: #000;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-label {
            font-weight: 600;
            color: #000;
            min-width: 65px;
            display: inline-block;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .invoice-table th {
            background-color: #fff;
            color: #000;
            font-weight: 700;
            font-size: 11px;
            padding: 6px 6px;
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
        }

        .invoice-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: middle;
            font-size: 11px;
            color: #000;
        }

        .invoice-table tbody tr:nth-of-type(even) {
            background-color: #fff;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer-section {
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }

        .terms-box {
            font-size: 11px;
            color: #000;
        }

        .terms-box ul {
            padding-left: 20px;
            margin-bottom: 0;
            color: #000;
        }

        .terms-box li {
            margin-bottom: 2px;
            color: #000;
        }

        .signature-area {
            margin-top: 40px;
            border-top: 1px solid #000;
            width: 180px;
            text-align: center;
            padding-top: 5px;
            color: #000;
        }

        .print-btn-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        @media print {
            body, body * {
                background: #fff;
                margin: 0;
                padding: 0;
                color: #000 !important;
                -webkit-text-fill-color: #000 !important;
            }

            .text-primary, .text-danger, .text-success, .text-warning, .text-info, .text-secondary, .text-muted, .text-dark, .info-label, .invoice-title, .company-name, .terms-box {
                color: #000 !important;
                -webkit-text-fill-color: #000 !important;
            }

            .invoice-container {
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 10px;
                box-shadow: none;
                border: none;
                min-height: auto;
            }

            .print-btn-container {
                display: none;
            }

            .no-print {
                display: none;
            }

            @page {
                margin: 5mm;
            }
        }
    </style>
</head>

<body>

    <!-- Print Button -->
    <div class="print-btn-container">
        <button onclick="window.print()" class="btn btn-primary btn-sm shadow fw-bold">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                class="bi bi-printer-fill me-2" viewBox="0 0 16 16">
                <path
                    d="M0 9a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V9zm4-6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2H4V3z" />
                <path d="M2.5 14.5A1.5 1.5 0 0 1 1 13V9a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v4a1.5 1.5 0 0 1-1.5 1.5h-13z" />
            </svg>
            Print
        </button>
        <a href="javascript:void(0)" onclick="handleGoBack()" class="btn btn-secondary btn-sm shadow ms-2 fw-bold">Back</a>
    </div>

    <div class="invoice-container">
        @php
            $dcLogo = \App\Models\Setting::get('company_logo') ?? \App\Models\Setting::get('web_site_logo');
        @endphp
        <!-- Company Header -->
        <div class="company-info">
            @if(!empty($dcLogo) && (file_exists(public_path($dcLogo)) || file_exists(base_path($dcLogo))))
                <div style="text-align: center; margin-bottom: 8px;">
                    <img src="{{ asset($dcLogo) }}" alt="Logo" style="max-height: 85px; max-width: 260px; object-fit: contain;">
                </div>
            @endif
            <div class="company-name">{{ \App\Models\Setting::get('company_name', config('app.name', 'Innot Tech')) }}</div>
            @if(\App\Models\Setting::get('company_address'))
                <div style="font-size: 12px;">{{ \App\Models\Setting::get('company_address') }}</div>
            @endif
            @if(\App\Models\Setting::get('company_phone'))
                <p style="margin-bottom:0;">{{ \App\Models\Setting::get('company_phone') }}</p>
            @endif
        </div>

        <div class="invoice-title">Delivery Challan</div>

        <!-- Info Grid -->
        <div class="row g-2 mb-2">
            <!-- Left Box: Customer Info -->
            <div class="col-6">
                <div class="info-box">
                    <div class="info-box-header">Deliver To</div>
                    @if($sale->customer_relation?->customer_id)
                        <div style="font-size: 11px; color: #555;">
                            Code: <strong>{{ $sale->customer_relation->customer_id }}</strong>
                        </div>
                    @endif
                    <div>
                        <span class="info-label">Name:</span>
                        <strong>{{ $sale->walkin_name ?? ($sale->customer_relation->customer_name ?? 'Walking Customer') }}</strong>
                    </div>
                    <div>
                        <span class="info-label">Address:</span>
                        <span style="font-size:11px;">{{ $sale->customer_relation->address ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="info-label">Mob:</span>
                        <span style="font-size:11px;">{{ $sale->customer_relation->mobile ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- Right Box: Invoice Specifics -->
            <div class="col-6">
                <div class="info-box">
                    <div class="info-box-header">Reference</div>
                    <div><span class="info-label">DC #:</span> <strong>{{ $sale->invoice_no }}</strong></div>
                    <div><span class="info-label">Date:</span> {{ $sale->created_at ? $sale->created_at->format('d/m/Y') : date('d/m/Y') }}</div>
                    @if($sale->reference)
                        <div style="margin-top:4px; padding-top:4px; border-top:1px dashed #ddd;">
                            <span class="info-label">Remarks:</span>
                            <span style="font-size:11px; color:#333;">{{ $sale->reference }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Remarks -->
        @if ($sale->return_note)
            <div class="row mb-2">
                <div class="col-12">
                    <div class="info-box"
                        style="min-height: auto; padding: 4px 8px; background-color: #f1f5f9; font-style: italic;">
                        <strong>Note:</strong> {{ $sale->return_note }}
                    </div>
                </div>
            </div>
        @endif

        <!-- Table -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%">S.No</th>
                    <th class="text-start" style="width: 45%">Description of Items</th>
                    <th class="text-center" style="width: 10%">Qty</th>
                    <th class="text-center" style="width: 20%">MODEL</th>
                    <th class="text-center" style="width: 20%">MAKE & ORIGIN</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($saleItems as $item)
                    @php
                        $vName = $item['variant_name'] ?? '';
                        $vSize = (!empty($item['size_val']) && $item['size_val'] !== '-') ? $item['size_val'] : '';
                        $vColor = (!empty($item['color_val']) && $item['color_val'] !== '-') ? $item['color_val'] : '';
                        
                        $vExtra = [];
                        if ($vColor) $vExtra[] = $vColor;
                        $vExtraStr = count($vExtra) > 0 ? ' (' . implode(', ', $vExtra) . ')' : '';

                        $productTitle = $item['item_name'];
                        if ($vName && strtolower(trim($vName)) !== strtolower(trim($productTitle))) {
                            $productTitle .= ' — ' . $vName;
                        }
                        $productTitle .= $vExtraStr;

                        $dcBatchNo = $item['batch_no'] ?? null;
                        $dcSerialsRaw = $item['serials'] ?? null;
                        $dcSerialsList = [];
                        if (!empty($dcSerialsRaw)) {
                            if (is_array($dcSerialsRaw)) {
                                $dcSerialsList = $dcSerialsRaw;
                            } elseif (is_string($dcSerialsRaw)) {
                                $decodedDc = json_decode($dcSerialsRaw, true);
                                if (is_array($decodedDc)) {
                                    $dcSerialsList = $decodedDc;
                                } else {
                                    $dcSerialsList = array_filter(array_map('trim', explode(',', $dcSerialsRaw)));
                                }
                            }
                        }

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

                        // Model Display value for MODEL column
                        $modelDisplay = '';
                        if ($vSize) {
                            $modelDisplay = $vSize;
                        } elseif (!empty($subDescriptions) && count($subDescriptions) > 0) {
                            $modelDisplay = implode(', ', $subDescriptions);
                        }

                        // Make & Origin value for MAKE & ORIGIN column
                        $brandName = $item['brand'] ?? ($item['product']['brand']['name'] ?? '');
                        $originName = $item['origin'] ?? ($item['product']['origin'] ?? ($item['product']['country'] ?? ''));
                        $makeOriginDisplay = $brandName;
                        if (!empty($originName) && !str_contains(strtolower($brandName), strtolower($originName))) {
                            $makeOriginDisplay .= ($makeOriginDisplay ? ', ' : '') . $originName;
                        }

                        $totalPieces = (int) ($item['total_pieces'] ?? $item['qty'] ?? 0);
                        $qtyVal = (float)($item['qty_box'] ?? $item['qty'] ?? $totalPieces);
                        $sizeMode = $item['size_mode'] ?? 'std';
                    @endphp
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>

                        <td class="text-start">
                            <div style="font-weight: 700; color: #000; font-size: 11px;">{{ $productTitle }}</div>

                            @if(!empty($dcBatchNo))
                                <div style="font-size: 11px; color: #1e40af; margin-top: 2px; font-weight: 700;">
                                    <i class="fas fa-layer-group me-1"></i>Batch: {{ $dcBatchNo }}
                                </div>
                            @endif

                            @if(!empty($dcSerialsList) && count($dcSerialsList) > 0)
                                <div style="font-size: 11px; color: #000; margin-top: 2px; font-weight: 800;">
                                    SN #: {{ implode(', ', $dcSerialsList) }}
                                </div>
                            @endif

                            @if(!empty($subDescriptions) && count($subDescriptions) > 0)
                                <div style="font-size: 10px; color: #1e293b; margin-top: 2px; padding-left: 2px;">
                                    @foreach($subDescriptions as $sd)
                                        @if(trim($sd))
                                            <div style="line-height: 1.4;"><span style="color: #000; margin-right: 4px; font-weight: bold;">•</span>{{ trim($sd) }}</div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>

                        <td class="text-center fw-bold">
                            {{ $qtyVal == (int)$qtyVal ? (int)$qtyVal : number_format($qtyVal, 2) }}
                        </td>

                        <td class="text-center fw-bold">
                            {{ $modelDisplay ?: '-' }}
                        </td>

                        <td class="text-center fw-bold">
                            {{ $makeOriginDisplay ?: '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Footer -->
        <div class="row mt-3">
            <div class="col-7">
                <div class="terms-box pt-2">
                    <p class="fw-bold mb-1">Terms & Conditions:</p>
                    <ul style="font-size: 10px;">
                        @php
                            $dcTerms = \App\Models\Setting::get('invoice_terms', "Please check items upon delivery.\nThis is a Delivery Challan, not a final invoice.\nSign and stamp to confirm receipt of goods in good condition.");
                            $termLines = explode("\n", $dcTerms);
                        @endphp
                        @foreach($termLines as $line)
                            @if(trim($line))
                                <li>{{ trim($line) }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>

                <div class="mt-4 pt-2">
                    <div class="d-flex justify-content-between" style="max-width: 600px;">
                        <div>
                            <div class="signature-area">
                                Authorized Signature
                            </div>
                        </div>
                        <div>
                            <div class="signature-area">
                                Receiver's Signature
                            </div>
                        </div>
                    </div>

                    <div class="small text-muted mt-2" style="font-size: 10px;">
                        Printed on: {{ date('d/m/Y h:i A') }}
                    </div>
                </div>
            </div>

            <div class="col-5">
                <!-- No totals for DC -->
            </div>
        </div>

    </div>

    <script>
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
</body>

</html>
