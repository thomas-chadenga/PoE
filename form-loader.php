<?php
/**
 * Created by PhpStorm.
 * User: Thomas
 * Date: 15/06/23
 * Time: 11:13 AM
 */

session_start();

//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(-1);

if(!isset($_SESSION['user_id'])){
    header("Location:login.php");
}

$user_id = $_SESSION['user_id'];

if(isset($_SESSION['form_id'])){
    $form_id = $_SESSION['form_id'];
}
else {
    if(($_SESSION['user_role']==0)||($_SESSION['user_role']==2)) {
        $form_id = 3;
    }
    else if($_SESSION['user_role']==1){
        $form_id = 23;
    }
    $_SESSION['form_id'] = $form_id;
}
$first_form_id = $form_id;

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

if(isset($_GET['student_id'])){
    $student_id=$_GET['student_id'];
    $_SESSION['student_id'] = $student_id;

    //get student details
    $sql = "SELECT * FROM users WHERE id = $student_id";

    $stmt = $link->query($sql);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $counter = 0;

    foreach($results as $res) {
        $student_name = $res['first_name'];
        $student_last = $res['last_name'];
        $student_idnum = $res['student_number'];
    }
}

?>


 <html>

<head>
    <?php //header('Content-Type: text/html; charset=Windows-1252');    ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script language="javascript" type="text/javascript">
    function startUpload(){
        var proc = 'f1_upload_process';
        var upl = 'f1_upload_form';
        document.getElementById(proc).style.visibility = 'visible';
        document.getElementById(upl).style.visibility = 'hidden';
        return true;
    }

    function stopUpload(success,form_id){
        var proc = 'f1_upload_process';
        var upl = 'f1_upload_form';
        var result = '';
        if (success == 1){
               //$('.content-right').load(location.href + " .content-right-inner");
            $('.content-right-inner').load("forms/form_"+form_id+".php");
            document.getElementById('uploader').style.display = 'none';
               document.getElementById('pager').style.display = 'inline';
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

    function showUpload(form_block_id) {
        var fbi = form_block_id;
        document.getElementById('uploader').style.display = 'inline';
        document.getElementById('pager').style.display = 'inherit';
        document.getElementById('myform').setAttribute("action", "upload.php?form_id=<?php echo $form_id; ?>&user_id=<?php echo $user_id; ?>&fbi=" + fbi);
    }

    $(function (){
           $( "#accordion" ).accordion({
               active:0,
               heightStyle: "content",
               autoHeight: false,
               clearStyle: true
           });
    })

</script>

    <script>

          $(document).ready(function(){

              $(document).on('click', '.submitButton', function() {
                  var ids = $(this).attr('id');
                  var arr = ids.split('_');
                  var form_id = arr[1];
                  var values = $('form').serialize();
                  var total=0;
                  var filled=0;
                  //get all filled fields vs blanks
                  $(this).parent().parent().find('input').each(function(){
                      total+=1;
                      if($(this).val().trim()){
                          filled+=1;
                      }
                      else{
                          $(this).val("");
                      }
                  })
                  console.log("a = %o, b = %o", values, form_id);
                  $.ajax({
                      type: "POST",
                      url: "form_submit.php?form_id="+form_id,
                      data: values,
                      success: function() {
                          //recalc height of ratio
                          var perc = (filled/(total-1))*100;
                          $(document).find('#form_'+form_id).find(".ratio").css("height",perc+"%");
                          var newID = parseInt(form_id,10);
                          newID +=1;
                          $('.content-right-inner').load("forms/form_"+newID+".php", function(){
                              //$('.content-right').load(location.href + " .content-right-inner");
                          });
                      }
                  })
              })

              $(document).on('click', '.section-child', function() {
                  var idee = $(this).attr('id');
                  var spl = idee.split('_');
                  var idstr = spl[1];
                  var title = $(this).find("span").html();
                  $('.content-right-inner').load("forms/form_"+idstr+".php?form_id="+idstr, function(){
                      //$('.content-right').load(location.href + " .content-right-inner");
                      $(this).find("form").prepend( "<p class='form-heading'>"+title+"</p>" );
                      //adjust assessor section
                      if($(this).find("form .input_submitButton").next().length) {
                          $(this).find("form .input_submitButton").next().css({
                              "margin-top": "20px",
                              "border-top": "1px solid #ccc",
                              "padding-top": "20px"
                          });
                          $(this).find("form .input_submitButton").eq(0).css({"display":"none"});
                      }
                  });
                  if (window.matchMedia('(max-width: 768px)').matches)
                  {
                      $('.section-child').next().each(function(){
                          $(this).addClass('mobile-form');
                      })
                      $(this).next().removeClass('mobile-form');
                      //scroll to top

                  }
                  $.scrollTo($(this), 1000);

              })

              $(document).on('click', '.bar', function() {
                 $('.bar').each(function(){
                     $(this).removeClass('showArrow');
                 })
                  $(this).addClass('showArrow');

              })

              $(document).on('click', '.fa-sign-out', function() {
                  alert("hello");
                  window.location.href = "login.php";
              })

          })

        $(window).resize(function(){
            if (window.matchMedia('(min-width: 769px)').matches)
            {
                $('.section-child').next().each(function(){
                    $(this).addClass('mobile-form');
                })
            }
        })


   </script>

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
    <?php if(isset($_SESSION['student_id'])){ ?>
        <div id="student-rec">Student: <span><?php echo $student_name." ".$student_last; ?></span>, Student ID: <span><?php echo $student_idnum; ?></span></div>
    <?php } ?>
    <div id="accordion">
    <?php

    if(!isset($_GET['student_id'])) {
        $sql = "SELECT * FROM submissions_sections WHERE section_name NOT LIKE '%Mentor%'";
    }else{
        if($_SESSION['user_role'] == 1) {
            $sql = "SELECT * FROM submissions_sections WHERE section_name LIKE '%Mentor%'";
        }
        else{
            $sql = "SELECT * FROM submissions_sections";
        }
    }

    //$sql = "SELECT * FROM submissions_sections";

    $stmt = $link->query($sql);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $counter = 0;

    foreach($results as $res) {
        //echo main section
        if($res['id']>1) {
            $counter += 1;
            if($counter == 1){
                echo "<div class='bar showArrow'><h3 class='section'>" . $res['section_name'] . "</h3><i class='fa fa-caret-right'></i></div>";
            }else {
                echo "<div class='bar'><h3 class='section'>" . $res['section_name'] . "</h3><i class='fa fa-caret-right'></i></div>";
            }
            $sql2 = "SELECT * FROM forms WHERE extras_sections_id = " . $res['id'] . " ORDER BY form_position";

            $stmt2 = $link->query($sql2);

            $results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            echo "<div>";


            foreach ($results2 as $res2) {
                //calculate ratio from form blocks
                $sql3 = 'SELECT * FROM user_form_block WHERE user_id=' . $user_id . ' AND form_id=' . $res2["id"];
                $stmt3 = $link->query($sql3);
                $results3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
                $filled = 0;
                $total = 0;
                foreach ($results3 as $res3) {
                    $total += 1;
                    if (trim($res3['value']) != "") {
                        $filled += 1;
                    }
                }
                $perc = 0;
                if ($total != 0) {
                    $perc = ($filled / $total) * 100;
                } else {
                    $perc = 0;
                }

                //echo eche child form
                echo "<div class='section-child' id='form_" . $res2['id'] . "'>
            <div class='ratioBar'>
                <div class='upperBar'>
                    <div class='upperBarHalf'></div>
                </div>
                <div class='ratioCircle'>
                    <div class='ratio' style='height:" . $perc . "%'></div>
                </div>
                <div class='upperBar'>
                    <div class='upperBarHalf'></div>
                </div>
            </div><span>
            " . $res2['form_name'] . "</span></div>";

                if ((file_exists("forms/form_" . $res2['id'] . ".php")) && ($res2['id'] > 2)) {
                    echo "<div class='mobile-form'>";

                    include("forms/form_" . $res2['id'] . ".php");

                    echo " </div>";
                }
            }

            echo "</div>";
        }
    }
    ?>
        </div>
</div>
<div class="content-right">
    <div class="content-right-inner">
<?php
    include("forms/form_" . $first_form_id . ".php");
?>
 </div>
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
<div id="login-details" class="desktop">
    <div id="blue-strip">
        <img src="arrow-login.jpg" />
        <span style="position:relative"><?php echo "Logged in: <span>".$_SESSION['user_name']." ".$_SESSION['user_last']."</span> "; ?></span>
    </div>

</div>
<div> <i class="fa fa-sign-out" style="padding:5px 10px;cursor:pointer;font-size:20px"></i></div>
</body>
</html>