<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

// Handle Event Update
if (isset($_POST['update_event'])) {
    $event_id = intval($_POST['event_id']);
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $client_name = mysqli_real_escape_string($conn, trim($_POST['client_name']));
    $start_date = mysqli_real_escape_string($conn, trim($_POST['start_date']));
    $end_date = mysqli_real_escape_string($conn, trim($_POST['end_date']));
    $start_time = mysqli_real_escape_string($conn, trim($_POST['start_time']));
    $end_time = mysqli_real_escape_string($conn, trim($_POST['end_time']));
    $venue = mysqli_real_escape_string($conn, trim($_POST['venue']));
    $budget = intval($_POST['budget']);
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));

    // Handle service details
    $service_details = [];
    if (isset($_POST['selected_services']) && !empty($_POST['selected_services'])) {
        $services_array = json_decode($_POST['selected_services'], true);
        if (is_array($services_array) && count($services_array) > 0) {
            $service_details = $services_array;
        }
    }
    $service_json = json_encode($service_details);

    $update_event = "UPDATE events SET 
                    name='$name', client_name='$client_name', start_date='$start_date', end_date='$end_date', 
                    start_time='$start_time', end_time='$end_time', venue='$venue', budget='$budget',
                    service='$service_json', description='$description', 
                    updated_at=NOW() 
                    WHERE id='$event_id'";

    if (mysqli_query($conn, $update_event)) {
        header('Location: events.php?success=updated');
        exit();
    } else {
        $error = 'Error: ' . mysqli_error($conn);
    }
}

// Handle Add Event
if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $client_name = mysqli_real_escape_string($conn, trim($_POST['client_name']));
    $start_date = mysqli_real_escape_string($conn, trim($_POST['start_date']));
    $end_date = mysqli_real_escape_string($conn, trim($_POST['end_date']));
    $start_time = mysqli_real_escape_string($conn, trim($_POST['start_time']));
    $end_time = mysqli_real_escape_string($conn, trim($_POST['end_time']));
    $venue = mysqli_real_escape_string($conn, trim($_POST['venue']));
    $budget = intval($_POST['budget']);
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));

    // Handle service details
    $service_details = [];
    if (isset($_POST['selected_services']) && !empty($_POST['selected_services'])) {
        $services_array = json_decode($_POST['selected_services'], true);
        if (is_array($services_array) && count($services_array) > 0) {
            $service_details = $services_array;
        }
    }

    // Convert to JSON - if empty, store empty array
    $service_json = json_encode($service_details);

    // Debug: Uncomment to see what's being saved
    // error_log("Services JSON: " . $service_json);
    // die("DEBUG - Services: " . $service_json . " | POST data: " . $_POST['selected_services']);

    $sql = "INSERT INTO events(name, client_name, start_date, end_date, start_time, end_time, venue, budget, service, description) 
            VALUES('$name', '$client_name', '$start_date', '$end_date', '$start_time', '$end_time', '$venue', '$budget', '$service_json', '$description')";

    if (mysqli_query($conn, $sql)) {
        header('Location: events.php?success=added');
        exit();
    } else {
        $error = 'Please try again: ' . mysqli_error($conn);
    }
}

// Handle Event Deletion
if (isset($_POST['delete_event'])) {
    $event_id = intval($_POST['delete_event_id']);
    $delete_event = "DELETE FROM events WHERE id='$event_id'";

    if (mysqli_query($conn, $delete_event)) {
        header('Location: events.php?success=deleted');
        exit();
    } else {
        $error = 'Error: ' . mysqli_error($conn);
    }
}

// Fetch event for editing
$edit_event = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_sql = "SELECT * FROM events WHERE id='$edit_id'";
    $edit_result = mysqli_query($conn, $edit_sql);
    if ($edit_result && mysqli_num_rows($edit_result) > 0) {
        $edit_event = mysqli_fetch_assoc($edit_result);
    }
}

// Fetch clients for dropdown
$client_sql = "SELECT * FROM clients ORDER BY client_name ASC";
$client_result = mysqli_query($conn, $client_sql);

// Fetch services for dropdown
$service_sql = "SELECT * FROM services WHERE name != '' ORDER BY name ASC";
$service_result = mysqli_query($conn, $service_sql);

