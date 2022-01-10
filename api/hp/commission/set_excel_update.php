<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../PHPExcel-1.8/Classes/PHPExcel.php";

	/*
	* 휴대폰 > 수수료 > 엑세등록
	* parameter
		excelFile: 엑셀파일명
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$excelFile = $input_data->{'excelFile'};

	//$excelFile = "abc_1.xlsx";

	// 파일의 저장형식이 utf-8일 경우 한글파일 이름은 깨지므로 euc-kr로 변환해준다.
	$excelFile = iconv("UTF-8", "EUC-KR", $excelFile);

	// 기존 자료 삭제
	$sql = "DELETE FROM hp_commi";
	$connect->query($sql);

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
			$modelCode     = $objWorksheet->getCell('A' . $i)->getValue(); // A열 > 모델코드
			$telecom       = $objWorksheet->getCell('B' . $i)->getValue(); // B열 > 통신사
            $priceNew_S    = $objWorksheet->getCell('C' . $i)->getValue(); // C열 > 공시지원 > 신규
            $priceMnp_S    = $objWorksheet->getCell('D' . $i)->getValue(); // D열 > 공시지원 > 번호이동
            $priceChange_S = $objWorksheet->getCell('E' . $i)->getValue(); // E열 > 공시지원 > 기기변경
            $priceNew_C    = $objWorksheet->getCell('F' . $i)->getValue(); // F열 > 선택양정 > 신규
            $priceMnp_C    = $objWorksheet->getCell('G' . $i)->getValue(); // G열 > 선택양정 > 번호이동
            $priceChange_C = $objWorksheet->getCell('H' . $i)->getValue(); // H열 > 선택양정 > 기기변경
            //$reg_date = $objWorksheet->getCell('E' . $i)->getValue(); // E열
            //$reg_date = PHPExcel_Style_NumberFormat::toFormattedString($reg_date, 'YYYY-MM-DD'); // 날짜 형태의 셀을 읽을때는 toFormattedString를 사용한다.
			// 공시지원
			$sql = "INSERT INTO hp_commi (telecom, assortCode, modelCode, priceNew, priceMnp, priceChange, useYn, wdate)
			    		          VALUES ('$telecom', 'S', '$modelCode', '$priceNew_S', '$priceMnp_S', '$priceChange_S', 'Y', now())";
			$connect->query($sql);

			// 선택약정
			$sql = "INSERT INTO hp_commi (telecom, assortCode, modelCode, priceNew, priceMnp, priceChange, useYn, wdate)
			    		          VALUES ('$telecom', 'C', '$modelCode', '$priceNew_C', '$priceMnp_C', '$priceChange_C', 'Y', now())";
			$connect->query($sql);
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