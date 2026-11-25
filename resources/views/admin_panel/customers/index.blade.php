@extends('admin_panel.layout.app')

@section('content')
    <style>
        .btn-sm i.fa-toggle-on {
            color: green;
            font-size: 20px;
        }

        .btn-sm i.fa-toggle-off {
            color: gray;
            font-size: 20px;
        }

        .customer-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .customer-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .btn-action-custom {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            font-weight: 500;
            border-radius: 8px;
            font-size: 0.88rem;
            transition: all 0.2s;
        }

        .filter-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 14px 16px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .cust-filter-bar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            width: 100%;
        }

        .cust-col-search {
            flex: 2 1 240px;
            min-width: 200px;
        }

        .cust-col-select {
            flex: 1 1 135px;
            min-width: 125px;
        }

        .cust-col-actions {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: auto;
        }

        .cust-filter-input,
        .cust-filter-select {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            height: 38px !important;
            border-radius: 8px !important;
            border: 1.5px solid #cbd5e1 !important;
            font-size: 0.86rem !important;
            font-weight: 500 !important;
            color: #1e293b !important;
            background-color: #ffffff !important;
            box-sizing: border-box !important;
            transition: all 0.2s ease-in-out !important;
        }

        .cust-filter-input {
            padding: 6px 12px 6px 36px !important;
        }

        .cust-filter-select {
            padding: 6px 28px 6px 12px !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23475569' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right 10px center !important;
            background-size: 11px 9px !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            cursor: pointer !important;
            line-height: 1.5 !important;
        }

        .cust-filter-select::-ms-expand {
            display: none !important;
        }

        .cust-filter-input:focus,
        .cust-filter-select:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
            outline: none !important;
        }

        @media (max-width: 991px) {
            .cust-col-search {
                flex: 1 1 100%;
                min-width: 100%;
            }
            .cust-col-select {
                flex: 1 1 calc(50% - 10px);
                min-width: 130px;
            }
            .cust-col-actions {
                flex: 1 1 100%;
                justify-content: flex-end;
                margin-left: 0;
                margin-top: 4px;
            }
        }

        @media (max-width: 576px) {
            .cust-col-select {
                flex: 1 1 100%;
            }
        }

        .detail-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 10px 14px;
            border: 1px solid #eef2f6;
            height: 100%;
        }

        .detail-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 3px;
        }

        .detail-val {
            font-size: 0.92rem;
            font-weight: 600;
            color: #1e293b;
            word-break: break-word;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid px-4 py-3">

                <!-- Header & Toolbar -->
                <div class="customer-toolbar">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h3 class="mb-0 fw-bold text-dark">Customer Management</h3>
                            <span class="badge bg-primary text-white" style="font-size: 0.75rem; border-radius: 6px;">Dynamic</span>
                        </div>
                        <small class="text-muted">Centrally configured customer records, balances, search, and data exports.</small>
                    </div>

                    <div class="customer-actions">
                        <!-- Field Configuration -->
                        <a href="{{ route('dynamic_fields.index', ['module' => 'customers']) }}" class="btn btn-outline-dark btn-action-custom" title="Configure dynamic fields, visibility and permissions">
                            <i class="fas fa-sliders-h text-primary"></i> Field Config
                        </a>

                        <!-- Template Download -->
                        <a href="{{ route('customers.template') }}" class="btn btn-outline-secondary btn-action-custom" title="Download blank CSV template">
                            <i class="fas fa-file-csv text-primary"></i> Template
                        </a>

                        <!-- Export CSV -->
                        <a href="{{ route('customers.export') }}" class="btn btn-outline-success btn-action-custom" title="Export configured customer columns to CSV">
                            <i class="fas fa-file-download"></i> Export CSV
                        </a>

                        <!-- Import CSV (Modal Trigger) -->
                        <button type="button" class="btn btn-outline-primary btn-action-custom" data-toggle="modal" data-target="#customerImportModal" id="openCustomerImportModalBtn" title="Import customers from CSV">
                            <i class="fas fa-file-upload"></i> Import CSV
                        </button>

                        @can('customers.create')
                            <a href="{{ route('customers.create') }}" class="btn btn-primary btn-action-custom">
                                <i class="fas fa-user-plus"></i> + Add Customer
                            </a>
                        @endcan

                        @can('customers.view')
                            <a href="{{ route('customers.ledger') }}" class="btn btn-info btn-action-custom text-white">
                                <i class="fas fa-book"></i> Ledger
                            </a>
                            <a href="{{ route('customer.payments') }}" class="btn btn-secondary btn-action-custom">
                                <i class="fas fa-money-bill-wave"></i> Payment
                            </a>
                        @endcan

                        <a href="{{ route('customers.inactive') }}" class="btn btn-outline-danger btn-action-custom">
                            <i class="fas fa-user-slash"></i> Inactive
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="outline: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="outline: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <!-- Modern Responsive Filter Bar -->
                <div class="filter-card py-2.5 px-3 mb-3">
                    <form method="GET" action="{{ route('customers.index') }}" class="mb-0">
                        <div class="cust-filter-bar">
                            <!-- 1. Universal Search Input -->
                            <div class="cust-col-search">
                                <div class="position-relative">
                                    <i class="fas fa-search position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); font-size: 13px;"></i>
                                    <input type="text" name="search" class="cust-filter-input" 
                                        placeholder="Search by name, code, phone, address..." 
                                        value="{{ request('search') }}">
                                </div>
                            </div>

                            <!-- 2. Province Filter -->
                            <div class="cust-col-select">
                                <select name="province" id="cust_search_province" class="cust-filter-select form-select-sm">
                                    <option value="">All Provinces</option>
                                    @foreach(app(\App\Services\LocationService::class)->getProvinces() as $p)
                                        <option value="{{ $p }}" {{ request('province') == $p ? 'selected' : '' }}>{{ $p }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 3. City Filter -->
                            <div class="cust-col-select">
                                <select name="city" id="cust_search_city" class="cust-filter-select form-select-sm">
                                    <option value="">All Cities</option>
                                    @foreach(app(\App\Services\LocationService::class)->getCities(request('province')) as $c)
                                        <option value="{{ $c }}" {{ request('city') == $c ? 'selected' : '' }}>{{ $c }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 4. Area Filter -->
                            <div class="cust-col-select">
                                <select name="area" id="cust_search_area" class="cust-filter-select form-select-sm">
                                    <option value="">All Areas</option>
                                    @foreach(app(\App\Services\LocationService::class)->getAreas(request('city')) as $a)
                                        <option value="{{ $a }}" {{ request('area') == $a ? 'selected' : '' }}>{{ $a }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 5. Customer Type Dropdown -->
                            <div class="cust-col-select">
                                <select name="customer_type" class="cust-filter-select form-select-sm">
                                    <option value="">All Types</option>
                                    @if(isset($dropdownLookups['customer_types']))
                                        @foreach($dropdownLookups['customer_types'] as $optVal => $optLabel)
                                            <option value="{{ $optVal }}" {{ request('customer_type') == $optVal ? 'selected' : '' }}>
                                                {{ $optLabel }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <!-- 6. Action Buttons -->
                            <div class="cust-col-actions">
                                <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold" style="height: 38px; border-radius: 8px; white-space: nowrap;">
                                    <i class="fas fa-search me-1"></i> Search
                                </button>
                                <a href="{{ route('customers.index') }}" class="btn btn-light border btn-sm text-secondary" title="Reset Filters" style="height: 38px; width: 38px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-undo"></i>
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="collapse" data-target="#moreCustomerFilters" data-bs-toggle="collapse" data-bs-target="#moreCustomerFilters" title="More Filters" style="height: 38px; width: 38px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-sliders-h"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Collapsible Extra Filters -->
                        <div class="collapse pt-3 mt-3 border-top {{ (request('party_type') || request('zone') || request('filer_type') || request('status') || request('ntn_number')) ? 'show' : '' }}" id="moreCustomerFilters">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-3 col-sm-6 mb-2">
                                    <label class="form-label small text-muted mb-1 fw-bold">Party Category (Role)</label>
                                    <select name="party_type" class="cust-filter-select">
                                        <option value="">All Categories</option>
                                        <option value="customer" {{ request('party_type') == 'customer' ? 'selected' : '' }}>Customer Only</option>
                                        <option value="both" {{ request('party_type') == 'both' ? 'selected' : '' }}>Both (Customer & Vendor)</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-2">
                                    <label class="form-label small text-muted mb-1 fw-bold">Region / Zone</label>
                                    <select name="zone" class="cust-filter-select">
                                        <option value="">All Zones</option>
                                        @if(isset($dropdownLookups['zones']))
                                            @foreach($dropdownLookups['zones'] as $optVal => $optLabel)
                                                <option value="{{ $optVal }}" {{ request('zone') == $optVal ? 'selected' : '' }}>
                                                    {{ $optLabel }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-2">
                                    <label class="form-label small text-muted mb-1 fw-bold">Tax Status (Filer)</label>
                                    <select name="filer_type" class="cust-filter-select">
                                        <option value="">All Tax Status</option>
                                        <option value="filer" {{ request('filer_type') == 'filer' ? 'selected' : '' }}>Filer</option>
                                        <option value="non_filer" {{ request('filer_type') == 'non_filer' ? 'selected' : '' }}>Non-Filer</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-2 d-flex align-items-end pt-sm-4">
                                    <button type="submit" class="btn btn-dark btn-sm px-3 w-100 fw-semibold" style="height: 38px; border-radius: 8px;">
                                        <i class="fas fa-check me-1"></i> Apply Extra Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Dynamic Customers Table -->
                <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0" id="customersTable">
                            <thead class="table-light">
                                <tr>
                                    @foreach($listFields as $field)
                                        <th>{{ $field->label }}</th>
                                    @endforeach
                                    <th style="min-width: 170px;" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($customers as $customer)
                                    <tr>
                                        @foreach($listFields as $field)
                                            @php
                                                $key = $field->field_key;
                                                $formattedVal = app(\App\Services\CustomerFieldService::class)->getFieldValue($customer, $field, true);
                                            @endphp

                                            <td>
                                                @if($key === 'customer_id')
                                                    <a href="javascript:void(0)" class="btn-view-customer fw-bold text-primary" data-id="{{ $customer->id }}" title="Click to view details">
                                                        {{ $formattedVal }}
                                                    </a>

                                                @elseif($key === 'party_type')
                                                    @if(($customer->party_type ?? '') === 'both' || !empty($customer->linked_vendor_id))
                                                        <span class="badge bg-primary text-white px-2 py-1" style="font-size: 11px;">
                                                            <i class="fas fa-sync-alt me-1"></i>Both (Cust &amp; Vend)
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 11px;">Customer</span>
                                                    @endif

                                                @elseif($key === 'customer_name')
                                                    <a href="javascript:void(0)" class="btn-view-customer fw-bold text-dark text-decoration-none" data-id="{{ $customer->id }}">
                                                        {{ $formattedVal }}
                                                    </a>
                                                    @if(($customer->party_type ?? '') === 'both' || !empty($customer->linked_vendor_id))
                                                        <span class="badge bg-primary-subtle text-primary border border-primary ms-1 px-1.5 py-0" style="font-size: 10px;" title="Party active as Customer &amp; Vendor">Both</span>
                                                    @endif
                                                    @if($customer->customer_name_ur)
                                                        <small class="text-muted d-block" dir="rtl" style="font-family: 'Noto Nastaliq Urdu', serif;">
                                                            {{ $customer->customer_name_ur }}
                                                        </small>
                                                    @endif
                                                    @if($customer->customer_type)
                                                        <span class="badge bg-light text-dark border mt-1">{{ $customer->customer_type }}</span>
                                                    @endif

                                                @elseif($key === 'status')
                                                    @if($customer->status === 'active')
                                                        <span class="badge bg-success text-white">active</span>
                                                    @else
                                                        <span class="badge bg-secondary text-white">inactive</span>
                                                    @endif

                                                @elseif($field->field_type === 'url' && !empty($formattedVal) && $formattedVal !== '-')
                                                    <a href="{{ $formattedVal }}" target="_blank" class="text-primary">
                                                        <i class="fas fa-external-link-alt me-1"></i> Link
                                                    </a>

                                                @else
                                                    {{ $formattedVal }}
                                                @endif
                                            </td>
                                        @endforeach

                                        <!-- Actions Column -->
                                        <td class="text-center">
                                            <!-- View Details Button -->
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-view-customer" data-id="{{ $customer->id }}" title="View Customer Details">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            @include('admin_panel.partials.action_buttons', [
                                                'editRoute' => route('customers.edit', $customer->id),
                                                'deleteRoute' => route('customers.destroy', $customer->id),
                                                'editIsLink' => true,
                                                'permissions' => [
                                                    'edit' => 'customers.edit',
                                                    'delete' => 'customers.delete',
                                                ],
                                                'dataId' => $customer->id,
                                            ])

                                            @can('customers.edit')
                                                <a href="{{ route('customers.toggleStatus', $customer->id) }}"
                                                    class="btn btn-sm {{ $customer->status === 'active' ? 'btn-dark' : 'btn-secondary' }}"
                                                    title="Toggle Status">
                                                    <i class="fa-solid {{ $customer->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                </a>
                                            @endcan

                                            @can('customers.view')
                                                <a href="{{ route('customer.payments') }}" class="btn btn-sm btn-info text-white" title="Customer Payments">Payments</a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($listFields) + 1 }}" class="text-center py-4 text-muted">
                                            No customers found matching the criteria. Click <strong>+ Add Customer</strong> or adjust your search filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         DYNAMIC CUSTOMER VIEW DETAILS MODAL
    ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="customerDetailsModal" tabindex="-1" role="dialog" aria-labelledby="customerDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius:16px; overflow:hidden;">
                <div class="modal-header" style="background: linear-gradient(135deg, #1e293b, #334155); color:#fff; border-bottom: none;">
                    <div>
                        <h5 class="modal-title fw-bold" id="customerDetailsModalLabel">
                            <i class="fas fa-user-circle me-2 text-primary"></i> <span id="modalCustomerName">Customer Details</span>
                        </h5>
                        <small style="color: rgba(255,255,255,0.75);" id="modalCustomerCode">Loading...</small>
                    </div>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity:.8; text-shadow:none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-4" style="background:#f8fafc; max-height: calc(85vh - 120px); overflow-y: auto;">
                    <!-- Loading Spinner -->
                    <div id="modalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="text-muted mt-2">Loading customer information...</p>
                    </div>

                    <!-- Details Content -->
                    <div id="modalDetailsContent" style="display: none;">
                        <!-- Top Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-sm-4">
                                <div class="p-3 bg-white border rounded-3 shadow-xs">
                                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Status</small>
                                    <div class="h6 mb-0 mt-1" id="modalCustomerStatus">-</div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-3 bg-white border rounded-3 shadow-xs">
                                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Current Balance</small>
                                    <div class="h6 mb-0 mt-1 text-danger fw-bold" id="modalCustomerBalance">-</div>
                                </div>
                            </div>
                            <div class="col-sm-4 text-end">
                                <div class="p-3 bg-white border rounded-3 shadow-xs d-flex justify-content-end align-items-center gap-2 h-100">
                                    <a href="#" id="modalEditLink" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit me-1"></i> Edit Customer
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Fields Grouped by Section -->
                        <div id="modalDynamicSections"></div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         CUSTOMER IMPORT MODAL
    ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="customerImportModal" tabindex="-1" role="dialog" aria-labelledby="customerImportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius:16px; overflow:hidden;">
                <div class="modal-header" style="background: linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; border-bottom: none;">
                    <div>
                        <h5 class="modal-title fw-bold" id="customerImportModalLabel">
                            <i class="fas fa-file-upload me-2"></i> Import Customers from CSV
                        </h5>
                        <small style="color: rgba(255,255,255,0.88);">
                            Create new customers or update existing records directly from spreadsheet.
                        </small>
                    </div>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity:.8; text-shadow:none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-4" style="background:#f8fafc;">
                    <form action="{{ route('customers.import.validate') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="alert alert-info d-flex gap-2 align-items-start mb-4" style="font-size:.85rem; border-left: 4px solid #3b82f6;">
                            <i class="fas fa-info-circle fs-5 mt-1 flex-shrink-0 text-primary"></i>
                            <div>
                                <strong>Quick Guide:</strong><br>
                                &bull; <strong>Create New Customers:</strong> Download the <a href="{{ route('customers.template') }}" class="alert-link fw-bold">Blank Template</a>, enter customer info, and import.<br>
                                &bull; <strong>Edit Existing Customers:</strong> Download current data via <a href="{{ route('customers.export') }}" class="alert-link fw-bold">Export CSV</a>, modify prices/limits/phones in Excel, and upload here.<br>
                                &bull; Existing customers are matched accurately by <strong>Customer ID</strong> or <strong>System ID</strong>.<br>
                                &bull; You can review all rows using <strong>Validate &amp; Preview</strong> or import right away using <strong>Direct Import</strong>.
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark">Import Mode</label>
                                <select name="import_mode" class="form-control" required style="border-radius: 8px;">
                                    <option value="upsert" selected>Create &amp; Update (Recommended)</option>
                                    <option value="create_only">Create Only (Skip existing)</option>
                                    <option value="update_only">Update Only (Ignore new)</option>
                                </select>
                                <small class="text-muted">Controls whether existing records are updated or skipped.</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark">Auto-create Missing Data</label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="autoCreateCustCheck" name="auto_create" value="1" checked>
                                    <label class="form-check-label fw-bold ms-1" for="autoCreateCustCheck">
                                        Auto-create missing Types &amp; Zones
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">Automatically registers new Customer Types or Zones found in the CSV.</small>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="fw-bold text-dark">Upload CSV File <span class="text-danger">*</span></label>
                            <input type="file" name="csv_file" class="form-control p-1" accept=".csv,.txt" required style="border-radius: 8px;">
                            <small class="text-muted">Supported formats: .csv, .txt (UTF-8 encoded). Max size: 10 MB.</small>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top flex-wrap" style="gap: 10px;">
                            <a href="{{ route('customers.template') }}" class="btn btn-outline-primary btn-sm" style="border-radius: 8px;">
                                <i class="fas fa-download me-1"></i> Download Template
                            </a>

                            <div class="d-flex flex-wrap" style="gap: 8px;">
                                <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                                <button type="submit" name="action_type" value="preview" class="btn btn-primary px-3 fw-bold" style="border-radius: 8px;">
                                    <i class="fas fa-eye me-1"></i> Validate &amp; Preview
                                </button>
                                <button type="submit" name="action_type" value="direct" class="btn btn-success px-3 fw-bold" style="border-radius: 8px;">
                                    <i class="fas fa-bolt me-1"></i> Direct Import
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@include('admin_panel.partials.location_selector_script')
<script>
$(document).ready(function() {
    setupLocationCascade({
        province: '#cust_search_province',
        city: '#cust_search_city',
        area: '#cust_search_area'
    });
});
</script>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Handle View Details Modal
        $(document).on('click', '.btn-view-customer', function(e) {
            e.preventDefault();
            const customerId = $(this).data('id');
            if (!customerId) return;

            // Reset modal states
            $('#modalCustomerName').text('Customer Details');
            $('#modalCustomerCode').text('Loading...');
            $('#modalLoading').show();
            $('#modalDetailsContent').hide();
            $('#modalDynamicSections').empty();

            // Set edit route dynamically
            const editUrl = "{{ url('customers') }}/" + customerId + "/edit";
            $('#modalEditLink').attr('href', editUrl);

            $('#customerDetailsModal').modal('show');

            // Fetch dynamic details
            $.ajax({
                url: "{{ url('customers') }}/" + customerId + "/details",
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#modalCustomerName').text(res.customer.customer_name);
                        $('#modalCustomerCode').text('Customer Code: ' + (res.customer.customer_id || 'N/A'));
                        
                        const statusBadge = res.customer.status === 'active' 
                            ? '<span class="badge bg-success text-white">active</span>'
                            : '<span class="badge bg-secondary text-white">inactive</span>';
                        $('#modalCustomerStatus').html(statusBadge);

                        const balance = parseFloat(res.customer.balance) || 0;
                        $('#modalCustomerBalance').text('Rs. ' + balance.toLocaleString('en-US', {minimumFractionDigits: 2}));

                        // Group fields by section
                        const sections = {};
                        (res.fields || []).forEach(function(f) {
                            const sec = f.section || 'General Information';
                            if (!sections[sec]) {
                                sections[sec] = [];
                            }
                            sections[sec].push(f);
                        });

                        // Render each section
                        let html = '';
                        for (const secName in sections) {
                            html += '<div class="mb-4">';
                            html += '<h6 class="fw-bold text-primary border-bottom pb-2 mb-3" style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.04em;">';
                            html += '<i class="fas fa-folder-open me-2"></i>' + secName;
                            html += '</h6>';
                            html += '<div class="row g-2">';

                            sections[secName].forEach(function(item) {
                                html += '<div class="col-sm-6 col-md-4 mb-2">';
                                html += '<div class="detail-item">';
                                html += '<div class="detail-label">' + item.label + '</div>';
                                
                                let displayVal = item.formatted;
                                if (!displayVal || displayVal === '-') {
                                    displayVal = '<span class="text-muted fst-italic">Not specified</span>';
                                } else if (item.type === 'url' && item.value) {
                                    displayVal = '<a href="' + item.value + '" target="_blank" class="text-primary"><i class="fas fa-external-link-alt me-1"></i>' + item.value + '</a>';
                                }
                                
                                html += '<div class="detail-val">' + displayVal + '</div>';
                                html += '</div>';
                                html += '</div>';
                            });

                            html += '</div></div>';
                        }

                        $('#modalDynamicSections').html(html);
                        $('#modalLoading').hide();
                        $('#modalDetailsContent').fadeIn(200);
                    } else {
                        $('#modalLoading').html('<div class="alert alert-danger">Failed to load customer details.</div>');
                    }
                },
                error: function() {
                    $('#modalLoading').html('<div class="alert alert-danger">An error occurred while fetching customer details.</div>');
                }
            });
        });
    });
</script>
@include('admin_panel.partials.location_selector_script')
<script>
$(document).ready(function() {
    setupLocationCascade({
        province: '#cust_search_province',
        city: '#cust_search_city',
        area: '#cust_search_area'
    });
});
</script>
@endsection
