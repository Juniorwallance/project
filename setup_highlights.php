<?php
// setup_highlights.php - Setup script for highlights functionality
require 'db.php';

echo "<h2>Setting up Highlights Database...</h2>";

try {
    // Read and execute the SQL schema
    $sql = file_get_contents('highlights_schema.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $pdo->exec($statement);
            echo "<p>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
        }
    }
    
    echo "<h3>✅ Database setup completed successfully!</h3>";
    echo "<p>The highlights functionality is now ready to use.</p>";
    echo "<p><a href='highlights.html'>Go to Highlights Page</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error setting up database:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>