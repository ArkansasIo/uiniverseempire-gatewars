<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Universe Civilization : Empire at wars
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
$s->updatePower($_SESSION['userid']);
$mil = $s->getRanks();
?>
<table width="100%" border="0">
      <tr>
        <td colspan="4" align="center">Military Effectiveness</td>
        </tr>
      <tr>
        <td align="left">Attack</td>
        <td align="right"><?= number_format($mil->milAtk); ?></td>
        <td align="right" valign="top">Rank:</td>
        <td align="left"><?= number_format($mil->milAtkRank); ?></td>
      </tr>
      <tr>
        <td align="left">Defense</td>
        <td align="right"><?= number_format($mil->milDef); ?></td>
        <td align="right" valign="top">Rank:</td>
        <td align="left"><?= number_format($mil->milDefRank); ?></td>
      </tr>
      <tr>
        <td align="left">Covert</td>
        <td align="right"><?= number_format($mil->milCov); ?></td>
        <td align="right" valign="top">Rank:</td>
        <td align="left"><?= number_format($mil->milCovRank); ?></td>
      </tr>
      <tr>
        <td align="left">AntiCovert</td>
        <td align="right"><?= number_format($mil->milAnti); ?></td>
        <td align="right" valign="top">Rank:</td>
        <td align="left"><?= number_format($mil->milAntiRank); ?></td>
      </tr>
      <tr>
        <td align="left">Total</td>
        <td align="right"><?= number_format($mil->mil); ?></td>
        <td align="right" valign="top">Rank:</td>
        <td align="left"><?= number_format($mil->milRank); ?></td>
      </tr>
    </table><br />
<?php /*
      <table width="100%" border="0">
        <tr>
          <td colspan="4" align="center">Fleet Effectiveness </td>
        </tr>
        <tr>
          <td align="left" valign="top">Attack</td>
          <td align="right" valign="top"><?= number_format($mil->fltAtk); ?></td>
          <td align="right" valign="bottom">Rank:</td>
          <td align="left" valign="bottom"><?= number_format($mil->fltAtkRank); ?></td>
        </tr>
        <tr>
          <td align="left" valign="top">Defense</td>
          <td align="right" valign="top"><?= number_format($mil->fltDef); ?></td>
          <td align="right" valign="bottom">Rank:</td>
          <td align="left" valign="bottom"><?= number_format($mil->fltDefRank); ?></td>
        </tr>
        <tr>
          <td align="left" valign="top">Total</td>
          <td align="right" valign="top"><?= number_format($mil->flt); ?></td>
          <td align="right" valign="bottom">Rank:</td>
          <td align="left" valign="bottom"><?= number_format($mil->fltRank); ?></td>
        </tr> 
      </table>*/ ?>