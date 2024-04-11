@extends('layouts.app')
@section('styles')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/quagga/dist/quagga.min.js"></script>
    <style>
        .card-button {
            background-color: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            padding: 20px;
            transition: all 0.3s; /* Smooth transition for all properties */
            height: 250px; /* Fixed height for both cards */
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center; /* Center-align text */
        }

        .card-button:hover {
            transform: translateY(-5px); /* Move the card up slightly */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1); /* Increase shadow on hover */
        }

        .card-button img {
            max-width: 100%; /* Ensure image fits within the container */
            max-height: 80%; /* Limit image height to 80% of container height */
            margin: auto; /* Center the image horizontally and vertically */
        }

        .card-button p {
            margin-top: 10px;
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .modal-header .close {
            position: absolute;
            right: 15px; /* Adjust as needed */
            top: 15px; /* Adjust as needed */
            padding: 0;
            border: 0px;
            margin: 0;
        }

        .loader {
            border: 16px solid #f3f3f3; /* Light grey */
            border-top: 16px solid #3498db; /* Blue */
            border-radius: 50%;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

    </style>
@endsection
@section('content')
    <div class="container">
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger" role="alert">
                    {{ session('error') }}
                </div>
            @endif
        </div>

        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Trendyol Mağaza bilgileri</div>
                    <div class="m-3 row">
                        <span>İşlem yapılacak mağaza:</span>
                        <br><br>
                        <div class="col-sm-8">
                            <select name="" id="" onchange="selectedStoreChanged(this)" class="form-control">
                                @foreach($stores as $store)
                                    <option @if($store->is_default) selected @endif value="{{$store->id}}">
                                        {{$store->merchant_name}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <button type="button" class="btn btn-primary" data-toggle="modal"
                                    data-target="#storeFormModal">
                                + Yeni Mağaza Oluştur
                            </button>
                        </div>
                    </div>

                    <div id="storeFormModal" class="modal">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form action="{{ route('user.account-information-store') }}" method="post">
                                        @csrf
                                        <div class="form-group">
                                            <label for="supplier_id">Mağaza Adı:</label>
                                            <input type="text" id="merchant_name" name="merchant_name"
                                                   class="form-control"
                                                   required>
                                        </div>
                                        <div class="form-group">
                                            <label for="supplier_id">Satıcı ID:</label>
                                            <input type="number" id="supplier_id" name="supplier_id"
                                                   class="form-control"
                                                   required>
                                        </div>
                                        <div class="form-group">
                                            <label for="token">Token:</label>
                                            <input type="text" id="token" name="token" class="form-control" required>
                                        </div>
                                        <br>

                                        <button type="submit" class="btn btn-success float-end">Oluştur</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <br><br>
                <div class="card">
                    <div class="card-header">Sipariş Çekme</div>

                    <div class="card-body">
                        <form action="{{route('order.getOrders')}}" method="post">
                            @csrf
                            @isset($orderFetchDate)
                                <p> Siparişleri son çekiş tarihi : {{$orderFetchDate}}</p>
                                <p>Sistemde bulunan sipariş : {{$orderCount}}</p>
                            @endisset

                            <div class="row">
                                <div class="col-sm-8">
                                    <select name="order_status" class="form-control">
                                        <option value="">Hepsi</option>
                                        @foreach(App\Models\Order::TYPES as $key => $type)
                                            <option @if($type == \App\Models\Order::TYPES['Created']) selected @endif
                                            value="{{$key}}">
                                                {{$type}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <button type="submit" class="btn btn-primary">Siparişleri Çek</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <br><br>
                <div class="card">
                    <div class="card-header">Barkod Okutma</div>

                    <div class="container pt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card-button" data-toggle="modal" data-target="#webcamModal">
                                    <img src="{{asset('images/webcam.png')}}" alt="Image 1">
                                    <p>Webcam</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card-button" data-toggle="modal" data-target="#barcodeModal">
                                    <img src="{{asset('images/barkod_okuyucu.png')}}" alt="Image 2">
                                    <p>Barcode</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modals -->
                    <div class="modal fade" id="webcamModal" tabindex="-1" role="dialog"
                         aria-labelledby="webcamModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="webcamModalLabel">Webcam Barkod Okutma</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p>Tercihen otomatik olarak bir sonraki siparişe geçmesi için süreleri
                                        ayarlayabilirsiniz</p>
                                    <p>Girdiğiniz süre sonunda video bitecek ve yeni sipariş için barkod okutmaya hazır
                                        hale gelecek</p>

                                    <!-- Countdown input -->
                                    <div class="form-group">
                                        <label for="countdownInput">Video uzunluğu (saniye):</label>
                                        <input type="number" class="form-control" id="countdownInput"
                                               placeholder="Saniye girin">
                                    </div>

                                    <button type="submit" class="btn btn-primary" id="startButton">Başlat</button>
                                    <button type="submit" class="btn btn-danger d-none" id="finishButton">Bitir</button>
                                    <br>
                                    <div class="row text-center">
                                        <div class="col-sm-6">
                                            <div id="scanner-container"></div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div id="html-result"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="barcodeModal" tabindex="-1" role="dialog"
                         aria-labelledby="barcodeModalLabel" aria-hidden="true">
                        <div class="modal-dialog  modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="barcodeModalLabel">Barkod Okutma</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p>Barkodu okuttuğunuz anda video kaydı başlayacaktır</p>
                                    <input type="text" name="barcode" class="form-control" id="barcodeInput">
                                    <div class="order-summary"></div>
                                </div>
                                <div class="modal-footer">
                                    <div class="container">
                                        <div class="row justify-content-center mb-3">
                                            <div class="col-md-4"></div>
                                            <div class="col-md-4">
                                                <button class="btn btn-success" id="countdownButton"
                                                        style="display: none">Video başlatılıyor
                                                </button>

                                                <button class="btn btn-danger" id="cancelCountdownButton"
                                                        style="display: none">Bekle
                                                </button>
                                                <button class="btn btn-danger" id="finishVideoButton"
                                                        style="display: none">Videoyu bitir
                                                </button>
                                            </div>
                                            <div class="col-md-4"></div>
                                        </div>
                                        <div class="row text-center">
                                            <video style="display: none" id="videoElement" width="640" height="480" autoplay></video>
                                            <div id="loadingDiv" class="d-none text-center">
                                                <div class="loader"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body text-center">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif
                        <div id="results-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cameraPermissionModal" tabindex="-1" role="dialog"
         aria-labelledby="cameraPermissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cameraPermissionModalLabel">Kamera erişimi yetkisi gerekli</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Barkod okutmak ve video çekmek için kamera erişimine izin vermeniz gerekiyor.
                    <br><br>
                    Tarayıcınızın sol üst kısmındaki güvenlik alanından kameraya izin verebilirsiniz
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="grantCameraPermissionButton">Tamam</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')

    <script>
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector('#scanner-container')
            },
            decoder: {
                readers: ["code_128_reader"]
            }
        }, function (err) {
            if (err) {
                console.log(err);
                return
            }
            console.log("Initialization finished. Ready to start");
            Quagga.start();
        });

        document.getElementById('startButton').addEventListener('click', function () {
            Quagga.start();
            $('#startButton').addClass('d-none');
            $('#finishButton').removeClass('d-none');
        });

        document.getElementById('finishButton').addEventListener('click', function () {
            $('#finishButton').addClass('d-none');
            $('#startButton').removeClass('d-none');
        });
        var isRequestSent = false;

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
                        $('.order-summary').html(response?.view);
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                    },
                    complete: function () {
                        setTimeout(function () {
                            isRequestSent = false;
                        }, 2000);
                    }
                });
                document.getElementById('results-container').innerHTML = 'Barcode okundu: ' + code;
            }
        });


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

    <script>
        var countdownDuration = 5;
        var countdownInterval;
        var secondsLeft = countdownDuration;

        function startCountdown() {
            updateCountdownLabel(secondsLeft);
            countdownInterval = setInterval(function () {
                secondsLeft--;
                updateCountdownLabel(secondsLeft);
                if (secondsLeft <= 0) {
                    clearInterval(countdownInterval);
                    startVideoRecording()
                }
            }, 1000);
        }

        function updateCountdownLabel(seconds) {
            $('#countdownButton').text('Video başlatılıyor (' + seconds + ')');
        }
        function resetModal() {
            $('#barcodeInput').val('')
            $('#countdownInput').val('')
            $('.order-summary').html('')
            clearInterval(countdownInterval);
            $('#countdownButton').hide();
            $('#cancelCountdownButton').hide();
            $('#finishVideoButton').hide();
            $('#videoElement').hide();
            secondsLeft = 5
        }

        function startVideoRecording(orderId) {
            $('#countdownButton').hide();
            $('#cancelCountdownButton').hide();
            $('#finishVideoButton').show();
            $('#videoElement').show();

            navigator.mediaDevices.getUserMedia({video: true})
                .then(function (stream) {
                    var videoElement = document.getElementById('videoElement');
                    videoElement.srcObject = stream;

                    var mediaRecorder = new MediaRecorder(stream);
                    var chunks = [];

                    mediaRecorder.ondataavailable = function (event) {
                        chunks.push(event.data);
                    };

                    mediaRecorder.onstop = function () {
                        var blob = new Blob(chunks, {type: 'video/webm'});
                        sendVideoToBackend(blob,orderId);
                    };

                    mediaRecorder.start();

                    $('#finishVideoButton').click(function () {
                        mediaRecorder.stop();
                    });
                })
                .catch(function (error) {
                    console.error('Error accessing user media:', error);
                });
        }

        function sendVideoToBackend(blob, orderId) {
            var formData = new FormData();
            formData.append('video', blob);

            let storeVideoUrl = '{{ route("order.storeVideo", ":orderId") }}';
            storeVideoUrl = storeVideoUrl.replace(':orderId', orderId);
            var csrfToken = `{{csrf_token()}}`;

            // display loading
            $('#loadingDiv').removeClass('d-none');
            $('#videoElement').hide();

            $.ajax({
                url: storeVideoUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    console.log('Video successfully sent to backend:', response);
                    // hide loading
                    $('#loadingDiv').addClass('d-none');
                },
                error: function(xhr, status, error) {
                    // hide loading
                    $('#loadingDiv').addClass('d-none');
                    console.error('Error sending video to backend:', error);
                }
            });
        }

        $('#webcamModal').on('show.bs.modal', function (e) {

        });

        var focusInterval;
        var isRequestSent = false;

        $('#barcodeModal').on('show.bs.modal', function (e) {
            $('#barcodeInput').focus();

            focusInterval = setInterval(function () {
                // $('#barcodeInput').focus();
            }, 3000);
        }).on('hide.bs.modal', function (e) {
            clearInterval(focusInterval);
            resetModal()
        });

        $('#barcodeInput').on('input', function () {
            if (!isRequestSent) {
                isRequestSent = true;

                var barcodeValue = $(this).val();
                console.log("Barcode detected and read: " + barcodeValue);

                // Send the AJAX request
                $.ajax({
                    url: '{{ route("order.getByCargoTrackId") }}',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        code: barcodeValue,
                        response_type: 'view',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        $('.order-summary').html(response?.view);

                        $('#countdownButton').show().click(function () {
                            startVideoRecording(response?.order_id)
                        });

                        $('#cancelCountdownButton').show().click(function () {
                            clearInterval(countdownInterval);
                            $('#countdownButton').text('Video kaydına başla');
                            $(this).hide();
                        });

                        startCountdown();
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                    },
                    complete: function () {
                        isRequestSent = false;
                    }
                });
            }
        });
    </script>

    <script>
        $(document).ready(function () {
            navigator.permissions.query({name: 'camera'})
                .then(function (permissionStatus) {
                    if (permissionStatus.state === 'granted') {
                    } else {
                        // $('#cameraPermissionModal').modal('show');
                    }
                });

            $('#grantCameraPermissionButton').click(function () {
                navigator.mediaDevices.getUserMedia({video: true})
                    .then(function (stream) {
                        $('#cameraPermissionModal').modal('hide');
                    })
            });
        });
    </script>

    <script>
        function selectedStoreChanged(select) {
            console.log(select, select.value)

            $.ajax({
                url: '{{ route("store.updateDefault") }}',
                type: 'POST',
                dataType: 'json',
                data: {
                    store_id: select.value,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    location.reload()
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }
    </script>

@endsection
