<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 포인트 세부내용 수정
	* parameter ==> mode:     insert(추가), update(수정)
	* parameter ==> idx:      수정할 레코드 id
	* parameter ==> memId:    회원ID
	* parameter ==> memName:  회원명
	* parameter ==> assort:   구분
	* parameter ==> descript: 적요
	* parameter ==> point:    포인트
	* parameter ==> wdate:    등록일자
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$mode     = $input_data->{'mode'};
	$idx      = $input_data->{'idx'};
	$memId    = $input_data->{'memId'};
	$memName  = $input_data->{'memName'};
	$assort   = $input_data->{'assort'};
	$descript = $input_data->{'descript'};
	$point    = $input_data->{'point'};
	$wdate    = $input_data->{'wdate'};

	$assort   = $assort->{'code'};
	$point    = str_replace(",", "", $point);

	if ($point == "") $point = "0";
	if ($wdate == "") $wdate = date("Y-m-d His");;

	if ($mode == "insert") {
		$sql = "INSERT INTO point (memId, memName, assort, descript, point, wdate)
		                   VALUES ('$memId', '$memName', '$assort', '$descript', '$point', '$wdate')";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_statue = "0";
		$result_message = "등록하였습니다.";

	} else {
		$sql = "UPDATE point SET memId    = '$memId', 
								 memName  = '$memName', 
								 assort   = '$assort', 
								 descript = '$descript', 
								 point    = '$point', 
								 wdate    = '$wdate' 
				WHERE idx = '$idx'";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_statue = "0";
		$result_message = "변경하였습니다.";
	}

	$response = array(
		'result'    => $result_statue,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>