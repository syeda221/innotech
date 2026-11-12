<?php

namespace App\Services;

use App\Http\Controllers\PurchaseController;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductSerial;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for reading product/warehouse/variant stock balances
 * and keeping product_batches synchronized on stock adjustments and returns.
 */
class StockBalanceService
{
    /**
     * Parse structured variants from product->color JSON.
     */
    public function parseVariants(Product $product): array
    {
        if (empty($product->color)) {
            return [];
        }

        try {
            $raw = $product->color;
            if (is_string($raw)) {
                $b64 = base64_decode($raw, true);
                if ($b64 !== false && (str_starts_with(trim($b64), '[') || str_starts_with(trim($b64), '{'))) {
                    $raw = $b64;
                }
                $decoded = json_decode($raw, true);
                if (is_string($decoded)) {
                    $decoded2 = json_decode($decoded, true);
                    if (is_array($decoded2)) {
                        $decoded = $decoded2;
                    }
                }
            } else {
                $decoded = $raw;
            }

            if (is_array($decoded) && count($decoded) > 0 && isset($decoded[0]['name'])) {
                return array_values($decoded);
            }
        } catch (\Throwable $e) {
        }

        return [];
    }

    /**
     * Sum physical pieces from warehouse_stocks for a product and optional warehouse.
     */
    public function getWarehousePieces(Product $product, $warehouseId = 'all'): float
    {
        $stocks = $product->relationLoaded('warehouseStocks')
            ? $product->warehouseStocks
            : $product->warehouseStocks()->get();

        if ($warehouseId && $warehouseId !== 'all') {
            $stocks = $stocks->where('warehouse_id', (int) $warehouseId);
        }

        $ppb = $product->pieces_per_box > 0 ? (float) $product->pieces_per_box : 1.0;
        $total = 0.0;

        foreach ($stocks as $ws) {
            $tp = (float) ($ws->total_pieces ?? 0);
            if ($tp != 0.0) {
                $total += $tp;
            } elseif ((float) ($ws->quantity ?? 0) != 0.0) {
                $total += ((float) $ws->quantity) * $ppb;
            }
        }

        return $total;
    }

    /**
     * Format clean numeric display value without redundant trailing zeros.
     */
    public function cleanNumber(float $value): float
    {
        return (float) round($value, 4);
    }

    /**
     * Decode a variant payload stored in sale_items / purchase_items / return items color column.
     */
    public function decodeItemVariant($itemColor): array
    {
        if (empty($itemColor)) {
            return [];
        }

        if (is_array($itemColor)) {
            return $itemColor;
        }

        $b64Decoded = base64_decode((string) $itemColor, true);
        if ($b64Decoded !== false) {
            $json = json_decode($b64Decoded, true);
            if (is_array($json)) {
                return $json;
            }
        }

        $json = json_decode((string) $itemColor, true);
        return is_array($json) ? $json : [];
    }

    /**
     * Match a transaction line item to a product variant definition.
     */
    public function matchItemVariant($item, array $variant): bool
    {
        $itemColor = $item->color ?? '';
        if (empty($itemColor)) {
            return false;
        }

        $itemVariant = $this->decodeItemVariant($itemColor);
        if (empty($itemVariant)) {
            return strtolower(trim((string) $itemColor)) === strtolower(trim((string) ($variant['color'] ?? '')));
        }

        $vBarcode = trim((string) ($variant['barcode'] ?? ''));
        $itemBarcode = trim((string) ($itemVariant['barcode'] ?? ''));
        if ($vBarcode !== '' && $itemBarcode !== '') {
            return $vBarcode === $itemBarcode;
        }

        if (isset($variant['is_base_variant']) && isset($itemVariant['is_base_variant'])) {
            if ((int) $variant['is_base_variant'] !== (int) $itemVariant['is_base_variant']) {
                return false;
            }
        }

        if (isset($variant['conv_factor']) && isset($itemVariant['conv_factor'])) {
            $vConv = (float) $variant['conv_factor'];
            $itemConv = (float) $itemVariant['conv_factor'];
            if ($vConv > 0 && $itemConv > 0 && abs($vConv - $itemConv) > 0.0001) {
                return false;
            }
        }

        $vUnit = strtolower(trim((string) ($variant['unit'] ?? '')));
        $itemUnit = strtolower(trim((string) ($itemVariant['unit'] ?? '')));
        if ($vUnit !== '' && $itemUnit !== '' && $vUnit !== $itemUnit) {
            return false;
        }

        $vColor = strtolower(trim((string) ($variant['color'] ?? '-')));
        $vSize = strtolower(trim((string) ($variant['size'] ?? '-')));
        $vName = strtolower(trim((string) ($variant['name'] ?? '')));

        $itemVColor = strtolower(trim((string) ($itemVariant['color'] ?? ($itemVariant['color_val'] ?? '-'))));
        $itemVSize = strtolower(trim((string) ($itemVariant['size'] ?? ($itemVariant['size_val'] ?? '-'))));
        $itemVName = strtolower(trim((string) ($itemVariant['name'] ?? '')));

        if ($vColor === '') $vColor = '-';
        if ($vSize === '') $vSize = '-';
        if ($itemVColor === '') $itemVColor = '-';
        if ($itemVSize === '') $itemVSize = '-';

        $colorSizeMatch = ($vColor === $itemVColor && $vSize === $itemVSize);

        if ($vName !== '' && $itemVName !== '') {
            return $colorSizeMatch && ($vName === $itemVName);
        }

        return $colorSizeMatch;
    }

