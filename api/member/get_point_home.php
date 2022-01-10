<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* MD,구독 마이페이지 > 홈 > 포인트 종합정보
	* parameter ==> userId: 회원 아이디
	* parameter ==> year:   해당년도
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};
	$year   = $data_back->{'year'};

	if ($userId == "") $userId = "a33368055";
	if ($year == "") $year = date("Y");

	// 년도 선택 옵션 값
	$currYear = date("Y");
	$yearOptions = Array();

	for ($i = 2020; $i <= $currYear; $i++) {
		array_push($yearOptions, $i);
	}

	/* ***************************************************************************************************************
	*                                     누적 포인트 산출                                                              *
	**************************************************************************************************************** */
	$totalPoint = 0;      // 포인트합계
	$pointBalance = 0;    // 포인트잔액
	$pointData = array();

	$sql = "SELECT assort, point 
			FROM (
				SELECT 'SP' as assort, ifnull(SUM(price),0) AS point
				FROM commission
				WHERE sponsId = '$userId' AND accurateStatus != '9'
				union
				SELECT 'SA' as assort, ifnull(SUM(point),0) AS point
				FROM point
				WHERE memId = '$userId' AND assort = 'OA' 
				union
				SELECT 'SO' as assort, ifnull(SUM(point),0) AS point
				FROM point
				WHERE memId = '$userId' AND assort IN ('IJ','N1','N2')
				union
				SELECT 'OC' as assort, ifnull(SUM(point),0) AS point
				FROM point
				WHERE memId = '$userId' AND assort = 'OC' 
				union
				SELECT 'OP' as assort, 0 - ifnull(SUM(point),0) AS point
				FROM cash_request
				WHERE memId = '$userId' AND (status != '8' AND status != '9')
				union		
				SELECT 'OB' as assort, ifnull(SUM(point),0) AS point 
				FROM point
				WHERE memId = '$userId'
			 ) t";	
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		if ($row[assort] == "SP") {
			$assortName = "적립대기";
			$point = $row[point];
			$totalPoint += $row[point];
		} else if ($row[assort] == "SA") {
			$assortName = "적립완료";
			$point = $row[point];
			$totalPoint += $row[point];
		} else if ($row[assort] == "SO") {
			$assortName = "기타적립";
			$point = $row[point];
			$totalPoint += $row[point];
		} else if ($row[assort] == "OC") {
			$assortName = "출금완료";
			$point = abs($row[point]);
		} else if ($row[assort] == "OP") {
			$assortName = "출금대기";
			$point = abs($row[point]);
			$pointBalance = $row[point];
		} else if ($row[assort] == "OB") {
			$assortName = "포인트잔액";
			$point = $row[point];
			$pointBalance += $row[point];
		}

		$data_info = array(
			'assort' => $assortName,
			'point'  => number_format($point),
		);
		array_push($pointData, $data_info);
	}

	/* ***************************************************************************************************************
	*                                           해당년도 > 월 합계 > 가장 큰 포인트                                      *
	**************************************************************************************************************** */
	$sql = "SELECT ifnull(MAX(point),0) AS point
			FROM ( SELECT date_format(wdate, '%Y.%m') AS month, sum(price ) AS point
				   FROM commission 
				   WHERE sponsId = '$userId' and date_format(wdate, '%Y') = '$year'
				   GROUP BY date_format(wdate, '%Y.%m')
				 ) t";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$maxPoint = $row->point;

	/* ***************************************************************************************************************
	*                                        년도별 누적 포인트                                                           *
	   ***************************************************************************************************************
		CS: MD유치
		MA: 월구독료(M)
		MS: 월구독료(구)
		R1: 렌탈수수료
		R2: 렌탈(뎁)
		P1: 휴대폰신청
		A1: 다이렉트보험
	**************************************************************************************************************** */
	$hpRequest = 0; // 휴대폰신청
	$rentalDirect = 0; // 렌탈(직접)
	$rentalDepth = 0; // 렌탈(뎁스)
	$insuRequest = 0; // 다이렉트보험
	$invite = 0; // MD유치
	$subscribe = 0; // 월 구독료
	$sql = "SELECT assort, SUM(price) AS point 
			FROM commission 
			WHERE sponsId = '$userId' AND date_format(wdate, '%Y') = '$year'
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

	$performancePoint = $hpRequest + $rentalDirect + $rentalDepth + $insuRequest;
	$supportPoint = $invite + $subscribe;

	// 휴대폰신청
	$hp = array(
		'hp'     => number_format($hpRequest),
		'detail' => array(),
	);

	// 렌탈
	$rental_detail = array(
		'direct' => number_format($rentalDirect),
		'depth'  => number_format($rentalDepth),
	);

	$rental = array(
		'rental' => number_format($rentalDirect + $rentalDepth),
		'detail' => $rental_detail,
	);

	// 다이렉트보험
	$insurance = array(
		'insurance' => number_format($insuRequest),
		'detail'    => array(),
	);

	// ***************** 해당년도 실적 포인트  ************************
	$performance = array(
		'sumPoint'  => number_format($performancePoint),
		'hp'        => $hp,
		'rental'    => $rental,
		'insurance' => $insurance,
	);

	// ***************** 해당년도 후원 포인트  ************************
	$support = array(
		'sumPoint'  => number_format($supportPoint),
		'invite'    => number_format($invite),
		'subscribe' => number_format($subscribe),
	);

	/* ***************************************************************************************************************
	*                                           월별 차트                                                             *
	**************************************************************************************************************** */
	$chartData = array();
	$monthData = array();
	$nowMonth = date("m");

	for ($i = 1; $i <= $nowMonth; $i++) {
		$data_info = array(
			'month'   => substr("00" . $i, -2),
			'point'   => 0,
			'percent' => 0,
		);
		array_push($chartData, $data_info);
	}

	// 차트 그래프 퍼센트
	$sql = "SELECT date_format(wdate, '%m') as month, SUM(price) AS point 
			FROM commission 
			WHERE sponsId = '$userId' AND date_format(wdate, '%Y') = '$year' 
			GROUP BY date_format(wdate, '%m')";	
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		$percent = ($row[point] / $maxPoint) * 100;
		$percent = (int) $percent;

		for ($i = 0; $i <= 12; $i++) {
			$objData = $chartData[$i];

		    foreach ($objData as $key => $value) {
				if ($key == 'month' && $value == $row[month]) {
					$data_info = array(
						'month'   => $row[month],
						'point'   => number_format($row[point]),
						'percent' => $percent,
					);
					$chartData[$i] = $data_info;
				}
			}
		}
	}

	// 해당년도의 월별 포인트
	$sql = "SELECT date_format(wdate, '%m') as month, assort, SUM(price) AS point 
			FROM commission 
			WHERE sponsId = '$userId' 
			AND date_format(wdate, '%Y') = '$year' 
			GROUP BY date_format(wdate, '%m'), assort
			ORDER BY month asc";	
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		$assortName = selected_object($row[assort], $arrCommiAssort);

		$data_info = array(
			'month'     => $row[month],
			'assort'    => $assortName,
			'point'     => number_format($row[point]),
		);
		array_push($monthData, $data_info);
	}

	/* ***************************************************************************************************************
	*                                           년도별 pie 차트                                                        *
	**************************************************************************************************************** */
	$pieSum = $hpRequest + $rentalDirect + $rentalDepth + $insuRequest + $invite + $subscribe;

	// 휴대폰
	$hpPercent = ($hpRequest / $pieSum) * 100;
	$hpPercent = (int) $hpPercent;

	$pieHp = array(
		'assort' => "휴대폰",
		'point'  => number_format($hpRequest),
		'percent'=> $hpPercent,
	);

	// 렌탈
	$rentalPercent = (($rentalDirect + $rentalDepth) / $pieSum) * 100;
	$rentalPercent = (int) $rentalPercent;

	$pieRental = array(
		'assort' => "렌탈",
		'point'  => number_format($rentalDirect + $rentalDepth),
		'percent'=> $rentalPercent,
	);

	// 다이렉트보험
	$insuPercent = ($insuRequest / $pieSum) * 100;
	$insuPercent = (int) $insuPercent;

	$pieInsu = array(
		'assort' => "보험",
		'point'  => number_format($insuRequest),
		'percent'=> $insuPercent,
	);

	// 후원
	$supportPercent = (($invite + $subscribe) / $pieSum) * 100;
	$supportPercent = (int) $supportPercent;

	$pieSupport = array(
		'assort' => "후원",
		'point'  => number_format($invite + $subscribe),
		'percent'=> $supportPercent,
	);

	$pieChartData = array(
		'hp'      => $pieHp,
		'rental'  => $pieRental,
		'insu'    => $pieInsu,
		'support' => $pieSupport,
	);

//print_r($pieChartData);
//exit;

	// ********************************** 최종 Array *****************************************************************
	$response = array(
		'totalPoint'      => number_format($totalPoint),
		'pointBalance'    => number_format($pointBalance),
		'pointData'       => $pointData,
		'performance'     => $performance,
		'support'         => $support,
		'chartData'       => $chartData,
		'pieChartData'    => $pieChartData,
		'monthData'       => $monthData,
		'year'            => $year,
		'yearOptions'     => $yearOptions,
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>