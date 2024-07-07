<!DOCTYPE html>
<html>

<head>
    <title>OEE Dashboard</title>
    <!-- Add CSS and JS here -->
    <link href="{{ asset('css/bootstrap.css') }}" rel="stylesheet"> <!-- Tautan CSS Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background-color: black;
        }

        .chart-container {
            position: relative;
            height: 200px;
            width: 200px;
            display: inline-block;
        }

        .chart-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 20px;
            font-weight: bold;
            color: white;
        }

        /* .center-vertical {
            display: flex;
            flex-direction: column;
            justify-content: center;
        } */
    </style>
</head>

<body>
    <x-navbar></x-navbar>
    <div class="container my-3">
        <div class="row text-center mb-5">
            <div class="col center-vertical">
                <div class="container">
                    <h4 class="text-light mb-3">Machine Status</h4>
                    <button id="toggleMachineStatus"
                        class="btn btn-lg {{ $status ? 'btn-success' : 'btn-danger' }} mb-3">{{ $status ? 'ON' : 'STOP' }}</button>
                </div>
            </div>
            <div class="col center-vertical">
                <div class="container">
                    <!-- Form Machine Start -->
                    <h4 class="text-light mb-3">Set Machine Start</h4>
                    <form id="machineStartForm" method="POST" action="{{ route('machine-start.store') }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="machine_start" class="form-label text-light">Start Time</label>
                            <input type="datetime-local" id="machine_start" name="machine_start" class="form-control"
                                required>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
            <div class="col center-vertical">
                <div class="container">
                    <h4 class="text-light mb-3">Set Downtime Schedule</h4>
                    <!-- Form Downtime Terjadwal -->
                    <form id="scheduleDowntimeForm" method="POST" action="/schedule-downtime">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="start_time" class="form-label text-light">Start Time</label>
                            <input type="datetime-local" id="start_time" name="start_time" class="form-control"
                                required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="end_time" class="form-label text-light">End Time (optional)</label>
                            <input type="datetime-local" id="end_time" name="end_time" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
            <div class="col center-vertical">
                <div class="container">
                    <h4 class="text-light mb-3">Input Reject Item</h4>
                    <!-- Form untuk mengisi reject -->
                    <form method="POST" action="{{ route('update.reject') }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="reject" class="form-label text-light">Jumlah Reject</label>
                            <input type="number" id="reject" name="reject" class="form-control"
                                value="{{ $latestReject ?? 0 }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="row text-center">
            <div class="col-md-4">
                <h4 class="text-light">Availability</h4>
                <div class="chart-container my-2">
                    <canvas id="availabilityChart"></canvas>
                    <div class="chart-text my-2" id="availabilityText"></div>
                </div>
            </div>
            <div class="col-md-4">
                <h4 class="text-light">Performance</h4>
                <div class="chart-container my-2">
                    <canvas id="performanceChart"></canvas>
                    <div class="chart-text my-2" id="performanceText"></div>
                </div>
            </div>
            <div class="col-md-4">
                <h4 class="text-light">Quality</h4>
                <div class="chart-container my-2">
                    <canvas id="qualityChart"></canvas>
                    <div class="chart-text my-2" id="qualityText"></div>
                </div>
            </div>
        </div>
        <div class="row text-center">
            <div class="col-8">
                <table class="table table-dark m-3" id="oee-table">
                    <thead>
                        <tr>
                            <th>Line</th>
                            <th>Nama Line</th>
                            <th>Tanggal</th>
                            <th>Shift</th>
                            <th>Tipe</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($productions as $production)
                            <tr>
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
            </div>
            <div class="col-4">
                <h4 class="text-light">OEE</h4>
                <div class="chart-container my-2">
                    <canvas id="oeeChart"></canvas>
                    <div class="chart-text my-2" id="oeeText"></div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function createGaugeChart(ctx, value, label, textId) {
            const chart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [value, 100 - value],
                        backgroundColor: ['#4CAF50', '#E4080A'],
                        borderWidth: 0
                    }],
                    labels: [label, 'Bad ' + label]
                },
                options: {
                    rotation: -135, // Rotate starting point to the top
                    circumference: 270, // Display only 3/4 of the chart
                    cutoutPercentage: 70,
                    tooltips: {
                        enabled: false
                    },
                    spacing: 5,
                    hover: {
                        mode: null
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            document.getElementById(textId).innerText = value + '%';
            return chart;
        };

        window.onload = function() {
            var availabilityCtx = document.getElementById('availabilityChart').getContext('2d');
            var performanceCtx = document.getElementById('performanceChart').getContext('2d');
            var qualityCtx = document.getElementById('qualityChart').getContext('2d');
            var oeeCtx = document.getElementById('oeeChart').getContext('2d');

            createGaugeChart(availabilityCtx, {{ $oeeMetrics->availability }}, 'Availability', 'availabilityText');
            createGaugeChart(performanceCtx, {{ $oeeMetrics->performance }}, 'Performance', 'performanceText');
            createGaugeChart(qualityCtx, {{ $oeeMetrics->quality }}, 'Quality', 'qualityText');
            createGaugeChart(oeeCtx, {{ $oeeMetrics->oee }}, 'OEE', 'oeeText');
        };

        function checkMachineStatus() {
            $.get('/machine-status', function(response) {
                if (response.status === "on") {
                    $('#toggleMachineStatus').removeClass('btn-danger').addClass('btn-success').text('ON');
                } else if (response.status === "stop") {
                    $('#toggleMachineStatus').removeClass('btn-success').addClass('btn-danger').text('STOP');
                }
            });
        }

        function updateChart(oeeMetrics) {
            createGaugeChart(availabilityCtx, oeeMetrics.availability, 'Availability', 'availabilityText');
            createGaugeChart(performanceCtx, oeeMetrics.performance, 'Performance', 'performanceText');
            createGaugeChart(qualityCtx, oeeMetrics.quality, 'Quality', 'qualityText');
            createGaugeChart(oeeCtx, oeeMetrics.oee, 'OEE', 'oeeText');
        }

        function fetchOeeMetrics() {
            $.ajax({
                url: '/calculate-oee',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        updateChart(response.oeeMetrics);
                    }
                },
                error: function(error) {
                    console.error('Error fetching OEE metrics:', error);
                }
            });
        }

        $(document).ready(function() {
            fetchOeeMetrics();
            setInterval(fetchOeeMetrics, 60000); // Check every minute
            setInterval(checkMachineStatus, 60000); // Check every minute

            var machineStatus = '{{ $status }}' === 'on';

            $('#toggleMachineStatus').click(function() {
                $.post('/toggle-machine-status', {
                    _token: '{{ csrf_token() }}',
                    status: machineStatus ? 'on' : 'stop'
                }, function(response) {
                    if (response.status === "on") {
                        $('#toggleMachineStatus').removeClass('btn-danger').addClass('btn-success')
                            .text('ON');
                        machineStatus = true;
                    } else {
                        $('#toggleMachineStatus').removeClass('btn-success').addClass('btn-danger')
                            .text('STOP');
                        machineStatus = false;
                    }
                });
            });
        });
    </script>
    <script src="{{ asset('js/bootstrap.js') }}"></script>
</body>

</html>
