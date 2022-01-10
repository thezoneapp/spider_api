<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 모델 목록
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> makerCode:      제조사코드
	* parameter ==> telecom:        통신사코드
	* parameter ==> useYn:          사용여부
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$page       = $input_data->{'page'};
	$rows       = $input_data->{'rows'};
	$makerCode  = $input_data->{'makerCode'};
	$telecom    = $input_data->{'telecom'};
	$useYn      = $input_data->{'useYn'};

	$makerCode  = $makerCode->{'code'};
	$telecom    = $telecom->{'code'};
	$useYn      = $useYn->{'code'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($makerCode == null || $makerCode == "") $maker_sql = "";
	else $maker_sql = "and makerCode = '$makerCode' ";

	if ($telecom == null || $telecom == "") $telecom_sql = "";
	else $telecom_sql = "and telecom = '$telecom' ";

	if ($useYn === null || $useYn=== "") $useYn_sql = "";
	else $useYn_sql = "and useYn = '$useYn' ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM hp_model WHERE idx > 0 $maker_sql $telecom_sql $useYn_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT no, idx, modelCode, modelName, telecom, makerCode, useYn 
	        FROM ( select @a:=@a+1 no, idx, modelCode, modelName, telecom, makerCode, useYn 
		           from hp_model, (select @a:= 0) AS a 
		           where idx > 0 $maker_sql $telecom_sql $useYn_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecomName = selected_object($row[telecom], $arrTelecomAssort3);
			$maker = selected_object($row[makerCode], $arrMakerAssort);
			$useYn = selected_object($row[useYn], $arrUseAssort);

			$data_info = array(
				'no'        => $row[no],
				'idx'       => $row[idx],
				'modelCode' => $row[modelCode],
				'modelName' => $row[modelName],
				'maker'     => $maker,
				'telecom'   => $telecomName,
				'useYn'     => $useYn,
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_ok = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_ok = "1";
	}

	$response = array(
		'result'         => $result_ok,
		'rowTotal'       => $total,
		'pageCount'      => $pageCount,
		'makerOptions'   => $arrMakerAssort,
		'telecomOptions' => $arrTelecomAssort3,
		'useOptions'     => $arrUseAssort,
		'data'           => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
