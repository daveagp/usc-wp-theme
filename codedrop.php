<?php

// code to deal with uploading : frontend

add_shortcode('assignment', 'assignment_shortcode');
function assignment_shortcode($atts, $content) {
   global $WS_AUTHINFO;
   if (!array_key_exists("slug", $atts)) 
      return "<div>Assignment shortcode needs a slug.</div>";
   if ($WS_AUTHINFO['username'] == "anonymous") 
      return "<div>You need to log in to access this page. 
Use the link at the top right.</div>";
   $slug = $atts["slug"];
   $course = $atts["course"];
   return "<div><script type='text/javascript'>
   codedrop.initialize_assignment('$slug', '$course', "
      .json_encode(assignment_state($slug, $course, $WS_AUTHINFO['username'])). ");
   </script></div>";
}

add_action('wp_head', 'codedrop_head');
function codedrop_head() {
  //submit_ajax_url = '".admin_url( 'admin-ajax.php' ) . "';
  echo "<script type='text/javascript'> 
submit_ajax_url = 'http://bits.usc.edu/codedrop/';
</script>\n";
}

// code to deal with uploading : backend

add_action( 'wp_ajax_codedrop', 'codedrop_callback' );
add_action( 'wp_ajax_nopriv_codedrop', 'codedrop_callback' );

function safeget($arr, $ind, $def) {
  if (array_key_exists($ind, $arr))
    return $arr[$ind];
  else return $def;
}

function safehas($arr, $ind, $val) {
  return array_key_exists($ind, $arr) && $arr[$ind] == $val;
}

// return jsonable representation of state
function assignment_state($slug, $course, $user) {
  $loc = "/home/parallel05/courseware/courses/$course/info.json";
  $course_info_j = file_get_contents($loc);
  $course_info = json_decode($course_info_j, true);
  if (!$course_info)
    return ["due_date" => array(9999, 'Dec', 31),
            "files" => array
            (
             ["filename"=>"error", "submitted" => true, 
              "description" => "can't parse info.json"])];

  $result = ["files" => array()];

  if (array_key_exists("blurbs", $course_info))
     $result["blurbs"] = $course_info["blurbs"];
  else
     $result["blurbs"] = array();

  $asst = NULL;

  foreach (["assignments"=>"Assignment", "labs"=>"Lab", "exams"=>"Exam"] as $key=>$kind)
     if (array_key_exists($key, $course_info))
        foreach ($course_info[$key] as $i=>$a)
           if ($a["slug"] == $slug) {
              $asst = $a;
              $result['kind'] = $kind;
              $result['number'] = $i+1;
           }
  if ($result['kind'] == 'Lab') {
     $result['labtime'] = "Not registered in any lab<script type='text/javascript'>window.setTimeout(function(){alert('Warning: user is not registered in any lab. Double-check their email address. However, they may have just joined the course.');}, 250);</script>";
     if (array_key_exists('labtimes', $course_info)) {
        foreach ($course_info['labtimes'] as $secstring => $time) {
           $loc = "/home/parallel05/courseware/courses/$course/enrolment/$secstring";
           $cont = file_get_contents($loc);
           if (preg_match('_\\b'.$user.';?\\b_', $cont))
              $result['labtime'] = "Registered in lab $secstring (<i>$time</i>)";
        }
     }
  }

  if ($asst == NULL)
    return ["due_date" => array(9999, 'Dec', 31),
            "files" => array
            (["filename"=>"error", 
              "submitted" => true, 
              "description" => "no assignment '$slug'"])];

  $result["due_date"] = $asst["due_date"];

  if (safehas($course_info, 'codedrop_pledge', true)) {
     $result["codedrop_pledge"] = true;
  }
  
  global $WS_AUTHINFO;
  $is_super = in_array($WS_AUTHINFO['username'], $course_info["staff"]);
  
  if ($is_super) 
     $result['is_super'] = true;

  if ($is_super) {
     $loc = "/home/parallel05/courseware/courses/$course/lab_rubrics/$slug.json";
     if (file_exists($loc))
        $result["rubric"] = json_decode(str_replace("\n", "", file_get_contents($loc)));
  }
  
  $extension = in_array(array($user, $slug),
                        $course_info['extensions']);

  if ($extension) {
     $result['extension'] = true;
  }

  global $wpdb;
  $fileslist = $asst["files"];
  if (array_key_exists("extra_files", $asst)) {
     $fileslist = array_merge($fileslist,  $asst["extra_files"]);
  }
  foreach ($fileslist as $filename) {
    $dbrow = $wpdb->get_row($wpdb->prepare("
SELECT operation, time, length(filecontents) FROM codedrop
WHERE user = %s
AND assignment = %s
AND filename = %s
AND course = %s
AND (operation = 'upload' OR operation = 'delete') 
ORDER BY rowid DESC
LIMIT 1
", $user, $slug, $filename, $course), ARRAY_A);
    
    $uirow = ["filename" => $filename];
    if ($dbrow == null || $dbrow["operation"] == "delete")
      $uirow["submitted"] = false;
    else {
      $uirow["submitted"] = true;
      $uirow["description"] = $dbrow["length(filecontents)"] . " bytes,
uploaded " . $dbrow["time"];
    }
    $result["files"][] = $uirow;
  }

  // double %% is to escape % in prepare
  $dbrow = $wpdb->get_row($wpdb->prepare("
SELECT time, info FROM codedrop
WHERE user = %s
AND assignment = %s
AND course = %s
AND (operation like 'enter-grade%%') 
ORDER BY rowid DESC
LIMIT 1
", $user, $slug, $course), ARRAY_A);
  if ($dbrow != null) {
     $info = json_decode($dbrow["info"], true);
     $info["time"] = $dbrow["time"];
     $result["grade"] = $info;
  }
  else $result["grade"] = null;

  return $result;
}

function codedrop_callback() {
  $result = json_encode(codedrop_callback_j());
  header("Content-Type:text/plain");
  echo $result;
  die();
}

function codedrop_callback_j() {
   global $WS_AUTHINFO;
  $request = [
              "user" => $WS_AUTHINFO['username'],
              "operation" => safeget($_REQUEST, "operation", null),
              "filename" => safeget($_REQUEST, "filename", null),
              "assignment" => safeget($_REQUEST, "assignment", null),
              "course" => safeget($_REQUEST, "course", null)
              ];

  $course = $request["course"];
  $loc = "/home/parallel05/courseware/courses/$course/info.json";
  $course_info_j = file_get_contents($loc);
  $course_info = json_decode($course_info_j, true);
  if (!$course_info)
     return ["status" => "<b>Error</b>: can't parse info.json"];

  $is_super = in_array($WS_AUTHINFO['username'], $course_info["staff"]);

  $tmpfile = null;
  $filesize = -1;
  foreach ($_FILES as $file) {
    if ($file['name'] == $request["filename"]) {
      $tmpfile = $file['tmp_name'];
      $filesize = filesize($file['tmp_name']);
    }
  }
  if ($filesize > 20000) 
    return ["status" 
            => "<b>Error</b>: couldn't accept your file, it was too large: ".
            $filesize . " bytes"];
  if ($tmpfile != null)
    $request["filecontents"] = file_get_contents($tmpfile);

  if ($WS_AUTHINFO['error_div'] != "") {
     return ["status" => $WS_AUTHINFO['error_div']];
  }

  $is_different = $request["user"] != safeget($_REQUEST, "user", null);

  if ($is_different) {
     if (!$is_super) {
        return ["status" => "Error: not a super user"];
     }
     else if ($request["operation"] == "upload" 
              || $request["operation"] == "delete") {
        return ["status" => "Error: invalid super user operation: " 
                . $request["operation"]];
     }
     else {
     }
  }

  $insertme = $request;

  $state = assignment_state($request["assignment"], $request["course"], $request["user"]);

  if ($request["operation"] == "enter-grade") {
     if (!$is_super)
        return ["status" => "Not super user, feature not available."];

     // wordpress adds slashes :(
     $obj = json_decode(stripslashes($_REQUEST["grade"]), true);
     $obj["grader"] = $WS_AUTHINFO['username'];
     mail($_REQUEST["user"], "{$course_info['title']} Lab {$state['number']} Grade Entered",
          "A grade of {$obj['total']}/10 has been entered for you ".
          "in {$course_info['title']} Lab {$state['number']}. This is an automatic email, ".
          "replies will not be received. Contact course staff if you have any questions.");
     mail("daveagp@gmail.com", $_REQUEST["user"] . " {$course_info['title']} Lab {$state['number']} Grade Entered",
          "A grade of {$obj['total']}/10 has been entered for you ".
          "in {$course_info['title']} Lab {$state['number']}. This is an automatic email, ".
          "replies will not be received. Contact course staff if you have any questions.");
     // info is the column name
     $insertme["info"] = json_encode($obj);
  }
  
  if ($request["operation"] == "upload") {
     $due = $state['due_date'];
     $tz = "America/Los_Angeles";
     $duestring = $due[2]." ".$due[1]." ".$due[0]." 11:59PM $tz";
     $duestamp = strtotime($duestring) + 600; // 10 minute grace period
     if ($state['kind'] == 'Assignment')
        $duestamp += 2*24*60*60; // up to 2 days late for assignments
     $now = time();
     if ($now > $duestamp && !array_key_exists('extension', $state) && !array_key_exists('is_super', $state))
        return ["status" => "Error: deadline has expired"];
  }

  // INSERT THE ROW

  global $wpdb;
  if ($is_different && $request["operation"] != "enter-grade") 
     $insertme["operation"] .= " as " . $request["user"];
  if ($request["operation"] == "enter-grade") {
     $insertme["operation"] .= " by " . $WS_AUTHINFO['username'];
     $insertme["user"] = $_REQUEST["user"];
  }
  $wpdb->insert("codedrop", $insertme);
  $rowkey = $wpdb->insert_id;
  
  if ($is_different)
    $request["user"] = $_REQUEST["user"];

  // refresh the state
  $state = assignment_state($request["assignment"], $request["course"], $request["user"]);
 
  if ($request["operation"] == "enter-grade")
    return ["state" => $state,
            "status" => "Grade entered for " . $_REQUEST["user"]];

  if ($request["operation"] == "upload") {
     $msg = "Upload complete.";
     if ($state['kind'] == 'Assignment') 
        $msg .= " Press 'Check My Submission' to verify status of submission.";
     return ["state" => $state,
             "status" => $msg];
  }

  if ($request["operation"] == "state")
    return ["state" => $state,
            "status" => "Info retrieved for " . $_REQUEST["user"]];

  if ($request["operation"] == "delete")
    return ["state" => $state,
            "status" => "File deleted."];

  if ($request["operation"] == "checklong" && !$is_super)
    return ["status" => "Not super user, feature not available."];
  
  if ($request["operation"] == "check" || $request["operation"] == "checklong") {

    putenv("PYTHONIOENCODING=UTF-8");
    
    $descriptorspec = array(
                            0 => array("pipe", "r"),  // stdin
                            1 => array("pipe", "w"),  // stdout
                            2 => array("pipe", "w"),  // stderr
                            );

    $stdin = json_encode(["user" => $request["user"],
                          "assignment" => $request["assignment"],
                          "files" => $state["files"],
                          "course" => $request["course"],
                          "operation" => $request["operation"]]);

    $checkscript = "/home/parallel05/courseware/tools/check.py";

    $process = proc_open($checkscript, $descriptorspec, $pipes);

    $msg = "";
    if (!is_resource($process)) {
      $msg = "Internal error, could not run check program";
    }
    else {
      fwrite($pipes[0], $stdin);
      fclose($pipes[0]);
      $stdout = stream_get_contents($pipes[1]);
      fclose($pipes[1]);
      $stderr = stream_get_contents($pipes[2]);
      fclose($pipes[2]);
      $return_value = proc_close($process);

      if ($stderr != "" || $return_value != 0) {
        $msg = "<b>Internal error.</b> Check program returned nonzero value or had error messages.";
        $msg .= "<p>Return value: $return_value";
        $msg .= "<p>stdout:<pre>".htmlspecialchars($stdout)."</pre>";
        $msg .= "<p>stderr:<pre>".htmlspecialchars($stderr)."</pre>";
    $wpdb->update("codedrop", ["info"=>$msg], ["rowid"=>$rowkey]);
      }
      else {
        //        $msg = $stdout;
        $wpdb->update("codedrop", ["info"=>substr($stdout, 0, 5000)], ["rowid"=>$rowkey]);
        //$msg = "<pre>".htmlspecialchars($stdout)."</pre>";    
        $msg = "<pre>$stdout</pre>";
      }
    }

    // do check
    // update table
    return ["state" => $state,
            "status" => $msg];
  }

  if ($request["operation"] == "view") {
    header("Content-Type:text/plain");
    $fc = $wpdb->get_var($wpdb->prepare("
SELECT filecontents FROM codedrop
WHERE user = %s
AND assignment = %s
AND filename = %s
AND course = %s
AND (operation = 'upload' OR operation = 'delete') 
ORDER BY rowid DESC
LIMIT 1", $request["user"], $request["assignment"], $request["filename"], $request["course"]));
    if ($fc) {
      echo $fc;
      die();
    }
    else {
      $errmsg = "File not found.";
      $wpdb->update("codedrop", ["info"=>$errmsg], ["rowid"=>$rowkey]);
      echo "File not found.";
      die();
    }
  }

  $errmsg = "Invalid operation (".$request["operation"].") for uploading tool. Contact admin. ID $rowkey";
  $wpdb->update("codedrop", ["info"=>$errmsg], ["rowid"=>$rowkey]);
  return ["status" => $errmsg];

}
