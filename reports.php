<?php
$pageTitle = 'Reports';
require_once 'includes/header.php';

$reportType = $_GET['type'] ?? 'inventory';
$items = getAllItems();
$shows = getAllShows();
$spaces = getAllTheatreSpaces();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="btn-group" role="group">
            <a href="?type=inventory" class="btn btn-<?php echo $reportType === 'inventory' ? 'primary' : 'outline-primary'; ?>">Inventory</a>
            <a href="?type=by_show" class="btn btn-<?php echo $reportType === 'by_show' ? 'primary' : 'outline-primary'; ?>">By Show</a>
            <a href="?type=by_space" class="btn btn-<?php echo $reportType === 'by_space' ? 'primary' : 'outline-primary'; ?>">By Space</a>
        </div>
    </div>
</div>

<?php if ($reportType === 'inventory'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Inventory Report</h3>
            <div class="card-actions">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="ti ti-printer"></i> Print
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Barcode</th>
                        <th>In Stock</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                            <td><code><?php echo htmlspecialchars($item['barcode']); ?></code></td>
                            <td><?php echo $item['in_stock_quantity']; ?></td>
                            <td><?php echo $item['total_quantity']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($reportType === 'by_show'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Items by Show</h3>
        </div>
        <div class="card-body">
            <?php foreach ($shows as $show): 
                $allocations = getDB()->fetchAll(
                    "SELECT i.name, ia.quantity, ia.status 
                     FROM item_allocations ia 
                     JOIN items i ON ia.item_id = i.id 
                     WHERE ia.show_id = ?",
                    [$show['id']]
                );
                if (empty($allocations)) continue;
                $showId = 'show-' . $show['id'];
            ?>
                <div class="mb-3">
                    <h4 class="d-flex align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#<?php echo $showId; ?>" aria-expanded="true">
                        <i class="ti ti-chevron-down me-2 collapse-icon"></i>
                        <?php echo htmlspecialchars($show['name']); ?>
                    </h4>
                    <div class="collapse show" id="<?php echo $showId; ?>">
                        <table class="table table-sm mb-4">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allocations as $alloc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($alloc['name']); ?></td>
                                        <td><?php echo $alloc['quantity']; ?></td>
                                        <td><span class="badge"><?php echo ucfirst($alloc['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php elseif ($reportType === 'by_space'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Items by Theatre Space</h3>
        </div>
        <div class="card-body">
            <?php foreach ($spaces as $space): 
                $allocations = getDB()->fetchAll(
                    "SELECT i.name, ia.quantity, s.name as show_name 
                     FROM item_allocations ia 
                     JOIN items i ON ia.item_id = i.id 
                     LEFT JOIN shows s ON ia.show_id = s.id 
                     WHERE ia.theatre_space_id = ? AND ia.status = 'checked_out'",
                    [$space['id']]
                );
                if (empty($allocations)) continue;
                $spaceId = 'space-' . $space['id'];
            ?>
                <div class="mb-3">
                    <h4 class="d-flex align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#<?php echo $spaceId; ?>" aria-expanded="true">
                        <i class="ti ti-chevron-down me-2 collapse-icon"></i>
                        <?php echo htmlspecialchars($space['name']); ?>
                    </h4>
                    <div class="collapse show" id="<?php echo $spaceId; ?>">
                        <table class="table table-sm mb-4">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Show</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allocations as $alloc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($alloc['name']); ?></td>
                                        <td><?php echo $alloc['quantity']; ?></td>
                                        <td><?php echo htmlspecialchars($alloc['show_name'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
