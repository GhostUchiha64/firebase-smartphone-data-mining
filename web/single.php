<?php
require_once 'config.php';

$showSource = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 if (!empty($_POST['password'])) { tryAuthenticate($_POST['password']); }
 if (isset($_POST['action']) && $_POST['action'] === 'source') {
 if (isAuthenticated()) { $showSource = true; }
 }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Single Prediction | CSCI 515</title>
<?php echo commonCSS(); ?>
<style>
.controls{background:#16213e;border:1px solid #2a2a4a;border-radius:10px;padding:18px;margin-bottom:20px;display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap}
.ctrl-group label{display:block;color:#aaa;font-size:0.88em;font-weight:600;margin-bottom:5px}
#status{color:#4a9eff;font-size:0.88em;min-height:20px;margin-top:10px}
.result-box{background:#0d2e1a;border:1px solid #27ae60;border-radius:8px;padding:14px;margin-top:16px;color:#27ae60;font-size:1em;font-weight:600;display:none}
.leg-dot{width:12px;height:12px;border-radius:50%;display:inline-block;margin-right:5px;vertical-align:middle}
#legend{display:flex;gap:18px;margin-top:10px;flex-wrap:wrap;font-size:0.85em}
</style>
</head>
<body>

<header>
 <div>
 <h1> Show a Prediction</h1>
 <p>Enter a price and see the TensorFlow prediction on the chart</p>
 </div>
 <?php echo navBar('single.php'); ?>
</header>

<div class="container">

 <div class="msg msg-info">
 First train the model by clicking <strong>Train Model</strong>, then enter any price and click <strong>Predict</strong> to see the result plotted as a on the chart.
 </div>

 <!-- Controls -->
 <div class="controls">
 <div class="ctrl-group">
 <label for="yFeature">Y-Axis Feature</label>
 <select id="yFeature">
 <option value="displaySize">Display Size (inches)</option>
 <option value="memory">Memory (GB)</option>
 <option value="resolution">Resolution (px)</option>
 </select>
 </div>
 <div class="ctrl-group">
 <label for="epochsSingle">Training Epochs</label>
 <input type="number" id="epochsSingle" value="200" min="10" max="2000">
 </div>
 <div class="ctrl-group">
 <label>&nbsp;</label>
 <button class="btn btn-green" onclick="trainAndPlot()"> Train Model</button>
 </div>
 </div>

 <!-- Price input (shown after training) -->
 <div id="priceSection" style="display:none;background:#16213e;border:1px solid #2a2a4a;border-radius:10px;padding:18px;margin-bottom:20px">
 <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
 <div>
 <label style="display:block;color:#aaa;font-size:0.88em;font-weight:600;margin-bottom:5px">Enter Price ($)</label>
 <input type="number" id="priceInput" placeholder="e.g. 799" min="0" style="width:150px">
 </div>
 <button class="btn btn-orange" onclick="predictPrice()"> Predict</button>
 </div>
 <div id="resultBox" class="result-box"></div>
 </div>

 <span id="status">Loading data from Firebase…</span>

 <!-- Chart -->
 <div class="chart-wrap" style="margin-top:12px">
 <div id="legend">
 <span><span class="leg-dot" style="background:#4a9eff"></span>Actual Data</span>
 <span><span class="leg-dot" style="background:#e74c3c;border-radius:0"></span>Regression Line</span>
 <span><span class="leg-dot" style="background:#f1c40f"></span>Your Prediction</span>
 </div>
 <canvas id="predChart" height="100"></canvas>
 </div>

 <!-- Display Source -->
 <?php echo sourceButton(); ?>
 <?php if ($showSource): ?>
 <?php echo showSource(['single.php' => __FILE__, 'config.php' => __DIR__ . '/config.php']); ?>
 <?php endif; ?>

</div>

<footer>CSCI 515 Exercise II &nbsp;|&nbsp; Firebase + TensorFlow Data Mining &nbsp;|&nbsp; Siddartha Bandi</footer>

<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
<!-- TensorFlow.js -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.12.0/dist/tf.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
const firebaseConfig = <?php echo JS_FIREBASE_CONFIG; ?>;
firebase.initializeApp(firebaseConfig);
const db = firebase.database();

let allData = [];
let trainedModel = null;
let xStats = null, yStats = null;
let myChart = null;
let lineCache = [];

const featureLabels = {
 displaySize: 'Display Size (inches)',
 memory: 'Memory (GB)',
 resolution: 'Resolution (px)'
};

// Load Firebase data 
async function loadData() {
 const snap = await db.ref('/smartphones').once('value');
 const raw = snap.val();
 if (!raw) return [];
 return Object.values(raw).map(r => ({
 price: parseFloat(r.price) || 0,
 displaySize: parseFloat(r.displaySize) || 0,
 memory: parseFloat(r.memory) || 0,
 resolution: parseFloat(r.resolution) || 0,
 condition: r.condition || ''
 })).filter(r => r.price > 0);
}

// Normalise helpers 
function normalise(arr) {
 const mn = Math.min(...arr), mx = Math.max(...arr);
 return { norm: arr.map(v => (mx === mn) ? 0 : (v - mn) / (mx - mn)), mn, mx };
}
function denorm(v, mn, mx) { return v * (mx - mn) + mn; }

// Train 
async function trainAndPlot() {
 const status = document.getElementById('status');
 const feature = document.getElementById('yFeature').value;
 const epochs = parseInt(document.getElementById('epochsSingle').value) || 200;

 status.textContent = '⏳ Loading data from Firebase…';
 allData = await loadData();

 if (allData.length < 2) {
 status.textContent = ' Need at least 2 records. Add data via the Android app.';
 return;
 }

 const prices = allData.map(r => r.price);
 const features = allData.map(r => r[feature]);

 xStats = normalise(prices);
 yStats = normalise(features);

 status.textContent = `⏳ Training (${epochs} epochs)…`;

 if (trainedModel) { trainedModel.dispose(); trainedModel = null; }

 trainedModel = tf.sequential();
 trainedModel.add(tf.layers.dense({ inputShape: [1], units: 1 }));
 trainedModel.compile({ optimizer: tf.train.adam(0.01), loss: 'meanSquaredError' });

 const xT = tf.tensor2d(xStats.norm, [xStats.norm.length, 1]);
 const yT = tf.tensor2d(yStats.norm, [yStats.norm.length, 1]);
 await trainedModel.fit(xT, yT, { epochs, verbose: 0 });
 xT.dispose(); yT.dispose();

 // Build regression line
 const minP = Math.min(...prices), maxP = Math.max(...prices);
 lineCache = [];
 for (let i = 0; i < 50; i++) {
 const p = minP + i * (maxP - minP) / 49;
 const pN = (xStats.mx === xStats.mn) ? 0 : (p - xStats.mn) / (xStats.mx - xStats.mn);
 const predN = trainedModel.predict(tf.tensor2d([pN], [1, 1])).dataSync()[0];
 lineCache.push({ x: p, y: denorm(predN, yStats.mn, yStats.mx) });
 }

 drawChart(prices, features, lineCache, featureLabels[feature], null);
 status.textContent = ` Model trained on ${allData.length} records. Now enter a price below.`;
 document.getElementById('priceSection').style.display = 'block';
}

// Predict single price 
function predictPrice() {
 if (!trainedModel) { alert('Please train the model first.'); return; }

 const inputPrice = parseFloat(document.getElementById('priceInput').value);
 if (isNaN(inputPrice) || inputPrice <= 0) { alert('Please enter a valid price.'); return; }

 const feature = document.getElementById('yFeature').value;
 const pN = (xStats.mx === xStats.mn) ? 0 : (inputPrice - xStats.mn) / (xStats.mx - xStats.mn);
 const predN = trainedModel.predict(tf.tensor2d([pN], [1, 1])).dataSync()[0];
 const predVal = denorm(predN, yStats.mn, yStats.mx);

 const prices = allData.map(r => r.price);
 const features = allData.map(r => r[feature]);
 drawChart(prices, features, lineCache, featureLabels[feature], [{ x: inputPrice, y: predVal }]);

 const box = document.getElementById('resultBox');
 box.style.display = 'block';
 box.textContent = ` Predicted ${featureLabels[feature]} for $${inputPrice.toFixed(2)}: ${predVal.toFixed(3)}`;
}

// Draw chart 
function drawChart(prices, features, linePoints, yLabel, extraPoints) {
 const ctx = document.getElementById('predChart').getContext('2d');
 if (myChart) { myChart.destroy(); }

 const datasets = [
 {
 label: 'Actual Data',
 data: prices.map((p, i) => ({ x: p, y: features[i] })),
 type: 'scatter',
 backgroundColor: '#4a9eff',
 pointRadius: 6,
 order: 2
 },
 {
 label: 'Regression Line',
 data: linePoints,
 type: 'line',
 borderColor: '#e74c3c',
 backgroundColor: 'transparent',
 borderWidth: 2,
 pointRadius: 0,
 tension: 0,
 order: 1
 }
 ];

 if (extraPoints && extraPoints.length) {
 datasets.push({
 label: 'Your Prediction ',
 data: extraPoints,
 type: 'scatter',
 backgroundColor: '#f1c40f',
 pointRadius: 12,
 pointStyle: 'star',
 order: 0
 });
 }

 myChart = new Chart(ctx, {
 type: 'scatter',
 data: { datasets },
 options: {
 responsive: true,
 plugins: {
 legend: { labels: { color: '#ccc' } },
 tooltip: {
 callbacks: {
 label: ctx => `Price: $${ctx.parsed.x.toFixed(0)}, ${yLabel}: ${ctx.parsed.y.toFixed(3)}`
 }
 }
 },
 scales: {
 x: {
 title: { display: true, text: 'Price ($)', color: '#aaa' },
 ticks: { color: '#aaa' },
 grid: { color: '#1a1a3a' }
 },
 y: {
 title: { display: true, text: yLabel, color: '#aaa' },
 ticks: { color: '#aaa' },
 grid: { color: '#1a1a3a' }
 }
 }
 }
 });
}

// Auto-load on page start
window.addEventListener('load', async () => {
 const status = document.getElementById('status');
 try {
 allData = await loadData();
 if (allData.length < 2) {
 status.textContent = ' Not enough data. Add at least 2 records via the Android app, then click Train Model.';
 } else {
 status.textContent = ` ${allData.length} records loaded. Select a feature and click Train Model.`;
 }
 } catch(e) {
 status.textContent = ' Firebase error: ' + e.message;
 }
});
</script>
</body>
</html>
