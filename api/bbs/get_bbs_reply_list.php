<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 댓글 목록
	* parameter ==> parentIdx: 부모글 idx
	* parameter ==> memId: 조회자ID
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$parentIdx  = $input_data->{'parentIdx'};
	$memId      = $input_data->{'memId'};

	//$parentIdx  = 36;
	//$memId      = "admin";

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
	$sql = "SELECT bm.authAdmin, bm.authPartner, bm.authMember, bm.authNone
			FROM bbs b
				 INNER JOIN bbs_manager bm ON b.bbsCode = bm.bbsCode
			WHERE b.idx = '$parentIdx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($authAssort == "A") $arrAuth = explode(",", $row->authAdmin);
		else if ($authAssort == "P") $arrAuth = explode(",", $row->authPartner);
		else if ($authAssort == "M" || $authAssort == "S") $arrAuth = explode(",", $row->authMember);
		else if ($authAssort == "N") $arrAuth = explode(",", $row->authNone);

		if ($arrAuth[2] == "Y") $adminAuthDelete = true;
		else $adminAuthDelete = false;

		if ($arrAuth[3] == "Y") $authReply = true;
		else $authReply = false;

	} else {
		$adminAuthDelete = false;
		$authReply = false;
	}

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, memId, memName, content, wdate 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, content, date_format(wdate, '%Y.%m.%d %H:%i') as wdate 
		           from bbs, (select @a:= 0) AS a 
		           where parentIdx = '$parentIdx' 
		         ) m 
			ORDER BY no DESC";
	$result = $connect->query($sql);
	$total = $result->num_rows;

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[memId] == $memId) $authDelete = true;
			else $authDelete = $adminAuthDelete;

			$data_info = array(
				'no'          => $row[no],
				'idx'         => $row[idx],
				'memId'       => $row[memId],
				'memName'     => $row[memName],
				'content'     => $row[content],
				'wdate'       => $row[wdate],
				'authDelete'  => $authDelete
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
		'result'    => $result_status,
		'rowTotal'  => $total,
		'data'      => $data,
		'authReply' => $authReply,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
