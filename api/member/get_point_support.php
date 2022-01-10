<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* MD,구독 마이페이지 > 홈 > 포인트 > 후원
	* parameter ==> userId: 회원 아이디
	* parameter ==> year:   해당년도
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};
	$year   = $data_back->{'year'};

	if ($userId == "") $userId = "a67888674";
	if ($year == "") $year = date("Y");

	/* ***************************************************************************************************************
	*                                        년도별 포인트                                                           *
	**************************************************************************************************************** */
	$chartData = array();
	$payPoint = 0;
	$unpaidPoint = 0;
	$sql = "SELECT payStatus, sum(commiAmount) AS payPoint
			FROM cms_pay
			WHERE sponsId = '$userId' and date_format(wdate, '%Y') = '$year'
			GROUP BY payStatus";	
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		if ($row[payStatus] == '9') $payPoint += $row[payPoint];
		else $unpaidPoint += $row[payPoint];
	}

	//$payPoint = 30000;
	//$unpaidPoint = 25000;

	$sumPoint = $payPoint + $unpaidPoint;
	$payPercent = 0;
	$unpaidPercent = 0;

	if ($payPoint > 0) {
		$payPercent = ($payPoint / $sumPoint) * 100;
		$payPercent = (int) $payPercent;
		$unpaidPercent = 100 - $payPercent;

	} else if ($unpaidPoint > 0) {
		$unpaidPercent = ($unpaidPoint / $sumPoint) * 100;
		$unpaidPercent = (int) $unpaidPercent;
		$payPercent = 100 - $unpaidPercent;
	}

	$chartData = array(
		'납부'           => number_format($payPoint),
		'payPercent'    => $payPercent,
		'미납'           => number_format($unpaidPoint),
		'unpaidPercent' => $unpaidPercent,
	);

	/* ***************************************************************************************************************
	*                                        MD 유치 포인트                                                           *
	**************************************************************************************************************** */
	// 조건에 맞는 데이타 검색 
	$listData = array();
    $sql = "SELECT date_format(wdate, '%m') as month, date_format(wdate, '%Y.%m.%d') as wdate, memName, price AS point 
			FROM commission 
			WHERE sponsId = '$userId' and assort = 'CS' and date_format(wdate, '%Y') = '$year' and price != 0 
			ORDER BY idx desc";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$assortName = selected_object($row[assort], $arrPointAssort);
			
			$data_info = array(
				'month'      => $row[month],
				'wdate'      => $row[wdate],
				'memName'    => $row[memName],
			    'point'      => number_format($row[point]),
			);
			array_push($listData, $data_info);
		}
	}

	// ********************************** 최종 Array *****************************************************************
	$response = array(
		'chartData' => $chartData,
		'listData'  => $listData,
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>