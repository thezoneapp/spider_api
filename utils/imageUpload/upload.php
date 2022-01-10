<?php
// ---------------------------------------------------------------------------
//                              CHXImage
//
// 이 코드는 데모를 위해서 제공됩니다.
// 환경에 맞게 수정 또는 참고하여 사용해 주십시오.
//
// ---------------------------------------------------------------------------

require_once("config.php");

//----------------------------------------------------------------------------
//
//
$tempfile = $_FILES['file']['tmp_name'];
$filename = $_FILES['file']['name'];

// 저장 파일 이름
// 년월일시분초_파일크기_랜덤문자4자.확장자
// 20140327125959_1234_abcd.jpg
$savefile = SAVE_DIR . '/' . $_FILES['file']['name'];

// 사용자PC의 파일 이름: $_POST["origName"]
// 사용자PC의 파일 경로: $_POST["filePath"]
// 사용자PC의 파일 크기: $_POST["filesize"]

move_uploaded_file($tempfile, $savefile);
$imgsize = getimagesize($savefile);
$filesize = filesize($savefile);

if (!$imgsize) {
	$filesize = 0;
	$random_name = '-ERR';
	unlink($savefile);
};

$rdata = sprintf('{"fileUrl": "%s/%s", "filePath": "%s", "fileName": "%s", "fileSize": "%d" }',
	SAVE_URL,
	$filename,
	$savefile,
	$filename,
	$filesize );

echo $rdata;
?>
