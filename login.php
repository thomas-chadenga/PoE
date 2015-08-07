<?php
/**
 * Created by PhpStorm.
 * User: Thomas
 * Date: 15/06/23
 * Time: 10:40 AM
 */

session_start();

unset($_SESSION['student_id']);
unset($_SESSION['form_id']);

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

$username = $password = null;

if(isset($_POST['name'])){
    $username = $_POST['name'];
}
if(isset($_POST['password'])){
    $password = md5($_POST['password']);
}

//check db for match
if($username && $password){
    $link = connect_db('poe_database','localhost','root','');

    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";

    $stmt = $link->query($sql);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if(count($results)>0){
        //login success
        //set session variables for user
        foreach($results as $res) {
            $user_id = $res['id'];
            $user_name = $res['first_name'];;
            $user_last = $res['last_name'];
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $user_name;
            $_SESSION['user_last'] = $user_last;
            $_SESSION['user_role'] = $res['user_role'];
        }
        if($_SESSION['user_role']==0){
            header("Location:form-loader.php");
        }
        else{
            header("Location:student-list.php");
        }

    }
    else{
        //no results found
        echo '<div class="errormsg" style="padding:0 10px;border:1px solid red; color:red; background-color:#FFB6C1;position:absolute;top:0;width:100%;left:0;font-size:12px;z-index:102">
There was an error Logging in. Please check your credentials and try again.</div>';
    }
}

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
            echo '<div id="first-form-parent">
            <form action="login.php" method="post" id="first-form">
                <div>
                    <label for="name">Username:</label>
                    <input type="text" id="name" name="name"/>
                </div>
                <div>
                    <label for="mail">Password:</label>
                    <input type="password" id="password" name="password" />
                </div>
               <div class="button">
                    <button type="submit">Login</button>
                </div>
            </form>
            </div>';
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
        <span><?php echo "Welcome to ICB PoE System"; ?></span>
    </div>
</div>
</body>
</html>
