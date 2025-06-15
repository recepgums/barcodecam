@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Zebra BrowserPrint for direct printing -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bluebird/3.7.2/bluebird.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/zebra-browser-print-min@3.0.216/BrowserPrint-3.0.216.min.js"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
.status-select-container {
    position: relative;
}

.selected-statuses {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
    background-color: #fff;
    min-height: 38px;
    cursor: pointer;
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    align-items: center;
}

.selected-statuses:hover {
    border-color: #86b7fe;
}

.status-tag {
    background-color: #0d6efd;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.status-tag .remove-tag {
    cursor: pointer;
    font-weight: bold;
}

.placeholder-text {
    color: #6c757d;
    font-size: 0.875rem;
}

.status-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    z-index: 1000;
    max-height: 200px;
    overflow-y: auto;
}

.status-option {
    display: block;
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    margin: 0;
}

.status-option:hover {
    background-color: #f8f9fa;
}

.status-option input {
    margin-right: 0.5rem;
}

/* Select2 Custom Styles */
.select2-container .select2-selection--multiple {
    min-height: 38px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #0d6efd;
    border: 1px solid #0d6efd;
    color: #fff;
    border-radius: 0.25rem;
    padding: 0.2rem 0.5rem;
    margin-top: 0.2rem;
    margin-right: 0.25rem;
    font-size: 0.875rem;
    line-height: 1.2;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #fff;
    margin-right: 0.25rem;
    font-size: 1rem;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #fff;
    background-color: rgba(255,255,255,0.2);
    border-radius: 50%;
}
.select2-dropdown {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}
.select2-container--default .select2-search--inline .select2-search__field {
    margin-top: 0.2rem;
    font-size: 0.875rem;
}
/* Form responsive improvements */
@media (max-width: 768px) {
    .row.g-3 > * {
        margin-bottom: 1rem;
    }
}
.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #495057;
}
.form-control, .form-select {
    font-size: 0.875rem;
}
.btn {
    font-size: 0.875rem;
    font-weight: 500;
}
.selected-barcode-item img {
    box-shadow: none !important;
}

/* Tablo iyileştirmeleri */
.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,0.05);
}

.table td {
    vertical-align: middle;
    border-color: #e9ecef;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    border-color: #dee2e6;
}

/* Badge iyileştirmeleri */
.badge {
    font-weight: 500;
}

.badge.fs-6 {
    font-size: 0.8rem !important;
}

/* Ürün resimleri için hover efekti */
.position-relative img:hover {
    transform: scale(1.1);
    transition: transform 0.2s ease;
    z-index: 10;
    position: relative;
}

/* Tutar sütunu için özel stil */
.table td:nth-child(8) {
    white-space: nowrap;
    min-width: 90px;
}

