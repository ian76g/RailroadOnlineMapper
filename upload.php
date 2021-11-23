<?php
if (isset($_POST) && !empty($_POST)) {
    $target_dir = "saves/";
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($_FILES["fileToUpload"]["tmp_name"], PATHINFO_EXTENSION));

// Check file size
    if ($_FILES["fileToUpload"]["size"] > 1500000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
        // if everything is ok, try to upload file
    } else {
        require_once 'utils/dtAbstractData.php';
        require_once 'utils/dtDynamic.php';
        require_once 'utils/dtHeader.php';
        require_once 'utils/dtProperty.php';
        require_once 'utils/dtString.php';
        require_once 'utils/dtVector.php';
        require_once 'utils/dtArray.php';
        require_once 'utils/dtStruct.php';
        require_once 'utils/dtTextProperty.php';
        require_once 'utils/GVASParser.php';
        require_once 'utils/SaveReader.php';

        $x = str_replace(array('slot', '.sav'), '', $_FILES['fileToUpload']['name']);
        if($x == ''.intval($x)){
            $slotNumber = '-'.$x;
        } else {
            $slotNumber = '';
        }

        $myParser = new GVASParser();
        $myParser->parseData(file_get_contents($_FILES["fileToUpload"]["tmp_name"]), false, $slotNumber);
        $newFilename = $myParser->owner;
        $target_file = $target_dir . $newFilename . '.sav';

        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            $saveReadr = new SaveReader($myParser->goldenBucket);
            $saveReadr->addDatabaseEntry($newFilename, isset($_POST['public']));
            header('Location: /map.php?name=' . $newFilename);
            die();
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
    die();
}
