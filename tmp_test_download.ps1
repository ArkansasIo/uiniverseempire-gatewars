$ErrorActionPreference = 'Stop'
$workspace = 'c:\Users\Shadow\Downloads\uiniverseempire-gatewars-sgw-theme-publish\uiniverseempire-gatewars-sgw-theme-publish'
Set-Location $workspace
$url = 'https://github.com/ArkansasIo/OGameX---Arkansaslo/archive/refs/heads/main.zip'
$tempRoot = Join-Path $env:TEMP 'ogamex-upstream'
$zipPath = Join-Path $tempRoot 'repo.zip'
$extractDir = Join-Path $tempRoot 'extracted'
New-Item -ItemType Directory -Path $tempRoot -Force | Out-Null
New-Item -ItemType Directory -Path $extractDir -Force | Out-Null
Write-Host "Downloading $url"
Invoke-WebRequest -Uri $url -OutFile $zipPath -UseBasicParsing -Headers @{ 'User-Agent'='Mozilla/5.0' }
Write-Host "Downloaded $(Get-Item $zipPath).Length bytes"
Expand-Archive -Path $zipPath -DestinationPath $extractDir -Force
Write-Host "Extracted to $extractDir"
Get-ChildItem $extractDir | Select-Object Name,FullName,PSIsContainer | Format-Table -AutoSize | Out-String | Write-Host
