<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

// Handle Status Toggle
if (isset($_POST['toggle_status'])) {
    $vendor_id = intval($_POST['vendor_id']);
    $current_status = $_POST['current_status'];
    $new_status = ($current_status == 'Active') ? 'Inactive' : 'Active';

    $toggle_sql = "UPDATE vendors SET status='$new_status', updated_at=NOW() WHERE vendor_id='$vendor_id'";

    if (mysqli_query($conn, $toggle_sql)) {
        header('Location: vendors.php?success=status_updated');
        exit();
    }
}

// Handle Vendor Update
if (isset($_POST['update_vendor'])) {
    $vendor_id = intval($_POST['vendor_id']);
    $vendor_name = mysqli_real_escape_string($conn, trim($_POST['vendor_name']));
    $service_type = mysqli_real_escape_string($conn, trim($_POST['service_type']));
    $contact_phone = mysqli_real_escape_string($conn, trim($_POST['contact_phone']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $country = mysqli_real_escape_string($conn, trim($_POST['country']));
    $state = mysqli_real_escape_string($conn, trim($_POST['state']));
    $city = mysqli_real_escape_string($conn, trim($_POST['city']));
    $status = mysqli_real_escape_string($conn, trim($_POST['status']));
    if (empty($vendor_name) || empty($service_type) || empty($contact_phone) || empty($country) || empty($state) || empty($city)) {

        $error = 'Please fill in all required fields.';
    } else {
        $update_vendor = "UPDATE vendors SET 
                         vendor_name='$vendor_name', service_type='$service_type', contact_phone='$contact_phone', address='$address', country='$country', state='$state', 
                         city='$city', status='$status', updated_at=NOW() 
                         WHERE vendor_id='$vendor_id'";

        if (mysqli_query($conn, $update_vendor)) {
            header('Location: vendors.php?success=updated');
            exit();
        } else {
            $error = 'Error: ' . mysqli_error($conn);
        }
    }
}

// Handle Vendor Add
if (isset($_POST['submit'])) {
    $vendor_name = mysqli_real_escape_string($conn, trim($_POST['vendor_name']));
    $service_type = mysqli_real_escape_string($conn, trim($_POST['service_type']));
    $contact_phone = mysqli_real_escape_string($conn, trim($_POST['contact_phone']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $country = mysqli_real_escape_string($conn, trim($_POST['country']));
    $state = mysqli_real_escape_string($conn, trim($_POST['state']));
    $city = mysqli_real_escape_string($conn, trim($_POST['city']));
    $status = mysqli_real_escape_string($conn, trim($_POST['status']));
    if (empty($vendor_name) || empty($service_type) || empty($contact_phone) || empty($country) || empty($state) || empty($city)) {
        $error = 'Please fill in all required fields.';
    } else {
        $sql = "INSERT INTO vendors(vendor_name, service_type, contact_phone, address, country, state, city, status) 
                VALUES('$vendor_name', '$service_type', '$contact_phone', '$address', '$country', '$state', '$city', '$status')";

        if (mysqli_query($conn, $sql)) {
            header('Location: vendors.php?success=added');
            exit();
        } else {
            $error = 'Please try again: ' . mysqli_error($conn);
        }
    }
}

// Fetch vendor for editing
$edit_vendor = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_sql = "SELECT * FROM vendors WHERE vendor_id='$edit_id'";
    $edit_result = mysqli_query($conn, $edit_sql);
    if ($edit_result && mysqli_num_rows($edit_result) > 0) {
        $edit_vendor = mysqli_fetch_assoc($edit_result);
    }
}

// Fetch vendor for viewing
$view_vendor = null;
if (isset($_GET['view'])) {
    $view_id = intval($_GET['view']);
    $view_sql = "SELECT * FROM vendors WHERE vendor_id='$view_id'";
    $view_result = mysqli_query($conn, $view_sql);
    if ($view_result && mysqli_num_rows($view_result) > 0) {
        $view_vendor = mysqli_fetch_assoc($view_result);
    }
}

// Fetch all locations data for cascading dropdowns
$locations_query = "SELECT country, state, city FROM locations ORDER BY country, state, city";
$locations_result = mysqli_query($conn, $locations_query);

// Store locations in arrays
$locations_data = [];
while ($location = mysqli_fetch_assoc($locations_result)) {
    $locations_data[] = $location;
}

// Get unique countries
$countries = array_unique(array_column($locations_data, 'country'));
sort($countries);

// Fetch all services
$services_query = "SELECT name FROM services_category WHERE category='Main' AND status='Active' ORDER BY name ASC";
$services_result = mysqli_query($conn, $services_query);

// Fetch all vendors
$vendors_query = "SELECT * FROM vendors ORDER BY vendor_id DESC";
$vendors_result = mysqli_query($conn, $vendors_query);
?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .clients-container {
        background: #f1f5f9;
    }

    .page-header {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
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

    .btn-print {
        display: flex;
        align-items: center;
        gap: 8px;
        background: white;
        color: #64748b;
        padding: 12px 24px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-right: 12px;
    }

    .btn-print:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .btn-print svg {
        width: 18px;
        height: 18px;
    }

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

    .table-container {
        overflow: auto;
        background: white;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
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
        padding: 16px 24px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .clients-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s ease;
    }

    .clients-table tbody tr:hover {
        background: #f8fafc;
    }

    .clients-table td {
        padding: 20px 24px;
        font-size: 14px;
        color: #1e293b;
    }

    .client-name {
        font-weight: 600;
        color: #1e293b;
    }

    .contact-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #64748b;
    }

    .contact-item svg {
        width: 14px;
        height: 14px;
        color: #94a3b8;
    }

    .location-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #f1f5f9;
        border-radius: 8px;
        font-size: 13px;
        color: #475569;
    }

    .location-badge svg {
        width: 14px;
        height: 14px;
    }

    .service-badge {
        display: inline-flex;
        padding: 6px 12px;
        background: #dbeafe;
        color: #1e40af;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
    }

    .status-badge {
        display: inline-flex;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
    }

    .status-badge.active {
        background: #d1fae5;
        color: #065f46;
    }

    .status-badge.inactive {
        background: #f3f4f6;
        color: #6b7280;
    }

    /* Toggle Button Styles - Similar to banner.php */
    .btn-toggle {
        display: inline-block;
        padding: 6px 16px;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: 'Lexend', sans-serif;
    }

    .btn-toggle.active {
        background: #d1fae5;
        color: #065f46;
    }

    .btn-toggle.active:hover {
        background: #a7f3d0;
    }

    .btn-toggle.inactive {
        background: #f3f4f6;
        color: #6b7280;
    }

    .btn-toggle.inactive:hover {
        background: #e5e7eb;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-action.view {
        background: #e0f2fe;
        color: #0369a1;
    }

    .btn-action.view:hover {
        background: #bae6fd;
        transform: translateY(-2px);
    }

    .btn-action.edit {
        background: #fef3c7;
        color: #92400e;
    }

    .btn-action.edit:hover {
        background: #fde68a;
        transform: translateY(-2px);
    }

    .btn-action svg {
        width: 16px;
        height: 16px;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(4px);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        padding: 24px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .modal.active {
        display: flex;
        opacity: 1;
    }

    .modal-content {
        background: white;
        border-radius: 16px;
        width: 100%;
        max-width: 70%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        background-color: #0ea5e9;
        padding: 24px;
        border-bottom: 1px solid #e2e8f0;
    }

    .modal-title {
        font-family: 'Outfit', sans-serif;
        font-size: 24px;
        font-weight: 700;
        color: #ffffff;
    }

    .modal-body {
        padding: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #475569;
    }

    .form-group label .required {
        color: #ef4444;
        margin-left: 2px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-family: 'Lexend', sans-serif;
        color: #1e293b;
        transition: all 0.2s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    .form-group select:disabled {
        background: #f1f5f9;
        color: #94a3b8;
        cursor: not-allowed;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 16px;
    }

    .modal-footer {
        padding: 24px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .btn-cancel,
    .btn-submit {
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
    }

    .btn-cancel {
        background: #f1f5f9;
        color: #64748b;
    }

    .btn-cancel:hover {
        background: #e2e8f0;
    }

    .btn-submit {
        background: #0ea5e9;
        color: white;
    }

    .btn-submit:hover {
        background: #0284c7;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
    }

    .alert {
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
        font-family: 'Lexend', sans-serif;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .view-modal-content {
        max-width: 70%;
    }

    .info-grid {
        display: grid;
        gap: 24px;
    }

    .info-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .info-label {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        font-size: 15px;
        color: #1e293b;
        font-weight: 500;
    }

    .info-item.full-width {
        grid-column: span 2;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        .clients-container {
            padding: 0;
        }

        .topbar {
            display: none;
        }

        .table-container {
            box-shadow: none;
        }

        .clients-table th,
        .clients-table td {
            padding: 12px;
            font-size: 12px;
        }

        body {
            background: white;
        }
    }

    .print-header {
        display: none;
        text-align: center;
        margin-bottom: 30px;
    }

    @media print {
        .print-header {
            display: block;
        }

        .print-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 32px;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .print-header p {
            color: #64748b;
            font-size: 14px;
        }
    }
</style>

<div class="main-content">
    <div class="clients-container">
        <div class="page-header no-print">

            <div class="search-bar no-print">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.35-4.35" />
                </svg>
                <input type="text" id="searchInput" placeholder="Search vendors...">
            </div>

            <button class="btn-add-client" onclick="openModal()">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 5v14m-7-7h14" stroke="white" stroke-width="2" stroke-linecap="round" />
                </svg>
                Add Vendor
            </button>

            <button class="btn-add-client" onclick="exportExcel()" style="background: #f0fdf4;
    color: #15803d;
    border-color: #bbf7d0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
                Export
            </button>

            <button class="btn-add-client" onclick="printTable()" style="    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9" />
                    <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" />
                    <rect x="6" y="14" width="12" height="8" />
                </svg>
                Print
            </button>

        </div>

        <div class="print-header">
            <h1>Vendors List</h1>
            <p>Generated on: <?php echo date('F d, Y'); ?></p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success no-print">
                <?php
                if ($_GET['success'] == 'added') echo 'Vendor added successfully!';
                else if ($_GET['success'] == 'updated') echo 'Vendor updated successfully!';
                else if ($_GET['success'] == 'status_updated') echo 'Vendor status updated successfully!';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error no-print">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table class="clients-table">
                <thead>
                    <tr>
                        <th>Sr.no</th>
                        <th>Vendor Name</th>
                        <th>Service Type</th>
                        <th>Contact Information</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sr = 1; ?>
                    <?php while ($vendor = mysqli_fetch_assoc($vendors_result)): ?>
                        <tr>
                            <td><?php echo $sr++ ?></td>

                            <td>
                                <div class="client-name"><?php echo htmlspecialchars($vendor['vendor_name']); ?></div>
                            </td>
                            <td>
                                <span class="service-badge"><?php echo htmlspecialchars($vendor['service_type']); ?></span>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <div class="contact-item">
                                        <?php echo htmlspecialchars($vendor['contact_phone']); ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="location-badge">
                                    <?php echo htmlspecialchars($vendor['city'] . ', ' . $vendor['state']); ?>
                                </div>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="vendor_id" value="<?php echo $vendor['vendor_id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $vendor['status']; ?>">
                                    <button type="submit" name="toggle_status" class="btn-toggle <?php echo strtolower($vendor['status']); ?>">
                                        <?php echo $vendor['status']; ?>
                                    </button>
                                </form>
                            </td>
                            <td class="no-print">
                                <div class="action-buttons">
                                    <button class="btn-action view" onclick="viewVendor(<?php echo $vendor['vendor_id']; ?>)">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </button>
                                    <button class="btn-action edit" onclick="editVendor(<?php echo $vendor['vendor_id']; ?>)">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="vendorModal" class="modal <?php echo $edit_vendor ? 'active' : ''; ?>">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><?php echo $edit_vendor ? 'Edit Vendor' : 'Add New Vendor'; ?></h2>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <?php if ($edit_vendor): ?>
                    <input type="hidden" name="vendor_id" value="<?php echo $edit_vendor['vendor_id']; ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label>Vendor Name <span class="required">*</span></label>
                        <input type="text" name="vendor_name" value="<?php echo $edit_vendor ? htmlspecialchars($edit_vendor['vendor_name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Service Type <span class="required">*</span></label>
                        <select name="service_type" required>
                            <option value="">--Select Service--</option>
                            <?php
                            mysqli_data_seek($services_result, 0);
                            while ($service = mysqli_fetch_assoc($services_result)):
                            ?>
                                <option value="<?php echo htmlspecialchars($service['name']); ?>"
                                    <?php echo ($edit_vendor && $edit_vendor['service_type'] == $service['name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Contact Phone <span class="required">*</span></label>
                        <input type="text" name="contact_phone" value="<?php echo $edit_vendor ? htmlspecialchars($edit_vendor['contact_phone']) : ''; ?>" maxlength="10" required>
                    </div>

                    <div class="form-group">
                        <label>Status <span class="required">*</span></label>
                        <select name="status" required>
                            <option value="Active" <?php echo ($edit_vendor && $edit_vendor['status'] == 'Active') ? 'selected' : (!$edit_vendor ? 'selected' : ''); ?>>Active</option>
                            <option value="Inactive" <?php echo ($edit_vendor && $edit_vendor['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                </div>

                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" value="<?php echo $edit_vendor ? htmlspecialchars($edit_vendor['address']) : ''; ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Country <span class="required">*</span></label>
                        <input type="text" name="country" id="country" value="<?php echo $edit_vendor ? htmlspecialchars($edit_vendor['country']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>State</span></label>
                        <input type="text" name="state" id="state" value="<?php echo $edit_vendor ? htmlspecialchars($edit_vendor['state']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>City <span class="required">*</span></label>
                        <input type="text" name="city" id="city" value="<?php echo $edit_vendor ? htmlspecialchars($edit_vendor['city']) : ''; ?>">
                    </div>
                </div>


            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="<?php echo $edit_vendor ? 'update_vendor' : 'submit'; ?>" class="btn-submit">
                    <?php echo $edit_vendor ? 'Update Vendor' : 'Add Vendor'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($view_vendor): ?>
    <div id="viewModal" class="modal active">
        <div class="modal-content view-modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Vendor Details</h2>
            </div>
            <div class="modal-body" id="vendorDetailsPrint">
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Vendor Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($view_vendor['vendor_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Service Type</span>
                            <span class="info-value">
                                <span class="service-badge"><?php echo htmlspecialchars($view_vendor['service_type']); ?></span>
                            </span>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Contact Phone</span>
                            <span class="info-value"><?php echo htmlspecialchars($view_vendor['contact_phone']); ?></span>
                        </div>

                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="info-value">
                                <span class="status-badge <?php echo strtolower($view_vendor['status']); ?>">
                                    <?php echo htmlspecialchars($view_vendor['status']); ?>
                                </span>
                            </span>
                        </div>
                    </div>

                    <div class="info-item full-width">
                        <span class="info-label">Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($view_vendor['address'] ?: 'N/A'); ?></span>
                    </div>

                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">City</span>
                            <span class="info-value"><?php echo htmlspecialchars($view_vendor['city']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">State</span>
                            <span class="info-value"><?php echo htmlspecialchars($view_vendor['state']); ?></span>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Country</span>
                            <span class="info-value"><?php echo htmlspecialchars($view_vendor['country']); ?></span>
                        </div>
                    </div>

                    <div class="info-item full-width">
                        <span class="info-label">Created At</span>
                        <span class="info-value"><?php echo date('F d, Y - g:i A', strtotime($view_vendor['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeViewModal()">Close</button>
                <button type="button" class="btn-submit" onclick="printVendorDetails()">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 6px;">
                        <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z" />
                    </svg>
                    Print
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    const locationsData = <?php echo json_encode($locations_data); ?>;

    function openModal() {
        document.getElementById('vendorModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        window.location.href = 'vendors.php';
    }

    function viewVendor(id) {
        window.location.href = 'vendors.php?view=' + id;
    }

    function editVendor(id) {
        window.location.href = 'vendors.php?edit=' + id;
    }

    function closeViewModal() {
        window.location.href = 'vendors.php';
    }

    function printVendorDetails() {
        const printContent = document.getElementById('vendorDetailsPrint').innerHTML;
        const printWindow = window.open('', '', 'height=600,width=800');

        printWindow.document.write('<html><head><title>Vendor Details</title>');
        printWindow.document.write('<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Lexend:wght@300;400;500;600&display=swap" rel="stylesheet">');
        printWindow.document.write('<style>');
        printWindow.document.write('body { font-family: "Lexend", sans-serif; padding: 40px; }');
        printWindow.document.write('.info-grid { display: grid; gap: 24px; }');
        printWindow.document.write('.info-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }');
        printWindow.document.write('.info-item { display: flex; flex-direction: column; gap: 8px; }');
        printWindow.document.write('.info-label { font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; }');
        printWindow.document.write('.info-value { font-size: 15px; color: #1e293b; font-weight: 500; }');
        printWindow.document.write('.info-item.full-width { grid-column: span 2; }');
        printWindow.document.write('.service-badge { display: inline-flex; padding: 6px 12px; background: #dbeafe; color: #1e40af; border-radius: 8px; font-size: 13px; font-weight: 500; }');
        printWindow.document.write('.status-badge { display: inline-flex; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 500; }');
        printWindow.document.write('.status-badge.active { background: #d1fae5; color: #065f46; }');
        printWindow.document.write('.status-badge.inactive { background: #f3f4f6; color: #6b7280; }');
        printWindow.document.write('</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<h1 style="font-family: Outfit; font-size: 28px; margin-bottom: 30px; text-align: center;">Vendor Details</h1>');
        printWindow.document.write(printContent);
        printWindow.document.write('</body></html>');

        printWindow.document.close();
        printWindow.print();
    }

    document.getElementById('vendorModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    document.getElementById('viewModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeViewModal();
        }
    });

    document.getElementById('country')?.addEventListener('change', function() {
        const selectedCountry = this.value;
        const stateDropdown = document.getElementById('state');
        const cityDropdown = document.getElementById('city');

        stateDropdown.innerHTML = '<option value="">--Select State--</option>';
        cityDropdown.innerHTML = '<option value="">--Select State --</option>';
        cityDropdown.disabled = true;

        if (selectedCountry) {
            const states = [...new Set(
                locationsData
                .filter(loc => loc.country === selectedCountry)
                .map(loc => loc.state)
            )].sort();

            states.forEach(state => {
                const option = document.createElement('option');
                option.value = state;
                option.textContent = state;
                stateDropdown.appendChild(option);
            });

            stateDropdown.disabled = false;
        } else {
            stateDropdown.disabled = true;
            stateDropdown.innerHTML = '<option value="">--Select Country --</option>';
        }
    });

    document.getElementById('state')?.addEventListener('change', function() {
        const selectedCountry = document.getElementById('country').value;
        const selectedState = this.value;
        const cityDropdown = document.getElementById('city');

        cityDropdown.innerHTML = '<option value="">--Select City--</option>';

        if (selectedState && selectedCountry) {
            const cities = [...new Set(
                locationsData
                .filter(loc => loc.country === selectedCountry && loc.state === selectedState)
                .map(loc => loc.city)
            )].sort();

            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                cityDropdown.appendChild(option);
            });

            cityDropdown.disabled = false;
        } else {
            cityDropdown.disabled = true;
            cityDropdown.innerHTML = '<option value="">--Select State --</option>';
        }
    });

    <?php if ($edit_vendor): ?>

        function populateEditDropdowns() {
            const editCountry = '<?php echo addslashes($edit_vendor['country']); ?>';
            const editState = '<?php echo addslashes($edit_vendor['state']); ?>';
            const editCity = '<?php echo addslashes($edit_vendor['city']); ?>';

            const stateDropdown = document.getElementById('edit_state');
            const states = [...new Set(
                locationsData
                .filter(loc => loc.country === editCountry)
                .map(loc => loc.state)
            )].sort();

            stateDropdown.innerHTML = '<option value="">--Select State--</option>';
            states.forEach(state => {
                const option = document.createElement('option');
                option.value = state;
                option.textContent = state;
                if (state === editState) option.selected = true;
                stateDropdown.appendChild(option);
            });
            stateDropdown.disabled = false;

            const cityDropdown = document.getElementById('edit_city');
            const cities = [...new Set(
                locationsData
                .filter(loc => loc.country === editCountry && loc.state === editState)
                .map(loc => loc.city)
            )].sort();

            cityDropdown.innerHTML = '<option value="">--Select City--</option>';
            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                if (city === editCity) option.selected = true;
                cityDropdown.appendChild(option);
            });
            cityDropdown.disabled = false;
        }

        document.getElementById('edit_country')?.addEventListener('change', function() {
            const selectedCountry = this.value;
            const stateDropdown = document.getElementById('edit_state');
            const cityDropdown = document.getElementById('edit_city');

            stateDropdown.innerHTML = '<option value="">--Select State--</option>';
            cityDropdown.innerHTML = '<option value="">--Select State --</option>';
            cityDropdown.disabled = true;

            if (selectedCountry) {
                const states = [...new Set(
                    locationsData
                    .filter(loc => loc.country === selectedCountry)
                    .map(loc => loc.state)
                )].sort();

                states.forEach(state => {
                    const option = document.createElement('option');
                    option.value = state;
                    option.textContent = state;
                    stateDropdown.appendChild(option);
                });

                stateDropdown.disabled = false;
            } else {
                stateDropdown.disabled = true;
                stateDropdown.innerHTML = '<option value="">--Select Country --</option>';
            }
        });

        document.getElementById('edit_state')?.addEventListener('change', function() {
            const selectedCountry = document.getElementById('edit_country').value;
            const selectedState = this.value;
            const cityDropdown = document.getElementById('edit_city');

            cityDropdown.innerHTML = '<option value="">--Select City--</option>';

            if (selectedState && selectedCountry) {
                const cities = [...new Set(
                    locationsData
                    .filter(loc => loc.country === selectedCountry && loc.state === selectedState)
                    .map(loc => loc.city)
                )].sort();

                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    cityDropdown.appendChild(option);
                });

                cityDropdown.disabled = false;
            } else {
                cityDropdown.disabled = true;
                cityDropdown.innerHTML = '<option value="">--Select State --</option>';
            }
        });

        populateEditDropdowns();
    <?php endif; ?>

    document.getElementById('searchInput')?.addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('.clients-table tbody tr');

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchValue) ? '' : 'none';
        });
    });

    // Print Table Function
    function printTable() {
        const printWindow = window.open('', '', 'height=600,width=800');
        const table = document.querySelector('.clients-table').cloneNode(true);

        // Remove action column (last column)
        const headers = table.querySelectorAll('thead th');
        const rows = table.querySelectorAll('tbody tr');

        // Remove the last header (Actions)
        if (headers.length > 0) {
            headers[headers.length - 1].remove();
        }

        // Remove the last cell in each row (Actions)
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 0) {
                cells[cells.length - 1].remove();
            }
        });

        printWindow.document.write('<html><head><title>Vendors List</title>');
        printWindow.document.write('<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Lexend:wght@300;400;500;600&display=swap" rel="stylesheet">');
        printWindow.document.write('<style>');
        printWindow.document.write('body { font-family: "Lexend", sans-serif; padding: 40px; }');
        printWindow.document.write('h1 { font-family: "Outfit", sans-serif; text-align: center; margin-bottom: 30px; font-size: 32px; color: #000000; }');
        printWindow.document.write('.print-date { text-align: center; color: #000000; margin-bottom: 30px; }');
        printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 20px; }');
        printWindow.document.write('th, td { padding: 12px; text-align: left; border: 1px solid #000000; }');
        printWindow.document.write('th { background: #f8fafc; font-weight: 600; color: #000000; text-transform: uppercase; font-size: 12px; }');
        printWindow.document.write('td { color: #1e293b; font-size: 14px; }');
        printWindow.document.write('.status-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; }');
        printWindow.document.write('.status-badge.active { background: #d1fae5; color: #065f46; }');
        printWindow.document.write('.status-badge.inactive { background: #f3f4f6; color: #6b7280; }');
        printWindow.document.write('.service-badge { padding: 4px 10px; background: #dbeafe; color: #1e40af; border-radius: 6px; font-size: 12px; font-weight: 500; }');
        printWindow.document.write('</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<h1>Vendors List</h1>');
        printWindow.document.write('<p class="print-date">Generated on: ' + new Date().toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }) + '</p>');
        printWindow.document.write(table.outerHTML);
        printWindow.document.write('</body></html>');

        printWindow.document.close();
        setTimeout(() => {
            printWindow.print();
        }, 250);
    }

    // Export to Excel Function
    function exportExcel() {
        // FIXED: Use correct table selector
        const table = document.querySelector('.vendors-table') ||
            document.querySelector('table');

        if (!table) {
            alert('Error: Table not found!');
            console.error('Available tables:', document.querySelectorAll('table'));
            return;
        }

        const rows = Array.from(table.querySelectorAll('tr'));

        // Build CSV
        let csv = 'Vendors List\n';
        csv += 'Generated: ' + new Date().toLocaleDateString() + '\n\n';

        rows.forEach((row, idx) => {
            const cells = Array.from(row.querySelectorAll(idx === 0 ? 'th' : 'td'));
            const data = cells
                .slice(0, -1) // Remove last column (Actions)
                .map(cell => {
                    let text = cell.textContent.trim().replace(/\s+/g, ' ');
                    return text.includes(',') ? `"${text}"` : text;
                })
                .join(',');

            if (data) csv += data + '\n';
        });

        // Download
        const blob = new Blob([csv], {
            type: 'text/csv;charset=utf-8;'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `vendors_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    }

    // Auto-dismiss success messages after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    });
</script>