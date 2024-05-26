@extends('layouts.app')
@section('styles')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/quagga/dist/quagga.min.js"></script>
    <style>
        button {
            margin: 0 3px 10px 0;
            padding-left: 2px;
            padding-right: 2px;
            width: 99px;
        }

        button:last-of-type {
            margin: 0;
        }

        p.borderBelow {
            margin: 0 0 20px 0;
            padding: 0 0 20px 0;
        }

        video {
            height: 232px;
            margin: 0 12px 20px 0;
            vertical-align: top;
            width: calc(20em - 10px);
        }

        video:last-of-type {
            margin: 0 0 20px 0;
        }

        video#gumVideo {
            margin: 0 20px 20px 0;
        }

        @media (max-width: 500px) {
            button {
                font-size: 0.8em;
                width: calc(33% - 5px);
            }
        }

        @media (max-width: 720px) {
            video {
                height: calc((50vw - 48px) * 3 / 4);
                margin: 0 10px 10px 0;
                width: calc(50vw - 48px);
            }

            video#gumVideo {
                margin: 0 10px 10px 0;
            }
        }
    </style>
@endsection
@section('content')
    <div id="container">
        <video id="gum" autoplay muted playsinline></video>
        <video id="recorded" autoplay loop playsinline></video>

        <div>
            <button id="record">Start Recording</button>
            <button id="play" disabled>Play</button>
            <button id="download" disabled>Download</button>
        </div>
    </div>
@endsection

@section('scripts')
@endsection
