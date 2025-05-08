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
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Kargo Kuralı Geçmişi</h5>
                </div>
                <div class="card-body">
                    @if($cargoRules->count() === 0)
                        <div class="alert alert-info">Henüz kural eklenmedi.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Kullanıcı</th>
                                        <th>Mağaza</th>
                                        <th>Kaynak Kargo</th>
                                        <th>Hedef Kargo</th>
                                        <th>Hariç Barkodlar</th>
                                        <th>Durum</th>
                                        <th>Sonuç</th>
                                        <th>Uygulama Zamanı</th>
                                        <th>Oluşturulma</th>
                                        <th>Kargo Firması</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cargoRules as $rule)
                                        <tr>
                                            <td>{{ $rule->id }}</td>
                                            <td>{{ $rule->user?->name ?? '-' }}</td>
                                            <td>{{ $rule->store?->name ?? '-' }}</td>
                                            <td>{{ \App\Models\CargoRule::CARGO_PROVIDERS[$rule->from_cargo] ?? '-' }}</td>
                                            <td>{{ \App\Models\CargoRule::CARGO_PROVIDERS[$rule->to_cargo] ?? '-' }}</td>
                                            <td>{{ $rule->exclude_barcodes }}</td>
                                            <td>
                                                @if($rule->status === 'executed')
                                                    <span class="badge bg-success">Başarılı</span>
                                                @elseif($rule->status === 'failed')
                                                    <span class="badge bg-danger">Hatalı</span>
                                                @else
                                                    <span class="badge bg-secondary">Bekliyor</span>
                                                @endif
                                            </td>
                                            <td style="max-width:200px; word-break:break-all;">{{ $rule->result }}</td>
                                            <td>{{ $rule->executed_at ? $rule->executed_at->format('Y-m-d H:i') : '-' }}</td>
                                            <td>{{ $rule->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('shipments.rules.edit', $rule) }}" 
                                                       class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i> Düzenle
                                                    </a>
                                                    <form action="{{ route('shipments.rules.destroy', $rule) }}" 
                                                          method="POST" 
                                                          onsubmit="return confirm('Bu kuralı silmek istediğinizden emin misiniz?')"
                                                          style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i> Sil
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-center mt-3">
                                {{ $cargoRules->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- Dinamik Kural Ekleme Formu Başlangıcı -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Kargo Kuralı Ekle</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('shipments.rules.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Mağaza</label>
                                <select name="store_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    @foreach(auth()->user()->stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->merchant_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kaynak Kargo Firması</label>
                                <select name="from_cargo" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    @foreach(\App\Models\CargoRule::CARGO_PROVIDERS as $key => $provider)
                                        <option value="{{ $key }}">{{ $provider }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Hedef Kargo Firması</label>
                                <select name="to_cargo" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    @foreach(\App\Models\CargoRule::CARGO_PROVIDERS as $key => $provider)
                                        <option value="{{ $key }}">{{ $provider }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Hariç Tutulacak Barkodlar</label>
                                <input type="text" name="exclude_barcodes" class="form-control" placeholder="Barkodları virgül ile ayırın">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">Kuralı Ekle</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Dinamik Kural Ekleme Formu Sonu -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <form method="GET" action="" class="card card-body shadow-sm mb-4">
                <div class="row g-2 align-items-end">
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
                    <div class="col-md-2 mt-2">
                        <button type="submit" class="btn btn-primary w-100">Filtrele</button>
                    </div>
                    <div class="col-md-2 mt-2">
                        <a href="{{ route('shipments.index') }}" class="btn btn-secondary w-100">Temizle</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Kargo Listesi</div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Sipariş No</th>
                                    <th>Müşteri Adı</th>
                                    <th>Adres</th>
                                    <th>Kargo Takip No</th>
                                    <th>Durum</th>
                                    <th>Toplam Tutar</th>
                                    <th>Ürünler</th>
                                    <th>Kargo Firması</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                    <tr>
                                        <td>{{ $order->order_id }}</td>
                                        <td>{{ $order->customer_name }}</td>
                                        <td>{{ $order->address }}</td>
                                        <td>{{ $order->cargo_tracking_number }}</td>
                                        <td>{{ \App\Models\Order::TYPES[$order->status] ?? $order->status }}</td>
                                        <td>{{ number_format($order->total_price, 2) }} TL</td>
                                        <td>
                                            @foreach($order->orderProducts as $orderProduct)
                                                @php $product = $orderProduct->product; @endphp
                                                @if($product)
                                                    <div class="align-items-center">
                                                        <div class="position-relative" style="width: 50px; height: 50px; margin-right: 12px;">
                                                            <img src="{{ $product->image_url }}" alt="{{ $product->title }}" 
                                                                 style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                                                            <span class="position-absolute top-0 end-0 badge bg-primary rounded-circle" 
                                                                  style="transform: translate(25%, -25%); font-size: 0.75rem;">
                                                                {{ $orderProduct->quantity}}
                                                            </span>
                                                        </div>
                                                        <br>
                                                        <div class="text-truncate bg-white" style="max-width: 200px;" title="{{ $product->title }}">
                                                          <small>{{ $product->title }}</small>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </td>
                                        <td>
                                        <form method="POST" action="{{ route('shipments.single-update', $order) }}" class="d-flex align-items-center gap-2">
                                                @csrf
                                                <select name="cargo_service_provider" class="form-select form-select-sm" style="width:auto;">
                                                    @foreach($cargoProviders as $provider)
                                                        <option value="{{ $provider }}" {{ $order->cargo_service_provider == $provider ? 'selected' : '' }}>{{ $provider }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary">Güncelle</button>
                                            </form>
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
    <hr class="my-5">
</div>
@endsection 