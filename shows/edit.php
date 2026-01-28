<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
requireAuth();
requirePermission('admin'); // Only admins can edit shows

$pageName = 'Edit Show';
$errors = [];
$showId = intval($_GET['id'] ?? 0);

if (!$showId) {
    $_SESSION['error_message'] = 'Show ID is required.';
    header('Location: index.php');
    exit;
}

// Get show details
$show = getShowById($showId);

if (!$show) {
    $_SESSION['error_message'] = 'Show not found.';
    header('Location: index.php');
    exit;
}

// Get theatre spaces and users for dropdowns
$theatreSpaces = getAllTheatreSpaces();
$designers = getUsersByRole('designer');
$productionAudioUsers = getUsersByRole('production_audio');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $theatreSpaceId = !empty($_POST['theatre_space_id']) ? intval($_POST['theatre_space_id']) : null;
    $designerId = !empty($_POST['designer_id']) ? intval($_POST['designer_id']) : null;
    $productionAudioId = !empty($_POST['production_audio_id']) ? intval($_POST['production_audio_id']) : null;
    $color = trim($_POST['color'] ?? '#3b82f6');
    $archived = isset($_POST['archived']) ? 1 : 0;
    
    // Validate
    if (empty($name)) {
        $errors[] = 'Show name is required.';
    }
    
    // Validate color format
    if (!preg_match('/^#[0-9A-F]{6}$/i', $color)) {
        $color = '#3b82f6'; // Default to blue if invalid
    }
    
    if (empty($errors)) {
        // Update show
        $query = "UPDATE shows 
                  SET name = ?, theatre_space_id = ?, designer_id = ?, production_audio_id = ?, color = ?, archived = ? 
                  WHERE id = ?";
        
        $result = executeQuery($query, [$name, $theatreSpaceId, $designerId, $productionAudioId, $color, $archived, $showId]);
        
        if ($result) {
            $_SESSION['success_message'] = 'Show "' . $name . '" updated successfully!';
            header('Location: view.php?id=' . $showId);
            exit;
        } else {
            $errors[] = 'Failed to update show. Please try again.';
        }
    }
    
    // Update show data with POST data for form
    $show = array_merge($show, $_POST);
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Shows</a></li>
                            <li class="breadcrumb-item"><a href="view.php?id=<?php echo $showId; ?>"><?php echo htmlspecialchars($show['name']); ?></a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                </div>
                <h2 class="page-title">Edit Show</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="ti ti-alert-circle icon"></i>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <form method="POST" class="card">
                    <div class="card-header">
                        <h3 class="card-title">Show Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required">Show Name</label>
                            <input type="text" class="form-control" name="name" 
                                   value="<?php echo htmlspecialchars($show['name'] ?? ''); ?>" 
                                   required autofocus>
                            <small class="form-hint">Enter the name of the production.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Theatre Space</label>
                            <select class="form-select" name="theatre_space_id">
                                <option value="">-- Select Theatre Space --</option>
                                <?php foreach ($theatreSpaces as $space): ?>
                                <option value="<?php echo $space['id']; ?>" 
                                        <?php echo ($show['theatre_space_id'] == $space['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($space['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-hint">Select the performance venue.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Designer</label>
                            <select class="form-select" name="designer_id">
                                <option value="">-- Select Designer --</option>
                                <?php foreach ($designers as $user): ?>
                                <option value="<?php echo $user['id']; ?>"
                                        <?php echo ($show['designer_id'] == $user['id']) ? 'selected' : ''; ?>>
                                    <?php 
                                    if ($user['first_name'] && $user['last_name']) {
                                        echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
                                    } else {
                                        echo htmlspecialchars($user['username']);
                                    }
                                    ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-hint">Assign the sound designer.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Production Audio</label>
                            <select class="form-select" name="production_audio_id">
                                <option value="">-- Select Production Audio --</option>
                                <?php foreach ($productionAudioUsers as $user): ?>
                                <option value="<?php echo $user['id']; ?>"
                                        <?php echo ($show['production_audio_id'] == $user['id']) ? 'selected' : ''; ?>>
                                    <?php 
                                    if ($user['first_name'] && $user['last_name']) {
                                        echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
                                    } else {
                                        echo htmlspecialchars($user['username']);
                                    }
                                    ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-hint">Assign the production audio engineer.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Calendar Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" name="color" 
                                       value="<?php echo htmlspecialchars($show['color'] ?? '#3b82f6'); ?>" 
                                       id="color-picker">
                                <input type="text" class="form-control" id="color-text" 
                                       value="<?php echo htmlspecialchars($show['color'] ?? '#3b82f6'); ?>" 
                                       pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                            <small class="form-hint">Choose a color to identify this show on the calendar.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="archived" 
                                       <?php echo $show['archived'] ? 'checked' : ''; ?>>
                                <span class="form-check-label">Archived</span>
                            </label>
                            <small class="form-hint">Archived shows are hidden from the active list.</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="btn-list">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check icon"></i>
                                Update Show
                            </button>
                            <a href="view.php?id=<?php echo $showId; ?>" class="btn btn-link">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Show Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Created:</strong><br>
                            <?php echo date('M d, Y g:i A', strtotime($show['created_at'])); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Last Updated:</strong><br>
                            <?php echo date('M d, Y g:i A', strtotime($show['updated_at'])); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Status:</strong><br>
                            <?php if ($show['archived']): ?>
                                <span class="badge bg-secondary">Archived</span>
                            <?php else: ?>
                                <span class="badge bg-success">Active</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Sync color picker and text input
document.getElementById('color-picker').addEventListener('input', function() {
    document.getElementById('color-text').value = this.value;
});
document.getElementById('color-text').addEventListener('input', function() {
    if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
        document.getElementById('color-picker').value = this.value;
    }
});
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
