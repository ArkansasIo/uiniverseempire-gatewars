$ErrorActionPreference = 'Stop'

$workspace = 'c:\Users\Shadow\Downloads\uiniverseempire-gatewars-sgw-theme-publish\uiniverseempire-gatewars-sgw-theme-publish'
$zipUrl = 'https://github.com/ArkansasIo/OGameX---Arkansaslo/archive/refs/heads/main.zip'
$tempRoot = Join-Path $env:TEMP 'ogamex-upstream'
$zipPath = Join-Path $tempRoot 'repo.zip'
$extractDir = Join-Path $tempRoot 'extracted'

if (Test-Path $tempRoot) {
    Remove-Item $tempRoot -Recurse -Force
}
New-Item -ItemType Directory -Path $tempRoot -Force | Out-Null
New-Item -ItemType Directory -Path $extractDir -Force | Out-Null

Write-Host "Downloading upstream archive..."
Invoke-WebRequest -Uri $zipUrl -OutFile $zipPath -UseBasicParsing
Write-Host "Extracting upstream archive..."
Expand-Archive -Path $zipPath -DestinationPath $extractDir -Force

$repoRoot = $null
foreach ($candidate in Get-ChildItem -Path $extractDir -Directory | Sort-Object Name) {
    if (Test-Path (Join-Path $candidate.FullName 'README.md')) {
        $repoRoot = $candidate.FullName
        break
    }
}
if (-not $repoRoot) {
    $repoRoot = (Get-ChildItem -Path $extractDir -Directory | Select-Object -First 1).FullName
}

Write-Host "Using upstream root: $repoRoot"

$copied = New-Object System.Collections.Generic.List[string]

Get-ChildItem -Path $repoRoot -File -Recurse | ForEach-Object {
    $relativePath = $_.FullName.Substring($repoRoot.Length + 1)
    $destination = Join-Path $workspace $relativePath
    if (Test-Path $destination) {
        return
    }

    $destinationDir = Split-Path -Parent $destination
    if (-not (Test-Path $destinationDir)) {
        New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
    }

    Copy-Item -Path $_.FullName -Destination $destination -Force
    $copied.Add($relativePath)
}

$summaryPath = Join-Path $workspace 'upstream_sync_summary.txt'
Set-Content -Path $summaryPath -Value ($copied -join "`n") -Encoding UTF8
Write-Host "Copied $($copied.Count) new files"
if ($copied.Count -gt 0) {
    $copied | Select-Object -First 80 | ForEach-Object { Write-Host $_ }
    if ($copied.Count -gt 80) { Write-Host '...' }
}
Write-Host "Summary written to $summaryPath"
