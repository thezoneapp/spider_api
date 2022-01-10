<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 요금제 목록
	* parameter
		page:           해당페이지
		rows:           페이지당 행의 갯수
		searchValue:    검색값
		imtAssort:      통신망
		telecom:        통신사
		useYn:          사용여부
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchValue = trim($input_data->{'searchValue'});
	$imtAssort   = $input_data->{'imtAssort'};
	$telecom     = $input_data->{'telecom'};
	$useYn       = $input_data->{'useYn'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	// 통신망
	$imtValue = getCheckedToString($imtAssort);
	// 통신사
	$telecomValue = getCheckedToString($telecom);
	// 사용여부
	$useYnValue = getCheckedToString($useYn);

	if ($searchValue != "") $search_sql = "and (chargeCode like '%$searchValue%' or chargeName like '%$searchValue%') ";
	else $search_sql = "";

	if ($imtValue == null || $imtValue == "") $imt_sql = "";
	else $imt_sql = "and imtAssort IN ($imtValue) ";

	if ($telecomValue == null || $telecomValue == "") $telecom_sql = "";
	else $telecom_sql = "and telecom IN ($telecomValue) ";

	if ($useYnValue == null || $useYnValue == "") $use_sql = "";
	else $use_sql = "and useYn IN ($useYnValue) ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM hp_charge WHERE idx > 0 $search_sql $imt_sql $telecom_sql $useYn_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT no, idx, chargeCode, chargeName, chargePrice, discountPrice, imtAssort, telecom, useYn 
	        FROM ( select @a:=@a+1 no, idx, chargeCode, chargeName, chargePrice, discountPrice, imtAssort, telecom, useYn 
		           from hp_charge, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $imt_sql $telecom_sql $useYn_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$imtAssort = selected_object($row[imtAssort], $arrImtAssort);
			$telecom = selected_object($row[telecom], $arrTelecomAssort);
			$useYn = selected_object($row[useYn], $arrUseAssort);

			$data_info = array(
				'no'            => $row[no],
				'idx'           => $row[idx],
				'chargeCode'    => $row[chargeCode],
				'chargeName'    => $row[chargeName],
				'chargePrice'   => number_format($row[chargePrice]),
				'discountPrice' => number_format($row[discountPrice]),
				'imtAssort'     => $imtAssort,
				'telecom'       => $telecom,
				'useYn'         => $useYn,
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
		'imtOptions'     => array_all_add($arrImtAssort),
		'telecomOptions' => array_all_add($arrTelecomAssort),
		'useOptions'     => array_all_add($arrUseAssort),
		'data'           => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