    /**
     * Match a stock_movements adjustment record to a variant by its note.
     */
    public function matchAdjustmentVariant($adjItem, array $variant): bool
    {
        $note = strtolower((string) ($adjItem->note ?? ''));
        if ($note === '' || $note === 'initial stock' || str_contains($note, 'opening stock')) {
            return false;
        }

        $vName = strtolower(trim((string) ($variant['name'] ?? '')));
        if ($vName !== '' && str_contains($note, $vName)) {
            return true;
        }

        $vSize = strtolower(trim((string) ($variant['size'] ?? '-')));
        $vColor = strtolower(trim((string) ($variant['color'] ?? '-')));

        $sizeMatch = ($vSize === '-' || $vSize === '')
            ? true
            : preg_match('/\b' . preg_quote($vSize, '/') . '\b/i', $note) === 1;
        $colorMatch = ($vColor === '-' || $vColor === '')
            ? true
            : preg_match('/\b' . preg_quote($vColor, '/') . '\b/i', $note) === 1;

        return $sizeMatch && $colorMatch && (($vSize !== '-' && $vSize !== '') || ($vColor !== '-' && $vColor !== ''));
    }

    /**
     * Build unified stock and ledger breakdown for a product (single or variant rows).
     */
    public function getProductBreakdown(Product $product, $warehouseId = 'all', array $dateFilters = [], bool $includeLedger = true): array
    {
        $parsedVariants = $this->parseVariants($product);
        $whPieces = max(0.0, $this->getWarehousePieces($product, $warehouseId));
        $unitName = ($product->size_mode === 'by_bandal') ? 'Bundal' : ($product->unit->name ?? $this->defaultUnitForMode($product->size_mode));
        $ppb = $product->pieces_per_box > 0 ? (float) $product->pieces_per_box : 1.0;
        $isCartonModeProduct = (in_array($product->size_mode, ['by_cartons', 'by_bandal']) || strtolower($unitName) === 'carton');

        if (count($parsedVariants) === 0) {
            $ledger = $includeLedger
                ? $this->computeNonVariantLedger($product, $warehouseId, $whPieces, $dateFilters)
                : ['initial' => 0.0, 'purchased' => 0.0, 'purchase_amount' => 0.0, 'sold' => 0.0, 'sale_amount' => 0.0, 'returned_qty' => 0.0, 'p_returned' => 0.0, 'adjustments' => 0.0];

            return [[
                'variant'            => null,
                'variant_key'        => null,
                'name'               => $product->item_name,
                'display_name'       => $product->item_name,
                'size'               => '-',
                'color'              => '-',
                'unit_name'          => $unitName,
                'size_mode'          => $product->size_mode ?: 'std',
                'is_carton_mode'     => $isCartonModeProduct,
                'ppb'                => $ppb,
                'is_variant'         => false,
                'alert'              => null,
                'purch_price'        => null,
                'initial_stock'      => $ledger['initial'],
                'purchased'          => $ledger['purchased'],
                'purchase_amount'    => $ledger['purchase_amount'],
                'sold'               => $ledger['sold'],
                'sale_amount'        => $ledger['sale_amount'],
                'returned_qty'       => $ledger['returned_qty'],
                'purch_returned_qty' => $ledger['p_returned'],
                'adjustments'        => $ledger['adjustments'],
                'balance'            => $this->cleanNumber($whPieces),
            ]];
        }

        if ($product->size_mode === 'by_kg') {
            $parentLedger = $includeLedger
                ? $this->computeNonVariantLedger($product, $warehouseId, $whPieces, $dateFilters)
                : ['initial' => 0.0, 'purchased' => 0.0, 'purchase_amount' => 0.0, 'sold' => 0.0, 'sale_amount' => 0.0, 'returned_qty' => 0.0, 'p_returned' => 0.0, 'adjustments' => 0.0];

            $rows = [];
            foreach ($parsedVariants as $v) {
                $vName = $v['name'] ?? $product->item_name;
                $vSize = $v['size'] ?? '-';
                $vColor = $v['color'] ?? '-';
                $vUnitName = $v['unit'] ?? $unitName;
                $factor = isset($v['conv_factor']) && (float) $v['conv_factor'] > 0 ? (float) $v['conv_factor'] : 1.0;

                if ($factor != 1.0 && !isset($v['unit'])) {
                    $vUnitName = 'Pcs';
                }

                $balance = ($factor == 1.0) ? $whPieces : (float) floor($whPieces / $factor);

                $rows[] = [
                    'variant'            => $v,
                    'variant_key'        => trim($vName) . '|' . trim($vSize) . '|' . trim($vColor),
                    'name'               => $vName,
                    'display_name'       => $this->buildVariantDisplayName($vName, $vSize, $vColor),
                    'size'               => $vSize,
                    'color'              => $vColor,
                    'unit_name'          => $vUnitName,
                    'size_mode'          => $product->size_mode ?: 'std',
                    'is_carton_mode'     => false,
                    'ppb'                => $ppb,
                    'is_variant'         => true,
                    'alert'              => isset($v['alert']) && $v['alert'] !== '' ? (float) $v['alert'] : null,
                    'purch_price'        => isset($v['purch_price']) && $v['purch_price'] !== '' ? (float) $v['purch_price'] : null,
                    'initial_stock'      => $parentLedger['initial'] / $factor,
                    'purchased'          => $parentLedger['purchased'] / $factor,
                    'purchase_amount'    => $parentLedger['purchase_amount'],
                    'sold'               => $parentLedger['sold'] / $factor,
                    'sale_amount'        => $parentLedger['sale_amount'],
                    'returned_qty'       => $parentLedger['returned_qty'] / $factor,
                    'purch_returned_qty' => $parentLedger['p_returned'] / $factor,
                    'adjustments'        => $parentLedger['adjustments'] / $factor,
                    'balance'            => $this->cleanNumber($balance),
                ];
            }

            return $rows;
        }

        // Standard / Carton / Serial / Batch variant product
        $tx = $includeLedger || count($parsedVariants) > 1
            ? $this->fetchVariantTransactions($product->id, $warehouseId, $dateFilters)
            : null;

        $rows = [];
        $rawLedgers = [];

        foreach ($parsedVariants as $idx => $v) {
            $vName = $v['name'] ?? $product->item_name;
            $vSize = $v['size'] ?? '-';
            $vColor = $v['color'] ?? '-';
            $vKey = trim($vName) . '|' . trim($vSize) . '|' . trim($vColor);
            $vUnitName = $v['unit'] ?? $unitName;
            if ($product->size_mode === 'by_bandal' && strtolower($vUnitName) === 'carton') {
                $vUnitName = 'Bundal';
            }
            $isCartonMode = (in_array($product->size_mode, ['by_cartons', 'by_bandal']) || strtolower($vUnitName) === 'carton');
            $vPpb = $ppb;
            if ($isCartonMode && isset($v['conv_factor']) && (float) $v['conv_factor'] > 0) {
                $vPpb = (float) $v['conv_factor'];
            }
            if ($vPpb <= 0) $vPpb = 1.0;

            $purchased = 0.0; $purchaseAmount = 0.0;
            $pReturned = 0.0;
            $sold = 0.0; $saleAmount = 0.0;
            $returnedQty = 0.0;
            $adjustments = 0.0;

            if ($tx !== null) {
                [$purchased, $purchaseAmount] = $this->sumVariantPurchases($tx['purchases'], $v, $vPpb);
                $pReturned = $this->sumVariantPurchaseReturns($tx['purchaseReturns'], $v);
                [$sold, $saleAmount] = $this->sumVariantSales($tx['sales'], $v);
                $returnedQty = $this->sumVariantSaleReturns($tx['saleReturns'], $tx['saleItemsMap'], $v);
                foreach ($tx['adjustments'] as $adjItem) {
                    if ($this->matchAdjustmentVariant($adjItem, $v)) {
                        $adjustments += (float) $adjItem->qty;
                    }
                }
            }

            $vRawInit = $this->parseVariantInitialPieces($v, $isCartonMode, $vPpb);
            $rawLedgers[$idx] = max(0.0, $vRawInit + $purchased - $sold + $returnedQty - $pReturned);

            $rows[$idx] = [
                'variant'            => $v,
                'variant_key'        => $vKey,
                'name'               => $vName,
                'display_name'       => $this->buildVariantDisplayName($vName, $vSize, $vColor),
                'size'               => $vSize,
                'color'              => $vColor,
                'unit_name'          => $vUnitName,
                'size_mode'          => $product->size_mode ?: 'std',
                'is_carton_mode'     => $isCartonMode,
                'ppb'                => $vPpb,
                'is_variant'         => true,
                'alert'              => isset($v['alert']) && $v['alert'] !== '' ? (float) $v['alert'] : null,
                'purch_price'        => isset($v['purch_price']) && $v['purch_price'] !== '' ? (float) $v['purch_price'] : null,
                'initial_stock'      => 0.0,
                'purchased'          => $purchased,
                'purchase_amount'    => $purchaseAmount,
                'sold'               => $sold,
                'sale_amount'        => $saleAmount,
                'returned_qty'       => $returnedQty,
                'purch_returned_qty' => $pReturned,
                'adjustments'        => $adjustments,
                'balance'            => 0.0,
            ];
        }

        if (count($rows) === 1) {
            // Single variant owns 100% of physical warehouse stock.
            $rows[0]['balance'] = $this->cleanNumber($whPieces);
            $rows[0]['initial_stock'] = max(0.0, $rows[0]['balance'] - $rows[0]['purchased'] + $rows[0]['sold'] - $rows[0]['returned_qty'] + $rows[0]['purch_returned_qty'] - $rows[0]['adjustments']);
            return $rows;
        }

        // Multiple variants: reconcile variant balances so sum === physical warehouse stock ($whPieces)
        $assigned = $this->resolveMultiVariantBalances($product, $rows, $rawLedgers, $whPieces, $warehouseId);
        foreach ($rows as $idx => &$row) {
            $row['balance'] = $this->cleanNumber($assigned[$idx] ?? 0.0);
            $row['initial_stock'] = max(0.0, $row['balance'] - $row['purchased'] + $row['sold'] - $row['returned_qty'] + $row['purch_returned_qty'] - $row['adjustments']);
        }
        unset($row);

        return $rows;
    }

