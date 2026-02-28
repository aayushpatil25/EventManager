<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

// Handle Add Multiple Requirements
if (isset($_POST['submit'])) {
    $service_id = intval($_POST['service_id']);
    $service_name = mysqli_real_escape_string($conn, trim($_POST['service_name']));
    
    // Get arrays of requirements data
    $requirement_names = $_POST['requirement_name'];
    $descriptions = $_POST['description'];
    $quantities = $_POST['quantity'];
    $units = $_POST['unit'];
    $statuses = $_POST['status'];
    
    $success = true;
    $errors = [];
    
    // Loop through each requirement and insert
    for ($i = 0; $i < count($requirement_names); $i++) {
        $requirement_name = mysqli_real_escape_string($conn, trim($requirement_names[$i]));
        $description = mysqli_real_escape_string($conn, trim($descriptions[$i]));
        $quantity = intval($quantities[$i]);
        $unit = mysqli_real_escape_string($conn, trim($units[$i]));
        $status = mysqli_real_escape_string($conn, trim($statuses[$i]));
        
        // Skip empty rows
        if (empty($requirement_name) || empty($unit)) {
            continue;
        }
        
        $sql = "INSERT INTO requirements(service_id, service_name, requirement_name, description, quantity, unit, status) 
                VALUES('$service_id', '$service_name', '$requirement_name', '$description', '$quantity', '$unit', '$status')";
        
        if (!mysqli_query($conn, $sql)) {
            $success = false;
            $errors[] = "Error adding '$requirement_name': " . mysqli_error($conn);
        }
    }
    
    if ($success && empty($errors)) {
        header('Location: requirements.php?success=added');
        exit();
    } else {
        $error = implode('<br>', $errors);
    }
}

