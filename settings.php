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
                    
                case 'import_csv':
                    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                        setAlert('Please select a CSV file to upload', 'danger');
                        redirect();
                    }
                    
                    $importType = $_POST['import_type'] ?? '';
                    if (!in_array($importType, ['categories', 'subcategories', 'theatre_spaces', 'items', 'all'])) {
                        setAlert('Invalid import type selected', 'danger');
                        redirect();
                    }
                    
                    // Validate file is CSV
                    $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
                    if ($ext !== 'csv') {
                        setAlert('Please upload a CSV file', 'danger');
                        redirect();
                    }
                    
                    // Read CSV file
                    $csvFile = $_FILES['csv_file']['tmp_name'];
                    $handle = fopen($csvFile, 'r');
                    
                    if ($handle === false) {
                        setAlert('Failed to read CSV file', 'danger');
                        redirect();
                    }
                    
                    $db = getDB();
                    $imported = 0;
                    $skipped = 0;
                    $errors = [];
                    
                    try {
                        // Get header row
                        $headers = fgetcsv($handle);
                        
                        // Normalize headers to lowercase for case-insensitive matching
                        $normalizedHeaders = array_map('strtolower', $headers);
                        
                        if ($importType === 'all') {
                            // For "all" type, detect based on headers
                            // CSV format: Column 1 = ID, Column 2 = Name (as per user requirement)
                            if (in_array('category_id', $normalizedHeaders) && in_array('barcode', $normalizedHeaders)) {
                                $importType = 'items';
                            } elseif (in_array('category_id', $normalizedHeaders) && !in_array('barcode', $normalizedHeaders)) {
                                $importType = 'subcategories';
                            } elseif (count($headers) <= 3 && !in_array('category_id', $normalizedHeaders)) {
                                // Likely categories - simple ID, name, description format
                                $importType = 'categories';
                            }
                        }
                        
                        // Process based on import type
                        switch ($importType) {
                            case 'categories':
                                // CSV format: Column 1 = ID, Column 2 = Name, Column 3 = Description (optional)
                                // This allows importing with specific IDs to maintain relationships
                                
                                while (($row = fgetcsv($handle)) !== false) {
                                    // Skip empty rows
                                    if (empty(array_filter($row))) continue;
                                    
                                    // Get ID from column 0, name from column 1
                                    $csvId = isset($row[0]) && trim($row[0]) !== '' ? trim($row[0]) : null;
                                    $name = isset($row[1]) && trim($row[1]) !== '' ? trim($row[1]) : null;
                                    $description = isset($row[2]) ? trim($row[2]) : '';
                                    
                                    if (!$name) continue; // Name is required
                                    
                                    try {
                                        // Check if category with this name already exists
                                        $existing = $db->fetchOne("SELECT id FROM categories WHERE name = ?", [$name]);
                                        if ($existing) {
                                            $skipped++;
                                            continue;
                                        }
                                        
                                        // If CSV has an ID column and it's numeric, try to insert with that ID
                                        if ($csvId && is_numeric($csvId)) {
                                            // Check if ID is already taken
                                            $idExists = $db->fetchOne("SELECT id FROM categories WHERE id = ?", [(int)$csvId]);
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
                                                    [(int)$csvId, $name, $description]
                                                );
                                            }
                                        } else {
                                            // No ID specified or not numeric, auto-generate
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
                                // CSV format: Column 1 = ID, Column 2 = Name, Column 3 = Category ID, Column 4 = Description (optional)
                                // This allows importing with specific IDs and category relationships
                                
                                while (($row = fgetcsv($handle)) !== false) {
                                    // Skip empty rows
                                    if (empty(array_filter($row))) continue;
                                    
                                    // Get values: ID from column 0, name from column 1
                                    $csvId = isset($row[0]) && trim($row[0]) !== '' ? trim($row[0]) : null;
                                    $name = isset($row[1]) && trim($row[1]) !== '' ? trim($row[1]) : null;
                                    
                                    if (!$name) continue; // Name is required
                                    
                                    // Try to find category_id from headers or assume column 2
                                    $categoryValue = isset($row[2]) && trim($row[2]) !== '' ? trim($row[2]) : null;
                                    $description = isset($row[3]) ? trim($row[3]) : '';
                                    
                                    if (!$categoryValue) {
                                        $errors[] = "Subcategory '$name': Missing category reference";
                                        continue;
                                    }
                                    
                                    // Determine if category value is ID or name
                                    $categoryId = null;
                                    try {
                                        if (is_numeric($categoryValue)) {
                                            // Treat as category ID
                                            $category = $db->fetchOne("SELECT id FROM categories WHERE id = ?", [(int)$categoryValue]);
                                            if ($category) {
                                                $categoryId = $category['id'];
                                            } else {
                                                $errors[] = "Subcategory '$name': Category ID '$categoryValue' not found";
                                                continue;
                                            }
                                        } else {
                                            // Treat as category name
                                            $category = $db->fetchOne("SELECT id FROM categories WHERE name = ?", [$categoryValue]);
                                            if ($category) {
                                                $categoryId = $category['id'];
                                            } else {
                                                $errors[] = "Subcategory '$name': Category '$categoryValue' not found";
                                                continue;
                                            }
                                        }
                                    } catch (Exception $e) {
                                        $errors[] = "Subcategory '$name': Error looking up category: " . $e->getMessage();
                                        continue;
                                    }
                                    
                                    if (!$categoryId) continue;
                                    
                                    try {
                                        // Check if subcategory exists
                                        $existing = $db->fetchOne(
                                            "SELECT id FROM subcategories WHERE name = ? AND category_id = ?",
                                            [$name, $categoryId]
                                        );
                                        if ($existing) {
                                            $skipped++;
                                            continue;
                                        }
                                        
                                        // If CSV has an ID column and it's numeric, try to insert with that ID
                                        if ($csvId && is_numeric($csvId)) {
                                            // Check if ID is already taken
                                            $idExists = $db->fetchOne("SELECT id FROM subcategories WHERE id = ?", [(int)$csvId]);
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
                                                    [(int)$csvId, $categoryId, $name, $description]
                                                );
                                            }
                                        } else {
                                            // No ID specified or not numeric, auto-generate
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
                                
                            case 'theatre_spaces':
                                // Map headers to column indices
                                $headerMap = array_flip(array_map('strtolower', $headers));
                                
                                while (($row = fgetcsv($handle)) !== false) {
                                    // Skip empty rows
                                    if (empty(array_filter($row))) continue;
                                    
                                    // Get values from the correct columns
                                    $nameIdx = $headerMap['name'] ?? 0;
                                    $descIdx = $headerMap['description'] ?? 1;
                                    
                                    if (empty($row[$nameIdx])) continue;
                                    
                                    $name = trim($row[$nameIdx]);
                                    $description = isset($row[$descIdx]) ? trim($row[$descIdx]) : '';
                                    
                                    try {
                                        // Check if space exists
                                        $existing = $db->fetchOne("SELECT id FROM theatre_spaces WHERE name = ?", [$name]);
                                        if ($existing) {
                                            $skipped++;
                                            continue;
                                        }
                                        
                                        $db->query(
                                            "INSERT INTO theatre_spaces (name, description) VALUES (?, ?)",
                                            [$name, $description]
                                        );
                                        $imported++;
                                    } catch (Exception $e) {
                                        $errors[] = "Theatre space '$name': " . $e->getMessage();
                                    }
                                }
                                break;
                                
                            case 'items':
                                // CSV format: Column 1 = ID, Column 2 = Name, then other columns
                                // Support both ID-based and name-based lookups for categories/subcategories
                                
                                // Map headers to column indices
                                $headerMap = array_flip(array_map('strtolower', $headers));
                                
                                while (($row = fgetcsv($handle)) !== false) {
                                    // Skip empty rows
                                    if (empty(array_filter($row))) continue;
                                    
                                    // Get ID from column 0 (optional), name from column 1
                                    $csvId = isset($row[0]) && trim($row[0]) !== '' ? trim($row[0]) : null;
                                    $name = isset($row[1]) && trim($row[1]) !== '' ? trim($row[1]) : null;
                                    
                                    if (!$name) continue; // Name is required
                                    
                                    // Get other fields using header map
                                    $description = isset($headerMap['description']) && isset($row[$headerMap['description']]) 
                                        ? trim($row[$headerMap['description']]) : '';
                                    $barcode = isset($headerMap['barcode']) && isset($row[$headerMap['barcode']]) 
                                        ? trim($row[$headerMap['barcode']]) : '';
                                    
                                    // If no barcode, skip (barcode is required for items)
                                    if (empty($barcode)) {
                                        $errors[] = "Item '$name': Missing barcode";
                                        continue;
                                    }
                                    
                                    // Get category and subcategory references (can be ID or name)
                                    $categoryRef = null;
                                    $subcategoryRef = null;
                                    
                                    // Check for category_id or category_name header
                                    if (isset($headerMap['category_id']) && isset($row[$headerMap['category_id']])) {
                                        $categoryRef = trim($row[$headerMap['category_id']]);
                                    } elseif (isset($headerMap['category_name']) && isset($row[$headerMap['category_name']])) {
                                        $categoryRef = trim($row[$headerMap['category_name']]);
                                    } elseif (isset($headerMap['category']) && isset($row[$headerMap['category']])) {
                                        $categoryRef = trim($row[$headerMap['category']]);
                                    }
                                    
                                    // Check for subcategory_id or subcategory_name header
                                    if (isset($headerMap['subcategory_id']) && isset($row[$headerMap['subcategory_id']])) {
                                        $subcategoryRef = trim($row[$headerMap['subcategory_id']]);
                                    } elseif (isset($headerMap['subcategory_name']) && isset($row[$headerMap['subcategory_name']])) {
                                        $subcategoryRef = trim($row[$headerMap['subcategory_name']]);
                                    } elseif (isset($headerMap['subcategory']) && isset($row[$headerMap['subcategory']])) {
                                        $subcategoryRef = trim($row[$headerMap['subcategory']]);
                                    }
                                    
                                    $trackingType = isset($headerMap['tracking_type']) && isset($row[$headerMap['tracking_type']]) 
                                        ? trim($row[$headerMap['tracking_type']]) : 'quantity';
                                    $totalQuantity = isset($headerMap['total_quantity']) && isset($row[$headerMap['total_quantity']]) 
                                        ? (int)$row[$headerMap['total_quantity']] : 0;
                                    $inStockQuantity = isset($headerMap['in_stock_quantity']) && isset($row[$headerMap['in_stock_quantity']]) 
                                        ? (int)$row[$headerMap['in_stock_quantity']] : 0;
                                    $location = isset($headerMap['location']) && isset($row[$headerMap['location']]) 
                                        ? trim($row[$headerMap['location']]) : '';
                                    
                                    try {
                                        // Check if item exists
                                        $existing = $db->fetchOne("SELECT id FROM items WHERE barcode = ?", [$barcode]);
                                        if ($existing) {
                                            $skipped++;
                                            continue;
                                        }
                                        
                                        // Find category ID
                                        $categoryId = null;
                                        if ($categoryRef) {
                                            if (is_numeric($categoryRef)) {
                                                // Lookup by ID
                                                $category = $db->fetchOne("SELECT id FROM categories WHERE id = ?", [(int)$categoryRef]);
                                                $categoryId = $category ? $category['id'] : null;
                                            } else {
                                                // Lookup by name
                                                $category = $db->fetchOne("SELECT id FROM categories WHERE name = ?", [$categoryRef]);
                                                $categoryId = $category ? $category['id'] : null;
                                            }
                                        }
                                        
                                        // Find subcategory ID
                                        $subcategoryId = null;
                                        if ($subcategoryRef && $categoryId) {
                                            if (is_numeric($subcategoryRef)) {
                                                // Lookup by ID (and verify it belongs to the category)
                                                $subcategory = $db->fetchOne(
                                                    "SELECT id FROM subcategories WHERE id = ? AND category_id = ?",
                                                    [(int)$subcategoryRef, $categoryId]
                                                );
                                                $subcategoryId = $subcategory ? $subcategory['id'] : null;
                                            } else {
                                                // Lookup by name within the category
                                                $subcategory = $db->fetchOne(
                                                    "SELECT id FROM subcategories WHERE name = ? AND category_id = ?",
                                                    [$subcategoryRef, $categoryId]
                                                );
                                                $subcategoryId = $subcategory ? $subcategory['id'] : null;
                                            }
                                        }
                                        
                                        // Insert item (with or without specified ID)
                                        if ($csvId && is_numeric($csvId)) {
                                            // Check if ID is already taken
                                            $idExists = $db->fetchOne("SELECT id FROM items WHERE id = ?", [(int)$csvId]);
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
                                                    [(int)$csvId, $name, $description, $barcode, $categoryId, $subcategoryId, $trackingType, $totalQuantity, $inStockQuantity, $location]
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
                        
                        fclose($handle);
                        
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
                        fclose($handle);
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
                <h3 class="card-title">Import Data from CSV</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h4 class="alert-title">CSV Import Instructions</h4>
                    <div class="text-muted">
                        <p>Upload a CSV file to import data into the database. The system will automatically detect the data type or you can specify it.</p>
                        <p><strong>CSV Format Requirements:</strong></p>
                        <ul class="mb-0">
                            <li><strong>Categories:</strong> name, description (optional)</li>
                            <li><strong>Subcategories:</strong> category_id OR category_name, name, description (optional)</li>
                            <li><strong>Theatre Spaces:</strong> name, description (optional)</li>
                            <li><strong>Items:</strong> name, description, barcode, category_name, subcategory_name, tracking_type, total_quantity, in_stock_quantity, location</li>
                        </ul>
                        <p class="mt-2 mb-0"><strong>Note:</strong> The first row should contain column headers. Duplicate entries (based on name or barcode) will be skipped.</p>
                    </div>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import_csv">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">CSV File</label>
                            <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                            <small class="form-hint">Select a CSV file to import (comma-separated values)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Import Type</label>
                            <select class="form-select" name="import_type" required>
                                <option value="all">Auto-detect from file</option>
                                <option value="categories">Categories</option>
                                <option value="subcategories">Subcategories</option>
                                <option value="theatre_spaces">Theatre Spaces</option>
                                <option value="items">Items</option>
                            </select>
                            <small class="form-hint">Select what type of data you're importing</small>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="ti ti-upload icon"></i>
                        Import CSV
                    </button>
                </form>
                
                <hr class="my-4">
                
                <div class="accordion" id="csvExamplesAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingExamples">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExamples">
                                <i class="ti ti-help icon me-2"></i>
                                View CSV Format Examples
                            </button>
                        </h2>
                        <div id="collapseExamples" class="accordion-collapse collapse" data-bs-parent="#csvExamplesAccordion">
                            <div class="accordion-body">
                                <h5>Categories CSV Example:</h5>
                                <pre class="bg-light p-2 rounded">name,description
Microphones,Professional microphones
Cables,Audio cables and adapters
Speakers,PA speakers and monitors</pre>
                                
                                <h5 class="mt-3">Subcategories CSV Example:</h5>
                                <p class="small text-muted">Option 1: Using category names</p>
                                <pre class="bg-light p-2 rounded">category_name,name,description
Microphones,Wired Microphones,Standard wired microphones
Microphones,Wireless Microphones,Wireless microphone systems
Cables,XLR Cables,3-pin XLR cables</pre>
                                <p class="small text-muted">Option 2: Using category IDs (for database exports)</p>
                                <pre class="bg-light p-2 rounded">category_id,name,description
1,Wired Microphones,Standard wired microphones
1,Wireless Microphones,Wireless microphone systems
2,XLR Cables,3-pin XLR cables</pre>
                                
                                <h5 class="mt-3">Theatre Spaces CSV Example:</h5>
                                <pre class="bg-light p-2 rounded">name,description
Main Stage,Primary performance space
Studio Theatre,Intimate black box theatre
Rehearsal Hall,Large rehearsal space</pre>
                                
                                <h5 class="mt-3">Items CSV Example:</h5>
                                <pre class="bg-light p-2 rounded">name,description,barcode,category_name,subcategory_name,tracking_type,total_quantity,in_stock_quantity,location
Shure SM58,Dynamic vocal microphone,MIC-SM58-001,Microphones,Wired Microphones,quantity,10,8,Cabinet A1
Sennheiser EW 100,Wireless handheld system,MIC-EW100-001,Microphones,Wireless Microphones,quantity,5,5,Cabinet A2
XLR Cable 25ft,25 foot XLR cable,CABLE-XLR25-001,Cables,XLR Cables,quantity,50,45,Cable Rack 1</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
