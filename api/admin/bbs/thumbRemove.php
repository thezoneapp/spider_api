<?php
	header("Access-Control-Allow-Origin:*");
	header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

	$path = "../../upload/bbs/thumbnail/";
	$fileName = $_POST['fileNames'];
	unlink($path . $fileName);

	// ���� ��� ��ȯ
	$response = array(
		'result' => "0"
	);

	echo json_encode( $response );
?>