<?php
include('config/db.php');

// List of countries for the dropdown
$countries = array(
    'United States', 'Canada', 'United Kingdom', 'Australia', 'Germany', 'France', 'Italy', 'Spain', 
    'Japan', 'China', 'India', 'Brazil', 'Mexico', 'Russia', 'South Korea', 'Netherlands', 'Sweden', 
    'Norway', 'Denmark', 'Finland', 'Switzerland', 'Austria', 'Belgium', 'Portugal', 'Greece', 'Turkey', 
    'Poland', 'Czech Republic', 'Hungary', 'Romania', 'Bulgaria', 'Croatia', 'Slovenia', 'Slovakia', 
    'Estonia', 'Latvia', 'Lithuania', 'Ireland', 'New Zealand', 'South Africa', 'Argentina', 'Chile', 
    'Colombia', 'Peru', 'Venezuela', 'Thailand', 'Vietnam', 'Singapore', 'Malaysia', 'Indonesia', 
    'Philippines', 'Bangladesh', 'Pakistan', 'Sri Lanka', 'Nepal', 'Myanmar', 'Cambodia', 'Laos'
);
sort($countries);

// Get all locations
$get_locations = "SELECT * FROM locations ORDER BY created_at DESC";
$run_locations = mysqli_query($conn, $get_locations);

// Handle form submission for creating new location
if (isset($_POST['submit_location'])) {
    $country = mysqli_real_escape_string($conn, trim($_POST['country']));
    $state = mysqli_real_escape_string($conn, trim($_POST['state']));
    $city = mysqli_real_escape_string($conn, trim($_POST['city']));
    $location_status = mysqli_real_escape_string($conn, $_POST['location_status']);
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));

    if (empty($country) || empty($state) || empty($city)) {
        $error = 'Please fill in all required fields (Country, State, City).';
    } else {
        // Check if location already exists
        $check_location = "SELECT * FROM locations WHERE country = '$country' AND state = '$state' AND city = '$city'";
        $run_check = mysqli_query($conn, $check_location);

        if (mysqli_num_rows($run_check) > 0) {
            $error = 'This location already exists!';
        } else {
            $insert_location = "INSERT INTO locations (country, state, city, status, description, created_at) 
                              VALUES ('$country', '$state', '$city', '$location_status', '$description', NOW())";

            if (mysqli_query($conn, $insert_location)) {
                header('Location: location.php?success=created');
                exit();
            } else {
                $error = 'Error: ' . mysqli_error($conn);
            }
        }
    }
}

// Handle location update
if (isset($_POST['update_location'])) {
    $location_id = intval($_POST['location_id']);
    $country = mysqli_real_escape_string($conn, trim($_POST['country']));
    $state = mysqli_real_escape_string($conn, trim($_POST['state']));
    $city = mysqli_real_escape_string($conn, trim($_POST['city']));
    $location_status = mysqli_real_escape_string($conn, $_POST['location_status']);
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));

    if (empty($country) || empty($state) || empty($city)) {
        $error = 'Please fill in all required fields.';
    } else {
        $check_location = "SELECT * FROM locations WHERE country = '$country' AND state = '$state' AND city = '$city' AND id != '$location_id'";
        $run_check = mysqli_query($conn, $check_location);

        if (mysqli_num_rows($run_check) > 0) {
            $error = 'This location already exists!';
        } else {
            $update_location = "UPDATE locations SET 
                              country='$country', state='$state', city='$city', 
                              status='$location_status', description='$description', updated_at=NOW() 
                              WHERE id='$location_id'";

            if (mysqli_query($conn, $update_location)) {
                header('Location: location.php?success=updated');
                exit();
            } else {
                $error = 'Error: ' . mysqli_error($conn);
            }
        }
    }
}

// Handle location deletion
if (isset($_POST['delete_location'])) {
    $location_id = intval($_POST['delete_location_id']);
    $delete_location = "DELETE FROM locations WHERE id='$location_id'";
    
    if (mysqli_query($conn, $delete_location)) {
        header('Location: location.php?success=deleted');
        exit();
    } else {
        $error = 'Error: ' . mysqli_error($conn);
    }
}
?>
<?php include 'includes/sidebar.php'; ?>

<style>
    /* =====================================================
   LOCATION MANAGEMENT FORM STYLES
   Modern, professional styling for location management
   ===================================================== */

/* Typography */
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Lexend:wght@400;500;600&display=swap');

/* =====================================================
   MAIN CONTAINER
   ===================================================== */
.location-container {
    padding: 40px;
    background: #f1f5f9;
    min-height: 100vh;
}

/* =====================================================
   PAGE HEADER
   ===================================================== */
.page-header {
    margin-bottom: 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    animation: fadeInDown 0.6s ease;
}

