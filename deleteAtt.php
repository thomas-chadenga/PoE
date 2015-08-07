<?php
/**
 * Created by PhpStorm.
 * User: Thomas
 * Date: 15/06/24
 * Time: 1:35 PM
 */

session_start();

if(!isset($_SESSION['user_id'])){
    header("Location:login.php");
}

//get the variables from the delete

$att_id = $_GET['id'];
$form_id = $_GET['form_id'];

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

$sql_update = "DELETE FROM user_form_block  WHERE id=:att_id";
$stmt = $link->prepare($sql_update);
$stmt->bindParam(':att_id', $att_id, PDO::PARAM_INT);
try {
    $stmt->execute();
} catch (Exception $e) {
    echo 'Caught exception: ', $e->getMessage(), "\n";
}

header("Location:form-loader.php?form_id=".$form_id);