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
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    <!-- Dinamik Kural Ekleme Formu -->
    <div class="row justify-content-center mb-4 mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Kargo Kuralı Ekle</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('shipments.rules.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Mağaza</label>
                                <select name="store_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    @foreach(auth()->user()->stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->merchant_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Kaynak Kargo Firması</label>
                                <select name="from_cargo" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    @foreach(\App\Models\CargoRule::CARGO_PROVIDERS as $key => $provider)
                                        <option value="{{ $key }}">{{ $provider }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Hedef Kargo Firması</label>
                                <select name="to_cargo" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    @foreach(\App\Models\CargoRule::CARGO_PROVIDERS as $key => $provider)
                                        <option value="{{ $key }}">{{ $provider }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Hariç Tutulacak Barkodlar</label>
                                <input type="text" name="exclude_barcodes" class="form-control" placeholder="Barkodları virgül ile ayırın">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Dahil Edilecek Barkodlar</label>
                                <input type="text" name="include_barcodes" class="form-control" placeholder="Barkodları virgül ile ayırın">
                            </div>
                            <div class="col-md-2 pt-4">
                                <button type="submit" class="btn btn-success">Kuralı Ekle</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Kargo Kuralları Listesi -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Kargo Sağlayıcı Değiştirme Kuralları</h5>
                    <small class="mb-0">(Kargo sağlayıcısı her sipariş için 5 dakikada 1 kere değişebilir!)</small>
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
                                        <th>Mağaza</th>
                                        <th>Kaynak Kargo</th>
                                        <th>Hedef Kargo</th>
                                        <th>Hariç Barkodlar</th>
                                        <th>Dahil Barkodlar</th>
                                        <th>Durum</th>
                                        <th>Sonuç</th>
                                        <th>Uygulama Zamanı</th>
                                        <th>Oluşturulma</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cargoRules as $rule)
                                        <tr>
                                            <td>{{ $rule->id }}</td>
                                            <td>{{ $rule->store?->merchant_name ?? '-' }}</td>
                                            <td>{{ \App\Models\CargoRule::CARGO_PROVIDERS[$rule->from_cargo] ?? '-' }}</td>
                                            <td>{{ \App\Models\CargoRule::CARGO_PROVIDERS[$rule->to_cargo] ?? '-' }}</td>
                                            <td>{{ $rule->exclude_barcodes }}</td>
                                            <td>{{ $rule->include_barcodes }}</td>
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
                                                    <form action="{{ route('shipments.rules.execute', $rule) }}" 
                                                          method="POST" 
                                                          style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-play"></i> Çalıştır
                                                        </button>
                                                    </form>
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

</div>
@endsection 