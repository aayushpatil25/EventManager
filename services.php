<?php

session_start();

require 'config/db.php';
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

// ── ADD ───────────────────────────────────────────────────────────────────────
if (isset($_POST['submit'])) {
    $name     = $_POST['name'];
    $category = $_POST['category'] ?? null;
    $status   = isset($_POST['status']) ? 'Active' : 'Inactive';

    if (empty($name)) {
        header('Location: services.php?error=name_required');
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO services_category (name, category, status) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $category, $status);
    header($stmt->execute() ? 'Location: services.php?success=1' : 'Location: services.php?error=db');
    exit;
}

// ── EDIT — reuses the same visible field names (name / category / status) ─────
if (isset($_POST['edit_submit'])) {
    $id       = intval($_POST['edit_id']);
    $name     = $_POST['name'];
    $category = $_POST['category'];
    $status   = isset($_POST['status']) ? 'Active' : 'Inactive';

    if (empty($name)) {
        header('Location: services.php?error=name_required');
        exit;
    }

    $stmt = $conn->prepare("UPDATE services_category SET name=?, category=?, status=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $category, $status, $id);
    header($stmt->execute() ? 'Location: services.php?updated=1' : 'Location: services.php?error=db');
    exit;
}

$service_result   = mysqli_query($conn, "SELECT * FROM services_category ORDER BY category ASC");
$categories_result = mysqli_query($conn, "SELECT * FROM services_category WHERE category='Main' AND status='Active' ORDER BY name ASC");

$filter_cats_result = mysqli_query($conn, "SELECT DISTINCT category FROM services_category WHERE  status='Active' ORDER BY name ASC");
$filter_categories  = [];
while ($fc = mysqli_fetch_assoc($filter_cats_result)) $filter_categories[] = $fc['category'];
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

    :root {
        --sky: #0ea5e9;
        --sky-dark: #0284c7;
        --sky-light: #e0f2fe;
        --ink: #0f172a;
        --ink-soft: #334155;
        --muted: #64748b;
        --border: #e2e8f0;
        --surface: #f8fafc;
        --white: #ffffff;
        --green: #16a34a;
        --green-bg: #f0fdf4;
        --green-border: #bbf7d0;
        --amber: #d97706;
        --amber-bg: #fffbeb;
        --amber-border: #fcd34d;
        --radius-sm: 6px;
        --radius-md: 10px;
        --radius-lg: 14px;
        --radius-xl: 20px;
        --shadow-sm: 0 1px 3px rgba(0, 0, 0, .06), 0 1px 2px rgba(0, 0, 0, .04);
        --shadow-lg: 0 20px 40px rgba(0, 0, 0, .12), 0 4px 12px rgba(0, 0, 0, .06);
        --font: 'Sora', sans-serif;
        --transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .client-container {
        display: flex;
        gap: 24px;
        align-items: flex-start;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 15px;
    }

    .page-header-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* Search */
    .search-bar {
        position: relative;
        flex: 1;
        min-width: 200px;
        max-width: 280px;
    }

    .search-bar svg {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        width: 16px;
        height: 16px;
        color: var(--muted);
        pointer-events: none;
    }

    .search-bar input {
        width: 100%;
        padding: 9px 14px 9px 36px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        font-size: 13.5px;
        font-family: var(--font);
        background: var(--white);
        color: var(--ink);
        transition: var(--transition);
    }

    .search-bar input::placeholder {
        color: var(--muted);
    }

    .search-bar input:focus {
        outline: none;
        border-color: var(--sky);
        box-shadow: 0 0 0 3px rgba(14, 165, 233, .12);
    }

    /* Filter dropdown */
    .filter-dropdown-wrap {
        position: relative;
    }

    .filter-dropdown-wrap svg.filter-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 14px;
        height: 14px;
        color: var(--sky);
        pointer-events: none;
        z-index: 1;
    }

    .filter-select {
        padding: 9px 32px 9px 30px;
        border: 1.5px solid var(--sky);
        border-radius: var(--radius-md);
        font-size: 13px;
        font-family: var(--font);
        color: var(--ink-soft);
        background: #f0f9ff;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%230ea5e9' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        transition: var(--transition);
        font-weight: 500;
        min-width: 160px;
    }

    .filter-select:focus {
        outline: none;
        border-color: var(--sky-dark);
        box-shadow: 0 0 0 3px rgba(14, 165, 233, .12);
    }

    .filter-select:hover {
        border-color: var(--sky-dark);
        background: #e0f2fe;
    }

    .filter-select.has-filter {
        background-color: #e0f2fe;
        color: var(--ink-soft);
        border-color: #0284c7;
    }

    /* Header action buttons */
    .btn-header-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 16px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        font-size: 13px;
        font-weight: 500;
        font-family: var(--font);
        cursor: pointer;
        transition: var(--transition);
        background: var(--white);
        white-space: nowrap;
    }

    .btn-header-action svg {
        width: 15px;
        height: 15px;
    }

    .btn-header-action.export {
        color: var(--green);
        border-color: var(--green-border);
        background: var(--green-bg);
    }

    .btn-header-action.export:hover {
        background: #dcfce7;
        transform: translateY(-1px);
        box-shadow: var(--shadow-sm);
    }

    .btn-header-action.print {
        color: #1d4ed8;
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    .btn-header-action.print:hover {
        background: #dbeafe;
        transform: translateY(-1px);
        box-shadow: var(--shadow-sm);
    }

    /* Left panel */
    .left-container {
        width: 360px;
        flex-shrink: 0;
        background: var(--white);
        border-radius: var(--radius-lg);
        border: 1.5px solid var(--border);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        transition: border-color .25s, box-shadow .25s;
    }

    /* ── EDIT MODE visual state ── */
    .left-container.edit-mode {
        border-color: #0ea5e9;
    }

    /* Edit mode banner inside the form */
    .edit-mode-banner {
        display: none;
        align-items: center;
        gap: 8px;
        background: #c5e8ff;
        border-bottom: 1.5px solid #0ea5e9;
        padding: 9px 18px;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--ink);
        font-family: var(--font);
    }

    .edit-mode-banner svg {
        width: 14px;
        height: 14px;
        flex-shrink: 0;
    }

    .left-container.edit-mode .edit-mode-banner {
        display: flex;
    }

    /* Form panel header */
    .form-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px 0;
    }

    .form-panel-header h4 {
        font-size: 14px;
        font-weight: 700;
        color: var(--ink);
        letter-spacing: -0.2px;
        padding-bottom: 10px;
        border-bottom: 1.5px solid var(--border);
        flex: 1;
    }

    .right-container {
        flex: 1;
        background: var(--white);
        border-radius: var(--radius-lg);
        border: 1.5px solid var(--border);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    /* Alerts */
    .alert {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        border-radius: var(--radius-md);
        font-size: 13.5px;
        font-weight: 500;
        margin-bottom: 16px;
    }

    .alert svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }

    .alert-success {
        background: #f0fdf4;
        border: 1.5px solid #bbf7d0;
        color: #15803d;
    }

    .alert-error {
        background: #fef2f2;
        border: 1.5px solid #fecaca;
        color: #b91c1c;
    }

    /* Action buttons in table */
    .actionBtn {
        padding: 3px 5px;
        border: none;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s ease;
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 6px;
        font-family: 'Lexend', sans-serif;
    }

    .actionBtn svg {
        width: 14px;
        height: 14px;
    }

    .actionBtn.edit {
        background: #fef3c7;
        color: #92400e;
    }

    .actionBtn.edit:hover {
        background: #fde68a;
        transform: translateY(-2px);
    }

    /* Form */
    #clientForm {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .modal-body {
        padding: 18px 24px 24px;
    }

    .form-grid {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group.full-width {
        width: 100%;
    }

    .form-group label {
        font-size: 13px;
        font-weight: 600;
        color: var(--ink-soft);
    }

    .form-group input[type="text"],
    .form-group select {
        padding: 9px 12px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        font-size: 13.5px;
        font-family: var(--font);
        color: var(--ink);
        background: var(--white);
        transition: var(--transition);
    }

    .form-group input[type="text"]:focus,
    .form-group select:focus {
        outline: none;
        border-color: var(--sky);
        box-shadow: 0 0 0 3px rgba(14, 165, 233, .12);
    }

    .form-group input[type="text"]::placeholder {
        color: #94a3b8;
    }

    /* Toggle */
    .toggle-label {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        user-select: none;
        font-size: 13px;
        font-weight: 600;
        color: var(--ink-soft);
    }

    .toggle-switch {
        position: relative;
        width: 40px;
        height: 22px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }

    .toggle-slider {
        position: absolute;
        inset: 0;
        background: #cbd5e1;
        border-radius: 999px;
        transition: var(--transition);
        cursor: pointer;
    }

    .toggle-slider::before {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        left: 3px;
        top: 3px;
        background: var(--white);
        border-radius: 50%;
        transition: var(--transition);
        box-shadow: 0 1px 3px rgba(0, 0, 0, .18);
    }

    .toggle-switch input:checked+.toggle-slider {
        background: var(--sky);
    }

    .toggle-switch input:checked+.toggle-slider::before {
        transform: translateX(18px);
    }

    .toggle-text {
        font-size: 13px;
        color: var(--muted);
        font-weight: 400;
    }

    /* Footer */
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 14px 18px;
        border-top: 1.5px solid var(--border);
        background: #f8fafc;
        flex-wrap: wrap;
    }

    .btn-cancel {
        padding: 9px 16px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        font-size: 13px;
        font-weight: 500;
        font-family: var(--font);
        color: var(--ink-soft);
        background: var(--white);
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-cancel:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    /* Cancel-edit button — distinct amber style */
    .btn-cancel-edit {
        display: none;
        align-items: center;
        gap: 6px;
        padding: 9px 16px;
        border: 1.5px solid var(--ink);
        border-radius: var(--radius-md);
        font-size: 13px;
        font-weight: 600;
        font-family: var(--font);
        color: var(--ink);
        background: var(--white);
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-cancel-edit:hover {
        background: #f1f5f9;
        border-color: var(--ink);
    }

    .btn-cancel-edit svg {
        width: 13px;
        height: 13px;
    }

    .left-container.edit-mode .btn-cancel-edit {
        display: inline-flex;
    }

    .btn-submit {
        display: flex;
        gap: 6px;
        align-items: center;
        padding: 9px 20px;
        background: var(--sky);
        color: var(--white);
        border: none;
        border-radius: var(--radius-md);
        font-size: 13.5px;
        font-weight: 600;
        font-family: var(--font);
        cursor: pointer;
        transition: var(--transition);
        box-shadow: 0 2px 8px rgba(14, 165, 233, .28);
    }

    .btn-submit:hover {
        background: var(--sky-dark);
        transform: translateY(-1px);
    }

    /* Edit-mode submit turns amber */
    .left-container.edit-mode .btn-submit {
        background: var(--sky-dark);
    }

    .left-container.edit-mode .btn-submit:hover {
        background: #0284c7;
    }

    /* Table */
    #servicesTable {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }

    #servicesTable thead tr {
        background: #0ea5e9;
        border-bottom: 1.5px solid var(--border);
    }

    #servicesTable th {
        padding: 10px 5px;
        text-align: left;
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        text-transform: uppercase;
        letter-spacing: .6px;
        white-space: nowrap;
    }

    #servicesTable td {
        padding: 5px;
        color: var(--ink-soft);
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    #servicesTable tbody tr:last-child td {
        border-bottom: none;
    }

    #servicesTable tbody tr:hover td {
        background: #f8fafc;
    }

    /* Highlight the row being edited */
    #servicesTable tbody tr.editing-row td {
        background: #fffbeb !important;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-active {
        background: #dcfce7;
        color: #15803d;
    }

    .badge-inactive {
        background: #f1f5f9;
        color: #64748b;
    }

    .badge::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    /* Pagination */
    .pagination-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 15px;
        border-top: 1.5px solid var(--border);
        background: #f8fafc;
        flex-wrap: wrap;
        gap: 10px;
    }

    .pagination-info {
        font-size: 14px;
        color: var(--muted);
        font-weight: 400;
    }

    .pagination-info strong {
        color: var(--ink-soft);
        font-weight: 600;
    }

    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .page-btn {
        min-width: 25px;
        height: 25px;
        padding: 3px 7px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        background: var(--white);
        color: var(--ink-soft);
        font-size: 13px;
        font-weight: 500;
        font-family: var(--font);
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .page-btn:hover:not(:disabled) {
        border-color: var(--sky);
        color: var(--sky);
        background: #f0f9ff;
    }

    .page-btn.active {
        background: var(--sky);
        border-color: var(--sky);
        color: #fff;
        font-weight: 700;
    }

    .page-btn:disabled {
        opacity: .4;
        cursor: not-allowed;
    }

    .page-btn svg {
        width: 13px;
        height: 13px;
    }

    .view-detail-row {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 14px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .view-detail-row:last-child {
        border-bottom: none;
    }

    .view-detail-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: var(--muted);
    }

    .view-detail-value {
        font-size: 14px;
        font-weight: 500;
        color: var(--ink);
    }

    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .45);
        backdrop-filter: blur(3px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.open {
        display: flex;
    }

    .modal-card {
        background: var(--white);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-lg);
        width: 100%;
        max-width: 440px;
        overflow: hidden;
        animation: modalIn .22s cubic-bezier(.4, 0, .2, 1);
    }

    @keyframes modalIn {
        from {
            opacity: 0;
            transform: translateY(16px) scale(.97)
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1)
        }
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 24px;
        border-bottom: 1.5px solid var(--border);
        background-color: #0284c7;
    }

    .modal-header h3 {
        font-size: 15px;
        font-weight: 700;
        color: white;
    }

    .btn-modal-close {
        width: 36px;
        height: 36px;
        border: none;
        background: #ff6666;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .2s ease;
        color: #fff;
    }

    .btn-modal-close:hover {
        background: #ff3333;
        transform: rotate(90deg);
    }

    @media print {

        .left-container,
        .page-header-right {
            display: none;
        }

        .right-container {
            box-shadow: none;
            border: none;
        }
    }

    @media (max-width:900px) {
        .client-container {
            flex-direction: column;
        }

        .left-container {
            width: 100%;
        }
    }