.page-title-section h1 {
    font-family: 'Outfit', sans-serif;
    font-size: 36px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 8px 0;
    letter-spacing: -1px;
}

.page-subtitle {
    font-size: 16px;
    color: #64748b;
    margin: 0;
    font-family: 'Lexend', sans-serif;
}

.btn-primary-custom {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    font-family: 'Lexend', sans-serif;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
}

.btn-primary-custom:active {
    transform: translateY(0);
}

/* =====================================================
   FILTER SECTION
   ===================================================== */
.filter-card {
    background: white;
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
    animation: fadeInUp 0.6s ease 0.1s forwards;
    opacity: 0;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.filter-group {
    position: relative;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    font-family: 'Lexend', sans-serif;
    transition: all 0.3s ease;
    background-color: #f8fafc;
    color: #1e293b;
}

.filter-group input::placeholder {
    color: #94a3b8;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #3b82f6;
    background-color: #ffffff;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.filter-group select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 40px;
}

/* =====================================================
   TABLE CARD
   ===================================================== */
.table-card {
    background: white;
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
    animation: fadeInUp 0.6s ease 0.2s forwards;
    opacity: 0;
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 12px;
    min-width: 800px;
}

.data-table thead th {
    font-family: 'Outfit', sans-serif;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 16px;
    text-align: left;
    border: none;
    background: transparent;
}

.data-table tbody tr {
    background: #f8fafc;
    transition: all 0.3s ease;
}

.data-table tbody tr:hover {
    background: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    transform: translateY(-2px);
}

.data-table tbody td {
    padding: 16px;
    font-size: 14px;
    color: #1e293b;
    border: none;
    font-family: 'Lexend', sans-serif;
}

.data-table tbody tr td:first-child {
    border-radius: 12px 0 0 12px;
    font-weight: 600;
}

.data-table tbody tr td:last-child {
    border-radius: 0 12px 12px 0;
}

/* =====================================================
   STATUS BADGES
   ===================================================== */
.badge-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    font-family: 'Lexend', sans-serif;
}

.badge-active {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.badge-active::before {
    content: '●';
    font-size: 14px;
}

.badge-inactive {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}

.badge-inactive::before {
    content: '●';
    font-size: 14px;
}

/* =====================================================
   ACTION BUTTONS
   ===================================================== */
.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-edit, 
.btn-delete {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.btn-edit {
    background: #dbeafe;
    color: #2563eb;
}

.btn-edit:hover {
    background: #3b82f6;
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-delete {
    background: #fee2e2;
    color: #dc2626;
}

.btn-delete:hover {
    background: #ef4444;
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* =====================================================
   MODAL STYLES
   ===================================================== */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9998;
    animation: fadeIn 0.3s ease;
}

.modal-container {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    overflow-y: auto;
    padding: 40px 20px;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    max-width: 600px;
    width: 100%;
    margin: 0 auto;
    border-radius: 20px;
    padding: 32px;
    animation: slideUp 0.3s ease;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e2e8f0;
}

.modal-title {
    font-family: 'Outfit', sans-serif;
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
}

.modal-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f8fafc;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    font-size: 20px;
    color: #64748b;
    font-weight: 300;
}

.modal-close:hover {
    background: #e2e8f0;
    transform: rotate(90deg);
}

/* =====================================================
   FORM STYLES
   ===================================================== */
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
    font-family: 'Lexend', sans-serif;
}

.form-group input[type="text"],
.form-group input[type="tel"],
.form-group input[type="email"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    font-family: 'Lexend', sans-serif;
    transition: all 0.3s ease;
    background-color: #f8fafc;
    color: #1e293b;
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: #94a3b8;
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
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    background-color: #ffffff;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

/* Form validation states */
.form-group input:invalid:not(:focus):not(:placeholder-shown),
.form-group select:invalid:not(:focus) {
    border-color: #ef4444;
}

.form-group input:valid:not(:focus):not(:placeholder-shown) {
    border-color: #10b981;
}

/* =====================================================
   FORM BUTTONS
   ===================================================== */
.btn-submit {
    grid-column: 1 / -1;
    padding: 14px 24px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    font-family: 'Lexend', sans-serif;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    margin-top: 8px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
}

.btn-submit:active {
    transform: translateY(0);
}

.btn-submit:disabled {
    background: #94a3b8;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* =====================================================
   ALERT MESSAGES
   ===================================================== */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideInDown 0.4s ease;
    font-family: 'Lexend', sans-serif;
}

.alert-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    border: 2px solid #10b981;
}

.alert-error {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
    border: 2px solid #ef4444;
}

.alert-warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
    border: 2px solid #f59e0b;
}

.alert-info {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
    border: 2px solid #3b82f6;
}

.alert svg {
    flex-shrink: 0;
}

/* =====================================================
   DELETE MODAL STYLES
   ===================================================== */
