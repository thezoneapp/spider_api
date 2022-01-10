<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* MD,구독 마이페이지 홈 정보
	* parameter ==> userId: 회원 아이디
	* parameter ==> year:   해당년도
	* parameter ==> month:  해당월
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};
	$page   = $data_back->{'page'};
	$rows   = $data_back->{'rows'};

	if ($userId == "") $userId = "a42484870";
	if ($rows == "") $rows = "5";

	$year = date("Y");
	$month = date("m");

	$data = Array();

	/* ***************************************************************************************************************
	*                                        포인트 산출                                                              *
	**************************************************************************************************************** */
	$totalPoint = 0; // 누적포인트합계
	$sql = "SELECT ifnull(sum(point),0) as totalPoint  
			FROM (
				SELECT assort, ifnull(SUM(point),0) AS point 
				FROM point
				WHERE memId = '$userId' AND assort != 'OC' 
				GROUP BY assort
				union
				SELECT 'ZA' as assort, ifnull(SUM(price),0) AS point
				FROM commission
				WHERE sponsId = '$userId' AND accurateStatus != '9'
			  ) t";	
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$totalPoint = $row->totalPoint;;

	/* ***************************************************************************************************************
	*                                           해당월 포인트                                                    *
	**************************************************************************************************************** */
	$sql = "SELECT ifnull(SUM(price),0) AS point 
			FROM commission 
			WHERE sponsId = '$userId' and date_format(wdate, '%Y') = '$year' and date_format(wdate, '%m') = '$month'";	
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$monthPoint = $row->point;

	/* ***************************************************************************************************************
	*                                           챠트용- 년도별 누적 포인트                                              *
	**************************************************************************************************************** */
	$chartData = array();
	$sql = "SELECT assort, SUM(price) AS point 
			FROM commission 
			WHERE sponsId = '$userId' AND date_format(wdate, '%Y') = '$year' 
			GROUP BY assort";	
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		$assortName = selected_object($row[assort], $arrCommiAssort);

		$data_info = array(
			'assort' => $assortName,
			'point'  => $row[point],
		);
		array_push($chartData, $data_info);
	}

	/* ***************************************************************************************************************
	*                                           챠트용- 월별 누적 포인트                                              *
	**************************************************************************************************************** */
	$chartMonthData = array();
	$sql = "SELECT assort, SUM(price) AS point 
			FROM commission 
			WHERE sponsId = '$userId' AND date_format(wdate, '%Y-%m') = '$year-$month' 
			GROUP BY assort";	
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		$assortName = selected_object($row[assort], $arrCommiAssort);

		$data_info = array(
			'assort' => $assortName,
			'point'  => $row[point],
		);
		array_push($chartMonthData, $data_info);
	}

	/* ***************************************************************************************************************
	*                                           휴대폰 신청현황                                                      *
	**************************************************************************************************************** */
	$sumHpCount = 0;
	$arrHpCount = array();
    $sql = "SELECT requestStatus as statusName, count(idx) statusCount FROM hp_request WHERE memId = '$userId' GROUP BY requestStatus";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$sumHpCount += $row[statusCount];
			array_push($arrHpCount, $row);
		}
	}

	/* ***************************************************************************************************************
	*                                           휴대폰 추천 모델                                                       *
	**************************************************************************************************************** */
	$sql = "SELECT max(price) AS maxCommi
			FROM (
			SELECT 'N' as assort, max(priceNew) AS price FROM hp_commi WHERE useYn = 'Y' 
			UNION 
			SELECT 'M' as assort, max(priceMnp) AS price FROM hp_commi WHERE useYn = 'Y' 
			UNION 
			SELECT 'C' as assort, max(priceChange) AS price FROM hp_commi WHERE useYn = 'Y' 
			) t1";	
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$maxCommi = $row->maxCommi;

	$hpRecommend = array();
	$sql = "SELECT hc.telecom, hc.modelCode, hm.modelName, hm.thumbnail,
				   case 
						when hc.priceNew > priceMnp AND hc.pricenew > hc.priceChange then priceNew
						when hc.priceMnp > pricenew AND hc.priceMnp > hc.priceChange then priceMnp
						when hc.priceChange > priceNew AND hc.priceMnp > hc.priceChange then priceChange		 	
				   END AS point,
				   case 
						when hc.priceNew > priceMnp AND hc.pricenew > hc.priceChange then 'N'
						when hc.priceMnp > pricenew AND hc.priceMnp > hc.priceChange then 'M'
						when hc.priceChange > priceNew AND hc.priceMnp > hc.priceChange then 'C'	 	
				   END AS assort
			FROM hp_commi hc 
				 INNER JOIN hp_model hm ON hc.modelCode = hm.modelCode 
			WHERE hc.useYn = 'Y' AND (hc.priceNew = $maxCommi OR hc.priceMnp = $maxCommi OR hc.priceChange = $maxCommi)";	
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		$telecomName = selected_object($row[telecom], $arrTelecomAssort);
		$assortName = selected_object($row[assort], $arrRequestAssort);

		$data_info = array(
			'telecomName' => $telecomName,
			'assortName'  => $assortName,
			'modelCode'   => $row[modelCode],
			'modelName'   => $row[modelName],
			'thumbnail'   => $row[thumbnail],
			'point'       => number_format($row[point]),
		);
		array_push($hpRecommend, $data_info);
	}

	/* ***************************************************************************************************************
	*                                           다이렉트보험 현황                                                       *
	**************************************************************************************************************** */
	$sumInsuCount = 0;
	$arrInsuCount = array();
    $sql = "SELECT requestStatus as statusName, count(idx) statusCount FROM insu_request WHERE memId = '$userId' GROUP BY requestStatus";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$sumInsuCount += $row[statusCount];
			array_push($arrInsuCount, $row);
		}
	}

	/* ***************************************************************************************************************
	*                                           렌탈 실적 현황                                                        *
	**************************************************************************************************************** */
	$sumR1 = 0;
	$sumR2 = 0;
	$arrRentalStatus = array();
    $sql = "SELECT assort, SUM(price) AS price  
			FROM commission 
			WHERE assort IN ('R1','R2') AND sponsId = '$userId'
			GROUP BY assort";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[assort] == 'R1') $sumR1 = $row[price];
			else $sumR2 = $row[price];
		}
	}

	$data_info = array(
		'name'  => "나의 실적 금액",
		'point' => number_format($sumR1),
	);
	array_push($arrRentalStatus, $data_info);

	$data_info = array(
		'name'  => "뎁스 실적 보너스 금액",
		'point' => number_format($sumR2),
	);
	array_push($arrRentalStatus, $data_info);

	/* ***************************************************************************************************************
	*                                           공지사항                                                              *
	**************************************************************************************************************** */
	$noticeData = array();
    $sql = "SELECT no, idx, memId, memName, subject, thumbnail, replyStatus, replyCount, viewCount, wdate 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, subject, thumbnail, replyStatus, replyCount, viewCount, date_format(wdate, '%Y-%m-%d') as wdate 
		           from bbs, (select @a:= 0) AS a 
		           where parentIdx = 0 and bbsCode = 'N_01'  
		         ) m 
			ORDER BY no DESC
			LIMIT $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$replyStatus = selected_object($row[replyStatus], $arrYesNo);

			$data_info = array(
				'no'          => $row[no],
				'idx'         => $row[idx],
				'memId'       => $row[memId],
				'memName'     => $row[memName],
				'subject'     => $row[subject],
				'thumbnail'   => $row[thumbnail],
				'replyStatus' => $replyStatus,
				'replyCount'  => $row[replyCount],
				'viewCount'   => $row[viewCount],
				'wdate'       => $row[wdate],
				'isChecked'   => false,
			);
			array_push($noticeData, $data_info);
		}
	}

	$pointBalance = 0;    // 포인트잔액
	$accurateStandby = 0; // 정산대기중
	$outComplete = 0;     // 출금완료
	$outIng = 0;          // 출금진행

	// ********************************** 최종 Array *****************************************************************
	$response = array(
		'totalPoint'      => number_format($totalPoint),
		'monthPoint'      => number_format($monthPoint),
		'chartData'       => $chartData,
		'chartMonthData'  => $chartMonthData,
		'hpRecommend'     => $hpRecommend,
		'hpRequest'       => array_add_count($arrRequestStatus, $arrHpCount, $sumHpCount),
		'insuStatus'      => array_add_count($arrInsuStatus, $arrInsuCount, $sumInsuCount),
		'rentalStatus'    => $arrRentalStatus,
		'noticeData'      => $noticeData,
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>