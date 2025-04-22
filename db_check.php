<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once 'config/db_connect.php';

echo "<h1>Database Structure and Content Check</h1>";

// Check if tables exist
$tables = [
    'users',
    'professionals',
    'specializations',
    'languages',
    'professional_specializations',
    'professional_languages'
];

echo "<h2>Table Existence Check</h2>";
echo "<ul>";
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    $exists = $result->num_rows > 0;
    echo "<li>Table <strong>$table</strong>: " . ($exists ? "✅ Exists" : "❌ Missing") . "</li>";
}
echo "</ul>";

// Check if specializations and languages tables have content
echo "<h2>Content Check</h2>";

// Specializations
echo "<h3>Specializations</h3>";
$result = $conn->query("SELECT * FROM specializations");
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Status</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . ($row['is_active'] ? "Active" : "Inactive") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No specializations found in the database.</p>";
        
        // Add some default specializations if none exist
        echo "<h4>Adding default specializations...</h4>";
        $defaults = [
            "Student Visa",
            "Work Visa",
            "Tourist Visa",
            "Permanent Residency",
            "Citizenship",
            "Family Sponsorship",
            "Business Visa",
            "Asylum & Refugee"
        ];
        
        foreach ($defaults as $name) {
            $stmt = $conn->prepare("INSERT INTO specializations (name, is_active) VALUES (?, 1)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                echo "<p>✅ Added: $name</p>";
            } else {
                echo "<p>❌ Failed to add: $name</p>";
            }
        }
    }
} else {
    echo "<p>❌ Error querying specializations: " . $conn->error . "</p>";
    
    // Create table if it doesn't exist
    echo "<h4>Creating specializations table...</h4>";
    $sql = "CREATE TABLE specializations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Table created successfully</p>";
        
        // Add some default specializations
        echo "<h4>Adding default specializations...</h4>";
        $defaults = [
            "Student Visa",
            "Work Visa",
            "Tourist Visa",
            "Permanent Residency",
            "Citizenship",
            "Family Sponsorship",
            "Business Visa",
            "Asylum & Refugee"
        ];
        
        foreach ($defaults as $name) {
            $stmt = $conn->prepare("INSERT INTO specializations (name, is_active) VALUES (?, 1)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                echo "<p>✅ Added: $name</p>";
            } else {
                echo "<p>❌ Failed to add: $name</p>";
            }
        }
    } else {
        echo "<p>❌ Table creation failed: " . $conn->error . "</p>";
    }
}

// Languages
echo "<h3>Languages</h3>";
$result = $conn->query("SELECT * FROM languages");
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Status</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . ($row['is_active'] ? "Active" : "Inactive") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No languages found in the database.</p>";
        
        // Add some default languages if none exist
        echo "<h4>Adding default languages...</h4>";
        $defaults = [
            "English",
            "Spanish",
            "French",
            "German",
            "Chinese",
            "Arabic",
            "Hindi",
            "Russian"
        ];
        
        foreach ($defaults as $name) {
            $stmt = $conn->prepare("INSERT INTO languages (name, is_active) VALUES (?, 1)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                echo "<p>✅ Added: $name</p>";
            } else {
                echo "<p>❌ Failed to add: $name</p>";
            }
        }
    }
} else {
    echo "<p>❌ Error querying languages: " . $conn->error . "</p>";
    
    // Create table if it doesn't exist
    echo "<h4>Creating languages table...</h4>";
    $sql = "CREATE TABLE languages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Table created successfully</p>";
        
        // Add some default languages
        echo "<h4>Adding default languages...</h4>";
        $defaults = [
            "English",
            "Spanish",
            "French",
            "German",
            "Chinese",
            "Arabic",
            "Hindi",
            "Russian"
        ];
        
        foreach ($defaults as $name) {
            $stmt = $conn->prepare("INSERT INTO languages (name, is_active) VALUES (?, 1)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                echo "<p>✅ Added: $name</p>";
            } else {
                echo "<p>❌ Failed to add: $name</p>";
            }
        }
    } else {
        echo "<p>❌ Table creation failed: " . $conn->error . "</p>";
    }
}

// Check if professionals tables have proper structure
echo "<h2>Professionals Table Structure</h2>";
$result = $conn->query("SHOW TABLES LIKE 'professionals'");
if ($result->num_rows > 0) {
    $result = $conn->query("DESCRIBE professionals");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . ($value ?? "NULL") . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Professionals table doesn't exist</p>";
    
    // Create table if it doesn't exist
    echo "<h4>Creating professionals table...</h4>";
    $sql = "CREATE TABLE professionals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        license_number VARCHAR(100) NOT NULL,
        years_experience INT NOT NULL,
        education TEXT NOT NULL,
        bio TEXT NOT NULL,
        phone VARCHAR(20) NOT NULL,
        website VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Table created successfully</p>";
    } else {
        echo "<p>❌ Table creation failed: " . $conn->error . "</p>";
    }
}

// Check if professional_specializations table has proper structure
echo "<h2>Professional Specializations Table Structure</h2>";
$result = $conn->query("SHOW TABLES LIKE 'professional_specializations'");
if ($result->num_rows > 0) {
    $result = $conn->query("DESCRIBE professional_specializations");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . ($value ?? "NULL") . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Professional Specializations table doesn't exist</p>";
    
    // Create table if it doesn't exist
    echo "<h4>Creating professional_specializations table...</h4>";
    $sql = "CREATE TABLE professional_specializations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        professional_id INT NOT NULL,
        specialization_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE CASCADE,
        FOREIGN KEY (specialization_id) REFERENCES specializations(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Table created successfully</p>";
    } else {
        echo "<p>❌ Table creation failed: " . $conn->error . "</p>";
    }
}

// Check if professional_languages table has proper structure
echo "<h2>Professional Languages Table Structure</h2>";
$result = $conn->query("SHOW TABLES LIKE 'professional_languages'");
if ($result->num_rows > 0) {
    $result = $conn->query("DESCRIBE professional_languages");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . ($value ?? "NULL") . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Professional Languages table doesn't exist</p>";
    
    // Create table if it doesn't exist
    echo "<h4>Creating professional_languages table...</h4>";
    $sql = "CREATE TABLE professional_languages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        professional_id INT NOT NULL,
        language_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE CASCADE,
        FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Table created successfully</p>";
    } else {
        echo "<p>❌ Table creation failed: " . $conn->error . "</p>";
    }
}
?> 