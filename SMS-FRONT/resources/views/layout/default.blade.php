<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'SMS Intelix') — SMS Intelix</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- jQuery (DataTables lo necesita) -->
    <script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.dataTables.min.css') }}"/>
    <script src="{{ asset('vendor/datatables/dataTables.min.js') }}"></script>
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}" />
    <script src="{{ asset('vendor/select2/select2.min.js') }}" defer></script>
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="{{ asset('vendor/tabler-icons/tabler-icons.min.css') }}" />
    <!-- SweetAlert2 -->
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('vendor/toastr/toastr.min.css') }}"/>
    <script src="{{ asset('vendor/toastr/toastr.min.js') }}"></script>
    @stack('styles')
</head>
<body>
    <div id="mobile-overlay" class="mobile-overlay"></div>
    <div class="app" id="app-shell">
        @include('partials._aside')
        <main class="main">
            @include('partials._header')
            <div class="page">
                @yield('content')
            </div>
        </main>
    </div>

    @include('partials._user_panel')

    <script>
        window.APP = { csrfToken: '{{ csrf_token() }}' };

        // DataTables Spanish language
        window.DT_ES = {
            processing:     "Procesando...",
            search:         "Buscar:",
            lengthMenu:     "Mostrar _MENU_ registros",
            info:           "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty:      "Mostrando 0 a 0 de 0 registros",
            infoFiltered:   "(filtrado de _MAX_ registros totales)",
            loadingRecords: "Cargando...",
            zeroRecords:    "No se encontraron resultados",
            emptyTable:     "Sin datos disponibles",
            paginate: {
                first:    "Primero",
                previous: "Anterior",
                next:     "Siguiente",
                last:     "Último"
            },
            aria: {
                sortAscending:  ": ordenar columna de forma ascendente",
                sortDescending: ": ordenar columna de forma descendente"
            }
        };

        // Toastr config
        toastr.options = {
            closeButton:   true,
            progressBar:   true,
            positionClass: "toast-top-right",
            timeOut:       "4000"
        };

        // Flash messages via toastr
        @if(session('success')) toastr.success("{{ addslashes(session('success')) }}"); @endif
        @if(session('error'))   toastr.error("{{ addslashes(session('error')) }}");   @endif
        @if(session('warning')) toastr.warning("{{ addslashes(session('warning')) }}"); @endif
        @if(session('info'))    toastr.info("{{ addslashes(session('info')) }}");    @endif

        // Validation errors via toastr (formularios tradicionales)
        @if($errors->any())
        toastr.error('Revisa los campos marcados en el formulario.');
        @endif
    </script>
    @stack('scripts')
</body>
</html>
