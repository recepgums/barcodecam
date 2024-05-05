@extends('layouts.app')
@section('styles')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.6/css/dataTables.dataTables.css" />

    <script src="https://cdn.datatables.net/2.0.6/js/dataTables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quagga/dist/quagga.min.js"></script>
    <style>
        #orders-table tbody tr {
            vertical-align: middle;
        }
    </style>
@endsection
@section('content')
    <div class="container">
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
            @foreach($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td>{{ $order->order_id }}</td>
                    <td>{{ $order->cargo_tracking_number }}</td>
                    <td style="max-width: 200px;text-align: center">
                        @foreach(json_decode($order->lines) as $line)
                            <div class="card mb-3" style="max-width: 300px;">
                                <div class="row g-0">
                                    <div class="col-md-12">
                                        <p class="card-text" style="color: red; font-size: 14px;">X {{$line->quantity}}</p>

                                    </div>
                                    <div class="col-md-12">
                                        <div class="card-body">
                                            <h5 class="card-title" style="font-size: 14px; overflow-wrap: break-word;">{{$line->productName}}</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </td>
                    <td>
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#productModalOrder{{$order->id}}">
                            {{$order->media->count()}} Video
                        </button>
                        <div class="modal fade" id="productModalOrder{{$order->id}}" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="productModalLabel">{{$order->media->count()}} Video</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                      <div class="row">
                                          @foreach($order->media as $media)
                                              <div class="col-md-6">
                                                  <video id="videoPlayer" controls width="100%">
                                                      <source src="{{ $media->getUrl() }}" type="video/mp4">
                                                      Your browser does not support the video tag.
                                                  </video>

                                                  <a href="{{ $media->getUrl() }}" download class="btn btn-primary">Videoyu indir</a>
                                              </div>
                                          @endforeach
                                      </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let table = new DataTable('#orders-table', {
        });
    </script>
@endsection
