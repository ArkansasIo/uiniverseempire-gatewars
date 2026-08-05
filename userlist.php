<?php
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
