@extends('layouts.app')

@section('content')
<!-- Bootstrap 5 CDN: Eğer app.blade.php'de yoksa, buraya ekleyebilirsiniz -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

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
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Mağaza</label>
                        <select name="store_id" class="form-select">
                            <option value="">Tümü</option>
                            @foreach(auth()->user()->stores as $store)
                                <option value="{{ $store->id }}" @if(request('store_id') == $store->id) selected @endif>{{ $store->merchant_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Kargo Firması</label>
                        <select name="cargo_service_provider" class="form-select">
                            <option value="">Tümü</option>
                            @foreach($cargoProviders as $provider)
                                <option value="{{ $provider }}" @if(request('cargo_service_provider') == $provider) selected @endif>{{ $provider }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sipariş Durumu</label>
                        <select name="status" class="form-select">
                            <option value="">Tümü</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" @if(request('status') == $key) selected @endif>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Müşteri Adı</label>
                        <input type="text" name="customer_name" value="{{ request('customer_name') }}" class="form-control" placeholder="Müşteri adı">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sipariş No</label>
                        <input type="text" name="order_id" value="{{ request('order_id') }}" class="form-control" placeholder="Sipariş no">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                    </div>
                    <div class="col-md-1 mt-2">
                        <button type="submit" class="btn btn-primary w-100">Filtrele</button>
                    </div>
                    <div class="col-md-1 mt-2">
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
                        <button type="button" class="btn btn-primary btn-sm" onclick="printZPL()" id="zplPrintBtn" disabled>
                            <i class="fas fa-print"></i> ZPL Yazdır
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="printPDF()" id="pdfPrintBtn" disabled>
                            <i class="fas fa-file-pdf"></i> PDF Yazdır
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" class="form-check-input" style="width: 1.25rem; height: 1.25rem;" 
                                               id="selectAll" onchange="toggleSelectAll()">
                                    </th>
                                    <th>Sipariş No</th>
                                    <th>Mağaza</th>
                                    <th>Müşteri Adı</th>
                                    <th>Kargo Takip No</th>
                                    <th>Durum</th>
                                    <th>Tutar</th>
                                    <th style="width: 200px; min-width: 200px;">Ürünler</th>
                                    <th>ZplBarcode</th>
                                    <th>Yaz</th>
                                    <th>Kargo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                    <tr>
                                        <td>
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
                                        <td>{{ $order->order_id }}</td>
                                        <td>{{ $order->store?->merchant_name ?? '-' }}</td>
                                        <td>{{ $order->customer_name }}</td>
                                        <td>{{ $order->cargo_tracking_number }}</td>
                                        <td>{{ \App\Models\Order::TYPES[$order->status] ?? $order->status }}</td>
                                        <td>{{ number_format($order->total_price, 2) }} TL</td>
                                        <td style="min-width: 200px; width: 200px;">
                                            <div style="display: flex; flex-wrap: wrap; gap: 1px; width: 100%; align-items: flex-start;">
                                                @foreach($order->orderProducts as $orderProduct)
                                                    @php $product = $orderProduct->product; @endphp
                                                    @if($product)
                                                        <div class="position-relative" style="width: 45px; height: 45px; flex-shrink: 0;">
                                                            <img src="{{ $product->image_url }}" alt="{{ $product->title }}" 
                                                                 style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                                            <span class="position-absolute top-0 end-0 badge bg-primary rounded-circle" 
                                                                  style="transform: translate(25%, -25%); font-size: 0.6rem; min-width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;">
                                                                {{ $orderProduct->quantity}}
                                                            </span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                            title="{{ $order->zpl_barcode }}">
                                            {{ $order->zpl_barcode }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $order->zpl_print_count ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <form method="POST" action="{{ route('shipments.single-update', $order) }}" class="d-flex align-items-center gap-2">
                                                    @csrf
                                                    <select name="cargo_service_provider" class="form-select form-select-sm" style="width:auto;">
                                                        @foreach($cargoProviders as $provider)
                                                            <option value="{{ $provider }}" {{ $order->cargo_service_provider == $provider ? 'selected' : '' }}>{{ $provider }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-primary">Güncelle</button>
                                                </form>
                                                
                                                @if($order->cargo_service_provider === 'Kolay Gelsin Marketplace')
                                                    <form method="POST" action="{{ route('shipments.generate-zpl', $order) }}" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="ZPL Barcode Oluştur">
                                                            <i class="fas fa-barcode"></i> ZPL
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
                        {{ $orders->links('pagination::bootstrap-5') }}
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
    const zplBtn = document.getElementById('zplPrintBtn');
    const pdfBtn = document.getElementById('pdfPrintBtn');
    
    if (checkedBoxes.length > 0) {
        zplBtn.disabled = false;
        pdfBtn.disabled = false;
    } else {
        zplBtn.disabled = true;
        pdfBtn.disabled = true;
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
        alert('Lütfen yazdırmak istediğiniz siparişleri seçin.');
        return;
    }
    
    let zplData = '';
    const orderIds = [];
    
    checkedBoxes.forEach(checkbox => {
        const zpl = checkbox.getAttribute('data-zpl');
        if (zpl && zpl.trim()) {
            zplData += zpl + '\n\n';
            orderIds.push(checkbox.value);
        }
    });
    
    if (!zplData.trim()) {
        alert('Seçilen siparişlerin ZPL verileri bulunamadı.');
        return;
    }
    
    // ZPL verisini yeni pencerede göster ve yazdır
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>ZPL Yazdırma</title>
            <style>
                body { font-family: monospace; white-space: pre-wrap; margin: 20px; }
                .print-info { background: #f8f9fa; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
                .zpl-content { background: #f5f5f5; padding: 15px; border: 1px solid #ddd; }
                @media print { .print-info { display: none; } }
            </style>
        </head>
        <body>
            <div class="print-info">
                <strong>ZPL Yazdırma</strong><br>
                Toplam ${checkedBoxes.length} adet etiket<br>
                <button onclick="window.print()">Yazdır</button>
                <button onclick="window.close()">Kapat</button>
            </div>
            <div class="zpl-content">${zplData}</div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Print işlemi tamamlandığında print count'u arttır
    printWindow.addEventListener('afterprint', function() {
        incrementPrintCount(orderIds);
    });
    
    // Biraz bekleyip otomatik yazdır
    setTimeout(() => {
        printWindow.print();
    }, 500);
}

function printPDF() {
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Lütfen yazdırmak istediğiniz siparişleri seçin.');
        return;
    }
    
    let htmlContent = '';
    const orderIds = [];
    
    checkedBoxes.forEach((checkbox, index) => {
        const orderId = checkbox.getAttribute('data-order-id');
        const customer = checkbox.getAttribute('data-customer');
        const address = checkbox.getAttribute('data-address');
        const tracking = checkbox.getAttribute('data-tracking');
        const cargo = checkbox.getAttribute('data-cargo');
        
        orderIds.push(checkbox.value);
        
        htmlContent += `
            <div class="label" style="page-break-after: always; border: 2px solid #000; width: 400px; margin: 20px auto; padding: 15px; font-family: Arial;">
                <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px;">
                    <strong>KARGO ETİKETİ</strong>
                </div>
                
                <div style="margin-bottom: 10px;">
                    <strong>Sipariş No:</strong> ${orderId}<br>
                    <strong>Kargo:</strong> ${cargo}<br>
                    <strong>Takip No:</strong> ${tracking}
                </div>
                
                <div style="border: 1px solid #000; padding: 10px; margin: 10px 0;">
                    <strong>ALICI</strong><br>
                    <strong>${customer}</strong><br>
                    ${address}
                </div>
                
                <div style="text-align: center; margin-top: 15px;">
                    <div style="font-family: 'Courier New', monospace; font-size: 24px; letter-spacing: 2px;">
                        ||||| ||| |||||
                    </div>
                    <div style="font-size: 12px; margin-top: 5px;">
                        ${tracking}
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 10px; font-size: 12px;">
                    Tarih: ${new Date().toLocaleDateString('tr-TR')}
                </div>
            </div>
        `;
        
        if (index < checkedBoxes.length - 1) {
            htmlContent += '<div style="page-break-before: always;"></div>';
        }
    });
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Kargo Etiketleri - PDF</title>
            <style>
                body { margin: 0; padding: 20px; }
                .label { page-break-after: always; }
                .label:last-child { page-break-after: auto; }
                @media print { 
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="no-print" style="text-align: center; margin-bottom: 20px;">
                <button onclick="window.print()">PDF Yazdır</button>
                <button onclick="window.close()">Kapat</button>
                <hr>
            </div>
            ${htmlContent}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Print işlemi tamamlandığında print count'u arttır
    printWindow.addEventListener('afterprint', function() {
        incrementPrintCount(orderIds);
    });
    
    // Biraz bekleyip otomatik yazdır
    setTimeout(() => {
        printWindow.print();
    }, 500);
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

// Initialize button states on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePrintButtons();
});
</script>
@endsection 