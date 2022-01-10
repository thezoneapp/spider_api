<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 회원 ID 변경
	* parameter ==> memId:    회원 아이디
	* parameter ==> changeId: 변경할 아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId      = $input_data->{'memId'};
	$changeId   = $input_data->{'changeId'};

	$memId    = "a20820675";
	$changeId = "a46408434";

	// 회원정보
	$sql = "UPDATE member SET memId  = '$changeId' WHERE memId = '$memId'";
	$connect->query($sql);

	$sql = "UPDATE member SET sponsId  = '$changeId' WHERE sponsId = '$memId'";
	$connect->query($sql);

	$sql = "UPDATE member_log SET adminId  = '$changeId' WHERE adminId = '$memId'";
	$connect->query($sql);

	// CMS 정보
	$sql = "UPDATE cms SET memId  = '$changeId' WHERE memId = '$memId'";
	$connect->query($sql);

	$sql = "UPDATE cms_log SET memId  = '$changeId' WHERE memId = '$memId'";
	$connect->query($sql);

	// CMS > 출금신청
	$sql = "UPDATE cms_pay SET memId  = '$changeId' WHERE memId = '$memId'";
	$connect->query($sql);

	$sql = "UPDATE cms_pay SET sponsId  = '$changeId' WHERE sponsId = '$memId'";
	$connect->query($sql);

	$sql = "UPDATE cms_pay_log SET memId  = '$changeId' WHERE memId = '$memId'";
	$connect->query($sql);

	// 수수료정보
	$sql = "UPDATE commission SET memId  = '$changeId' WHERE memId = '$memId'";
	$connect->query($sql);

	$sql = "UPDATE commission SET sponsId  = '$changeId' WHERE sponsId = '$memId'";
	$connect->query($sql);

	// 수수료 정산
	$sql = "UPDATE commi_accurate SET memId  = '$changeId' WHERE memId = '$memId'";
	$connect->query($sql);

	$sql = "UPDATE commi_accurate_detail SET memId  = '$changeId' WHERE memId = '$memId'";
	$connect->query($sql);

	// 현금인출요청
	$sql = "UPDATE cash_request SET memId  = '$changeId' WHERE memId = '$memId'";
	$connect->query($sql);

	// 휴대폰 신청
	$sql = "UPDATE hp_request SET memId  = '$changeId' WHERE memId = '$memId'";
	$connect->query($sql);

	// 다이렉트보험 상담신청
	$sql = "UPDATE insu_request SET memId  = '$changeId' WHERE memId = '$memId'";
	$connect->query($sql);

	// 포인트정보
	$sql = "UPDATE point SET memId  = '$changeId' WHERE memId = '$memId'";
	$connect->query($sql);

	// 성공 결과를 반환합니다.
	$result_status = "0";
	$result_message = "변경하였습니다.";

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>