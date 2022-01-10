<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 이벤트 > 휴대폰신청 > 채널목록
	* parameter
		page:        해당페이지
		rows:        페이지당 행의 갯수
		searchValue: 검색값
	*/

	$back_data     = json_decode(file_get_contents('php://input'));
	$page          = $back_data->{'page'};
	$rows          = $back_data->{'rows'};
	$searchValue   = trim($back_data->{'searchValue'});

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($searchValue == "") $search_sql = "";
	else $search_sql = "and (channelName like '%$searchValue%') ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM hp_event_channel WHERE idx > 0 $search_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT @a:=@a+1 no, idx, channelName, dueDate, useYn 
	        FROM (SELECT idx, channelName, dueDate, useYn 
				  FROM hp_event_channel 
			      WHERE idx > 0 $search_sql 
		   		  ORDER BY idx DESC 
		         ) m, (select @a:= 0) as a 
		    ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'mode'        => "update",
				'no'          => $row[no],
				'idx'         => $row[idx],
			    'channelName' => $row[channelName],
				'dueDate'     => $row[dueDate],
				'useYn'       => $row[useYn],
				'isChecked'   => false,
			);
			array_push($data, $data_info);
			$no--;
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	// 최종
	$response = array(
		'result'     => $result_status,
		'rowTotal'   => $total,
		'pageCount'  => $pageCount,
		'useOptions' => $arrUseAssort,
		'data'       => $data
    );
//print_r($response);
//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
