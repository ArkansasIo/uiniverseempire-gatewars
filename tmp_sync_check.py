import pathlib, tempfile, zipfile
root = pathlib.Path(tempfile.gettempdir()) / 'ogamex-upstream'
zip_path = root / 'repo.zip'
extract_dir = root / 'extracted'
print('zip_exists', zip_path.exists())
if zip_path.exists():
    print('zip_size', zip_path.stat().st_size)
    try:
        with zipfile.ZipFile(zip_path) as z:
            names = z.namelist()[:10]
            print('names', names)
            print('count', len(z.namelist()))
    except Exception as exc:
        print('zip_error', repr(exc))
print('extract_exists', extract_dir.exists())
if extract_dir.exists():
    print('extract_children', [p.name for p in extract_dir.iterdir()][:10])
