<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();
$message = '';
$errors = [];

// Handle logo upload
if (isset($_POST['upload_logo']) && isset($_FILES['logo'])) {
    $file = $_FILES['logo'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $file['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $newFilename = 'logo.' . $ext;
            $destination = UPLOAD_DIR . $newFilename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                setSetting('logo_path', 'uploads/' . $newFilename);
                $message = 'Logo uploaded successfully';
            } else {
                $errors[] = 'Failed to upload logo';
            }
        } else {
            $errors[] = 'Invalid file type. Allowed: JPG, PNG, GIF';
        }
    } else {
        $errors[] = 'Upload error: ' . $file['error'];
    }
}

// Handle category add
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['category_description'] ?? '');
    
    if (!empty($name)) {
        $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        if ($stmt->execute()) {
            $message = 'Category added successfully';
        } else {
            $errors[] = 'Failed to add category';
        }
    } else {
        $errors[] = 'Category name is required';
    }
}

// Handle category delete
if (isset($_POST['delete_category'])) {
    $categoryId = (int)$_POST['category_id'];
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $categoryId);
    if ($stmt->execute()) {
        $message = 'Category deleted successfully';
    } else {
        $errors[] = 'Failed to delete category';
    }
}

// Handle theatre space add
if (isset($_POST['add_space'])) {
    $name = trim($_POST['space_name'] ?? '');
    $description = trim($_POST['space_description'] ?? '');
    
    if (!empty($name)) {
        $stmt = $db->prepare("INSERT INTO theatre_spaces (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        if ($stmt->execute()) {
            $message = 'Theatre space added successfully';
        } else {
            $errors[] = 'Failed to add theatre space';
        }
    } else {
        $errors[] = 'Theatre space name is required';
    }
}

// Handle theatre space delete
if (isset($_POST['delete_space'])) {
    $spaceId = (int)$_POST['space_id'];
    $stmt = $db->prepare("DELETE FROM theatre_spaces WHERE id = ?");
    $stmt->bind_param("i", $spaceId);
    if ($stmt->execute()) {
        $message = 'Theatre space deleted successfully';
    } else {
        $errors[] = 'Failed to delete theatre space';
    }
}

// Get categories
$categories = $db->query("SELECT * FROM categories ORDER BY name");

// Get theatre spaces
$theatreSpaces = $db->query("SELECT * FROM theatre_spaces ORDER BY name");

// Get current logo
$logoPath = getSetting('logo_path', 'assets/logo.png');

$pageTitle = "Settings - " . APP_NAME;
$pageHeader = "Settings";

ob_start();
?>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible" role="alert">
    <div class="d-flex">
        <div><i class="ti ti-check"></i></div>
        <div><?php echo sanitize($message); ?></div>
    </div>
    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <div class="d-flex">
        <div><i class="ti ti-alert-circle"></i></div>
        <div>
            <?php foreach ($errors as $error): ?>
                <div><?php echo sanitize($error); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Logo</h3>
            </div>
            <div class="card-body">
                <?php if (file_exists($logoPath)): ?>
                <div class="mb-3">
                    <img src="<?php echo $logoPath; ?>" alt="Logo" style="max-width: 200px; max-height: 100px;">
                </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Upload New Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        <small class="form-hint">JPG, PNG, or GIF format</small>
                    </div>
                    <button type="submit" name="upload_logo" class="btn btn-primary">
                        <i class="ti ti-upload"></i> Upload Logo
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Categories</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <div class="row g-2">
                        <div class="col">
                            <input type="text" name="category_name" class="form-control" 
                                   placeholder="Category name" required>
                        </div>
                        <div class="col">
                            <input type="text" name="category_description" class="form-control" 
                                   placeholder="Description">
                        </div>
                        <div class="col-auto">
                            <button type="submit" name="add_category" class="btn btn-primary">
                                <i class="ti ti-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="list-group list-group-flush">
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col">
                                <strong><?php echo sanitize($cat['name']); ?></strong>
                                <?php if ($cat['description']): ?>
                                <div class="text-muted small"><?php echo sanitize($cat['description']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-auto">
                                <form method="POST" class="d-inline" 
                                      onsubmit="return confirm('Delete this category?');">
                                    <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                    <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Theatre Spaces</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <div class="row g-2">
                        <div class="col">
                            <input type="text" name="space_name" class="form-control" 
                                   placeholder="Theatre space name" required>
                        </div>
                        <div class="col">
                            <input type="text" name="space_description" class="form-control" 
                                   placeholder="Description">
                        </div>
                        <div class="col-auto">
                            <button type="submit" name="add_space" class="btn btn-primary">
                                <i class="ti ti-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="list-group list-group-flush">
                    <?php while ($space = $theatreSpaces->fetch_assoc()): ?>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col">
                                <strong><?php echo sanitize($space['name']); ?></strong>
                                <?php if ($space['description']): ?>
                                <div class="text-muted small"><?php echo sanitize($space['description']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-auto">
                                <form method="POST" class="d-inline" 
                                      onsubmit="return confirm('Delete this theatre space?');">
                                    <input type="hidden" name="space_id" value="<?php echo $space['id']; ?>">
                                    <button type="submit" name="delete_space" class="btn btn-sm btn-danger">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
