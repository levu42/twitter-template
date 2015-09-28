<?php
namespace Levu;

function ensure($arr, $keys) {
	if (!is_array($arr)) return false;
	foreach ($keys as $k) {
		if (!isset($arr[$k])) {
			return false;
		}
	}
	return true;
}

function send_json($data) {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($data);
}
