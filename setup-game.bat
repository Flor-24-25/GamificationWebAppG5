@echo off
REM Gamification Setup Script for Windows

echo ================================
echo Gamification System Setup
echo ================================
echo.

REM Check if .env exists
if exist .env (
    echo ^✓ .env file already exists
) else (
    if exist .env.example (
        copy .env.example .env
        echo ^✓ Created .env file
        echo ^⚠ Please edit .env and add your OpenAI API key
    )
)

echo.
echo ================================
echo Setup Complete!
echo ================================
echo.
echo Next steps:
echo 1. Make sure XAMPP is running
echo 2. Open phpMyAdmin: http://localhost/phpmyadmin
echo 3. Go to 'login' database
echo 4. Click Import
echo 5. Select: database\gamification_tables.sql
echo 6. Click Import
echo 7. Login to your account
echo 8. Navigate to: http://localhost/login-form-with-database-connection-main/public/dashboard.php
echo 9. Click 'Play Game' to start
echo.
echo For OpenAI hints:
echo 10. Edit .env and add your OpenAI API key
echo 11. Uncomment the OpenAI initialization in game.php
echo.
pause
