<?php
/**
 * Created by PhpStorm.
 * User: Thomas
 * Date: 15/07/03
 * Time: 12:45 PM
 */

session_start();

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

if(!isset($_SESSION['user_id'])){
    header("Location:login.php");
}

$user_id = $_SESSION["user_id"];

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


//connect to
//list all the stiudents

$link = connect_db('poe_database','localhost','root','');

if($_SESSION['user_role'] == 1) {
    $sql = "SELECT * FROM users WHERE mentor_id = '$user_id'";
}
else{
    $sql = "SELECT * FROM users WHERE assessor_id = '$user_id'";
}

$stmt = $link->query($sql);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$students = array();
$student_fnames = array();
$student_lnames = array();

foreach($results as $res) {
    $students[] = $res['id'];
    $student_fnames[] = $res['first_name'];
    $student_lnames[] = $res['last_name'];
}

close_db_connection($link);

?>

<html>

<head>
    <?php header('Content-Type: text/html; charset=Windows-1252');    ?>

<link href="style/style.css" rel="stylesheet" type="text/css" />

<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">

<script src="ckeditor/ckeditor.js"></script>

</head>
<body>
<div class="bottom" id="pager">
    <div id="content-head">
        <div id="content-head-logo" class="desktop"><img src="icb-strip.jpg"/></div>
        <div id="content-head-strip-mobile" class="mobile"><img src="arrow-mobile.jpg"/></div>
        <div id="content-head-logo-text"><img src="icb-logo-text.jpg"/></div>
    </div>
    <div class="content-left">
        <?php
            //list the $students
            echo' <form id="first-form">
        <div>Students</div>
        ';
            for($i=0;$i<count($students);$i++){
                echo'
        <div><a href="form-loader.php?student_id='.$students[$i].'">'.$student_fnames[$i].' '.$student_lnames[$i].'</a></div>
        ';
            }
        echo '</form>';
        ?>
    </div>
    <div class="content-right" style="display:none">
        <div class="content-right-inner">

        </div>
    </div>
</div>
<div id="login-details" class="desktop">
    <div id="blue-strip">
        <img src="arrow-login.jpg" />
        <span><?php echo "Logged in: <span>".$_SESSION['user_name']." ".$_SESSION['user_last']."</span>"; ?></span>
    </div>
</div>
</body>
</html>

