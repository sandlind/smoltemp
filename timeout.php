<?php 
header('Content-Type: text/plain');
##every 10 minutes, run this

if (isset($_GET['key'])){
    if ($_GET['key'] !== "CHANGEME"){
        die("wrong key");
    }
} else {
    die("no key");
}

$files = file_get_contents("files.json");
$files = json_decode($files, true);
$new_files = array();

foreach ($files as $file){
    if ($file['expire'] < time()){
        unlink($file['link']);
    } else {
        array_push($new_files, $file);
    }
}

$new_files = json_encode($new_files, true);
$listfile = fopen("files.json","w");
fwrite($listfile, $new_files);
fclose($listfile);

echo "based";

?> 
