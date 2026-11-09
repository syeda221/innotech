@extends('admin_panel.layout.app')

@section('content')
<style>
    /* Force main template to be static and scroll-free on viewport */
    html, body {
        overflow: hidden !important;
        height: 100% !important;
        background-color: #f3f4f6;
    }
    
    .page-container, .main-panel, .content-wrapper, .main-content-inner, .main-content {
        height: calc(100vh - 140px) !important;
        overflow: hidden !important;
        padding-bottom: 0 !important;
        margin-bottom: 0 !important;
    }
    
    footer {
        display: none !important;
    }
    
    /* SCROLLBAR STYLING */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    /* POS LAYOUT WRAPPER */
    .pos-wrapper {
        display: flex;
        gap: 20px;
        height: calc(100vh - 260px);
        margin-top: 10px;
        overflow: hidden;
    }
    
    /* LEFT PANEL: PRODUCTS GRID */
    .pos-products-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        padding: 20px;
        overflow: hidden;
        height: 100%;
    }
    
    .pos-search-box {
        position: relative;
        margin-bottom: 15px;
    }
    
    .pos-search-box input {
        width: 100%;
        padding: 14px 40px 14px 20px;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 500;
        background-color: #f9fafb;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    }
    
    .pos-search-box input:focus {
        background-color: #ffffff;
        border-color: #4f46e5;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        outline: none;
    }
    
    .pos-search-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 16px;
    }
    
    .pos-grid-container {
        flex: 1;
        overflow-y: auto;
        padding-right: 5px;
    }
    
    .pos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 12px;
    }
    
    /* PRODUCT CARD STYLING */
    .product-card {
        background: #ffffff;
        border: 1px solid #f3f4f6;
        border-radius: 16px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        position: relative;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    
    .product-card:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 12px 20px rgba(0,0,0,0.08);
        border-color: #6366f1;
    }
    
    .product-card-image {
        height: 85px;
        width: 100%;
        background-color: #f9fafb;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-bottom: 1px solid #f3f4f6;
        position: relative;
    }
    
    .product-card-image img {
        height: 100%;
        width: 100%;
        object-fit: cover;
    }
    
    .product-card-image i {
        font-size: 32px;
        color: #cbd5e1;
    }
    
    .product-card-body {
        padding: 8px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    
    .product-card-title {
        font-size: 11.5px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
        line-height: 1.2;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 30px;
    }
    
    .product-card-variants-badge {
        font-size: 9.5px;
        font-weight: 700;
        color: #4f46e5;
        background-color: #eeebff;
        padding: 1px 6px;
        border-radius: 99px;
        align-self: flex-start;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .product-card-footer {
        margin-top: auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .product-card-price {
        font-size: 12.5px;
        font-weight: 800;
        color: #111827;
    }
    
    .product-card-stock {
        font-size: 9.5px;
        font-weight: 700;
        color: #10b981;
    }
    
    .product-card-stock.out {
        color: #ef4444;
    }
    
    /* RIGHT PANEL: CART & CHECKOUT */
    .pos-cart-panel {
        width: 400px;
        flex: 0 0 400px;
        display: flex;
        flex-direction: column;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        overflow-y: auto;
        overflow-x: hidden;
        height: 100%;
        min-height: 0;
        border: 1px solid rgba(0,0,0,0.02);
        padding-bottom: 10px;
    }
    
    .pos-cart-header {
        padding: 12px 18px;
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        border-bottom: 2px solid #4f46e5;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .pos-cart-header h5 {
        margin: 0;
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        color: #ffffff !important;
        z-index: 10;
    }
    
    .pos-cart-header .cart-count {
        background-color: #4f46e5;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 99px;
    }
    
    /* CART ITEMS LIST */
    .pos-cart-items {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
        background-color: #f9fafb;
        min-height: 220px;
    }
    
    .cart-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        min-height: 150px;
        color: #9ca3af;
        padding: 30px 15px;
        background: linear-gradient(180deg, #f9fafb 0%, #ffffff 100%);
        border-radius: 12px;
        border: 2px dashed #e5e7eb;
        margin: 10px;
    }
    
    .cart-empty i {
        font-size: 48px;
        margin-bottom: 12px;
        color: #cbd5e1;
        transition: transform 0.3s ease;
    }
    
    .cart-empty:hover i {
        transform: scale(1.1) rotate(-5deg);
        color: #94a3b8;
    }
    
    .cart-item {
        background: #ffffff;
        border-radius: 10px;
        padding: 6px 10px;
        margin-bottom: 6px;
        border: 1px solid #f3f4f6;
        box-shadow: 0 2px 4px rgba(0,0,0,0.01);
        display: flex;
        flex-direction: column;
        position: relative;
    }
    
    .cart-item-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 3px;
    }
    
    .cart-item-name {
        font-size: 12.5px;
        font-weight: 700;
        color: #1f2937;
        max-width: 85%;
        line-height: 1.2;
    }
    
    .cart-item-remove {
        color: #9ca3af;
        cursor: pointer;
        font-size: 14px;
        transition: color 0.2s;
    }
    
    .cart-item-remove:hover {
        color: #ef4444;
    }
    
    .cart-item-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 4px;
    }
    
    /* QUANTITY SELECTORS */
    .qty-controls {
        display: flex;
        align-items: center;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        overflow: hidden;
        background: #ffffff;
    }
    
    .qty-btn {
        border: none;
        background: #f9fafb;
        color: #4b5563;
        width: 28px;
        height: 28px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }
    
    .qty-btn:hover {
        background: #e5e7eb;
    }
    
    .qty-input {
        border: none;
        border-left: 1px solid #e5e7eb;
        border-right: 1px solid #e5e7eb;
        width: 36px;
        height: 28px;
        text-align: center;
        font-size: 13px;
        font-weight: 700;
    }
    
    .qty-input:focus {
        outline: none;
    }
    
    .cart-item-price {
        font-size: 13.5px;
        font-weight: 800;
        color: #111827;
    }
    
    /* CART BILL SUMMARY */
    .pos-cart-summary {
        background-color: #ffffff;
        border-top: 1px solid #e5e7eb;
        padding: 8px 15px;
        flex-shrink: 0;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        font-size: 12.5px;
        margin-bottom: 4px;
        font-weight: 500;
        color: #4b5563;
    }
    
    .summary-row.payable {
        border-top: 1px dashed #cbd5e1;
        padding-top: 4px;
        margin-top: 4px;
        font-size: 15px;
        font-weight: 800;
        color: #111827;
    }
    
    /* Vendor & PAYMENT FIELDS */
    .pos-checkout-section {
        background-color: #ffffff;
        border-top: 1px solid #e5e7eb;
        padding: 8px 15px 15px 15px;
        flex-shrink: 0;
    }
    
    .form-group {
        margin-bottom: 4px;
    }
    
    .form-group label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 2px;
        display: block;
    }
    
    .pos-input {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        height: 36px;
    }
    
    .pos-input:focus {
        border-color: #4f46e5;
        outline: none;
    }
    
    .btn-checkout {
        width: 100%;
        background: linear-gradient(135deg, #10b981, #059669);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        padding: 8px;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        transition: all 0.2s;
        margin-top: 4px;
    }
    
    .btn-checkout:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 15px rgba(16, 185, 129, 0.3);
    }
    
    .btn-checkout:disabled {
        background: #cbd5e1;
        box-shadow: none;
        cursor: not-allowed;
        transform: none;
    }
    
    .btn-back-to-products {
        border-radius: 8px !important;
        border: 1.5px solid #cbd5e1 !important;
        color: #475569 !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
        transition: all 0.2s !important;
        background: #ffffff !important;
        height: 38px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .btn-back-to-products:hover {
        background: #f8fafc !important;
        color: #0f172a !important;
        border-color: #94a3b8 !important;
    }
    
    .Vendor-toggle-btns {
        display: flex;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 4px;
    }
    
    .toggle-btn {
        flex: 1;
        border: none;
        background: #f9fafb;
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    
    .toggle-btn.active {
        background: #1f2937;
        color: #ffffff;
    }
    
    /* MODAL STYLE OVERRIDES */
    .table-responsive {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .variant-add-btn {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 5px 12px;
        border-radius: 6px;
    }
    
    /* ── MOBILE POS SPECIFIC STYLES (< 768px) ── */
    .mobile-pos-bottom-bar {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1040;
        background: #ffffff;
        border-top: 1.5px solid #e2e8f0;
        padding: 10px 16px;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.12);
        align-items: center;
        justify-content: space-between;
    }
    
    .cat-pills-scroll {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 4px 0 10px 0;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 10px;
    }
    .cat-pill {
        flex: 0 0 auto;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #475569;
        padding: 6px 14px;
        border-radius: 99px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .cat-pill.active {
        background: #4f46e5;
        color: #ffffff;
        border-color: #4f46e5;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
    }

    /* RESPONSIVE DESIGN */
    @media (max-width: 991.98px) {
        body { padding-bottom: 75px !important; }

        .pos-wrapper {
            flex-direction: column !important;
            height: auto !important;
            overflow: visible !important;
        }
        html, body {
            overflow: auto !important;
            height: auto !important;
        }
        .page-container, .main-panel, .content-wrapper, .main-content-inner, .main-content {
            height: auto !important;
            overflow: visible !important;
        }
        .pos-products-panel {
            min-height: 400px;
            height: auto;
            padding: 12px !important;
            border-radius: 12px !important;
        }
        .pos-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 10px !important;
        }
        .product-card-image { height: 75px !important; }
        .product-card-title { font-size: 11px !important; }
        .product-card-price { font-size: 11.5px !important; }

        /* Slide-up Cart Drawer on Mobile */
        .pos-cart-panel {
            position: fixed !important;
            top: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            height: 100% !important;
            z-index: 1050 !important;
            border-radius: 0 !important;
            display: none !important;
            box-shadow: none !important;
            overflow-y: auto !important;
        }
        .pos-cart-panel.mobile-drawer-open {
            display: flex !important;
            animation: slideUpMob 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .mobile-pos-bottom-bar {
            display: flex !important;
        }
    }

    @keyframes slideUpMob {
        from { transform: translateY(100%); }
        to   { transform: translateY(0); }
    }
</style>

<div class="main-content">
    <div class="container-fluid p-2">
        <!-- POS Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-3 p-3 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border: 1px solid #f1f5f9;">
            <div>
                <h4 class="mb-1 fw-bolder" style="color: #1e293b; letter-spacing: -0.5px;">
                    <i class="fas fa-cash-register me-2" style="color: #4f46e5;"></i>Purchase POS
                </h4>
                <small class="text-muted fw-medium">Process new purchases from vendors efficiently</small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn d-flex align-items-center gap-2 px-4 py-2 fw-bold text-white shadow-sm transition-all" id="btnManualProductModal" data-toggle="modal" data-target="#manualProductModal" style="border-radius: 10px; background: linear-gradient(135deg, #10b981, #059669); border: none;">
                    <i class="fas fa-plus"></i> Manual Product
                </button>
                <button type="button" class="btn d-none align-items-center gap-2 px-4 py-2 fw-bold text-white shadow-sm transition-all" id="btnPOSExchangeHeader" style="border-radius: 10px; background: linear-gradient(135deg, #ef4444, #dc2626); border: none;">
                    <i class="fas fa-exchange-alt"></i> Exchange / Return Item
                </button>
            </div>
        </div>

        <div class="pos-wrapper">
            
            <!-- LEFT PANEL: Products Grid -->
            <div class="pos-products-panel">
                <div class="pos-search-box">
                    <input type="text" id="posSearch" placeholder="Search base product by name or code...">
                    <i class="fas fa-search pos-search-icon"></i>
                </div>

                <!-- Category Filter Pills Bar -->
                <div class="cat-pills-scroll" id="categoryPillsRow">
                    <button type="button" class="cat-pill active" data-mode="all"><i class="fas fa-cubes me-1"></i> All Items</button>
                    <button type="button" class="cat-pill" data-mode="by_cartons">📦 Cartons</button>
                    <button type="button" class="cat-pill" data-mode="by_pieces">🧩 Pieces</button>
                    <button type="button" class="cat-pill" data-mode="by_size">📐 By Size</button>
                    <button type="button" class="cat-pill" data-mode="by_kg">⚖ Weight (Kg)</button>
                </div>
                
                <div class="pos-grid-container">
                    <div class="pos-grid" id="productsGrid">
                        @foreach ($posProducts as $product)
                            @php
                                $hasVariants = count($product['variants']) > 0;
                                $isOut = $product['stock_pieces'] <= 0;
                            @endphp
                            <div class="product-card" 
                                 data-id="{{ $product['id'] }}" 
                                 data-name="{{ $product['name'] }}" 
                                 data-sku="{{ $product['sku'] }}" 
                                 data-price="{{ $product['price'] }}"
                                 data-wholesale-price="{{ $product['wholesale_price'] }}"
                                 data-weight-per-piece="{{ $product['weight_per_piece'] }}"
                                 data-stock-pieces="{{ $product['stock_pieces'] }}"
                                 data-stock="{{ $product['stock'] }}"
                                 data-pieces-per-box="{{ $product['pieces_per_box'] }}"
                                 data-size-mode="{{ $product['size_mode'] }}"
                                 data-has-variants="{{ $hasVariants ? '1' : '0' }}"
                                 data-variants="{{ json_encode($product['variants']) }}">
                                 
                                <div class="product-card-image">
                                    @if ($product['image'])
                                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}">
                                    @else
                                        <i class="fas fa-box-open"></i>
                                    @endif
                                </div>
                                <div class="product-card-body">
                                    <div class="product-card-title">{{ $product['name'] }}</div>
                                    @if ($hasVariants)
                                        <div class="product-card-variants-badge">
                                            {{ count($product['variants']) }} Variants
                                        </div>
                                    @else
                                        <div style="height: 24px;"></div>
                                    @endif
                                    <div class="product-card-footer">
                                        <div class="product-card-price">
                                            @if ($hasVariants)
                                                Rs {{ number_format($product['variants'][0]['price'] ?? $product['price'], 0) }}
                                            @else
                                                Rs {{ number_format($product['price'], 0) }}
                                            @endif
                                        </div>
                                        <div class="product-card-stock {{ $isOut ? 'out' : '' }}">
                                            Stock: {{ $product['stock'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <!-- RIGHT PANEL: Cart & Checkout -->
            <div class="pos-cart-panel">
                <form id="posCheckoutForm" autocomplete="off" style="flex: 1; display: flex; flex-direction: column; margin-bottom: 0;">
                    @csrf
                    <!-- Store action mirror -->
                    <input type="hidden" name="action" value="post">
                    
                    <div class="pos-cart-header">
                        <div class="d-flex align-items-center gap-2">
                            <h5>Selected Items</h5>
                            <button type="button" class="btn btn-outline-danger py-0 px-2 btn-sm d-none" id="btnPOSExchange" style="font-size: 10px; line-height: 18px; border-color: rgba(239, 68, 68, 0.4); color: #fca5a5;">
                                <i class="fas fa-exchange-alt"></i> Exchange
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="btn-group btn-group-sm d-none" id="posPriceModeToggleGroup" style="height: 20px;">
                                <input type="radio" class="btn-check" name="pos_price_mode" id="pos_mode_retail" value="retail" checked>
                                <label class="btn btn-outline-light py-0 px-2" for="pos_mode_retail" style="font-size: 10px; line-height: 18px; border-color: rgba(255,255,255,0.2);">Retail</label>

                                <input type="radio" class="btn-check" name="pos_price_mode" id="pos_mode_wholesale" value="wholesale">
                                <label class="btn btn-outline-light py-0 px-2" for="pos_mode_wholesale" style="font-size: 10px; line-height: 18px; border-color: rgba(255,255,255,0.2);">Wholesale</label>
                            </div>
                            <span class="cart-count" id="cartCountBadge">0</span>
                            <button type="button" class="btn-close btn-close-white d-md-none ms-2" id="btnCloseMobileCartDrawer" aria-label="Close"></button>
                        </div>
                    </div>
                    
                    <!-- CART LIST -->
                    <div class="pos-cart-items" id="cartItemsList">
                        <div class="cart-empty" id="cartEmptyPlaceholder">
                            <i class="fas fa-shopping-cart"></i>
                            <div class="fw-bold">Cart is Empty</div>
                            <small class="text-muted mt-1">Click on any product card to add it</small>
                        </div>
                        <!-- Items populated via JavaScript -->
                    </div>
                    
                    <!-- BILL SUMMARY -->
                    <div class="pos-cart-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span id="summarySubtotal">Rs 0.00</span>
                        </div>
                        <div class="summary-row align-items-center">
                            <span>Discount (Rs):</span>
                            <div class="d-flex align-items-center gap-1">
                                <input type="number" id="summaryDiscount" class="form-control form-control-sm text-end bg-light" value="0" style="width: 80px; height: 26px !important; padding: 2px 8px;">
                                <button type="button" class="btn btn-dark btn-sm py-0 px-2" id="btnDiscountDistribute" style="height: 24px; font-size: 10px; line-height: 20px;" title="Distribute Discount"><i class="fas fa-divide"></i> Distribute</button>
                            </div>
                        </div>
                        <div class="summary-row payable">
                            <span>Total Payable:</span>
                            <span id="summaryPayable">Rs 0.00</span>
                        </div>
                    </div>
                    
                    <!-- CHECKOUT SECTION -->
                    <div class="pos-checkout-section">
                        <input type="hidden" name="is_walkin" id="isWalkinInput" value="0">
                        
                        <div class="row g-2 mb-2">
                            <!-- Vendor Selection -->
                            <div class="col-8">
                                <div class="form-group" id="registeredVendorGroup">
                                    <label class="d-flex justify-content-between align-items-center w-100" style="margin-bottom: 2px;">
                                        Vendor
                                        <a href="javascript:void(0)" data-toggle="modal" data-target="#newVendorModal" class="text-primary text-decoration-none" style="font-size: 11px;"><i class="fas fa-plus"></i> New</a>
                                    </label>
                                    <select class="form-select select2" name="Vendor" id="VendorSelect" style="width: 100%;">
                                        <option value="" selected disabled>Select</option>
                                        @foreach ($vendors as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label style="margin-bottom: 2px; font-size: 12px; font-weight: 600;">Vendor Ref #</label>
                                    <input type="text" class="form-control form-control-sm" name="purchase_order_no" id="pos_purchase_order_no" placeholder="Ref #" style="height: 38px;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payments Section -->
                        <div class="payments-wrapper mb-2" style="border: 1px solid #ced4da; padding: 10px; border-radius: 6px; background-color: #f8f9fa;">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px solid #dee2e6;">
                                <label class="mb-0 fw-bold small text-secondary">PAYMENTS</label>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" id="btnAddSplitPayment" style="font-size: 11px;">
                                    <i class="fas fa-plus"></i> Split
                                </button>
                            </div>
                            
                            <div id="paymentRowsContainer">
                                <div class="row g-2 payment-row mb-2">
                                    <div class="col-7">
                                        <label class="small text-secondary mb-1">Payment Account</label>
                                        <select class="form-select pos-input payment-account" name="payment_account_id[]" required>
                                            @foreach ($accounts as $acc)
                                                <option value="{{ $acc->id }}" {{ str_contains(strtolower($acc->title), 'cash') ? 'selected' : '' }}>{{ $acc->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <label class="small text-secondary mb-1">Paid</label>
                                        <input type="number" name="payment_amount[]" class="pos-input text-end fw-bold payment-amount" placeholder="0.00" value="">
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-2">
                            
                            <!-- Change Calculation -->
                            <div class="row g-2 align-items-center">
                                <div class="col-6 text-end">
                                    <span class="small text-secondary fw-semibold">Total Paid:</span><br>
                                    <span class="small text-secondary fw-semibold">Change:</span>
                                </div>
                                <div class="col-6">
                                    <input type="text" id="totalPaidDisplay" class="pos-input text-end fw-bold border-0 bg-transparent mb-1 p-0" readonly value="0.00">
                                    <input type="text" id="returnedChange" class="pos-input text-end fw-bold text-success bg-light" readonly value="0.00">
                                    <input type="hidden" name="cash" id="backendCash">
                                </div>
                            </div>
                        </div>

                        <!-- Hidden template for split payments -->
                        <div id="paymentRowTemplate" style="display: none;">
                            <div class="row g-2 payment-row mb-2">
                                <div class="col-6">
                                    <select class="form-select pos-input payment-account" name="payment_account_id[]" required disabled>
                                        @foreach ($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <input type="number" name="payment_amount[]" class="pos-input text-end fw-bold payment-amount" placeholder="0.00" disabled>
                                </div>
                                <div class="col-2 d-flex align-items-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-payment p-1 w-100">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        
                        <!-- Hidden values for backend submission -->
                        <input type="hidden" name="subTotal1" id="backendSubTotal1" value="0">
                        <input type="hidden" name="total_subtotal" id="backendSubTotal2" value="0">
                        <input type="hidden" name="total_net" id="backendTotalNet" value="0">
                        <input type="hidden" name="total_extra_cost" id="backendExtraDiscount" value="0">
                        <input type="hidden" name="warehouse_id[]" id="backendWarehouseId" value="1">
                        
                        <div class="d-flex flex-column gap-2 mt-2">
                            <button type="submit" class="btn-checkout" id="btnPOSSubmit" disabled>
                                <i class="fas fa-check-circle me-1"></i> Submit & Print
                            </button>
                            <button type="button" class="btn btn-outline-secondary w-100 py-2 fw-bold btn-back-to-products" id="btnBackToProducts">
                                <i class="fas fa-arrow-left me-1"></i> Back / Add More Products
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</div>

<!-- VARIANTS SELECTION POPUP MODAL -->
<div class="modal fade" id="variantsModal" tabindex="-1" aria-labelledby="variantsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold text-white fs-6 mb-0" id="variantsModalLabel">Select Product Variant</h5>
                <button type="button" class="btn-close btn-close-white" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light" style="max-height: 70vh; overflow-y: auto;">
                <div class="mb-3">
                    <input type="text" id="variantSearchInput" class="form-control px-3 py-2" placeholder="Search variant size / color..." style="border-radius: 10px; border: 1.5px solid #cbd5e1;">
                </div>
                <div id="variantsModalList" class="d-flex flex-column gap-2">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>
            <div class="modal-footer py-2 bg-white border-top">
                <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold" data-dismiss="modal" style="border-radius: 8px;">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- NEW Vendor MODAL -->
<div class="modal fade" id="newVendorModal" tabindex="-1" aria-labelledby="newVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fs-6" id="newVendorModalLabel">Create New Vendor</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="border: none; background: transparent; font-size: 24px;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="newVendorForm" autocomplete="off">
                @csrf
                <input type="hidden" name="opening_balance" value="0">
                <div class="modal-body p-3">
                    <div class="form-group mb-3">
                        <label>Vendor Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" name="mobile" class="form-control" required>
                    </div>
                                        <div class="form-group mb-3">
                        <div class="row g-2">
                            <div class="col-4">
                                <label class="small text-muted fw-bold">Province</label>
                                <select name="province" class="form-select form-control form-control-sm">
                                    <option value="">Optional</option>
                                    @foreach(app(\App\Services\LocationService::class)->getProvinces() as $p)
                                        <option value="{{ $p }}">{{ $p }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="small text-muted fw-bold">City</label>
                                <div class="input-group">
                                    <select name="city" class="form-select form-control form-control-sm">
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
                            <div class="col-4">
                                <label class="small text-muted fw-bold">Area</label>
                                <div class="input-group">
                                    <select name="area" class="form-select form-control form-control-sm">
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
                    </div>
<div class="form-group mb-0">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnSaveNewVendor">Save Vendor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EXCHANGE SEARCH MODAL -->
<div class="modal fade" id="posExchangeModal" tabindex="-1" aria-labelledby="posExchangeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white py-2">
                <h5 class="modal-title fs-6" id="posExchangeModalLabel">Product Exchange / Return</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="border: none; background: transparent; font-size: 24px;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group mb-3">
                    <input type="text" id="exchangeInvoiceNo" class="form-control" placeholder="Enter Invoice Number or Invoice ID (e.g. INV-0010)...">
                    <button class="btn btn-danger" type="button" id="btnSearchExchangeInvoice"><i class="fas fa-search"></i> Search</button>
                </div>
                
                <div id="exchangeInvoiceDetails" class="d-none border rounded p-3 bg-light mb-3">
                    <div class="row">
                        <div class="col-6"><strong>Invoice:</strong> <span id="exchangeInvoiceVal">-</span></div>
                        <div class="col-6"><strong>Vendor:</strong> <span id="exchangeVendorVal">-</span></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="exchangeItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="font-size: 12px;">Product / Variant</th>
                                <th class="text-center" style="font-size: 12px;">Qty Sold</th>
                                <th class="text-center" style="font-size: 12px;">Returned</th>
                                <th class="text-end" style="font-size: 12px;">Net Price</th>
                                <th class="text-center" style="width: 140px; font-size: 12px;">Qty to Return</th>
                                <th class="text-center" style="width: 100px; font-size: 12px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="exchangeItemsList">
                            <tr><td colspan="6" class="text-center text-muted py-3">Enter invoice number to load items.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- DISCOUNT DISTRIBUTION MODAL -->
<div class="modal fade" id="discountDistributeModal" tabindex="-1" aria-labelledby="discountDistributeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2">
                <h5 class="modal-title fs-6" id="discountDistributeModalLabel">Distribute Discount</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="border: none; background: transparent; font-size: 24px;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label class="form-label fw-bold">Total Discount to Distribute (Rs):</label>
                    <input type="number" id="distributeAmount" class="form-control" min="0" value="0">
                </div>
                
                <div class="form-group mb-3">
                    <label class="form-label fw-bold">Distribution Method:</label>
                    <div class="d-flex flex-column gap-2 mt-1">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="distribute_method" id="method_equal" value="equal" checked>
                            <label class="form-check-label" for="method_equal">
                                <strong>Equal</strong> (Divide equally among items)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="distribute_method" id="method_proportional" value="proportional">
                            <label class="form-check-label" for="method_proportional">
                                <strong>Proportional</strong> (Divide by item total price)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="distribute_method" id="method_manual" value="manual">
                            <label class="form-check-label" for="method_manual">
                                <strong>Manual</strong> (Specify discount next to each item)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="border rounded p-2 bg-light d-none" id="manualDistributeTableContainer">
                    <label class="form-label fw-bold mb-1" style="font-size: 11px;">Manual Item Discounts:</label>
                    <div style="max-height: 200px; overflow-y: auto;">
                        <table class="table table-sm align-middle mb-0" style="font-size: 12px;">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end" style="width: 100px;">Discount (Rs)</th>
                                </tr>
                            </thead>
                            <tbody id="manualDistributeItemsList"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnApplyDiscountDistribution">Apply</button>
            </div>
        </div>
    </div>
</div>

<!-- Manual Product Modal -->
<div class="modal fade" id="manualProductModal" tabindex="-1" aria-labelledby="manualProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden;">
            <div class="modal-header py-3" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title fs-5 text-dark fw-bold d-flex align-items-center" id="manualProductModalLabel">
                    <i class="fas fa-box me-2 text-primary"></i> Add Manual Product (Outsourced)
                </h5>
                <button type="button" class="close text-secondary" data-dismiss="modal" aria-label="Close" style="border: none; background: transparent; font-size: 24px; outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-12 mb-3">
                        <label class="form-label text-dark fw-semibold small">Vendor <span class="text-danger">*</span></label>
                        <select class="form-control" id="manual_vendor_id" style="border-radius: 4px; border: 1px solid #ced4da;">
                            <option value="">Select Vendor</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" data-balance="{{ $vendor->balance }}">{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label text-dark fw-semibold small mb-1">Product Name <span class="text-danger">*</span></label>
                                <input type="text" id="manual_product_name" class="form-control form-control-sm" placeholder="e.g. Red Shirt" style="border-radius: 4px; border: 1px solid #ced4da;">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-dark fw-semibold small mb-1">Size</label>
                                <input type="text" id="manual_size" class="form-control form-control-sm" placeholder="Optional" style="border-radius: 4px; border: 1px solid #ced4da;">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-dark fw-semibold small mb-1">Color</label>
                                <input type="text" id="manual_color" class="form-control form-control-sm" placeholder="Optional" style="border-radius: 4px; border: 1px solid #ced4da;">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-dark fw-semibold small mb-1">Quantity <span class="text-danger">*</span></label>
                                <input type="number" id="manual_qty" class="form-control form-control-sm text-center" value="1" min="1" style="border-radius: 4px; border: 1px solid #ced4da;">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-dark fw-semibold small mb-1">Purchase (Rs)<span class="text-danger">*</span></label>
                                <input type="number" id="manual_purchase_price" class="form-control form-control-sm text-end" min="0" step="0.01" style="border-radius: 4px; border: 1px solid #ced4da;" placeholder="0.00">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-dark fw-semibold small mb-1">Sale (Rs)<span class="text-danger">*</span></label>
                                <input type="number" id="manual_sale_price" class="form-control form-control-sm text-end" min="0" step="0.01" style="border-radius: 4px; border: 1px solid #ced4da;" placeholder="0.00">
                            </div>
                        </div>
                        <div class="text-end mt-2">
                            <button type="button" class="btn btn-sm btn-success fw-bold px-3" id="btnAddManualItemInner">
                                <i class="fas fa-plus me-1"></i> Add Item to Sale
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <div class="table-responsive" style="max-height: 200px; border: 1px solid #dee2e6; border-radius: 4px;">
                            <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
                                <thead style="background-color: #f8f9fa;">
                                    <tr>
                                        <th class="text-secondary fw-semibold">ITEM</th>
                                        <th class="text-center text-secondary fw-semibold" style="width: 60px;">QTY</th>
                                        <th class="text-end text-secondary fw-semibold" style="width: 90px;">PURCHASE</th>
                                        <th class="text-end text-secondary fw-semibold" style="width: 90px;">SALE</th>
                                        <th class="text-end text-secondary fw-semibold" style="width: 90px;">PROFIT</th>
                                        <th style="width: 40px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="manualItemsList">
                                    <tr id="manualNoItemsRow">
                                        <td colspan="6" class="text-center text-muted py-3">No items added yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row g-3">
                    <div class="col-md-12 mb-2">
                        <div class="d-flex align-items-center py-2 text-secondary small">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            <span>If paying the vendor immediately, enter the amount below. Otherwise, leave as 0 to keep the balance outstanding in the Vendor Ledger.</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark fw-semibold small">Payment Amount (to Vendor)</label>
                        <div class="input-group" style="border-radius: 4px; border: 1px solid #ced4da; overflow: hidden;">
                            <span class="input-group-text bg-light text-secondary border-0" style="border-right: 1px solid #ced4da !important;">Rs</span>
                            <input type="number" id="manual_vendor_payment_amount" class="form-control border-0" value="0" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark fw-semibold small">Payment Account</label>
                        <select class="form-control" id="manual_vendor_account_id" style="border-radius: 4px; border: 1px solid #ced4da;">
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Live Summary Box -->
                <div class="row mt-3" id="manual_vendor_summary_box" style="display: none;">
                    <div class="col-md-12">
                        <div class="p-3" style="background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px;">
                            <div class="d-flex align-items-center mb-3 pb-2" style="border-bottom: 1px solid #dee2e6;">
                                <i class="fas fa-file-invoice fs-5 text-secondary me-2"></i>
                                <h6 class="fw-bold mb-0 text-dark">Vendor Ledger Summary</h6>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary small">Previous Balance</span>
                                <span id="v_prev_bal" class="fw-medium text-dark">0.00</span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary small">Current Purchase (Payable)</span>
                                <span id="v_curr_purch" class="fw-medium text-danger">+ 0.00</span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-secondary small">Current Paid</span>
                                <span id="v_curr_paid" class="fw-medium text-success">- 0.00</span>
                            </div>
                            
                            <div class="d-flex justify-content-between pt-2 mt-2" style="border-top: 1px solid #dee2e6;">
                                <strong class="text-dark small">New Balance</strong>
                                <strong id="v_new_bal" class="text-dark">0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer py-2" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-4" id="btnConfirmManualCart">
                    Confirm & Add All to POS
                </button>
            </div>
        </div>
    </div>
</div>

<!-- STICKY FLOATING CART BAR FOR MOBILE -->
<div class="mobile-pos-bottom-bar" id="mobilePosBottomBar">
    <div class="d-flex align-items-center gap-2">
        <div class="position-relative">
            <i class="fas fa-shopping-cart text-primary fs-4"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="mobileCartCountBadge" style="font-size: 10px;">0</span>
        </div>
        <div class="ms-1">
            <div class="text-muted small" style="font-size: 9px; font-weight: 700; text-transform: uppercase;">Total Payable</div>
            <div class="fw-extrabold text-dark" id="mobileCartTotal" style="font-size: 1rem; font-weight: 800; color: #0f172a;">Rs 0.00</div>
        </div>
    </div>
    <button type="button" class="btn btn-primary fw-bold px-3 py-2" id="btnOpenMobileCart" style="border-radius: 10px; background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); border: none; font-size: 0.85rem;">
        View Cart <i class="fas fa-arrow-right ms-1"></i>
    </button>
</div>
@include('admin_panel.partials.location_selector_script')
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        let cart = [];
        let manualModalCart = [];
        
        // Init Select2 for Vendor
        $('#VendorSelect').select2({
            width: '100%',
            dropdownParent: $('#registeredVendorGroup')
        });

        // New Vendor form submission
        $('#newVendorForm').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btnSaveNewVendor');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            
            $.ajax({
                url: "{{ route('vendors.store.ajax') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    btn.prop('disabled', false).text('Save Vendor');
                    if(response.ok || response.success || (response.data && response.data.id)) {
                        Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Vendor Created', showConfirmButton:false, timer:1500 });
                        $('#newVendorModal').modal('hide');
                        $('#newVendorForm')[0].reset();
                        
                        let VendorData = response.vendor || response.data || response;
                        let newOption = new Option(VendorData.name, VendorData.id, true, true);
                        $('#VendorSelect').append(newOption).trigger('change');
                    } else {
                        Swal.fire('Error', 'Failed to create Vendor.', 'error');
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).text('Save Vendor');
                    let msg = 'Failed to create Vendor.';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

        // Toggle Vendor Type
        $('#btnToggleWalkin').on('click', function() {
            $(this).addClass('active');
            $('#btnToggleRegistered').removeClass('active');
            $('#isWalkinInput').val('1');
            $('#walkinNameGroup').removeClass('d-none');
            $('#registeredVendorGroup').addClass('d-none');
            $('#VendorSelect').val('').trigger('change');
            $('#walkinNameInput').val('Walking Vendor');
        });

        $('#btnToggleRegistered').on('click', function() {
            $(this).addClass('active');
            $('#btnToggleWalkin').removeClass('active');
            $('#isWalkinInput').val('0');
            $('#registeredVendorGroup').removeClass('d-none');
            $('#walkinNameGroup').addClass('d-none');
            $('#walkinNameInput').val('');
        });

        // Filter Grid Products via Search Input
        $('#posSearch').on('input', function() {
            let term = $(this).val().toLowerCase();
            $('.product-card').each(function() {
                let name = $(this).data('name').toLowerCase();
                let sku = $(this).data('sku').toLowerCase();
                if (name.includes(term) || sku.includes(term)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Click on Product Card
        $('.product-card').on('click', function() {
            let hasVariants = $(this).data('has-variants') === 1;
            let stockPieces = parseFloat($(this).data('stock-pieces'));
            
            // No stock check required for purchases

            if (hasVariants) {
                // Open variants selection popup modal
                let variants = $(this).data('variants');
                let productName = $(this).data('name');
                let sizeMode = $(this).data('size-mode');
                let piecesPerBox = $(this).data('pieces-per-box') || 1;
                let idBase = $(this).data('id');
                
                $('#variantsModalLabel').text(`Select Variant - ${productName}`);
                let $tbody = $('#variantsModalList');
                $tbody.empty();
                
                variants.forEach((v) => {
                    let isOut = false; // Purchases always allow adding items
                    let desc = `${v.size_val !== '-' ? v.size_val : ''} ${v.color_val !== '-' ? '(' + v.color_val + ')' : ''}`;
                    if (desc.trim() === '') {
                        desc = 'Standard Variant';
                    }
                    
                    let retailPrice = parseFloat(v.price) || 0;
                    let wholesalePrice = parseFloat(v.wholesale_price) || 0;
                    let weightPerPiece = parseFloat(v.weight_per_piece) || 0;
                    let activePriceMode = $('input[name="pos_price_mode"]:checked').val() || 'retail';
                    let price = (activePriceMode === 'wholesale' && wholesalePrice > 0) ? wholesalePrice : retailPrice;

                    let row = `
                        <div class="variant-mobile-card p-3 bg-white rounded-3 border" 
                             data-id="${v.id}" 
                             data-name="${v.name}" 
                             data-price="${retailPrice}" 
                             data-wholesale-price="${wholesalePrice}"
                             data-weight-per-piece="${weightPerPiece}"
                             data-stock-pieces="${v.stock_pieces}"
                             data-size-mode="${sizeMode}"
                             data-pieces-per-box="${piecesPerBox}"
                             data-variant-data="${v.variant_data}">
                             
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark variant-title-text" style="font-size: 0.92rem;">${desc}</span>
                                <span class="badge ${isOut ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-success-subtle text-success border-success-subtle'} border px-2 py-1" style="font-size: 0.75rem; font-weight: 700;">
                                    Stock: ${v.stock}
                                </span>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <div class="fw-extrabold text-primary" style="font-size: 0.95rem; font-weight: 800;">
                                    Rs ${price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="qty-controls" style="width: 85px;">
                                        <button type="button" class="qty-btn modal-qty-minus" ${isOut ? 'disabled' : ''}>-</button>
                                        <input type="number" class="qty-input modal-qty-val" value="1" min="1" ${isOut ? 'disabled' : ''}>
                                        <button type="button" class="qty-btn modal-qty-plus" ${isOut ? 'disabled' : ''}>+</button>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm variant-add-btn add-to-cart-modal-btn px-3 fw-bold" style="border-radius: 8px;" ${isOut ? 'disabled' : ''}>
                                        <i class="fas fa-plus me-1"></i> Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    $tbody.append(row);
                });
                
                $('#variantsModal').modal('show');
            } else {
                // Add base product directly
                let id = $(this).data('id');
                let name = $(this).data('name');
                let retailPrice = parseFloat($(this).data('price'));
                let wholesalePrice = parseFloat($(this).data('wholesale-price')) || 0;
                let weightPerPiece = parseFloat($(this).data('weight-per-piece')) || 0;
                let stockPieces = parseFloat($(this).data('stock-pieces'));
                let sizeMode = $(this).data('size-mode');
                let piecesPerBox = parseFloat($(this).data('pieces-per-box')) || 1;
                
                let activePriceMode = $('input[name="pos_price_mode"]:checked').val() || 'retail';
                let price = (activePriceMode === 'wholesale' && wholesalePrice > 0) ? wholesalePrice : retailPrice;
                
                addToCart(id, name, price, stockPieces, 1, sizeMode, piecesPerBox, '', retailPrice, wholesalePrice, weightPerPiece);
            }
        });

        // Modal quantity increment/decrement
        $(document).on('click', '.modal-qty-plus', function() {
            let $input = $(this).siblings('.modal-qty-val');
            let max = parseInt($input.attr('max')) || 1;
            let current = parseInt($input.val()) || 1;
            if (current < max) {
                $input.val(current + 1);
            }
        });

        $(document).on('click', '.modal-qty-minus', function() {
            let $input = $(this).siblings('.modal-qty-val');
            let current = parseInt($input.val()) || 1;
            if (current > 1) {
                $input.val(current - 1);
            }
        });

        $(document).on('click', '.add-to-cart-modal-btn', function() {
            let $row = $(this).closest('.variant-mobile-card');
            let id = $row.data('id');
            let name = $row.data('name');
            let retailPrice = parseFloat($row.data('price'));
            let wholesalePrice = parseFloat($row.data('wholesale-price')) || 0;
            let weightPerPiece = parseFloat($row.data('weight-per-piece')) || 0;
            let stockPieces = parseFloat($row.data('stock-pieces'));
            let sizeMode = $row.data('size-mode');
            let piecesPerBox = parseFloat($row.data('pieces-per-box')) || 1;
            let variantData = $row.data('variant-data');
            let qty = parseInt($row.find('.modal-qty-val').val()) || 1;

            let activePriceMode = $('input[name="pos_price_mode"]:checked').val() || 'retail';
            let price = (activePriceMode === 'wholesale' && wholesalePrice > 0) ? wholesalePrice : retailPrice;

            addToCart(id, name, price, stockPieces, qty, sizeMode, piecesPerBox, variantData, retailPrice, wholesalePrice, weightPerPiece);
            
            // Show added feedback
            let $btn = $(this);
            $btn.removeClass('btn-primary').addClass('btn-success').html('<i class="fas fa-check"></i> Added');
            setTimeout(() => {
                $btn.removeClass('btn-success').addClass('btn-primary').html('<i class="fas fa-plus me-1"></i> Add');
            }, 1000);
        });

        // Live Vendor Summary for Manual Product Modal
        function updateVendorSummary() {
            let vendorId = $('#manual_vendor_id').val();
            let $summaryBox = $('#manual_vendor_summary_box');
            
            if (!vendorId) {
                $summaryBox.hide();
                return;
            }
            
            $summaryBox.show();
            let prevBal = parseFloat($('#manual_vendor_id option:selected').data('balance')) || 0;
            
            let innerCartPurchase = manualModalCart.reduce((sum, item) => sum + (item.qty * item.purchase_price), 0);
            
            let qty = parseInt($('#manual_qty').val()) || 0;
            let purchase_price = parseFloat($('#manual_purchase_price').val()) || 0;
            let currentPurchase = innerCartPurchase + (qty * purchase_price);
            let currentPaid = parseFloat($('#manual_vendor_payment_amount').val()) || 0;
            
            let newBal = prevBal + currentPurchase - currentPaid;
            
            $('#v_prev_bal').text(prevBal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#v_curr_purch').text(currentPurchase.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#v_curr_paid').text(currentPaid.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#v_new_bal').text(newBal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            
            // Color code new balance
            if (newBal > 0) {
                $('#v_new_bal').removeClass('text-success').addClass('text-danger'); // Owe vendor
            } else if (newBal < 0) {
                $('#v_new_bal').removeClass('text-danger').addClass('text-success'); // Vendor owes us
            } else {
                $('#v_new_bal').removeClass('text-danger text-success');
            }
        }

        $('#manual_vendor_id, #manual_qty, #manual_purchase_price, #manual_vendor_payment_amount').on('input change', updateVendorSummary);
        
        $('#manualProductModal').on('show.bs.modal', function () {
            manualModalCart = []; // reset inner cart on open
            renderManualModalCart();
            updateVendorSummary();
        });

        function renderManualModalCart() {
            let $tbody = $('#manualItemsList');
            $tbody.empty();
            
            if (manualModalCart.length === 0) {
                $tbody.append('<tr id="manualNoItemsRow"><td colspan="6" class="text-center text-muted py-3">No items added yet.</td></tr>');
                return;
            }
            
            manualModalCart.forEach((item, index) => {
                let profit = (item.sale_price - item.purchase_price) * item.qty;
                let tr = `
                    <tr>
                        <td class="align-middle fw-medium text-dark">${item.name}</td>
                        <td class="align-middle text-center fw-bold text-dark">${item.qty}</td>
                        <td class="align-middle text-end">${item.purchase_price.toFixed(2)}</td>
                        <td class="align-middle text-end">${item.sale_price.toFixed(2)}</td>
                        <td class="align-middle text-end ${profit >= 0 ? 'text-success' : 'text-danger'}">${profit.toFixed(2)}</td>
                        <td class="align-middle text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-manual-item" data-index="${index}" style="padding: 2px 6px;">
                                <i class="fas fa-trash-alt" style="font-size: 10px;"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $tbody.append(tr);
            });
        }

        $(document).on('click', '.remove-manual-item', function() {
            let index = $(this).data('index');
            manualModalCart.splice(index, 1);
            renderManualModalCart();
            updateVendorSummary();
        });

        $('#btnAddManualItemInner').on('click', function() {
            let vendor_id = $('#manual_vendor_id').val();
            let product_name = $('#manual_product_name').val().trim();
            let size = $('#manual_size').val().trim();
            let color = $('#manual_color').val().trim();
            let qty = parseInt($('#manual_qty').val()) || 1;
            let purchase_price = parseFloat($('#manual_purchase_price').val());
            let sale_price = parseFloat($('#manual_sale_price').val());

            if (!vendor_id) { Swal.fire('Validation Error', 'Please select a vendor first.', 'error'); return; }
            if (!product_name) { Swal.fire('Validation Error', 'Please enter a product name.', 'error'); return; }
            if (qty < 1) { Swal.fire('Validation Error', 'Quantity must be at least 1.', 'error'); return; }
            if (isNaN(purchase_price) || purchase_price < 0) { Swal.fire('Validation Error', 'Please enter a valid purchase price.', 'error'); return; }
            if (isNaN(sale_price) || sale_price < 0) { Swal.fire('Validation Error', 'Please enter a valid sale price.', 'error'); return; }

            // Construct variant string
            let variantObj = {};
            if (color) variantObj.color = color;
            if (size) variantObj.size = size;
            let variantData = Object.keys(variantObj).length > 0 ? JSON.stringify(variantObj) : '';
            
            let displayName = product_name;
            let parts = [];
            if (size && size.trim() !== '-') parts.push(size);
            if (color && color.trim() !== '-') parts.push(color);
            if (parts.length > 0) {
                displayName += ` (${parts.join(' | ')})`;
            }

            let id = 'manual_' + Date.now() + Math.floor(Math.random() * 1000);
            
            manualModalCart.push({
                id: id,
                product_id: '',
                name: displayName,
                price: sale_price,
                retailPrice: sale_price,
                wholesalePrice: sale_price,
                weightPerPiece: 0,
                qty: qty,
                stock: 999999, // Infinite stock for manual items
                sizeMode: 'pieces',
                piecesPerBox: 1,
                variantData: variantData,
                is_manual: 1,
                vendor_id: vendor_id,
                purchase_price: purchase_price,
                sale_price: sale_price // keep for profit calc
            });
            
            renderManualModalCart();
            
            // Clear inputs for next item
            $('#manual_product_name').val('');
            $('#manual_size').val('');
            $('#manual_color').val('');
            $('#manual_qty').val(1);
            $('#manual_purchase_price').val('');
            $('#manual_sale_price').val('');
            
            updateVendorSummary();
        });

        $('#btnConfirmManualCart').on('click', function() {
            if (manualModalCart.length === 0) {
                Swal.fire('Empty', 'Please add at least one item first.', 'warning');
                return;
            }
            
            let payment_amount = parseFloat($('#manual_vendor_payment_amount').val()) || 0;
            let account_id = $('#manual_vendor_account_id').val();
            
            if (payment_amount > 0 && !account_id) { 
                Swal.fire('Validation Error', 'Please select a payment account for the vendor payment.', 'error'); 
                return; 
            }
            
            // Loop through all items and push to POS cart
            manualModalCart.forEach(item => {
                // Apply the vendor payment ONLY to the first item (or distribute it). 
                // Currently, the existing logic stored it per item. We can just attach it to the first manual item so it's recorded once.
                let itemPayment = 0;
                let itemAccount = null;
                if (item === manualModalCart[0]) {
                    itemPayment = payment_amount;
                    itemAccount = account_id;
                }
                
                cart.push({
                    id: item.id,
                    product_id: item.product_id,
                    name: item.name,
                    price: item.price,
                    retailPrice: item.retailPrice,
                    wholesalePrice: item.wholesalePrice,
                    weightPerPiece: item.weightPerPiece,
                    qty: item.qty,
                    stock: item.stock,
                    sizeMode: item.sizeMode,
                    piecesPerBox: item.piecesPerBox,
                    variantData: item.variantData,
                    is_manual: item.is_manual,
                    vendor_id: item.vendor_id,
                    purchase_price: item.purchase_price,
                    vendor_payment_amount: itemPayment,
                    vendor_account_id: itemAccount
                });
            });
            
            renderCart();
            
            // Close modal & Reset
            $('#manualProductModal').modal('hide');
            manualModalCart = [];
            $('#manual_vendor_payment_amount').val(0);
        });

        // Core addToCart Helper
        function addToCart(id, name, price, stockPieces, qty, sizeMode, piecesPerBox, variantData, retailPrice = 0, wholesalePrice = 0, weightPerPiece = 0) {
            let cartItem = cart.find(item => item.id === id);
            if (cartItem) {
                cartItem.qty += qty;
            } else {
                cart.push({
                    id: id,
                    product_id: id.toString().split('|')[0],
                    name: name,
                    price: price,
                    retailPrice: retailPrice || price,
                    wholesalePrice: wholesalePrice,
                    weightPerPiece: weightPerPiece,
                    qty: qty,
                    stock: stockPieces,
                    sizeMode: sizeMode,
                    piecesPerBox: piecesPerBox,
                    variantData: variantData
                });
            }
            renderCart();
        }

        // Render Cart items list
        function renderCart() {
            let $list = $('#cartItemsList');
            let $empty = $('#cartEmptyPlaceholder');
            
            updateMobileCartBar();
            if (cart.length === 0) {
                $list.html($empty);
                $('#cartCountBadge').text('0');
                $('#btnPOSSubmit').prop('disabled', true);
                updateBillSummary();
                return;
            }
            
            $empty.detach();
            $list.empty();
            
            cart.forEach((item, index) => {
                let factor = parseFloat(item.weightPerPiece) || 0;
                let totalPieces = item.qty;
                if (factor > 0) {
                    if (item.sizeMode === 'by_kg') {
                        totalPieces = item.qty * (factor / 1000);
                    } else if (item.sizeMode === 'by_meter') {
                        totalPieces = item.qty * factor;
                    }
                }
                
                let isRet = item.is_return === true;
                let itemBg = isRet ? 'background: #fff5f5; border: 1px solid #feb2b2;' : '';
                let badge = isRet ? '<span class="badge bg-danger me-1">RETURN</span>' : '';
                
                let html = `
                    <div class="cart-item" data-index="${index}" style="${itemBg}">
                        <div class="cart-item-header">
                            <span class="cart-item-name">${badge}${item.name}</span>
                            <span class="cart-item-remove remove-cart-item"><i class="fas fa-trash-alt"></i></span>
                        </div>
                        <div class="cart-item-details">
                            <div class="qty-controls">
                                <button type="button" class="qty-btn btn-qty-minus">-</button>
                                <input type="number" class="qty-input cart-qty-val" value="${item.qty}" min="0.01" step="any">
                                <button type="button" class="qty-btn btn-qty-plus">+</button>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                <div class="d-flex align-items-center">
                                    <span class="me-1" style="font-size: 13.5px; font-weight: 800; color: #111827;">Rs</span>
                                    <input type="number" name="${isRet ? 'return_price_dummy[]' : 'price_per_piece[]'}" class="form-control form-control-sm text-end cart-price-val" style="width: 80px; padding: 2px 4px; height: 26px; font-size: 13.5px; font-weight: 800; color: #111827; border: 1px solid #e5e7eb; border-radius: 4px;" value="${item.price}" step="any" min="0" ${isRet ? 'readonly' : ''}>
                                </div>
                                ${!isRet ? `
                                <div class="d-flex align-items-center">
                                    <span class="me-1" style="font-size: 10px; color: #4b5563;">Disc (Rs):</span>
                                    <input type="number" name="item_disc[]" class="form-control form-control-sm text-end cart-item-disc-val" style="width: 60px; padding: 1px 4px; height: 22px; font-size: 11px; border: 1px solid #e5e7eb; border-radius: 4px;" value="${item.discount || 0}" min="0">
                                    <input type="hidden" name="discount_type[]" value="pkr">
                                </div>` : ''}
                            </div>
                        </div>
                        <!-- Hidden inputs for backend form serialization -->
                        ${isRet ? `
                            <input type="hidden" name="return_sale_item_id[]" value="${item.sale_item_id}">
                            <input type="hidden" name="return_product_id[]" value="${item.product_id || ''}">
                            <input type="hidden" name="return_qty[]" value="${item.qty}">
                            <input type="hidden" name="return_price[]" value="${item.price}">
                            <input type="hidden" name="return_color[]" value="${item.variantData}">
                            <input type="hidden" name="return_is_manual[]" value="${item.is_manual ? 1 : 0}">
                            <input type="hidden" name="original_sale_id[]" value="${item.original_sale_id}">
                        ` : `
                            <input type="hidden" name="product_id[]" value="${item.product_id}">
                            <input type="hidden" name="qty[]" value="${item.qty}">
                            <input type="hidden" name="total_pieces[]" value="${totalPieces}">
                            <input type="hidden" name="color[]" value="${item.variantData}">
                            <input type="hidden" name="is_manual[]" value="${item.is_manual ? 1 : 0}">
                            <input type="hidden" name="manual_vendor_id[]" value="${item.vendor_id || ''}">
                            <input type="hidden" name="manual_product_name[]" value="${item.is_manual ? item.name : ''}">
                            <input type="hidden" name="manual_purchase_price[]" value="${item.purchase_price || 0}">
                            <input type="hidden" name="manual_vendor_payment_amount[]" value="${item.vendor_payment_amount || 0}">
                            <input type="hidden" name="manual_vendor_account_id[]" value="${item.vendor_account_id || ''}">
                        `}
                    </div>
                `;
                $list.append(html);
            });
            
            $('#cartCountBadge').text(cart.length);
            $('#btnPOSSubmit').prop('disabled', false);
            updateBillSummary();
        }

        // POS Price Mode change handler
        $(document).on('change', 'input[name="pos_price_mode"]', function() {
            let mode = $(this).val();
            cart.forEach(item => {
                let wsPrice = parseFloat(item.wholesalePrice) || 0;
                if (mode === 'wholesale' && wsPrice > 0) {
                    item.price = wsPrice;
                } else {
                    item.price = item.retailPrice;
                }
            });
            renderCart();
        });

        // Cart Actions: Remove, Quantity Plus, Minus, Manual change
        $(document).on('input', '.cart-price-val', function() {
            let index = $(this).closest('.cart-item').data('index');
            if (cart[index]) {
                let val = parseFloat($(this).val()) || 0;
                if (val < 0) val = 0;
                cart[index].price = val;
                updateBillSummary();
            }
        });

        $(document).on('input', '.cart-item-disc-val', function() {
            let index = $(this).closest('.cart-item').data('index');
            if (cart[index]) {
                let val = parseFloat($(this).val()) || 0;
                if (val < 0) val = 0;
                cart[index].discount = val;
                updateBillSummary();
            }
        });

        $(document).on('click', '.remove-cart-item', function() {
            let index = $(this).closest('.cart-item').data('index');
            cart.splice(index, 1);
            renderCart();
        });

        $(document).on('click', '.btn-qty-plus', function() {
            let index = $(this).closest('.cart-item').data('index');
            let item = cart[index];
            item.qty++;
            renderCart();
        });

        $(document).on('click', '.btn-qty-minus', function() {
            let index = $(this).closest('.cart-item').data('index');
            let item = cart[index];
            if (item.qty > 1) {
                item.qty--;
                renderCart();
            }
        });

        $(document).on('change', '.cart-qty-val', function() {
            let index = $(this).closest('.cart-item').data('index');
            let item = cart[index];
            let val = parseFloat($(this).val()) || 1;
            
            if (val <= 0) val = 1;
            // No stock check required
            
            item.qty = val;
            renderCart();
        });

        // Split Payments Logic
        $('#btnAddSplitPayment').on('click', function() {
            let $template = $('#paymentRowTemplate .payment-row').clone();
            // enable inputs inside the clone
            $template.find('select, input').prop('disabled', false);
            $('#paymentRowsContainer').append($template);
        });

        $(document).on('click', '.btn-remove-payment', function() {
            if ($('#paymentRowsContainer .payment-row').length > 1) {
                $(this).closest('.payment-row').remove();
                updateBillSummary();
            } else {
                // If it's the last row, just clear it
                $(this).closest('.payment-row').find('.payment-amount').val('');
                updateBillSummary();
            }
        });

        // Live Discount and Cash calculation
        $(document).on('input', '#summaryDiscount, .payment-amount', function() {
            updateBillSummary();
        });

        let lastItemDiscountsSum = 0;

        function updateBillSummary() {
            let subtotal = 0;
            let totalReturn = 0;
            let itemDiscountsSum = 0;
            
            cart.forEach(item => {
                if (item.is_return === true) {
                    totalReturn += item.qty * item.price;
                } else {
                    subtotal += item.qty * item.price;
                    itemDiscountsSum += parseFloat(item.discount) || 0;
                }
            });
            
            let typedDiscount = parseFloat($('#summaryDiscount').val()) || 0;
            
            // If item discounts changed since last calculation, adjust the total discount box to reflect this change
            if (itemDiscountsSum !== lastItemDiscountsSum) {
                typedDiscount += (itemDiscountsSum - lastItemDiscountsSum);
                if (typedDiscount < 0) typedDiscount = 0;
                $('#summaryDiscount').val(typedDiscount.toFixed(2));
                lastItemDiscountsSum = itemDiscountsSum;
            }
            
            let extraDiscount = typedDiscount - itemDiscountsSum;
            $('#backendExtraDiscount').val(extraDiscount.toFixed(2));
            
            let payable = Math.max(0, subtotal - typedDiscount - totalReturn);
            
            $('#summarySubtotal').text('Rs ' + subtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            
            // Show return value if there are returned items
            if (totalReturn > 0) {
                if ($('#summaryReturnRow').length === 0) {
                    $('.pos-cart-summary').prepend(`
                        <div class="summary-row text-danger" id="summaryReturnRow">
                            <span>Return Value (-):</span>
                            <span id="summaryReturnVal">Rs 0.00</span>
                        </div>
                    `);
                }
                $('#summaryReturnVal').text('Rs ' + totalReturn.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            } else {
                $('#summaryReturnRow').remove();
            }
            
            $('#summaryPayable').text('Rs ' + payable.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            
            // Calculate Total Paid from all payment rows
            let totalPaid = 0;
            $('.payment-amount:not(:disabled)').each(function() {
                totalPaid += parseFloat($(this).val()) || 0;
            });
            $('#totalPaidDisplay').val(totalPaid.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            
            // Handle negative payable (Refund to Vendor)
            let netTotal = subtotal - itemDiscountsSum - totalReturn;
            if (netTotal < 0) {
                let refundAmt = Math.abs(netTotal);
                $('#summaryPayable').html(`<span class="text-danger">Refund: Rs ${refundAmt.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`);
                payable = 0; // Payable is 0, since it's a refund
                $('.payment-amount:not(:disabled)').val(0).prop('readonly', true);
                $('#returnedChange').val(refundAmt.toFixed(2));
            } else {
                $('.payment-amount:not(:disabled)').prop('readonly', false);
            }
            
            let change = Math.max(0, totalPaid - payable);
            if (netTotal >= 0) {
                $('#returnedChange').val(change.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            }
            
            // Populating hidden backend mirrors
            $('#backendSubTotal1').val(subtotal.toFixed(2));
            $('#backendSubTotal2').val(subtotal.toFixed(2));
            $('#backendTotalNet').val(payable.toFixed(2));
            $('#backendCash').val(totalPaid.toFixed(2));

            updateMobileCartBar();
        }

        function updateMobileCartBar() {
            let totalQty = cart.reduce((sum, item) => sum + (parseInt(item.qty) || 0), 0);
            let totalAmount = 0;
            cart.forEach(item => {
                let price = parseFloat(item.price) || 0;
                totalAmount += (item.qty * price);
            });
            
            $('#mobileCartCountBadge').text(totalQty);
            $('#mobileCartTotal').text('Rs ' + totalAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            
            if (totalQty > 0) {
                $('#btnOpenMobileCart').html(`Cart (${totalQty}) <i class="fas fa-arrow-right ms-1"></i>`);
            } else {
                $('#btnOpenMobileCart').html(`Cart (0) <i class="fas fa-arrow-right ms-1"></i>`);
            }
        }

        // Category Pills Filtering
        $(document).on('click', '.cat-pill', function() {
            $('.cat-pill').removeClass('active');
            $(this).addClass('active');
            let mode = $(this).data('mode');
            if (mode === 'all') {
                $('.product-card').show();
            } else {
                $('.product-card').each(function() {
                    let smode = $(this).data('size-mode');
                    if (smode === mode) $(this).show();
                    else $(this).hide();
                });
            }
        });

        // Mobile Cart Drawer Open/Close
        $('#btnOpenMobileCart, #mobilePosBottomBar').on('click', function(e) {
            $('.pos-cart-panel').addClass('mobile-drawer-open');
        });

        $('#btnCloseMobileCartDrawer, #btnBackToProducts').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('.pos-cart-panel').removeClass('mobile-drawer-open');
            if (window.innerWidth < 768) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });

        // Form Submit
        $('#posCheckoutForm').on('submit', function(e) {
            e.preventDefault();
            
            // Validate Cash Received (Avoid checkout without payment unless registered)
            let isWalkin = $('#isWalkinInput').val() === '1';
            let payable = parseFloat($('#backendTotalNet').val()) || 0;
            let received = parseFloat($('#backendCash').val()) || 0;
            
            if (isWalkin && received < payable) {
                Swal.fire('Incomplete Payment', 'Walk-in Vendor must pay the full bill amount.', 'warning');
                return;
            }

            if (!isWalkin && $('#VendorSelect').val() === null) {
                Swal.fire('Vendor Required', 'Please select a registered Vendor.', 'warning');
                return;
            }

            let $btn = $('#btnPOSSubmit');
            let origHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');

            $.ajax({
                url: '{{ route("purchase-pos.store") }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $btn.prop('disabled', false).html(origHtml);
                    if (response.ok) {
                        if (response.invoice_url) {
                            window.open(response.invoice_url, '_blank');
                        }
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Sale Processed!',
                            text: 'POS checkout / exchange completed successfully.',
                            showConfirmButton: true,
                            showCloseButton: true
                        }).then(() => {
                            cart = [];
                            $('#summaryDiscount').val(0);
                            
                            // Reset payment rows
                            let $firstRow = $('#paymentRowsContainer .payment-row').first();
                            $firstRow.find('.payment-amount').val('');
                            $('#paymentRowsContainer').empty().append($firstRow);

                            $('#posCheckoutForm')[0].reset();
                            $('#VendorSelect').val('').trigger('change');
                            renderCart();
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to complete transaction.', 'error');
                    }
                },
                error: function(err) {
                    $btn.prop('disabled', false).html(origHtml);
                    let msg = 'Failed to submit transaction.';
                    if (err.responseJSON && err.responseJSON.message) {
                        msg = err.responseJSON.message;
                    }
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

        // Variant Search Filter
        $('#variantSearchInput').on('input', function() {
            let searchTerm = $(this).val().toLowerCase();
            $('#variantsModalList .variant-mobile-card').each(function() {
                let variantName = $(this).find('.variant-title-text').text().toLowerCase();
                if (variantName.indexOf(searchTerm) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Clear search when modal opens
        $('#variantsModal').on('show.bs.modal', function () {
            $('#variantSearchInput').val('');
            $('#variantsModalList .variant-mobile-card').show();
        });

        // Discount Distribution Modal Trigger
        $('#btnDiscountDistribute').on('click', function() {
            let subtotal = 0;
            let normalItemsCount = 0;
            cart.forEach(item => {
                if (!item.is_return) {
                    subtotal += item.qty * item.price;
                    normalItemsCount++;
                }
            });

            if (normalItemsCount === 0) {
                Swal.fire('No Items', 'Please add positive sale items to distribute discount.', 'warning');
                return;
            }

            let currentDisc = parseFloat($('#summaryDiscount').val()) || 0;
            $('#distributeAmount').val(currentDisc);
            
            let $manualList = $('#manualDistributeItemsList');
            $manualList.empty();
            cart.forEach((item, index) => {
                if (!item.is_return) {
                    $manualList.append(`
                        <tr>
                            <td>${item.name} <small class="text-muted">(Rs ${item.qty * item.price})</small></td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-end manual-dist-item-input" data-index="${index}" value="${item.discount || 0}" min="0" style="height: 24px; padding: 2px 4px;">
                            </td>
                        </tr>
                    `);
                }
            });

            $('input[name="distribute_method"]').on('change', function() {
                if ($(this).val() === 'manual') {
                    $('#manualDistributeTableContainer').removeClass('d-none');
                } else {
                    $('#manualDistributeTableContainer').addClass('d-none');
                }
            });

            if ($('input[name="distribute_method"]:checked').val() === 'manual') {
                $('#manualDistributeTableContainer').removeClass('d-none');
            } else {
                $('#manualDistributeTableContainer').addClass('d-none');
            }

            $('#discountDistributeModal').modal('show');
        });

        $(document).on('input', '.manual-dist-item-input', function() {
            let sum = 0;
            $('.manual-dist-item-input').each(function() {
                sum += parseFloat($(this).val()) || 0;
            });
            $('#distributeAmount').val(sum);
        });

        $('#btnApplyDiscountDistribution').on('click', function() {
            let amount = parseFloat($('#distributeAmount').val()) || 0;
            let method = $('input[name="distribute_method"]:checked').val();
            
            let normalItems = cart.filter(item => !item.is_return);
            let subtotal = normalItems.reduce((sum, item) => sum + (item.qty * item.price), 0);

            if (method === 'equal') {
                let share = amount / normalItems.length;
                cart.forEach(item => {
                    if (!item.is_return) {
                        item.discount = parseFloat(share.toFixed(2));
                    }
                });
            } else if (method === 'proportional') {
                if (subtotal > 0) {
                    cart.forEach(item => {
                        if (!item.is_return) {
                            let prop = (item.qty * item.price) / subtotal;
                            item.discount = parseFloat((amount * prop).toFixed(2));
                        }
                    });
                }
            } else if (method === 'manual') {
                $('.manual-dist-item-input').each(function() {
                    let index = $(this).data('index');
                    let val = parseFloat($(this).val()) || 0;
                    if (cart[index]) {
                        cart[index].discount = val;
                    }
                });
            }

            $('#discountDistributeModal').modal('hide');
            renderCart();
        });

        // Exchange Modal toggle
        $('#btnPOSExchange, #btnPOSExchangeHeader').on('click', function() {
            $('#exchangeInvoiceNo').val('');
            $('#exchangeInvoiceDetails').addClass('d-none');
            $('#exchangeItemsList').html('<tr><td colspan="6" class="text-center text-muted py-3">Enter invoice number to load items.</td></tr>');
            $('#posExchangeModal').modal('show');
        });

        // Search Invoice handler
        $('#btnSearchExchangeInvoice').on('click', function() {
            let invNo = $('#exchangeInvoiceNo').val().trim();
            if (!invNo) {
                Swal.fire('Required', 'Please enter an invoice number.', 'warning');
                return;
            }

            let $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: '{{ route("pos.search_invoice") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    invoice_no: invNo
                },
                success: function(res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-search"></i> Search');
                    $('#exchangeInvoiceVal').text(res.invoice_no);
                    $('#exchangeVendorVal').text(res.name);
                    $('#exchangeInvoiceDetails').removeClass('d-none');

                    let $tbody = $('#exchangeItemsList');
                    $tbody.empty();

                    if (res.items.length === 0) {
                        $tbody.html('<tr><td colspan="6" class="text-center text-danger py-3">No returnable items found in this invoice.</td></tr>');
                        return;
                    }

                    res.items.forEach(item => {
                        let itemParts = [item.size, item.color].filter(v => v && v.trim() !== '-');
                        let desc = item.product_name + (itemParts.length > 0 ? ` (${itemParts.join(' | ')})` : '');
                        $tbody.append(`
                            <tr data-sale-item-id="${item.id}" data-is-manual="${item.is_manual}" data-product-id="${item.product_id || ''}" data-name="${item.product_name}" data-variant-data="${item.variant_data}" data-net-unit-price="${item.net_unit_price}" data-max-returnable="${item.max_returnable}" data-original-sale-id="${res.sale_id}">
                                <td class="ps-3 fw-bold" style="font-size: 13px;">${desc}</td>
                                <td class="text-center">${item.qty_sold}</td>
                                <td class="text-center">${item.already_returned}</td>
                                <td class="text-end fw-bold">Rs ${item.net_unit_price}</td>
                                <td class="text-center">
                                    <input type="number" class="form-control form-control-sm text-center return-qty-input mx-auto" value="1" min="1" max="${item.max_returnable}" style="width: 70px;">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm add-exchange-to-cart-btn">Return</button>
                                </td>
                            </tr>
                        `);
                    });
                },
                error: function(err) {
                    $btn.prop('disabled', false).html('<i class="fas fa-search"></i> Search');
                    let msg = 'Invoice details could not be loaded.';
                    if (err.responseJSON && err.responseJSON.error) {
                        msg = err.responseJSON.error;
                    }
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

        // Add return item to POS cart
        $(document).on('click', '.add-exchange-to-cart-btn', function() {
            let $row = $(this).closest('tr');
            let saleItemId = $row.data('sale-item-id');
            let isManual = parseInt($row.data('is-manual')) || 0;
            let productId = $row.data('product-id');
            let name = $row.data('name');
            let variantData = $row.data('variant-data');
            let price = parseFloat($row.data('net-unit-price')) || 0;
            let maxReturnable = parseFloat($row.data('max-returnable')) || 1;
            let originalSaleId = $row.data('original-sale-id');
            let qty = parseFloat($row.find('.return-qty-input').val()) || 1;

            if (qty > maxReturnable) {
                Swal.fire('Limit Exceeded', `Maximum returnable quantity is ${maxReturnable}.`, 'warning');
                return;
            }

            let cartId = 'return_' + (isManual ? 'manual_' + saleItemId : productId) + '_' + variantData;
            
            let cartItem = cart.find(item => item.id === cartId);
            if (cartItem) {
                if (cartItem.qty + qty <= maxReturnable) {
                    cartItem.qty += qty;
                } else {
                    cartItem.qty = maxReturnable;
                    Swal.fire('Limit Exceeded', 'Adjusted to maximum returnable quantity.', 'warning');
                }
            } else {
                cart.push({
                    id: cartId,
                    product_id: productId,
                    name: '[RETURN] ' + name,
                    price: price,
                    retailPrice: price,
                    wholesalePrice: price,
                    weightPerPiece: 0,
                    qty: qty,
                    stock: maxReturnable,
                    sizeMode: 'by_pieces',
                    piecesPerBox: 1,
                    variantData: variantData,
                    is_return: true,
                    is_manual: isManual,
                    sale_item_id: saleItemId,
                    original_sale_id: originalSaleId,
                    discount: 0
                });
            }

            let $btn = $(this);
            $btn.removeClass('btn-danger').addClass('btn-success').html('<i class="fas fa-check"></i> Added');
            setTimeout(() => {
                $btn.removeClass('btn-success').addClass('btn-danger').html('Return');
            }, 1000);

            renderCart();
        });

        // Auto-load Edit Purchase data if present
        @if(isset($editPurchaseData) && !empty($editPurchaseData))
            const editPurchaseData = @json($editPurchaseData);
            if (editPurchaseData && editPurchaseData.items && editPurchaseData.items.length > 0) {
                cart = [];
                
                if (editPurchaseData.vendor_id) {
                    $('#VendorSelect').val(editPurchaseData.vendor_id).trigger('change');
                }
                if (editPurchaseData.purchase_order_no) {
                    $('#pos_purchase_order_no').val(editPurchaseData.purchase_order_no);
                }

                if ($('#pos_edit_id').length === 0) {
                    $('#posCheckoutForm').append('<input type="hidden" name="edit_id" id="pos_edit_id" value="' + editPurchaseData.id + '">');
                } else {
                    $('#pos_edit_id').val(editPurchaseData.id);
                }

                editPurchaseData.items.forEach(function(item) {
                    addToCart(
                        item.id,
                        item.name,
                        item.price,
                        999999,
                        item.qty,
                        item.size_mode,
                        item.pieces_per_box,
                        item.variant_data,
                        item.price,
                        item.price,
                        0
                    );
                });

                if (editPurchaseData.paid_amount !== undefined) {
                    $('.payment-row:first .payment-amount').val(editPurchaseData.paid_amount).trigger('input');
                }
                if (editPurchaseData.payment_account_id) {
                    $('.payment-row:first .payment-account').val(String(editPurchaseData.payment_account_id)).trigger('change');
                }

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'Editing Purchase #' + editPurchaseData.invoice_no,
                    showConfirmButton: false,
                    timer: 3500
                });
            }
        @endif

    });
</script>
@include('admin_panel.partials.location_selector_script')
@endsection
