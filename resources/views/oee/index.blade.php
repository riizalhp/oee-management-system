<!DOCTYPE html>
<html>

<head>
    <title>OEE Dashboard</title>
    <!-- Add CSS and JS here -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet"> <!-- Tautan CSS Bootstrap -->
</head>

<body>
    <h1>OEE Dashboard</h1>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Line</th>
                <th>Nama Line</th>
                <th>Tgl</th>
                <th>Shift</th>
                <th>Item</th>
                <th>Seq</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $datum)
                <tr>
                    <td>{{ $datum->no }}</td>
                    <td>{{ $datum->line }}</td>
                    <td>{{ $datum->nama_line }}</td>
                    <td>{{ $datum->tgl }}</td>
                    <td>{{ $datum->shift }}</td>
                    <td>{{ $datum->item }}</td>
                    <td>{{ $datum->seq }}</td>
                    <td>{{ $datum->timestamp }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
