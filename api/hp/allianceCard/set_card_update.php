<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰신청 > 제휴카드할인 > 목록 > 상세정보 > 추가/수정
	* parameter ==> mode:          insert(추가), update(수정)
	* parameter ==> idx:           수정할 레코드 id
	* parameter ==> cardCode:      카드코드
	* parameter ==> cardName:      카드명
	* parameter ==> telecom:       통신사
	* parameter ==> usePrice:      사용실적
	* parameter ==> discountPrice: 월-할인금액
	* parameter ==> thumbnail:     썸네일
	* parameter ==> cardExplain:   카드설명
	* parameter ==> useYn:         사용여부
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$mode          = $data_back->{'mode'};
	$idx           = $data_back->{'idx'};
	$cardCode      = $data_back->{'cardCode'};
	$cardName      = $data_back->{'cardName'};
	$telecom       = $data_back->{'telecom'};
	$usePrice      = $data_back->{'usePrice'};
	$discountPrice = $data_back->{'discountPrice'};
	$thumbnail     = $data_back->{'thumbnail'};
	$cardExplain   = $data_back->{'cardExplain'};
	$useYn         = $data_back->{'useYn'};

	//$telecom       = $telecom->{'code'};

	if ($mode == "insert") {
		$sql = "SELECT idx FROM hp_alliance_card WHERE cardCode = '$cardCode'";
		$result = $connect->query($sql);

	    if ($result->num_rows == 0) {
			if ($serviceCode == "") {
				$sql = "SELECT ifnull(max(idx),0) + 1 AS maxIdx FROM hp_alliance_card";
				$result2 = $connect->query($sql);
				$row2 = mysqli_fetch_object($result2);
				$cardCode = $telecom . "-" . $row2->maxIdx;
			}

			$sql = "INSERT INTO hp_alliance_card (cardCode, cardName, telecom, usePrice, discountPrice, thumbnail, cardExplain, useYn)
							            VALUES ('$cardCode', '$cardName', '$telecom', '$usePrice', '$discountPrice', '$thumbnail', '$cardExplain', '$useYn')";
			$connect->query($sql);

			// 성공 결과를 반환합니다.
			$result_status = "0";
			$result_message = "등록하였습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "중복된 '카드코드'입니다.";
		}

	} else {
		$sql = "UPDATE hp_alliance_card SET cardCode = '$cardCode',
		                                    cardName = '$cardName', 
											telecom = '$telecom', 
								            usePrice = '$usePrice', 
								            discountPrice = '$discountPrice', 
									        thumbnail = '$thumbnail', 
									        cardExplain = '$cardExplain', 
								            useYn = '$useYn' 
						WHERE idx = '$idx'";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "변경하였습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>