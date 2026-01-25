/**
 * Settings Module
 * Handles application settings including theatre and category management
 */

(function() {
'use strict';

let currentTheatres = [];
let currentCategories = [];

// Render settings page
async function renderSettingsPage() {
  currentTheatres = await window.api.theatres.getAll();
  currentCategories = await window.api.categories.getAll();
  
  return `
    <div class="row mb-3">
      <div class="col-12">
        <p class="text-muted">Manage application settings, theatres, and categories</p>
      </div>
    </div>
    
    <div class="row row-deck row-cards">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Theatre Management</h3>
            <div class="card-actions">
              <button class="btn btn-primary btn-sm" id="addTheatreBtn">
                <i class="ti ti-plus icon"></i> Add Theatre
              </button>
            </div>
          </div>
          <div class="card-body">
            ${renderTheatreList(currentTheatres)}
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Category Management</h3>
            <div class="card-actions">
              <button class="btn btn-primary btn-sm" id="addCategoryBtn">
                <i class="ti ti-plus icon"></i> Add Category
              </button>
            </div>
          </div>
          <div class="card-body">
            ${renderCategoryList(currentCategories)}
          </div>
        </div>
      </div>
      
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Logo & Branding</h3>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Company Logo</label>
                <input type="file" class="form-control" id="logoUpload" accept="image/*">
                <small class="form-hint">Upload a logo to include in PDF reports and labels</small>
              </div>
              <div class="col-md-6">
                <label class="form-label">Preview</label>
                <div id="logoPreview" class="border rounded p-3 text-center" style="min-height: 100px;">
                  <span class="text-muted">No logo uploaded</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Application Information</h3>
          </div>
          <div class="card-body">
            <div id="appInfo">Loading...</div>
          </div>
        </div>
      </div>
      
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Database Management</h3>
          </div>
          <div class="card-body">
            <div class="row g-2">
              <div class="col-md-4">
                <button class="btn btn-primary w-100" onclick="createBackup()">
                  <i class="ti ti-database icon"></i> Create Backup
                </button>
                <small class="text-muted d-block mt-1">Backup database to file</small>
              </div>
              <div class="col-md-4">
                <button class="btn btn-info w-100" onclick="exportDatabase()">
                  <i class="ti ti-download icon"></i> Export Database
                </button>
                <small class="text-muted d-block mt-1">Export as SQL file</small>
              </div>
              <div class="col-md-4">
                <button class="btn btn-danger w-100" onclick="clearAllData()">
                  <i class="ti ti-trash icon"></i> Clear All Data
                </button>
                <small class="text-muted d-block mt-1">Delete all records (careful!)</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Add/Edit Theatre Modal -->
    <div class="modal modal-blur fade" id="theatreModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="theatreModalTitle">Add Theatre</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="theatreForm">
              <input type="hidden" id="theatreId">
              <div class="mb-3">
                <label class="form-label required">Theatre Name</label>
                <input type="text" class="form-control" id="theatreName" required>
                <small class="form-hint">e.g., Main Stage, Black Box, Studio Theatre</small>
              </div>
              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" id="theatreDescription" rows="3"></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveTheatreBtn">Save Theatre</button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Add/Edit Category Modal -->
    <div class="modal modal-blur fade" id="categoryModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="categoryModalTitle">Add Category</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="categoryForm">
              <input type="hidden" id="categoryId">
              <div class="mb-3">
                <label class="form-label required">Category Name</label>
                <input type="text" class="form-control" id="categoryName" required>
                <small class="form-hint">e.g., Microphones, Wireless Systems, Cables</small>
              </div>
              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" id="categoryDescription" rows="3"></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveCategoryBtn">Save Category</button>
          </div>
        </div>
      </div>
    </div>
  `;
}

// Render category list
function renderCategoryList(categories) {
  if (categories.length === 0) {
    return `
      <div class="empty-state">
        <i class="ti ti-tag empty-state-icon"></i>
        <h3>No Categories Defined</h3>
        <p>Add your first category to organize inventory items.</p>
        <button class="btn btn-primary" onclick="document.getElementById('addCategoryBtn').click()">
          <i class="ti ti-plus icon"></i> Add First Category
        </button>
      </div>
    `;
  }
  
  return `
    <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
      ${categories.map(category => `
        <div class="list-group-item">
          <div class="row align-items-center">
            <div class="col">
              <div class="text-truncate">
                <strong>${category.name}</strong>
              </div>
              ${category.description ? `<div class="text-muted text-truncate small">${category.description}</div>` : ''}
            </div>
            <div class="col-auto">
              <button class="btn btn-sm btn-ghost-secondary" onclick="editCategory(${category.id})">
                <i class="ti ti-edit icon"></i>
              </button>
              <button class="btn btn-sm btn-ghost-danger" onclick="deleteCategory(${category.id})">
                <i class="ti ti-trash icon"></i>
              </button>
            </div>
          </div>
        </div>
      `).join('')}
    </div>
  `;
}

// Render theatre list
function renderTheatreList(theatres) {
  if (theatres.length === 0) {
    return `
      <div class="empty-state">
        <i class="ti ti-building empty-state-icon"></i>
        <h3>No Theatres Defined</h3>
        <p>Add your first theatre or performance space to get started.</p>
        <button class="btn btn-primary" onclick="document.getElementById('addTheatreBtn').click()">
          <i class="ti ti-plus icon"></i> Add First Theatre
        </button>
      </div>
    `;
  }
  
  return `
    <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
      ${theatres.map(theatre => `
        <div class="list-group-item">
          <div class="row align-items-center">
            <div class="col">
              <div class="text-truncate">
                <strong>${theatre.name}</strong>
              </div>
              ${theatre.description ? `<div class="text-muted text-truncate">${theatre.description}</div>` : ''}
              <div class="text-muted small">Created: ${new Date(theatre.created_at).toLocaleDateString()}</div>
            </div>
            <div class="col-auto">
              <button class="btn btn-sm btn-ghost-secondary" onclick="editTheatre(${theatre.id})">
                <i class="ti ti-edit icon"></i> Edit
              </button>
              <button class="btn btn-sm btn-ghost-danger" onclick="deleteTheatre(${theatre.id})">
                <i class="ti ti-trash icon"></i> Delete
              </button>
            </div>
          </div>
        </div>
      `).join('')}
    </div>
  `;
}

// Initialize settings page
async function initSettingsPage() {
  // Load app info
  const version = await window.api.update.getVersion();
  const appInfo = document.getElementById('appInfo');
  if (appInfo) {
    appInfo.innerHTML = `
      <div class="row">
        <div class="col-md-6 mb-2">
          <strong>Application:</strong> ${version.name}
        </div>
        <div class="col-md-6 mb-2">
          <strong>Version:</strong> ${version.current}
        </div>
        <div class="col-12 mt-3">
          <button class="btn btn-outline-primary" onclick="checkForUpdates()">
            <i class="ti ti-refresh icon"></i> Check for Updates
          </button>
        </div>
      </div>
    `;
  }
  
  // Add theatre button
  const addBtn = document.getElementById('addTheatreBtn');
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      openTheatreModal();
    });
  }
  
  // Save theatre button
  const saveBtn = document.getElementById('saveTheatreBtn');
  if (saveBtn) {
    saveBtn.addEventListener('click', async () => {
      await saveTheatre();
    });
  }
  
  // Add category button
  const addCatBtn = document.getElementById('addCategoryBtn');
  if (addCatBtn) {
    addCatBtn.addEventListener('click', () => {
      openCategoryModal();
    });
  }
  
  // Save category button
  const saveCatBtn = document.getElementById('saveCategoryBtn');
  if (saveCatBtn) {
    saveCatBtn.addEventListener('click', async () => {
      await saveCategory();
    });
  }
  
  // Logo upload handler
  const logoUpload = document.getElementById('logoUpload');
  if (logoUpload) {
    logoUpload.addEventListener('change', handleLogoUpload);
  }
  
  // Load existing logo
  await loadLogo();
}

