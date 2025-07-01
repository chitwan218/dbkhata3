<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php'; // Optional: requireLogin() if page is for logged-in users only
// requireLogin(); // Uncomment if needed

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
  <h1 class="mb-4">Terms and Conditions</h1>
  
  <p>Welcome to Sneha Photo Studio's accounting system. By accessing or using our service, you agree to be bound by the following terms and conditions:</p>

  <h3>Use of Service</h3>
  <p>You agree to use the service for lawful purposes only and not to misuse or disrupt it in any way.</p>

  <h3>Account Security</h3>
  <p>You are responsible for maintaining the confidentiality of your account and password and for restricting access to your computer.</p>

  <h3>Intellectual Property</h3>
  <p>All content and software provided by us remain our intellectual property and are protected by applicable laws.</p>

  <h3>Limitation of Liability</h3>
  <p>We do not guarantee the accuracy or reliability of the information entered into the system and are not liable for any damages arising from its use.</p>

  <h3>Modifications</h3>
  <p>We reserve the right to modify these terms at any time. Changes will be posted on this page.</p>

  <h3>Governing Law</h3>
  <p>These terms shall be governed by and construed in accordance with the laws applicable in Nepal.</p>

  <p>If you do not agree to these terms, please do not use the service.</p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
