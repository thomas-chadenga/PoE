<?php
/**
 * Created by PhpStorm.
 * User: Thomas
 * Date: 15/06/12
 * Time: 12:54 PM
 */

session_start();

//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(-1);

//includes
include_once('simple_html_dom.php');

$_SESSION['user_id'] = 1;

$user_id = $_SESSION['user_id'];

//$user_level = $_SESSION['user_role'];


//connect to database

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




//create forms that dont exist from db

function create_form(){

    $link = connect_db("poe_database","localhost","root","");

    $sql = "SELECT id FROM forms";

    $stmt = $link->query($sql);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($results as $res){

        //if html form doesnt exist create it
        //if(!file_exists('forms/form_'.$res['id'].'.html')){

            $html = '';

            $sql2 = "SELECT * FROM form_blocks WHERE form_id = ".$res['id']." ORDER BY form_block_position";

            $stmt2 = $link->query($sql2);

            $results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $form_el_id = array();

        //set up php function to fetch all inserted answers for user to this form
            $html .= '<?php ';

            $html .= '

            session_start();
            $user_id = $_SESSION["user_id"];
            $user_level = $_SESSION["user_role"];


             $_SESSION["form_id"] = '.$res['id'].';

             $form_id = $_SESSION["form_id"];

            if(!function_exists("connect_db")){

            function connect_db($db,$host,$user,$pass)
{

        $link = new PDO(\'mysql:dbname=\'.$db.\';host=\'.$host, $user,$pass);
    return $link;
}

function close_db_connection($link){
    $link = null;
}
}

           ';

            $html .= '$link = connect_db("poe_database","localhost","root","");';

            $html .= '

            if(!isset($_SESSION["student_id"]) || (trim($_SESSION["student_id"])=="")){

            $sql = "SELECT * FROM user_form_block WHERE user_id=$user_id AND form_id='.$res['id'].'";

            }

            else{

            $student_id = $_SESSION["student_id"];

            $sql = "SELECT * FROM user_form_block WHERE (user_id=$student_id OR user_id=$user_id) AND form_id='.$res['id'].'";

            }

            $stmt = $link->query($sql);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $form_el_id = array();

            $form_el_val = array();

            $form_el_valID = array();

            foreach($results as $res){';

            $html .= '$form_el_id[] = $res["form_block_link"];';

            $html .= '$form_el_val[] = $res["value"];';

            $html .= '$form_el_valID[] = $res["id"];';

            $html .= '}';

            $html .= 'close_db_connection($link);';


            $html .= '$link1 = connect_db("poe_database","localhost","root","");';

            $html .= '$sql1 = "SELECT * FROM form_blocks WHERE form_id=$form_id ORDER BY form_block_position";

                $stmt1 = $link1->query($sql1);

                $results1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

                $form_el_access = array();

                foreach($results1 as $res1){';

            $html .= '$form_el_access[] = $res1["access_level"];';

            $html .= '}';

            $html .= 'close_db_connection($link);';


            //create html
            $html .= "echo \"<form action='' method='post' name='form_".$res['id']."' id='form_".$res['id']."' enctype='multipart/form-data'>\";";

            $ctr = 0;

            foreach($results2 as $res2){

                if(($res2['form_block_type'] == 'textfield')) {
                    $html .= '

                    if($form_el_access["'.$ctr.'"]<=$user_level){
                    $disabled="";
                        if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="readonly";
                        }else{
                        $disabled="";
                        }
                         if(in_array(strval(' . $res2['id'] . '),$form_el_id)){
                        $key = array_search(strval(' . $res2['id'] . '),$form_el_id);
                        $myVal = $form_el_val[$key];
                            if(trim($myVal) != ""){
                                echo \'<div class="input_' . $res2['form_block_type'] . '"><input class="' . $res2['form_block_type'] . '" type="text" name="form_' . $res['id'] . '_' . $res2['id'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '" value="\'.$myVal.\'" \'.$disabled.\' /></div>\';
                            }else{
                                echo \'<div class="input_' . $res2['form_block_type'] . '"><input class="' . $res2['form_block_type'] . '" type="text" name="form_' . $res['id'] . '_' . $res2['id'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '" placeholder="' . $res2['value'] . '" \'.$disabled.\' /></div>\';
                            }
                        }else{
                           echo \'<div class="input_' . $res2['form_block_type'] . '"><input class="' . $res2['form_block_type'] . '" type="text" name="form_' . $res['id'] . '_' . $res2['id'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '" placeholder="' . $res2['value'] . '" \'.$disabled.\' /></div>\';
                        }
                    }
                    ';
                    $form_block_ids[] = 'form_'.$res['id']."_".$res2['id'];
                    $form_block_types[] = $res2['form_block_type'];
                }
                else if(($res2['form_block_type'] == 'textarea')){
                    $html .= '
                    if($form_el_access["'.$ctr.'"]<=$user_level){
                    $disabled="";
                    if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="disabled";
                        }else{
                        $disabled="";
                        }
                        if(in_array(strval(' . $res2['id'] . '),$form_el_id)){
                            $key = array_search(strval(' . $res2['id'] . '),$form_el_id);
                            $myVal = $form_el_val[$key];
                           echo \'<div class="input_' . $res2['form_block_type'] . '"><textarea class="' . $res2['form_block_type'] . '"  name="form_' . $res['id'] . '_' . $res2['id'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '"  \'.$disabled.\' >\'.$myVal.\'</textarea></div>\';
                        }else{
                           echo \'<div class="input_' . $res2['form_block_type'] . '"><textarea class="' . $res2['form_block_type'] . '" name="form_' . $res['id'] . '_' . $res2['id'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '"  \'.$disabled.\' ></textarea></div>\';
                        }
                    }

                    ';
                   // $html .= "echo \"<div class='input_".$res2['form_block_type']."'><textarea class='".$res2['form_block_type']."' name='form_".$res['id']."_".$res2['id']."' id='form_".$res['id']."_".$res2['id']."' ></textarea></div>\";";
                    $form_block_ids[] = 'form_'.$res['id']."_".$res2['id'];
                    $form_block_types[] = $res2['form_block_type'];
                }
                else if(($res2['form_block_type'] == 'submitButton')){

                    $html .= '
                    if($form_el_access["'.$ctr.'"]<=$user_level){
                    $disabled="";
                    if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="disabled";
                        }else{
                        $disabled="";
                        }
                    echo \'<div class="input_'.$res2['form_block_type'].'"><i class="fa fa-caret-left"></i><input class="'.$res2['form_block_type'].'" type="button" name="form_'.$res['id'].'_'.$res2['id'].'" id="form_'.$res['id'].'_'.$res2['id'].'" value="'.$res2['value'].'" \'.$disabled.\'/></div>\';
                    }
                    ';
                    //$form_block_ids[] = 'form_'.$res['id']."_".$res2['id'];
                }
                else if(($res2['form_block_type'] == 'button')){
                    $html .= '
                    if($form_el_access["'.$ctr.'"]<=$user_level){
                    $disabled="";
                    if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="disabled";
                        }else{
                        $disabled="";
                        }
                    echo \'<div class="input_'.$res2['form_block_type'].'"><input class="'.$res2['form_block_type'].'" type="button" name="form_'.$res['id'].'_'.$res2['id'].'" id="form_'.$res['id'].'_'.$res2['id'].'" value="'.$res2['value'].'" \'.$disabled.\' /></div>\';
                    }';
                }
                else if(($res2['form_block_type'] == 'label')){
                    $html .= '
                    if($form_el_access["'.$ctr.'"]<=$user_level){
                    if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="disabled";
                        }else{
                        $disabled="";
                        }
                    echo \'<div class="input_'.$res2['form_block_type'].'"><label class="'.$res2['form_block_type'].'" name="form_'.$res['id'].'_'.$res2['id'].'" id="form_'.$res['id'].'_'.$res2['id'].'">'. $res2['value'].'</label></div>\';
                    }';
                }
                else if(($res2['form_block_type'] == 'hint')){
                    $html .= '
                    if($form_el_access["'.$ctr.'"]<=$user_level){
                    if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="disabled";
                        }else{
                        $disabled="";
                        }
                    echo \'<div class="input_'.$res2['form_block_type'].'"><div class="'.$res2['form_block_type'].'" name="form_'.$res['id'].'_'.$res2['id'].'" id="form_'.$res['id'].'_'.$res2['id'].'" >'.$res2['value'].'</div></div>\';
                    }';
                }
                else if(($res2['form_block_type'] == 'heading')){
                    $html .= '
                    if($form_el_access["'.$ctr.'"]<=$user_level){
                    if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="disabled";
                        }else{
                        $disabled="";
                        }
                    echo \'<div class="input input_'.$res2['form_block_type'].'"><div class="'.$res2['form_block_type'].'" name="form_'.$res['id'].'_'.$res2['id'].'" id="form_'.$res['id'].'_'.$res2['id'].'" >'.$res2['value'].'</div></div>\';
                    }';
                }
                else if(($res2['form_block_type'] == 'subheading')){
                    $html .= '
                    if($form_el_access["'.$ctr.'"]<=$user_level){
                    $disabled="";
                    if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="disabled";
                        }else{
                        $disabled="";
                        }
                    echo \'<div class="input input_'.$res2['form_block_type'].'"><div class="'.$res2['form_block_type'].'" name="form_'.$res['id'].'_'.$res2['id'].'" id="form_'.$res['id'].'_'.$res2['id'].'" >'.$res2['value'].'</div></div>\';
                    }';
                }
                else if(($res2['form_block_type'] == 'copy')){
                    $valley = $res2['value'];

                    $html .= '
                    if($form_el_access["'.$ctr.'"]<=$user_level){
                    if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="disabled";
                        }else{
                        $disabled="";
                        }
                    echo \'<div class="input input_'.$res2['form_block_type'].'"><div class="'.$res2['form_block_type'].'" name="form_'.$res['id'].'_'.$res2['id'].'" id="form_'.$res['id'].'_'.$res2['id'].'" >'.$valley.'</div></div>\';
                   }' ;
                }
                else if(($res2['form_block_type'] == 'upload')){
                    $html .= '
                    if($form_el_access["'.$ctr.'"]<=$user_level){
                    $disabled="";
                    if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="disabled";
                        }else{
                        $disabled="";
                        }
                    $ctr = 0;

                    for($i=0;$i<count($form_el_id);$i++){

                    if(in_array(strval(' . $res2['id'] . '),$form_el_id)){
                        $ctr+=1;
                        $myVal = "";
                        $key = array_search(strval(' . $res2['id'] . '),$form_el_id);
                        $myVal = $form_el_val[$key];
                        $myID = $form_el_valID[$key];
                        $form_el_id[$key] = "ioioioioio";
                      if($myVal != ""){
                      if($disabled==""){
                       echo \'<div class="uploaded_file"><a href="uploads/\'.$myVal.\'">\'.$myVal.\' </a>
                       <span><a href="deleteAtt.php?id=\'.$myID.\'&form_id='.$res['id'].'"> Delete </a></span>
                       </div>\';
                       }else{
                       echo \'<div class="uploaded_file"><a href="uploads/\'.$myVal.\'">\'.$myVal.\' </a>
                       </div>\';
                       }
                        }

                    }
                    }
                    echo \'<div class="input_' . $res2['form_block_type'] . '">

                    <input class="uploader ' . $res2['form_block_type'] . '" type="button" name="form_' . $res['id'] . '_' . $res2['id'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '" placeholder="' . $res2['value'] . '"  value="upload" onClick="showUpload(' . $res2['id'] . ')"  \'.$disabled.\' />

                    </div>\';
                   }';
                    //$html .= "echo '<div class='input input_".$res2['form_block_type']."'><input class='".$res2['form_block_type']."' type='file' name='form_".$res['id']."_".$res2['id']."' id='form_".$res['id']."_".$res2['id']."' placeholder='".$res2['value']."'/></div>';";
                    //$form_block_ids[] = 'form_'.$res['id']."_".$res2['id'];
                    //$form_block_types[] = $res2['form_block_type'];
                }
                else if(($res2['form_block_type'] == 'checkbox')){
                    $html .= '
                    if($form_el_access["'.$ctr.'"]<=$user_level){
                    if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="disabled";
                        }else{
                        $disabled="";
                        }
                    if(in_array(strval(' . $res2['id'] . '),$form_el_id)){
                        $key = array_search(strval(' . $res2['id'] . '),$form_el_id);
                        $myVal = $form_el_val[$key];
                        if($myVal == "on"){
                            echo \'<div class="input_' . $res2['form_block_type'] . '"><input class="' . $res2['form_block_type'] . '" type="checkbox" name="form_' . $res['id'] . '_' . $res2['id'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '" checked \'.$disabled.\' />'.$res2['value'].'</div>\';
                        }
                        else{
                            echo \'<div class="input_' . $res2['form_block_type'] . '"><input class="' . $res2['form_block_type'] . '" type="checkbox" name="form_' . $res['id'] . '_' . $res2['id'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '"  \'.$disabled.\' />'.$res2['value'].'</div>\';
                        }
                    }else{
                       echo \'<div class="input_' . $res2['form_block_type'] . '"><input class="' . $res2['form_block_type'] . '" type="checkbox" name="form_' . $res['id'] . '_' . $res2['id'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '"  \'.$disabled.\'/>'.$res2['value'].'</div>\';
                    }
                    }';
                    //$html .= "echo '<div class='input input_".$res2['form_block_type']."'><input class='".$res2['form_block_type']."' type='checkbox' name='form_".$res['id']."_".$res2['id']."' id='form_".$res['id']."_".$res2['id']."' />".$res2['value']."</div>';";
                    $str = 'form_'.$res['id']."_".$res2['id'];
                    if(!in_array($str,$form_block_ids)) {
                        $form_block_ids[] = 'form_' . $res['id'] . "_" . $res2['id'];
                        $form_block_types[] = $res2['form_block_type'];
                    }
                }
                else if(($res2['form_block_type'] == 'radio')){
                    $html .= '
                    if($form_el_access["'.$ctr.'"]<=$user_level){
                    if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="disabled";
                        }else{
                        $disabled="";
                        }
                    if(in_array("' . $res2['extra'] . '",$form_el_id)){
                        $key = array_search("' . $res2['extra'] . '",$form_el_id);
                        $myVal = $form_el_val[$key];
                        if($myVal == "'.$res2['value'].'"){
                            echo \'<div class="input input_' . $res2['form_block_type'] . '"><input class="' . $res2['form_block_type'] . '" type="radio" name="form_' . $res['id'] . '_' . $res2['extra'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '" value="'.$res2['value'].'" checked \'.$disabled.\' />'.$res2['value'].'</div>\';
                        }
                        else{
                            echo \'<div class="input input_' . $res2['form_block_type'] . '"><input class="' . $res2['form_block_type'] . '" type="radio" name="form_' . $res['id'] . '_' . $res2['extra'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '"  value="'.$res2['value'].'" \'.$disabled.\' />'.$res2['value'].'</div>\';
                        }
                    }else{
                       echo \'<div class="input input_' . $res2['form_block_type'] . '"><input class="' . $res2['form_block_type'] . '" type="radio" name="form_' . $res['id'] . '_' . $res2['extra'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '" value="'.$res2['value'].'" \'.$disabled.\' />'.$res2['value'].'</div>\';
                    }
                    }';
                   // $html .= "echo '<div class='input input_".$res2['form_block_type']."'><input class='".$res2['form_block_type']."' type='radio' name='form_".$res['id']."_".$res2['extra']."' id='form_".$res['id']."_".$res2['id']."' value='".$res2['value']."'  />".$res2['value']."</div>';";
                    $str = 'form_'.$res['id']."_".$res2['extra'];
                    if(!in_array($str,$form_block_ids)) {
                        $form_block_ids[] = 'form_' . $res['id'] . "_" . $res2['extra'];
                        $form_block_types[] = $res2['form_block_type'];
                    }
                }
                else if(($res2['form_block_type'] == 'dropdown')){

                    $str = 'form_'.$res['id']."_".$res2['extra'];
                    if(!in_array($str,$form_block_ids)) {
                        $form_block_ids[] = 'form_' . $res['id'] . "_" . $res2['extra'];
                        $form_block_types[] = $res2['form_block_type'];
                    }
                   // $html .= "<option class='".$res2['extra']."'  value='".$res2['value']."' id='form_".$res['id']."_".$res2['id']."' >".$res2['value']."</option>";
                    $htmlCode = str_get_html($html);
                    $es = $htmlCode->find('select.' . $res2['extra'] . '', 0)->innertext;

                    if($es){
                        $es_inner = $es;
                        $es_inner .= '
                        if($form_el_access["'.$ctr.'"]<=$user_level){
                        if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="disabled";
                        }else{
                        $disabled="";
                        }
                        if(in_array("' . $res2['extra'] . '",$form_el_id)){
                        $key = array_search("' . $res2['extra'] . '",$form_el_id);
                        $myVal = $form_el_val[$key];
                        if($myVal == "'.$res2['value'].'"){
                         echo \'<option class="' . $res2['extra'] . '"  value="' . $res2['value'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '" selected  \'.$disabled.\' >' . $res2['value'] . '</option>\';
                         }else{
                          echo \'<option class="' . $res2['extra'] . '"  value="' . $res2['value'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '" \'.$disabled.\' >' . $res2['value'] . '</option>\';
                         }}
                         else{
                          echo \'<option class="' . $res2['extra'] . '"  value="' . $res2['value'] . '" id="form_' . $res['id'] . '_' . $res2['id'] . '" \'.$disabled.\' >' . $res2['value'] . '</option>\';
                         }}echo \'';
                        echo $es_inner;
                        $htmlCode->find('select.'.$res2['extra'].'',0)->innertext = $es_inner;
                        $html = $htmlCode;
                    }
                    else{
                        $html .= '
                        if($form_el_access["'.$ctr.'"]<=$user_level){
                        if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="disabled";
                        }else{
                        $disabled="";
                        }
                        echo \'<div class="input input_'.$res2['form_block_type'].'"><select class="'.$res2['extra'].'" name="form_'.$res['id'].'_'.$res2['extra'].'" \'.$disabled.\'>\';
                        }';
                        $html .= '
                        if($form_el_access["'.$ctr.'"]<=$user_level){
                        if($form_el_access["'.$ctr.'"]<$user_level){
                        $disabled="disabled";
                        }else{
                        $disabled="";
                        }
                        if(in_array("' . $res2['extra'] . '",$form_el_id)){
                        $key = array_search("' . $res2['extra'] . '",$form_el_id);
                        $myVal = $form_el_val[$key];
                        if($myVal == "'.$res2['value'].'"){
                            echo \'<option class="'.$res2['extra'].'"  value="'.$res2['value'].'" id="form_'.$res['id'].'_'.$res2['id'].'"  selected \'.$disabled.\'>'.$res2['value'].'</option>\';
                            }else{
                            echo \'<option class="'.$res2['extra'].'"  value="'.$res2['value'].'" id="form_'.$res['id'].'_'.$res2['id'].'" \'.$disabled.\' >'.$res2['value'].'</option>\';
                            }}
                            else{
                            echo \'<option class="'.$res2['extra'].'"  value="'.$res2['value'].'" id="form_'.$res['id'].'_'.$res2['id'].'" \'.$disabled.\'>'.$res2['value'].'</option>\';
                            }}';

                        $html .= '
                    if($form_el_access["'.$ctr.'"]<=$user_level){
                    </select></div>\';
                    }';
                    }

                    $html .= '}';
                }

                $ctr+=1;

            }
            $_SESSION['data'] = $form_block_ids;
            $_SESSION['types'] = $form_block_types;
            //$html .= "<input  type='hidden' name='form_block_ids' value='".$form_block_ids."' />";
            $html .= "echo \"</form>\";

            ";

            //create html file
            $htmlCode = str_get_html($html);



            $urlSave = "forms/form_".$res['id'].".php";

            $htmlCode->save($urlSave);

       // }//close if file does not exist
    }

    close_db_connection($link);
}

create_form();