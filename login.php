<?php
/**
 * Login Page
 * Tabler login with cover image support
 */

require_once __DIR__ . '/config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Query user
        $result = executeQuery(
            'SELECT id, username, password, first_name, last_name, role, avatar_seed FROM users WHERE username = ? OR email = ?',
            [$username, $username],
            'ss'
        );
        
        if ($result && numRows($result) > 0) {
            $user = fetchAssoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['theme'] = 'light'; // Default theme
                
                // Redirect to dashboard
                header('Location: ' . BASE_URL . 'dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

// Get settings
$siteName = getSetting('site_name', 'S-Shop Inventory System');
$siteLogo = getSetting('site_logo', null);
$loginCover = getSetting('login_cover', null);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($siteName); ?></title>
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css" rel="stylesheet">
    
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .page-cover {
            min-height: 100vh;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            <?php if ($loginCover && file_exists(UPLOADS_PATH . '/covers/' . $loginCover)): ?>
            background-image: url('<?php echo BASE_URL . 'uploads/covers/' . htmlspecialchars($loginCover); ?>');
            <?php else: ?>
            background-image: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 1080"%3E%3Cdefs%3E%3ClinearGradient id="grad" x1="0%25" y1="0%25" x2="100%25" y2="100%25"%3E%3Cstop offset="0%25" style="stop-color:%230054a6;stop-opacity:1" /%3E%3Cstop offset="100%25" style="stop-color:%2300b4d8;stop-opacity:1" /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width="1920" height="1080" fill="url(%23grad)" /%3E%3C/svg%3E');
            <?php endif; ?>
        }
        .card-login {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
        }
        @media (prefers-color-scheme: dark) {
            .card-login {
                background-color: rgba(26, 32, 44, 0.95);
            }
        }
    </style>
</head>
<body class="d-flex flex-column">
    <div class="page page-center page-cover">
        <div class="container container-tight py-4">
            <div class="card card-md card-login">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if ($siteLogo && file_exists(UPLOADS_PATH . '/logos/' . $siteLogo)): ?>
                            <img src="<?php echo BASE_URL . 'uploads/logos/' . htmlspecialchars($siteLogo); ?>" 
                                 height="80" alt="<?php echo htmlspecialchars($siteName); ?>" 
                                 class="mb-3">
                        <?php else: ?>
                            <h1 class="mb-3"><?php echo htmlspecialchars($siteName); ?></h1>
                        <?php endif; ?>
                        <p class="text-muted">Sign in to your account to continue</p>
                    </div>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div>
                                <i class="ti ti-alert-circle me-2"></i>
                            </div>
                            <div>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div>
                                <i class="ti ti-check me-2"></i>
                            </div>
                            <div>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" autocomplete="off">
                        <div class="mb-3">
                            <label class="form-label">Username or Email</label>
                            <input type="text" class="form-control" name="username" placeholder="Enter username or email" autofocus required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                        </div>
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-login me-2"></i>
                                Sign In
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center text-muted mt-3">
                        <small>Default credentials: admin / admin123</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
</body>
</html>
