<?php
require_once 'includes/functions.php';

// Check permissions BEFORE including header
if (!hasPermission('paperwork')) {
    setAlert('You do not have permission to access paperwork', 'danger');
    redirect('index.php');
}

$pageTitle = 'Master Barcode List';
require_once 'includes/header.php';

$db = getDB();

// Get filter parameters
$categoryFilter = $_GET['category'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'name';
$sortOrder = $_GET['order'] ?? 'ASC';

// Build query
$query = "SELECT i.*, c.name as category_name 
          FROM items i 
          LEFT JOIN categories c ON i.category_id = c.id 
          WHERE 1=1";
$params = [];

if ($categoryFilter !== 'all') {
    $query .= " AND i.category_id = ?";
    $params[] = $categoryFilter;
}

// Add sorting
$allowedSorts = ['name' => 'i.name', 'barcode' => 'i.barcode', 'category_name' => 'c.name'];
$sortColumn = isset($allowedSorts[$sortBy]) ? $allowedSorts[$sortBy] : 'i.name';
$sortDirection = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
$query .= " ORDER BY $sortColumn $sortDirection";

$items = $db->fetchAll($query, $params);
$categories = getAllCategories();
$totalItems = count($items);

// Check if we're in print mode
$printMode = isset($_GET['print']) && $_GET['print'] === '1';
?>

<?php if (!$printMode): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Master Barcode List</h3>
                <div class="ms-auto">
                    <button onclick="generateAllBarcodes()" class="btn btn-success me-2" id="generateBtn">
                        <i class="ti ti-download"></i> Generate All Barcodes
                    </button>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="ti ti-printer"></i> Print All Barcodes
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category" onchange="this.form.submit()">
                            <option value="all" <?php echo $categoryFilter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Sort By</label>
                        <select class="form-select" name="sort" onchange="this.form.submit()">
                            <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Item Name</option>
                            <option value="barcode" <?php echo $sortBy === 'barcode' ? 'selected' : ''; ?>>Barcode</option>
                            <option value="category_name" <?php echo $sortBy === 'category_name' ? 'selected' : ''; ?>>Category</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Order</label>
                        <select class="form-select" name="order" onchange="this.form.submit()">
                            <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                            <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <a href="master_barcode_list.php" class="btn btn-secondary w-100">Clear Filters</a>
                    </div>
                </form>
                
                <div class="alert alert-info mt-3 mb-0">
                    <i class="ti ti-info-circle"></i>
                    <strong><?php echo $totalItems; ?></strong> item(s) will be printed. Each item will have one barcode label.
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
    @media print {
        body * { visibility: hidden; }
        .barcode-grid, .barcode-grid * { visibility: visible; }
        .barcode-grid { position: absolute; left: 0; top: 0; }
        .barcode-label { page-break-inside: avoid; }
        .no-print { display: none !important; }
    }
    
    .barcode-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.25in;
        padding: 0.5in;
    }
    
    .barcode-label {
        width: 4in;
        height: 2in;
        border: 1px solid #ddd;
        padding: 0.25in;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }
    
    .barcode-image {
        max-width: 100%;
        height: auto;
        margin: 0.1in 0;
    }
    
    .barcode-text {
        font-size: 12pt;
        font-weight: bold;
        margin-bottom: 0.1in;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
    }
    
    .barcode-code {
        font-family: monospace;
        font-size: 10pt;
    }
    
    .barcode-category {
        font-size: 9pt;
        color: #666;
        margin-top: 0.05in;
    }
</style>

<?php if (!$printMode): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Preview (<?php echo $totalItems; ?> items)</h3>
            </div>
            <div class="card-body">
<?php endif; ?>

                <?php if (empty($items)): ?>
                <div class="text-center text-muted py-5">
                    <i class="ti ti-barcode-off icon mb-3" style="font-size: 3rem;"></i>
                    <p>No items found with the selected filters.</p>
                </div>
                <?php else: ?>
                <div class="barcode-grid">
                    <?php 
                    // Use cached barcodes or generate them
                    foreach ($items as $item): 
                        // Try to get cached barcode URL first
                        $barcodeUrl = getBarcodeImageUrl($item['barcode']);
                        
                        if ($barcodeUrl) {
                            // Use cached file
                            $barcodeData = $barcodeUrl;
                        } else {
                            // Generate and cache if not exists
                            $imageData = getOrGenerateBarcodeImage($item['barcode'], true);
                            $barcodeData = 'data:image/png;base64,' . base64_encode($imageData);
                        }
                    ?>
                        <div class="barcode-label">
                            <div class="barcode-text" title="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </div>
                            <img src="<?php echo $barcodeData; ?>" alt="Barcode" class="barcode-image">
                            <div class="barcode-code"><?php echo htmlspecialchars($item['barcode']); ?></div>
                            <?php if ($item['category_name']): ?>
                            <div class="barcode-category"><?php echo htmlspecialchars($item['category_name']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

<?php if (!$printMode): ?>
            </div>
        </div>
    </div>
</div>

<script>
function generateAllBarcodes() {
    const btn = document.getElementById('generateBtn');
    const originalText = btn.innerHTML;
    
    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
    
    // Make AJAX request
    fetch('generate_barcodes.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Success! Generated ${data.generated} barcodes out of ${data.total} items.${data.failed > 0 ? '\nFailed: ' + data.failed : ''}`);
            // Reload page to show cached barcodes
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to generate barcodes'));
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    })
    .finally(() => {
        // Re-enable button
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
