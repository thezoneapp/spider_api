<?php
// ---------------------------------------------------------------------------
//                              CHXImage
//
// �� �ڵ�� ���� ���ؼ� �����˴ϴ�.
// ȯ�濡 �°� ���� �Ǵ� �����Ͽ� ����� �ֽʽÿ�.
//
// ---------------------------------------------------------------------------

require_once("config.php");

//----------------------------------------------------------------------------
//
//
$tempfile = $_FILES['file']['tmp_name'];
$filename = $_FILES['file']['name'];

// ���� ���� �̸�
// ����Ͻú���_����ũ��_��������4��.Ȯ����
// 20140327125959_1234_abcd.jpg
$savefile = SAVE_DIR . '/' . $_FILES['file']['name'];

// �����PC�� ���� �̸�: $_POST["origName"]
// �����PC�� ���� ���: $_POST["filePath"]
// �����PC�� ���� ũ��: $_POST["filesize"]

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
