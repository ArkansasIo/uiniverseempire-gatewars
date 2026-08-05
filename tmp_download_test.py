import pathlib, tempfile, urllib.request, zipfile, os, sys
root = pathlib.Path(tempfile.gettempdir()) / 'ogamex-upstream'
root.mkdir(parents=True, exist_ok=True)
zip_path = root / 'repo.zip'
extract_dir = root / 'extracted'
url = 'https://github.com/ArkansasIo/OGameX---Arkansaslo/archive/refs/heads/main.zip'
print('downloading', url)
req = urllib.request.Request(url, headers={'User-Agent':'Mozilla/5.0'})
urllib.request.urlretrieve(req, zip_path)
print('downloaded', zip_path, 'size', zip_path.stat().st_size)
with zipfile.ZipFile(zip_path) as z:
    print('entries', len(z.namelist()))
    print('first', z.namelist()[:5])
