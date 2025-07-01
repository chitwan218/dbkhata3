<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php'; // Optional: requireLogin() if page is for logged-in users only
// requireLogin(); // Uncomment if needed

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
  <h1 class="mb-4">Privacy Policy</h1>
  
  <p>At Sneha Photo Studio, your privacy is important to us. This Privacy Policy explains how we collect, use, and protect your personal information when you use our accounting system.</p>
  
  <h3>Information We Collect</h3>
  <ul>
    <li>Personal information such as name, email, and contact details when you register.</li>
    <li>Financial transaction data you enter into the system.</li>
    <li>Usage data for improving our services.</li>
  </ul>

  <h3>How We Use Your Information</h3>
  <ul>
    <li>To provide and maintain our services.</li>
    <li>To improve user experience and system functionality.</li>
    <li>To communicate updates and important information.</li>
  </ul>

  <h3>Data Security</h3>
  <p>We implement appropriate technical and organizational measures to protect your data from unauthorized access, alteration, disclosure, or destruction.</p>

  <h3>Cookies</h3>
  <p>We may use cookies to enhance your experience. You can control cookie preferences through your browser settings.</p>

  <h3>Third-Party Services</h3>
  <p>We do not sell or trade your personal information to third parties. However, some third-party services may be used to provide features; their privacy policies apply.</p>

  <h3>Changes to This Privacy Policy</h3>
  <p>We may update this policy occasionally. Changes will be posted here with updated effective dates.</p>

  <p>If you have any questions about our Privacy Policy, please contact us.</p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
