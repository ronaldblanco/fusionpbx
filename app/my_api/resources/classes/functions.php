<?php
function send_api_answer($code, $message, $data = False) {
	$response = array(
		'status' => $code,
		'message' => $message,
	);
	if ($data) {
		$response['data'] = $data;
	}
	$response = json_encode($response, JSON_UNESCAPED_SLASHES);
	echo $response;
	return;
}
function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}
?>
