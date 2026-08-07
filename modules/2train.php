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
$train = $s->getPersonnel($_SESSION['userid']);

?><form action="javascript:void(0)" name="2train">
	<table width="100%" border="0">
      <tr>
        <td colspan="3" align="center" valign="top">Training Program </td>
      </tr>
      <tr>
        <td align="left" valign="top">Training</td>
        <td align="left" valign="top">Cost</td>
        <td align="left" valign="top">Quanity</td>
      </tr>
      <tr>
        <td align="left" valign="top">Miners</td>
        <td align="left" valign="top">1,500</td>
        <td align="left" valign="top"><input type='text' name="miners" value='0'/></td>
      </tr>
      <tr>
        <td align="left" valign="top"><?= $train->attackName; ?></td>
        <td align="left" valign="top"><?= number_format($train->attackCost); ?></td>
        <td align="left" valign="top"><input type='text' name="atk" value='0'/></td>
      </tr>
      <tr>
        <td align="left" valign="top"><?= $train->superAttackName; ?></td>
        <td align="left" valign="top"><?= number_format($train->superAttackCost); ?></td>
        <td align="left" valign="top"><input type='text' name="uberAtk"value='0' /></td>
      </tr>
      <tr>
        <td align="left" valign="top"><?= $train->defenseName; ?></td>
        <td align="left" valign="top"><?= number_format($train->defenseCost); ?></td>
        <td align="left" valign="top"><input type='text' name="def" value='0'/></td>
      </tr>
      <tr>
        <td align="left" valign="top"><?= $train->superDefenseName; ?></td>
        <td align="left" valign="top"><?= number_format($train->superDefenseCost); ?></td>
        <td align="left" valign="top"><input type='text' name="uberDef" value='0'/></td>
      </tr>
      <tr>
        <td align="left" valign="top"><?= $train->covertName; ?></td>
        <td align="left" valign="top"><?= number_format($train->covertCost); ?></td>
        <td align="left" valign="top"><input type='text' name="cov"value='0' /></td>
      </tr>
      <tr>
        <td align="left" valign="top"><?= $train->superCovertName; ?></td>
        <td align="left" valign="top"><?= number_format($train->superCovertCost); ?></td>
        <td align="left" valign="top"><input type='text' name="uberCov"value='0' /></td>
      </tr>
      <tr>
        <td align="left" valign="top"><?= $train->anticovertName; ?></td>
        <td align="left" valign="top"><?= number_format($train->anticovertCost); ?></td>
        <td align="left" valign="top"><input name="anti" type='text' value="0" /></td>
      </tr>
      <tr>
        <td align="left" valign="top"><?= $train->superAnticovertName; ?></td>
        <td align="left" valign="top"><?= number_format($train->superAnticovertCost); ?></td>
        <td align="left" valign="top"><input type='text' name="uberAnti"value='0' /></td>
      </tr>
      <tr>
        <td colspan="3" align="center" valign="top"><input type="submit" name='trn' value='Train' onclick="this.value='Training...'; this.disabled=true; sendData('train','post','trn');" /></td>
      </tr>
  </table>
	</form>
