-- Migration: add created_at to tblpatient and backfill from tblappointment
-- Generated: 2025-09-27

-- 1) Add column if it doesn't exist (run once)
ALTER TABLE `tblpatient`
  ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT NULL;

-- 2) Backfill created_at from tblappointment (first seen per patient_number)
UPDATE `tblpatient` p
JOIN (
  SELECT `patient_number`, MIN(`created_at`) AS first_created
  FROM `tblappointment`
  WHERE `patient_number` IS NOT NULL
  GROUP BY `patient_number`
) a ON p.`number` = a.`patient_number`
SET p.`created_at` = a.`first_created`
WHERE p.`created_at` IS NULL;

-- Notes:
-- * This migration leaves the column nullable so it is safe for environments where some patients
--   may not have any appointment records. If you'd like to make it NOT NULL with a default
--   CURRENT_TIMESTAMP, run an ALTER TABLE afterwards after verifying backfill.
