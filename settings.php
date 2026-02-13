<?php
require_once 'includes/functions.php';

// Only admins can access settings
requireRole('admin');

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
                        // Validate file size (max 5MB)
                        $maxSize = 5 * 1024 * 1024; // 5MB
                        if ($_FILES['logo']['size'] > $maxSize) {
                            setAlert('Logo file is too large. Maximum size is 5MB.', 'danger');
                            redirect();
                        }
                        
                        // Validate file is an actual image
                        $imageInfo = getimagesize($_FILES['logo']['tmp_name']);
                        if ($imageInfo === false) {
                            setAlert('Invalid image file. Please upload a valid image.', 'danger');
                            redirect();
                        }
                        
                        // Validate extension matches MIME type
                        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
                        if (!in_array($ext, $allowedExtensions)) {
                            setAlert('Invalid file type. Allowed types: ' . implode(', ', $allowedExtensions), 'danger');
                            redirect();
                        }
                        
                        // Validate MIME type
                        $mimeType = $imageInfo['mime'] ?? '';
                        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
                        if (!in_array($mimeType, $allowedMimeTypes)) {
                            setAlert('Invalid image type. File MIME type does not match extension.', 'danger');
                            redirect();
                        }
                        
                        // Generate unique filename and move file
                        $filename = 'logo_' . time() . '.' . $ext;
                        if (move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_DIR . $filename)) {
                            setSetting('logo_path', $filename);
                        } else {
                            setAlert('Failed to upload logo file.', 'danger');
                            redirect();
                        }
                    }
                    
                    // Handle login illustration upload
                    if (isset($_FILES['login_illustration']) && $_FILES['login_illustration']['error'] === UPLOAD_ERR_OK) {
                        // Validate file size (max 5MB)
                        $maxSize = 5 * 1024 * 1024; // 5MB
                        if ($_FILES['login_illustration']['size'] > $maxSize) {
                            setAlert('Login illustration file is too large. Maximum size is 5MB.', 'danger');
                            redirect();
                        }
                        
                        // Validate file is an actual image
                        $imageInfo = getimagesize($_FILES['login_illustration']['tmp_name']);
                        if ($imageInfo === false) {
                            setAlert('Invalid image file. Please upload a valid image.', 'danger');
                            redirect();
                        }
                        
                        // Validate extension matches MIME type
                        $ext = strtolower(pathinfo($_FILES['login_illustration']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
                        if (!in_array($ext, $allowedExtensions)) {
                            setAlert('Invalid file type. Allowed types: ' . implode(', ', $allowedExtensions), 'danger');
                            redirect();
                        }
                        
                        // Validate MIME type
                        $mimeType = $imageInfo['mime'] ?? '';
                        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
                        if (!in_array($mimeType, $allowedMimeTypes)) {
                            setAlert('Invalid image type. File MIME type does not match extension.', 'danger');
                            redirect();
                        }
                        
                        // Generate unique filename and move file
                        $filename = 'login_illustration_' . time() . '.' . $ext;
                        if (move_uploaded_file($_FILES['login_illustration']['tmp_name'], UPLOAD_DIR . $filename)) {
                            setSetting('login_illustration_path', $filename);
                        } else {
                            setAlert('Failed to upload login illustration file.', 'danger');
                            redirect();
                        }
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
                    
                case 'import_json':
                    if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
                        setAlert('Please select a JSON file to upload', 'danger');
                        redirect();
                    }
                    
                    // Validate file is JSON
                    $ext = strtolower(pathinfo($_FILES['json_file']['name'], PATHINFO_EXTENSION));
                    if ($ext !== 'json') {
                        setAlert('Please upload a JSON file', 'danger');
                        redirect();
                    }
                    
                    // Read JSON file
                    $jsonFile = $_FILES['json_file']['tmp_name'];
                    $jsonContent = file_get_contents($jsonFile);
                    
                    if ($jsonContent === false) {
                        setAlert('Failed to read JSON file', 'danger');
                        redirect();
                    }
                    
                    // Parse JSON
                    $jsonData = json_decode($jsonContent, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        setAlert('Invalid JSON file: ' . json_last_error_msg(), 'danger');
                        redirect();
                    }
                    
                    $db = getDB();
                    $imported = 0;
                    $skipped = 0;
                    $errors = [];
                    
                    try {
                        // Process each table in the JSON data
                        foreach ($jsonData as $item) {
                            if (!isset($item['type']) || $item['type'] !== 'table') {
                                continue; // Skip non-table items (header, database info)
                            }
                            
                            $tableName = $item['name'] ?? '';
                            $tableData = $item['data'] ?? [];
                            
                            switch ($tableName) {
                                case 'categories':
                                    foreach ($tableData as $row) {
                                        $id = isset($row['id']) && trim($row['id']) !== '' ? (int)trim($row['id']) : null;
                                        $name = isset($row['name']) && trim($row['name']) !== '' ? trim($row['name']) : null;
                                        $description = isset($row['description']) ? trim($row['description']) : '';
                                        
                                        if (!$name) continue; // Name is required
                                        
                                        try {
                                            // Check if category exists
                                            $existing = $db->fetchOne("SELECT id FROM categories WHERE name = ?", [$name]);
                                            if ($existing) {
                                                $skipped++;
                                                continue;
                                            }
                                            
                                            // Try to insert with specified ID if available
                                            if ($id) {
                                                $idExists = $db->fetchOne("SELECT id FROM categories WHERE id = ?", [$id]);
                                                if ($idExists) {
                                                    // ID taken, insert without specifying ID
                                                    $db->query(
                                                        "INSERT INTO categories (name, description) VALUES (?, ?)",
                                                        [$name, $description]
                                                    );
                                                } else {
                                                    // Insert with specific ID
                                                    $db->query(
                                                        "INSERT INTO categories (id, name, description) VALUES (?, ?, ?)",
                                                        [$id, $name, $description]
                                                    );
                                                }
                                            } else {
                                                // No ID specified, auto-generate
                                                $db->query(
                                                    "INSERT INTO categories (name, description) VALUES (?, ?)",
                                                    [$name, $description]
                                                );
                                            }
                                            $imported++;
                                        } catch (Exception $e) {
                                            $errors[] = "Category '$name': " . $e->getMessage();
                                        }
                                    }
                                    break;
                                    
                                case 'subcategories':
                                    foreach ($tableData as $row) {
                                        $id = isset($row['id']) && trim($row['id']) !== '' ? (int)trim($row['id']) : null;
                                        $name = isset($row['name']) && trim($row['name']) !== '' ? trim($row['name']) : null;
                                        $categoryId = isset($row['category_id']) && trim($row['category_id']) !== '' ? (int)trim($row['category_id']) : null;
                                        $description = isset($row['description']) ? trim($row['description']) : '';
                                        
                                        if (!$name || !$categoryId) continue; // Name and category_id are required
                                        
                                        try {
                                            // Verify category exists
                                            $category = $db->fetchOne("SELECT id FROM categories WHERE id = ?", [$categoryId]);
                                            if (!$category) {
                                                $errors[] = "Subcategory '$name': Category ID '$categoryId' not found";
                                                continue;
                                            }
                                            
                                            // Check if subcategory exists
                                            $existing = $db->fetchOne(
                                                "SELECT id FROM subcategories WHERE name = ? AND category_id = ?",
                                                [$name, $categoryId]
                                            );
                                            if ($existing) {
                                                $skipped++;
                                                continue;
                                            }
                                            
                                            // Try to insert with specified ID if available
                                            if ($id) {
                                                $idExists = $db->fetchOne("SELECT id FROM subcategories WHERE id = ?", [$id]);
                                                if ($idExists) {
                                                    // ID taken, insert without specifying ID
                                                    $db->query(
                                                        "INSERT INTO subcategories (category_id, name, description) VALUES (?, ?, ?)",
                                                        [$categoryId, $name, $description]
                                                    );
                                                } else {
                                                    // Insert with specific ID
                                                    $db->query(
                                                        "INSERT INTO subcategories (id, category_id, name, description) VALUES (?, ?, ?, ?)",
                                                        [$id, $categoryId, $name, $description]
                                                    );
                                                }
                                            } else {
                                                // No ID specified, auto-generate
                                                $db->query(
                                                    "INSERT INTO subcategories (category_id, name, description) VALUES (?, ?, ?)",
                                                    [$categoryId, $name, $description]
                                                );
                                            }
                                            $imported++;
                                        } catch (Exception $e) {
                                            $errors[] = "Subcategory '$name': " . $e->getMessage();
                                        }
                                    }
                                    break;
                                    
                                case 'items':
                                    foreach ($tableData as $row) {
                                        $id = isset($row['id']) && trim($row['id']) !== '' ? (int)trim($row['id']) : null;
                                        $name = isset($row['name']) && trim($row['name']) !== '' ? trim($row['name']) : null;
                                        $description = isset($row['description']) ? trim($row['description']) : '';
                                        $barcode = isset($row['barcode']) && trim($row['barcode']) !== '' ? trim($row['barcode']) : null;
                                        
                                        if (!$name || !$barcode) continue; // Name and barcode are required
                                        
                                        // Get optional fields
                                        $categoryId = isset($row['category_id']) && trim($row['category_id']) !== '' && trim($row['category_id']) !== 'null' 
                                            ? (int)trim($row['category_id']) : null;
                                        $subcategoryId = isset($row['subcategory_id']) && trim($row['subcategory_id']) !== '' && trim($row['subcategory_id']) !== 'null'
                                            ? (int)trim($row['subcategory_id']) : null;
                                        $trackingType = isset($row['tracking_type']) ? trim($row['tracking_type']) : 'quantity';
                                        $totalQuantity = isset($row['total_quantity']) ? (int)$row['total_quantity'] : 0;
                                        $inStockQuantity = isset($row['in_stock_quantity']) ? (int)$row['in_stock_quantity'] : 0;
                                        $location = isset($row['location']) ? trim($row['location']) : '';
                                        
                                        try {
                                            // Check if item exists
                                            $existing = $db->fetchOne("SELECT id FROM items WHERE barcode = ?", [$barcode]);
                                            if ($existing) {
                                                $skipped++;
                                                continue;
                                            }
                                            
                                            // Try to insert with specified ID if available
                                            if ($id) {
                                                $idExists = $db->fetchOne("SELECT id FROM items WHERE id = ?", [$id]);
                                                if ($idExists) {
                                                    // ID taken, insert without specifying ID
                                                    $db->query(
                                                        "INSERT INTO items (name, description, barcode, category_id, subcategory_id, tracking_type, total_quantity, in_stock_quantity, location) 
                                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                                        [$name, $description, $barcode, $categoryId, $subcategoryId, $trackingType, $totalQuantity, $inStockQuantity, $location]
                                                    );
                                                } else {
                                                    // Insert with specific ID
                                                    $db->query(
                                                        "INSERT INTO items (id, name, description, barcode, category_id, subcategory_id, tracking_type, total_quantity, in_stock_quantity, location) 
                                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                                        [$id, $name, $description, $barcode, $categoryId, $subcategoryId, $trackingType, $totalQuantity, $inStockQuantity, $location]
                                                    );
                                                }
                                            } else {
                                                // No ID specified, auto-generate
                                                $db->query(
                                                    "INSERT INTO items (name, description, barcode, category_id, subcategory_id, tracking_type, total_quantity, in_stock_quantity, location) 
                                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                                    [$name, $description, $barcode, $categoryId, $subcategoryId, $trackingType, $totalQuantity, $inStockQuantity, $location]
                                                );
                                            }
                                            $imported++;
                                        } catch (Exception $e) {
                                            $errors[] = "Item '$name' ($barcode): " . $e->getMessage();
                                        }
                                    }
                                    break;
                            }
                        }
                        
                        // Build success message
                        $message = "Import completed: $imported records imported";
                        if ($skipped > 0) {
                            $message .= ", $skipped skipped (already exist)";
                        }
                        if (!empty($errors)) {
                            $message .= ". Errors: " . implode('; ', array_slice($errors, 0, 5));
                            if (count($errors) > 5) {
                                $message .= " (and " . (count($errors) - 5) . " more)";
                            }
                            setAlert($message, 'warning');
                        } else {
                            setAlert($message, 'success');
                        }
                        
                    } catch (Exception $e) {
                        setAlert('Import failed: ' . $e->getMessage(), 'danger');
                    }
                    break;
            }
        }
        redirect();
    } catch (Exception $e) {
        setAlert($e->getMessage(), 'danger');
    }
}

