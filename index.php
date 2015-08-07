<?php
require_once('assets/app-config.php');
require_once('assets/phpmailer/PHPMailerAutoload.php');

define ('doc_root_files',$_SERVER['DOCUMENT_ROOT']."");

/***** PRELOAD ASSETS *****/
function ListIn($dir, $prefix = '') {
    $dir = rtrim($dir, '\\/');
    $result = array();

    foreach (array_diff(scandir($dir), array('..', '.', 'src', '.DS_Store')) as $f) {
        if (is_dir("$dir/$f")) {
            $result = array_merge($result, ListIn("$dir/$f", "$prefix$f/"));
        } else {
            $result[] = $prefix.$f;
        }
    }

    return $result;
}

function PreLoadAsset()
{
    $imgs = ListIn('img');
    foreach ($imgs as $key => $img_src) {
        echo '<img src="img/'.$img_src.'"/><br>';
    }
}

/***** FORMATTING *****/
function print_f($var)
{
    echo "<pre>";
    print_r($var);
    echo "</pre>";
}

function my_strip_tags($tag,$value)
{
    $value = str_replace('<'.$tag.'>','',$value);
    $value = str_replace('</'.$tag.'>','',$value);
    return $value;
}

function esc_sql($value)
{
    global $db;
    $value = mysqli_real_escape_string($db,$value);
    return $value;
}

/***** OBJECT TO ARRAY *****/
function to_array ($object) {
    if(!is_object($object) && !is_array($object))
        return $object;
    return array_map('to_array', (array) $object);
}

/***** IF IN MULTI ARRAY *****/
function in_array_multi($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_multi($needle, $item, $strict))) {
            return true;
        }
    }
    return false;
}

/***** BUILD ARRAY TREE *****/
function build_tree(array &$elements, $parentId = 0) {

    $branch = array();
    foreach ($elements as &$element) {

        if ($element['parent_id'] == $parentId) {
            $children = build_tree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[$element['id']] = $element;
            unset($element);
        }
    }
    return $branch;
}

/***** DISPLAY ARRAY TREE ITEMS *****/
function display_tree($items) {
    foreach($items as $item) {
        echo "<li id={$item['id']}><div>{$item['numbering']} {$item['heading']}</div>";
        if ($item['children']) {
            echo '<ol>';
            displayItem( $item['children']);
            echo '</ol>';
        }
        echo "</li>";
    }
}

// FORMAT FILE NAME
function format_file_name($file_name,$time)
{
    if ($file_name != "")
    {
        $file_name = str_replace(" ","_",$file_name);
        $file_name = str_replace("%20","_",$file_name);
        $file_name = str_replace("&","_",$file_name);
        $file_name = str_replace("?","_",$file_name);
        return $time."_".$file_name;
    }
    else
    {
        return "";
    }
}

//UPLOAD IMAGES
function upload_file($file,$folder,$time)
{
    if (!empty($file['tmp_name']) && $folder != "")
    {
        //This is the directory where images will be saved
        //$target = doc_root_files."uploads/".$folder."/";
        $target = "uploads/".$folder."/";

        $type = explode(".",$file['name']);
        $file_name = format_file_name($file['name'],$time);
        $type = end($type);
        $target = $target.$file_name;
        //Writes the photo to the server
        if(move_uploaded_file($file['tmp_name'], $target))
        {
            $return['msg'] = "File Uploaded Successfully";
        }
        else
        {
            $return['msg'] = "Uploaded Failed";
        }
    }
    else
    {
        $return['msg'] = "Please select a file and folder";
    }

}

/***** USERS *****/
// CHECK IF USER EXISTS
function username_exists($username)
{
    global $db;
    $Q = "SELECT * FROM `users` WHERE `username` = '$username'";
    $result = $db->query($Q);
    if ($result->num_rows == 0)
    {
        return false;
    }
    else
    {
        return true;
    }
}

// ADD USER
function add_user($arr)
{
    global $db;
    $user_exists = username_exists($arr['username']);
    if (($arr['firstname'] && $arr['lastname'] && $arr['email'] && $arr['username'] && $arr['password'] && $arr['role']) != "" && $user_exists == false ) // REQUIREED FIELDS
    {
        $Q = "INSERT INTO `users` ";
        $field_str = "(";
        $value_str = " VALUES (";
        foreach ($arr as $key => $value)
        {
            $value = mysqli_real_escape_string($db,$value);
            $field_str .= "`".$key."`,";
            $value_str .= "'".$value."',";
        }
        $field_str = substr($field_str,0,-1).")";
        $value_str = substr($value_str,0,-1).")";
        $Q .= $field_str.$value_str;
        $db->query($Q); //or die ($db->error);
        return $db->insert_id;
    }

}

// UPDATE USER
function update_user($arr)
{
    global $db;

    if (($arr['id'] && $arr['firstname'] && $arr['lastname'] && $arr['email'] && $arr['username'] && $arr['password'] && $arr['role']) != "") // REQUIREED FIELDS
    {
        $Q = "UPDATE `users` SET ";
        $update_str = "";
        foreach ($arr as $key => $value)
        {
            if ($key != "id")
            {
                $value = mysqli_real_escape_string($db,$value);
                $update_str .= "`".$key."` = '".$value."',";
            }
        }
        $update_str = substr($update_str,0,-1)." WHERE `id` = '".$arr['id']."'";
        $Q .= $update_str;
        $db->query($Q); //or die ($db->error);
        return $arr['id'];
    }

}

// DELETE USER
function delete_user($user_id)
{
    global $db;
    $Q = "DELETE FROM `users` WHERE `id` = '$user_id'";
    $db->query($Q); //or die ($db->error);
    return $result['msg'] = "User removed successfully";
}

//GET USERS
function get_users()
{
    global $db;
    $users = array();
    $Q = "SELECT * FROM `users`";
    $result = $db->query($Q);
    while ($user = $result->fetch_object())
    {
        $users[] = $user;
    }
    return $users;
}

//GET USER BY ID
function get_user($user_id)
{
    global $db;
    $Q = "SELECT * FROM `users` WHERE `id` = '$user_id' LIMIT 0,1";
    $result = $db->query($Q);
    while ($user = $result->fetch_object())
    {
        return $user;
    }
}