/* Responsive iyileştirmeler */
@media (max-width: 1200px) {
    .table th, .table td {
        font-size: 0.8rem;
        padding: 0.5rem 0.25rem;
    }
    
    /* Küçük ekranlarda tutar sütunu için */
    .table td:nth-child(8) {
        min-width: 80px;
        font-size: 0.75rem;
    }
}
</style>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Filtre Formu -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <form method="GET" action="" class="card card-body shadow-sm mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label">Mağaza</label>
                        <select name="store_id" class="form-select">
                            <option value="">Tümü</option>
                            @foreach(auth()->user()->stores as $store)
                                <option value="{{ $store->id }}" @if(request('store_id') == $store->id) selected @endif>{{ $store->merchant_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label">Kargo Firması</label>
                        <select name="cargo_service_provider" class="form-select">
                            <option value="">Tümü</option>
                            @foreach($cargoProviders as $provider)
                                <option value="{{ $provider }}" @if(request('cargo_service_provider') == $provider) selected @endif>{{ $provider }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label">Sipariş Durumu</label>
                        <div class="status-select-container">
                            <div class="selected-statuses" id="selectedStatuses">
                                <span class="placeholder-text">Durumları seçin...</span>
                            </div>
                            <div class="status-dropdown" id="statusDropdown" style="display: none;">
                                @foreach($statuses as $key => $label)
                                    <label class="status-option">
                                        <input type="checkbox" name="status[]" value="{{ $key }}" 
                                               @if(is_array(request('status')) && in_array($key, request('status'))) checked @endif
                                               @if(!is_array(request('status')) && request('status') == $key) checked @endif>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label">Müşteri</label>
                        <input type="text" name="customer_name" value="{{ request('customer_name') }}" class="form-control" placeholder="Müşteri adı">
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label">Sipariş No</label>
                        <input type="text" name="order_id" value="{{ request('order_id') }}" class="form-control" placeholder="Sipariş no">
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label">Yazdırma Durumu</label>
                        <select name="print_status" class="form-select">
                            <option value="">Tümü</option>
                            <option value="printed" @if(request('print_status') == 'printed') selected @endif>Yazdırılmış</option>
                            <option value="not_printed" @if(request('print_status') == 'not_printed') selected @endif>Yazdırılmamış</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label">Sıralama</label>
                        <select name="sort_by" class="form-select">
                            <option value="">Varsayılan (Tarih)</option>
                            <option value="price_asc" @if(request('sort_by') == 'price_asc') selected @endif>Satış Tutarı (Artan)</option>
                            <option value="price_desc" @if(request('sort_by') == 'price_desc') selected @endif>Satış Tutarı (Azalan)</option>
                            <option value="date_desc" @if(request('sort_by') == 'date_desc') selected @endif>Sipariş Tarihi (Yeniden Eskiye)</option>
                            <option value="date_asc" @if(request('sort_by') == 'date_asc') selected @endif>Sipariş Tarihi (Eskiden Yeniye)</option>
                            <option value="delivery_time_desc" @if(request('sort_by') == 'delivery_time_desc') selected @endif>Kargoya Vermek İçin Kalan Süre (Yeniden Eskiye)</option>
                            <option value="delivery_time_asc" @if(request('sort_by') == 'delivery_time_asc') selected @endif>Kargoya Vermek İçin Kalan Süre (Eskiden Yeniye)</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <label class="form-label">Barkod</label>
                        <select name="barcode[]" class="form-select js-barcode-select" multiple>
                            @foreach($barcodeProducts as $product)
                                <option value="{{ $product->barcode }}" @if(is_array(request('barcode')) && in_array($product->barcode, request('barcode'))) selected @endif>{{ $product->barcode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-2 col-sm-3">
                        <label class="form-label">Sayfa Başına</label>
                        <select name="per_page" class="form-select" onchange="this.form.submit()">
                            @foreach([10, 50, 100, 500,1000] as $size)
                                <option value="{{ $size }}" @if(request('per_page', 10) == $size) selected @endif>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-2 col-sm-3">
                        <button type="submit" class="btn btn-primary w-100">Filtrele</button>
                    </div>
                    <div class="col-lg-1 col-md-2 col-sm-3">
                        <a href="{{ route('shipments.index') }}" class="btn btn-secondary w-100">Temizle</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Kargo Listesi -->
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Kargo Listesi</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success btn-sm" onclick="generateBulkZPLImages()" id="zplCreateBtn" disabled>
                            <i class="fas fa-magic"></i> <span id="zplCreateText">ZPL Oluştur</span>
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="printZPL()" id="zplPrintBtn" disabled>
                            <i class="fas fa-print"></i> <span id="zplPrintText">ZPL Yazdır</span>
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="printPDF()" id="pdfPrintBtn" disabled>
                            <i class="fas fa-file-pdf"></i> <span id="pdfPrintText">PDF Yazdır</span>
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="updateToProcess()" id="processBtn" disabled>
                            <i class="fas fa-cogs"></i> <span id="processText">İşleme Alındı</span>
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center" style="width: 50px;">
                                        <input type="checkbox" class="form-check-input" style="width: 1.25rem; height: 1.25rem;" 
                                               id="selectAll" onchange="toggleSelectAll()">
                                    </th>
                                    <th class="text-center" style="width: 120px;">Sipariş No</th>
                                    <th class="text-center" style="width: 100px;">Mağaza</th>
                                    <th class="text-center" style="width: 100px;">Kalan Süre</th>
                                    <th class="text-center" style="width: 150px;">Müşteri</th>
                                    <th class="text-center" style="width: 140px;">Kargo Takip No</th>
                                    <th class="text-center" style="width: 100px;">Durum</th>
                                    <th class="text-center" style="width: 90px;">Tutar</th>
                                    <th class="text-center" style="width: 220px; min-width: 220px;">Ürünler</th>
                                    <th class="text-center" style="width: 60px;">Yazdırma</th>
                                    <th class="text-center" style="width: 200px;">Kargo İşlemleri</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                    <tr class="align-middle">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input order-checkbox" 
                                                   style="width: 1.25rem; height: 1.25rem;" 
                                                   name="order_ids[]" value="{{ $order->id }}" 
                                                   data-zpl="{{ htmlspecialchars($order->zpl_barcode) }}"
                                                   data-order-id="{{ $order->order_id }}"
                                                   data-customer="{{ $order->customer_name }}"
                                                   data-address="{{ $order->address }}"
                                                   data-tracking="{{ $order->cargo_tracking_number }}"
                                                   data-cargo="{{ $order->cargo_service_provider }}"
                                                   onchange="updatePrintButtons()">
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary fs-6">{{ $order->order_id }}</span>
                                        </td>
                                        <td class="text-center">
                                            <small class="text-muted">{{ $order->store?->merchant_name ?? '-' }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($order->remaining_delivery_time)
                                                @if($order->remaining_delivery_time === 'Süre doldu')
                                                    <span class="badge bg-danger fs-6">
                                                        <i class="fas fa-clock"></i> {{ $order->remaining_delivery_time }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-info text-dark fs-6">
                                                        <i class="fas fa-hourglass-half"></i> {{ $order->remaining_delivery_time }}
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary">{{ Str::limit($order->customer_name, 20) }}</div>
                                        </td>
                                        <td class="text-center">
                                            <small class="font-monospace">{{ $order->cargo_tracking_number ?: '-' }}</small>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusColors = [
                                                    'Created' => 'bg-info',
                                                    'Picking' => 'bg-warning',
                                                    'Invoiced' => 'bg-primary',
                                                    'Shipped' => 'bg-success',
                                                    'Delivered' => 'bg-success',
                                                    'Cancelled' => 'bg-danger',
                                                    'Returned' => 'bg-secondary'
                                                ];
                                                $statusColor = $statusColors[$order->status] ?? 'bg-light text-dark';
                                            @endphp
                                            <span class="badge {{ $statusColor }}">
                                                {{ \App\Models\Order::TYPES[$order->status] ?? $order->status }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="fw-bold text-success">{{ number_format($order->total_price, 2) }} ₺</div>
                                        </td>
                                        <td style="min-width: 220px; width: 220px; padding: 8px;">
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                @php $barcodes = []; @endphp
                                                @foreach($order->orderProducts as $orderProduct)
                                                    @php $product = $orderProduct->product; @endphp
                                                    @if($product)
                                                        @php $barcodes[] = $product->barcode; @endphp
                                                        <div class="position-relative" style="width: 40px; height: 40px;">
                                                            <img src="{{ $product->image_url }}" alt="{{ $product->title }}" 
                                                                 class="rounded border shadow-sm" 
                                                                 style="width: 100%; height: 100%; object-fit: cover;"
                                                                 title="{{ $product->title }}">
                                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                                                  style="font-size: 0.6rem;">
                                                                {{ $orderProduct->quantity}}
                                                            </span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                            @if(count($barcodes))
                                                <div class="mt-1">
                                                    <small class="text-muted d-block mb-1"><i class="fas fa-barcode"></i> <strong>Barkodlar:</strong></small>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach(array_filter($barcodes) as $barcode)
                                                            <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">{{ $barcode }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary fs-6">
                                                <i class="fas fa-print"></i> {{ $order->zpl_print_count ?? 0 }}
                                            </span>
                                        </td>
                                        <td style="padding: 8px;">
                                            <div class="d-flex flex-column gap-2">
                                                <form method="POST" action="{{ route('shipments.single-update', $order) }}" class="d-flex align-items-center gap-1">
                                                    @csrf
                                                    <select name="cargo_service_provider" class="form-select form-select-sm" style="min-width: 140px;">
                                                        @foreach($cargoProviders as $provider)
                                                            <option value="{{ $provider }}" {{ $order->cargo_service_provider == $provider ? 'selected' : '' }}>
                                                                {{ Str::limit($provider, 15) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-primary" title="Kargo Firmasını Güncelle">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                </form>
                                                
                                                @if($order->cargo_service_provider === 'Kolay Gelsin Marketplace')
                                                    <form method="POST" action="{{ route('shipments.generate-zpl', $order) }}" class="d-flex">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success w-100" title="ZPL Barcode Oluştur">
                                                            <i class="fas fa-barcode"></i> ZPL Oluştur
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-4">
                        {{ $orders->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    
    orderCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updatePrintButtons();
}

function updatePrintButtons() {
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    const zplCreateBtn = document.getElementById('zplCreateBtn');
    const zplBtn = document.getElementById('zplPrintBtn');
    const pdfBtn = document.getElementById('pdfPrintBtn');
    const processBtn = document.getElementById('processBtn');
    
    // Buton metinlerini güncelle
    const count = checkedBoxes.length;
    const zplCreateText = document.getElementById('zplCreateText');
    const zplPrintText = document.getElementById('zplPrintText');
    const pdfPrintText = document.getElementById('pdfPrintText');
    const processText = document.getElementById('processText');
    
    if (count > 0) {
        zplCreateBtn.disabled = false;
        zplBtn.disabled = false;
        pdfBtn.disabled = false;
        processBtn.disabled = false;
        
        // Buton metinlerinde sayıyı göster
        zplCreateText.textContent = `ZPL Oluştur (${count})`;
        zplPrintText.textContent = `ZPL Yazdır (${count})`;
        pdfPrintText.textContent = `PDF Yazdır (${count})`;
        processText.textContent = `İşleme Alındı (${count})`;
    } else {
        zplCreateBtn.disabled = true;
        zplBtn.disabled = true;
        pdfBtn.disabled = true;
        processBtn.disabled = true;
        
        // Buton metinlerini sıfırla
        zplCreateText.textContent = 'ZPL Oluştur';
        zplPrintText.textContent = 'ZPL Yazdır';
        pdfPrintText.textContent = 'PDF Yazdır';
        processText.textContent = 'İşleme Alındı';
    }
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.order-checkbox');
    const selectAllCheckbox = document.getElementById('selectAll');
    
    if (checkedBoxes.length === allCheckboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else if (checkedBoxes.length === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

function printZPL() {
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    if (checkedBoxes.length === 0) {
        showToast('Lütfen yazdırmak istediğiniz siparişleri seçin.', 'error');
        return;
    }
    
    const orderIds = [];
    const zplCodes = [];
    
    checkedBoxes.forEach(checkbox => {
        const zpl = checkbox.getAttribute('data-zpl');
        if (zpl && zpl.trim()) {
            zplCodes.push(zpl);
            orderIds.push(checkbox.value);
        }
    });
    
    if (zplCodes.length === 0) {
        showToast('Seçilen siparişlerin ZPL verileri bulunamadı.', 'error');
        return;
    }
    
    // Önce Zebra yazıcı ile denemeye çalış
    if (typeof BrowserPrint !== 'undefined') {
        tryZebraPrint(zplCodes, orderIds);
    } else {
        // BrowserPrint yoksa direkt normal yazdırmaya geç
        fallbackToBrowserPrint(zplCodes, orderIds);
    }
}

// Zebra yazıcı ile yazdırmaya çalış
function tryZebraPrint(zplCodes, orderIds) {
    showToast(`Zebra yazıcı aranıyor...`, 'info');
    
    // Varsayılan yazıcıyı al ve yazdır
    BrowserPrint.getDefaultDevice("printer", function(printer) {
        if (!printer) {
            showToast('Zebra yazıcı bulunamadı. Normal yazdırma moduna geçiliyor...', 'warning');
            fallbackToBrowserPrint(zplCodes, orderIds);
            return;
        }
        
        showToast(`${zplCodes.length} adet ZPL etiketi Zebra yazıcıya gönderiliyor...`, 'info');
        
        let printedCount = 0;
        let errorCount = 0;
        
        // Her ZPL kodunu sırayla yazdır
        zplCodes.forEach((zpl, index) => {
            printer.send(zpl, function() {
                // Başarılı yazdırma
                printedCount++;
                console.log(`ZPL ${index + 1} başarıyla yazdırıldı`);
                
                // Tüm yazdırma işlemleri tamamlandığında
                if (printedCount + errorCount === zplCodes.length) {
                    if (errorCount === 0) {
                        showToast(`${printedCount} adet etiket Zebra yazıcıdan başarıyla yazdırıldı!`, 'success');
                    } else {
                        showToast(`${printedCount} adet etiket yazdırıldı, ${errorCount} adet hata oluştu.`, 'warning');
                    }
                    
                    // Print count'u arttır
                    incrementPrintCount(orderIds);
                }
            }, function(errorMessage) {
                // Yazdırma hatası
                errorCount++;
                console.error(`ZPL ${index + 1} yazdırma hatası:`, errorMessage);
                
                // Tüm yazdırma işlemleri tamamlandığında
                if (printedCount + errorCount === zplCodes.length) {
                    if (printedCount === 0) {
                        showToast('Zebra yazıcı hatası. Normal yazdırma moduna geçiliyor...', 'warning');
                        fallbackToBrowserPrint(zplCodes, orderIds);
                    } else {
                        showToast(`${printedCount} adet etiket yazdırıldı, ${errorCount} adet hata oluştu.`, 'warning');
                        // Başarılı olanlar için print count'u arttır
                        incrementPrintCount(orderIds.slice(0, printedCount));
                    }
                }
            });
        });
        
    }, function(error) {
        showToast('Zebra yazıcı bağlantı hatası. Normal yazdırma moduna geçiliyor...', 'warning');
        console.error('Zebra yazıcı bağlantı hatası:', error);
        fallbackToBrowserPrint(zplCodes, orderIds);
    });
}

// Normal A4 yazıcı için fallback yazdırma (ZPL görüntüleri ile)
function fallbackToBrowserPrint(zplCodes, orderIds) {
    showToast('Normal yazıcı için etiket görüntüleri hazırlanıyor...', 'info');
    
    // ZPL kodlarından görüntü URL'lerini al
    const imagePromises = zplCodes.map((zpl, index) => {
        return new Promise((resolve, reject) => {
            // ZPL kodunu Labelary API'ye göndererek PNG elde et (6x4 inç - daha uzun etiket)
            fetch('https://api.labelary.com/v1/printers/8dpmm/labels/6x4/0/', {
                method: 'POST',
                headers: {
                    'Accept': 'image/png',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: zpl
            })
            .then(response => {
                if (response.ok) {
                    return response.blob();
                }
                throw new Error('API hatası');
            })
            .then(blob => {
                const imageUrl = URL.createObjectURL(blob);
                resolve({ index, imageUrl, orderId: orderIds[index] });
            })
            .catch(error => {
                console.error(`ZPL ${index + 1} görüntü oluşturma hatası:`, error);
                resolve({ index, imageUrl: null, orderId: orderIds[index] });
            });
        });
    });
    
    Promise.all(imagePromises).then(results => {
        const validImages = results.filter(result => result.imageUrl !== null);
        
        if (validImages.length === 0) {
            showToast('Hiçbir etiket görüntüsü oluşturulamadı.', 'error');
            return;
        }
        
        // Yazdırma penceresi oluştur
        const printWindow = window.open('', '_blank');
        
        let imagesHtml = '';
        validImages.forEach(result => {
            imagesHtml += `
                <div class="label-container" style="page-break-after: always; text-align: center; margin: 20px 0;">
                    <img src="${result.imageUrl}" style="max-width: 600px; height: auto; border: 1px solid #ddd;" alt="ZPL Etiket ${result.orderId}">
                </div>
            `;
        });
        
        printWindow.document.write(`
            <html>
            <head>
                <title>ZPL Etiketler - Normal Yazıcı</title>
                <style>
                    body { margin: 0; padding: 20px; font-family: Arial, sans-serif; }
                    .print-info { background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; }
                    .label-container:last-child { page-break-after: auto; }
                    @media print { 
                        body { margin: 0; padding: 0; }
                        .print-info { display: none; }
                        .label-container { margin: 10px 0; }
                    }
                </style>
            </head>
            <body>
                <div class="print-info">
                    <strong>ZPL Etiketler (Normal Yazıcı)</strong><br>
                    Toplam ${validImages.length} adet etiket<br><br>
                    <button onclick="window.print()" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-right: 10px;">Yazdır</button>
                    <button onclick="window.close()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Kapat</button>
                </div>
                ${imagesHtml}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        
        showToast(`${validImages.length} adet etiket görüntüsü hazırlandı. Yazdırma penceresinden yazdırabilirsiniz.`, 'success');
        
        // Print işlemi tamamlandığında print count'u arttır
        printWindow.addEventListener('afterprint', function() {
            incrementPrintCount(orderIds);
        });
        
        // 2 saniye sonra otomatik yazdır
        setTimeout(() => {
            printWindow.print();
        }, 2000);
        
        // Bellek temizliği için URL'leri 30 saniye sonra revoke et
        setTimeout(() => {
            validImages.forEach(result => {
                if (result.imageUrl) {
                    URL.revokeObjectURL(result.imageUrl);
                }
            });
        }, 30000);
    });
}

function printPDF() {
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    if (checkedBoxes.length === 0) {
        showToast('Lütfen yazdırmak istediğiniz siparişleri seçin.', 'error');
        return;
    }
    
    const orderIds = [];
    const zplCodes = [];
    
    checkedBoxes.forEach(checkbox => {
        const zpl = checkbox.getAttribute('data-zpl');
        if (zpl && zpl.trim()) {
            zplCodes.push(zpl);
            orderIds.push(checkbox.value);
        }
    });
    
    if (zplCodes.length === 0) {
        showToast('Seçilen siparişlerin ZPL verileri bulunamadı.', 'error');
        return;
    }
    
    showToast('PDF için ZPL görüntüleri hazırlanıyor...', 'info');
    
    // ZPL kodlarından görüntü URL'lerini al (ZPL yazdır ile aynı mantık)
    const imagePromises = zplCodes.map((zpl, index) => {
        return new Promise((resolve, reject) => {
            // ZPL kodunu Labelary API'ye göndererek PNG elde et (6x4 inç - daha uzun etiket)
            fetch('https://api.labelary.com/v1/printers/8dpmm/labels/6x4/0/', {
                method: 'POST',
                headers: {
                    'Accept': 'image/png',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: zpl
            })
            .then(response => {
                if (response.ok) {
                    return response.blob();
                }
                throw new Error('API hatası');
            })
            .then(blob => {
                const imageUrl = URL.createObjectURL(blob);
                resolve({ index, imageUrl, orderId: orderIds[index] });
            })
            .catch(error => {
                console.error(`ZPL ${index + 1} görüntü oluşturma hatası:`, error);
                resolve({ index, imageUrl: null, orderId: orderIds[index] });
            });
        });
    });
    
    Promise.all(imagePromises).then(results => {
        const validImages = results.filter(result => result.imageUrl !== null);
        
        if (validImages.length === 0) {
            showToast('Hiçbir etiket görüntüsü oluşturulamadı.', 'error');
            return;
        }
        
        // PDF yazdırma penceresi oluştur (ZPL görüntüleri ile)
        const printWindow = window.open('', '_blank');
        
        let imagesHtml = '';
        validImages.forEach((result, index) => {
            imagesHtml += `
                <div class="label-container" style="page-break-after: always; text-align: center; margin: 20px 0;">
                    <img src="${result.imageUrl}" style="max-width: 600px; height: auto; border: 1px solid #ddd;" alt="ZPL Etiket ${result.orderId}">
                </div>
            `;
        });
        
        printWindow.document.write(`
            <html>
            <head>
                <title>ZPL Etiketler - PDF Yazdırma</title>
                <style>
                    body { margin: 0; padding: 20px; font-family: Arial, sans-serif; }
                    .print-info { background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; }
                    .label-container:last-child { page-break-after: auto; }
                    @media print { 
                        body { margin: 0; padding: 0; }
                        .print-info { display: none; }
                        .label-container { margin: 10px 0; }
                    }
                </style>
            </head>
            <body>
                <div class="print-info">
                    <strong>ZPL Etiketler - PDF Yazdırma</strong><br>
                    Toplam ${validImages.length} adet etiket<br><br>
                    <button onclick="window.print()" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-right: 10px;">PDF Yazdır</button>
                    <button onclick="window.close()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Kapat</button>
                </div>
                ${imagesHtml}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        
        showToast(`${validImages.length} adet ZPL etiket görüntüsü PDF için hazırlandı!`, 'success');
        
        // Print işlemi tamamlandığında print count'u arttır
        printWindow.addEventListener('afterprint', function() {
            incrementPrintCount(orderIds);
        });
        
        // 2 saniye sonra otomatik yazdır
        setTimeout(() => {
            printWindow.print();
        }, 2000);
        
        // Bellek temizliği için URL'leri 30 saniye sonra revoke et
        setTimeout(() => {
            validImages.forEach(result => {
                if (result.imageUrl) {
                    URL.revokeObjectURL(result.imageUrl);
                }
            });
        }, 30000);
    });
}

// Print count arttırma fonksiyonu
function incrementPrintCount(orderIds) {
    fetch('{{ route("shipments.increment-print-count") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            order_ids: orderIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Sayfayı yenile veya print count'u güncelle
            location.reload();
        }
    })
    .catch(error => {
        console.error('Print count güncellenirken hata:', error);
    });
}

// İşleme alındı fonksiyonu
function updateToProcess() {
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    if (checkedBoxes.length === 0) {
        showToast('Lütfen işleme almak istediğiniz siparişleri seçin.', 'error');
        return;
    }
    
    const orderIds = Array.from(checkedBoxes).map(checkbox => parseInt(checkbox.value));
    
    showToast(`${orderIds.length} sipariş işleme alınıyor...`, 'info');
    
    fetch('{{ route("orders.update-to-process") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            order_ids: orderIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            if (data.error_count > 0) {
                // Hataları da göster
                data.errors.forEach(error => {
                    showToast(error, 'warning');
                });
            }
            // Sayfayı yenile
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showToast('Hata: ' + data.message, 'error');
            if (data.errors && data.errors.length > 0) {
                data.errors.forEach(error => {
                    showToast(error, 'error');
                });
            }
        }
    })
    .catch(error => {
        console.error('İşleme alma hatası:', error);
        showToast('İşleme alma sırasında hata oluştu!', 'error');
    });
}

// Multiple status select functionality
function initializeStatusSelect() {
    const selectedContainer = document.getElementById('selectedStatuses');
    const dropdown = document.getElementById('statusDropdown');
    const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');
    
    // Toggle dropdown on click
    selectedContainer.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        dropdown.style.display = 'none';
    });
    
    // Prevent dropdown close when clicking inside
    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Handle checkbox changes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedStatuses();
        });
    });
    
    // Initialize display
    updateSelectedStatuses();
}

function updateSelectedStatuses() {
    const selectedContainer = document.getElementById('selectedStatuses');
    const checkboxes = document.querySelectorAll('#statusDropdown input[type="checkbox"]:checked');
    
    selectedContainer.innerHTML = '';
    
    if (checkboxes.length === 0) {
        selectedContainer.innerHTML = '<span class="placeholder-text">Durumları seçin...</span>';
    } else {
        checkboxes.forEach(checkbox => {
            const label = checkbox.nextElementSibling.textContent;
            const tag = document.createElement('span');
            tag.className = 'status-tag';
            tag.innerHTML = `
                ${label}
                <span class="remove-tag" onclick="removeStatusTag('${checkbox.value}')">&times;</span>
            `;
            selectedContainer.appendChild(tag);
        });
    }
}

function removeStatusTag(statusValue) {
    const checkbox = document.querySelector(`#statusDropdown input[value="${statusValue}"]`);
    if (checkbox) {
        checkbox.checked = false;
        updateSelectedStatuses();
    }
}

// Initialize button states on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePrintButtons();
    initializeStatusSelect();
    
    // Print status'u da URL'den al ve set et
    const urlParams = new URLSearchParams(window.location.search);
    const printStatus = urlParams.get('print_status');
    if (printStatus) {
        const printStatusSelect = document.querySelector('select[name="print_status"]');
        if (printStatusSelect) {
            printStatusSelect.value = printStatus;
        }
    }
});



// ZPL Image oluştur ve kaydet (Tekil)
function generateZPLImage(orderId) {
    // Loading toast göster
    showToast('ZPL görüntüsü oluşturuluyor...', 'info');
    
    fetch(`/shipments/${orderId}/generate-zpl-image`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('ZPL görüntüsü başarıyla oluşturuldu!', 'success');
            // Sayfayı yenile ki image görünsün
            location.reload();
        } else {
            showToast('Hata: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('ZPL image generation error:', error);
        showToast('ZPL görüntüsü oluşturulurken hata oluştu!', 'error');
    });
}

// Toplu ZPL Image oluştur ve kaydet
function generateBulkZPLImages() {
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    if (checkedBoxes.length === 0) {
        showToast('Lütfen ZPL oluşturmak istediğiniz siparişleri seçin.', 'error');
        return;
    }
    
    // Seçili order ID'leri topla
    const orderIds = Array.from(checkedBoxes).map(checkbox => checkbox.value);
    
    // Loading toast göster
    showToast(`${orderIds.length} siparişin ZPL görüntüleri oluşturuluyor...`, 'info');
    
    // Butonları disable et
    const zplCreateBtn = document.getElementById('zplCreateBtn');
    const originalText = zplCreateBtn.innerHTML;
    zplCreateBtn.disabled = true;
    zplCreateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Oluşturuluyor...';
    
    fetch('/shipments/generate-bulk-zpl-images', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            order_ids: orderIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`Başarılı: ${data.success_count} adet ZPL görüntüsü oluşturuldu!`, 'success');
            if (data.error_count > 0) {
                showToast(`Uyarı: ${data.error_count} adet sipariş işlenemedi. Detaylar: ${data.errors.join(', ')}`, 'warning');
            }
            // Sayfayı yenile ki yeni image'lar görünsün
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showToast('Hata: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Bulk ZPL image generation error:', error);
        showToast('ZPL görüntüleri oluşturulurken hata oluştu!', 'error');
    })
    .finally(() => {
        // Butonu eski haline getir
        zplCreateBtn.disabled = false;
        zplCreateBtn.innerHTML = originalText;
        updatePrintButtons(); // Button state'ini güncelle
    });
}

// Mevcut ZPL görüntüsünü modal'da göster  
function showZPLLabel(orderId) {
    // Mevcut image URL'ini al
    const imgElement = document.querySelector(`img[onclick="showZPLLabel(${orderId})"]`);
    if (!imgElement) {
        showToast('ZPL görüntüsü bulunamadı!', 'error');
        return;
    }
    
    const imageUrl = imgElement.src;
    
    // Modal oluştur ve göster
    const modalHTML = `
        <div class="modal fade" id="zplModal" tabindex="-1" aria-labelledby="zplModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="zplModalLabel">ZPL Etiket Görünümü</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="${imageUrl}" class="img-fluid" style="max-width: 100%; border: 1px solid #ddd; border-radius: 5px;" alt="ZPL Etiket">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <a href="${imageUrl}" download="zpl_label_${orderId}.png" class="btn btn-success">PNG İndir</a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Eski modalı kaldır
    const existingModal = document.getElementById('zplModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Yeni modalı ekle ve göster
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    const modal = new bootstrap.Modal(document.getElementById('zplModal'));
    modal.show();
    
    // Modal kapandığında cleanup
    document.getElementById('zplModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// Toast notification göster
function showToast(message, type = 'info') {
    const getBootstrapClass = (type) => {
        switch(type) {
            case 'success': return 'success';
            case 'error': return 'danger';
            case 'warning': return 'warning';
            default: return 'info';
        }
    };
    
    const toastHTML = `
        <div class="toast-container position-fixed top-0 end-0 p-3">
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-${getBootstrapClass(type)} text-white">
                    <strong class="me-auto">Bildirim</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', toastHTML);
    
    // 3 saniye sonra otomatik kapat
    setTimeout(() => {
        const toasts = document.querySelectorAll('.toast-container');
        toasts.forEach(toast => toast.remove());
    }, 3000);
}

// ZPL verisini panoya kopyala
function copyZPL(zplData) {
    navigator.clipboard.writeText(zplData).then(function() {
        alert('ZPL kodu panoya kopyalandı!');
    }).catch(function(err) {
        console.error('Kopyalama hatası: ', err);
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = zplData;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('ZPL kodu kopyalandı!');
    });
}
</script>
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<style>
    .select2-container--bootstrap4 .select2-selection--multiple {
        min-height: 38px;
        border-radius: 0.375rem;
        border: 1px solid #ced4da;
        padding: 0.375rem 0.75rem;
        background: #fff;
    }
    .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
        background-color: #0d6efd;
        color: #fff;
        border: none;
        border-radius: 0.25rem;
        padding: 0.2rem 0.5rem;
        margin-top: 0.2rem;
    }
    .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
        color: #fff;
        margin-right: 4px;
    }
</style>
@endpush
</div>

<!-- jQuery ve Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
window.onload = function() {
    $('.js-barcode-select').select2({
        placeholder: 'Barkod seçin',
        allowClear: true,
        width: '100%',
        closeOnSelect: false
    });
};
</script>

@endsection 