<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* MD,구독 포인트합계, 수수료 합계, 휴대폰신청현황
	* parameter ==> userId: 회원 아이디
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};
	$month  = $data_back->{'month'};

	if ($month == null || $month == "") $month = date("Y-m");

	//$userId = "a33368055";
	$data = array();

	// ********************************** 포인트 합계 *****************************************************************
	$sql = "SELECT SUM(point) as point FROM point WHERE memId = '$userId'";	
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$point = $row->point;

	// ********************************** 수수료 현황 *****************************************************************
	$sql = "SELECT SUM(price) as commission FROM commission WHERE sponsId = '$userId' AND date_format(wdate, '%Y-%m') = '$month'";	
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$commission = $row->commission;

	// ********************************** 휴대폰신청 현황 *****************************************************************
	$sql = "SELECT SUM(status_0) AS status_0, SUM(status_1) AS status_1, SUM(status_9) AS status_9 
            FROM ( SELECT if(requestStatus = '0', 1, 0) AS status_0, if(requestStatus = '1', 1, 0) AS status_1, if(requestStatus = '9', 1, 0) AS status_9 
                   FROM hp_request 
                   WHERE memId = '$userId' AND date_format(wdate, '%Y-%m') = '$month' 
            ) t";	
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$status_0 = $row->status_0;
	$status_1 = $row->status_1;
	$status_9 = $row->status_9;

	$data = array(
		'commission' => number_format($commission),
		'point'      => number_format($point),
		'hpStatus_0' => number_format($status_0),
		'hpStatus_1' => number_format($status_1),
		'hpStatus_9' => number_format($status_9)
	);

	$response = array(
		'result'    => "0",
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>