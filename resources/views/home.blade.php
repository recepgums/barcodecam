@extends('layouts.app')
@section('styles')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/quagga/dist/quagga.min.js"></script>
@endsection
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">

                @if (!auth()->user()->supplier_id || !auth()->user()->token || session()->has('trendyol_login'))
                    <div class="card">
                        <div class="card-header">Trendyol Kullanici bilgileri</div>

                        <div class="card-body">
                            @if (session('status'))
                                <div class="alert alert-success" role="alert">
                                    {{ session('status') }}
                                </div>
                            @endif

                            <form action="{{ route('user.account-information-store') }}" method="post">
                                @csrf

                                <div class="form-group">
                                    <label for="supplier_id">Supplier ID:</label>
                                    <input type="text" id="supplier_id" name="supplier_id" class="form-control"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="token">Token:</label>
                                    <input type="text" id="token" name="token" class="form-control" required>
                                </div>

                                <button type="submit" class="btn btn-primary">Submit</button>
                            </form>
                        </div>
                    </div>
                    <br><br>
                @endif
                <div class="card">
                    <div class="card-header">Barkod Okutma</div>

                    <div class="card-body text-center">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif
                        <form action="{{route('order.getOrders')}}" method="post">
                            @csrf
                            @isset($orderFetchDate)
                                <span> Siparişleri son çekiş tarihi : {{$orderFetchDate}}</span>
                                <br>
                            @endisset

                            <button type="submit" class="btn btn-primary">Siparişleri Çek</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Barkod Okutma</div>

                    <div class="card-body text-center">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif
                        <div id="results-container"></div>

                        <button type="submit" class="btn btn-primary" id="startButton">Başlat</button>
                            <br>
                        <div class="row">
                            <div class="col-sm-6">
                                <div id="scanner-container"></div>
                            </div>
                            <div class="col-sm-6">
                                <div id="html-result"></div>
                            </div>
                        </div>

                            <div id="myModal" class="modal">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <!-- Your modal body content goes here -->
                                            <div id="modal-content"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')

    <script>
        // Configure QuaggaJS
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector('#scanner-container')    // Selector for the container where the video should be placed
            },
            decoder: {
                readers: ["code_128_reader"]  // Specify the barcode types you want to scan
            }
        }, function (err) {
            if (err) {
                console.log(err);
                return
            }
            console.log("Initialization finished. Ready to start");
            Quagga.start();  // Start the scanner
        });

        // Add listener to the start button
        document.getElementById('startButton').addEventListener('click', function () {
            Quagga.start();
        });
        var isRequestSent = false;
        // Callback function when barcode is detected
        Quagga.onDetected(function (result) {
            if (!isRequestSent) {
                isRequestSent = true;
                var code = result.codeResult.code;
                console.log("Barcode detected and read: " + code);
                $.ajax({
                    url: '{{ route("order.getByCargoTrackId") }}',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        code: code,
                        response_type: 'view',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        console.log(response);
                        $('#modal-content').html(response?.view);
                        // Open modal
                        $('#myModal').modal('show');
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                    },
                    complete: function () {
                        // Reset flag after request is completed
                        setTimeout(function () {
                            isRequestSent = false;
                        }, 2000); // 5000 milliseconds (5 seconds) timeout
                    }
                });
                document.getElementById('results-container').innerHTML = 'Barcode okundu: ' + code;
            }
        });

        // Callback function when an error occurs during scanning
        Quagga.onProcessed(function (result) {
            var drawingCtx = Quagga.canvas.ctx.overlay,
                drawingCanvas = Quagga.canvas.dom.overlay;

            if (result) {
                if (result.boxes) {
                    drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute("width")), parseInt(drawingCanvas.getAttribute("height")));
                    result.boxes.filter(function (box) {
                        return box !== result.box;
                    }).forEach(function (box) {
                        Quagga.ImageDebug.drawPath(box, {x: 0, y: 1}, drawingCtx, {color: "green", lineWidth: 2});
                    });
                }

                if (result.box) {
                    Quagga.ImageDebug.drawPath(result.box, {x: 0, y: 1}, drawingCtx, {color: "#00F", lineWidth: 2});
                }

                if (result.codeResult && result.codeResult.code) {
                    Quagga.ImageDebug.drawPath(result.line, {x: 'x', y: 'y'}, drawingCtx, {color: 'red', lineWidth: 3});
                }
            }
        });
    </script>

@endsection
