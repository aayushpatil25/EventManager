<?php
session_start();
require 'config/db.php';

// Fetch stats
$events_query = "SELECT COUNT(*) as total FROM events";
$events_result = $conn->query($events_query);
$total_events = $events_result->fetch_assoc()['total'];

$clients_query = "SELECT COUNT(*) as total FROM clients";
$clients_result = $conn->query($clients_query);
$total_clients = $clients_result->fetch_assoc()['total'];

$vendors_query = "SELECT COUNT(*) as total FROM vendors";
$vendors_result = $conn->query($vendors_query);
$total_vendors = $vendors_result->fetch_assoc()['total'];

$services_query = "SELECT COUNT(*) as total FROM services_category where category = 'Main'";
$services_result = $conn->query($services_query);
$total_services = $services_result->fetch_assoc()['total'];

// Fetch events for calendar - FIXED: Using start_date instead of event_date
$calendar_query = "SELECT 
                    id, 
                    name, 
                    client_name, 
                    venue, 
                    service, 
                    budget, 
                    description, 
                    start_date,
                    end_date,
                    start_time,
                    end_time,
                    created_at
                  FROM events 
                  WHERE start_date IS NOT NULL 
                  ORDER BY start_date";
$calendar_result = $conn->query($calendar_query);

// Organize events by date
$events_by_date = [];
$all_events = [];
if ($calendar_result && $calendar_result->num_rows > 0) {
    while ($row = $calendar_result->fetch_assoc()) {
        // Extract just the date part from start_date (datetime field)
        $date = date('Y-m-d', strtotime($row['start_date']));
        // Add the date field to the row for JavaScript
        $row['start_date'] = $date;
        $all_events[] = $row;

        if (!isset($events_by_date[$date])) {
            $events_by_date[$date] = [
                'events' => [],
                'client_count' => 0,
                'clients' => [],
                'total_budget' => 0,
                'pending_payments' => 0
            ];
        }
        $events_by_date[$date]['events'][] = $row;
        $events_by_date[$date]['total_budget'] += floatval($row['budget']);

        // Count unique clients
        if (!in_array($row['client_name'], $events_by_date[$date]['clients'])) {
            $events_by_date[$date]['clients'][] = $row['client_name'];
            $events_by_date[$date]['client_count']++;
        }
    }
}

