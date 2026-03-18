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
<title>Loss Chart | CSCI 515</title>
<?php echo commonCSS(); ?>
<style>
.controls{background:#16213e;border:1px solid #2a2a4a;border-radius:10px;padding:18px;margin-bottom:20px;display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap}
.ctrl-group label{display:block;color:#aaa;font-size:0.88em;font-weight:600;margin-bottom:5px}
#status{color:#4a9eff;font-size:0.88em;min-height:20px;margin-top:10px}
.stats-row{display:flex;gap:12px;flex-wrap:wrap;margin-top:16px}
.stat-card{background:#16213e;border:1px solid #2a2a4a;border-radius:8px;padding:14px 20px;text-align:center;min-width:140px}
.stat-card .val{font-size:1.4em;color:#4a9eff;font-weight:700}
.stat-card .lbl{font-size:0.8em;color:#888;margin-top:3px}
</style>
</head>
<body>

<header>
 <div>
 <h1> Show Loss</h1>
 <p>TensorFlow.js training loss curve over epochs</p>
 </div>
 <?php echo navBar('loss.php'); ?>
</header>

<div class="container">

 <div class="msg msg-info">
 Enter the number of epochs and a Y-axis feature, then click <strong>Train &amp; Show Loss</strong>. The chart shows how the model's Mean Squared Error (MSE) decreases as training progresses.
 </div>

 <!-- Controls -->
 <div class="controls">
 <div class="ctrl-group">
 <label for="yFeature">Feature (Y-Axis for regression)</label>
 <select id="yFeature">
 <option value="displaySize">Display Size (inches)</option>
 <option value="memory">Memory (GB)</option>
 <option value="resolution">Resolution (px)</option>
 </select>
 </div>
 <div class="ctrl-group">
 <label for="epochsInput">Number of Epochs</label>
 <input type="number" id="epochsInput" value="100" min="5" max="1000" style="width:120px">
 </div>
 <div class="ctrl-group">
 <label>&nbsp;</label>
 <button class="btn btn-purple" onclick="runLoss()"> Train &amp; Show Loss</button>
 </div>
 </div>

 <span id="status">Loading data from Firebase…</span>

 <!-- Stats row (shown after training) -->
 <div class="stats-row" id="statsRow" style="display:none">
 <div class="stat-card"><div class="val" id="statEpochs">—</div><div class="lbl">Epochs</div></div>
 <div class="stat-card"><div class="val" id="statInitLoss">—</div><div class="lbl">Initial Loss</div></div>
 <div class="stat-card"><div class="val" id="statFinalLoss">—</div><div class="lbl">Final Loss</div></div>
 <div class="stat-card"><div class="val" id="statImprove">—</div><div class="lbl">Improvement</div></div>
 </div>

 <!-- Loss Chart -->
 <div class="chart-wrap" style="margin-top:16px">
 <canvas id="lossChart" height="90"></canvas>
 </div>

 <!-- Display Source -->
 <?php echo sourceButton(); ?>
 <?php if ($showSource): ?>
 <?php echo showSource(['loss.php' => __FILE__, 'config.php' => __DIR__ . '/config.php']); ?>
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
let lossChart = null;

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
 })).filter(r => r.price > 0);
}

// Normalise 
function normalise(arr) {
 const mn = Math.min(...arr), mx = Math.max(...arr);
 return arr.map(v => (mx === mn) ? 0 : (v - mn) / (mx - mn));
}

// Train & collect loss 
async function runLoss() {
 const status = document.getElementById('status');
 const feature = document.getElementById('yFeature').value;
 const epochs = parseInt(document.getElementById('epochsInput').value) || 100;

 if (epochs < 5 || epochs > 1000) { alert('Please enter epochs between 5 and 1000.'); return; }

 status.textContent = '⏳ Loading data from Firebase…';
 allData = await loadData();

 if (allData.length < 2) {
 status.textContent = ' Need at least 2 records. Add data via the Android app.';
 return;
 }

 const prices = allData.map(r => r.price);
 const features = allData.map(r => r[feature]);

 const xN = normalise(prices);
 const yN = normalise(features);

 const model = tf.sequential();
 model.add(tf.layers.dense({ inputShape: [1], units: 1 }));
 model.compile({ optimizer: tf.train.adam(0.01), loss: 'meanSquaredError' });

 const xT = tf.tensor2d(xN, [xN.length, 1]);
 const yT = tf.tensor2d(yN, [yN.length, 1]);

 const lossHistory = [];
 const batchSize = Math.max(1, Math.floor(epochs / 50)); // update status every ~50 steps

 status.textContent = `⏳ Training… 0 / ${epochs} epochs`;

 await model.fit(xT, yT, {
 epochs,
 verbose: 0,
 callbacks: {
 onEpochEnd: (epoch, logs) => {
 lossHistory.push(parseFloat(logs.loss.toFixed(6)));
 if ((epoch + 1) % batchSize === 0 || epoch === epochs - 1) {
 status.textContent = `⏳ Training… ${epoch + 1} / ${epochs} epochs | Loss: ${logs.loss.toFixed(6)}`;
 }
 }
 }
 });

 xT.dispose(); yT.dispose(); model.dispose();

 // Draw chart
 drawLossChart(lossHistory);

 // Show stats
 const initLoss = lossHistory[0];
 const finalLoss = lossHistory[lossHistory.length - 1];
 const improve = (((initLoss - finalLoss) / initLoss) * 100).toFixed(1);
 document.getElementById('statEpochs').textContent = epochs;
 document.getElementById('statInitLoss').textContent = initLoss.toFixed(6);
 document.getElementById('statFinalLoss').textContent = finalLoss.toFixed(6);
 document.getElementById('statImprove').textContent = improve + '%';
 document.getElementById('statsRow').style.display = 'flex';

 const featureLabels = { displaySize: 'Display Size', memory: 'Memory', resolution: 'Resolution' };
 status.textContent = ` Training complete. Feature: ${featureLabels[feature]}, ${allData.length} records, ${epochs} epochs.`;
}

// Draw loss chart 
function drawLossChart(lossHistory) {
 const ctx = document.getElementById('lossChart').getContext('2d');
 if (lossChart) { lossChart.destroy(); }

 const labels = lossHistory.map((_, i) => i + 1);

 lossChart = new Chart(ctx, {
 type: 'line',
 data: {
 labels,
 datasets: [{
 label: 'MSE Loss',
 data: lossHistory,
 borderColor: '#8e44ad',
 backgroundColor: 'rgba(142,68,173,0.1)',
 borderWidth: 2,
 pointRadius: lossHistory.length > 100 ? 0 : 3,
 fill: true,
 tension: 0.3
 }]
 },
 options: {
 responsive: true,
 plugins: {
 legend: { labels: { color: '#ccc' } },
 tooltip: {
 callbacks: {
 label: ctx => `Epoch ${ctx.parsed.x}: Loss = ${ctx.parsed.y.toFixed(6)}`
 }
 }
 },
 scales: {
 x: {
 title: { display: true, text: 'Epoch', color: '#aaa' },
 ticks: { color: '#aaa', maxTicksLimit: 15 },
 grid: { color: '#1a1a3a' }
 },
 y: {
 title: { display: true, text: 'Loss (MSE)', color: '#aaa' },
 ticks: { color: '#aaa' },
 grid: { color: '#1a1a3a' }
 }
 }
 }
 });
}

// Auto-load status on page load
window.addEventListener('load', async () => {
 const status = document.getElementById('status');
 try {
 allData = await loadData();
 if (allData.length < 2) {
 status.textContent = ' Not enough data. Add at least 2 records via the Android app.';
 } else {
 status.textContent = ` ${allData.length} records ready. Select epochs and click Train.`;
 }
 } catch(e) {
 status.textContent = ' Firebase error: ' + e.message;
 }
});
</script>
</body>
</html>
