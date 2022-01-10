<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 게시글 정보
	* parameter ==> bbsCode: 게시판코드
	* parameter ==> idx: 글 idx
	* parameter ==> memId: 조회자ID
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$bbsCode = $input_data->{'bbsCode'};
	$idx     = $input_data->{'idx'};
	$memId   = $input_data->{'memId'};
	
	//$bbsCode = "N_01";
	//$idx = 95;
	//$memId = "a27233377";

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

	// 관리자이면 수정권한
	if ($authAssort == "A") $authEdit = true;
	else $authEdit = false;

	// 게시판 정보
	$sql = "SELECT bm.thumbYn, bm.replyYn, bm.authAdmin, bm.authPartner, bm.authMember, bm.authNone
			FROM bbs b
				 INNER JOIN bbs_manager bm ON b.bbsCode = bm.bbsCode
			WHERE b.idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->thumbYn == "Y") $useThumb = true;
		else $useThumb = false;

		if ($row->replyYn == "Y") $useReply = true;
		else $useReply = false;

		if ($authAssort == "A") $arrAuth = explode(",", $row->authAdmin);
		else if ($authAssort == "P") $arrAuth = explode(",", $row->authPartner);
		else if ($authAssort == "M" || $authAssort == "S") $arrAuth = explode(",", $row->authMember);
		else if ($authAssort == "N") $arrAuth = explode(",", $row->authNone);

		if ($arrAuth[0] == "Y") $authWrite = true;
		else $authWrite = false;

		if ($arrAuth[1] == "Y") $authView = true;
		else $authView = false;

		if ($arrAuth[2] == "Y") $authDelete = true;
		else $authDelete = false;

		if ($arrAuth[3] == "Y") $authReply = true;
		else $authReply = false;

	} else {
		$useThumb = false;
		$useReply = false;
		$authWrite = false;
		$authView = false;
		$authDelete = false;
	}

	// 게시글 정보
    $sql = "SELECT b.idx, b.bbsCode, b.assortCode, b.assort2Code, bm.title as bbsName, b.memId, b.memName, b.hpNo, b.email, b.subject, b.content, b.thumbnail, b.score, date_format(b.wdate, '%Y.%m.%d %H:%i') as wdate 
	        FROM bbs b
			     inner join bbs_manager bm on b.bbsCode = bm.bbsCode 
			WHERE b.idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);
		if ($row->email !== "") $row->email = aes_decode($row->email);

		$bbsName     = $row->bbsName;
		$assortCode  = $row->assortCode;
		$assort2Code = $row->assort2Code;
		$memName     = $row->memName;
		$hpNo        = $row->hpNo;
		$email       = $row->email;
		$subject     = $row->subject;
		$content     = $row->content;
		$thumbnail   = $row->thumbnail;
		$score       = $row->score;
		$wdate       = $row->wdate;

		if ($row->memId == $memId) $authEdit = true; // 본인이 쓴글이면 수정권한

		// 분류 정보
		$assortName = "";
		$sql = "SELECT depthNo, assortName FROM bbs_assort WHERE bbsCode = '$bbsCode' and assortCode in ('$assortCode','$assort2Code')";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				if ($row2[depthNo] == "1") $assortName = $row2[assortName];
				else $assort2Name = $row2[assortName];
			}
		}

		// 이전글, 다음글 정보
		$navData = array();
		$sql = "SELECT nav, idx, memId, memName, subject, replyStatus, viewCount, replyCount, wdate 
				FROM ( SELECT '이전글' as nav, idx, memId, memName, subject, replyStatus, viewCount, replyCount, wdate 
					   FROM ( SELECT idx, memId, memName, subject, replyStatus, viewCount, replyCount, wdate 
							  FROM bbs
							  WHERE bbsCode = '$bbsCode' AND parentIdx = 0 and idx > '$idx' 
							  ORDER BY idx DESC
							  LIMIT 1
							) t1
						UNION
						SELECT '다음글' as nav, idx, memId, memName, subject, replyStatus, viewCount, replyCount, wdate 
						FROM ( SELECT idx, memId, memName, subject, replyStatus, viewCount, replyCount, wdate 
							   FROM bbs
							   WHERE bbsCode = '$bbsCode' AND parentIdx = 0 and idx < '$idx' 
							   ORDER BY idx DESC
							   LIMIT 1
							) t2
				) t";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				$nav_info = array(
					'nav'         => $row2[nav],
					'idx'         => $row2[idx],
					'memId'       => $row2[memId],
					'memName'     => $row2[memName],
					'subject'     => $row2[subject],
					'wdate'       => $row2[wdate],
				);
				array_push($navData, $nav_info);
			}
		}

		// 평점등록 체크
		$sql = "SELECT count(idx) as scoreCount FROM bbs_score WHERE bbsCode = '$bbsCode' and bbsIdx = '$idx' and memId = '$memId'";
		$result3 = $connect->query($sql);
		$row3 = mysqli_fetch_object($result3);
			
		if ($row3->scoreCount > 0) $scoreRegist = true;
		else $scoreRegist = false;

		// 읽음표시 등록
		$sql = "SELECT ifnull(count(idx),0) as viewCount FROM bbs_view WHERE bbsIdx = '$idx' and memId = '$memId'";
		$result3 = $connect->query($sql);
		$row3 = mysqli_fetch_object($result3);

		if ($row3->viewCount == 0) {
			$sql = "INSERT INTO bbs_view (bbsIdx, memId, wdate) 
			                      VALUES ('$idx', '$memId', now())";
			$connect->query($sql);
		}

		$data = array(
			'idx'         => $idx,
			'bbsCode'     => $bbsCode,
			'bbsName'     => $bbsName,
			'assortCode'  => $assortCode,
			'assortName'  => $assortName,
			'assort2Code' => $assort2Code,
			'assort2Name' => $assort2Name,
			'memId'       => $memId,
			'memName'     => $memName,
			'hpNo'        => $hpNo,
			'email'       => $email,
			'subject'     => $subject,
			'content'     => $content,
			'thumbnail'   => $thumbnail,
			'score'       => $score,
			'scoreRegist' => $scoreRegist,
			'wdate'       => $wdate,
			'navData'     => $navData,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

	} else {
		$data = array(
			'idx'         => "",
			'bbsCode'     => "",
			'bbsName'     => "",
			'assortCode'  => "",
			'assortName'  => "",
			'assort2Code' => "",
			'assort2Name' => "",
			'memId'       => "",
			'memName'     => "",
			'hpNo'        => "",
			'email'       => "",
			'subject'     => "",
			'content'     => "",
			'score'       => "0",
			'scoreRegist' => false,
			'thumbnail'   => "",
			'wdate'       => "",
			'navData'     => "",
		);
	}

	// 게시판구분
	$bbsOptions = array();
    $sql = "SELECT bbsCode, title FROM bbs_manager";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$code_info = array(
				'code'    => $row[bbsCode],
				'name'    => $row[title],
			);
			array_push($bbsOptions, $code_info);
		}
	}

	$bbsInfo = array(
		'title'      => $row->title,
		'useThumb'   => $useThumb,
		'useReply'   => $useReply,
		'authWrite'  => $authWrite,
		'authEdit'   => $authEdit,
		'authView'   => $authView,
		'authDelete' => $authDelete,
	);

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
		'data'          => $data,
		'bbsOptions'    => $bbsOptions,
		'assortOptions' => $assortOptions,
		'bbsInfo'       => $bbsInfo,
    );
//print_r($response);
//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>