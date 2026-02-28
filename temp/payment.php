<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

// Auto-add vendor columns if upgrading existing payments table
mysqli_query($conn, "ALTER TABLE payments ADD COLUMN IF NOT EXISTS event_vendor_id INT DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE payments ADD COLUMN IF NOT EXISTS vendor_id INT DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE payments ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(100) DEFAULT NULL");

// ── Handle Add Vendor Payment ──────────────────────────────────────────────
if (isset($_POST['add_payment'])) {
    $event_id        = intval($_POST['event_id']);
    $event_vendor_id = intval($_POST['event_vendor_id']);
    $vendor_id       = intval($_POST['vendor_id']);
    $amount_received = floatval($_POST['amount_received']);
    $payment_date    = mysqli_real_escape_string($conn, trim($_POST['payment_date']));
    $trasaction_id    = mysqli_real_escape_string($conn, trim($_POST['transaction_id']));
    $note            = mysqli_real_escape_string($conn, trim($_POST['note']));
    $payment_mode    = mysqli_real_escape_string($conn, trim($_POST['payment_mode']));

    // Get agreed amount for this event_vendor row
    $ev_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT amount FROM event_vendors WHERE id='$event_vendor_id'"));
    $agreed_amt = floatval($ev_row['amount'] ?? 0);

    // Already paid for this specific vendor-requirement
    $paid_row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount_received),0) as paid FROM payments WHERE event_vendor_id='$event_vendor_id'"));
    $already   = floatval($paid_row['paid']);
    $remaining = $agreed_amt - $already;

    if ($amount_received <= 0) {
        $error = 'Amount must be greater than zero.';
    } elseif ($amount_received > $remaining) {
        $error = 'Payment of ₹' . number_format($amount_received, 0) . ' exceeds remaining balance of ₹' . number_format($remaining, 0) . '.';
    } else {
        $sql = "INSERT INTO payments (event_id, event_vendor_id, vendor_id, amount_received, payment_date, transaction_id, note, payment_mode, created_at)
                VALUES ('$event_id','$event_vendor_id','$vendor_id','$amount_received','$payment_date','$trasaction_id','$note','$payment_mode',NOW())";
        if (mysqli_query($conn, $sql)) {
            header('Location: payment.php?success=added');
            exit();
        } else {
            $error = 'DB Error: ' . mysqli_error($conn);
        }
    }
}

// ── Handle Delete Payment ──────────────────────────────────────────────────
if (isset($_POST['delete_payment'])) {
    $payment_id = intval($_POST['payment_id']);
    if (mysqli_query($conn, "DELETE FROM payments WHERE id='$payment_id'")) {
        header('Location: payment.php?success=deleted');
        exit();
    } else {
        $error = 'Error: ' . mysqli_error($conn);
    }
}

// ── Fetch events with vendor assignments ──────────────────────────────────
$events_sql = "SELECT e.id, e.name, e.client_name, e.start_date, e.end_date, e.venue,
                      c.contact,
                      COALESCE(SUM(ev.amount),0) as total_agreed
               FROM events e
               LEFT JOIN clients c ON e.client_name = c.client_name
               INNER JOIN event_vendors ev ON ev.event_id = e.id
               GROUP BY e.id, e.name, e.client_name, e.start_date, e.end_date, e.venue, c.contact
               ORDER BY e.start_date DESC";
$events = [];
$res = mysqli_query($conn, $events_sql);
while ($row = mysqli_fetch_assoc($res)) $events[] = $row;

// ── Fetch all event_vendor rows with paid totals ───────────────────────────
$ev_sql = "SELECT ev.id as ev_id, ev.event_id, ev.vendor_id, ev.service_name,
                  ev.requirement_type, ev.amount as agreed_amount, ev.notes,
                  v.vendor_name, v.contact_phone,
                  COALESCE(SUM(p.amount_received),0) as paid_amount
           FROM event_vendors ev
           LEFT JOIN vendors v ON v.vendor_id = ev.vendor_id
           LEFT JOIN payments p ON p.event_vendor_id = ev.id
           GROUP BY ev.id, ev.event_id, ev.vendor_id, ev.service_name,
                    ev.requirement_type, ev.amount, ev.notes, v.vendor_name, v.contact_phone
           ORDER BY ev.event_id, ev.service_name, ev.requirement_type";
$all_ev_rows = [];
$ev_by_event = [];
$res2 = mysqli_query($conn, $ev_sql);
while ($row = mysqli_fetch_assoc($res2)) {
    $all_ev_rows[]                  = $row;
    $ev_by_event[$row['event_id']][] = $row;
}

