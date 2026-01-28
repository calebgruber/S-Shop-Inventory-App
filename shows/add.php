<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
requireAuth();
requirePermission('admin'); // Only admins can add shows

$pageName = 'Add Show';
$errors = [];
$formData = [];

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
    
    // Validate
    if (empty($name)) {
        $errors[] = 'Show name is required.';
    }
    
    // Validate color format
    if (!preg_match('/^#[0-9A-F]{6}$/i', $color)) {
        $color = '#3b82f6'; // Default to blue if invalid
    }
    
    if (empty($errors)) {
        // Insert show
        $query = "INSERT INTO shows (name, theatre_space_id, designer_id, production_audio_id, color, archived) 
                  VALUES (?, ?, ?, ?, ?, 0)";
        
        $result = executeQuery($query, [$name, $theatreSpaceId, $designerId, $productionAudioId, $color]);
        
        if ($result) {
            $_SESSION['success_message'] = 'Show "' . $name . '" created successfully!';
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Failed to create show. Please try again.';
        }
    }
    
    // Store form data for repopulating
    $formData = $_POST;
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
                            <li class="breadcrumb-item active">Add Show</li>
                        </ol>
                    </nav>
                </div>
                <h2 class="page-title">Add Show</h2>
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
                                   value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>" 
                                   required autofocus>
                            <small class="form-hint">Enter the name of the production.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Theatre Space</label>
                            <select class="form-select" name="theatre_space_id">
                                <option value="">-- Select Theatre Space --</option>
                                <?php foreach ($theatreSpaces as $space): ?>
                                <option value="<?php echo $space['id']; ?>" 
                                        <?php echo (isset($formData['theatre_space_id']) && $formData['theatre_space_id'] == $space['id']) ? 'selected' : ''; ?>>
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
                                        <?php echo (isset($formData['designer_id']) && $formData['designer_id'] == $user['id']) ? 'selected' : ''; ?>>
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
                                        <?php echo (isset($formData['production_audio_id']) && $formData['production_audio_id'] == $user['id']) ? 'selected' : ''; ?>>
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
                                       value="<?php echo htmlspecialchars($formData['color'] ?? '#3b82f6'); ?>" 
                                       id="color-picker">
                                <input type="text" class="form-control" id="color-text" 
                                       value="<?php echo htmlspecialchars($formData['color'] ?? '#3b82f6'); ?>" 
                                       pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                            <small class="form-hint">Choose a color to identify this show on the calendar.</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="btn-list">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check icon"></i>
                                Create Show
                            </button>
                            <a href="index.php" class="btn btn-link">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Help</h3>
                    </div>
                    <div class="card-body">
                        <h4>Creating a Show</h4>
                        <p class="text-muted">Shows represent productions that require equipment from the shop.</p>
                        
                        <h4 class="mt-3">Assignments</h4>
                        <p class="text-muted">Assign a designer and production audio engineer to the show. They will be able to create pull sheets and change orders for this production.</p>
                        
                        <h4 class="mt-3">Calendar Color</h4>
                        <p class="text-muted">Choose a unique color for easy identification on the production calendar.</p>
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
