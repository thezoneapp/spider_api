<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 수수료 정산대상 목록
	* parameter ==> minDate: 발생기간 최소일자
	* parameter ==> maxDate: 발생기간 최대일자
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$minDate = $input_data->{'minDate'};
	$maxDate = $input_data->{'maxDate'};

	$minDate = str_replace(".", "-", $minDate);
	$maxDate = str_replace(".", "-", $maxDate);

	$minDate = "2020-10-01";
	$maxDate = "2020-10-31";

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT sponsId, sponsName 
			FROM commission 
			WHERE ifnull(accurateStatus,'0') = '0' and wdate >= '$minDate' and wdate <= '$maxDate' 
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