    /**
     * Resolve available stock for a specific sale item / variant within a warehouse.
     */
    public function getAvailableForSaleItem(Product $product, $warehouseId, ?string $itemColor = null): float
    {
        $breakdown = $this->getProductBreakdown($product, $warehouseId, [], false);
        if (count($breakdown) === 1) {
            return (float) $breakdown[0]['balance'];
        }

        $dummyItem = (object) ['color' => $itemColor];
        foreach ($breakdown as $entry) {
            if (!empty($entry['variant']) && $this->matchItemVariant($dummyItem, $entry['variant'])) {
                return (float) $entry['balance'];
            }
        }

        return max(0.0, $this->getWarehousePieces($product, $warehouseId));
    }

    /**
     * Keep product_batches synced when warehouse stock changes via return/adjustment/manual edit.
     */
    public function syncBatchStockOnDelta(Product $product, int $warehouseId, float $deltaQty, ?string $variantKey = null, ?int $batchId = null, ?string $batchNo = null, string $reason = 'Stock Sync'): void
    {
        if ($product->tracking_type !== 'batch' || abs($deltaQty) < 0.0001) {
            return;
        }

        if ($deltaQty > 0) {
            $batch = null;
            if ($batchId) {
                $batch = ProductBatch::where('id', $batchId)->lockForUpdate()->first();
            }
            if (!$batch && $batchNo) {
                $batch = ProductBatch::where('product_id', $product->id)
                    ->where('warehouse_id', $warehouseId)
                    ->where('batch_no', $batchNo)
                    ->lockForUpdate()
                    ->first();
            }
            if (!$batch) {
                $q = ProductBatch::where('product_id', $product->id)
                    ->where('warehouse_id', $warehouseId);
                if ($variantKey) {
                    $q->where('variant_key', $variantKey);
                }
                $batch = $q->orderBy('id', 'desc')->lockForUpdate()->first();
            }

            if ($batch) {
                $batch->increment('qty', $deltaQty);
            } else {
                ProductBatch::create([
                    'product_id'   => $product->id,
                    'variant_key'  => $variantKey,
                    'warehouse_id' => $warehouseId,
                    'batch_no'     => $batchNo ?: ('BATCH-' . date('Ymd')),
                    'qty'          => $deltaQty,
                    'cost_price'   => $product->purchase_price_per_piece ?? 0,
                    'remarks'      => $reason,
                ]);
            }
            return;
        }

        // Negative delta: deduct from specific batch first, then FIFO across warehouse batches
        $remaining = abs($deltaQty);
        $targetBatches = collect();

        if ($batchId) {
            $b = ProductBatch::where('id', $batchId)->where('qty', '>', 0)->lockForUpdate()->first();
            if ($b) $targetBatches->push($b);
        } elseif ($batchNo) {
            $b = ProductBatch::where('product_id', $product->id)
                ->where('warehouse_id', $warehouseId)
                ->where('batch_no', $batchNo)
                ->where('qty', '>', 0)
                ->lockForUpdate()
                ->first();
            if ($b) $targetBatches->push($b);
        }

        if ($targetBatches->isEmpty()) {
            $q = ProductBatch::where('product_id', $product->id)
                ->where('warehouse_id', $warehouseId)
                ->where('qty', '>', 0);
            if ($variantKey) {
                $q->where('variant_key', $variantKey);
            }
            $targetBatches = $q->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, expiry_date ASC, id ASC')
                ->lockForUpdate()
                ->get();
        }

        foreach ($targetBatches as $batch) {
            if ($remaining <= 0) {
                break;
            }
            $deduct = min((float) $batch->qty, $remaining);
            if ($deduct > 0) {
                $batch->decrement('qty', $deduct);
                $remaining -= $deduct;
            }
        }
    }

