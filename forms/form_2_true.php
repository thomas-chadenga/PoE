<?php
session_start();
$_SESSION["user_id"] = 1;
$user_id = $_SESSION["user_id"];
function connect_db($db,$host,$user,$pass)
{
    try {
        $link = new PDO("mysql:dbname=".$db.";host=".$host, $user,$pass);
    } catch (PDOException $e) {
        echo "Connection failed:".  $e->getMessage();
        exit();
    }
    return $link;
}
function close_db_connection($link){
    $link = null;
}
$link = connect_db("poe_database","localhost","root","");
$sql = "SELECT * FROM user_form_block WHERE user_id=$user_id AND form_id=2";
$stmt = $link->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($results as $res){
    $form_el_id[] = $res["form_block_link"];
    $form_el_val[] = $res["value"];
}
close_db_connection($link);
echo "<form action='../form_submit.php?form_id=2&user_id=1' method='post' name='form_2' id='form_2' enctype='multipart/form-data'>";
    if(in_array(strval(1),$form_el_id)){
        $key = array_search(strval(1),$form_el_id);
        $myVal = $form_el_val[$key];
        echo '<div class="input_textfield"><input class="textfield" type="text" name="form_2_1" id="form_2_1" value="$myVal"/></div>';
    }else{
        echo '<div class="input_textfield"><input class="textfield" type="text" name="form_2_1" id="form_2_1" placeholder="insert name"/></div>';
    }
    if(in_array(strval(2),$form_el_id)){
        $key = array_search(strval(2),$form_el_id);
        $myVal = $form_el_val[$key];
        echo '<div class="input_textfield"><input class="textfield" type="text" name="form_2_2" id="form_2_2" value="$myVal"/></div>';
    }else{
        echo '<div class="input_textfield"><input class="textfield" type="text" name="form_2_2" id="form_2_2" placeholder="insert last name"/></div>                     }<div class='input_label'><label class='label' name='form_2_3' id='form_2_3'>Description</label></div><div class='input_textarea'><textarea class='textarea' name='form_2_4' id='form_2_4' ></textarea></div><div class='input_submitButton'><input class='submitButton' type='submit' name='form_2_5' id='form_2_5' placeholder='Save'/></div><div class='input input_dropdown'><select class='drop' name='form_2_drop'><option class='drop'  value='value 1' id='form_2_9' >value 1</option><option class='drop'  value='value 2' id='form_2_10' >value 2</option></select></div><div class='input input_checkbox'><input class='checkbox' type='checkbox' name='form_2_11' id='form_2_11' />check 1</div><div class='input input_checkbox'><input class='checkbox' type='checkbox' name='form_2_12' id='form_2_12' />check 2</div><div class='input input_radio'><input class='radio' type='radio' name='form_2_radio1' id='form_2_13' value='radio1'  />radio1</div><div class='input input_radio'><input class='radio' type='radio' name='form_2_radio1' id='form_2_14' value='radio 2'  />radio 2</div><div class='input input_upload'><input class='upload' type='file' name='form_2_15' id='form_2_15' placeholder='Upload file'/></div></form>