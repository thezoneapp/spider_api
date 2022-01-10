<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 그룹관리 > 그룹 > 목록 > 상세정보
	* parameter
		page:            해당페이지
		rows:            페이지당 행의 갯수
		searchValue:     검색값
		joinMethod:      가입방식(R: 추천가입, O: 오픈가입)
		recommendOption: 추천가입옵션(D: 다이렉트, N: 네트워크)
		accurateMethod:  정산방식(E: 개별정산, B: 일괄정산)
	*/
	$input_data      = json_decode(file_get_contents('php://input'));
	$page            = $input_data->{'page'};
	$rows            = $input_data->{'rows'};
	$searchValue     = $input_data->{'searchValue'};
	$joinMethod      = $input_data->{'joinMethod'};
	$recommendOption = $input_data->{'recommendOption'};
	$accurateMethod  = $input_data->{'accurateMethod'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	// 가입방식
	$joinValue = getCheckedToString($joinMethod);
	// 추천가입옵션
	$recommendValue = getCheckedToString($recommendOption);
	// 정산방식
	$accurateValue = getCheckedToString($accurateMethod);

	if ($searchValue != "") $search_sql = "and (groupCode like '%$searchValue%' or groupName like '%$searchValue%') ";
	else $search_sql = "";

	if ($joinValue == null || $joinValue == "") $joinMethod_sql = "";
	else $joinMethod_sql = "and joinMethod IN ($joinValue) ";

	if ($recommendValue == null || $recommendValue == "") $recommendOption_sql = "";
	else $recommendOption_sql = "and recommendOption IN ($recommendValue) ";

	if ($accurateValue == null || $accurateValue == "") $accurateMethod_sql = "";
	else $accurateMethod_sql = "and accurateMethod IN ($accurateValue) ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM group WHERE idx > 0 $joinMethod_sql $recommendOption_sql $accurateMethod_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, groupCode, groupName, joinMethod, recommendOption, accurateMethod, 
				   companyName, ceoName, taxNo, email, telNo, useYn 
	        FROM ( select @a:=@a+1 no, idx, groupCode, groupName, joinMethod, recommendOption, accurateMethod, 
						  companyName, ceoName, taxNo, email, telNo, useYn 
		           from group_info, (select @a:= 0) AS a 
		           where idx > 0 $joinMethod_sql $recommendOption_sql $accurateMethod_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$joinName = selected_object($row[joinMethod], $arrJoinMethod);
			$recommendName = selected_object($row[recommendOption], $arrRecommendOption);
			$accurateName = selected_object($row[accurateMethod], $arrAccurateMethod);
			$useName = selected_object($row[useYn], $arrUseAssort);

			if ($row[email] != "") $row[email] = aes_decode($row[email]);
			if ($row[telNo] != "") $row[telNo] = aes_decode($row[telNo]);

			$data_info = array(
				'no'              => $row[no],
				'idx'             => $row[idx],
				'groupCode'       => $row[groupCode],
				'groupName'       => $row[groupName],
				'joinMethod'      => $joinName,
				'recommendOption' => $recommendName,
				'accurateMethod'  => $accurateName,
				'companyName'     => $row[companyName],
				'ceoName'         => $row[ceoName],
				'taxNo'           => $row[taxNo],
				'email'           => $row[email],
				'telNo'           => $row[telNo],
				'useYn'           => $useName,
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
		'result'            => $result_status,
		'rowTotal'          => $total,
		'pageCount'         => $pageCount,
		'joinMethodOptions' => $arrJoinMethod,
		'recommendOptions'  => $arrRecommendOption,
		'accurateOptions'   => $arrAccurateMethod,
		'useOptions'        => $arrUseAssort,
		'data'              => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
