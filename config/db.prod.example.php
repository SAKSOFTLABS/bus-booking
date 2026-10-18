<?php
/**
 * Production Database Configuration for ServerByte
 * 
 * Instructions:
 * 1. On your ServerByte server, copy this file as 'db.prod.php' inside the 'config' folder.
 * 2. Fill in the MySQL database credentials created in ServerByte cPanel / MySQL management.
 * 3. This file ('db.prod.php') is ignored by Git so your live credentials will NEVER be overwritten when syncing code!
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'your_serverbyte_cpanel_user');
define('DB_PASS', 'your_strong_database_password');
define('DB_NAME', 'your_serverbyte_database_name');
