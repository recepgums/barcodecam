@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Kargo Kuralını Düzenle</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('shipments.rules.update', $rule) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label class="form-label">Mağaza</label>
                            <select name="store_id" class="form-select" required>
                                <option value="">Seçiniz</option>
                                @foreach(auth()->user()->stores as $store)
                                    <option value="{{ $store->id }}" {{ $rule->store_id == $store->id ? 'selected' : '' }}>
                                        {{ $store->merchant_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Kaynak Kargo Firması</label>
                            <select name="from_cargo" class="form-select" required>
                                <option value="">Seçiniz</option>
                                @foreach(\App\Models\CargoRule::CARGO_PROVIDERS as $key => $provider)
                                    <option value="{{ $key }}" {{ $rule->from_cargo == $key ? 'selected' : '' }}>
                                        {{ $provider }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Hedef Kargo Firması</label>
                            <select name="to_cargo" class="form-select" required>
                                <option value="">Seçiniz</option>
                                @foreach(\App\Models\CargoRule::CARGO_PROVIDERS as $key => $provider)
                                    <option value="{{ $key }}" {{ $rule->to_cargo == $key ? 'selected' : '' }}>
                                        {{ $provider }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Hariç Tutulacak Barkodlar</label>
                            <input type="text" 
                                   name="exclude_barcodes" 
                                   class="form-control" 
                                   value="{{ $rule->exclude_barcodes }}"
                                   placeholder="Barkodları virgül ile ayırın">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dahil Edilecek Barkodlar</label>
                            <input type="text" 
                                   name="include_barcodes" 
                                   class="form-control" 
                                   value="{{ $rule->include_barcodes }}"
                                   placeholder="Barkodları virgül ile ayırın">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('shipments.rules.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Geri
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Güncelle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 