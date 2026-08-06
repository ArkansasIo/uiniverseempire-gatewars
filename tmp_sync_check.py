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
