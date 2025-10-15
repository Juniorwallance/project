<?php
// admin_highlights.php - Admin interface for managing highlights
require 'db.php';
session_start();

// Simple authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                createHighlight();
                break;
            case 'update':
                updateHighlight();
                break;
            case 'delete':
                deleteHighlight();
                break;
        }
    }
}

function createHighlight() {
    global $pdo;
    
    $title = $_POST['title'];
    $description = $_POST['description'];
    $thumbnail = $_POST['thumbnail'];
    $video_url = $_POST['video_url'];
    $duration = $_POST['duration'];
    $categories = implode(',', $_POST['categories'] ?? []);
    $tags = $_POST['tags'];
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    $stmt = $pdo->prepare('
        INSERT INTO highlights (title, description, thumbnail, video_url, duration, categories, tags, featured)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    if ($stmt->execute([$title, $description, $thumbnail, $video_url, $duration, $categories, $tags, $featured])) {
        $message = "Highlight created successfully!";
    } else {
        $error = "Error creating highlight.";
    }
}

function updateHighlight() {
    global $pdo;
    
    $id = $_POST['id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $thumbnail = $_POST['thumbnail'];
    $video_url = $_POST['video_url'];
    $duration = $_POST['duration'];
    $categories = implode(',', $_POST['categories'] ?? []);
    $tags = $_POST['tags'];
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    $stmt = $pdo->prepare('
        UPDATE highlights 
        SET title=?, description=?, thumbnail=?, video_url=?, duration=?, categories=?, tags=?, featured=?
        WHERE id=?
    ');
    
    if ($stmt->execute([$title, $description, $thumbnail, $video_url, $duration, $categories, $tags, $featured, $id])) {
        $message = "Highlight updated successfully!";
    } else {
        $error = "Error updating highlight.";
    }
}

function deleteHighlight() {
    global $pdo;
    
    $id = $_POST['id'];
    $stmt = $pdo->prepare('DELETE FROM highlights WHERE id = ?');
    
    if ($stmt->execute([$id])) {
        $message = "Highlight deleted successfully!";
    } else {
        $error = "Error deleting highlight.";
    }
}

// Get all highlights for display
$stmt = $pdo->prepare('SELECT * FROM highlights ORDER BY posted_date DESC');
$stmt->execute();
$highlights = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Highlights Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .form-section { background: #f5f5f5; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        textarea { height: 100px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .delete-btn { background: #dc3545; }
        .delete-btn:hover { background: #c82333; }
        .highlights-list { margin-top: 30px; }
        .highlight-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
        .highlight-item h3 { margin: 0 0 10px 0; }
        .highlight-meta { color: #666; font-size: 0.9em; margin-bottom: 10px; }
        .actions { margin-top: 10px; }
        .actions button { margin-right: 10px; }
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; }
        .checkbox-group { display: flex; flex-wrap: wrap; gap: 10px; }
        .checkbox-group label { display: inline; margin-right: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Highlights Management</h1>
        
        <?php if (isset($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="form-section">
            <h2>Add New Highlight</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="thumbnail">Thumbnail URL:</label>
                    <input type="url" id="thumbnail" name="thumbnail" required>
                </div>
                
                <div class="form-group">
                    <label for="video_url">Video URL:</label>
                    <input type="url" id="video_url" name="video_url" required>
                </div>
                
                <div class="form-group">
                    <label for="duration">Duration (e.g., 2:45):</label>
                    <input type="text" id="duration" name="duration" required>
                </div>
                
                <div class="form-group">
                    <label>Categories:</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="categories[]" value="try"> Try</label>
                        <label><input type="checkbox" name="categories[]" value="tackle"> Tackle</label>
                        <label><input type="checkbox" name="categories[]" value="kick"> Kick</label>
                        <label><input type="checkbox" name="categories[]" value="recent"> Recent</label>
                        <label><input type="checkbox" name="categories[]" value="classic"> Classic</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tags">Tags (comma-separated):</label>
                    <input type="text" id="tags" name="tags" placeholder="championship, national, team">
                </div>
                
                <div class="form-group">
                    <label><input type="checkbox" name="featured"> Featured Highlight</label>
                </div>
                
                <button type="submit">Create Highlight</button>
            </form>
        </div>
        
        <div class="highlights-list">
            <h2>Existing Highlights</h2>
            
            <?php foreach ($highlights as $highlight): ?>
                <div class="highlight-item">
                    <h3><?= htmlspecialchars($highlight['title']) ?></h3>
                    <div class="highlight-meta">
                        Posted: <?= date('M j, Y', strtotime($highlight['posted_date'])) ?> | 
                        Views: <?= number_format($highlight['views']) ?> | 
                        Likes: <?= number_format($highlight['likes']) ?> |
                        Categories: <?= htmlspecialchars($highlight['categories']) ?>
                        <?php if ($highlight['featured']): ?> | <strong>FEATURED</strong><?php endif; ?>
                    </div>
                    <p><?= htmlspecialchars($highlight['description']) ?></p>
                    
                    <div class="actions">
                        <button onclick="editHighlight(<?= htmlspecialchars(json_encode($highlight)) ?>)">Edit</button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this highlight?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $highlight['id'] ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        function editHighlight(highlight) {
            // Populate form with highlight data
            document.getElementById('title').value = highlight.title;
            document.getElementById('description').value = highlight.description;
            document.getElementById('thumbnail').value = highlight.thumbnail;
            document.getElementById('video_url').value = highlight.video_url;
            document.getElementById('duration').value = highlight.duration;
            document.getElementById('tags').value = highlight.tags;
            
            // Check categories
            const categories = highlight.categories.split(',');
            document.querySelectorAll('input[name="categories[]"]').forEach(checkbox => {
                checkbox.checked = categories.includes(checkbox.value);
            });
            
            // Check featured
            document.querySelector('input[name="featured"]').checked = highlight.featured == 1;
            
            // Change form to update mode
            const form = document.querySelector('form');
            form.querySelector('input[name="action"]').value = 'update';
            form.insertAdjacentHTML('afterbegin', '<input type="hidden" name="id" value="' + highlight.id + '">');
            form.querySelector('button[type="submit"]').textContent = 'Update Highlight';
            
            // Scroll to form
            document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>