// Open theatre modal
function openTheatreModal(theatreId = null) {
  const modal = new bootstrap.Modal(document.getElementById('theatreModal'));
  const modalTitle = document.getElementById('theatreModalTitle');
  const form = document.getElementById('theatreForm');
  
  form.reset();
  document.getElementById('theatreId').value = '';
  
  if (theatreId) {
    // Edit mode
    modalTitle.textContent = 'Edit Theatre';
    const theatre = currentTheatres.find(t => t.id === theatreId);
    if (theatre) {
      document.getElementById('theatreId').value = theatre.id;
      document.getElementById('theatreName').value = theatre.name;
      document.getElementById('theatreDescription').value = theatre.description || '';
    }
  } else {
    // Add mode
    modalTitle.textContent = 'Add Theatre';
  }
  
  modal.show();
}

// Save theatre
async function saveTheatre() {
  const id = document.getElementById('theatreId').value;
  const name = document.getElementById('theatreName').value.trim();
  const description = document.getElementById('theatreDescription').value.trim();
  
  if (!name) {
    alert('Theatre name is required');
    return;
  }
  
  const theatre = { name, description };
  
  try {
    if (id) {
      // Update existing
      await window.api.theatres.update(parseInt(id), theatre);
    } else {
      // Create new
      await window.api.theatres.create(theatre);
    }
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('theatreModal')).hide();
    
    // Reload page
    window.navigation.loadPage('settings');
  } catch (error) {
    console.error('Error saving theatre:', error);
    alert('Failed to save theatre: ' + error.message);
  }
}

