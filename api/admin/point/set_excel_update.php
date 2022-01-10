<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/barobill.php";
	include '../../../api/barobill/BaroService_TI.php';
	include "../../../PHPExcel-1.8/Classes/PHPExcel.php";

	/*
	* 포인트관리 > 출금요청목록 > 엑세등록
	* parameter ==> excelFile: 엑셀파일명
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$excelFile = $data_back->{'excelFile'};

	// 엑셀파일 정보
	$fileName = $_SERVER['DOCUMENT_ROOT']."/upload/excel/" . $excelFile;

	$objPHPExcel = new PHPExcel();
	
	require_once "../../../PHPExcel-1.8/Classes/PHPExcel/IOFactory.php"; // IOFactory.php을 불러와야 하며, 경로는 사용자의 설정에 맞게 수정해야 한다.

	try {
		// 업로드 된 엑셀 형식에 맞는 Reader객체를 만든다.
	    $objReader = PHPExcel_IOFactory::createReaderForFile($fileName);

		// 읽기전용으로 설정
	    $objReader->setReadDataOnly(true);

		// 엑셀파일을 읽는다
	    $objExcel = $objReader->load($fileName);

	    // 첫번째 시트를 선택
		$objExcel->setActiveSheetIndex(0);
	    $objWorksheet = $objExcel->getActiveSheet();
	    $rowIterator = $objWorksheet->getRowIterator();

		foreach ($rowIterator as $row) { // 모든 행에 대해서
	        $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); 
	    }

		$maxRow = $objWorksheet->getHighestRow();
		
		$tergetAssort = "C"; // 현금인출 세금계산서
		$assort = "OC";      // 포인트구분: 현금인출

		for ($i = 2 ; $i <= $maxRow ; $i++) {
			$idx         = $objWorksheet->getCell('A' . $i)->getValue(); // A열 > idx
			$memId       = $objWorksheet->getCell('B' . $i)->getValue(); // B열 > 회원ID
			$memName     = $objWorksheet->getCell('C' . $i)->getValue(); // C열 > 회원명
			$point       = $objWorksheet->getCell('D' . $i)->getValue(); // D열 > 사용포인트
			$cash        = $objWorksheet->getCell('F' . $i)->getValue(); // F열 > 지급액
			$taxAssort   = $objWorksheet->getCell('I' . $i)->getValue(); // I열 > 사업자구분
			$paymentDate = $objWorksheet->getCell('N' . $i)->getValue(); // N열 > 입금일자
            $requestDate = $objWorksheet->getCell('P' . $i)->getValue(); // P열 > 등록일자

            $paymentDate = PHPExcel_Style_NumberFormat::toFormattedString($paymentDate, 'YYYY-MM-DD'); 

			if ($paymentDate != null && $paymentDate != "") {
				// 입금완료처리
				$sql = "UPDATE cash_request SET paymentDate = '$paymentDate', status = '9' WHERE idx = '$idx'";
				$connect->query($sql);

				$sql = "SELECT idx FROM point WHERE cashIdx = '$idx'";
				$result = $connect->query($sql);
				$total = $result->num_rows;

				//error_log ($total, 3, "/home/spiderfla/upload/doc/debug.log");

				if ($total == 0) {
					$descript = "신청일자: " . $requestDate;
					$point = str_replace(",", "", $point);
					$point = 0 - $point;
					$sql = "INSERT INTO point (memId, memName, assort, descript, point, cashIdx, wdate)
									   VALUES ('$memId', '$memName', '$assort', '$descript', '$point', '$idx', now())";
					$connect->query($sql);

					if ($taxAssort == "T") { // 사업자회원이면... 세금계산서 역발행
						$cash = str_replace(",", "", $cash);

						$params = array(
							'tergetAssort' => $tergetAssort,
							'issueType'    => "R",
							'modifyCode'   => "",
							'idx'          => $idx,
							'memId'        => $memId,
							'itemName'     => "판매수수료",
							'totalAmount'  => $cash,
						);

						// 바로빌 api호출
						reverseIssueTaxInvoice($params);
					}

				} else {
					$sql = "UPDATE point SET point = '$point' WHERE cashIdx = '$idx'";
					$connect->query($sql);
				}
			}
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "등록하였습니다.";

	} catch(exception $e) {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "오류가 발생하였습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>