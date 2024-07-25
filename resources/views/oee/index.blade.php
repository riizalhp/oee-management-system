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
            height: 150px;
            width: 150px;
            display: inline-block;
        }

        .chart-container-oee {
            position: relative;
            height: 250px;
            width: 250px;
            display: inline-block;
        }

        .chart-container-oee-loss {
            position: relative;
            height: 150px;
            width: 150px;
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

        .center-vertical {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        #machine-status {
            display: flex;
            flex-direction: column;
        }

        .chart-legend {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .legend-color {
            width: 6px;
            height: 6px;
            margin-right: 8px;
        }

        .legend-text {
            display: flex;
            align-items: center;
            margin-right: 8px;
        }
    </style>
</head>

<body>
    <x-navbar></x-navbar>
    <div class="container mb-3">
        {{-- <div class="row text-center mb-3">
            <div class="col border py-3 me-3">
                <h4 class="text-light mb-3">Shift Start Time</h4>
                <span id="countdown" class="text-light"></span>
            </div>
            <div class="col border py-3">
                <h4 class="text-light mb-3">Downtime Schedule</h4>
                <span id="downtimeCountdown" class="text-light"></span>
            </div>
        </div> --}}
        <div class="row text-center mb-3">
            <div class="col-4 border pt-3 me-3">
                <h4 class="text-light mb-3">Machine Information</h4>
                <table class="table table-dark">
                    <tbody>
                        <tr>
                            <td>
                                <div id="lineProduksi">-</div>
                            </td>
                            <td>
                                <div id="namaLine">-</div>
                            </td>
                            <td>
                                <div id="shiftProduksi">-</div>
                            </td>
                            <td>
                                <div id="tipeBarang">-</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col border py-3 me-3">
                <div class="container">
                    <h4 class="text-light mb-3">Loading Time</h4>
                    <h5 class="text-success">
                        <span style="font-size: larger" id="runtime">0.0</span>
                        <span>min</span>
                    </h5>
                </div>
            </div>
            <div class="col border py-3 me-3">
                <h4 class="text-light mb-3">Total Stop Time</h4>
                <h5 class="text-danger">
                    <span style="font-size: larger" id="stopTime">0.0</span>
                    <span>min</span>
                </h5>
            </div>
            <div class="col border py-3">
                <h4 class="text-light mb-3">Operation Time</h4>
                <h5 class="text-primary">
                    <span style="font-size: larger" id="optTime">0.0</span>
                    <span>min</span>
                </h5>
            </div>
        </div>
        <div class="row text-center mb-3">
            <div class="col-8">
                <div class="row mb-3">
                    <div class="col border pt-3" id="machine-status">
                        <h4 class="text-light mb-3">Machine Status</h4>
                        <button id="toggleMachineStatus"
                            class="btn btn-lg {{ $status ? 'btn-success' : 'btn-danger' }} mb-3">{{ $status ? 'ON' : 'STOP' }}</button>
                    </div>
                    <div class="col border ms-3 py-3">
                        <h4 class="text-light mb-3">Trouble Information</h4>
                        <span></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col border pt-3 me-3">
                        <h4 class="text-light">Availability</h4>
                        <div class="chart-container my-2">
                            <canvas id="availabilityChart"></canvas>
                            <div class="chart-text my-2" id="availabilityText"></div>
                        </div>
                    </div>
                    <div class="col border pt-3 me-3">
                        <h4 class="text-light">Performance</h4>
                        <div class="chart-container my-2">
                            <canvas id="performanceChart"></canvas>
                            <div class="chart-text my-2" id="performanceText"></div>
                        </div>
                    </div>
                    <div class="col border pt-3">
                        <h4 class="text-light">Quality</h4>
                        <div class="chart-container my-2">
                            <canvas id="qualityChart"></canvas>
                            <div class="chart-text my-2" id="qualityText"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-4 center-vertical">
                <div class="col border ms-1 center-vertical">
                    <div class="container">
                        <h4 class="text-light">OEE</h4>
                        <div class="chart-container-oee my-2">
                            <canvas id="oeeChart"></canvas>
                            <div class="chart-text my-2" id="oeeText"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row text-center">
            <div class="col-3 border center-vertical me-3">
                <div class="col center-vertical">
                    <div class="container">
                        <h4 class="text-light">Stop Category</h4>
                        <div class="my-2">
                            <canvas id="stopCategory"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-5 me-3 center-vertical">
                <div class="row border p-3 mb-3">
                    <h4 class="text-light">Output Time</h4>
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>type</th>
                                <th>output</th>
                                <th>cycle_time</th>
                                <th>qty_cycle</th>
                                <th>output_time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div id="product">-</div>
                                </td>
                                <td>
                                    <div id="output">-</div>
                                </td>
                                <td>
                                    <div id="cycleTime">-</div>
                                </td>
                                <td>
                                    <div id="qtyCycle">-</div>
                                </td>
                                <td>
                                    <div id="outputTime">-</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="row border p-3">
                    <h4 class="text-light">Quality Loss Time</h4>
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>type</th>
                                <th>qty_ng</th>
                                <th>cycle_time</th>
                                <th>qty_cycle</th>
                                <th>defect_time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div id="productQt">-</div>
                                </td>
                                <td>
                                    <div id="qtyNg">-</div>
                                </td>
                                <td>
                                    <div id="cycleTimeQt">-</div>
                                </td>
                                <td>
                                    <div id="qtyCycleQt">-</div>
                                </td>
                                <td>
                                    <div id="defectTimeQt">-</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col border center-vertical">
                <div class="container py-3">
                    <h4 class="text-light">OEE VS Loss</h4>
                    <div class="chart-container-oee-loss my-2">
                        <canvas id="oeeLossChart"></canvas>
                    </div>
                    <table class="table table-dark table-sm" style="font-size: 12px">
                        <thead>
                            <tr>
                                <th></th>
                                <th>current</th>
                                <th>percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="legend-color" style="background-color: red;"></div>Stop_Loss
                                </td>
                                <td id="stopTimeLoss">-</td>
                                <td id="stopLossPercent">100%</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="legend-color" style="background-color: yellow;"></div>
                                    Speed_Loss
                                </td>
                                <td id="speedLoss">-</td>
                                <td id="speedLossPercent">0%</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="legend-color" style="background-color: orange;"></div>
                                    Quality_Loss
                                </td>
                                <td id="defectTime">-</td>
                                <td id="qualityLossPercent">0%</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="legend-color" style="background-color: green;"></div>OEE
                                </td>
                                <td id="oeeCurrent">-</td>
                                <td id="oeePercent">0%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        {{-- <div class="row text-center">
            <div class="col center-vertical border py-3 me-3">
                <div class="container">
                    <!-- Form Machine Start -->
                    <h4 class="text-light mb-3">Set Shift Time</h4>
                    <form id="machineStartForm" method="POST" action="{{ route('machine-start.store') }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="machine_start" class="form-label text-light">Start Time</label>
                            <input type="datetime-local" id="machine_start" name="machine_start" class="form-control"
                                required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="machine_end" class="form-label text-light">End Time</label>
                            <input type="datetime-local" id="machine_end" name="machine_end" class="form-control"
                                required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="planned_time" class="form-label text-light">Planned Time</label>
                            <input type="number" id="planned_time" name="planned_time" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
            <div class="col center-vertical border me-3">
                <div class="container py-3">
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
            <div class="col center-vertical border">
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
        </div> --}}
        {{-- <div class="row text-center">
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
        </div> --}}
    </div>
    <script>
        var ctx = document.getElementById('oeeLossChart').getContext('2d');
        var oeeLossChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Stop_Loss', 'Speed_Loss', 'Quality_Loss', 'OEE'],
                datasets: [{
                    data: [100, 0, 0, ], // Nilai dalam menit (Quality_Loss dalam detik)
                    backgroundColor: ['red', 'yellow', 'orange', 'green'],
                    borderColor: ['black', 'black', 'black', 'black'],
                    borderWidth: 1,
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        var stopCtx = document.getElementById('stopCategory').getContext('2d');
        // Plugin to display data values
        const dataLabelsPlugin = {
            id: 'dataLabels',
            afterDatasetsDraw(chart) {
                const {
                    ctx,
                    data,
                    chartArea: {
                        left,
                        right,
                        top,
                        bottom
                    },
                    scales: {
                        y
                    }
                } = chart;
                ctx.save();
                data.datasets[0].data.forEach((value, index) => {
                    ctx.font = '12px Arial';
                    ctx.fillStyle = 'white';
                    ctx.textAlign = 'left';
                    ctx.textBaseline = 'middle';
                    const x = y.getPixelForTick(index);
                    ctx.fillText(value, right + 5, x);
                });
            }
        };

        const stopChart = new Chart(stopCtx, {
            type: 'bar',
            data: {
                labels: ["Dandori", "Others", "Tool", "Start_Up", "Breakdown"],
                datasets: [{
                    data: [5.3, 1.14, 0.255, 3.5, 3.47],
                    backgroundColor: [
                        "rgba(54, 162, 235, 0.2)",
                        "rgba(54, 162, 235, 0.2)",
                        "rgba(54, 162, 235, 0.2)",
                        "rgba(54, 162, 235, 0.2)",
                        "rgba(54, 162, 235, 0.2)",
                    ],
                    borderColor: [
                        "rgb(54, 162, 235)",
                        "rgb(54, 162, 235)",
                        "rgb(54, 162, 235)",
                        "rgb(54, 162, 235)",
                        "rgb(54, 162, 235)",
                    ],
                    borderWidth: 1,
                    barThickness: 15,
                }]
            },
            options: {
                indexAxis: "y",
                plugins: {
                    legend: {
                        display: false
                    },
                    dataLabels: dataLabelsPlugin
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: 14,
                            },
                        },
                    },
                    x: {
                        display: false, // Hide x-axis
                    },
                }
            }
        });

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

            document.getElementById(textId).innerText = value.toFixed(2) + '%';
            return chart;
        };

        var availabilityCtx = document.getElementById('availabilityChart').getContext('2d');
        var performanceCtx = document.getElementById('performanceChart').getContext('2d');
        var qualityCtx = document.getElementById('qualityChart').getContext('2d');
        var oeeCtx = document.getElementById('oeeChart').getContext('2d');

        var availabilityChart = createGaugeChart(availabilityCtx, {{ $latestOeeMetrics->availability }}, 'Availability',
            'availabilityText');
        var performanceChart = createGaugeChart(performanceCtx, {{ $latestOeeMetrics->performance }}, 'Performance',
            'performanceText');
        var qualityChart = createGaugeChart(qualityCtx, {{ $latestOeeMetrics->quality }}, 'Quality', 'qualityText');
        var oeeChart = createGaugeChart(oeeCtx, {{ $latestOeeMetrics->oee }}, 'OEE', 'oeeText');


        // window.onload = function() {};

        function checkMachineStatus() {
            $.get('/machine-status', function(response) {
                if (response.status === "on") {
                    $('#toggleMachineStatus').removeClass('btn-danger').addClass('btn-success').text('ON');
                } else if (response.status === "stop") {
                    $('#toggleMachineStatus').removeClass('btn-success').addClass('btn-danger').text('STOP');
                }
            });
        }

        function updateChart(oeeMetrics, tipeBarang, totalItems, cycleTime, outputTime, cycleCount, defectTime) {
            var availabilityValue = Number(oeeMetrics.availability);
            availabilityChart.data.datasets[0].data[0] = oeeMetrics.availability;
            availabilityChart.data.datasets[0].data[1] = 100 - oeeMetrics.availability;
            document.getElementById('availabilityText').innerText = availabilityValue.toFixed(2) + '%';
            availabilityChart.update();
            var performanceValue = Number(oeeMetrics.performance);
            performanceChart.data.datasets[0].data[0] = oeeMetrics.performance;
            performanceChart.data.datasets[0].data[1] = 100 - oeeMetrics.performance;
            document.getElementById('performanceText').innerText = performanceValue.toFixed(2) + '%';
            performanceChart.update();
            var qualityValue = Number(oeeMetrics.quality);
            qualityChart.data.datasets[0].data[0] = oeeMetrics.quality;
            qualityChart.data.datasets[0].data[1] = 100 - oeeMetrics.quality;
            document.getElementById('qualityText').innerText = qualityValue.toFixed(2) + '%';
            qualityChart.update();
            var oeeValue = Number(oeeMetrics.oee);
            oeeChart.data.datasets[0].data[0] = oeeMetrics.oee;
            oeeChart.data.datasets[0].data[1] = 100 - oeeMetrics.oee;
            document.getElementById('oeeText').innerText = oeeValue.toFixed(2) + '%';
            oeeChart.update();
            var downtimeValue = Number(oeeMetrics.downtime / 60);
            var operatingTimeValue = Number(oeeMetrics.operating_time);
            document.getElementById('runtime').innerText = oeeMetrics.runtime.toFixed(1);
            document.getElementById('stopTime').innerText = downtimeValue.toFixed(1);
            document.getElementById('optTime').innerText = operatingTimeValue.toFixed(1);
            document.getElementById('product').innerText = tipeBarang;
            document.getElementById('productQt').innerText = tipeBarang;
            document.getElementById('output').innerText = totalItems;
            document.getElementById('outputTime').innerText = outputTime;
            document.getElementById('qtyNg').innerText = oeeMetrics.reject;
            document.getElementById('defectTime').innerText = defectTime;
            document.getElementById('defectTimeQt').innerText = defectTime;
            document.getElementById('cycleTime').innerText = cycleTime;
            document.getElementById('cycleTimeQt').innerText = cycleTime;
            document.getElementById('qtyCycle').innerText = cycleCount.toFixed(1);
            document.getElementById('qtyCycleQt').innerText = cycleCount.toFixed(1);
            var stopLossPercent = (oeeMetrics.downtime / oeeMetrics.runtime) / 100;
            document.getElementById('qualityLossPercent').innerText = stopLossPercent.toFixed(1) + '%';
            document.getElementById('stopTimeLoss').innerText = downtimeValue.toFixed(1);
            document.getElementById('stopLossPercent').innerText = stopLossPercent.toFixed(1) + '%';
            var speedLoss = oeeMetrics.operating_time - outputTime;
            var qualityLossPercent = (defectTime / oeeMetrics.runtime) * 100;
            document.getElementById('speedLoss').innerText = speedLoss.toFixed(1);
            var speedLossPercent = 100 - stopLossPercent - qualityLossPercent.toFixed(1);
            document.getElementById('speedLossPercent').innerText = speedLossPercent.toFixed(1) + '%';
            var oeeCurrent = oeeMetrics.runtime - speedLoss - defectTime - oeeMetrics.downtime;
            if (oeeCurrent < 0) {
                oeeCurrent = 0;
            } else if (oeeCurrent > 100) {
                oeeCurrent = 100;
            }
            document.getElementById('oeeCurrent').innerText = oeeCurrent.toFixed(1);
            document.getElementById('oeePercent').innerText = oeeValue.toFixed(1) + '%';
            oeeLossChart.data.datasets[0].data[0] = stopLossPercent;
            oeeLossChart.data.datasets[0].data[1] = speedLossPercent;
            oeeLossChart.data.datasets[0].data[2] = qualityLossPercent;
            oeeLossChart.data.datasets[0].data[3] = oeeMetrics.oee;
            oeeLossChart.update();
        }

        var fetchOeeMetricsInterval = null;

        function fetchOeeMetrics() {
            $.ajax({
                url: '/calculate-oee',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        updateChart(
                            response.oeeMetrics,
                            response.tipeBarang,
                            response.totalItems,
                            response.cycleTime,
                            response.outputTime,
                            response.cycleCount,
                            response.defectTime
                        );
                    }
                },
                error: function(error) {
                    console.error('Error fetching OEE metrics:', error);
                }
            });
        }

        function startFetchingOeeMetrics() {
            // fetchOeeMetrics();
            if (fetchOeeMetricsInterval !== null) {
                clearInterval(fetchOeeMetricsInterval);
            }
            fetchOeeMetricsInterval = setInterval(fetchOeeMetrics, 1000);
        }

        function stopFetchingOeeMetrics() {
            if (fetchOeeMetricsInterval !== null) {
                clearInterval(fetchOeeMetricsInterval);
                fetchOeeMetricsInterval = null;
            }
        }

        @if ($nearestMachineStartTime)
            var machineStartTime = new Date('{{ $nearestMachineStartTime->start_prod }}');
            var machineEndTime = new Date('{{ $nearestMachineStartTime->finish_prod }}');
            var countdownElement = document.getElementById('countdown');

            function updateCountdown() {
                var now = new Date();
                var timeRemainingStart = machineStartTime - now;
                var timeRemainingEnd = machineEndTime - now;
                var runtime = now - machineStartTime;

                if (timeRemainingEnd > 0 && timeRemainingStart <= 0) {
                    document.getElementById('lineProduksi').innerText = '{{ $nearestMachineStartTime->line }}';
                    document.getElementById('namaLine').innerText = '{{ $nearestMachineStartTime->linedesc }}';
                    document.getElementById('shiftProduksi').innerText = '{{ $nearestMachineStartTime->shift }}';
                    document.getElementById('tipeBarang').innerText = '{{ $nearestMachineStartTime->tipe_barang }}';
                    if (fetchOeeMetricsInterval === null) {
                        startFetchingOeeMetrics();
                    }
                } else {
                    stopFetchingOeeMetrics();
                }
            }

            setInterval(updateCountdown, 1000);
        @elseif ($nearestMachineEndTime)
            var machineStartTime = new Date('{{ $nearestMachineEndTime->start_prod }}');
            var machineEndTime = new Date('{{ $nearestMachineEndTime->finish_prod }}');
            var countdownElement = document.getElementById('countdown');

            function updateCountdown() {
                var now = new Date();
                var timeRemainingStart = machineStartTime - now;
                var timeRemainingEnd = machineEndTime - now;
                var runtime = now - machineStartTime;

                if (timeRemainingEnd > 0 && timeRemainingStart <= 0) {
                    document.getElementById('lineProduksi').innerText = '{{ $nearestMachineEndTime->line }}';
                    document.getElementById('namaLine').innerText = '{{ $nearestMachineEndTime->linedesc }}';
                    document.getElementById('shiftProduksi').innerText = '{{ $nearestMachineEndTime->shift }}';
                    document.getElementById('tipeBarang').innerText = '{{ $nearestMachineEndTime->tipe_barang }}';
                    if (fetchOeeMetricsInterval === null) {
                        startFetchingOeeMetrics();
                    }
                } else {
                    stopFetchingOeeMetrics();
                }
            }

            setInterval(updateCountdown, 1000);
        @else
            // another function here
        @endif

        $(document).ready(function() {
            // fetchOeeMetrics();
            // setInterval(fetchOeeMetrics, 1000); // Check every minute
            setInterval(checkMachineStatus, 1000); // Check every minute

            var machineStatus = '{{ $status }}' === 'on';

            $('#toggleMachineStatus').click(function() {
                $.post('/toggle-machine-status', {
                    _token: '{{ csrf_token() }}',
                    status: machineStatus ? 'on' : 'stop'
                }, function(response) {
                    if (response.status === "on") {
                        $('#toggleMachineStatus').removeClass('btn-danger').addClass(
                                'btn-success')
                            .text('ON');
                        machineStatus = true;
                    } else {
                        $('#toggleMachineStatus').removeClass('btn-success').addClass(
                                'btn-danger')
                            .text('STOP');
                        machineStatus = false;
                    }
                });
            });
        });
    </script>
    <script src="{{ asset('js/bootstrap.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
        integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous">
    </script>
</body>

</html>
