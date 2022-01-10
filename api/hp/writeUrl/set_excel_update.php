<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../PHPExcel-1.8/Classes/PHPExcel.php";

	/*
	* 관리자 > 휴대폰신청 > 가입신청서URL > 목록 > 엑셀업로드
	* parameter
		telecom: 통신사코드
		excelFile: 엑셀파일명
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$telecom   = $input_data->{'telecom'};
	$excelFile = $input_data->{'excelFile'};

	$telecom   = $telecom->{'code'};

	//$telecom   = "K";
	//$excelFile = "211013140813.xlsx";

	// 파일의 저장형식이 utf-8일 경우 한글파일 이름은 깨지므로 euc-kr로 변환해준다.
	//$excelFile = iconv("UTF-8", "EUC-KR", $excelFile);

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

		for ($i = 3 ; $i <= $maxRow ; $i++) {
			$modelCode      = $objWorksheet->getCell('A' . $i)->getValue(); // A열
            $requestAssorts = $objWorksheet->getCell('B' . $i)->getValue(); // B열
            $installments   = $objWorksheet->getCell('C' . $i)->getValue(); // C열
            $discountTypes  = $objWorksheet->getCell('D' . $i)->getValue(); // D열
            $writeUrl       = $objWorksheet->getCell('E' . $i)->getValue(); // E열

			$arrRequestAssort = explode("/", $requestAssorts);
			$arrInstallment = explode("/", $installments);
			$arrDiscountTypes = explode("/", $discountTypes);

			for ($i = 0; $i < count($arrRequestAssort); $i++) {
				$requestAssort = $arrRequestAssort[$i];

				for ($j = 0; $j < count($arrInstallment); $j++) {
					$installment = $arrInstallment[$j];

					for ($n = 0; $n < count($arrDiscountTypes); $n++) {
						$discountType = $arrDiscountTypes[$n];

						$sql = "UPDATE hp_write_url SET writeUrl = '$writeUrl' 
									WHERE modelCode = '$modelCode' and telecom = '$telecom' and requestAssort = '$requestAssort' and installment = '$installment' and discountType = '$discountType'";
						$connect->query($sql);
					}
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