<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Receipt - {{ $purchase->invoice_no }}</title>
    <style>
        @media print {
            body {
                width: 72mm;
                margin: 0;
                padding: 0;
                font-family: 'Courier New', Courier, monospace;
            }

            .no-print {
                display: none;
            }

            @page {
                size: 72mm auto;
                margin: 0;
            }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
            width: 72mm;
            /* Preview width */
            margin: 0 auto;
            padding: 5px;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .sub-header {
            font-size: 10px;
            margin-bottom: 5px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .items-table th,
        .items-table td {
            text-align: left;
            vertical-align: top;
            padding: 2px 0;
        }

        .items-table th {
            border-bottom: 1px solid #000;
            font-size: 10px;
        }

        .text-end {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .totals-section {
            margin-top: 10px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
        }

        .btn-print {
            padding: 10px;
            text-align: center;
            background: #eee;
            margin-bottom: 10px;
            cursor: pointer;
            display: block;
            text-decoration: none;
            color: #333;
            font-weight: bold;
            border: 1px solid #ccc;
        }
    </style>
</head>

<body>

    <a href="javascript:window.print()" class="btn-print no-print">PRINT RECEIPT</a>

    <div class="header">
        <div class="company-name">{{ \App\Models\Setting::get('company_name', 'prowave technogies') }}</div>
        <div class="sub-header">{{ \App\Models\Setting::get('company_address', 'Hyderabad') }}</div>
        <div class="sub-header">{{ \App\Models\Setting::get('company_phone', '0327-9226901') }}</div>
    </div>

    <div class="divider"></div>

    <div class="info-row">
        <span>Inv #: {{ $purchase->invoice_no ?: ('GRN-' . str_pad($purchase->id, 3, '0', STR_PAD_LEFT)) }}</span>
        <span>{{ $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('d/M/Y') : '-' }}</span>
    </div>
    <div class="info-row">
        <span>Vendor Inv#: {{ $purchase->purchase_order_no ?: ($purchase->note ?: '—') }}</span>
    </div>
    <div class="info-row">
        <span>Vendor: {{ Str::limit($purchase->vendor->name ?? 'N/A', 15) }}</span>
    </div>
    @if (auth()->check())
        <div class="info-row">
            <span>User: {{ auth()->user()->name }}</span>
        </div>
    @endif

    <div class="divider"></div>

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
            } else {
                $cartonPcsDisplay = "{$sumCartons} Ctn/Bndl (" . number_format($sumTotalPieces) . " Total Pcs)";
            }
        } elseif ($hasCartonMode && $sumLoosePieces > 0) {
            $cartonPcsDisplay = "{$sumLoosePieces} Pcs";
        } else {
            $cartonPcsDisplay = number_format($sumTotalPieces) . " Pcs";
        }
    @endphp

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 45%;">Item</th>
                <th style="width: 20%;" class="text-center">Qty</th>
                <th style="width: 35%;" class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchase->items as $item)
                @php
                    $rawUnit = strtolower(trim($item->unit ?? ''));
                    $isPiece = in_array($rawUnit, ['pcs', 'pc', 'piece']);
                    $isCarton = in_array($rawUnit, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl']) || (!$isPiece && (in_array($item->size_mode, ['by_cartons', 'by_bandal'])));

                    if ($isCarton) {
                        if ($item->boxes_qty > 0 || $item->loose_qty > 0) {
                            $b = (int) $item->boxes_qty;
                            $l = (int) $item->loose_qty;
                        } else {
                            [$b, $l] = \App\Http\Controllers\PurchaseController::parseCartonQty($item->qty);
                        }
                        $ctnLbl = ($item->size_mode === 'by_bandal') ? 'Bndl' : 'Ctn';
                        if ($b > 0 && $l > 0) {
                            $qtyDisplay = "{$b} {$ctnLbl} + {$l} Pcs";
                        } elseif ($b > 0) {
                            $qtyDisplay = "{$b} {$ctnLbl}";
                        } elseif ($l > 0) {
                            $qtyDisplay = "{$l} Pcs";
                        } else {
                            $qtyDisplay = "0 {$ctnLbl}";
                        }
                    } elseif (in_array($rawUnit, ['pcs', 'pc', 'piece'])) {
                        $qtyDisplay = ((float) $item->qty) . ' Pcs';
                    } else {
                        $qtyDisplay = ((float) $item->qty) . ' ' . ($item->unit ?? 'Pcs');
                    }
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
                                $variantDetails[] = $vSizeName;
                            }
                            if ($vColorName !== '' && $vColorName !== '-') {
                                $variantDetails[] = $vColorName;
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
                    <td colspan="3" style="font-weight: 600;">
                        {{ $fullItemTitle }}
                    </td>
                </tr>
                <tr>
                    <td style="font-size: 10px; padding-left: 5px;">
                        {{ number_format($item->price, 2) }} x
                    </td>
                    <td class="text-center">{{ $qtyDisplay }}</td>
                    <td class="text-end">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <div class="info-row" style="font-weight: bold;">
            <span>Total Qty:</span>
            <span>{{ $cartonPcsDisplay }}</span>
        </div>

        <div class="info-row">
            <span>Subtotal:</span>
            <span>{{ number_format($purchase->subtotal, 2) }}</span>
        </div>

        @if ($purchase->additional_discount > 0)
            <div class="info-row">
                <span>Additional Discount:</span>
                <span>-{{ number_format($purchase->additional_discount, 2) }}</span>
            </div>
        @endif

        @if ($purchase->extra_cost > 0)
            <div class="info-row">
                <span>Extra Cost:</span>
                <span>{{ number_format($purchase->extra_cost, 2) }}</span>
            </div>
        @endif

        <div class="total-row" style="font-size: 14px; border-top: 1px dashed #000; margin-top: 5px; padding-top: 2px;">
            <span>Total Net:</span>
            <span>{{ number_format($purchase->net_amount, 2) }}</span>
        </div>

        <div class="divider"></div>

        <div class="info-row">
            <span>Paid:</span>
            <span>{{ number_format($purchase->paid_amount, 2) }}</span>
        </div>

        <div class="info-row" style="font-weight: bold;">
            <span>Bill Due:</span>
            <span>{{ number_format($purchase->net_amount - $purchase->paid_amount, 2) }}</span>
        </div>

        <div class="info-row">
            <span>Previous Bal:</span>
            <span>{{ number_format($previousBalance, 2) }}</span>
        </div>

        <div class="info-row" style="font-weight: bold; font-size: 14px; border-top: 1px dashed #000; padding-top: 5px; margin-top: 5px;">
            <span>Total Closing Bal:</span>
            <span>{{ number_format($currentBalance, 2) }}</span>
        </div>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <p>Purchase Record</p>
        <p style="font-size: 9px;">Software by: Antigravity AI</p>
    </div>

</body>

</html>
