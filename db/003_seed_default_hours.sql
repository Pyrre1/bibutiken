INSERT INTO hours_plans (type, header_text, free_text_1, free_text_2)
VALUES ('default', 'Opening Hours', NULL, NULL);

SET @default_plan_id = LAST_INSERT_ID();

INSERT INTO hours_plan_days (plan_id, day_of_week, open_time, close_time, closed) VALUES
(@default_plan_id, 1, '16:00:00', '18:00:00', 0),
(@default_plan_id, 2, '16:00:00', '18:00:00', 0),
(@default_plan_id, 3, '16:00:00', '18:00:00', 0),
(@default_plan_id, 4, '16:00:00', '18:00:00', 0),
(@default_plan_id, 5, '16:00:00', '18:00:00', 0),
(@default_plan_id, 6, NULL, NULL, 1),
(@default_plan_id, 7, NULL, NULL, 1);