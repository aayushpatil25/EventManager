<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

// Check authentication (uncomment when you have login system)
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit();
// }
// Handle Client Update
if (isset($_POST['update_client'])) {
    $client_id = intval($_POST['client_id']);
    $name = mysqli_real_escape_string($conn, trim($_POST['client_name']));
    $contact = mysqli_real_escape_string($conn, trim($_POST['contact']));
    $address = mysqli_real_escape_string($conn, trim($_POST['client_address']));
    $state = mysqli_real_escape_string($conn, trim($_POST['state']));
    $city = mysqli_real_escape_string($conn, trim($_POST['city']));
    $pincode = mysqli_real_escape_string($conn, trim($_POST['pincode']));

    if (empty($name) || empty($contact) || empty($address) || empty($state) || empty($city)) {
        $error = 'Please fill in all required fields.';
    } else {
        $update_client = "UPDATE clients SET 
                         client_name='$name', contact='$contact', client_address='$address', 
                         state='$state', city='$city', pincode='$pincode', 
                         updated_at=NOW() 
                         WHERE client_id='$client_id'";

        if (mysqli_query($conn, $update_client)) {
            header('Location: clients.php?success=updated');
            exit();
        } else {
            $error = 'Error: ' . mysqli_error($conn);
        }
    }
}

// Handle Client Add
if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['client_name']));
    $contact = mysqli_real_escape_string($conn, trim($_POST['contact']));
    $address = mysqli_real_escape_string($conn, trim($_POST['client_address']));
    $state = mysqli_real_escape_string($conn, trim($_POST['state']));
    $city = mysqli_real_escape_string($conn, trim($_POST['city']));
    $pincode = mysqli_real_escape_string($conn, trim($_POST['pincode']));

    if (empty($name) || empty($contact) || empty($address) || empty($state) || empty($city)) {
        $error = 'Please fill in all required fields.';
    } else {
        $sql = "INSERT INTO clients(client_name, contact, client_address, state, city, pincode) 
                VALUES('$name', '$contact', '$address', '$state', '$city', '$pincode')";

        if (mysqli_query($conn, $sql)) {
            header('Location: clients.php?success=added');
            exit();
        } else {
            $error = 'Please try again: ' . mysqli_error($conn);
        }
    }
}

// Handle Client Deletion
if (isset($_POST['delete_client'])) {
    $client_id = intval($_POST['delete_client_id']);
    $delete_client = "DELETE FROM clients WHERE client_id='$client_id'";

    if (mysqli_query($conn, $delete_client)) {
        header('Location: clients.php?success=deleted');
        exit();
    } else {
        $error = 'Error: ' . mysqli_error($conn);
    }
}

// Fetch client for editing
$edit_client = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_sql = "SELECT * FROM clients WHERE client_id='$edit_id'";
    $edit_result = mysqli_query($conn, $edit_sql);
    if ($edit_result && mysqli_num_rows($edit_result) > 0) {
        $edit_client = mysqli_fetch_assoc($edit_result);
    }
}

// Fetch client for viewing
$view_client = null;
if (isset($_GET['view'])) {
    $view_id = intval($_GET['view']);
    $view_sql = "SELECT * FROM clients WHERE client_id='$view_id'";
    $view_result = mysqli_query($conn, $view_sql);
    if ($view_result && mysqli_num_rows($view_result) > 0) {
        $view_client = mysqli_fetch_assoc($view_result);

        // Get event count for this client
        $event_count_sql = "SELECT COUNT(*) as count FROM events WHERE client_name='" . mysqli_real_escape_string($conn, $view_client['client_name']) . "'";
        $event_count_result = mysqli_query($conn, $event_count_sql);
        $event_count = mysqli_fetch_assoc($event_count_result)['count'];
    }
}


// Fetch all unique states for dropdown
$states_query = "SELECT DISTINCT state FROM locations WHERE state IS NOT NULL AND state != '' ORDER BY state ASC";
$states_result = mysqli_query($conn, $states_query);

$states_list = [];
$locations_data = []; // ADD THIS LINE - Initialize locations_data array

while ($state = mysqli_fetch_assoc($states_result)) {
    $states_list[] = $state['state'];
    $locations_data[] = $state; // ADD THIS LINE - Store for JavaScript
}

// If no states found in database, use static list
$states_list = [
    'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
    'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka',
    'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram',
    'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu',
    'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
    'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu',
    'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry'
];

