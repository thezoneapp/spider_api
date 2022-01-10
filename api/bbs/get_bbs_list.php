<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 게시글 목록
	* parameter ==> memId:          조회자ID
	* parameter ==> bbsCode:        게시판코드
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> bbsCode:        게시판 코드
	* parameter ==> searchKey:      검색항목
	* parameter ==> searchValue:    검색값
	* parameter ==> minDate:        등록일자
	* parameter ==> maxDate:        등록일자
	*/
	$input_data     = json_decode(file_get_contents('php://input'));
	$page           = $input_data->{'page'};
	$rows           = $input_data->{'rows'};
	$memId          = $input_data->{'memId'};
	$userId         = $input_data->{'userId'};
	$bbsCode        = $input_data->{'bbsCode'};
	$assortCode     = $input_data->{'assortCode'};
	$searchKey      = $input_data->{'searchKey'};
	$searchValue    = trim($input_data->{'searchValue'});
	$minDate        = $input_data->{'minDate'};
	$maxDate        = $input_data->{'maxDate'};

	$searchKey      = $searchKey->{'code'};

	if (is_object($bbsCode)) $bbsCode = $bbsCode->{'code'};
	if (is_object($assortCode)) $assortCode = $assortCode->{'code'};

    $sql = "SELECT auth 
			FROM ( SELECT id, auth FROM admin
                   union 
                   SELECT memId as id, memAssort AS auth FROM member
				 ) m 
			WHERE id = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$authAssort = $row->auth; // 관리자 또는 회원

	} else {
		$authAssort = "N"; // 비회원
	}

	// 게시판 정보
	$sql = "SELECT authAdmin, authPartner, authMember, authNone
			FROM bbs_manager 
			WHERE bbsCode = '$bbsCode'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($authAssort == "A") $arrAuth = explode(",", $row->authAdmin);
		else if ($authAssort == "P") $arrAuth = explode(",", $row->authPartner);
		else if ($authAssort == "M" || $authAssort == "S") $arrAuth = explode(",", $row->authMember);
		else if ($authAssort == "N") $arrAuth = explode(",", $row->authNone);

		if ($arrAuth[0] == "Y") $authWrite = true;
		else $authWrite = false;

		if ($arrAuth[2] == "Y") $authDelete = true;
		else $authDelete = false;

	} else {
		// 관리자이면 쓰기/삭제권한
		if ($authAssort == "A") {
			$authWrite = true;
			$authDelete = true;
		} else {
			$authWrite = false;
			$authDelete = false;
		}
	}

	// 검색조건
	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($searchKey == null || $searchKey == "") {
		if ($searchValue !== "") $search_sql = "and (memId like '%$searchValue%' or memName like '%$searchValue%' or subject like '%$searchValue%') ";
		else $search_sql = "";
	} else $search_sql = "and $searchKey like '%$searchValue%' ";

	if ($bbsCode == null || $bbsCode == "") $bbsCode_sql = "";
	else $bbsCode_sql = "and bbsCode = '$bbsCode' ";

	if ($assortCode == null || $assortCode == "") $assort_sql = "";
	else $assort_sql = "and assortCode = '$assortCode' ";

	if ($memId == null || $memId == "") $memId_sql = "";
	else $memId_sql = "and memId = '$memId' ";

	if ($maxDate == null || $maxDate == "") $date_sql = "";
	else $date_sql = "and (date_format(wdate, '%Y-%m-%d') >= '$minDate' and date_format(wdate, '%Y-%m-%d') <= '$maxDate') ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM bbs WHERE parentIdx = 0 $memId_sql $assort_sql $search_sql $bbsCode_sql $replyStatus_sql $date_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, memId, memName, assortCode, assort2Code, subject, thumbnail, score, replyStatus, replyCount, viewCount, wdate 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, assortCode, assort2Code, subject, thumbnail, score, replyStatus, replyCount, viewCount, date_format(wdate, '%Y-%m-%d') as wdate 
		           from bbs, (select @a:= 0) AS a 
		           where parentIdx = 0 $memId_sql $assort_sql $search_sql $bbsCode_sql $replyStatus_sql $date_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$bbsIdx = $row[idx];
			$assortCode = $row[assortCode];

			if ($row[replyStatus] == "Y") $replyName = "답변완료";
			else $replyName = "접수";

			// 대분류정보
			$sql = "SELECT assortName FROM bbs_assort WHERE bbsCode = '$bbsCode' and assortCode = '$assortCode' and depthNo = '1'";
			$result2 = $connect->query($sql);
			$row2 = mysqli_fetch_object($result2);
			$assortName = $row2->assortName;

			// 읽음 여부 체크
			$sql = "SELECT count(idx) AS newCheck FROM bbs_view WHERE bbsIdx = '$bbsIdx' AND memId = '$userId'";
			$result2 = $connect->query($sql);
			$row2 = mysqli_fetch_object($result2);

			if ($row2->newCheck == 0) $newMark = true;
			else $newMark = false;

			$data_info = array(
				'no'          => $row[no],
				'idx'         => $row[idx],
				'memId'       => $row[memId],
				'memName'     => $row[memName],
				'assortName'  => $assortName,
				'subject'     => $row[subject],
				'thumbnail'   => $row[thumbnail],
				'score'       => $row[score],
				'replyStatus' => $row[replyStatus],
				'replyName'   => $replyName,
				'replyCount'  => $row[replyCount],
				'viewCount'   => $row[viewCount],
				'wdate'       => $row[wdate],
				'newMark'     => $newMark,
				'isChecked'   => false,
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	// 게시판구분
	$arrBbsOptions = array();
    $sql = "SELECT bbsCode, title FROM bbs_manager";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$code_info = array(
				'code'    => $row[bbsCode],
				'name'    => $row[title],
			);
			array_push($arrBbsOptions, $code_info);
		}
	}

	// 분류 구분
	$assortOptions = array();
    $sql = "SELECT assortCode, assortName FROM bbs_assort WHERE bbsCode = '$bbsCode' and depthNo = '1'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$assortCode = $row[assortCode];
			$assortName = $row[assortName];

			// 소분류 구분
			$smallOptions = array();
			$sql = "SELECT assortCode, assortName FROM bbs_assort WHERE bbsCode = '$bbsCode' and depthNo = '2' and parentCode = '$assortCode'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					$assort_info = array(
						'code' => $row2[assortCode],
						'name' => $row2[assortName],
					);
					array_push($smallOptions, $assort_info);
				}
			}

			$assort_info = array(
				'code'         => $assortCode,
				'name'         => $assortName,
				'smallOptions' => $smallOptions,
			);
			array_push($assortOptions, $assort_info);
		}
	}

	$response = array(
		'result'        => $result_status,
		'rowTotal'      => $total,
		'pageCount'     => $pageCount,
		'searchOptions' => $arrSearchOption,
		'bbsOptions'    => $arrBbsOptions,
		'assortOptions' => $assortOptions,
		'data'          => $data,
		'authWrite'     => $authWrite,
		'authDelete'    => $authDelete,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
