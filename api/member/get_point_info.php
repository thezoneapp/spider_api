<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* MD,구독 마이페이지 > 홈 > 포인트 종합정보
	* parameter ==> memId: 회원 아이디
	* parameter ==> year:  해당년도
	* parameter ==> month: 해당월
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$memId  = $data_back->{'memId'};
	$year   = $data_back->{'year'};
	$month  = $data_back->{'month'};

	//$memId = "a64171763";
	//$year = date("Y");
	//$month = date("Y-m");

	/* ***************************************************************************************************************
	*                                        년도별 누적 포인트                                                           *
	   ***************************************************************************************************************
		CS: MD유치
		MA: 월구독료(M)
		MS: 월구독료(구)

		R1: 렌탈수수료
		R2: 렌탈(뎁)
		S1: 일팔쇼핑
		S2: 일팔쇼핑(뎁)
		P1: 휴대폰신청
		A1: 다이렉트보험
	**************************************************************************************************************** */
	$yearSum = 0;
	$hpRequest = 0; // 휴대폰신청
	$rentalDirect = 0; // 렌탈(직접)
	$rentalDepth = 0; // 렌탈(뎁스)
	$insuRequest = 0; // 다이렉트보험
	$invite = 0; // MD유치
	$subscribe = 0; // 월 구독료
	$sql = "SELECT assort, SUM(price) AS point 
			FROM commission 
			WHERE sponsId = '$memId' and date_format(wdate, '%Y') = '$year'
			GROUP BY assort";	
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		$yearSum += $row[point];

		switch ($row[assort]) {
	        case ($row[assort] == "P1"): // 휴대폰
				$hpRequest += $row[point];
				break;

	        case ($row[assort] == "R1"): // 렌탈(직접)
				$rentalDirect += $row[point];
				break;

	        case ($row[assort] == "R2"): // 렌탈(뎁스)
				$rentalDepth += $row[point];
				break;

	        case ($row[assort] == "A1"): // 다이렉트보험
				$insuRequest += $row[point];
				break;

	        case ($row[assort] == "CS"): // MD유치
				$invite += $row[point];
				break;

	        case ($row[assort] == "MA" || $row[assort] == "MS"): // 월 구독료
				$subscribe += $row[point];
				break;
		}
	}

	// 휴대폰신청
	$hp = array(
		'hp'     => $hpRequest,
		'detail' => array(),
	);

	// 렌탈
	$rental_detail = array(
		'direct' => $rentalDirect,
		'depth'  => $rentalDepth,
	);

	$rental = array(
		'rental' => $rentalDirect + $rentalDepth,
		'detail' => $rental_detail,
	);

	// 다이렉트보험
	$insurance = array(
		'insurance' => $insuRequest,
		'detail'    => array(),
	);

	// ***************** 해당년도 실적 포인트  ************************
	$yearPerformance = array(
		'hp'        => $hp,
		'rental'    => $rental,
		'insurance' => $insurance,
	);

	// ***************** 해당년도 후원 포인트  ************************
	$yearSupport = array(
		'invite'    => $invite,
		'subscribe' => $subscribe,
	);

	// ***************** 해당년도 후원 포인트  ************************
	$yearData = array(
		'performance' => $yearPerformance,
		'support'     => $yearSupport,
	);

	/* ***************************************************************************************************************
	*                                           월별 누적 포인트                                                       *
	**************************************************************************************************************** */
	$monthSum = 0;
	$hpRequest = 0; // 휴대폰신청
	$rental = 0; // 렌탈(직접)
	$insu = 0; // 다이렉트보험
	$invite = 0; // MD유치
	$subscribe = 0; // 월 구독료
	$sql = "SELECT assort, SUM(price) AS point 
			FROM commission 
			WHERE sponsId = '$memId' AND date_format(wdate, '%Y') = '$year' and date_format(wdate, '%Y-%m') = '$month' 
			GROUP BY assort";	
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		$yearSum += $row[point];

		switch ($row[assort]) {
	        case ($row[assort] == "H1" || $row[assort] == "H2"): // 휴대폰
				$hpRequest += $row[point];
				break;

	        case ($row[assort] == "R1" || $row[assort] == "R2"): // 렌탈
				$rental += $row[point];
				break;

	        case ($row[assort] == "A1"): // 다이렉트보험
				$insuRequest += $row[point];
				break;

	        case ($row[assort] == "CS"): // MD유치
				$invite += $row[point];
				break;

	        case ($row[assort] == "MA" || $row[assort] == "MS"): // 월 구독료
				$subscribe += $row[point];
				break;
		}
	}

	// 월간포인트
	$monthData = array(
		'hp'        => $hpRequest,
		'rental'    => $rental,
		'insurance' => $insuRequest,
		'invite'    => $invite,
		'subscribe' => $subscribe,
	);

	// ********************************** 최종 Array *****************************************************************
	$response = array(
		'yearSum'   => $yearSum,
		'yearData'  => $yearData,
		'monthSum'  => $monthSum,
		'monthData' => $monthData,
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>