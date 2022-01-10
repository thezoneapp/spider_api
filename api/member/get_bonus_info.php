<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* MD,구독 보너스 정보
	* parameter ==> userId: 회원 아이디
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};

	//$userId = "33368055";

	$month = date("Y-m");
	$timestamp = strtotime("-3 month"); 
	$minMonth = date("Y-m", $timestamp);
	$maxMonth = date("Y-m", time());

	$sponsIdSql = "and sponsId = '$userId'"; // MD, 온라인구독플랫폼
	$memIdSql = "and memId = '$userId'";
	$monthSql = "and date_format(wdate, '%Y-%m') = '$month' ";
	$dateSql = "and (date_format(wdate, '%Y-%m') >= '$minMonth' and date_format(wdate, '%Y-%m') <= '$maxMonth') ";

	$data = Array();

	/* ***************************************************************************************************************
	*                                                      3개월 차트                                                 *
	**************************************************************************************************************** */
	$category3Month = array();
	$series3Month = array();
	$dataMonth = array();
	$sql = "SELECT date_format(wdate, '%Y.%m') AS monthName, SUM(price) AS price 
			FROM commission 
			WHERE idx > 0 $sponsIdSql $dateSql 
			GROUP BY date_format(wdate, '%Y-%m')";
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		$price = $row[price] / 10000;
		$price = (int) $price;
		array_push($dataMonth,      $price);
		array_push($category3Month, $row[monthName]);
	}

	$seriesInfo = array(
		'name' => "",
		'data' => $dataMonth,
	);
	array_push($series3Month, $seriesInfo);

	/* ***************************************************************************************************************
	*                                                      당월 합계 금액                                             *
	**************************************************************************************************************** */
	$monthSummary = array();
	$price = 0;

	$sql = "SELECT SUM(price) AS price 
			FROM commission 
			WHERE idx > 0 $sponsIdSql $monthSql 
			GROUP BY date_format(wdate, '%Y-%m')";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$price = $row->price;
	}

	$monthSummary = array(
		'monthName' => date("Y-m", time()) . "월",
		'price'     => number_format($price)
	);

	/* ***************************************************************************************************************
	*                                                   당월 수수료 내역                                               *
	**************************************************************************************************************** */
	$Cdetail = array(); // MD유치 수수료
	$Mdetail = array(); // 구독 수수료
	$Rdetail = array(); // 가자렌탈
	$Hdetail = array(); // 핸디
	$Sdetail = array(); // 일팔쇼핑
	$Pdetail = array(); // 휴대폰 간편 신청

	$Csumprice = 0;
	$Msumprice = 0;
	$Rsumprice = 0;
	$Hsumprice = 0;
	$Ssumprice = 0;
	$Psumprice = 0;

	$sql = "SELECT assort, price
			FROM ( SELECT assort, SUM(price) AS price,
						  case 
							  when assort = 'CS' then 'A'
							  when assort = 'MA' then 'B'
							  when assort = 'MS' then 'C'
							  when assort = 'P1' then 'D'
							  when assort = 'R1' then 'E'
							  when assort = 'R2' then 'F'
							  when assort = 'S1' then 'G'
							  when assort = 'S2' then 'H'
							  when assort = 'H1' then 'I'	
							  when assort = 'H2' then 'J'								  
						  END AS sort
				   FROM commission
				   WHERE idx > 0 $sponsIdSql $monthSql 
				   GROUP BY assort
				  ) t
			ORDER BY sort asc";
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		if ($row[assort] == "CS") { // 개설비용 = MD유치
			$Csumprice += $row[price];

			$detailInfo = array(
				'detailName'  => "실적 수수료",
				'detailPrice' => number_format($row[price])
			);
			array_push($Cdetail, $detailInfo);

		} else if ($row[assort] == "MA") { // 월구독료(대)
			$Msumprice += $row[price];

			$detailInfo = array(
				'detailName'  => "MD 구독 수수료",
				'detailPrice' => number_format($row[price])
			);
			array_push($Mdetail, $detailInfo);

		} else if ($row[assort] == "MS") { // 월구독료(판
			$Msumprice += $row[price];

			$detailInfo = array(
				'detailName'  => "판매점 구독 수수료",
				'detailPrice' => number_format($row[price])
			);
			array_push($Mdetail, $detailInfo);

		} else if ($row[assort] == "R1") { // 가자렌탈수수료
			$Rsumprice += $row[price];

			$detailInfo = array(
				'detailName'  => "실적 수수료",
				'detailPrice' => number_format($row[price])
			);
			array_push($Rdetail, $detailInfo);

		} else if ($row[assort] == "R2") { // 가자렌탈수수료(댑)
			$Rsumprice += $row[price];

			$detailInfo = array(
				'detailName'  => "뎁스 수수료",
				'detailPrice' => number_format($row[price])
			);
			array_push($Rdetail, $detailInfo);

		} else if ($row[assort] == "H1") { // 핸디 수수료
			$Hsumprice += $row[price];

			$detailInfo = array(
				'detailName'  => "실적 수수료",
				'detailPrice' => number_format($row[price])
			);
			array_push($Hdetail, $detailInfo);

		} else if ($row[assort] == "H2") { // 핸디 수수료(댑)
			$Hsumprice += $row[price];

			$detailInfo = array(
				'detailName'  => "뎁스 수수료",
				'detailPrice' => number_format($row[price])
			);
			array_push($Hdetail, $detailInfo);

		} else if ($row[assort] == "S1") { // 일팔쇼핑 수수료
			$Ssumprice += $row[price];

			$detailInfo = array(
				'detailName'  => "실적 수수료",
				'detailPrice' => number_format($row[price])
			);
			array_push($Sdetail, $detailInfo);

		} else if ($row[assort] == "S2") { // 일팔쇼핑 수수료(댑)
			$Ssumprice += $row[price];

			$detailInfo = array(
				'detailName'  => "뎁스 수수료",
				'detailPrice' => number_format($row[price])
			);
			array_push($Sdetail, $detailInfo);

		} else if ($row[assort] == "P1") { // 휴대폰 신청
			$Psumprice += $row[price];

			$detailInfo = array(
				'detailName'  => "뎁스 수수료",
				'detailPrice' => number_format($row[price])
			);
			array_push($Pdetail, $detailInfo);
		}
	}


	$bonusList = array();

	// 가자렌탈
	$bonusInfo = array(
		'platformName'  => "가자렌탈",
		'platformPrice' => number_format($Rsumprice),
		'detail'        => $Rdetail
	);
	array_push($bonusList, $bonusInfo);

	// 핸디
	$bonusInfo = array(
		'platformName'  => "핸디",
		'platformPrice' => number_format($Hsumprice),
		'detail'        => $Hdetail
	);
	array_push($bonusList, $bonusInfo);

	// 휴대폰 간편 신청
	$bonusInfo = array(
		'platformName'  => "휴대폰 간편 신청",
		'platformPrice' => number_format($Psumprice),
		'detail'        => $Pdetail
	);
	array_push($bonusList, $bonusInfo);

	// 일팔쇼핑
	$bonusInfo = array(
		'platformName'  => "일팔쇼핑",
		'platformPrice' => number_format($Ssumprice),
		'detail'        => $Sdetail
	);
	array_push($bonusList, $bonusInfo);

	// MD유치 보너스
	$bonusInfo = array(
		'platformName'  => "MD유치 보너스",
		'platformPrice' => number_format($Csumprice),
		'detail'        => $Cdetail
	);
	array_push($bonusList, $bonusInfo);

	// 구독수수료 보너스
	$bonusInfo = array(
		'platformName'  => "구독수수료 보너스",
		'platformPrice' => number_format($Msumprice),
		'detail'        => $Mdetail
	);
	array_push($bonusList, $bonusInfo);

	// ********************************** 정산정보 *****************************************************************
	// 정산 예정일
	$timestamp = strtotime("+1 month"); 
	$scheduleDate = date("Y", $timestamp) . "년" . date("m", $timestamp) . "월";

	// 정산 기준일
	$timestamp = strtotime("-1 month"); 
	$prenextMonth = date("Y-m-d", $timestamp);
	$lastDay = date('t', strtotime($nextMonth));
	$standardDate = date("m", $timestamp) . "월" . $lastDay . "일";
 
	$accurateInfo = array(
		'scheduleDate' => $scheduleDate,
		'standardDate' => $standardDate,
	);

	// ********************************** 최종 Array *****************************************************************
	$response = array(
		'category3Month' => $category3Month,
		'series3Month'   => $series3Month,
		'accurateInfo'   => $accurateInfo,
		'monthSummary'   => $monthSummary,
		'bonusList'      => $bonusList,
    );

	//print_r($response);
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>