<?php
require_once 'config.php';

$message = '';
$msgType = '';
$showSource = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 // Authenticate if password submitted
 if (!empty($_POST['password'])) {
 if (!tryAuthenticate($_POST['password'])) {
 $message = ' Incorrect password. Please try again.';
 $msgType = 'err';
 } else {
 $message = ' System unlocked successfully.';
 $msgType = 'ok';
 }
 }
 // Clear System
 if (isset($_POST['action']) && $_POST['action'] === 'clear') {
 if (isAuthenticated()) {
 fbDelete('/smartphones');
 $message = ' System cleared! All smartphone data has been removed from Firebase.';
 $msgType = 'ok';
 } else {
 $message = ' Enter the password first to clear the system.';
 $msgType = 'err';
 }
 }
 // Display Source
 if (isset($_POST['action']) && $_POST['action'] === 'source') {
 if (!empty($_POST['password'])) { tryAuthenticate($_POST['password']); }
 if (isAuthenticated()) {
 $showSource = true;
 } else {
 $message = ' Incorrect password.';
 $msgType = 'err';
 }
 }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Firebase Data Mining System – CSCI 515</title>
<?php echo commonCSS(); ?>
</head>
<body>

<header>
 <div>
 <h1> Firebase Data Mining System</h1>
 <p>CSCI 515 – Exercise II &nbsp;|&nbsp; Siddartha Bandi</p>
 </div>
 <?php echo navBar('index.php'); ?>
</header>

<div class="container">

 <?php if ($message): ?>
 <div class="msg msg-<?php echo $msgType; ?>"><?php echo $message; ?></div>
 <?php endif; ?>

 <!-- Password unlock (shown only when not authenticated) -->
 <?php if (!isAuthenticated()): ?>
 <div class="pw-box">
 <h3> System Access</h3>
 <p>Enter the system password once to enable <strong>Clear System</strong> and <strong>Display Source</strong> for this session.</p>
 <form method="POST" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
 <input type="password" name="password" placeholder="Enter password" required>
 <button type="submit" class="btn btn-blue">Unlock</button>
 </form>
 </div>
 <?php else: ?>
 <div class="msg msg-ok"> System unlocked — Clear System and Display Source are enabled.</div>
 <?php endif; ?>

 <!-- Feature cards -->
 <div class="cards">

 <!-- Clear System -->
 <div class="card">
 <h2> Clear System</h2>
 <p>Remove all smartphone data from the Firebase database. Use this before loading test data.</p>
 <?php if (isAuthenticated()): ?>
 <form method="POST" onsubmit="return confirm('Delete ALL data from Firebase? This cannot be undone.');">
 <input type="hidden" name="action" value="clear">
 <button type="submit" class="btn btn-red">Clear System</button>
 </form>
 <?php else: ?>
 <button class="btn btn-gray" onclick="alert('Please unlock the system above first.')">Clear System</button>
 <?php endif; ?>
 </div>

 <!-- Check Database -->
 <div class="card">
 <h2> Check Database (JSON)</h2>
 <p>View all smartphone data currently stored in Firebase, displayed in JSON format.</p>
 <a href="json.php" class="btn btn-blue">View JSON Data</a>
 </div>

 <!-- All Predictions -->
 <div class="card">
 <h2> Show All Predictions</h2>
 <p>Plot all data and the TensorFlow regression line. Choose Display Size, Memory, or Resolution for the Y-axis.</p>
 <a href="predict.php" class="btn btn-green">Open Chart</a>
 </div>

 <!-- Single Prediction -->
 <div class="card">
 <h2> Show a Prediction</h2>
 <p>Enter a price and see where the TensorFlow model predicts the selected feature value on the chart.</p>
 <a href="single.php" class="btn btn-orange">Predict by Price</a>
 </div>

 <!-- Loss -->
 <div class="card">
 <h2> Show Loss</h2>
 <p>Enter the number of training epochs and view the TensorFlow model's loss curve over time.</p>
 <a href="loss.php" class="btn btn-purple">View Loss Chart</a>
 </div>

 <!-- Display Source -->
 <div class="card">
 <h2> Display Source</h2>
 <p>View all server-side PHP source code and Android Java code for this system.</p>
 <?php if (isAuthenticated()): ?>
 <form method="POST" style="display:inline">
 <input type="hidden" name="action" value="source">
 <button type="submit" class="btn btn-gray">Display Source</button>
 </form>
 <?php else: ?>
 <button class="btn btn-gray" onclick="alert('Please unlock the system above first.')">Display Source</button>
 <?php endif; ?>
 </div>

 </div><!-- /cards -->

 <!-- Source code section -->
 <?php if ($showSource): ?>
 <?php echo showSource([
 'index.php' => __FILE__,
 'config.php' => __DIR__ . '/config.php',
 'json.php' => __DIR__ . '/json.php',
 'predict.php'=> __DIR__ . '/predict.php',
 'single.php' => __DIR__ . '/single.php',
 'loss.php' => __DIR__ . '/loss.php',
 ]); ?>
 <?php endif; ?>

</div><!-- /container -->

<footer>CSCI 515 Exercise II &nbsp;|&nbsp; Firebase + TensorFlow Data Mining &nbsp;|&nbsp; Siddartha Bandi</footer>
</body>
</html>
