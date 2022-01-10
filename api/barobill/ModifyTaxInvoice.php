<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/barobill.php";
	include './BaroService_TI.php';

	/*
	* 바로빌 > 수정 세금계산서 "등록" 과 "발행" 을 한번에 처리
	* parameter :
		idx:          해당 idx
		assort:       구분(C: 현금인출)
		modifyCode:   수정발행사유(2: 공급가액, 4: 계약해제)
		memId:        회원ID
		totalAmount:  합계금액
	*/

	$data_back = json_decode(file_get_contents('php://input'));
	$idx          = $data_back->{'idx'};
	$assort       = $data_back->{'assort'};
	$modifyCode   = $data_back->{'modifyCode'};
	$memId        = $data_back->{'memId'};
	$totalAmount  = $data_back->{'totalAmount'};

	//$assort = "C";
	//$modifyCode = "4";
	//$idx = "487";
	//$memId = "a51907770";
	//$totalAmount = "52500";

    $totalAmount = str_replace(",", "", $totalAmount);

	$params = array(
		'tergetAssort' => $assort,
		'modifyCode'   => $modifyCode,
		'idx'          => $idx,
		'memId'        => $memId,
		'itemName'     => "판매수수료",
		'totalAmount'  => $totalAmount,
	);

	// 바로빌 api호출
	$response = reverseModifyTaxInvoice($params);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
