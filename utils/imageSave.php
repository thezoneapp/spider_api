<?php
header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

$path = "../upload/thumbnail/";
$file = 'files';

if (isset($_FILES)) {
	try {
		// Check $_FILES value.
		switch ($_FILES[$file]['error']) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
				throw new RuntimeException('No file sent.');
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				throw new RuntimeException('Exceeded filesize limit.');
			default:
				throw new RuntimeException('Unknown errors.');
		}

		// �ӽ� ������ upload������ �̵�
		$fileName = $_FILES[$file]['name'];
		$fileTmp  = $_FILES[$file]['tmp_name'];
		$fileType = $_FILES[$file]['type'];
		$fileSize = $_FILES[$file]['size'];

		$arrName = explode('.', $fileName);
		$name = $arrName[0];
		$ext  = $arrName[1];

		$fileName = date("ymdHis") . "." . $ext;
		$saveFileName = $path . $fileName;

		//$log_date = date("ymdHis");
		//$log_file = "/home/spiderfla/upload/log/" . $log_date . ".log";
		//error_log ($saveFileName, 3, $log_file);

		//if (file_exists($saveFileName)) {
		//	$i = 0;
		//	$true = true;
		//	$arrName = explode('.', $fileName);
		//	$name = $arrName[0];
		//	$ext  = $arrName[1];

		//	while ($true) {
		//		++$i;
		//		$fileName = $name . "_" . $i . "." . $ext;
		//		$saveFileName = $path . $fileName;

		//		if (!file_exists($saveFileName)) $true = false;
		//	}
		//}

		if (!move_uploaded_file($fileTmp, $saveFileName)) {
			throw new RuntimeException($fileTmp . $file);
		}

	} catch (RuntimeException $e) {
		//error_log ($e, 3, "/home/spiderfla/upload/doc/debug.log");
	}

	// ���� ��� ��ȯ
	$response = array(
		'saveName' => $fileName
	);

	echo json_encode( $response );

} else {
	error_log ("File is not selected", 3, "/home/spiderfla/upload/doc/debug.log");
}
?>