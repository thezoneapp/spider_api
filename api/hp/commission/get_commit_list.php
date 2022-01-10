<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 신청 > 수수료율 목록
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> telecom:        통신사
	* parameter ==> useYn:          사용여부
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$telecom     = $input_data->{'telecom'};
	$useYn       = $input_data->{'useYn'};

	$telecom     = $telecom->{'code'};
	$useYn       = $useYn->{'code'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($telecom == null || $telecom == "") $telecom_sql = "";
	else $telecom_sql = "and hc.telecom = '$telecom' ";

	if ($useYn == null || $useYn == "") $useYn_sql = "";
	else $useYn_sql = "and hc.useYn = '$useYn' ";

	// 전체 데이타 갯수
    $sql = "SELECT hc.idx 
			FROM hp_commit hc
                 LEFT OUTER JOIN hp_model hm ON hc.modelCode = hm.modelCode 
	        WHERE hc.idx > 0 $telecom_sql $useYn_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT no, idx, telecom, modelCode, modelName, priceNew, priceMnp, priceChange, useYn, wdate 
			FROM ( select @a:=@a+1 no, idx, telecom, modelCode, modelName, priceNew, priceMnp, priceChange, useYn, wdate 
				   from ( select hc.idx, hc.telecom, hc.modelCode, hm.modelName, hc.priceNew, hc.priceMnp, hc.priceChange, hc.useYn, date_format(wdate, '%Y-%m-%d') as wdate  
                          from hp_commit hc 
                               left outer join hp_model hm on hc.modelCode = hm.modelCode 
						  where hc.idx > 0 $telecom_sql $useYn_sql 
                        ) t1, (select @a:= 0) AS a 
                 ) t2 
            ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecom = selected_object($row[telecom], $arrTelecomAssort);
			$useYn = selected_object($row[useYn], $arrUseAssort);

			$data_info = array(
				'no'          => $row[no],
				'idx'         => $row[idx],
				'telecom'     => $telecom,
				'modelCode'   => $row[modelCode],
				'modelName'   => $row[modelName],
				'priceNew'    => number_format($row[priceNew]),
				'priceMnp'    => number_format($row[priceMnp]),
				'priceChange' => number_format($row[priceChange]),
				'useYn'       => $useYn,
				'wdate'       => $row[wdate],
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
		'message'         => $imtAssort2,
		'rowTotal'       => $total,
		'pageCount'      => $pageCount,
		'telecomOptions' => $arrTelecomAssort,
		'useOptions'     => $arrUseAssort,
		'data'           => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
