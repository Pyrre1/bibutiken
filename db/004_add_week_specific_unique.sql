ALTER TABLE hours_plans
    ADD UNIQUE KEY uq_type_week_year (type, week_number, year);