.modal-content p {
    color: #64748b;
    margin-bottom: 24px;
    font-family: 'Lexend', sans-serif;
    line-height: 1.6;
}



.modal-content form button {
    flex: 1;
    padding: 12px;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    font-family: 'Lexend', sans-serif;
    font-size: 14px;
    transition: all 0.2s ease;
}

.modal-content form button[type="button"] {
    border: 2px solid #e2e8f0;
    background: white;
    color: #64748b;
}

.modal-content form button[type="button"]:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.modal-content form button[type="submit"] {
    background: #ef4444;
    color: white;
    border: none;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.modal-content form button[type="submit"]:hover {
    background: #dc2626;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

/* =====================================================
   EMPTY STATE
   ===================================================== */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}

.empty-state svg {
    width: 80px;
    height: 80px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state h3 {
    font-family: 'Outfit', sans-serif;
    font-size: 20px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 8px;
}

.empty-state p {
    font-size: 14px;
}

/* =====================================================
   LOADING STATE
   ===================================================== */
.loading {
    position: relative;
    pointer-events: none;
    opacity: 0.6;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #3b82f6;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

/* =====================================================
   ANIMATIONS
   ===================================================== */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

/* =====================================================
   RESPONSIVE DESIGN
   ===================================================== */
@media (max-width: 1024px) {
    .location-container {
        padding: 32px;
    }

    .page-title-section h1 {
        font-size: 32px;
    }
}

@media (max-width: 768px) {
    .location-container {
        padding: 24px;
    }

    .page-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }

    .page-title-section h1 {
        font-size: 28px;
    }

    .page-subtitle {
        font-size: 14px;
    }

    .btn-primary-custom {
        width: 100%;
        justify-content: center;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

    .table-card {
        overflow-x: auto;
        padding: 20px;
    }

    .data-table {
        font-size: 13px;
    }

    .data-table thead th,
    .data-table tbody td {
        padding: 12px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .modal-content {
        padding: 24px;
        margin: 20px;
    }

    .modal-title {
        font-size: 20px;
    }

    .action-buttons {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .location-container {
        padding: 16px;
    }

    .page-title-section h1 {
        font-size: 24px;
    }

    .page-subtitle {
        font-size: 13px;
    }

    .filter-card,
    .table-card {
        padding: 16px;
        border-radius: 16px;
    }

    .btn-primary-custom {
        padding: 10px 20px;
        font-size: 14px;
    }

    .modal-content {
        border-radius: 16px;
    }

    .badge-status {
        font-size: 11px;
        padding: 4px 8px;
    }
}

/* =====================================================
   PRINT STYLES
   ===================================================== */
@media print {
    .btn-primary-custom,
    .filter-card,
    .action-buttons,
    .modal-overlay,
    .modal-container {
        display: none !important;
    }

    .location-container {
        padding: 0;
        background: white;
    }

    .table-card {
        box-shadow: none;
        border: 1px solid #e2e8f0;
    }

    .data-table tbody tr {
        box-shadow: none;
        border: 1px solid #e2e8f0;
    }

    .page-header {
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 16px;
    }
}

/* =====================================================
   ACCESSIBILITY
   ===================================================== */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

/* Focus visible styles for keyboard navigation */
button:focus-visible,
.btn-primary-custom:focus-visible,
.btn-edit:focus-visible,
.btn-delete:focus-visible,
.modal-close:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

.form-group input:focus-visible,
.form-group select:focus-visible,
.form-group textarea:focus-visible {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .data-table tbody tr {
        border: 1px solid #1e293b;
    }

    .badge-active,
    .badge-inactive {
        border: 2px solid currentColor;
    }

    .btn-edit,
    .btn-delete {
        border: 2px solid currentColor;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

<main class="main-content">
    <div class="location-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title-section">
                <h1>Location Management</h1>
            </div>
            <button class="btn-primary-custom" onclick="openCreateModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                Add Location
            </button>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
                </svg>
                <?php
                    if ($_GET['success'] == 'created') echo 'Location created successfully!';
                    elseif ($_GET['success'] == 'updated') echo 'Location updated successfully!';
                    elseif ($_GET['success'] == 'deleted') echo 'Location deleted successfully!';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filter-card">
            <div class="filter-grid">
                <div class="filter-group">
                    <input type="text" id="searchLocations" placeholder="🔍 Search locations...">
                </div>
                <div class="filter-group">
                    <select id="countryFilter">
                        <option value="">All Countries</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?php echo htmlspecialchars($country); ?>"><?php echo htmlspecialchars($country); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Locations Table -->
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>State</th>
                        <th>City</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="locationsTableBody">
                    <?php while($location = mysqli_fetch_assoc($run_locations)): ?>
                        <tr data-country="<?php echo htmlspecialchars($location['country']); ?>" 
                            data-state="<?php echo htmlspecialchars($location['state']); ?>" 
                            data-city="<?php echo htmlspecialchars($location['city']); ?>" 
                            data-status="<?php echo $location['status']; ?>">
                            <td><strong><?php echo htmlspecialchars($location['country']); ?></strong></td>
                            <td><?php echo htmlspecialchars($location['state']); ?></td>
                            <td><?php echo htmlspecialchars($location['city']); ?></td>
                            <td>
                                <span class="badge-status <?php echo $location['status'] == 'Active' ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $location['status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($location['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-edit" onclick="editLocation(<?php echo $location['id']; ?>, '<?php echo htmlspecialchars($location['country']); ?>', '<?php echo htmlspecialchars($location['state']); ?>', '<?php echo htmlspecialchars($location['city']); ?>', '<?php echo $location['status']; ?>', '<?php echo htmlspecialchars($location['description'] ?? ''); ?>')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
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
</main>

<!-- Create/Edit Modal -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal()"></div>
<div class="modal-container" id="locationModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                Add Location
            </h2>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>

        <form method="post" id="locationForm">
            <input type="hidden" name="location_id" id="location_id">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="country">Country</label>
                    <select name="country" id="country" required>
                        <option value="">--Select Country--</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?php echo htmlspecialchars($country); ?>"><?php echo htmlspecialchars($country); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="state">State</label>
                    <input type="text" name="state" id="state" placeholder="Enter state" required>
                </div>

                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" name="city" id="city" placeholder="Enter city" required>
                </div>

                <div class="form-group">
                    <label for="location_status">Status</label>
                    <select name="location_status" id="location_status" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="description">Description (Optional)</label>
                    <input type="text" name="description" id="description" placeholder="Enter description">
                </div>

                <button type="submit" name="submit_location" id="submitBtn" class="btn-submit">
                    <span id="submitBtnText">Add Location</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-container" id="deleteModal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2 class="modal-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="#ef4444">
                    <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                </svg>
                Confirm Deletion
            </h2>
            <button class="modal-close" onclick="closeDeleteModal()">✕</button>
        </div>
        <p style="color: #64748b; margin-bottom: 24px;">Are you sure you want to delete this location? This action cannot be undone.</p>
        <form method="post">
            <input type="hidden" name="delete_location_id" id="delete_location_id">
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeDeleteModal()" style="flex: 1; padding: 12px; border: 2px solid #e2e8f0; background: white; border-radius: 12px; cursor: pointer;">Cancel</button>
                <button type="submit" name="delete_location" style="flex: 1; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 12px; cursor: pointer; font-weight: 600;">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('locationForm').reset();
    document.getElementById('location_id').value = '';
    document.getElementById('submitBtn').name = 'submit_location';
    document.getElementById('submitBtnText').textContent = 'Add Location';
    document.getElementById('modalTitle').innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>Add Location';
    openModal();
}

function editLocation(id, country, state, city, status, description) {
    document.getElementById('location_id').value = id;
    document.getElementById('country').value = country;
    document.getElementById('state').value = state;
    document.getElementById('city').value = city;
    document.getElementById('location_status').value = status;
    document.getElementById('description').value = description;
    document.getElementById('submitBtn').name = 'update_location';
    document.getElementById('submitBtnText').textContent = 'Update Location';
    document.getElementById('modalTitle').innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>Update Location';
    openModal();
}

function deleteLocation(id) {
    document.getElementById('delete_location_id').value = id;
    document.getElementById('deleteModal').style.display = 'flex';
    document.getElementById('modalOverlay').style.display = 'block';
}

function openModal() {
    document.getElementById('locationModal').style.display = 'flex';
    document.getElementById('modalOverlay').style.display = 'block';
}

function closeModal() {
    document.getElementById('locationModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}

// Search and filter functionality
document.getElementById('searchLocations').addEventListener('keyup', filterTable);
document.getElementById('countryFilter').addEventListener('change', filterTable);
document.getElementById('statusFilter').addEventListener('change', filterTable);

function filterTable() {
    const searchValue = document.getElementById('searchLocations').value.toLowerCase();
    const countryValue = document.getElementById('countryFilter').value;
    const statusValue = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#locationsTableBody tr');

    rows.forEach(row => {
        const country = row.getAttribute('data-country').toLowerCase();
        const state = row.getAttribute('data-state').toLowerCase();
        const city = row.getAttribute('data-city').toLowerCase();
        const status = row.getAttribute('data-status');
        
        let show = true;

        if (searchValue && !country.includes(searchValue) && !state.includes(searchValue) && !city.includes(searchValue)) {
            show = false;
        }

        if (countryValue && row.getAttribute('data-country') !== countryValue) {
            show = false;
        }

        if (statusValue && status !== statusValue) {
            show = false;
        }

        row.style.display = show ? '' : 'none';
    });
}
</script>