    private function defaultUnitForMode(?string $sizeMode): string
    {
        return match ($sizeMode) {
            'by_kg'      => 'Kg',
            'by_gm'      => 'Gm',
            'by_ton'     => 'Ton',
            'by_meter'   => 'Meter',
            'by_feet'    => 'Ft',
            'by_cartons' => 'Carton',
            'by_bandal'  => 'Bundal',
            'by_size'    => 'M²',
            default      => 'Pcs',
        };
    }

    private function buildVariantDisplayName(string $vName, string $vSize, string $vColor): string
    {
        $parts = array_filter([$vSize, $vColor], fn ($val) => !empty($val) && trim($val) !== '-' && trim($val) !== '');
        return $vName . (!empty($parts) ? ' (' . implode(' | ', $parts) . ')' : '');
    }

    private function computeNonVariantLedger(Product $product, $warehouseId, float $whPieces, array $dateFilters): array
    {
        $dateFrom = $dateFilters['from'] ?? null;
        $dateTo = $dateFilters['to'] ?? null;
        $ppb = $product->pieces_per_box > 0 ? (float) $product->pieces_per_box : 1.0;

        // Purchased
        $purQuery = DB::table('purchase_items as pi')
            ->join('purchases as pur', 'pur.id', '=', 'pi.purchase_id')
            ->where('pi.product_id', $product->id)
            ->whereIn('pur.status_purchase', ['approved', 'Returned', 'Partial']);
        if ($warehouseId && $warehouseId !== 'all') {
            $purQuery->where('pur.warehouse_id', $warehouseId);
        }
        if ($dateFrom) $purQuery->whereDate('pur.created_at', '>=', $dateFrom);
        if ($dateTo)   $purQuery->whereDate('pur.created_at', '<=', $dateTo);
        $purchasesList = $purQuery->select('pi.qty', 'pi.unit', 'pi.pieces_per_box', 'pi.boxes_qty', 'pi.loose_qty', 'pi.line_total')->get();

        $purchased = 0.0;
        $purchaseAmount = 0.0;
        foreach ($purchasesList as $pItem) {
            $purchased += $this->extractPurchasePieces($pItem, $ppb);
            $purchaseAmount += (float) $pItem->line_total;
        }

        // Sold
        $saleStatsQuery = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.product_id', $product->id)
            ->whereIn('sales.sale_status', ['posted', 'returned']);
        if ($warehouseId && $warehouseId !== 'all') {
            $saleStatsQuery->where('sale_items.warehouse_id', $warehouseId);
        }
        if ($dateFrom) $saleStatsQuery->whereDate('sale_items.created_at', '>=', $dateFrom);
        if ($dateTo)   $saleStatsQuery->whereDate('sale_items.created_at', '<=', $dateTo);
        $saleStats = $saleStatsQuery->selectRaw('COALESCE(SUM(sale_items.total_pieces),0) as total_qty, COALESCE(SUM(sale_items.total),0) as total_amount')->first();

        $sold = (float) ($saleStats->total_qty ?? 0);
        $saleAmount = (float) ($saleStats->total_amount ?? 0);

        // Sale returns (source from sale_return_items + legacy sale exchange returns)
        $retQuery = DB::table('sale_return_items as sri')
            ->join('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
            ->where('sri.product_id', $product->id);
        if ($warehouseId && $warehouseId !== 'all') {
            $retQuery->where(function ($q) use ($warehouseId) {
                $q->where('sri.warehouse_id', $warehouseId)->orWhere('sr.warehouse_id', $warehouseId);
            });
        }
        if ($dateFrom) $retQuery->whereDate('sr.created_at', '>=', $dateFrom);
        if ($dateTo)   $retQuery->whereDate('sr.created_at', '<=', $dateTo);
        $returnedQty = (float) $retQuery->sum('sri.qty');

        // Purchase returns
        $pRetQuery = DB::table('purchase_return_items as pri')
            ->join('purchase_returns as pr', 'pr.id', '=', 'pri.purchase_return_id')
            ->where('pri.product_id', $product->id);
        if ($warehouseId && $warehouseId !== 'all') {
            $pRetQuery->where('pr.warehouse_id', $warehouseId);
        }
        if ($dateFrom) $pRetQuery->whereDate('pr.created_at', '>=', $dateFrom);
        if ($dateTo)   $pRetQuery->whereDate('pr.created_at', '<=', $dateTo);
        $pReturned = (float) $pRetQuery->sum('pri.qty');

        // Adjustments
        $adjQuery = DB::table('stock_movements')
            ->where('product_id', $product->id)
            ->where('type', 'adjustment')
            ->where(function ($q) {
                $q->whereNull('ref_type')->orWhereNotIn('ref_type', ['INIT', 'OPENING_STOCK']);
            });
        if ($warehouseId && $warehouseId !== 'all') {
            $adjQuery->where('note', 'like', "%Warehouse #{$warehouseId}%");
        }
        if ($dateFrom) $adjQuery->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo)   $adjQuery->whereDate('created_at', '<=', $dateTo);
        $adjustments = (float) $adjQuery->sum('qty');

        $initial = max(0.0, $whPieces - $purchased + $sold - $returnedQty + $pReturned - $adjustments);

        return [
            'initial'         => $initial,
            'purchased'       => $purchased,
            'purchase_amount' => $purchaseAmount,
            'sold'            => $sold,
            'sale_amount'     => $saleAmount,
            'returned_qty'    => $returnedQty,
            'p_returned'      => $pReturned,
            'adjustments'     => $adjustments,
        ];
    }