// Convert to JSON for JavaScript
$events_json = json_encode($events_by_date);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EventsManager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Lexend:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0ea5e9;
            --primary-foreground: #ffffff;
            --secondary: #64748b;
            --secondary-foreground: #ffffff;
            --muted: #f1f5f9;
            --muted-foreground: #64748b;
            --accent: #f8fafc;
            --accent-foreground: #0f172a;
            --destructive: #ef4444;
            --destructive-foreground: #ffffff;
            --border: #e2e8f0;
            --input: #ffffff;
            --ring: #0ea5e9;
            --radius: 0.75rem;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Lexend', sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }

        .main-content {
            padding: 35px 20px;
        }

        .page-header {
            margin-bottom: 32px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .page-subtitle {
            font-size: 16px;
            color: #64748b;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            border: 1px solid var(--border);
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .stat-title {
            font-size: 14px;
            color: var(--muted-foreground);
            font-weight: 500;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon.blue {
            background: #dbeafe;
            color: var(--primary);
        }

        .stat-icon.purple {
            background: #e9d5ff;
            color: #8b5cf6;
        }

        .stat-icon.yellow {
            background: #fef3c7;
            color: #f59e0b;
        }


        .stat-icon.green {
            background: #d1fae5;
            color: var(--success);
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #1e293b;
        }

        /* Calendar Container */
        .calendar-container {
            background: white;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 32px;
        }

        /* Calendar Header */
        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border-bottom: 1px solid var(--border);
            background: #0ea5e9;
        }

        .calendar-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
        }

        .calendar-title i {
            color: #ffffff;
        }

        .calendar-nav {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .calendar-nav-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: white;
            border: 1px solid var(--border);
            color: var(--muted-foreground);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .calendar-nav-btn:hover {
            background: var(--muted);
            border-color: var(--primary);
            color: var(--primary);
        }

        .calendar-month {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            min-width: 140px;
            text-align: center;
        }

        /* Weekday Headers */
        .weekday-headers {
            background-color: #ddf5ff;
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            border-bottom: 1px solid var(--border);
        }

        .weekday-header {
            padding: 12px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            color: var(--muted-foreground);
            text-transform: uppercase;
        }

        /* Calendar Grid */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }

        .calendar-day {
            min-height: 80px;
            padding: 8px;
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            position: relative;
            transition: all 0.2s ease;
            background: white;
        }

        .calendar-day:hover {
            background: var(--muted);
        }

        .calendar-day.other-month {
            background: var(--muted);
            color: var(--muted-foreground);
            opacity: 0.5;
        }

        .calendar-day.today {
            background: #f0f9ff;
            border-left: 4px solid var(--primary);
        }

        .calendar-day-number {
            font-size: 14px;
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .calendar-day.today .day-number {
            background: var(--primary);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .calendar-day.past-event {
            background: #fff1f2;
            cursor: pointer;
        }

        .calendar-day.past-event:hover {
            background: #ffe4e6;
        }

        .calendar-day.past-event .event-badge {
            background: #ef4444;
        }

        .calendar-day.past-event .client-badge {
            background: #dc2626;
        }

        .day-events {
            position: absolute;
            bottom: 4px;
            left: 4px;
            right: 4px;
        }

        .event-badge {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            margin-right: 2px;
            margin-bottom: 2px;
        }

        .client-badge {
            display: inline-block;
            background: var(--success);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
        }

        .day-icons {
            display: flex;
            gap: 2px;
        }

        .icon-pending {
            color: var(--warning);
        }

        .icon-confirmed {
            color: var(--success);
        }

        .calendar-day.has-events {
            cursor: pointer;
            background: #d5fee5;
        }

        .calendar-day.has-events:hover {
            background: #c0ffd7;
        }

        /* Legend */
        .calendar-legend {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
            padding: 16px;
            border-top: 1px solid var(--border);
            background: var(--muted);
            font-size: 12px;
            color: var(--muted-foreground);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .legend-event {
            width: 12px;
            height: 12px;
            background: var(--primary);
            border-radius: 2px;
        }

        .legend-pending {
            color: var(--warning);
        }

        .legend-confirmed {
            color: var(--success);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: var(--radius);
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Modal Header */
        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%);
            color: white;
            padding: 15px;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .modal-title {
            font-size: 28px;
            font-weight: 700;
        }

        .modal-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 10px;
        }

        .modal-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .modal-badge.warning {
            background: rgba(245, 158, 11, 0.8);
        }

        /* Modal Body */
        .modal-body {
            padding: 30px;
            max-height: 60vh;
            overflow-y: auto;
        }

        .stats-grid-modal {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 30px;
        }

        .stat-card-modal {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s ease;
        }

        .stat-card-modal:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .stat-header-modal {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .stat-icon-modal {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon-modal.blue {
            background: #dbeafe;
            color: var(--primary);
        }

        .stat-icon-modal.green {
            background: #d1fae5;
            color: var(--success);
        }

        .stat-icon-modal.yellow {
            background: #fef3c7;
            color: var(--warning);
        }

        .stat-title-modal {
            font-size: 14px;
            font-weight: 500;
            color: var(--muted-foreground);
        }

        .stat-subtitle-modal {
            font-size: 12px;
            color: var(--muted-foreground);
        }

        .stat-value-modal {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }

        .stat-value-modal.success {
            color: var(--success);
        }

        .stat-value-modal.warning {
            color: var(--warning);
        }

        /* Event List */
        .event-list-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }

        .event-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            background: white;
            transition: all 0.2s ease;
        }

        .event-card:hover {
            background: var(--muted);
            transform: translateX(4px);
        }

        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .event-name {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .event-client {
            font-size: 14px;
            color: var(--muted-foreground);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .event-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-completed {
            background: #d1fae5;
            color: var(--success);
        }

        .status-in-progress {
            background: #fef3c7;
            color: var(--warning);
        }

        .status-upcoming {
            background: #dbeafe;
            color: var(--primary);
        }

        .event-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .event-detail {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--muted-foreground);
        }

        .event-detail i {
            width: 16px;
            color: var(--primary);
        }

        .services-section {
            margin-bottom: 16px;
        }

        .services-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--muted-foreground);
            margin-bottom: 8px;
        }

        .service-badge {
            display: inline-block;
            background: var(--muted);
            color: var(--muted-foreground);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 4px;
            margin-bottom: 4px;
        }

        .progress-section {
            margin-top: 16px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .progress-label {
            color: var(--muted-foreground);
        }

        .progress-percentage {
            font-weight: 500;
            color: #1e293b;
        }

        .progress-bar {
            height: 6px;
            background: var(--muted);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .progress-completed {
            background: var(--success);
        }

        .progress-in-progress {
            background: var(--warning);
        }

        .progress-upcoming {
            background: var(--primary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted-foreground);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            cursor: pointer;
        }

        .tooltip-content {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
            margin-bottom: 8px;
            display: none;
        }

        .tooltip-content::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 6px;
            border-style: solid;
            border-color: #1e293b transparent transparent transparent;
        }

        .tooltip:hover .tooltip-content {
            display: block;
        }

        @media (max-width: 768px) {
            .stats-grid-modal {
                grid-template-columns: 1fr;
            }

            .modal-body {
                padding: 20px;
            }

            .event-details-grid {
                grid-template-columns: 1fr;
            }

            .calendar-day {
                min-height: 60px;
            }

            .modal-badges {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- <div class="page-header">
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Welcome back! Here's what's happening today.</p>
        </div> -->

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Events</div>
                        <div class="stat-value"><?php echo $total_events; ?></div>
                    </div>
                    <div class="stat-icon blue">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Services</div>
                        <div class="stat-value"><?php echo $total_services; ?></div>
                    </div>
                    <div class="stat-icon yellow">
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Clients</div>
                        <div class="stat-value"><?php echo $total_clients; ?></div>
                    </div>
                    <div class="stat-icon green">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Vendors</div>
                        <div class="stat-value"><?php echo $total_vendors; ?></div>
                    </div>
                    <div class="stat-icon purple">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Section -->
        <div class="calendar-container">
            <!-- Calendar Header -->
            <div class="calendar-header">
                <div class="calendar-title">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Event Calendar</span>
                </div>
                <div class="calendar-nav">
                    <button class="calendar-nav-btn" id="prevMonth">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="calendar-month" id="currentMonth"></div>
                    <button class="calendar-nav-btn" id="nextMonth">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Weekday Headers -->
            <div class="weekday-headers">
                <div class="weekday-header">Sun</div>
                <div class="weekday-header">Mon</div>
                <div class="weekday-header">Tue</div>
                <div class="weekday-header">Wed</div>
                <div class="weekday-header">Thu</div>
                <div class="weekday-header">Fri</div>
                <div class="weekday-header">Sat</div>
            </div>

            <!-- Calendar Grid -->
            <div class="calendar-grid" id="calendarGrid">
                <!-- Days will be populated by JavaScript -->
            </div>

            <!-- Legend -->
            <div class="calendar-legend">
                <div class="legend-item">
                    <div class="legend-event"></div>
                    <span>Has Events</span>
                </div>
                <div class="legend-item">
                    <i class="fas fa-exclamation-circle legend-pending"></i>
                    <span>Pending Payment</span>
                </div>
                <div class="legend-item">
                    <i class="fas fa-check-circle legend-confirmed"></i>
                    <span>Confirmed</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal" id="eventModal">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <button class="modal-close" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
                <h2 class="modal-title" id="modalDate"></h2>
                <div class="modal-badges" id="modalBadges">
                    <!-- Badges will be populated by JavaScript -->
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body" id="modalBody">
                <!-- Stats Grid -->
                <div class="stats-grid-modal" id="modalStats">
                    <!-- Stats will be populated by JavaScript -->
                </div>

                <!-- Event List -->
                <h3 class="event-list-title">Event Details</h3>
                <div class="event-list" id="eventList">
                    <!-- Events will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentDate = new Date();
        let eventsData = <?php echo $events_json ? $events_json : '{}'; ?>;
        let allEvents = <?php echo json_encode($all_events); ?>;

        // Format currency
        function formatCurrency(amount) {
            amount = parseFloat(amount) || 0;
            if (amount >= 100000) {
                return '₹' + (amount / 100000).toFixed(1) + 'L';
            }
            if (amount >= 1000) {
                return '₹' + (amount / 1000).toFixed(0) + 'K';
            }
            return '₹' + amount;
        }

        function formatFullCurrency(amount) {
            amount = parseFloat(amount) || 0;
            return new Intl.NumberFormat('en-IN', {
                maximumFractionDigits: 0
            }).format(amount);
        }

        // Format time from database time field
        function formatTime(timeStr) {
            if (!timeStr) return 'Time TBD';
            // timeStr is in format HH:MM:SS
            const parts = timeStr.split(':');
            let hours = parseInt(parts[0]);
            const minutes = parts[1];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // 0 should be 12
            return hours + ':' + minutes + ' ' + ampm;
        }

        // Calculate event status based on start_date
        function getEventStatus(startDate) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const eventDateObj = new Date(startDate);
            eventDateObj.setHours(0, 0, 0, 0);
            const timeDiff = eventDateObj.getTime() - today.getTime();
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));

            if (daysDiff < 0) return 'completed';
            if (daysDiff <= 7) return 'in-progress';
            return 'upcoming';
        }

        // Get progress percentage
        function getProgressPercentage(status) {
            switch (status) {
                case 'completed':
                    return 100;
                case 'in-progress':
                    return 70;
                case 'upcoming':
                    return 30;
                default:
                    return 0;
            }
        }

        // Render calendar
        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();

            // Update month display
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;

            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            // Clear existing days
            const calendarGrid = document.getElementById('calendarGrid');
            calendarGrid.innerHTML = '';

            const today = new Date();
            const isCurrentMonth = today.getMonth() === month && today.getFullYear() === year;
            const todayDate = today.getDate();

            // Add previous month days
            for (let i = firstDay - 1; i >= 0; i--) {
                const day = daysInPrevMonth - i;
                const dayDiv = createDayElement(day, true, false);
                calendarGrid.appendChild(dayDiv);
            }

            // Add current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const isToday = isCurrentMonth && day === todayDate;
                const dateKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dayEvents = eventsData[dateKey];
                const dayDiv = createDayElement(day, false, isToday, dateKey, dayEvents);
                calendarGrid.appendChild(dayDiv);
            }

            // Add next month days to fill grid
            const totalCells = calendarGrid.children.length;
            const remainingCells = 35 - totalCells; // 6 weeks * 7 days
            for (let day = 1; day <= remainingCells; day++) {
                const dayDiv = createDayElement(day, true, false);
                calendarGrid.appendChild(dayDiv);
            }
        }

        // Create day element
        function createDayElement(day, isOtherMonth, isToday, dateKey, dayEvents) {
            const dayDiv = document.createElement('div');
            dayDiv.className = 'calendar-day';
            if (isOtherMonth) dayDiv.classList.add('other-month');
            if (isToday) dayDiv.classList.add('today');

            const dayNumber = document.createElement('div');
            dayNumber.className = 'calendar-day-number';
            dayNumber.innerHTML = `<span class="day-number">${day}</span>`;
            dayDiv.appendChild(dayNumber);

            if (!isOtherMonth && dayEvents) {
                const eventCount = dayEvents.events ? dayEvents.events.length : 0;
                const clientCount = dayEvents.client_count || 0;

                if (eventCount > 0) {
                    // Check if past event based on end_date
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    const allPast = dayEvents.events.every(ev => {
                        const endRaw = (ev.end_date || ev.start_date || '').toString().substring(0, 10);
                        const endDate = new Date(endRaw + 'T00:00:00');
                        return endDate < today;
                    });

                    if (allPast) {
                        dayDiv.classList.add('past-event');
                    } else {
                        dayDiv.classList.add('has-events');
                    }

                    // Add event badges
                    const dayEventsDiv = document.createElement('div');
                    dayEventsDiv.className = 'day-events';

                    if (eventCount > 0) {
                        const eventBadge = document.createElement('span');
                        eventBadge.className = 'event-badge';
                        eventBadge.textContent = `${eventCount} event${eventCount > 1 ? 's' : ''}`;
                        dayEventsDiv.appendChild(eventBadge);
                    }

                    if (clientCount > 1) {
                        const clientBadge = document.createElement('span');
                        clientBadge.className = 'client-badge';
                        clientBadge.textContent = `${clientCount} clients`;
                        dayEventsDiv.appendChild(clientBadge);
                    }

                    dayDiv.appendChild(dayEventsDiv);
                    dayDiv.style.cursor = 'pointer';

                    // Add click event
                    dayDiv.addEventListener('click', () => showEventDetails(dateKey, dayEvents));

                    // Add tooltip
                    dayDiv.className += ' tooltip';
                    const tooltipContent = document.createElement('div');
                    tooltipContent.className = 'tooltip-content';
                    tooltipContent.innerHTML = `
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Events:</span>
                            <span style="font-weight: 600;">${eventCount}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Clients:</span>
                            <span style="font-weight: 600;">${clientCount}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Budget:</span>
                            <span style="font-weight: 600; color: #10b981;">${formatCurrency(dayEvents.total_budget || 0)}</span>
                        </div>
                        ${(dayEvents.pending_payments || 0) > 0 ? `
                        <div style="display: flex; justify-content: space-between;">
                            <span>Pending:</span>
                            <span style="font-weight: 600; color: #f59e0b;">${formatCurrency(dayEvents.pending_payments || 0)}</span>
                        </div>
                        ` : ''}
                    `;
                    dayDiv.appendChild(tooltipContent);
                }
            }

            return dayDiv;
        }

        // Show event details in modal
        function showEventDetails(date, dayEvents) {
            const modal = document.getElementById('eventModal');
            const modalDate = document.getElementById('modalDate');
            const modalBadges = document.getElementById('modalBadges');
            const modalStats = document.getElementById('modalStats');
            const eventList = document.getElementById('eventList');

            // Format date
            const dateObj = new Date(date + 'T00:00:00');
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            modalDate.textContent = dateObj.toLocaleDateString('en-US', options);

            // Update badges
            const eventCount = dayEvents.events ? dayEvents.events.length : 0;
            const clientCount = dayEvents.client_count || 0;
            const totalBudget = dayEvents.total_budget || 0;

            modalBadges.innerHTML = `
                <div class="modal-badge">
                    <i class="fas fa-calendar-check"></i>
                    <span>${eventCount} Event${eventCount > 1 ? 's' : ''}</span>
                </div>
                <div class="modal-badge">
                    <i class="fas fa-rupee-sign"></i>
                    <span>${formatCurrency(totalBudget)}</span>
                </div>
                <div class="modal-badge">
                    <i class="fas fa-users"></i>
                    <span>${clientCount} Client${clientCount > 1 ? 's' : ''}</span>
                </div>
            `;

            // Update stats grid
            modalStats.innerHTML = `
                <div class="stat-card-modal">
                    <div class="stat-header-modal">
                        <div class="stat-icon-modal blue">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div>
                            <div class="stat-title-modal">Scheduled Events</div>
                            <div class="stat-subtitle-modal">Today's lineup</div>
                        </div>
                    </div>
                    <div class="stat-value-modal">${eventCount}</div>
                </div>
                
                <div class="stat-card-modal">
                    <div class="stat-header-modal">
                        <div class="stat-icon-modal green">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div>
                            <div class="stat-title-modal">Total Budget</div>
                            <div class="stat-subtitle-modal">Combined value</div>
                        </div>
                    </div>
                    <div class="stat-value-modal success">₹${formatFullCurrency(totalBudget)}</div>
                </div>
                
                <div class="stat-card-modal">
                    <div class="stat-header-modal">
                        <div class="stat-icon-modal blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="stat-title-modal">Unique Clients</div>
                            <div class="stat-subtitle-modal">Attending today</div>
                        </div>
                    </div>
                    <div class="stat-value-modal">${clientCount}</div>
                </div>
            `;

            // Update event list
            if (dayEvents.events && dayEvents.events.length > 0) {
                eventList.innerHTML = '';

                dayEvents.events.forEach((event, index) => {
                    // Use start_date for calculating status
                    const status = getEventStatus(event.start_date);
                    const progress = getProgressPercentage(status);

                    // Parse services
                    // Parse services
                    let services = [];
                    try {
                        const parsed = JSON.parse(event.service);
                        if (Array.isArray(parsed)) {
                            // New format: array of objects with .service property
                            services = parsed.map(s => typeof s === 'object' ? s.service : s).filter(Boolean);
                        }
                    } catch (e) {
                        services = event.service ? [event.service] : [];
                    }

                    // Format time range
                    const timeRange = event.start_time && event.end_time ?
                        `${formatTime(event.start_time)} - ${formatTime(event.end_time)}` :
                        'Time TBD';

                    const eventCard = document.createElement('div');
                    eventCard.className = 'event-card';
                    eventCard.innerHTML = `
                        <div class="event-header">
                            <div>
                                <h4 class="event-name">${event.name}</h4>
                                <div class="event-client">
                                    <i class="fas fa-user"></i>
                                    <span>${event.client_name}</span>
                                </div>
                            </div>
                            <span class="event-status status-${status.replace('-', '-')}">
                                ${status.replace('-', ' ')}
                            </span>
                        </div>
                        
                        <div class="event-details-grid">
                            <div class="event-detail">
                                <i class="fas fa-clock"></i>
                                <span>${timeRange}</span>
                            </div>
                            <div class="event-detail">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>${event.venue || 'Venue TBD'}</span>
                            </div>
                            <div class="event-detail">
                                <i class="fas fa-rupee-sign"></i>
                                <span>${formatCurrency(event.budget || 0)}</span>
                            </div>
                        </div>
                        
                        ${services.length > 0 ? `
                        <div class="services-section">
                            <div class="services-label">Services:</div>
                            <div>
                                ${services.map(service => 
                                    `<span class="service-badge">${service}</span>`
                                ).join('')}
                            </div>
                        </div>
                        ` : ''}
                        
                        ${event.description ? `
                        <div class="services-section">
                            <div class="services-label">Description:</div>
                            <div style="font-size: 14px; color: #64748b;">${event.description}</div>
                        </div>
                        ` : ''}
                        
                        <div class="progress-section">
                            <div class="progress-header">
                                <span class="progress-label">Progress</span>
                                <span class="progress-percentage">${progress}%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill progress-${status.replace('-', '-')}" 
                                     style="width: ${progress}%"></div>
                            </div>
                        </div>
                    `;

                    eventList.appendChild(eventCard);
                });
            } else {
                eventList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>No events scheduled for this date</p>
                    </div>
                `;
            }

            modal.classList.add('show');
        }

        // Close modal
        document.getElementById('closeModal').addEventListener('click', () => {
            document.getElementById('eventModal').classList.remove('show');
        });

        // Close modal on backdrop click
        document.getElementById('eventModal').addEventListener('click', (e) => {
            if (e.target.id === 'eventModal') {
                document.getElementById('eventModal').classList.remove('show');
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.getElementById('eventModal').classList.remove('show');
            }
        });

        // Calendar navigation
        document.getElementById('prevMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        });

        // Initialize
        renderCalendar();
    </script>
</body>

</html>