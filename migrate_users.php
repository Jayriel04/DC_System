<?php
require_once __DIR__ . '/vendor/autoload.php';
include('includes/dbconnection.php');

$auth = new \Delight\Auth\Auth($dbh);

// Check if users table exists, if not, create it
try {
    $stmt = $dbh->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        // Create users table
        $createUsers = "CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(249) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '0',
  `verified` tinyint unsigned NOT NULL DEFAULT '0',
  `resettable` tinyint unsigned NOT NULL DEFAULT '1',
  `roles_mask` int unsigned NOT NULL DEFAULT '0',
  `registered` int unsigned NOT NULL,
  `last_login` int unsigned DEFAULT NULL,
  `force_logout` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $dbh->exec($createUsers);
        echo "Users table created.\n";
    } else {
        echo "Users table already exists.\n";
    }

    // Create users_2fa if not exists
    $stmt = $dbh->query("SHOW TABLES LIKE 'users_2fa'");
    if ($stmt->rowCount() == 0) {
        $create2fa = "CREATE TABLE `users_2fa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `mechanism` tinyint unsigned NOT NULL,
  `seed` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` int unsigned NOT NULL,
  `expires_at` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_mechanism` (`user_id`,`mechanism`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $dbh->exec($create2fa);
        echo "Users_2fa table created.\n";
    }

    // Create users_audit_log if not exists
    $stmt = $dbh->query("SHOW TABLES LIKE 'users_audit_log'");
    if ($stmt->rowCount() == 0) {
        $createAudit = "CREATE TABLE `users_audit_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `event_at` int unsigned NOT NULL,
  `event_type` varchar(128) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `admin_id` int unsigned DEFAULT NULL,
  `ip_address` varchar(49) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details_json` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_at` (`event_at`),
  KEY `user_id_event_at` (`user_id`,`event_at`),
  KEY `user_id_event_type_event_at` (`user_id`,`event_type`,`event_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $dbh->exec($createAudit);
        echo "Users_audit_log table created.\n";
    }

} catch (Exception $e) {
    echo "Error with tables: " . $e->getMessage() . "\n";
}

// Now, migrate existing patients who don't have users
$patients = $dbh->query("SELECT number, email, username, password FROM tblpatient WHERE number NOT IN (SELECT id FROM users)");
foreach ($patients as $patient) {
    try {
        // Insert into users
        $stmt = $dbh->prepare("INSERT INTO users (id, email, password, username, verified, registered) VALUES (?, ?, ?, ?, 1, UNIX_TIMESTAMP(NOW()))");
        $stmt->execute([$patient['number'], $patient['email'], $patient['password'], $patient['username']]);
        echo "Migrated patient {$patient['number']}\n";
    } catch (Exception $e) {
        echo "Error migrating patient {$patient['number']}: " . $e->getMessage() . "\n";
    }
}

echo "Migration complete.\n";
?>