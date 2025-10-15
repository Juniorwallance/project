<?php
// test_highlights_api.php - Test script for highlights API
echo "<h2>Testing Highlights API</h2>";

// Test 1: Get all highlights
echo "<h3>Test 1: Get all highlights</h3>";
$response = file_get_contents('http://localhost/highlights_api.php');
$data = json_decode($response, true);
if ($data && isset($data['highlights'])) {
    echo "✅ Success: Retrieved " . count($data['highlights']) . " highlights<br>";
    echo "Total pages: " . $data['totalPages'] . "<br>";
    echo "Total highlights: " . $data['total'] . "<br>";
} else {
    echo "❌ Error: " . $response . "<br>";
}

// Test 2: Get featured highlights
echo "<h3>Test 2: Get featured highlights</h3>";
$response = file_get_contents('http://localhost/highlights_api.php/featured');
$data = json_decode($response, true);
if ($data && is_array($data)) {
    echo "✅ Success: Retrieved " . count($data) . " featured highlights<br>";
} else {
    echo "❌ Error: " . $response . "<br>";
}

// Test 3: Get categories
echo "<h3>Test 3: Get categories</h3>";
$response = file_get_contents('http://localhost/highlights_api.php/categories');
$data = json_decode($response, true);
if ($data && is_array($data)) {
    echo "✅ Success: Retrieved categories: " . implode(', ', $data) . "<br>";
} else {
    echo "❌ Error: " . $response . "<br>";
}

// Test 4: Get statistics
echo "<h3>Test 4: Get statistics</h3>";
$response = file_get_contents('http://localhost/highlights_api.php/statistics');
$data = json_decode($response, true);
if ($data && isset($data['totalHighlights'])) {
    echo "✅ Success: Retrieved statistics<br>";
    echo "Total highlights: " . $data['totalHighlights'] . "<br>";
    echo "Total views: " . $data['totalViews'] . "<br>";
    echo "Total subscribers: " . $data['totalSubscribers'] . "<br>";
} else {
    echo "❌ Error: " . $response . "<br>";
}

// Test 5: Test filtering
echo "<h3>Test 5: Test filtering (try category)</h3>";
$response = file_get_contents('http://localhost/highlights_api.php?category=try');
$data = json_decode($response, true);
if ($data && isset($data['highlights'])) {
    echo "✅ Success: Retrieved " . count($data['highlights']) . " try highlights<br>";
} else {
    echo "❌ Error: " . $response . "<br>";
}

// Test 6: Test search
echo "<h3>Test 6: Test search (championship)</h3>";
$response = file_get_contents('http://localhost/highlights_api.php?search=championship');
$data = json_decode($response, true);
if ($data && isset($data['highlights'])) {
    echo "✅ Success: Found " . count($data['highlights']) . " highlights matching 'championship'<br>";
} else {
    echo "❌ Error: " . $response . "<br>";
}

echo "<h3>API Test Complete!</h3>";
echo "<p><a href='highlights.html'>Go to Highlights Page</a></p>";
echo "<p><a href='admin_highlights.php'>Go to Admin Interface</a></p>";
?>