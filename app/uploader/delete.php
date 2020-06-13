<?php
	$data = json_decode(file_get_contents("php://input"),true);

	$base_path 	= dirname(dirname(dirname(__FILE__)));
	$target_dir = $data['target_dir'];
	$file_names = $data['file_names'];

	foreach($file_names as $fn){
		$target_file = $base_path . $target_dir . $fn;
		if (file_exists($target_file)) {
			unlink($target_file);
		}
	}

?>
