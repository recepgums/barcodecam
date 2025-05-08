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
        <div class="mt-5" style="margin-top:20px">
            <div id="myGrid" style="height: 100vh; width:auto;overflow-x: scroll!important;" class="ag-theme-balham"></div>
        </div>
        <div id="modals"></div>

        <table id="orders-table" class="table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Müşteri Adı</th>
                <th>Sipariş Numarası</th>
                <th>Kargo Numarası</th>
                <th>Ürünler</th>
                <th>Video</th>
            </tr>
            </thead>
            <tbody>
            @foreach($products as $product)
                <tr>
                    <td>{{ $product->id }}</td>
                    <td>{{ $product->title }}</td>
                    <td>{{ $product->barcode }}</td>
                    <td>{{ $product->price }}</td>
                    <td>
                        <button type="button" class="btn btn-success">
                            Güncelle
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
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
