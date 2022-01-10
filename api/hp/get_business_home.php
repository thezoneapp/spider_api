<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/* 드림프리덤 비지니스 홈 */
	$data = Array();
	$month = date("Y-m");
	$day = date("Y-m-d");

	$timestamp = strtotime("-3 month"); 
	$minMonth = date("Y-m", $timestamp);
	$maxMonth = date("Y-m", time());

	// ********************************** 신청현황 *****************************************************************
	$data = array();
	$sql = "SELECT '전체' AS title, ifnull(SUM(status_0),0) AS status_0, ifnull(SUM(status_1),0) AS status_1, ifnull(SUM(status_2),0) AS status_2, ifnull(SUM(status_5),0) AS status_5, ifnull(SUM(status_9),0) AS status_9 
            FROM (select if(requestStatus = '0', 1, 0) AS status_0, if(requestStatus = '1', 1, 0) AS status_1, if(requestStatus = '2', 1, 0) AS status_2, if(requestStatus = '5', 1, 0) AS status_5, if(requestStatus = '9', 1, 0) AS status_9 
                  from hp_request
				  where idx > 0
                 ) m
			UNION 
			SELECT '당월' AS title, ifnull(SUM(status_0),0) AS status_0, ifnull(SUM(status_1),0) AS status_1, ifnull(SUM(status_2),0) AS status_2, ifnull(SUM(status_5),0) AS status_5, ifnull(SUM(status_9),0) AS status_9 
			FROM (select if(requestStatus = '0', 1, 0) AS status_0, if(requestStatus = '1', 1, 0) AS status_1, if(requestStatus = '2', 1, 0) AS status_2, if(requestStatus = '5', 1, 0) AS status_5, if(requestStatus = '9', 1, 0) AS status_9 
				  from hp_request
				  where date_format(wdate, '%Y-%m') = '$month' 
				 ) m
			UNION 
			SELECT '당일' AS title, ifnull(SUM(status_0),0) AS status_0, ifnull(SUM(status_1),0) AS status_1, ifnull(SUM(status_2),0) AS status_2, ifnull(SUM(status_5),0) AS status_5, ifnull(SUM(status_9),0) AS status_9 
			FROM (select if(requestStatus = '0', 1, 0) AS status_0, if(requestStatus = '1', 1, 0) AS status_1, if(requestStatus = '2', 1, 0) AS status_2, if(requestStatus = '5', 1, 0) AS status_5, if(requestStatus = '9', 1, 0) AS status_9 
				  from hp_request
				  where date_format(wdate, '%Y-%m-%d') = '$day' 
				 ) m";	
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		$statusInfo = array(
			'title'   => $row[title],
			'status_0' => number_format($row[status_0]),
			'status_1' => number_format($row[status_1]),
			'status_2' => number_format($row[status_2]),
			'status_5' => number_format($row[status_5]),
			'status_9' => number_format($row[status_9]),
			'sumCount' => number_format($row[status_0] + $row[status_1] + $row[status_2] + $row[status_5] + $row[status_9]),
		);
		array_push($data, $statusInfo);
	}

	$response = array(
		'result'    => $result,
		'message'   => $result_message,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>