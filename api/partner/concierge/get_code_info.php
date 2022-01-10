<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 컨시어지 > 회원가입 > 코드정보
	*/

	$response = array(
		'result'      => "0",
		'bankOptions' => $arrBankCode,
		'cardOptions' => $arrCardCode,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>