@extends('layouts.app')
@section('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap4.min.css">
    <style>
        .video-preview {
            max-width: 200px;
            max-height: 150px;
            cursor: pointer;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .filter-section .form-group {
            margin-bottom: 10px;
        }
        .dataTables_wrapper .dataTables_filter {
            float: right;
            margin-bottom: 15px;
        }
        .dataTables_wrapper .dataTables_length {
            float: left;
            margin-bottom: 15px;
        }
        .btn-group-sm > .btn, .btn-sm {
            padding: .25rem .5rem;
            font-size: .875rem;
            line-height: 1.5;
            border-radius: .2rem;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .modal-xl {
            max-width: 90%;
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="filter-section">
                    <form id="filterForm" class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="dateRange">Tarih Aralığı</label>
                                <input type="text" class="form-control" id="dateRange" name="date_range">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="orderStatus">Sipariş Durumu</label>
                                <select class="form-control" id="orderStatus" name="status">
                                    <option value="">Tümü</option>
                                    <option value="Created">Oluşturuldu</option>
                                    <option value="Picking">Hazırlanıyor</option>
                                    <option value="Invoiced">Faturalandı</option>
                                    <option value="Shipped">Kargoya Verildi</option>
                                    <option value="Delivered">Teslim Edildi</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="hasVideo">Video Durumu</label>
                                <select class="form-control" id="hasVideo" name="has_video">
                                    <option value="">Tümü</option>
                                    <option value="1">Videolu</option>
                                    <option value="0">Videosuz</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">Filtrele</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @if(request('only_videos') == '1')
                            <!-- Laravel Pagination için basit tablo -->
                            <table class="table table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Sipariş No</th>
                                        <th>Müşteri</th>
                                        <th>Kargo No</th>
                                        <th>Tarih</th>
                                        <th>Durum</th>
                                        <th>Video</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orders as $order)
                                        <tr>
                                            <td>{{ $order->id }}</td>
                                            <td>{{ $order->order_id }}</td>
                                            <td>{{ $order->customer_name }}</td>
                                            <td>{{ $order->cargo_tracking_number }}</td>
                                            <td>{{ $order->order_date }}</td>
                                            <td>
                                                <span class="badge bg-{{ $order->status == 'Delivered' ? 'success' : 'info' }}">
                                                    {{ $order->status }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($order->media->count() > 0)
                                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#videoModal{{$order->id}}">
                                                        <i class="fas fa-video"></i> {{$order->media->count()}}
                                                    </button>
                                                @else
                                                    <span class="badge bg-secondary">Video Yok</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#orderDetailModal{{$order->id}}">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="{{ $order->media->first()?->getUrl() }}" download class="btn btn-primary">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            
                            <!-- Bootstrap 5 Pagination -->
                            <div class="d-flex justify-content-center mt-4">
                                {{ $orders->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        @else
                            <!-- DataTable için mevcut yapı -->
                            <table id="ordersTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Sipariş No</th>
                                        <th>Müşteri</th>
                                        <th>Kargo No</th>
                                        <th>Tarih</th>
                                        <th>Durum</th>
                                        <th>Video</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orders as $order)
                                        <tr>
                                            <td>{{ $order->id }}</td>
                                            <td>{{ $order->order_id }}</td>
                                            <td>{{ $order->customer_name }}</td>
                                            <td>{{ $order->cargo_tracking_number }}</td>
                                            <td>{{ $order->order_date }}</td>
                                            <td>
                                                <span class="badge badge-{{ $order->status == 'Delivered' ? 'success' : 'info' }}">
                                                    {{ $order->status }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($order->media->count() > 0)
                                                    <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#videoModal{{$order->id}}">
                                                        <i class="fas fa-video"></i> {{$order->media->count()}}
                                                    </button>
                                                @else
                                                    <span class="badge badge-secondary">Video Yok</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#orderDetailModal{{$order->id}}">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="{{ $order->media->first()?->getUrl() }}" download class="btn btn-primary">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Modals -->
    @foreach($orders as $order)
        @if($order->media->count() > 0)
            <div class="modal fade" id="videoModal{{$order->id}}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Sipariş Videoları - #{{$order->order_id}}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                @foreach($order->media as $media)
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <video class="card-img-top" controls>
                                                <source src="{{ $media->getUrl() }}" type="video/mp4">
                                            </video>
                                            <div class="card-body">
                                                <a href="{{ $media->getUrl() }}" download class="btn btn-primary btn-sm">
                                                    <i class="fas fa-download"></i> İndir
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endsection

@section('scripts')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    <script>
        $(document).ready(function() {
            @if(request('only_videos') != '1')
            // Initialize DataTable - sadece video modu değilse
            var table = $('#ordersTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json'
                },
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'excel', 'pdf', 'print'
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tümü"]]
            });

            // Filter form submit
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                var dateRange = $('#dateRange').val();
                var status = $('#orderStatus').val();
                var hasVideo = $('#hasVideo').val();

                // Custom filtering logic
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    var row = table.row(dataIndex).data();
                    var rowDate = new Date(row[4]);
                    var rowStatus = row[5];
                    var rowHasVideo = row[6].includes('Video Yok') ? '0' : '1';

                    if (status && rowStatus !== status) return false;
                    if (hasVideo && rowHasVideo !== hasVideo) return false;
                    if (dateRange) {
                        var dates = dateRange.split(' - ');
                        var startDate = new Date(dates[0]);
                        var endDate = new Date(dates[1]);
                        if (rowDate < startDate || rowDate > endDate) return false;
                    }
                    return true;
                });

                table.draw();
            });

            // Reset filters
            $('#resetFilters').on('click', function() {
                $('#filterForm')[0].reset();
                $('#dateRange').val('');
                $.fn.dataTable.ext.search.pop();
                table.draw();
            });
            @endif

            // Initialize DateRangePicker
            $('#dateRange').daterangepicker({
                locale: {
                    format: 'DD/MM/YYYY',
                    applyLabel: 'Uygula',
                    cancelLabel: 'İptal',
                    fromLabel: 'Dan',
                    toLabel: 'a',
                    customRangeLabel: 'Özel',
                    daysOfWeek: ['Pz', 'Pt', 'Sa', 'Ça', 'Pe', 'Cu', 'Ct'],
                    monthNames: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık']
                }
            });
        });
    </script>
@endsection
