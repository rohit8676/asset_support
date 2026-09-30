<?php
/**
 * Help & Support Module Page
 * IT Asset & Support Management System
 */

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Help & Support';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
    <div>
        <h3 class="fw-bold mb-1">Help &amp; Support</h3>
        <p class="text-muted mb-0">IT Support tickets, incident handling, and service desk</p>
    </div>
</div>

<!-- Placeholder Card for Help & Support Module -->
<div class="content-card p-5 text-center">
    <div class="d-inline-flex p-4 bg-primary bg-opacity-10 text-primary rounded-circle mb-3 fs-2">
        <i class="bi bi-headset"></i>
    </div>
    <h4 class="fw-bold">Help &amp; Support Desk</h4>
    <p class="text-muted col-lg-6 mx-auto mb-4">
        This module will handle user service requests, incident reporting, troubleshooting logs, and resolution tracking.
    </p>
    <a href="<?= url('index.php') ?>" class="btn btn-outline-primary">
        <i class="bi bi-speedometer2 me-1"></i> Back to Dashboard
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
