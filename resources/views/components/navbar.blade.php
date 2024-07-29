<nav class="navbar border-bottom mb-2 navbar-dark navbar-expand-lg bg-body-dark p-2">
    <div class="container-fluid">
        <a class="navbar-brand" href="/">
            <h5>OEE Monitoring System</h5>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="/items">Insert Item</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Inspect Data
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="/productions">Production</a></li>
                        <li><a class="dropdown-item" href="/metrics">OEE Metrics</a></li>
                    </ul>
                </li>
            </ul>
            <div class="d-flex align-items-center text-light">
                <div class="pe-5 d-flex align-items-center">
                    <span class="text-light me-2"><b>Date</b></span>
                    <span id="date">2021-03-28</span>
                </div>
                <div class="pe-2 d-flex align-items-center">
                    <span class="text-light me-2"><b>Time</b></span>
                    <span id="time">06:24:26</span>
                </div>
            </div>
        </div>
    </div>
</nav>
<script>
    function updateTime() {
        const now = new Date();
        const date = now.toISOString().split('T')[0];
        const time = now.toTimeString().split(' ')[0];
        document.getElementById('date').innerText = date;
        document.getElementById('time').innerText = time;
    }

    setInterval(updateTime, 1000);
    updateTime(); // initial call to set the time immediately
</script>
