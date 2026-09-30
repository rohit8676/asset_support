<?php
/**
 * Asset & Stock Management Module Page
 * IT Asset & Support Management System
 */

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Asset & Stock Mgt';

$db = Database::getConnection();

// Fetch categories from database (2 columns: category_id, category_name)
$categoriesList = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();
$totalCategories = count($categoriesList);

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Page Header & Action Bar with 3 Right-Side Buttons -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom gap-3">
    <div>
        <h3 class="fw-bold mb-1">Asset &amp; Stock Management</h3>
        <p class="text-muted mb-0">Manage hardware inventory, import bulk assets, and manage category classifications</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <!-- Button 1: Import Asset -->
        <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2 px-3 py-2 bg-white" data-bs-toggle="modal" data-bs-target="#modalImportAsset" id="btnImportAsset">
            <i class="bi bi-file-earmark-arrow-up text-primary"></i>
            <span>Import Asset</span>
        </button>

        <!-- Button 2: Add Asset -->
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalAddAsset" id="btnAddAsset">
            <i class="bi bi-plus-lg"></i>
            <span>Add Asset</span>
        </button>

        <!-- Button 3: Categories (Opens Right-Side Slider) -->
        <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2 px-3 py-2 bg-white" data-bs-toggle="offcanvas" data-bs-target="#sliderCategories" aria-controls="sliderCategories" id="btnOpenCategoriesSlider">
            <i class="bi bi-tags-fill"></i>
            <span>Categories</span>
            <span class="badge bg-primary text-white ms-1" id="badgeCategoriesCountNav"><?= $totalCategories ?></span>
        </button>
    </div>
</div>

<!-- 2. Stock Summary / KPI Cards (Filtered in Real-Time Based on Active Filters) -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card kpi-filter-card active d-flex align-items-center gap-3 p-3 bg-white border rounded-3 shadow-sm" data-status="" style="cursor: pointer; transition: all 0.2s ease;">
            <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-3">
                <i class="bi bi-laptop"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Total Assets</div>
                <div class="fs-4 fw-bold text-dark" id="statTotalAssets">0</div>
                <div class="small text-muted">In Current Filter</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card kpi-filter-card d-flex align-items-center gap-3 p-3 bg-white border rounded-3 shadow-sm" data-status="In Use" style="cursor: pointer; transition: all 0.2s ease;">
            <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-3">
                <i class="bi bi-person-check"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Assigned / In Use</div>
                <div class="fs-4 fw-bold text-dark" id="statInUseAssets">0</div>
                <div class="small text-muted">With Employees</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card kpi-filter-card d-flex align-items-center gap-3 p-3 bg-white border rounded-3 shadow-sm" data-status="Available" style="cursor: pointer; transition: all 0.2s ease;">
            <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 fs-3">
                <i class="bi bi-box-seam"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold text-uppercase">In Stock / Available</div>
                <div class="fs-4 fw-bold text-dark" id="statInStockAssets">0</div>
                <div class="small text-muted">Ready for Assignment</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card kpi-filter-card d-flex align-items-center gap-3 p-3 bg-white border rounded-3 shadow-sm" data-status="Under Repair" style="cursor: pointer; transition: all 0.2s ease;">
            <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 fs-3">
                <i class="bi bi-tools"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Under Repair</div>
                <div class="fs-4 fw-bold text-dark" id="statRepairAssets">0</div>
                <div class="small text-muted">Maintenance &amp; Support</div>
            </div>
        </div>
    </div>
</div>

