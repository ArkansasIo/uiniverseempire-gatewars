<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Stargate Wars contributors
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
$s = new Game();
$train = $s->getPersonnel($_SESSION['userid']);
?>
<form action="javascript:void(0)" name="untrain">
      <table width="100%" border="0">
	    <tr>
          <td colspan="3" align="center" valign="top">Untrain Units </td>
        </tr>
        <tr>
          <td align="left">&nbsp;</td>
          <td align="left">Cost</td>
          <td align="left">Quantity</td>
        </tr>
        <tr>
          <td align="left">Reassign Miners</td>
          <td align="left">0</td>
          <td align="left"><input type="text" name="resmin" value='0'/></td>
        </tr>
        <tr>
          <td align="left">Reassign <?= htmlspecialchars($train->attackName, ENT_QUOTES, 'UTF-8'); ?></td>
          <td align="left">0</td>
          <td align="left"><input type="text" name="resatk" value='0'/></td>
        </tr>
        <tr>
          <td align="left">Reassign <?= htmlspecialchars($train->defenseName, ENT_QUOTES, 'UTF-8'); ?></td>
          <td align="left">0</td>
          <td align="left"><input type="text" name="resdef" value='0' /></td>
        </tr>
        <tr>
          <td align="left">Reassign <?= htmlspecialchars($train->covertName, ENT_QUOTES, 'UTF-8'); ?></td>
          <td align="left">0</td>
          <td align="left"><input type="text" name="rescov" value='0' /></td>
        </tr>
        <tr>
          <td align="left">Reassign <?= htmlspecialchars($train->anticovertName, ENT_QUOTES, 'UTF-8'); ?></td>
          <td align="left">0</td>
          <td align="left"><input type="text" name="resanti" value='0' /></td>
        </tr>
        <tr>
          <td colspan="3" align="center"><input type="submit" name='unt' value='Reassign' onclick="this.value='Reassigning...'; this.disabled=true; sendData('train','post','untrn');"/></td>
        </tr>
  </table>
</form>