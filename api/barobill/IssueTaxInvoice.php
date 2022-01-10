<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/barobill.php";
	include './BaroService_TI.php';

	/*
	* 바로빌 > 일반(수정)세금계산서 "등록" 과 "발행" 을 한번에 처리
	* parameter :
		assort:      구분(C: 현금인출)
		idx:         해당 idx
		memId:       회원ID
		totalAmount: 합계금액
	*/

	$data_back = json_decode(file_get_contents('php://input'));
	$assort      = $data_back->{'assort'};
	$idx         = $data_back->{'idx'};
	$memId       = $data_back->{'memId'};
	$totalAmount = $data_back->{'totalAmount'};

	$assort = "C";
	$idx = "";
	$memId = "a27233377";
	$totalAmount = "155556";

	$params = array(
		'tergetAssort' => $assort,
		'idx'          => $idx,
		'memId'        => $memId,
		'itemName'     => "판매수수료",
		'totalAmount'  => $totalAmount,
	);

	// 바로빌 api호출
	$response = reverseIssueTaxInvoice($params);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
