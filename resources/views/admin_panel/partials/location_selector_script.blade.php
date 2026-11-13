@php
    $locationService = app(\App\Services\LocationService::class);
    $locationHierarchy = $locationService->getHierarchy();
    $allProvinces = $locationService->getProvinces();
    $allCities = $locationService->getCities();
    $allAreas = $locationService->getAreas();
    $allZones = \App\Models\Zone::orderBy('zone')->get();
@endphp

{{-- Datalists for fallback autocomplete --}}
<datalist id="provinceList">
    @foreach($allProvinces as $p)
        <option value="{{ $p }}"></option>
    @endforeach
</datalist>

<datalist id="cityList">
    @foreach($allCities as $c)
        <option value="{{ $c }}"></option>
    @endforeach
</datalist>

<datalist id="areaList">
    @foreach($allAreas as $a)
        <option value="{{ $a }}"></option>
    @endforeach
</datalist>

{{-- QUICK ADD PROVINCE MODAL --}}
<div class="modal fade" id="quickAddProvinceModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white py-2.5 px-3" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                <h6 class="modal-title fw-bold mb-0 text-white" style="font-size: 0.95rem;">
                    <i class="fas fa-map me-1.5"></i> Add New Province
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <label class="form-label small fw-bold text-dark mb-1">Province Name <span class="text-danger">*</span></label>
                    <input type="text" id="quick_province_name" class="form-control" placeholder="Enter Province Name (e.g. Sindh, Punjab...)" style="height: 38px; border-radius: 8px; border: 1.5px solid #cbd5e1;">
                    <small class="text-muted" style="font-size: 0.72rem;">Dynamic province will instantly appear across all forms and reports.</small>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" data-dismiss="modal" style="border-radius: 6px;">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary fw-bold px-3" id="btnSaveQuickProvince" style="border-radius: 6px;">
                    <i class="fas fa-check me-1"></i> Save Province
                </button>
            </div>
        </div>
    </div>
</div>

