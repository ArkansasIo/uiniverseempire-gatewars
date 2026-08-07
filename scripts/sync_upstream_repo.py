# MIT License
#
# Copyright (c) 2026 Universe Civilization : Empire at wars
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
import os
import shutil
import tempfile
import urllib.request
import zipfile
from pathlib import Path

workspace = Path(r'c:\Users\Shadow\Downloads\uiniverseempire-gatewars-sgw-theme-publish\uiniverseempire-gatewars-sgw-theme-publish')
url = 'https://github.com/ArkansasIo/OGameX---Arkansaslo/archive/refs/heads/main.zip'

if not workspace.exists():
    raise SystemExit(f'Workspace not found: {workspace}')

root = Path(tempfile.gettempdir()) / 'ogamex-upstream'
zip_path = root / 'repo.zip'
extract_dir = root / 'extracted'
root.mkdir(parents=True, exist_ok=True)
if extract_dir.exists():
    shutil.rmtree(extract_dir)
extract_dir.mkdir(parents=True, exist_ok=True)

print(f'Downloading {url}')
urllib.request.urlretrieve(url, zip_path)
print(f'Extracting to {extract_dir}')
with zipfile.ZipFile(zip_path) as z:
    z.extractall(extract_dir)

repo_root = None
for p in sorted(extract_dir.iterdir()):
    if p.is_dir() and (p / 'README.md').exists():
        repo_root = p
        break
if repo_root is None:
    for p in sorted(extract_dir.iterdir()):
        if p.is_dir():
            repo_root = p
            break
if repo_root is None:
    raise SystemExit('No repository root found in extracted archive')

copied = []
for src_path in repo_root.rglob('*'):
    if not src_path.is_file():
        continue
    rel = src_path.relative_to(repo_root)
    dest = workspace / rel
    if dest.exists():
        continue
    dest.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(src_path, dest)
    copied.append(str(rel))

summary_path = workspace / 'upstream_sync_summary.txt'
summary_path.write_text('\n'.join(copied), encoding='utf-8')
print(f'Copied {len(copied)} new files')
for item in copied[:80]:
    print(item)
if len(copied) > 80:
    print('...')
print(f'Summary written to {summary_path}')
