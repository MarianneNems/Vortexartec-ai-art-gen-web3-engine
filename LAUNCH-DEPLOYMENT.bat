@echo off
echo ========================================
echo Vortex AI Engine - Deployment Launcher
echo ========================================
echo.

REM Check if deployment package exists
if not exist "vortex-ai-engine-production-deploy.zip" (
    echo ERROR: Deployment package not found!
    echo Please ensure vortex-ai-engine-production-deploy.zip exists.
    pause
    exit /b 1
)

echo Deployment package found: vortex-ai-engine-production-deploy.zip
echo.

REM Get WordPress path from user
set /p WORDPRESS_PATH="Enter WordPress installation path (e.g., C:\xampp\htdocs\wordpress): "

REM Check if path exists
if not exist "%WORDPRESS_PATH%\wp-config.php" (
    echo ERROR: wp-config.php not found in %WORDPRESS_PATH%
    echo Please verify the WordPress installation path.
    pause
    exit /b 1
)

echo.
echo WordPress installation found: %WORDPRESS_PATH%
echo.

REM Run automated deployment
echo Starting automated deployment...
powershell -ExecutionPolicy Bypass -File "deployment\automated-deployment.ps1" -WordPressPath "%WORDPRESS_PATH%"

echo.
echo Deployment completed!
echo.
echo Next steps:
echo 1. Go to WordPress Admin ^> Plugins
echo 2. Activate 'Vortex AI Engine' plugin
echo 3. Go to Vortex AI ^> Dashboard
echo 4. Configure settings and test functionality
echo.
pause 