ALTER TABLE schedules
    ADD COLUMN patient_id INT NULL AFTER category,
    ADD INDEX idx_schedules_category_patient (category, patient_id);

ALTER TABLE maternal_records
    ADD COLUMN schedule_id INT NULL AFTER mother_id,
    ADD INDEX idx_maternal_records_schedule (schedule_id);

UPDATE schedules AS s
JOIN (
    SELECT
        LOWER(TRIM(CONCAT_WS(' ', TRIM(client_fname), TRIM(client_lname)))) AS patient_name_key,
        MIN(id) AS patient_id,
        COUNT(*) AS matching_patients
    FROM maternal_registration
    WHERE status = 'Approved'
    GROUP BY LOWER(TRIM(CONCAT_WS(' ', TRIM(client_fname), TRIM(client_lname))))
) AS r
    ON s.category = 'Maternal'
    AND LOWER(TRIM(s.patient_name)) = r.patient_name_key
    AND r.matching_patients = 1
SET s.patient_id = r.patient_id
WHERE s.patient_id IS NULL;

UPDATE schedules AS s
JOIN (
    SELECT
        LOWER(TRIM(child_name)) AS patient_name_key,
        MIN(id) AS patient_id,
        COUNT(*) AS matching_patients
    FROM children
    GROUP BY LOWER(TRIM(child_name))
) AS c
    ON s.category = 'Child'
    AND LOWER(TRIM(s.patient_name)) = c.patient_name_key
    AND c.matching_patients = 1
SET s.patient_id = c.patient_id
WHERE s.patient_id IS NULL;
