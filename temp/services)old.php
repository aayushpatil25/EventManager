<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Add Service
if (isset($_POST['submit'])) {
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Check if multiple services are being submitted
    if (isset($_POST['multiple_services']) && !empty($_POST['multiple_services'])) {
        $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;
        $services_array = json_decode($_POST['multiple_services'], true);

        if (is_array($services_array) && count($services_array) > 0) {
            $success_count = 0;
            $error_count = 0;

            foreach ($services_array as $service_name) {
                $service_name = trim($service_name);
                if (!empty($service_name)) {
                    $stmt = $conn->prepare("INSERT INTO services(name, parent_id, is_active) VALUES(?, ?, ?)");
                    $stmt->bind_param("sii", $service_name, $parent_id, $is_active);

                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                    $stmt->close();
                }
            }

            if ($success_count > 0) {
                header('Location: services.php?success=1&added=' . $success_count);
                exit();
            } else {
                header('Location: services.php?error=db');
                exit();
            }
        }
    } else {
        // Single service submission
        $name = trim($_POST['name']);
        $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;

        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO services(name, parent_id, is_active) VALUES(?, ?, ?)");
        $stmt->bind_param("sii", $name, $parent_id, $is_active);

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: services.php?success=1');
            exit();
        } else {
            $stmt->close();
            header('Location: services.php?error=db');
            exit();
        }
    }
}

// Handle AJAX: fetch subcategories for view modal
if (isset($_GET['fetch_subcategories']) && isset($_GET['category_id'])) {
    header('Content-Type: application/json');
    $cat_id = intval($_GET['category_id']);
    $result = mysqli_query($conn, "SELECT name, is_active FROM services WHERE parent_id = $cat_id ORDER BY name ASC");
    $subs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $subs[] = $row;
    }
    echo json_encode($subs);
    exit();
}

// Handle Update Service
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validate inputs
    if (empty($name)) {
        header('Location: services.php?error=name_required');
        exit();
    }

    // Prevent circular reference (service cannot be its own parent)
    if ($parent_id == $id) {
        header('Location: services.php?error=circular_reference');
        exit();
    }

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("UPDATE services SET name = ?, parent_id = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("siii", $name, $parent_id, $is_active, $id);

    if ($stmt->execute()) {
        $stmt->close();
        header('Location: services.php?updated=1');
        exit();
    } else {
        $stmt->close();
        header('Location: services.php?error=update_failed');
        exit();
    }
}

