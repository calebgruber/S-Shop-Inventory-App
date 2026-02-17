<?php
$pageTitle = 'User Management';
require_once 'includes/header.php';

// Only admins can access this page
requireRole('admin');

$db = getDB();

// Handle user operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? 'student';
        
        if (empty($email) || empty($password) || empty($full_name)) {
            setAlert('All fields are required', 'danger');
        } else {
            // Check if email already exists
            $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existing) {
                setAlert('Email already exists', 'danger');
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $db->query(
                    "INSERT INTO users (email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, 1)",
                    [$email, $passwordHash, $full_name, $role]
                );
                setAlert('User created successfully', 'success');
                redirect();
            }
        }
    } elseif ($action === 'update') {
        $userId = $_POST['user_id'] ?? 0;
        $full_name = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? 'student';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $db->query(
            "UPDATE users SET full_name = ?, role = ?, is_active = ? WHERE id = ?",
            [$full_name, $role, $is_active, $userId]
        );
        setAlert('User updated successfully', 'success');
        redirect();
    } elseif ($action === 'reset_password') {
        $userId = $_POST['user_id'] ?? 0;
        $newPassword = $_POST['new_password'] ?? '';
        
        if (!empty($newPassword)) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password_hash = ? WHERE id = ?", [$passwordHash, $userId]);
            setAlert('Password reset successfully', 'success');
        }
        redirect();
    } elseif ($action === 'delete') {
        $userId = $_POST['user_id'] ?? 0;
        // Hard delete - permanently remove from database
        $db->query("DELETE FROM users WHERE id = ?", [$userId]);
        setAlert('User permanently deleted from database', 'success');
        redirect();
    } elseif ($action === 'update_permissions') {
        $userId = $_POST['user_id'] ?? 0;
        $permissions = $_POST['permissions'] ?? [];
        
        // Remove all existing permissions for this user
        $db->query("DELETE FROM user_permissions WHERE user_id = ?", [$userId]);
        
        // Add new permissions
        foreach ($permissions as $permission) {
            $db->query(
                "INSERT INTO user_permissions (user_id, permission_key, can_access) VALUES (?, ?, 1)",
                [$userId, $permission]
            );
        }
        setAlert('Permissions updated successfully', 'success');
        redirect();
    } elseif ($action === 'update_show_assignments') {
        $userId = $_POST['user_id'] ?? 0;
        $showIds = $_POST['show_ids'] ?? [];
        $currentUserId = getCurrentUser()['id'];
        
        // Remove all existing show assignments for this user
        $db->query("DELETE FROM user_show_assignments WHERE user_id = ?", [$userId]);
        
        // Add new show assignments
        foreach ($showIds as $showId) {
            $db->query(
                "INSERT INTO user_show_assignments (user_id, show_id, assigned_by) VALUES (?, ?, ?)",
                [$userId, (int)$showId, $currentUserId]
            );
        }
        
        logMessage("Show assignments updated for user ID $userId by user ID $currentUserId", 'INFO');
        setAlert('Show assignments updated successfully', 'success');
        redirect();
    } elseif ($action === 'bulk_create') {
        $names = trim($_POST['names'] ?? '');
        $role = $_POST['role'] ?? 'student';
        $defaultPassword = $_POST['default_password'] ?? 'Purchase123!';
        
        if (empty($names)) {
            setAlert('Please enter at least one name', 'danger');
        } else {
            $nameLines = explode("\n", $names);
            $created = 0;
            $errors = [];
            $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
            
            foreach ($nameLines as $line) {
                $name = trim($line);
                if (empty($name)) continue;
                
                // Generate email from name (lowercase, replace spaces with dots)
                $emailName = strtolower(str_replace(' ', '.', $name));
                $email = $emailName . '@purchase.edu';
                
                // Check if email already exists
                $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
                if ($existing) {
                    $errors[] = "$name (email already exists)";
                } else {
                    try {
                        $db->query(
                            "INSERT INTO users (email, password_hash, full_name, role, is_active, must_reset_password, temp_password) VALUES (?, ?, ?, ?, 1, 1, ?)",
                            [$email, $passwordHash, $name, $role, $defaultPassword]
                        );
                        $created++;
                        
                        // Send welcome email with temporary password
                        try {
                            sendWelcomeEmail($email, $name, $defaultPassword);
                            logMessage("Welcome email sent to: $email", 'INFO');
                        } catch (Exception $e) {
                            logMessage("Failed to send welcome email to $email: " . $e->getMessage(), 'WARNING');
                            // Don't add to errors array - user was created successfully
                        }
                    } catch (Exception $e) {
                        $errors[] = "$name (error: " . $e->getMessage() . ")";
                    }
                }
            }
            
            if ($created > 0) {
                $message = "Successfully created $created user(s). Welcome emails have been sent.";
                if (!empty($errors)) {
                    $message .= " Errors: " . implode(', ', $errors);
                }
                setAlert($message, $created > 0 ? 'success' : 'warning');
            } else {
                setAlert('No users were created. ' . implode(', ', $errors), 'danger');
            }
            redirect();
        }
    } elseif ($action === 'bulk_delete') {
        $userIds = $_POST['user_ids'] ?? [];
        
        if (empty($userIds)) {
            setAlert('Please select at least one user', 'danger');
        } else {
            $deleted = 0;
            foreach ($userIds as $userId) {
                // Hard delete - permanently remove from database
                $db->query("DELETE FROM users WHERE id = ?", [$userId]);
                $deleted++;
            }
            setAlert("Successfully permanently deleted $deleted user(s) from database", 'success');
            redirect();
        }
    } elseif ($action === 'bulk_deactivate') {
        $userIds = $_POST['user_ids'] ?? [];
        
        if (empty($userIds)) {
            setAlert('Please select at least one user', 'danger');
        } else {
            $deactivated = 0;
            foreach ($userIds as $userId) {
                $db->query("UPDATE users SET is_active = 0 WHERE id = ?", [$userId]);
                $deactivated++;
            }
            setAlert("Successfully deactivated $deactivated user(s)", 'success');
            redirect();
        }
    }
}

