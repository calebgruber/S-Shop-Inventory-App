<?php
/**
 * User Profile
 */

require_once __DIR__ . '/config/config.php';

requireAuth();

$pageName = 'Profile';
$currentUser = getCurrentUser();

include __DIR__ . '/includes/header.php';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-user me-2"></i>
                    Profile
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <span class="avatar avatar-xl mb-3" style="background-image: url(https://api.dicebear.com/7.x/thumbs/svg?seed=<?php echo urlencode($currentUser['avatar_seed'] ?? $currentUser['username']); ?>&backgroundColor=<?php echo rand(0, 1) ? 'b6e3f4,c0aede,d1d4f9' : 'transparent'; ?>)"></span>
                            <h3><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h3>
                            <p class="text-muted"><?php echo ucfirst(htmlspecialchars(str_replace('_', ' ', $currentUser['role']))); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['username']); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($currentUser['email']); ?>" readonly>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle me-2"></i>
                            Profile editing coming soon. Contact an administrator to update your information.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Hotkey Configuration</h3>
                    </div>
                    <div class="card-body">
                        <div class="empty">
                            <div class="empty-icon">
                                <i class="ti ti-keyboard icon"></i>
                            </div>
                            <p class="empty-title">Hotkey Configuration - Coming Soon</p>
                            <p class="empty-subtitle text-muted">
                                Configure custom hotkeys for quick actions.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
