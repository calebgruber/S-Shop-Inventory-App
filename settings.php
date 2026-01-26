<?php
$pageTitle = 'Settings';
require_once 'includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_app_settings':
                    setSetting('app_name', $_POST['app_name']);
                    
                    // Handle logo upload
                    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                        $filename = 'logo_' . time() . '.' . $ext;
                        move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_DIR . $filename);
                        setSetting('logo_path', $filename);
                    }
                    
                    setAlert('Settings updated successfully');
                    break;
                    
                case 'add_category':
                    getDB()->query(
                        "INSERT INTO categories (name, description) VALUES (?, ?)",
                        [$_POST['name'], $_POST['description']]
                    );
                    setAlert('Category added successfully');
                    break;
                    
                case 'delete_category':
                    getDB()->query("DELETE FROM categories WHERE id = ?", [$_POST['id']]);
                    setAlert('Category deleted successfully');
                    break;
                    
                case 'add_subcategory':
                    getDB()->query(
                        "INSERT INTO subcategories (category_id, name, description) VALUES (?, ?, ?)",
                        [$_POST['category_id'], $_POST['name'], $_POST['description']]
                    );
                    setAlert('Subcategory added successfully');
                    break;
                    
                case 'delete_subcategory':
                    getDB()->query("DELETE FROM subcategories WHERE id = ?", [$_POST['id']]);
                    setAlert('Subcategory deleted successfully');
                    break;
                    
                case 'add_theatre_space':
                    getDB()->query(
                        "INSERT INTO theatre_spaces (name, description) VALUES (?, ?)",
                        [$_POST['name'], $_POST['description']]
                    );
                    setAlert('Theatre space added successfully');
                    break;
                    
                case 'delete_theatre_space':
                    getDB()->query("DELETE FROM theatre_spaces WHERE id = ?", [$_POST['id']]);
                    setAlert('Theatre space deleted successfully');
                    break;
            }
        }
        redirect('settings.php');
    } catch (Exception $e) {
        setAlert($e->getMessage(), 'danger');
    }
}

$categories = getAllCategories();
$subcategories = getAllSubcategories();
$theatreSpaces = getAllTheatreSpaces();
$appName = getSetting('app_name');
$logoPath = getSetting('logo_path');
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Application Settings</h3>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_app_settings">
                    
                    <div class="mb-3">
                        <label class="form-label">Application Name</label>
                        <input type="text" class="form-control" name="app_name" value="<?php echo htmlspecialchars($appName); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Logo (for PDFs)</label>
                        <?php if ($logoPath && file_exists(UPLOAD_DIR . $logoPath)): ?>
                            <div class="mb-2">
                                <img src="uploads/<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" style="max-height: 100px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="logo" accept="image/*">
                        <small class="form-hint">Upload a logo to appear on PDFs</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Categories</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="add_category">
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" name="name" placeholder="Category name" required>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                    <textarea class="form-control" name="description" placeholder="Description (optional)" rows="2"></textarea>
                </form>
                
                <div class="list-group">
                    <?php foreach ($categories as $category): ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col">
                                    <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                    <?php if ($category['description']): ?>
                                        <div class="text-muted small"><?php echo htmlspecialchars($category['description']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-auto">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_category">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Subcategories</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="add_subcategory">
                    <div class="mb-2">
                        <select class="form-select" name="category_id" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" name="name" placeholder="Subcategory name" required>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                    <textarea class="form-control" name="description" placeholder="Description (optional)" rows="2"></textarea>
                </form>
                
                <div class="list-group">
                    <?php foreach ($subcategories as $subcategory): ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col">
                                    <strong><?php echo htmlspecialchars($subcategory['name']); ?></strong>
                                    <div class="text-muted small">
                                        Category: <?php echo htmlspecialchars($subcategory['category_name'] ?? 'N/A'); ?>
                                    </div>
                                    <?php if ($subcategory['description']): ?>
                                        <div class="text-muted small"><?php echo htmlspecialchars($subcategory['description']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-auto">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_subcategory">
                                        <input type="hidden" name="id" value="<?php echo $subcategory['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this subcategory?')">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Theatre Spaces</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="add_theatre_space">
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" name="name" placeholder="Space name" required>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                    <textarea class="form-control" name="description" placeholder="Description (optional)" rows="2"></textarea>
                </form>
                
                <div class="list-group">
                    <?php foreach ($theatreSpaces as $space): ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col">
                                    <strong><?php echo htmlspecialchars($space['name']); ?></strong>
                                    <?php if ($space['description']): ?>
                                        <div class="text-muted small"><?php echo htmlspecialchars($space['description']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-auto">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_theatre_space">
                                        <input type="hidden" name="id" value="<?php echo $space['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this theatre space?')">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