$categories = getAllCategories();
$subcategories = getAllSubcategories();
$theatreSpaces = getAllTheatreSpaces();
$appName = getSetting('app_name');
$logoPath = getSetting('logo_path');
$loginIllustrationPath = getSetting('login_illustration_path');
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
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Logo (for Header & PDFs)</label>
                            <?php if ($logoPath && file_exists(UPLOAD_DIR . $logoPath)): ?>
                                <div class="mb-2">
                                    <img src="uploads/<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" style="max-height: 80px; border: 1px solid #ddd; padding: 5px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="logo" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp">
                            <small class="form-hint">Upload a logo to appear in header and on PDFs (PNG, JPG, GIF, WebP - max 5MB)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Login Illustration (optional)</label>
                            <?php if ($loginIllustrationPath && file_exists(UPLOAD_DIR . $loginIllustrationPath)): ?>
                                <div class="mb-2">
                                    <img src="uploads/<?php echo htmlspecialchars($loginIllustrationPath); ?>" alt="Login Illustration" style="max-height: 80px; border: 1px solid #ddd; padding: 5px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="login_illustration" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp">
                            <small class="form-hint">Upload an illustration for the login page (PNG, JPG, GIF, WebP - max 5MB)</small>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy icon"></i>
                        Save Settings
                    </button>
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
    
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Import Data from JSON</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h4 class="alert-title">JSON Import Instructions</h4>
                    <div class="text-muted">
                        <p>Upload a JSON file to import data into the database. Supports PHPMyAdmin JSON export format.</p>
                        <p><strong>JSON Format:</strong></p>
                        <ul class="mb-0">
                            <li>PHPMyAdmin "Export to JSON plugin" format</li>
                            <li>Contains tables: categories, subcategories, items</li>
                            <li>Each table has a "data" array with rows</li>
                            <li>IDs are preserved when possible to maintain relationships</li>
                        </ul>
                        <p class="mt-2 mb-0"><strong>Note:</strong> Duplicate entries (based on name or barcode) will be skipped. The import processes tables in order: categories → subcategories → items to maintain relationships.</p>
                    </div>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import_json">
                    
                    <div class="mb-3">
                        <label class="form-label required">JSON File</label>
                        <input type="file" class="form-control" name="json_file" accept=".json" required>
                        <small class="form-hint">Select a JSON file to import (PHPMyAdmin export format)</small>
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="ti ti-upload icon"></i>
                        Import JSON
                    </button>
                </form>
                
                <hr class="my-4">
                
                <div class="accordion" id="jsonExamplesAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingExamples">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExamples">
                                <i class="ti ti-help icon me-2"></i>
                                View JSON Format Example
                            </button>
                        </h2>
                        <div id="collapseExamples" class="accordion-collapse collapse" data-bs-parent="#jsonExamplesAccordion">
                            <div class="accordion-body">
                                <h5>PHPMyAdmin JSON Export Format:</h5>
                                <pre class="bg-light p-2 rounded" style="max-height: 400px; overflow-y: auto;">[
  {
    "type": "header",
    "version": "5.2.2",
    "comment": "Export to JSON plugin for PHPMyAdmin"
  },
  {
    "type": "database",
    "name": "your_database"
  },
  {
    "type": "table",
    "name": "categories",
    "database": "your_database",
    "data": [
      {
        "id": "1",
        "name": "Cable",
        "description": "",
        "created_at": "2026-01-26 22:08:02",
        "updated_at": "2026-01-26 22:08:02"
      }
    ]
  },
  {
    "type": "table",
    "name": "subcategories",
    "database": "your_database",
    "data": [
      {
        "id": "1",
        "category_id": "1",
        "name": "XLR",
        "description": "",
        "created_at": "2026-01-26 23:25:33",
        "updated_at": "2026-01-26 23:25:33"
      }
    ]
  },
  {
    "type": "table",
    "name": "items",
    "database": "your_database",
    "data": [
      {
        "id": "6",
        "name": "Soundcraft SI3",
        "description": "",
        "barcode": "CSS-Soundcraft-SI3",
        "category_id": "2",
        "subcategory_id": "5",
        "tracking_type": "serial",
        "total_quantity": "1",
        "in_stock_quantity": "1",
        "location": "",
        "photo_path": null,
        "created_at": "2026-01-27 21:28:53",
        "updated_at": "2026-01-27 21:32:56"
      }
    ]
  }
]</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
