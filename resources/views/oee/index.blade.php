<!DOCTYPE html>
<html>

<head>
    <title>OEE Dashboard</title>
    <!-- Add CSS and JS here -->
    <link href="{{ asset('css/bootstrap.css') }}" rel="stylesheet"> <!-- Tautan CSS Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    </style>
</head>

<body>
    <x-navbar></x-navbar>
    <div class="container my-3">
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
            <div class="text-light" id="downtimeText"></div>
            <div class="text-light" id="operatingTimeText"></div>
        </div>
        <table class="table table-dark m-3">
            <thead>
                <tr>
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
            // var availabilityCtx = document.getElementById('availabilityChart').getContext('2d');
            var performanceCtx = document.getElementById('performanceChart').getContext('2d');
            var qualityCtx = document.getElementById('qualityChart').getContext('2d');


            createGaugeChart(performanceCtx, 65, 'Performance', 'performanceText');
            createGaugeChart(qualityCtx, 91, 'Quality', 'qualityText');
        };

        function fetchAvailability() {
            fetch('/api/oee-availability')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('availabilityText').innerText = data.availability.toFixed(3) + '%';
                    document.getElementById('downtimeText').innerText = data.downtime;
                    document.getElementById('operatingTimeText').innerText = data.operatingTime;
                    updateChart(availabilityChart, data.availability);
                })
                .catch(error => console.error('Error fetching availability:', error));
        }

        function updateChart(chart, value) {
            chart.data.datasets[0].data[0] = value;
            chart.data.datasets[0].data[1] = 100 - value;
            chart.update();
        }

        const availabilityChart = new Chart(document.getElementById('availabilityChart'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [0, 100],
                    backgroundColor: ['#4CAF50', '#E4080A'],
                    borderWidth: 0
                }]
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

        setInterval(fetchAvailability, 60000); // Refresh every minute
        fetchAvailability(); // Initial fetch
    </script>
    <script src="{{ asset('js/bootstrap.js') }}"></script>
</body>

</html>
