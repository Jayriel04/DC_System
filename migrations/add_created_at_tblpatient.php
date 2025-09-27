<?php
/**
 * Migration script to add created_at to tblpatient and backfill from tblappointment.
 * Usage: run from project root with PHP CLI: php migrations/add_created_at_tblpatient.php
 */

require_once __DIR__ . "/../includes/dbconnection.php";

try {
    // 1) Check if column exists
    $stmt = $dbh->prepare("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'tblpatient' AND COLUMN_NAME = 'created_at'");
    $stmt->execute([':db' => DB_NAME]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && intval($row['cnt']) > 0) {
        echo "Column tblpatient.created_at already exists.\n";
    } else {
        echo "Adding column tblpatient.created_at...\n";
        $dbh->exec("ALTER TABLE `tblpatient` ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT NULL");
        echo "Column added.\n";
    }

    // 2) Backfill using earliest appointment created_at per patient
    echo "Backfilling tblpatient.created_at from tblappointment (first appointment per patient)...\n";
    $updateSql = "UPDATE `tblpatient` p
JOIN (
  SELECT `patient_number`, MIN(`created_at`) AS first_created
  FROM `tblappointment`
  WHERE `patient_number` IS NOT NULL
  GROUP BY `patient_number`
) a ON p.`number` = a.`patient_number`
SET p.`created_at` = a.`first_created`
WHERE p.`created_at` IS NULL";
    $affected = $dbh->exec($updateSql);
    echo "Backfill complete. Rows updated: " . ($affected === false ? '0 or error' : $affected) . "\n";

    echo "Migration finished. Please verify data and, if desired, alter the column to NOT NULL.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

?>