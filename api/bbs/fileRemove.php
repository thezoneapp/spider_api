<?php
	header("Access-Control-Allow-Origin:*");
	header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

	$path = "../../upload/bbs/images/";
	$fileName = $_POST['fileNames'];
	unlink($path . $fileName);

	// 저장 결과 반환
	$response = array(
		'result' => "0"
	);

	echo json_encode( $response );
?>