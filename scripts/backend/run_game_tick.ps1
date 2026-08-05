$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Resolve-Path (Join-Path $scriptDir '..\..')
$logDir = Join-Path $projectRoot 'exports'
if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir -Force | Out-Null
}

$logPath = Join-Path $logDir 'game_tick.log'
$phpCommand = Get-Command php -ErrorAction SilentlyContinue
if (-not $phpCommand) {
    throw 'php was not found in PATH'
}

& $phpCommand.Source (Join-Path $projectRoot 'scripts\backend\game_tick.php') *>> $logPath
