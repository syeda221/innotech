<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoice - {{ $purchase->invoice_no }}</title>
    <!-- Bootstrap 5 CSS -->
    <link href="{{ asset('assets/vendors/bootstrap5/css/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0f172a;
            --accent-color: #4f46e5;
            --border-color: #000000;
            --text-color: #0f172a;
        }

        body {
            background-color: #f1f5f9;
            color: var(--text-color);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            font-size: 13px;
            margin: 0;
            padding: 0;
        }

        .invoice-container {
            max-width: 210mm;
            margin: 20px auto;
            background: #ffffff;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            position: relative;
        }

        .company-info {
            text-align: center;
            margin-bottom: 16px;
        }

        .company-name {
            font-size: 22px;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 2px;
            letter-spacing: -0.02em;
        }

        .invoice-title {
            text-align: center;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            color: #1e293b;
            margin: 12px 0 16px 0;
            letter-spacing: 2px;
        }

        .info-box {
            border: 1px solid var(--border-color);
            padding: 10px 12px;
            height: 100%;
            border-radius: 8px;
            background-color: #ffffff;
        }

        .info-box-header {
            font-weight: 800;
            border-bottom: 1.5px solid var(--border-color);
            margin-bottom: 6px;
            padding-bottom: 4px;
            color: var(--primary-color);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .info-label {
            font-weight: 700;
            color: #334155;
            min-width: 70px;
            display: inline-block;
        }

        /* Desktop Invoice Table */
        .invoice-table-wrap {
            overflow-x: auto;
            margin-top: 16px;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-table th {
            background-color: #ffffff;
            color: #000000;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 800;
            padding: 8px 6px;
            border: 1px solid var(--border-color);
        }

        .invoice-table td {
            border: 1px solid var(--border-color);
            padding: 8px 6px;
            vertical-align: middle;
            font-size: 12px;
        }

        .invoice-table tbody tr:nth-of-type(even) {
            background-color: #f8fafc;
        }

        /* Mobile Item Cards View (< 768px) */
        .mobile-invoice-items {
            display: none;
            flex-direction: column;
            gap: 10px;
            margin-top: 16px;
        }

        .mob-item-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .mob-item-hdr {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .mob-item-title {
            font-weight: 700;
            font-size: 0.92rem;
            color: #0f172a;
        }

        .mob-item-code {
            font-family: monospace;
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .mob-item-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.84rem;
            padding-top: 8px;
            border-top: 1px solid #f1f5f9;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .totals-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .totals-table .total-row td {
            border-top: 2px solid var(--primary-color);
            font-weight: 800;
            font-size: 14px;
            color: var(--primary-color);
        }

        .signature-area {
            margin-top: 40px;
            border-top: 1px solid #000000;
            width: 180px;
            text-align: center;
            padding-top: 6px;
            font-weight: 600;
        }

        /* Action bar */
        .action-bar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        /* Print Media Queries */
        @media print {
            body {
                background: #ffffff !important;
                margin: 0;
                padding: 0;
            }

            .action-bar {
                display: none !important;
            }

            .invoice-container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 10px !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
            }

            .invoice-table-wrap {
                display: block !important;
            }

            .mobile-invoice-items {
                display: none !important;
            }

            @page {
                margin: 5mm;
            }
        }

        /* Mobile Breakpoints (< 768px) */
        @media (max-width: 768px) {
            body {
                background-color: #ffffff;
            }
            .invoice-container {
                margin: 0;
                border-radius: 0;
                box-shadow: none;
                padding: 14px;
            }
            .invoice-table-wrap {
                display: none !important;
            }
            .mobile-invoice-items {
                display: flex !important;
            }
            .info-box {
                margin-bottom: 8px;
            }
        }
    </style>
</head>

<body>

    <!-- Sticky Responsive Action Bar -->
    <div class="action-bar no-print">
        <div class="container-fluid d-flex align-items-center justify-content-between gap-2 px-2 px-md-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary text-white fw-bold px-2 py-1" style="font-size: 0.75rem; white-space: nowrap;">#{{ $purchase->invoice_no }}</span>
                @if(!empty($purchase->purchase_order_no))
                    <span class="badge bg-secondary text-white fw-medium px-2 py-1" style="font-size: 0.75rem; white-space: nowrap;">Ref: {{ $purchase->purchase_order_no }}</span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                <button onclick="window.print()" class="btn btn-primary btn-sm px-3 fw-bold shadow-sm d-flex align-items-center gap-1" style="border-radius: 8px; font-size: 0.8rem; white-space: nowrap;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-printer-fill" viewBox="0 0 16 16">
                        <path d="M0 9a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V9zm4-6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2H4V3z" />
                        <path d="M2.5 14.5A1.5 1.5 0 0 1 1 13V9a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v4a1.5 1.5 0 0 1-1.5 1.5h-13z" />
                    </svg>
                    Print
                </button>
                <a href="{{ route('Purchase.home') }}" class="btn btn-outline-secondary btn-sm px-2 px-md-3 fw-semibold text-nowrap" style="border-radius: 8px; font-size: 0.8rem;">Back</a>
            </div>
        </div>
    </div>

    <div class="invoice-container">
        <!-- Company Header -->
        <div class="company-info">
            <div class="company-name">{{ \App\Models\Setting::get('company_name', 'prowave technogies') }} - {{ date('Y') }}</div>
            <div style="font-size: 12px; color: #475569;">{{ \App\Models\Setting::get('company_address', 'Hyderabad') }}</div>
        </div>

        <div class="invoice-title">Purchase Invoice</div>

        <!-- Info Grid -->
        <div class="row g-2 mb-3">
            <!-- Left Box: Vendor Info -->
            <div class="col-12 col-md-4">
                <div class="info-box">
                    <div class="info-box-header">Vendor Details</div>
                    <div style="font-size: 13px; font-weight: 800; color: #0f172a;">
                        {{ $purchase->vendor->name ?? 'N/A' }}
                    </div>
                    @if(!empty($purchase->vendor->address))
                        <div style="font-size: 11px; color: #475569;">{{ $purchase->vendor->address }}</div>
                    @endif
                    @if(!empty($purchase->vendor->phone))
                        <div class="text-dark small" style="font-size: 11px;">Mob: {{ $purchase->vendor->phone }}</div>
                    @endif
                </div>
            </div>

            <!-- Middle Box: Details -->
            <div class="col-12 col-md-4">
                <div class="info-box">
                    <div class="info-box-header">Details</div>
                    <div><span class="info-label">Type:</span> {{ $purchase->status_purchase ?? 'Confirmed' }}</div>
                    <div><span class="info-label">Warehouse:</span> {{ $purchase->warehouse->warehouse_name ?? 'Main' }}</div>
                </div>
            </div>

            <!-- Right Box: Invoice Specifics -->
            <div class="col-12 col-md-4">
                <div class="info-box">
                    <div class="info-box-header">Reference</div>
                    <div><span class="info-label">Inv #:</span> <strong>{{ $purchase->invoice_no ?: ('GRN-' . str_pad($purchase->id, 3, '0', STR_PAD_LEFT)) }}</strong></div>
                    <div><span class="info-label">Date:</span> {{ $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('d/M/Y') : '-' }}</div>
                    <div><span class="info-label">Vendor Inv #:</span> <strong>{{ $purchase->purchase_order_no ?: ($purchase->note ?: '—') }}</strong></div>
                </div>
            </div>
        </div>

        <!-- Remarks -->
        @if ($purchase->note)
            <div class="row mb-3">
                <div class="col-12">
                    <div class="info-box" style="min-height: auto; padding: 6px 10px; background-color: #f8fafc; font-style: italic; border-color: #cbd5e1;">
                        <strong>Note:</strong> {{ $purchase->note }}
                    </div>
                </div>
            </div>
        @endif

        @php
            $sumCartons = 0;
            $sumLoosePieces = 0;
            $sumTotalPieces = 0;
            $hasCartonMode = false;

            foreach ($purchase->items as $it) {
                $rawU = strtolower(trim($it->unit ?? ''));
                $itPPB = (float) ($it->pieces_per_box > 0 ? $it->pieces_per_box : ($it->product->pieces_per_box ?? 1));

                if (!empty($it->color)) {
                    $dec = base64_decode($it->color, true);
                    $vD = ($dec !== false) ? json_decode($dec, true) : json_decode($it->color, true);
                    if (is_array($vD)) {
                        if (!empty($vD['conv_factor']) && (float)$vD['conv_factor'] > 0) {
                            $itPPB = (float)$vD['conv_factor'];
                        }
                        if (empty($rawU) && !empty($vD['unit'])) {
                            $rawU = strtolower(trim($vD['unit']));
                        }
                    }
                }
                if ($itPPB <= 0) $itPPB = 1;

                $rawQtyStr = (string) ($it->qty ?? '0');
                $isPiece = in_array($rawU, ['pcs', 'pc', 'piece']);
                $isCtn = in_array($rawU, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl']) || (!$isPiece && in_array($it->size_mode, ['by_cartons', 'by_bandal']));

                if ($isCtn) {
                    $hasCartonMode = true;
                    if ($it->boxes_qty > 0 || $it->loose_qty > 0) {
                        $b = (int) $it->boxes_qty;
                        $l = (int) $it->loose_qty;
                    } else {
                        [$b, $l] = \App\Http\Controllers\PurchaseController::parseCartonQty($it->qty);
                    }
                    $sumCartons += $b;
                    $sumLoosePieces += $l;
                    $sumTotalPieces += (($b * $itPPB) + $l);
                } elseif ($itPPB > 1) {
                    $hasCartonMode = true;
                    $itQty = (float) $it->qty;
                    $b = floor($itQty / $itPPB);
                    $l = $itQty - ($b * $itPPB);
                    $sumCartons += $b;
                    $sumLoosePieces += $l;
                    $sumTotalPieces += $itQty;
                } else {
                    $itQty = (float) $it->qty;
                    $sumLoosePieces += $itQty;
                    $sumTotalPieces += $itQty;
                }
            }

            if ($hasCartonMode && $sumCartons > 0) {
                if ($sumLoosePieces > 0) {
                    $cartonPcsDisplay = "{$sumCartons} Ctn/Bndl + {$sumLoosePieces} Pcs (" . number_format($sumTotalPieces) . " Total Pcs)";
                    $cartonPcsShort = "{$sumCartons} Ctn/Bndl + {$sumLoosePieces} Pcs";
                } else {
                    $cartonPcsDisplay = "{$sumCartons} Ctn/Bndl (" . number_format($sumTotalPieces) . " Total Pcs)";
                    $cartonPcsShort = "{$sumCartons} Ctn/Bndl";
                }
            } elseif ($hasCartonMode && $sumLoosePieces > 0) {
                $cartonPcsDisplay = "{$sumLoosePieces} Pcs";
                $cartonPcsShort = "{$sumLoosePieces} Pcs";
            } else {
                $cartonPcsDisplay = number_format($sumTotalPieces) . " Pcs";
                $cartonPcsShort = number_format($sumTotalPieces) . " Pcs";
            }
        @endphp

        <!-- Desktop & Print Table View -->
        <div class="invoice-table-wrap">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 12%">Code</th>
                        <th class="text-start" style="width: 33%">Description</th>
                        <th class="text-center" style="width: 12%">Qty</th>
                        <th class="text-center" style="width: 10%">UOM</th>
                        <th class="text-end" style="width: 10%">Price</th>
                        <th class="text-end" style="width: 10%">Disc</th>
                        <th class="text-end" style="width: 13%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchase->items as $item)
                        @php
                            $height = $item->length ?? 0;
                            $width = $item->width ?? 0;

                            $piecesPerBox = (float) ($item->pieces_per_box > 0 ? $item->pieces_per_box : ($item->product->pieces_per_box ?? 1));
                            if (!empty($item->color)) {
                                $decColor = base64_decode($item->color, true);
                                $vColorData = ($decColor !== false) ? json_decode($decColor, true) : json_decode($item->color, true);
                                if (is_array($vColorData) && !empty($vColorData['conv_factor']) && (float)$vColorData['conv_factor'] > 0) {
                                    $piecesPerBox = (float)$vColorData['conv_factor'];
                                }
                            }
                            if ($piecesPerBox <= 0) $piecesPerBox = 1;

                            $m2PerPiece = (float) ($item->pieces_per_m2 ?? 0);
                            $m2PerBox = $m2PerPiece * $piecesPerBox;

                            $rawUnit = strtolower(trim($item->unit ?? ''));
                            $isPiece = in_array($rawUnit, ['pcs', 'pc', 'piece']);
                            $isCarton = in_array($rawUnit, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl']) || (!$isPiece && (in_array($item->size_mode, ['by_cartons', 'by_bandal'])));
                            $isWeight = in_array($rawUnit, ['kg', 'gm', 'g']);

                            if ($isCarton) {
                                if ($item->boxes_qty > 0 || $item->loose_qty > 0) {
                                    $boxes = (int) $item->boxes_qty;
                                    $loosePieces = (int) $item->loose_qty;
                                } else {
                                    [$boxes, $loosePieces] = \App\Http\Controllers\PurchaseController::parseCartonQty($item->qty);
                                }
                                $totalPieces = ($boxes * $piecesPerBox) + $loosePieces;
                                $ctnLbl = ($item->size_mode === 'by_bandal') ? 'Bndl' : 'Ctn';
                                $cartonFull = ($item->size_mode === 'by_bandal') ? 'Bundal' : 'Carton';
                                $cartonFulls = ($item->size_mode === 'by_bandal') ? 'Bundals' : 'Cartons';
                                $uomDisplay = $cartonFull;
                                if ($boxes > 0 && $loosePieces > 0) {
                                    $qtyDisplay = "{$boxes} {$ctnLbl} + {$loosePieces} Pcs";
                                } elseif ($boxes > 0) {
                                    $qtyDisplay = ($boxes == 1 ? "1 {$cartonFull}" : ($boxes . " {$cartonFulls}"));
                                } elseif ($loosePieces > 0) {
                                    $qtyDisplay = "{$loosePieces} Pcs";
                                } else {
                                    $qtyDisplay = "0 {$cartonFulls}";
                                }
                                $subQtyText = '(' . $totalPieces . ' pcs)';
                            } elseif ($isPiece) {
                                $totalPieces = (float) $item->qty;
                                $boxes = $piecesPerBox > 1 ? floor($totalPieces / $piecesPerBox) : 0;
                                $loosePieces = $piecesPerBox > 1 ? ($totalPieces % $piecesPerBox) : $totalPieces;
                                if ($item->size_mode === 'by_bandal') {
                                    $uomDisplay = 'Bundal';
                                } elseif ($item->size_mode === 'by_cartons') {
                                    $uomDisplay = 'Carton';
                                } else {
                                    $uomDisplay = 'Pcs';
                                }
                                $qtyDisplay = $totalPieces . ' Pcs';
                                $subQtyText = '';
                            } elseif ($isWeight) {
                                $totalPieces = (float) $item->qty;
                                $uomDisplay = $item->unit ?? 'Kg';
                                $qtyDisplay = $item->qty . ' ' . $uomDisplay;
                                $subQtyText = '';
                            } else {
                                $totalPieces = (float) $item->qty;
                                $uomDisplay = $item->unit ?? 'Pcs';
                                $qtyDisplay = $item->qty . ' ' . $uomDisplay;
                                $subQtyText = '(' . $totalPieces . ' pcs)';
                            }

                            $totalM2Line = $m2PerPiece * $totalPieces;
                            $sizeMode = $item->size_mode ?? ($item->product->size_mode ?? 'by_pieces');
                        @endphp
                        @php
                            $baseProductName = $item->product->item_name ?? 'Item';
                            $variantNameDisplay = '';
                            $variantDetails = [];

                            if (!empty($item->color)) {
                                $decodedColor = base64_decode($item->color, true);
                                $vData = ($decodedColor !== false) ? json_decode($decodedColor, true) : null;
                                if (empty($vData) || !is_array($vData)) {
                                    $vData = json_decode($item->color, true);
                                }
                                if (!empty($vData) && is_array($vData)) {
                                    $vName = trim($vData['name'] ?? ($vData['variant_name'] ?? ''));
                                    $vColorName = trim($vData['color'] ?? '');
                                    $vSizeName = trim($vData['size'] ?? '');

                                    if ($vName !== '' && strcasecmp($vName, $baseProductName) !== 0) {
                                        $variantNameDisplay = $vName;
                                    }

                                    if ($vSizeName !== '' && $vSizeName !== '-') {
                                        $variantDetails[] = 'Size: ' . $vSizeName;
                                    }
                                    if ($vColorName !== '' && $vColorName !== '-') {
                                        $variantDetails[] = 'Color: ' . $vColorName;
                                    }
                                } elseif (is_string($item->color) && trim($item->color) !== '' && trim($item->color) !== '-') {
                                    $variantDetails[] = trim($item->color);
                                }
                            }

                            if ($variantNameDisplay !== '') {
                                if (stripos($variantNameDisplay, $baseProductName) !== false) {
                                    $fullItemTitle = $variantNameDisplay;
                                } else {
                                    $fullItemTitle = $baseProductName . ' — ' . $variantNameDisplay;
                                }
                            } else {
                                $fullItemTitle = $baseProductName;
                            }

                            if (!empty($variantDetails)) {
                                $fullItemTitle .= ' (' . implode(' | ', $variantDetails) . ')';
                            }
                        @endphp
                        <tr>
                            <td class="text-center" style="vertical-align: middle; font-size: 11px; font-weight: bold;">
                                {{ $item->product->item_code ?? '-' }}
                            </td>

                            <td class="text-start">
                                <div style="font-weight: bold; font-size: 12px; margin-bottom: 2px;">
                                    {{ $fullItemTitle }}
                                </div>
                                <div style="font-size: 11px; color: #475569;">
                                    @if ($sizeMode == 'by_size')
                                        @if ($height > 0 || $width > 0)
                                            Dims: {{ $width }}x{{ $height }}
                                        @endif
                                    @endif
                                </div>
                            </td>

                            <td class="text-center" style="vertical-align: middle;">
                                <div style="font-weight: bold; color: #0f172a;">
                                    {{ $qtyDisplay }}
                                </div>
                                @if (!empty($subQtyText))
                                    <small class="text-muted" style="font-size: 10px;">{{ $subQtyText }}</small>
                                @endif
                            </td>

                            <td class="text-center" style="vertical-align: middle;">
                                <span class="fw-bold">{{ $uomDisplay }}</span>
                            </td>

                            <td class="text-end" style="vertical-align: middle;">
                                {{ number_format($item->price, 2) }}
                            </td>

                            <td class="text-end" style="vertical-align: middle; color: #dc2626;">
                                @if ($item->item_discount > 0)
                                    @php
                                        $grossLine = $item->line_total + $item->item_discount;
                                        $discPercent = $grossLine > 0 ? ($item->item_discount / $grossLine) * 100 : 0;
                                    @endphp
                                    <div style="font-size: 10px; line-height: 1;">{{ number_format($discPercent, 1) }}%</div>
                                    <div style="font-size: 11px; font-weight: bold;">{{ number_format($item->item_discount, 2) }}</div>
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-end fw-bold" style="vertical-align: middle;">
                                {{ number_format($item->line_total, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background-color: #f8fafc; font-weight: 800; border-top: 2px solid var(--border-color);">
                        <td colspan="2" class="text-end text-uppercase" style="font-size: 11px; padding: 10px 8px;">Total Quantity:</td>
                        <td class="text-center" style="font-size: 12px; color: var(--primary-color); padding: 10px 6px;">
                            {{ $cartonPcsShort }}
                        </td>
                        <td></td>
                        <td></td>
                        <td class="text-end" style="color: #dc2626;">
                            @php
                                $totalInlineDisc = $purchase->items->sum('item_discount');
                            @endphp
                            @if ($totalInlineDisc > 0)
                                {{ number_format($totalInlineDisc, 2) }}
                            @endif
                        </td>
                        <td class="text-end" style="font-size: 13px; color: var(--primary-color); padding: 10px 6px;">
                            {{ number_format($purchase->subtotal, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Mobile Items View (< 768px) -->
        <div class="mobile-invoice-items">
            @foreach ($purchase->items as $item)
                @php
                    $piecesPerBox = (float) ($item->pieces_per_box > 0 ? $item->pieces_per_box : ($item->product->pieces_per_box ?? 1));
                    $rawUnit = strtolower(trim($item->unit ?? ''));
                    $isPiece = in_array($rawUnit, ['pcs', 'pc', 'piece']);
                    $isCarton = in_array($rawUnit, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl']) || (!$isPiece && (in_array($item->size_mode, ['by_cartons', 'by_bandal'])));

                    if ($isCarton) {
                        if ($item->boxes_qty > 0 || $item->loose_qty > 0) {
                            $boxes = (int) $item->boxes_qty;
                            $loosePieces = (int) $item->loose_qty;
                        } else {
                            [$boxes, $loosePieces] = \App\Http\Controllers\PurchaseController::parseCartonQty($item->qty);
                        }
                        $ctnLbl = ($item->size_mode === 'by_bandal') ? 'Bndl' : 'Ctn';
                        $cartonFull = ($item->size_mode === 'by_bandal') ? 'Bundal' : 'Carton';
                        $cartonFulls = ($item->size_mode === 'by_bandal') ? 'Bundals' : 'Cartons';
                        if ($boxes > 0 && $loosePieces > 0) {
                            $qtyDisplay = "{$boxes} {$ctnLbl} + {$loosePieces} Pcs";
                        } elseif ($boxes > 0) {
                            $qtyDisplay = ($boxes == 1 ? "1 {$cartonFull}" : ($boxes . " {$cartonFulls}"));
                        } elseif ($loosePieces > 0) {
                            $qtyDisplay = "{$loosePieces} Pcs";
                        } else {
                            $qtyDisplay = "0 {$cartonFulls}";
                        }
                    } elseif ($isPiece) {
                        $qtyDisplay = (float) $item->qty . ' Pcs';
                    } else {
                        $qtyDisplay = (float) $item->qty . ' ' . ($item->unit ?? 'Pcs');
                    }

                    $sizeMode = $item->size_mode ?? 'by_pieces';
                    $baseProductName = $item->product->item_name ?? 'Item';
                    $variantNameDisplay = '';
                    $variantDetails = [];

                    if (!empty($item->color)) {
                        $decodedColor = base64_decode($item->color, true);
                        $vData = ($decodedColor !== false) ? json_decode($decodedColor, true) : null;
                        if (empty($vData) || !is_array($vData)) {
                            $vData = json_decode($item->color, true);
                        }
                        if (!empty($vData) && is_array($vData)) {
                            $vName = trim($vData['name'] ?? ($vData['variant_name'] ?? ''));
                            $vColorName = trim($vData['color'] ?? '');
                            $vSizeName = trim($vData['size'] ?? '');

                            if ($vName !== '' && strcasecmp($vName, $baseProductName) !== 0) {
                                $variantNameDisplay = $vName;
                            }

                            if ($vSizeName !== '' && $vSizeName !== '-') {
                                $variantDetails[] = 'Size: ' . $vSizeName;
                            }
                            if ($vColorName !== '' && $vColorName !== '-') {
                                $variantDetails[] = 'Color: ' . $vColorName;
                            }
                        } elseif (is_string($item->color) && trim($item->color) !== '' && trim($item->color) !== '-') {
                            $variantDetails[] = trim($item->color);
                        }
                    }

                    if ($variantNameDisplay !== '') {
                        if (stripos($variantNameDisplay, $baseProductName) !== false) {
                            $fullItemTitle = $variantNameDisplay;
                        } else {
                            $fullItemTitle = $baseProductName . ' — ' . $variantNameDisplay;
                        }
                    } else {
                        $fullItemTitle = $baseProductName;
                    }

                    if (!empty($variantDetails)) {
                        $fullItemTitle .= ' (' . implode(' | ', $variantDetails) . ')';
                    }
                @endphp
                <div class="mob-item-card">
                    <div class="mob-item-hdr">
                        <div class="mob-item-title">{{ $fullItemTitle }}</div>
                        <span class="mob-item-code">#{{ $item->product->item_code ?? '—' }}</span>
                    </div>

                    <div class="mob-item-details">
                        <div>
                            <span class="fw-bold text-dark">{{ $qtyDisplay }}</span>
                            <span class="text-muted ms-1">@ Rs. {{ number_format($item->price, 2) }}</span>
                        </div>
                        <div class="text-end">
                            @if ($item->item_discount > 0)
                                <div class="text-danger small" style="font-size: 0.72rem;">Disc: Rs. {{ number_format($item->item_discount, 2) }}</div>
                            @endif
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">Rs. {{ number_format($item->line_total, 2) }}</div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="mob-item-card bg-light" style="border: 1.5px solid var(--primary-color);">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark">Total Quantity:</span>
                    <span class="fw-bold text-primary font-monospace">{{ $cartonPcsDisplay }}</span>
                </div>
            </div>
        </div>

        <!-- Footer / Totals Section -->
        <div class="row mt-3">
            <div class="col-12 col-md-7 mb-3 mb-md-0">
                <div class="mt-md-4 pt-2">
                    <div class="signature-area">
                        Authorized Signature
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-5">
                <div class="info-box" style="border: 1px solid #cbd5e1; padding: 10px; border-radius: 8px;">
                    <table class="totals-table">
                        <tr>
                            <td class="text-muted fw-bold">Total Cartons / Pcs</td>
                            <td class="text-end fw-bold text-primary font-monospace" style="font-size: 12px;">{{ $cartonPcsDisplay }}</td>
                        </tr>
                        <tr>
                            <td class="text-dark">Subtotal</td>
                            <td class="text-end font-monospace">Rs. {{ number_format($purchase->subtotal, 2) }}</td>
                        </tr>
                        @if (($purchase->total_gst ?? 0) > 0)
                        <tr>
                            <td class="text-muted fw-bold">Excl. GST Subtotal</td>
                            <td class="text-end font-monospace">Rs. {{ number_format($purchase->total_exclusive_gst ?: ($purchase->subtotal - $purchase->total_gst), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-success fw-bold">GST Amount</td>
                            <td class="text-end text-success fw-bold font-monospace">+Rs. {{ number_format($purchase->total_gst, 2) }}</td>
                        </tr>
                        @endif
                        @if ($purchase->additional_discount > 0)
                            <tr>
                                <td>Additional Discount</td>
                                <td class="text-end text-danger font-monospace">
                                    @php
                                        $billDiscPercent = $purchase->subtotal > 0 ? ($purchase->additional_discount / $purchase->subtotal) * 100 : 0;
                                    @endphp
                                    <span style="font-size: 10px;" class="me-1">({{ number_format($billDiscPercent, 1) }}%)</span>
                                    -{{ number_format($purchase->additional_discount, 2) }}
                                </td>
                            </tr>
                        @endif
                        @if ($purchase->extra_cost > 0)
                            <tr>
                                <td>Extra Cost</td>
                                <td class="text-end font-monospace">Rs. {{ number_format($purchase->extra_cost, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="total-row" style="background-color: #f8fafc;">
                            <td>Total Net</td>
                            <td class="text-end font-monospace">Rs. {{ number_format($purchase->net_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Paid Amount</td>
                            <td class="text-end text-success fw-bold font-monospace">Rs. {{ number_format($purchase->paid_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Bill Due</td>
                            <td class="text-end fw-bold font-monospace text-danger">
                                Rs. {{ number_format($purchase->net_amount - $purchase->paid_amount, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-dark">Previous Balance</td>
                            <td class="text-end text-dark font-monospace">Rs. {{ number_format($previousBalance, 2) }}</td>
                        </tr>
                        <tr style="border-top: 2px solid #0f172a;">
                            <td class="fw-bold text-danger">Total Closing Balance</td>
                            <td class="text-end fw-bold text-danger font-monospace" style="font-size: 1.05rem;">
                                Rs. {{ number_format($currentBalance, 2) }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

    </div>
</body>

</html>
