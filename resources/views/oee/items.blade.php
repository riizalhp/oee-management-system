<!DOCTYPE html>
<html>

<head>
    <title>Manage Items</title>
    <link href="{{ asset('css/bootstrap.css') }}" rel="stylesheet">
    <style>
        body {
            background-color: black;
        }
    </style>
</head>

<body>
    <x-navbar></x-navbar>
    <div class="container mt-5">
        <h1 class="text-light mb-5 text-center">Manage Items</h1>
        <div class="row">
            <div class="col-4">
                <!-- Form to Add New Item -->
                <form method="POST" action="/items">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="nama_item" class="form-label text-light">Nama Item</label>
                        <input type="text" id="nama_item" name="nama_item" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="tipe_barang" class="form-label text-light">Tipe Barang</label>
                        <input type="text" id="tipe_barang" name="tipe_barang" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="idealProduceTime" class="form-label text-light">Ideal Produce Time (in
                            minutes)</label>
                        <input type="number" id="idealProduceTime" name="idealProduceTime" class="form-control"
                            required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </form>
            </div>
            <div class="col-8">
                <!-- List of Items -->
                <table class="table table-dark mt-4">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Item</th>
                            <th>Tipe Barang</th>
                            <th>Ideal Produce Time (in minutes)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->nama_item }}</td>
                                <td>{{ $item->tipe_barang }}</td>
                                <td>{{ $item->ideal_produce_time }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($items->count() == 0)
                    <p class="text-light text-center">Belum ada data!</p>
                @endif
            </div>
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
