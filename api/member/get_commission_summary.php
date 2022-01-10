<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 사용안함.. 삭제예정
	* MD,구독 포인트합계, 이달의 수수료 합계
	* parameter ==> userId: 회원 아이디
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};
	$month  = $data_back->{'month'};

	//$userId = "a33368055";
	$data = array();

	// ********************************** 포인트 합계 *****************************************************************
	$sql = "SELECT SUM(point) as point FROM point WHERE memId = '$userId'";	
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$point = $row->point;

	// ********************************** 수수료 현황 *****************************************************************
	if ($month == null || $month == "") $month = date("Y-m");

	$sql = "SELECT SUM(price) as commission FROM commission WHERE sponsId = '$userId' AND date_format(wdate, '%Y-%m') = '$month'";	
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$commission = $row->commission;

	$data = array(
		'monthCommission' => number_format($commission),
		'point'           => number_format($point)
	);

	$response = array(
		'result'    => "0",
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>