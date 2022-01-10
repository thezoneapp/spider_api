<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 관리자 > 홈
	* parameter ==> userId: 회원 아이디
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$userId    = $data_back->{'userId'};
	
	//$userId = "admin";

	$data = array();
	$sql = "SELECT am.menuIdx, m.menuName, m.linkTo, am.subMenu 
			FROM admin_menu am 
				 INNER JOIN menu m ON am.menuIdx = m.idx 
			WHERE am.adminId = '$userId' 
			ORDER BY m.sortNo ASC";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$subMenu = $row[subMenu];
			$linkTo  = $row[linkTo];

			$dashData = array();

			// 드림컨시어지
			if ($row[menuIdx] == 176) {
				// 영업ID 발급요청
				if (strpos($subMenu, "187") !== false) {
					$sql = "SELECT SUM(status0) AS status0, SUM(status9) AS status9 
							FROM (SELECT if (requestStatus = '0', 1, 0) status0, if (requestStatus = '9', 1, 0) status9  
								  FROM concierge_request
								 ) t";
					$result2 = $connect->query($sql);
					$row2 = mysqli_fetch_object($result2);

					$data_info = array(
						'title' => "영업자",
						'count' => $row2->status9 . "명",
						'linkTo'   => "",
					);
					array_push($dashData, $data_info);

					$data_info = array(
						'title' => "영업 ID 신청대기",
						'count' => $row2->status0 . "건",
						'linkTo'   => "/admin/concierge/request",
					);
					array_push($dashData, $data_info);
				}

				// 계약상태
				if (strpos($subMenu, "177") !== false) {
					$sql = "SELECT SUM(status0) AS status0, SUM(status9) AS status9 
							FROM (SELECT if (requestStatus = '0', 1, 0) status0, if (requestStatus = '9', 1, 0) status9  
								  FROM concierge_contract
								 ) t";
					$result2 = $connect->query($sql);
					$row2 = mysqli_fetch_object($result2);

					$data_info = array(
						'title' => "상조가입완료",
						'count' => $row2->status9 . "명",
						'linkTo'   => "",
					);
					array_push($dashData, $data_info);

					$data_info = array(
						'title' => "상조신청대기",
						'count' => $row2->status0 . "건",
						'linkTo'   => "/admin/concierge",
					);
					array_push($dashData, $data_info);
				}

				// 최종
				$data_info = array(
					'dashName' => "보람상조",
					'dashData' => $dashData,
					'linkTo'   => $linkTo,
				);
				array_push($data, $data_info);

			// 휴대폰
			} else if ($row[menuIdx] == 57) {
				// 신청목록
				if (strpos($subMenu, "58") !== false) {
					// 신규접수
					$sql = "SELECT count(idx) as status0 FROM hp_request WHERE date_format(wdate, '%Y-%m-%d') >= date_add(curdate(), INTERVAL -30 DAY)";
					$result2 = $connect->query($sql);
					$row2 = mysqli_fetch_object($result2);

					$data_info = array(
						'title' => "신규접수",
						'count' => $row2->status0 . "건",
						'linkTo'   => "/admin/hp/request",
					);
					array_push($dashData, $data_info);

					// 당월 개통 완료
					$sql = "SELECT count(idx) as status9 FROM hp_request WHERE date_format(wdate, '%Y-%m') = date_format(now(), '%Y-%m')";
					$result2 = $connect->query($sql);
					$row2 = mysqli_fetch_object($result2);

					$data_info = array(
						'title' => "당월 개통완료",
						'count' => $row2->status9 . "건",
						'linkTo'   => "/admin/hp/request",
					);
					array_push($dashData, $data_info);
				}

				// 최종
				$data_info = array(
					'dashName' => "휴대폰",
					'dashData' => $dashData,
					'linkTo'   => $linkTo,
				);
				array_push($data, $data_info);

			// 회원
			} else if ($row[menuIdx] == 99) {
				// 회원목록
				if (strpos($subMenu, "102") !== false) {
					$sql = "SELECT SUM(assortM) AS assortM, SUM(assortS) AS assortS 
							FROM (SELECT if (memAssort = 'M', 1, 0) assortM, if (memAssort = 'S', 1, 0) assortS  
									FROM member
									WHERE date_format(wdate, '%Y-%m') = date_format(now(), '%Y-%m') 
								  ) t";
					$result2 = $connect->query($sql);
					$row2 = mysqli_fetch_object($result2);

					$data_info = array(
						'title'  => "당월 MD 가입",
						'count'  => $row2->assortM . "명",
						'linkTo' => "",
					);
					array_push($dashData, $data_info);

					$data_info = array(
						'title'  => "당월 구독 가입",
						'count'  => $row2->assortS . "명",
						'linkTo' => "",
					);
					array_push($dashData, $data_info);

					$data_info = array(
						'title'  => "당월 전체 가입자",
						'count'  => $row2->assortM + $row2->assortS . "명",
						'linkTo' => "",
					);
					array_push($dashData, $data_info);
				}

				// 최종
				$data_info = array(
					'dashName' => "회원",
					'dashData' => $dashData,
					'linkTo'   => $linkTo,
				);
				array_push($data, $data_info);

			// 고객문의
			} else if ($row[menuIdx] == 51) {
				if (strpos($subMenu, "53") !== false) {
					$sql = "SELECT SUM(assortQ) AS assortQ, SUM(assortS) AS assortS 
							FROM (SELECT if (bbsCode = 'Q_01', 1, 0) assortQ, if (bbsCode = 'S-01', 1, 0) assortS  
									FROM bbs
									WHERE bbsCode IN ('Q_01', 'S-01') and parentIdx > 0 AND replyStatus = 'N' AND date_format(wdate, '%Y-%m-%d') >= date_add(curdate(), INTERVAL -30 DAY) 
								  ) t";
					$result2 = $connect->query($sql);
					$row2 = mysqli_fetch_object($result2);

					$data_info = array(
						'title'  => "1:1문의 접수",
						'count'  => $row2->assortQ . "건",
						'linkTo' => "",
					);
					array_push($dashData, $data_info);

					$data_info = array(
						'title'  => "서비스문의 접수",
						'count'  => $row2->assortS . "건",
						'linkTo' => "",
					);
					array_push($dashData, $data_info);
				}

				// 최종
				$data_info = array(
					'dashName' => "고객문의",
					'dashData' => $dashData,
					'linkTo'   => $linkTo,
				);
				array_push($data, $data_info);

			// 다이렉트보험
			} else if ($row[menuIdx] == 78) {
				if (strpos($subMenu, "79") !== false) {
					$sql = "SELECT SUM(status0) AS status0, SUM(status9) AS status9 
							FROM (SELECT if (requestStatus = '0', 1, 0) status0, if (requestStatus = '9', 1, 0) status9  
									FROM insu_request
									WHERE date_format(wdate, '%Y-%m') = date_format(now(), '%Y-%m') 
								  ) t";
					$result2 = $connect->query($sql);
					$row2 = mysqli_fetch_object($result2);

					$data_info = array(
						'title'  => "당월 신규접수",
						'count'  => $row2->status0 . "명",
						'linkTo' => "",
					);
					array_push($dashData, $data_info);

					$data_info = array(
						'title'  => "당월 계약완료",
						'count'  => $row2->status9 . "건",
						'linkTo' => "",
					);
					array_push($dashData, $data_info);
				}

				// 최종
				$data_info = array(
					'dashName' => "다이렉트보험",
					'dashData' => $dashData,
					'linkTo'   => $linkTo,
				);
				array_push($data, $data_info);

			// 가자렌탈
			} else if ($row[menuIdx] == 188) {
				if (strpos($subMenu, "189") !== false) {
					$sql = "SELECT SUM(status0) AS status0, SUM(status9) AS status9 
							FROM (SELECT if (status = '0', 1, 0) status0, if (status = '9', 1, 0) status9  
									FROM rental_request
									WHERE date_format(wdate, '%Y-%m-%d') >= date_add(curdate(), INTERVAL -30 DAY) 
								  ) t";
					$result2 = $connect->query($sql);
					$row2 = mysqli_fetch_object($result2);

					$data_info = array(
						'title'  => "당월 신규접수",
						'count'  => $row2->status0 . "명",
						'linkTo' => "",
					);
					array_push($dashData, $data_info);

					$data_info = array(
						'title'  => "당월 계약완료",
						'count'  => $row2->status9 . "건",
						'linkTo' => "",
					);
					array_push($dashData, $data_info);
				}

				// 최종
				$data_info = array(
					'dashName' => "가자렌탈",
					'dashData' => $dashData,
					'linkTo'   => $linkTo,
				);
				array_push($data, $data_info);
			} 
		}

		$result_status = "0";
		$result_message = "정상.";

	} else {
		$result_status = "1";
		$result_message = "등록되지 않은 사용자입니다.";
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