</style>

<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
    <div class="top-container">

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success">
                <svg viewBox="0 0 24 24">
                    <path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
                </svg>
                <?= (isset($_GET['added']) && $_GET['added'] > 1) ? $_GET['added'] . ' subcategories added!' : 'Service added successfully!' ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
            <div class="alert alert-success">
                <svg viewBox="0 0 24 24">
                    <path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
                </svg>
                Service updated successfully!
            </div>
        <?php endif; ?>

        <div class="client-container">

            <!-- ══════════════════════════════════════════════
             LEFT PANEL — Add / Edit form (inline, no modal)
             ══════════════════════════════════════════════ -->
            <div class="left-container" id="leftContainer">

                <!-- Edit-mode banner (hidden in add mode) -->
                <div class="edit-mode-banner">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                    Editing service — make changes and save
                </div>

                <form action="" method="post" id="clientForm">
                    <!-- Hidden fields (only used in edit mode) -->
                    <input type="hidden" name="edit_id" id="inline_edit_id" value="">

                    <div class="modal-body">
                        <div class="form-grid">
                            <div class="form-section">
                                <h4 id="formTitle">Service Details</h4>
                            </div>

                            <div class="form-group full-width">
                                <label for="name">Name *</label>
                                <input type="text" name="name" id="name" placeholder="Enter service name">
                            </div>

                            <div class="form-group full-width">
                                <label>Category *</label>
                                <select name="category" id="category">
                                    <option value="Main">Main</option>
                                    <?php if ($categories_result && mysqli_num_rows($categories_result) > 0):
                                        while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                                            <option value="<?= htmlspecialchars($cat['name']) ?>">
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                    <?php endwhile;
                                    endif; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="toggle-label">
                                    <span>Status</span>
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="status" id="status" checked
                                            onchange="document.getElementById('statusText').textContent=this.checked?'Active':'Inactive'">
                                        <span class="toggle-slider"></span>
                                    </div>
                                    <span class="toggle-text" id="statusText">Active</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <!-- Cancel edit (amber, only visible in edit mode) -->
                        <button type="button" class="btn-cancel-edit" onclick="resetToAddMode()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                            Cancel
                        </button>

                        <!-- ADD mode submit -->
                        <button type="submit" name="submit" id="addSubmitBtn" class="btn-submit">
                            <span style="font-size:18px;line-height:1;">+</span> Add Service
                        </button>

                        <!-- EDIT mode submit (hidden until edit mode) -->
                        <button type="submit" name="edit_submit" id="editSubmitBtn" class="btn-submit" style="display:none;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
                                <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z" />
                                <polyline points="17 21 17 13 7 13 7 21" />
                                <polyline points="7 3 7 8 15 8" />
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div><!-- /left-container -->

            <!-- ══════════════════════════════════════════════
             RIGHT PANEL — Table
             ══════════════════════════════════════════════ -->
            <div>
                <div class="page-header">
                    <div class="page-header-right">
                        <div class="search-bar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                            </svg>
                            <input type="text" id="searchInput" placeholder="Search…" oninput="applyFilters()">
                        </div>

                        <div class="filter-dropdown-wrap">
                            <svg class="filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                            </svg>
                            <select class="filter-select" id="categoryFilter" onchange="applyFilters()" title="Filter by category">
                                <option value="">All Categories</option>
                                <?php foreach ($filter_categories as $fc_name): ?>
                                    <option value="<?= htmlspecialchars($fc_name) ?>"><?= htmlspecialchars($fc_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button class="btn-header-action export" onclick="exportCSV()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg><span>Export</span>
                        </button>

                        <button class="btn-header-action print" onclick="printTable()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 6 2 18 2 18 9" />
                                <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" />
                                <rect x="6" y="14" width="12" height="8" />
                            </svg><span>Print</span>
                        </button>
                    </div>
                </div>

                <div class="right-container">
                    <table id="servicesTable">
                        <thead>
                            <tr>
                                <th style="text-align:center;">Sr.</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th style="text-align:center;">Status</th>
                                <th style="width:10%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="servicesTableBody">
                            <?php if (mysqli_num_rows($service_result) > 0):
                                $sr = 1;
                                while ($svc = mysqli_fetch_assoc($service_result)): ?>
                                    <tr data-id="<?= $svc['id'] ?>">
                                        <td style="text-align:center;"><?= $sr++ ?></td>
                                        <td><?= htmlspecialchars($svc['name']) ?></td>
                                        <td><?= htmlspecialchars($svc['category']) ?></td>
                                        <td style="text-align:center;">
                                            <span class="badge <?= $svc['status'] ? 'badge-active' : 'badge-inactive' ?>">
                                                <?= $svc['status'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="actionBtn edit"
                                                onclick="loadEditForm(
                                            <?= $svc['id'] ?>,
                                            '<?= htmlspecialchars($svc['name'], ENT_QUOTES) ?>',
                                            '<?= htmlspecialchars($svc['category'], ENT_QUOTES) ?>',
                                            '<?= $svc['status'] ?>'
                                        )">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                </svg>Edit
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr class="empty-row">
                                    <td colspan="5">No services found. Add one using the form on the left.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div class="pagination-wrapper" id="paginationWrapper">
                        <div class="pagination-info" id="paginationInfo"></div>
                        <div class="pagination-controls" id="paginationControls"></div>
                    </div>
                </div>
            </div><!-- /right wrapper -->

        </div><!-- /client-container -->
    </div><!-- /top-container -->

    <!-- ── VIEW MODAL (kept for potential future use) ── -->
    <div class="modal-overlay" id="viewModalOverlay" onclick="closeViewModal(event)">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Service Details</h3>
                <button class="btn-modal-close" onclick="closeViewModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="view-detail-row"><span class="view-detail-label">Name</span><span class="view-detail-value" id="view_name">—</span></div>
                <div class="view-detail-row"><span class="view-detail-label">Category</span><span class="view-detail-value" id="view_category">—</span></div>
                <div class="view-detail-row"><span class="view-detail-label">Status</span><span class="view-detail-value" id="view_status">—</span></div>
            </div>
        </div>
    </div>

</main>

<script>
    const ROWS_PER_PAGE = 10;
    let currentPage = 1;
    let filteredRows = [];

    // ══════════════════════════════════════════════════════════════
    // INLINE EDIT — loads data into the left panel form
    // ══════════════════════════════════════════════════════════════

    let _activeEditRow = null; // reference to highlighted table row

    function loadEditForm(id, name, category, status) {
        // ── Populate fields ────────────────────────────────────
        document.getElementById('inline_edit_id').value = id;
        document.getElementById('name').value = name;
        document.getElementById('category').value = category;

        const toggle = document.getElementById('status');
        toggle.checked = (status === 'Active' || status === '1');
        document.getElementById('statusText').textContent = toggle.checked ? 'Active' : 'Inactive';

        // ── Switch UI to edit mode ─────────────────────────────
        document.getElementById('formTitle').textContent = 'Edit Service';
        document.getElementById('addSubmitBtn').style.display = 'none';
        document.getElementById('editSubmitBtn').style.display = '';
        document.getElementById('leftContainer').classList.add('edit-mode');

        // ── Highlight edited row ───────────────────────────────
        if (_activeEditRow) _activeEditRow.classList.remove('editing-row');
        _activeEditRow = document.querySelector(`#servicesTable tbody tr[data-id="${id}"]`);
        if (_activeEditRow) _activeEditRow.classList.add('editing-row');

        // ── Scroll left panel into view (mobile) ──────────────
        document.getElementById('leftContainer').scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }

    function resetToAddMode() {
        document.getElementById('inline_edit_id').value = '';
        document.getElementById('name').value = '';
        document.getElementById('category').value = 'Main';

        const toggle = document.getElementById('status');
        toggle.checked = true;
        document.getElementById('statusText').textContent = 'Active';

        document.getElementById('formTitle').textContent = 'Service Details';
        document.getElementById('addSubmitBtn').style.display = '';
        document.getElementById('editSubmitBtn').style.display = 'none';
        document.getElementById('leftContainer').classList.remove('edit-mode');

        if (_activeEditRow) {
            _activeEditRow.classList.remove('editing-row');
            _activeEditRow = null;
        }
        document.getElementById('name').focus();
    }

    // ── VIEW MODAL (kept lean) ────────────────────────────────────
    function openViewModal(id, name, category, status) {
        document.getElementById('view_name').textContent = name;
        document.getElementById('view_category').textContent = category;
        const el = document.getElementById('view_status');
        el.innerHTML = status === 'Active' ?
            '<span class="badge badge-active">Active</span>' :
            '<span class="badge badge-inactive">Inactive</span>';
        document.getElementById('viewModalOverlay').classList.add('open');
    }

    function closeViewModal(e) {
        if (!e || e.target === document.getElementById('viewModalOverlay'))
            document.getElementById('viewModalOverlay').classList.remove('open');
    }

    // ══════════════════════════════════════════════════════════════
    // PAGINATION
    // ══════════════════════════════════════════════════════════════
    function getAllDataRows() {
        return Array.from(document.querySelectorAll('#servicesTable tbody tr:not(.empty-row):not(.no-results-row)'));
    }

    function renderPage() {
        getAllDataRows().forEach(r => r.style.display = 'none');
        const stale = document.querySelector('.no-results-row');
        if (stale) stale.remove();

        const total = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / ROWS_PER_PAGE));
        currentPage = Math.min(Math.max(currentPage, 1), totalPages);

        if (total === 0) {
            const noRow = document.createElement('tr');
            noRow.className = 'no-results-row';
            noRow.innerHTML = `<td colspan="5" style="text-align:center;padding:40px;color:var(--muted);">No services match your search.</td>`;
            document.querySelector('#servicesTable tbody').appendChild(noRow);
            document.getElementById('paginationInfo').textContent = 'No results';
            document.getElementById('paginationControls').innerHTML = '';
            return;
        }

        const start = (currentPage - 1) * ROWS_PER_PAGE;
        const end = Math.min(start + ROWS_PER_PAGE, total);
        filteredRows.slice(start, end).forEach(r => r.style.display = '');

        document.getElementById('paginationInfo').innerHTML =
            `Showing <strong>${start+1}–${end}</strong> of <strong>${total}</strong> services`;

        const ctrl = document.getElementById('paginationControls');
        ctrl.innerHTML = '';

        const prev = document.createElement('button');
        prev.className = 'page-btn';
        prev.disabled = currentPage === 1;
        prev.title = 'Previous';
        prev.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>`;
        prev.addEventListener('click', () => goToPage(currentPage - 1));
        ctrl.appendChild(prev);

        getPageNumbers(currentPage, totalPages).forEach(p => {
            const b = document.createElement('button');
            b.className = 'page-btn' + (p === currentPage ? ' active' : '');
            b.disabled = p === '…';
            b.textContent = p;
            if (p !== '…') b.addEventListener('click', () => goToPage(p));
            ctrl.appendChild(b);
        });

        const next = document.createElement('button');
        next.className = 'page-btn';
        next.disabled = currentPage === totalPages;
        next.title = 'Next';
        next.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>`;
        next.addEventListener('click', () => goToPage(currentPage + 1));
        ctrl.appendChild(next);
    }

    function getPageNumbers(cur, total) {
        if (total <= 7) return Array.from({
            length: total
        }, (_, i) => i + 1);
        const s = new Set([1, total, cur]);
        if (cur - 1 > 1) s.add(cur - 1);
        if (cur + 1 < total) s.add(cur + 1);
        const arr = Array.from(s).sort((a, b) => a - b);
        const res = [];
        let prev = 0;
        arr.forEach(p => {
            if (p - prev > 1) res.push('…');
            res.push(p);
            prev = p;
        });
        return res;
    }

    function goToPage(p) {
        currentPage = p;
        renderPage();
        document.getElementById('servicesTable').scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }

    // ══════════════════════════════════════════════════════════════
    // FILTERS
    // ══════════════════════════════════════════════════════════════
    function applyFilters() {
        const query = document.getElementById('searchInput').value.trim().toLowerCase();
        const catSel = document.getElementById('categoryFilter');
        const category = catSel.value.trim().toLowerCase();
        filteredRows = getAllDataRows().filter(row => {
            const cells = row.querySelectorAll('td');
            const name = cells[1]?.textContent.toLowerCase() ?? '';
            const rowCat = cells[2]?.textContent.toLowerCase() ?? '';
            return (!query || name.includes(query) || rowCat.includes(query)) &&
                (!category || rowCat === category);
        });
        catSel.classList.toggle('has-filter', !!category);
        currentPage = 1;
        renderPage();
    }

    // ── EXPORT CSV ─────────────────────────────────────────────────
    function exportCSV() {
        const rows = filteredRows.length ? filteredRows : getAllDataRows();
        const csvRows = [
            ['Sr.No', 'Name', 'Category', 'Status'].join(',')
        ];
        rows.forEach(row => {
            if (row.classList.contains('empty-row') || row.classList.contains('no-results-row')) return;
            const c = row.querySelectorAll('td');
            if (c.length < 4) return;
            csvRows.push([`"${c[0].textContent.trim()}"`, `"${c[1].textContent.trim().replace(/"/g,'""')}"`, `"${c[2].textContent.trim().replace(/"/g,'""')}"`, `"${c[3].textContent.trim().replace(/"/g,'""')}"`].join(','));
        });
        const blob = new Blob([csvRows.join('\n')], {
            type: 'text/csv;charset=utf-8;'
        });
        const a = Object.assign(document.createElement('a'), {
            href: URL.createObjectURL(blob),
            download: `services_${new Date().toISOString().slice(0,10)}.csv`
        });
        a.click();
        URL.revokeObjectURL(a.href);
    }

    // ── PRINT ──────────────────────────────────────────────────────
    function printTable() {
        const rows = filteredRows.length ? filteredRows : getAllDataRows();
        const pw = window.open('', '_blank');
        let trs = '';
        rows.forEach(row => {
            if (row.classList.contains('empty-row') || row.classList.contains('no-results-row')) return;
            const c = row.querySelectorAll('td');
            if (c.length < 4) return;
            const isA = c[3].textContent.trim().toLowerCase() === 'active';
            trs += `<tr><td>${c[0].textContent.trim()}</td><td>${c[1].textContent.trim()}</td><td>${c[2].textContent.trim()}</td><td><span class="badge ${isA?'badge-active':'badge-inactive'}">${c[3].textContent.trim()}</span></td></tr>`;
        });
        pw.document.write(`<!DOCTYPE html><html><head><title>Services</title><style>body{font-family:'Segoe UI',sans-serif;padding:30px;color:#0f172a}h2{font-size:20px;margin-bottom:4px}p.sub{font-size:12px;color:#64748b;margin-bottom:20px}table{width:100%;border-collapse:collapse;font-size:13px}th{background:#f8fafc;padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#64748b;border:2px solid #000000}td{padding:10px 14px;border:1px solid #000000}.badge{padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600}.badge-active{background:#dcfce7;color:#15803d}.badge-inactive{background:#f1f5f9;color:#64748b}</style></head><body><h2>Services List</h2><p class="sub">Generated: ${new Date().toLocaleString()}</p><table><thead><tr><th>Sr.</th><th>Name</th><th>Category</th><th>Status</th></tr></thead><tbody>${trs}</tbody></table></body></html>`);
        pw.document.close();
        pw.focus();
        setTimeout(() => {
            pw.print();
            pw.close();
        }, 400);
    }

    // ── INIT ────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        filteredRows = getAllDataRows();
        renderPage();
    });
</script>