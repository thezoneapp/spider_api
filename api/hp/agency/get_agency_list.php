<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자페이지 > 휴대폰신청 > 업체관리 > 목록
	* parameter
	  page:           해당페이지
	  rows:           페이지당 행의 갯수
	  searchValue:    검색값
	  telecom:        통신사
	  useYn:          사용여부
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchValue = trim($input_data->{'searchValue'});
	$telecom     = $input_data->{'telecom'};
	$useYn       = $input_data->{'useYn'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($searchValue == null || $searchValue == "") $search_sql = "";
	else $search_sql = "and (agencyId like '%$searchValue%' or agencyName like '%$searchValue%') ";

	// 통신사
	$string = "";

	for ($i = 0; $i < count($telecom); $i++) {
		$item    = $telecom[$i];
		$code    = $item->code;
		$checked = $item->checked;

		if ($checked == true) {
			if ($string != "") $string .= " or ";
			$string .= "telecoms like '%" . $code . "%' ";
		}
	}

	if ($string == "") $telecom_sql = "";
	else $telecom_sql = "and (" . $string . ") ";

	// 사용여부
	$useYnValue = getCheckedToString($useYn);

	if ($useYnValue == null || $useYnValue == "") $use_sql = "";
	else $use_sql = "and useYn IN ($useYnValue) ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM hp_agency WHERE idx > 0 $search_sql $telecom_sql $use_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, agencyId, agencyName, telNo, hpNo, telecoms, useYn, wdate 
	        FROM ( select @a:=@a+1 no, idx, agencyId, agencyName, telNo, hpNo, telecoms, useYn, date_format(wdate, '%Y-%m-%d') as wdate 
		           from hp_agency, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $telecom_sql $use_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[telNo] !== "") $row[telNo] = aes_decode($row[telNo]);
			if ($row[hpNo] !== "") $row[hpNo] = aes_decode($row[hpNo]);

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
				'no'         => $row[no],
				'idx'        => $row[idx],
				'agencyId'   => $row[agencyId],
				'agencyName' => $row[agencyName],
				'telNo'      => $row[telNo],
				'hpNo'       => $row[hpNo],
				'telecoms'   => $telecoms,
				'useYn'      => $useYn,
				'wdate'      => $row[wdate],
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	// ********************************* 우선순위 **************************************************************
	$skSupportData = array();
	$skChargeData = array();
	$ktSupportData = array();
	$ktChargeData = array();
	$lgSupportData = array();
	$lgChargeData = array();

    $sql = "SELECT agencyId, agencyName, telecoms, sort_sk_s, sort_sk_c, sort_kt_s, sort_kt_c, sort_lg_s, sort_lg_c 
			FROM hp_agency 
			WHERE useYn = 'Y'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecoms = "";
			$arrTelecoms = explode(",", $row[telecoms]);

			for ($i=0; $i < count($arrTelecoms); $i++) {
				$telecom = $arrTelecoms[$i];
				$telecomName = selected_object($telecom, $arrTelecomAssort);

				if ($telecoms != "") $telecoms .= " / ";
				$telecoms .= $telecomName;
			}

			if (strpos($row[telecoms], "S") !== false) {
				// SK > 공시지원
				$data_info = array(
					'agencyId'   => $row[agencyId],
					'agencyName' => $row[agencyName],
					'telecom'    => $telecoms,
					'sortNo'     => $row[sort_sk_s],
				);
				array_push($skSupportData, $data_info);

				// SK > 요금할인
				$data_info = array(
					'agencyId'   => $row[agencyId],
					'agencyName' => $row[agencyName],
					'telecom'    => $telecoms,
					'sortNo'     => $row[sort_sk_c],
				);
				array_push($skChargeData, $data_info);
			}

			if (strpos($row[telecoms], "K") !== false) {
				// KT > 공시지원
				$data_info = array(
					'agencyId'   => $row[agencyId],
					'agencyName' => $row[agencyName],
					'telecom'    => $telecoms,
					'sortNo'     => $row[sort_kt_s],
				);
				array_push($ktSupportData, $data_info);

				// KT > 요금할인
				$data_info = array(
					'agencyId'   => $row[agencyId],
					'agencyName' => $row[agencyName],
					'telecom'    => $telecoms,
					'sortNo'     => $row[sort_kt_c],
				);
				array_push($ktChargeData, $data_info);
			}

			if (strpos($row[telecoms], "L") !== false) {
				// LG > 공시지원
				$data_info = array(
					'agencyId'   => $row[agencyId],
					'agencyName' => $row[agencyName],
					'telecom'    => $telecoms,
					'sortNo'     => $row[sort_lg_s],
				);
				array_push($lgSupportData, $data_info);

				// LG > 요금할인
				$data_info = array(
					'agencyId'   => $row[agencyId],
					'agencyName' => $row[agencyName],
					'telecom'    => $telecoms,
					'sortNo'     => $row[sort_lg_c],
				);
				array_push($lgChargeData, $data_info);
			}
		}
	}


	//*************************** Sort
	// SK
	usort($skSupportData, make_comparer(['sortNo', SORT_ASC]));
	usort($skChargeData, make_comparer(['sortNo', SORT_ASC]));

	$skData = array(
		'supportData' => $skSupportData,
		'chargeData'  => $skChargeData,
	);

	usort($skSupportData, make_comparer(['sortNo', SORT_ASC]));
	usort($skChargeData, make_comparer(['sortNo', SORT_ASC]));

	// KT
	usort($ktSupportData, make_comparer(['sortNo', SORT_ASC]));
	usort($ktChargeData, make_comparer(['sortNo', SORT_ASC]));

	$ktData = array(
		'supportData' => $ktSupportData,
		'chargeData'  => $ktChargeData,
	);

	// LG
	usort($lgSupportData, make_comparer(['sortNo', SORT_ASC]));
	usort($lgChargeData, make_comparer(['sortNo', SORT_ASC]));

	$lgData = array(
		'supportData' => $lgSupportData,
		'chargeData'  => $lgChargeData,
	);

	$sortData = array(
		'sk' => $skData,
		'kt' => $ktData,
		'lg' => $lgData,
	);

	// 검색항목
	$arrSearchOption = array(
		['code' => 'agencyId',       'name' => '업체ID'],
		['code' => 'agencyName',     'name' => '업체명'],
	);

	$response = array(
		'result'         => $result_status,
		'rowTotal'       => $total,
		'pageCount'      => $pageCount,
		'searchOptions'  => $arrSearchOption,
		'telecomOptions' => array_all_add($arrTelecomAssort),
		'useOptions'     => array_all_add($arrUseAssort),
		'data'           => $data,
		'sortData'       => $sortData,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
