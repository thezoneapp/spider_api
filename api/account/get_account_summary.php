<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 회원가입집계표(모바일, 마이비지니스용)
	* parameter ==> userId: 회원 아이디
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};
	
	//$userId = "a33368055";
	$month = date("Y-m");
	$day = date("Y-m-d");

	$data = Array();
	$allCount = 0;
	$monthCount = 0;
	$dayCount = 0;
	$monthAmount = 0;

    $sql = "SELECT id, name, auth  
			FROM ( SELECT id, name, 'A' as auth 
                   FROM admin
                   union 
                   SELECT memId AS id, memName AS name, memAssort AS auth 
                   FROM member
				 ) m 
			WHERE id = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$accountAuth = $row->auth;

		// 관리자
		if ($row->auth == "A") { 
			$sql = "SELECT 'allCount' AS 'assort', COUNT(idx) AS value FROM member 
					UNION 
					SELECT 'monthCount' AS 'assort', COUNT(*) AS value FROM member WHERE date_format(wdate, '%Y-%m') = '$month'
					UNION 
					SELECT 'dayCount' AS 'assort', COUNT(*) AS value FROM member WHERE date_format(wdate, '%Y-%m-%d') = '$day' 
					UNION 
					SELECT 'monthAmount' AS 'assort', SUM(price) AS value FROM sales WHERE date_format(wdate, '%Y-%m') = '$month'";	

		// MD, 온라인구독플랫폼
		} else {
			$sql = "SELECT 'allCount' AS 'assort', COUNT(idx) AS value FROM member WHERE sponsId = '$userId' 
					UNION 
					SELECT 'monthCount' AS 'assort', COUNT(*) AS value FROM member WHERE sponsId = '$userId' and date_format(wdate, '%Y-%m') = '$month' 
					UNION 
					SELECT 'dayCount' AS 'assort', COUNT(*) AS value FROM member WHERE sponsId = '$userId' and date_format(wdate, '%Y-%m-%d') = '$day' 
					UNION 
					SELECT 'monthAmount' AS 'assort', SUM(value) AS value 
					FROM (select sum(price) AS value 
					      from commission 
						  where sponsId = '$userId' and date_format(wdate, '%Y-%m') = '$month' 
					     ) m";
		}

		$result = $connect->query($sql);

		while($row = mysqli_fetch_array($result)) {
			if ($row[assort] == "allCount") $allCount = $row[value];
			else if ($row[assort] == "monthCount") $monthCount = $row[value];
			else if ($row[assort] == "dayCount") $dayCount = $row[value];
			else if ($row[assort] == "monthAmount") $monthAmount = $row[value];
		}

		$monthAmount = $monthAmount / 10000;
		$monthAmount = (int) $monthAmount;

		$data = array(
			'accountAuth' => $accountAuth,
			'allCount'    => number_format($allCount),
			'monthCount'  => number_format($monthCount),
			'dayCount'    => number_format($dayCount),
			'monthAmount' => number_format($monthAmount),
		);

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$data = array();
		$result = "1";
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