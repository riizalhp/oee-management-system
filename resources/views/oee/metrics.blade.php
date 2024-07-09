<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>OEE Metrics Data</title>
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
        <h1 class="text-light mb-3">OEE Metrics Data</h1>
        <table class="table table-dark">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Availability</th>
                    <th>Performance</th>
                    <th>Quality</th>
                    <th>OEE</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($metrics as $metric)
                    <tr>
                        <td>{{ $metric->id }}</td>
                        <td>{{ $metric->availability }}</td>
                        <td>{{ $metric->performance }}</td>
                        <td>{{ $metric->quality }}</td>
                        <td>{{ $metric->oee }}</td>
                        <td>{{ $metric->timestamp }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="d-flex justify-content-center">
            {{ $metrics->links('vendor.pagination.bootstrap-5') }}
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
