<html>

<head>
<title>smoltemp - temp uploads</title>
<style>
body { margin: 0; padding: 0; font-family: monospace; }
div.contain { width: 600px; text-align: left; }
p { padding: 0; margin: 0; margin-left: 15px; padding: 2px; border: 1px #000; border-style: hidden hidden hidden solid;}
td { border: 1px solid black; font-size: 10pt; }
</style>
</head>

<body>
<center>
<div class='contain'>

<?php
session_start();

$setting_admin_key = "CHANGEME";
$crypt_cipher = "AES-128-CTR"; 
$crypt_iv_length = openssl_cipher_iv_length($crypt_cipher); 
$crypt_options = 0;  
$crypt_iv = 'CHANGEME'; ## 16 bytes long, hexadecimal

if ($crypt_iv == "CHANGEME" || $setting_admin_key == "CHANGEME"){
	die("<h1>Error</h1> <i>crypt_iv</i> and <i>setting_admin_key</i> are set to <b>CHANGEME</b>. Edit <i>index.php</i> and change them.");
}

date_default_timezone_set("America/New_York");

## Account array
## name, password, storage limit, total file limit
## Paste a new user array to add more users
## -1 for byte_limit and total_limit means unlimited
$accounts = array( array("name" => "example", "pass" => "CHANGEME", "byte_limit" => "-1", "total_limit" => "-1") );

## Allowed formats
## Every bad format will be in the second param below, separated by spaces.
## Example: "php exe jar"
$badext = explode(" ","php");

if (isset($_GET['logout'])){
    if (isset($_SESSION['smoltemp_name'])){
        session_destroy();
        echo "Logged out.";
        header("Location: index.php");
        die();
    } else {
        echo "You weren't logged in.";
    }
}

if (isset($_POST['smoltemp_mode'])) {
    $mode = $_POST['smoltemp_mode'];
    
    if ($mode == "login"){
        if (isset($_POST["smoltemp_pass"]) && isset($_POST["smoltemp_user"])){
            $pass = $_POST["smoltemp_pass"]; 
            $user = $_POST["smoltemp_user"];
        } else {
            die("Pass and/or User not set.");
        }
        
        $usernames = array_column($accounts, "name");
        
        if (!in_array($user,$usernames)){
            die("User doesn't exist.");
        }
        
        $userplace = array_search($user, $usernames);
        if ($accounts[$userplace]["pass"] === $pass){
            ## Create session
            $_SESSION['smoltemp_name'] = $user;
        } else {
            echo "<b>Wrong password, go away.</b>";
        }
        
    } elseif ($mode == "upload"){
        ## See if user has reached total limit so far
        $files = file_get_contents("files.json");
        $files = json_decode($files, true);
        $user_files = array_column($files, "user"); 
        $user_enc = openssl_encrypt($_SESSION['smoltemp_name'], $crypt_cipher, $setting_admin_key, $crypt_options, $crypt_iv); 
        $user_files = count(array_keys($user_files, $user_enc));
        
            ## Get bytes of user's files
        $stuff = array("bytes" => "0");
        foreach ($files as $file){
            if ($file['user'] == $user_enc){
                $stuff['bytes'] = $stuff['bytes'] + $file['size'];
            }
        }   

        $user_bytetotal = $stuff['bytes'];
        
        $usernames = array_column($accounts, "name");
        $userplace = array_search($_SESSION['smoltemp_name'], $usernames);
        $filesizes = array_sum($_FILES["file"]["size"]);
        
        ## Not enough bytes / null upload
        if ($filesizes < 2){
            echo "This upload is less than two bytes. This means you uploaded nothing or you uploaded something retarded.";
            die();
        }
        
        ## Too many files
        if ($user_files >= $accounts[$userplace]["total_limit"] && $accounts[$userplace]["total_limit"] !== "-1"){
            echo "You have reached your maximum existing-file limit. Your limit is <b>" . $accounts[$userplace]["total_limit"] . "</b>.<br>
            You have <b>" . $user_files . "</b> existing files.<br><br>
            
            Problem? Contact Vincememe.";
            die();
        }
        
        ## Exceed byte limit
        if ($user_bytetotal >= $accounts[$userplace]["byte_limit"] && $accounts[$userplace]["byte_limit"] !== "-1"){
            echo "You have reached your byte-limit. Your limit is <b>" . number_format($accounts[$userplace]["byte_limit"]) . " bytes</b>.<br>
            You have <b>" . number_format($user_bytetotal) . " bytes</b> of data existing.<br><br>
            
            Problem? Contact Vincememe.";
            die();
        }
        
        ## Potentially exceed byte limit
        if ($user_bytetotal + $filesizes >= $accounts[$userplace]["byte_limit"] && $accounts[$userplace]["byte_limit"] !== "-1"){
            echo "This upload would exceed your byte-limit. Your limit is <b>" . number_format($accounts[$userplace]["byte_limit"]) . " bytes</b>.<br>
            You have <b>" . number_format($user_bytetotal) . " bytes</b> of data existing.<br>
            Your total upload was <b>" . number_format($filesizes) . " bytes</b>, an excess of <b>" . number_format(($user_bytetotal + $filesizes) - $accounts[$userplace]["byte_limit"] ) . " bytes</b>.<br><br>
            
            Problem? Contact Vincememe.";
            die();
        }
        
        if (isset($_POST['time'])){
            $time = $_POST['time'];
            
            if ($time == "15m"){
                $duration = 900;
            } elseif ($time == "30m"){
                $duration = 1800;
            } elseif ($time == "1h"){
                $duration = 3600;
            } elseif ($time == "12h"){
                $duration = 43200;
            } elseif ($time == "24h"){
                $duration = 86400;
            }
        } else {
            die("Set a time for how long the files should exist for.");
        }
        $countfiles = count($_FILES['file']['name']);
        echo "<b>Uploads:</b> ($time \ ~$duration seconds)<br>";
        for($i=0;$i<$countfiles;$i++){
            $ext = strtolower(pathinfo($_FILES["file"]["name"][$i], PATHINFO_EXTENSION));
            if (in_array($ext, $badext)) {
                echo "[0] Bad format for file <b>" . $_FILES["file"]["name"][$i] . "</b>. Not uploaded.<br>";
            } else {
                $upname = bin2hex(random_bytes(3));          
                $filename = $upname . "." . $ext;
                $uploadname = openssl_encrypt(bin2hex(random_bytes(2)) . " " . $_FILES["file"]["name"][$i] . " " . bin2hex(random_bytes(2)), $crypt_cipher, $setting_admin_key, $crypt_options, $crypt_iv);
                
                ## Push file into list of existing files 
                
                $uploader = $_SESSION['smoltemp_name'];
                $uploader = openssl_encrypt($uploader, $crypt_cipher, $setting_admin_key, $crypt_options, $crypt_iv); 
                
                $expire_time = time() + $duration;
                $filelist = array("link" => "$filename", "size" => $_FILES["file"]["size"][$i], "user" => "$uploader", "id" => "$uploadname", "expire" => "$expire_time");
                
                $filejson = file_get_contents("files.json");
                $filejson = json_decode($filejson, true);
                
                array_push($filejson, $filelist);
                
                $new_files = json_encode($filejson, true);
                $listfile = fopen("files.json","w");
                fwrite($listfile, $new_files);
                fclose($listfile);
                
                $success = move_uploaded_file($_FILES['file']['tmp_name'][$i], $filename);
                if ($success) {
                    echo "[1] Uploaded <b>" . $_FILES["file"]["name"][$i] . "</b> to <a href='$filename'>$filename</a>.<br>"; 
                } else {
                    echo "[0] Error for <b>" . $_FILES["file"]["name"][$i] . "</b>.<br>"; 
                }
            }
        }
        echo "<hr>";
    }
}

if (isset($_SESSION['smoltemp_name'])) {
    $files = file_get_contents("files.json");
    $files = json_decode($files, true);
    $user_files = array_column($files, "user"); 
    $user_enc = openssl_encrypt($_SESSION['smoltemp_name'], $crypt_cipher, $setting_admin_key, $crypt_options, $crypt_iv); 
    $user_files = count(array_keys($user_files, $user_enc));
    
    if ($user_files > 0){
        echo "Your existing files:<br> 
        <table>
        <tr><td>File</td><td>Name</td><td>Size</td><td>Expires</td></tr>";
    }
    
    ## Get bytes of user's files
    $stuff = array("bytes" => "0", "files" => array());
    foreach ($files as $file){
        if ($file['user'] == $user_enc){
            $stuff['bytes'] = $stuff['bytes'] + $file['size'];
            array_push($stuff['files'], $file["link"]);
            
            $id_raw = explode(" ",openssl_decrypt($file["id"], $crypt_cipher, $setting_admin_key, $crypt_options, $crypt_iv));
            $uploadname = $id_raw[1];
            
            echo "<tr><td><a href='" . $file['link'] . "'>" . $file['link'] . "</a></td><td>$uploadname</td><td> " . number_format($file['size']) . "</td><td>" . date("d M y / H:i:s T",$file['expire']) . "</td></tr>";
        }
    }
    
    if ($user_files > 0){
        echo "</table>";
    }
    
    $usernames = array_column($accounts, "name");
    $userplace = array_search($_SESSION['smoltemp_name'], $usernames);
    $filesizes = array_sum($_FILES["file"]["size"]);
        
    ## Print upload page
    echo
    "
    <br>
    $user_files/" . $accounts[$userplace]["total_limit"] . " existing files - " . number_format($stuff['bytes']) . "/" . number_format($accounts[$userplace]["byte_limit"]) . " bytes being used<br>
    
    Signed in as <b>" . $_SESSION['smoltemp_name'] . "</b>.<br>
    <a href='?logout'>Log out</a><br><br>
    
    <form action='index.php' method='post' enctype='multipart/form-data'>
    Expire after:<br>
    <input type='radio' name='time' value='15m' required> 15 Minutes<br> 
    <input type='radio' name='time' value='30m' required> 30 Minutes<br> 
    <input type='radio' name='time' value='1h' required> 1 Hour<br> 
    <input type='radio' name='time' value='12h' required> 12 Hours<br> 
    <input type='radio' name='time' value='24h' required> 24 Hours<br> 
    <input type='file' name='file[]' multiple><br>
    <input type='hidden' name='smoltemp_mode' value='upload'>
    <input type='submit' value='Upload'> 
    </form>
    ";
} else {
    ## Print login page
    echo "
    <form action='index.php' method='post'>
    <input required type='text' name='smoltemp_user' placeholder='username' maxlength='64'><br>
    <input required type='password' name='smoltemp_pass' placeholder='password' maxlength='64'>
    <input type='hidden' name='smoltemp_mode' value='login'>
    <input type='submit' value='>'> 
    </form>
    ";
}


?>

</div>
</center>
</body>

</html>