// Edit theatre
window.editTheatre = function(id) {
  openTheatreModal(id);
};

// Delete theatre
window.deleteTheatre = async function(id) {
  const theatre = currentTheatres.find(t => t.id === id);
  if (!theatre) return;
  
  const result = await window.api.dialog.showMessage({
    type: 'warning',
    title: 'Delete Theatre',
    message: `Are you sure you want to delete "${theatre.name}"?`,
    detail: 'This action cannot be undone. Shows linked to this theatre will not be deleted.',
    buttons: ['Cancel', 'Delete'],
    defaultId: 0,
    cancelId: 0
  });
  
  if (result.response === 1) {
    try {
      const deleteResult = await window.api.theatres.delete(id);
      if (deleteResult.success) {
        window.navigation.loadPage('settings');
      } else {
        alert('Failed to delete theatre: ' + deleteResult.error);
      }
    } catch (error) {
      console.error('Error deleting theatre:', error);
      alert('Failed to delete theatre: ' + error.message);
    }
  }
};

// ===== CATEGORY MANAGEMENT =====

// Open category modal
function openCategoryModal(categoryId = null) {
  const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
  const modalTitle = document.getElementById('categoryModalTitle');
  const form = document.getElementById('categoryForm');
  
  form.reset();
  document.getElementById('categoryId').value = '';
  
  if (categoryId) {
    // Edit mode
    modalTitle.textContent = 'Edit Category';
    const category = currentCategories.find(c => c.id === categoryId);
    if (category) {
      document.getElementById('categoryId').value = category.id;
      document.getElementById('categoryName').value = category.name;
      document.getElementById('categoryDescription').value = category.description || '';
    }
  } else {
    // Add mode
    modalTitle.textContent = 'Add Category';
  }
  
  modal.show();
}

// Save category
async function saveCategory() {
  const id = document.getElementById('categoryId').value;
  const name = document.getElementById('categoryName').value.trim();
  const description = document.getElementById('categoryDescription').value.trim();
  
  if (!name) {
    alert('Category name is required');
    return;
  }
  
  const category = { name, description };
  
  try {
    if (id) {
      // Update existing
      await window.api.categories.update(parseInt(id), category);
    } else {
      // Create new
      await window.api.categories.create(category);
    }
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
    
    // Reload page
    window.navigation.loadPage('settings');
  } catch (error) {
    console.error('Error saving category:', error);
    alert('Failed to save category: ' + error.message);
  }
}

// Edit category
window.editCategory = function(id) {
  openCategoryModal(id);
};

