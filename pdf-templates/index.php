<?php
/**
 * PDF Templates - List and Manage Templates
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

session_start();
requireRole('admin'); // Only admins can manage PDF templates

$db = getDB();
$currentUser = getCurrentUser();

// Get all templates
$templates = $db->fetchAll("
    SELECT 
        t.*,
        u.full_name as created_by_name,
        COUNT(DISTINCT a.id) as assignment_count
    FROM pdf_templates t
    LEFT JOIN users u ON t.created_by = u.id
    LEFT JOIN pdf_template_assignments a ON t.id = a.template_id
    GROUP BY t.id
    ORDER BY t.updated_at DESC
");

$pageTitle = "PDF Templates";
require_once '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="ti ti-file-type-pdf me-2"></i>PDF Templates</h1>
        <div>
            <a href="assignments.php" class="btn btn-outline-primary me-2">
                <i class="ti ti-link"></i> Template Assignments
            </a>
            <a href="create.php" class="btn btn-primary">
                <i class="ti ti-plus"></i> Create New Template
            </a>
        </div>
    </div>

    <?php if (empty($templates)): ?>
        <div class="alert alert-info">
            <i class="ti ti-info-circle me-2"></i>
            No templates created yet. Create your first PDF template to get started.
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($templates as $template): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 <?= $template['is_active'] ? '' : 'border-secondary' ?>">
                        <?php if ($template['preview_image']): ?>
                            <img src="/<?= htmlspecialchars($template['preview_image']) ?>" class="card-img-top" alt="Template Preview" style="max-height: 200px; object-fit: contain;">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="ti ti-file-type-pdf" style="font-size: 72px; opacity: 0.3;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($template['name']) ?></h5>
                                <?php if (!$template['is_active']): ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($template['description']): ?>
                                <p class="card-text text-muted small"><?= htmlspecialchars($template['description']) ?></p>
                            <?php endif; ?>
                            
                            <div class="small text-muted mb-3">
                                <div><i class="ti ti-user me-1"></i><?= htmlspecialchars($template['created_by_name'] ?? 'Unknown') ?></div>
                                <div><i class="ti ti-clock me-1"></i><?= timeAgo($template['updated_at']) ?></div>
                                <div><i class="ti ti-link me-1"></i><?= $template['assignment_count'] ?> assignment(s)</div>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100" role="group">
                                <a href="editor.php?id=<?= $template['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="ti ti-edit"></i> Edit
                                </a>
                                <a href="preview.php?id=<?= $template['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="ti ti-eye"></i> Preview
                                </a>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteTemplate(<?= $template['id'] ?>, '<?= htmlspecialchars($template['name'], ENT_QUOTES) ?>')">
                                    <i class="ti ti-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function deleteTemplate(id, name) {
    if (!confirm(`Are you sure you want to delete the template "${name}"?\n\nThis will also remove all assignments for this template.`)) {
        return;
    }
    
    fetch('/pdf-templates/delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error deleting template: ' + error);
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
