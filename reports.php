<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

// Get filter parameters
$reportType = isset($_GET['type']) ? $_GET['type'] : 'all';
$showId = isset($_GET['show_id']) ? (int)$_GET['show_id'] : 0;
$theatreSpaceId = isset($_GET['theatre_space_id']) ? (int)$_GET['theatre_space_id'] : 0;

// Build query based on report type
$items = [];
$reportTitle = "All Items";

if ($reportType === 'all') {
    $sql = "SELECT i.*, c.name as category_name 
            FROM items i 
            LEFT JOIN categories c ON i.category_id = c.id 
            ORDER BY i.name";
    $items = $db->query($sql);
    $reportTitle = "All Inventory Items";
    
} elseif ($reportType === 'show' && $showId > 0) {
    // Get show name
    $stmt = $db->prepare("SELECT name FROM shows WHERE id = ?");
    $stmt->bind_param("i", $showId);
    $stmt->execute();
    $show = $stmt->get_result()->fetch_assoc();
    
    if ($show) {
        $reportTitle = "Items for Show: " . $show['name'];
        
        // Get items from pull sheets and change orders for this show
        $sql = "SELECT DISTINCT i.*, c.name as category_name,
                SUM(COALESCE(psi.quantity_needed, 0) + COALESCE(coi.quantity, 0)) as quantity_used
                FROM items i
                LEFT JOIN categories c ON i.category_id = c.id
                LEFT JOIN pull_sheet_items psi ON i.id = psi.item_id
                LEFT JOIN pull_sheets ps ON psi.pull_sheet_id = ps.id AND ps.show_id = ?
                LEFT JOIN change_order_items coi ON i.id = coi.item_id AND coi.action_type = 'add'
                LEFT JOIN change_orders co ON coi.change_order_id = co.id AND co.show_id = ?
                WHERE ps.show_id = ? OR co.show_id = ?
                GROUP BY i.id
                ORDER BY i.name";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("iiii", $showId, $showId, $showId, $showId);
        $stmt->execute();
        $items = $stmt->get_result();
    }
    
} elseif ($reportType === 'theatre_space' && $theatreSpaceId > 0) {
    // Get theatre space name
    $stmt = $db->prepare("SELECT name FROM theatre_spaces WHERE id = ?");
    $stmt->bind_param("i", $theatreSpaceId);
    $stmt->execute();
    $space = $stmt->get_result()->fetch_assoc();
    
    if ($space) {
        $reportTitle = "Items in Theatre Space: " . $space['name'];
        
        // Get items currently in this theatre space
        $sql = "SELECT DISTINCT i.*, c.name as category_name, il.quantity as quantity_in_space
                FROM items i
                LEFT JOIN categories c ON i.category_id = c.id
                INNER JOIN item_locations il ON i.id = il.item_id
                WHERE il.theatre_space_id = ? AND il.location_type = 'theatre'
                ORDER BY i.name";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $theatreSpaceId);
        $stmt->execute();
        $items = $stmt->get_result();
    }
}

// Get shows for dropdown
$shows = $db->query("SELECT id, name FROM shows ORDER BY name");

// Get theatre spaces for dropdown
$theatreSpaces = $db->query("SELECT id, name FROM theatre_spaces ORDER BY name");

$pageTitle = "Reports - " . APP_NAME;
$pageHeader = "Reports";

ob_start();
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Report Type</label>
                        <select name="type" class="form-select" onchange="updateFilters(this.value)">
                            <option value="all" <?php echo $reportType === 'all' ? 'selected' : ''; ?>>
                                All Items
                            </option>
                            <option value="show" <?php echo $reportType === 'show' ? 'selected' : ''; ?>>
                                By Show
                            </option>
                            <option value="theatre_space" <?php echo $reportType === 'theatre_space' ? 'selected' : ''; ?>>
                                By Theatre Space
                            </option>
                        </select>
                    </div>
                    
                    <div class="col-md-4" id="show-filter" style="<?php echo $reportType === 'show' ? '' : 'display:none;'; ?>">
                        <label class="form-label">Show</label>
                        <select name="show_id" class="form-select">
                            <option value="">Select show</option>
                            <?php while ($show = $shows->fetch_assoc()): ?>
                            <option value="<?php echo $show['id']; ?>" 
                                <?php echo $showId == $show['id'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($show['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4" id="space-filter" style="<?php echo $reportType === 'theatre_space' ? '' : 'display:none;'; ?>">
                        <label class="form-label">Theatre Space</label>
                        <select name="theatre_space_id" class="form-select">
                            <option value="">Select theatre space</option>
                            <?php while ($space = $theatreSpaces->fetch_assoc()): ?>
                            <option value="<?php echo $space['id']; ?>" 
                                <?php echo $theatreSpaceId == $space['id'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($space['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="ti ti-filter"></i> Generate Report
                        </button>
                        <?php if ($items && (is_object($items) ? $items->num_rows : count($items)) > 0): ?>
                        <a href="pdf_generator.php?type=report&report_type=<?php echo $reportType; ?>&show_id=<?php echo $showId; ?>&theatre_space_id=<?php echo $theatreSpaceId; ?>" 
                           class="btn btn-success" target="_blank">
                            <i class="ti ti-download"></i> Download PDF
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?php echo $reportTitle; ?></h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Barcode</th>
                    <th>Category</th>
                    <th>Total Qty</th>
                    <th>Available</th>
                    <?php if ($reportType === 'show'): ?>
                    <th>Qty Used</th>
                    <?php elseif ($reportType === 'theatre_space'): ?>
                    <th>Qty in Space</th>
                    <?php endif; ?>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items && (is_object($items) ? $items->num_rows : count($items)) > 0): ?>
                    <?php while ($item = is_object($items) ? $items->fetch_assoc() : array_shift($items)): ?>
                    <tr>
                        <td><?php echo sanitize($item['name']); ?></td>
                        <td><span class="font-monospace"><?php echo sanitize($item['barcode']); ?></span></td>
                        <td><?php echo $item['category_name'] ? sanitize($item['category_name']) : '-'; ?></td>
                        <td><?php echo $item['total_quantity']; ?></td>
                        <td><?php echo $item['available_quantity']; ?></td>
                        <?php if ($reportType === 'show'): ?>
                        <td><?php echo isset($item['quantity_used']) ? $item['quantity_used'] : 0; ?></td>
                        <?php elseif ($reportType === 'theatre_space'): ?>
                        <td><?php echo isset($item['quantity_in_space']) ? $item['quantity_in_space'] : 0; ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if ($item['available_quantity'] === 0): ?>
                            <span class="badge bg-danger">Out of Stock</span>
                            <?php elseif ($item['available_quantity'] < 5): ?>
                            <span class="badge bg-warning">Low Stock</span>
                            <?php else: ?>
                            <span class="badge bg-success">In Stock</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No items found for this report
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updateFilters(type) {
    document.getElementById('show-filter').style.display = type === 'show' ? 'block' : 'none';
    document.getElementById('space-filter').style.display = type === 'theatre_space' ? 'block' : 'none';
}
</script>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