// Delete category
window.deleteCategory = async function(id) {
  const category = currentCategories.find(c => c.id === id);
  if (!category) return;
  
  const result = await window.api.dialog.showMessage({
    type: 'warning',
    title: 'Delete Category',
    message: `Are you sure you want to delete "${category.name}"?`,
    detail: 'This action cannot be undone. Items using this category will keep the category name.',
    buttons: ['Cancel', 'Delete'],
    defaultId: 0,
    cancelId: 0
  });
  
  if (result.response === 1) {
    try {
      const deleteResult = await window.api.categories.delete(id);
      if (deleteResult.success) {
        window.navigation.loadPage('settings');
      } else {
        alert('Failed to delete category: ' + deleteResult.error);
      }
    } catch (error) {
      console.error('Error deleting category:', error);
      alert('Failed to delete category: ' + error.message);
    }
  }
};

// ===== LOGO MANAGEMENT =====

// Handle logo upload
async function handleLogoUpload(event) {
  const file = event.target.files[0];
  if (!file) return;
  
  // Check file type
  if (!file.type.startsWith('image/')) {
    alert('Please select an image file');
    return;
  }
  
  // Check file size (max 5MB)
  if (file.size > 5 * 1024 * 1024) {
    alert('File size must be less than 5MB');
    return;
  }
  
  // Read file as data URL
  const reader = new FileReader();
  reader.onload = async (e) => {
    const logoData = e.target.result;
    
    // Save to settings
    try {
      await window.api.settings.set('company_logo', logoData);
      
      // Update preview
      displayLogo(logoData);
      
      alert('Logo uploaded successfully!');
    } catch (error) {
      console.error('Error saving logo:', error);
      alert('Failed to save logo: ' + error.message);
    }
  };
  reader.readAsDataURL(file);
}

// Load existing logo
async function loadLogo() {
  try {
    const logoData = await window.api.settings.get('company_logo');
    if (logoData) {
      displayLogo(logoData);
    }
  } catch (error) {
    console.error('Error loading logo:', error);
  }
}

// Display logo in preview
function displayLogo(logoData) {
  const preview = document.getElementById('logoPreview');
  if (preview) {
    preview.innerHTML = `<img src="${logoData}" alt="Company Logo" style="max-width: 100%; max-height: 200px;">`;
  }
}

// Check for updates
window.checkForUpdates = async function() {
  await window.api.update.check();
  alert('Checking for updates...');
};

// Create backup
window.createBackup = async function() {
  try {
    const result = await window.api.backup.create();
    if (result.success) {
      alert(`Backup created successfully!\nFile: ${result.path}`);
    } else {
      alert('Backup failed: ' + result.error);
    }
  } catch (error) {
    alert('Error creating backup: ' + error.message);
  }
};

// Export database
window.exportDatabase = async function() {
  try {
    const result = await window.api.backup.export();
    if (result.success) {
      alert(`Database exported successfully!\nFile: ${result.path}`);
    } else if (!result.canceled) {
      alert('Export failed: ' + result.error);
    }
  } catch (error) {
    alert('Error exporting database: ' + error.message);
  }
};

// Clear all data
window.clearAllData = async function() {
  const result = await window.api.dialog.showMessage({
    type: 'warning',
    title: 'Clear All Data',
    message: 'Are you absolutely sure you want to delete ALL data?',
    detail: 'This will permanently delete:\n• All inventory items\n• All shows\n• All pull sheets\n• All returns\n• All activity logs\n\nThis action CANNOT be undone!',
    buttons: ['Cancel', 'Delete Everything'],
    defaultId: 0,
    cancelId: 0
  });
  
  if (result.response === 1) {
    // Second confirmation
    const confirmed = await window.api.dialog.showMessage({
      type: 'error',
      title: 'Final Confirmation',
      message: 'Type DELETE to confirm permanent data deletion',
      buttons: ['Cancel', 'I Understand - Delete All Data'],
      defaultId: 0
    });
    
    if (confirmed.response === 1) {
      alert('Data clearing would be implemented here. This requires database reset functionality.');
      // In full implementation: await window.api.database.clearAll();
    }
  }
};

// Register page with navigation
window.navigation.registerPage('settings', {
  render: renderSettingsPage,
  init: initSettingsPage
});

})();