// ── Fetch all payments for JS history ─────────────────────────────────────
$pay_sql = "SELECT p.*, v.vendor_name, ev.service_name, ev.requirement_type
            FROM payments p
            LEFT JOIN event_vendors ev ON p.event_vendor_id = ev.id
            LEFT JOIN vendors v ON p.vendor_id = v.vendor_id
            ORDER BY p.payment_date DESC";
$all_payments = [];
$res3 = mysqli_query($conn, $pay_sql);
while ($p = mysqli_fetch_assoc($res3)) $all_payments[] = $p;

// ── Summary totals ─────────────────────────────────────────────────────────
$total_agreed  = array_sum(array_column($events, 'total_agreed'));
$total_paid    = array_sum(array_column($all_ev_rows, 'paid_amount'));
$total_pending = $total_agreed - $total_paid;
?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .pay-wrap { background: #f1f5f9; }

    /* ── Summary Cards ── */
    .summary-cards { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; margin-bottom:26px; }
    .s-card {
        background:white; border-radius:16px; padding:20px 24px;
        display:flex; align-items:center; gap:16px;
        box-shadow:0 1px 4px rgba(0,0,0,.06);
        border-left:5px solid transparent;
        transition:transform .2s,box-shadow .2s;
    }
    .s-card:hover { transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,.09); }
    .s-card.c-agreed  { border-color:#0ea5e9; }
    .s-card.c-paid    { border-color:#10b981; }
    .s-card.c-pending { border-color:#f59e0b; }
    .s-icon { width:50px;height:50px;border-radius:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
    .s-icon.c-agreed  { background:#e0f2fe;color:#0284c7; }
    .s-icon.c-paid    { background:#d1fae5;color:#059669; }
    .s-icon.c-pending { background:#fef3c7;color:#d97706; }
    .s-icon svg { width:24px;height:24px; }
    .s-lbl { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b;margin-bottom:3px; }
    .s-val { font-family:'Outfit',sans-serif;font-size:24px;font-weight:700;color:#1e293b;line-height:1; }
    .s-sub { font-size:11px;color:#94a3b8;margin-top:3px; }

    /* ── Page Header ── */
    .page-header { display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap; }
    .search-bar { position:relative;flex:1;max-width:320px; }
    .search-bar input {
        width:100%;padding:10px 16px 10px 40px;
        border:1px solid #e2e8f0;border-radius:10px;
        font-size:14px;font-family:'Lexend',sans-serif;
        background:white;transition:all .2s;
    }
    .search-bar input:focus { outline:none;border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,.1); }
    .search-bar svg { position:absolute;left:12px;top:50%;transform:translateY(-50%);width:17px;height:17px;color:#94a3b8; }

    .btn-export,.btn-print {
        display:flex;align-items:center;gap:8px;
        padding:10px 20px;border-radius:10px;
        font-size:13px;font-weight:600;font-family:'Lexend',sans-serif;
        cursor:pointer;transition:all .2s;
    }
    .btn-export { background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0; }
    .btn-export:hover { background:#dcfce7;transform:translateY(-2px); }
    .btn-print  { background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe; }
    .btn-print:hover  { background:#dbeafe;transform:translateY(-2px); }

    /* ── Alerts ── */
    .alert { padding:12px 16px;border-radius:12px;margin-bottom:18px;font-size:14px;font-weight:500;display:flex;align-items:center;gap:10px; }
    .alert-success { background:#d1fae5;color:#065f46;border:2px solid #10b981; }
    .alert-error   { background:#fee2e2;color:#991b1b;border:2px solid #dc2626; }

    /* ── Event Accordion Cards ── */
    .event-card {
        background:white;border-radius:16px;
        box-shadow:0 1px 4px rgba(0,0,0,.06);
        margin-bottom:16px;overflow:hidden;
        transition:box-shadow .2s;
    }
    .event-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.09); }

    .event-card-header {
        display:flex;align-items:center;gap:16px;
        padding:18px 24px;cursor:pointer;
        border-bottom:2px solid transparent;
        transition:border-color .2s;user-select:none;
    }
    .event-card-header.open { border-color:#e2e8f0; }

    .event-chevron {
        width:28px;height:28px;border-radius:8px;background:#f1f5f9;
        display:flex;align-items:center;justify-content:center;
        transition:transform .25s;flex-shrink:0;
    }
    .event-chevron.open { transform:rotate(180deg);background:#e0f2fe; }
    .event-chevron svg { width:14px;height:14px;color:#64748b; }

    .event-info { flex:1; }
    .event-title { font-family:'Outfit',sans-serif;font-size:16px;font-weight:700;color:#1e293b; }
    .event-meta  { font-size:12px;color:#94a3b8;margin-top:2px; }

    .event-totals { display:flex;gap:20px;align-items:center; }
    .etotal { text-align:right; }
    .etotal-lbl { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8; }
    .etotal-val { font-family:'Outfit',sans-serif;font-size:15px;font-weight:700; }
    .etotal-val.agreed  { color:#0369a1; }
    .etotal-val.paid    { color:#059669; }
    .etotal-val.pending { color:#d97706; }

    .event-status-pill {
        display:inline-flex;align-items:center;gap:5px;
        padding:5px 14px;border-radius:20px;
        font-size:12px;font-weight:700;white-space:nowrap;flex-shrink:0;
    }
    .event-status-pill::before { content:'';width:7px;height:7px;border-radius:50%;background:currentColor; }
    .event-status-pill.paid    { background:#d1fae5;color:#065f46; }
    .event-status-pill.partial { background:#fef3c7;color:#92400e; }
    .event-status-pill.unpaid  { background:#fee2e2;color:#991b1b; }

    /* ── Vendor Table ── */
    .event-vendors-body { display:none;padding:0 24px 20px; }
    .event-vendors-body.open { display:block; }

    .vendor-table { width:100%;border-collapse:collapse;margin-top:14px; }
    .vendor-table thead { background:#f8fafc; }
    .vendor-table th {
        padding:10px 14px;text-align:left;
        font-size:11px;font-weight:700;color:#64748b;
        text-transform:uppercase;letter-spacing:.5px;
        border-bottom:2px solid #e2e8f0;
    }
    .vendor-table td {
        padding:13px 14px;border-bottom:1px solid #f1f5f9;
        color:#1e293b;font-size:13px;vertical-align:middle;
    }
    .vendor-table tbody tr:last-child td { border-bottom:none; }
    .vendor-table tbody tr:hover { background:#fafafa; }

    .vendor-name-cell { font-weight:600;color:#1e293b; }
    .vendor-sub { font-size:11px;color:#94a3b8;margin-top:1px; }

    .svc-badge { display:inline-flex;background:#ede9fe;color:#6b21a8;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600; }
    .req-badge { display:inline-flex;background:#dbeafe;color:#1e40af;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;margin-top:3px; }

    .amt-agreed      { font-weight:700;color:#0369a1; }
    .amt-paid        { font-weight:700;color:#059669; }
    .amt-pending-pos { font-weight:700;color:#d97706; }
    .amt-pending-zero{ font-weight:700;color:#10b981; }

    .prog-bg   { background:#e2e8f0;border-radius:99px;height:6px;overflow:hidden;margin-bottom:3px; }
    .prog-fill { height:100%;border-radius:99px;transition:width .4s; }
    .prog-lbl  { font-size:10px;font-weight:700;color:#94a3b8; }

    .v-status { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700; }
    .v-status::before { content:'';width:6px;height:6px;border-radius:50%;background:currentColor; }
    .v-status.paid    { background:#d1fae5;color:#065f46; }
    .v-status.partial { background:#fef3c7;color:#92400e; }
    .v-status.unpaid  { background:#fee2e2;color:#991b1b; }

    /* ── Action Buttons ── */
    .action-buttons { display:flex;gap:7px; }
    .btn-act { display:flex;align-items:center;justify-content:center;width:32px;height:32px;border:none;border-radius:8px;cursor:pointer;transition:all .2s; }
    .btn-act svg { width:14px;height:14px; }
    .btn-act.add  { background:#d1fae5;color:#059669; }
    .btn-act.add:hover  { background:#a7f3d0;transform:translateY(-2px); }
    .btn-act.hist { background:#e0f2fe;color:#0369a1; }
    .btn-act.hist:hover { background:#bae6fd;transform:translateY(-2px); }
    .btn-act.done { background:#d1fae5;cursor:default; }

    /* ── Modals ── */
    .modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;backdrop-filter:blur(4px);animation:fadeIn .3s; }
    .modal-overlay.active { display:flex;align-items:center;justify-content:center; }
    .modal { background:white;border-radius:20px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);animation:slideUp .3s; }
    .modal-lg { max-width:780px; }
    .modal-header { background:#0284c7;padding:20px 26px;display:flex;justify-content:space-between;align-items:center; }
    .modal-title { font-family:'Outfit',sans-serif;font-size:20px;font-weight:700;color:white; }
    .modal-close { width:32px;height:32px;border:none;background:#ff6666;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:white;transition:all .2s; }
    .modal-close:hover { background:#ff3333;transform:rotate(90deg); }
    .modal-close svg { width:17px;height:17px; }
    .modal-body   { padding:22px 26px; }
    .modal-footer { padding:16px 26px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px; }

    .form-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:16px; }
    .form-group { display:flex;flex-direction:column; }
    .form-group.fw { grid-column:1/-1; }
    .form-group label { font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#334155;margin-bottom:6px; }
    .form-group input,.form-group select,.form-group textarea { padding:10px 12px;border:2px solid #e2e8f0;border-radius:10px;font-size:14px;font-family:'Lexend',sans-serif;background:#f8fafc;transition:all .2s; }
    .form-group input:focus,.form-group select:focus { outline:none;border-color:#0ea5e9;background:white;box-shadow:0 0 0 3px rgba(14,165,233,.1); }
    .form-group select { cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:36px; }

    .info-strip { background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:12px;padding:12px 16px;margin-bottom:18px; }
    .info-strip-title { font-weight:700;color:#0369a1;font-size:15px; }
    .info-strip-sub   { font-size:12px;color:#64748b;margin-top:3px; }

    .btn-cancel { padding:10px 20px;border:2px solid #e2e8f0;background:white;color:#64748b;border-radius:10px;font-size:14px;font-weight:600;font-family:'Lexend',sans-serif;cursor:pointer;transition:all .2s; }
    .btn-cancel:hover { background:#f8fafc; }
    .btn-submit { padding:10px 20px;background:#0ea5e9;color:white;border:none;border-radius:10px;font-size:14px;font-weight:600;font-family:'Lexend',sans-serif;cursor:pointer;transition:all .2s; }
    .btn-submit:hover { background:#0284c7;transform:translateY(-2px); }
    .btn-danger { padding:10px 20px;background:#dc2626;color:white;border:none;border-radius:10px;font-size:14px;font-weight:600;font-family:'Lexend',sans-serif;cursor:pointer;transition:all .2s; }
    .btn-danger:hover { background:#b91c1c; }

    /* ── History Table ── */
    .hist-table { width:100%;border-collapse:collapse;font-size:13px; }
    .hist-table thead { background:#f1f5f9; }
    .hist-table th { padding:9px 12px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e2e8f0; }
    .hist-table td { padding:10px 12px;border-bottom:1px solid #f1f5f9;color:#1e293b; }
    .hist-table tbody tr:last-child td { border-bottom:none; }
    .mode-badge { display:inline-flex;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;background:#dbeafe;color:#1e40af; }

    .hist-info-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px; }
    .hist-info-card { padding:12px 14px;border-radius:10px;border:2px solid #e2e8f0;background:#f8fafc; }
    .hist-info-lbl  { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;margin-bottom:3px; }
    .hist-info-val  { font-family:'Outfit',sans-serif;font-size:18px;font-weight:700; }
    .hist-info-val.agreed  { color:#0369a1; }
    .hist-info-val.paid    { color:#059669; }
    .hist-info-val.pending { color:#d97706; }

    @keyframes fadeIn  { from{opacity:0}to{opacity:1} }
    @keyframes slideUp { from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:translateY(0)} }

    @media print {
        .sidebar,.topbar,.page-header,.no-print,.action-buttons,.summary-cards { display:none !important; }
        .event-card { box-shadow:none !important;border:1px solid #000 !important;break-inside:avoid; }
        .event-vendors-body { display:block !important; }
        .event-card-header { border-bottom:2px solid #000 !important; }
        .vendor-table th,.vendor-table td { border:1px solid #000 !important;font-size:11px !important;padding:6px !important; }
    }
</style>

<main class="main-content">
<div class="pay-wrap">

    <!-- Summary Cards -->
    <!-- <div class="summary-cards">
        <div class="s-card c-agreed">
            <div class="s-icon c-agreed">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
            </div>
            <div>
                <div class="s-lbl">Total Agreed</div>
                <div class="s-val">₹<?php echo number_format($total_agreed, 0); ?></div>
                <div class="s-sub"><?php echo count($all_ev_rows); ?> vendor assignments</div>
            </div>
        </div>
        <div class="s-card c-paid">
            <div class="s-icon c-paid">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <div>
                <div class="s-lbl">Total Paid</div>
                <div class="s-val">₹<?php echo number_format($total_paid, 0); ?></div>
                <div class="s-sub"><?php echo count($all_payments); ?> transactions</div>
            </div>
        </div>
        <div class="s-card c-pending">
            <div class="s-icon c-pending">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div>
                <div class="s-lbl">Total Pending</div>
                <div class="s-val">₹<?php echo number_format($total_pending, 0); ?></div>
                <div class="s-sub"><?php echo $total_agreed > 0 ? round(($total_paid/$total_agreed)*100) : 0; ?>% paid overall</div>
            </div>
        </div>
    </div> -->

    <!-- Page Header -->
    <div class="page-header">
        <div class="search-bar">
            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            <input type="text" id="searchInput" placeholder="Search event or vendor…">
        </div>
        <select id="statusFilter" style="padding:10px 16px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-family:'Lexend',sans-serif;background:white;cursor:pointer;">
            <option value="">All Status</option>
            <option value="paid">Paid</option>
            <option value="partial">Partial</option>
            <option value="unpaid">Unpaid</option>
        </select>
        <button class="btn-export" onclick="exportExcel()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
            </svg>Export
        </button>
        <button class="btn-print" onclick="printPage()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
            </svg>Print
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

    <!-- Event Cards -->
    <?php if (empty($events)): ?>
        <div style="text-align:center;padding:60px;color:#94a3b8;background:white;border-radius:16px;">
            No events with vendor assignments found.
        </div>
    <?php else: ?>
        <?php foreach ($events as $ev):
            $ev_vendors = $ev_by_event[$ev['id']] ?? [];
            $ev_agreed  = floatval($ev['total_agreed']);
            $ev_paid    = array_sum(array_column($ev_vendors, 'paid_amount'));
            $ev_pending = $ev_agreed - $ev_paid;
            $ev_pct     = $ev_agreed > 0 ? min(100, round(($ev_paid / $ev_agreed) * 100)) : 0;
            $ev_status  = $ev_pct >= 100 ? 'paid' : ($ev_pct > 0 ? 'partial' : 'unpaid');
            $ev_status_label = ['paid'=>'Fully Paid','partial'=>'Partial','unpaid'=>'Unpaid'][$ev_status];
        ?>
        <div class="event-card" data-event-id="<?php echo $ev['id']; ?>" data-status="<?php echo $ev_status; ?>">
            <div class="event-card-header" onclick="toggleCard(this)">
                <div class="event-chevron">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </div>
                <div class="event-info">
                    <div class="event-title"><?php echo htmlspecialchars($ev['name']); ?></div>
                    <div class="event-meta">
                        <?php echo htmlspecialchars($ev['client_name']); ?> &nbsp;·&nbsp;
                        <?php echo date('d M Y', strtotime($ev['start_date'])); ?> &nbsp;·&nbsp;
                        <?php echo htmlspecialchars($ev['venue']); ?> &nbsp;·&nbsp;
                        <?php echo count($ev_vendors); ?> vendor<?php echo count($ev_vendors) > 1 ? 's' : ''; ?>
                    </div>
                </div>
                <div class="event-totals">
                    <div class="etotal">
                        <div class="etotal-lbl">To be Paid</div>
                        <div class="etotal-val agreed">₹<?php echo number_format($ev_agreed, 0); ?></div>
                    </div>
                    <div class="etotal">
                        <div class="etotal-lbl">Paid</div>
                        <div class="etotal-val paid">₹<?php echo number_format($ev_paid, 0); ?></div>
                    </div>
                    <div class="etotal">
                        <div class="etotal-lbl">Pending</div>
                        <div class="etotal-val pending">₹<?php echo number_format($ev_pending, 0); ?></div>
                    </div>
                    <span class="event-status-pill <?php echo $ev_status; ?>"><?php echo $ev_status_label; ?></span>
                </div>
            </div>

            <div class="event-vendors-body">
                <table class="vendor-table">
                    <thead>
                        <tr>
                            <th>Vendor</th>
                            <th>Service / Requirement</th>
                            <th>Amount to be Paid</th>
                            <th>Received Amount</th>
                            <th>Pending Amount</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ev_vendors as $vr):
                        $agreed  = floatval($vr['agreed_amount']);
                        $paid    = floatval($vr['paid_amount']);
                        $pending = $agreed - $paid;
                        $pct     = $agreed > 0 ? min(100, round(($paid / $agreed) * 100)) : 0;
                        $vstatus = $pct >= 100 ? 'paid' : ($pct > 0 ? 'partial' : 'unpaid');
                        $vstatus_label = ['paid'=>'Paid','partial'=>'Partial','unpaid'=>'Unpaid'][$vstatus];
                        $fill_color = $pct >= 100
                            ? 'linear-gradient(90deg,#10b981,#34d399)'
                            : ($pct > 0 ? 'linear-gradient(90deg,#f59e0b,#fbbf24)' : '#e2e8f0');

                        $vr_json = htmlspecialchars(json_encode([
                            'ev_id'        => $vr['ev_id'],
                            'event_id'     => $vr['event_id'],
                            'vendor_id'    => $vr['vendor_id'],
                            'vendor_name'  => $vr['vendor_name'],
                            'service_name' => $vr['service_name'],
                            'req_type'     => $vr['requirement_type'],
                            'agreed'       => $agreed,
                            'paid'         => $paid,
                            'pending'      => $pending,
                            'pct'          => $pct,
                            'status'       => $vstatus,
                            'event_name'   => $ev['name'],
                        ]), ENT_QUOTES);
                    ?>
                        <tr data-vstatus="<?php echo $vstatus; ?>">
                            <td>
                                <div class="vendor-name-cell"><?php echo htmlspecialchars($vr['vendor_name']); ?></div>
                                <div class="vendor-sub"><?php echo htmlspecialchars($vr['contact_phone'] ?? ''); ?></div>
                            </td>
                            <td>
                                <div><span class="svc-badge"><?php echo htmlspecialchars($vr['service_name']); ?></span></div>
                                <div style="margin-top:4px;"><span class="req-badge"><?php echo htmlspecialchars($vr['requirement_type']); ?></span></div>
                            </td>
                            <td class="amt-agreed">₹<?php echo number_format($agreed, 0); ?></td>
                            <td class="amt-paid">₹<?php echo number_format($paid, 0); ?></td>
                            <td class="<?php echo $pending > 0 ? 'amt-pending-pos' : 'amt-pending-zero'; ?>">
                                ₹<?php echo number_format($pending, 0); ?>
                            </td>
                            <td style="min-width:100px;">
                                <div class="prog-bg">
                                    <div class="prog-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $fill_color; ?>;"></div>
                                </div>
                                <div class="prog-lbl"><?php echo $pct; ?>%</div>
                            </td>
                            <td><span class="v-status <?php echo $vstatus; ?>"><?php echo $vstatus_label; ?></span></td>
                            <td class="no-print">
                                <div class="action-buttons">
                                    <?php if ($vstatus !== 'paid'): ?>
                                        <button class="btn-act add" title="Record Payment"
                                            onclick='openAddPayment(<?php echo $vr_json; ?>)'>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <line x1="12" y1="5" x2="12" y2="19"/>
                                                <line x1="5" y1="12" x2="19" y2="12"/>
                                            </svg>
                                        </button>
                                    <?php else: ?>
                                        <span class="btn-act done" title="Fully Paid">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" width="14" height="14">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </span>
                                    <?php endif; ?>
                                    <button class="btn-act hist" title="Payment History"
                                        onclick='openHistory(<?php echo $vr_json; ?>)'>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</main>

<!-- ══ Add Payment Modal ══ -->
<div class="modal-overlay" id="addPaymentModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Record Payment</h2>
            <button class="modal-close" onclick="closeModal('addPaymentModal')">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form action="" method="post">
            <input type="hidden" name="event_id"        id="pay_event_id">
            <input type="hidden" name="event_vendor_id" id="pay_ev_id">
            <input type="hidden" name="vendor_id"       id="pay_vendor_id">
            <div class="modal-body">
                <div class="info-strip">
                    <div class="info-strip-title" id="pay_vendor_name"></div>
                    <div class="info-strip-sub">
                        <span id="pay_event_name"></span> &nbsp;·&nbsp;
                        <span id="pay_svc_name"></span> → <span id="pay_req_type"></span>
                    </div>
                    <div class="info-strip-sub" style="margin-top:5px;">
                        Agreed: <strong id="pay_agreed"></strong> &nbsp;|&nbsp;
                        Pending: <strong id="pay_pending" style="color:#d97706;"></strong>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Amount (₹)</label>
                        <input type="number" name="amount_received" id="pay_amount" step="0.01" min="0" placeholder="0.00" required>
                        <span id="pay_amount_error" style="color:#dc2626;font-size:11px;font-weight:600;margin-top:4px;"></span>
                    </div>
                    <div class="form-group">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" id="pay_date" required>
                    </div>
                    <div class="form-group">
                        <label>Transaction ID</label>
                        <input type="text" name="transaction_id" id="pay_transaction_id" placeholder="e.g. UPI Ref No.">
                    </div>
                    <div class="form-group">
                        <label>Payment Mode</label>
                        <select name="payment_mode">
                            <option>Cash</option>
                            <option>Bank Transfer</option>
                            <option>UPI</option>
                            <option>Cheque</option>
                            <option>Card</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Note (Optional)</label>
                        <input type="text" name="note" placeholder="e.g. Advance">
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
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div style="font-family:'Outfit',sans-serif;font-size:17px;font-weight:700;color:#1e293b;" id="hist_title"></div>
            <div style="font-size:12px;color:#64748b;margin-bottom:16px;" id="hist_sub"></div>
            <div class="hist-info-grid">
                <div class="hist-info-card">
                    <div class="hist-info-lbl">Agreed</div>
                    <div class="hist-info-val agreed" id="hist_agreed"></div>
                </div>
                <div class="hist-info-card">
                    <div class="hist-info-lbl">Paid</div>
                    <div class="hist-info-val paid" id="hist_paid"></div>
                </div>
                <div class="hist-info-card">
                    <div class="hist-info-lbl">Pending</div>
                    <div class="hist-info-val pending" id="hist_pending"></div>
                </div>
            </div>
            <div style="margin-bottom:18px;">
                <div class="prog-bg" style="height:10px;">
                    <div class="prog-fill" id="hist_prog" style="height:10px;"></div>
                </div>
                <div style="font-size:11px;color:#64748b;margin-top:4px;" id="hist_pct"></div>
            </div>
            <div id="hist_table_wrap">
                <div style="text-align:center;padding:30px;color:#94a3b8;">Loading…</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeModal('historyModal')">Close</button>
        </div>
    </div>
</div>

<!-- ══ Delete Modal ══ -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header" style="background:#dc2626;">
            <h2 class="modal-title">Confirm Delete</h2>
            <button class="modal-close" onclick="closeModal('deleteModal')">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form action="" method="post">
            <input type="hidden" name="payment_id" id="del_pay_id">
            <div class="modal-body">
                <p style="font-size:14px;color:#64748b;margin:0;">Are you sure you want to delete this payment? This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" name="delete_payment" class="btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
const allPayments = <?php echo json_encode($all_payments); ?>;

// ── Accordion ──
function toggleCard(header) {
    const body    = header.nextElementSibling;
    const chevron = header.querySelector('.event-chevron');
    const isOpen  = body.classList.contains('open');
    body.classList.toggle('open', !isOpen);
    chevron.classList.toggle('open', !isOpen);
    header.classList.toggle('open', !isOpen);
}

// ── Modals ──
function openModal(id)  { document.getElementById(id).classList.add('active');    document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('active'); document.body.style.overflow='auto'; }
document.querySelectorAll('.modal-overlay').forEach(el =>
    el.addEventListener('click', e => { if(e.target===el) closeModal(el.id); })
);

// ── Add Payment ──
function openAddPayment(vr) {
    document.getElementById('pay_event_id').value  = vr.event_id;
    document.getElementById('pay_ev_id').value      = vr.ev_id;
    document.getElementById('pay_vendor_id').value  = vr.vendor_id;
    document.getElementById('pay_vendor_name').textContent = vr.vendor_name;
    document.getElementById('pay_event_name').textContent  = vr.event_name;
    document.getElementById('pay_svc_name').textContent    = vr.service_name;
    document.getElementById('pay_req_type').textContent    = vr.req_type;
    document.getElementById('pay_agreed').textContent  = '₹' + Number(vr.agreed).toLocaleString('en-IN');
    document.getElementById('pay_pending').textContent = '₹' + Number(vr.pending).toLocaleString('en-IN');
    document.getElementById('pay_date').value   = new Date().toISOString().split('T')[0];
    document.getElementById('pay_amount').value = '';
    document.getElementById('pay_amount').max   = vr.pending;
    document.getElementById('pay_amount_error').textContent = '';

    document.getElementById('pay_amount').oninput = function() {
        const val   = parseFloat(this.value) || 0;
        const errEl = document.getElementById('pay_amount_error');
        if (val > vr.pending)
            errEl.textContent = '⚠ Exceeds pending ₹' + Number(vr.pending).toLocaleString('en-IN');
        else if (val <= 0)
            errEl.textContent = '⚠ Must be greater than zero.';
        else
            errEl.textContent = '';
    };
    openModal('addPaymentModal');
}

// ── History ──
function openHistory(vr) {
    document.getElementById('hist_title').textContent   = vr.vendor_name;
    document.getElementById('hist_sub').textContent     = vr.event_name + ' · ' + vr.service_name + ' → ' + vr.req_type;
    document.getElementById('hist_agreed').textContent  = '₹' + Number(vr.agreed).toLocaleString('en-IN');
    document.getElementById('hist_paid').textContent    = '₹' + Number(vr.paid).toLocaleString('en-IN');
    document.getElementById('hist_pending').textContent = '₹' + Number(vr.pending).toLocaleString('en-IN');
    document.getElementById('hist_prog').style.width      = vr.pct + '%';
    document.getElementById('hist_prog').style.background = vr.pct >= 100
        ? 'linear-gradient(90deg,#10b981,#34d399)'
        : vr.pct > 0 ? 'linear-gradient(90deg,#f59e0b,#fbbf24)' : '#e2e8f0';
    document.getElementById('hist_pct').textContent = vr.pct + '% of agreed amount paid';

    const rows = allPayments.filter(p => p.event_vendor_id == vr.ev_id);
    const wrap = document.getElementById('hist_table_wrap');
    if (!rows.length) {
        wrap.innerHTML = '<div style="text-align:center;padding:30px;color:#94a3b8;font-size:14px;">No payments recorded yet.</div>';
    } else {
        let html = `<table class="hist-table"><thead><tr>
            <th>Sr.</th><th>Date</th><th>Amount</th><th>Mode</th><th>Txn ID</th><th>Note</th>
            <th class="no-print">Action</th>
        </tr></thead><tbody>`;
        rows.forEach((p,i) => {
            html += `<tr>
                <td>${i+1}</td>
                <td>${fmtDate(p.payment_date)}</td>
                <td style="font-weight:700;color:#059669;">₹${parseFloat(p.amount_received).toLocaleString('en-IN')}</td>
                <td><span class="mode-badge">${p.payment_mode||'Cash'}</span></td>
                <td style="color:#64748b;font-family:monospace;font-size:12px;">${p.transaction_id||'—'}</td>
                <td style="color:#64748b;">${p.note||'—'}</td>
                <td class="no-print">
                    <button onclick="confirmDelete(${p.id})"
                        style="background:#fee2e2;color:#dc2626;border:none;border-radius:7px;
                               padding:4px 10px;cursor:pointer;font-size:12px;font-weight:600;">
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

function confirmDelete(id) {
    document.getElementById('del_pay_id').value = id;
    openModal('deleteModal');
}

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'});
}

// ── Search & Filter ──
document.getElementById('searchInput').addEventListener('keyup', filterCards);
document.getElementById('statusFilter').addEventListener('change', filterCards);

function filterCards() {
    const q  = document.getElementById('searchInput').value.toLowerCase();
    const st = document.getElementById('statusFilter').value;
    document.querySelectorAll('.event-card').forEach(card => {
        const text = card.textContent.toLowerCase();
        let hasMatchingVendor = !st;
        if (st) card.querySelectorAll('tr[data-vstatus]').forEach(row => {
            if (row.dataset.vstatus === st) hasMatchingVendor = true;
        });
        card.style.display = (text.includes(q) && hasMatchingVendor) ? '' : 'none';
    });
}

// ── Export ──
function exportExcel() {
    const rows = ['"Event","Vendor","Service","Requirement","Amount to be Paid","Received Amount","Pending Amount","Status"'];
    document.querySelectorAll('.event-card').forEach(card => {
        const eventName = card.querySelector('.event-title')?.textContent.trim() || '';
        card.querySelectorAll('.vendor-table tbody tr').forEach(row => {
            const cells = row.querySelectorAll('td');
            if (!cells.length) return;
            const vendor  = cells[0]?.querySelector('.vendor-name-cell')?.textContent.trim() || '';
            const svc     = cells[1]?.querySelector('.svc-badge')?.textContent.trim() || '';
            const req     = cells[1]?.querySelector('.req-badge')?.textContent.trim() || '';
            const agreed  = cells[2]?.textContent.trim() || '';
            const paid    = cells[3]?.textContent.trim() || '';
            const pending = cells[4]?.textContent.trim() || '';
            const status  = cells[6]?.textContent.trim() || '';
            rows.push([eventName,vendor,svc,req,agreed,paid,pending,status].map(v=>`"${v.replace(/"/g,'""')}"`).join(','));
        });
    });
    const link = document.createElement('a');
    link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(rows.join('\n'));
    link.download = 'vendor_payments_' + new Date().toLocaleDateString() + '.csv';
    link.click();
}

// ── Print ──
function printPage() {
    document.querySelectorAll('.event-vendors-body').forEach(b => b.classList.add('open'));
    window.print();
}
</script>