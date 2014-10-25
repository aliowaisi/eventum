INSERT INTO {{%reminder_field}} VALUES (null, 'Group', 'iss_grp_id', 'iss_grp_id', 0);
INSERT INTO {{%reminder_field}} SET rmf_title = 'Active Group', rmf_sql_field = 'iss_grp_id', rmf_sql_representation = '';
INSERT INTO {{%reminder_field}} SET rmf_title = 'Expected Resolution Date', rmf_sql_field = 'iss_expected_resolution_date', rmf_sql_representation = '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_expected_resolution_date), 0))', rmf_allow_column_compare = 1;
