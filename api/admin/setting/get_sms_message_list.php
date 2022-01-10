<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* SMS 메세지 목록
	* parameter ==> page:     해당페이지
	* parameter ==> rows:     페이지당 행의 갯수
	* parameter ==> assort:   구분
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$page       = $input_data->{'page'};
	$rows       = $input_data->{'rows'};
	$assort     = $input_data->{'assort'};

	$assort     = $assort->{'code'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($assort == null || $assort == "") $assort_sql = "";
	else $assort_sql = "and assort = '$assort' ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM sms_message WHERE idx > 0 $assort_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 데이타 검색 
	$data = array();
	$sql = "SELECT no, idx, assort, code, subject 
	        FROM ( select @a:=@a+1 no, idx, assort, code, subject 
		           from sms_message, (select @a:= 0) AS a  
		           where idx > 0 $assort_sql 
				   order by assort asc, code asc 
		         ) t 
			ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$assort = selected_object($row[assort], $arrSmsAssort);

			$data_info = array(
				'no'      => $row[no],
				'idx'     => $row[idx],
				'assort'  => $assort,
				'code'    => $row[code],
				'subject' => $row[subject],
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result = "1";
	}

	$response = array(
		'result'        => $result,
		'rowTotal'      => $total,
		'pageCount'     => $pageCount,
		'assortOptions' => $arrSmsAssort,
		'data'          => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>