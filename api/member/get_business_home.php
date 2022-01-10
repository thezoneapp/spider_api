<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* MD,구독 마이홈 정보
	* parameter ==> userId: 회원 아이디
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};
	
	//$userId = "a33368055";
	$data = Array();
	$month = date("Y-m");
	$day = date("Y-m-d");

	$timestamp = strtotime("-3 month"); 
	$minMonth = date("Y-m", $timestamp);
	$maxMonth = date("Y-m", time());

	$sponsIdSql = "and sponsId = '$userId'"; // MD, 온라인구독플랫폼
	$memIdSql = "and memId = '$userId'";

	/* ***************************************************************************************************************
	*                                                      회원 현황                                                  *
	**************************************************************************************************************** */
	$memStatus = array();
	$sql = "SELECT '전체' AS title, ifnull(SUM(md),0) AS mdCount, ifnull(SUM(os),0) AS osCount 
            FROM (select if(memAssort = 'M', 1, 0) AS md, if(memAssort = 'S', 1, 0) AS os
                  from member
				  where idx > 0 $sponsIdSql 
                 ) m
			UNION 
			SELECT '당월' AS title, ifnull(SUM(md),0) AS mdCount, ifnull(SUM(os),0) AS osCount 
			FROM (select if(memAssort = 'M', 1, 0) AS md, if(memAssort = 'S', 1, 0) AS os
				  from member
				  where date_format(wdate, '%Y-%m') = '$month' $sponsIdSql 
				 ) m
			UNION 
			SELECT '당일' AS title, ifnull(SUM(md),0) AS mdCount, ifnull(SUM(os),0) AS osCount 
			FROM (select if(memAssort = 'M', 1, 0) AS md, if(memAssort = 'S', 1, 0) AS os
				  from member
				  where date_format(wdate, '%Y-%m-%d') = '$day' $sponsIdSql 
				 ) m";	
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		$statusInfo = array(
			'title'   => $row[title],
			'mdCount' => number_format($row[mdCount]),
			'osCount' => number_format($row[osCount]),
			'sumCount' => number_format($row[mdCount] + $row[osCount]),
		);
		array_push($memStatus, $statusInfo);
	}

	// ********************************** 회원현황 차트용 *****************************************************************
	$categoryMemMonth = array();
	$seriesMemMonth = array();
	$dataMd = array();
	$dataOs = array();
	$dataSum = array();
	$sql = "SELECT date_format(wdate, '%m') AS month, SUM(md) AS mdCount, SUM(os) AS osCount 
			FROM ( SELECT wdate, if(memAssort = 'M', 1, 0) AS md, if(memAssort = 'S', 1, 0) AS os 
				   FROM member
				   WHERE date_format(wdate, '%Y-%m') >= '$minMonth' AND date_format(wdate, '%Y-%m') <= '$maxMonth' $sponsIdSql 
				  ) m
			GROUP BY date_format(wdate, '%Y-%m') 
			ORDER BY date_format(wdate, '%Y-%m') ASC";
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		array_push($categoryMemMonth, $row[month]."월");
		array_push($dataMd,  $row[mdCount]);
		array_push($dataOs,  $row[osCount]);
		array_push($dataSum, $row[mdCount] + $row[osCount]);
	}

	$seriesInfo = array(
		'name' => "전체",
		'data' => $dataSum,
	);
	array_push($seriesMemMonth, $seriesInfo);

	$seriesInfo = array(
		'name' => "MD",
		'data' => $dataMd,
	);
	array_push($seriesMemMonth, $seriesInfo);

	$seriesInfo = array(
		'name' => "구독",
		'data' => $dataOs,
	);
	array_push($seriesMemMonth, $seriesInfo);

	/* ***************************************************************************************************************
	*                                                      수수료 현황                                                *
	**************************************************************************************************************** */
	$commiStatus = array();
	$monthSql = "and date_format(wdate, '%Y-%m') = '$month' ";
	$totalPrice = 0;
	$sql = "SELECT assort, price
			FROM ( SELECT assort, SUM(price) AS price,
						  case 
							  when assort = 'CS' then 1
							  when assort = 'MA' then 2
							  when assort = 'MS' then 3
							  when assort = 'P1' then 4
							  when assort = 'R1' then 5
							  when assort = 'R2' then 6
							  when assort = 'S1' then 7
							  when assort = 'S2' then 8
							  when assort = 'H1' then 9			 			 			        	
						  END AS sort
				   FROM commission
				   WHERE idx > 0 $sponsIdSql $monthSql 
				   GROUP BY assort
				  ) t
			ORDER BY sort asc";
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		if ($row[assort] == "CS") {
			$platformName = "스파이더플랫폼";
			$assortName = "MD유치수수료";

		} else if ($row[assort] == "MA") {
			$platformName = "스파이더플랫폼";
			$assortName = "정기결제수수료(MD)";

		} else if ($row[assort] == "MS") {
			$platformName = "스파이더플랫폼";
			$assortName = "정기결제수수료(구독)";

		} else if ($row[assort] == "P1") {
			$platformName = "스파이더플랫폼";
			$assortName = "SIP 프로모션";

		} else if ($row[assort] == "R1") {
			$platformName = "가자렌탈";
			$assortName = "본인 판매 수수료";

		} else if ($row[assort] == "R2") {
			$platformName = "가자렌탈";
			$assortName = "뎁스 실적 수수료";

		} else if ($row[assort] == "S1") {
			$platformName = "일팔쇼핑";
			$assortName = "본인 구매 수수료";

		} else if ($row[assort] == "S2") {
			$platformName = "일팔쇼핑";
			$assortName = "뎁스 구매 수수료";

		} else if ($row[assort] == "H1") {
			$platformName = "휴대폰간편신청";
			$assortName = "유치수수료";
		}

		$totalPrice += $row[price];

		$statusInfo = array(
			'platformName' => $platformName,
			'assortName'   => $assortName,
			'price'        => number_format($row[price])
		);
		array_push($commiStatus, $statusInfo);
	}

	$statusInfo = array(
		'platformName' => "합계금액",
		'assortName'   => "",
		'price'        => number_format($totalPrice)
	);
	array_push($commiStatus, $statusInfo);

	/* ***************************************************************************************************************
	*                                                      CMS납부 현황                                               *
	**************************************************************************************************************** */
	// 최근 CMS 출금 메세지
	$cmsMessage = array();
    $sql = "SELECT message FROM cms_pay_log WHERE idx > 0 $memIdSql ORDER BY idx DESC LIMIT 1";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$message = $row->message;

	} else {
		$message = "납부내역이 없습니다";
	}

	$cmsMessage = array(
		'message' => $message,
	);

	// 납부내역
	$cmsPayStatus = array();
    $sql = "SELECT paymentKind, payMonth, payAmount, date_format(wdate, '%Y-%m-%d') as wdate 
			FROM cms_pay 
			WHERE idx > 0 $memIdSql 
			ORDER BY payMonth DESC 
			LIMIT 12";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$paymentKind = selected_object($row[paymentKind], $arrPaymentKind);

			$data_info = array(
				'payMonth'    => $row[payMonth],
				'paymentKind' => $paymentKind,
				'payAmount'   => number_format($row[payAmount]),
				'wdate'       => $row[wdate],
			);
			array_push($cmsPayStatus, $data_info);
		}
	}

	// ********************************** 최종 Array *****************************************************************
	$data = array(
		'memStatus'        => $memStatus,
		'categoryMemMonth' => $categoryMemMonth,
		'seriesMemMonth'   => $seriesMemMonth,
		'commiStatus'      => $commiStatus,
		'cmsPayStatus'     => $cmsPayStatus,
		'cmsMessage'       => $cmsMessage,
	);

	// 성공 결과를 반환합니다.
	$result_ok = "0";

	$response = array(
		'result'    => $result_ok,
		'message'   => $result_message,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>