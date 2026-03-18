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
<title>All Predictions | CSCI 515</title>
<?php echo commonCSS(); ?>
<style>
.controls{background:#16213e;border:1px solid #2a2a4a;border-radius:10px;padding:18px;margin-bottom:20px;display:flex;gap:16px;align-items:center;flex-wrap:wrap}
.controls label{color:#aaa;font-size:0.9em;font-weight:600}
#status{color:#4a9eff;font-size:0.88em;margin-top:10px;min-height:20px}
#legend{display:flex;gap:18px;margin-top:10px;flex-wrap:wrap;font-size:0.85em}
.leg-dot{width:12px;height:12px;border-radius:50%;display:inline-block;margin-right:5px;vertical-align:middle}
</style>
</head>
<body>

<header>
 <div>
 <h1> Show All Predictions</h1>
 <p>TensorFlow.js linear regression – price vs selected feature</p>
 </div>
 <?php echo navBar('predict.php'); ?>
</header>

<div class="container">

 <div class="msg msg-info">
 This page trains a TensorFlow.js linear regression model on your Firebase data and plots actual data points alongside the predicted regression line.
 </div>

 <!-- Controls -->
 <div class="controls">
 <div>
 <label for="yFeature">Y-Axis Feature:</label><br>
 <select id="yFeature" onchange="runModel()">
 <option value="displaySize">Display Size (inches)</option>
 <option value="memory">Memory (GB)</option>
 <option value="resolution">Resolution (px)</option>
 </select>
 </div>
 <div>
 <label for="epochsPred">Training Epochs:</label><br>
 <input type="number" id="epochsPred" value="200" min="10" max="2000" style="width:100px">
 </div>
 <div style="align-self:flex-end">
 <button class="btn btn-green" onclick="runModel()"> Train &amp; Plot</button>
 </div>
 <div style="align-self:flex-end">
 <span id="status">Loading data from Firebase…</span>
 </div>
 </div>

 <!-- Chart -->
 <div class="chart-wrap">
 <div id="legend">
 <span><span class="leg-dot" style="background:#4a9eff"></span>Actual Data</span>
 <span><span class="leg-dot" style="background:#e74c3c;border-radius:0"></span>Regression Line</span>
 </div>
 <canvas id="predChart" height="100"></canvas>
 </div>

 <!-- Display Source -->
 <?php echo sourceButton(); ?>
 <?php if ($showSource): ?>
 <?php echo showSource(['predict.php' => __FILE__, 'config.php' => __DIR__ . '/config.php']); ?>
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
// Firebase init 
const firebaseConfig = <?php echo JS_FIREBASE_CONFIG; ?>;
firebase.initializeApp(firebaseConfig);
const db = firebase.database();

let allData = [];
let myChart = null;

// Load data from Firebase 
function loadData() {
 return new Promise((resolve, reject) => {
 db.ref('/smartphones').once('value')
 .then(snapshot => {
 const raw = snapshot.val();
 if (!raw) { resolve([]); return; }
 allData = Object.values(raw).map(r => ({
 price: parseFloat(r.price) || 0,
 displaySize: parseFloat(r.displaySize) || 0,
 memory: parseFloat(r.memory) || 0,
 resolution: parseFloat(r.resolution) || 0,
 condition: r.condition || ''
 })).filter(r => r.price > 0);
 resolve(allData);
 })
 .catch(reject);
 });
}

// Normalise / denormalise 
function normalise(arr) {
 const mn = Math.min(...arr), mx = Math.max(...arr);
 return { norm: arr.map(v => (mx - mn) === 0 ? 0 : (v - mn) / (mx - mn)), mn, mx };
}
function denorm(val, mn, mx) { return val * (mx - mn) + mn; }

// Train TF.js model 
async function trainModel(prices, features, epochs) {
 const xN = normalise(prices);
 const yN = normalise(features);

 const model = tf.sequential();
 model.add(tf.layers.dense({ inputShape: [1], units: 1 }));
 model.compile({ optimizer: tf.train.adam(0.01), loss: 'meanSquaredError' });

 const xT = tf.tensor2d(xN.norm, [xN.norm.length, 1]);
 const yT = tf.tensor2d(yN.norm, [yN.norm.length, 1]);

 await model.fit(xT, yT, { epochs, verbose: 0 });

 xT.dispose(); yT.dispose();
 return { model, xN, yN };
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
 pointHoverRadius: 8,
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
 label: 'Prediction',
 data: extraPoints,
 type: 'scatter',
 backgroundColor: '#f1c40f',
 pointRadius: 10,
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
 label: ctx => `Price: $${ctx.parsed.x.toFixed(0)}, ${yLabel}: ${ctx.parsed.y.toFixed(2)}`
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

// Main run 
async function runModel(extraPrice) {
 const status = document.getElementById('status');
 const feature = document.getElementById('yFeature').value;
 const epochs = parseInt(document.getElementById('epochsPred').value) || 200;

 const featureLabels = {
 displaySize: 'Display Size (inches)',
 memory: 'Memory (GB)',
 resolution: 'Resolution (px)'
 };

 status.textContent = '⏳ Loading data…';
 try {
 await loadData();
 if (allData.length < 2) {
 status.textContent = ' Not enough data. Add at least 2 records in the Android app.';
 return;
 }

 const prices = allData.map(r => r.price);
 const features = allData.map(r => r[feature]);

 status.textContent = `⏳ Training TensorFlow model (${epochs} epochs)…`;
 const { model, xN, yN } = await trainModel(prices, features, epochs);

 // Build regression line using 50 points across price range
 const minP = Math.min(...prices), maxP = Math.max(...prices);
 const step = (maxP - minP) / 49;
 const linePoints = [];
 for (let i = 0; i < 50; i++) {
 const p = minP + i * step;
 const pN = xN.mx === xN.mn ? 0 : (p - xN.mn) / (xN.mx - xN.mn);
 const predN = model.predict(tf.tensor2d([pN], [1, 1])).dataSync()[0];
 linePoints.push({ x: p, y: denorm(predN, yN.mn, yN.mx) });
 }

 // Extra star point for single prediction
 let extraPoints = null;
 if (extraPrice !== undefined) {
 const pN = xN.mx === xN.mn ? 0 : (extraPrice - xN.mn) / (xN.mx - xN.mn);
 const predN = model.predict(tf.tensor2d([pN], [1, 1])).dataSync()[0];
 const predVal = denorm(predN, yN.mn, yN.mx);
 extraPoints = [{ x: extraPrice, y: predVal }];
 document.getElementById('predResult') &&
 (document.getElementById('predResult').textContent =
 `Predicted ${featureLabels[feature]} for $${extraPrice}: ${predVal.toFixed(3)}`);
 }

 drawChart(prices, features, linePoints, featureLabels[feature], extraPoints);
 status.textContent = ` Done! Model trained on ${allData.length} records.`;
 model.dispose();
 } catch (e) {
 status.textContent = ' Error: ' + e.message;
 }
}

// Auto-run on page load
window.addEventListener('load', () => runModel());
</script>
</body>
</html>