<!-- 3. Filter & Search Toolbar (Below KPI Cards) -->
<div class="card p-3 mb-4 bg-white border shadow-sm" style="border-radius: 8px;">
    <div class="row g-3 align-items-center">
        <div class="col-md-6 col-lg-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="assetSearchInput" class="form-control border-start-0" placeholder="Search assets by tag, model, serial...">
            </div>
        </div>
        <div class="col-md-4 col-lg-4">
            <select id="assetCategoryFilter" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categoriesList as $cat): ?>
                    <option value="<?= e($cat['category_name']) ?>"><?= e($cat['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-lg-3 text-md-end">
            <button class="btn btn-outline-secondary btn-sm px-3" id="btnResetAssetFilters">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
            </button>
        </div>
    </div>
</div>

<!-- 4. Asset Inventory Table Card -->
<div class="content-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-hdd-network text-primary me-2"></i> Asset &amp; Stock Inventory Records</span>
        <span class="badge bg-secondary" id="badgeAssetCount">0 Assets</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0" id="tableAssets">
            <thead>
                <tr>
                    <th>Asset ID</th>
                    <th>Asset</th>
                    <th>Category</th>
                    <th>Serial No</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>In Date</th>
                    <th class="text-end" style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody id="assetsTableBody">
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                        <div>Loading asset records...</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 5. RIGHT SIDE SLIDER: ASSET CATEGORIES DRAWER (Offcanvas) -->
<!-- ========================================================================= -->
<div class="offcanvas offcanvas-end shadow-lg" tabindex="-1" id="sliderCategories" aria-labelledby="sliderCategoriesLabel" style="width: 440px;">
    <div class="offcanvas-header bg-light border-bottom py-3">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-primary bg-opacity-10 text-primary rounded">
                <i class="bi bi-tags-fill fs-5"></i>
            </div>
            <div>
                <h5 class="offcanvas-title fw-bold mb-0" id="sliderCategoriesLabel">Asset Categories</h5>
                <small class="text-muted" id="sliderCategoriesSubtitle"><?= $totalCategories ?> categories configured</small>
            </div>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-3 d-flex flex-column gap-3">
        <!-- Add / Edit Category Form Inside Drawer (1 Single Input Field) -->
        <div class="card border p-3 bg-light shadow-sm" style="border-radius: 8px;">
            <form id="formDrawerCategory">
                <input type="hidden" name="action" value="save_category">
                <input type="hidden" name="category_id" id="drawerCatId" value="">
                
                <label class="form-label small fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                    <span id="drawerFormTitle"><i class="bi bi-plus-circle text-primary me-1"></i> Add Category</span>
                    <button type="button" class="btn btn-link btn-sm p-0 text-muted d-none" id="btnCancelDrawerEdit">Cancel Edit</button>
                </label>
                
                <div class="input-group">
                    <input type="text" name="category_name" id="drawerCatName" class="form-control" placeholder="Enter category name..." required autocomplete="off">
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-1 px-3" id="btnSaveDrawerCategory">
                        <span id="drawerCatSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span id="drawerCatBtnText">Save</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Search Categories Filter in Drawer -->
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="text" id="sliderSearchCategory" class="form-control" placeholder="Filter categories list...">
        </div>

        <!-- Categories List (Showing Only Name Simply) -->
        <div class="flex-grow-1 overflow-auto border rounded bg-white">
            <ul class="list-group list-group-flush" id="drawerCategoriesList">
                <?php if (empty($categoriesList)): ?>
                    <li class="list-group-item text-center py-4 text-muted" id="drawerEmptyMsg">
                        <i class="bi bi-tag fs-4 d-block mb-1"></i>
                        No categories found. Type a name above to add.
                    </li>
                <?php else: ?>
                    <?php foreach ($categoriesList as $cat): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center drawer-cat-item py-2 px-3"
                            data-id="<?= e($cat['category_id']) ?>"
                            data-name="<?= strtolower(e($cat['category_name'])) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-folder2 text-secondary"></i>
                                <span class="fw-medium text-dark cat-item-text"><?= e($cat['category_name']) ?></span>
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-drawer-edit"
                                        data-id="<?= e($cat['category_id']) ?>"
                                        data-name="<?= e($cat['category_name']) ?>"
                                        title="Edit Category">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-drawer-delete"
                                        data-id="<?= e($cat['category_id']) ?>"
                                        data-name="<?= e($cat['category_name']) ?>"
                                        title="Delete Category">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <div class="offcanvas-footer p-3 bg-light border-top text-end">
        <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="offcanvas">Close</button>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 6. MODAL: ADD ASSET (Category, Asset, Description [Required], Serial No, Source, In Date) -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalAddAsset" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formAddAsset">
                <input type="hidden" name="action" value="save_asset">
                <input type="hidden" name="is_edit" value="0">

                <div class="modal-header border-bottom py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 bg-primary bg-opacity-10 text-primary rounded">
                            <i class="bi bi-plus-circle-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0">Add New Hardware Asset</h5>
                            <small class="text-muted">Register hardware asset (Status defaults to <strong>Available</strong>)</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Category ID Selection -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="addAssetCategory" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categoriesList as $cat): ?>
                                    <option value="<?= e($cat['category_id']) ?>">
                                        <?= e($cat['category_name']) ?> (<?= e($cat['category_id']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Asset Name / Model -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Asset (Name / Model) <span class="text-danger">*</span></label>
                            <input type="text" name="asset_name" id="addAssetName" class="form-control" placeholder="e.g. Dell Latitude 5420, MacBook Pro M2" required>
                        </div>

                        <!-- Description (Required, Placed Directly Below Category and Asset Name) -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark">Description <span class="text-danger">*</span></label>
                            <textarea name="description" id="addAssetDescription" class="form-control" rows="3" placeholder="Enter hardware specifications, configuration details, or additional notes..." required></textarea>
                        </div>

                        <!-- Serial No -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Serial No</label>
                            <input type="text" name="serial_number" id="addAssetSerial" class="form-control" placeholder="e.g. SN-88392014, C02G90">
                        </div>

                        <!-- Source -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Source</label>
                            <input type="text" name="source" id="addAssetSource" class="form-control" list="sourceListOptions" placeholder="e.g. Direct Purchase, Vendor, Transfer">
                            <datalist id="sourceListOptions">
                                <option value="Direct Purchase">
                                <option value="Vendor Procurement">
                                <option value="Internal Transfer">
                                <option value="Rental / Lease">
                                <option value="Client Provided">
                                <option value="Donation / Grant">
                            </datalist>
                        </div>

                        <!-- In Date -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">In Date <span class="text-danger">*</span></label>
                            <input type="date" name="in_date" id="addAssetInDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2" id="btnAddAssetSubmit">
                        <span id="addAssetSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span id="addAssetBtnText">Save Asset</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 7. MODAL: EDIT ASSET -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalEditAsset" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formEditAsset">
                <input type="hidden" name="action" value="save_asset">
                <input type="hidden" name="is_edit" value="1">
                <input type="hidden" name="asset_id" id="editAssetIdInput" value="">

                <div class="modal-header border-bottom py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 bg-warning bg-opacity-10 text-warning rounded">
                            <i class="bi bi-pencil-square fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0">Edit Asset Details</h5>
                            <small class="text-muted">Update asset attributes and inventory status</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-secondary d-flex align-items-center justify-content-between p-3 mb-4 rounded-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-upc-scan fs-4 text-primary"></i>
                            <div>
                                <div class="small fw-semibold text-uppercase text-secondary">Asset ID (Permanent)</div>
                                <div class="fw-bold font-monospace fs-5 text-dark" id="editAssetIdDisplay">---</div>
                            </div>
                        </div>
                        <span class="badge bg-secondary px-3 py-2">Assigned Key</span>
                    </div>

                    <div class="row g-3">
                        <!-- Category ID Selection -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="editAssetCategory" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categoriesList as $cat): ?>
                                    <option value="<?= e($cat['category_id']) ?>">
                                        <?= e($cat['category_name']) ?> (<?= e($cat['category_id']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Asset Name / Model -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Asset (Name / Model) <span class="text-danger">*</span></label>
                            <input type="text" name="asset_name" id="editAssetName" class="form-control" required>
                        </div>

                        <!-- Serial No -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Serial No</label>
                            <input type="text" name="serial_number" id="editAssetSerial" class="form-control">
                        </div>

                        <!-- Source -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Source</label>
                            <input type="text" name="source" id="editAssetSource" class="form-control" list="sourceListOptions">
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Status <span class="text-danger">*</span></label>
                            <select name="status" id="editAssetStatus" class="form-select" required>
                                <option value="In Stock">In Stock (Available)</option>
                                <option value="In Use">In Use (Assigned)</option>
                                <option value="Under Repair">Under Repair</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Scrapped">Scrapped</option>
                            </select>
                        </div>

                        <!-- In Date -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">In Date <span class="text-danger">*</span></label>
                            <input type="date" name="in_date" id="editAssetInDate" class="form-control" required>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark">Description</label>
                            <textarea name="description" id="editAssetDescription" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2" id="btnEditAssetSubmit">
                        <span id="editAssetSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span id="editAssetBtnText">Update Asset</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 8. MODAL: VIEW ASSET DETAILS -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalViewAsset" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle text-primary me-2"></i> Asset Overview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="viewAssetContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary spinner-border-sm"></div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 bg-light">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 9. MODAL: IMPORT ASSET -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalImportAsset" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i> Import Bulk Assets</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Upload a CSV or Excel file containing asset details to import multiple hardware records at once.</p>
                
                <div class="border border-dashed p-4 text-center rounded bg-light mb-3">
                    <i class="bi bi-cloud-arrow-up display-5 text-primary mb-2"></i>
                    <div class="fw-semibold">Choose a CSV / Excel File</div>
                    <div class="small text-muted mb-2">Supported formats: .csv, .xlsx</div>
                    <input type="file" class="form-control form-control-sm mx-auto" style="max-width: 280px;" accept=".csv, .xlsx">
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Need the template format?</span>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="showToast('Downloading CSV template format...', 'success')">
                        <i class="bi bi-download me-1"></i> Download Template
                    </button>
                </div>
            </div>
            <div class="modal-footer border-top py-2 bg-light">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" onclick="showToast('Bulk import feature initialized.', 'success'); bootstrap.Modal.getInstance(document.getElementById('modalImportAsset')).hide();">
                    Upload &amp; Import
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // -------------------------------------------------------------
    // 1. ASSET LISTING & DYNAMIC RENDERING (REAL-TIME FILTER SYNC)
    // -------------------------------------------------------------
    const assetsTableBody = document.getElementById('assetsTableBody');
    const badgeAssetCount = document.getElementById('badgeAssetCount');
    const statTotalAssets = document.getElementById('statTotalAssets');
    const statInUseAssets = document.getElementById('statInUseAssets');
    const statInStockAssets = document.getElementById('statInStockAssets');
    const statRepairAssets = document.getElementById('statRepairAssets');
    const assetSearchInput = document.getElementById('assetSearchInput');
    const assetCategoryFilter = document.getElementById('assetCategoryFilter');
    const kpiFilterCards = document.querySelectorAll('.kpi-filter-card');

    let currentStatusFilter = '';

    async function loadAssets() {
        const search = assetSearchInput ? assetSearchInput.value.trim() : '';
        const category = assetCategoryFilter ? assetCategoryFilter.value.trim() : '';

        try {
            const res = await fetch('<?= url('api/asset_action.php') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'list_assets', 
                    search: search, 
                    category: category,
                    status: currentStatusFilter
                })
            });
            const data = await res.json();

            if (data.success) {
                // Update Top KPI Cards (Reflects counts based on active search & category filter)
                if (statTotalAssets) statTotalAssets.textContent = data.counts.total;
                if (statInStockAssets) statInStockAssets.textContent = data.counts.in_stock;
                if (statInUseAssets) statInUseAssets.textContent = data.counts.in_use;
                if (statRepairAssets) statRepairAssets.textContent = data.counts.under_repair;
                if (badgeAssetCount) badgeAssetCount.textContent = `${data.counts.filtered} Assets`;

                // Update active state visuals on KPI cards
                kpiFilterCards.forEach(card => {
                    const cardStatus = card.getAttribute('data-status') || '';
                    if (cardStatus === currentStatusFilter) {
                        card.classList.add('border-primary', 'bg-light', 'shadow');
                        card.style.borderWidth = '2px';
                    } else {
                        card.classList.remove('border-primary', 'bg-light', 'shadow');
                        card.style.borderWidth = '1px';
                    }
                });

                // Render Table Rows
                renderAssetsTable(data.assets);
            } else {
                assetsTableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">${escapeHtml(data.message || 'Error loading assets')}</td></tr>`;
            }
        } catch (err) {
            assetsTableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Server connection error while fetching assets.</td></tr>`;
        }
    }

    // KPI Card Click Handler (Filter Table by Status on Click)
    kpiFilterCards.forEach(card => {
        card.addEventListener('click', function() {
            const clickedStatus = this.getAttribute('data-status') || '';
            if (currentStatusFilter === clickedStatus && clickedStatus !== '') {
                // Toggle off to all
                currentStatusFilter = '';
            } else {
                currentStatusFilter = clickedStatus;
            }
            loadAssets();
        });
    });

    function renderAssetsTable(assets) {
        if (!assets || assets.length === 0) {
            assetsTableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        No matching asset records found for the selected filter.
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        assets.forEach(a => {
            let statusBadge = '<span class="badge bg-secondary">Unknown</span>';
            const st = (a.status || '').toLowerCase();
            if (st === 'available' || st === 'in stock') {
                statusBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><i class="bi bi-box-seam me-1"></i>Available</span>';
            } else if (st === 'in use' || st === 'assigned') {
                statusBadge = '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1"><i class="bi bi-person-check me-1"></i>In Use</span>';
            } else if (st === 'under repair') {
                statusBadge = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1"><i class="bi bi-tools me-1"></i>Under Repair</span>';
            } else if (st === 'damaged' || st === 'scrapped') {
                statusBadge = `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1"><i class="bi bi-exclamation-octagon me-1"></i>${escapeHtml(a.status)}</span>`;
            }

            html += `
                <tr>
                    <td><strong class="font-monospace text-primary">${escapeHtml(a.asset_id)}</strong></td>
                    <td>
                        <div class="fw-bold text-dark">${escapeHtml(a.asset_name)}</div>
                        ${a.description ? `<small class="text-muted d-block text-truncate" style="max-width: 200px;">${escapeHtml(a.description)}</small>` : ''}
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">${escapeHtml(a.category_name || a.category_id)}</span>
                    </td>
                    <td>
                        <span class="font-monospace small">${escapeHtml(a.serial_number || '—')}</span>
                    </td>
                    <td>
                        <span class="small text-muted">${escapeHtml(a.source || '—')}</span>
                    </td>
                    <td>${statusBadge}</td>
                    <td>
                        <span class="small text-secondary">${escapeHtml(a.in_date || '—')}</span>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary btn-view-asset" data-id="${escapeHtml(a.asset_id)}" title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-edit-asset" data-id="${escapeHtml(a.asset_id)}" title="Edit Asset">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete-asset" data-id="${escapeHtml(a.asset_id)}" data-name="${escapeHtml(a.asset_name)}" title="Delete Asset">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        assetsTableBody.innerHTML = html;
        attachAssetActionEvents();
    }

    function attachAssetActionEvents() {
        // View Asset Details
        document.querySelectorAll('.btn-view-asset').forEach(btn => {
            btn.onclick = async function() {
                const assetId = this.getAttribute('data-id');
                const modal = new bootstrap.Modal(document.getElementById('modalViewAsset'));
                const viewContainer = document.getElementById('viewAssetContent');
                viewContainer.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div></div>';
                modal.show();

                try {
                    const res = await fetch('<?= url('api/asset_action.php') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'get_asset', asset_id: assetId })
                    });
                    const resJson = await res.json();
                    if (resJson.success) {
                        const a = resJson.asset;
                        viewContainer.innerHTML = `
                            <div class="card bg-light border-0 p-3 mb-3 text-center rounded-3">
                                <span class="small text-muted fw-bold text-uppercase">Asset ID</span>
                                <h4 class="font-monospace text-primary fw-bold mb-0">${escapeHtml(a.asset_id)}</h4>
                            </div>
                            <div class="row g-3 small">
                                <div class="col-6">
                                    <div class="text-muted fw-bold">Asset Name:</div>
                                    <div class="fw-semibold text-dark fs-6">${escapeHtml(a.asset_name)}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted fw-bold">Category:</div>
                                    <div class="fw-semibold text-dark">${escapeHtml(a.category_name)} (${escapeHtml(a.category_id)})</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted fw-bold">Serial Number:</div>
                                    <div class="font-monospace">${escapeHtml(a.serial_number || 'N/A')}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted fw-bold">Source:</div>
                                    <div>${escapeHtml(a.source || 'N/A')}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted fw-bold">Status:</div>
                                    <div><span class="badge bg-primary">${escapeHtml(a.status)}</span></div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted fw-bold">In Date:</div>
                                    <div>${escapeHtml(a.in_date || 'N/A')}</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted fw-bold">Description:</div>
                                    <div class="p-2 border rounded bg-white mt-1 text-secondary" style="min-height: 60px;">${escapeHtml(a.description || 'No description provided.')}</div>
                                </div>
                            </div>
                        `;
                    } else {
                        viewContainer.innerHTML = `<div class="alert alert-danger">${escapeHtml(resJson.message)}</div>`;
                    }
                } catch (e) {
                    viewContainer.innerHTML = `<div class="alert alert-danger">Error fetching asset details.</div>`;
                }
            };
        });

        // Edit Asset Details
        document.querySelectorAll('.btn-edit-asset').forEach(btn => {
            btn.onclick = async function() {
                const assetId = this.getAttribute('data-id');
                try {
                    const res = await fetch('<?= url('api/asset_action.php') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'get_asset', asset_id: assetId })
                    });
                    const resJson = await res.json();
                    if (resJson.success) {
                        const a = resJson.asset;
                        document.getElementById('editAssetIdInput').value = a.asset_id;
                        document.getElementById('editAssetIdDisplay').textContent = a.asset_id;
                        document.getElementById('editAssetCategory').value = a.category_id;
                        document.getElementById('editAssetName').value = a.asset_name;
                        document.getElementById('editAssetSerial').value = a.serial_number || '';
                        document.getElementById('editAssetSource').value = a.source || '';
                        document.getElementById('editAssetStatus').value = a.status || 'Available';
                        document.getElementById('editAssetInDate').value = a.in_date || '';
                        document.getElementById('editAssetDescription').value = a.description || '';

                        const modal = new bootstrap.Modal(document.getElementById('modalEditAsset'));
                        modal.show();
                    } else {
                        showToast(resJson.message, 'error');
                    }
                } catch (e) {
                    showToast('Failed to load asset for editing.', 'error');
                }
            };
        });

        // Delete Asset
        document.querySelectorAll('.btn-delete-asset').forEach(btn => {
            btn.onclick = async function() {
                const assetId = this.getAttribute('data-id');
                const assetName = this.getAttribute('data-name');
                if (!confirm(`Are you sure you want to delete asset "${assetName}" (${assetId})?`)) {
                    return;
                }

                try {
                    const res = await fetch('<?= url('api/asset_action.php') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete_asset', asset_id: assetId })
                    });
                    const resJson = await res.json();
                    if (resJson.success) {
                        showToast(resJson.message, 'success');
                        loadAssets();
                    } else {
                        showToast(resJson.message || 'Failed to delete asset.', 'error');
                    }
                } catch (e) {
                    showToast('Server error while deleting asset.', 'error');
                }
            };
        });
    }

    // Search and Filter Listeners
    if (assetSearchInput) {
        let debounceTimer;
        assetSearchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(loadAssets, 300);
        });
    }

    if (assetCategoryFilter) {
        assetCategoryFilter.addEventListener('change', loadAssets);
    }

    const btnResetAssetFilters = document.getElementById('btnResetAssetFilters');
    if (btnResetAssetFilters) {
        btnResetAssetFilters.addEventListener('click', () => {
            if (assetSearchInput) assetSearchInput.value = '';
            if (assetCategoryFilter) assetCategoryFilter.value = '';
            currentStatusFilter = '';
            loadAssets();
            showToast('Filters reset to show all assets.', 'success');
        });
    }

    // -------------------------------------------------------------
    // 2. ADD ASSET FORM SUBMISSION
    // -------------------------------------------------------------
    const formAddAsset = document.getElementById('formAddAsset');
    const btnAddAssetSubmit = document.getElementById('btnAddAssetSubmit');
    const addAssetSpinner = document.getElementById('addAssetSpinner');
    const addAssetBtnText = document.getElementById('addAssetBtnText');

    if (formAddAsset) {
        formAddAsset.addEventListener('submit', async function(e) {
            e.preventDefault();

            btnAddAssetSubmit.disabled = true;
            addAssetSpinner.classList.remove('d-none');
            addAssetBtnText.textContent = 'Saving...';

            const formData = new FormData(formAddAsset);
            const dataObj = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('<?= url('api/asset_action.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dataObj)
                });
                const resJson = await res.json();

                if (resJson.success) {
                    showToast(resJson.message, 'success');
                    formAddAsset.reset();
                    // Set default today date again after reset
                    const inDateInput = document.getElementById('addAssetInDate');
                    if (inDateInput) inDateInput.value = new Date().toISOString().split('T')[0];
                    const modalEl = document.getElementById('modalAddAsset');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    loadAssets();
                } else {
                    showToast(resJson.message || 'Failed to save asset.', 'error');
                }
            } catch (err) {
                showToast('Server error while saving asset.', 'error');
            } finally {
                btnAddAssetSubmit.disabled = false;
                addAssetSpinner.classList.add('d-none');
                addAssetBtnText.textContent = 'Save Asset';
            }
        });
    }

    // -------------------------------------------------------------
    // 3. EDIT ASSET FORM HANDLER
    // -------------------------------------------------------------
    const formEditAsset = document.getElementById('formEditAsset');
    const btnEditAssetSubmit = document.getElementById('btnEditAssetSubmit');
    const editAssetSpinner = document.getElementById('editAssetSpinner');
    const editAssetBtnText = document.getElementById('editAssetBtnText');

    if (formEditAsset) {
        formEditAsset.addEventListener('submit', async function(e) {
            e.preventDefault();

            btnEditAssetSubmit.disabled = true;
            editAssetSpinner.classList.remove('d-none');
            editAssetBtnText.textContent = 'Updating...';

            const formData = new FormData(formEditAsset);
            const dataObj = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('<?= url('api/asset_action.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dataObj)
                });
                const resJson = await res.json();

                if (resJson.success) {
                    showToast(resJson.message, 'success');
                    const modalEl = document.getElementById('modalEditAsset');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    loadAssets();
                } else {
                    showToast(resJson.message || 'Failed to update asset.', 'error');
                }
            } catch (err) {
                showToast('Server error while updating asset.', 'error');
            } finally {
                btnEditAssetSubmit.disabled = false;
                editAssetSpinner.classList.add('d-none');
                editAssetBtnText.textContent = 'Update Asset';
            }
        });
    }

    // -------------------------------------------------------------
    // 4. DRAWER CATEGORY HANDLERS
    // -------------------------------------------------------------
    const formDrawer = document.getElementById('formDrawerCategory');
    const drawerCatId = document.getElementById('drawerCatId');
    const drawerCatName = document.getElementById('drawerCatName');
    const drawerFormTitle = document.getElementById('drawerFormTitle');
    const drawerCatBtnText = document.getElementById('drawerCatBtnText');
    const drawerCatSpinner = document.getElementById('drawerCatSpinner');
    const btnCancelDrawerEdit = document.getElementById('btnCancelDrawerEdit');
    const btnSaveDrawer = document.getElementById('btnSaveDrawerCategory');
    const sliderSearch = document.getElementById('sliderSearchCategory');

    function attachDrawerCategoryHandlers() {
        document.querySelectorAll('.btn-drawer-edit').forEach(btn => {
            btn.onclick = function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                drawerCatId.value = id;
                drawerCatName.value = name;
                drawerFormTitle.innerHTML = '<i class="bi bi-pencil-square text-primary me-1"></i> Edit Category';
                drawerCatBtnText.textContent = 'Update';
                btnCancelDrawerEdit.classList.remove('d-none');
                drawerCatName.focus();
            };
        });

        document.querySelectorAll('.btn-drawer-delete').forEach(btn => {
            btn.onclick = async function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                if (!confirm(`Are you sure you want to delete the category "${name}"?`)) return;

                try {
                    const res = await fetch('<?= url('api/category_action.php') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete_category', category_id: id })
                    });
                    const resJson = await res.json();
                    if (resJson.success) {
                        showToast(resJson.message, 'success');
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        showToast(resJson.message || 'Failed to delete category.', 'error');
                    }
                } catch (err) {
                    showToast('Server error while deleting category.', 'error');
                }
            };
        });
    }

    attachDrawerCategoryHandlers();

    function resetDrawerForm() {
        drawerCatId.value = '';
        drawerCatName.value = '';
        drawerFormTitle.innerHTML = '<i class="bi bi-plus-circle text-primary me-1"></i> Add Category';
        drawerCatBtnText.textContent = 'Save';
        btnCancelDrawerEdit.classList.add('d-none');
    }

    if (btnCancelDrawerEdit) {
        btnCancelDrawerEdit.addEventListener('click', resetDrawerForm);
    }

    if (formDrawer) {
        formDrawer.addEventListener('submit', async (e) => {
            e.preventDefault();
            btnSaveDrawer.disabled = true;
            drawerCatSpinner.classList.remove('d-none');
            drawerCatBtnText.textContent = 'Saving...';

            const formData = new FormData(formDrawer);
            const dataObj = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('<?= url('api/category_action.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dataObj)
                });
                const resJson = await res.json();
                if (resJson.success) {
                    showToast(resJson.message, 'success');
                    resetDrawerForm();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showToast(resJson.message || 'Failed to save category', 'error');
                    btnSaveDrawer.disabled = false;
                    drawerCatSpinner.classList.add('d-none');
                    drawerCatBtnText.textContent = drawerCatId.value ? 'Update' : 'Save';
                }
            } catch (err) {
                showToast('Network error occurred.', 'error');
                btnSaveDrawer.disabled = false;
                drawerCatSpinner.classList.add('d-none');
                drawerCatBtnText.textContent = drawerCatId.value ? 'Update' : 'Save';
            }
        });
    }

    if (sliderSearch) {
        sliderSearch.addEventListener('input', () => {
            const query = sliderSearch.value.trim().toLowerCase();
            document.querySelectorAll('.drawer-cat-item').forEach(item => {
                const name = item.getAttribute('data-name') || '';
                if (!query || name.includes(query)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Helper HTML Escape function
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Initial Load of Assets
    loadAssets();
});
</script>

