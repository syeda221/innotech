

<script>
    @php
        $user = auth()->user();
        $canSelectWarehouse = $user && (
            (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) ||
            (method_exists($user, 'hasRole') && ($user->hasRole('Super Admin') || $user->hasRole('admin') || $user->hasRole('superadmin'))) ||
            $user->can('warehouse.view')
        );
        $userWarehouseId = $user ? ($user->warehouse_id ?? 1) : 1;
    @endphp
    const CAN_SELECT_WAREHOUSE = {{ $canSelectWarehouse ? 'true' : 'false' }};
    const USER_DEFAULT_WH_ID = {{ $userWarehouseId ?? 1 }};

    let lastSelectedPriceMode = 'retail';
    /* =========================================
       SHARED SALES LOGIC (Add/Edit)
       ========================================= */

    // --- Helpers ---
    function pad(n) {
        return n < 10 ? '0' + n : n
    }

    function setNowStamp() {
        if ($('#entryDateTime').length) {
            const d = new Date();
            const dt =
                `${pad(d.getDate())}-${pad(d.getMonth()+1)}-${String(d.getFullYear()).slice(-2)} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
            const dOnly = `${pad(d.getDate())}-${pad(d.getMonth()+1)}-${String(d.getFullYear()).slice(-2)}`;
            $('#entryDateTime').text('Entry Date_Time: ' + dt);
            $('#entryDate').text('Date: ' + dOnly);
        }
    }

    setNowStamp();
    setInterval(setNowStamp, 60 * 1000);

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('input[name="_token"]').val()
        }
    });

    function showAlert(type, msg) {
        const el = $('#alertBox');
        if (el.length) {
            el.removeClass('d-none alert-success alert-danger alert-warning').addClass('alert-' + type).text(msg)
                .show();
            setTimeout(() => el.addClass('d-none'), 3000);
        } else {
            // fallback if alertBox missing
            if (type === 'error') Swal.fire('Error', msg, 'error');
            else if (type === 'warning') Swal.fire('Warning', msg, 'warning');
            else Swal.fire('Info', msg, 'info');
        }
    }

    function toNum(v) {
        if (typeof v === 'number') return v;
        if (!v) return 0;
        // Handle thousands separator (comma)
        const str = v.toString().replace(/,/g, '');
        return parseFloat(str) || 0;
    }

    /* =========================================
       CUSTOMER TAX STATUS (STRN / NTN / FT)
       ========================================= */
    window.currentCustomerHasStrn = false;
    window.currentCustomerHasNtn = false;
    window.currentCustomerStrn = '';
    window.currentCustomerNtn = '';

    window.applyCustomerTaxStatus = function(customerData, forceRowUpdate = true) {
        const cfg = window.saleScreenConfig || {};
        const isFtEnabled = cfg.flags && cfg.flags.further_tax !== undefined ? cfg.flags.further_tax : true;
        const strnExempt = cfg.strn_exemption !== undefined ? cfg.strn_exemption : true;
        const defaultFt = isFtEnabled ? (cfg.default_ft_rate !== undefined ? cfg.default_ft_rate : 3) : 0;
        const badgesEnabled = cfg.flags && cfg.flags.customer_tax_badges !== undefined ? cfg.flags.customer_tax_badges : true;

        if (!customerData) {
            window.currentCustomerHasStrn = false;
            window.currentCustomerHasNtn = false;
            window.currentCustomerStrn = '';
            window.currentCustomerNtn = '';
        } else {
            const strn = (customerData.strn_number || (customerData.custom_fields && customerData.custom_fields.strn_number) || '').toString().trim();
            const ntn = (customerData.ntn_number || (customerData.custom_fields && customerData.custom_fields.ntn_number) || '').toString().trim();
            window.currentCustomerStrn = strn;
            window.currentCustomerNtn = ntn;
            window.currentCustomerHasStrn = Boolean(strn !== '');
            window.currentCustomerHasNtn = Boolean(ntn !== '');
        }

        const ftPercent = (!isFtEnabled) ? 0 : ((window.currentCustomerHasStrn && strnExempt) ? 0 : defaultFt);

        const shouldForceRows = forceRowUpdate && (!window.isEditMode || window.userExplicitlyChangedCustomer === true);
        if (shouldForceRows) {
            // Update Further Tax percent on all item rows
            $('#salesTableBody tr').each(function() {
                const $r = $(this);
                $r.find('.further-tax-percent').val(ftPercent);
                if (typeof computeRow === 'function') {
                    computeRow($r);
                } else if (typeof recalculateRow === 'function') {
                    recalculateRow($r);
                }
            });
        }

        // Update customer tax badges
        if (typeof updateCustomerTaxBadges === 'function') {
            updateCustomerTaxBadges();
        }

        if (typeof updateGrandTotals === 'function') {
            updateGrandTotals();
        }
    };

    window.updateCustomerTaxBadges = function() {
        const $container = $('#customerTaxBadgeContainer');
        if (!$container.length) return;
        const cfg = window.saleScreenConfig || {};
        const badgesEnabled = cfg.flags && cfg.flags.customer_tax_badges !== undefined ? cfg.flags.customer_tax_badges : true;
        if (!badgesEnabled) {
            $container.empty().addClass('d-none');
            return;
        }

        if (window.currentCustomerHasStrn) {
            $container.html(`
                <span class="badge bg-success-subtle text-success border border-success fw-bold px-2 py-1" style="font-size: 11px;" title="STRN Registered: Exempt from Further Tax">
                    <i class="fas fa-check-circle me-1"></i> STRN: ${window.currentCustomerStrn} &bull; FT (0%) Exempt
                </span>
                ${window.currentCustomerHasNtn ? `<span class="badge bg-info-subtle text-info border border-info fw-bold px-2 py-1 ms-1" style="font-size: 11px;"><i class="fas fa-id-card me-1"></i> NTN: ${window.currentCustomerNtn}</span>` : ''}
            `).removeClass('d-none');
        } else if (window.currentCustomerHasNtn) {
            $container.html(`
                <span class="badge bg-info-subtle text-info border border-info fw-bold px-2 py-1" style="font-size: 11px;"><i class="fas fa-id-card me-1"></i> NTN: ${window.currentCustomerNtn}</span>
                <span class="badge bg-warning-subtle text-warning border border-warning fw-bold px-2 py-1 ms-1" style="font-size: 11px;" title="Unregistered for Sales Tax - Further Tax 3% applied">
                    <i class="fas fa-exclamation-triangle me-1"></i> No STRN &bull; FT (3%) Applied
                </span>
            `).removeClass('d-none');
        } else {
            const isWalkin = $('#is_walkin').val() === '1';
            const hasCust = $('#customerSelect').val();
            if (hasCust || isWalkin) {
                $container.html(`
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary fw-bold px-2 py-1" style="font-size: 11px;" title="Unregistered buyer - Further Tax 3% applied">
                        <i class="fas fa-info-circle me-1"></i> Unregistered (No STRN) &bull; FT (3%) Applied
                    </span>
                `).removeClass('d-none');
            } else {
                $container.empty().addClass('d-none');
            }
        }
    };

    /* =========================================
       PRODUCT SELECT2
       ========================================= */
    function initProductSelect2($el) {
        if (!$el || !$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) {
            try { $el.select2('destroy'); } catch(e) {}
        }
        $el.select2({
            placeholder: 'Search Product (Name / SKU / Barcode)',
            allowClear: true,
            width: '100%',
            dropdownParent: $(document.body),
            ajax: {
                url: '{{ route('products.ajax.search') }}',
                dataType: 'json',
                delay: 200,
                data: function(params) {
                    return {
                        term: params.term || '',
                        page: params.page || 1,
                        warehouse_id: $el.closest('tr').find('.warehouse').val() || $('#headerWarehouseSelect').val() || 'all'
                    };
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.results,
                        pagination: {
                            more: data.pagination.more
                        }
                    };
                },
                cache: false
            },
            minimumInputLength: 0,
            language: {
                searching: function() {
                    return '<div class="p-2 text-muted" style="font-size: 13px;"><i class="fas fa-spinner fa-spin me-1"></i> Searching products...</div>';
                },
                noResults: function() {
                    return '<div class="p-2 text-muted" style="font-size: 13px;">No product found</div>';
                }
            },
            escapeMarkup: function(markup) {
                return markup;
            },
            templateResult: formatProduct,
            templateSelection: formatSelection
        });

        $el.off('select2:open').on('select2:open', function() {
            const $select = $(this);
            const $wrap = $select.closest('.pos-table-wrap, .table-responsive');
            
            // Auto-scroll table container back to the left if scrolled horizontally
            if ($wrap.length && $wrap.scrollLeft() > 50) {
                $wrap.animate({ scrollLeft: 0 }, 100);
            }

            setTimeout(function() {
                const searchInput = document.querySelector('.select2-container--open .select2-search__field');
                if (searchInput) {
                    searchInput.focus();
                }
                $(window).trigger('scroll.select2');
            }, 50);
        });
    }

    function formatProduct(repo) {
        if (repo.loading) return repo.text;
        
        let name = repo.name || repo.text || '';
        let sku = repo.sku || '';
        let stock = repo.stock !== undefined ? repo.stock : 0;
        let stockVal = parseFloat(repo.stock_pieces !== undefined ? repo.stock_pieces : repo.stock) || 0;

        if (repo.element) {
            const $el = $(repo.element);
            if ($el.val() === '') return repo.text;
            sku = $el.data('sku') || sku;
            stock = $el.data('stock') !== undefined ? $el.data('stock') : stock;
            stockVal = parseFloat(stock) || 0;
            name = $el.data('name') || name || $el.text();
        }

        let skuText = sku ? ('SKU: ' + sku) : 'N/A';
        let badgeClass = stockVal > 0 ? 'bg-success' : 'bg-danger';

        return '<div class="d-flex align-items-center justify-content-between w-100 py-1" style="color: #0f172a;">' +
            '<div>' +
                '<div class="fw-bold" style="color: #0f172a;">' + name + '</div>' +
                '<small class="text-muted" style="font-size: 11px;">' + skuText + '</small>' +
            '</div>' +
            '<div>' +
                '<span class="badge ' + badgeClass + ' rounded-pill px-2 py-1">Stock: ' + stock + '</span>' +
            '</div>' +
        '</div>';
    }

    function formatSelection(repo) {
        if (repo.element) {
            const $el = $(repo.element);
            if (!$el.val()) return repo.text || '';
            const name = $el.data('name') || repo.name || repo.text || '';
            const sku = $el.data('sku') || repo.sku || '';
            return sku ? (name + ' (SKU: ' + sku + ')') : name;
        }
        return repo.name || repo.text;
    }


    /* =========================================
       CORE LOGIC
       ========================================= */

    function updateRowIndexes() {
        $('#salesTableBody tr').each(function(index) {
            $(this).find('.row-index').text(index + 1);
        });
    }

    function addNewRow() {
        const isWholesale = lastSelectedPriceMode === 'wholesale';
        const btnText = isWholesale ? 'W' : 'R';
        const btnClass = isWholesale ? 'btn-outline-info' : 'btn-outline-success';
        const btnTitle = isWholesale ? 'Wholesale Mode' : 'Retail Mode';

        const rowHtml = `
  <tr>
    <!-- # ROW INDEX -->
    <td class="text-center fw-bold text-muted row-index" style="vertical-align:middle; font-size:0.75rem;">1</td>

    <!-- PRODUCT -->
    <td class="col-product">
      <select class="form-select product" style="width:100%">
        <option value=""></option>
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

    @if($canSelectWarehouse)
    <!-- WAREHOUSE -->
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
    <td class="col-stock">
      <input type="text" class="form-control stock text-center input-readonly" readonly tabindex="-1">
      <input type="hidden" class="variant-stock-value">
    </td>

    <!-- Qty cell with Sub-Unit toggle on Right and Left-Aligned Cursor -->
    <td class="col-qty-wrapper">
      <div class="input-group input-group-sm">
        <input type="number" step="any" class="form-control carton-qty text-start fw-bold" name="carton_qty[]" placeholder="0" min="0" value="">
        <button type="button" class="btn btn-outline-primary qty-unit-toggle px-1 py-0 d-none fw-bold" 
                data-unit-mode="main" title="Toggle Unit (Ctn ↔ Pcs / Kg ↔ Gm / Ft ↔ In)">
          Kg
        </button>
      </div>
      <input type="hidden" class="hidden-sub-unit-mode" name="sub_unit_mode[]" value="main">
    </td>

    <!-- BONUS (Commented out) -->
    <!-- <td class="col-bonus"> -->
      <input type="hidden" class="bonus-qty" name="bonus_qty[]" value="0">
    <!-- </td> -->

    <!-- Loose Pieces -->
    <td style="width:70px;min-width:70px;" class="d-none">
      <input type="number" class="form-control loose-pcs-input text-end" name="loose_qty[]" placeholder="" min="0" value="">
    </td>

    <!-- Size -->
    <td class="col-size">
       <input type="text" class="form-control size-display text-center" name="size_display[]" placeholder="-">
       <input type="hidden" class="pack-qty" name="pack_qty[]" value="1">
    </td>

    <!-- Total Pieces (Calculated) -->
    <td class="col-pieces">
      <input type="text" class="form-control total-pieces text-end input-readonly" name="total_pieces[]" readonly placeholder="0" tabindex="-1">
      <!-- Hidden qty field for backend compatibility -->
      <input type="hidden" class="sales-qty" name="qty[]" value="0">
    </td>

    <!-- PCS/CTN (always visible; shows value when unit is Carton, "–" otherwise) -->
    <td class="col-pcs-ctn text-center">
      <input type="text" class="form-control pcs-per-ctn text-center input-readonly" readonly tabindex="-1" placeholder="-">
    </td>
 
    <!-- Price/Piece (EDITABLE) -->
    <td class="col-price-p">
      <div class="input-group input-group-sm">
        <input type="text" class="form-control visible-price text-end fw-semibold" name="visible_price[]" placeholder="0">
        <button type="button" class="btn ${btnClass} price-mode-row-toggle px-2 py-0 fw-bold" 
                data-mode="${lastSelectedPriceMode}" title="${btnTitle}">
          ${btnText}
        </button>
      </div>
      <input type="hidden" class="price-per-piece" name="price_per_piece[]">
      <input type="hidden" class="retail-price">
      <input type="hidden" class="wholesale-price">
      <input type="hidden" class="weight-per-piece">
    </td>

    <!-- SINGLE DISCOUNT COLUMN -->
    <td class="col-disc">
      <div class="input-group input-group-sm">
        <input type="number"
               class="form-control discount-value text-end"
               name="item_disc[]"
               placeholder="0">
        <input type="hidden" class="discount-type-hidden" name="discount_type[]" value="percent">
        <button type="button"
                class="btn btn-outline-secondary discount-toggle px-2 py-0 fw-bold"
                data-type="percent" tabindex="-1">%</button>
      </div>
      <input type="hidden" class="discount-amount" value="0">
    </td>

    <!-- ST % (Dynamic Sales Tax) -->
    <td class="col-st">
      <div class="input-group input-group-sm">
        <input type="number" step="0.01" class="form-control sales-tax-percent text-end" name="sales_tax_percent[]" value="${window.saleScreenConfig && window.saleScreenConfig.default_st_rate !== undefined ? window.saleScreenConfig.default_st_rate : 18}" placeholder="18">
        <span class="input-group-text px-1 py-0 text-muted" style="font-size: 11px; font-weight: 600;">%</span>
      </div>
      <input type="hidden" class="sales-tax-amount" name="sales_tax_amount[]" value="0">
    </td>

    <!-- FT % (Dynamic Further Tax) -->
    <td class="col-ft">
      <div class="input-group input-group-sm">
        <input type="number" step="0.01" class="form-control further-tax-percent text-end" name="further_tax_percent[]" value="${(window.currentCustomerHasStrn && (window.saleScreenConfig && window.saleScreenConfig.strn_exemption !== false)) ? 0 : (window.saleScreenConfig && window.saleScreenConfig.default_ft_rate !== undefined ? window.saleScreenConfig.default_ft_rate : 3)}" placeholder="0">
        <span class="input-group-text px-1 py-0 text-muted" style="font-size: 11px; font-weight: 600;">%</span>
      </div>
      <input type="hidden" class="further-tax-amount" name="further_tax_amount[]" value="0">
    </td>

    <!-- NET AMOUNT -->
    <td class="col-amount">
      <input type="text" class="form-control sales-amount text-end input-readonly" name="total[]" value="0" readonly tabindex="-1">
      <input type="hidden" class="gross-amount" name="gross_amount[]">
      <input type="hidden" class="exclusive-gst-amount" name="exclusive_gst_amount[]" value="0">
    </td>

    <!-- ACTION -->
    <td class="col-action text-center">
      <button type="button" class="del-row" tabindex="-1" title="Delete Row"><i class="fas fa-trash-alt"></i></button>
    </td>
  </tr>`;

        const $row = $(rowHtml);
        const $initialOpts = $('#salesTableBody tr:first-child .product option').clone();
        if ($initialOpts.length > 0) {
            $row.find('.product').html($initialOpts);
        }
        const $initialWhOpts = $('#salesTableBody tr:first-child .warehouse option').clone();
        if ($initialWhOpts.length > 0) {
            $row.find('.warehouse').html($initialWhOpts);
        }
        $row.find('.warehouse').val($('#headerWarehouseSelect').val() || "{{ auth()->user()->warehouse_id ?? 1 }}");
        $('#salesTableBody').append($row);
        initProductSelect2($row.find('.product'));
        updateRowIndexes();
    }

    // --- Loading Data for Rows ---

    function fetchProductPrice($row, productId) {
        console.log('Fetching price for product:', productId);
        $.get('{{ route('get-price') }}', {
            product_id: productId
        }).done(function(pRes) {
            // Fill Item Code
            $row.find('.item-code-display').val(pRes.item_code || '');

            // Populate Fields
            // Store retail price (box price) in hidden field and visible if needed
             $row.find('.retail-price').val(pRes.retail_price || 0);
             $row.find('.wholesale-price').val(pRes.wholesale_price || 0);
             $row.find('.weight-per-piece').val(pRes.weight_per_piece || 0);
             
             let rowMode = $row.find('.price-mode-row-toggle').attr('data-mode') || 'retail';
             let wsPrice = parseFloat(pRes.wholesale_price) || 0;
             let rate = (rowMode === 'wholesale' && wsPrice > 0) ? wsPrice : (pRes.retail_price || 0);

             const ppb = parseFloat(pRes.pieces_per_box) || 1;
             const unitMode = $row.find('.qty-unit-toggle').attr('data-unit-mode') || 'ctn';
             if (pRes.size_mode == "by_cartons") {
                 let piecePrice = parseFloat(pRes.sale_price_per_piece || rate || 0);
                 let cartonPrice = ppb > 1 ? (piecePrice * ppb) : piecePrice;
                 $row.find('.visible-price').val(unitMode === 'pcs' ? piecePrice : cartonPrice);
                 $row.find('.price-per-piece').val(unitMode === 'pcs' ? piecePrice : cartonPrice);
             } else if (pRes.size_mode == "by_pieces" || pRes.size_mode == "by_kg" || pRes.size_mode == "by_gm" || pRes.size_mode == "by_meter") {
                 $row.find('.visible-price').val(pRes.sale_price_per_piece || rate || 0);
                 $row.find('.price-per-piece').val($row.find('.visible-price').val() || 0);
             } else {
                 $row.find('.visible-price').val(pRes.price_per_m2 || rate || 0);
                 $row.find('.price-per-piece').val($row.find('.visible-price').val() || 0);
             }

             $row.find('.pack-qty').val(pRes.pieces_per_box || 1);
             $row.find('.size-h').val(pRes.height || '-');
             $row.find('.size-w').val(pRes.width || '-');
             $row.find('.size-mode-text').val(pRes.size_mode || '-');

            // Set default discount
            $row.find('.discount-value').val(pRes.sale_discount_percent || 0);

            $row.data('size_mode', pRes.size_mode);
            $row.data('pieces_per_box', pRes.pieces_per_box || 1);
            $row.data('price_per_m2', pRes.price_per_m2 || 0);

            setupRowQtyToggle($row, pRes.size_mode);

            computeRow($row);
        }).fail(function(err) {
            console.error('Price fetch failed', err);
        });
    }

    function loadWarehousesForProduct($row, productId, preSelectId = null) {
        var $whInput = $row.find('.warehouse');
        var targetWhId = preSelectId || $whInput.val() || $('#headerWarehouseSelect').val() || "{{ auth()->user()->warehouse_id ?? 1 }}";

        $.get('{{ route('warehouses.get') }}', {
                product_id: productId,
                variant_data: $row.find('.variant-data-hidden').val() || ''
            })
            .done(function(warehouses) {
                var validWarehouses = Array.isArray(warehouses) ? warehouses : [];
                var matched = validWarehouses.find(function(w) { return w.warehouse_id == targetWhId; });
                if (!matched && validWarehouses.length > 0) {
                    matched = validWarehouses[0];
                }

                if (matched) {
                    $whInput.val(matched.warehouse_id);
                    let disp;
                    const ppb = parseFloat(matched.ppb) || 1;

                    if ((['by_cartons', 'by_bandal'].includes(matched.size_mode) || matched.size_mode === 'by_size') && ppb > 1) {
                        const boxes = Math.floor(matched.boxes || 0);
                        const loose = matched.stock % ppb;
                        disp = loose > 0 ? `${boxes}.${loose}` : boxes;
                    } else if (matched.size_mode === 'by_kg') {
                        const s = parseFloat(matched.stock) || 0;
                        if (s > 0 && s < 1) {
                            const gm = (s * 1000).toFixed(1);
                            disp = `${s.toFixed(2)} Kg (${gm} Gm)`;
                        } else {
                            disp = `${s.toFixed(2)} Kg`;
                        }
                    } else {
                        disp = matched.stock;
                    }
                    $row.find('.stock').val(disp);
                    $row.find('.variant-stock-value').val(disp);
                }
            })
            .fail(function(xhr) {
                console.error('Warehouse fetch error:', xhr);
            });
    }

    // Row inline warehouse change handler
    $(document).on('change', '.sales-table .warehouse', function() {
        const $row = $(this).closest('tr');
        const productId = $row.find('.product-id-hidden').val() || $row.find('.product').val();
        const selectedWh = $(this).val();
        if (productId) {
            loadWarehousesForProduct($row, productId, selectedWh);
        }
    });


    // --- Calculation ---

    function computeRow($row, isManual = false) {
        const rp = toNum($row.find('.retail-price').val()); // Box Price
        const visiblePrice = toNum($row.find('.visible-price').val());
        const weightPerPiece = parseFloat($row.find('.weight-per-piece').val()) || 0;

        const m2_per_piece = parseFloat($row.find('.size-h').val() * $row.find('.size-w').val() / 10000);

        const sizeMode = $row.data('size_mode') || $row.find('.size-mode-text').val();
        const packQty = parseFloat($row.find('.pack-qty').val()) || 1;

        const rawQtyStr = ($row.find('.carton-qty').val() || '').toString().trim();
        const rawQty = parseFloat(rawQtyStr) || 0;
        const unitMode = $row.find('.qty-unit-toggle').attr('data-unit-mode') || 'main';

        // Unit Price from visible-price
        let unitPrice = toNum($row.find('.visible-price').val());

        let baseQty = rawQty;
        let pcsDisplay = rawQty;
        let gross = 0;

        if (['by_cartons', 'by_bandal'].includes(sizeMode)) {
            if (unitMode === 'pcs') {
                // Selling in Pieces: unitPrice is price per piece
                pcsDisplay = rawQty;
                baseQty = packQty > 0 ? (rawQty / packQty) : rawQty;
                gross = rawQty * unitPrice;
                $row.find('.loose-pcs-input').val(0);
            } else {
                // Selling in Cartons: unitPrice is price per carton
                if (rawQtyStr.includes('.')) {
                    const parts = rawQtyStr.split('.');
                    const boxes = parseInt(parts[0]) || 0;
                    const loose = parseInt(parts[1]) || 0;
                    pcsDisplay = (boxes * packQty) + loose;
                    baseQty = packQty > 0 ? (pcsDisplay / packQty) : rawQty;
                    const piecePrice = packQty > 0 ? (unitPrice / packQty) : unitPrice;
                    gross = (boxes * unitPrice) + (loose * piecePrice);
                    $row.find('.loose-pcs-input').val(loose);
                } else {
                    pcsDisplay = rawQty * packQty;
                    baseQty = rawQty;
                    gross = rawQty * unitPrice;
                    $row.find('.loose-pcs-input').val(0);
                }
            }
        } else if (sizeMode === 'by_kg' || sizeMode === 'by_gm') {
            if (unitMode === 'gm') {
                baseQty = rawQty / 1000;
                pcsDisplay = baseQty;
                gross = rawQty * unitPrice; // unitPrice is per Gm
            } else {
                baseQty = rawQty;
                pcsDisplay = rawQty;
                gross = rawQty * unitPrice; // unitPrice is per Kg
            }
        } else if (sizeMode === 'by_feet' || sizeMode === 'by_meter') {
            if (unitMode === 'in') {
                baseQty = rawQty / 12;
            } else {
                baseQty = rawQty;
            }
            pcsDisplay = baseQty.toFixed(3);
            gross = rawQty * unitPrice;
        } else if (sizeMode === 'by_size') {
            gross = m2_per_piece * baseQty * unitPrice;
            if (!m2_per_piece) gross = 0;
        } else {
            gross = baseQty * unitPrice;
        }

        // Sync hidden qty and total-pieces field for backend
        $row.find('.sales-qty').val(baseQty);
        $row.find('.total-pieces').val(pcsDisplay);
        $row.find('.price-per-piece').val(unitPrice);

        const discValue = toNum($row.find('.discount-value').val());
        const discType = $row.find('.discount-toggle').data('type');
        let dam = toNum($row.find('.discount-amount').val());

        // Discount Calculation
        if (discType === 'percent') {
            dam = discValue > 0 ? (gross * discValue) / 100 : 0;
        } else {
            dam = discValue > 0 ? discValue : 0;
        }
        $row.find('.discount-amount').val(dam.toFixed(2));

        const exclusiveGst = Math.max(0, gross - dam);
        $row.find('.exclusive-gst-amount').val(exclusiveGst.toFixed(2));

        const cfg = window.saleScreenConfig || {};
        const isStEnabled = cfg.flags && cfg.flags.sales_tax !== undefined ? cfg.flags.sales_tax : true;
        const isFtEnabled = cfg.flags && cfg.flags.further_tax !== undefined ? cfg.flags.further_tax : true;

        const salesTaxPct = isStEnabled ? toNum($row.find('.sales-tax-percent').val()) : 0;
        if (!isStEnabled) {
            $row.find('.sales-tax-percent').val(0);
        }
        const salesTaxAmt = isStEnabled && exclusiveGst > 0 ? (exclusiveGst * salesTaxPct) / 100 : 0;
        $row.find('.sales-tax-amount').val(salesTaxAmt.toFixed(2));

        const furtherTaxPct = isFtEnabled ? toNum($row.find('.further-tax-percent').val()) : 0;
        if (!isFtEnabled) {
            $row.find('.further-tax-percent').val(0);
        }
        const furtherTaxAmt = isFtEnabled && exclusiveGst > 0 ? (exclusiveGst * furtherTaxPct) / 100 : 0;
        $row.find('.further-tax-amount').val(furtherTaxAmt.toFixed(2));

        const netRow = exclusiveGst + salesTaxAmt + furtherTaxAmt;
        $row.find('.gross-amount').val(gross.toFixed(2));
        $row.find('.sales-amount').val(netRow.toFixed(2));

        if (typeof updateGrandTotals === 'function') {
            updateGrandTotals();
        }
    }

    function updateGrandTotals() {
        let tQty = 0;
        let tBonus = 0;
        let tGross = 0;
        let tLineDisc = 0;
        let tExclusiveGst = 0;
        let tSalesTax = 0;
        let tFurtherTax = 0;
        let tNet = 0;

        $('#salesTableBody tr').each(function() {
            const $r = $(this);
            let gross = toNum($r.find('.gross-amount').val());
            const net = toNum($r.find('.sales-amount').val());
            const dam = toNum($r.find('.discount-amount').val());
            const exclGst = toNum($r.find('.exclusive-gst-amount').val());
            const stAmt = toNum($r.find('.sales-tax-amount').val());
            const ftAmt = toNum($r.find('.further-tax-amount').val());
            const bonus = toNum($r.find('.bonus-qty').val());
            
            if (gross <= 0 && net > 0) gross = net + dam;

            // Piece calc: use total-pieces field (already computed)
            const pieces = parseInt($r.find('.total-pieces').val()) || 0;

            tQty += pieces;
            tBonus += bonus;
            tGross += gross;
            tLineDisc += dam;
            tExclusiveGst += exclGst;
            tSalesTax += stAmt;
            tFurtherTax += ftAmt;
            tNet += net;
        });

        const isWalkin = $('#is_walkin').val() === '1';

        let orderDisc = 0;
        // Rs discount is always applied (Order Summary -> Discount (Rs))
        const rsDisc = toNum($('#walkinDiscountRs').length ? $('#walkinDiscountRs').val() : 0);
        // % discount additionally applied if provided
        const orderPct = toNum($('#discountPercent').length ? $('#discountPercent').val() : 0);
        orderDisc = (rsDisc > 0 ? rsDisc : 0) + ((orderPct > 0 && tNet > 0) ? (tNet * orderPct) / 100 : 0);

        const freightType = $('#freightType').length ? $('#freightType').val() : 'add';
        const freightCharges = $('#freightCharges').length ? toNum($('#freightCharges').val()) : 0;

        let currentInvoiceTotal = Math.max(0, tNet - orderDisc);
        if (freightType === 'add') {
            currentInvoiceTotal += freightCharges;
        } else if (freightType === 'deduct') {
            currentInvoiceTotal -= freightCharges;
        }

        const prev = isWalkin ? 0 : toNum($('#previousBalance').val());
        const receipts = toNum($('#receiptsTotal').text());
        const payable = Math.max(0, currentInvoiceTotal + prev - receipts);

        $('#tQty').text(tQty.toFixed(0));
        if ($('#tBonus').length) $('#tBonus').text(tBonus.toFixed(0));
        $('#tGross').text(tGross.toFixed(2));
        $('#tLineDisc').text(tLineDisc.toFixed(2));
        if ($('#tExclusiveGst').length) $('#tExclusiveGst').text(tExclusiveGst.toFixed(2));
        if ($('#tSalesTax').length) $('#tSalesTax').text(tSalesTax.toFixed(2));
        if ($('#tFurtherTax').length) $('#tFurtherTax').text(tFurtherTax.toFixed(2));

        // Dynamic Tax Rate Labels in Summary
        if ($('#labelStRate').length) {
            let stRates = [];
            $('#salesTableBody tr').each(function() {
                stRates.push(toNum($(this).find('.sales-tax-percent').val()));
            });
            const uniqueSt = [...new Set(stRates)];
            if (uniqueSt.length === 1) {
                $('#labelStRate').text(uniqueSt[0] + '%');
            } else if (uniqueSt.length > 1) {
                $('#labelStRate').text('Mixed');
            }
        }
        if ($('#labelFtRate').length) {
            let ftRates = [];
            $('#salesTableBody tr').each(function() {
                ftRates.push(toNum($(this).find('.further-tax-percent').val()));
            });
            const uniqueFt = [...new Set(ftRates)];
            if (uniqueFt.length === 1) {
                $('#labelFtRate').text(uniqueFt[0] + '%');
            } else if (uniqueFt.length > 1) {
                $('#labelFtRate').text('Mixed');
            }
        }
        $('#tSub').text(currentInvoiceTotal.toFixed(2));
        $('#tOrderDisc').text(orderDisc.toFixed(2));
        $('#tPrev').text(prev.toFixed(2));
        $('#tPayable').text(payable.toFixed(2));
        $('#totalAmount').text(tNet.toFixed(2));
        $('#walkinNetTotal').text(currentInvoiceTotal.toFixed(2));
        $('#bottomPaymentsTotal').text(receipts.toFixed(2));
        $('#receiptsTotalBadge').text(receipts.toFixed(2));
        $('#itemsRowCount').text($('#salesTableBody tr').length);

        // Display current bill total after all discounts
        $('#tCurrentBill').text(currentInvoiceTotal.toFixed(2));

        const walkinPaid = receipts;
        const change = walkinPaid - currentInvoiceTotal;
        const changeStr = change.toFixed(2);
        $('#walkinChange').text(changeStr);
        $('#bottomChangeVal').text(changeStr);
        $('#backendChange').val(change > 0 ? change.toFixed(2) : 0);
        if (change > 0) {
            $('#walkinChange, #bottomChangeVal').removeClass('text-warning text-danger').addClass('text-success');
            $('#changeAccountRow, #bottomChangeAccountWrapper, #editChangeAccountContainer').show().css('display', 'flex');
        } else if (change === 0) {
            $('#walkinChange, #bottomChangeVal').removeClass('text-warning text-danger').addClass('text-success');
            $('#changeAccountRow, #bottomChangeAccountWrapper, #editChangeAccountContainer').hide();
        } else {
            $('#walkinChange, #bottomChangeVal').removeClass('text-success text-warning').addClass('text-danger');
            $('#changeAccountRow, #bottomChangeAccountWrapper, #editChangeAccountContainer').hide();
        }

        $('#subTotal1').val(tGross.toFixed(2));
        $('#subTotal2').val(tNet.toFixed(2));
        $('#discountAmount').val(orderDisc.toFixed(2));
        $('#totalBalance').val(currentInvoiceTotal.toFixed(2));
        $('input[name="cash"]').val(receipts.toFixed(2));

        // Update Premium Horizontal Card Values
        // 1. Customer Name
        let customerName = "Select Customer";
        const customerVal = $('#customerSelect').val();
        if (isWalkin) {
            customerName = $('#walkinNameInput').val() || "Walk-in Customer";
        } else if (customerVal) {
            let customerData = null;
            if ($('#customerSelect').hasClass('select2-hidden-accessible') || $('#customerSelect').data('select2')) {
                try {
                    customerData = $('#customerSelect').select2('data');
                } catch(e) {
                    customerData = null;
                }
            }
            if (customerData && customerData.length > 0 && customerData[0].customer) {
                customerName = customerData[0].customer.customer_name;
            } else {
                const text = $('#customerSelect').find(':selected').text() || '';
                const parts = text.split(' — ');
                customerName = parts.length > 1 ? parts[1] : (text || "Select Customer");
            }
        }
        $('#cc_customer_name').text(customerName);

        // 2. Previous Balance
        const prevAbs = Math.abs(prev);
        const prevSuffix = prev >= 0 ? 'Dr' : 'Cr';
        $('#cc_prev_bal_val').text('Rs ' + prevAbs.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2}));
        $('#cc_prev_bal_suffix').text(prevSuffix);
        if (prev >= 0) {
            $('#cc_prev_bal_val, #cc_prev_bal_suffix').removeClass('text-success').addClass('text-danger');
        } else {
            $('#cc_prev_bal_val, #cc_prev_bal_suffix').removeClass('text-danger').addClass('text-success');
        }

        // 3. Current Bill
        $('#cc_current_bill').text('Rs ' + currentInvoiceTotal.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2}));

        // 4. Paid Now
        $('#cc_paid_now').text('Rs ' + receipts.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2}));

        // 5. Closing Balance
        const actualClosingBal = currentInvoiceTotal + prev - receipts;
        const closingAbs = Math.abs(actualClosingBal);
        const closingSuffix = actualClosingBal >= 0 ? 'Dr' : 'Cr';
        $('#cc_closing_bal_val').text('Rs ' + closingAbs.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2}));
        $('#cc_closing_bal_suffix').text(closingSuffix);
        if (actualClosingBal >= 0) {
            $('#cc_closing_bal_val, #cc_closing_bal_suffix').removeClass('text-success').addClass('text-danger');
        } else {
            $('#cc_closing_bal_val, #cc_closing_bal_suffix').removeClass('text-danger').addClass('text-success');
        }
    }


    /* =========================================
       VALIDATION & SAVE
       ========================================= */

    function serializeForm() {
        return $('#saleForm').serialize();
    }

    function canPost() {
        let ok = false;
        $('#salesTableBody tr').each(function() {
            const pid = $(this).find('.product-id-hidden').val() || $(this).find('.product').val();
            const cartons = parseFloat($(this).find('.carton-qty').val()) || 0;
            const loose   = parseFloat($(this).find('.loose-pcs-input').val()) || 0;
            const pieces  = parseFloat($(this).find('.total-pieces').val()) || 0;
            const qty     = parseFloat($(this).find('.sales-qty').val()) || 0;
            if (pid && (cartons > 0 || loose > 0 || pieces > 0 || qty > 0)) {
                ok = true;
                return false;
            }
        });
        return ok;
    }

    function refreshPostedState() {
        const state = canPost();
        $('#btnPosted, #btnHeaderPosted').prop('disabled', !state);
    }

    function ensureSaved() {
        return new Promise(function(resolve, reject) {
            const existing = $('#booking_id').val();
            let url = '{{ route('sales.store') }}';
            let method = 'POST';
            if (existing) {
                url = '{{ route('sales.update', ':id') }}'.replace(':id', existing);
                method = 'PUT';
            }

            $('#btnSave, #btnHeaderPosted, #btnPosted').prop('disabled', true);

            $.ajax({
                url: url,
                type: method,
                data: serializeForm(),
                success: function(res) {
                    $('#btnSave, #btnHeaderPosted, #btnPosted').prop('disabled', false);
                    if (res?.ok) {
                        const bid = res.booking_id || existing;
                        $('#booking_id').val(bid);
                        if ($('#action').val() === 'booking') {
                            Swal.fire('Saved', 'Sale saved as booking successfully', 'success');
                        }
                        resolve(res);
                    } else {
                        Swal.fire('Error', res.msg || 'Save failed', 'error');
                        reject(res);
                    }
                },
                error: function(xhr) {
                    $('#btnSave, #btnHeaderPosted, #btnPosted').prop('disabled', false);
                    let errMsg = 'Save error';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        try {
                            let resp = JSON.parse(xhr.responseText);
                            if (resp.message) errMsg = resp.message;
                        } catch(e) {}
                    }
                    Swal.fire('Error', errMsg, 'error');
                    reject(xhr);
                }
            });
        });
    }

    function postNow() {
        let formData = $('#saleForm').serializeArray();
        formData = formData.filter(item => item.name !== '_method');

        $.post('{{ route('sales.post_final') }}', $.param(formData))
            .done(function(res) {
                if (res?.ok) {
                    Swal.fire({
                        title: 'Success!',
                        text: res.msg || 'Sale saved successfully',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => window.location.href = "{{ route('sale.index') }}", 1500);
                } else {
                    Swal.fire('Post Failed', res.msg || 'Post failed', 'error');
                }
            })
            .fail(function(xhr) {
                let errMsg = 'Post error';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    try {
                        let resp = JSON.parse(xhr.responseText);
                        if (resp.message) errMsg = resp.message;
                    } catch(e) {}
                }
                Swal.fire('Error', errMsg, 'error');
            });
    }

    // Validation Utils
    function markInvalid($el) {
        $el.addClass('invalid-input invalid-select');
        $el.closest('td').addClass('invalid-cell');
    }

    function clearInvalid($el) {
        $el.removeClass('invalid-input invalid-select');
        $el.closest('td').removeClass('invalid-cell');
    }

    function clearAllInvalids() {
        $('.invalid-input, .invalid-select').removeClass('invalid-input invalid-select');
        $('.invalid-cell').removeClass('invalid-cell');
    }

    function cleanupEmptyRows() {
        $('#salesTableBody tr').each(function() {
            const $r = $(this);
            const prod = $r.find('.product-id-hidden').val();
            const wh = $r.find('.warehouse').val();
            const cartons = parseInt($r.find('.carton-qty').val()) || 0;
            const loose   = parseInt($r.find('.loose-pcs-input').val()) || 0;
            const qty = cartons + loose;
            if ((!prod || prod === '') && qty <= 0) {
                if ($('#salesTableBody tr').length > 1) {
                    $r.remove();
                } else {
                    $r.find('select').val('');
                    $r.find('input').val('');
                    $r.find('.carton-qty').val(0);
                    $r.find('.loose-pcs-input').val(0);
                    $r.find('.stock').val('');
                    $r.find('.sales-amount').val('0');
                }
            }
        });
        if ($('#salesTableBody tr').length === 0) addNewRow();
    }

    function validateHeader() {
        let ok = true;
        let firstMessage = null;
        let firstEl = null;

        const isWalkin = $('#is_walkin').val() === '1';

        if (isWalkin) {
            const walkinName = $('#walkinNameInput').val().trim();
            if (!walkinName) {
                ok = false;
                firstMessage = 'Please enter a Walk-in Customer Name';
                firstEl = $('#walkinNameInput');
                markInvalid($('#walkinNameInput'));
            } else {
                $('#walkinNameInput').removeClass('is-invalid border-danger');
            }
        } else {
            const cust = $('#customerSelect').val();
            if (!cust) {
                ok = false;
                if (!firstMessage) {
                    firstMessage = 'Please select a Customer';
                    firstEl = $('#customerSelect');
                }
                markInvalid($('#customerSelect'));
            }
        }

        const chequeAmt = parseFloat($('#sale_cheque_amount').val() || '0') || 0;
        if (chequeAmt > 0) {
            if (!$('#sale_cheque_no').val() || !$('#sale_cheque_bank').val() || !$('#sale_cheque_date').val()) {
                ok = false;
                if (!firstMessage) {
                    firstMessage = 'Please enter Cheque Number, Bank Name, and Due Date for the cheque payment.';
                    firstEl = !$('#sale_cheque_no').val() ? $('#sale_cheque_no') : (!$('#sale_cheque_bank').val() ? $('#sale_cheque_bank') : $('#sale_cheque_date'));
                }
            }
        }

        return {
            ok,
            firstMessage,
            firstEl
        };
    }

    function validateRows() {
        let ok = true;
        let firstMessage = null;
        let firstEl = null;

        $('#salesTableBody tr').each(function(rowIndex) {
            const $row = $(this);
            const $wh = $row.find('.warehouse');
            const $prod = $row.find('.product');
            const $cartonQtyInput = $row.find('.carton-qty');
            const $loosePcsInput = $row.find('.loose-pcs-input');
            const hiddenPid = $row.find('.product-id-hidden').val();
            const prodVal = $prod.val();

            if (!$wh.val()) {
                $wh.val("{{ auth()->user()->warehouse_id ?? 1 }}");
            }

            // If this row has no product selected:
            if (!prodVal && !hiddenPid) {
                // If there are other items in the table, remove this unselected row automatically
                if ($('#salesTableBody tr').length > 1) {
                    $row.remove();
                    return;
                }
                ok = false;
                if (!firstMessage) {
                    firstMessage = 'Bara-e-karam sale mein item select karein (Row ' + (rowIndex + 1) + ')';
                    firstEl = $prod;
                }
                markInvalid($prod);
                return;
            }

            const qtyVal = (parseFloat($cartonQtyInput.val()) || 0) + (parseFloat($loosePcsInput.val()) || 0);
            if (qtyVal <= 0) {
                ok = false;
                if (!firstMessage) {
                    firstMessage = 'Qty daalen row ' + (rowIndex + 1) + ' mein';
                    firstEl = $cartonQtyInput;
                }
                markInvalid($cartonQtyInput);
            }
        });

        const chequeAmt = parseFloat($('#sale_cheque_amount').val() || '0') || 0;
        if (chequeAmt > 0) {
            if (!$('#sale_cheque_no').val() || !$('#sale_cheque_bank').val() || !$('#sale_cheque_date').val()) {
                ok = false;
                if (!firstMessage) {
                    firstMessage = 'Please enter Cheque Number, Bank Name, and Due Date for the cheque payment.';
                    firstEl = !$('#sale_cheque_no').val() ? $('#sale_cheque_no') : (!$('#sale_cheque_bank').val() ? $('#sale_cheque_bank') : $('#sale_cheque_date'));
                }
            }
        }

        return {
            ok,
            firstMessage,
            firstEl
        };
    }

    function validateReceipts() {
        let ok = true;
        let firstMessage = null;
        let firstEl = null;

        $('#rvWrapper .rv-row').each(function(i) {
            const $row = $(this);
            const $acc = $row.find('.rv-account');
            const $amt = $row.find('.rv-amount');
            const amtVal = parseFloat($amt.val() || '0') || 0;

            if (amtVal > 0 && (!$acc.val() || $acc.val() === "")) {
                ok = false;
                if (!firstMessage) {
                    firstMessage = 'Please select Account for receipt row ' + (i + 1);
                    firstEl = $acc;
                }
                markInvalid($acc);
            }
        });

        const chequeAmt = parseFloat($('#sale_cheque_amount').val() || '0') || 0;
        if (chequeAmt > 0) {
            if (!$('#sale_cheque_no').val() || !$('#sale_cheque_bank').val() || !$('#sale_cheque_date').val()) {
                ok = false;
                if (!firstMessage) {
                    firstMessage = 'Please enter Cheque Number, Bank Name, and Due Date for the cheque payment.';
                    firstEl = !$('#sale_cheque_no').val() ? $('#sale_cheque_no') : (!$('#sale_cheque_bank').val() ? $('#sale_cheque_bank') : $('#sale_cheque_date'));
                }
            }
        }

        return {
            ok,
            firstMessage,
            firstEl
        };
    }

    function validateFormAll() {
        clearAllInvalids();

        const h = validateHeader();
        if (!h.ok) return {
            ok: false,
            message: h.firstMessage,
            el: h.firstEl
        };

        const r = validateRows();
        if (!r.ok) return {
            ok: false,
            message: r.firstMessage,
            el: r.firstEl
        };

        const rec = validateReceipts();
        if (!rec.ok) return {
            ok: false,
            message: rec.firstMessage,
            el: rec.firstEl
        };

        return {
            ok: true
        };
    }


    /* =========================================
       EVENT BINDINGS
    ========================================= */

    // Customer Type change logic -> controls Walk-in behavior
    function handleCustomerTypeChange() {
        const type = $('#partyTypeSelect').val();
        const isWalkin = (type === 'Walking Customer');
        
        // Update hidden field value
        $('#is_walkin').val(isWalkin ? '1' : '0');
        
        if (isWalkin) {
            $('#previousBalance').val(0);
            $('#rangeBalance').val(0);
            $('#ci_name').text($('#walkinNameInput').val() || 'Walk-in Customer');
            $('#ci_mobile').text('—');
            $('#ci_address').text('—');
            $('#ci_code').text('—');
            $('#cc_customer_name').text($('#walkinNameInput').val() || 'Walk-in Customer');

            $('#btnTypeWalkin').addClass('btn-primary active text-white').removeClass('btn-outline-primary');
            $('#btnTypeCustomer').removeClass('btn-primary active text-white').addClass('btn-outline-primary');
            
            $('#customerSelect').addClass('d-none').next('.select2-container').addClass('d-none');
            $('#walkinNameInput').removeClass('d-none');
            
            $('#receiptVouchersSection').hide();
            $('#totalsSection').removeClass('col-lg-5').addClass('col-lg-12');
            $('#totalsCustomerView').addClass('d-none').removeClass('d-flex');
            $('#totalsWalkinView').removeClass('d-none').addClass('d-flex');
            if ($('#walkinReceiptsContainer').length) {
                $('#rvWrapper').appendTo('#walkinReceiptsContainer');
            }
            if (typeof window.applyCustomerTaxStatus === 'function') {
                window.applyCustomerTaxStatus(null, true);
            }
        } else {
            $('#btnTypeCustomer').addClass('btn-primary active text-white').removeClass('btn-outline-primary');
            $('#btnTypeWalkin').removeClass('btn-primary active text-white').addClass('btn-outline-primary');
            
            $('#walkinNameInput').addClass('d-none');
            $('#customerSelect').removeClass('d-none').next('.select2-container').removeClass('d-none');
            
            $('#receiptVouchersSection').show();
            $('#totalsSection').removeClass('col-lg-12').addClass('col-lg-5');
            $('#totalsWalkinView').addClass('d-none').removeClass('d-flex');
            $('#totalsCustomerView').removeClass('d-none').addClass('d-flex');
            if ($('#receiptVouchersSection .card-body').length) {
                $('#rvWrapper').appendTo('#receiptVouchersSection .card-body');
            }

            const currentCustId = $('#customerSelect').val();
            if (currentCustId) {
                $.get("{{ url('sale/customers') }}/" + currentCustId + "?t=" + new Date().getTime(), function(d) {
                    if (typeof window.applyCustomerTaxStatus === 'function') {
                        window.applyCustomerTaxStatus(d, true);
                    }
                });
            } else if (typeof window.applyCustomerTaxStatus === 'function') {
                window.applyCustomerTaxStatus(null, true);
            }
        }
        if (typeof updateGrandTotals === 'function') updateGrandTotals();
    }

    $(document).on('change', '#partyTypeSelect', function() {
        handleCustomerTypeChange();
    });

    $(document).on('click', '#btnTypeCustomer', function() {
        $('#partyTypeSelect').val('Main Customer').trigger('change');
    });

    $(document).on('click', '#btnTypeWalkin', function() {
        $('#partyTypeSelect').val('Walking Customer').trigger('change');
    });

    $(document).on('input', '#walkinNameInput', function() {
        if ($('#is_walkin').val() === '1') {
            $('#cc_customer_name').text($(this).val() || 'Walk-in Customer');
        }
    });

    $(document).ready(function() {

        // Show Pcs/Ctn header on load if any existing row is in carton mode
        if (typeof refreshPcsCtnHeader === 'function') refreshPcsCtnHeader();

        // Walk-in UI Event Bindings
        $(document).on('input', '#walkinDiscountRs, #freightCharges', function() {
            updateGrandTotals();
        });
        
        $(document).on('click', '#freightTypeToggle', function() {
            const btn = $(this);
            const input = $('#freightType');
            if (input.val() === 'add') {
                input.val('deduct');
                btn.text('-');
            } else {
                input.val('add');
                btn.text('+');
            }
            updateGrandTotals();
        });
        
        // Ensure initial UI setup on page load
        handleCustomerTypeChange();

        // Remove invalid classes on input
        $(document).on('input change', 'select, input, textarea', function() {
            clearInvalid($(this));
        });

        // Product change
        $('#salesTableBody').on('select2:select', '.product', function(e) {
            console.log("product change in shared logic");
            if (window.isEditModeLoading) return; // Block during edit load
            
            const data = e.params.data;
            if (!data || !data.id) return;
            
            const $row = $(this).closest('tr');
            
            if (data.element) {
                const $el = $(data.element);
                data.sku = data.sku || $el.data('sku') || '';
                data.retail_price = data.retail_price !== undefined ? data.retail_price : ($el.data('retail_price') || $el.data('trade_price') || 0);
                data.wholesale_price = data.wholesale_price !== undefined ? data.wholesale_price : ($el.data('wholesale_price') || 0);
                data.weight_per_piece = data.weight_per_piece !== undefined ? data.weight_per_piece : ($el.data('weight_per_piece') || 0);
                data.size_mode = data.size_mode || $el.data('size_mode') || 'by_pcs';
                data.pieces_per_box = data.pieces_per_box || $el.data('pieces_per_box') || 1;
                data.variant_data = data.variant_data || $el.data('variant_data') || '';
                data.stock = data.stock !== undefined ? data.stock : ($el.data('stock') || 0);
                data.sale_discount_percent = data.sale_discount_percent !== undefined ? data.sale_discount_percent : ($el.data('sale_discount_percent') || 0);
            }

            let pid = data.id.toString().split('|')[0];
            $row.find('.product-id-hidden').val(pid);
            $row.find('.variant-data-hidden').val(data.variant_data || '');
            
            // Parse variant data for size/color display
            let variantSize = '-';
            let variantColor = '-';
            let variantStock = null;
            if (data.variant_data) {
                try {
                    const vd = JSON.parse(atob(data.variant_data));
                    variantSize = (vd.size && vd.size !== '-') ? vd.size : '-';
                    variantColor = (vd.color && vd.color !== '-') ? vd.color : '-';
                    // Prefer vd.current_stock from parsed variant_data (highly reliable), fallback to data.stock or vd.stock
                    variantStock = vd.current_stock !== undefined ? vd.current_stock : (data.stock !== undefined ? data.stock : (vd.stock !== undefined ? vd.stock : null));
                } catch(ex) {}
            }
            
            // Set size and color display
            $row.find('.size-display').val(variantSize !== '-' ? variantSize : '');
            $row.find('.color-display').val(variantColor);
            
            // Store variant stock for later use (after warehouse loads)
            if (variantStock !== null) {
                $row.find('.variant-stock-value').val(variantStock);
                $row.find('.stock').val(variantStock);
            } else {
                $row.find('.variant-stock-value').val('');
            }
            
            loadWarehousesForProduct($row, pid);
            
            // Set properties directly
            $row.find('.item-code-display').val(data.sku || '');
            $row.find('.retail-price').val(data.retail_price || data.trade_price || 0);
            $row.find('.wholesale-price').val(data.wholesale_price || 0);
            $row.find('.weight-per-piece').val(data.weight_per_piece || 0);
            
            let rowMode = $row.find('.price-mode-row-toggle').attr('data-mode') || 'retail';
            let wsPrice = parseFloat(data.wholesale_price) || 0;
            let rate = (rowMode === 'wholesale' && wsPrice > 0) ? wsPrice : (data.retail_price || data.trade_price || 0);

            const ppb = parseFloat(data.pieces_per_box) || 1;
            const unitMode = $row.find('.qty-unit-toggle').attr('data-unit-mode') || 'ctn';
            if (['by_cartons', 'by_bandal'].includes(data.size_mode)) {
                let cartonPrice = ppb > 1 ? (rate * ppb) : rate;
                $row.find('.visible-price').val(unitMode === 'pcs' ? rate : cartonPrice);
                $row.find('.price-per-piece').val(unitMode === 'pcs' ? rate : cartonPrice);
            } else {
                $row.find('.visible-price').val(rate);
                $row.find('.price-per-piece').val(rate);
            }
            
            $row.find('.pack-qty').val(data.pieces_per_box || 1);
            $row.find('.size-h').val(data.height || '-');
            $row.find('.size-w').val(data.width || '-');
            $row.find('.size-mode-text').val(data.size_mode || '-');
            
            $row.find('.discount-value').val(data.sale_discount_percent || 0);
            
            $row.data('size_mode', data.size_mode);
            $row.data('pieces_per_box', data.pieces_per_box || 1);
            
            setupRowQtyToggle($row, data.size_mode);

            computeRow($row);
            checkAndOpenProductTracking($row, pid);
            setTimeout(() => {
                $row.find('.carton-qty').focus().select();
            }, 100);
        });

    // Pcs/Ctn column is always rendered — just set the value based on size mode
    function updatePcsCtnColumn($row, sizeMode) {
        const packQty = parseFloat($row.find('.pack-qty').val()) || 1;
        const $col = $row.find('.col-pcs-ctn');
        if ($col.length === 0) return;
        if (['by_cartons', 'by_bandal'].includes(sizeMode)) {
            $row.find('.pcs-per-ctn').val(packQty);
        } else {
            $row.find('.pcs-per-ctn').val('');
        }
    }

    function refreshPcsCtnHeader() {
        $('.col-pcs-ctn-th').show();
    }

    function setupRowQtyToggle($row, sizeMode) {
        const $toggleBtn = $row.find('.qty-unit-toggle');
        updatePcsCtnColumn($row, sizeMode);
        if (['by_cartons', 'by_bandal'].includes(sizeMode)) {
            let label = (sizeMode === 'by_bandal') ? 'Bundal' : 'Ctn';
            $toggleBtn.removeClass('d-none')
                      .attr('data-unit-mode', 'ctn')
                      .text(label)
                      .removeClass('btn-outline-primary btn-outline-info btn-outline-warning')
                      .addClass('btn-outline-success');
            $row.find('.hidden-sub-unit-mode').val('ctn');
            $row.find('.carton-qty').attr('placeholder', '0');
        } else if (sizeMode === 'by_kg') {
            $toggleBtn.removeClass('d-none')
                      .attr('data-unit-mode', 'kg')
                      .text('Kg')
                      .removeClass('btn-outline-info btn-outline-warning btn-outline-success')
                      .addClass('btn-outline-primary');
            $row.find('.hidden-sub-unit-mode').val('kg');
            $row.find('.carton-qty').attr('placeholder', '0');
        } else if (sizeMode === 'by_gm') {
            $toggleBtn.removeClass('d-none')
                      .attr('data-unit-mode', 'gm')
                      .text('Gm')
                      .removeClass('btn-outline-primary btn-outline-warning btn-outline-success')
                      .addClass('btn-outline-info');
            $row.find('.hidden-sub-unit-mode').val('gm');
            $row.find('.carton-qty').attr('placeholder', '0');
        } else if (sizeMode === 'by_feet') {
            $toggleBtn.removeClass('d-none')
                      .attr('data-unit-mode', 'ft')
                      .text('Ft')
                      .removeClass('btn-outline-info btn-outline-warning btn-outline-success')
                      .addClass('btn-outline-primary');
            $row.find('.hidden-sub-unit-mode').val('ft');
            $row.find('.carton-qty').attr('placeholder', '0');
        } else if (sizeMode === 'by_meter') {
            $toggleBtn.removeClass('d-none')
                      .attr('data-unit-mode', 'm')
                      .text('Mtr')
                      .removeClass('btn-outline-info btn-outline-warning btn-outline-success')
                      .addClass('btn-outline-primary');
            $row.find('.hidden-sub-unit-mode').val('m');
            $row.find('.carton-qty').attr('placeholder', '0');
        } else {
            $toggleBtn.addClass('d-none').attr('data-unit-mode', 'main');
            $row.find('.hidden-sub-unit-mode').val('main');
            $row.find('.carton-qty').attr('placeholder', '0');
        }
    }

    // Toggle Qty Sub-Unit (Ctn ↔ Pcs / Kg ↔ Gm / Ft ↔ In)
    $(document).on('click', '.qty-unit-toggle', function () {
        const $btn = $(this);
        const $row = $btn.closest('tr');
        const sizeMode = $row.data('size_mode') || $row.find('.size-mode-text').val();
        let currentMode = $btn.attr('data-unit-mode');

        if (['by_cartons', 'by_bandal'].includes(sizeMode)) {
            const packQty = parseFloat($row.find('.pack-qty').val()) || 1;
            const $priceInp = $row.find('.visible-price');
            let curPrice = parseFloat($priceInp.val()) || 0;
            let retailPrice = parseFloat($row.find('.retail-price').val()) || 0;
            let wholesalePrice = parseFloat($row.find('.wholesale-price').val()) || 0;
            let rowMode = $row.find('.price-mode-row-toggle').attr('data-mode') || 'retail';
            let basePiecePrice = (rowMode === 'wholesale' && wholesalePrice > 0) ? wholesalePrice : retailPrice;

            if (currentMode === 'ctn') {
                currentMode = 'pcs';
                $btn.attr('data-unit-mode', 'pcs').text('Pcs').removeClass('btn-outline-success').addClass('btn-outline-info');
                $row.find('.carton-qty').attr('placeholder', '0');

                // Convert Quantity from Ctn (dot notation) to Pcs
                const curQtyStr = ($row.find('.carton-qty').val() || '').toString().trim();
                if (curQtyStr !== '') {
                    if (curQtyStr.includes('.')) {
                        const parts = curQtyStr.split('.');
                        const boxes = parseInt(parts[0]) || 0;
                        const loose = parseInt(parts[1]) || 0;
                        const totalPcs = (boxes * packQty) + loose;
                        $row.find('.carton-qty').val(totalPcs);
                    } else {
                        const boxes = parseFloat(curQtyStr) || 0;
                        $row.find('.carton-qty').val(boxes * packQty);
                    }
                }

                if (packQty > 1 && curPrice > 0) {
                    let piecePrice = curPrice / packQty;
                    $priceInp.val(piecePrice % 1 === 0 ? piecePrice : piecePrice.toFixed(2));
                } else if (basePiecePrice > 0) {
                    $priceInp.val(basePiecePrice);
                }
                $row.find('.price-per-piece').val($priceInp.val());
            } else {
                currentMode = 'ctn';
                let label = (sizeMode === 'by_bandal') ? 'Bundal' : 'Ctn';
                $btn.attr('data-unit-mode', 'ctn').text(label).removeClass('btn-outline-info').addClass('btn-outline-success');
                $row.find('.carton-qty').attr('placeholder', '0');

                // Convert Quantity from Pcs to Ctn (dot notation)
                const curPcs = parseFloat($row.find('.carton-qty').val()) || 0;
                if (curPcs > 0 && packQty > 1) {
                    const boxes = Math.floor(curPcs / packQty);
                    const loose = Math.round(curPcs % packQty);
                    $row.find('.carton-qty').val(loose > 0 ? `${boxes}.${loose}` : boxes);
                }

                if (packQty > 1 && curPrice > 0) {
                    let cartonPrice = curPrice * packQty;
                    $priceInp.val(cartonPrice % 1 === 0 ? cartonPrice : cartonPrice.toFixed(2));
                } else if (basePiecePrice > 0) {
                    let cartonPrice = packQty > 1 ? (basePiecePrice * packQty) : basePiecePrice;
                    $priceInp.val(cartonPrice % 1 === 0 ? cartonPrice : cartonPrice.toFixed(2));
                }
                $row.find('.price-per-piece').val($priceInp.val());
            }
            $row.find('.hidden-sub-unit-mode').val(currentMode);
            computeRow($row);
        } else if (sizeMode === 'by_kg' || sizeMode === 'by_gm') {
            const $priceInp = $row.find('.visible-price');
            let curPrice = parseFloat($priceInp.val()) || 0;
            const curQty = parseFloat($row.find('.carton-qty').val()) || 0;

            if (currentMode === 'kg') {
                currentMode = 'gm';
                $btn.text('Gm').removeClass('btn-outline-primary').addClass('btn-outline-info');
                $row.find('.carton-qty').attr('placeholder', 'Gm');
                if (curQty > 0) {
                    $row.find('.carton-qty').val(Math.round(curQty * 1000));
                }
                if (curPrice > 0) {
                    let gmPrice = curPrice / 1000;
                    $priceInp.val(gmPrice.toFixed(4).replace(/\.?0+$/, ''));
                    $row.find('.price-per-piece').val($priceInp.val());
                }
            } else {
                currentMode = 'kg';
                $btn.text('Kg').removeClass('btn-outline-info').addClass('btn-outline-primary');
                $row.find('.carton-qty').attr('placeholder', 'Kg');
                if (curQty > 0) {
                    $row.find('.carton-qty').val(curQty / 1000);
                }
                if (curPrice > 0) {
                    let kgPrice = curPrice * 1000;
                    $priceInp.val(kgPrice % 1 === 0 ? kgPrice : kgPrice.toFixed(2));
                    $row.find('.price-per-piece').val($priceInp.val());
                }
            }
        } else if (sizeMode === 'by_feet' || sizeMode === 'by_meter') {
            if (currentMode === 'ft' || currentMode === 'm') {
                currentMode = 'in';
                $btn.text('In').removeClass('btn-outline-primary').addClass('btn-outline-warning');
                $row.find('.carton-qty').attr('placeholder', 'In');
            } else {
                currentMode = (sizeMode === 'by_feet') ? 'ft' : 'm';
                $btn.text(sizeMode === 'by_feet' ? 'Ft' : 'Mtr').removeClass('btn-outline-warning').addClass('btn-outline-primary');
                $row.find('.carton-qty').attr('placeholder', sizeMode === 'by_feet' ? 'Ft' : 'Mtr');
            }
        }

        $btn.attr('data-unit-mode', currentMode);
        $row.find('.hidden-sub-unit-mode').val(currentMode);
        computeRow($row);
        if (typeof updateGrandTotals === 'function') updateGrandTotals();
        if (typeof refreshPostedState === 'function') refreshPostedState();
    });

        // Warehouse change -> handled asynchronously via loadWarehousesForProduct()

        // Inputs -> Calc
        // --- Helper for Box Conversion ---
        window.normalizeQtyInput = function($input, $row) {
            const val = $input.val();
            const sizeMode = $row.data('size_mode');
            const ppb = parseFloat($row.find('.pack-qty').val()) || 1;

            if ((['by_cartons', 'by_bandal'].includes(sizeMode) || sizeMode === 'by_size') && ppb > 1 && val.includes('.')) {
                const parts = val.split('.');
                const boxes = parseInt(parts[0]) || 0;
                const looseStr = parts[1];

                if (looseStr && looseStr !== '') {
                    const loose = parseInt(looseStr);
                    if (loose >= ppb) {
                        const extraBoxes = Math.floor(loose / ppb);
                        const newLoose = loose % ppb;
                        const newBoxes = boxes + extraBoxes;

                        let newVal = newBoxes.toString();
                        if (newLoose > 0) {
                            newVal += '.' + newLoose;
                        } // re-run
                        $input.val(newVal);
                    }
                }
            }
        };

        // $(document).ready(function() {

        // ... existing bindings ...

        // Inputs -> Calc
        $(document).on('input', '.carton-qty, .loose-pcs-input, .pack-qty, .discount-value, .visible-price, .sales-tax-percent, .further-tax-percent, .bonus-qty',
            function() {
                // If user manually changes the visible price, also update the hidden price-per-piece and normalized base rate
                if ($(this).hasClass('visible-price')) {
                    const $row = $(this).closest('tr');
                    const newPrice = toNum($(this).val());
                    $row.find('.price-per-piece').val(newPrice);

                    const sizeMode = $row.data('size_mode') || $row.find('.size-mode-text').val();
                    const packQty = parseFloat($row.find('.pack-qty').val()) || 1;
                    const unitMode = $row.find('.qty-unit-toggle').attr('data-unit-mode') || 'main';
                    const rowMode = $row.find('.price-mode-row-toggle').attr('data-mode') || 'retail';

                    let baseRate = newPrice;
                    if (['by_cartons', 'by_bandal'].includes(sizeMode)) {
                        baseRate = (unitMode === 'pcs' || packQty <= 1) ? newPrice : (newPrice / packQty);
                    } else if (sizeMode === 'by_kg' || sizeMode === 'by_gm') {
                        baseRate = (unitMode === 'gm') ? (newPrice * 1000) : newPrice;
                    }

                    if (rowMode === 'wholesale') {
                        $row.find('.wholesale-price').val(baseRate);
                    } else {
                        $row.find('.retail-price').val(baseRate);
                    }
                }

                computeRow($(this).closest('tr'));
                updateGrandTotals();
                refreshPostedState();
            });

        $(document).on('input', '.discount-amount', function() {
            computeRow($(this).closest('tr'), true);
            updateGrandTotals();
            refreshPostedState();
        });

        // Size Display Input Sync
        $(document).on('input change', '.size-display', function() {
            const $row = $(this).closest('tr');
            const newSize = $(this).val().trim();
            let variantData = {};
            const existingVd = $row.find('.variant-data-hidden').val();
            if (existingVd) {
                try {
                    const decoded = atob(existingVd);
                    variantData = JSON.parse(decoded);
                } catch(e) {
                    try {
                        variantData = JSON.parse(existingVd);
                    } catch(e2) {
                        variantData = { color: $row.find('.color-display').val() || '-' };
                    }
                }
            } else {
                variantData = { color: $row.find('.color-display').val() || '-' };
            }
            if (typeof variantData !== 'object' || variantData === null) {
                variantData = { color: $row.find('.color-display').val() || '-' };
            }
            variantData.size = newSize;
            $row.find('.variant-data-hidden').val(btoa(JSON.stringify(variantData)));
        });

        // Delete Row
        $(document).on('click', '.del-row', function() {
            if ($('#salesTableBody tr').length > 1) {
                $(this).closest('tr').remove();
                updateRowIndexes();
                updateGrandTotals();
                refreshPostedState();
                refreshPcsCtnHeader();
            }
        });

        // Add Row Button
        $('#btnAdd').click(addNewRow);

        // --- ENTER KEY NAVIGATION & AUTOMATIC EXCEL NEW ROW OPENING ---
        $(document).on('keydown', '#salesTableBody input, #salesTableBody select, .select2-selection', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                const $target = $(this);

                // Allow default Select2 selection behavior when dropdown list is actively open
                if ($('.select2-container--open').length > 0 && ($target.hasClass('select2-search__field') || $target.hasClass('select2-selection'))) {
                    return; 
                }
                
                e.preventDefault();
                const $row = $target.closest('tr');
                computeRow($row);
                updateGrandTotals();
                
                // Find all visible, editable inputs/selects in the row
                const $rowInputs = $row.find('input:visible:not([readonly]):not([disabled]), select:visible:not([disabled])');
                const currentIndex = $rowInputs.index($target);
                
                if (currentIndex !== -1 && currentIndex < $rowInputs.length - 1) {
                    const $nextInput = $rowInputs.eq(currentIndex + 1);
                    $nextInput.focus();
                    if ($nextInput.is('input')) {
                        $nextInput.select();
                    }
                } else {
                    // At last editable cell of row (e.g. FT %) -> scroll table back to left & open new row if on last row, or jump to next row
                    const $allRows = $('#salesTableBody tr');
                    const isLastRow = $row.is($allRows.last());
                    const $tableWrap = $('#salesTableBody').closest('.pos-table-wrap, .table-responsive');

                    if ($tableWrap.length && $tableWrap.scrollLeft() > 20) {
                        $tableWrap.animate({ scrollLeft: 0 }, 120);
                    }
                    
                    if (isLastRow) {
                        addNewRow();
                        const $newRow = $('#salesTableBody tr:last-child');
                        setTimeout(function() {
                            const $nextProduct = $newRow.find('.product');
                            $nextProduct.focus();
                            if ($nextProduct.data('select2')) {
                                $nextProduct.select2('open');
                            }
                        }, 140);
                    } else {
                        const $nextRow = $row.next('tr');
                        const $nextRowProduct = $nextRow.find('.product');
                        if ($nextRowProduct.val()) {
                            $nextRow.find('.carton-qty').focus().select();
                        } else {
                            setTimeout(function() {
                                $nextRowProduct.focus();
                                if ($nextRowProduct.data('select2')) {
                                    $nextRowProduct.select2('open');
                                }
                            }, 100);
                        }
                    }
                }
            }
        });

        // Row Pricing Mode Toggle Handler
        $(document).on('click', '.price-mode-row-toggle', function() {
            const $btn = $(this);
            const $row = $btn.closest('tr');
            const currentMode = $btn.attr('data-mode') || 'retail';
            const newMode = currentMode === 'retail' ? 'wholesale' : 'retail';
            
            $btn.attr('data-mode', newMode);
            if (newMode === 'wholesale') {
                $btn.removeClass('btn-outline-success').addClass('btn-outline-info').text('W').attr('title', 'Wholesale Mode');
            } else {
                $btn.removeClass('btn-outline-info').addClass('btn-outline-success').text('R').attr('title', 'Retail Mode');
            }
            
            lastSelectedPriceMode = newMode;
            
            let retailPrice = parseFloat($row.find('.retail-price').val()) || 0;
            let wholesalePrice = parseFloat($row.find('.wholesale-price').val()) || 0;
            
            let pieceRate = (newMode === 'wholesale' && wholesalePrice > 0) ? wholesalePrice : retailPrice;
            const sizeMode = $row.data('size_mode') || $row.find('.size-mode-text').val();
            const packQty = parseFloat($row.find('.pack-qty').val()) || 1;
            const unitMode = $row.find('.qty-unit-toggle').attr('data-unit-mode') || 'ctn';

            if (['by_cartons', 'by_bandal'].includes(sizeMode)) {
                let cartonPrice = packQty > 1 ? (pieceRate * packQty) : pieceRate;
                let finalPrice = (unitMode === 'pcs') ? pieceRate : cartonPrice;
                $row.find('.visible-price').val(finalPrice % 1 === 0 ? finalPrice : finalPrice.toFixed(2));
                $row.find('.price-per-piece').val($row.find('.visible-price').val());
            } else if (sizeMode === 'by_kg' || sizeMode === 'by_gm') {
                if (unitMode === 'gm') {
                    $row.find('.visible-price').val((pieceRate / 1000).toFixed(4).replace(/\.?0+$/, ''));
                } else {
                    $row.find('.visible-price').val(pieceRate);
                }
                $row.find('.price-per-piece').val($row.find('.visible-price').val());
            } else {
                $row.find('.visible-price').val(pieceRate);
                $row.find('.price-per-piece').val(pieceRate);
            }
            
            computeRow($row);
            updateGrandTotals();
        });

        // Payment Head Quick Selection Buttons
        $(document).on('click', '.btn-pay-head', function() {
            $('.btn-pay-head').removeClass('btn-outline-primary active').addClass('btn-outline-secondary');
            $(this).removeClass('btn-outline-secondary').addClass('btn-outline-primary active');

            const head = $(this).data('head');
            const $firstAcc = $('#rvWrapper .rv-account').first();
            const $firstAmt = $('#rvWrapper .rv-amount').first();

            if ($firstAcc.length) {
                let matched = false;
                $firstAcc.find('option').each(function() {
                    const txt = $(this).text().toLowerCase();
                    if (txt.includes(head)) {
                        $firstAcc.val($(this).val()).trigger('change');
                        matched = true;
                        return false;
                    }
                });
                if (!matched) {
                    if (['bank', 'card', 'jazzcash', 'easypaisa'].includes(head)) {
                        $firstAcc.find('option').each(function() {
                            const txt = $(this).text().toLowerCase();
                            if (txt.includes('bank') || txt.includes('online') || txt.includes('meezan') || txt.includes('hbl') || txt.includes('ubl') || txt.includes('easypaisa') || txt.includes('jazz')) {
                                $firstAcc.val($(this).val()).trigger('change');
                                return false;
                            }
                        });
                    }
                }
            }

            if ($firstAmt.length) {
                const netVal = toNum($('#tSub').text()) || toNum($('#walkinNetTotal').text());
                if (netVal > 0) {
                    $firstAmt.val(netVal.toFixed(2));
                    if (typeof window.recomputeReceipts === 'function') window.recomputeReceipts();
                }
            }
        });

        // Discount Toggle: % <-> PKR
        $(document).on('click', '.discount-toggle', function() {
            const $btn = $(this);
            const currentType = $btn.data('type');
            const newType = currentType === 'percent' ? 'pkr' : 'percent';
            $btn.data('type', newType).text(newType === 'percent' ? '%' : 'PKR');
            // Sync hidden input so form submission carries correct type
            $btn.closest('.discount-wrapper').find('.discount-type-hidden').val(newType);
            computeRow($btn.closest('tr'));
            updateGrandTotals();
        });

        // Buttons: Booking (Save)
        $('#btnSave').off('click').on('click', function() {
            cleanupEmptyRows();
            updateGrandTotals();
            refreshPostedState();

            const v = validateFormAll();
            if (!v.ok) {
                showAlert('warning', v.message);
                if (v.el && v.el.length) {
                    v.el.focus();
                    if (v.el.hasClass('js-customer')) v.el.select2?.('open');
                }
                return;
            }
            $('#action').val('booking');
            ensureSaved();
        });

        // Buttons: Quotation (No stock / no ledger)
        $('#btnQuotation').off('click').on('click', function() {
            cleanupEmptyRows();
            updateGrandTotals();
            refreshPostedState();

            const v = validateFormAll();
            if (!v.ok) {
                showAlert('warning', v.message);
                if (v.el && v.el.length) {
                    v.el.focus();
                    if (v.el.hasClass('js-customer')) v.el.select2?.('open');
                }
                return;
            }
            $('#action').val('quotation');
            let curInv = ($('#inputInvoiceNo').val() || '').toUpperCase();
            if (!curInv.startsWith('QUOT-')) {
                $.ajax({
                    url: "{{ route('invoice_series.generate_no') }}",
                    type: "GET",
                    data: { prefix: 'QUOT' },
                    async: false,
                    success: function(res) {
                        if (res.invoice_no) {
                            $('#inputInvoiceNo').val(res.invoice_no);
                            if (typeof window.switchInvoicePrefix === 'function') {
                                window.switchInvoicePrefix('QUOT');
                            }
                        }
                    }
                });
            }
            ensureSaved().then(function() {
                Swal.fire({
                    title: 'Quotation Saved',
                    text: 'Quotation saved successfully (no stock or ledger impact)',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                setTimeout(() => window.location.href = "{{ route('sale.index') }}", 1500);
            });
        });

        // Buttons: Sale (Post)
        $('#btnPosted, #btnHeaderPosted').off('click').on('click', function() {
            $('#action').val('sale');
            cleanupEmptyRows();
            updateGrandTotals();
            refreshPostedState();

            const v = validateFormAll();
            if (!v.ok) {
                showAlert('warning', v.message);
                if (v.el && v.el.length) {
                    v.el.focus();
                    if (v.el.hasClass('js-customer')) v.el.select2?.('open');
                }
                return;
            }

            // Walk-in 100% Payment Validation Check
            const isWalkin = $('#is_walkin').val() === '1';
            const invoiceNet = toNum($('#totalBalance').val());
            const paidNow = toNum($('#receiptsTotal').text());

            if (isWalkin) {
                // For walk-in, they must pay 100% upfront (with a small floating point tolerance)
                if (paidNow < (invoiceNet - 0.05)) {
                    Swal.fire({
                        icon: 'error',
                        title: '100% Payment Required',
                        text: 'Walk-in customers do not have a ledger. You must receive full payment to post this sale.',
                        confirmButtonColor: '#d33'
                    });
                    return;
                }
            }

            // Credit Limit Check
            // Credit Limit Check
            const rangeBal = toNum($('#rangeBalance').val());
            if (rangeBal > 0) {
                const prevBal = toNum($('#previousBalance').val());

                // Invoice Net Amount (Subtotal - Extra Discount)
                const invoiceNet = toNum($('#totalBalance').val());
                // Amount Paid Now
                const paidNow = toNum($('#receiptsTotal').text());

                // Projected Balance: Previous + New Debt - Payment
                const projectedBalance = prevBal + invoiceNet - paidNow;

                // Validate
                if (projectedBalance > rangeBal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Credit Limit Exceeded',
                        html: `Projected Balance (<strong>${projectedBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>) exceeds Credit Limit (<strong>${rangeBal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>)`,
                        confirmButtonColor: '#d33'
                    });
                    return;
                }
            }

            if (!canPost()) {
                showAlert('warning', 'No valid item lines to post.');
                return;
            }

            ensureSaved().then(function(res) {
                Swal.fire({
                    title: 'Success!',
                    text: (res && res.msg) ? res.msg : 'Sale saved successfully',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                setTimeout(() => window.location.href = "{{ route('sale.index') }}", 1500);
            });
        });

        
        // Switch Payment Mode (Cash/Bank vs Cheque vs Both)
        window.switchPayMode = function(mode) {
            $('#payModeToggleGroup button').removeClass('active');
            if (mode === 'accounts') {
                $('#btnModeAccounts').addClass('active');
                $('#panel_accounts').slideDown(200);
                $('#panel_cheque').slideUp(200);
                $('#sale_cheque_amount').val('');
                $('#sale_cheque_no').val('');
                $('#sale_cheque_bank').val('');
            } else if (mode === 'cheque') {
                $('#btnModeCheque').addClass('active');
                $('#panel_accounts').slideUp(200);
                $('#panel_cheque').slideDown(200);
                $('#panel_accounts .rv-amount').val('');
            } else if (mode === 'both') {
                $('#btnModeBoth').addClass('active');
                $('#panel_accounts').slideDown(200);
                $('#panel_cheque').slideDown(200);
            }
            if (typeof window.recomputeReceipts === 'function') {
                window.recomputeReceipts();
            }
        };

        // Receipts Logic
        $(document).on('input', '.rv-amount', function() {
            if (typeof window.recomputeReceipts === 'function') window.recomputeReceipts();
        });



        $('#btnAddRV').on('click', function() {
            const row = `
              <div class="d-flex gap-2 align-items-center mb-2 rv-row">
                <select class="form-select rv-account" name="receipt_account_id[]" style="max-width:320px">
                  <option value="">Select account</option>
                </select>
                <input type="text" class="form-control text-end rv-amount" name="receipt_amount[]" placeholder="0.00" style="max-width:160px">
                <button type="button" class="btn btn-outline-danger btn-sm btnRemRV">&times;</button>
              </div>`;
            $('#rvWrapper').append(row);
            loadAccountsInto($('#rvWrapper .rv-account:last'));
        });

        $(document).on('click', '.btnRemRV', function() {
            $(this).closest('.rv-row').remove();
            if (typeof window.recomputeReceipts === 'function') window.recomputeReceipts();
        });

        // Sync Change Account Dropdowns
        $(document).on('change', '#changeAccountId', function() {
            const val = $(this).val();
            $('#bottomChangeAccountId, #editChangeAccountId').val(val);
        });
        $(document).on('change', '#bottomChangeAccountId', function() {
            const val = $(this).val();
            $('#changeAccountId, #editChangeAccountId').val(val);
        });
        $(document).on('change', '#editChangeAccountId', function() {
            const val = $(this).val();
            $('#changeAccountId, #bottomChangeAccountId').val(val);
        });

        // Toggle Customer Info Modal
        function populateCustomerModalDetails(customObj) {
            let code = $('#ci_code').text().trim();
            let name = $('#ci_name').text().trim();
            let mobile = $('#ci_mobile').text().trim();
            let address = $('#ci_address').text().trim();
            let range = parseFloat($('#rangeBalance').val()) || 0;
            
            // Fallback to selected option in customerSelect if ci_ fields are blank
            const $selectedOpt = $('#customerSelect option:selected');
            if ($selectedOpt.length && $selectedOpt.val()) {
                if (!code || code === '—') code = $selectedOpt.attr('data-code') || '';
                if (!name || name === '—') name = $selectedOpt.text().trim();
                if (!mobile || mobile === '—') mobile = $selectedOpt.attr('data-mobile') || '';
                if (!address || address === '—') address = $selectedOpt.attr('data-address') || '';
                if (!range) range = parseFloat($selectedOpt.attr('data-range') || 0);
            }

            if (customObj) {
                if (customObj.customer_id) code = customObj.customer_id;
                if (customObj.customer_name) name = customObj.customer_name;
                if (customObj.mobile) mobile = customObj.mobile;
                if (customObj.address) address = customObj.address;
                if (customObj.balance_range !== undefined) range = parseFloat(customObj.balance_range || 0);
            }

            const prevVal = $('#cc_prev_bal_val').text().trim();
            const prevSuf = $('#cc_prev_bal_suffix').text().trim();
            const closingVal = $('#cc_closing_bal_val').text().trim();
            const closingSuf = $('#cc_closing_bal_suffix').text().trim();

            $('#ci_modal_code').text(code || '—');
            $('#ci_modal_name').text(name || '—');
            $('#ci_modal_mobile').text(mobile || '—');
            $('#ci_modal_address').text(address || '—');
            $('#ci_modal_credit_limit').text(range > 0 ? ('Rs ' + range.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2})) : 'Unlimited');
            $('#ci_modal_prev').html((prevVal || 'Rs 0') + (prevSuf ? ' <small>' + prevSuf + '</small>' : ''));
            $('#ci_modal_closing').html((closingVal || 'Rs 0') + (closingSuf ? ' <small>' + closingSuf + '</small>' : ''));
        }
        window.populateCustomerModalDetails = populateCustomerModalDetails;

        window.updateCustomerDisplayCards = function(data) {
            if (!data) {
                $('#customerQuickInfoContainer').addClass('d-none');
                return;
            }
            const addr = data.address ? data.address.trim() : '';
            const range = parseFloat(data.credit_limit !== undefined ? data.credit_limit : (data.balance_range !== undefined ? data.balance_range : ($('#rangeBalance').val() || 0)));

            if (addr) {
                $('#cqi_address_text').text(addr).attr('title', addr);
                $('#cqi_address_badge').removeClass('d-none');
            } else {
                $('#cqi_address_badge').addClass('d-none');
            }

            if (range > 0) {
                $('#cqi_credit_text').text('Rs ' + range.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2}));
            } else {
                $('#cqi_credit_text').text('Unlimited');
            }
            $('#cqi_credit_badge').removeClass('d-none');

            $('#customerQuickInfoContainer').removeClass('d-none');
        };

        $(document).on('click', '#btnToggleCustomerInfo', function(e) {
            populateCustomerModalDetails();
            const modalEl = document.getElementById('modalCustomerDetails');
            if (modalEl) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    bsModal.show();
                } else if ($.fn.modal) {
                    $('#modalCustomerDetails').modal('show');
                } else {
                    $(modalEl).addClass('show').css('display', 'block').removeAttr('aria-hidden');
                }
            }
        });

        $(document).on('show.bs.modal show.bs.modal', '#modalCustomerDetails', function() {
            populateCustomerModalDetails();
        });

        $(document).on('click', '#modalCustomerDetails [data-dismiss="modal"], #modalCustomerDetails [data-bs-dismiss="modal"]', function() {
            const modalEl = document.getElementById('modalCustomerDetails');
            if (modalEl) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const bsModal = bootstrap.Modal.getInstance(modalEl);
                    if (bsModal) bsModal.hide();
                    else $(modalEl).removeClass('show').css('display', 'none');
                } else if ($.fn.modal) {
                    $('#modalCustomerDetails').modal('hide');
                } else {
                    $(modalEl).removeClass('show').css('display', 'none');
                }
            }
        });


        // --- Customers & Accounts ---
        const accountData = {!! isset($accounts) ? json_encode($accounts) : '[]' !!};

        function loadAccountsInto($select, customerId) {
            const currentVal = $select.val();
            let options = '<option value="">Select account</option>';
            accountData.forEach(acc => {
                options += `<option value="${acc.id}">${acc.title}</option>`;
            });
            $select.html(options);
            if (currentVal) $select.val(currentVal);
        }

        window.recomputeReceipts = function() {
            let sum = 0;
            $('.rv-amount').each(function() {
                const amt = toNum($(this).val());
                sum += amt;
                const $card = $(this).closest('.pay-account-card');
                if ($card.length) {
                    if (amt > 0) {
                        $card.css({
                            'border-color': '#2563eb',
                            'background-color': '#eff6ff'
                        }).addClass('border-primary bg-primary-soft');
                    } else {
                        $card.css({
                            'border-color': '#e2e8f0',
                            'background-color': '#ffffff'
                        }).removeClass('border-primary bg-primary-soft');
                    }
                }
            });
            $('#receiptsTotal').text(sum.toFixed(2));
            if ($('#footerPaid').length) $('#footerPaid').text(sum.toFixed(2));
            if (typeof updateGrandTotals === 'function') updateGrandTotals();
        }

        $(function() {
            if (typeof window.recomputeReceipts === 'function') {
                window.recomputeReceipts();
            }
        });

        // --- Sidebar Direct Add Product ---
        $(document).on('click', '.add-product-direct-btn', function() {
            const prodId = $(this).data('id');
            if (!prodId) return;

            let $targetRow = $('#salesTableBody tr:last');
            const lastProdVal = $targetRow.find('.product').val();

            if (!$targetRow.length || lastProdVal) {
                addNewRow();
                $targetRow = $('#salesTableBody tr:last');
            }

            const $select = $targetRow.find('.product');
            const prodName = $(this).closest('.pos-product-card').find('.pos-product-name').text();
            if ($select.find(`option[value="${prodId}"]`).length === 0) {
                $select.append(new Option(prodName, prodId, true, true));
            } else {
                $select.val(prodId).trigger('change.select2');
            }
            $select.trigger('change');
        });

        // --- Sidebar Live Search ---
        $('#sidebarProductSearch').on('input', function() {
            const q = $(this).val().trim();
            if (q.length < 1) return;
            $.get('{{ route('search-product-name') }}', { q: q }).done(function(res) {
                let html = '';
                if (res && res.length > 0) {
                    res.forEach(p => {
                        const stockText = p.wh_stock ? `${p.wh_stock} Pcs` : '0 Pcs';
                        const priceText = p.retail_price ? parseFloat(p.retail_price).toFixed(2) : '0.00';
                        html += `
                            <div class="pos-product-card">
                                <div class="pos-product-img">
                                    <i class="fas fa-box text-secondary fs-5"></i>
                                </div>
                                <div class="pos-product-info">
                                    <div class="pos-product-name" title="${p.item_name}">${p.item_name}</div>
                                    <div class="pos-product-sub">
                                        <span class="badge-stock-green">${stockText}</span> SKU: ${p.item_code || '-'}
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="pos-product-price">${priceText}<br><small class="text-muted fs-7">PCS</small></div>
                                    <button type="button" class="pos-product-add-btn add-product-direct-btn" data-id="${p.id}" title="Add to Grid"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>`;
                    });
                } else {
                    html = '<div class="text-center text-muted py-3" style="font-size:0.75rem;">No products found</div>';
                }
                $('#sidebarProductContainer').html(html);
            });
        });

        // --- Save & Complete (F9) Handler ---
        $('#btnSaveAndComplete, #btnHeaderSaveSale').on('click', function() {
            $('#btnPosted').trigger('click');
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'F9') {
                e.preventDefault();
                $('#btnSaveAndComplete').trigger('click');
            }
        });

        // Failsafe trigger for Quick Add Product Modal
        $(document).on('click', '[data-bs-target="#quickAddProductModal"], [data-target="#quickAddProductModal"]', function(e) {
            e.preventDefault();
            $('#quickAddProductModal').modal('show');
        });

        // Initialize Posted Button State
        refreshPostedState();

        // --- Product Tracking (Batch / Serial IMEI Modals) ---
        window.checkAndOpenProductTracking = function($row, productId, warehouseId) {
            if (!productId) return;
            warehouseId = warehouseId || $row.find('.warehouse').val() || 1;

            // Check Batches first
            $.get('{{ route("sale.get_batches") }}', { product_id: productId, warehouse_id: warehouseId }).done(function(res) {
                if (res.success && res.is_batch_product) {
                    openBatchSelectModal($row, res.batches || []);
                    return;
                }

                // If not batch product, check Serials / IMEIs
                $.get('{{ route("sale.get_serials") }}', { product_id: productId, warehouse_id: warehouseId }).done(function(sRes) {
                    if (sRes.success && sRes.is_serial_product) {
                        openSerialSelectModal($row, sRes.serials || [], sRes);
                    }
                });
            });
        };

        // Open Batch Modal
        function openBatchSelectModal($row, batches) {
            const rowIndex = $('#salesTableBody tr').index($row);
            $('#activeBatchRowIndex').val(rowIndex);

            let html = '';
            if (!batches || batches.length === 0) {
                html = '<tr><td colspan="5" class="text-muted py-3"><i class="fas fa-info-circle me-1"></i> No registered batches found for this product. You can enter a custom batch number below.</td></tr>';
            } else {
                batches.forEach(b => {
                    const expDate = b.expiry_date ? b.expiry_date : 'N/A';
                    const mfgDate = b.mfg_date ? b.mfg_date : 'N/A';
                    
                    let isExpired = false;
                    if (b.expiry_date) {
                        const today = new Date().toISOString().split('T')[0];
                        if (b.expiry_date <= today) isExpired = true;
                    }

                    const expBadge = isExpired ? '<span class="badge bg-danger">Expired</span>' : `<span class="badge bg-light text-dark border">${expDate}</span>`;

                    const whName = b.warehouse ? (b.warehouse.warehouse_name || b.warehouse.name) : (b.warehouse_name || "");
                    const whBadge = whName ? `<span class="badge bg-light text-dark border me-1"><i class="fas fa-warehouse text-primary me-1"></i>${whName}</span>` : "";

                    const bQty = parseFloat(b.qty || 0);
                    const qtyBadge = bQty > 0 
                        ? `<span class="badge bg-success font-monospace fs-6">${bQty}</span>` 
                        : `<span class="badge bg-secondary font-monospace fs-6">0</span>`;

                    html += `
                        <tr>
                            <td class="fw-bold text-primary font-monospace">${b.batch_no} ${whBadge}</td>
                            <td class="small">${mfgDate}</td>
                            <td>${expBadge}</td>
                            <td>${qtyBadge}</td>
                            <td>
                                <button type="button" class="btn btn-sm ${bQty > 0 ? 'btn-primary' : 'btn-outline-primary'} btn-choose-batch py-1 px-3 fw-bold"
                                        data-batch-id="${b.id}" data-batch-no="${b.batch_no}" data-batch-qty="${b.qty}" data-warehouse-id="${b.warehouse_id || ""}">
                                    <i class="fas fa-check me-1"></i> Select Batch
                                </button>
                            </td>
                        </tr>`;
                });
            }

            $('#batchModalTableBody').html(html);
            $('#modalSelectBatch').modal('show');
        }

        // Custom Batch Apply Button Click inside Modal
        $(document).on('click', '#btnApplyCustomBatch', function() {
            const batchNo = ($('#inputCustomBatchNo').val() || '').trim();
            if (!batchNo) {
                alert('Please enter a batch number.');
                return;
            }
            const rowIndex = $('#activeBatchRowIndex').val();
            const $row = $('#salesTableBody tr').eq(rowIndex);

            if ($row.length) {
                $row.find('.batch-id-hidden').val('');
                $row.find('.batch-no-hidden').val(batchNo);

                let badgeHtml = `<span class="badge bg-info text-dark" style="font-size:0.7rem;"><i class="fas fa-layer-group me-1"></i>Batch: ${batchNo}</span>`;
                $row.find('.row-tracking-badges').html(badgeHtml);

                $('#modalSelectBatch').modal('hide');
                $('#inputCustomBatchNo').val('');
                $row.find('.carton-qty').focus().select();
            }
        });

        // Select Batch Button Click inside Modal
        $(document).on('click', '.btn-choose-batch', function() {
            const batchId = $(this).data('batch-id');
            const batchNo = $(this).data('batch-no');
            const rowIndex = $('#activeBatchRowIndex').val();
            const $row = $('#salesTableBody tr').eq(rowIndex);

            if ($row.length) {
                $row.find('.batch-id-hidden').val(batchId);
                $row.find('.batch-no-hidden').val(batchNo);

                const whId = $(this).data('warehouse-id');
                if (whId) {
                    $row.find('.warehouse').val(whId);
                }

                // Update tracking badges display on row
                let badgeHtml = `<span class="badge bg-info text-dark" style="font-size:0.7rem;"><i class="fas fa-layer-group me-1"></i>Batch: ${batchNo}</span>`;
                $row.find('.row-tracking-badges').html(badgeHtml);

                $('#modalSelectBatch').modal('hide');
                $row.find('.carton-qty').focus().select();
            }
        });

        // Open Serial / IMEI Modal
        let currentAvailableSerials = [];
        let currentSerialStockInfo = {};
        function openSerialSelectModal($row, serials, stockInfo = {}) {
            const rowIndex = $('#salesTableBody tr').index($row);
            $('#activeSerialRowIndex').val(rowIndex);
            currentAvailableSerials = serials || [];
            currentSerialStockInfo = stockInfo || {};

            const totalStock = parseFloat(stockInfo.total_stock || 0);
            const serialCount = (serials || []).length;
            const nonSerialStock = Math.max(0, parseFloat(stockInfo.non_serial_stock !== undefined ? stockInfo.non_serial_stock : (totalStock - serialCount)));

            $('#modalTotalStockCount').text(totalStock);
            $('#modalAvailableSerialsCount').text(serialCount);
            $('#modalNonSerialStockCount').text(nonSerialStock);
            $('#modalNonSerialStockHint').text(nonSerialStock);

            // Pre-selected serials if any
            let existingSelected = [];
            try {
                existingSelected = JSON.parse($row.find('.serials-hidden').val() || '[]');
            } catch(e) {}

            renderSerialList(currentAvailableSerials, existingSelected);
            $('#modalSelectSerial').modal('show');
        }

        // Choose to Sell Without Serial
        $(document).on('click', '#btnChooseWithoutSerial', function() {
            const rowIndex = $('#activeSerialRowIndex').val();
            const $row = $('#salesTableBody tr').eq(rowIndex);

            if ($row.length) {
                $row.find('.serials-hidden').val('[]');
                let badgeHtml = `<span class="badge bg-warning text-dark border" style="font-size:0.7rem;"><i class="fas fa-box-open me-1"></i>Without Serial</span>`;
                $row.find('.row-tracking-badges').html(badgeHtml);

                $('#modalSelectSerial').modal('hide');
                $row.find('.carton-qty').focus().select();
            }
        });

        function renderSerialList(serials, selectedList = []) {
            let html = '';
            if (!serials || serials.length === 0) {
                html = '<div class="text-center text-muted py-3">No available IMEIs / Serial numbers found for this product in selected warehouse.</div>';
            } else {
                serials.forEach(s => {
                    const isChecked = selectedList.includes(s.serial_number) ? 'checked' : '';
                    const whName = s.warehouse ? (s.warehouse.warehouse_name || s.warehouse.name) : (s.warehouse_name || '');
                    const whBadge = whName ? `<span class="badge bg-light text-dark border me-2"><i class="fas fa-warehouse me-1 text-primary"></i>${whName}</span>` : '';
                    html += `
                        <div class="d-flex align-items-center justify-content-between p-2 border-bottom serial-item-row" style="font-size:0.85rem; cursor: pointer; user-select: none;">
                            <div class="form-check mb-0 flex-grow-1 d-flex align-items-center">
                                <input class="form-check-input serial-chk me-2" type="checkbox" value="${s.serial_number}" data-warehouse-id="${s.warehouse_id || ''}" id="chk_s_${s.id}" ${isChecked}>
                                <label class="form-check-label fw-bold text-dark font-monospace" for="chk_s_${s.id}" style="cursor: pointer;">
                                    ${s.serial_number}
                                </label>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                ${whBadge}
                                <span class="badge bg-success font-monospace">Available</span>
                            </div>
                        </div>`;
                });
            }
            $('#serialListContainer').html(html);
            updateSelectedSerialCount();
        }

        // Click anywhere on serial item row to toggle checkbox
        $(document).on('click', '.serial-item-row', function(e) {
            if ($(e.target).is('input[type="checkbox"]') || $(e.target).is('label')) {
                return; // Native checkbox and label handling
            }
            const $chk = $(this).find('.serial-chk');
            $chk.prop('checked', !$chk.prop('checked')).trigger('change');
        });

        // Live Search/Scan Serial Numbers
        $(document).on('input', '#serialSearchInput', function() {
            const q = $(this).val().trim().toLowerCase();
            let existingSelected = [];
            $('.serial-chk:checked').each(function() { existingSelected.push($(this).val()); });

            const filtered = currentAvailableSerials.filter(s => s.serial_number.toLowerCase().includes(q));
            renderSerialList(filtered, existingSelected);

            // Auto-check if exact barcode scan match
            if (q.length > 4) {
                const exact = currentAvailableSerials.find(s => s.serial_number.toLowerCase() === q);
                if (exact) {
                    $(`#chk_s_${exact.id}`).prop('checked', true).trigger('change');
                    $('#serialSearchInput').val('').focus();
                }
            }
        });

        $(document).on('change', '.serial-chk', function() {
            updateSelectedSerialCount();
        });

        function updateSelectedSerialCount() {
            const count = $('.serial-chk:checked').length;
            $('#selectedSerialCount').text(count);
        }

        // Apply Selected Serials Button
        $(document).on('click', '#btnApplySelectedSerials', function() {
            const rowIndex = $('#activeSerialRowIndex').val();
            const $row = $('#salesTableBody tr').eq(rowIndex);

            let selectedSerials = [];
            $('.serial-chk:checked').each(function() {
                selectedSerials.push($(this).val());
            });

            if ($row.length) {
                $row.find('.serials-hidden').val(JSON.stringify(selectedSerials));

                // Auto update qty in row based on selected IMEIs count
                if (selectedSerials.length > 0) {
                    $row.find('.carton-qty').val(selectedSerials.length);
                    computeRow($row);
                    updateGrandTotals();

                    const firstWh = $('.serial-chk:checked').first().data('warehouse-id');
                    if (firstWh) {
                        $row.find('.warehouse').val(firstWh);
                    }

                    let badgeHtml = `<span class="badge bg-secondary text-light" style="font-size:0.7rem;" title="${selectedSerials.join(', ')}"><i class="fas fa-barcode me-1"></i>${selectedSerials.length} IMEI(s)</span>`;
                    $row.find('.row-tracking-badges').html(badgeHtml);
                } else {
                    $row.find('.row-tracking-badges').html('');
                }

                $('#modalSelectSerial').modal('hide');
            }
        });

        // Re-open Batch or Serial Modal on clicking tracking badge
        $(document).on('click', '.row-tracking-badges', function() {
            const $row = $(this).closest('tr');
            const pid = $row.find('.product-id-hidden').val() || $row.find('.product').val();
            if (pid) {
                checkAndOpenProductTracking($row, pid);
            }
        });
        // Universal modal dismiss handler (ensures close & cancel buttons always work in Bootstrap 4 / 5)
        $(document).on('click', '[data-dismiss="modal"], [data-bs-dismiss="modal"], .modal .close, .modal .btn-close', function(e) {
            const $modal = $(this).closest('.modal');
            if ($modal.length) {
                $modal.modal('hide');
            }
        });
    }); // Close $(document).ready
</script>
