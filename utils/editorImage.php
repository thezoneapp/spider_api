<?php
header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

$path = "../upload/bbs/images/";
$urlPath = "https://spiderplatform.co.kr/upload/bbs/images/";
$file = 'files';

$log_date = date("ymdHis");
$log_file = "/home/spiderfla/upload/log/insu/" . $log_date . ".log";

if (isset($_FILES)) {
	try {
		// 임시 파일을 upload폴더로 이동
		$fileName = $_FILES['upload']['name'];
		$fileTmp  = $_FILES['upload']['tmp_name'];
		$fileType = $_FILES['upload']['type'];
		$fileSize = $_FILES['upload']['size'];

		$saveFileName = $path . $fileName;

		if (file_exists($saveFileName)) {
			$i = 0;
			$true = true;
			$arrName = explode('.', $fileName);
			$name = $arrName[0];
			$ext  = $arrName[1];

			while ($true) {
				++$i;
				$fileName = $name . "_" . $i . "." . $ext;
				$saveFileName = $path . $fileName;
				$urlName = $urlPath . $fileName;

				if (!file_exists($saveFileName)) {
					$true = false;
				}
			}
		}

		if (!move_uploaded_file($fileTmp, $saveFileName)) {
			throw new RuntimeException($fileTmp . $file);
		}

		$response = array(
			'uploaded' => true,
			'url'      => $urlName
		);

		$json = stripslashes(json_encode($response)); // Remove the backslash
		echo $json;

	} catch (RuntimeException $e) {
		//error_log ($e, 3, "/home/spiderfla/upload/doc/debug.log");
	}

} else {
	error_log ("File is not selected", 3, "/home/spiderfla/upload/doc/debug.log");
}
?>