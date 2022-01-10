<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* MD,구독 마이페이지 > 홈 > 포인트 > 휴대폰신청
	* parameter ==> userId: 회원 아이디
	* parameter ==> year:   해당년도
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};
	$year   = $data_back->{'year'};

	if ($userId == "") $userId = "a82852359";
	if ($year == "") $year = date("Y");

	/* ***************************************************************************************************************
	*                                           해당년도 > 월 합계 > 가장 큰 포인트                                      *
	**************************************************************************************************************** */
	$sql = "SELECT ifnull(MAX(point),0) AS point
			FROM ( SELECT date_format(wdate, '%Y.%m') AS month, sum(price ) AS point
				   FROM commission 
				   WHERE sponsId = '$userId' and assort in ('P1') and date_format(wdate, '%Y') = '$year'
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
			'month'   => substr("00" . $i, -2),
			'point'   => 0,
			'percent' => 0,
		);
		array_push($chartData, $data_info);
	}

	$sql = "SELECT date_format(wdate, '%m') as month, SUM(price) AS point 
			FROM commission 
			WHERE sponsId = '$userId' and assort in ('P1') AND date_format(wdate, '%Y') = '$year' 
			GROUP BY date_format(wdate, '%Y.%m')
			ORDER BY date_format(wdate, '%Y.%m') asc";	
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

	/* ***************************************************************************************************************
	*                                        월별 신청목록                                                           *
	**************************************************************************************************************** */
	// 조건에 맞는 데이타 검색 
	$listData = array();
	$sql = "SELECT date_format(openingDate, '%m') as month, date_format(openingDate, '%Y.%m.%d') as openingDate, custName, modelName, commission AS point 
			FROM hp_request 
			WHERE memId = '$userId' AND date_format(wdate, '%Y') = '$year' 
			ORDER BY idx desc";	
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$assortName = selected_object($row[assort], $arrPointAssort);
			
			$data_info = array(
				'month'       => $row[month],
				'openingDate' => $row[openingDate],
				'custName'    => $row[custName],
				'modelName'   => $row[modelName],
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