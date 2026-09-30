<?php
/**
 * Shared Header Layout Component
 * IT Asset & Support Management System
 */

if (empty($_SESSION['user'])) {
    header('Location: ' . url('login.php'));
    exit;
}

$currentUser = $_SESSION['user'];
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?>IT Asset &amp; Support Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }

        /* Native Bootstrap Form Select Styling */
        .form-select, .form-control {
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            font-size: 0.925rem;
            color: #1e293b;
            background-color: #ffffff;
        }

        .form-select:focus, .form-control:focus {
            border-color: #1e5c94;
            box-shadow: 0 0 0 0.2rem rgba(30, 92, 148, 0.15);
        }

        /* Top-Right Toast Notifications Container */
        .toast-container-top-right {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-width: 380px;
            width: 100%;
        }

        .custom-toast {
            border-radius: 8px;
            padding: 0.85rem 1.15rem;
            color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideIn 0.25s ease-out;
            font-size: 0.925rem;
            font-weight: 500;
        }

        .custom-toast.toast-success {
            background-color: #10b981;
            border-left: 5px solid #059669;
        }

        .custom-toast.toast-error {
            background-color: #ef4444;
            border-left: 5px solid #dc2626;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Sticky Navigation Header Wrapper */
        .sticky-header-container {
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        /* 1. Top Header Bar */
        .top-header-bar {
            background-color: #f0f6fc;
            color: #0f172a;
            padding: 0.85rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #cbdbe9;
        }

        .app-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.015em;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-section {
            font-size: 1rem;
            font-weight: 500;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .user-btn {
            color: #0f172a !important;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.85rem;
            border-radius: 8px;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            transition: all 0.15s ease;
        }

        .user-btn:hover {
            background-color: #f8fafc;
            border-color: #94a3b8;
        }

        /* 2. Secondary Navigation Bar - Centered */
        .sub-nav-bar {
            background-color: #1e5c94;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.25rem;
        }

        .sub-nav-link {
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 500;
            text-decoration: none;
            padding: 0.95rem 1.6rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.15s ease;
            border-bottom: 4px solid transparent;
            opacity: 0.92;
            letter-spacing: -0.01em;
        }

        .sub-nav-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.15);
            opacity: 1;
        }

        .sub-nav-link.active {
            color: #ffffff;
            font-weight: 600;
            border-bottom-color: #ffffff;
            background-color: rgba(0, 0, 0, 0.15);
            opacity: 1;
        }

        /* 3. Main Workspace Canvas */
        .main-workspace {
            padding: 2rem 2.5rem;
            min-height: calc(100vh - 120px);
            background-color: #f8fafc;
        }

        .content-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            margin-bottom: 1.5rem;
        }

        .content-card .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.25rem;
            font-weight: 600;
            font-size: 1rem;
            color: #1e293b;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.15rem 1.35rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            height: 100%;
        }

        .table-custom th {
            font-weight: 600;
            font-size: 0.85rem;
            color: #475569;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1rem;
        }

        .table-custom td {
            font-size: 0.9rem;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }

        .table-custom tbody tr:hover {
            background-color: #f8fafc;
        }
    </style>
</head>
<body>

<!-- Toast Notification Container -->
<div id="toastContainer" class="toast-container-top-right"></div>

<!-- Sticky Header Container for Both Bars -->
<div class="sticky-header-container">
    <!-- 1. Top Header Bar -->
    <header class="top-header-bar">
        <a href="<?= url('index.php') ?>" class="app-title">
            <i class="bi bi-cpu text-primary fs-4"></i>
            <span>IT Asset &amp; Support Management System</span>
        </a>

        <div class="user-section dropdown">
            <button class="btn btn-link user-btn dropdown-toggle p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle fs-5"></i>
                <span><?= e($currentUser['full_name'] ?: $currentUser['username']) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border: 1px solid #e2e8f0;">
                <li><div class="dropdown-item-text small text-muted">Role: <strong><?= e($currentUser['role']) ?></strong></div></li>
                <li><div class="dropdown-item-text small text-muted">Email: <?= e($currentUser['email']) ?></div></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item small" href="<?= url('database_setup.php') ?>"><i class="bi bi-database me-2"></i> Database Setup Tool</a></li>
                <li><a class="dropdown-item small text-danger" href="<?= url('logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
            </ul>
        </div>
    </header>

    <!-- 2. Centered Secondary Navigation Bar -->
    <nav class="sub-nav-bar">
        <a href="<?= url('index.php') ?>" class="sub-nav-link <?= in_array($currentPage, ['index', 'dashboard']) ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?= url('asset_stock.php') ?>" class="sub-nav-link <?= $currentPage === 'asset_stock' ? 'active' : '' ?>">
            <i class="bi bi-laptop"></i>
            <span>Asset &amp; Stock Mgt</span>
        </a>
        <a href="<?= url('handover.php') ?>" class="sub-nav-link <?= $currentPage === 'handover' ? 'active' : '' ?>">
            <i class="bi bi-person-badge"></i>
            <span>HandOver</span>
        </a>
        <a href="<?= url('help_support.php') ?>" class="sub-nav-link <?= $currentPage === 'help_support' ? 'active' : '' ?>">
            <i class="bi bi-headset"></i>
            <span>Help &amp; Support</span>
        </a>
        <a href="<?= url('lifecycle_recovery.php') ?>" class="sub-nav-link <?= $currentPage === 'lifecycle_recovery' ? 'active' : '' ?>">
            <i class="bi bi-arrow-repeat"></i>
            <span>LifeCycle &amp; Recovery</span>
        </a>
    </nav>
</div>

<!-- 3. Main Workspace Container -->
<main class="main-workspace">
