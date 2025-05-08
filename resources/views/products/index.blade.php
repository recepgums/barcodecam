@extends('layouts.app')
@section('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jQuery-QueryBuilder/dist/css/query-builder.default.min.css"/>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script src="https://unpkg.com/@ag-grid-enterprise/all-modules@25.1.0/dist/ag-grid-enterprise.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/element-ui/lib/theme-chalk/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endsection
@section('content')
    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <!-- Filtreleme Formu -->
        <div class="row justify-content-center mb-4">
            <div class="col-md-12">
                <form method="GET" action="" class="card card-body shadow-sm mb-4">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">Mağaza</label>
                            <select name="store_id" class="form-select">
                                <option value="">Tümü</option>
                                @foreach(auth()->user()->stores as $store)
                                    <option value="{{ $store->id }}" @if(request('store_id') == $store->id) selected @endif>
                                        {{ $store->merchant_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ürün Adı</label>
                            <input type="text" name="title" value="{{ request('title') }}" class="form-control" placeholder="Ürün adı">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Barkod</label>
                            <input type="text" name="barcode" value="{{ request('barcode') }}" class="form-control" placeholder="Barkod">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Min Fiyat</label>
                            <input type="number" name="min_price" value="{{ request('min_price') }}" class="form-control" placeholder="Min fiyat">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Max Fiyat</label>
                            <input type="number" name="max_price" value="{{ request('max_price') }}" class="form-control" placeholder="Max fiyat">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Stok Durumu</label>
                            <select name="stock_status" class="form-select">
                                <option value="">Tümü</option>
                                <option value="in_stock" @if(request('stock_status') == 'in_stock') selected @endif>Stokta Var</option>
                                <option value="out_of_stock" @if(request('stock_status') == 'out_of_stock') selected @endif>Stokta Yok</option>
                            </select>
                        </div>
                        <div class="col-md-2 mt-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrele</button>
                        </div>
                        <div class="col-md-2 mt-2">
                            <a href="{{ route('products.index') }}" class="btn btn-secondary w-100">Temizle</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ürünler Tablosu -->
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Ürün Listesi</h5>
                        <span class="badge bg-primary">{{ $products->total() }} Ürün</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 80px">Görsel</th>
                                        <th>Ürün Adı</th>
                                        <th>Barkod</th>
                                        <th>Fiyat</th>
                                        <th>Stok</th>
                                        <th>Mağaza</th>
                                        <th>Sipariş Sayısı</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                        <tr>
                                            <td>
                                                <div class="position-relative" style="width: 60px; height: 60px;">
                                                    <img src="{{ $product->image_url }}" 
                                                         alt="{{ $product->title }}" 
                                                         class="img-thumbnail"
                                                         style="width: 100%; height: 100%; object-fit: cover;">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-truncate" style="max-width: 200px;" title="{{ $product->title }}">
                                                        {{ $product->title }}
                                                    </span>
                                                    <small class="text-muted">
                                                        <a href="{{ $product->productUrl }}" target="_blank" class="text-decoration-none">
                                                            <i class="fas fa-external-link-alt"></i> Trendyol'da Gör
                                                        </a>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">{{ $product->barcode }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold">{{ number_format($product->price, 2) }} TL</span>
                                            </td>
                                            <td>
                                                @if($product->quantity > 0)
                                                    <span class="badge bg-success">{{ $product->quantity }}</span>
                                                @else
                                                    <span class="badge bg-danger">Stokta Yok</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $product->store->merchant_name }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $product->order_count ?? 0 }}</span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="" 
                                                       class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                  
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-4">
                            {{ $products->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .table > :not(caption) > * > * {
        padding: 1rem;
    }
    .badge {
        font-size: 0.85em;
    }
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
    }
    </style>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const localMaxWidth = 250;
            const localMinWidth = 75;
            const columnDefs = [
                { field: 'id', sortable: true, pivot: true, resizable: true, minWidth: localMinWidth, maxWidth: 50 },
                { field: 'title', sortable: true, filter: 'agSetColumnFilter', pivot: true, resizable: true, minWidth: localMinWidth, maxWidth: localMaxWidth },
                { field: 'barcode', sortable: true, filter: 'agSetColumnFilter', pivot: true, resizable: true, minWidth: localMinWidth, maxWidth: localMaxWidth },
                { field: 'price', sortable: true, filter: 'agSetColumnFilter', pivot: true, resizable: true, minWidth: localMinWidth, maxWidth: localMaxWidth },
                { headerName: 'Video', field: 'media', cellRenderer: videoCellRenderer }
            ];

            let rowData = @json($products);

            const gridOptions = {
                rowData: rowData,
                columnDefs: columnDefs,
                rowClass: 'textCenter',
                resizable: true,
                floatingFilter: true,
                flex: 1,
                rowSelection: 'multiple',
                rowMultiSelectWithClick: true,
                rowHeight: 45,
                defaultColDef: {
                    flex: 1,
                    filter: true,
                    editable: true,
                    sortable: true,
                    resizable: true,
                    groupSelectsChildren: true,
                },
                animateRows: true,
                rowDragManaged: true,
                onFirstDataRendered: onFirstDataRendered,
            };

            const gridDiv = document.querySelector('#myGrid');
            new agGrid.Grid(gridDiv, gridOptions);

            function onFirstDataRendered(params) {
                params.api.sizeColumnsToFit();
            }

            function videoCellRenderer(params) {
                if (!params.value || params.value.length === 0) {
                    return '<div>No videos found</div>';
                }
                const count = params.value.length;
                const modalId = `productModalOrder${params.data.id}`;
                const videosHtml = params.value.map(media => `
                    <div class="col-md-6">
                        <video id="videoPlayer" controls width="100%">
                            <source src="${media.url}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        <a href="${media.url}" download class="btn btn-primary">Videoyu indir</a>
                    </div>
                `).join('');
                return `
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#${modalId}">
                        ${count} Video
                    </button>
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">${count} Video</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">${videosHtml}</div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
@endsection
