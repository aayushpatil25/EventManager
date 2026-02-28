<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['add_payment'])) {
    $event_id        = intval($_POST['event_id']);
    $amount_received = floatval($_POST['amount_received']);
    $payment_date    = mysqli_real_escape_string($conn, trim($_POST['payment_date']));
    $note            = mysqli_real_escape_string($conn, trim($_POST['note']));
    $payment_mode    = mysqli_real_escape_string($conn, trim($_POST['payment_mode']));
    $transaction_id  = mysqli_real_escape_string($conn, trim($_POST['transaction_id']));

    // If transaction_id is empty, auto-generate one
    if (empty($transaction_id)) {
        $transaction_id = 'TXN-' . strtoupper(uniqid());
    }

    // Check transaction_id uniqueness
    $txn_check = mysqli_query($conn, "SELECT id FROM payments WHERE transaction_id='$transaction_id'");
    if (mysqli_num_rows($txn_check) > 0) {
        $error = 'Transaction ID already exists. Please use a different Transaction ID.';
    } else {

        // Get planned amount for this event
        $ev_sql = "SELECT service, budget FROM events WHERE id='$event_id'";
        $ev_res = mysqli_query($conn, $ev_sql);
        $ev_row = mysqli_fetch_assoc($ev_res);
        $planned = 0;
        if (!empty($ev_row['service'])) {
            $svc_data = json_decode($ev_row['service'], true);
            if (is_array($svc_data)) {
                foreach ($svc_data as $svc) {
                    if (!empty($svc['requirements']) && is_array($svc['requirements'])) {
                        foreach ($svc['requirements'] as $req) {
                            $planned += floatval($req['amount'] ?? 0);
                        }
                    }
                }
            }
        }
        if ($planned == 0) $planned = floatval($ev_row['budget']);

        // Get already received amount
        $already_sql    = "SELECT COALESCE(SUM(amount_received), 0) as total FROM payments WHERE event_id='$event_id'";
        $already_result = mysqli_query($conn, $already_sql);
        $already_row    = mysqli_fetch_assoc($already_result);
        $already_paid   = floatval($already_row['total']);
        $remaining      = $planned - $already_paid;

        // Check vendors are assigned
        $vcheck = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM event_vendors WHERE event_id='$event_id'");
        $vrow   = mysqli_fetch_assoc($vcheck);
        if (intval($vrow['cnt']) === 0) {
            $error = 'Cannot record payment — no vendors have been assigned to this event yet.';
        } elseif ($amount_received <= 0) {
            $error = 'Payment amount must be greater than zero.';
        } elseif ($amount_received > $remaining) {
            $error = 'Payment of ₹' . number_format($amount_received, 0) . ' exceeds the remaining balance of ₹' . number_format($remaining, 0) . '. Payment not accepted.';
        } else {
            $sql = "INSERT INTO payments (event_id, amount_received, payment_date, note, payment_mode, transaction_id, created_at)
                    VALUES ('$event_id', '$amount_received', '$payment_date', '$note', '$payment_mode', '$transaction_id', NOW())";
            if (mysqli_query($conn, $sql)) {
                header('Location: payment.php?success=added');
                exit();
            } else {
                $error = 'Error: ' . mysqli_error($conn);
            }
        }
    }
}

// Handle Delete Payment
if (isset($_POST['delete_payment'])) {
    $payment_id = intval($_POST['payment_id']);
    if (mysqli_query($conn, "DELETE FROM payments WHERE id='$payment_id'")) {
        header('Location: payment.php?success=deleted');
        exit();
    } else {
        $error = 'Error: ' . mysqli_error($conn);
    }
}

$events_sql = "SELECT e.id, e.name, e.client_name, e.start_date, e.end_date, e.venue, e.service, e.budget,
                      c.contact,
                      COALESCE(SUM(ev.amount), 0) as planned_amount
               FROM events e
               LEFT JOIN clients c ON e.client_name = c.client_name
               INNER JOIN event_vendors ev ON ev.event_id = e.id
               GROUP BY e.id, e.name, e.client_name, e.start_date, e.end_date, 
                        e.venue, e.service, e.budget, c.contact
               ORDER BY e.start_date DESC";
