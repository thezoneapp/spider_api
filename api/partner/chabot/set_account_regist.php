<?
	// *********************************************************************************************************************************
	// *                                      다이렉트보험(차봇)    회원가입                                                               *
	// *********************************************************************************************************************************
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/chabot.php";

	/*
	* parameter ==> memId: 회원 아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId = $input_data->{'memId'};
	
	//$memId = "a27233377";

	// 회원정보
	$sql = "SELECT memName, hpNo, insuId FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$insuId  = $row->insuId;
		$memName = $row->memName;

		if ($row->hpNo !== "") {
			$hpNo = aes_decode($row->hpNo);
			$hpNo = str_replace("-", "", $hpNo);
		} else $hpNo = "";


		// 기존 회원삭제 --> 차후 삭제예정
		$body = Array(
			"coCode"     => $coCode, 
			"mode"       => "external",
			"coUserKey"  => $memId,
			"dealerCode" => $insuId,
		);

		//memberOut($body);

		// 회원등록
		$body = Array(
			"coCode"     => $coCode, 
			"mode"       => "regist",
			"dealerName" => $memName,
			"mobile"     => $hpNo,
			"coUserKey"  => $memId,
			"orgInfo"    => "",
		);

		// 회원등록 함수 호출 /inc/chabot.php
		$response = memberRegist($body);
		$response = json_decode($response);

		$result_status  = $response->{'result'};
		$result_message = $response->{'message'};
		$insuId         = $response->{'insuId'};

		if ($insuId != "") {
			$sql = "UPDATE member SET insuId = '$insuId' WHERE memId = '$memId'";
			$connect->query($sql);

			$result_status  = "0";
			$result_message = $response->{'message'};

		} else {
			$result_status  = $response->{'result'};
			$result_message = $response->{'message'};
		}

	} else {
		$result_status = "1";
		$result_message = "회원정보가 존재하지 않습니다.";
	}

	$response = array(
		'result'   => $result_status,
		'message'  => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>