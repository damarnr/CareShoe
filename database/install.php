<?php
// Database installation script
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'shoe_laundry_db';

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8 COLLATE utf8_general_ci");
    echo "Database '$dbname' created successfully or already exists.\n";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute schema.sql
    $schema = file_get_contents('schema.sql');
    if ($schema === false) {
        throw new Exception('Could not read schema.sql file');
    }
    
    // Split the schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "Database schema installed successfully.\n";
    echo "Tables created:\n";
    echo "- services\n";
    echo "- orders\n";
    echo "- admin_users\n";
    echo "\nDefault admin credentials:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    echo "\nInstallation completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

