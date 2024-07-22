<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Productions Data</title>
    <link href="{{ asset('css/bootstrap.css') }}" rel="stylesheet">
    <style>
        body {
            background-color: black;
        }
    </style>
</head>

<body>
    <x-navbar></x-navbar>
    <div class="container text-center mt-3">
        <h1 class="text-light mb-3">Productions Data</h1>
        @if ($productions->count() == 0)
            <p class="text-light">Tidak ada data!</p>
        @else
            <table class="table table-dark">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Line Produksi</th>
                        <th>Nama Line</th>
                        <th>Tanggal Produksi</th>
                        <th>Shift Produksi</th>
                        <th>Tipe Barang</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($productions as $production)
                        <tr>
                            <td>{{ $production->id }}</td>
                            <td>{{ $production->line_produksi }}</td>
                            <td>{{ $production->nama_line }}</td>
                            <td>{{ $production->tgl_produksi }}</td>
                            <td>{{ $production->shift_produksi }}</td>
                            <td>{{ $production->tipe_barang }}</td>
                            <td>{{ $production->timestamp_capture }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <div class="d-flex justify-content-center">
            {{ $productions->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>
    <script src="{{ asset('js/bootstrap.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
        integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous">
    </script>
</body>

</html>
