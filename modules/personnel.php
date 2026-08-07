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
$personnel = $s->getPersonnel($_SESSION['userid']);
?>
<table width="100%" border="0">
        <tr>
          <td colspan="2" align="center">Personnel</td>
        </tr>
        <tr>
          <td width="37%" align="left"><?= $personnel->attackName; ?></td>
          <td width="63%" align="right" valign="middle"><?= number_format($personnel->attackCount); ?></td>
        </tr>
        <tr>
          <td align="left"><?= $personnel->superAttackName; ?></td>
          <td align="right" valign="middle"><?= number_format($personnel->superAttackCount); ?></td>
        </tr>
        <tr>
          <td align="left"><?= $personnel->attackMercName ?></td>
          <td align="right" valign="middle"><?= number_format($personnel->attackMercCount); ?></td>
        </tr>
        <tr>
          <td align="left"><?= $personnel->defenseName; ?> </td>
          <td align="right" valign="middle"><?= number_format($personnel->defenseCount); ?></td>
        </tr>
        <tr>
          <td align="left"><?= $personnel->superDefenseName; ?> </td>
          <td align="right" valign="middle"><?= number_format($personnel->superDefenseCount); ?></td>
        </tr>
        <tr>
          <td align="left"><?= $personnel->defenseMercName; ?></td>
          <td align="right" valign="middle"><?= number_format($personnel->defenseMercCount); ?></td>
        </tr>
        <tr>
          <td align="left">Untrained</td>
          <td align="right" valign="middle"><?= number_format($personnel->uuCount); ?></td>
        </tr>
        <tr>
          <td align="left">Miners (Lifers) </td>
          <td align="right" valign="middle"><?php $x = $personnel->minerCount + $personnel->liferCount; print number_format($x); ?>( <?= number_format($personnel->liferCount); ?> )</td>
        </tr>
        <tr>
          <td align="left"><?= $personnel->covertName; ?></td>
          <td align="right" valign="middle"><?= number_format($personnel->covertCount); ?></td>
        </tr>
        <tr>
          <td align="left"><?= $personnel->superCovertName; ?></td>
          <td align="right" valign="middle"><?= number_format($personnel->superCovertCount); ?></td>
        </tr>
        <tr>
          <td align="left"><?= $personnel->anticovertName; ?></td>
          <td align="right" valign="middle"><?= number_format($personnel->anticovertCount); ?></td>
        </tr>
        <tr>
          <td align="left"><?= $personnel->superAnticovertName; ?></td>
          <td align="right" valign="middle"><?= number_format($personnel->superAnticovertCount); ?></td>
        </tr>
        <tr>
          <td>Total</td>
          <td align="right" valign="middle"><?= number_format($personnel->ttlarmysize); ?></td>
        </tr>
      </table>