<?php
/**
 * CSVToExcelConverter
 */
class CSVToExcelConverter
{
	/**
	 * Read given csv file and write all rows to given xls file
	 * 
	 * @param string $csv_file Resource path of the csv file
	 * @param string $xls_file Resource path of the excel file
	 * @param string $csv_enc Encoding of the csv file, use utf8 if null
	 * @throws Exception
	 */
	public static function convert($csv_file, $xls_file, $csv_enc=null) {
		//set cache
		$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
		PHPExcel_Settings::setCacheStorageMethod($cacheMethod);
		
		//open csv file
		$objReader = new PHPExcel_Reader_CSV();
		if ($csv_enc != null)
			$objReader->setInputEncoding($csv_enc);
		$objPHPExcel = $objReader->load($csv_file);
		$in_sheet = $objPHPExcel->getActiveSheet();

		//open excel file
		$objPHPExcel = new PHPExcel();
		$out_sheet = $objPHPExcel->getActiveSheet();
		
		//row index start from 1
		$row_index = 0;
		foreach ($in_sheet->getRowIterator() as $row) {
			$row_index++;
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(false);
			
			//column index start from 0
			$column_index = -1;
			foreach ($cellIterator as $cell) {
				$column_index++;
				$out_sheet->setCellValueByColumnAndRow($column_index, $row_index, $cell->getValue());
			}
		}
		
		//write excel file
		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
		$objWriter->save($xls_file);
	}
}