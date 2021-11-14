<?php
if (isset($_POST) && !empty($_POST)) {
    $target_dir = "saves/";
    $newFilename = str_replace(array('#', '&', ' ', "'", '`', '�'), '_', substr($_POST['discordName'], 0, 8));
    $target_file = $target_dir . $newFilename . '.sav';
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// Check if file already exists
    if (!$_POST['discordName']) {
        echo "You MUST enter your Name.<br>";
        $uploadOk = 0;
    }
    if (strpos($_POST['discordName'], 'live') !== false) {
        echo "Name MUST NOT contain 'live'.<br>";
        $uploadOk = 0;
    }
    if (strpos($_POST['discordName'], 'slot') !== false) {
        echo "Name MUST NOT contain 'slot'.<br>";
        $uploadOk = 0;
    }
    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }

// Check file size
    if ($_FILES["fileToUpload"]["size"] > 1500000) {
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

            $myParser = new GVASParser();
            $myParser->NEWUPLOADEDFILE = $target_file;
            $myParser->parseData(file_get_contents($target_file), false);
            $saveReadr = new SaveReader($myParser->goldenBucket);
            $saveReadr->addDatabaseEntry($target_file);

            header('Location: /map.php?name=' . $newFilename);
            die();
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
    die();
}
