<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

// ── Handle vendor assignment form submission ──────────────────────────────────
if (isset($_POST['assign_vendors'])) {
    $event_id = intval($_POST['event_id']);

    // Fetch event budget for server-side guard
    $budget_row   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT budget FROM events WHERE id = $event_id LIMIT 1"));
    $event_budget = $budget_row ? floatval($budget_row['budget']) : PHP_INT_MAX;

    // Sum all submitted numeric amounts
    $submitted_total = 0;
    foreach (($_POST['amount'] ?? []) as $amt) {
        if (is_numeric($amt)) $submitted_total += floatval($amt);
    }

    if ($submitted_total > $event_budget) {
        $assign_error = sprintf(
            "Total assigned amount \u20b9%s exceeds the event budget of \u20b9%s. Please reduce the amounts before saving.",
            number_format($submitted_total, 2),
            number_format($event_budget, 2)
        );
    } else {
        // Delete existing assignments for this event
        mysqli_query($conn, "DELETE FROM event_vendors WHERE event_id = $event_id");

        $success_count = 0;
        $assignments = $_POST['vendor_assignment'] ?? []; // keyed: "ServiceName||RequirementType"

        foreach ($assignments as $key => $vendor_id) {
            if (empty($vendor_id)) continue;

            [$service_name, $requirement_type] = explode('||', $key, 2);
            $service_name     = mysqli_real_escape_string($conn, trim($service_name));
            $requirement_type = mysqli_real_escape_string($conn, trim($requirement_type));
            $vendor_id        = intval($vendor_id);
            $req_count        = intval($_POST['req_count'][$key] ?? 1);
            $amount           = mysqli_real_escape_string($conn, $_POST['amount'][$key] ?? '');
            $notes            = mysqli_real_escape_string($conn, $_POST['notes'][$key] ?? '');

            $sql = "INSERT INTO event_vendors
                        (event_id, vendor_id, service_name, requirement_type, requirement_count, amount, notes)
                    VALUES
                        ($event_id, $vendor_id, '$service_name', '$requirement_type', $req_count,
                         " . (is_numeric($amount) ? $amount : 'NULL') . ", '$notes')";

            if (mysqli_query($conn, $sql)) $success_count++;
        }

        if ($success_count > 0) {
            $assign_success = "$success_count vendor assignment(s) saved successfully!";
        } else {
            $assign_error = "No vendors were assigned. Please select at least one vendor.";
        }
    }
}

// ── Fetch all events with client info ─────────────────────────────────────────
$events_result = mysqli_query(
    $conn,
    "SELECT e.*, c.client_id
     FROM events e
     LEFT JOIN clients c ON e.client_name = c.client_name
     ORDER BY e.start_date DESC"
);
$all_events = [];
while ($row = mysqli_fetch_assoc($events_result)) $all_events[] = $row;

// ── Fetch all active vendors ──────────────────────────────────────────────────
$vendors_result = mysqli_query(
    $conn,
    "SELECT * FROM vendors WHERE status = 'Active' ORDER BY service_type, vendor_name"
);
$all_vendors = [];
while ($row = mysqli_fetch_assoc($vendors_result)) $all_vendors[] = $row;

