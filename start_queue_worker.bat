@echo off
echo Starting Laravel Queue Worker...
echo This window must stay open for reminders to work!
echo.

if not exist "artisan" (
    echo Error: artisan file not found. Please run this script from your Laravel root directory.
    pause
    exit /b 1
)

php artisan queue:work --daemon --tries=3 --timeout=60 --sleep=3 --max-jobs=1000
pause