// Fetch all parent services (categories)
$parent_services_query = "SELECT * FROM services WHERE parent_id IS NULL ORDER BY name ASC";
$parent_services_result = mysqli_query($conn, $parent_services_query);
?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .clients-container {
        padding: 32px;
        background: #f1f5f9;
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
        font-size: 15px;
        color: #64748b;
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

    /* Search Bar */
    .search-bar {
        position: relative;
        margin-bottom: 24px;
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

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 2px solid #ef4444;
    }

    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        backdrop-filter: blur(4px);
        animation: fadeIn 0.3s ease;
    }

    .modal-overlay.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal {
        background: white;
        border-radius: 20px;
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease;
    }

    .modal-header {
        background-color: #0284c7;
        padding: 24px 32px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-family: 'Outfit', sans-serif;
        font-size: 24px;
        font-weight: 700;
        color: #ffffff;
    }

    .modal-close {
        width: 36px;
        height: 36px;
        border: none;
        background: #ff6666;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        color: #ffffff;
    }

    .modal-close:hover {
        background: #ff3333;
        transform: rotate(90deg);
    }

    .modal-close svg {
        width: 20px;
        height: 20px;
    }

    .modal-body {
        padding: 32px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-group label {
        font-weight: 600;
        margin-bottom: 8px;
        color: #334155;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-group input[type="text"],
    .form-group input[type="tel"],
    .form-group input[type="number"],
    .form-group select {
        width: 100%;
        padding: 12px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-family: 'Lexend', sans-serif;
        transition: all 0.2s ease;
        background-color: #f8fafc;
    }

    .form-group select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        padding-right: 40px;
    }

    .form-group select:disabled {
        background-color: #e2e8f0;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #0ea5e9;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    .form-section {
        grid-column: 1 / -1;
        margin-top: 8px;
        margin-bottom: 4px;
    }

    .form-section h4 {
        font-family: 'Outfit', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        padding-bottom: 8px;
        border-bottom: 2px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-section svg {
        width: 18px;
        height: 18px;
        fill: #0ea5e9;
    }

    .modal-footer {
        padding: 20px 32px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .btn-cancel {
        padding: 12px 24px;
        border: 2px solid #e2e8f0;
        background: white;
        color: #64748b;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-cancel:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .btn-submit {
        padding: 12px 24px;
        background: #0ea5e9;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
    }

    .btn-submit:hover {
        background: #0284c7;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
    }

    /* Alert Messages */
    .alert {
        padding: 14px 18px;
        border-radius: 12px;
        margin-bottom: 24px;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 2px solid #10b981;
    }

    .alert svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }

    /* Table Container */
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
        background: #0ea5e9;
        border-bottom: 1px solid #e2e8f0;
    }

    .clients-table th {
        padding: 16px 20px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .clients-table td {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
        font-size: 14px;
        vertical-align: top;
    }

    .clients-table tbody tr {
        transition: background 0.2s ease;
    }

    .clients-table tbody tr:hover {
        background: #f8fafc;
    }

    .category-name {
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
    }

    /* Subcategories List in Table */
    .subcategories-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .subcategory-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .subcategory-item:hover {
        background: #e0f2fe;
        border-color: #0ea5e9;
    }

    .subcategory-name-text {
        font-size: 13px;
        font-weight: 500;
        color: #1e293b;
    }

    .no-subcategories {
        font-size: 13px;
        color: #94a3b8;
        font-style: italic;
    }

    .btn-subcategory-edit {
        padding: 4px 6px;
        background: transparent;
        border: none;
        color: #64748b;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-subcategory-edit:hover {
        background: #dbeafe;
        color: #0284c7;
    }

    .btn-subcategory-edit svg {
        width: 14px;
        height: 14px;
    }

    /* Table Action Buttons */
    .action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-table-action {
        padding: 8px 14px;
        border: none;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
        font-family: 'Lexend', sans-serif;
    }

    .btn-table-action svg {
        width: 14px;
        height: 14px;
    }

    .btn-table-action.add {
        background: #d1fae5;
        color: #065f46;
    }

    .btn-table-action.add:hover {
        background: #a7f3d0;
        transform: translateY(-2px);
    }

    .btn-table-action.edit {
        background: #fef3c7;
        color: #92400e;
    }

    .btn-table-action.edit:hover {
        background: #bfdbfe;
        transform: translateY(-2px);
    }

    /* Empty State */
    .empty-row {
        text-align: center;
        padding: 60px 20px !important;
        color: #94a3b8;
    }

    .empty-row svg {
        display: block;
        margin: 0 auto 12px;
    }

    /* Status Badges */
    .status-badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-active {
        background-color: #dcfce7;
        color: #16a34a;
    }

    .status-inactive {
        background-color: #fee2e2;
        color: #dc2626;
    }

    /* Toggle Switch */
    .toggle-label {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        font-size: 14px;
        color: #1e293b;
        font-weight: 500;
    }

    .toggle-switch {
        position: relative;
        width: 48px;
        height: 24px;
    }

    .toggle-switch input[type="checkbox"] {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: 0.3s;
        border-radius: 24px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
    }

    .toggle-switch input:checked+.toggle-slider {
        background-color: #10b981;
    }

    .toggle-switch input:checked+.toggle-slider:before {
        transform: translateX(24px);
    }

    .toggle-text {
        font-size: 14px;
        color: #64748b;
    }

    /* Info Text */
    .info-text {
        font-size: 13px;
        color: #64748b;
        font-style: italic;
        margin-top: 4px;
    }

    /* Subcategory Input Rows */
    .subcategory-input-row {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-bottom: 8px;
    }

    .subcategory-input-row input {
        flex: 1;
        padding: 12px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-family: 'Lexend', sans-serif;
        background-color: #f8fafc;
        transition: all 0.2s ease;
    }

    .subcategory-input-row input:focus {
        outline: none;
        border-color: #0ea5e9;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    .btn-remove-subcategory {
        padding: 10px;
        background: #fee2e2;
        color: #dc2626;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 44px;
        font-weight: 600;
    }

    .btn-remove-subcategory:hover {
        background: #fecaca;
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(40px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .modal {
            width: 95%;
            max-height: 95vh;
        }

        .modal-body {
            padding: 24px;
        }

        .clients-table th,
        .clients-table td {
            padding: 12px;
            font-size: 13px;
        }

        .subcategories-list {
            flex-direction: column;
        }

        .action-buttons {
            flex-direction: column;
        }

        .btn-table-action {
            width: 100%;
            justify-content: center;
        }
    }

    .btn-add-service {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #0ea5e9;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-table-action.view {
        background: #e0f2fe;
        color: #0369a1;
    }

    .btn-table-action.view:hover {
        background: #ede9fe;
        transform: translateY(-2px);
    }

    /* View Modal Specifics */
    .view-detail-row {
        display: flex;
        align-items: flex-start;
        padding: 14px 0;
        border-bottom: 1px solid #f1f5f9;
        gap: 16px;
    }

    .view-detail-row:last-child {
        border-bottom: none;
    }

    .view-detail-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #64748b;
        min-width: 140px;
    }

    .view-detail-value {
        font-size: 14px;
        color: #1e293b;
        font-weight: 500;
        flex: 1;
    }

    .view-subcategories-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .view-subcategory-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 20px;
        font-size: 13px;
        color: #0369a1;
        font-weight: 500;
    }

    .view-modal-header-icon {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 16px;
    }

    .view-modal-header-wrap {
        display: flex;
        align-items: center;
    }

    .view-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .view-stat-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }

    .view-stat-number {
        font-size: 28px;
        font-weight: 700;
        color: #0ea5e9;
        font-family: 'Outfit', sans-serif;
    }

    .view-stat-label {
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 4px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .page-header-left {
        flex-shrink: 0;
    }

    .page-header-right {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* Search Bar */
    .search-bar {
        position: relative;
        width: 220px;
    }

    .search-bar input {
        width: 100%;
        padding: 12px 14px 12px 38px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13.5px;
        font-family: 'Lexend', sans-serif;
        background: white;
        transition: all 0.2s ease;
        box-sizing: border-box;
        color: #1e293b;
    }

    .search-bar input:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);

    }

    .search-bar svg {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        width: 16px;
        height: 16px;
        color: #94a3b8;
        pointer-events: none;
        
    }

    /* Status Filter */
    .filter-select {
        padding: 12px 32px 12px 12px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13.5px;
        font-family: 'Lexend', sans-serif;
        background: white;
        color: #334155;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        transition: all 0.2s ease;
    }

    .filter-select:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    /* Results Count */
    .results-count {
        font-size: 13px;
        color: #64748b;
        padding: 5px 12px;
        background: #f1f5f9;
        border-radius: 20px;
        font-weight: 600;
        white-space: nowrap;
    }

    /* Vertical Divider */
    .header-divider {
        width: 1px;
        height: 32px;
        background: #e2e8f0;
        border-radius: 2px;
        flex-shrink: 0;
    }

    /* Export & Print buttons */
    .btn-header-action {
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 10px 16px;
        border-radius: 10px;
        font-size: 13.5px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        border: 1.5px solid transparent;
    }

    .btn-header-action svg {
        width: 15px;
        height: 15px;
        flex-shrink: 0;
    }

    .btn-header-action:hover {
        transform: translateY(-1px);
    }

    .btn-header-action.export {
        background: #f0fdf4;
        color: #15803d;
        border-color: #bbf7d0;
        padding: 12px 24px;
    }

    .btn-header-action.export:hover {
        background: #dcfce7;
        border-color: #86efac;
        box-shadow: 0 2px 8px rgba(21, 128, 61, 0.15);
    }

    .btn-header-action.print {
        background: #eff6ff;
        color: #1d4ed8;
        border-color: #bfdbfe;
        padding: 12px 24px;
    }

    .btn-header-action.print:hover {
        background: #dbeafe;
        border-color: #93c5fd;
        box-shadow: 0 2px 8px rgba(29, 78, 216, 0.15);
    }

    /* Responsive collapse */
    @media (max-width: 1100px) {
        .search-bar {
            width: 180px;
        }

        .btn-header-action span {
            display: none;
        }

        .btn-header-action {
            padding: 10px 12px;
        }
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .page-header-right {
            width: 100%;
        }

        .search-bar {
            width: 100%;
            flex: 1;
        }
    }

    /* Highlight matched text */
    .highlight {
        background: #fef08a;
        border-radius: 2px;
        padding: 0 2px;
        font-weight: 600;
        color: #713f12;
    }

    /* No results */
    .no-results-row td {
        text-align: center;
        padding: 52px 20px !important;
        color: #94a3b8;
    }

    /* Print */
    @media print {
        body * {
            visibility: hidden;
        }

        #printArea,
        #printArea * {
            visibility: visible;
        }

        #printArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        .action-buttons,
        .btn-subcategory-edit {
            display: none !important;
        }

        #printHeader {
            display: flex !important;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #0ea5e9;
        }

        .print-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
        }

        .print-header span {
            font-size: 12px;
            color: #64748b;
        }
    }

    /* replace: .print-header { display: none; } */
    #printHeader {
        display: none;
    }