// Handle Update Requirement
if (isset($_POST['update_requirement'])) {
    $requirement_id = intval($_POST['requirement_id']);
    $service_id = intval($_POST['service_id']);
    $service_name = mysqli_real_escape_string($conn, trim($_POST['service_name']));
    $requirement_name = mysqli_real_escape_string($conn, trim($_POST['requirement_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $quantity = intval($_POST['quantity']);
    $unit = mysqli_real_escape_string($conn, trim($_POST['unit']));
    $status = mysqli_real_escape_string($conn, trim($_POST['status']));

    $update_sql = "UPDATE requirements SET 
                   service_id='$service_id', service_name='$service_name', requirement_name='$requirement_name',
                   description='$description', quantity='$quantity', unit='$unit', 
                   status='$status', updated_at=NOW() 
                   WHERE requirement_id='$requirement_id'";

    if (mysqli_query($conn, $update_sql)) {
        header('Location: requirements.php?success=updated');
        exit();
    } else {
        $error = 'Error: ' . mysqli_error($conn);
    }
}

// Handle Delete Requirement
if (isset($_POST['delete_requirement'])) {
    $requirement_id = intval($_POST['delete_requirement_id']);
    $delete_sql = "DELETE FROM requirements WHERE requirement_id='$requirement_id'";

    if (mysqli_query($conn, $delete_sql)) {
        header('Location: requirements.php?success=deleted');
        exit();
    } else {
        $error = 'Error: ' . mysqli_error($conn);
    }
}

// Fetch requirement for editing
$edit_requirement = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_sql = "SELECT * FROM requirements WHERE requirement_id='$edit_id'";
    $edit_result = mysqli_query($conn, $edit_sql);
    if ($edit_result && mysqli_num_rows($edit_result) > 0) {
        $edit_requirement = mysqli_fetch_assoc($edit_result);
    }
}

// Fetch services for dropdown
$service_sql = "SELECT * FROM services WHERE is_active=1 ORDER BY name ASC";
$service_result = mysqli_query($conn, $service_sql);

// Fetch all requirements
$requirements_sql = "SELECT * FROM requirements ORDER BY service_name ASC, requirement_name ASC";
$requirements_result = mysqli_query($conn, $requirements_sql);
?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .clients-container {
        padding: 32px;
        background: #f1f5f9;
    }

    .page-header {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 28px;
    }

    .page-header-left {
        flex: 1;
    }

    .page-title {
        font-family: 'Outfit', sans-serif;
        font-size: 32px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
        letter-spacing: -0.5px;
    }

    .page-subtitle {
        font-size: 14px;
        color: #64748b;
        font-family: 'Lexend', sans-serif;
    }

    .btn-add-client {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #0ea5e9;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
    }

    .btn-add-client:hover {
        background: #0284c7;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
    }

    .btn-add-client svg {
        width: 18px;
        height: 18px;
    }

    .action-bar {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .search-bar {
        position: relative;
        flex: 1;
        max-width: 400px;
    }

    .search-bar input {
        width: 100%;
        padding: 12px 16px 12px 44px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
        font-family: 'Lexend', sans-serif;
        background: white;
        transition: all 0.2s ease;
    }

    .search-bar input:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    .search-bar svg {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        color: #94a3b8;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
    }

    .btn-export,
    .btn-print {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #475569;
    }

    .btn-export:hover,
    .btn-print:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }

    .btn-export svg,
    .btn-print svg {
        width: 16px;
        height: 16px;
    }

    .table-container {
        background: white;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .clients-table {
        width: 100%;
        border-collapse: collapse;
    }

    .clients-table thead {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }

    .clients-table th {
        padding: 16px 20px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        font-family: 'Lexend', sans-serif;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .clients-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s ease;
    }

    .clients-table tbody tr:hover {
        background: #f8fafc;
    }

    .clients-table td {
        padding: 16px 20px;
        font-size: 14px;
        color: #1e293b;
        font-family: 'Lexend', sans-serif;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
    }

    .status-active {
        background: #dcfce7;
        color: #16a34a;
    }

    .status-inactive {
        background: #fee2e2;
        color: #dc2626;
    }

    .action-buttons-cell {
        display: flex;
        gap: 8px;
    }

    .btn-edit,
    .btn-delete {
        padding: 6px 14px;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-edit {
        background: #dbeafe;
        color: #1e40af;
    }

    .btn-edit:hover {
        background: #bfdbfe;
        transform: translateY(-1px);
    }

    .btn-delete {
        background: #fee2e2;
        color: #dc2626;
    }

    .btn-delete:hover {
        background: #fecaca;
        transform: translateY(-1px);
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 20px;
        width: 90%;
        max-width: 900px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        padding: 24px 28px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 22px;
        font-weight: 700;
        color: #1e293b;
        font-family: 'Outfit', sans-serif;
    }

    .btn-close {
        background: none;
        border: none;
        font-size: 28px;
        color: #94a3b8;
        cursor: pointer;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .btn-close:hover {
        background: #f1f5f9;
        color: #475569;
    }

    .modal-body {
        padding: 28px;
    }

    .service-selection {
        margin-bottom: 24px;
        padding: 20px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .service-selection .form-group {
        margin-bottom: 0;
    }

    .requirements-table-wrapper {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 16px;
    }

    .requirements-table {
        width: 100%;
        border-collapse: collapse;
    }

    .requirements-table thead {
        background: #f8fafc;
    }

    .requirements-table th {
        padding: 12px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        font-family: 'Lexend', sans-serif;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e2e8f0;
    }

    .requirements-table td {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .requirements-table tbody tr:last-child td {
        border-bottom: none;
    }

    .requirements-table input,
    .requirements-table select,
    .requirements-table textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        font-family: 'Lexend', sans-serif;
        transition: all 0.2s ease;
    }

    .requirements-table input:focus,
    .requirements-table select:focus,
    .requirements-table textarea:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    .requirements-table textarea {
        resize: vertical;
        min-height: 60px;
    }

    .btn-remove-row {
        padding: 6px 10px;
        background: #fee2e2;
        color: #dc2626;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: 'Lexend', sans-serif;
    }

    .btn-remove-row:hover {
        background: #fecaca;
    }

    .btn-add-row {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        background: #0ea5e9;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-add-row:hover {
        background: #0284c7;
        transform: translateY(-1px);
    }

    .btn-add-row svg {
        width: 16px;
        height: 16px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-label {
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        font-family: 'Lexend', sans-serif;
    }

    .form-label .required {
        color: #ef4444;
    }

    .form-input,
    .form-select,
    .form-textarea {
        padding: 12px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-family: 'Lexend', sans-serif;
        transition: all 0.2s ease;
        background: white;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    .form-textarea {
        resize: vertical;
        min-height: 80px;
    }

    .modal-footer {
        padding: 20px 28px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }

    .btn-submit {
        padding: 12px 28px;
        background: #0ea5e9;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-submit:hover {
        background: #0284c7;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    }

    .btn-cancel {
        padding: 12px 28px;
        background: #f1f5f9;
        color: #475569;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-cancel:hover {
        background: #e2e8f0;
    }

    .alert {
        padding: 14px 18px;
        border-radius: 12px;
        margin-bottom: 24px;
        font-size: 14px;
        font-family: 'Lexend', sans-serif;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success {
        background: #dcfce7;
        color: #16a34a;
        border: 1px solid #86efac;
    }

    .alert-error {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fca5a5;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        .table-container, .table-container * {
            visibility: visible;
        }
        .table-container {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .action-buttons-cell {
            display: none !important;
        }
        .clients-table th:last-child,
        .clients-table td:last-child {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .action-bar {
            flex-direction: column;
            align-items: stretch;
        }
        
        .search-bar {
            max-width: 100%;
        }

        .requirements-table-wrapper {
            overflow-x: auto;
        }
    }
</style>

<div class="clients-container">
    <div class="page-header">
        <!-- <div class="page-header-left">
            <h1 class="page-title">Requirements Management</h1>
            <p class="page-subtitle">Manage service requirements and specifications</p>
        </div> -->
        <button class="btn-add-client" onclick="openModal()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Requirements
        </button>
        <div class="action-bar">
        <div class="search-bar">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" id="searchInput" placeholder="Search requirements...">
        </div>
        <div class="action-buttons">
            <button class="btn-export" onclick="exportExcel()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export
            </button>
            <button class="btn-print" onclick="printTable()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print
            </button>
        </div>
    </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <?php
                if ($_GET['success'] == 'added') echo 'Requirements added successfully!';
                else if ($_GET['success'] == 'updated') echo 'Requirement updated successfully!';
                else if ($_GET['success'] == 'deleted') echo 'Requirement deleted successfully!';
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    

    <div class="table-container">
        <table class="clients-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Service</th>
                    <th>Requirement Name</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($requirements_result) > 0) {
                    while ($row = mysqli_fetch_assoc($requirements_result)) {
                        $status_class = $row['status'] == 'Active' ? 'status-active' : 'status-inactive';
                        echo "<tr>";
                        echo "<td>{$row['requirement_id']}</td>";
                        echo "<td>{$row['service_name']}</td>";
                        echo "<td>{$row['requirement_name']}</td>";
                        echo "<td>" . (empty($row['description']) ? '-' : $row['description']) . "</td>";
                        echo "<td>" . (isset($row['quantity']) ? $row['quantity'] : '0') . "</td>";
                        echo "<td>{$row['unit']}</td>";
                        echo "<td><span class='status-badge {$status_class}'>{$row['status']}</span></td>";
                        echo "<td>
                                <div class='action-buttons-cell'>
                                    <button class='btn-edit' onclick='editRequirement({$row['requirement_id']})'>Edit</button>
                                    <button class='btn-delete' onclick='deleteRequirement({$row['requirement_id']})'>Delete</button>
                                </div>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' style='text-align: center; padding: 40px; color: #94a3b8;'>No requirements found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Requirements Modal -->
<div id="requirementModal" class="modal">
    <div class="modal-content">
        <form method="POST" id="requirementForm">
            <div class="modal-header">
                <h2 class="modal-title">Add Requirements</h2>
                <button type="button" class="btn-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="service-selection">
                    <div class="form-group">
                        <label class="form-label">Select Service <span class="required">*</span></label>
                        <select name="service_id" id="service_id" class="form-select" required onchange="updateServiceName()">
                            <option value="">Select Service</option>
                            <?php
                            mysqli_data_seek($service_result, 0);
                            while ($service = mysqli_fetch_assoc($service_result)) {
                                echo "<option value='{$service['id']}' data-name='{$service['name']}'>{$service['name']}</option>";
                            }
                            ?>
                        </select>
                        <input type="hidden" name="service_name" id="service_name">
                    </div>
                </div>

                <div class="requirements-table-wrapper">
                    <table class="requirements-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Requirement Name <span class="required">*</span></th>
                                <th style="width: 30%;">Description</th>
                                <th style="width: 12%;">Quantity <span class="required">*</span></th>
                                <th style="width: 15%;">Unit <span class="required">*</span></th>
                                <th style="width: 13%;">Status</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="requirementsTableBody">
                            <tr class="requirement-row">
                                <td>
                                    <input type="text" name="requirement_name[]" class="requirement-name" placeholder="e.g., Balloons" required>
                                </td>
                                <td>
                                    <textarea name="description[]" placeholder="Optional description"></textarea>
                                </td>
                                <td>
                                    <input type="number" name="quantity[]" min="0" value="1" required>
                                </td>
                                <td>
                                    <input type="text" name="unit[]" placeholder="e.g., pieces" required>
                                </td>
                                <td>
                                    <select name="status[]" required>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="btn-remove-row" onclick="removeRow(this)" style="display: none;">×</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn-add-row" onclick="addRow()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Row
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="submit" class="btn-submit">Save All Requirements</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Requirement Modal -->
<div id="requirementEditModal" class="modal">
    <div class="modal-content">
        <form method="POST" id="requirementEditForm">
            <input type="hidden" name="requirement_id" id="edit_requirement_id" value="<?php echo $edit_requirement ? $edit_requirement['requirement_id'] : ''; ?>">
            <div class="modal-header">
                <h2 class="modal-title">Edit Requirement</h2>
                <button type="button" class="btn-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Service <span class="required">*</span></label>
                        <select name="service_id" id="edit_service_id" class="form-select" required onchange="updateEditServiceName()">
                            <option value="">Select Service</option>
                            <?php
                            mysqli_data_seek($service_result, 0);
                            while ($service = mysqli_fetch_assoc($service_result)) {
                                $selected = ($edit_requirement && $edit_requirement['service_id'] == $service['id']) ? 'selected' : '';
                                echo "<option value='{$service['id']}' data-name='{$service['name']}' {$selected}>{$service['name']}</option>";
                            }
                            ?>
                        </select>
                        <input type="hidden" name="service_name" id="edit_service_name" value="<?php echo $edit_requirement ? $edit_requirement['service_name'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Requirement Name <span class="required">*</span></label>
                        <input type="text" name="requirement_name" class="form-input" required value="<?php echo $edit_requirement ? $edit_requirement['requirement_name'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantity <span class="required">*</span></label>
                        <input type="number" name="quantity" class="form-input" min="0" required value="<?php echo $edit_requirement ? $edit_requirement['quantity'] : '1'; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit <span class="required">*</span></label>
                        <input type="text" name="unit" class="form-input" required value="<?php echo $edit_requirement ? $edit_requirement['unit'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status <span class="required">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active" <?php echo ($edit_requirement && $edit_requirement['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($edit_requirement && $edit_requirement['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-textarea"><?php echo $edit_requirement ? $edit_requirement['description'] : ''; ?></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" name="update_requirement" class="btn-submit">Update Requirement</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <form method="POST">
            <input type="hidden" name="delete_requirement_id" id="delete_requirement_id">
            <div class="modal-header">
                <h2 class="modal-title">Confirm Delete</h2>
                <button type="button" class="btn-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="color: #64748b; font-family: 'Lexend', sans-serif; font-size: 14px; margin: 0;">
                    Are you sure you want to delete this requirement? This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" name="delete_requirement" class="btn-submit" style="background: #dc2626;">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Add new row to requirements table
    function addRow() {
        const tbody = document.getElementById('requirementsTableBody');
        const newRow = document.createElement('tr');
        newRow.className = 'requirement-row';
        newRow.innerHTML = `
            <td>
                <input type="text" name="requirement_name[]" class="requirement-name" placeholder="e.g., Balloons" required>
            </td>
            <td>
                <textarea name="description[]" placeholder="Optional description"></textarea>
            </td>
            <td>
                <input type="number" name="quantity[]" min="0" value="1" required>
            </td>
            <td>
                <input type="text" name="unit[]" placeholder="e.g., pieces" required>
            </td>
            <td>
                <select name="status[]" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </td>
            <td>
                <button type="button" class="btn-remove-row" onclick="removeRow(this)">×</button>
            </td>
        `;
        tbody.appendChild(newRow);
        updateRemoveButtons();
    }

    // Remove row from requirements table
    function removeRow(button) {
        const row = button.closest('tr');
        row.remove();
        updateRemoveButtons();
    }

    // Update visibility of remove buttons
    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.requirement-row');
        rows.forEach((row, index) => {
            const removeBtn = row.querySelector('.btn-remove-row');
            if (rows.length > 1) {
                removeBtn.style.display = 'block';
            } else {
                removeBtn.style.display = 'none';
            }
        });
    }

    // Update service name in hidden input for add form
    function updateServiceName() {
        const select = document.getElementById('service_id');
        const selectedOption = select.options[select.selectedIndex];
        const serviceName = selectedOption.getAttribute('data-name');
        document.getElementById('service_name').value = serviceName || '';
    }

    // Update service name in hidden input for edit form
    function updateEditServiceName() {
        const select = document.getElementById('edit_service_id');
        const selectedOption = select.options[select.selectedIndex];
        const serviceName = selectedOption.getAttribute('data-name');
        document.getElementById('edit_service_name').value = serviceName || '';
    }

    // Modal functions
    function openModal() {
        document.getElementById('requirementModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('requirementModal').classList.remove('active');
        document.body.style.overflow = 'auto';
        document.getElementById('requirementForm').reset();
        
        // Reset to single row
        const tbody = document.getElementById('requirementsTableBody');
        tbody.innerHTML = `
            <tr class="requirement-row">
                <td>
                    <input type="text" name="requirement_name[]" class="requirement-name" placeholder="e.g., Balloons" required>
                </td>
                <td>
                    <textarea name="description[]" placeholder="Optional description"></textarea>
                </td>
                <td>
                    <input type="number" name="quantity[]" min="0" value="1" required>
                </td>
                <td>
                    <input type="text" name="unit[]" placeholder="e.g., pieces" required>
                </td>
                <td>
                    <select name="status[]" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </td>
                <td>
                    <button type="button" class="btn-remove-row" onclick="removeRow(this)" style="display: none;">×</button>
                </td>
            </tr>
        `;
    }

    function editRequirement(id) {
        window.location.href = 'requirements.php?edit=' + id;
    }

    function closeEditModal() {
        window.location.href = 'requirements.php';
    }

    function deleteRequirement(id) {
        document.getElementById('delete_requirement_id').value = id;
        document.getElementById('deleteModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Export to Excel
    function exportExcel() {
        var table = document.querySelector('.clients-table');
        var rows = table.querySelectorAll('tr');
        var xls = [];

        for (var i = 0; i < rows.length; i++) {
            var row = [],
                cols = rows[i].querySelectorAll('td, th');

            for (var j = 0; j < cols.length - 1; j++) { // Exclude last column (actions)
                var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                data = data.replace(/"/g, '""');
                row.push('"' + data + '"');
            }

            xls.push(row.join(','));
        }

        var xls_string = xls.join('\n');
        var filename = 'requirements_export_' + new Date().toLocaleDateString() + '.xls';
        var link = document.createElement('a');
        link.style.display = 'none';
        link.setAttribute('target', '_blank');
        link.setAttribute('href', 'data:text/xls;charset=utf-8,' + encodeURIComponent(xls_string));
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Print table
    function printTable() {
        window.print();
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('.clients-table tbody tr');

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchValue) ? '' : 'none';
        });
    });

    // Modal click outside to close
    document.getElementById('requirementModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Auto-open edit modal if editing
    <?php if ($edit_requirement): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('requirementEditModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    <?php endif; ?>

    // Initialize remove buttons visibility on load
    document.addEventListener('DOMContentLoaded', function() {
        updateRemoveButtons();
    });
</script>