// Get all users
$users = $db->fetchAll(
    "SELECT * FROM users WHERE is_deleted = 0 ORDER BY created_at DESC"
);

$allPermissions = [
    'dashboard' => 'Dashboard',
    'inventory' => 'Inventory',
    'shows' => 'Shows',
    'pullsheets' => 'Pullsheets',
    'change_orders' => 'Change Orders',
    'pick_mode' => 'Pick Mode',
    'return_mode' => 'Return Mode',
    'reports' => 'Reports',
    'paperwork' => 'Paperwork',
    'repairs' => 'Repairs',
    'student_requests' => 'Student Requests',
    'settings' => 'Settings',
    'user_management' => 'User Management'
];
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="btn-group">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="ti ti-plus icon"></i> Create New User
            </button>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkCreateModal">
                <i class="ti ti-users-plus icon"></i> Bulk Create Users
            </button>
        </div>
        <div class="btn-group ms-2" id="bulkActionButtons" style="display: none;">
            <button class="btn btn-warning" onclick="bulkDeactivate()">
                <i class="ti ti-user-off icon"></i> Deactivate Selected
            </button>
            <button class="btn btn-danger" onclick="bulkDelete()">
                <i class="ti ti-trash icon"></i> Delete Selected
            </button>
        </div>
    </div>
