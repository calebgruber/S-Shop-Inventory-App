<?php
require_once '../includes/functions.php';

// Only admins can access settings
requireRole('admin');

$pageTitle = 'Settings';
require_once '../includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_app_settings':
                    setSetting('app_name', $_POST['app_name']);
                    setSetting('support_email', $_POST['support_email'] ?? 'support@example.com');
                    setSetting('app_url', $_POST['app_url'] ?? '');
                    
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
                    
                case 'remove_logo':
                    $logoPath = getSetting('logo_path');
                    if ($logoPath) {
                        // Delete the logo file if it exists
                        $logoFile = UPLOAD_DIR . basename($logoPath);
                        if (file_exists($logoFile)) {
                            unlink($logoFile);
                        }
                        // Clear the logo_path setting
                        setSetting('logo_path', '');
                        setAlert('Logo removed successfully. App name will now be displayed as text.');
                    } else {
                        setAlert('No logo to remove.', 'info');
                    }
                    redirect();
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
                    
                case 'clear_all_data':
                    // Confirm action
                    $db = getDB();
                    try {
                        // Disable foreign key checks
                        $db->query("SET FOREIGN_KEY_CHECKS = 0");
                        
                        // Delete in proper order (items first, then subcategories, then categories)
                        $db->query("DELETE FROM items");
                        $db->query("DELETE FROM subcategories");
                        $db->query("DELETE FROM categories");
                        
                        // Reset auto increment
                        $db->query("ALTER TABLE items AUTO_INCREMENT = 1");
                        $db->query("ALTER TABLE subcategories AUTO_INCREMENT = 1");
                        $db->query("ALTER TABLE categories AUTO_INCREMENT = 1");
                        
                        // Re-enable foreign key checks
                        $db->query("SET FOREIGN_KEY_CHECKS = 1");
                        
                        setAlert('All categories, subcategories, and items have been cleared successfully', 'success');
                    } catch (Exception $e) {
                        setAlert('Error clearing data: ' . $e->getMessage(), 'danger');
                    }
                    redirect();
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
                                            
                                            // Validate category_id exists if provided
                                            if ($categoryId) {
                                                $catExists = $db->fetchOne("SELECT id FROM categories WHERE id = ?", [$categoryId]);
                                                if (!$catExists) {
                                                    $errors[] = "Item '$name' ($barcode): category_id $categoryId does not exist. Setting to NULL.";
                                                    $categoryId = null;
                                                }
                                            }
                                            
                                            // Validate subcategory_id exists if provided
                                            if ($subcategoryId) {
                                                $subExists = $db->fetchOne("SELECT id FROM subcategories WHERE id = ?", [$subcategoryId]);
                                                if (!$subExists) {
                                                    $errors[] = "Item '$name' ($barcode): subcategory_id $subcategoryId does not exist. Setting to NULL.";
                                                    $subcategoryId = null;
                                                }
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
                    
                case 'upload_banner':
                    if (!isset($_FILES['banners']) || empty($_FILES['banners']['name'][0])) {
                        setAlert('Please select at least one image', 'danger');
                        redirect();
                    }
                    
                    $uploadDir = '../uploads/banners/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $uploadedCount = 0;
                    $files = $_FILES['banners'];
                    $fileCount = count($files['name']);
                    
                    for ($i = 0; $i < $fileCount; $i++) {
                        if ($files['error'][$i] === UPLOAD_ERR_OK) {
                            // Validate file size (max 5MB)
                            if ($files['size'][$i] > 5 * 1024 * 1024) {
                                setAlert('File ' . $files['name'][$i] . ' is too large (max 5MB)', 'warning');
                                continue;
                            }
                            
                            // Validate image type
                            $imageInfo = getimagesize($files['tmp_name'][$i]);
                            if ($imageInfo === false) {
                                setAlert('File ' . $files['name'][$i] . ' is not a valid image', 'warning');
                                continue;
                            }
                            
                            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                            $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
                            if (!in_array($ext, $allowedExtensions)) {
                                setAlert('File ' . $files['name'][$i] . ' has invalid extension', 'warning');
                                continue;
                            }
                            
                            // Generate unique filename
                            $filename = 'banner_' . time() . '_' . $i . '.' . $ext;
                            $filepath = $uploadDir . $filename;
                            
                            if (move_uploaded_file($files['tmp_name'][$i], $filepath)) {
                                // Save to database
                                getDB()->query(
                                    "INSERT INTO login_banners (file_path, uploaded_by, display_order) VALUES (?, ?, ?)",
                                    [$filepath, getCurrentUser()['id'], $i]
                                );
                                $uploadedCount++;
                            }
                        }
                    }
                    
                    if ($uploadedCount > 0) {
                        setAlert("Successfully uploaded $uploadedCount banner(s)", 'success');
                    } else {
                        setAlert('No banners were uploaded', 'warning');
                    }
                    redirect();
                    break;
                    
                case 'delete_banner':
                    if (isset($_POST['banner_id'])) {
                        $banner = getDB()->fetchOne(
                            "SELECT * FROM login_banners WHERE id = ?",
                            [$_POST['banner_id']]
                        );
                        
                        if ($banner) {
                            // Delete file
                            if (file_exists($banner['file_path'])) {
                                unlink($banner['file_path']);
                            }
                            
                            // Delete from database
                            getDB()->query("DELETE FROM login_banners WHERE id = ?", [$_POST['banner_id']]);
                            
                            echo json_encode(['success' => true, 'message' => 'Banner deleted successfully']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Banner not found']);
                        }
                        exit;
                    }
                    break;
                    
                case 'toggle_banner_active':
                    if (isset($_POST['banner_id'])) {
                        $banner = getDB()->fetchOne(
                            "SELECT * FROM login_banners WHERE id = ?",
                            [$_POST['banner_id']]
                        );
                        
                        if ($banner) {
                            $newStatus = !$banner['is_active'];
                            getDB()->query(
                                "UPDATE login_banners SET is_active = ? WHERE id = ?",
                                [$newStatus, $_POST['banner_id']]
                            );
                            
                            echo json_encode(['success' => true, 'active' => $newStatus]);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Banner not found']);
                        }
                        exit;
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
$supportEmail = getSetting('support_email', 'support@example.com');
$appUrl = getSetting('app_url', '');
$loginBanners = getDB()->fetchAll("SELECT * FROM login_banners ORDER BY display_order, uploaded_at DESC");
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
                        <label class="form-label">Support Email</label>
                        <input type="email" class="form-control" name="support_email" value="<?php echo htmlspecialchars($supportEmail); ?>" required>
                        <small class="form-hint">This email will be displayed on the login page for password reset requests</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Application URL</label>
                        <input type="url" class="form-control" name="app_url" value="<?php echo htmlspecialchars($appUrl); ?>" placeholder="https://inventory.calebgruber.me">
                        <small class="form-hint">The base URL of your application (e.g., https://dev.inventory.calebgruber.me or https://inventory.calebgruber.me). Used in welcome emails for the login button.</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Logo (for Header & PDFs)</label>
                            <?php if ($logoPath && file_exists(UPLOAD_DIR . $logoPath)): ?>
                                <div class="mb-2 d-flex align-items-center gap-2">
                                    <img src="/uploads/<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" style="max-height: 80px; border: 1px solid #ddd; padding: 5px;">
                                    <form method="POST" style="display: inline;" onsubmit="showLoading()">
                                        <input type="hidden" name="action" value="remove_logo">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove logo and use text instead?')">
                                            <i class="ti ti-trash"></i> Remove Logo
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="logo" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp">
                            <small class="form-hint">Upload a logo to appear in header and on PDFs (PNG, JPG, GIF, WebP - max 5MB). Without a logo, the app name will be displayed as text.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Login Illustration (optional)</label>
                            <?php if ($loginIllustrationPath && file_exists(UPLOAD_DIR . $loginIllustrationPath)): ?>
                                <div class="mb-2">
                                    <img src="/uploads/<?php echo htmlspecialchars($loginIllustrationPath); ?>" alt="Login Illustration" style="max-height: 80px; border: 1px solid #ddd; padding: 5px;">
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
    
    <!-- Collapsible Inventory Management Section -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Inventory Management</h3>
            </div>
            <div class="card-body">
                <div class="accordion" id="inventoryAccordion">
                    <!-- Categories Accordion Item -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingCategories">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCategories">
                                <i class="ti ti-category icon me-2"></i>
                                Categories (<?php echo count($categories); ?>)
                            </button>
                        </h2>
                        <div id="collapseCategories" class="accordion-collapse collapse" data-bs-parent="#inventoryAccordion">
                            <div class="accordion-body">
                                <form method="POST" class="mb-3">
                                    <input type="hidden" name="action" value="add_category">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="name" placeholder="Category name" required>
                                        <button type="submit" class="btn btn-primary">Add</button>
                                    </div>
                                    <textarea class="form-control" name="description" placeholder="Description (optional)" rows="2"></textarea>
                                </form>
                                
                                <div class="table-responsive">
                                    <table class="table table-sm card-table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Description</th>
                                                <th width="80">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $category): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                                                    <td class="text-muted"><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                                                    <td>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="delete_category">
                                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Subcategories Accordion Item -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingSubcategories">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSubcategories">
                                <i class="ti ti-list icon me-2"></i>
                                Subcategories (<?php echo count($subcategories); ?>)
                            </button>
                        </h2>
                        <div id="collapseSubcategories" class="accordion-collapse collapse" data-bs-parent="#inventoryAccordion">
                            <div class="accordion-body">
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
                                
                                <div class="table-responsive">
                                    <table class="table table-sm card-table">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Name</th>
                                                <th>Description</th>
                                                <th width="80">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subcategories as $subcategory): ?>
                                                <tr>
                                                    <td><span class="badge bg-blue-lt"><?php echo htmlspecialchars($subcategory['category_name']); ?></span></td>
                                                    <td><strong><?php echo htmlspecialchars($subcategory['name']); ?></strong></td>
                                                    <td class="text-muted"><?php echo htmlspecialchars($subcategory['description'] ?? ''); ?></td>
                                                    <td>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="delete_subcategory">
                                                            <input type="hidden" name="id" value="<?php echo $subcategory['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this subcategory?')">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Theatre Spaces Accordion Item -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTheatreSpaces">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTheatreSpaces">
                                <i class="ti ti-building-theater icon me-2"></i>
                                Theatre Spaces (<?php echo count($theatreSpaces); ?>)
                            </button>
                        </h2>
                        <div id="collapseTheatreSpaces" class="accordion-collapse collapse" data-bs-parent="#inventoryAccordion">
                            <div class="accordion-body">
                                <form method="POST" class="mb-3">
                                    <input type="hidden" name="action" value="add_theatre_space">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="name" placeholder="Space name" required>
                                        <button type="submit" class="btn btn-primary">Add</button>
                                    </div>
                                    <textarea class="form-control" name="description" placeholder="Description (optional)" rows="2"></textarea>
                                </form>
                                
                                <div class="table-responsive">
                                    <table class="table table-sm card-table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Description</th>
                                                <th width="80">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($theatreSpaces as $space): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($space['name']); ?></strong></td>
                                                    <td class="text-muted"><?php echo htmlspecialchars($space['description'] ?? ''); ?></td>
                                                    <td>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="delete_theatre_space">
                                                            <input type="hidden" name="id" value="<?php echo $space['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this theatre space?')">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Order CSV Templates</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h4 class="alert-title">CSV Order Import</h4>
                    <div class="text-muted">
                        <p>Download a CSV template to import shop orders (pullsheets) and change orders in bulk.</p>
                        <p><strong>Features:</strong></p>
                        <ul class="mb-0">
                            <li>Import multiple orders at once from a single CSV file</li>
                            <li>Works for both shop orders and change orders</li>
                            <li>Automatically groups items by show and order type</li>
                            <li>Includes validation and error reporting</li>
                        </ul>
                        <p class="mt-2 mb-0"><strong>How to use:</strong> Download the template, fill in your order details, then upload it on the Shop Orders or Change Orders page.</p>
                    </div>
                </div>
                
                <a href="csv_template.php" class="btn btn-primary" download>
                    <i class="ti ti-download icon"></i>
                    Download Order Import Template (CSV)
                </a>
                
                <hr class="my-4">
                
                <div class="card bg-light">
                    <div class="card-body">
                        <h4 class="card-title">CSV Format</h4>
                        <p><strong>Columns:</strong></p>
                        <ul>
                            <li><strong>Order Type:</strong> "shop_order" or "change_order"</li>
                            <li><strong>Show Name:</strong> Name of the show (must exist in database)</li>
                            <li><strong>Item Barcode:</strong> Barcode of the inventory item</li>
                            <li><strong>Item Name:</strong> (Optional) For reference only</li>
                            <li><strong>Quantity:</strong> Number of items</li>
                            <li><strong>Type:</strong> For change orders: "add" or "remove" (leave blank for shop orders)</li>
                        </ul>
                        <p class="mb-0"><strong>Note:</strong> All rows with the same Order Type and Show Name will be grouped into one order automatically.</p>
                    </div>
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
                
                <div class="alert alert-warning">
                    <h4 class="alert-title"><i class="ti ti-alert-triangle"></i> Clear All Data</h4>
                    <p>This will permanently delete all categories, subcategories, and items from the database. This action cannot be undone!</p>
                    <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete ALL categories, subcategories, and items? This action CANNOT be undone!');">
                        <input type="hidden" name="action" value="clear_all_data">
                        <button type="submit" class="btn btn-danger">
                            <i class="ti ti-trash icon"></i>
                            Clear All Categories, Subcategories & Items
                        </button>
                    </form>
                </div>
                
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
        
        <!-- Login Banners Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Login Banners</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Upload multiple banner images for the login page. Images will rotate to provide visual variety.
                </p>
                
                <!-- Upload Form -->
                <form method="POST" enctype="multipart/form-data" onsubmit="return handleBannerUpload(event)">
                    <input type="hidden" name="action" value="upload_banner">
                    <div class="mb-3">
                        <label class="form-label">Upload Banners (Multiple)</label>
                        <input type="file" class="form-control" name="banners[]" id="bannerFiles" 
                               accept="image/png,image/jpeg,image/gif,image/webp" 
                               multiple onchange="previewBanners(this)">
                        <div class="form-hint">
                            Maximum 5MB per image. Supported formats: JPG, PNG, GIF, WEBP
                        </div>
                    </div>
                    
                    <!-- Preview Container -->
                    <div id="bannerPreview" class="row g-2 mb-3"></div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-upload icon"></i>
                        Upload Banners
                    </button>
                </form>
                
                <!-- Existing Banners Gallery -->
                <?php if (!empty($loginBanners)): ?>
                <hr class="my-4">
                <h4 class="mb-3">Current Banners (<?= count($loginBanners) ?>)</h4>
                <div class="row g-3">
                    <?php foreach ($loginBanners as $banner): ?>
                    <div class="col-md-4 col-lg-3" id="banner-<?= $banner['id'] ?>">
                        <div class="card">
                            <img src="<?= htmlspecialchars($banner['file_path']) ?>" 
                                 class="card-img-top" 
                                 style="height: 200px; object-fit: cover;"
                                 alt="Login Banner">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge <?= $banner['is_active'] ? 'bg-success' : 'bg-secondary' ?>" id="status-badge-<?= $banner['id'] ?>">
                                        <?= $banner['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                    <small class="text-muted">Order: <?= $banner['display_order'] ?></small>
                                </div>
                                <small class="text-muted d-block mb-2">
                                    <?= date('M j, Y', strtotime($banner['uploaded_at'])) ?>
                                </small>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="toggleBannerActive(<?= $banner['id'] ?>)" 
                                            id="toggle-btn-<?= $banner['id'] ?>">
                                        <i class="ti ti-toggle-<?= $banner['is_active'] ? 'right' : 'left' ?> icon"></i>
                                        Toggle
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteBanner(<?= $banner['id'] ?>)">
                                        <i class="ti ti-trash icon"></i>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="alert alert-info mt-3">
                    <i class="ti ti-info-circle icon"></i>
                    No banners uploaded yet. Upload your first banner above!
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Database Migrations Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Database Migrations</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Run database migrations to update the database schema to the latest version. 
                    This ensures all required tables and columns exist.
                </p>
                
                <button type="button" class="btn btn-primary" id="runMigrationsBtn" onclick="runMigrations()">
                    <i class="ti ti-database-cog icon"></i>
                    Run Database Migrations
                </button>
                
                <div id="migrationOutput" class="mt-3" style="display: none;">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Migration Output:</h5>
                            <pre id="migrationLog" style="max-height: 400px; overflow-y: auto; font-size: 12px;"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function runMigrations() {
    const btn = document.getElementById('runMigrationsBtn');
    const output = document.getElementById('migrationOutput');
    const log = document.getElementById('migrationLog');
    
    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Running migrations...';
    output.style.display = 'block';
    log.textContent = 'Starting migrations...\n';
    
    // Run migrations
    fetch('run_migrations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            log.textContent = data.output.join('\n');
            
            // Show success message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
            alertDiv.innerHTML = `
                <i class="ti ti-check icon"></i>
                <strong>Success!</strong> ${data.migrations_run} migration(s) completed successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            output.parentElement.insertBefore(alertDiv, output);
        } else {
            log.textContent = data.output ? data.output.join('\n') : data.error;
            
            // Show error message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
            alertDiv.innerHTML = `
                <i class="ti ti-alert-triangle icon"></i>
                <strong>Error!</strong> Some migrations failed. Check the output above.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            output.parentElement.insertBefore(alertDiv, output);
        }
    })
    .catch(error => {
        log.textContent = 'Error: ' + error.message;
        
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
        alertDiv.innerHTML = `
            <i class="ti ti-alert-triangle icon"></i>
            <strong>Error!</strong> Failed to run migrations: ${error.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        output.parentElement.insertBefore(alertDiv, output);
    })
    .finally(() => {
        // Re-enable button
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-database-cog icon"></i> Run Database Migrations';
    });
}

// Banner management functions
function previewBanners(input) {
    const preview = document.getElementById('bannerPreview');
    preview.innerHTML = '';
    
    if (input.files) {
        Array.from(input.files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-md-3';
                    col.innerHTML = `
                        <div class="card">
                            <img src="${e.target.result}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Preview">
                            <div class="card-body p-2">
                                <small class="text-muted">${file.name}</small>
                            </div>
                        </div>
                    `;
                    preview.appendChild(col);
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

function handleBannerUpload(event) {
    const fileInput = document.getElementById('bannerFiles');
    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Please select at least one image file');
        event.preventDefault();
        return false;
    }
    
    // Check file sizes
    for (let i = 0; i < fileInput.files.length; i++) {
        if (fileInput.files[i].size > 5 * 1024 * 1024) {
            alert(`File "${fileInput.files[i].name}" is too large (max 5MB)`);
            event.preventDefault();
            return false;
        }
    }
    
    return true;
}

function deleteBanner(bannerId) {
    if (!confirm('Are you sure you want to delete this banner? This action cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_banner');
    formData.append('banner_id', bannerId);
    
    fetch('settings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the banner card from DOM
            const bannerCard = document.getElementById('banner-' + bannerId);
            if (bannerCard) {
                bannerCard.remove();
            }
            
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                <i class="ti ti-check icon"></i>
                ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.card-body').prepend(alert);
            
            setTimeout(() => alert.remove(), 3000);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error deleting banner: ' + error.message);
    });
}

function toggleBannerActive(bannerId) {
    const formData = new FormData();
    formData.append('action', 'toggle_banner_active');
    formData.append('banner_id', bannerId);
    
    fetch('settings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update badge
            const badge = document.getElementById('status-badge-' + bannerId);
            const toggleBtn = document.getElementById('toggle-btn-' + bannerId);
            
            if (data.active) {
                badge.className = 'badge bg-success';
                badge.textContent = 'Active';
                toggleBtn.querySelector('.ti').className = 'ti ti-toggle-right icon';
            } else {
                badge.className = 'badge bg-secondary';
                badge.textContent = 'Inactive';
                toggleBtn.querySelector('.ti').className = 'ti ti-toggle-left icon';
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error toggling banner status: ' + error.message);
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
