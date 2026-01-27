<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Fetch user from database
        $user = getDB()->fetchOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1 AND is_deleted = 0",
            [$email]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Update last login
            getDB()->query(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user['id']]
            );
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$appName = getSetting('app_name', 'CMFT Sound Shop Inventory');
$loginIllustration = getSetting('login_illustration_path', '');
$logoPath = getSetting('logo_path', '');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($appName); ?></title>
    
    <!-- Apply theme immediately to prevent flash -->
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    </script>
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
    
    <style>
        .page {
            display: flex;
            flex-direction: column;
            position: relative;
            min-height: 100vh;
        }
        
        .page-single {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 0;
        }
        
        .login-illustration {
            display: none;
            background: linear-gradient(135deg, var(--tblr-primary) 0%, var(--tblr-primary-darken) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .login-illustration img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
            opacity: 0.9;
        }
        
        @media (min-width: 768px) {
            .login-illustration {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }
        }
        
        .card-login {
            max-width: 24rem;
        }
    </style>
</head>
<body class="d-flex flex-column border-top-wide border-primary">
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="row g-0">
                <?php if ($loginIllustration && file_exists(UPLOAD_DIR . basename($loginIllustration))): ?>
                <!-- Login with illustration (Tabler style) -->
                <div class="col-12 col-md-6 login-illustration">
                    <img src="<?php echo htmlspecialchars(UPLOAD_DIR . basename($loginIllustration)); ?>" alt="Login illustration">
                </div>
                <div class="col-12 col-md-6 d-flex align-items-center justify-content-center p-4">
                <?php else: ?>
                <!-- Login without illustration -->
                <div class="col-12 d-flex align-items-center justify-content-center">
                <?php endif; ?>
                    <div class="card card-login card-md">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <?php if ($logoPath && file_exists(UPLOAD_DIR . basename($logoPath))): 
                                    $logoExt = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                                    if (in_array($logoExt, ['png', 'jpg', 'jpeg', 'gif', 'webp'])): ?>
                                        <img src="<?php echo htmlspecialchars(UPLOAD_DIR . basename($logoPath)); ?>" alt="Logo" style="height: 48px; margin-bottom: 1rem;">
                                <?php endif; endif; ?>
                                <h2 class="h2 text-center mb-2"><?php echo htmlspecialchars($appName); ?></h2>
                                <p class="text-muted">Sign in to your account to continue</p>
                            </div>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible" role="alert">
                                    <div class="d-flex">
                                        <div>
                                            <i class="ti ti-alert-circle icon alert-icon"></i>
                                        </div>
                                        <div>
                                            <?php echo htmlspecialchars($error); ?>
                                        </div>
                                    </div>
                                    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="login.php" autocomplete="off">
                                <div class="mb-3">
                                    <label class="form-label" for="email">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="your@email.com" required autofocus autocomplete="username"
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-2">
                                    <label class="form-label" for="password">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Your password" required autocomplete="current-password">
                                </div>
                                
                                <div class="form-footer">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="ti ti-login icon"></i>
                                        Sign In
                                    </button>
                                </div>
                            </form>
                            
                            <div class="hr-text text-muted mt-3">Help</div>
                            <div class="text-center text-muted small">
                                Default credentials: <code>admin@example.com</code> / <code>admin123</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap & Tabler JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js"></script>
</body>
</html>
