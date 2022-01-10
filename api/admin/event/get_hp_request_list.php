<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 이벤트 > 휴대폰신청 > 신청목록
	* parameter
		page:        해당페이지
		rows:        페이지당 행의 갯수
		channel:     유입채널
		minDate:     검색최소일자
		maxDate:     검색최대일자
		searchValue: 검색값
	*/

	$back_data     = json_decode(file_get_contents('php://input'));
	$page          = $back_data->{'page'};
	$rows          = $back_data->{'rows'};
	$channelIdx    = $back_data->{'channel'};
	$minDate       = $back_data->{'minDate'};
	$maxDate       = $back_data->{'maxDate'};
	$requestStatus = $back_data->{'requestStatus'};
	$searchValue   = trim($back_data->{'searchValue'});

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	$searchHpNo  = aes128encrypt($searchValue);

	// 유입채널
	$channelValue = getCheckedToString($channelIdx);
	// 신청상태
	$statusValue = getCheckedToString($requestStatus);

	if ($channelValue == null || $channelValue == "") $channel_sql = "";
	else $channel_sql = "and channelIdx IN ($channelValue) ";

	if ($statusValue == null || $statusValue == "") $status_sql = "";
	else $status_sql = "and requestStatus IN ($statusValue) ";

	if ($maxDate == "") $date_sql = "";
	else $date_sql = "and (date_format(wdate, '%Y-%m-%d') >= '$minDate' and date_format(wdate, '%Y-%m-%d') <= '$maxDate') ";

	if ($searchValue == "") $search_sql = "";
	else $search_sql = "and (custName like '%$searchValue%' or hpNo like '%$searchHpNo%') ";

	// 전체 데이타 갯수
    $sql = "SELECT her.idx 
			FROM hp_event_request her 
				 INNER JOIN hp_event_channel hec ON her.channelIdx = hec.idx 
			WHERE her.idx > 0 $search_sql $channel_sql $status_sql $date_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT @a:=@a+1 no, idx, channelName, custName, hpNo, useTelecom, modelName, requestStatus, adminMemo, wdate 
	        FROM (SELECT her.idx, hec.channelName, her.custName, her.hpNo, her.useTelecom, her.modelName, her.requestStatus, her.adminMemo, date_format(her.wdate, '%Y/%m/%d') as wdate 
				  FROM hp_event_request her 
				       INNER JOIN hp_event_channel hec ON her.channelIdx = hec.idx 
			      WHERE her.idx > 0 $search_sql $channel_sql $status_sql $date_sql 
		   		  ORDER BY idx DESC 
		         ) m, (select @a:= 0) as a 
		    ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[hpNo] != "") $row[hpNo] = aes_decode($row[hpNo]);

			$telecomName = selected_object($row[useTelecom], $arrTelecomAssort);
			$statusName = selected_object($row[requestStatus], $arrRequestStatus3);
			
			$data_info = array(
				'no'            => $row[no],
				'idx'           => $row[idx],
			    'channelName'   => $row[channelName],
				'custName'      => $row[custName],
				'hpNo'          => $row[hpNo],
				'telecom'       => $telecomName,
				'modelName'     => $row[modelName],
				'requestStatus' => $statusName,
				'adminMemo'     => $row[adminMemo],
				'wdate'         => $row[wdate],
				'isChecked'     => false,
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


	// 유입채널 정보 
	$channerOptions = array();
	$sql = "SELECT idx, channelName FROM hp_event_channel ORDER BY channelName ASC";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$dta_info = array(
				'code' => $row[idx],
				'name' => $row[channelName],
			);
			array_push($channerOptions, $dta_info);
		}
	}

	// 최종
	$response = array(
		'result'          => $result_status,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'channelOptions'  => array_all_add($channerOptions),
		'channel2Options' => $channerOptions,
		'statusOptions'   => array_all_add($arrRequestStatus3),
		'status2Options'  => $arrRequestStatus3,
		'data'            => $data
    );
//print_r($response);
//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
