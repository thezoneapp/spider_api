<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 이벤트 > 휴대폰신청 > 기본 정보
	* parameter
		page:     해당페이지
		rows:     페이지당 행의 갯수
		channel:  유입채널
	*/

	$back_data  = json_decode(file_get_contents('php://input'));
	$page       = $back_data->{'page'};
	$rows       = $back_data->{'rows'};
	$channelIdx = $back_data->{'channel'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	$channelIdx = "1";

	// 휴대폰 모델
	$modelData = array();
	$sql = "SELECT modelCode, modelName 
			FROM hp_event_model 
			WHERE useYn = 'Y' and channelIdx like '%$channelIdx%'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'modelCode' => $row[modelCode],
				'modelName' => $row[modelName],
			);
			array_push($modelData, $data_info);
		}
	}

	// 신청 목록
	// 전체 데이타 갯수
    $sql = "SELECT idx FROM hp_event_request ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 데이타 검색 
	$listData = array();
    $sql = "SELECT no, custName, hpNo, modelName, requestStatus, wdate 
	        FROM ( select @a:=@a+1 no, custName, hpNo, modelName, requestStatus, wdate 
		           from hp_event_request, (select @a:= 0) AS a 
		         ) t 
			ORDER BY wdate DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);
	$total = $result->num_rows;

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[custName] != "") {
				$len = mb_strlen($row[custName], "UTF-8");
				$row[custName] = mb_substr($row[custName], 0, 1, 'utf-8') . "*" . mb_substr($row[custName], 2, $len - 2, 'utf-8');
			}

			if ($row[hpNo] != "") {
				$row[hpNo] = aes_decode($row[hpNo]);
				$row[hpNo] = substr($row[hpNo], 0, 3) . "-xxxx-xxxx";
			}

			if ($row[requestStatus] == "0") $requestStatus = "접수중";
			else $requestStatus = "접수완료";

			$data_info = array(
				'no'            => $row[no],
				'custName'      => $row[custName],
				'hpNo'          => $row[hpNo],
				'modelName'     => $row[modelName],
				'requestStatus' => $requestStatus,
			);
			array_push($listData, $data_info);
		}
	}

	$data = array(
		'listData'       => $listData,
		'modelOptions'   => $modelData,
		'telecomOptions' => $arrTelecomAssort6,
	);

	$response = array(
		'result'  => $result_status,
		'data'    => $data,
	);
	
	//print_r($response);
	//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>