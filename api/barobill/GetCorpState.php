<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/barobill.php";
	include "./BaroService_CORPSTATE.php";

	/*
	* 바로빌 > 사업자번호 유효성체크
	* parameter :
		corpNum:    바로빌 회원 사업자번호 ('-' 제외, 10자리)
	*/

	$data_back = json_decode(file_get_contents('php://input'));
	$corpNum   = $data_back->{'corpNum'};

	//$corpNum = '1168118750';

	$corpNum = str_replace("-", "", $corpNum);

	// 바로빌 > 사업자번호 유효성 체크 api호출
	$params = array(
		'corpNum'      => $spider_corpNum,
		'checkCorpNum' => $corpNum,
    );

	$response = corpState($params);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>