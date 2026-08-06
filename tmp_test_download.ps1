# MIT License
#
# Copyright (c) 2026 Stargate Wars contributors
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.
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
