<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 회원 검색옵션
	*/

	// 검색항목
	$arrSearchOption = array(
		['code' => 'memName', 'name' => '회원명'],
		['code' => 'hpNo',    'name' => '휴대폰번호'],
		['code' => 'sponsId', 'name' => '스폰서아이디'],
	);

	$response = array(
		'searchOptions'   => $arrSearchOption,
		'assortOptions'   => $arrMemAssort,
		'cmsOptions'      => $arrCmsStatus,
		'contractOptions' => $arrContractStatus,
		'memOptions'      => $arrMemStatus,
		'kindOptions'     => $arrPaymentKind,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
