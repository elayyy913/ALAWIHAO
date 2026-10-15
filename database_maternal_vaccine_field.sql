ALTER TABLE maternal_records
    ADD COLUMN vaccine_given VARCHAR(255) NULL AFTER tetanus_vaccine_date;
