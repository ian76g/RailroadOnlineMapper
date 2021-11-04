<?php
if (isset($_POST) && !empty($_POST)) {

    $target_dir = "uploads/";
    $myNewName = str_replace(array('#','&',' ',"'",'`', '´'), '_', substr($_POST['discordName'],0,8));
    $target_file = $target_dir . $myNewName . '.sav';
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// Check if file already exists
    if(!$_POST['discordName']){
        echo "You MUST enter your Name.<br>";
        $uploadOk = 0;
    }
    if(strpos($_POST['discordName'], 'live')!==false){
        echo "Name MUST NOT contain 'live'.<br>";
        $uploadOk = 0;
    }
    if(strpos($_POST['discordName'], 'slot')!==false){
        echo "Name MUST NOT contain 'slot'.<br>";
        $uploadOk = 0;
    }
    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }

// Check file size
    if ($_FILES["fileToUpload"]["size"] > 500000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

// Allow certain file formats
    if ($imageFileType != "sav") {
        echo "Sorry, only .sav files are allowed.";
        $uploadOk = 0;
    }

// Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "The file " . htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . " has been uploaded.<br>";
//            die('YOUR FILE WAS PLACED IN QUEUE UNTIL I TURN THE COMPUTER ON AGAIN AFTER MY NIGHT!');
$NEWUPLOADEDFILE = $myNewName.'.sav';
include('converter.php');
            echo '<A target="_map" href="done/'.$myNewName.'.html?t='.time().'">Your MAP as SVG</A><br>';
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
    die();
}

?>
<!DOCTYPE html>
<html><head><style>* {font-family:Verdana; font-size:12pt;}</style></head>
<body style="background-image: url('indexbg.jpg'); background-position: 50% 50%; padding:50px;background-repeat: no-repeat">
<div style="padding:100px;">
<!--H1>I AM IN BED NOW. GAMING COMPUTER SHUT DOWN! YOUR UPLOADS WILL BE QUEUED UNTIL I WAKE UP TOMORROW.</H1>
<H2>Due to some technical limitations this mapping can yet not be done plainely on Webservers (yet!)</H2>
<H3>hence we miss some cruicial bits needed to display a map.</H3-->
Open explorer at %localappdata%\arr\saved\savegames\<br>
<form method="post" enctype="multipart/form-data">
    Select image to upload:
    <input type="file" name="fileToUpload" id="fileToUpload"><br>
    Your Name on Discord or similar: <input name="discordName" maxlength="8"><br>
    <fieldset>
        <input type="radio" id="bg" name="background" value="bg">
        <label for="bg"> <img border="2" src="done/bg.png" width="90" height="90"> old background </label><br>
        <input  type="radio" id="bg3" name="background" value="bg3">
        <label for="bg3"> <img border="2" src="done/bg3.png" width="90" height="90"> new background </label><br>
        <input  type="radio" id="bg4" name="background" value="bg4">
        <label for="bg4"> <img border="2" src="done/bg4.png" width="90" height="90"> Psawhns background </label><br>
        <input checked type="radio" id="bg5" name="background" value="bg5">
        <label for="bg5"> <img border="2" src="done/bg4.png" width="90" height="90"> Psawhns background with Kanados overlay</label><br>
    </fieldset><br>
    <hr>expert settings: DON'T TOUCH when you are no expert!<hr>
    background image offset X: <input name="xoff" value=""><br>
    background image offset Y: <input name="yoff" value=""><br>
    background image width: <input name="xsoff" value=""><br>
    background image height: <input name="ysoff" value=""><br>
    <input type="checkbox" name="empty"> include unnamed rolling stock to list <br>
    <input type="submit" value="Upload save game" name="submit">
</form>
</div>
</body>
</html>