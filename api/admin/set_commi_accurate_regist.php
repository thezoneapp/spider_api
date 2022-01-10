<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 수수료 정산서 등록
	* parameter ==> memId:   회원ID
	* parameter ==> memName: 회원명
	* parameter ==> minDate: 발생기간 최소일자
	* parameter ==> maxDate: 발생기간 최대일자
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId   = $input_data->{'memId'};
	$memName = $input_data->{'memName'};
	$minDate = $input_data->{'minDate'};
	$maxDate = $input_data->{'maxDate'};

	// 정산서 생성
	$wdate = date("Y-m-d");
	$commission = 0;
	$accurateStatus = "1"; // 정산중

	$sql = "INSERT INTO commi_accurate (memId, memName, minDate, maxDate, taxAssort, accurateStatus, wdate)
						        VALUES ('$memId', '$memName', '$minDate', '$maxDate', '$taxAssort', '$accurateStatus', '$wdate')";
	$connect->query($sql);

	// 정산서 번호 취득
    $sql = "SELECT idx FROM commi_accurate WHERE memId = '$memId' and wdate = '$wdate' LIMIT 1";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$accurateIdx = $row->idx;

	// 수수료 정산대상 검색
    $sql = "SELECT idx, memId, memName, assort, price, date_format(wdate, '%Y-%m-%d') as commiDate 
			FROM commission 
			WHERE accurateStatus = '0' and sponsId = '$memId' and wdate >= '$minDate 23:59' and wdate <= '$maxDate' 
		    ORDER BY idx ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$idx       = $row[idx];
			$memId     = $row[memId];
			$memName   = $row[memName];
			$assort    = $row[assort];
			$price     = $row[price];
			$commiDate = $row[commiDate];

			$commission += $price;

			// 정산서 세부내용 등록
			$sql = "INSERT INTO commi_accurate_detail (accurateIdx, commissionIdx, memId, memName, assort, price, commiDate, accurateStatus)
										       VALUES ('$accurateIdx', '$idx', '$memId', '$memName', '$assort', '$price', '$commiDate', '$accurateStatus')";
			$connect->query($sql);

			// 수수료목록의 정산서번호, 정산상태를 변경
			$sql = "UPDATE commission SET accurateIdx = '$accurateIdx', accurateStatus = '$accurateStatus' WHERE idx = '$idx'";
			$connect->query($sql);
		}

		// 장선서 목록의 금액 변경
		$sql = "UPDATE commi_accurate SET commission = '$commission', totalAmount = '$commission', accurateAmount = '$commission' WHERE idx = '$accurateIdx'";
		$connect->query($sql);
	}

	$response = array(
		'result' => "0"
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>