<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 > 상품정보 > 목록
	* parameter 
		page:           해당페이지
		rows:           페이지당 행의 갯수
		makerCode:      제조사코드
		telecom:        통신사코드
		imtAssort:      통신망
		useYn:          사용여부
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchValue = trim($input_data->{'searchValue'});
	$telecom     = $input_data->{'telecom'};
	$imtAssort   = $input_data->{'imtAssort'};
	$useYn       = $input_data->{'useYn'};

	//$telecom    = $telecom->{'code'};
	//$useYn      = $useYn->{'code'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	$imtValue  = getCheckedToString($imtAssort);
	$useValue  = getCheckedToString($useYn);

	// 통신사 검색 조건
	$telecomValue = "";

	for ($i = 0; $i < count($telecom); $i++) {
		$item    = $telecom[$i];
		$code    = $item->code;
		$checked = $item->checked;

		if ($checked == true) {
			if ($telecomValue != "") $telecomValue .= " or ";
			$telecomValue .= "telecoms like '%" . $code . "%'";
		}
	}

	if ($searchValue == null || $searchValue == "") $search_sql = "";
	else $search_sql = $search_sql = "and (goodsCode like '%$searchValue%' or goodsName like '%$searchValue%') ";

	if ($telecomValue == null || $telecomValue == "") $telecom_sql = "";
	else $telecom_sql = "and (" . $telecomValue . ") ";

	if ($imtValue == null || $imtValue == "") $imt_sql = "";
	else $imt_sql = "and imtAssort IN ($imtValue) ";

	if ($useValue == null || $useValue == "") $useYn_sql = "";
	else $useYn_sql = "and useYn IN ($useValue) ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM hp_goods WHERE idx > 0 $search_sql $telecom_sql $imt_sql $useYn_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, goodsCode, goodsName, makerCode, telecoms, imtAssort, useYn 
	        FROM ( select @a:=@a+1 no, idx, goodsCode, goodsName, makerCode, telecoms, imtAssort, useYn 
		           from hp_goods, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $telecom_sql $imt_sql $useYn_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$imtAssort = selected_object($row[imtAssort], $arrImtAssort);
			$telecomName = selected_object($row[telecom], $arrTelecomAssort3);
			$maker = selected_object($row[makerCode], $arrMakerAssort);
			$useYn = selected_object($row[useYn], $arrUseAssort);

			$telecoms = "";
			$arrTelecoms = explode(",", $row[telecoms]);

			for ($i=0; $i < count($arrTelecoms); $i++) {
				$telecom = $arrTelecoms[$i];
				$telecomName = selected_object($telecom, $arrTelecomAssort);

				if ($telecoms != "") $telecoms .= " / ";
				$telecoms .= $telecomName;
			}

			$data_info = array(
				'no'          => $row[no],
				'idx'         => $row[idx],
				'goodsCode'   => $row[goodsCode],
				'goodsName'   => $row[goodsName],
				'maker'       => $maker,
				'telecoms'    => $telecoms,
				'imtAssort'   => $imtAssort,
				'useYn'       => $useYn,
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	$response = array(
		'result'         => $result_status,
		'rowTotal'       => $total,
		'pageCount'      => $pageCount,
		'telecomOptions' => array_all_add($arrTelecomAssort4),
		'imtOptions'     => array_all_add($arrImtAssort),
		'useOptions'     => array_all_add($arrUseAssort),
		'data'           => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
