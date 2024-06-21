@extends('layouts.app')
@section('styles')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <script src="https://cdn.jsdelivr.net/npm/quagga/dist/quagga.min.js"></script>
    <style>
        .drawingBuffer{
            display: none;
        }
        .toastify {
            z-index: 99999; /* Make sure it's higher than the modal (Bootstrap modals usually have z-index: 1050) */
        }
        #timer {
            font-size: 1em;
            font-weight: bold;
            color: black;
            margin: 10px;
            padding: 5px;
            border: 1px solid black;
            border-radius: 5px;
            display: inline-block;
            text-align: center;
            background-color: #f3f3f3;
        }

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
                                <p> Siparişleri son çekiş tarihi : {{auth()->user()->stores()->defaultStore()->first()->order_fetched_at ?? $orderFetchDate}}</p>
                                <p>Sistemde bulunan sipariş toplam : {{$orderCount}}</p>
                            @endisset

                            <div class="row">
                                <div class="col-sm-8">
                                    @foreach(App\Models\Order::TYPES as $key => $type)
                                        <div class="form-check">
                                            <input type="checkbox" name="status[]" class="form-check-input" id="type_{{$key}}" value="{{$key}}">
                                            <label class="form-check-label" for="type_{{$key}}">
                                                {{$type}} ({{ $statusCounts[$key] ?? 0 }}) <!-- Burada durum sayısını göster -->
                                            </label>
                                        </div>
                                    @endforeach
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
                                    <div class="row text-center">
                                        <div class="col-sm-6">
                                            <div id="scanner-container" class="row"></div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div id="html-result"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <div class="order-summary"></div>
{{--                                    <button type="button" class="btn btn-success" id="startVideoButton">Video Başlat</button>--}}
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="barcodeModal" tabindex="-1" role="dialog"
                         aria-labelledby="barcodeModalLabel" aria-hidden="true">
                        <div class="modal-dialog  modal-xl" style="max-width: 1200px;!important;" role="document">
                            <div class="modal-content">
                                <div class="modal-body">
                                    <input type="text" name="barcode" class="form-control" id="barcodeInput">
                                    <div class="row pt-2"  id="helperBarcodeImages">
                                        <div class="col-sm-3 text-center">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <button type="button" class="btn" id="deleteValueButton">
                                                        <img src="{{asset('images/barcode/temizle_barcode.png')}}">
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 text-center">
                                            <div id="camera-container"  style="display: none">
                                                <div class="row">
                                                    <div class="col-md-6 text-center">
                                                        <video id="gum" style="height: 200px;margin-top: 10px;border-radius: 20px" autoplay muted playsinline></video>
{{--                                                        <video id="recorded" style="height: 250px;width: 200px" autoplay loop playsinline></video>--}}
                                                        <div class="col-md-12">
                                                            <div id="timer">00:00</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6" id="uploadedVideoArea">

                                                    </div>
                                                    <div id="loadingDiv" class="d-none text-center">
                                                        <div class="loader"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-3 text-center">
                                            <div class="row">
                                                <strong style="text-align: center;color:#0a53be">Video Kaydını</strong>
                                                <div class="col-md-12">
                                                    <button type="button" class="btn" id="startVideoButton">
                                                        <img src="{{asset('images/barcode/baslat_barcode.png')}}">
                                                    </button>
                                                </div>
                                                <div class="col-md-12">
                                                    <button type="button" class="btn" id="stopVideoButton">
                                                        <img src="{{asset('images/barcode/kaydet_barcode.png')}}">
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="order-summary"></div>
                                </div>
                                <div class="modal-footer">
                                    Footer
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
                        $('#html-result').html(response?.view);
                        orderId = response?.order_id
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
    </script>

    <script>
        var countdownDuration = 5;
        var countdownInterval;
        var orderId = null;
        var secondsLeft = countdownDuration;
        var focusInterval;
        var isRequestSent = false;


        function resetModal() {
            $('#barcodeInput').val('')
            $('#countdownInput').val('')
            $('.order-summary').html('')
            clearInterval(countdownInterval);
            $('#finishVideoButton').hide();
            $('#camera-container').hide();
            resetTime()
            secondsLeft = 5
        }



        $('#barcodeModal').on('shown.bs.modal', function (e) {
            $('#barcodeInput').focus();

            var previousBarcodeValue = '';
            focusInterval = setInterval(function () {
                $('#barcodeInput').focus();
                var barcodeValue = $('#barcodeInput').val() ?? "";
                if (barcodeValue.toLowerCase().includes("temizle") || barcodeValue.toLowerCase().includes("temızle")) {
                    resetModal()
                    $('#barcodeInput').val('');
                    $('.order-summary').html('')
                    previousBarcodeValue = '';
                }else if(barcodeValue.toLowerCase().includes("durdur")){
                    toggleRecording(orderId,false)
                    $('#barcodeInput').val(barcodeValue.replace("durdur", ""))
                }else if(barcodeValue.toLowerCase().includes("baslat")){
                    toggleRecording(orderId,true)
                    $('#barcodeInput').val(barcodeValue.replace("baslat", ""))
                }else{
                    var parsedValue = parseInt(barcodeValue);
                    if (
                        !isNaN(parsedValue) &&
                        Number.isInteger(parsedValue) &&
                        (barcodeValue !== previousBarcodeValue) &&
                        barcodeValue.length > 12
                    ) {
                        previousBarcodeValue = barcodeValue;
                        try {
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
                                    orderId = response?.order_id;
                                    $('#camera-container').css('display', 'block');
                                    if  (response?.video_url){
                                        $('#uploadedVideoArea').html(
                                            `  <small style="color:red">Bu sipariş için daha önce bir video çekildi. Tekrar video çekerseniz üstüne yazacaktır!</small>
                                            <video style="height: 150px;border-radius: 20px" controls src="${response?.video_url}"></video>`
                                        )
                                    }
                                },
                                error: function (xhr, status, error) {
                                    console.error(xhr.responseText);
                                },
                            });
                        }catch (e) {
                            alert('An error occurred: ' + e.message);
                        }
                    }
                }
            }, 100);
        }).on('hide.bs.modal', function (e) {
            clearInterval(focusInterval);
            resetModal()
        });

        $('#deleteValueButton').on('click', function () {
            resetModal()
            $('#barcodeInput').val('')
        });
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

        var mediaSource = new MediaSource();
        mediaSource.addEventListener('sourceopen', handleSourceOpen, false);
        var mediaRecorder;
        var recordedBlobs;
        var sourceBuffer;
        var gumVideo = document.querySelector('video#gum');
        var recordedVideo = document.querySelector('video#recorded');
        var recordButton = document.querySelector('#startVideoButton');
        var stopButton = document.querySelector('#stopVideoButton');

        $('#startVideoButton').on('click', function () {
            toggleRecording(orderId,true)
        });

        $('#stopVideoButton').on('click', function () {
            toggleRecording(orderId,false)
        });

        console.log('location.host:', location.host);
        // window.isSecureContext could be used for Chrome
        var isSecureOrigin = location.protocol === 'https:' ||
            location.host.includes('localhost');
        if (!isSecureOrigin) {
            alert('getUserMedia() must be run from a secure origin: HTTPS or localhost.' +
                '\n\nChanging protocol to HTTPS');
            location.protocol = 'HTTPS';
        }

        var constraints = {
            video: true
        };

        navigator.mediaDevices.getUserMedia(
            constraints
        ).then(
            successCallback,
            errorCallback
        );

        function successCallback(stream) {
            console.log('getUserMedia() got stream: ', stream);
            window.stream = stream;
            gumVideo.srcObject = stream;
        }

        function errorCallback(error) {
            console.log('navigator.getUserMedia error: ', error);
        }

        function handleSourceOpen(event) {
            console.log('MediaSource opened');
            sourceBuffer = mediaSource.addSourceBuffer('video/webm; codecs="vp8"');
            console.log('Source buffer: ', sourceBuffer);
        }

        let previousTime = Date.now();

        function handleDataAvailable(event) {
            const timeNow = Date.now();
            console.log(`Interval: ${timeNow - previousTime}`);
            previousTime = timeNow;
            if (event.data && event.data.size > 0) {
                recordedBlobs.push(event.data);
            }
        }

        function handleStop(event) {
            var blob = new Blob(recordedBlobs, { type: 'video/webm' });

            var formData = new FormData();
            formData.append('video', blob, 'recorded_video.webm');

            let storeVideoUrl = '{{ route("order.storeVideo", ":orderId") }}';
            storeVideoUrl = storeVideoUrl.replace(':orderId', orderId);
            var csrfToken = `{{csrf_token()}}`;

            $('#loadingDiv').removeClass('d-none');

            $.ajax({
                url: storeVideoUrl,
                type: 'POST',
                data: formData,
                processData: false, // Don't process the data
                contentType: false, // Don't set content type
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    console.log('Video successfully sent to backend:', response);
                    // Hide loading indicator
                    $('#loadingDiv').addClass('d-none');
                    $('#finishVideoButton').addClass('d-none');
                    var videoElement = $('<video controls></video>');
                    videoElement.attr('src', response.video_url);
                    $('#playRecentVideo').append(videoElement);

                    Toastify({
                        text: "Video başarıyla yüklendi sonraki siparişe başlayabilirsiniz!",
                        duration: 3500, // Duration in milliseconds
                        close: true, // Show close button
                        gravity: "top", // Position: top or bottom
                        position: "right", // Position: left, center or right
                        backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)", // Background color
                    }).showToast();
                },
                error: function(xhr, status, error) {
                    // Hide loading indicator
                    $('#loadingDiv').addClass('d-none');
                    console.error('Error sending video to backend:', error);
                }
            });
        }

        function toggleRecording(orderId,isStart = true) {
            if (isStart) {
                startRecording();
            } else {
                stopRecording();
                // playButton.disabled = false;
            }
        }

        var timerInterval;
        var startTime;

        function startRecording() {
            var options = {mimeType: 'video/webm;codecs=vp9', bitsPerSecond: 100000};
            recordedBlobs = [];
            try {
                mediaRecorder = new MediaRecorder(window.stream, options);
            } catch (e0) {
                console.log('Unable to create MediaRecorder with options Object: ', options, e0);
                try {
                    options = {mimeType: 'video/webm;codecs=vp8', bitsPerSecond: 100000};
                    mediaRecorder = new MediaRecorder(window.stream, options);
                } catch (e1) {
                    console.log('Unable to create MediaRecorder with options Object: ', options, e1);
                    try {
                        mediaRecorder = new MediaRecorder(window.stream);
                    } catch (e2) {
                        alert('MediaRecorder is not supported by this browser.');
                        console.log('Unable to create MediaRecorder', e2);
                        return;
                    }
                }
            }
            console.log('Created MediaRecorder', mediaRecorder, 'with options', options);
            recordButton.disabled = true;
            mediaRecorder.onstop = handleStop;
            mediaRecorder.ondataavailable = handleDataAvailable;
            mediaRecorder.start(1000);
            console.log('MediaRecorder started', mediaRecorder);

            startTimer(); // Start the timer when recording starts
        }

        function stopRecording() {
            recordButton.disabled = false;
            mediaRecorder.stop();
            stopTimer(); // Stop the timer when recording stops
        }

        function resetTime() {
            clearInterval(timerInterval); // Stop any ongoing timer
            document.getElementById('timer').textContent = '00:00'; // Reset the displayed time
            document.getElementById('timer').style.color = 'black'; // Reset the color to the original
        }

        function startTimer() {
            startTime = Date.now();
            timerInterval = setInterval(updateTimer, 1000);
            document.getElementById('timer').style.color = 'green';
        }

        function updateTimer() {
            var elapsedTime = Date.now() - startTime;
            var totalSeconds = Math.floor(elapsedTime / 1000);
            var minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
            var seconds = String(totalSeconds % 60).padStart(2, '0');
            document.getElementById('timer').textContent = `${minutes}:${seconds}`;
        }

        function stopTimer() {
            clearInterval(timerInterval);
            document.getElementById('timer').style.color = 'red';
        }

    </script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

@endsection
