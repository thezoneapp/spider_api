<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 수수료 정산대상 목록
	* parameter ==> memId:   회원ID
	* parameter ==> minDate: 발생기간 최소일자
	* parameter ==> maxDate: 발생기간 최대일자
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$memId   = $input_data->{'memId'};
	$minDate = $input_data->{'minDate'};
	$maxDate = $input_data->{'maxDate'};

	$minDate = str_replace(".", "-", $minDate);
	$maxDate = str_replace(".", "-", $maxDate);

	//$memId = "a55800982";
	//$minDate = "2021-04-01";
	//$maxDate = "2021-04-30";

	if ($memId == "" || $memId == null) $memId_sql = "";
	else $memId_sql = "and sponsId = '$memId' ";

	if ($maxDate == "" || $maxDate == null) $date_sql = "";
	else $date_sql = "and (DATE_FORMAT(wdate, '%Y-%m-%d') >= '$minDate' and DATE_FORMAT(wdate, '%Y-%m-%d') <= '$maxDate') ";

	/*
	CS => 개설비용
	MA => 월구독료(대)
	MS => 월구독료(판)
	R1 => 렌탈수수료
	R2 => 렌탈수수료(댑)
	P1 => 휴대폰신청
	A1 => 다이렉트보험
	*/
	$inAssort = "'CS','MA','MS','R2','S2','P1','A1'";

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT sponsId, sponsName 
			FROM commission 
			WHERE assort in ($inAssort) and ifnull(accurateStatus,'0') = '0' $memId_sql $date_sql 
			GROUP BY sponsId 
		    ORDER BY sponsName ASC";
	$result = $connect->query($sql);
	$total = $result->num_rows;

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
			    'memId'   => $row[sponsId],
			    'memName' => $row[sponsName],
			);
			array_push($data, $data_info);
		}
	}

	$response = array(
		'rowTotal' => $total,
		'data'     => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
