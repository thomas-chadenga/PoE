<?php
/**
 * Created by PhpStorm.
 * User: Thomas
 * Date: 15/06/23
 * Time: 11:13 AM
 */

session_start();

$user_id = $_SESSION['user_id'];

if(isset($_GET['form_id'])){
    $form_id = $_GET['form_id'];
}
else {
    $form_id = 3;
}

//load starting point form

//connect to db

function connect_db($db,$host,$user,$pass)
{

    try {
        $link = new PDO('mysql:dbname='.$db.';host='.$host, $user,$pass);
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit();
    }

    return $link;
}

function close_db_connection($link){
    $link = null;
}

$link = connect_db('poe_database','localhost','root','');


?>



<html>

<head>
<script language="javascript" type="text/javascript">
    function startUpload(){
        var proc = 'f1_upload_process';
        var upl = 'f1_upload_form';
        document.getElementById(proc).style.visibility = 'visible';
        document.getElementById(upl).style.visibility = 'hidden';
        return true;
    }

    function stopUpload(success){
        var proc = 'f1_upload_process';
        var upl = 'f1_upload_form';
        var result = '';
        if (success == 1){
            result = '<span class="msg">The file was uploaded successfully!<\/span><br/><br/>';
            location.reload(true);
            //document.getElementById('uploader').style.display = 'none';
            //document.getElementById('pager').style.display = 'inline';
        }
        else {
            result = '<span class="emsg">There was an error during file upload!<\/span><br/><br/>';
        }
        document.getElementById(proc).style.visibility = 'hidden';
        document.getElementById(upl).innerHTML = result + '<label>File: <input name="myfile" type="file" size="30" /><\/label><label><input type="submit" name="submitBtn" class="sbtn" value="Upload" /><\/label>';
        document.getElementById(upl).style.visibility = 'visible';
        //document.getElementById(uploader).style.display = 'none';
        //document.getElementById(pager).style.display = 'inline';
       return true;
    }

    function showUpload(form_block_id){
        var fbi = form_block_id;
        document.getElementById('uploader').style.display = 'inline';
        document.getElementById('pager').style.display = 'none';
        document.getElementById('myform').setAttribute("action","upload.php?form_id=<?php echo $form_id; ?>&user_id=<?php echo $user_id; ?>&fbi="+fbi);

    }

</script>
    <link href="style/style.css" rel="stylesheet" type="text/css" />
    <style>
        .content-left, .content-right{
            width:49%;
            display:inline-block;
            vertical-align:top;
        }
        .bottom, .top{
            position:absolute;
            width:100%;
            z-index:1;
            top:0;
            left:0;
        }
        .top{z-index:10; display:none}
    </style>
</head>
<body>
<div class="bottom" id="pager">
<div class="content-left">
    <?php
    $sql = "SELECT * FROM submissions_sections";
    $stmt = $link->query($sql);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);




    foreach($results as $res) {
        //echo main section
        echo "<div class='section'>".$res['section_name']."</div>";

        $sql2 = "SELECT * FROM forms WHERE extras_sections_id = " . $res['id'] . " ORDER BY form_position";

        $stmt2 = $link->query($sql2);

        $results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        foreach($results2 as $res2){
            //echo eche child form
            echo "<div class='section-child'>".$res2['form_name']."</div>";
        }
    }
    ?>
</div>
<div class="content-right">
<?php
include("forms/form_".$form_id.".php");
?>
</div>
    </div>
<div class="top" id="uploader" >
    <div id="container">
        <div id="content">
            <form action="upload.php?form_id=<?php echo $form_id; ?>&user_id=<?php echo $user_id; ?>" method="post" enctype="multipart/form-data" target="upload_target" onsubmit="startUpload();" id="myform" >
                <p id="f1_upload_process">Loading...<br/><img src="loader.gif" /><br/></p>
                <p id="f1_upload_form" align="center"><br/>
                    <label>File:
                        <input name="myfile" type="file" size="30" />
                    </label>
                    <label>
                        <input type="submit" name="submitBtn" class="sbtn" value="Upload" />
                    </label>
                </p>

                <iframe id="upload_target" name="upload_target" src="#" style="width:0;height:0;border:0px solid #fff;"></iframe>
            </form>
        </div>
    </div>
</div>
</body>
</html>