<?php
header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

/******************************************************************************
* ����Ÿ ���̽� ����
******************************************************************************/
$db_host = "localhost";
$db_user = "spiderfla";
$db_pass = "dlfvkf#$12";
$db_name = "spiderfla";

$connect = @mysqli_connect($db_host, $db_user, $db_pass, $db_name) or die("Failed to connect to MySQL: " . mysqli_error()); 
//@mysqli_select_db($db_name, $connect) or error("DB Select ������ �߻��߽��ϴ�");
?>