// Fetch all events
$events_sql = "SELECT * FROM events ORDER BY id DESC";
$events_result = mysqli_query($conn, $events_sql);
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
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .clients-table td {
        padding: 20px;
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

    .event-name {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 4px;
    }

    .client-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #ede9fe;
        border-radius: 8px;
        font-size: 13px;
        color: #6b21a8;
        font-weight: 500;
    }

    .service-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #dbeafe;
        border-radius: 8px;
        font-size: 13px;
        color: #1e40af;
        font-weight: 500;
        margin: 2px;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
    }

    .action-btn {
        padding: 8px 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #64748b;
        background: #f1f5f9;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
    }

    .action-btn:hover {
        transform: translateY(-2px);
    }

    .edit-btn {
        background: #dbeafe !important;
        color: #0284c7 !important;
    }

    .edit-btn:hover {
        background: #bfdbfe !important;
        color: #0369a1 !important;
    }

    .delete-btn {
        background: #fee2e2 !important;
        color: #dc2626 !important;
    }

    .delete-btn:hover {
        background: #fecaca !important;
        color: #b91c1c !important;
    }

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
        overflow-x: auto;
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
        padding: 10px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }



    .form-group.full-widths {
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
    .form-group input[type="date"],
    .form-group input[type="time"],
    .form-group input[type="number"],
    .form-group textarea,
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

    .form-group textarea {
        min-height: 100px;
        resize: vertical;
    }

    .form-group select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        padding-right: 40px;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #0ea5e9;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
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
    }

    .btn-submit:hover {
        background: #0284c7;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
    }

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

    .empty-row {
        text-align: center;
        padding: 60px 20px !important;
        color: #94a3b8;
    }

    /* Service Selection Styles */
    .services-section {
        margin-bottom: 20px;
    }

    .services-section h4 {
        font-family: 'Outfit', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 16px;
    }

    .service-selector {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        align-items: center;
    }

    .service-selector select {
        flex: 1;
        padding: 12px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-family: 'Lexend', sans-serif;
        background-color: #f8fafc;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        padding-right: 40px;
    }

    .service-selector select:focus {
        outline: none;
        border-color: #0ea5e9;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    .btn-add-service {
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
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-add-service:hover {
        background: #0284c7;
        transform: translateY(-2px);
    }

    .btn-add-service svg {
        width: 16px;
        height: 16px;
    }

    .services-table-container {
        background: #f8fafc;
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        overflow: hidden;
    }

    .services-table {
        width: 100%;
        border-collapse: collapse;
    }

    .services-table thead {
        background: #e2e8f0;
    }

    .services-table th {
        padding: 12px 16px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .services-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
        color: #1e293b;
        font-size: 14px;
        background: white;
        vertical-align: top;
    }

    .services-table tbody tr:last-child td {
        border-bottom: none;
    }

    .services-table .btn-remove {
        padding: 6px 12px;
        background: #fee2e2;
        color: #dc2626;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .services-table .btn-remove:hover {
        background: #fecaca;
    }

    .services-table-empty {
        padding: 40px 20px;
        text-align: center;
        color: #94a3b8;
        font-size: 14px;
        background: white;
    }

    /* Editable input fields in table */
    .services-table input[type="text"],
    .services-table input[type="number"],
    .services-table select {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 13px;
        font-family: 'Lexend', sans-serif;
        background: #f8fafc;
        transition: all 0.2s ease;
    }

    .services-table input[type="text"]:focus,
    .services-table input[type="number"]:focus,
    .services-table select:focus {
        outline: none;
        border-color: #0ea5e9;
        background: white;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    .services-table input[type="number"] {
        text-align: center;
    }

    .services-table select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        padding-right: 30px;
    }

    /* Requirements rows container */
    .requirements-container {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .requirement-row {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .requirement-row select,
    .requirement-row input {
        flex: 1;
    }

    .btn-add-requirement {
        padding: 6px 12px;
        background: #d1fae5;
        color: #065f46;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 11px;
        font-weight: 500;
        transition: all 0.2s ease;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .btn-add-requirement:hover {
        background: #a7f3d0;
    }

    .btn-remove-requirement {
        padding: 6px 8px;
        background: #fee2e2;
        color: #dc2626;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 11px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 28px;
    }

    .btn-remove-requirement:hover {
        background: #fecaca;
    }

    /* View Modal */

    .view-field {
        padding: 12px 14px;
        background-color: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-family: 'Lexend', sans-serif;
        color: #1e293b;
        min-height: 44px;
        display: flex;
        align-items: center;
    }

    .view-field:empty::before {
        content: 'Not specified';
        color: #94a3b8;
        font-style: italic;
    }

    .view-requirements-list {
        display: flex;
        gap: 8px;
        overflow: auto;
        width: 90%;
    }

    .view-requirement-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 12px;
        background: #f1f5f9;
        border-radius: 6px;
        font-size: 13px;
    }

    .view-requirement-type {
        font-weight: 600;
        color: #334155;
    }

    .view-requirement-count {
        padding: 4px 12px;
        background: #dbeafe;
        color: #1e40af;
        border-radius: 6px;
        font-weight: 600;
        font-size: 12px;
    }

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

    /* Print styles for main table */
    @media print {

        /* Only apply these styles when NOT printing from view modal */
        body:not(.printing-modal) .sidebar,
        body:not(.printing-modal) .page-header,
        body:not(.printing-modal) .search-bar,
        body:not(.printing-modal) .action-buttons,
        body:not(.printing-modal) .btn-add-client,
        body:not(.printing-modal) .modal-overlay,
        body:not(.printing-modal) .no-print {
            display: none !important;
        }

        body:not(.printing-modal) {
            background: white !important;
            margin: 0;
            padding: 0;
        }

        body:not(.printing-modal) .clients-container {
            padding: 0 !important;
        }

        body:not(.printing-modal) .table-container {
            box-shadow: none !important;
        }

        body:not(.printing-modal) table {
            width: 100%;
            border-collapse: collapse;
        }

        body:not(.printing-modal) th,
        body:not(.printing-modal) td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 12px;
        }

        body:not(.printing-modal) th {
            background: #f3f4f6;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }

    /* Print styles for view modal */
    @media print {

        /* Hide everything except the view modal content */
        body * {
            visibility: hidden;
        }

        #viewModal,
        #viewModal * {
            visibility: visible;
        }

        #viewModal {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: auto;
            background: white !important;
            backdrop-filter: none !important;
            display: block !important;
        }

        #viewModal .modal {
            position: relative;
            max-width: 100%;
            width: 100%;
            max-height: none;
            box-shadow: none !important;
            margin: 0;
            padding: 0;
        }

        #viewModal .modal-header {
            visibility: hidden;
        }

        #viewModal .modal-close {
            display: none !important;
        }

        #viewModal .modal-footer {
            display: none !important;
        }

        #viewModal .modal-body {
            padding: 10px;
            page-break-inside: avoid;
        }

        #viewModal .form-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        #viewModal .form-group.full-width {
            grid-column: 1 / -1;
        }

        #viewModal .form-group.full-width label {
            visibility: hidden;
        }

        #viewModal .form-group.full-widths {
            grid-column: 2 / -1;
        }

        #viewModal .form-group label {
            font-weight: 700;
            color: #000 !important;
            margin-bottom: 6px;
            font-size: 11px;
        }

        #viewModal .view-field {
            background-color: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            padding: 10px;
            font-size: 12px;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #viewModal .services-table-container {
            border: 1px solid #cbd5e1 !important;
            margin-top: 10px;
            page-break-inside: avoid;
        }

        #viewModal .services-table {
            width: 100%;
            border-collapse: collapse;
        }

        #viewModal .services-table th {
            background: #e2e8f0 !important;
            border: 1px solid #cbd5e1 !important;
            padding: 8px;
            font-size: 10px;
            color: #000 !important;
            font-weight: 700;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #viewModal .services-table td {
            border: 1px solid #cbd5e1 !important;
            padding: 8px;
            font-size: 11px;
            color: #000 !important;
            background: white !important;
        }

        #viewModal .view-requirements-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 6px;
            width: 95%;
        }

        #viewModal .view-requirement-item {
            background: #f1f5f9 !important;
            border: 1px solid #e2e8f0 !important;
            padding: 6px 10px;
            border-radius: 4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #viewModal .view-requirement-type {
            color: #000 !important;
            font-weight: 600;
        }

        #viewModal .view-requirement-count {
            background: #dbeafe !important;
            color: #1e40af !important;
            padding: 3px 10px;
            border-radius: 4px;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #viewModal .services-table-empty {
            text-align: center;
            color: #64748b !important;
            font-style: italic;
        }

        /* Print header */
        #viewModal .modal-header::after {
            display: block;
            font-size: 12px;
            font-weight: 400;
            margin-top: 5px;
            opacity: 0.9;
        }

        /* Page breaks */
        .form-group {
            page-break-inside: avoid;
        }

        .services-table tbody tr {
            page-break-inside: avoid;
        }
    }

    /* Additional styling for better print preview */
    .print-header {
        display: none;
    }

    @media print {
        .print-header {
            display: block;
            text-align: right;
            padding: 10px 20px;
            font-size: 10px;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
        }
    }

    /* Signature section - hidden on screen, visible on print */
    .print-signature-section {
        display: none;
    }

    @media print {

        /* Make sure signature section is visible in viewModal */
        #viewModal .print-signature-section {
            display: block !important;
            visibility: visible !important;
            margin-top: 60px;
            padding-top: 30px;
            page-break-inside: avoid;
        }

        #viewModal .signature-line {
            border-top: 2px solid #000 !important;
            width: 300px;
            margin: 80px auto 10px auto;
            visibility: visible !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #viewModal .signature-label {
            text-align: right;
            font-size: 12px;
            font-weight: 600;
            color: #000 !important;
            visibility: visible !important;
        }

        #viewModal .print-footer {
            margin-top: 40px;
            text-align: right;
            font-size: 9px;
            color: #94a3b8 !important;
            visibility: visible !important;
        }

        /* Generic signature styles for table print */
        .print-signature-section {
            display: block;
            margin-top: 60px;
            padding-top: 30px;
            page-break-inside: avoid;
        }

        .signature-line {
            border-top: 2px solid #000;
            width: 300px;
            margin: 80px auto 10px auto;
        }

        .signature-label {
            text-align: right;
            font-size: 12px;
            font-weight: 600;
            color: #000;
        }

        .print-footer {
            margin-top: 40px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
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
        <div class="page-header">
            <div class="search-bar">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                </svg>
                <input type="text" placeholder="Search Events..." id="searchInput">
            </div>

            <button class="btn-add-client" onclick="openModal()">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                </svg>
                Add Event
            </button>

            <button class="btn-add-client" onclick="exportExcel()">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3">
                    <path d="M202-65l-56-57 118-118h-90v-80h226v226h-80v-89L202-65Zm278-15v-80h240v-440H520v-200H240v400h-80v-400q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H480Z" />
                </svg>
                Export
            </button>

            <button class="btn-add-client" onclick="printTable()">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3">
                    <path d="M640-640v-120H320v120h-80v-200h480v200h-80Zm-480 80h640-640Zm560 100q17 0 28.5-11.5T760-500q0-17-11.5-28.5T720-540q-17 0-28.5 11.5T680-500q0 17 11.5 28.5T720-460Zm-80 260v-160H320v160h320Zm80 80H240v-160H80v-240q0-51 35-85.5t85-34.5h560q51 0 85.5 34.5T880-520v240H720v160Zm80-240v-160q0-17-11.5-28.5T760-560H200q-17 0-28.5 11.5T160-520v160h80v-80h480v80h80Z" />
                </svg>
                Print
            </button>
        </div>

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

                    Event added successfully!
                </div>
            <?php elseif ($_GET['success'] == 'updated'): ?>
                <div class="alert alert-info">

                    Event updated successfully!
                </div>
            <?php elseif ($_GET['success'] == 'deleted'): ?>
                <div class="alert alert-info">

                    Event deleted successfully!
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="table-container">
            <table class="clients-table">
                <thead>
                    <tr>
                        <th>Sr.no</th>
                        <th>Event Name</th>
                        <th>Client</th>
                        <th>Venue</th>
                        <th>Services</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sr = 1;
                    if (mysqli_num_rows($events_result) > 0) {
                        while ($event = mysqli_fetch_assoc($events_result)) {
                            $services = json_decode($event['service'], true);
                    ?>
                            <tr>
                                <td><?php echo $sr++; ?></td>
                                <td>
                                    <div class="event-name"><?php echo htmlspecialchars($event['name']); ?></div>
                                </td>
                                <td>
                                    <span class="client-badge"><?php echo htmlspecialchars($event['client_name']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($event['venue']); ?></td>
                                <td>
                                    <?php
                                    // Debug: Show raw service data
                                    // echo '<pre style="font-size:10px;">' . htmlspecialchars($event['service']) . '</pre>';

                                    if (is_array($services) && count($services) > 0) {
                                        foreach ($services as $service_item) {
                                            if (isset($service_item['service']) && !empty($service_item['service'])) {
                                                echo '<span class="service-badge">' . htmlspecialchars($service_item['service']) . '</span>';
                                            }
                                        }
                                    } else {
                                        echo '<span style="color: #94a3b8; font-size: 12px; font-style: italic;">No services</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">

                                        <button onclick='viewDetails(<?php echo json_encode($event); ?>)' class="btn-action view">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                        </button>

                                        <a href="events.php?edit=<?php echo $event['id']; ?>" class="btn-action edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                        </a>

                                    </div>
                                </td>
                            </tr>
                        <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="7" class="empty-row">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 48px; height: 48px; margin-bottom: 12px;">
                                    <path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                                </svg>
                                <div>No events found. Click "Add Event" to create your first event.</div>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add Modal -->
<div class="modal-overlay" id="eventModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Add New Event</h2>
            <button class="modal-close" onclick="closeModal()">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </button>
        </div>
        <form action="" method="post" id="eventForm">
            <input type="hidden" name="selected_services" id="selected_services_input">
            <div class="modal-body">
                <div class="form-grid">


                    <div class="form-group">
                        <label for="client_name">Client Name</label>
                        <select name="client_name" id="client_name" required>
                            <option value="">--Select Client--</option>
                            <?php
                            mysqli_data_seek($client_result, 0);
                            while ($client = mysqli_fetch_assoc($client_result)):
                            ?>
                                <option value="<?php echo htmlspecialchars($client['client_name']); ?>">
                                    <?php echo htmlspecialchars($client['client_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="venue">Venue</label>
                        <input type="text" name="venue" id="venue" placeholder="Enter venue" required>
                    </div>

                    <div class="form-group">
                        <label for="name">Event Name</label>
                        <input type="text" name="name" id="name" placeholder="Enter event name" required>
                    </div>

                    <div class="form-group">
                        <label for="services">Services</label>
                        <div class="service-selector">
                            <select id="service_dropdown">
                                <option value="">--Select Service--</option>
                                <?php
                                mysqli_data_seek($service_result, 0);
                                while ($service = mysqli_fetch_assoc($service_result)):
                                ?>
                                    <option value="<?php echo htmlspecialchars($service['name']); ?>">
                                        <?php echo htmlspecialchars($service['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button type="button" class="btn-add-service" onclick="addServiceToTable()">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <div class="services-section">
                            <div class="services-table-container">
                                <table class="services-table full-width">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">Sr. No</th>
                                            <th style="width: 180px;">Service Name</th>
                                            <th>Requirements</th>
                                            <th style="width: 100px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="services_table_body">
                                        <tr>
                                            <td colspan="4" class="services-table-empty">No services added yet</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" required>
                    </div>

                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" name="end_date" id="end_date" required>
                    </div>

                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" name="start_time" id="start_time">
                    </div>

                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" name="end_time" id="end_time">
                    </div>

                    <div class="form-group">
                        <label for="budget">Budget</label>
                        <input type="number" name="budget" id="budget" placeholder="Enter budget" min="0" value="0">
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" placeholder="Enter event description (optional)"></textarea>
                    </div>
                </div>

                <!-- Services Section -->

            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="submit" class="btn-submit" onclick="return validateForm()">Add Event</button>
            </div>
        </form>
    </div>
</div>

<!-- View Event Modal -->
<div class="modal-overlay" id="viewModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Event Details </h2>
            <button class="modal-close" onclick="closeViewModal()">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-grid">
                <div class="form-group">
                    <label>Event Name</label>
                    <div class="view-field" id="view_name"></div>
                </div>

                <div class="form-group">
                    <label>Client Name</label>
                    <div class="view-field" id="view_client_name"></div>
                </div>

                <div class="form-group">
                    <label>Venue</label>
                    <div class="view-field" id="view_venue"></div>
                </div>

                <div class="form-group">
                    <label>Budget</label>
                    <div class="view-field" id="view_budget"></div>
                </div>

                <div class="form-group">
                    <label>Start Date</label>
                    <div class="view-field" id="view_start_date"></div>
                </div>

                <div class="form-group">
                    <label>End Date</label>
                    <div class="view-field" id="view_end_date"></div>
                </div>

                <div class="form-group">
                    <label>Start Time</label>
                    <div class="view-field" id="view_start_time"></div>
                </div>

                <div class="form-group">
                    <label>End Time</label>
                    <div class="view-field" id="view_end_time"></div>
                </div>

                <div class="form-group full-widths">
                    <label>Description</label>
                    <div class="view-field" id="view_description"></div>
                </div>

                <div class="form-group full-width">
                    <label>Services & Requirements</label>
                    <div class="services-table-container">
                        <table class="services-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">Sr. No</th>
                                    <th style="width: 200px;">Service Name</th>
                                    <th style="width: 80%;">Requirements</th>
                                </tr>
                            </thead>
                            <tbody id="view_services_table_body">
                                <tr style="overflow:auto">
                                    <td colspan="3" class="services-table-empty" style="overflow:auto;width:30%">No services added</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeViewModal()">Close</button>
            <button type="button" class="btn-submit" onclick="printEventDetails()">
                <svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 -960 960 960" width="18px" fill="currentColor" style="margin-right: 6px;">
                    <path d="M640-640v-120H320v120h-80v-200h480v200h-80Zm-480 80h640-640Zm560 100q17 0 28.5-11.5T760-500q0-17-11.5-28.5T720-540q-17 0-28.5 11.5T680-500q0 17 11.5 28.5T720-460Zm-80 260v-160H320v160h320Zm80 80H240v-160H80v-240q0-51 35-85.5t85-34.5h560q51 0 85.5 34.5T880-520v240H720v160Zm80-240v-160q0-17-11.5-28.5T760-560H200q-17 0-28.5 11.5T160-520v160h80v-80h480v80h80Z" />
                </svg>
                Print
            </button>
        </div>
    </div>
</div>

<!-- Edit Event Modal -->
<div class="modal-overlay" id="eventEditModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Edit Event</h2>
            <a href="events.php" class="modal-close">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </a>
        </div>
        <form action="" method="post" id="eventEditForm">
            <input type="hidden" name="event_id" value="<?php echo $edit_event ? htmlspecialchars($edit_event['id']) : ''; ?>">
            <input type="hidden" name="selected_services" id="edit_selected_services_input">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_name">Event Name</label>
                        <input type="text" name="name" id="edit_name"
                            value="<?php echo $edit_event ? htmlspecialchars($edit_event['name']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_client_name">Client Name</label>
                        <select name="client_name" id="edit_client_name" required>
                            <option value="">--Select Client--</option>
                            <?php
                            mysqli_data_seek($client_result, 0);
                            while ($client = mysqli_fetch_assoc($client_result)):
                                $selected = ($edit_event && $edit_event['client_name'] == $client['client_name']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo htmlspecialchars($client['client_name']); ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($client['client_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_start_date">Start Date</label>
                        <input type="date" name="start_date" id="edit_start_date"
                            value="<?php echo $edit_event ? htmlspecialchars($edit_event['start_date']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_end_date">End Date</label>
                        <input type="date" name="end_date" id="edit_end_date"
                            value="<?php echo $edit_event ? htmlspecialchars($edit_event['end_date']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_start_time">Start Time</label>
                        <input type="time" name="start_time" id="edit_start_time"
                            value="<?php echo $edit_event ? htmlspecialchars($edit_event['start_time']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="edit_end_time">End Time</label>
                        <input type="time" name="end_time" id="edit_end_time"
                            value="<?php echo $edit_event ? htmlspecialchars($edit_event['end_time']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="edit_venue">Venue</label>
                        <input type="text" name="venue" id="edit_venue"
                            value="<?php echo $edit_event ? htmlspecialchars($edit_event['venue']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_budget">Budget</label>
                        <input type="number" name="budget" id="edit_budget"
                            value="<?php echo $edit_event ? htmlspecialchars($edit_event['budget']) : '0'; ?>" min="0">
                    </div>

                    <div class="form-group full-width">
                        <label for="edit_description">Description</label>
                        <textarea name="description" id="edit_description"><?php echo $edit_event ? htmlspecialchars($edit_event['description']) : ''; ?></textarea>
                    </div>
                </div>

                <!-- Services Section -->
                <div class="services-section">
                    <h4>Services</h4>
                    <div class="service-selector">
                        <select id="edit_service_dropdown">
                            <option value="">--Select Service--</option>
                            <?php
                            mysqli_data_seek($service_result, 0);
                            while ($service = mysqli_fetch_assoc($service_result)):
                            ?>
                                <option value="<?php echo htmlspecialchars($service['name']); ?>">
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <button type="button" class="btn-add-service" onclick="addEditServiceToTable()">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                            </svg>
                            Add Row
                        </button>
                    </div>

                    <div class="services-table-container">
                        <table class="services-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">Sr. No</th>
                                    <th style="width: 180px;">Service Name</th>
                                    <th>Requirements</th>
                                    <th style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="edit_services_table_body">
                                <tr>
                                    <td colspan="4" class="services-table-empty">No services added yet</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="events.php" class="btn-cancel">Cancel</a>
                <button type="submit" name="update_event" class="btn-submit">Update Event</button>
            </div>
        </form>
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
            <input type="hidden" name="delete_event_id" id="delete_event_id">
            <div class="modal-body">
                <p style="font-size: 15px; color: #64748b; margin: 0;">
                    Are you sure you want to delete this event? This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" name="delete_event" class="btn-submit" style="background: #dc2626;">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Array to store selected services
    let selectedServices = [];
    let editSelectedServices = [];

    // Static requirements options
    const requirementOptions = [
        'Veg',
        'Non-Veg',
        'Beverages',
        'Desserts',
        'Stage',
        'Backdrop',
        'Floral Arrangements',
        'Lighting',
        'Entrance Decor',
        'Table Decor',
        'Chairs',
        'Tables',
        'Sound System',
        'Microphones',
        'Speakers',
        'Projector',
        'Screen',
        'DJ Setup',
        'Photography',
        'Videography',
        'Drone',
        'Photo Booth',
        'LED Walls',
        'Trussing',
        'Generators',
        'Tents',
        'AC/Coolers',
        'Waiters',
        'Cleaners',
        'Security',
        'Valet Parking',
        'Other'
    ];

    // Generate requirement dropdown HTML
    function generateRequirementDropdown(selectedValue = '') {
        let html = '<option value="">--Select Requirement--</option>';
        requirementOptions.forEach(option => {
            const selected = option === selectedValue ? 'selected' : '';
            html += `<option value="${option}" ${selected}>${option}</option>`;
        });
        return html;
    }

    // Add service to table (Add Modal)
    function addServiceToTable() {
        const dropdown = document.getElementById('service_dropdown');
        const selectedValue = dropdown.value;
        const selectedText = dropdown.options[dropdown.selectedIndex].text;

        if (!selectedValue) {
            alert('Please select a service first');
            return;
        }

        // Check if service already exists
        if (selectedServices.some(s => s.service === selectedValue)) {
            alert('This service is already added');
            return;
        }

        // Add to array with one default requirement row
        selectedServices.push({
            service: selectedValue,
            requirements: [{
                type: '',
                count: 1
            }]
        });

        // Update table
        updateServicesTable();

        // Reset dropdown
        dropdown.value = '';
    }

    // Add requirement row to service
    function addRequirementRow(serviceIndex) {
        selectedServices[serviceIndex].requirements.push({
            type: '',
            count: 1
        });
        updateServicesTable();
    }

    // Remove requirement row
    function removeRequirementRow(serviceIndex, reqIndex) {
        if (selectedServices[serviceIndex].requirements.length > 1) {
            selectedServices[serviceIndex].requirements.splice(reqIndex, 1);
            updateServicesTable();
        } else {
            alert('At least one requirement is needed');
        }
    }

    // Update requirement type
    function updateRequirementType(serviceIndex, reqIndex, value) {
        selectedServices[serviceIndex].requirements[reqIndex].type = value;
        updateHiddenInput();
    }

    // Update requirement count
    function updateRequirementCount(serviceIndex, reqIndex, value) {
        selectedServices[serviceIndex].requirements[reqIndex].count = parseInt(value) || 1;
        updateHiddenInput();
    }

    // Update hidden input
    function updateHiddenInput() {
        document.getElementById('selected_services_input').value = JSON.stringify(selectedServices);
    }

    // Generate requirements HTML
    function generateRequirementsHTML(serviceIndex, requirements) {
        return `
            <div class="requirements-container">
                ${requirements.map((req, reqIndex) => `
                    <div class="requirement-row">
                        <select onchange="updateRequirementType(${serviceIndex}, ${reqIndex}, this.value)">
                            ${generateRequirementDropdown(req.type)}
                        </select>
                        <input type="number" 
                               value="${req.count || 1}" 
                               onchange="updateRequirementCount(${serviceIndex}, ${reqIndex}, this.value)"
                               min="1"
                               style="max-width: 80px;"
                               placeholder="Qty">
                        <button type="button" 
                                class="btn-remove-requirement" 
                                onclick="removeRequirementRow(${serviceIndex}, ${reqIndex})"
                                title="Remove requirement">
                            ×
                        </button>
                    </div>
                `).join('')}
                <button type="button" 
                        class="btn-add-requirement" 
                        onclick="addRequirementRow(${serviceIndex})">
                    + Add Requirement
                </button>
            </div>
        `;
    }

    function printTable() {
        window.print();
    }

    // Update services table display (Add Modal)
    function updateServicesTable() {
        const tbody = document.getElementById('services_table_body');

        if (selectedServices.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="services-table-empty">No services added yet</td></tr>';
        } else {
            tbody.innerHTML = selectedServices.map((service, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${service.service}</td>
                    <td>
                        ${generateRequirementsHTML(index, service.requirements)}
                    </td>
                    <td>
                        <button type="button" class="btn-remove" onclick="removeServiceFromTable(${index})">
                            Remove
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        // Update hidden input
        updateHiddenInput();
    }

    // Remove service from table (Add Modal)
    function removeServiceFromTable(index) {
        selectedServices.splice(index, 1);
        updateServicesTable();
    }

    // ===== EDIT MODAL FUNCTIONS =====

    // Add service to edit table
    function addEditServiceToTable() {
        const dropdown = document.getElementById('edit_service_dropdown');
        const selectedValue = dropdown.value;
        const selectedText = dropdown.options[dropdown.selectedIndex].text;

        if (!selectedValue) {
            alert('Please select a service first');
            return;
        }

        // Check if service already exists
        if (editSelectedServices.some(s => s.service === selectedValue)) {
            alert('This service is already added');
            return;
        }

        // Add to array with one default requirement row
        editSelectedServices.push({
            service: selectedValue,
            requirements: [{
                type: '',
                count: 1
            }]
        });

        // Update table
        updateEditServicesTable();

        // Reset dropdown
        dropdown.value = '';
    }

    // Add requirement row to edit service
    function addEditRequirementRow(serviceIndex) {
        editSelectedServices[serviceIndex].requirements.push({
            type: '',
            count: 1
        });
        updateEditServicesTable();
    }

    // Remove edit requirement row
    function removeEditRequirementRow(serviceIndex, reqIndex) {
        if (editSelectedServices[serviceIndex].requirements.length > 1) {
            editSelectedServices[serviceIndex].requirements.splice(reqIndex, 1);
            updateEditServicesTable();
        } else {
            alert('At least one requirement is needed');
        }
    }

    // Update edit requirement type
    function updateEditRequirementType(serviceIndex, reqIndex, value) {
        editSelectedServices[serviceIndex].requirements[reqIndex].type = value;
        updateEditHiddenInput();
    }

    // Update edit requirement count
    function updateEditRequirementCount(serviceIndex, reqIndex, value) {
        editSelectedServices[serviceIndex].requirements[reqIndex].count = parseInt(value) || 1;
        updateEditHiddenInput();
    }

    // Update edit hidden input
    function updateEditHiddenInput() {
        document.getElementById('edit_selected_services_input').value = JSON.stringify(editSelectedServices);
    }

    // Generate edit requirements HTML
    function generateEditRequirementsHTML(serviceIndex, requirements) {
        return `
            <div class="requirements-container">
                ${requirements.map((req, reqIndex) => `
                    <div class="requirement-row">
                        <select onchange="updateEditRequirementType(${serviceIndex}, ${reqIndex}, this.value)">
                            ${generateRequirementDropdown(req.type)}
                        </select>
                        <input type="number" 
                               value="${req.count || 1}" 
                               onchange="updateEditRequirementCount(${serviceIndex}, ${reqIndex}, this.value)"
                               min="1"
                               style="max-width: 80px;"
                               placeholder="Qty">
                        <button type="button" 
                                class="btn-remove-requirement" 
                                onclick="removeEditRequirementRow(${serviceIndex}, ${reqIndex})"
                                title="Remove requirement">
                            ×
                        </button>
                    </div>
                `).join('')}
                <button type="button" 
                        class="btn-add-requirement" 
                        onclick="addEditRequirementRow(${serviceIndex})">
                    + Add Requirement
                </button>
            </div>
        `;
    }

    // Update edit services table display
    function updateEditServicesTable() {
        const tbody = document.getElementById('edit_services_table_body');

        if (editSelectedServices.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="services-table-empty">No services added yet</td></tr>';
        } else {
            tbody.innerHTML = editSelectedServices.map((service, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${service.service}</td>
                    <td>
                        ${generateEditRequirementsHTML(index, service.requirements)}
                    </td>
                    <td>
                        <button type="button" class="btn-remove" onclick="removeEditServiceFromTable(${index})">
                            Remove
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        // Update hidden input
        updateEditHiddenInput();
    }

    // Remove service from edit table
    function removeEditServiceFromTable(index) {
        editSelectedServices.splice(index, 1);
        updateEditServicesTable();
    }

    // Modal functions
    function openModal() {
        selectedServices = [];
        updateServicesTable();
        document.getElementById('eventModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('eventModal').classList.remove('active');
        document.body.style.overflow = 'auto';
        document.getElementById('eventForm').reset();
        selectedServices = [];
        updateServicesTable();
    }

    function deleteEvent(id) {
        document.getElementById('delete_event_id').value = id;
        document.getElementById('deleteModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Export and Print
    function exportExcel() {
        var table = document.querySelector('.clients-table');
        var rows = table.querySelectorAll('tr');
        var xls = [];

        for (var i = 0; i < rows.length; i++) {
            var row = [],
                cols = rows[i].querySelectorAll('td, th');

            for (var j = 0; j < cols.length - 1; j++) {
                var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                data = data.replace(/"/g, '""');
                row.push('"' + data + '"');
            }

            xls.push(row.join(','));
        }

        var xls_string = xls.join('\n');
        var filename = 'events_export_' + new Date().toLocaleDateString() + '.xls';
        var link = document.createElement('a');
        link.style.display = 'none';
        link.setAttribute('target', '_blank');
        link.setAttribute('href', 'data:text/xls;charset=utf-8,' + encodeURIComponent(xls_string));
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
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
    document.getElementById('eventModal').addEventListener('click', function(e) {
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
    <?php if ($edit_event): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('eventEditModal').classList.add('active');
            document.body.style.overflow = 'hidden';

            // Load existing service data
            <?php if (!empty($edit_event['service'])): ?>
                const serviceData = <?php echo $edit_event['service']; ?>;
                if (Array.isArray(serviceData)) {
                    // Convert old format to new format if needed
                    editSelectedServices = serviceData.map(service => {
                        // Check if it's old format (with requirements as string)
                        if (typeof service.requirements === 'string') {
                            return {
                                service: service.service,
                                requirements: [{
                                    type: service.requirements,
                                    count: service.count || 1
                                }]
                            };
                        }
                        // New format - ensure requirements is an array
                        else if (Array.isArray(service.requirements)) {
                            return service;
                        }
                        // Fallback - create default requirement
                        else {
                            return {
                                service: service.service,
                                requirements: [{
                                    type: '',
                                    count: 1
                                }]
                            };
                        }
                    });
                    updateEditServicesTable();
                }
            <?php endif; ?>
        });
    <?php endif; ?>

    // View event details
    function viewDetails(eventData) {
        // Populate basic fields
        document.getElementById('view_name').textContent = eventData.name || '';
        document.getElementById('view_client_name').textContent = eventData.client_name || '';
        document.getElementById('view_venue').textContent = eventData.venue || '';
        document.getElementById('view_budget').textContent = eventData.budget ? '₹' + parseInt(eventData.budget).toLocaleString() : '₹0';
        document.getElementById('view_start_date').textContent = eventData.start_date ? formatDate(eventData.start_date) : '';
        document.getElementById('view_end_date').textContent = eventData.end_date ? formatDate(eventData.end_date) : '';
        document.getElementById('view_start_time').textContent = eventData.start_time ? formatTime(eventData.start_time) : '';
        document.getElementById('view_end_time').textContent = eventData.end_time ? formatTime(eventData.end_time) : '';
        document.getElementById('view_description').textContent = eventData.description || '';



        // Parse and display services
        let services = [];
        try {
            services = JSON.parse(eventData.service);
        } catch (e) {
            services = [];
        }

        const tbody = document.getElementById('view_services_table_body');

        if (!Array.isArray(services) || services.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="services-table-empty">No services added</td></tr>';
        } else {
            tbody.innerHTML = services.map((service, index) => {
                let requirementsHTML = '';

                if (Array.isArray(service.requirements) && service.requirements.length > 0) {
                    requirementsHTML = `
                    <div class="view-requirements-list">
                        ${service.requirements.map(req => `
                            <div class="view-requirement-item">
                                <span class="view-requirement-type">
                                    ${req.type || 'Not specified'}
                                </span>
                                <span class="view-requirement-count">
                                    Qty: ${req.count || 1}
                                </span>
                            </div>
                        `).join('')}
                    </div>
                `;
                } else {
                    requirementsHTML = '<span style="color: #94a3b8; font-style: italic;">No requirements specified</span>';
                }

                return `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${service.service}</strong></td>
                    <td>${requirementsHTML}</td>
                </tr>
            `;
            }).join('');
        }

        // ADD THIS LINE - Add signature section
        addSignatureSection(eventData);

        // Open modal
        document.getElementById('viewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeViewModal() {
        document.getElementById('viewModal').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Helper function to format date
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const options = {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        return date.toLocaleDateString('en-US', options);
    }

    // Helper function to format time
    function formatTime(timeString) {
        if (!timeString) return '';
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    }

    // Close view modal on outside click
    document.addEventListener('DOMContentLoaded', function() {
        const viewModal = document.getElementById('viewModal');
        if (viewModal) {
            viewModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeViewModal();
                }
            });
        }
    });

    // Print event details from view modal
    function printEventDetails() {
        // Get the event name for the document title
        const eventName = document.getElementById('view_name').textContent;

        // Store original title
        const originalTitle = document.title;

        // Set document title for print
        document.title = eventName ? `Event Details - ${eventName}` : 'Event Details';

        // Add printing class to body
        document.body.classList.add('printing-modal');


        // Trigger print
        window.print();

        // Restore original title and remove class after print dialog closes
        setTimeout(() => {
            document.title = originalTitle;
            document.body.classList.remove('printing-modal');
            printHeader.remove();
        }, 100);
    }
    // Add signature section to view modal
    function addSignatureSection(eventData) {
        console.log('Adding signature section...');

        // Remove existing signature section if any
        const existingSignature = document.querySelector('.print-signature-section');
        if (existingSignature) {
            existingSignature.remove();
            console.log('Removed existing signature');
        }

        // Create simple signature section
        const signatureSection = document.createElement('div');
        signatureSection.className = 'print-signature-section';
        signatureSection.innerHTML = `
        <div class="signature-label">Signature</div>    
            `;

        // Append to modal body
        const modalBody = document.querySelector('#viewModal .modal-body');
        if (modalBody) {
            modalBody.appendChild(signatureSection);
            console.log('Signature section added successfully');
        } else {
            console.error('Modal body not found!');
        }
    }
    // Validate form before submission
    function validateForm() {
        // Make sure hidden input is updated
        updateHiddenInput();

        // Check if services are added
        const servicesInput = document.getElementById('selected_services_input').value;

        // Debug: log what's being submitted
        console.log('Services being submitted:', servicesInput);

        // Allow form to submit
        return true;
    }
</script>