</div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Users</h3>
        <div class="ms-auto">
            <input type="text" class="form-control" id="userSearchInput" placeholder="Search users...">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table" id="usersTable">
            <thead>
                <tr>
                    <th class="w-1">
                        <input type="checkbox" class="form-check-input" id="selectAllUsers" onchange="toggleAllUsers(this)">
                    </th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input user-checkbox" value="<?php echo $user['id']; ?>" onchange="updateBulkActions()">
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-sm me-2" style="background-image: url('<?php echo getUserAvatarUrl($user); ?>')"></span>
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><span class="badge bg-<?php 
                        echo $user['role'] === 'admin' ? 'red' : 
                            ($user['role'] === 'designer' ? 'blue' : 
                            ($user['role'] === 'production_audio' ? 'purple' : 'green')); 
                    ?>"><?php 
                        echo $user['role'] === 'production_audio' ? 'Production Audio' : ucfirst($user['role']); 
                    ?></span></td>
                    <td>
                        <?php if ($user['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $user['last_login'] ? date('m/d/Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>">
                                <i class="ti ti-edit icon"></i>
                            </button>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#permissionsModal<?php echo $user['id']; ?>">
                                <i class="ti ti-lock icon"></i>
                            </button>
                            <?php if ($user['role'] === 'designer' || $user['role'] === 'production_audio'): ?>
                            <button class="btn btn-sm btn-cyan" data-bs-toggle="modal" data-bs-target="#showAssignmentsModal<?php echo $user['id']; ?>" title="Show Assignments">
                                <i class="ti ti-calendar icon"></i>
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordModal<?php echo $user['id']; ?>">
                                <i class="ti ti-key icon"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo $user['id']; ?>">
                                <i class="ti ti-trash icon"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="student">Student</option>
                            <option value="designer">Designer</option>
                            <option value="production_audio">Production Audio</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit, Reset Password, Delete, and Permissions Modals for each user -->
<?php foreach ($users as $user): ?>
<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <small class="text-muted">Email cannot be changed</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                            <option value="designer" <?php echo $user['role'] === 'designer' ? 'selected' : ''; ?>>Designer</option>
                            <option value="production_audio" <?php echo $user['role'] === 'production_audio' ? 'selected' : ''; ?>>Production Audio</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_active" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                            <span class="form-check-label">Active</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal<?php echo $user['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <div class="modal-body">
                    <p>Reset password for <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></p>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal<?php echo $user['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Permissions Modal -->
<div class="modal fade" id="permissionsModal<?php echo $user['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Permissions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_permissions">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <div class="modal-body">
                    <p>Permissions for <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></p>
                    <?php if ($user['role'] === 'admin'): ?>
                    <div class="alert alert-info">Admins have all permissions by default</div>
                    <?php else: ?>
                    <?php 
                    $userPermissions = $db->fetchAll("SELECT permission_key FROM user_permissions WHERE user_id = ?", [$user['id']]);
                    $userPermsArray = array_column($userPermissions, 'permission_key');
                    foreach ($allPermissions as $key => $label): 
                    ?>
                    <div class="mb-2">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="permissions[]" value="<?php echo $key; ?>" 
                                   <?php echo in_array($key, $userPermsArray) ? 'checked' : ''; ?>>
                            <span class="form-check-label"><?php echo $label; ?></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <?php if ($user['role'] !== 'admin'): ?>
                    <button type="submit" class="btn btn-primary">Update Permissions</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Show Assignments Modal -->
<div class="modal fade" id="showAssignmentsModal<?php echo $user['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Show Assignments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_show_assignments">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <div class="modal-body">
                    <p>Show assignments for <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></p>
                    <?php if ($user['role'] === 'designer' || $user['role'] === 'production_audio' || $user['role'] === 'student'): ?>
                    <?php 
                    $allShows = getAllShows();
                    $assignedShows = $db->fetchAll("SELECT show_id FROM user_show_assignments WHERE user_id = ?", [$user['id']]);
                    $assignedShowIds = array_column($assignedShows, 'show_id');
                    ?>
                    <div class="mb-3">
                        <label class="form-label">Assigned Shows</label>
                        <?php if (empty($allShows)): ?>
                            <p class="text-muted">No shows available</p>
                        <?php else: ?>
                            <?php foreach ($allShows as $show): ?>
                            <div class="mb-2">
                                <label class="form-check">
                                    <input type="checkbox" class="form-check-input" name="show_ids[]" value="<?php echo $show['id']; ?>" 
                                           <?php echo in_array($show['id'], $assignedShowIds) ? 'checked' : ''; ?>>
                                    <span class="form-check-label"><?php echo htmlspecialchars($show['name']); ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">Show assignments are only available for designers, production audio, and students</div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <?php if ($user['role'] === 'designer' || $user['role'] === 'production_audio' || $user['role'] === 'student'): ?>
                    <button type="submit" class="btn btn-primary">Update Assignments</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Bulk Create Users Modal -->
<div class="modal fade" id="bulkCreateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Create Users</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="bulk_create">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Instructions:</strong> Enter one name per line. Email addresses will be automatically generated as name@purchase.edu (spaces converted to dots, lowercase).
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Names (one per line)</label>
                        <textarea class="form-control" name="names" rows="10" placeholder="John Smith&#10;Jane Doe&#10;Robert Johnson" required></textarea>
                        <small class="form-hint">Example: "John Smith" will become john.smith@purchase.edu</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Default Password</label>
                        <input type="text" class="form-control" name="default_password" value="Purchase123!" required>
                        <small class="form-hint">All users will be created with this password</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="student" selected>Student</option>
                            <option value="designer">Designer</option>
                            <option value="production_audio">Production Audio</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Users</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Delete Confirmation Form -->
<form method="POST" id="bulkDeleteForm" style="display: none;">
    <input type="hidden" name="action" value="bulk_delete">
    <div id="bulkDeleteUserIds"></div>
</form>

<!-- Bulk Deactivate Confirmation Form -->
<form method="POST" id="bulkDeactivateForm" style="display: none;">
    <input type="hidden" name="action" value="bulk_deactivate">
    <div id="bulkDeactivateUserIds"></div>
</form>

<script>
// User search functionality
document.getElementById('userSearchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#usersTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Bulk actions functionality
function toggleAllUsers(checkbox) {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions();
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    const bulkButtons = document.getElementById('bulkActionButtons');
    
    if (checkboxes.length > 0) {
        bulkButtons.style.display = 'inline-block';
    } else {
        bulkButtons.style.display = 'none';
    }
}

function getSelectedUserIds() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function bulkDelete() {
    const userIds = getSelectedUserIds();
    
    if (userIds.length === 0) {
        alert('Please select at least one user');
        return;
    }
    
    if (!confirm(`Are you sure you want to delete ${userIds.length} user(s)? This action cannot be undone.`)) {
        return;
    }
    
    // Add user IDs as hidden inputs
    const container = document.getElementById('bulkDeleteUserIds');
    container.innerHTML = '';
    userIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'user_ids[]';
        input.value = id;
        container.appendChild(input);
    });
    
    // Submit form
    document.getElementById('bulkDeleteForm').submit();
}

function bulkDeactivate() {
    const userIds = getSelectedUserIds();
    
    if (userIds.length === 0) {
        alert('Please select at least one user');
        return;
    }
    
    if (!confirm(`Are you sure you want to deactivate ${userIds.length} user(s)?`)) {
        return;
    }
    
    // Add user IDs as hidden inputs
    const container = document.getElementById('bulkDeactivateUserIds');
    container.innerHTML = '';
    userIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'user_ids[]';
        input.value = id;
        container.appendChild(input);
    });
    
    // Submit form
    document.getElementById('bulkDeactivateForm').submit();
}
</script>

<?php require_once 'includes/footer.php'; ?>
