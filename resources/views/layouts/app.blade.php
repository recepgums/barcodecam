<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Inter:400,500,600,700" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @yield('styles')
    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            border: none;
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 600;
            font-size: 1.1rem;
            margin-right: 2rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .navbar-brand:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }
        
        .navbar-brand.active {
            background: rgba(255,255,255,0.2);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
        }
        
        .nav-link:hover {
            color: white !important;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border-radius: 0.75rem;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .container-fluid {
            max-width: 1400px;
        }
        
        main {
            min-height: calc(100vh - 76px);
            padding: 2rem 0;
        }
    </style>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">
                    <i class="fas fa-cube me-2"></i>Paketleme
                </a>
                
                @if(Auth::check())
                <div class="d-flex gap-3">
                    <a class="navbar-brand {{ request()->routeIs('orders.index') && request('only_videos') ? 'active' : '' }}" href="{{ route('orders.index',['only_videos' => true]) }}">
                        <i class="fas fa-video me-2"></i>Videolar
                    </a>
                    <a class="navbar-brand {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                        <i class="fas fa-box me-2"></i>Ürünler
                    </a>
                    <a class="navbar-brand {{ request()->routeIs('shipments.index') ? 'active' : '' }}" href="{{ route('shipments.index') }}">
                        <i class="fas fa-shipping-fast me-2"></i>Siparişler
                    </a>
                  
                    <a class="navbar-brand" href="#" data-bs-toggle="modal" data-bs-target="#commandsModal">
                        <i class="fas fa-terminal me-2"></i>Komutlar
                    </a>
                </div>
                @endif

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">
                                        <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                                    </a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-weight: 600;">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </div>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                       <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main>
            @yield('content')
        </main>
    </div>

    @if(Auth::check())
    <!-- Commands Modal -->
    <div class="modal fade" id="commandsModal" tabindex="-1" aria-labelledby="commandsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="commandsModalLabel">
                        <i class="fas fa-terminal me-2"></i>Komutlar
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-download fa-3x text-primary mb-3"></i>
                                    <h5 class="card-title">Siparişleri Çek</h5>
                                    <p class="card-text">Tüm mağazalar için siparişleri çeker</p>
                                    <button type="button" class="btn btn-primary" onclick="executeCommand('fetch:orders')">
                                        <i class="fas fa-play me-2"></i>Çalıştır
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-box fa-3x text-success mb-3"></i>
                                    <h5 class="card-title">Ürün Bilgilerini Çek</h5>
                                    <p class="card-text">Siparişlerdeki ürün bilgilerini çeker</p>
                                    <button type="button" class="btn btn-success" onclick="executeCommand('order:product-fetch')">
                                        <i class="fas fa-play me-2"></i>Çalıştır
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Command Output -->
                    <div id="commandOutput" class="mt-4" style="display: none;">
                        <h6>Komut Çıktısı:</h6>
                        <div class="alert" id="commandResult">
                            <div id="commandResultContent"></div>
                        </div>
                    </div>
                    
                    <!-- Loading -->
                    <div id="commandLoading" class="text-center mt-4" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Yükleniyor...</span>
                        </div>
                        <p class="mt-2">Komut çalışıyor, lütfen bekleyiniz...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function executeCommand(command) {
            // Show loading
            document.getElementById('commandLoading').style.display = 'block';
            document.getElementById('commandOutput').style.display = 'none';
            
            // Disable all buttons
            const buttons = document.querySelectorAll('#commandsModal button[onclick]');
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Çalışıyor...';
            });

            fetch('{{ route("commands.execute") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    command: command
                })
            })
            .then(response => response.json())
            .then(data => {
                // Hide loading
                document.getElementById('commandLoading').style.display = 'none';
                
                // Show result
                const resultDiv = document.getElementById('commandResult');
                const contentDiv = document.getElementById('commandResultContent');
                
                if (data.success) {
                    resultDiv.className = 'alert alert-success';
                    contentDiv.innerHTML = `
                        <h6><i class="fas fa-check-circle me-2"></i>${data.message}</h6>
                        ${data.output ? `<pre class="mt-2 mb-0">${data.output}</pre>` : ''}
                    `;
                } else {
                    resultDiv.className = 'alert alert-danger';
                    contentDiv.innerHTML = `
                        <h6><i class="fas fa-exclamation-circle me-2"></i>${data.message}</h6>
                        ${data.output ? `<pre class="mt-2 mb-0">${data.output}</pre>` : ''}
                    `;
                }
                
                document.getElementById('commandOutput').style.display = 'block';
                
                // Re-enable buttons
                buttons.forEach(btn => {
                    btn.disabled = false;
                    const commandName = btn.getAttribute('onclick').match(/'([^']+)'/)[1];
                    if (commandName === 'fetch:orders') {
                        btn.innerHTML = '<i class="fas fa-play me-2"></i>Çalıştır';
                    } else {
                        btn.innerHTML = '<i class="fas fa-play me-2"></i>Çalıştır';
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Hide loading
                document.getElementById('commandLoading').style.display = 'none';
                
                // Show error
                const resultDiv = document.getElementById('commandResult');
                const contentDiv = document.getElementById('commandResultContent');
                
                resultDiv.className = 'alert alert-danger';
                contentDiv.innerHTML = `
                    <h6><i class="fas fa-exclamation-circle me-2"></i>Hata oluştu!</h6>
                    <p>Komut çalıştırılırken bir hata oluştu.</p>
                `;
                
                document.getElementById('commandOutput').style.display = 'block';
                
                // Re-enable buttons
                buttons.forEach(btn => {
                    btn.disabled = false;
                    const commandName = btn.getAttribute('onclick').match(/'([^']+)'/)[1];
                    if (commandName === 'fetch:orders') {
                        btn.innerHTML = '<i class="fas fa-play me-2"></i>Çalıştır';
                    } else {
                        btn.innerHTML = '<i class="fas fa-play me-2"></i>Çalıştır';
                    }
                });
            });
        }
    </script>
    @endif

@yield('scripts')
</body>
</html>
