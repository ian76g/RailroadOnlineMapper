<?php
require_once ('config.php');
if (isset($_POST) && !empty($_POST)) {

    $target_dir = SHELL_ROOT."uploads/";
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
          $NEWUPLOADEDFILE = $_FILES["fileToUpload"]["tmp_name"];
          include('converter.php');
          header('Location: '.$mapFile);
        }
        die();
}

?>
<!DOCTYPE html>
<html lang="en">
<?php
$PageTitle = "Upload";
include_once(SHELL_ROOT.'includes/head.php');
?>
<body>
<!--H1>I AM IN BED NOW. GAMING COMPUTER SHUT DOWN! YOUR UPLOADS WILL BE QUEUED UNTIL I WAKE UP TOMORROW.</H1>
<H2>Due to some technical limitations this mapping can yet not be done plainely on Webservers (yet!)</H2>
<H3>hence we miss some cruicial bits needed to display a map.</H3-->

<header class="header">
    <a class="button" href="<?php echo WWW_ROOT;?>">Go Back</a>
</header>

<main>
    <form class="upload-form" method="post" enctype="multipart/form-data">
        <h1>Upload your savefile</h1>
        <p>Open explorer at %localappdata%\arr\saved\savegames\</p>
        <br>

        <section>
            <h3>1. Select savefile</h3>
              <input type="file" name="fileToUpload" id="fileToUpload">
        </section>
        <!--img src="hint.png" width="700"-->
        <section>
            <h3>2. Make it public</h3>
            <div class="input-group input-group--row">
                <input id="public" type="checkbox" name="public">
                <label for="public">Others are allowed to download this for 2 days</label>

            </div>

        </section>

            <input class="button" type="submit" value="Upload" name="submit">


    </form>
</main>

<?php include_once(SHELL_ROOT.'includes/footer.php') ?>

</body>
</html>
