<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 마이페이지 > 헤더 > 배찌정보
	* parameter
		memId: 사용자ID
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId = $input_data->{'memId'};

	//$memId = "a27233377";

	$data = array();
	$count = 0;

	// 최근 1주일 파트너 회원가입
    $sql = "SELECT COUNT(idx) AS memberCount FROM member WHERE sponsId = '$memId' AND wdate > DATE_ADD(NOW(),INTERVAL -1 WEEK )";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$memberCount = $row->memberCount;

	if ($memberCount > 0) {
		$count += 1;
		$data_info = array(
			'assort'   => "P",
			'descript' => "최근1주일 " . $memberCount . "건"
		);
		array_push($data, $data_info);
	}

	// 구독료 연체 건수
    $sql = "SELECT COUNT(idx) AS cmsCount FROM cms_pay WHERE memId = '$memId' AND (requestStatus != '0' OR payStatus = '5')";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$cmsCount = $row->cmsCount;

	if ($cmsCount > 0) {
		$count += 1;
		$data_info = array(
			'assort'   => "C",
			'descript' => "총 " . $cmsCount . "건"
		);
		array_push($data, $data_info);
	}

	// 공지사항 읽음 체크
    $sql = "SELECT idx, date_format(wdate, '%Y-%m-%d') as wdate FROM bbs WHERE bbsCode = 'N_01' ORDER BY idx DESC LIMIT 1";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$noticeIdx  = $row->idx;
	$noticeDate = $row->wdate;

	$sql = "SELECT ifnull(count(idx),0) as noticeCount FROM bbs_view WHERE bbsIdx = '$noticeIdx' and memId = '$memId'";
	$result2 = $connect->query($sql);
	$row2 = mysqli_fetch_object($result2);
	$noticeCount = $row2->noticeCount;

	if ($noticeCount == 0) {
		$count += 1;
		$data_info = array(
			'assort'   => "N",
			'descript' => $noticeDate
		);
		array_push($data, $data_info);
	}

	// 문의및답변 읽음 체크
    $sql = "SELECT idx, date_format(wdate, '%Y-%m-%d') as wdate FROM bbs WHERE memId = '$memId' and bbsCode = 'Q_01' ORDER BY idx DESC LIMIT 1";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$questionIdx  = $row->idx;
		$questionDate = $row->wdate;

		$sql = "SELECT ifnull(count(idx),0) as questionCount FROM bbs_view WHERE bbsIdx = '$questionIdx' and memId = '$memId'";
		$result2 = $connect->query($sql);
		$row2 = mysqli_fetch_object($result2);
		$questionCount = $row2->questionCount;

		if ($questionCount == 0) {
			$count += 1;
			$data_info = array(
				'assort'   => "Q",
				'descript' => $questionDate
			);
			array_push($data, $data_info);
		}
	}

	$response = array(
		'result' => "0",
		'count'  => $count,
		'data'   => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
