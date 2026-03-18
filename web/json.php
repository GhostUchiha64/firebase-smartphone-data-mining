<?php
require_once 'config.php';

$showSource = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 if (!empty($_POST['password'])) { tryAuthenticate($_POST['password']); }
 if (isset($_POST['action']) && $_POST['action'] === 'source') {
 if (isAuthenticated()) {
 $showSource = true;
 }
 }
}

// Fetch data from Firebase (server-side)
$raw = fbGet('/smartphones');
$data = json_decode($raw, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Database – JSON View | CSCI 515</title>
<?php echo commonCSS(); ?>
<style>
.json-box{background:#050510;border:1px solid #2a2a4a;border-radius:10px;padding:20px;overflow:auto;max-height:70vh}
.json-box pre{color:#a8ff78;font-size:0.85em;white-space:pre-wrap;word-wrap:break-word;line-height:1.6}
.count-badge{display:inline-block;background:#4a9eff;color:#fff;padding:3px 10px;border-radius:12px;font-size:0.82em;margin-left:10px}
.table-wrap{overflow-x:auto;margin-top:20px}
table{width:100%;border-collapse:collapse;font-size:0.88em}
th{background:#1a2a4a;color:#4a9eff;padding:10px 14px;text-align:left;border-bottom:2px solid #4a9eff}
td{padding:9px 14px;border-bottom:1px solid #1a1a3a;color:#ddd}
tr:hover td{background:#1a1a2e}
.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:0.8em;font-weight:600}
.badge-new{background:#1a4a2e;color:#27ae60}
.badge-exc{background:#1a2e4a;color:#4a9eff}
.badge-vg {background:#2e1a4a;color:#8e44ad}
.badge-gd {background:#2e2e1a;color:#f39c12}
</style>
</head>
<body>

<header>
 <div>
 <h1> Database – JSON View</h1>
 <p>All smartphone data stored in Firebase Realtime Database</p>
 </div>
 <?php echo navBar('json.php'); ?>
</header>

<div class="container">

<?php
$count = is_array($data) ? count($data) : 0;
?>

<div class="msg msg-info">
 Firebase path: <code>/smartphones</code>
 <span class="count-badge"><?php echo $count; ?> record<?php echo $count !== 1 ? 's' : ''; ?></span>
</div>

<?php if ($count === 0): ?>
 <div class="msg msg-err">No data found in Firebase. Use the Android app to add smartphone records first.</div>
<?php else: ?>

 <!-- Table View -->
 <div class="chart-wrap">
 <h3 style="color:#fff;margin-bottom:4px"> Table View</h3>
 <p style="color:#aaa;font-size:0.85em;margin-bottom:12px">Human-readable summary of all stored records</p>
 <div class="table-wrap">
 <table>
 <thead>
 <tr>
 <th>#</th>
 <th>Key</th>
 <th>Price ($)</th>
 <th>Display Size (in)</th>
 <th>Memory (GB)</th>
 <th>Resolution (px)</th>
 <th>Condition</th>
 </tr>
 </thead>
 <tbody>
 <?php
 $i = 1;
 foreach ($data as $key => $row):
 $cond = htmlspecialchars($row['condition'] ?? '—');
 $badge = '';
 if ($cond === 'New') $badge = 'badge-new';
 elseif ($cond === 'Excellent') $badge = 'badge-exc';
 elseif ($cond === 'Very good') $badge = 'badge-vg';
 else $badge = 'badge-gd';
 ?>
 <tr>
 <td><?php echo $i++; ?></td>
 <td style="color:#555;font-size:0.8em"><?php echo htmlspecialchars($key); ?></td>
 <td><?php echo number_format($row['price'] ?? 0, 2); ?></td>
 <td><?php echo $row['displaySize'] ?? '—'; ?></td>
 <td><?php echo $row['memory'] ?? '—'; ?></td>
 <td><?php echo $row['resolution'] ?? '—'; ?></td>
 <td><span class="badge <?php echo $badge; ?>"><?php echo $cond; ?></span></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
 </div>
 </div>

 <!-- Raw JSON View -->
 <div class="chart-wrap" style="margin-top:20px">
 <h3 style="color:#fff;margin-bottom:4px"> Raw JSON</h3>
 <p style="color:#aaa;font-size:0.85em;margin-bottom:12px">Exact data as stored in Firebase</p>
 <div class="json-box">
 <pre><?php echo htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)); ?></pre>
 </div>
 </div>

<?php endif; ?>

 <!-- Display Source -->
 <?php echo sourceButton(); ?>
 <?php if ($showSource): ?>
 <?php echo showSource(['json.php' => __FILE__, 'config.php' => __DIR__ . '/config.php']); ?>
 <?php endif; ?>

</div>

<footer>CSCI 515 Exercise II &nbsp;|&nbsp; Firebase + TensorFlow Data Mining &nbsp;|&nbsp; Siddartha Bandi</footer>
</body>
</html>