{{-- QUICK ADD CITY MODAL (100% Dropdown for Province) --}}
<div class="modal fade" id="quickAddCityModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white py-2.5 px-3" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                <h6 class="modal-title fw-bold mb-0 text-white" style="font-size: 0.95rem;">
                    <i class="fas fa-city me-1.5"></i> Add New City
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark mb-1">City Name <span class="text-danger">*</span></label>
                    <input type="text" id="quick_city_name" class="form-control" placeholder="Enter City Name (e.g. Vehari, Mirpurkhas...)" style="height: 38px; border-radius: 8px; border: 1.5px solid #cbd5e1;">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold text-dark mb-1">Province <span class="text-danger">*</span></label>
                    <select id="quick_city_province" class="form-select form-control" style="height: 38px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 0.88rem;">
                        <option value="">-- Select Province --</option>
                        @foreach($allProvinces as $p)
                            <option value="{{ $p }}">{{ $p }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted" style="font-size: 0.72rem;">Province dropdown helps group cities &amp; filter reports.</small>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" data-dismiss="modal" style="border-radius: 6px;">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary fw-bold px-3" id="btnSaveQuickCity" style="border-radius: 6px;">
                    <i class="fas fa-check me-1"></i> Save City
                </button>
            </div>
        </div>
    </div>
</div>

{{-- QUICK ADD AREA MODAL (100% Dropdowns for City & Province) --}}
<div class="modal fade" id="quickAddAreaModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white py-2.5 px-3" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                <h6 class="modal-title fw-bold mb-0 text-white" style="font-size: 0.95rem;">
                    <i class="fas fa-map-pin me-1.5"></i> Add New Area / Town
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark mb-1">Area / Town Name <span class="text-danger">*</span></label>
                    <input type="text" id="quick_area_name" class="form-control" placeholder="Enter Area / Sector (e.g. Model Colony, Block 4...)" style="height: 38px; border-radius: 8px; border: 1.5px solid #cbd5e1;">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark mb-1">Province (Optional)</label>
                    <select id="quick_area_province" class="form-select form-control" style="height: 38px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 0.88rem;">
                        <option value="">-- All Provinces --</option>
                        @foreach($allProvinces as $p)
                            <option value="{{ $p }}">{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold text-dark mb-1">City <span class="text-danger">*</span></label>
                    <select id="quick_area_city" class="form-select form-control" style="height: 38px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 0.88rem;">
                        <option value="">-- Select City --</option>
                        @foreach($allCities as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" data-dismiss="modal" style="border-radius: 6px;">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary fw-bold px-3" id="btnSaveQuickArea" style="border-radius: 6px;">
                    <i class="fas fa-check me-1"></i> Save Area
                </button>
            </div>
        </div>
    </div>
</div>

{{-- QUICK ADD ZONE MODAL --}}
<div class="modal fade" id="quickAddZoneModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white py-2.5 px-3" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                <h6 class="modal-title fw-bold mb-0 text-white" style="font-size: 0.95rem;">
                    <i class="fas fa-map-marked-alt me-1.5"></i> Add New Zone / Region
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <label class="form-label small fw-bold text-dark mb-1">Zone / Region Name <span class="text-danger">*</span></label>
                    <input type="text" id="quick_zone_name" class="form-control" placeholder="Enter Zone / Region (e.g. North Zone, Central...)" style="height: 38px; border-radius: 8px; border: 1.5px solid #cbd5e1;">
                    <small class="text-muted" style="font-size: 0.72rem;">Zones help group customers and vendors by geographic territory.</small>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" data-dismiss="modal" style="border-radius: 6px;">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary fw-bold px-3" id="btnSaveQuickZone" style="border-radius: 6px;">
                    <i class="fas fa-check me-1"></i> Save Zone
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.locationHierarchy = @json($locationHierarchy);

    function showQuickModal(modalId) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            try {
                var el = document.getElementById(modalId);
                var modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                modal.show();
                return;
            } catch(e) {}
        }
        if ($.fn.modal) {
            $('#' + modalId).modal('show');
        }
    }

    function hideQuickModal(modalId) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            try {
                var el = document.getElementById(modalId);
                var modal = bootstrap.Modal.getInstance(el);
                if (modal) { modal.hide(); return; }
            } catch(e) {}
        }
        if ($.fn.modal) {
            $('#' + modalId).modal('hide');
        }
    }

    function showQuickNotice(msg, icon) {
        icon = icon || 'success';
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icon,
                title: msg,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        } else {
            alert(msg);
        }
    }

    /**
     * Attach Cascading behavior to any Province, City, Area dropdowns / inputs
     */
    function setupLocationCascade(config) {
        const provEl = $(config.province);
        const cityEl = $(config.city);
        const areaEl = $(config.area);
        const custEl = config.customer ? $(config.customer) : null;
        const vendEl = config.vendor ? $(config.vendor) : null;

        // 1. Initial City Dropdown population if select element
        if (cityEl.is('select') && cityEl.find('option').length <= 1) {
            let cities = [];
            for (let p in window.locationHierarchy) {
                for (let c in window.locationHierarchy[p]) {
                    if (!cities.includes(c)) cities.push(c);
                }
            }
            cities.sort().forEach(c => {
                cityEl.append(new Option(c, c));
            });
        }

        // 2. Initial Area Dropdown population if select element
        if (areaEl.is('select') && areaEl.find('option').length <= 1) {
            let areas = [];
            for (let p in window.locationHierarchy) {
                for (let c in window.locationHierarchy[p]) {
                    window.locationHierarchy[p][c].forEach(a => {
                        if (!areas.includes(a)) areas.push(a);
                    });
                }
            }
            areas.sort().forEach(a => {
                areaEl.append(new Option(a, a));
            });
        }

        // Store original customer/vendor options
        if (custEl && custEl.length && !custEl.data('all-options')) {
            let options = [];
            custEl.find('option').each(function() {
                options.push({
                    val: $(this).val(),
                    text: $(this).text(),
                    province: $(this).data('province') || '',
                    city: $(this).data('city') || '',
                    area: $(this).data('area') || ''
                });
            });
            custEl.data('all-options', options);
        }

        if (vendEl && vendEl.length && !vendEl.data('all-options')) {
            let options = [];
            vendEl.find('option').each(function() {
                options.push({
                    val: $(this).val(),
                    text: $(this).text(),
                    province: $(this).data('province') || '',
                    city: $(this).data('city') || '',
                    area: $(this).data('area') || ''
                });
            });
            vendEl.data('all-options', options);
        }

        function filterParties() {
            const pVal = (provEl.val() || '').trim().toLowerCase();
            const cVal = (cityEl.val() || '').trim().toLowerCase();
            const aVal = (areaEl.val() || '').trim().toLowerCase();

            // Filter Customer Select
            if (custEl && custEl.length) {
                const allCusts = custEl.data('all-options') || [];
                const currentSelected = custEl.val();
                
                custEl.empty();
                allCusts.forEach(opt => {
                    if (opt.val === '' || opt.val === 'all') {
                        custEl.append(new Option(opt.text, opt.val));
                        return;
                    }
                    const cProv = (opt.province || '').toLowerCase();
                    const cCity = (opt.city || '').toLowerCase();
                    const cArea = (opt.area || '').toLowerCase();

                    let match = true;
                    if (pVal && pVal !== 'all' && cProv && cProv !== pVal) match = false;
                    if (cVal && cVal !== 'all' && cCity && cCity !== cVal) match = false;
                    if (aVal && aVal !== 'all' && cArea && cArea !== aVal) match = false;

                    if (match) {
                        let newOpt = new Option(opt.text, opt.val);
                        $(newOpt).data('province', opt.province).data('city', opt.city).data('area', opt.area);
                        custEl.append(newOpt);
                    }
                });

                if (currentSelected && custEl.find(`option[value="${currentSelected}"]`).length) {
                    custEl.val(currentSelected);
                }
                if (custEl.hasClass('select2-hidden-accessible')) {
                    custEl.trigger('change.select2');
                }
            }

            // Filter Vendor Select
            if (vendEl && vendEl.length) {
                const allVends = vendEl.data('all-options') || [];
                const currentSelected = vendEl.val();

                vendEl.empty();
                allVends.forEach(opt => {
                    if (opt.val === '' || opt.val === 'all') {
                        vendEl.append(new Option(opt.text, opt.val));
                        return;
                    }
                    const vProv = (opt.province || '').toLowerCase();
                    const vCity = (opt.city || '').toLowerCase();
                    const vArea = (opt.area || '').toLowerCase();

                    let match = true;
                    if (pVal && pVal !== 'all' && vProv && vProv !== pVal) match = false;
                    if (cVal && cVal !== 'all' && vCity && vCity !== cVal) match = false;
                    if (aVal && aVal !== 'all' && vArea && vArea !== aVal) match = false;

                    if (match) {
                        let newOpt = new Option(opt.text, opt.val);
                        $(newOpt).data('province', opt.province).data('city', opt.city).data('area', opt.area);
                        vendEl.append(newOpt);
                    }
                });

                if (currentSelected && vendEl.find(`option[value="${currentSelected}"]`).length) {
                    vendEl.val(currentSelected);
                }
                if (vendEl.hasClass('select2-hidden-accessible')) {
                    vendEl.trigger('change.select2');
                }
            }
        }

        // When Province changes
        provEl.on('change', function() {
            const selectedProv = ($(this).val() || '').trim();
            const currCity = cityEl.val();

            if (cityEl.is('select')) {
                cityEl.empty();
                cityEl.append(new Option(cityEl.hasClass('form-select-sm') ? 'All Cities' : '-- Select City --', ''));

                let cities = [];
                if (selectedProv && selectedProv !== 'all' && window.locationHierarchy[selectedProv]) {
                    cities = Object.keys(window.locationHierarchy[selectedProv]);
                } else {
                    for (let p in window.locationHierarchy) {
                        for (let c in window.locationHierarchy[p]) {
                            if (!cities.includes(c)) cities.push(c);
                        }
                    }
                }
                cities.sort().forEach(c => {
                    cityEl.append(new Option(c, c));
                });

                if (currCity && cityEl.find(`option[value="${currCity}"]`).length) {
                    cityEl.val(currCity);
                } else {
                    cityEl.val('');
                }
                cityEl.trigger('change');
            }

            filterParties();
        });

        // When City changes
        cityEl.on('change', function() {
            const selectedCity = ($(this).val() || '').trim();
            const currArea = areaEl.val();

            // Auto-select Province in province dropdown if not selected
            if (selectedCity && (!provEl.val() || provEl.val() === 'all')) {
                for (let p in window.locationHierarchy) {
                    for (let c in window.locationHierarchy[p]) {
                        if (c.toLowerCase() === selectedCity.toLowerCase()) {
                            provEl.val(p);
                            break;
                        }
                    }
                    if (provEl.val() && provEl.val() !== 'all') break;
                }
            }

            // Update Areas dropdown
            if (areaEl.is('select')) {
                areaEl.empty();
                areaEl.append(new Option(areaEl.hasClass('form-select-sm') ? 'All Areas' : '-- Select Area --', ''));

                let areas = [];
                for (let p in window.locationHierarchy) {
                    for (let c in window.locationHierarchy[p]) {
                        if (!selectedCity || selectedCity === 'all' || c.toLowerCase() === selectedCity.toLowerCase()) {
                            window.locationHierarchy[p][c].forEach(a => {
                                if (!areas.includes(a)) areas.push(a);
                            });
                        }
                    }
                }
                areas.sort().forEach(a => {
                    areaEl.append(new Option(a, a));
                });

                if (currArea && areaEl.find(`option[value="${currArea}"]`).length) {
                    areaEl.val(currArea);
                } else {
                    areaEl.val('');
                }
                areaEl.trigger('change');
            }

            filterParties();
        });

        // When Area changes
        areaEl.on('change', function() {
            filterParties();
        });
    }

    $(document).ready(function() {
        // Auto-initialize standard fields if present
        if ($('#field_province, select[name="province"]').length) {
            setupLocationCascade({
                province: '#field_province, select[name="province"]',
                city: '#field_city, select[name="city"]',
                area: '#field_area, select[name="area"]'
            });
        }

        // ==========================================
        // QUICK ADD PROVINCE MODAL
        // ==========================================
        $(document).on('click', '.btn-quick-add-province, #btnQuickAddProvince', function(e) {
            e.preventDefault();
            $('#quick_province_name').val('');
            showQuickModal('quickAddProvinceModal');
            setTimeout(function() { $('#quick_province_name').focus(); }, 300);
        });

        $('#btnSaveQuickProvince').click(function() {
            const provinceName = $('#quick_province_name').val().trim();

            if (!provinceName) {
                alert('Please enter Province name');
                return;
            }

            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

            $.ajax({
                url: "{{ route('locations.province.store') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    name: provinceName
                },
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Save Province');
                    hideQuickModal('quickAddProvinceModal');

                    const newName = res.item ? res.item.name : provinceName;

                    // Update hierarchy cache
                    if (res.hierarchy) {
                        window.locationHierarchy = res.hierarchy;
                    } else if (!window.locationHierarchy[newName]) {
                        window.locationHierarchy[newName] = {};
                    }

                    // Append & select in all Province dropdowns
                    $('select[name="province"], #field_province, #vprovince, #cust_search_province, #vend_filter_province, #province_desk, #quick_city_province, #quick_area_province').each(function() {
                        const sel = $(this);
                        let exists = false;
                        sel.find('option').each(function() {
                            if (($(this).val() || '').toLowerCase() === newName.toLowerCase()) {
                                exists = true;
                                sel.val($(this).val());
                            }
                        });
                        if (!exists) {
                            sel.append(new Option(newName, newName, true, true));
                            sel.val(newName);
                        }
                        if (sel.hasClass('select2-hidden-accessible')) {
                            sel.trigger('change.select2');
                        } else {
                            sel.trigger('change');
                        }
                    });

                    // Update datalist
                    if ($('#provinceList').length) {
                        $('#provinceList').append(new Option(newName));
                    }

                    showQuickNotice('Province "' + newName + '" added successfully!');
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Save Province');
                    const err = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error saving province';
                    alert(err);
                }
            });
        });

        // ==========================================
        // QUICK ADD CITY MODAL
        // ==========================================
        $(document).on('click', '.btn-quick-add-city, #btnQuickAddCity', function(e) {
            e.preventDefault();
            $('#quick_city_name').val('');
            
            // Pre-fill Province dropdown from active province in form
            const activeProv = $('select[name="province"], #field_province, #vprovince, #cust_search_province, #vend_filter_province, #province_desk').val() || '';
            if (activeProv && activeProv !== 'all') {
                $('#quick_city_province').val(activeProv);
            } else {
                $('#quick_city_province').val('');
            }
            showQuickModal('quickAddCityModal');
            setTimeout(function() { $('#quick_city_name').focus(); }, 300);
        });

        $('#btnSaveQuickCity').click(function() {
            const cityName = $('#quick_city_name').val().trim();
            const provinceName = $('#quick_city_province').val().trim();

            if (!cityName) {
                alert('Please enter City name');
                return;
            }
            if (!provinceName) {
                alert('Please select a Province from the dropdown');
                return;
            }

            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

            $.ajax({
                url: "{{ route('locations.city.store') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    name: cityName,
                    province: provinceName
                },
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Save City');
                    hideQuickModal('quickAddCityModal');

                    const newName = res.item ? res.item.name : cityName;
                    const newProv = res.item ? res.item.province : provinceName;

                    // Update hierarchy cache
                    if (res.hierarchy) {
                        window.locationHierarchy = res.hierarchy;
                    } else {
                        const p = newProv || 'Other';
                        if (!window.locationHierarchy[p]) window.locationHierarchy[p] = {};
                        if (!window.locationHierarchy[p][newName]) window.locationHierarchy[p][newName] = [];
                    }

                    // Append & select in all City dropdowns
                    $('select[name="city"], #field_city, #vcity, .cityInput, .citySelect').each(function() {
                        const sel = $(this);
                        if (!sel.find(`option[value="${newName}"]`).length) {
                            sel.append(new Option(newName, newName, true, true));
                        }
                        sel.val(newName).trigger('change');
                    });

                    // Set Province dropdown if empty
                    if (newProv) {
                        $('select[name="province"], #field_province, #vprovince').each(function() {
                            if (!$(this).val() || $(this).val() === 'all') {
                                $(this).val(newProv);
                            }
                        });
                    }

                    showQuickNotice('City "' + newName + '" added successfully!');
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Save City');
                    const err = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error saving city';
                    alert(err);
                }
            });
        });

        // ==========================================
        // QUICK ADD AREA MODAL
        // ==========================================
        $(document).on('click', '.btn-quick-add-area, #btnQuickAddArea', function(e) {
            e.preventDefault();
            $('#quick_area_name').val('');
            
            // Pre-fill City & Province from active values in form
            const activeCity = $('select[name="city"], #field_city, #vcity, #cust_search_city, #vend_filter_city, #city_desk').val() || '';
            const activeProv = $('select[name="province"], #field_province, #vprovince, #cust_search_province, #vend_filter_province, #province_desk').val() || '';

            if (activeProv && activeProv !== 'all') {
                $('#quick_area_province').val(activeProv).trigger('change');
            } else {
                $('#quick_area_province').val('');
            }

            if (activeCity && activeCity !== 'all') {
                $('#quick_area_city').val(activeCity);
            }

            showQuickModal('quickAddAreaModal');
            setTimeout(function() { $('#quick_area_name').focus(); }, 300);
        });

        // Filter city dropdown inside Quick Add Area modal when province changes
        $('#quick_area_province').change(function() {
            const p = $(this).val();
            const currC = $('#quick_area_city').val();
            $('#quick_area_city').empty();
            $('#quick_area_city').append(new Option('-- Select City --', ''));

            let cities = [];
            if (p && window.locationHierarchy[p]) {
                cities = Object.keys(window.locationHierarchy[p]);
            } else {
                for (let prov in window.locationHierarchy) {
                    for (let c in window.locationHierarchy[prov]) {
                        if (!cities.includes(c)) cities.push(c);
                    }
                }
            }
            cities.sort().forEach(c => {
                $('#quick_area_city').append(new Option(c, c));
            });
            if (currC) $('#quick_area_city').val(currC);
        });

        $('#btnSaveQuickArea').click(function() {
            const areaName = $('#quick_area_name').val().trim();
            const cityName = $('#quick_area_city').val().trim();
            const provinceName = $('#quick_area_province').val().trim();

            if (!areaName) {
                alert('Please enter Area name');
                return;
            }
            if (!cityName) {
                alert('Please select a City from the dropdown');
                return;
            }

            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

            $.ajax({
                url: "{{ route('locations.area.store') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    name: areaName,
                    city_name: cityName,
                    province: provinceName
                },
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Save Area');
                    hideQuickModal('quickAddAreaModal');

                    const newName = res.item ? res.item.name : areaName;
                    const resCity = res.item ? res.item.city_name : cityName;

                    // Update hierarchy cache
                    if (res.hierarchy) {
                        window.locationHierarchy = res.hierarchy;
                    } else {
                        const p = provinceName || 'Other';
                        if (!window.locationHierarchy[p]) window.locationHierarchy[p] = {};
                        if (!window.locationHierarchy[p][resCity]) window.locationHierarchy[p][resCity] = [];
                        if (!window.locationHierarchy[p][resCity].includes(newName)) {
                            window.locationHierarchy[p][resCity].push(newName);
                        }
                    }

                    // Append & select in all Area dropdowns
                    $('select[name="area"], #field_area, #varea, .areaInput, .areaSelect').each(function() {
                        const sel = $(this);
                        if (!sel.find(`option[value="${newName}"]`).length) {
                            sel.append(new Option(newName, newName, true, true));
                        }
                        sel.val(newName).trigger('change');
                    });

                    showQuickNotice('Area "' + newName + '" added successfully!');
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Save Area');
                    const err = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error saving area';
                    alert(err);
                }
            });
        });

        // ==========================================
        // QUICK ADD ZONE MODAL
        // ==========================================
        $(document).on('click', '.btn-quick-add-zone, #btnQuickAddZone', function(e) {
            e.preventDefault();
            $('#quick_zone_name').val('');
            showQuickModal('quickAddZoneModal');
            setTimeout(function() { $('#quick_zone_name').focus(); }, 300);
        });

        $('#btnSaveQuickZone').click(function() {
            const zoneName = $('#quick_zone_name').val().trim();
            if (!zoneName) {
                alert('Please enter Zone name');
                return;
            }
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

            $.ajax({
                url: "{{ route('locations.zone.store') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    name: zoneName,
                    zone: zoneName
                },
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Save Zone');
                    hideQuickModal('quickAddZoneModal');
                    const newZone = res.item ? (res.item.name || res.item.zone) : zoneName;

                    $('select[name="zone"], #vzone, #field_zone, #vend_filter_zone, .zoneInput, .zoneSelect').each(function() {
                        const sel = $(this);
                        if (!sel.find(`option[value="${newZone}"]`).length) {
                            sel.append(new Option(newZone, newZone, true, true));
                        }
                        sel.val(newZone).trigger('change');
                    });
                    showQuickNotice('Zone "' + newZone + '" saved successfully!');
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Save Zone');
                    const err = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to save zone.';
                    alert(err);
                }
            });
        });

    });
</script>