// ── Fetch existing assignments grouped by event → service → requirement ───────
$asgn_result = mysqli_query(
    $conn,
    "SELECT ev.*, v.vendor_name, v.service_type, v.contact_phone
     FROM event_vendors ev
     JOIN vendors v ON ev.vendor_id = v.vendor_id
     ORDER BY ev.event_id, ev.service_name, ev.requirement_type"
);
$existing_assignments = [];  // [event_id][service_name][requirement_type] = row
while ($row = mysqli_fetch_assoc($asgn_result)) {
    $existing_assignments[$row['event_id']][$row['service_name']][$row['requirement_type']] = $row;
}
?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .coordination-container {
        background: #f1f5f9;
        min-height: 100vh;
    }

    /* ── Page header ── */
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
        font-size: 15px;
        color: #64748b;
    }

    /* ── Search ── */
    .search-bar {
        position: relative;
        margin-bottom: 24px;
        max-width: 400px;
    }

    .search-bar input {
        width: 100%;
        padding: 11px 16px 11px 44px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-family: 'Lexend', sans-serif;
        background: white;
        color: #1e293b;
        transition: border-color .2s;
        box-sizing: border-box;
    }

    .search-bar input:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, .1);
    }

    .search-bar svg {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        width: 18px;
        height: 18px;
    }

    /* ── Table card ── */
    .table-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .06), 0 4px 16px rgba(0, 0, 0, .04);
        overflow: hidden;
        margin-bottom: 24px;
    }

    .table-responsive {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead tr {
        background: #0ea5e9;
        border-bottom: 2px solid #e2e8f0;
    }

    thead th {
        padding: 14px 18px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        font-family: 'Outfit', sans-serif;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: .5px;
        white-space: nowrap;
    }

    tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background .15s;
    }

    tbody tr:last-child {
        border-bottom: none;
    }

    tbody tr:hover {
        background: #f8fafc;
    }

    tbody td {
        padding: 14px 18px;
        font-size: 14px;
        color: #334155;
        font-family: 'Lexend', sans-serif;
        vertical-align: middle;
    }

    .event-name-cell {
        font-weight: 600;
        color: #1e293b;
        font-family: 'Outfit', sans-serif;
    }

    .client-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #eff6ff;
        color: #1e293b;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Outfit', sans-serif;
    }

    .date-range {
        font-size: 13px;
        color: #64748b;
    }

    .date-range span {
        color: #1e293b;
        font-weight: 600;
    }

    /* ── Service tags ── */
    .services-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .service-tag {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        font-family: 'Outfit', sans-serif;
    }

    .service-tag.catering {
        background: #fef3c7;
        color: #92400e;
    }

    .service-tag.decoration {
        background: #fce7f3;
        color: #9d174d;
    }

    .service-tag.photography {
        background: #e0e7ff;
        color: #3730a3;
    }

    .service-tag.default {
        background: #f0fdf4;
        color: #166534;
    }

    /* ── Assign button ── */
    .btn-assign {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #0ea5e9;
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all .2s;
        box-shadow: 0 2px 6px rgba(14, 165, 233, .3);
        text-decoration: none;
    }

    .btn-assign:hover {
        background: #0284c7;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(14, 165, 233, .4);
    }

    .btn-assign.assigned {
        background: #caeaff;
        border: 1px solid #7dd3fc;
    }

    .btn-assign.assigned:hover {
        background: #b1e1ff;
    }

    /* ── Modal ── */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .55);
        backdrop-filter: blur(4px);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal {
        background: white;
        border-radius: 20px;
        width: 100%;
        max-width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 60px rgba(0, 0, 0, .2);
        animation: modalIn .3s ease;
    }

    @keyframes modalIn {
        from {
            opacity: 0;
            transform: translateY(20px) scale(.97)
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
        padding: 24px 28px 20px;
        border-bottom: 1px solid #f1f5f9;
        position: sticky;
        top: 0;
        background: #0ea5e9;
        z-index: 10;
    }

    .modal-header-left h2 {
        font-family: 'Outfit', sans-serif;
        font-size: 22px;
        font-weight: 700;
        color: #ffffff;
        margin: 0 0 4px;
    }

    .modal-header-left p {
        font-size: 13px;
        color: #ffffff;
        margin: 0;
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

    .modal-body {
        padding: 24px 28px;
    }

    /* ── Event info card ── */
    .event-info-card {
        background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 14px;
    }

    .info-item label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #94a3b8;
        font-family: 'Outfit', sans-serif;
        margin-bottom: 4px;
    }

    .info-item span {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        font-family: 'Lexend', sans-serif;
    }

    /* ── Service group header ── */
    .service-group-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        background: #0ea5e9;
        border-bottom: 2px solid #a4cbff;
    }

    .service-group-title {
        font-family: 'Outfit', sans-serif;
        font-size: 13px;
        font-weight: 700;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .service-group-icon {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }

    /* ── Sub-requirement rows ── */
    .req-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .req-table thead tr {
        background: #0ea5e9;
    }

    .req-table thead th {
        padding: 10px 14px;
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: .5px;
        border-bottom: 1.5px solid #e2e8f0;
    }

    .req-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
    }

    .req-table tbody tr:last-child {
        border-bottom: none;
    }

    .req-table tbody td {
        padding: 10px 14px;
        font-size: 13px;
        color: #334155;
        font-family: 'Lexend', sans-serif;
        vertical-align: middle;
    }

    .req-label {
        font-weight: 600;
        color: #1e293b;
        font-family: 'Outfit', sans-serif;
    }

    .req-count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e0e7ff;
        color: #3730a3;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 9px;
        margin-left: 6px;
        font-family: 'Outfit', sans-serif;
    }

    /* ── Inputs ── */
    .vendor-select,
    .notes-input,
    .amount-input {
        width: 100%;
        padding: 8px 12px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        font-family: 'Lexend', sans-serif;
        color: #334155;
        background: white;
        transition: border-color .2s;
        box-sizing: border-box;
    }

    .vendor-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 32px;
        cursor: pointer;
    }

    .vendor-select:focus,
    .notes-input:focus,
    .amount-input:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, .1);
    }

    .amount-input {
        max-width: 130px;
    }

    /* ── Badges ── */
    .assigned-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #dcfce7;
        color: #166534;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        font-family: 'Outfit', sans-serif;
    }

    .assigned-badge svg {
        width: 11px;
        height: 11px;
    }

    .no-vendor-warn {
        font-size: 10px;
        color: #f59e0b;
        margin-top: 4px;
    }

    /* ── Alerts ── */
    .alert {
        padding: 12px 18px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 14px;
        font-family: 'Lexend', sans-serif;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    /* ── Empty state ── */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }

    .empty-state svg {
        width: 48px;
        height: 48px;
        margin-bottom: 12px;
        opacity: .4;
    }

    .empty-state h3 {
        font-family: 'Outfit', sans-serif;
        font-size: 18px;
        color: #64748b;
        margin-bottom: 6px;
    }

    .empty-state p {
        font-size: 14px;
    }

    /* ── Modal footer ── */
    .modal-footer {
        padding: 16px 28px 24px;
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        border-top: 1px solid #f1f5f9;
        position: sticky;
        bottom: 0;
        background: white;
    }

    .btn-cancel {
        padding: 10px 22px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        background: white;
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all .2s;
    }

    .btn-cancel:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .btn-submit {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 22px;
        background: #0ea5e9;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all .2s;
        box-shadow: 0 2px 8px rgba(14, 165, 233, .3);
    }

    .btn-submit:hover {
        background: #0284c7;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(14, 165, 233, .4);
    }

    /* ── View Assignment Button ── */
    .btn-view-assign {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f0fdf4;
        color: #166534;
        padding: 8px 16px;
        border: 1.5px solid #bbf7d0;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all .2s;
    }

    .btn-view-assign:hover {
        background: #dcfce7;
        border-color: #86efac;
        transform: translateY(-1px);
    }

    /* ── View Modal specific ── */
    .view-service-block {
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .view-req-row {
        display: grid;
        grid-template-columns: 1.2fr 1.5fr 0.8fr 1.5fr;
        gap: 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .view-req-row:last-child {
        border-bottom: none;
    }

    .view-req-row>div {
        padding: 12px 16px;
        font-size: 13px;
        font-family: 'Lexend', sans-serif;
        color: #334155;
        border-right: 1px solid #f1f5f9;
    }

    .view-req-row>div:last-child {
        border-right: none;
    }

    .view-req-label {
        font-weight: 700;
        color: #1e293b;
        font-family: 'Outfit', sans-serif;
        font-size: 13px;
    }

    .view-req-sub {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 2px;
    }

    .view-vendor-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #eff6ff;
        color: #1d4ed8;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        font-family: 'Outfit', sans-serif;
    }

    .view-amount {
        font-weight: 700;
        color: #059669;
        font-size: 14px;
    }

    .view-notes {
        font-size: 12px;
        color: #64748b;
        font-style: italic;
    }

    .view-col-header {
        display: grid;
        grid-template-columns: 1.2fr 1.5fr 0.8fr 1.5fr;
        background: #0ea5e9;
        border-bottom: 2px solid #e2e8f0;
    }

    .view-col-header>span {
        padding: 9px 16px;
        font-size: 11px;
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: .5px;
        border-right: 1px solid #e2e8f0;
    }

    .view-col-header>span:last-child {
        border-right: none;
    }

    /* ── Budget tracker ── */
    .budget-tracker {
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
    }

    .budget-tracker.over-budget {
        background: #fff1f2;
        border-color: #fca5a5;
        animation: shake .3s ease;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-4px);
        }

        75% {
            transform: translateX(4px);
        }
    }

    .budget-stat {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 110px;
    }

    .budget-stat-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #94a3b8;
        font-family: 'Outfit', sans-serif;
    }

    .budget-stat-value {
        font-size: 16px;
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
        color: #1e293b;
        transition: color .2s;
    }

    .budget-stat-value.over {
        color: #dc2626;
    }

    .budget-stat-value.safe {
        color: #059669;
    }

    .budget-bar-wrap {
        flex: 1;
        min-width: 160px;
    }

    .budget-bar-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #94a3b8;
        font-family: 'Outfit', sans-serif;
        margin-bottom: 5px;
    }

    .budget-bar-track {
        height: 10px;
        background: #e2e8f0;
        border-radius: 99px;
        overflow: hidden;
    }

    .budget-bar-fill {
        height: 100%;
        border-radius: 99px;
        background: linear-gradient(90deg, #22c55e, #16a34a);
        transition: width .25s ease, background .25s ease;
    }

    .budget-bar-fill.over {
        background: linear-gradient(90deg, #f87171, #dc2626);
    }

    .budget-over-msg {
        display: none;
        font-size: 12px;
        font-weight: 600;
        color: #dc2626;
        font-family: 'Lexend', sans-serif;
        margin-top: 4px;
    }

    .budget-over-msg.visible {
        display: block;
    }

    @media (max-width:768px) {
        .modal {
            max-width: 100%;
            border-radius: 16px 16px 0 0;
        }

        .modal-overlay {
            align-items: flex-end;
            padding: 0;
        }

        .event-info-card {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<div class="main-content coordination-container">

    <?php if (isset($assign_success)): ?>
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                <polyline points="22 4 12 14.01 9 11.01" />
            </svg>
            <?= htmlspecialchars($assign_success) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($assign_error)): ?>
        <div class="alert alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            <?= htmlspecialchars($assign_error) ?>
        </div>
    <?php endif; ?>

    <!-- Search -->
    <div class="search-bar">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" />
        </svg>
        <input type="text" id="searchInput" placeholder="Search by event or client..." oninput="filterRows()">
    </div>

    <!-- Events Table -->
    <div class="table-card">
        <div class="table-responsive">
            <table id="eventsTable">
                <thead>
                    <tr>
                        <th>Sr</th>
                        <th>Date</th>
                        <th>Event</th>
                        <th>Venue</th>
                        <th>Client</th>
                        <th>Services</th>
                        <!-- <th>Status</th> -->
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_events)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="3" y="4" width="18" height="18" rx="2" />
                                        <line x1="16" y1="2" x2="16" y2="6" />
                                        <line x1="8" y1="2" x2="8" y2="6" />
                                        <line x1="3" y1="10" x2="21" y2="10" />
                                    </svg>
                                    <h3>No Events Found</h3>
                                    <p>Create events first to assign vendors.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $sno = 1;
                        foreach ($all_events as $event): ?>
                            <?php
                            $event_id      = $event['id'];
                            $services_data = json_decode($event['service'] ?? '[]', true) ?: [];
                            $has_assignment = !empty($existing_assignments[$event_id]);
                            $start = date('d M Y', strtotime($event['start_date']));
                            $end   = date('d M Y', strtotime($event['end_date']));
                            ?>
                            <tr class="event-row" data-search="<?= strtolower(htmlspecialchars($event['name'] . ' ' . $event['client_name'])) ?>">
                                <td><?= $sno++ ?></td>
                                <td>
                                    <div class="date-range">
                                        <span><?= $start ?></span>
                                        <?php if ($start !== $end): ?> &rarr; <span><?= $end ?></span><?php endif; ?>
                                    </div>
                                    <?php if ($event['start_time']): ?>
                                        <div style="font-size:11px;color:#94a3b8;margin-top:2px">
                                            <?= date('h:i A', strtotime($event['start_time'])) ?> – <?= date('h:i A', strtotime($event['end_time'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="event-name-cell"><?= htmlspecialchars($event['name']) ?></div>
                                </td>
                                <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($event['venue']) ?>">
                                    <?= htmlspecialchars($event['venue'] ?? '—') ?>
                                </td>
                                <td>
                                    <span class="client-badge">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                            <circle cx="12" cy="7" r="4" />
                                        </svg>
                                        <?= htmlspecialchars($event['client_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="services-tags">
                                        <?php foreach ($services_data as $svc):
                                            $sname = strtolower($svc['service'] ?? '');
                                            $cls = str_contains($sname, 'cater') ? 'catering' : (str_contains($sname, 'decor') ? 'decoration' : (str_contains($sname, 'photo') ? 'photography' : 'default'));
                                        ?>
                                            <span class="service-tag <?= $cls ?>"><?= htmlspecialchars($svc['service'] ?? '') ?></span>
                                        <?php endforeach; ?>
                                        <?php if (empty($services_data)): ?>
                                            <span style="color:#94a3b8;font-size:12px">No services</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <!-- <td>
                                    <?php if ($has_assignment): ?>
                                        <span class="assigned-badge">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <polyline points="20 6 9 17 4 12" />
                                            </svg>
                                            Assigned
                                        </span>
                                    <?php else: ?>
                                        <span style="font-size:12px;color:#94a3b8">Pending</span>
                                    <?php endif; ?>
                                </td> -->
                                <td>
                                    <div style="display:flex;gap:8px;align-items:center;">
                                        <button class="btn-assign <?= $has_assignment ? 'assigned' : '' ?>"
                                            onclick="openAssignModal(
                                            <?= htmlspecialchars(json_encode($event), ENT_QUOTES) ?>,
                                            <?= htmlspecialchars(json_encode($existing_assignments[$event_id] ?? []), ENT_QUOTES) ?>
                                        )">

                                            <?= $has_assignment ? '<i class="fa-regular fa-pen-to-square" style="color:blue"></i>' : '<i class="fa-solid fa-user-plus"> </i> Assign' ?>
                                        </button>

                                        <?php if ($has_assignment): ?>
                                            <button class="btn-view-assign"
                                                onclick="openViewModal(
                                                    <?= htmlspecialchars(json_encode($event), ENT_QUOTES) ?>,
                                                    <?= htmlspecialchars(json_encode($existing_assignments[$event_id] ?? []), ENT_QUOTES) ?>
                                                )">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Assign Vendors Modal ───────────────────────────────────────────────── -->
<div class="modal-overlay" id="assignModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-header-left">
                <h2 id="modalEventTitle">Assign Vendors</h2>
                <p id="modalEventSubtitle">Assign a vendor to each sub-requirement</p>
            </div>
            <button class="modal-close" onclick="closeModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <form method="POST" id="assignForm">
            <input type="hidden" name="assign_vendors" value="1">
            <input type="hidden" name="event_id" id="modalEventId">

            <div class="modal-body">
                <div class="event-info-card" id="modalEventInfo"></div>

                <!-- ── Live Budget Tracker ── -->
                <div class="budget-tracker" id="budgetTracker">
                    <div class="budget-stat">
                        <span class="budget-stat-label">Event Budget</span>
                        <span class="budget-stat-value" id="bt_budget">₹0</span>
                    </div>
                    <div class="budget-stat">
                        <span class="budget-stat-label">Total Assigned</span>
                        <span class="budget-stat-value" id="bt_assigned">₹0</span>
                    </div>
                    <div class="budget-stat">
                        <span class="budget-stat-label">Remaining</span>
                        <span class="budget-stat-value safe" id="bt_remaining">₹0</span>
                    </div>
                    <div class="budget-bar-wrap">
                        <div class="budget-bar-label">Budget Used</div>
                        <div class="budget-bar-track">
                            <div class="budget-bar-fill" id="bt_bar" style="width:0%"></div>
                        </div>
                        <div class="budget-over-msg" id="bt_over_msg">
                            ⚠️ Over budget! Please reduce assigned amounts.
                        </div>
                    </div>
                </div>

                <div id="serviceGroups"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                    Save Assignments
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── View Assignment Modal ─────────────────────────────────────────────── -->
<div class="modal-overlay" id="viewModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-header-left">
                <h2 id="viewModalTitle">Assignment Summary</h2>
                <p id="viewModalSubtitle"></p>
            </div>
            <button class="modal-close" onclick="closeViewModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="event-info-card" id="viewModalEventInfo"></div>
            <div id="viewServiceGroups"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeViewModal()">Close</button>
        </div>
    </div>
</div>

<script>
    // All active vendors passed from PHP
    const allVendors = <?= json_encode($all_vendors) ?>;

    // ── Helpers ──────────────────────────────────────────────────────────────────
    function svcIcon(name) {
        const n = (name || '').toLowerCase();
        if (n.includes('cater')) return {
            emoji: '🍽️',
            bg: '#fef3c7'
        };
        if (n.includes('decor')) return {
            emoji: '🎀',
            bg: '#fce7f3'
        };
        if (n.includes('photo')) return {
            emoji: '📷',
            bg: '#e0e7ff'
        };
        return {
            emoji: '⭐',
            bg: '#f0fdf4'
        };
    }

    /**
     * Filter vendors whose service_type matches the PARENT service name.
     * e.g., requirement "Desserts" under service "Catering"
     *       → show vendors where service_type == "Catering"
     */
    function vendorsForService(serviceName) {
        const sn = (serviceName || '').toLowerCase().trim();
        return allVendors.filter(v => {
            const vt = (v.service_type || '').toLowerCase().trim();
            return vt === sn || vt.includes(sn) || sn.includes(vt);
        });
    }

    // ── Open modal ───────────────────────────────────────────────────────────────
    // Current event budget used by the tracker
    let _modalBudget = 0;

    function openAssignModal(event, existingAssignments) {
        document.getElementById('modalEventId').value = event.id;
        // Store budget for live tracker
        _modalBudget = parseFloat(event.budget) || 0;
        document.getElementById('bt_budget').textContent = '\u20b9' + _modalBudget.toLocaleString('en-IN');
        document.getElementById('modalEventTitle').textContent = event.name + ' — Vendor Assignment';
        document.getElementById('modalEventSubtitle').textContent =
            'Client: ' + event.client_name + ' | Assign a vendor to each sub-requirement below';

        // Event info card
        const sd = new Date(event.start_date).toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
        const ed = new Date(event.end_date).toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
        document.getElementById('modalEventInfo').innerHTML = `
        <div class="info-item"><label>Client</label><span>${event.client_name}</span></div>
        <div class="info-item"><label>Start Date</label><span>${sd}</span></div>
        <div class="info-item"><label>End Date</label><span>${ed}</span></div>
        <div class="info-item"><label>Venue</label><span>${event.venue || '—'}</span></div>
        <div class="info-item"><label>Budget</label><span>₹${parseInt(event.budget).toLocaleString('en-IN')}</span></div>`;

        // Parse services
        let services = [];
        try {
            services = JSON.parse(event.service || '[]');
        } catch (e) {}

        const container = document.getElementById('serviceGroups');
        container.innerHTML = '';

        if (!services.length) {
            container.innerHTML = `<div style="text-align:center;color:#94a3b8;padding:40px;">No services defined for this event.</div>`;
            document.getElementById('assignModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            return;
        }

        services.forEach(svc => {
            const serviceName = svc.service || '';
            const requirements = svc.requirements || [];
            const icon = svcIcon(serviceName);

            // Vendors for this service type (filtered by parent service)
            const svcVendors = vendorsForService(serviceName);
            const noMatch = svcVendors.length === 0;
            const vendorPool = noMatch ? allVendors : svcVendors;

            // Existing assignments for this service: existingAssignments[serviceName][reqType]
            const svcExisting = (existingAssignments[serviceName] || {});

            // Build rows for each requirement
            let rows = '';
            if (!requirements.length) {
                rows = `<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:20px;font-size:13px;">No sub-requirements defined.</td></tr>`;
            } else {
                requirements.forEach(req => {
                    const reqType = req.type || '';
                    const reqCount = req.count || 1;
                    const formKey = `${serviceName}||${reqType}`; // composite key

                    const existing = svcExisting[reqType] || null;
                    const selVendorId = existing ? existing.vendor_id : '';
                    const selAmount = existing ? (existing.amount || '') : '';
                    const selNotes = existing ? (existing.notes || '') : '';

                    // Build <option> list
                    let options = `<option value="">— Select Vendor —</option>`;
                    vendorPool.forEach(v => {
                        const sel = (v.vendor_id == selVendorId) ? 'selected' : '';
                        options += `<option value="${v.vendor_id}" ${sel}>${v.vendor_name} `;
                    });

                    const assignedChip = existing ?
                        `<div style="margin-top:5px"><span class="assigned-badge"><svg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5'><polyline points='20 6 9 17 4 12'/></svg>${existing.vendor_name}</span></div>` :
                        '';

                    rows += `
                <tr>
                    <td>
                        <div class="req-label">${reqType}</div>
                        ${assignedChip}
                    </td>
                    <td>
                        <select name="vendor_assignment[${formKey}]" class="vendor-select">
                            ${options}
                        </select>
                        
                        ${allVendors.length === 0
                            ? `<div style="font-size:11px;color:#ef4444;margin-top:4px">No active vendors available</div>`
                            : ''}
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            ${req.amount
                                ? `<div style="display:flex;flex-direction:column;gap:2px;">
                                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.4px;">Budget</div>
                                    <div style="display:inline-flex;align-items:center;gap:4px;background:#ecfdf5;color:#065f46;border:1.5px solid #6ee7b7;border-radius:7px;padding:2px 10px;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif;">
                                        \u20b9${parseFloat(req.amount).toLocaleString('en-IN')}
                                    </div>
                                </div>`
                                : ''}
                            <input type="text" name="amount[${formKey}]" class="amount-input"
                                placeholder="\u20b9 Amount" value="${selAmount}"
                                oninput="recalcBudget()">
                        </div>
                    </td>
                    <td>
                        <input type="text" name="notes[${formKey}]" class="notes-input"
                               placeholder="Optional notes…" value="${selNotes}">
                    </td>
                </tr>`;
                });
            }

            // Wrap in service group
            const group = document.createElement('div');
            group.style.cssText = 'margin-bottom:20px; border:1.5px solid #e2e8f0; border-radius:12px; overflow:hidden;';
            group.innerHTML = `
            <div class="service-group-header">
                <span class="service-group-icon" style="background:${icon.bg}">${icon.emoji}</span>
                <span class="service-group-title">${serviceName}</span>
                <span style="margin-left:auto;font-size:11px;color:#ffffff;font-family:'Lexend',sans-serif;">
                    ${requirements.length} sub-requirement${requirements.length !== 1 ? 's' : ''}
                </span>
            </div>
            <table class="req-table">
                <thead>
                    <tr>
                        <th>Requirement</th>
                        <th>Assign Vendor</th>
                        <th>Amount (₹)</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>`;
            container.appendChild(group);
        });

        document.getElementById('assignModal').classList.add('active');
        document.body.style.overflow = 'hidden';

        // Initialise tracker with any pre-filled amounts
        recalcBudget();
    }

    // ── Recalculate live budget tracker ──────────────────────────────────────────
    function recalcBudget() {
        let total = 0;
        document.querySelectorAll('#serviceGroups .amount-input').forEach(inp => {
            const v = parseFloat(inp.value);
            if (!isNaN(v) && v > 0) total += v;
        });

        const remaining = _modalBudget - total;
        const pct = _modalBudget > 0 ? Math.min((total / _modalBudget) * 100, 100) : 0;
        const isOver = total > _modalBudget;

        document.getElementById('bt_assigned').textContent = '\u20b9' + total.toLocaleString('en-IN', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
        const remEl = document.getElementById('bt_remaining');
        remEl.textContent = (isOver ? '-' : '') + '\u20b9' + Math.abs(remaining).toLocaleString('en-IN', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
        remEl.className = 'budget-stat-value ' + (isOver ? 'over' : 'safe');

        const bar = document.getElementById('bt_bar');
        bar.style.width = pct + '%';
        bar.className = 'budget-bar-fill' + (isOver ? ' over' : '');

        const tracker = document.getElementById('budgetTracker');
        tracker.className = 'budget-tracker' + (isOver ? ' over-budget' : '');

        const overMsg = document.getElementById('bt_over_msg');
        overMsg.className = 'budget-over-msg' + (isOver ? ' visible' : '');

        // Enable/disable save button
        const saveBtn = document.querySelector('#assignForm .btn-submit');
        if (saveBtn) {
            saveBtn.disabled = isOver;
            saveBtn.style.opacity = isOver ? '.45' : '1';
            saveBtn.style.cursor = isOver ? 'not-allowed' : 'pointer';
            saveBtn.title = isOver ? 'Total exceeds budget. Please reduce amounts.' : '';
        }
    }

    // ── Form submit guard ────────────────────────────────────────────────────────
    document.getElementById('assignForm').addEventListener('submit', function(e) {
        let total = 0;
        document.querySelectorAll('#serviceGroups .amount-input').forEach(inp => {
            const v = parseFloat(inp.value);
            if (!isNaN(v) && v > 0) total += v;
        });
        if (_modalBudget > 0 && total > _modalBudget) {
            e.preventDefault();
            document.getElementById('budgetTracker').classList.add('over-budget');
            document.getElementById('bt_over_msg').classList.add('visible');
            document.getElementById('budgetTracker').scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    });

    // ── Close modal ──────────────────────────────────────────────────────────────
    function closeModal() {
        document.getElementById('assignModal').classList.remove('active');
        document.body.style.overflow = 'auto';
    }
    document.getElementById('assignModal').addEventListener('click', e => {
        if (e.target === document.getElementById('assignModal')) closeModal();
    });

    // ── Search filter ────────────────────────────────────────────────────────────
    function filterRows() {
        const val = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('.event-row').forEach(row => {
            row.style.display = row.dataset.search.includes(val) ? '' : 'none';
        });
    }

    // ── Open View Modal ──────────────────────────────────────────────────────────
    function openViewModal(event, existingAssignments) {
        document.getElementById('viewModalTitle').textContent = event.name + ' — Assignment Summary';
        document.getElementById('viewModalSubtitle').textContent = 'Client: ' + event.client_name;

        const sd = new Date(event.start_date).toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
        const ed = new Date(event.end_date).toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
        document.getElementById('viewModalEventInfo').innerHTML = `
        <div class="info-item"><label>Client</label><span>${event.client_name}</span></div>
        <div class="info-item"><label>Start Date</label><span>${sd}</span></div>
        <div class="info-item"><label>End Date</label><span>${ed}</span></div>
        <div class="info-item"><label>Venue</label><span>${event.venue || '—'}</span></div>
        <div class="info-item"><label>Budget</label><span>₹${parseInt(event.budget).toLocaleString('en-IN')}</span></div>`;

        let services = [];
        try {
            services = JSON.parse(event.service || '[]');
        } catch (e) {}

        const container = document.getElementById('viewServiceGroups');
        container.innerHTML = '';

        if (!services.length) {
            container.innerHTML = `<div style="text-align:center;color:#94a3b8;padding:40px;">No services defined for this event.</div>`;
        } else {
            let totalAmount = 0;
            let totalAssigned = 0;

            services.forEach(svc => {
                const serviceName = svc.service || '';
                const requirements = svc.requirements || [];
                const icon = svcIcon(serviceName);
                const svcExisting = existingAssignments[serviceName] || {};

                let rows = '';
                requirements.forEach(req => {
                    const reqType = req.type || '';
                    const existing = svcExisting[reqType] || null;
                    if (existing) totalAssigned++;

                    const vendorHtml = existing ?
                        `<span class="view-vendor-chip">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            ${existing.vendor_name}
                       </span>
                       <div style="font-size:11px;color:#94a3b8;margin-top:4px">${existing.service_type || ''}</div>` :
                        `<span style="color:#94a3b8;font-size:12px;font-style:italic;">Not assigned</span>`;

                    const amountHtml = existing && existing.amount ?
                        `<span class="view-amount">₹${parseFloat(existing.amount).toLocaleString('en-IN')}</span>` :
                        `<span style="color:#cbd5e1;font-size:12px;">—</span>`;

                    const notesHtml = existing && existing.notes ?
                        `<span class="view-notes">${existing.notes}</span>` :
                        `<span style="color:#cbd5e1;font-size:12px;">—</span>`;

                    if (existing && existing.amount) totalAmount += parseFloat(existing.amount) || 0;

                    rows += `
                <div class="view-req-row">
                    <div>
                        <div class="view-req-label">${reqType}</div>
                        <div class="view-req-sub">Count: ${req.count || 1}</div>
                    </div>
                    <div>${vendorHtml}</div>
                    <div>${amountHtml}</div>
                    <div>${notesHtml}</div>
                </div>`;
                });

                const block = document.createElement('div');
                block.className = 'view-service-block';
                block.innerHTML = `
                <div class="service-group-header">
                    <span class="service-group-icon" style="background:${icon.bg}">${icon.emoji}</span>
                    <span class="service-group-title">${serviceName}</span>
                    <span style="margin-left:auto;font-size:11px;color:#94a3b8;font-family:'Lexend',sans-serif;">
                        ${requirements.length} sub-requirement${requirements.length !== 1 ? 's' : ''}
                    </span>
                </div>
                <div class="view-col-header">
                    <span>Requirement</span><span>Vendor</span><span>Amount</span><span>Notes</span>
                </div>
                ${rows || '<div style="padding:16px;text-align:center;color:#94a3b8;font-size:13px;">No sub-requirements defined.</div>'}`;
                container.appendChild(block);
            });

            // Summary strip
            const summary = document.createElement('div');
            summary.style.cssText = 'background:linear-gradient(135deg,#f0fdf4,#eff6ff);border:1.5px solid #bbf7d0;border-radius:12px;padding:16px 20px;display:flex;gap:24px;flex-wrap:wrap;margin-bottom:4px;';
            summary.innerHTML = `
            <div><div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.5px;margin-bottom:4px;">Total Assigned</div>
                 <div style="font-size:20px;font-weight:700;color:#1e293b;font-family:'Outfit',sans-serif;">${totalAssigned}</div></div>
            <div><div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.5px;margin-bottom:4px;">Total Amount</div>
                 <div style="font-size:20px;font-weight:700;color:#059669;font-family:'Outfit',sans-serif;">₹${totalAmount.toLocaleString('en-IN')}</div></div>`;
            container.prepend(summary);
        }

        document.getElementById('viewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeViewModal() {
        document.getElementById('viewModal').classList.remove('active');
        document.body.style.overflow = 'auto';
    }
    document.getElementById('viewModal').addEventListener('click', e => {
        if (e.target === document.getElementById('viewModal')) closeViewModal();
    });
</script>