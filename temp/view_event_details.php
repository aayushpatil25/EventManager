<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch event details
$event_sql = "SELECT * FROM events WHERE id='$event_id'";
$event_result = mysqli_query($conn, $event_sql);

if (!$event_result || mysqli_num_rows($event_result) == 0) {
    header('Location: events.php');
    exit();
}

$event = mysqli_fetch_assoc($event_result);
$service_details = json_decode($event['service'], true);
?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .detail-container {
        padding: 32px;
        background: #f1f5f9;
        min-height: 100vh;
    }

    .detail-header {
        background: white;
        border-radius: 16px;
        padding: 32px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .detail-title {
        font-family: 'Outfit', sans-serif;
        font-size: 32px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .detail-subtitle {
        color: #64748b;
        font-size: 16px;
    }

    .event-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 24px;
    }

    .info-card {
        background: #f8fafc;
        padding: 20px;
        border-radius: 12px;
        border-left: 4px solid #0ea5e9;
    }

    .info-label {
        font-size: 12px;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .info-value {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
    }

    .service-section {
        background: white;
        border-radius: 16px;
        padding: 32px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .service-title {
        font-size: 24px;
        font-weight: 700;
        color: #0369a1;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 16px;
        border-bottom: 2px solid #e0f2fe;
    }

    .service-title svg {
        width: 28px;
        height: 28px;
    }

    .service-items-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
    }

    .service-items-table thead th {
        text-align: left;
        font-size: 13px;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 600;
        letter-spacing: 0.5px;
        padding: 12px 16px;
        background: #f8fafc;
    }

    .service-items-table thead th:first-child {
        border-radius: 8px 0 0 8px;
    }

    .service-items-table thead th:last-child {
        border-radius: 0 8px 8px 0;
    }

    .service-items-table tbody tr {
        background: #f8fafc;
        transition: all 0.2s ease;
    }

    .service-items-table tbody tr:hover {
        background: #e0f2fe;
        transform: translateX(4px);
    }

    .service-items-table tbody td {
        padding: 16px;
        color: #1e293b;
        font-size: 15px;
    }

    .service-items-table tbody td:first-child {
        border-radius: 8px 0 0 8px;
        font-weight: 600;
    }

    .service-items-table tbody td:last-child {
        border-radius: 0 8px 8px 0;
    }

    .quantity-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #0ea5e9;
        color: white;
        padding: 6px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
        min-width: 60px;
    }

    .type-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
    }

    .type-veg {
        background: #dcfce7;
        color: #166534;
    }

    .type-nonveg {
        background: #fee2e2;
        color: #991b1b;
    }

    .type-both {
        background: #fef3c7;
        color: #92400e;
    }

    .people-count {
        font-weight: 600;
        color: #059669;
        font-size: 16px;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #64748b;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        margin-bottom: 24px;
    }

    .back-btn:hover {
        background: #475569;
        transform: translateY(-2px);
    }

    .back-btn svg {
        width: 18px;
        height: 18px;
    }

    .no-services {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }

    .print-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #059669;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        margin-left: 12px;
    }

    .print-btn:hover {
        background: #047857;
        transform: translateY(-2px);
    }

    @media print {
        .back-btn, .print-btn, .sidebar {
            display: none !important;
        }
        
        .detail-container {
            padding: 20px;
        }
        
        .service-section {
            page-break-inside: avoid;
        }
    }
</style>

<main class="main-content">
    <div class="detail-container">
        <a href="events.php" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
            </svg>
            Back to Events
        </a>
        <button class="print-btn" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/>
            </svg>
            Print Details
        </button>

        <div class="detail-header">
            <div class="detail-title"><?php echo htmlspecialchars($event['name']); ?></div>
            <div class="detail-subtitle">Complete Event Details & Service Breakdown</div>

            <div class="event-info-grid">
                <div class="info-card">
                    <div class="info-label">Client Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($event['client_name']); ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Venue</div>
                    <div class="info-value"><?php echo htmlspecialchars($event['venue']); ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Event Date</div>
                    <div class="info-value">
                        <?php 
                        $start = date('M d, Y', strtotime($event['start_date']));
                        $end = date('M d, Y', strtotime($event['end_date']));
                        echo $start == $end ? $start : "$start - $end";
                        ?>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-label">Time</div>
                    <div class="info-value">
                        <?php 
                        if (!empty($event['start_time']) && !empty($event['end_time'])) {
                            echo date('h:i A', strtotime($event['start_time'])) . ' - ' . date('h:i A', strtotime($event['end_time']));
                        } else {
                            echo 'Not specified';
                        }
                        ?>
                    </div>
                </div>
                <div class="info-card" style="border-left-color: #10b981;">
                    <div class="info-label">Budget</div>
                    <div class="info-value" style="color: #059669;">₹<?php echo number_format($event['budget']); ?></div>
                </div>
                <div class="info-card" style="border-left-color: #f59e0b;">
                    <div class="info-label">Men Power</div>
                    <div class="info-value" style="color: #d97706;"><?php echo $event['quantity']; ?> People</div>
                </div>
            </div>

            <?php if (!empty($event['description'])): ?>
            <div style="margin-top: 24px; padding: 16px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                <div class="info-label" style="color: #92400e;">Description</div>
                <div style="color: #78350f; margin-top: 8px;"><?php echo nl2br(htmlspecialchars($event['description'])); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (is_array($service_details) && !empty($service_details)): ?>
            <?php foreach ($service_details as $service_name => $details): ?>
                <?php if (isset($details['items']) && !empty($details['items'])): ?>
                    <div class="service-section">
                        <div class="service-title">
                            <?php if ($service_name == 'Decoration'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                </svg>
                            <?php elseif ($service_name == 'Catering'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z"/>
                                </svg>
                            <?php elseif ($service_name == 'Photography'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 12.5c-1.38 0-2.5 1.12-2.5 2.5s1.12 2.5 2.5 2.5 2.5-1.12 2.5-2.5-1.12-2.5-2.5-2.5zM9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/>
                                </svg>
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                                </svg>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($service_name); ?>
                        </div>

                        <table class="service-items-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <?php if ($service_name == 'Catering'): ?>
                                        <th>Type</th>
                                        <th>No. of People</th>
                                    <?php else: ?>
                                        <th>Quantity</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($details['items'] as $item_name => $item_data): ?>
                                    <?php if (isset($item_data['selected']) && $item_data['selected']): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item_name); ?></td>
                                            <?php if ($service_name == 'Catering'): ?>
                                                <td>
                                                    <?php 
                                                    $type = isset($item_data['type']) ? $item_data['type'] : 'Veg';
                                                    $badge_class = 'type-veg';
                                                    if ($type == 'Non-Veg') $badge_class = 'type-nonveg';
                                                    if ($type == 'Both') $badge_class = 'type-both';
                                                    ?>
                                                    <span class="type-badge <?php echo $badge_class; ?>">
                                                        <?php echo htmlspecialchars($type); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="people-count">
                                                        <?php echo isset($item_data['people']) ? number_format($item_data['people']) : '0'; ?> People
                                                    </span>
                                                </td>
                                            <?php else: ?>
                                                <td>
                                                    <span class="quantity-badge">
                                                        <?php echo isset($item_data['quantity']) ? $item_data['quantity'] : '1'; ?>
                                                    </span>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="service-section">
                <div class="no-services">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 48px; height: 48px; margin-bottom: 12px;">
                        <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                    </svg>
                    <div>No service details available for this event.</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>