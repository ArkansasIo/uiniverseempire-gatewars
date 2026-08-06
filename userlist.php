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
include("config.php");
$s = new Game();
header('Content-Type: application/json; charset=utf-8');

$results = [];
if (!empty($_GET['val']) && $s && $s->connected() && $s->db_link) {
	$query = "SELECT users.uname, userdata.uid, race.r_name as race, rank.overall as rank
	          FROM users, race, userdata, rank
	          WHERE uname LIKE ?
	          AND userdata.uid = users.uid
	          AND race.rid = userdata.rid
	          AND rank.uid = userdata.uid
	          ORDER BY rank
	          LIMIT 10";
	$stmt = $s->db_link->prepare($query);
	if ($stmt) {
		$searchVal = $_GET['val'] . '%';
		$stmt->bind_param("s", $searchVal);
		$stmt->execute();
		$q = $stmt->get_result();
		if ($q) {
			while ($data = $q->fetch_object()) {
				$results[] = [$data->uname, $data->race, $data->rank, $data->uid];
			}
		}
	}
}
echo json_encode(['result' => $results]);
?>
