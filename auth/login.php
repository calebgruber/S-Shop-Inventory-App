<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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
                
                logMessage("User logged in: " . $email, 'INFO');
                
                // Check if user must reset password
                if ($user['must_reset_password']) {
                    header('Location: /change_password');
                    exit;
                }
                
                header('Location: /');
                exit;
            } else {
                $error = 'Invalid email or password.';
                logMessage("Failed login attempt for email: " . $email, 'WARNING');
            }
        }
    } catch (Exception $e) {
        $error = 'An error occurred during login. Please try again.';
        logMessage("Login error: " . $e->getMessage(), 'ERROR');
    }
}

try {
    $appName = getSetting('app_name', 'CMFT Sound Shop Inventory');
    $loginCoverImage = getSetting('login_cover_image', '');
    $logoPath = getSetting('logo_path', '');
    $supportEmail = getSetting('support_email', 'support@example.com');
    $loginIllustration = getSetting('login_illustration_path', '');
    $bannerRotationInterval = getSetting('banner_rotation_interval', '5000');
    
    // Fetch active login banners
    $loginBanners = getDB()->fetchAll(
        "SELECT file_path FROM login_banners WHERE is_active = 1 ORDER BY display_order, uploaded_at DESC"
    );
} catch (Exception $e) {
    logMessage("Error loading settings: " . $e->getMessage(), 'ERROR');
    $appName = 'CMFT Sound Shop Inventory';
    $loginCoverImage = '';
    $logoPath = '';
    $supportEmail = 'support@example.com';
    $loginIllustration = '';
    $bannerRotationInterval = '5000';
    $loginBanners = [];
}
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Sign in - <?php echo htmlspecialchars($appName); ?></title>
    <?php require_once __DIR__ . '/../includes/favicon.php'; ?>
    
    <!-- Force dark mode for login page -->
    <script>
        // Override any saved theme preference - login page is always dark
        document.documentElement.setAttribute('data-bs-theme', 'dark');
    </script>
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
    
    <style>
      @import url('https://rsms.me/inter/inter.css');
      :root {
      	--tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
      }
      body {
      	font-feature-settings: "cv03", "cv04", "cv11";
      }
    </style>
  </head>
  <body class="d-flex flex-column">
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js"></script>
    <div class="row g-0 flex-fill">
      <div class="col-12 col-lg-6 col-xl-4 border-top-wide border-primary d-flex flex-column justify-content-center">
        <div class="container container-tight my-5 px-lg-5">
          <div class="text-center mb-4">
            <?php if ($logoPath): 
                $logoFile = basename($logoPath);
                $logoFullPath = __DIR__ . '/../uploads/' . $logoFile;
                if (file_exists($logoFullPath)): 
                    $logoExt = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                    if (in_array($logoExt, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'])): ?>
                        <a href="." class="navbar-brand navbar-brand-autodark">
                          <img src="/uploads/<?php echo htmlspecialchars($logoFile); ?>" height="56" alt="<?php echo htmlspecialchars($appName); ?>">
                        </a>
            <?php endif; endif; endif; ?>
          </div>
          <h2 class="h3 text-center mb-3">
            Login to your account
          </h2>
          
          <?php if ($error): ?>
          <div class="alert alert-danger alert-dismissible" role="alert">
            <div class="d-flex">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 8v4" /><path d="M12 16h.01" /></svg>
              </div>
              <div>
                <?php echo htmlspecialchars($error); ?>
              </div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
          </div>
          <?php endif; ?>
          
          <form action="login" method="post" autocomplete="off" novalidate>
            <div class="mb-3">
              <label class="form-label">Email address</label>
              <input type="email" name="email" class="form-control" placeholder="your@email.com" autocomplete="off" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Password</label>
              <div class="input-group input-group-flat">
                <input type="password" name="password" class="form-control" placeholder="Your password" autocomplete="off" required>
              </div>
            </div>
            <div class="form-footer">
              <button type="submit" class="btn btn-primary w-100">Sign in</button>
            </div>
          </form>
          
          <div class="text-center text-muted mt-3">
            <small>Forgot your password? Please email <a href="mailto:<?php echo htmlspecialchars($supportEmail); ?>"><?php echo htmlspecialchars($supportEmail); ?></a></small>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-6 col-xl-8 d-none d-lg-block">
        <!-- Photo with rotating banners -->
        <div id="login-background" class="bg-cover h-100 min-vh-100" style="background-image: url(<?php 
          // Determine initial background image
          $coverImagePath = '';
          if (!empty($loginBanners)) {
            // Use first active banner
            $coverImagePath = htmlspecialchars($loginBanners[0]['file_path']);
          } elseif ($loginIllustration && file_exists(__DIR__ . '/../uploads/' . basename($loginIllustration))) {
            $coverImagePath = '../uploads/' . htmlspecialchars(basename($loginIllustration));
          } elseif ($loginCoverImage && file_exists(__DIR__ . '/../uploads/' . basename($loginCoverImage))) {
            $coverImagePath = '../uploads/' . htmlspecialchars(basename($loginCoverImage));
          } else {
            $coverImagePath = 'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80';
          }
          echo $coverImagePath;
        ?>); transition: background-image 1s ease-in-out;"></div>
        
        <?php if (count($loginBanners) > 1): ?>
        <script>
          // Rotating banner functionality
          const banners = <?php echo json_encode(array_column($loginBanners, 'file_path'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
          let currentBannerIndex = 0;
          const backgroundElement = document.getElementById('login-background');
          const rotationInterval = <?php echo (int)$bannerRotationInterval; ?>;
          
          function rotateBanner() {
            if (banners.length <= 1) return;
            
            currentBannerIndex = (currentBannerIndex + 1) % banners.length;
            // Escape and quote the URL for CSS
            const escapedUrl = CSS.escape(banners[currentBannerIndex]);
            backgroundElement.style.backgroundImage = `url("${escapedUrl}")`;
          }
          
          // Rotate at configured interval
          setInterval(rotateBanner, rotationInterval);
        </script>
        <?php endif; ?>
      </div>
    </div>
  </body>
</html>
