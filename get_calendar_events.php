<?php
require 'config/db.php';

header('Content-Type: application/json');

try {
    // Fetch all events with their dates
    $query = "SELECT 
                e.id,
                e.name,
                e.client_name,
                e.venue,
                e.service,
                e.budget,
                e.description,
                e.event_date,
                e.created_at
              FROM events e
              WHERE e.event_date IS NOT NULL
              ORDER BY e.event_date";
    
    $result = $conn->query($query);
    
    $eventsData = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $eventDate = $row['event_date'];
            
            // Calculate progress based on event date
            $today = new DateTime();
            $eventDateTime = new DateTime($eventDate);
            $daysUntilEvent = $today->diff($eventDateTime)->days;
            $isPast = $eventDateTime < $today;
            
            $progress = 'upcoming';
            $status = 'upcoming';
            
            if ($isPast) {
                $progress = 'completed';
                $status = 'completed';
            } elseif ($daysUntilEvent <= 7) {
                $progress = 'in-progress';
                $status = 'in-progress';
            }
            
            // Initialize date key if not exists
            if (!isset($eventsData[$eventDate])) {
                $eventsData[$eventDate] = [
                    'events' => 0,
                    'clients' => [],
                    'details' => []
                ];
            }
            
            // Increment event count
            $eventsData[$eventDate]['events']++;
            
            // Add unique clients
            if (!in_array($row['client_name'], $eventsData[$eventDate]['clients'])) {
                $eventsData[$eventDate]['clients'][] = $row['client_name'];
            }
            
            // Decode service if it's JSON
            $services = $row['service'];
            if (is_string($services) && strpos($services, '[') === 0) {
                $services = json_decode($services, true);
                $services = is_array($services) ? implode(', ', $services) : $services;
            }
            
            // Add event details
            $eventsData[$eventDate]['details'][] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'client_name' => $row['client_name'],
                'venue' => $row['venue'],
                'service' => $services,
                'budget' => $row['budget'],
                'description' => $row['description'],
                'event_date' => $eventDate,
                'progress' => $progress,
                'status' => $status,
                'days_until' => $isPast ? null : $daysUntilEvent
            ];
        }
    }
    
    // Convert clients array to count
    $formattedData = [];
    foreach ($eventsData as $date => $data) {
        $formattedData[$date] = [
            'events' => $data['events'],
            'clients' => count($data['clients']),
            'details' => $data['details']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'events' => $formattedData
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>