<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}
$current_page = basename($_SERVER['SCRIPT_NAME']);
$current_dir = basename(dirname($_SERVER['SCRIPT_NAME']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DBKhata - Accounting System</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    .bg-dark-blue {
      background-color: #0d2d52;
    }
    .navbar-dark .navbar-nav .nav-link {
      color: #ffffff;
      opacity: 0.85;
    }
    .navbar-dark .navbar-nav .nav-link:hover,
    .navbar-dark .navbar-nav .nav-link:focus,
    .navbar-dark .navbar-nav .nav-link.active {
      color: #ffffff;
      opacity: 1;
    }
  </style>
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark-blue">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/dashboard.php">
      <i class="bi bi-cash-stack fs-3 me-2"></i>
      <span>DBKhataV2</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" 
            aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <li class="nav-item">
          <a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/dashboard.php">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
          </a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= ($current_dir == 'parties') ? 'active' : '' ?>" href="#" id="partiesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-badge-fill me-1"></i> Parties
          </a>
          <ul class="dropdown-menu" aria-labelledby="partiesDropdown">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/parties/index.php">View Parties</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/parties/add.php">Add Party</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= ($current_dir == 'items') ? 'active' : '' ?>" href="#" id="itemsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-box-seam me-1"></i> Items
          </a>
          <ul class="dropdown-menu" aria-labelledby="itemsDropdown">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/items/index.php">View Items</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/items/add.php">Add Item</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= ($current_dir == 'transactions') ? 'active' : '' ?>" href="#" id="transactionsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-journal-check me-1"></i> Transactions
          </a>
          <ul class="dropdown-menu" aria-labelledby="transactionsDropdown">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/transactions/index.php">View Transactions</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/transactions/add.php">Add Transaction</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= ($current_dir == 'reports') ? 'active' : '' ?>" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-bar-chart-line-fill me-1"></i> Reports
          </a>
          <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/trial_balance.php">Trial Balance</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/ledger.php">Ledger</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/receivables.php">Receivables</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/payables.php">Payables</a></li>
          </ul>
        </li>

      </ul>

      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/auth/logout.php" title="Logout">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </li>
      </ul>

    </div>
  </div>
</nav>

<div class="container mt-4">
