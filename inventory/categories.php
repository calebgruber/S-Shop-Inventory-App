<?php
/**
 * Category and Subcategory Management
 * Manage product categories and subcategories
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();
requireRole(['admin', 'designer', 'production_audio']);

$pageName = 'Categories';
$error = '';
$success = '';

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_category') {
        $name = sanitize($_POST['category_name'] ?? '');
        $description = sanitize($_POST['category_description'] ?? '');
        
        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            $result = executeQuery(
                'INSERT INTO categories (name, description) VALUES (?, ?)',
                [$name, $description],
                'ss'
            );
            
            if ($result) {
                $success = 'Category added successfully.';
            } else {
                $error = 'Failed to add category. Name may already exist.';
            }
        }
    }
    
    elseif ($action === 'edit_category') {
        $id = intval($_POST['category_id']);
        $name = sanitize($_POST['category_name'] ?? '');
        $description = sanitize($_POST['category_description'] ?? '');
        
        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            $result = executeQuery(
                'UPDATE categories SET name = ?, description = ? WHERE id = ?',
                [$name, $description, $id],
                'ssi'
            );
            
            if ($result) {
                $success = 'Category updated successfully.';
            } else {
                $error = 'Failed to update category.';
            }
        }
    }
    
    elseif ($action === 'delete_category' && hasRole('admin')) {
        $id = intval($_POST['category_id']);
        
        // Check if category has items
        $checkResult = executeQuery(
            'SELECT COUNT(*) as count FROM items WHERE category_id = ?',
            [$id],
            'i'
        );
        
        if ($checkResult) {
            $row = fetchAssoc($checkResult);
            if ($row['count'] > 0) {
                $error = 'Cannot delete category. It has ' . $row['count'] . ' item(s).';
            } else {
                $result = executeQuery('DELETE FROM categories WHERE id = ?', [$id], 'i');
                if ($result) {
                    $success = 'Category deleted successfully.';
                } else {
                    $error = 'Failed to delete category.';
                }
            }
        }
    }
    
    elseif ($action === 'add_subcategory') {
        $categoryId = intval($_POST['category_id']);
        $name = sanitize($_POST['subcategory_name'] ?? '');
        $description = sanitize($_POST['subcategory_description'] ?? '');
        
        if (empty($name)) {
            $error = 'Subcategory name is required.';
        } elseif (empty($categoryId)) {
            $error = 'Parent category is required.';
        } else {
            $result = executeQuery(
                'INSERT INTO subcategories (category_id, name, description) VALUES (?, ?, ?)',
                [$categoryId, $name, $description],
                'iss'
            );
            
            if ($result) {
                $success = 'Subcategory added successfully.';
            } else {
                $error = 'Failed to add subcategory.';
            }
        }
    }
    
    elseif ($action === 'edit_subcategory') {
        $id = intval($_POST['subcategory_id']);
        $categoryId = intval($_POST['category_id']);
        $name = sanitize($_POST['subcategory_name'] ?? '');
        $description = sanitize($_POST['subcategory_description'] ?? '');
        
        if (empty($name)) {
            $error = 'Subcategory name is required.';
        } else {
            $result = executeQuery(
                'UPDATE subcategories SET category_id = ?, name = ?, description = ? WHERE id = ?',
                [$categoryId, $name, $description, $id],
                'issi'
            );
            
            if ($result) {
                $success = 'Subcategory updated successfully.';
            } else {
                $error = 'Failed to update subcategory.';
            }
        }
    }
    
    elseif ($action === 'delete_subcategory' && hasRole('admin')) {
        $id = intval($_POST['subcategory_id']);
        
        // Check if subcategory has items
        $checkResult = executeQuery(
            'SELECT COUNT(*) as count FROM items WHERE subcategory_id = ?',
            [$id],
            'i'
        );
        
        if ($checkResult) {
            $row = fetchAssoc($checkResult);
            if ($row['count'] > 0) {
                $error = 'Cannot delete subcategory. It has ' . $row['count'] . ' item(s).';
            } else {
                $result = executeQuery('DELETE FROM subcategories WHERE id = ?', [$id], 'i');
                if ($result) {
                    $success = 'Subcategory deleted successfully.';
                } else {
                    $error = 'Failed to delete subcategory.';
                }
            }
        }
    }
}

// Get all categories with subcategories
$categories = [];
$categoriesResult = executeQuery('SELECT * FROM categories ORDER BY name ASC');
if ($categoriesResult) {
    while ($cat = fetchAssoc($categoriesResult)) {
        // Get subcategories for this category
        $subsResult = executeQuery(
            'SELECT * FROM subcategories WHERE category_id = ? ORDER BY name ASC',
            [$cat['id']],
            'i'
        );
        $cat['subcategories'] = [];
        if ($subsResult) {
            while ($sub = fetchAssoc($subsResult)) {
                $cat['subcategories'][] = $sub;
            }
        }
        
        // Get item count
        $countResult = executeQuery(
            'SELECT COUNT(*) as count FROM items WHERE category_id = ?',
            [$cat['id']],
            'i'
        );
        $cat['item_count'] = $countResult ? fetchAssoc($countResult)['count'] : 0;
        
        $categories[] = $cat;
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <a href="<?php echo BASE_URL; ?>inventory/">Inventory</a>
                </div>
                <h2 class="page-title">
                    <i class="ti ti-category me-2"></i>
                    Categories & Subcategories
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-category-modal">
                        <i class="ti ti-plus me-2"></i>
                        Add Category
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page Body -->
<div class="page-body">
    <div class="container-xl">
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <div class="d-flex">
                <div><i class="ti ti-alert-circle me-2"></i></div>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <div class="d-flex">
                <div><i class="ti ti-check me-2"></i></div>
                <div><?php echo htmlspecialchars($success); ?></div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        <?php endif; ?>
        
        <?php if (empty($categories)): ?>
        <div class="empty">
            <div class="empty-icon">
                <i class="ti ti-category icon"></i>
            </div>
            <p class="empty-title">No categories yet</p>
            <p class="empty-subtitle text-muted">
                Get started by adding your first category.
            </p>
            <div class="empty-action">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-category-modal">
                    <i class="ti ti-plus me-2"></i>
                    Add Category
                </button>
            </div>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($categories as $category): ?>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <div class="card-actions">
                            <span class="badge bg-blue"><?php echo $category['item_count']; ?> items</span>
                            <button type="button" class="btn btn-sm btn-icon" 
                                    onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>', '<?php echo addslashes($category['description'] ?? ''); ?>')" 
                                    title="Edit">
                                <i class="ti ti-edit"></i>
                            </button>
                            <?php if (hasRole('admin') && $category['item_count'] == 0): ?>
                            <button type="button" class="btn btn-sm btn-icon text-danger" 
                                    onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>')" 
                                    title="Delete">
                                <i class="ti ti-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($category['description']): ?>
                    <div class="card-body py-2">
                        <div class="text-muted small"><?php echo htmlspecialchars($category['description']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="list-group list-group-flush">
                        <?php if (empty($category['subcategories'])): ?>
                        <div class="list-group-item text-muted">
                            <em>No subcategories</em>
                        </div>
                        <?php else: ?>
                        <?php foreach ($category['subcategories'] as $sub): ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="fw-bold"><?php echo htmlspecialchars($sub['name']); ?></div>
                                    <?php if ($sub['description']): ?>
                                    <div class="text-muted small"><?php echo htmlspecialchars($sub['description']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-auto">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-icon" 
                                                onclick="editSubcategory(<?php echo $sub['id']; ?>, <?php echo $category['id']; ?>, '<?php echo addslashes($sub['name']); ?>', '<?php echo addslashes($sub['description'] ?? ''); ?>')" 
                                                title="Edit">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <?php if (hasRole('admin')): ?>
                                        <button type="button" class="btn btn-icon text-danger" 
                                                onclick="deleteSubcategory(<?php echo $sub['id']; ?>, '<?php echo addslashes($sub['name']); ?>')" 
                                                title="Delete">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                onclick="showAddSubcategory(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>')">
                            <i class="ti ti-plus me-2"></i>
                            Add Subcategory
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal modal-blur fade" id="add-category-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_category">
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Name</label>
                        <input type="text" class="form-control" name="category_name" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="category_description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal modal-blur fade" id="edit-category-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_category">
                <input type="hidden" name="category_id" id="edit-category-id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Name</label>
                        <input type="text" class="form-control" name="category_name" id="edit-category-name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="category_description" id="edit-category-description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Subcategory Modal -->
<div class="modal modal-blur fade" id="add-subcategory-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_subcategory">
                <input type="hidden" name="category_id" id="add-sub-category-id">
                <div class="modal-header">
                    <h5 class="modal-title">Add Subcategory to <span id="add-sub-category-name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Name</label>
                        <input type="text" class="form-control" name="subcategory_name" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="subcategory_description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Subcategory</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subcategory Modal -->
<div class="modal modal-blur fade" id="edit-subcategory-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_subcategory">
                <input type="hidden" name="subcategory_id" id="edit-subcategory-id">
                <input type="hidden" name="category_id" id="edit-sub-category-id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Subcategory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Name</label>
                        <input type="text" class="form-control" name="subcategory_name" id="edit-subcategory-name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="subcategory_description" id="edit-subcategory-description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Subcategory</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCategory(id, name, description) {
    document.getElementById('edit-category-id').value = id;
    document.getElementById('edit-category-name').value = name;
    document.getElementById('edit-category-description').value = description;
    new bootstrap.Modal(document.getElementById('edit-category-modal')).show();
}

function deleteCategory(id, name) {
    if (confirm('Are you sure you want to delete the category "' + name + '"?\n\nThis action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_category';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'category_id';
        idInput.value = id;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function showAddSubcategory(categoryId, categoryName) {
    document.getElementById('add-sub-category-id').value = categoryId;
    document.getElementById('add-sub-category-name').textContent = categoryName;
    new bootstrap.Modal(document.getElementById('add-subcategory-modal')).show();
}

function editSubcategory(id, categoryId, name, description) {
    document.getElementById('edit-subcategory-id').value = id;
    document.getElementById('edit-sub-category-id').value = categoryId;
    document.getElementById('edit-subcategory-name').value = name;
    document.getElementById('edit-subcategory-description').value = description;
    new bootstrap.Modal(document.getElementById('edit-subcategory-modal')).show();
}

function deleteSubcategory(id, name) {
    if (confirm('Are you sure you want to delete the subcategory "' + name + '"?\n\nThis action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_subcategory';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'subcategory_id';
        idInput.value = id;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
