<?php
/**
 * Login Page - Clean Light Theme with Loading State & Top-Right Toast Notifications
 * IT Asset & Support Management System
 */

require_once __DIR__ . '/includes/bootstrap.php';

// If already logged in, redirect to dashboard
if (!empty($_SESSION['user'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'redirect' => url('index.php')]);
        exit;
    }
    header('Location: ' . url('index.php'));
    exit;
}

// Handle Login Submission (AJAX or Standard POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
              (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

    $input = $isAjax ? (json_decode(file_get_contents('php://input'), true) ?? $_POST) : $_POST;

    $usernameOrEmail = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    $response = ['success' => false, 'message' => ''];

    if (empty($usernameOrEmail) || empty($password)) {
        $response['message'] = 'Please enter both username and password.';
    } else {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT id, username, email, full_name, role, status, password
                FROM users 
                WHERE (username = ? OR email = ?)
            ");
            $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
            $user = $stmt->fetch();

            if (!$user) {
                $response['message'] = 'Invalid username or password.';
            } elseif ($user['status'] !== 'active') {
                $response['message'] = 'Your account is deactivated. Please contact an administrator.';
            } elseif ($user['password'] !== $password) { // Plain text comparison
                $response['message'] = 'Invalid username or password.';
            } else {
                // Successful Login
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'        => (int)$user['id'],
                    'username'  => $user['username'],
                    'email'     => $user['email'],
                    'full_name' => $user['full_name'],
                    'role'      => $user['role'],
                    'logged_at' => time(),
                ];

                $response['success'] = true;
                $response['message'] = 'Login successful! Redirecting to dashboard...';
                $response['redirect'] = url('index.php');
            }
        } catch (Exception $e) {
            $response['message'] = 'Database Connection Error: ' . $e->getMessage();
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    }

    if ($response['success']) {
        header('Location: ' . $response['redirect']);
        exit;
    } else {
        $error = $response['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — IT Asset Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #334155;
            position: relative;
        }

        /* Top-Right Toast Notification Container */
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
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
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
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .login-box {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            border: 1px solid #cbd5e1;
            overflow: hidden;
        }

        .login-header {
            background-color: #5B9BD5;
            color: #ffffff;
            padding: 1.5rem 1.75rem;
            text-align: center;
        }

        .login-header h4 {
            font-weight: 700;
            margin: 0;
            font-size: 1.35rem;
            letter-spacing: -0.02em;
        }

        .login-header p {
            margin: 0.35rem 0 0;
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .login-body {
            padding: 2rem 1.75rem 2.25rem;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.875rem;
            color: #475569;
            margin-bottom: 0.35rem;
        }

        .form-control {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 0.65rem 0.85rem;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: #2E75B6;
            box-shadow: 0 0 0 3px rgba(46, 117, 182, 0.15);
        }

        .btn-login {
            background-color: #2E75B6;
            border-color: #2E75B6;
            color: #ffffff;
            font-weight: 600;
            padding: 0.7rem;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-login:hover:not(:disabled) {
            background-color: #235c91;
            border-color: #235c91;
            color: #ffffff;
        }

        .btn-login:disabled {
            background-color: #5B9BD5;
            border-color: #5B9BD5;
            opacity: 0.85;
            cursor: not-allowed;
        }

        .input-group-text {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #64748b;
        }
    </style>
</head>
<body>

<!-- Toast Notification Container in Top-Right Corner -->
<div id="toastContainer" class="toast-container-top-right"></div>

<div class="login-box">
    <div class="login-header">
        <h4>IT Asset & Support Hub</h4>
        <p>Sign in to your account</p>
    </div>

    <div class="login-body">
        <form id="loginForm" method="POST" action="<?= url('login.php') ?>" autocomplete="off">
            <div class="mb-3">
                <label class="form-label" for="username">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Enter username or email" value="" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" value="" required>
                    <button class="btn btn-outline-secondary" type="button" id="btnTogglePass">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" id="btnLogin" class="btn btn-login w-100">
                <span id="btnIcon"><i class="bi bi-box-arrow-in-right"></i></span>
                <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                <span id="btnText">Sign In</span>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Password toggle
    const toggleBtn = document.getElementById('btnTogglePass');
    const passInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    toggleBtn.addEventListener('click', () => {
        if (passInput.type === 'password') {
            passInput.type = 'text';
            toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            passInput.type = 'password';
            toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });

    // Toast Notification Function (Top-Right Corner)
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `custom-toast toast-${type}`;
        
        const iconClass = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        
        toast.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <i class="bi ${iconClass} fs-5"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="btn-close btn-close-white ms-3" aria-label="Close" style="font-size: 0.75rem;"></button>
        `;

        container.appendChild(toast);

        // Close on click
        toast.querySelector('.btn-close').addEventListener('click', () => {
            toast.remove();
        });

        // Auto dismiss after 4 seconds
        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(50px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // AJAX Form Handling with Loading Button Progress State
    const loginForm = document.getElementById('loginForm');
    const btnLogin = document.getElementById('btnLogin');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnIcon = document.getElementById('btnIcon');
    const btnText = document.getElementById('btnText');

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();

        if (!username || !password) {
            showToast('Please enter both username and password.', 'error');
            return;
        }

        // Show Buffering / Round Progress Spinner State
        btnLogin.disabled = true;
        btnIcon.classList.add('d-none');
        btnSpinner.classList.remove('d-none');
        btnText.textContent = 'Verifying...';

        try {
            const response = await fetch('<?= url('login.php') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ username, password })
            });

            const result = await response.json();

            if (result.success) {
                // Success Toast on Top-Right
                showToast(result.message || 'Login successful!', 'success');
                btnText.textContent = 'Success! Redirecting...';
                
                setTimeout(() => {
                    window.location.href = result.redirect || '<?= url('index.php') ?>';
                }, 700);
            } else {
                // Error Toast on Top-Right
                showToast(result.message || 'Invalid username or password.', 'error');
                
                // Reset Button
                btnLogin.disabled = false;
                btnSpinner.classList.add('d-none');
                btnIcon.classList.remove('d-none');
                btnText.textContent = 'Sign In';
            }
        } catch (err) {
            console.error('Login error:', err);
            showToast('A network or server error occurred. Please try again.', 'error');

            // Reset Button
            btnLogin.disabled = false;
            btnSpinner.classList.add('d-none');
            btnIcon.classList.remove('d-none');
            btnText.textContent = 'Sign In';
        }
    });
});
</script>

</body>
</html>
