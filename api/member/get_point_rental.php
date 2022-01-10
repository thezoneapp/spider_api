<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* MD,구독 마이페이지 > 홈 > 포인트 > 렌탈
	* parameter ==> userId: 회원 아이디
	* parameter ==> year:   해당년도
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};
	$year   = $data_back->{'year'};

	if ($userId == "") $userId = "a33368055";
	if ($year == "") $year = date("Y");

	/* ***************************************************************************************************************
	*                                           해당년도 > 월 합계 > 가장 큰 포인트                                      *
	**************************************************************************************************************** */
	$sql = "SELECT ifnull(MAX(point),0) AS point
			FROM ( SELECT date_format(wdate, '%Y.%m') AS month, sum(price ) AS point
				   FROM commission 
				   WHERE sponsId = '$userId' and assort in ('R1','R2') and date_format(wdate, '%Y') = '$year'
				   GROUP BY date_format(wdate, '%Y.%m')
				 ) t";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$maxPoint = $row->point;

	/* ***************************************************************************************************************
	*                                        년도별 포인트                                                           *
	**************************************************************************************************************** */
	$chartData = array();
	$nowMonth = date("m");

	for ($i = 1; $i <= $nowMonth; $i++) {
		$data_info = array(
			'month'         => substr("00" . $i, -2),
			'point'         => 0,
			'direct'        => 0,
			'depth'         => 0,
			'percent'       => 0,
			'directPercent' => 0,
			'depthPercent'  => 0,
		);
		array_push($chartData, $data_info);
	}

	$sql = "SELECT month, sum(direct) AS direct, sum(depth ) AS depth
			FROM ( SELECT date_format(wdate, '%m') as month, if(assort = 'R1', price, 0) AS direct, if(assort = 'R2', price, 0) AS depth  
					 FROM commission 
					 WHERE sponsId = '$userId' and assort in ('R1','R2') and date_format(wdate, '%Y') = '$year'  
					) t
			GROUP BY month
			ORDER BY month asc";	
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		$percent = ($row[point] / $maxPoint) * 100;
		$percent = (int) $percent;

		$sumPoint = $row[direct] + $row[depth];
		$directPercent = 0;
		$depthPercent = 0;

		if ($row[direct] > 0) {
			$directPercent = ($row[direct] / $sumPoint) * 100;
			$directPercent = (int) $directPercent;
			$depthPercent = 100 - $directPercent;

		} else if ($row[depth] > 0) {
			$depthPercent = ($row[depth] / $sumPoint) * 100;
			$depthPercent = (int) $depthPercent;
			$directPercent = 100 - $depthPercent;
		}

		for ($i = 0; $i <= 12; $i++) {
			$objData = $chartData[$i];

		    foreach ($objData as $key => $value) {
				if ($key == 'month' && $value == $row[month]) {
					$data_info = array(
						'month'         => $row[month],
						'point'         => number_format($row[direct] + $row[depth]),
						'direct'        => $row[direct],
						'depth'         => $row[depth],
						'percent'       => $percent,
						'directPercent' => $directPercent,
						'depthPercent'  => $depthPercent,
					);
					$chartData[$i] = $data_info;
				}
			}
		}
	}

	/* ***************************************************************************************************************
	*                                        월별 신청목록                                                           *
	**************************************************************************************************************** */
	// 조건에 맞는 데이타 검색 
	$listData = array();
	$sql = "SELECT date_format(wdate, '%m') as month, DATE_FORMAT(wdate, '%Y.%m.%d') as wdate, assort, if (assort = 'R1', remarks, memName) as custName, assort, price as point  
			FROM commission 
			WHERE sponsId = '$userId' and assort in ('R1','R2') AND date_format(wdate, '%Y') = '$year' 
			ORDER BY idx desc";	
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$assortName = "";

			if ($row[month] == "R1") $assortName = "직접판매";
			else $assortName = "뎁스판매";
			
			$data_info = array(
				'month'       => $row[month],
				'wdate'       => $row[wdate],
				'assort'      => $assortName,
				'custName'    => $row[custName],
			    'point'       => number_format($row[point]),
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