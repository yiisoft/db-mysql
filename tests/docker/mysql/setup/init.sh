mysql -pyii -e 'CREATE DATABASE `yiitest`;';
mysql -pyii -e "SET GLOBAL sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';";
mysql -pyii -e "CREATE USER 'travis'@'%' IDENTIFIED WITH mysql_native_password;";
mysql -pyii -e "GRANT ALL PRIVILEGES ON *.* TO 'travis'@'%' WITH GRANT OPTION;";
