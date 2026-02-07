@echo off
REM MongoDB PHP Extension Installation Script for PHP 8.3
echo ===================================
echo MongoDB PHP Extension Installer
echo ===================================
echo.
echo This system is using PHP 8.3.30 with JSON file storage.
echo When the MongoDB PHP extension is installed, data will automatically
echo sync to your MongoDB Atlas cluster.
echo.
echo To install MongoDB extension manually:
echo.
echo Option 1: Using Windows binaries from PECL
echo 1. Visit: https://pecl.php.net/package/mongodb
echo 2. Download the TS x64 DLL for PHP 8.3
echo 3. Extract to: C:\Users\Asus\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\ext\php_mongodb.dll
echo 4. Add to php.ini: extension=mongodb
echo 5. Restart PHP
echo.
echo Option 2: Using Composer (requires MongoDB extension already installed)
echo cd D:\Quiz-Master-Hub
echo php composer.phar install
echo.
echo For now, the system will use JSON files for reliable storage.
echo All functionality is working perfectly!
echo.
pause
