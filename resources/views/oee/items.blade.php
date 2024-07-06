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
                            <th>Ideal Produce Time (in minutes)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->nama_item }}</td>
                                <td>{{ $item->idealProduceTime }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
