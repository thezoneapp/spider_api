<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰신청 > 제휴카드할인 > 목록
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> telecom:        통신사
	* parameter ==> useYn:          사용여부
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchValue = trim($input_data->{'searchValue'});
	$telecom     = $input_data->{'telecom'};
	$useYn       = $input_data->{'useYn'};

	//$telecom    = $telecom->{'code'};
	//$useYn      = $useYn->{'code'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	// 통신사
	$telecomValue = getCheckedToString($telecom);
	// 사용여부
	$useValue = getCheckedToString($useYn);

	if ($searchValue != "") $search_sql = "and (cardCode like '%$searchValue%' or cardName like '%$searchValue%') ";
	else $search_sql = "";

	if ($telecomValue == null || $telecomValue == "") $telecom_sql = "";
	else $telecom_sql = "and telecom IN ($telecomValue) ";

	if ($useValue == null || $useValue == "") $useYn_sql = "";
	else $useYn_sql = "and useYn IN ($useValue) ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM hp_alliance_card WHERE idx > 0 $search_sql $telecom_sql $useYn_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 데이타 검색 
	$data = array();

    $sql = "SELECT no, idx, cardCode, cardName, telecom, usePrice, discountPrice, thumbnail, cardExplain, useYn 
	        FROM ( select @a:=@a+1 no, idx, cardCode, cardName, telecom, usePrice, discountPrice, thumbnail, cardExplain, useYn 
		           from hp_alliance_card, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $telecom_sql $useYn_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecom = selected_object($row[telecom], $arrTelecomAssort);
			$useYn = selected_object($row[useYn], $arrUseAssort);

			$data_info = array(
				'no'            => $row[no],
				'idx'           => $row[idx],
				'cardCode'      => $row[cardCode],
				'cardName'      => $row[cardName],
				'telecom'       => $telecom,
				'usePrice'      => number_format($row[usePrice]),
				'discountPrice' => number_format($row[discountPrice]),
				'thumbnail'     => $row[thumbnail],
				'cardExplain'   => $row[cardExplain],
				'useYn'         => $useYn,
				'isChecked'     => false,
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
		'telecomOptions' => array_all_add($arrTelecomAssort),
		'useOptions'     => array_all_add($arrUseAssort),
		'data'           => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
