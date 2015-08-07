<?php

session_start();

    if(!isset($_SESSION['user_id'])){
        header("Location:login.php");
    }



    $user_id = $_GET['user_id'];
    $form_id = $_GET['form_id'];
$fbi = $_GET['fbi'];

$form_id= $_SESSION['form_id'];

    //
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

   // Edit upload location here
   $destination_path = getcwd().DIRECTORY_SEPARATOR;

   $result = 0;
   
   $target_path = $destination_path ."/uploads/". basename( $_FILES['myfile']['name']);

   if(@move_uploaded_file($_FILES['myfile']['tmp_name'], $target_path)) {
      $result = 1;
       $ext=$fbi;
       //insert into db
       //insert because no record exitst
       $vall = basename( $_FILES['myfile']['name']);
       $checked = 0;
       $checked_by=0;
       $timer = '';
       $block_id = '';
       $access_level=0;
       $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
       $sql_insert = "INSERT INTO user_form_block (user_id,form_id,form_block_link,value,checked,checked_by_id,date_checked,access_level) VALUES (:user_id,:form_id,:form_block_link,:val,:checked,:checked_by,:timer,:access_level)";
       $stmt = $link->prepare($sql_insert);
       $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
       $stmt->bindParam(':form_id', $form_id, PDO::PARAM_INT);
       $stmt->bindParam(':access_level', $access_level, PDO::PARAM_INT);
       $stmt->bindParam(':form_block_link', $ext, PDO::PARAM_STR);
       $stmt->bindParam(':val', $vall, PDO::PARAM_STR);
       $stmt->bindParam(':checked', $checked, PDO::PARAM_INT);
       $stmt->bindParam(':checked_by', $checked_by, PDO::PARAM_INT);
       $stmt->bindParam(':timer', $timer, PDO::PARAM_STR);
       try {
           $stmt->execute();
       }
       catch(Exception $e){
           echo 'Caught exception: ',  $e->getMessage(), "\n";
       }
   }
   
   sleep(1);
?>

<script language="javascript" type="text/javascript">window.top.window.stopUpload(<?php echo $result; ?>,<?php echo $form_id; ?>);</script>
