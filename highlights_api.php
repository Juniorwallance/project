<?php
// highlights_api.php - Highlights API endpoints
require 'db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Remove 'highlights_api.php' from path parts
$pathParts = array_filter($pathParts, function($part) {
    return $part !== 'highlights_api.php';
});
$pathParts = array_values($pathParts);

// Helper function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Helper function to get authorization token
function getAuthToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

// Helper function to validate JWT token (simplified)
function validateToken($token) {
    // In a real implementation, you would validate the JWT token
    // For now, we'll use a simple session-based approach
    session_start();
    return isset($_SESSION['user_id']);
}

// Helper function to get current user ID
function getCurrentUserId() {
    session_start();
    return $_SESSION['user_id'] ?? null;
}

// Route handling
try {
    switch ($method) {
        case 'GET':
            if (empty($pathParts)) {
                // GET /highlights_api.php - Get all highlights with filtering
                getHighlights();
            } elseif ($pathParts[0] === 'featured') {
                // GET /highlights_api.php/featured - Get featured highlights
                getFeaturedHighlights();
            } elseif ($pathParts[0] === 'categories') {
                // GET /highlights_api.php/categories - Get highlight categories
                getCategories();
            } elseif ($pathParts[0] === 'statistics') {
                // GET /highlights_api.php/statistics - Get highlight statistics
                getStatistics();
            } elseif (is_numeric($pathParts[0])) {
                // GET /highlights_api.php/{id} - Get single highlight
                getHighlight($pathParts[0]);
            } else {
                sendResponse(['error' => 'Not found'], 404);
            }
            break;
            
        case 'POST':
            if ($pathParts[0] === 'subscribe') {
                // POST /highlights_api.php/subscribe - Newsletter subscription
                subscribeNewsletter();
            } elseif ($pathParts[0] === 'unsubscribe') {
                // POST /highlights_api.php/unsubscribe - Newsletter unsubscribe
                unsubscribeNewsletter();
            } elseif (is_numeric($pathParts[0]) && $pathParts[1] === 'like') {
                // POST /highlights_api.php/{id}/like - Like a highlight
                likeHighlight($pathParts[0]);
            } elseif (is_numeric($pathParts[0]) && $pathParts[1] === 'save') {
                // POST /highlights_api.php/{id}/save - Save highlight to user's list
                saveHighlight($pathParts[0]);
            } else {
                // POST /highlights_api.php - Create new highlight (Admin only)
                createHighlight();
            }
            break;
            
        case 'PUT':
            if (is_numeric($pathParts[0])) {
                // PUT /highlights_api.php/{id} - Update highlight
                updateHighlight($pathParts[0]);
            } else {
                sendResponse(['error' => 'Not found'], 404);
            }
            break;
            
        case 'DELETE':
            if (is_numeric($pathParts[0])) {
                // DELETE /highlights_api.php/{id} - Delete highlight
                deleteHighlight($pathParts[0]);
            } elseif (is_numeric($pathParts[0]) && $pathParts[1] === 'save') {
                // DELETE /highlights_api.php/{id}/save - Remove highlight from saved list
                unsaveHighlight($pathParts[0]);
            } else {
                sendResponse(['error' => 'Not found'], 404);
            }
            break;
            
        default:
            sendResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}

// Get all highlights with filtering
function getHighlights() {
    global $pdo;
    
    $category = $_GET['category'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'posted_date';
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 12);
    $offset = ($page - 1) * $limit;
    
    $whereConditions = [];
    $params = [];
    
    // Filter by category
    if ($category !== 'all') {
        $whereConditions[] = "FIND_IN_SET(?, categories) > 0";
        $params[] = $category;
    }
    
    // Search in title and description
    if (!empty($search)) {
        $whereConditions[] = "(title LIKE ? OR description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Validate sort field
    $allowedSorts = ['posted_date', 'views', 'likes', 'title'];
    if (!in_array($sort, $allowedSorts)) {
        $sort = 'posted_date';
    }
    
    // Get highlights
    $sql = "SELECT * FROM highlights $whereClause ORDER BY $sort DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $highlights = $stmt->fetchAll();
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM highlights $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute(array_slice($params, 0, -2)); // Remove limit and offset
    $total = $countStmt->fetch()['total'];
    
    sendResponse([
        'highlights' => $highlights,
        'totalPages' => ceil($total / $limit),
        'currentPage' => $page,
        'total' => $total
    ]);
}

// Get single highlight
function getHighlight($id) {
    global $pdo;
    
    $stmt = $pdo->prepare('SELECT * FROM highlights WHERE id = ?');
    $stmt->execute([$id]);
    $highlight = $stmt->fetch();
    
    if (!$highlight) {
        sendResponse(['error' => 'Highlight not found'], 404);
    }
    
    // Increment views
    $updateStmt = $pdo->prepare('UPDATE highlights SET views = views + 1 WHERE id = ?');
    $updateStmt->execute([$id]);
    
    sendResponse($highlight);
}

// Get featured highlights
function getFeaturedHighlights() {
    global $pdo;
    
    $stmt = $pdo->prepare('SELECT * FROM highlights WHERE featured = 1 ORDER BY posted_date DESC LIMIT 6');
    $stmt->execute();
    $highlights = $stmt->fetchAll();
    
    sendResponse($highlights);
}

// Create new highlight (Admin only)
function createHighlight() {
    global $pdo;
    
    if (!validateToken(getAuthToken())) {
        sendResponse(['error' => 'Unauthorized'], 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['title', 'description', 'thumbnail', 'video_url', 'duration', 'categories'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            sendResponse(['error' => "Missing required field: $field"], 400);
        }
    }
    
    $title = $input['title'];
    $description = $input['description'];
    $thumbnail = $input['thumbnail'];
    $videoUrl = $input['video_url'];
    $duration = $input['duration'];
    $categories = is_array($input['categories']) ? implode(',', $input['categories']) : $input['categories'];
    $tags = isset($input['tags']) ? (is_array($input['tags']) ? implode(',', $input['tags']) : $input['tags']) : '';
    $featured = isset($input['featured']) ? (int)$input['featured'] : 0;
    
    $stmt = $pdo->prepare('
        INSERT INTO highlights (title, description, thumbnail, video_url, duration, categories, tags, featured, views, likes, posted_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW())
    ');
    
    $stmt->execute([$title, $description, $thumbnail, $videoUrl, $duration, $categories, $tags, $featured]);
    
    $highlightId = $pdo->lastInsertId();
    
    // Get the created highlight
    $stmt = $pdo->prepare('SELECT * FROM highlights WHERE id = ?');
    $stmt->execute([$highlightId]);
    $highlight = $stmt->fetch();
    
    sendResponse($highlight, 201);
}

// Update highlight
function updateHighlight($id) {
    global $pdo;
    
    if (!validateToken(getAuthToken())) {
        sendResponse(['error' => 'Unauthorized'], 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $updateFields = [];
    $params = [];
    
    $allowedFields = ['title', 'description', 'thumbnail', 'video_url', 'duration', 'categories', 'tags', 'featured'];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateFields[] = "$field = ?";
            if ($field === 'categories' && is_array($input[$field])) {
                $params[] = implode(',', $input[$field]);
            } elseif ($field === 'tags' && is_array($input[$field])) {
                $params[] = implode(',', $input[$field]);
            } else {
                $params[] = $input[$field];
            }
        }
    }
    
    if (empty($updateFields)) {
        sendResponse(['error' => 'No fields to update'], 400);
    }
    
    $params[] = $id;
    
    $sql = 'UPDATE highlights SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    if ($stmt->rowCount() === 0) {
        sendResponse(['error' => 'Highlight not found'], 404);
    }
    
    // Get updated highlight
    $stmt = $pdo->prepare('SELECT * FROM highlights WHERE id = ?');
    $stmt->execute([$id]);
    $highlight = $stmt->fetch();
    
    sendResponse($highlight);
}

// Delete highlight
function deleteHighlight($id) {
    global $pdo;
    
    if (!validateToken(getAuthToken())) {
        sendResponse(['error' => 'Unauthorized'], 401);
    }
    
    $stmt = $pdo->prepare('DELETE FROM highlights WHERE id = ?');
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        sendResponse(['error' => 'Highlight not found'], 404);
    }
    
    sendResponse(['message' => 'Highlight deleted successfully']);
}

// Like a highlight
function likeHighlight($id) {
    global $pdo;
    
    if (!validateToken(getAuthToken())) {
        sendResponse(['error' => 'Unauthorized'], 401);
    }
    
    $stmt = $pdo->prepare('UPDATE highlights SET likes = likes + 1 WHERE id = ?');
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        sendResponse(['error' => 'Highlight not found'], 404);
    }
    
    // Get updated likes count
    $stmt = $pdo->prepare('SELECT likes FROM highlights WHERE id = ?');
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    sendResponse(['likes' => $result['likes']]);
}

// Save highlight to user's saved list
function saveHighlight($id) {
    global $pdo;
    
    $userId = getCurrentUserId();
    if (!$userId) {
        sendResponse(['error' => 'Unauthorized'], 401);
    }
    
    // Check if highlight exists
    $stmt = $pdo->prepare('SELECT id FROM highlights WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        sendResponse(['error' => 'Highlight not found'], 404);
    }
    
    // Check if already saved
    $stmt = $pdo->prepare('SELECT id FROM user_saved_highlights WHERE user_id = ? AND highlight_id = ?');
    $stmt->execute([$userId, $id]);
    if ($stmt->fetch()) {
        sendResponse(['message' => 'Highlight already saved']);
    }
    
    // Save highlight
    $stmt = $pdo->prepare('INSERT INTO user_saved_highlights (user_id, highlight_id, saved_at) VALUES (?, ?, NOW())');
    $stmt->execute([$userId, $id]);
    
    sendResponse(['message' => 'Highlight saved successfully']);
}

// Remove highlight from saved list
function unsaveHighlight($id) {
    global $pdo;
    
    $userId = getCurrentUserId();
    if (!$userId) {
        sendResponse(['error' => 'Unauthorized'], 401);
    }
    
    $stmt = $pdo->prepare('DELETE FROM user_saved_highlights WHERE user_id = ? AND highlight_id = ?');
    $stmt->execute([$userId, $id]);
    
    sendResponse(['message' => 'Highlight removed from saved list']);
}

// Get highlight categories
function getCategories() {
    global $pdo;
    
    $stmt = $pdo->prepare('SELECT DISTINCT categories FROM highlights WHERE categories IS NOT NULL AND categories != ""');
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    $categories = [];
    foreach ($results as $result) {
        $cats = explode(',', $result['categories']);
        foreach ($cats as $cat) {
            $cat = trim($cat);
            if (!in_array($cat, $categories)) {
                $categories[] = $cat;
            }
        }
    }
    
    sendResponse($categories);
}

// Get highlight statistics
function getStatistics() {
    global $pdo;
    
    $stmt = $pdo->prepare('SELECT COUNT(*) as total_highlights FROM highlights');
    $stmt->execute();
    $totalHighlights = $stmt->fetch()['total_highlights'];
    
    $stmt = $pdo->prepare('SELECT SUM(views) as total_views FROM highlights');
    $stmt->execute();
    $totalViews = $stmt->fetch()['total_views'] ?? 0;
    
    $stmt = $pdo->prepare('SELECT title, views FROM highlights ORDER BY views DESC LIMIT 1');
    $stmt->execute();
    $mostViewed = $stmt->fetch();
    
    $stmt = $pdo->prepare('SELECT COUNT(*) as total_subscribers FROM newsletter_subscriptions WHERE active = 1');
    $stmt->execute();
    $totalSubscribers = $stmt->fetch()['total_subscribers'];
    
    sendResponse([
        'totalHighlights' => $totalHighlights,
        'totalViews' => $totalViews,
        'mostViewed' => $mostViewed ?: null,
        'totalSubscribers' => $totalSubscribers
    ]);
}

// Newsletter subscription
function subscribeNewsletter() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        sendResponse(['error' => 'Invalid email address'], 400);
    }
    
    $email = $input['email'];
    
    // Check if already subscribed
    $stmt = $pdo->prepare('SELECT id, active FROM newsletter_subscriptions WHERE email = ?');
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['active']) {
            sendResponse(['error' => 'Email already subscribed'], 400);
        } else {
            // Reactivate subscription
            $stmt = $pdo->prepare('UPDATE newsletter_subscriptions SET active = 1, subscribed_at = NOW() WHERE id = ?');
            $stmt->execute([$existing['id']]);
            sendResponse(['message' => 'Resubscribed successfully']);
        }
    } else {
        // Create new subscription
        $stmt = $pdo->prepare('INSERT INTO newsletter_subscriptions (email, active, subscribed_at) VALUES (?, 1, NOW())');
        $stmt->execute([$email]);
        sendResponse(['message' => 'Subscribed successfully'], 201);
    }
}

// Newsletter unsubscribe
function unsubscribeNewsletter() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['email'])) {
        sendResponse(['error' => 'Email required'], 400);
    }
    
    $email = $input['email'];
    
    $stmt = $pdo->prepare('UPDATE newsletter_subscriptions SET active = 0 WHERE email = ?');
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() === 0) {
        sendResponse(['error' => 'Email not found in subscription list'], 404);
    }
    
    sendResponse(['message' => 'Unsubscribed successfully']);
}
?>