    private function fetchVariantTransactions(int $productId, $warehouseId, array $dateFilters): array
    {
        $dateFrom = $dateFilters['from'] ?? null;
        $dateTo = $dateFilters['to'] ?? null;

        $salesQuery = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.product_id', $productId)
            ->whereIn('sales.sale_status', ['posted', 'returned']);
        if ($warehouseId && $warehouseId !== 'all') {
            $salesQuery->where('sale_items.warehouse_id', $warehouseId);
        }
        if ($dateFrom) $salesQuery->whereDate('sale_items.created_at', '>=', $dateFrom);
        if ($dateTo)   $salesQuery->whereDate('sale_items.created_at', '<=', $dateTo);
        $salesList = $salesQuery->select('sale_items.total_pieces', 'sale_items.total', 'sale_items.color')->get()->toArray();

        $webSalesQuery = DB::table('ecommerce_order_items as eoi')
            ->join('ecommerce_orders as eo', 'eo.id', '=', 'eoi.ecommerce_order_id')
            ->where('eoi.product_id', $productId)
            ->where('eo.is_stock_deducted', 1);
        if ($warehouseId && $warehouseId !== 'all' && $warehouseId != 1) {
            $webSalesQuery->whereRaw('1 = 0');
        }
        if ($dateFrom) $webSalesQuery->whereDate('eo.created_at', '>=', $dateFrom);
        if ($dateTo)   $webSalesQuery->whereDate('eo.created_at', '<=', $dateTo);
        foreach ($webSalesQuery->select('eoi.quantity as total_pieces', 'eoi.total', 'eoi.color', 'eoi.size')->get() as $wItem) {
            $salesList[] = (object) [
                'total_pieces' => $wItem->total_pieces,
                'total'        => $wItem->total,
                'color'        => json_encode(['color' => $wItem->color ?: '-', 'size' => $wItem->size ?: '-']),
            ];
        }

        $returnsQuery = DB::table('sale_return_items as sri')
            ->join('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
            ->where('sri.product_id', $productId);
        if ($warehouseId && $warehouseId !== 'all') {
            $returnsQuery->where(function ($q) use ($warehouseId) {
                $q->where('sri.warehouse_id', $warehouseId)->orWhere('sr.warehouse_id', $warehouseId);
            });
        }
        if ($dateFrom) $returnsQuery->whereDate('sr.created_at', '>=', $dateFrom);
        if ($dateTo)   $returnsQuery->whereDate('sr.created_at', '<=', $dateTo);
        $returnsList = $returnsQuery->select('sri.qty', 'sri.color', 'sr.sale_id')->get();

        $saleIds = $returnsList->pluck('sale_id')->filter()->unique()->toArray();
        $saleItemsMap = [];
        if (!empty($saleIds)) {
            foreach (DB::table('sale_items')->whereIn('sale_id', $saleIds)->where('product_id', $productId)->select('sale_id', 'color')->get() as $si) {
                $saleItemsMap[$si->sale_id][] = $si->color;
            }
        }

        $purchasesQuery = DB::table('purchase_items as pi')
            ->join('purchases as pur', 'pur.id', '=', 'pi.purchase_id')
            ->where('pi.product_id', $productId)
            ->whereIn('pur.status_purchase', ['approved', 'Returned', 'Partial']);
        if ($warehouseId && $warehouseId !== 'all') {
            $purchasesQuery->where(function ($q) use ($warehouseId) {
                $q->where('pi.warehouse_id', $warehouseId)
                  ->orWhere(function ($q2) use ($warehouseId) {
                      $q2->whereNull('pi.warehouse_id')->where('pur.warehouse_id', $warehouseId);
                  });
            });
        }
        if ($dateFrom) $purchasesQuery->whereDate('pur.created_at', '>=', $dateFrom);
        if ($dateTo)   $purchasesQuery->whereDate('pur.created_at', '<=', $dateTo);
        $purchasesList = $purchasesQuery->select('pi.qty', 'pi.unit', 'pi.pieces_per_box', 'pi.boxes_qty', 'pi.loose_qty', 'pi.line_total', 'pi.color')->get();

        $pReturnsQuery = DB::table('purchase_return_items as pri')
            ->join('purchase_returns as pr', 'pr.id', '=', 'pri.purchase_return_id')
            ->where('pri.product_id', $productId);
        if ($warehouseId && $warehouseId !== 'all') {
            $pReturnsQuery->where('pr.warehouse_id', $warehouseId);
        }
        if ($dateFrom) $pReturnsQuery->whereDate('pr.created_at', '>=', $dateFrom);
        if ($dateTo)   $pReturnsQuery->whereDate('pr.created_at', '<=', $dateTo);
        $purchaseReturnsList = $pReturnsQuery->select('pri.qty', 'pri.line_total', 'pri.color')->get();

        $adjQuery = DB::table('stock_movements')
            ->where('product_id', $productId)
            ->where('type', 'adjustment')
            ->where(function ($q) {
                $q->whereNull('ref_type')->orWhereNotIn('ref_type', ['INIT', 'OPENING_STOCK']);
            });
        if ($warehouseId && $warehouseId !== 'all') {
            $adjQuery->where('note', 'like', "%Warehouse #{$warehouseId}%");
        }
        if ($dateFrom) $adjQuery->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo)   $adjQuery->whereDate('created_at', '<=', $dateTo);
        $adjList = $adjQuery->select('qty', 'note', 'ref_type')->get();

        return [
            'sales'           => $salesList,
            'saleReturns'     => $returnsList,
            'saleItemsMap'    => $saleItemsMap,
            'purchases'       => $purchasesList,
            'purchaseReturns' => $purchaseReturnsList,
            'adjustments'     => $adjList,
        ];
    }

