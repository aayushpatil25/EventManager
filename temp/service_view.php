<?php
// session_start();
include('config/db.php');

// Check authentication (uncomment when you have login system)
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit();
// }

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Add Service
if (isset($_POST['submit'])) {


    $name = trim($_POST['name']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;



    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO services(name, is_active) VALUES(?, ?)");
    $stmt->bind_param("si", $name, $is_active);

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

// Handle Update Service
if (isset($_POST['update'])) {



    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validate inputs
    if (empty($name)) {
        header('Location: services.php?error=name_required');
        exit();
    }


    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("UPDATE services SET name = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("sii", $name, $is_active, $id);

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
?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .clients-container {
        padding: 32px;
        background: #f1f5f9;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
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
        padding: 16px 20px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #ffffff;
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

    .company-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #f1f5f9;
        border-radius: 8px;
        font-size: 13px;
        color: #475569;
    }

    .company-badge svg {
        width: 14px;
        height: 14px;
        color: #64748b;
    }

    .events-count {
        font-weight: 500;
        color: #0ea5e9;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 2px solid #ef4444;
    }

    .action-btn {
        width: 50px;
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

    /* Service Cards Grid */
    .service-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }

    .service-card {
        display: flex;
        align-items: flex-start;
        flex-direction: row-reverse;
        justify-content: space-between;
        gap: 10px;
        background: white;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .service-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        border-color: #0ea5e9;
    }

    .service-card-header {
        display: flex;
        align-items: center;
        margin-bottom: 16px;
    }

    .service-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    }

    .service-icon svg {
        width: 24px;
        height: 24px;
        color: white;
    }

    .service-actions {
        display: flex;
        gap: 8px;
    }

    .edit-btn:hover {
        background: #dbeafe !important;
        color: #0284c7 !important;
    }

    .delete-btn:hover {
        background: #fee2e2 !important;
        color: #dc2626 !important;
    }

    .service-name {
        font-family: 'Outfit', sans-serif;
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 12px;
    }

    .service-amount {
        font-family: 'Outfit', sans-serif;
        font-size: 18px;
        font-weight: 500;
        color: #0d3040;
        background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Empty State */
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 16px;
        border: 2px dashed #e2e8f0;
    }

    .empty-state svg {
        width: 64px;
        height: 64px;
        color: #cbd5e1;
        margin-bottom: 16px;
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
        color: #94a3b8;
    }

    @media (max-width: 768px) {
        .service-cards {
            grid-template-columns: 1fr;
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

    /* Status Badges */
    .status-badge {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
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

    .service-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
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
    }
</style>

<main class="main-content">
    <div class="clients-container">
        <!-- Page Header -->
        <div class="page-header">

            <button class="btn-add-client" onclick="openModal()">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                </svg>
                Add Services
            </button>
        </div>

        <!-- Success Message -->
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
                </svg>
                Service details added successfully!
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
                    case 'invalid_amount':
                        $error_msg = 'Please enter a valid positive number for amount.';
                        break;
                    case 'csrf':
                        $error_msg = 'Security validation failed. Please try again.';
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

        <?php
        $services = [];
        $result = mysqli_query($conn, "SELECT id, name, is_active FROM services ORDER BY id DESC");
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $services[] = $row;
            }
        }
        ?>


        <!-- Services table -->
        <div class="table-container">
            <table class="clients-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Service Name</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($services)): ?>
                        <?php $i = 1;
                        foreach ($services as $service): ?>
                            <tr>
                                <td><?= $i++; ?></td>

                                <td>
                                    <div class="client-name"><?= htmlspecialchars($service['name']); ?></div>
                                </td>

                                <td>
                                    <?php if ($service['is_active']): ?>
                                        <span class="status-badge status-active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="action-buttons">
                                        <button
                                            class="action-btn edit-btn"
                                            onclick="editService(this)"
                                            data-id="<?= $service['id']; ?>"
                                            data-name="<?= htmlspecialchars($service['name'], ENT_QUOTES); ?>"
                                            data-active="<?= $service['is_active']; ?>">
                                            ✏️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding: 30px;">
                                No services found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


    </div>
</main>

<!-- Add Service Modal -->
<div class="modal-overlay" id="clientModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Add Services</h2>
            <button class="modal-close" onclick="closeModal()">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </button>
        </div>
        <form action="" method="post" id="clientForm">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-section">
                        <h4>
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                            </svg>
                            Services
                        </h4>
                    </div>

                    <div class="form-group">
                        <label for="name">Service Name</label>
                        <input type="text" name="name" id="name" placeholder="Enter service name" required>
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
                <button type="submit" name="submit" class="btn-submit">Add Services</button>
            </div>
        </form>
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
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-section">
                        <h4>
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                            </svg>
                            Service Details
                        </h4>
                    </div>

                    <div class="form-group">
                        <label for="edit_name">Service Name</label>
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
    // Open Add Service Modal
    function openModal() {
        document.getElementById('clientModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Close Add Service Modal
    function closeModal() {
        document.getElementById('clientModal').classList.remove('active');
        document.body.style.overflow = 'auto';
        document.getElementById('clientForm').reset();
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

    // FIXED: Edit Service Function - Opens modal with service data
    function editService(button) {
        // Get data from button's data attributes
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const isActive = button.getAttribute('data-active');

        // Set the values in the edit form
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_is_active').checked = (isActive === '1');

        // Open the edit modal
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
</script>