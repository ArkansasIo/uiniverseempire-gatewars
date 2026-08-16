<#
.SYNOPSIS
    Universe Civilization: Empire at Wars - Complete Stack Setup & Game Package Tool
.DESCRIPTION
    Automates PHP verification, built-in server startup, database initialization scripts, and game packaging.
#>

param(
    [string]$Action = "start"
)

Write-Host "========================================================" -ForegroundColor Cyan
Write-Host " Universe Civilization: Empire at Wars - Setup System" -ForegroundColor Cyan
Write-Host "========================================================" -ForegroundColor Cyan

switch ($Action.ToLower()) {
    "start" {
        Write-Host "[+] Starting PHP built-in game server on http://localhost:8080..." -ForegroundColor Green
        Stop-Process -Name "php" -ErrorAction SilentlyContinue
        Start-Sleep -Seconds 1
        Start-Process php -ArgumentList "-S", "localhost:8080" -WorkingDirectory $PSScriptRoot\..
        Write-Host "[+] Server started successfully." -ForegroundColor Green
        Write-Host "[+] Access the game at: http://localhost:8080/index.php" -ForegroundColor Yellow
    }
    "test" {
        Write-Host "[+] Running PHP syntax linter on all codebase files..." -ForegroundColor Green
        $files = Get-ChildItem -Recurse -Filter "*.php"
        foreach ($f in $files) {
            $res = & php -l $f.FullName 2>&1
            if ($LASTEXITCODE -ne 0) {
                Write-Host "Syntax error in $($f.FullName): $res" -ForegroundColor Red
            }
        }
        Write-Host "[+] Running all unit regression tests..." -ForegroundColor Green
        php tests/game_logic_pure_test.php
        php tests/game_tick_pure_test.php
        php tests/research_tree_test.php
        php tests/admin_panel_test.php
        php tests/colony_grid_test.php
        php tests/artillery_catalog_test.php
        Write-Host "[+] All test suites completed." -ForegroundColor Green
    }
    "package" {
        Write-Host "[+] Packaging game release archive..." -ForegroundColor Green
        $dest = "$PSScriptRoot\..\dist"
        if (!(Test-Path $dest)) { New-Item -ItemType Directory -Path $dest | Out-Null }
        $archive = "$dest\empire-at-wars-v1.5.0.zip"
        if (Test-Path $archive) { Remove-Item $archive }
        Compress-Archive -Path "$PSScriptRoot\..\base", "$PSScriptRoot\..\modules", "$PSScriptRoot\..\templates", "$PSScriptRoot\..\css", "$PSScriptRoot\..\js", "$PSScriptRoot\..\images", "$PSScriptRoot\..\database", "$PSScriptRoot\..\index.php", "$PSScriptRoot\..\config.php" -DestinationPath $archive -Force
        Write-Host "[+] Package created at: $archive" -ForegroundColor Green
    }
    default {
        Write-Host "Usage: .\setup_stack.ps1 [start|test|package]" -ForegroundColor Yellow
    }
}