$events_result = mysqli_query($conn, $events_sql);

$events = [];
while ($row = mysqli_fetch_assoc($events_result)) {
    $row['planned_amount'] = floatval($row['planned_amount']);
    $events[] = $row;
}

// Fetch payments grouped by event
$payments_sql = "SELECT p.*, e.name as event_name, e.client_name, e.start_date
                 FROM payments p
                 LEFT JOIN events e ON p.event_id = e.id
                 ORDER BY p.payment_date DESC";
$payments_result = mysqli_query($conn, $payments_sql);
$all_payments = [];
$received_by_event = [];
while ($pay = mysqli_fetch_assoc($payments_result)) {
    $all_payments[] = $pay;
    $received_by_event[$pay['event_id']] = ($received_by_event[$pay['event_id']] ?? 0) + floatval($pay['amount_received']);
}

// Fetch which events have vendors assigned
$vendors_sql = "SELECT DISTINCT event_id FROM event_vendors";
$vendors_result = mysqli_query($conn, $vendors_sql);
$events_with_vendors = [];
while ($vrow = mysqli_fetch_assoc($vendors_result)) {
    $events_with_vendors[] = $vrow['event_id'];
}

// Summary totals
$total_planned  = array_sum(array_column($events, 'planned_amount'));
$total_received = array_sum($received_by_event);
$total_balance  = $total_planned - $total_received;
?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .payments-container {
        background: #f1f5f9;
    }

    /* ── Top Summary Cards ── */
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-bottom: 28px;
    }

    .summary-card {
        background: white;
        border-radius: 16px;
        padding: 22px 26px;
        display: flex;
        align-items: center;
        gap: 18px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .06);
        border-left: 5px solid transparent;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, .09);
    }

    .summary-card.planned  { border-color: #0ea5e9; }
    .summary-card.received { border-color: #10b981; }
    .summary-card.balance  { border-color: #f59e0b; }

    .summary-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .summary-icon.planned  { background: #e0f2fe; color: #0284c7; }
    .summary-icon.received { background: #d1fae5; color: #059669; }
    .summary-icon.balance  { background: #fef3c7; color: #d97706; }

    .summary-icon svg { width: 26px; height: 26px; }

    .summary-info { flex: 1; }

    .summary-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: #64748b;
        margin-bottom: 4px;
    }

    .summary-value {
        font-family: 'Outfit', sans-serif;
        font-size: 26px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
    }

    .summary-sub { font-size: 12px; color: #94a3b8; margin-top: 4px; }

    /* ── Page Header ── */
    .page-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }

    .search-bar {
        position: relative;
        flex: 1;
        max-width: 340px;
    }

    .search-bar input {
        width: 100%;
        padding: 11px 16px 11px 42px;
        border: 1px solid #e2e8f0;
        border-radius: 11px;
        font-size: 14px;
        font-family: 'Lexend', sans-serif;
        background: white;
        transition: all .2s ease;
    }

    .search-bar input:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, .1);
    }

    .search-bar svg {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        color: #94a3b8;
    }

    .btn-primary {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 11px 22px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all .2s ease;
        background: #0ea5e9;
        color: white;
        box-shadow: 0 2px 8px rgba(14, 165, 233, .3);
    }

    .btn-primary:hover {
        background: #0284c7;
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(14, 165, 233, .4);
    }

    .btn-export {
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 11px 22px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all .2s ease;
    }

    .btn-export:hover { background: #dcfce7; transform: translateY(-2px); }

    .btn-print {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 11px 22px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all .2s ease;
    }

    .btn-print:hover { background: #dbeafe; transform: translateY(-2px); }

    /* ── Table ── */
    .table-container {
        background: white;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
        overflow: hidden;
        margin-bottom: 32px;
    }

    .section-title {
        font-family: 'Outfit', sans-serif;
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        padding: 20px 24px 0;
        margin-bottom: 0;
    }

    .payments-table { width: 100%; border-collapse: collapse; }

    .payments-table thead { background: #0ea5e9; }

    .payments-table th {
        padding: 14px 18px;
        text-align: left;
        font-size: 12px;
        font-weight: 700;
        color: white;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .payments-table td {
        padding: 16px 18px;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
        font-size: 14px;
        vertical-align: middle;
    }

    .payments-table tbody tr { transition: background .15s ease; }
    .payments-table tbody tr:hover { background: #f8fafc; }
    .payments-table tbody tr:last-child td { border-bottom: none; }

    .event-name-cell { font-weight: 600; color: #1e293b; }
    .event-sub { font-size: 12px; color: #94a3b8; margin-top: 2px; }

    .badge-client {
        display: inline-flex;
        align-items: center;
        background: #ede9fe;
        color: #6b21a8;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .amount-planned   { font-weight: 700; color: #0369a1; }
    .amount-received  { font-weight: 700; color: #059669; }
    .amount-balance-pos  { font-weight: 700; color: #d97706; }
    .amount-balance-zero { font-weight: 700; color: #10b981; }
    .amount-balance-neg  { font-weight: 700; color: #dc2626; }

    .progress-wrap { width: 100%; min-width: 100px; }

    .progress-bar-bg {
        background: #e2e8f0;
        border-radius: 99px;
        height: 8px;
        overflow: hidden;
        margin-bottom: 4px;
    }

    .progress-bar-fill {
        height: 100%;
        border-radius: 99px;
        background: linear-gradient(90deg, #10b981, #34d399);
        transition: width .4s ease;
    }

    .progress-pct { font-size: 11px; font-weight: 700; color: #64748b; }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .status-pill.paid    { background: #d1fae5; color: #065f46; }
    .status-pill.partial { background: #fef3c7; color: #92400e; }
    .status-pill.unpaid  { background: #fee2e2; color: #991b1b; }

    .status-pill::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    .action-buttons { display: flex; gap: 8px; }

    .btn-action {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all .2s ease;
    }

    .btn-action.add-pay  { background: #d1fae5; color: #059669; }
    .btn-action.add-pay:hover  { background: #a7f3d0; transform: translateY(-2px); }
    .btn-action.view-pay { background: #e0f2fe; color: #0369a1; }
    .btn-action.view-pay:hover { background: #bae6fd; transform: translateY(-2px); }
    .btn-action svg { width: 15px; height: 15px; }

    /* ── Alerts ── */
    .alert {
        padding: 13px 18px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success { background: #d1fae5; color: #065f46; border: 2px solid #10b981; }
    .alert-error   { background: #fee2e2; color: #991b1b; border: 2px solid #dc2626; }

    /* ── Modals ── */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .5);
        z-index: 9999;
        backdrop-filter: blur(4px);
        animation: fadeIn .3s ease;
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
        max-width: 560px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .3);
        animation: slideUp .3s ease;
    }

    .modal-lg { max-width: 860px; }

    .modal-header {
        background: #0284c7;
        padding: 22px 28px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-family: 'Outfit', sans-serif;
        font-size: 22px;
        font-weight: 700;
        color: white;
    }

    .modal-close {
        width: 34px;
        height: 34px;
        border: none;
        background: #ff6666;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .2s ease;
        color: white;
    }

    .modal-close:hover { background: #ff3333; transform: rotate(90deg); }
    .modal-close svg { width: 18px; height: 18px; }

    .modal-body   { padding: 24px 28px; }

    .modal-footer {
        padding: 18px 28px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
    }

    .form-group { display: flex; flex-direction: column; }
    .form-group.full-width { grid-column: 1 / -1; }

    .form-group label {
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #334155;
        margin-bottom: 7px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 11px 13px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-family: 'Lexend', sans-serif;
        background: #f8fafc;
        transition: all .2s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #0ea5e9;
        background: white;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, .1);
    }

    .form-group select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 13px center;
        padding-right: 38px;
    }

    /* Transaction ID field hint */
    .txn-hint {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 5px;
        font-style: italic;
    }

    .txn-badge {
        display: inline-block;
        background: #f0f9ff;
        border: 1px dashed #7dd3fc;
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 600;
        color: #0369a1;
        margin-top: 5px;
        font-family: monospace;
        letter-spacing: .5px;
    }

    .btn-cancel {
        padding: 11px 22px;
        border: 2px solid #e2e8f0;
        background: white;
        color: #64748b;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all .2s ease;
    }

    .btn-cancel:hover { background: #f8fafc; }

    .btn-submit {
        padding: 11px 22px;
        background: #0ea5e9;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all .2s ease;
    }

    .btn-submit:hover { background: #0284c7; transform: translateY(-2px); }

    .btn-danger {
        padding: 11px 22px;
        background: #dc2626;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Lexend', sans-serif;
        cursor: pointer;
        transition: all .2s ease;
    }

    .btn-danger:hover { background: #b91c1c; }

    /* Payment history inside modal */
    .payment-history-table { width: 100%; border-collapse: collapse; font-size: 13px; }

    .payment-history-table thead { background: #f1f5f9; }

    .payment-history-table th {
        padding: 10px 14px;
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .5px;
        border-bottom: 2px solid #e2e8f0;
    }

    .payment-history-table td {
        padding: 11px 14px;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
    }

    .payment-history-table tbody tr:last-child td { border-bottom: none; }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    .info-card {
        padding: 14px 16px;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        background: #f8fafc;
    }

    .info-card-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #64748b;
        margin-bottom: 4px;
    }

    .info-card-value {
        font-family: 'Outfit', sans-serif;
        font-size: 20px;
        font-weight: 700;
    }

    .info-card.planned  .info-card-value { color: #0369a1; }
    .info-card.received .info-card-value { color: #059669; }
    .info-card.balance  .info-card-value { color: #d97706; }

    .mode-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        background: #dbeafe;
        color: #1e40af;
    }

    .txn-id-cell {
        font-family: monospace;
        font-size: 12px;
        color: #0369a1;
        background: #f0f9ff;
        padding: 3px 8px;
        border-radius: 5px;
        border: 1px solid #bae6fd;
        display: inline-block;
    }

    @keyframes fadeIn { from { opacity: 0 } to { opacity: 1 } }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(40px) }
        to   { opacity: 1; transform: translateY(0) }
    }

    /* ── Print ── */
    @media print {
        .sidebar, .topbar, .page-header, .no-print,
        .action-buttons, .summary-cards { display: none !important; }
        .table-container { border-radius: 0 !important; }
        .payments-table th,
        .payments-table td {
            border: 1px solid #000 !important;
            color: #000 !important;
            font-size: 11px !important;
            padding: 7px !important;
        }
    }
</style>

<main class="main-content">
    <div class="payments-container">

        <!-- Page Header -->
        <div class="page-header">
            <div class="search-bar">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                </svg>
                <input type="text" id="searchInput" placeholder="Search events or clients…">
            </div>

            <select id="statusFilter" style="padding:11px 16px;border:1px solid #e2e8f0;border-radius:11px;font-size:14px;font-family:'Lexend',sans-serif;background:white;cursor:pointer;">
                <option value="">All Status</option>
                <option value="paid">Paid</option>
                <option value="partial">Partial</option>
                <option value="unpaid">Unpaid</option>
            </select>

            <button class="btn-export" onclick="exportExcel()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
                Export
            </button>
            <button class="btn-print" onclick="printTable()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                    <polyline points="6 9 6 2 18 2 18 9" />
                    <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" />
                    <rect x="6" y="14" width="12" height="8" />
                </svg>
                Print
            </button>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">⚠ <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_GET['success'] === 'added' ? '✓ Payment recorded successfully!' : '✓ Payment deleted.'; ?>
            </div>
        <?php endif; ?>

        <!-- Main Table -->
        <div class="table-container">
            <table class="payments-table" id="paymentsTable">
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <th>Event</th>
                        <th>Client</th>
                        <th>Payment Amount</th>
                        <th>Received Amount</th>
                        <th>Pending Amount</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center;padding:60px;color:#94a3b8;">No events found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($events as $i => $ev):
                            $received = $received_by_event[$ev['id']] ?? 0;
                            $balance  = $ev['planned_amount'] - $received;
                            $pct      = $ev['planned_amount'] > 0 ? min(100, round(($received / $ev['planned_amount']) * 100)) : 0;
                            $status   = $pct >= 100 ? 'paid' : ($pct > 0 ? 'partial' : 'unpaid');
                            $status_label = $status === 'paid' ? 'Paid' : ($status === 'partial' ? 'Partial' : 'Unpaid');
                            $has_vendors  = in_array($ev['id'], $events_with_vendors);
                            $ev_json = htmlspecialchars(json_encode([
                                'id'             => $ev['id'],
                                'name'           => $ev['name'],
                                'client_name'    => $ev['client_name'],
                                'start_date'     => $ev['start_date'],
                                'planned_amount' => $ev['planned_amount'],
                                'received'       => $received,
                                'balance'        => $balance,
                                'pct'            => $pct,
                                'status'         => $status,
                                'has_vendors'    => $has_vendors,
                            ]), ENT_QUOTES);
                        ?>
                            <tr data-status="<?php echo $status; ?>">
                                <td><?php echo $i + 1; ?></td>
                                <td>
                                    <div class="event-name-cell"><?php echo htmlspecialchars($ev['name']); ?></div>
                                    <div class="event-sub"><?php echo date('d M Y', strtotime($ev['start_date'])); ?></div>
                                </td>
                                <td><span class="badge-client"><?php echo htmlspecialchars($ev['client_name']); ?></span></td>
                                <td class="amount-planned">₹<?php echo number_format($ev['planned_amount'], 0); ?></td>
                                <td class="amount-received">₹<?php echo number_format($received, 0); ?></td>
                                <td class="<?php echo $balance > 0 ? 'amount-balance-pos' : ($balance < 0 ? 'amount-balance-neg' : 'amount-balance-zero'); ?>">
                                    ₹<?php echo number_format(abs($balance), 0); ?>
                                    <?php if ($balance < 0): ?><span style="font-size:10px;color:#dc2626;"> (overpaid)</span><?php endif; ?>
                                </td>
                                <td>
                                    <div class="progress-wrap">
                                        <div class="progress-bar-bg">
                                            <div class="progress-bar-fill" style="width:<?php echo $pct; ?>%;<?php echo $pct >= 100 ? 'background:linear-gradient(90deg,#10b981,#34d399)' : ($pct > 0 ? 'background:linear-gradient(90deg,#f59e0b,#fbbf24)' : ''); ?>"></div>
                                        </div>
                                        <div class="progress-pct"><?php echo $pct; ?>% received</div>
                                    </div>
                                </td>
                                <td><span class="status-pill <?php echo $status; ?>"><?php echo $status_label; ?></span></td>
                                <td class="no-print">
                                    <div class="action-buttons">
                                        <?php if (!$has_vendors): ?>
                                            <span title="No vendors assigned — payment locked"
                                                style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;background:#fef3c7;border-radius:8px;cursor:not-allowed;">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" width="15" height="15">
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                                </svg>
                                            </span>
                                        <?php elseif ($status !== 'paid'): ?>
                                            <button class="btn-action add-pay" title="Add Payment"
                                                onclick='openAddPayment(<?php echo $ev_json; ?>)'>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                    <line x1="12" y1="5" x2="12" y2="19" />
                                                    <line x1="5" y1="12" x2="19" y2="12" />
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <span title="Fully Paid"
                                                style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;background:#d1fae5;border-radius:8px;">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" width="15" height="15">
                                                    <polyline points="20 6 9 17 4 12" />
                                                </svg>
                                            </span>
                                        <?php endif; ?>
                                        <button class="btn-action view-pay" title="Payment History"
                                            onclick='openHistory(<?php echo $ev_json; ?>)'>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</main>

<!-- ══ Add Payment Modal ══ -->
<div class="modal-overlay" id="addPaymentModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Record Payment</h2>
            <button class="modal-close" onclick="closeModal('addPaymentModal')">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" /></svg>
            </button>
        </div>
        <form action="" method="post">
            <input type="hidden" name="event_id" id="pay_event_id">
            <div class="modal-body">
                <!-- Event info strip -->
                <div style="background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:12px;padding:14px 18px;margin-bottom:20px;">
                    <div style="font-weight:700;color:#0369a1;font-size:15px;" id="pay_event_name"></div>
                    <div style="font-size:13px;color:#64748b;margin-top:4px;">
                        Client: <strong id="pay_client_name"></strong> &nbsp;|&nbsp;
                        Pending: <strong id="pay_balance" style="color:#d97706;"></strong>
                    </div>
                </div>

                <div class="form-grid">
                    <!-- Amount -->
                    <div class="form-group">
                        <label>Amount Received (₹)</label>
                        <input type="number" name="amount_received" id="pay_amount" step="0.01" min="0" placeholder="0.00" required>
                        <span id="pay_amount_error" style="color:#dc2626;font-size:12px;font-weight:600;margin-top:5px;"></span>
                    </div>

                    <!-- Date -->
                    <div class="form-group">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" id="pay_date" required>
                    </div>

                    <!-- Payment Mode -->
                    <div class="form-group">
                        <label>Payment Mode</label>
                        <select name="payment_mode" id="pay_mode">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="UPI">UPI</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Card">Card</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <!-- Note -->
                    <div class="form-group">
                        <label>Note (Optional)</label>
                        <input type="text" name="note" placeholder="e.g. Advance payment">
                    </div>

                    <!-- Transaction ID — full width -->
                    <div class="form-group full-width">
                        <label>
                            Transaction ID
                            <span style="font-weight:400;color:#94a3b8;text-transform:none;letter-spacing:0;font-size:11px;">(Optional — auto-generated if left blank)</span>
                        </label>
                        <input type="text" name="transaction_id" id="pay_txn_id"
                               placeholder="e.g. UTR123456789 / leave blank to auto-generate"
                               style="font-family:monospace;">
                        <div id="pay_txn_preview" class="txn-hint" style="display:none;">
                            Auto-generated ID: <span class="txn-badge" id="pay_txn_auto"></span>
                        </div>
                        <span id="pay_txn_error" style="color:#dc2626;font-size:12px;font-weight:600;margin-top:5px;display:none;">
                            ⚠ Transaction ID already exists. Please enter a different one.
                        </span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addPaymentModal')">Cancel</button>
                <button type="submit" name="add_payment" class="btn-submit">Save Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ Payment History Modal ══ -->
<div class="modal-overlay" id="historyModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h2 class="modal-title">Payment History</h2>
            <button class="modal-close" onclick="closeModal('historyModal')">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" /></svg>
            </button>
        </div>
        <div class="modal-body">
            <div style="font-size:18px;font-family:'Outfit',sans-serif;font-weight:700;color:#1e293b;margin-bottom:4px;" id="hist_event_name"></div>
            <div style="font-size:13px;color:#64748b;margin-bottom:18px;" id="hist_client_name"></div>

            <div class="info-grid">
                <div class="info-card planned">
                    <div class="info-card-label">Amount</div>
                    <div class="info-card-value" id="hist_planned"></div>
                </div>
                <div class="info-card received">
                    <div class="info-card-label">Received</div>
                    <div class="info-card-value" id="hist_received"></div>
                </div>
                <div class="info-card balance">
                    <div class="info-card-label">Pending</div>
                    <div class="info-card-value" id="hist_balance"></div>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <div class="progress-bar-bg" style="height:12px;">
                    <div class="progress-bar-fill" id="hist_progress" style="height:12px;"></div>
                </div>
                <div style="font-size:12px;color:#64748b;margin-top:5px;" id="hist_pct_label"></div>
            </div>

            <div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;margin-bottom:10px;">Transactions</div>
            <div id="hist_table_wrap">
                <div style="text-align:center;padding:40px;color:#94a3b8;">Loading…</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeModal('historyModal')">Close</button>
        </div>
    </div>
</div>

<!-- ══ Delete Payment Confirm ══ -->
<div class="modal-overlay" id="deletePaymentModal">
    <div class="modal" style="max-width:440px;">
        <div class="modal-header" style="background:#dc2626;">
            <h2 class="modal-title">Confirm Delete</h2>
            <button class="modal-close" onclick="closeModal('deletePaymentModal')">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" /></svg>
            </button>
        </div>
        <form action="" method="post">
            <input type="hidden" name="payment_id" id="del_payment_id">
            <div class="modal-body">
                <p style="font-size:15px;color:#64748b;margin:0;">Are you sure you want to delete this payment record? This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('deletePaymentModal')">Cancel</button>
                <button type="submit" name="delete_payment" class="btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- All payments JSON for JS -->
<script>
    const allPaymentsData = <?php echo json_encode($all_payments); ?>;

    // ── Modal helpers ──
    function openModal(id) {
        document.getElementById(id).classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    document.querySelectorAll('.modal-overlay').forEach(el => {
        el.addEventListener('click', e => { if (e.target === el) closeModal(el.id); });
    });

    // ── Generate preview TXN ID ──
    function generateTxnId() {
        return 'TXN-' + Date.now().toString(36).toUpperCase() + Math.random().toString(36).substring(2, 6).toUpperCase();
    }

    // ── Add Payment ──
    function openAddPayment(ev) {
        if (!ev.has_vendors) {
            alert('Cannot record payment — no vendors have been assigned to this event yet.');
            return;
        }

        document.getElementById('pay_event_id').value      = ev.id;
        document.getElementById('pay_event_name').textContent = ev.name;
        document.getElementById('pay_client_name').textContent = ev.client_name;
        document.getElementById('pay_balance').textContent  = '₹' + Number(ev.balance).toLocaleString('en-IN');
        document.getElementById('pay_date').value           = new Date().toISOString().split('T')[0];
        document.getElementById('pay_amount').value         = '';
        document.getElementById('pay_amount').max           = ev.balance;
        document.getElementById('pay_amount_error').textContent = '';

        // Reset transaction ID field
        const txnInput   = document.getElementById('pay_txn_id');
        const txnPreview = document.getElementById('pay_txn_preview');
        const txnAuto    = document.getElementById('pay_txn_auto');
        const txnError   = document.getElementById('pay_txn_error');
        const autoId     = generateTxnId();

        txnInput.value       = '';
        txnAuto.textContent  = autoId;
        txnPreview.style.display = 'block';
        txnError.style.display   = 'none';

        // Show/hide auto-preview based on whether user types
        txnInput.oninput = function () {
            txnPreview.style.display = this.value.trim() === '' ? 'block' : 'none';
            txnError.style.display   = 'none';
        };

        // Amount validation
        document.getElementById('pay_amount').oninput = function () {
            const val  = parseFloat(this.value) || 0;
            const errEl = document.getElementById('pay_amount_error');
            if (val > ev.balance) {
                errEl.textContent = '⚠ Exceeds balance of ₹' + Number(ev.balance).toLocaleString('en-IN');
            } else if (val <= 0) {
                errEl.textContent = '⚠ Amount must be greater than zero.';
            } else {
                errEl.textContent = '';
            }
        };

        // Payment mode change — show TXN hint for digital modes
        document.getElementById('pay_mode').onchange = function () {
            const cashless = ['Bank Transfer','UPI','Cheque','Card'];
            txnInput.placeholder = cashless.includes(this.value)
                ? 'Enter UTR / Reference / Cheque No.'
                : 'e.g. UTR123456789 / leave blank to auto-generate';
        };

        openModal('addPaymentModal');
    }

    // ── History Modal ──
    function openHistory(ev) {
        document.getElementById('hist_event_name').textContent  = ev.name;
        document.getElementById('hist_client_name').textContent = 'Client: ' + ev.client_name;
        document.getElementById('hist_planned').textContent     = '₹' + Number(ev.planned_amount).toLocaleString('en-IN');
        document.getElementById('hist_received').textContent    = '₹' + Number(ev.received).toLocaleString('en-IN');
        document.getElementById('hist_balance').textContent     = '₹' + Number(ev.balance).toLocaleString('en-IN');
        document.getElementById('hist_progress').style.width   = ev.pct + '%';
        document.getElementById('hist_pct_label').textContent  = ev.pct + '% of planned amount received';

        const rows = allPaymentsData.filter(p => p.event_id == ev.id);
        const wrap = document.getElementById('hist_table_wrap');

        if (rows.length === 0) {
            wrap.innerHTML = '<div style="text-align:center;padding:40px 20px;color:#94a3b8;font-size:14px;">No payments recorded yet. Click the <strong>+</strong> button to add one.</div>';
        } else {
            let html = `<table class="payment-history-table">
            <thead><tr>
                <th>Sr.</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Mode</th>
                <th>Transaction ID</th>
                <th>Note</th>
                <th class="no-print">Delete</th>
            </tr></thead><tbody>`;
            rows.forEach((p, i) => {
                const txn = p.transaction_id && p.transaction_id !== ''
                    ? `<span class="txn-id-cell">${p.transaction_id}</span>`
                    : '<span style="color:#94a3b8;">—</span>';
                html += `<tr>
                <td>${i + 1}</td>
                <td>${formatDate(p.payment_date)}</td>
                <td style="font-weight:700;color:#059669;">₹${parseFloat(p.amount_received).toLocaleString('en-IN')}</td>
                <td><span class="mode-badge">${p.payment_mode || 'Cash'}</span></td>
                <td>${txn}</td>
                <td style="color:#64748b;">${p.note || '—'}</td>
                <td class="no-print">
                    <button onclick="confirmDelete(${p.id})"
                        style="background:#fee2e2;color:#dc2626;border:none;border-radius:7px;padding:5px 12px;cursor:pointer;font-size:12px;font-weight:600;">
                        Delete
                    </button>
                </td>
            </tr>`;
            });
            html += '</tbody></table>';
            wrap.innerHTML = html;
        }

        openModal('historyModal');
    }

    function confirmDelete(paymentId) {
        document.getElementById('del_payment_id').value = paymentId;
        openModal('deletePaymentModal');
    }

    function formatDate(d) {
        if (!d) return '—';
        return new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    // ── Search & Filter ──
    document.getElementById('searchInput').addEventListener('keyup', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);

    function filterTable() {
        const q      = document.getElementById('searchInput').value.toLowerCase();
        const status = document.getElementById('statusFilter').value;
        document.querySelectorAll('#paymentsTable tbody tr').forEach(row => {
            const matchQ = row.textContent.toLowerCase().includes(q);
            const matchS = !status || (row.dataset.status || '') === status;
            row.style.display = (matchQ && matchS) ? '' : 'none';
        });
    }

    // ── Export ──
    function exportExcel() {
        const rows = document.querySelectorAll('#paymentsTable tr');
        const xls  = [];
        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            const rowData = [];
            for (let j = 0; j < cols.length - 1; j++) {
                let d = cols[j].innerText.replace(/[\r\n]+/g, ' ').replace(/\s\s+/g, ' ').trim();
                rowData.push('"' + d.replace(/"/g, '""') + '"');
            }
            xls.push(rowData.join(','));
        });
        const link   = document.createElement('a');
        link.href     = 'data:text/xls;charset=utf-8,' + encodeURIComponent(xls.join('\n'));
        link.download = 'payments_' + new Date().toLocaleDateString() + '.xls';
        link.click();
    }

    // ── Print ──
    function printTable() {
        const header = document.createElement('div');
        header.id    = 'print-pay-header';
        header.innerHTML = `
        <div style="text-align:center;padding:4px 0 12px;border-bottom:2px solid #0ea5e9;margin-bottom:16px;">
            <h2 style="font-family:'Outfit',sans-serif;font-size:22px;font-weight:700;color:#1e293b;margin:0;">Payment Report</h2>
            <p style="font-size:12px;color:#64748b;margin:4px 0 0;">
                Printed on: ${new Date().toLocaleDateString('en-IN', { day: '2-digit', month: 'long', year: 'numeric' })}
            </p>
        </div>`;
        document.querySelector('.table-container').prepend(header);
        window.print();
        setTimeout(() => document.getElementById('print-pay-header')?.remove(), 150);
    }
</script>