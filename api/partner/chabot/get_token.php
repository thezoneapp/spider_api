<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* �ֵ��ν� > ȸ������ ����
	* parameter ==> memId: ���̵�
	*/
	$remoteIp = $_SERVER['REMOTE_ADDR'];
	$headers = getallheaders();
	$input_data = json_decode(file_get_contents('php://input'), true); 
	//$input_data = $_REQUEST; 


	echo var_dump($remoteIp);
?>