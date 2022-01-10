<?php
header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

//error_log "delete", 3, "/home/spiderfla/upload/doc/debug.log");
$path = "../upload/doc/";
$fileName = $_POST['fileNames'];
unlink($path . $fileName);
?>