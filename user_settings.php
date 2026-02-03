<?php
requireLogin();
$pageTitle = 'User Settings';
require_once 'includes/header.php';

$db = getDB();
$currentUser = getCurrentUser();

// Handle hotkey updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_hotkeys') {
        $hotkeys = $_POST['hotkeys'] ?? [];
        
        // Get existing hotkeys to determine what changed
        $existingHotkeys = $db->fetchAll(
            "SELECT action, hotkey FROM user_hotkeys WHERE user_id = ?",
            [$currentUser['id']]
        );
        $existingMap = [];
        foreach ($existingHotkeys as $row) {
            $existingMap[$row['action']] = $row['hotkey'];
        }
        
        // Update or insert hotkeys
        foreach ($hotkeys as $actionKey => $hotkey) {
            if (!empty($hotkey)) {
                try {
                    if (isset($existingMap[$actionKey])) {
                        // Update existing
                        $db->query(
                            "UPDATE user_hotkeys SET hotkey = ? WHERE user_id = ? AND action = ?",
                            [$hotkey, $currentUser['id'], $actionKey]
                        );
                    } else {
                        // Insert new
                        $db->query(
                            "INSERT INTO user_hotkeys (user_id, action, hotkey) VALUES (?, ?, ?)",
                            [$currentUser['id'], $actionKey, $hotkey]
                        );
                    }
                } catch (PDOException $e) {
                    // Check if it's a duplicate key error
                    if ($e->getCode() == 23000) {
                        setAlert('Hotkey conflict: ' . $hotkey . ' is already assigned to another action', 'warning');
                    } else {
                        setAlert('Database error: ' . $e->getMessage(), 'danger');
                    }
                }
            } else {
                // Delete if hotkey is empty
                $db->query(
                    "DELETE FROM user_hotkeys WHERE user_id = ? AND action = ?",
                    [$currentUser['id'], $actionKey]
                );
            }
        }
        
        setAlert('Hotkey settings updated successfully', 'success');
        redirect();
    }
}

// Get current hotkeys
$currentHotkeys = [];
$hotkeysData = $db->fetchAll(
    "SELECT action, hotkey FROM user_hotkeys WHERE user_id = ?",
    [$currentUser['id']]
);
foreach ($hotkeysData as $row) {
    $currentHotkeys[$row['action']] = $row['hotkey'];
}

// Available actions with default hotkeys
$availableActions = [
    'quick_lookup' => ['label' => 'Quick Lookup', 'default' => 'Ctrl+K'],
    'pick_mode' => ['label' => 'Pick Mode', 'default' => 'Ctrl+Shift+P'],
    'return_mode' => ['label' => 'Return Mode', 'default' => 'Ctrl+Shift+R'],
    'create_pullsheet' => ['label' => 'Create Pullsheet', 'default' => 'Ctrl+Shift+N'],
    'inventory' => ['label' => 'Go to Inventory', 'default' => 'Ctrl+I'],
    'reports' => ['label' => 'Go to Reports', 'default' => 'Ctrl+Shift+E'],
    'shows' => ['label' => 'Go to Shows', 'default' => 'Ctrl+Shift+S']
];
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Keyboard Shortcuts</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="hotkeysForm">
                    <input type="hidden" name="action" value="update_hotkeys">
                    
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>
                        Configure custom keyboard shortcuts for quick access to common actions. 
                        Use format like: Ctrl+K, Alt+P, Shift+A
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Default Hotkey</th>
                                    <th>Your Hotkey</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($availableActions as $actionKey => $actionData): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($actionData['label']); ?></strong>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($actionData['default']); ?></code>
                                    </td>
                                    <td>
                                        <input 
                                            type="text" 
                                            class="form-control hotkey-input" 
                                            name="hotkeys[<?php echo $actionKey; ?>]" 
                                            value="<?php echo htmlspecialchars($currentHotkeys[$actionKey] ?? $actionData['default']); ?>"
                                            placeholder="<?php echo htmlspecialchars($actionData['default']); ?>"
                                            data-action="<?php echo $actionKey; ?>"
                                        >
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-2"></i>
                            Save Hotkeys
                        </button>
                        <button type="button" class="btn btn-secondary" id="resetDefaults">
                            <i class="ti ti-refresh me-2"></i>
                            Reset to Defaults
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">How to Use Hotkeys</h3>
            </div>
            <div class="card-body">
                <h4>Modifier Keys</h4>
                <ul>
                    <li><code>Ctrl</code> - Control key</li>
                    <li><code>Alt</code> - Alt/Option key</li>
                    <li><code>Shift</code> - Shift key</li>
                </ul>
                
                <h4>Examples</h4>
                <ul>
                    <li><code>Ctrl+K</code> - Control + K</li>
                    <li><code>Ctrl+Shift+P</code> - Control + Shift + P</li>
                    <li><code>Alt+R</code> - Alt + R</li>
                </ul>
                
                <div class="alert alert-warning mt-3">
                    <i class="ti ti-alert-triangle me-2"></i>
                    Avoid using browser shortcuts like Ctrl+T, Ctrl+W, etc.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Record hotkey on keydown
    const hotkeyInputs = document.querySelectorAll('.hotkey-input');
    
    hotkeyInputs.forEach(input => {
        input.addEventListener('keydown', function(e) {
            e.preventDefault();
            
            const parts = [];
            if (e.ctrlKey || e.metaKey) parts.push('Ctrl');
            if (e.altKey) parts.push('Alt');
            if (e.shiftKey) parts.push('Shift');
            
            // Add the key itself (ignore modifier keys alone)
            if (!['Control', 'Alt', 'Shift', 'Meta', 'MetaLeft', 'MetaRight'].includes(e.key)) {
                parts.push(e.key.toUpperCase());
            }
            
            if (parts.length > 1) {
                this.value = parts.join('+');
            }
        });
        
        // Validate on blur
        input.addEventListener('blur', function() {
            const value = this.value.trim();
            if (value && !value.includes('+')) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-warning alert-dismissible mt-2';
                alertDiv.innerHTML = `
                    <div>Hotkeys should include a modifier key (Ctrl, Alt, or Shift)</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                this.parentElement.appendChild(alertDiv);
                this.value = '';
                setTimeout(() => alertDiv.remove(), 3000);
            }
        });
    });
    
    // Reset to defaults
    document.getElementById('resetDefaults').addEventListener('click', function() {
        if (confirm('Reset all hotkeys to default values?')) {
            hotkeyInputs.forEach(input => {
                input.value = input.placeholder;
            });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
