<?php
/**
 * Created by PhpStorm.
 * User: Thomas
 * Date: 15/06/17
 * Time: 1:55 PM
 */

session_start();

if(!isset($_SESSION['user_id'])){
    header("Location:login.php");
}

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

function fileUpload($val){
   // echo $val."</br>";
    $valid_file = true;
    //if they DID upload a file...
    if($_FILES[$val]['name'])
    {
        //if no errors...
        if(!$_FILES[$val]['error'])
        {
            //now is the time to modify the future file name and validate the file
            $new_file_name = strtolower($_FILES[$val]['tmp_name']); //rename file
            if($_FILES[$val]['size'] > (1024000)) //can't be larger than 1 MB
            {
                $valid_file = false;
                $message = 'Oops!  Your file\'s size is to large.';
              //  echo 'Oops!  Your file\'s size is to large.';
            }

            //if the file has passed the test
            if($valid_file)
            {
                //move it to where we want it to be
                move_uploaded_file($_FILES[$val]['tmp_name'], 'uploads/'.$_FILES[$val]['name']);
                $message = 'Congratulations!  Your file was accepted.';
                //echo 'Congratulations!  Your file was accepted.';
            }
        }
        //if there is an error...
        else
        {
            //set that to be the returned message
            $message = 'Ooops!  Your upload triggered the following error:  '.$_FILES[$val]['error'];
            //echo 'Ooops!  Your upload triggered the following error:  '.$_FILES[$val]['error'];
        }
    }
}

$form_id = $_GET['form_id'];
$user_id = $_SESSION['user_id'];
$form_block_ids = $_SESSION['data'];
$form_block_types = $_SESSION['types'];
$access_level = $_SESSION["user_role"];

if(isset($_SESSION['student_id'])){
    $user_id = $_SESSION['student_id'];
}

//var_dump($_POST['form_5_radio1']);
//die($form_id);

$link = connect_db('poe_database','localhost','root','');

//var_dump($_FILES);//var_dump ($_POST['form_2_check1']);
//var_dump($_SESSION['user_id']);
//die();

$form_block_ids_new = array();
$form_block_types_new = array();
$form_block_access_level = array();

//filter form ids by form id
$needle = "_".$form_id."_";

for($i=0;$i<count($form_block_ids);$i++){
    if(strpos($form_block_ids[$i],$needle) > 0){
        $form_block_ids_new[] = $form_block_ids[$i];
        $form_block_types_new[] = $form_block_types[$i];
    }
}

$form_block_ids=$form_block_ids_new;
$form_block_types=$form_block_types_new;

//var_dump($form_block_ids);
//echo $user_id." ".$form_id;
//die();


for($i=0;$i<count($form_block_ids);$i++) {
    //get all fields values
    if ((isset($_POST[$form_block_ids[$i]]))){

        $vall = "";

        //if not in db then insert
        if(isset($_POST[$form_block_ids[$i]])) {
            $val = $_POST[$form_block_ids[$i]];
        }
        else if(isset($_FILES[$form_block_ids[$i]])){
            $val = "";
            $valll = $_FILES[$form_block_ids[$i]]['name'];
            $param = $form_block_ids[$i];
        }

        $exts = explode("_",$form_block_ids[$i]);
        $ext = $exts[2];
        $fID = $exts[1];

        //echo $val." ".$form_block_ids[$i]."</br>";
        //echo $form_block_types[$i]."</br>";
        if($form_block_types[$i] == 'upload'){
            //run upload function
            fileUpload($param);
        }

        $sql = "(SELECT * FROM user_form_block WHERE user_id = ".$user_id." AND form_block_link ='".$ext."' AND form_id = ".$form_id.")";

        $stmt = $link->query($sql);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(count($results)>0){
           // echo "in here ".$val." ".$user_id." ".$form_block_ids[$i]."</br>";
            //update because it exists
            if($val != "") {
                $sql_update = "UPDATE user_form_block SET value=:val WHERE user_id=:user_id AND form_block_link=:form_block_link AND form_id=:form_id ";
                $stmt = $link->prepare($sql_update);
                $stmt->bindParam(':val', $val, PDO::PARAM_STR);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':form_block_link', $ext, PDO::PARAM_STR);
                $stmt->bindParam(':form_id', $form_id, PDO::PARAM_INT);
                try {
                    $stmt->execute();
                } catch (Exception $e) {
                    echo 'Caught exception: ', $e->getMessage(), "\n";
                }
            }
            else if($vall != ""){
                //insert because no record exitst
                $checked = 0;
                $checked_by=0;
                $timer = '';
                $block_id = '';
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
            else if($val == ""){
                //delete
                $sql_update = "DELETE FROM user_form_block WHERE user_id=:user_id AND form_block_link=:form_block_link AND form_id=:form_id ";
                $stmt = $link->prepare($sql_update);
                $stmt->bindParam(':val', $val, PDO::PARAM_STR);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':form_block_link', $ext, PDO::PARAM_STR);
                $stmt->bindParam(':form_id', $form_id, PDO::PARAM_INT);
                try {
                    $stmt->execute();
                } catch (Exception $e) {
                    echo 'Caught exception: ', $e->getMessage(), "\n";
                }
            }

        }
        else{
            //insert because no record exitst
            $checked = 0;
            $checked_by=0;
            $timer = '';
            $block_id = '';
            $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql_insert = "INSERT INTO user_form_block (user_id,form_id,form_block_link,value,checked,checked_by_id,date_checked,access_level) VALUES (:user_id,:form_id,:form_block_link,:val,:checked,:checked_by,:timer,:access_level)";
            $stmt = $link->prepare($sql_insert);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':form_id', $form_id, PDO::PARAM_INT);
            $stmt->bindParam(':access_level', $access_level, PDO::PARAM_INT);
            $stmt->bindParam(':form_block_link', $ext, PDO::PARAM_STR);
            $stmt->bindParam(':val', $val, PDO::PARAM_STR);
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
    }
    else{
        //set value to zero
        //if not in db then insert
        $val = NULL;

        $exts = explode("_",$form_block_ids[$i]);
        $ext = $exts[2];

        //echo $val." ".$form_block_ids[$i]."</br>";

        $sql = "(SELECT * FROM user_form_block WHERE user_id = ".$user_id." AND form_block_link ='".$ext."' AND form_id = ".$form_id.")";

        $stmt = $link->query($sql);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(count($results)>0){
            // echo "in here ".$val." ".$user_id." ".$form_block_ids[$i]."</br>";
            //update because it exists
            $sql_update = "UPDATE user_form_block SET value=:val WHERE user_id=:user_id AND form_block_link=:form_block_link  AND form_id=:form_id";
            $stmt = $link->prepare($sql_update);
            $stmt->bindParam(':val', $val, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':form_block_link', $ext, PDO::PARAM_STR);
            $stmt->bindParam(':form_id', $form_id, PDO::PARAM_INT);
            try {
                $stmt->execute();
            }
            catch(Exception $e){
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }

        }
    }
}

close_db_connection($link);

$form_id +=1;

if(file_exists("forms/form_"+$form_id+".php")) {
    $_SESSION['form_id'] = $form_id;
//return to the form
    header("Location:form-loader.php?user_id=" . $user_id . "&form_id=" . $form_id);
}
else{
    $form_id -= 1;
    $_SESSION['form_id'] = $form_id;

}