    private function extractPurchasePieces($pItem, float $defaultPpb): float
    {
        $pUnit = strtolower(trim((string) ($pItem->unit ?? '')));
        $pPPB = (float) ($pItem->pieces_per_box > 0 ? $pItem->pieces_per_box : $defaultPpb);
        if ($pPPB <= 0) $pPPB = 1.0;

        if (in_array($pUnit, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl'])) {
            if (isset($pItem->boxes_qty) && ($pItem->boxes_qty > 0 || $pItem->loose_qty > 0)) {
                return (((int) $pItem->boxes_qty) * $pPPB) + ((int) $pItem->loose_qty);
            }
            [$b, $l] = PurchaseController::parseCartonQty($pItem->qty);
            return ($b * $pPPB) + $l;
        }

        if (in_array($pUnit, ['gm', 'g'])) {
            return ((float) $pItem->qty) / 1000.0;
        }

        return (float) $pItem->qty;
    }

    private function sumVariantPurchases($purchasesList, array $variant, float $defaultPpb): array
    {
        $qty = 0.0;
        $amount = 0.0;
        foreach ($purchasesList as $pItem) {
            if ($this->matchItemVariant($pItem, $variant)) {
                $qty += $this->extractPurchasePieces($pItem, $defaultPpb);
                $amount += (float) ($pItem->line_total ?? 0);
            }
        }
        return [$qty, $amount];
    }

    private function sumVariantPurchaseReturns($purchaseReturnsList, array $variant): float
    {
        $qty = 0.0;
        foreach ($purchaseReturnsList as $prItem) {
            if ($this->matchItemVariant($prItem, $variant)) {
                $qty += (float) $prItem->qty;
            }
        }
        return $qty;
    }

    private function sumVariantSales($salesList, array $variant): array
    {
        $qty = 0.0;
        $amount = 0.0;
        foreach ($salesList as $sItem) {
            if ($this->matchItemVariant($sItem, $variant)) {
                $qty += (float) $sItem->total_pieces;
                $amount += (float) ($sItem->total ?? 0);
            }
        }
        return [$qty, $amount];
    }

    private function sumVariantSaleReturns($returnsList, array $saleItemsMap, array $variant): float
    {
        $qty = 0.0;
        foreach ($returnsList as $rItem) {
            $rColor = $rItem->color;
            if (empty($rColor)) {
                $saleColors = $saleItemsMap[$rItem->sale_id] ?? [];
                $rColor = !empty($saleColors) ? $saleColors[0] : '';
            }
            $rItemCopy = (object) ['qty' => $rItem->qty, 'color' => $rColor];
            if ($this->matchItemVariant($rItemCopy, $variant)) {
                $qty += (float) $rItem->qty;
            }
        }
        return $qty;
    }

    private function parseVariantInitialPieces(array $variant, bool $isCartonMode, float $vPpb): float
    {
        $vRawStock = (string) ($variant['stock'] ?? '0');
        if ($isCartonMode && $vPpb > 1) {
            if (strpos($vRawStock, '.') !== false) {
                $parts = explode('.', $vRawStock);
                return (((int) ($parts[0] ?? 0)) * $vPpb) + ((int) ($parts[1] ?? 0));
            }
            return (float) $vRawStock * $vPpb;
        }
        return (float) $vRawStock;
    }

    private function resolveMultiVariantBalances(Product $product, array $rows, array $rawLedgers, float $whPieces, $warehouseId): array
    {
        $count = count($rows);
        $assigned = array_fill(0, $count, 0.0);

        if ($whPieces <= 0) {
            return $assigned;
        }

        // If batch/serial records track per-variant quantities and reconcile cleanly with warehouse stock, use them directly
        if ($product->tracking_type === 'batch' || $product->tracking_type === 'serial') {
            $tracked = [];
            foreach ($rows as $idx => $r) {
                $vKey = $r['variant_key'];
                $cleanVKey = str_replace(' ', '', $vKey);
                $vName = $r['name'];
                if ($product->tracking_type === 'batch') {
                    $q = ProductBatch::where('product_id', $product->id);
                    $q->where(function ($sub) use ($vKey, $cleanVKey, $vName) {
                        $sub->where('variant_key', $vKey)->orWhere('variant_key', $cleanVKey)->orWhere('variant_key', $vName);
                    });
                    if ($warehouseId && $warehouseId !== 'all') {
                        $q->where('warehouse_id', $warehouseId);
                    }
                    $tracked[$idx] = (float) $q->sum('qty');
                } else {
                    $q = ProductSerial::where('product_id', $product->id)->where('status', 'available');
                    $q->where(function ($sub) use ($vKey, $cleanVKey, $vName) {
                        $sub->where('variant_key', $vKey)->orWhere('variant_key', $cleanVKey)->orWhere('variant_key', $vName);
                    });
                    if ($warehouseId && $warehouseId !== 'all') {
                        $q->where('warehouse_id', $warehouseId);
                    }
                    $tracked[$idx] = (float) $q->count();
                }
            }
            $trackedSum = array_sum($tracked);
            if ($trackedSum > 0 && abs($trackedSum - $whPieces) < 0.01) {
                return $tracked;
            }
        }

        $totalRaw = array_sum($rawLedgers);
        if ($totalRaw > 0) {
            if (abs($totalRaw - $whPieces) < 0.01) {
                return $rawLedgers;
            }
            foreach ($rawLedgers as $idx => $val) {
                $assigned[$idx] = round(($val / $totalRaw) * $whPieces, 2);
            }
            return $assigned;
        }

        // Fallback: assign unallocated warehouse stock to the base variant (avoid double-counting across all rows)
        $baseIdx = 0;
        foreach ($rows as $idx => $r) {
            if (!empty($r['variant']['is_base_variant'])) {
                $baseIdx = $idx;
                break;
            }
        }
        $assigned[$baseIdx] = $whPieces;
        return $assigned;
    }
}