// VIEW USERS
function view_users()
{

    $users = get_users();
    echo '<table id="data_table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th class="txt_c">UserID</th>';
    echo '<th>Name</th>';
    echo '<th>Surname</th>';
    echo '<th>Email</th>';
    echo '<th>Username</th>';
    //echo '<th>Rights</th>';
    echo '<th>Role</th>';
    echo '<th></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($users as $user)
    {

        echo '<tr>';
        echo '<td width="5" class="txt_c">'.$user->id.'</td>';
        echo '<td>'.$user->firstname.'</td>';
        echo '<td>'.$user->lastname.'</td>';
        echo '<td>'.$user->email.'</td>';
        echo '<td>'.$user->username.'</td>';
        //echo '<td style="text-transform:capitalize;">'.$user->user_rights.'</td>';
        echo '<td width="">'.$user->role.'</td>';
        echo '<td width="120" class="txt_c">
		<div class="actions">
		<a class="action-edit" onclick="ajax_load_file(\'#middle\',\'ajax-master.php?action=edit-user&id='.$user->id.'\',false)" href="#edit-user">Edit</a>';
        //if ($status == 'active') { echo '<a href="ajax-master.php?action=suspend_agent&id='.$agent_id.'">Suspend</a>'; }
        //if ($status == 'suspended') { echo '<a href="ajax-master.php?action=activate_agent&id='.$agent_id.'">Activate</a>'; }
        echo '<a data-reveal-id="modalWindowSmall" class="action-trash" onclick="ajax_load_file(\'#modalWindowSmall\',\'ajax-master.php?action=confirm-delete&url=ajax-master.php?action=delete-user&id='.$user->id.'\',false)" href="#delete-user">Remove</a>';
        echo '</div>
		</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    //log_activity($_SESSION['current_user_id'],'view admin users');

}

// LOGIN
function login ($username,$password,$auth_code="")
{
    global $db;
    $password = md5($password);
    if ($auth_code == "")
    {
        $Q = "SELECT * FROM `users` WHERE `username` = '$username' AND `password` = '$password'";
        $result = $db->query($Q);
        if ($result->num_rows == 1)
        {
            while ($user = $result->fetch_object())
            {
                $new_auth_code = send_auth_mail($user->email);
                change_auth_code($user->id,$new_auth_code);
                return "auth required";
            }
        }
        else
        {
            return "invalid username or password";
        }
    }
    else
    {
        $Q = "SELECT * FROM `users` WHERE `username` = '$username' AND `password` = '$password' AND `auth_code` = '$auth_code'";
        $result = $db->query($Q);
        if ($result->num_rows == 1)
        {
            while ($user = $result->fetch_object())
            {
                session_start();
                $_SESSION['user_id'] = $user->id;
                $_SESSION['is_logged_in'] = true;
                return "success";
            }
        }
        else
        {
            return "invalid authentication code";
        }
    }
}

//LOGOUT
function logout()
{
    ob_start();
    session_start();
    $_SESSION['is_logged_in'] = "";
    $_SESSION['user_id'] = "";
    header("location:?logged-out");
}

// CHANGE AUTH CODE
function change_auth_code($user_id,$new_auth_code)
{
    global $db;
    $Q = "UPDATE `users` SET `auth_code` = '$new_auth_code' WHERE `id` = '$user_id'";
    $db->query($Q);
}

// SEND AUTHENTICATION CODE
function send_auth_mail($email_address)
{

    //$unique_code = "1234";
    $unique_code = bin2hex(openssl_random_pseudo_bytes(2));

    $email_copy = $unique_code;

    // PHP MAIL SETTINGS
    $email = new PHPMailer();
    $email->IsHTML(true);
    $email->IsSMTP();
    $email->SMTPDebug = 0;
    $email->SMTPAuth = true;
    $email->Host = "smtp.staging.edgemanagementsystem.co.za";
    $email->Port = 587;
    $email->Username = "no-reply@staging.edgemanagementsystem.co.za";
    $email->Password = "n0r3Ply001";
    $email->setFrom('no-reply@staging.edgemanagementsystem.co.za','Edge Management System');
    $email->addReplyTo('no-reply@staging.edgemanagementsystem.co.za','EMS - No-Reply');
    //$email->AddBCC($bcc);
    $email->Subject = "EMS - Authentication Code";
    $email->Body = $email_copy;
    $email->AddAddress( $email_address );

    /*// EMAIL ATTACHMENTS
    $attachments = get_setting($email_name.'_attachment',$language);
    foreach($attachments as $attachment)
    {
        $email->AddAttachment( doc_root_files.'/files/'.$attachment->value );
    }*/

    // SEND EMAIL
    $email->Send();

    return $unique_code;
}


/***** AUTHORS *****/
// ADD AUTHOR
function add_author($arr)
{
    global $db;
    if (($arr['firstname'] && $arr['lastname'] && $arr['email']) != "") // REQUIREED FIELDS
    {
        $Q = "INSERT INTO `authors` ";
        $field_str = "(";
        $value_str = " VALUES (";
        foreach ($arr as $key => $value)
        {
            $value = mysqli_real_escape_string($db,$value);
            $field_str .= "`".$key."`,";
            $value_str .= "'".$value."',";
        }
        $field_str = substr($field_str,0,-1).")";
        $value_str = substr($value_str,0,-1).")";
        $Q .= $field_str.$value_str;
        $db->query($Q); //or die ($db->error);
        return $db->insert_id;
    }

}

// UPDATE AUTHOR
function update_author($arr)
{
    global $db;

    if (($arr['id'] && $arr['firstname'] && $arr['lastname'] && $arr['email']) != "") // REQUIREED FIELDS
    {
        $Q = "UPDATE `authors` SET ";
        $update_str = "";
        foreach ($arr as $key => $value)
        {
            if ($key != "id")
            {
                $value = mysqli_real_escape_string($db,$value);
                $update_str .= "`".$key."` = '".$value."',";
            }
        }
        $update_str = substr($update_str,0,-1)." WHERE `id` = '".$arr['id']."'";
        $Q .= $update_str;
        $db->query($Q); //or die ($db->error);
        return $arr['id'];
    }

}

// DELETE AUTHOR
function delete_author($author_id)
{
    global $db;
    $Q = "DELETE FROM `authors` WHERE `id` = '$author_id'";
    $db->query($Q); //or die ($db->error);
    return $result['msg'] = "Author removed successfully";
}

//GET AUTHORS
function get_authors()
{
    global $db;
    $authors = array();
    $Q = "SELECT * FROM `authors`";
    $result = $db->query($Q);
    while ($author = $result->fetch_object())
    {
        $authors[] = $author;
    }
    return $authors;
}

//GET AUTHOR BY ID
function get_author($author_id)
{
    global $db;
    $Q = "SELECT * FROM `authors` WHERE `id` = '$author_id' LIMIT 0,1";
    $result = $db->query($Q);
    while ($author = $result->fetch_object())
    {
        return $author;
    }
}

// VIEW AUTHORS
function view_authors()
{

    $authors = get_authors();
    echo '<table id="data_table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th class="txt_c">AuthorID</th>';
    echo '<th>Name</th>';
    echo '<th>Surname</th>';
    echo '<th>Email</th>';
    echo '<th>Telephone</th>';
    echo '<th></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($authors as $author)
    {

        echo '<tr>';
        echo '<td width="5" class="txt_c">'.$author->id.'</td>';
        echo '<td>'.$author->firstname.'</td>';
        echo '<td>'.$author->lastname.'</td>';
        echo '<td>'.$author->email.'</td>';
        echo '<td>'.$author->telephone.'</td>';
        echo '<td width="120" class="txt_c">
		<div class="actions">
		<a class="action-edit" onclick="ajax_load_file(\'#middle\',\'ajax-master.php?action=edit-author&id='.$author->id.'\',false)" href="#edit-author">Edit</a>';
        echo '<a data-reveal-id="modalWindowSmall" class="action-trash" onclick="ajax_load_file(\'#modalWindowSmall\',\'ajax-master.php?action=confirm-delete&url=ajax-master.php?action=delete-author&id='.$author->id.'\',false)" href="#delete-author">Remove</a>';
        echo '</div>
		</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    //log_activity($_SESSION['current_user_id'],'view admin users');

}


// LINK AUTHOR
function link_author($type,$type_id,$author_ids)
{
    global $db;
    if ($type == 'learning_area') { $laid = $type_id; } else { $laid = null; }
    if ($type == 'unit') { $uid = $type_id; } else { $uid = null; }
    if ($type == 'unit_section') { $sid = $type_id; } else { $sid = null; }

    $Q = "SELECT * FROM `authors_reference` WHERE `".$type."_id` = '$type_id'"; // AND  `components_id` = '$component_id'";
    $result = $db->query($Q);

    while ($authors = $result->fetch_object())
    {
        if (in_array($authors->authors_id,$author_ids))
        {
            $key = array_search($authors->authors_id,$author_ids);
            unset($author_ids[$key]);
        }
        else
        {
            $Q = "DELETE FROM `authors_reference` WHERE `".$type."_id` = '$type_id' AND  `authors_id` = '$authors->authors_id'";
            $db->query($Q);
            $key = array_search($authors->authors_id,$author_ids);
            unset($author_ids[$key]);
        }
    }
    foreach ($author_ids as $author_id)
    {
        $Q = "INSERT INTO `authors_reference` VALUES('','$author_id','$laid','$uid','$sid')";
        $db->query($Q); //or die ($db->error);
    }
}


//GET LINKED AUTHORS
function get_linked_authors($type,$type_id)
{
    global $db;
    $authors = array();
    $Q = "SELECT * FROM `authors_reference` WHERE `".$type."_id` = '$type_id'";
    $result = $db->query($Q);
    while ($author = $result->fetch_object())
    {
        $authors[] = $author->authors_id;
    }
    return $authors;
}

/***** CLIENTS *****/
// ADD CLIENT
function add_client($arr)
{
    global $db;
    if (($arr['company_name'] && $arr['contact_firstname'] && $arr['contact_lastname'] && $arr['email']) != "") // REQUIREED FIELDS
    {
        $Q = "INSERT INTO `clients` ";
        $field_str = "(";
        $value_str = " VALUES (";
        foreach ($arr as $key => $value)
        {
            $value = mysqli_real_escape_string($db,$value);
            $field_str .= "`".$key."`,";
            $value_str .= "'".$value."',";
        }
        $field_str = substr($field_str,0,-1).")";
        $value_str = substr($value_str,0,-1).")";
        $Q .= $field_str.$value_str;
        $db->query($Q); //or die ($db->error);
        return $db->insert_id;
    }

}

// UPDATE CLIENT
function update_client($arr)
{
    global $db;

    if (($arr['id'] && $arr['company_name'] && $arr['contact_firstname'] && $arr['contact_lastname'] && $arr['email']) != "") // REQUIREED FIELDS
    {
        $Q = "UPDATE `clients` SET ";
        $update_str = "";
        foreach ($arr as $key => $value)
        {
            if ($key != "id")
            {
                $value = mysqli_real_escape_string($db,$value);
                $update_str .= "`".$key."` = '".$value."',";
            }
        }
        $update_str = substr($update_str,0,-1)." WHERE `id` = '".$arr['id']."'";
        $Q .= $update_str;
        $db->query($Q); //or die ($db->error);
        return $arr['id'];
    }

}

// DELETE CLIENT
function delete_client($client_id)
{
    global $db;
    $Q = "DELETE FROM `clients` WHERE `id` = '$client_id'";
    $db->query($Q); //or die ($db->error);
    return $result['msg'] = "Client removed successfully";
}

//GET CLIENTS
function get_clients()
{
    global $db;
    $clients = array();
    $Q = "SELECT * FROM `clients`";
    $result = $db->query($Q);
    while ($client = $result->fetch_object())
    {
        $clients[] = $client;
    }
    return $clients;
}

//GET CLIENT BY ID
function get_client($client_id)
{
    global $db;
    $Q = "SELECT * FROM `clients` WHERE `id` = '$client_id' LIMIT 0,1";
    $result = $db->query($Q);
    while ($client = $result->fetch_object())
    {
        return $client;
    }
}

// VIEW CLIENTS
function view_clients()
{

    $clients = get_clients();
    echo '<table id="data_table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th class="txt_c">ClientID</th>';
    echo '<th>Company</th>';
    echo '<th>Contact Name</th>';
    echo '<th>Contact Surname</th>';
    echo '<th>Email</th>';
    echo '<th>Telephone</th>';
    echo '<th></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($clients as $client)
    {

        echo '<tr>';
        echo '<td width="5" class="txt_c">'.$client->id.'</td>';
        echo '<td>'.$client->company_name.'</td>';
        echo '<td>'.$client->contact_firstname.'</td>';
        echo '<td>'.$client->contact_lastname.'</td>';
        echo '<td>'.$client->email.'</td>';
        echo '<td>'.$client->telephone.'</td>';
        echo '<td width="120" class="txt_c">
		<div class="actions">
		<a class="action-edit" onclick="ajax_load_file(\'#middle\',\'ajax-master.php?action=edit-client&id='.$client->id.'\',false)" href="#edit-client">Edit</a>';
        echo '<a data-reveal-id="modalWindowSmall" class="action-trash" onclick="ajax_load_file(\'#modalWindowSmall\',\'ajax-master.php?action=confirm-delete&url=ajax-master.php?action=delete-client&id='.$client->id.'\',false)" href="#delete-client">Remove</a>';
        echo '</div>
		</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    //log_activity($_SESSION['current_user_id'],'view admin users');

}

/***** STATUS *****/
// ADD STATUS
function add_status($arr)
{
    global $db;
    if (($arr['status']) != "") // REQUIREED FIELDS
    {

        $Q = "INSERT INTO `status` ";
        $field_str = "(";
        $value_str = " VALUES (";
        foreach ($arr as $key => $value)
        {
            $value = mysqli_real_escape_string($db,$value);
            $field_str .= "`".$key."`,";
            $value_str .= "'".$value."',";
        }
        $field_str = substr($field_str,0,-1).")";
        $value_str = substr($value_str,0,-1).")";
        $Q .= $field_str.$value_str;
        $db->query($Q); //or die ($db->error);
        return $db->insert_id;
    }

}

// UPDATE STATUS
function update_status($arr)
{
    global $db;
    if (($arr['id'] && $arr['status']) != "") // REQUIREED FIELDS
    {
        $Q = "UPDATE `status` SET ";
        $update_str = "";
        foreach ($arr as $key => $value)
        {
            if ($key != "id")
            {
                $value = mysqli_real_escape_string($db,$value);
                $update_str .= "`".$key."` = '".$value."',";
            }
        }
        $update_str = substr($update_str,0,-1)." WHERE `id` = '".$arr['id']."'";
        $Q .= $update_str;
        $db->query($Q); //or die ($db->error);
        return $arr['id'];
    }

}

// DELETE STATUS
function delete_status($status_id)
{
    global $db;
    $Q = "DELETE FROM `status` WHERE `id` = '$status_id'";
    $db->query($Q); //or die ($db->error);
    return $result['msg'] = "Status removed successfully";
}

//GET STATUSES
function get_statuses()
{
    global $db;
    $statuses = array();
    $Q = "SELECT * FROM `status`";
    $result = $db->query($Q);
    while ($status = $result->fetch_object())
    {
        $statuses[] = $status;
    }
    return $statuses;
}

//GET STATUS BY ID
function get_status($status_id)
{
    global $db;
    $Q = "SELECT * FROM `status` WHERE `id` = '$status_id' LIMIT 0,1";
    $result = $db->query($Q);
    while ($status = $result->fetch_object())
    {
        return $status;
    }
}

// VIEW STATUSES
function view_statuses()
{

    $statuses = get_statuses();
    echo '<table id="data_table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th width="5" class="txt_c">StatusID</th>';
    echo '<th>Status</th>';
    echo '<th></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($statuses as $status)
    {

        echo '<tr>';
        echo '<td width="5" class="txt_c">'.$status->id.'</td>';
        echo '<td>'.$status->status.'</td>';
        echo '<td width="120" class="txt_c">
		<div class="actions">
		<a class="action-edit" onclick="ajax_load_file(\'#middle\',\'ajax-master.php?action=edit-status&id='.$status->id.'\',false)" href="#edit-status">Edit</a>';
        echo '<a data-reveal-id="modalWindowSmall" class="action-trash" onclick="ajax_load_file(\'#modalWindowSmall\',\'ajax-master.php?action=confirm-delete&url=ajax-master.php?action=delete-status&id='.$status->id.'\',false)" href="#delete-status">Remove</a>';
        echo '</div>
		</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    //log_activity($_SESSION['current_user_id'],'view admin users');

}


/***** CONTENT STYLE *****/
// ADD CONTENT STYLE
function add_content_style($arr)
{
    global $db;
    if (($arr['content_style']) != "") // REQUIREED FIELDS
    {

        $Q = "INSERT INTO `content_style` ";
        $field_str = "(";
        $value_str = " VALUES (";
        foreach ($arr as $key => $value)
        {
            $value = mysqli_real_escape_string($db,$value);
            $field_str .= "`".$key."`,";
            $value_str .= "'".$value."',";
        }
        $field_str = substr($field_str,0,-1).")";
        $value_str = substr($value_str,0,-1).")";
        $Q .= $field_str.$value_str;
        $db->query($Q); //or die ($db->error);
        return $db->insert_id;
    }

}

// UPDATE CONTENT STYLE
function update_content_style($arr)
{
    global $db;
    if (($arr['id'] && $arr['content_style']) != "") // REQUIREED FIELDS
    {
        $Q = "UPDATE `content_style` SET ";
        $update_str = "";
        foreach ($arr as $key => $value)
        {
            if ($key != "id")
            {
                $value = mysqli_real_escape_string($db,$value);
                $update_str .= "`".$key."` = '".$value."',";
            }
        }
        $update_str = substr($update_str,0,-1)." WHERE `id` = '".$arr['id']."'";
        $Q .= $update_str;
        $db->query($Q); //or die ($db->error);
        return $arr['id'];
    }

}

// DELETE CONTENT STYLE
function delete_content_style($content_style_id)
{
    global $db;
    $Q = "DELETE FROM `content_style` WHERE `id` = '$content_style_id'";
    $db->query($Q); //or die ($db->error);
    return $result['msg'] = "Content Style removed successfully";
}

//GET CONTENT STYLES
function get_content_styles()
{
    global $db;
    $content_styles = array();
    $Q = "SELECT * FROM `content_style` order by `media_type`,`content_Style` ASC";
    $result = $db->query($Q);
    while ($content_style = $result->fetch_object())
    {
        $content_styles[] = $content_style;
    }
    return $content_styles;
}

//GET CONTENT STYLE BY ID
function get_content_style($content_style_id)
{
    global $db;
    $Q = "SELECT * FROM `content_style` WHERE `id` = '$content_style_id' LIMIT 0,1";
    $result = $db->query($Q);
    while ($content_style = $result->fetch_object())
    {
        return $content_style;
    }
}

// VIEW CONTENT STYLES
function view_content_styles()
{

    $content_styles = get_content_styles();
    echo '<table id="data_table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th width="5" class="txt_c">StyleID</th>';
    echo '<th>Content Style</th>';
    echo '<th>Media Type</th>';
    echo '<th></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($content_styles as $content_style)
    {

        echo '<tr>';
        echo '<td width="5" class="txt_c">'.$content_style->id.'</td>';
        echo '<td>'.$content_style->content_style.'</td>';
        echo '<td>'.$content_style->media_type.'</td>';
        echo '<td width="120" class="txt_c">
		<div class="actions">
		<a class="action-edit" onclick="ajax_load_file(\'#middle\',\'ajax-master.php?action=edit-content-style&id='.$content_style->id.'\',false)" href="#edit-content-style">Edit</a>';
        echo '<a data-reveal-id="modalWindowSmall" class="action-trash" onclick="ajax_load_file(\'#modalWindowSmall\',\'ajax-master.php?action=confirm-delete&url=ajax-master.php?action=delete-content-style&id='.$content_style->id.'\',false)" href="#delete-content-style">Remove</a>';
        echo '</div>
		</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    //log_activity($_SESSION['current_user_id'],'view admin users');

}

/***** COMPONENTS *****/
// ADD COMPONENT
function add_component($arr)
{
    global $db;
    if (($arr['component']) != "") // REQUIREED FIELDS
    {
        $Q = "INSERT INTO `components` ";
        $field_str = "(";
        $value_str = " VALUES (";
        foreach ($arr as $key => $value)
        {
            $value = mysqli_real_escape_string($db,$value);
            $field_str .= "`".$key."`,";
            $value_str .= "'".$value."',";
        }
        $field_str = substr($field_str,0,-1).")";
        $value_str = substr($value_str,0,-1).")";
        $Q .= $field_str.$value_str;
        $db->query($Q); //or die ($db->error);
        return $db->insert_id;
    }

}

// UPDATE COMPONENT
function update_component($arr)
{
    global $db;
    if (($arr['id'] && $arr['component']) != "") // REQUIREED FIELDS
    {
        $Q = "UPDATE `components` SET ";
        $update_str = "";
        foreach ($arr as $key => $value)
        {
            if ($key != "id")
            {
                $value = mysqli_real_escape_string($db,$value);
                $update_str .= "`".$key."` = '".$value."',";
            }
        }
        $update_str = substr($update_str,0,-1)." WHERE `id` = '".$arr['id']."'";
        $Q .= $update_str;
        $db->query($Q); //or die ($db->error);
        return $arr['id'];
    }

}

// DELETE COMPONENT
function delete_component($component_id)
{
    global $db;
    $Q = "DELETE FROM `components` WHERE `id` = '$component_id'";
    $db->query($Q); //or die ($db->error);
    return $result['msg'] = "Component removed successfully";
}

//GET COMPONENTS
function get_components($learning_area_id="")
{
    global $db;
    $components = array();
    $Q = "SELECT * FROM `components`";
    if ($learning_area_id != "")
    {
        $Q .= " as a LEFT JOIN `components_learning_area_reference` as b on a.`id` = b.`components_id` WHERE b.`learning_area_id` = '$learning_area_id'";
    }
    $result = $db->query($Q);
    while ($component = $result->fetch_object())
    {
        $components[] = $component;
    }
    return $components;
}

//GET COMPONENT BY ID
function get_component($component_id)
{
    global $db;
    $Q = "SELECT * FROM `components` WHERE `id` = '$component_id' LIMIT 0,1";
    $result = $db->query($Q);
    while ($component = $result->fetch_object())
    {
        return $component;
    }
}

//GET LINKED COMPONENTS
function get_linked_components($learning_area_id)
{
    global $db;
    $components = array();
    $Q = "SELECT * FROM `components_learning_area_reference` WHERE `learning_area_id` = '$learning_area_id'";
    $result = $db->query($Q);
    while ($component = $result->fetch_object())
    {
        $components[] = $component->components_id;
    }
    return $components;
}

// LINK COMPONENT TO LEARNING AREA
function link_component_learning_area($learning_area_id,$component_ids)
{
    global $db;
    $Q = "SELECT * FROM `components_learning_area_reference` WHERE `learning_area_id` = '$learning_area_id'"; // AND  `components_id` = '$component_id'";
    $result = $db->query($Q);
    while ($component = $result->fetch_object())
    {
        if (in_array($component->components_id,$component_ids))
        {
            $key = array_search($component->components_id,$component_ids);
            unset($component_ids[$key]);
        }
        else
        {
            $Q = "DELETE FROM `components_learning_area_reference` WHERE `learning_area_id` = '$learning_area_id' AND  `components_id` = '$component->components_id'";
            $db->query($Q);
            $key = array_search($component->components_id,$component_ids);
            unset($component_ids[$key]);
        }
    }
    foreach ($component_ids as $component_id)
    {
        $Q = "INSERT INTO `components_learning_area_reference` VALUES('','$component_id','$learning_area_id')";
        $db->query($Q); //or die ($db->error);
    }
}

// VIEW COMPONENTS
function view_components()
{

    $components = get_components();
    echo '<table id="data_table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th width="5" class="txt_c">StatusID</th>';
    echo '<th>Status</th>';
    echo '<th></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($components as $component)
    {

        echo '<tr>';
        echo '<td width="5" class="txt_c">'.$component->id.'</td>';
        echo '<td>'.$component->component.'</td>';
        echo '<td width="120" class="txt_c">
		<div class="actions">
		<a class="action-edit" onclick="ajax_load_file(\'#middle\',\'ajax-master.php?action=edit-component&id='.$component->id.'\',false)" href="#edit-component">Edit</a>';
        echo '<a data-reveal-id="modalWindowSmall" class="action-trash" onclick="ajax_load_file(\'#modalWindowSmall\',\'ajax-master.php?action=confirm-delete&url=ajax-master.php?action=delete-component&id='.$component->id.'\',false)" href="#delete-component">Remove</a>';
        echo '</div>
		</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    //log_activity($_SESSION['current_user_id'],'view admin users');

}

/***** LEARNING AREAS *****/
// ADD LEARNING AREA
function add_learning_area($arr)
{
    global $db;
    if (($arr['title'] && $arr['ISBN'] && $arr['EDGE_code'] && $arr['edition'] && $arr['nqf_level'] && $arr['date_created'] && $arr['status_id']) != "") // REQUIREED FIELDS
    {
        $Q = "INSERT INTO `learning_area` ";
        $field_str = "(";
        $value_str = " VALUES (";
        foreach ($arr as $key => $value)
        {
            if (($key == 'cover_graphic' || $key == 'outline') && $value == 'uploads/learning-areas/')
            {
                // PREVENT EMPTY FILE NAMES
            }
            else
            {
                $value = mysqli_real_escape_string($db,$value);
                $field_str .= "`".$key."`,";
                $value_str .= "'".$value."',";
            }
        }
        $field_str = substr($field_str,0,-1).")";
        $value_str = substr($value_str,0,-1).")";
        $Q .= $field_str.$value_str;
        $db->query($Q); //or die ($db->error);
        return $db->insert_id;
    }

}

// UPDATE LEARNING AREA
function update_learning_area($arr)
{
    global $db;

    //if (($arr['id'] && $arr['royalty'] && $arr['ISBN'] && $arr['EDGE_code'] && $arr['cover_graphic'] && $arr['edition']) != "" && $arr['impression'] && $arr['extraction'] && $arr['outline'] && $arr['nqf_level'] && $arr['academic_scoring'] && $arr['editor_scoring'] && $arr['date_created'] && $arr['date_published'] && $arr['status_id'] && $arr['users_id'] && $arr['clients_id']) != "") // REQUIREED FIELDS
    if (($arr['id'] && $arr['title'] && $arr['ISBN'] && $arr['EDGE_code'] && $arr['edition'] && $arr['nqf_level'] && $arr['date_created'] && $arr['status_id']) != "") // REQUIREED FIELDS
    {
        $Q = "UPDATE `learning_area` SET ";
        $update_str = "";
        foreach ($arr as $key => $value)
        {
            if ($key != "id")
            {
                if (($key == 'cover_graphic' || $key == 'outline') && $value == 'uploads/learning-areas/')
                {
                    // PREVENT EMPTY FILE NAMES
                }
                else
                {
                    $value = mysqli_real_escape_string($db,$value);
                    $update_str .= "`".$key."` = '".$value."',";
                }
            }
        }
        $update_str = substr($update_str,0,-1)." WHERE `id` = '".$arr['id']."'";
        $Q .= $update_str;
        $db->query($Q); //or die ($db->error);
        return $arr['id'];
    }

}

// DELETE LEARNING AREA
function delete_learning_area($learning_area_id)
{
    global $db;
    $Q = "DELETE FROM `learning_area` WHERE `id` = '$learning_area_id'";
    $db->query($Q); //or die ($db->error);
    return $result['msg'] = "Learning area removed successfully";
}

//GET LEARNING AREAS
function get_learning_areas($search="",$field="id",$order="desc",$limit="99999")
{
    global $db;
    $learning_areas = array();
    $Q = "SELECT * FROM `learning_area` WHERE `title` LIKE '%$search%' ORDER BY `$field` $order LIMIT 0,$limit";
    $result = $db->query($Q);
    while ($learning_area = $result->fetch_object())
    {
        $learning_areas[] = $learning_area;
    }
    return $learning_areas;
}

//GET LEARNING AREA BY ID
function get_learning_area($learning_area_id)
{
    global $db;
    $Q = "SELECT * FROM `learning_area` WHERE `id` = '$learning_area_id' LIMIT 0,1";
    $result = $db->query($Q);
    while ($learning_area = $result->fetch_object())
    {
        return $learning_area;
    }
}



/***** UNITS *****/
// ADD UNIT
function add_unit($arr)
{
    global $db;
    if (($arr['title'] && $arr['nqf'] && $arr['status_id']) != "") // REQUIREED FIELDS
    {
        $Q = "INSERT INTO `unit` ";
        $field_str = "(";
        $value_str = " VALUES (";
        foreach ($arr as $key => $value)
        {
            if ($key == 'original_doc' && $value == 'uploads/units/')
            {
                // PREVENT EMPTY FILE NAMES
            }
            else
            {
                $value = mysqli_real_escape_string($db,$value);
                $field_str .= "`".$key."`,";
                $value_str .= "'".$value."',";
            }
        }
        $field_str = substr($field_str,0,-1).")";
        $value_str = substr($value_str,0,-1).")";
        $Q .= $field_str.$value_str;
        $db->query($Q); //or die ($db->error);
        return $db->insert_id;
    }

}

// LINK UNIT TO LEARNING AREA
function link_unit_learning_area($learning_area_id,$unit_id)
{
    global $db;
    $Q = "SELECT * FROM `la_unit_reference` WHERE `learning_area_id` = '$learning_area_id' AND  `unit_id` = '$unit_id'";
    $result = $db->query($Q);
    if ($result->num_rows == 0)
    {
        $Q = "INSERT INTO `la_unit_reference` VALUES('$unit_id','$learning_area_id','')";
        $db->query($Q);
    }
}

// UPDATE UNIT
function update_unit($arr)
{
    global $db;

    //if (($arr['id'] && $arr['royalty'] && $arr['ISBN'] && $arr['EDGE_code'] && $arr['cover_graphic'] && $arr['edition']) != "" && $arr['impression'] && $arr['extraction'] && $arr['outline'] && $arr['nqf_level'] && $arr['academic_scoring'] && $arr['editor_scoring'] && $arr['date_created'] && $arr['date_published'] && $arr['status_id'] && $arr['users_id'] && $arr['clients_id']) != "") // REQUIREED FIELDS
    if (($arr['id'] && $arr['title'] && $arr['nqf'] && $arr['status_id']) != "") // REQUIREED FIELDS
    {
        $Q = "UPDATE `unit` SET ";
        $update_str = "";
        foreach ($arr as $key => $value)
        {
            if ($key != "id")
            {
                if ($key == 'original_doc' && $value == 'uploads/units/')
                {
                    // PREVENT EMPTY FILE NAMES
                }
                else
                {
                    $value = mysqli_real_escape_string($db,$value);
                    $update_str .= "`".$key."` = '".$value."',";
                }
            }
        }
        $update_str = substr($update_str,0,-1)." WHERE `id` = '".$arr['id']."'";
        $Q .= $update_str;
        $db->query($Q); //or die ($db->error);
        return $arr['id'];
    }

}

// UPDATE UNIT
function update_unit_order($arr)
{
    global $db;
    $z = 1;
    foreach ($arr as $unit_order)
    {
        $Q = "UPDATE `unit` SET `order` = '$z' WHERE `id` = '$unit_order'";
        $db->query($Q);
        $z++;
    }
}

// DELETE UNIT
function delete_unit($learning_area_id,$unit_id)
{
    global $db;
    if (($learning_area_id && $unit_id) != "") {
        $Q = "DELETE FROM `unit` WHERE `id` = '$unit_id'";
        $db->query($Q); //or die ($db->error);
        $Q = "DELETE FROM `la_unit_reference` WHERE `unit_id` = '$unit_id' AND `learning_area_id` = '$learning_area_id'";
        $db->query($Q); //or die ($db->error);
        return $result['msg'] = "Unit removed successfully";
    }
}

//GET UNITS
function get_units($learning_area_id="")
{
    global $db;
    $units = array();
    $Q = "SELECT a.*, b.`id` as `ref_id`, b.`learning_area_id` FROM `unit` as a";
    if ($learning_area_id) { $Q .= " LEFT JOIN `la_unit_reference` as b on a.`id` = b.`unit_id` WHERE b.`learning_area_id` = '$learning_area_id' ORDER BY a.`order` ASC, a.`id` ASC"; }
    $result = $db->query($Q) or die (mysqli_error());
    while ($unit = $result->fetch_object())
    {
        $units[] = $unit;
    }
    return $units;
}

//GET UNIT BY ID
function get_unit($unit_id)
{
    global $db;
    $Q = "SELECT * FROM `unit` WHERE `id` = '$unit_id' LIMIT 0,1";
    $result = $db->query($Q);
    while ($unit = $result->fetch_object())
    {
        return $unit;
    }
}

/***** SECTIONS *****/
// ADD SECTION
function add_section($arr)
{
    global $db;
    if (($arr['heading'] && $arr['nqf'] && $arr['status_id']) != "") // REQUIREED FIELDS
    {
        $Q = "INSERT INTO `unit_section` ";
        $field_str = "(";
        $value_str = " VALUES (";
        foreach ($arr as $key => $value)
        {
            $value = mysqli_real_escape_string($db,$value);
            $field_str .= "`".$key."`,";
            $value_str .= "'".$value."',";
        }
        $field_str = substr($field_str,0,-1).")";
        $value_str = substr($value_str,0,-1).")";
        $Q .= $field_str.$value_str;
        $db->query($Q) or die ($db->error);
        return $db->insert_id;
    }

}

// LINK SECTION TO UNIT
function link_section_unit($unit_id,$section_id)
{
    global $db;
    $Q = "SELECT * FROM `unit_section_reference` WHERE `unit_id` = '$unit_id' AND  `unit_section_id` = '$section_id'";
    $result = $db->query($Q) or die ($db->error);

    if ($result->num_rows == 0)
    {
        $Q = "INSERT INTO `unit_section_reference` VALUES('','$unit_id','$section_id')";
        $db->query($Q) or die ($db->error);
    }
}

// UPDATE SECTION
function update_section($arr)
{
    global $db;
    if (($arr['id'] && $arr['heading'] && $arr['nqf'] && $arr['status_id']) != "") // REQUIREED FIELDS
    {
        $Q = "UPDATE `unit_section` SET ";
        $update_str = "";
        foreach ($arr as $key => $value)
        {
            if ($key != "id")
            {
                $value = mysqli_real_escape_string($db,$value);
                $update_str .= "`".$key."` = '".$value."',";
            }
        }
        $update_str = substr($update_str,0,-1)." WHERE `id` = '".$arr['id']."'";
        $Q .= $update_str;
        $db->query($Q); //or die ($db->error);
        return $arr['id'];
    }

}


// UPDATE SECTION FIELD
function update_section_field($arr)
{
    global $db;
    if (($arr['id']) != "") // REQUIREED FIELDS
    {
        $Q = "UPDATE `unit_section` SET ";
        $update_str = "";
        foreach ($arr as $key => $value)
        {
            if ($key != "id")
            {
                $value = mysqli_real_escape_string($db,$value);
                $update_str .= "`".$key."` = '".$value."',";
            }
        }
        $update_str = substr($update_str,0,-1)." WHERE `id` = '".$arr['id']."'";
        $Q .= $update_str;
        $db->query($Q) or die ($db->error);
        return $arr['id'];
    }

}

// UPDATE SECTION
function update_section_order($arr)
{
    print_f($arr);
    global $db;
    $z = 1;
    foreach ($arr as $section_id => $section_parent_id)
    {
        if($section_parent_id == null) { $section_parent_id = 0; }
        $Q = "UPDATE `unit_section` SET `parent_id` = '$section_parent_id', `list_order` = '$z' WHERE `id` = '$section_id'";
        $db->query($Q) or die ($db->error);
        $z++;
    }
}

// DELETE SECTION
function delete_section($unit_id,$section_id)
{
    global $db;
    if (($unit_id && $section_id) != "") {
        $Q = "DELETE FROM `unit_section` WHERE `id` = '$section_id'";
        $db->query($Q); //or die ($db->error);
        $Q = "DELETE FROM `unit_section_reference` WHERE `unit_id` = '$unit_id' AND `unit_section_id` = '$section_id'";
        $db->query($Q); //or die ($db->error);
        return $result['msg'] = "Section removed successfully";
    }
}

//GET SECTIONS
function get_sections($unit_id="")
{
    global $db;
    $sections = array();
    $Q = "SELECT * FROM `unit_section_reference` as a";
    $Q .= " LEFT JOIN `unit_section` as b on a.`unit_section_id` = b.`id` WHERE a.`unit_id` = '$unit_id' ORDER BY b.`parent_id`, b.`list_order` ASC";
    $result = $db->query($Q) or die ($db->error);
    while ($section = $result->fetch_object())
    {
        $sections[] = $section;
    }

    return $sections;
}

//GET SECTION BY ID
function get_section($section_id)
{
    global $db;
    $Q = "SELECT * FROM `unit_section` WHERE `id` = '$section_id' LIMIT 0,1";
    $result = $db->query($Q);
    while ($section = $result->fetch_object())
    {
        return $section;
    }
}

// ADD CONTENT TEXT RECORD
function add_content_text($arr)
{
    global $db;
    if (($arr['content']) != "") // REQUIREED FIELDS
    {
        $Q = "INSERT INTO `content_text` ";
        $field_str = "(";
        $value_str = " VALUES (";
        foreach ($arr as $key => $value)
        {
            $value = mysqli_real_escape_string($db,$value);
            $field_str .= "`".$key."`,";
            $value_str .= "'".$value."',";
        }
        $field_str = substr($field_str,0,-1).")";
        $value_str = substr($value_str,0,-1).")";
        $Q .= $field_str.$value_str;
        $db->query($Q) or die ($db->error);
        return $db->insert_id;
    }

}

// UPDATE CONTENT TEXT
function update_content_text($arr)
{
    global $db;
    if ($arr['id'] != "") // REQUIRED FIELDS
    {
        $Q = "UPDATE `content_text` SET ";
        $update_str = "";
        foreach ($arr as $key => $value)
        {
            if ($key != "id")
            {
                $value = mysqli_real_escape_string($db,$value);
                $update_str .= "`".$key."` = '".$value."',";
            }
        }
        $update_str = substr($update_str,0,-1)." WHERE `id` = '".$arr['id']."'";
        $Q .= $update_str;
        $db->query($Q); // or die ($db->error);
        return $arr['id'];
    }


}

// GET CONTENT TEXT BY ID
function get_content_text($text_id)
{
    global $db;
    $Q = "SELECT * FROM `content_text` WHERE `id` = '$text_id' LIMIT 0,1";
    $result = $db->query($Q) or die ($db->error);
    while ($content_text = $result->fetch_object())
    {
        return $content_text;
    }
}


/***** BILIOGRAPHY *****/
// ADD BILIOGRAPHY
function add_bibliography($arr)
{
    global $db;
    if (($arr['bibliography']) != "") // REQUIREED FIELDS
    {
        $Q = "INSERT INTO `bibliography` ";
        $field_str = "(";
        $value_str = " VALUES (";
        foreach ($arr as $key => $value)
        {

            $value = mysqli_real_escape_string($db,$value);
            $value = my_strip_tags('p',$value);
            $field_str .= "`".$key."`,";
            $value_str .= "'".$value."',";
        }
        $field_str = substr($field_str,0,-1).")";
        $value_str = substr($value_str,0,-1).")";
        $Q .= $field_str.$value_str;
        $db->query($Q); //or die ($db->error);
        return $db->insert_id;
    }

}

// LINK BILIOGRAPHY CONTENT TYPE
function link_bibliography_content_type($foreign_key_id,$bibliography_id,$content_type)
{
    global $db;
    $content_type_id = get_content_type_id($content_type);
    $Q = "SELECT * FROM `bibliography_reference` WHERE `foreign_key_id` = '$foreign_key_id' AND  `bibliography_id` = '$bibliography_id' AND `content_type_id` = '$content_type_id'";
    $result = $db->query($Q);
    if ($result->num_rows == 0)
    {
        $Q = "INSERT INTO `bibliography_reference` VALUES('','$foreign_key_id','$bibliography_id','$content_type_id')";
        $db->query($Q);
    }
}

// UPDATE BILIOGRAPHY
function update_bibliography($arr)
{
    global $db;

    //if (($arr['id'] && $arr['royalty'] && $arr['ISBN'] && $arr['EDGE_code'] && $arr['cover_graphic'] && $arr['edition']) != "" && $arr['impression'] && $arr['extraction'] && $arr['outline'] && $arr['nqf_level'] && $arr['academic_scoring'] && $arr['editor_scoring'] && $arr['date_created'] && $arr['date_published'] && $arr['status_id'] && $arr['users_id'] && $arr['clients_id']) != "") // REQUIREED FIELDS
    if (($arr['id'] && $arr['bibliography']) != "") // REQUIREED FIELDS
    {
        $Q = "UPDATE `bibliography` SET ";
        $update_str = "";
        foreach ($arr as $key => $value)
        {
            if ($key != "id")
            {
                $value = mysqli_real_escape_string($db,$value);
                $value = my_strip_tags('p',$value);
                $update_str .= "`".$key."` = '".$value."',";
            }
        }
        $update_str = substr($update_str,0,-1)." WHERE `id` = '".$arr['id']."'";
        $Q .= $update_str;
        $db->query($Q); //or die ($db->error);
        return $arr['id'];
    }

}


// DELETE BILIOGRAPHY
function delete_bibliography($bibliography_id,$link_id,$content_type)
{
    global $db;
    $content_type_id = get_content_type_id($content_type);
    if (($bibliography_id && $link_id && $content_type_id) != "") {
        $Q = "DELETE FROM `bibliography_reference` WHERE `bibliography_id` = '$bibliography_id' AND `foreign_key_id` = '$link_id' AND `content_type_id` = '$content_type_id'";
        $db->query($Q) or die ($db->error);
        $Q = "DELETE FROM `bibliography` WHERE `id` = '$bibliography_id'";
        $db->query($Q) or die ($db->error);

        return $result['msg'] = "Biliography removed successfully";
    }
}

//GET BILIOGRAPHY
function get_all_biliography($id,$content_type="unit")
{
    global $db;
    $biliography = array();
    $content_type_id = get_content_type_id($content_type);
    $Q = "SELECT a.*, b.`bibliography` FROM `bibliography_reference` as a
	LEFT JOIN `bibliography` as b on b.`id` = a.`bibliography_id` WHERE a.`foreign_key_id` = '$id' AND a.`content_type_id` = '$content_type_id' ORDER BY b.`bibliography` ASC";
    $result = $db->query($Q) or die (mysqli_error());
    while ($bibliography = $result->fetch_object())
    {
        $biliography[] = $bibliography;
    }
    return $biliography;
}

//GET BILIOGRAPHY BY ID
function get_bibliography($bibliography_id)
{
    global $db;
    $Q = "SELECT * FROM `bibliography` WHERE `id` = '$bibliography_id' LIMIT 0,1";
    $result = $db->query($Q);
    while ($bibliography = $result->fetch_object())
    {
        return $bibliography;
    }
}

//GET BILIOGRAPHY ID BY FOREIGN KEY AND CONTENT TYPE
function get_bibliography_id($foreign_key_id,$content_type)
{
    global $db;
    $content_type_id = get_content_type_id($content_type);
    $Q = "SELECT * FROM `bibliography_reference` WHERE `foreign_key_id` = '$foreign_key_id' AND `content_type_id` = '$content_type_id'  LIMIT 0,1";
    $result = $db->query($Q);
    while ($bibliography = $result->fetch_object())
    {
        return $bibliography->bibliography_id;
    }
}

// SEARCH BIBLIOGRAPHY 
function search_bibliography($search,$unit_id="",$section_id="")
{
    global $db;
    $search_results = "";
    $Q = "SELECT * FROM `bibliography` WHERE `bibliography` LIKE '%$search%' GROUP BY `bibliography`";
    $result = $db->query($Q);
    if ($result->num_rows == 0)
    {
        $search_results = '<div class="search-result">No Results Found...</div>';
    }
    else
    {
        while ($bibliography = $result->fetch_object())
        {
            $search_results .= '<div id="bibliography_'.$bibliography->id.'" class="search-result result" onclick="ajax_load_file(\'#modalWindow\',\'ajax-master.php?action=add-bibliography&id='.$bibliography->id.'&uid='.$unit_id.'&sid='.$section_id.'\',false), clear_element(\'.search-list\');">'.$bibliography->bibliography.'</div>';
        }
    }
    return $search_results;
}


/***** TAGS *****/
// ADD TAG
function add_tag($arr,$link_id,$content_type)
{
    global $db;
    if (($arr['tag_name'])  != "") // REQUIREED FIELDS
    {
        $Q = "SELECT * FROM `tags` WHERE `tag_name` = '".$arr['tag_name']."' LIMIT 0,1";
        $result = $db->query($Q);
        if ($result->num_rows == 0)
        {
            $Q = "INSERT INTO `tags` ";
            $field_str = "(";
            $value_str = " VALUES (";
            foreach ($arr as $key => $value)
            {

                $value = mysqli_real_escape_string($db,$value);
                $value = my_strip_tags('p',$value);
                $field_str .= "`".$key."`,";
                $value_str .= "'".$value."',";
            }
            $field_str = substr($field_str,0,-1).")";
            $value_str = substr($value_str,0,-1).")";
            $Q .= $field_str.$value_str;
            $db->query($Q); //or die ($db->error);
            $tag_id =  $db->insert_id;
        }
        else
        {
            while ($tag = $result->fetch_object())
            {
                $tag_id = $tag->id;
            }
        }

        link_tag_content_type($tag_id,$link_id,$content_type);
    }

}


// LINK TAG CONTENT TYPE
function link_tag_content_type($tag_id,$foreign_key_id,$content_type)
{
    global $db;
    $content_type_id = get_content_type_id($content_type);
    $Q = "SELECT * FROM `tags_reference` WHERE `foreign_key_id` = '$foreign_key_id' AND  `tags_id` = '$tag_id' AND `content_type_id` = '$content_type_id'";
    $result = $db->query($Q);
    if ($result->num_rows == 0)
    {
        $Q = "INSERT INTO `tags_reference` VALUES('','$tag_id','$foreign_key_id','$content_type_id')";
        $db->query($Q);
    }
}

// GET TAG ID
function get_tag_id($tag)
{
    global $db;
    $Q = "SELECT * FROM `tags` WHERE `tag_name` = '$tag' LIMIT 0,1";
    $result = $db->query($Q) or die (mysqli_error());
    if ($result->num_rows > 0)
    {
        while ($tag = $result->fetch_object())
        {
            return $tag->id;
        }
    }
    else
    {
        return "No tags found";
    }
}

//GET TAGS
function get_all_tags($id,$content_type)
{
    global $db;
    $tags = array();
    $content_type_id = get_content_type_id($content_type);
    $Q = "SELECT a.*, b.`tag_name` FROM `tags_reference` as a
	LEFT JOIN `tags` as b on b.`id` = a.`tags_id` WHERE a.`foreign_key_id` = '$id' AND a.`content_type_id` = '$content_type_id' ORDER BY b.`tag_name` ASC";
    $result = $db->query($Q) or die (mysqli_error());
    while ($tag = $result->fetch_object())
    {
        $tags[] = $tag;
    }
    return $tags;
}

//GET TAGS
function get_available_tags()
{
    global $db;
    $tags = "";
    $Q = "SELECT DISTINCT `tag_name` FROM `tags` ORDER BY `tag_name` ASC";
    $result = $db->query($Q) or die (mysqli_error());
    while ($tag = $result->fetch_object())
    {
        $tags .= $tag->tag_name.',';
    }
    $tags = substr($tags,0,-1);
    return $tags;
}

// DELETE TAG
function delete_tag($tag,$link_id,$content_type)
{
    global $db;
    $tag_id = get_tag_id($tag);
    echo $tag_id;
    $content_type_id = get_content_type_id($content_type);
    if (($tag_id && $link_id && $content_type_id) != "") {
        $Q = "DELETE FROM `tags_reference` WHERE `tags_id` = '$tag_id' AND `foreign_key_id` = '$link_id' AND `content_type_id` = '$content_type_id'";
        $db->query($Q) or die ($db->error);
        return $result['msg'] = "Tag removed successfully";
    }
}


/***** CONTENT TYPES *****/
// GET CONTENT TYPE ID
function get_content_type_id($name)
{
    global $db;
    $Q = "SELECT * FROM `content_type` WHERE `name` = '$name' LIMIT 0,1";
    $result = $db->query($Q);
    while ($content_type = $result->fetch_object())
    {
        return $content_type->id;
    }
}



// UPLOAD IMAGE/FILE
function add_media($type,$files,$time)
{
    global $db;
    session_start();
    if ($type != "") // REQUIREED FIELDS
    {
        if ($type == "image") { $folder = "images"; }
        if ($type == "file") { $folder = "files"; }
        $z = 0;
        $num_files = sizeof($files['tmp_name']);
        while ($z < $num_files)
        {
            if ($files['error'][$z] == 0)
            {
                $size = getimagesize($files['tmp_name'][$z]);
                if ($type == "image")
                {
                    $arr = array(
                        'filename' => format_file_name($files['name'][$z],$time),
                        'format' => $files['type'][$z],
                        'dimensions' => $size[0].','.$size[1],
                        'url' => 'uploads/images/'.format_file_name($files['name'][$z],$time),
                        'thumbnail_url' => 'uploads/images/thumbs/'.format_file_name($files['name'][$z],$time),
                        'last_edited' => date('Y-m-d'),
                        'status_id' => 1,
                        'users_id' => $_SESSION['user_id'],
                    );
                }
                if ($type == "file")
                {
                    $arr = array(
                        'filename' => format_file_name($files['name'][$z],$time),
                        //'format' => $files['type'][$z],
                        'url' => 'uploads/files/'.format_file_name($files['name'][$z],$time),
                        //'last_edited' => date('Y-m-d'),
                        'status_id' => 1,
                        'users_id' => $_SESSION['user_id'],
                    );
                }
                $file = array(
                    'name' => $files['name'][$z],
                    'tmp_name' => $files['tmp_name'][$z],
                    'size' => $files['size'][$z],
                    'error' => $files['error'][$z],
                    'type' => $files['type'][$z]
                );
                upload_file($file,$folder,$time);

                $Q = "INSERT INTO `$type` ";
                $field_str = "(";
                $value_str = " VALUES (";
                foreach ($arr as $key => $value)
                {
                    $value = mysqli_real_escape_string($db,$value);
                    $field_str .= "`".$key."`,";
                    $value_str .= "'".$value."',";
                }
                $field_str = substr($field_str,0,-1).")";
                $value_str = substr($value_str,0,-1).")";
                $Q .= $field_str.$value_str;
                $db->query($Q) or die ($db->error);
                $ids[] = $db->insert_id;
            }
            $z++;
        }
        return $ids;
    }

}

// UPDATE MEDIA
function update_media($arr,$type)
{
    global $db;

    //if (($arr['id'] && $arr['royalty'] && $arr['ISBN'] && $arr['EDGE_code'] && $arr['cover_graphic'] && $arr['edition']) != "" && $arr['impression'] && $arr['extraction'] && $arr['outline'] && $arr['nqf_level'] && $arr['academic_scoring'] && $arr['editor_scoring'] && $arr['date_created'] && $arr['date_published'] && $arr['status_id'] && $arr['users_id'] && $arr['clients_id']) != "") // REQUIREED FIELDS
    //if (($arr['id'] && $arr['bibliography']) != "") // REQUIREED FIELDS
    //{
    $Q = "UPDATE `$type` SET ";
    $update_str = "";
    foreach ($arr as $key => $value)
    {
        if ($key != "id")
        {
            $value = mysqli_real_escape_string($db,$value);
            $update_str .= "`".$key."` = '".$value."',";
        }
    }
    $update_str = substr($update_str,0,-1)." WHERE `id` = '".$arr['id']."'";
    $Q .= $update_str;
    $db->query($Q) or die ($db->error);
    return $arr['id'];
    //}

}

// GET IMAGE BY ID
function get_image($image_id)
{
    global $db;
    $Q = "SELECT * FROM `image` WHERE `id` = '$image_id' LIMIT 0,1";
    $result = $db->query($Q);
    while ($image = $result->fetch_object())
    {
        return $image;
    }
}

// GET IMAGES
function get_images($search="",$items_per_page=999999,$offset=1)
{
    global $db;
    $offset = $offset - 1;
    $offset = $items_per_page*$offset;
    $content_type_id = '6'; // image
    $images = array();
    $Q = "SELECT a.* ";
    if ($search != "")
    {
        $Q .= ", b.`foreign_key_id`, b.`tags_id`, c.`id` as `tag_id`,c.`tag_name` ";
    }
    $Q .= "FROM `image` as a ";
    if ($search != "")
    {
        $Q .= "INNER JOIN `tags_reference` as b on b.`foreign_key_id` = a.`id`
			   INNER JOIN `tags` as c on c.`id` = b.`tags_id` ";
    }
    $Q .= "WHERE a.`title` LIKE '%$search%' ";
    if ($search != "")
    {
        $Q .= "OR c.`tag_name` = '$search' AND b.`content_type_id` = '$content_type_id' ";
    }
    $Q .= " GROUP BY a.`id` LIMIT $offset,$items_per_page";

    $result = $db->query($Q);
    while ($image = $result->fetch_object())
    {
        $images[] = $image;
    }
    //print_f($images);
    return $images;
}

// GET NUM IMAGES
function get_num_images_pages($search="",$items_per_page)
{
    global $db;
    $content_type_id = '6'; // image
    $Q = "SELECT a.* ";
    if ($search != "")
    {
        $Q .= ", b.`foreign_key_id`, b.`tags_id`, c.`id` as `tag_id`,c.`tag_name` ";
    }
    $Q .= "FROM `image` as a ";
    if ($search != "")
    {
        $Q .= "INNER JOIN `tags_reference` as b on b.`foreign_key_id` = a.`id`
			   INNER JOIN `tags` as c on c.`id` = b.`tags_id` ";
    }
    $Q .= "WHERE a.`title` LIKE '%$search%' ";
    if ($search != "")
    {
        $Q .= "OR c.`tag_name` = '$search' AND b.`content_type_id` = '$content_type_id' ";
    }
    $Q .= " GROUP BY a.`id`";

    $result = $db->query($Q);
    $num_pages = ceil(($result->num_rows)/$items_per_page);
    if($num_pages == 0) { $num_pages = 1; }
    return $num_pages;
}

// CREATE THUMBNAILS
function create_thumbs( $pathToImages, $pathToThumbs, $thumbWidth )
{
    $dir = opendir( $pathToImages );
    while (false !== ($fname = readdir( $dir ))) {
        $info = pathinfo($pathToImages . $fname);
        if ( strtolower($info['extension']) == 'jpg' )
        {

            $img = imagecreatefromjpeg( "{$pathToImages}{$fname}" );
            $width = imagesx( $img );
            $height = imagesy( $img );

            $new_width = $thumbWidth;
            $new_height = floor( $height * ( $thumbWidth / $width ) );
            $tmp_img = imagecreatetruecolor( $new_width, $new_height );
            imagecopyresized( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
            imagejpeg( $tmp_img, "{$pathToThumbs}{$fname}", 100 );
        }

        if ( strtolower($info['extension']) == 'png' )
        {

            $img = imagecreatefrompng( "{$pathToImages}{$fname}" );
            //$img = imagecolorallocate($img, 0, 0, 0);

            $width = imagesx( $img );
            $height = imagesy( $img );

            $new_width = $thumbWidth;
            $new_height = floor( $height * ( $thumbWidth / $width ) );
            $tmp_img = imagecreatetruecolor( $new_width, $new_height );
            imagecolorallocate($tmp_img, 0, 0, 0);
            imagealphablending($tmp_img, false);
            imagesavealpha($tmp_img, true);
            imagecopyresized( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
            imagepng( $tmp_img, "{$pathToThumbs}{$fname}" );
        }

        if ( strtolower($info['extension']) == 'gif' )
        {

            $img = imagecreatefromgif( "{$pathToImages}{$fname}" );
            //$img = imagecolorallocate($img, 0, 0, 0);

            $width = imagesx( $img );
            $height = imagesy( $img );

            $new_width = $thumbWidth;
            $new_height = floor( $height * ( $thumbWidth / $width ) );
            $tmp_img = imagecreatetruecolor( $new_width, $new_height );
            $bg = imagecolorallocate($tmp_img, 0, 0, 0);
            imagecolortransparent($tmp_img, $bg);
            imagecopyresized( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
            imagepng( $tmp_img, "{$pathToThumbs}{$fname}" );
        }



    }
    closedir( $dir );
}


// GET FILES
function get_files($search="",$items_per_page=999999,$offset=1)
{
    global $db;
    $offset = $offset - 1;
    $offset = $items_per_page*$offset;
    $content_type_id = '7'; // file
    $files = array();
    $Q = "SELECT a.* ";
    if ($search != "")
    {
        $Q .= ", b.`foreign_key_id`, b.`tags_id`, c.`id` as `tag_id`,c.`tag_name` ";
    }
    $Q .= "FROM `file` as a ";
    if ($search != "")
    {
        $Q .= "INNER JOIN `tags_reference` as b on b.`foreign_key_id` = a.`id`
			   INNER JOIN `tags` as c on c.`id` = b.`tags_id` ";
    }
    $Q .= "WHERE a.`title` LIKE '%$search%' ";
    if ($search != "")
    {
        $Q .= "OR c.`tag_name` = '$search' AND b.`content_type_id` = '$content_type_id' ";
    }
    $Q .= " GROUP BY a.`id` LIMIT $offset,$items_per_page";

    $result = $db->query($Q);
    while ($file = $result->fetch_object())
    {
        $files[] = $file;
    }
    //print_f($files);
    return $files;
}

// GET NUM FILES
function get_num_files_pages($search="",$items_per_page)
{
    global $db;
    $content_type_id = '7'; // file
    $Q = "SELECT a.* ";
    if ($search != "")
    {
        $Q .= ", b.`foreign_key_id`, b.`tags_id`, c.`id` as `tag_id`,c.`tag_name` ";
    }
    $Q .= "FROM `file` as a ";
    if ($search != "")
    {
        $Q .= "INNER JOIN `tags_reference` as b on b.`foreign_key_id` = a.`id`
			   INNER JOIN `tags` as c on c.`id` = b.`tags_id` ";
    }
    $Q .= "WHERE a.`title` LIKE '%$search%' ";
    if ($search != "")
    {
        $Q .= "OR c.`tag_name` = '$search' AND b.`content_type_id` = '$content_type_id' ";
    }
    $Q .= " GROUP BY a.`id`";

    $result = $db->query($Q);
    $num_pages = ceil(($result->num_rows)/$items_per_page);
    if($num_pages == 0) { $num_pages = 1; }
    return $num_pages;
}

// GET FILE BY ID
function get_file($file_id)
{
    global $db;
    $Q = "SELECT * FROM `file` WHERE `id` = '$file_id' LIMIT 0,1";
    $result = $db->query($Q);
    while ($file = $result->fetch_object())
    {
        return $file;
    }
}

// ADD VIDEO 
function add_video($arr)
{
    global $db;
    session_start();
    if (($arr['filename']) != "") // REQUIREED FIELDS
    {
        if (strpos($arr['filename'],'https://vimeo.com/') !== false)
        {
            $filename = $arr['filename'];
            $video_id = end(explode("/",$filename));
            $video = file_get_contents('https://vimeo.com/api/v2/video/'.$video_id.'.json');
            $video = json_decode($video);
            //print_f($video);
            $video = $video[0];
            $arr = array(
                'filename'=>$filename,
                'title'=>$video->title,
                'description'=>$video->description,
                'vimeo_code'=>$video_id,
                'thumbnail_url'=>$video->thumbnail_large,
                'status_id' => 1,
                'users_id' => $_SESSION['user_id'],
                'update_date' => date('Y-m-d')
            );
        }


        $Q = "INSERT INTO `video` ";
        $field_str = "(";
        $value_str = " VALUES (";
        foreach ($arr as $key => $value)
        {

            $value = mysqli_real_escape_string($db,$value);
            $field_str .= "`".$key."`,";
            $value_str .= "'".$value."',";
        }
        $field_str = substr($field_str,0,-1).")";
        $value_str = substr($value_str,0,-1).")";
        $Q .= $field_str.$value_str;
        $db->query($Q); //or die ($db->error);
        return $db->insert_id;
    }
}


// UPDATE VIDEO 
function update_video($arr)
{
    global $db;
    if (($arr['id'] != "")) // REQUIREED FIELDS
    {
        $Q = "UPDATE `video` SET ";
        $update_str = "";
        foreach ($arr as $key => $value)
        {
            if ($key != "id")
            {
                $value = mysqli_real_escape_string($db,$value);
                $update_str .= "`".$key."` = '".$value."',";
            }
        }
        $update_str = substr($update_str,0,-1)." WHERE `id` = '".$arr['id']."'";
        $Q .= $update_str;
        $db->query($Q) or die ($db->error);
        return $arr['id'];
    }
}

// GET VIDEOS
function get_videos($search="",$items_per_page=999999,$offset=1)
{
    global $db;
    $offset = $offset - 1;
    $offset = $items_per_page*$offset;
    $content_type_id = '8'; // video
    $videos = array();
    $Q = "SELECT a.* ";
    if ($search != "")
    {
        $Q .= ", b.`foreign_key_id`, b.`tags_id`, c.`id` as `tag_id`,c.`tag_name` ";
    }
    $Q .= "FROM `video` as a ";
    if ($search != "")
    {
        $Q .= "INNER JOIN `tags_reference` as b on b.`foreign_key_id` = a.`id`
			   INNER JOIN `tags` as c on c.`id` = b.`tags_id` ";
    }
    $Q .= "WHERE a.`title` LIKE '%$search%' ";
    if ($search != "")
    {
        $Q .= "OR c.`tag_name` = '$search' AND b.`content_type_id` = '$content_type_id' ";
    }
    $Q .= " GROUP BY a.`id` LIMIT $offset,$items_per_page";

    $result = $db->query($Q);
    while ($video = $result->fetch_object())
    {
        $videos[] = $video;
    }
    return $videos;
}

// GET NUM VIDEOS
function get_num_videos_pages($search="",$items_per_page)
{
    global $db;
    $content_type_id = '8'; // video
    $Q = "SELECT a.* ";
    if ($search != "")
    {
        $Q .= ", b.`foreign_key_id`, b.`tags_id`, c.`id` as `tag_id`,c.`tag_name` ";
    }
    $Q .= "FROM `video` as a ";
    if ($search != "")
    {
        $Q .= "INNER JOIN `tags_reference` as b on b.`foreign_key_id` = a.`id`
			   INNER JOIN `tags` as c on c.`id` = b.`tags_id` ";
    }
    $Q .= "WHERE a.`title` LIKE '%$search%' ";
    if ($search != "")
    {
        $Q .= "OR c.`tag_name` = '$search' AND b.`content_type_id` = '$content_type_id' ";
    }
    $Q .= " GROUP BY a.`id`";

    $result = $db->query($Q);
    $num_pages = ceil(($result->num_rows)/$items_per_page);
    if($num_pages == 0) { $num_pages = 1; }
    return $num_pages;
}

//GET VIDEO BY ID
function get_video($video_id)
{
    global $db;
    $Q = "SELECT * FROM `video` WHERE `id` = '$video_id' LIMIT 0,1";
    $result = $db->query($Q);
    while ($video = $result->fetch_object())
    {
        return $video;
    }
}



/***** QUESTIONS *****/

// GET QUESTION BY ID
function get_question($question_id)
{
    global $db;
    $Q = "SELECT * FROM `questions` WHERE `id` = '$question_id' LIMIT 0,1";
    $result = $db->query($Q);
    while ($question = $result->fetch_object())
    {
        return $question;
    }
}

// GET QUESTIONS
function get_questions($search="",$items_per_page=999999,$offset=1)
{
    global $db;
    $offset = $offset - 1;
    $offset = $items_per_page*$offset;
    $questions = array();
    $Q = "SELECT * FROM `questions` WHERE `question` LIKE '%$search%' LIMIT $offset,$items_per_page";
    $result = $db->query($Q);
    while ($question = $result->fetch_object())
    {
        $questions[] = $question;
    }
    return $questions;
}


// GET NUM PAGES
function get_num_question_pages($search="", $items_per_page)
{
    global $db;
    $Q = "SELECT * FROM `questions` WHERE `question` LIKE '%$search%'";
    $result = $db->query($Q);
    $num_pages = ceil(($result->num_rows)/$items_per_page);
    if($num_pages == 0) { $num_pages = 1; }
    return $num_pages;
}

//IMPORT QUESTIONS
function import_questions_csv($csv,$method)
{
    global $db;
    $z = 0;
    $z2 = 1;
    $tmpName = $csv['tmp_name'];
    if(($handle = fopen($tmpName, 'r')) !== FALSE)
    {
        if ($method == 'overwrite')
        {
            $Q = "TRUNCATE TABLE `questions`";
            $db->query($Q);
        }

        while(($data = fgetcsv($handle, 0, ',')) !== FALSE)
        {
            $data = array_map("esc_sql",$data);
            if ($z > 0 && !empty($data))
            {
                $Q = "INSERT INTO `questions` VALUES ";
                //$data = explode(",",$data);
                //echo "<pre>";
                //print_r($data);
                $question_type = $data[0];
                $question = $data[1];
                $video = $data[2];
                $audio = $data[3];
                $image = $data[4];
                $answer_1 = $data[5];
                $answer_2 = $data[6];
                $answer_3 = $data[7];
                $answer_4 = $data[8];
                $answer_5 = $data[9];
                $answer_6 = $data[10];
                $answer_7 = $data[11];
                $answer_8 = $data[12];
                $answer_9 = $data[13];
                $answer_10 = $data[14];
                $correct_feedback = $data[15];
                $incorrect_feedback = $data[16];
                $tags = trim($data[17]);
                $learning_area = $data[18];
                $sub_area = $data[19];
                $sub_sub_area = $data[20];
                $nqf = $data[21];
                $difficulty = $data[22];

                $Q .= "(NULL,'$question_type','$question','$video','$audio','$image','$answer_1','$answer_2','$answer_3','$answer_4','$answer_5','$answer_6','$answer_7','$answer_8','$answer_9','$answer_10','$correct_feedback','$incorrect_feedback','$learning_area','$sub_area','$sub_sub_area','$nqf','$difficulty')";
                $db->query($Q) or die ($db->error);
                //print_f($db);
                $msg = "Import completeed";

                if($tags)
                {
                    $tags = str_replace(" ","-",$tags);
                    $tags = str_replace("--","-",$tags);
                    $syn_array = explode(":",$tags);
                    foreach ($tags_array as $tag)
                    {
                        add_tag(array("tag_name"=>$tag),$$db->insert_id,'question');
                    }

                }
            }
            else
            {
                //return "File does not contain any records";
            }
            $z++;
            $z2++;
        }
        if ($img != "")
        {
            return $z2." Questions imported";
        }

    }
    else
    {
        echo "Error importing questions CSV";
    }
}

?>