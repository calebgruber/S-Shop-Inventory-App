<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $designer = trim($_POST['designer'] ?? '');
    $shop_lead = trim($_POST['shop_lead'] ?? '');
    $theatre_space_id = !empty($_POST['theatre_space_id']) ? (int)$_POST['theatre_space_id'] : null;
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($name)) {
        $errors[] = "Show name is required.";
    }
    
    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO shows (name, designer, shop_lead, theatre_space_id, status) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $name, $designer, $shop_lead, $theatre_space_id, $status);
        
        if ($stmt->execute()) {
            $showId = $stmt->insert_id;
            redirectTo("show_view.php?id=$showId");
        } else {
            $errors[] = "Failed to create show. Please try again.";
        }
    }
}

// Get theatre spaces for dropdown
$theatre_spaces = $db->query("SELECT * FROM theatre_spaces ORDER BY name");

$pageTitle = "Create Show - " . APP_NAME;
$pageHeader = "Create Show";
$pageActions = '<a href="shows.php" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Back to Shows</a>';

ob_start();
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <div class="d-flex">
        <div>
            <i class="ti ti-alert-circle"></i>
        </div>
        <div>
            <h4 class="alert-title">Error</h4>
            <div class="text-muted">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo sanitize($error); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label required">Show Name</label>
                        <input type="text" name="name" class="form-control auto-focus" 
                               value="<?php echo sanitize($_POST['name'] ?? ''); ?>" 
                               placeholder="Enter show name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Designer</label>
                        <input type="text" name="designer" class="form-control" 
                               value="<?php echo sanitize($_POST['designer'] ?? ''); ?>" 
                               placeholder="Enter designer name">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Shop Lead</label>
                        <input type="text" name="shop_lead" class="form-control" 
                               value="<?php echo sanitize($_POST['shop_lead'] ?? ''); ?>" 
                               placeholder="Enter shop lead name">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Theatre Space</label>
                        <select name="theatre_space_id" class="form-select">
                            <option value="">Select theatre space</option>
                            <?php while ($space = $theatre_spaces->fetch_assoc()): ?>
                            <option value="<?php echo $space['id']; ?>"
                                <?php echo (isset($_POST['theatre_space_id']) && $_POST['theatre_space_id'] == $space['id']) ? 'selected' : ''; ?>>
                                <?php echo sanitize($space['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="completed" <?php echo (isset($_POST['status']) && $_POST['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo (isset($_POST['status']) && $_POST['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check"></i> Create Show
                        </button>
                        <a href="shows.php" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
