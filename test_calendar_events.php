<?php
require_once 'lydo-system/admin2/config.php';

$pdo = db();
$events = $pdo->query("
    SELECT 
        id, 
        title, 
        event_date, 
        event_time, 
        event_type, 
        location 
    FROM events 
    WHERE event_date >= CURDATE() 
    ORDER BY event_date ASC 
    LIMIT 10
")->fetchAll();

echo "<h2>Calendar Events Check</h2>";
echo "<p><strong>Total upcoming events:</strong> " . count($events) . "</p><br>";

if (empty($events)) {
    echo "<div style='color:#c62828;padding:20px;background:#ffebee;border-radius:8px'>";
    echo "<h3>❌ No upcoming events found in database!</h3>";
    echo "<p>The calendar is empty because there are no events with <code>event_date >= today</code>.</p>";
    echo "<h4>Solutions:</h4>";
    echo "<ol>";
    echo "<li><strong>Create events manually:</strong> Go to <a href='lydo-system/admin2/events.php'>Events Page</a> and click 'Add Event'</li>";
    echo "<li><strong>Run test script:</strong> <a href='lydo-system/admin2/test_create_events.php'>Create Sample Events</a></li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='color:#2e7d32;padding:20px;background:#e8f5e9;border-radius:8px'>";
    echo "<h3>✅ Events found! These should appear on calendar:</h3><br>";
    echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse:collapse;width:100%'>";
    echo "<tr style='background:#1565c0;color:#fff'><th>#</th><th>Title</th><th>Date</th><th>Time</th><th>Type</th><th>Location</th></tr>";
    foreach ($events as $i => $e) {
        echo "<tr>";
        echo "<td>" . ($i + 1) . "</td>";
        echo "<td><strong>" . htmlspecialchars($e['title']) . "</strong></td>";
        echo "<td>" . date('M j, Y', strtotime($e['event_date'])) . "</td>";
        echo "<td>" . ($e['event_time'] ? date('g:i A', strtotime($e['event_time'])) : '—') . "</td>";
        echo "<td><span style='background:#e3f2fd;padding:4px 8px;border-radius:4px'>" . htmlspecialchars($e['event_type']) . "</span></td>";
        echo "<td>" . htmlspecialchars($e['location'] ?? '—') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
}

echo "<br><br>";
echo "<h3>📋 Quick Actions:</h3>";
echo "<ul>";
echo "<li><a href='lydo-system/admin2/dashboard.php' style='color:#1565c0;font-weight:bold'>→ Go to Dashboard (View Calendar)</a></li>";
echo "<li><a href='lydo-system/admin2/events.php' style='color:#1565c0;font-weight:bold'>→ Manage Events</a></li>";
echo "<li><a href='lydo-system/admin2/test_create_events.php' style='color:#2e7d32;font-weight:bold'>→ Create Sample Events</a></li>";
echo "</ul>";
?>
