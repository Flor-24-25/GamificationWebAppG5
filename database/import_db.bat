@echo off
"C:\xampp\mysql\bin\mysql.exe" -u root < "%~dp0create_enhanced_db.sql"
pause