$locations_data = array_map(fn($s) => ['state' => $s], $states_list);

// Re-fetch clients after state query (cursor might have moved)
$clients_query = "SELECT * FROM clients ORDER BY client_id DESC";
$clients_result = mysqli_query($conn, $clients_query);
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
        padding: 12px 12px;
        text-align: center;
        font-size: 13px;
        font-weight: 600;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .clients-table td {
        text-align: center;
        padding: 10px;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
        font-size: 14px;
    }

    .clients-table tbody tr {
        transition: background 0.2s ease;
    }

    .clients-table tbody tr:hover {
        background: #f8fafc;
    }

    .client-name {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 4px;
    }

    .client-address {
        font-size: 13px;
        color: #64748b;
    }

    .contact-info {
        display: flex;
        flex-direction: column;
        align-items: center;
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

    .events-count {
        font-weight: 500;
        color: #0ea5e9;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
        justify-content: center;
    }

    .action-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #f1f5f9;
        color: #64748b;
        text-decoration: none;
    }

    .action-btn:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
    }

    .action-btn svg {
        width: 16px;
        height: 16px;
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
        width: 100%;
        max-width: 70%;
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
        text-decoration: none;
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
        padding: 20px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        text-decoration: none;
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
        text-decoration: none;
        display: inline-flex;
        align-items: center;
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

    .alert-info {
        background: #dbeafe;
        color: #1e40af;
        border: 2px solid #3b82f6;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 2px solid #dc2626;
    }

    .alert svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }

    /* View Modal Specific Styles */
    .view-detail-row {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .view-detail {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }

    .view-detail-row:last-child {
        border-bottom: none;
    }

    .view-label {
        font-weight: 600;
        color: #64748b;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .view-value {
        color: #1e293b;
        font-size: 15px;
    }

    .view-section-title {
        font-family: 'Outfit', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        padding-bottom: 12px;
        margin-bottom: 16px;
        margin-top: 24px;
        border-bottom: 2px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .view-section-title:first-child {
        margin-top: 0;
    }

    .view-section-title svg {
        width: 18px;
        height: 18px;
        fill: #0ea5e9;
    }

    .stat-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: #dbeafe;
        border-radius: 8px;
        font-size: 14px;
        color: #0369a1;
        font-weight: 600;
    }

    /* Print Styles */
    @media print {

        /* Hide unwanted UI */
        .sidebar,
        .page-header,
        .search-bar,
        .action-buttons,
        .btn-add-client,
        .modal-overlay,
        .no-print,
        .topbar{
            display: none !important;
        }

        /* Reset body */
        body {
            background: white !important;
            margin: 0;
            padding: 0;
        }

        /* Show print header */
        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 20px;
        }

        /* Table styles */
        .clients-container {
            padding: 0 !important;
        }

        .table-container {
            box-shadow: none !important;
            border-radius: 0 !important;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000 !important;
            padding: 8px;
            font-size: 12px;
            color: #000 !important;
        }

        th {
            background: #f3f4f6;
        }
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

        .view-detail-row {
            grid-template-columns: 1fr;
            gap: 8px;
        }
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
</style>

<main class="main-content">
    <div class="clients-container">
        <!-- Page Header -->
        <div class="page-header">

            <!-- Search Bar -->
            <div class="search-bar">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                </svg>
                <input type="text" placeholder="Search clients..." id="searchInput">
            </div>

            <button class="btn-add-client" onclick="openModal()">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                </svg>
                Add Client
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

        <!-- Alert Messages -->
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                </svg>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <?php if ($_GET['success'] == 'added'): ?>
                <div class="alert alert-success">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
                    </svg>
                    Client added successfully!
                </div>
            <?php elseif ($_GET['success'] == 'updated'): ?>
                <div class="alert alert-info">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
                    </svg>
                    Client updated successfully!
                </div>
            <?php elseif ($_GET['success'] == 'deleted'): ?>
                <div class="alert alert-info">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
                    </svg>
                    Client deleted successfully!
                </div>
            <?php endif; ?>
        <?php endif; ?>



        <!-- Clients Table -->
        <div class="table-container">
            <table class="clients-table" id="clientsTable">
                <thead>
                    <tr>
                        <th >Sr.no</th>
                        <th >Name</th>
                        <th >Contact</th>
                        <th >Location</th>
                        <th>Date</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sr = 1;
                    if (mysqli_num_rows($clients_result) > 0):
                        while ($client = mysqli_fetch_assoc($clients_result)):
                            // Get event count for each client
                            $event_count_sql = "SELECT count(*) as count FROM events WHERE client_name='" . mysqli_real_escape_string($conn, $client['client_name']) . "'";
                            $event_count_result = mysqli_query($conn, $event_count_sql);
                            $client_event_count = mysqli_fetch_assoc($event_count_result)['count'];
                    ?>
                            <tr>
                                <td><?php echo $sr++ ?></td>
                                <td>
                                    <div class="client-name"><?php echo htmlspecialchars($client['client_name']); ?></div>
                                </td>
                                <td>
                                    <div class="contact-info" >
                                        <div class="contact-item">
                                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path fill="currentColor" d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" />
                                            </svg>
                                            <?php echo htmlspecialchars($client['contact']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($client['client_address'] . ', ' . $client['state'] . ', ' . $client['city'] . ', ' . $client['pincode']); ?></td>
                                <!-- <td><span class="events-count"><?php echo $client_event_count; ?> events</span></td> -->
                                <td><?php echo date('d M Y', strtotime($client['created_at'] ?? 'now')); ?></td>
                                <td class="no-print">
                                    <div class="action-buttons">
                                        <a href="clients.php?view=<?php echo $client['client_id']; ?>" class="btn-action view" title="View">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                        </a>
                                        <a href="clients.php?edit=<?php echo $client['client_id']; ?>" class="btn-action edit" title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                        </a>

                                    </div>
                                </td>
                            </tr>
                        <?php
                        endwhile;
                    else:
                        ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                No clients found. Click "Add Client" to create your first client.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add Client Modal -->
<div class="modal-overlay" id="clientModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Add New Client</h2>
            <button class="modal-close" onclick="closeModal()">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </button>
        </div>
        <form action="" method="post" id="clientForm">
            <div class="modal-body">
                <div class="form-grid">
                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <h4>
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                            </svg>
                            Personal Information
                        </h4>
                    </div>

                    <div class="form-group">
                        <label for="client_name">Client Name </label>
                        <input type="text" name="client_name" id="client_name" placeholder="Enter full name">
                    </div>

                    <div class="form-group">
                        <label for="contact">Contact Number </label>
                        <input type="tel" name="contact" id="contact" placeholder="Enter contact number" maxlength="10">
                    </div>

                    <!-- Address Section -->
                    <div class="form-section">
                        <h4>
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                            </svg>
                            Address Details
                        </h4>
                    </div>

                    <div class="form-group full-width">
                        <label for="client_address"> Address </label>
                        <input type="text" name="client_address" id="client_address" placeholder="Enter street address" required>
                    </div>

                    <div class="form-group">
                        <label for="state">State</label>
                        <select name="state" id="state" required>
                            <option value="">--Select State--</option>
                            <?php foreach ($states_list as $state): ?>
                                <option value="<?php echo htmlspecialchars($state); ?>">
                                    <?php echo htmlspecialchars($state); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="city">City </label>
                        <input type="text" name="city" id="city">
                    </div>

                    <div class="form-group">
                        <label for="pincode">Pincode</label>
                        <input type="text" name="pincode" id="pincode" maxlength="6">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="submit" class="btn-submit">Add Client</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Client Modal -->
<div class="modal-overlay" id="clientEditModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Edit Client</h2>
            <a href="clients.php" class="modal-close">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </a>
        </div>
        <form action="" method="post" id="clientEditForm">
            <input type="hidden" name="client_id" value="<?php echo $edit_client ? htmlspecialchars($edit_client['client_id']) : ''; ?>">
            <div class="modal-body">
                <div class="form-grid">
                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <h4>
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                            </svg>
                            Personal Information
                        </h4>
                    </div>

                    <div class="form-group">
                        <label for="edit_client_name">Client Name *</label>
                        <input type="text" name="client_name" id="edit_client_name"
                            value="<?php echo $edit_client ? htmlspecialchars($edit_client['client_name']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_contact">Contact Number *</label>
                        <input type="tel" name="contact" id="edit_contact"
                            value="<?php echo $edit_client ? htmlspecialchars($edit_client['contact']) : ''; ?>" maxlength="10" required>
                    </div>

                    <!-- Address Section -->
                    <div class="form-section">
                        <h4>
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                            </svg>
                            Address Details
                        </h4>
                    </div>

                    <div class="form-group full-width">
                        <label for="edit_client_address">Street Address *</label>
                        <input type="text" name="client_address" id="edit_client_address"
                            value="<?php echo $edit_client ? htmlspecialchars($edit_client['client_address']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_state">State *</label>
                        <select name="state" id="edit_state" required>
                            <option value="">--Select State--</option>
                            <?php foreach ($states_list as $state): ?>
                                <option value="<?php echo htmlspecialchars($state); ?>"
                                    <?php echo ($edit_client && $edit_client['state'] == $state) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($state); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_city">City *</label>
                        <input type="text" name="city" id="edit_city" value="<?php echo $edit_client ? htmlspecialchars($edit_client['city']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="edit_pincode">Pincode</label>
                        <input type="text" name="pincode" id="edit_pincode"
                            value="<?php echo $edit_client ? htmlspecialchars($edit_client['pincode']) : ''; ?>" maxlength="6">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="clients.php" class="btn-cancel">Cancel</a>
                <button type="submit" name="update_client" class="btn-submit">Update Client</button>
            </div>
        </form>
    </div>
</div>

<!-- View Client Modal -->
<div class="modal-overlay" id="clientViewModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Client Details</h2>
            <a href="clients.php" class="modal-close">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </a>
        </div>
        <div class="modal-body" id="clientDetailsPrint">
            <?php if ($view_client): ?>
                <div class="view-section-title">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                    </svg>
                    Personal Information
                </div>
                <div class="view-detail">
                    <div class="view-detail-row">
                        <div class="view-label">Client Name</div>
                        <div class="view-value"><?php echo htmlspecialchars($view_client['client_name']); ?></div>
                    </div>

                    <div class="view-detail-row">
                        <div class="view-label">Contact</div>
                        <div class="view-value"><?php echo htmlspecialchars($view_client['contact']); ?></div>
                    </div>

                    <div class="view-detail-row">
                        <div class="view-label">Events</div>
                        <div class="view-value">
                            <span class="stat-badge"><?php echo $event_count; ?> Events</span>
                        </div>
                    </div>
                </div>
                <div class="view-section-title">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                    </svg>
                    Address Details
                </div>

                <div class="view-detail-row">
                    <div class="view-label">Street Address</div>
                    <div class="view-value full-width"><?php echo htmlspecialchars($view_client['client_address']); ?></div>
                </div>
                <div class="view-detail">
                    <div class="view-detail-row">
                        <div class="view-label">City</div>
                        <div class="view-value"><?php echo htmlspecialchars($view_client['city']); ?></div>
                    </div>

                    <div class="view-detail-row">
                        <div class="view-label">State</div>
                        <div class="view-value"><?php echo htmlspecialchars($view_client['state']); ?></div>
                    </div>

                    <div class="view-detail-row">
                        <div class="view-label">Pincode</div>
                        <div class="view-value"><?php echo htmlspecialchars($view_client['pincode'] ?: 'N/A'); ?></div>
                    </div>
                </div>
                <div class="view-section-title">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" />
                    </svg>
                    Timeline
                </div>
                <div class="view-detail">
                    <div class="view-detail-row">
                        <div class="view-label">Joined</div>
                        <div class="view-value"><?php echo date('d M Y, h:i A', strtotime($view_client['created_at'])); ?></div>
                    </div>

                    <div class="view-detail-row">
                        <div class="view-label">Last Updated</div>
                        <div class="view-value"><?php echo date('d M Y, h:i A', strtotime($view_client['updated_at'])); ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <a href="clients.php" class="btn-cancel">Close</a>
            <?php if ($view_client): ?>
                <button type="button" class="btn-submit" onclick="printClientDetails()" style="background: #0ea5e9;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 6px;">
                        <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z" />
                    </svg>
                    Print
                </button>

            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <h2 class="modal-title">Confirm Delete</h2>
            <button class="modal-close" onclick="closeDeleteModal()">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </button>
        </div>
        <form action="" method="post" id="deleteForm">
            <input type="hidden" name="delete_client_id" id="delete_client_id">
            <div class="modal-body">
                <p style="font-size: 15px; color: #64748b; margin: 0;">
                    Are you sure you want to delete this client? This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" name="delete_client" class="btn-submit" style="background: #dc2626;">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Pass PHP locations data to JavaScript -->
<script>
    // Store all locations data in JavaScript
    const locationsData = <?php echo json_encode($locations_data); ?>;

    // Auto-open edit modal if editing
    <?php if ($edit_client): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('clientEditModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    <?php endif; ?>

    // Auto-open view modal if viewing
    <?php if ($view_client): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('clientViewModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    <?php endif; ?>

    function openModal() {
        document.getElementById('clientModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('clientModal').classList.remove('active');
        document.body.style.overflow = 'auto';
        document.getElementById('clientForm').reset();
    }

    // Delete Modal Functions
    function deleteClient(id) {
        document.getElementById('delete_client_id').value = id;
        document.getElementById('deleteModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Print Client Details Function
    function printClientDetails() {
        const printContent = document.getElementById('clientDetailsPrint').innerHTML;
        const printWindow = window.open('', '', 'height=600,width=800');

        printWindow.document.write('<html><head><title>Client Details</title>');
        printWindow.document.write('<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Lexend:wght@300;400;500;600&display=swap" rel="stylesheet">');
        printWindow.document.write('<style>');
        printWindow.document.write('body { font-family: "Lexend", sans-serif; padding: 40px; }');
        printWindow.document.write('.view-section-title { font-family: "Outfit", sans-serif; font-size: 18px; font-weight: 600; color: #1e293b; padding-bottom: 12px; margin-bottom: 16px; margin-top: 24px; border-bottom: 2px solid #e2e8f0; display: flex; align-items: center; gap: 8px; }');
        printWindow.document.write('.view-section-title:first-child { margin-top: 0; }');
        printWindow.document.write('.view-section-title svg { width: 18px; height: 18px; fill: #0ea5e9; }');
        printWindow.document.write('.view-detail { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }');
        printWindow.document.write('.view-detail-row { display: flex; flex-direction: column; gap: 8px; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }');
        printWindow.document.write('.view-detail-row:last-child { border-bottom: none; }');
        printWindow.document.write('.view-label { font-weight: 600; color: #64748b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }');
        printWindow.document.write('.view-value { color: #1e293b; font-size: 15px; }');
        printWindow.document.write('.stat-badge { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #dbeafe; border-radius: 8px; font-size: 14px; color: #0369a1; font-weight: 600; }');
        printWindow.document.write('</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<h1 style="font-family: Outfit; font-size: 28px; margin-bottom: 30px; text-align: center;">Client Details</h1>');
        printWindow.document.write(printContent);
        printWindow.document.write('</body></html>');

        printWindow.document.close();
        printWindow.print();
    }

    // Export to Excel Function
    function exportExcel() {
        var table = document.getElementById('clientsTable');
        var rows = table.querySelectorAll('tr');
        var csv = [];

        for (var i = 0; i < rows.length; i++) {
            var row = [],
                cols = rows[i].querySelectorAll('td, th');

            // Skip the Actions column (last column)
            for (var j = 0; j < cols.length - 1; j++) {
                // Get text content and clean it
                var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/(\s\s)/gm, ' ');
                data = data.replace(/"/g, '""'); // Escape double quotes
                row.push('"' + data + '"');
            }

            csv.push(row.join(','));
        }

        var csv_string = csv.join('\n');
        var filename = 'clients_export_' + new Date().toLocaleDateString() + '.csv';

        // Create download link
        var link = document.createElement('a');
        link.style.display = 'none';
        link.setAttribute('target', '_blank');
        link.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv_string));
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Print Table Function
    function printTable() {
    const header = document.createElement('div');
    header.id = 'print-client-header';
    header.innerHTML = `
        <div style="text-align:center; padding:4px 0 10px; border-bottom:2px solid #0ea5e9; margin-bottom:16px;">
            <h2 style="font-family:'Outfit',sans-serif; font-size:22px; font-weight:700; color:#1e293b; margin:0;">
                Client Details
            </h2>
            <p style="font-size:12px; color:#64748b; margin:4px 0 0;">
                Printed on: ${new Date().toLocaleDateString('en-IN', {day:'2-digit', month:'long', year:'numeric'})}
            </p>
        </div>
    `;
    document.querySelector('.table-container').prepend(header);
    window.print();
    setTimeout(() => {
        document.getElementById('print-client-header')?.remove();
    }, 100);
}

    // Close modal when clicking outside
    document.getElementById('clientModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('.clients-table tbody tr');

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchValue) ? '' : 'none';
        });
    });
</script>