</style>

<main class="main-content">
    <div class="clients-container">
        <!-- Page Header -->
        <!-- Unified Page Header with all controls -->
        <div class="page-header">

            <div class="page-header-right">
                <!-- Search -->
                <div class="search-bar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input type="text" id="searchInput" placeholder="Search…" oninput="applyFilters()">
                </div>

                <!-- Status Filter -->
                <select class="filter-select" id="statusFilter" onchange="applyFilters()">
                    <option value="">All Statuses</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>

                <!-- Add Category -->
                <button class="btn-add-client" onclick="openModal()">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                    </svg>
                    Add Service
                </button>

                <!-- Export -->
                <button class="btn-header-action export" onclick="exportCSV()" title="Export CSV">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
                        <polyline points="7 10 12 15 17 10" />
                        <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                    <span>Export</span>
                </button>

                <!-- Print -->
                <button class="btn-header-action print" onclick="printTable()" title="Print">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9" />
                        <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" />
                        <rect x="6" y="14" width="12" height="8" />
                    </svg>
                    <span>Print</span>
                </button>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
                </svg>
                <?php
                if (isset($_GET['added']) && $_GET['added'] > 1) {
                    echo $_GET['added'] . ' subcategories added successfully!';
                } else {
                    echo 'Service added successfully!';
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                </svg>
                <?php
                $error_msg = 'An error occurred. Please try again.';
                switch ($_GET['error']) {
                    case 'name_required':
                        $error_msg = 'Service name is required.';
                        break;
                    case 'circular_reference':
                        $error_msg = 'A service cannot be its own parent.';
                        break;
                    case 'update_failed':
                        $error_msg = 'Failed to update service. Please try again.';
                        break;
                    case 'db':
                        $error_msg = 'Database error occurred. Please try again.';
                        break;
                }
                echo $error_msg;
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
            <div class="alert alert-success">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
                </svg>
                Service updated successfully!
            </div>
        <?php endif; ?>

        <!-- Categories Display -->
        <div id="printArea">
            <div style="display:none;" id="printHeader" class="print-header">
                <h2>Services Management Report</h2>
                <span id="printDate"></span>
            </div>
            <div class="table-container">
                <table class="clients-table" id="servicesTable">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Sr.No</th>
                            <th>Category</th>
                            <th style="width: 45%;">Subcategories</th>
                            <th style="width: 100px;">Status</th>
                            <th style="width: 175px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch all parent services (categories)
                        $categories_query = "SELECT * FROM services WHERE parent_id IS NULL ORDER BY name ASC";
                        $categories_result = mysqli_query($conn, $categories_query);

                        if (mysqli_num_rows($categories_result) > 0) {
                            $sr = 1;
                            while ($category = mysqli_fetch_assoc($categories_result)) {
                                // Fetch subcategories for this category
                                $subcategories_query = "SELECT * FROM services WHERE parent_id = " . intval($category['id']) . " ORDER BY name ASC";
                                $subcategories_result = mysqli_query($conn, $subcategories_query);
                        ?>
                                <tr>
                                    <td><?php echo $sr++; ?></td>
                                    <td>
                                        <div class="category-name">
                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="subcategories-list">
                                            <?php
                                            if (mysqli_num_rows($subcategories_result) > 0) {
                                                while ($subcategory = mysqli_fetch_assoc($subcategories_result)) {
                                            ?>
                                                    <div class="subcategory-item">
                                                        <span class="subcategory-name-text"><?php echo htmlspecialchars($subcategory['name']); ?></span>
                                                        <!-- <span class="status-badge status-<?php echo $subcategory['is_active'] ? 'active' : 'inactive'; ?>">
                                                        <?php echo $subcategory['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span> -->
                                                        <button
                                                            class="btn-subcategory-edit"
                                                            data-id="<?php echo intval($subcategory['id']); ?>"
                                                            data-name="<?php echo htmlspecialchars($subcategory['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-active="<?php echo $subcategory['is_active']; ?>"
                                                            data-parent="<?php echo $subcategory['parent_id']; ?>"
                                                            onclick="editService(this)"
                                                            title="Edit subcategory">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                                                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                <?php
                                                }
                                            } else {
                                                ?>
                                                <span class="no-subcategories">No subcategories</span>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $category['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button
                                                class="btn-table-action add"
                                                onclick="addSubcategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')"
                                                title="Add Subcategory">
                                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                                                </svg>
                                            </button>
                                            <button
                                                class="btn-table-action view"
                                                onclick="viewService(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>', <?php echo $category['is_active']; ?>)"
                                                title="View Category">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                            </button>
                                            <button
                                                class="btn-table-action edit"
                                                data-id="<?php echo intval($category['id']); ?>"
                                                data-name="<?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-active="<?php echo $category['is_active']; ?>"
                                                data-parent=""
                                                onclick="editService(this)"
                                                title="Edit Category">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="5" class="empty-row">
                                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 48px; height: 48px; margin-bottom: 12px; color: #cbd5e1;">
                                        <path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                                    </svg>
                                    <div>No categories yet. Click "Add Category" to create your first service category.</div>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add Service Modal -->
<div class="modal-overlay" id="clientModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title" id="addModalTitle">Add Category</h2>
            <button class="modal-close" onclick="closeModal()">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </button>
        </div>
        <form action="" method="post" id="clientForm" onsubmit="return submitMultipleServices()">
            <input type="hidden" name="parent_id" id="parent_id" value="">
            <input type="hidden" name="multiple_services" id="multiple_services_input" value="">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-section">
                        <h4>
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor" d="M4 8h4V4H4v4zm6 12h4v-4h-4v4zm-6 0h4v-4H4v4zm0-6h4v-4H4v4zm6 0h4v-4h-4v4zm6-10v4h4V4h-4zm-6 4h4V4h-4v4zm6 6h4v-4h-4v4zm0 6h4v-4h-4v4z" />
                            </svg>
                            Service Details
                        </h4>
                    </div>

                    <div class="form-group full-width" id="parentCategoryInfo" style="display: none;">
                        <div style="background: #dbeafe; padding: 12px; border-radius: 8px; color: #1e40af; font-size: 14px;">
                            <strong>Parent Category:</strong> <span id="parentCategoryName"></span>
                        </div>
                    </div>

                    <!-- Single service input (for main category) -->
                    <div class="form-group full-width" id="singleServiceInput">
                        <label for="name">Service Name *</label>
                        <input type="text" name="name" id="name" placeholder="Enter service name">
                        <span class="info-text" id="serviceTypeLabel">This will be a main category</span>
                    </div>

                    <!-- Multiple services input (for subcategories) -->
                    <div class="form-group full-width" id="multipleServicesInput" style="display: none;">
                        <label>Subcategories *</label>
                        <div id="subcategoriesContainer">
                            <!-- Subcategory rows will be added here dynamically -->
                        </div>
                        <button type="button" class="btn-add-service" onclick="addSubcategoryRow()" style="margin-top: 12px;">
                            Add
                        </button>
                    </div>

                    <div class="form-group">
                        <label class="toggle-label">
                            <span>Status</span>
                            <div class="toggle-switch">
                                <input type="checkbox" name="is_active" id="is_active" checked>
                                <span class="toggle-slider"></span>
                            </div>
                            <span class="toggle-text">Active</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="submit" class="btn-submit" id="submitBtn">Add Service</button>
            </div>
        </form>
    </div>
</div>

<!-- View Service Modal -->
<div class="modal-overlay" id="viewModal">
    <div class="modal">
        <div class="modal-header">
            <div class="view-modal-header-wrap">
                <div class="view-modal-header-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:24px;height:24px;fill:white;">
                        <path d="M4 8h4V4H4v4zm6 12h4v-4h-4v4zm-6 0h4v-4H4v4zm0-6h4v-4H4v4zm6 0h4v-4h-4v4zm6-10v4h4V4h-4zm-6 4h4V4h-4v4zm6 6h4v-4h-4v4zm0 6h4v-4h-4v4z" />
                    </svg>
                </div>
                <div>
                    <h2 class="modal-title" id="viewModalTitle">Category Details</h2>
                    <p style="color: rgba(255,255,255,0.75); font-size: 13px; margin-top: 2px;">Service category information</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeViewModal()">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </button>
        </div>
        <div class="modal-body">

            <!-- Stats Grid -->
            <!-- <div class="view-stats-grid">
                <div class="view-stat-card">
                    <div class="view-stat-number" id="viewSubcategoryCount">0</div>
                    <div class="view-stat-label">Subcategories</div>
                </div>
                <div class="view-stat-card">
                    <div class="view-stat-number" id="viewActiveSubcategoryCount">0</div>
                    <div class="view-stat-label">Active Subcategories</div>
                </div>
            </div> -->

            <!-- Details -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 8px 20px;">

                <div class="view-detail-row">
                    <span class="view-detail-label">Category Name</span>
                    <span class="view-detail-value" id="viewCategoryName">—</span>
                </div>

                <div class="view-detail-row">
                    <span class="view-detail-label">Status</span>
                    <span class="view-detail-value" id="viewCategoryStatus">—</span>
                </div>

                <div class="view-detail-row">
                    <span class="view-detail-label">Subcategories</span>
                    <div class="view-detail-value">
                        <div class="view-subcategories-wrap" id="viewSubcategoriesList">
                            <span style="color:#94a3b8; font-style:italic;">Loading...</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeViewModal()">Close</button>
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Edit Service</h2>
            <button class="modal-close" onclick="closeEditModal()">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </button>
        </div>
        <form action="" method="post" id="editForm">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="parent_id" id="edit_parent_id">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-section">
                        <h4>
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor" d="M4 8h4V4H4v4zm6 12h4v-4h-4v4zm-6 0h4v-4H4v4zm0-6h4v-4H4v4zm6 0h4v-4h-4v4zm6-10v4h4V4h-4zm-6 4h4V4h-4v4zm6 6h4v-4h-4v4zm0 6h4v-4h-4v4z" />
                            </svg>
                            Service Details
                        </h4>
                    </div>

                    <div class="form-group full-width">
                        <label for="edit_name">Service Name *</label>
                        <input type="text" name="name" id="edit_name" placeholder="Enter service name" required>
                    </div>

                    <div class="form-group">
                        <label class="toggle-label">
                            <span>Status</span>
                            <div class="toggle-switch">
                                <input type="checkbox" name="is_active" id="edit_is_active">
                                <span class="toggle-slider"></span>
                            </div>
                            <span class="toggle-text">Active</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" name="update" class="btn-submit">Update Service</button>
            </div>
        </form>
    </div>
</div>

<script>
    let subcategoryRows = [];

    // Open Add Service Modal (for main category)
    function openModal() {
        document.getElementById('parent_id').value = '';
        document.getElementById('parentCategoryInfo').style.display = 'none';
        document.getElementById('serviceTypeLabel').textContent = 'This will be a main category';
        document.getElementById('addModalTitle').textContent = 'Add Category';

        // Show single input, hide multiple inputs
        document.getElementById('singleServiceInput').style.display = 'block';
        document.getElementById('multipleServicesInput').style.display = 'none';
        document.getElementById('name').required = true;

        document.getElementById('clientModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Close Add Service Modal
    function closeModal() {
        document.getElementById('clientModal').classList.remove('active');
        document.body.style.overflow = 'auto';
        document.getElementById('clientForm').reset();
        document.getElementById('parent_id').value = '';
        document.getElementById('parentCategoryInfo').style.display = 'none';
        subcategoryRows = [];
        document.getElementById('subcategoriesContainer').innerHTML = '';
    }

    // Open Add Subcategory Modal (Multiple inputs mode)
    function addSubcategory(parentId, parentName) {
        document.getElementById('parent_id').value = parentId;
        document.getElementById('parentCategoryName').textContent = parentName;
        document.getElementById('parentCategoryInfo').style.display = 'block';
        document.getElementById('serviceTypeLabel').textContent = 'This will be a subcategory';
        document.getElementById('addModalTitle').textContent = 'Add Subcategories';
        document.getElementById('submitBtn').textContent = 'Add Subcategories';

        // Hide single input, show multiple inputs
        document.getElementById('singleServiceInput').style.display = 'none';
        document.getElementById('multipleServicesInput').style.display = 'block';
        document.getElementById('name').required = false;

        // Initialize with one empty row
        subcategoryRows = [''];
        renderSubcategoryRows();

        document.getElementById('clientModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Add a new subcategory input row
    function addSubcategoryRow() {
        subcategoryRows.push('');
        renderSubcategoryRows();
    }

    // Remove a subcategory input row
    function removeSubcategoryRow(index) {
        if (subcategoryRows.length > 1) {
            subcategoryRows.splice(index, 1);
            renderSubcategoryRows();
        } else {
            alert('At least one subcategory field is required');
        }
    }

    // Update subcategory value
    function updateSubcategoryValue(index, value) {
        subcategoryRows[index] = value;
    }

    // Render all subcategory input rows
    function renderSubcategoryRows() {
        const container = document.getElementById('subcategoriesContainer');
        container.innerHTML = subcategoryRows.map((value, index) => `
            <div class="subcategory-input-row">
                <input 
                    type="text" 
                    placeholder="Enter subcategory name ${index + 1}" 
                    value="${value}"
                    onchange="updateSubcategoryValue(${index}, this.value)"
                    required>
                <button 
                    type="button" 
                    class="btn-remove-subcategory" 
                    onclick="removeSubcategoryRow(${index})"
                    title="Remove this subcategory">
                    ×
                </button>
            </div>
        `).join('');
    }

    // Submit form - handle multiple subcategories
    function submitMultipleServices() {
        const parentId = document.getElementById('parent_id').value;

        // If it's a subcategory (multiple mode)
        if (parentId && document.getElementById('multipleServicesInput').style.display !== 'none') {
            // Filter out empty values
            const validSubcategories = subcategoryRows.filter(name => name.trim() !== '');

            if (validSubcategories.length === 0) {
                alert('Please enter at least one subcategory name');
                return false;
            }

            // Store in hidden input as JSON
            document.getElementById('multiple_services_input').value = JSON.stringify(validSubcategories);
            return true;
        }

        // Normal single service submission
        return true;
    }

    // Open Edit Service Modal
    function openEditModal() {
        document.getElementById('editModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Close Edit Service Modal
    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
        document.body.style.overflow = 'auto';
        document.getElementById('editForm').reset();
    }

    // Edit Service Function
    function editService(button) {
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const isActive = button.getAttribute('data-active');
        const parentId = button.getAttribute('data-parent');

        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_is_active').checked = (isActive === '1');
        document.getElementById('edit_parent_id').value = parentId || '';

        // Update toggle text
        const toggleText = document.querySelector('#editModal .toggle-text');
        toggleText.textContent = (isActive === '1') ? 'Active' : 'Inactive';

        openEditModal();
    }

    // Close Add modal when clicking outside
    document.getElementById('clientModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Close Edit modal when clicking outside
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    // Update toggle text based on checkbox state
    document.getElementById('is_active').addEventListener('change', function() {
        const toggleText = this.closest('.toggle-label').querySelector('.toggle-text');
        toggleText.textContent = this.checked ? 'Active' : 'Inactive';
    });

    document.getElementById('edit_is_active').addEventListener('change', function() {
        const toggleText = this.closest('.toggle-label').querySelector('.toggle-text');
        toggleText.textContent = this.checked ? 'Active' : 'Inactive';
    });

    // Open View Modal
    function viewService(id, name, isActive) {
        document.getElementById('viewModalTitle').textContent = name;
        document.getElementById('viewCategoryName').textContent = name;
        document.getElementById('viewCategoryStatus').innerHTML = isActive ?
            '<span class="status-badge status-active">Active</span>' :
            '<span class="status-badge status-inactive">Inactive</span>';

        // Reset counts
        // document.getElementById('viewSubcategoryCount').textContent = '...';
        // document.getElementById('viewActiveSubcategoryCount').textContent = '...';
        document.getElementById('viewSubcategoriesList').innerHTML = '<span style="color:#94a3b8;font-style:italic;">Loading...</span>';

        document.getElementById('viewModal').classList.add('active');
        document.body.style.overflow = 'hidden';

        // Fetch subcategories via AJAX
        fetch(`services.php?fetch_subcategories=1&category_id=${id}`)
            .then(res => res.json())
            .then(subs => {
                // document.getElementById('viewSubcategoryCount').textContent = subs.length;
                // const activeCount = subs.filter(s => s.is_active == 1).length;
                // document.getElementById('viewActiveSubcategoryCount').textContent = activeCount;

                const list = document.getElementById('viewSubcategoriesList');
                if (subs.length === 0) {
                    list.innerHTML = '<span style="color:#94a3b8;font-style:italic;">No subcategories found</span>';
                } else {
                    list.innerHTML = subs.map(s => `
                    <span class="view-subcategory-pill">
                        ${s.name}
                        
                    </span>
                `).join('');
                }
            })
            .catch(() => {
                document.getElementById('viewSubcategoriesList').innerHTML = '<span style="color:#dc2626;">Failed to load subcategories.</span>';
            });
    }

    // Close View Modal
    function closeViewModal() {
        document.getElementById('viewModal').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Close view modal when clicking outside
    document.getElementById('viewModal').addEventListener('click', function(e) {
        if (e.target === this) closeViewModal();
    });


    // ── Search & Filter ──────────────────────────────────────────

    function applyFilters() {
        const query = document.getElementById('searchInput').value.trim().toLowerCase();
        const status = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('#servicesTable tbody tr:not(.no-results-row)');

        // Remove any previous no-results row
        const existingNoResults = document.querySelector('.no-results-row');
        if (existingNoResults) existingNoResults.remove();

        let visibleCount = 0;

        rows.forEach(row => {
            if (row.classList.contains('empty-row')) return;

            const categoryNameEl = row.querySelector('.category-name strong');
            const subcategoryEls = row.querySelectorAll('.subcategory-name-text');
            const statusBadge = row.querySelector('td:nth-child(4) .status-badge');

            const categoryName = categoryNameEl ? categoryNameEl.textContent.toLowerCase() : '';
            const subcategoryNames = Array.from(subcategoryEls).map(el => el.textContent.toLowerCase()).join(' ');
            const rowStatus = statusBadge ? (statusBadge.classList.contains('status-active') ? '1' : '0') : '';

            const matchesSearch = !query || categoryName.includes(query) || subcategoryNames.includes(query);
            const matchesStatus = !status || rowStatus === status;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;

                // Highlight matched text
                if (query) {
                    highlightText(categoryNameEl, query);
                    subcategoryEls.forEach(el => highlightText(el, query));
                } else {
                    clearHighlight(categoryNameEl);
                    subcategoryEls.forEach(el => clearHighlight(el));
                }
            } else {
                row.style.display = 'none';
            }
        });

        // Update count badge
        document.getElementById('resultsCount').textContent =
            visibleCount === 1 ? '1 category' : `${visibleCount} categories`;

        // Show no-results message if needed
        if (visibleCount === 0) {
            const tbody = document.querySelector('#servicesTable tbody');
            const noRow = document.createElement('tr');
            noRow.className = 'no-results-row';
            noRow.innerHTML = `
            <td colspan="5">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                     style="width:40px;height:40px;color:#cbd5e1;margin-bottom:10px;">
                    <path fill="currentColor"
                          d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5
                             6.5 6.5 0 109.5 16c1.61 0 3.09-.59
                             4.23-1.57l.27.28v.79l5 4.99L20.49
                             19l-4.99-5zm-6 0C7.01 14 5 11.99 5
                             9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                </svg>
                <div>No categories match your search or filter.</div>
            </td>`;
            tbody.appendChild(noRow);
        }
    }

    function highlightText(el, query) {
        if (!el) return;
        const original = el.getAttribute('data-original') || el.textContent;
        el.setAttribute('data-original', original);
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        el.innerHTML = original.replace(regex, '<span class="highlight">$1</span>');
    }

    function clearHighlight(el) {
        if (!el) return;
        const original = el.getAttribute('data-original');
        if (original) {
            el.textContent = original;
            el.removeAttribute('data-original');
        }
    }

    // Init count on page load
    document.addEventListener('DOMContentLoaded', () => {
        const total = document.querySelectorAll('#servicesTable tbody tr:not(.empty-row)').length;
        document.getElementById('resultsCount').textContent =
            total === 1 ? '1 category' : `${total} categories`;
    });


    // ── Export CSV ────────────────────────────────────────────────

    function exportCSV() {
        const rows = document.querySelectorAll('#servicesTable tbody tr');
        const headers = ['Sr.No', 'Category', 'Subcategories', 'Status'];
        const csvRows = [headers.join(',')];

        rows.forEach(row => {
            if (row.style.display === 'none') return;
            if (row.classList.contains('empty-row') || row.classList.contains('no-results-row')) return;

            const cells = row.querySelectorAll('td');
            if (cells.length < 4) return;

            const sr = cells[0].textContent.trim();
            const category = cells[1].querySelector('strong')?.textContent.trim() || '';
            const subcats = Array.from(cells[2].querySelectorAll('.subcategory-name-text'))
                .map(el => el.textContent.trim()).join(' | ') || 'None';
            const status = cells[3].querySelector('.status-badge')?.textContent.trim() || '';

            csvRows.push([
                `"${sr}"`,
                `"${category.replace(/"/g, '""')}"`,
                `"${subcats.replace(/"/g, '""')}"`,
                `"${status}"`
            ].join(','));
        });

        const blob = new Blob([csvRows.join('\n')], {
            type: 'text/csv;charset=utf-8;'
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `services_${new Date().toISOString().slice(0,10)}.csv`;
        link.click();
        URL.revokeObjectURL(url);
    }


    // ── Print ─────────────────────────────────────────────────────

    function printTable() {
        document.getElementById('printDate').textContent =
            'Generated: ' + new Date().toLocaleString();
        window.print();
    }
</script>