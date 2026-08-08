<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Stephen, Universe Civilization : Empire at wars
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
include_once("../config.php");
$progressValue = isset($_SESSION["progress"]) ? (int)$_SESSION["progress"] : 0;
if ($progressValue < 0) {
    $progressValue = 0;
}
if ($progressValue > 200) {
    $progressValue = 200;
}
?>
<center><table width="510" border="0">
  <tr>
    <td align="center">Enlightenment Progress:<br /> <font>This Shows You Your Current Progress When you reach on of the markers this shows you can ascend to the next level with the ascend button. </td>
  </tr>
  <tr>
    <td align="center"><img src="progress/prog.gif" width="480" height="50"><img src="modules/progress.php?prog=<?= $progressValue; ?>" width="480" height="11" /> </td><td align="left" valign="bottom"><font><?= (float)($progressValue/2)."%"; ?></font></td>
  </tr>
</table>
</center>
