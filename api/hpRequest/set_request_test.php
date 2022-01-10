<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/kakaoTalk.php";

$custHpNo = "010-2723-3377";
$custName = "박태수";
$memName = "마선빈";
$requestIdx = "1060";

			$custHpNo = preg_replace('/\D+/', '', $custHpNo);
			$receiptInfo = array(
				"custName"    => $custName,
				"memName"     => $memName,
				"idx"         => $requestIdx,
				"receiptHpNo" => $custHpNo,
			);
			sendTalk("HP_05_01_01", $receiptInfo);
?>