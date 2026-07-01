<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
    <title>Is Your Email PWNED?</title>
</head>
<body>

    <section class="hero-section">
        <h1>Is Your Email <span class="red">PWNED?</span></h1>
        <div class="search-box">
            <input type="text" id="emailInput" placeholder="Email here">
            <button onclick="checkEmail()">CHECK</button>
        </div>
        <a href="#stats" class="stats-link">See Statistic</a>
    </section>

    <section id="resultSection" class="result-section" style="display:none;">
        <div class="account-analysis">
            <h2>ACCOUNT ANALYSIS</h2>
            <div class="cards-container">
                <div class="card">EMAIL: <br><span id="displayEmail"></span></div>
                <div id="statusCard" class="card">BREACH STATUS: <br> <b id="statusText"></b></div>
                <div class="card">NEXT ACTION: <br> <span id="nextAction"></span></div>
            </div>
            <div class="table-container">
                </div>
        </div>
    </section>

    <section id="stats" class="stats-section">
        <h2>SYSTEM STATISTICS</h2>
        </section>

    <script src="<?= base_url('js/script.js') ?>"></script>
</body>
</html>