<?php

  /*

requires: ua-config.json

authenticates user and defines several constants:

UA_LOGGED_IN : boolean
UA_USERNAME : email address, or "anonymous"
UA_AUTHDOMAIN : Facebook, Google, Princeton, etc or "n/a"
UA_PROVIDERS : space-separated list of known providers
UA_CONFIG_ERROR : string (error message, or empty string if config ok)

   */

  /*
    internal details:
?auth=logout will log out
?auth=XXX will try to log in with authentication domain XXX
  */

  // try to load the ua-config.json file and see if it includes required fields
$config_error = ''; // no news is good news
$data = file_get_contents(dirname(__FILE__)."/"."ua-config.json");
if ($data == FALSE) {
  $config_error = "Couldn't find ua-config.json";
  $config_error .= "<br><b>You cannot submit any code.</b>";
}
else {
  $config_jo = json_decode($data, TRUE); // associative array
  if ($config_jo == NULL) {
    $config_error = "ua-config.json is not JSON formatted";
  }
  else foreach (array("hybridauth-base_url",
    "hybridauth-Auth_path") as $required) { 
      if (!array_key_exists($required, $config_jo)) {
        $config_error = "ua-config.json does not define $required";
        if ($required=="safeexec-executable-abspath" || $required=="java_jail-abspath")
          $config_error .= "<br><b>You cannot submit any code.</b>";
        break;
      }
    }      
}

if ($config_error != "") {
  define("UA_PROVIDERS", "");  
}
else {
  $ha_config = array();
  $ha_config["base_url"] = $config_jo["hybridauth-base_url"];
  $ha_config["providers"] = array();

  // configure Facebook if details exist in ua-config.json
  if (array_key_exists("facebook-id", $config_jo) 
      && array_key_exists("facebook-secret", $config_jo)) {
    $ha_config["providers"]["Facebook"] = 
      array("enabled" => true,
            "keys"    => 
            array("id" => $config_jo["facebook-id"],
                  "secret" => $config_jo["facebook-secret"]
                  ),
            "scope"   => "email",
            );
  }    

  // configure Google if details exist in ua-config.json
  if (array_key_exists("google-id", $config_jo) 
      && array_key_exists("google-secret", $config_jo)) {
    $ha_config["providers"]["Google"] = 
      array("enabled" => true,
            "keys"    => 
            array("id" => $config_jo["google-id"],
                  "secret" => $config_jo["google-secret"]
                  ),
            "scope"           => "https://www.googleapis.com/auth/userinfo.email",
            "access_type"     => "online",
            "approval_prompt" => "auto",
            "hd" => "usc.edu",
	    "name" => "@usc.edu via Google"
            );
  }

  //echo json_encode($ha_config);
  
  // now call the hybridauth library
  include_once (  $config_jo["hybridauth-Auth_path"] );
  $hybridauth = new Hybrid_Auth( $ha_config );

  if (array_key_exists('auth', $_REQUEST) && $_REQUEST['auth']=='logout') {
    $hybridauth->logoutAllProviders();
  }

  // try logging in, also build a list of providers
  $providers = "";
  foreach ($ha_config["providers"] as $authdomain => $domaininfo) {
    $providers .= " " . $authdomain;
    
  if (array_key_exists('auth', $_REQUEST) && $_REQUEST['auth']==$authdomain)
      $hybridauth->authenticate( $authdomain );  
    
    if ($hybridauth->isConnectedWith($authdomain) && !defined('UA_LOGGED_IN')) {
      $adapter = $hybridauth->authenticate( $authdomain );  
      try {
      	  $user_profile = $adapter->getUserProfile(); 
      }
      catch (Exception $e) {
          echo "Error getting user profile. Usually this means you have to <a href='index.php?auth=logout'>log out</a> and log back in.";
          echo "<br>data:<tt>" . $e->getMessage() . "</tt>";
          die();
      }
      define ('UA_USERNAME', $user_profile->emailVerified); 
      define ('UA_AUTHDOMAIN', $authdomain); 
      define ('UA_LOGGED_IN', true);
      if (!(substr(UA_USERNAME, -8) === "@usc.edu"))  {
        echo "You need to log in with your @usc.edu account, but you".
	" logged in as " . UA_USERNAME . " instead. <a href='index.php?auth=logout'>Click here to try again.</a>";
	die();
}
    }     
  }
  
  // some schools will want to use their own authentication
  if (substr($_SERVER['SERVER_NAME'], -13)=='princeton.edu') {
    $providers = " Princeton" . $providers;
    
    include_once('../CAS-1.3.2/CAS.php');
    phpCAS::setDebug();
    phpCAS::client(CAS_VERSION_2_0,'fed.princeton.edu',443,'cas');
    phpCAS::setNoCasServerValidation();
    
    if ($_REQUEST['auth']=='Princeton') {
      phpCAS::forceAuthentication();
    }
    
    if (phpCAS::isAuthenticated() && !defined('UA_LOGGED_IN')) {
      if ($_REQUEST['auth']=='logout') {
        phpCAS::logout();
      }
      
      define('UA_USERNAME', phpCAS::getUser() . '@princeton.edu');
      define('UA_AUTHDOMAIN', 'Princeton'); 
      define('UA_LOGGED_IN', true);
    }     
  }

// pass the list of authentication services to the next php file
  if (strlen($providers) > 0) {
    // e.g. "Facebook Google"
    define("UA_PROVIDERS", substr($providers, 1));
  }
  else {
    define("UA_PROVIDERS", "");
    $config_error = "No authentication providers are configured.";
  }
}

// define all remaining constants
if (!defined('UA_LOGGED_IN')) {
  define('UA_LOGGED_IN', false);
  define('UA_USERNAME', "anonymous");
  define('UA_AUTHDOMAIN', "n/a"); 
}

if (strlen($config_error) > 0) {
  define('UA_CONFIG_ERROR_DIV',
         "<div><i>$config_error<br>You will not be able to log in, load, or save.</i>
  <script type='text/javascript'>alert('Configuration problem! See status at top of page.');</script>
</div>");
 }
 else {
   define('UA_CONFIG_ERROR_DIV', '');
 }

define('UA_JAVASCRIPT', "
<script type='text/javascript'>
var auth = function(provider) {
  // go to ?group=prob1+prob2&start=prob2&auth=provider
  var url = '?auth='+provider;
  window.location.href = url;
}
</script>
");

if (UA_CONFIG_ERROR_DIV) {
  define('UA_INFO', UA_CONFIG_ERROR_DIV);
}
else {
  if (UA_LOGGED_IN) {
    define('UA_INFO', "<a class=\"smaller\" href=\"javascript:auth('logout')\">Logout from <b>".UA_USERNAME."</b></a>");
  } 
  else {
    define('UA_INFO', "<a class=\"smaller\" href='javascript:auth(\"Google\")'>Login to @usc account via Google</a>");
  }
}

if (!is_admin()) echo UA_JAVASCRIPT;
