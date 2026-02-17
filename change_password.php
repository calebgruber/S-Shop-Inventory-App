<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getDB()->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

// Check if password reset is required
$mustReset = $user['must_reset_password'] ?? false;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($newPassword) || empty($confirmPassword)) {
            $error = 'Please enter and confirm your new password.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } else {
            // For non-forced resets, verify current password
            if (!$mustReset) {
                if (empty($currentPassword)) {
                    $error = 'Please enter your current password.';
                } elseif (!password_verify($currentPassword, $user['password_hash'])) {
                    $error = 'Current password is incorrect.';
                }
            }
            
            if (empty($error)) {
                // Update password
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                getDB()->query(
                    "UPDATE users SET password_hash = ?, must_reset_password = 0, temp_password = NULL WHERE id = ?",
                    [$newPasswordHash, $userId]
                );
                
                logMessage("User changed password: " . $user['email'], 'INFO');
                
                if ($mustReset) {
                    setAlert('Password changed successfully! You can now access the system.', 'success');
                    header('Location: index');
                    exit;
                } else {
                    $success = 'Password changed successfully!';
                    $user['must_reset_password'] = 0;
                    $mustReset = false;
                }
            }
        }
    } catch (Exception $e) {
        $error = 'An error occurred while changing your password. Please try again.';
        logMessage("Password change error: " . $e->getMessage(), 'ERROR');
    }
}

try {
    $appName = getSetting('app_name', 'CMFT Sound Shop Inventory');
    $logoPath = getSetting('logo_path', '');
} catch (Exception $e) {
    logMessage("Error loading settings: " . $e->getMessage(), 'ERROR');
    $appName = 'CMFT Sound Shop Inventory';
    $logoPath = '';
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Change Password - <?php echo htmlspecialchars($appName); ?></title>
    
    <!-- Apply theme immediately to prevent flash -->
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
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
    <div class="page page-center">
      <div class="container container-tight py-4">
        <div class="text-center mb-4">
          <?php if ($logoPath): 
              $logoFile = basename($logoPath);
              $logoFullPath = __DIR__ . '/uploads/' . $logoFile;
              if (file_exists($logoFullPath)): 
                  $logoExt = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                  if (in_array($logoExt, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'])): ?>
                      <a href="." class="navbar-brand navbar-brand-autodark">
                        <img src="uploads/<?php echo htmlspecialchars($logoFile); ?>" height="36" alt="<?php echo htmlspecialchars($appName); ?>">
                      </a>
          <?php endif; endif; endif; ?>
        </div>
        
        <div class="card card-md">
          <div class="card-body">
            <h2 class="h2 text-center mb-4">
              <?php echo $mustReset ? 'Set Your Password' : 'Change Password'; ?>
            </h2>
            
            <?php if ($mustReset): ?>
            <div class="alert alert-info" role="alert">
              <div class="d-flex">
                <div>
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>
                </div>
                <div>
                  <h4 class="alert-title">Password Change Required</h4>
                  <div class="text-muted">You must set a new password before accessing the system.</div>
                </div>
              </div>
            </div>
            <?php endif; ?>
            
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
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
              <div class="d-flex">
                <div>
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                </div>
                <div>
                  <?php echo htmlspecialchars($success); ?>
                </div>
              </div>
              <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
            <?php endif; ?>
            
            <form action="change_password" method="post" autocomplete="off" novalidate>
              <?php if (!$mustReset): ?>
              <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
              </div>
              <?php endif; ?>
              
              <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Enter new password" minlength="8" required>
                <small class="form-hint">Password must be at least 8 characters long.</small>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" minlength="8" required>
              </div>
              
              <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z" /><path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /><path d="M8 11v-4a4 4 0 1 1 8 0v4" /></svg>
                  Change Password
                </button>
              </div>
            </form>
            
            <?php if (!$mustReset): ?>
            <div class="text-center text-muted mt-3">
              <a href="index" class="text-decoration-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /><path d="M5 12l6 6" /><path d="M5 12l6 -6" /></svg>
                Back to Dashboard
              </a>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
