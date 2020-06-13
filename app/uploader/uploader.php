<?php
	require_once("upload_res.php");

	$base_path 			= dirname(dirname(dirname(__FILE__)));
	$target_dir 		= $_POST["target_dir"];
	$file_name 			= $_POST["file_name"];
	$old_name 			= $_POST["old_name"];
	$target_file 		= $base_path . $target_dir . $file_name;
	$target_file_old 	= $base_path . $target_dir . $old_name;

	$res = new UploadRes();

	// Check if new file already exists
	if (file_exists($target_file)) {
		unlink($target_file);
	}
	
	// Check if old file already exists
	if ($old_name != "" && file_exists($target_file_old)) {
		unlink($target_file_old);
	}

	// Check if $uploadOk is set to 0 by an error
	if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
		$success = array('status' => "success", "name" => $file_name);
		$res-> response($res->json($success), 200);
	} else {
		$success = array('status' => "failed", "msg" => "Failed uploading file");
		$res-> response($res->json($success), 200);
	}

?>
