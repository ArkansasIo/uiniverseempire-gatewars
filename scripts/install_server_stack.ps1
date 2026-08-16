<#
.SYNOPSIS
    Universe Civilization: Empire at Wars - Server Stack Installation Assistant
.DESCRIPTION
    Provides automated checks and instructions for setting up Apache, PHP, MySQL, and phpMyAdmin on Windows environments.
#>

Write-Host "========================================================" -ForegroundColor Cyan
Write-Host " Server Stack Installation Assistant (Apache / PHP / MySQL)" -ForegroundColor Cyan
Write-Host "========================================================" -ForegroundColor Cyan

# Check PHP
$phpVersion = & php -v 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] PHP is installed: $($phpVersion[0])" -ForegroundColor Green
} else {
    Write-Host "[!] PHP is not detected in PATH. Please install PHP 8.x with mysqli and mbstring extensions enabled." -ForegroundColor Yellow
}

# Check MySQL
$mysqlVersion = & mysql --version 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] MySQL/MariaDB client is available: $mysqlVersion" -ForegroundColor Green
} else {
    Write-Host "[!] MySQL client not detected in PATH. Ensure MySQL or MariaDB is installed." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "--- Recommended Setup Steps for Production ---" -ForegroundColor Cyan
Write-Host "1. Apache HTTP Server:" -ForegroundColor White
p
    Write-Host "   - Download Apache Lounge (httpd) or use XAMPP." -ForegroundColor Gray
    Write-Host "   - Enable mod_rewrite and configure DocumentRoot to point to this repository root." -ForegroundColor Gray
Write-Host "2. PHP 8.x:" -ForegroundColor White
    Write-Host "   - Ensure php.ini has extension=mysqli enabled and extension_dir set." -ForegroundColor Gray
Write-Host "3. MySQL / MariaDB:" -ForegroundColor White
    Write-Host "   - Execute database/sql/00_fresh_master_install.sql to set up database and tables." -ForegroundColor Gray
Write-Host "4. phpMyAdmin:" -ForegroundColor White
    Write-Host "   - Extract phpMyAdmin into Apache htdocs or configure virtual host for database management." -ForegroundColor Gray
Write-Host ""
Write-Host "To start the development server instantly, run: .\scripts\start_server.bat" -ForegroundColor Green
