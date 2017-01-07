<?php
/*
error_reporting(-1);
ini_set("display_errors", 1); /* Debugging: uncomment if needed */

//---------------------------------------- SESSION MANAGEMENT -----------------------------------------\\

if(file_exists("php/session_dbconnect.php"))
    require_once("php/session_dbconnect.php"); 
        // contains functions that are used more than once (DB connection, sessions, Captcha's) 
else 
    exit("<p>Sorry, the function library could not be found.</p>"); 

if(file_exists('php/config.php'))
    require('php/config.php'); // obtain website URL
else 
    exit("<p>Sorry, the configuration file could not be found.</p>");
        
startSession();

if(isset($_POST["logout"])) 
{
    // user wants to log out (and was referred to this page), kill his/her session
    $_SESSION = array();
    session_destroy();
}

if(isset($_SESSION["usr"])) 
{
    if (!$_SESSION["admin"])
    {
        // normal user login session active: redirect to personal page
        header("Location: " . $url . "mymusicmood.php");
        exit;
    }
    else
    {    
        // admin user login session active: redirect to admin page
        header("Location: " . $url . "controlpanel.php");
        exit;
    }
}
?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta charset="UTF-8"/>
<title>MUSICMOOD - Login Page</title>
<link href="css/css.css" rel="stylesheet" type="text/css">
<link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>

<body>

<div id="container">

<?php include 'menu.php'; ?>
 
<div id="content">

<?php 
if(file_exists("php/validate.php"))
    require_once("php/validate.php"); // contains input checking/validation
else 
    exit("<p>Sorry, the validation library could not be found.</p>"); 

$uname_error = $pwd_error = $captch_error = "";
$captcha_on = 0;

if (!empty($_POST) && isset($_POST["sub"])) // $_POST is set, form submitted
{         
    $uname_error = isEmpty("username", "a username");
    $pwd_error = isEmpty("password", "a password");
    
    if (isset($_POST["recaptcha_response_field"])) // check Captcha if it's set to on
    {
        $captch_error = CaptchaOK();
        $captcha_on = 1; // turn it on again
    }

    if (!$uname_error && !$pwd_error && !$captch_error) // no input errors
    {
        $db_handle = setupDBConnection();
        $q_handle = $db_handle->prepare("select username, password from User where username = ?");
        $q_handle->bindParam(1, $_POST["username"]);
        $q_handle->execute();
        $arr = $q_handle->fetch(PDO::FETCH_ASSOC);
    
        if(file_exists("php/PasswordHash.php"))
            require("php/PasswordHash.php"); // PHPass hashing algorithm
        else 
            exit("<p>Sorry, the hashing library could not be found.</p>");

        $hasher = new PasswordHash(12, false);
        $correct = $hasher->CheckPassword($_POST["password"], $arr["password"]);
            // compare entered password and hash
            
        if (!empty($arr["username"]) && $correct) 
            // login data valid: regenerate session ID and redirect to personal page
        {
            evalAttempt($db_handle, 1); // user regains our trust: reset login attempts
            session_regenerate_id();
            $_SESSION["usr"] = $_POST["username"];
            // fetch from database whether user is admin or not
            $q_handle = $db_handle->prepare("select isadmin from User where username = ?");
            $q_handle->bindParam(1, $_SESSION["usr"]);
            $q_handle->execute();
            $arr = $q_handle->fetch(PDO::FETCH_ASSOC);
            $_SESSION["admin"] = $arr["isadmin"];
            if (!$_SESSION["admin"])
                header("Location: " . $url . "mymusicmood.php");
            else 
                header("Location: " . $url . "controlpanel.php");
        }
        else 
            $captcha_on = evalAttempt($db_handle, 0); // users loses our trust   
            
        if (!$captcha_on || ($captcha_on && !$captch_error && isset($_POST["recaptcha_response_field"]))) 
            // Captcha off/entered correctly, so provide user feedback about wrong login
            $uname_error = $pwd_error = "Incorrect password or user does not exist.";
    }            
}
?>

<script type="text/javascript">
var RecaptchaOptions = 
{
    theme : 'white' // set a nice Captcha theme
};
</script>

<h2>Login</h2>

<div class="form-align">
<form action=login.php method=post>

<p>Username</p>
<input type="text" class="form-control" name="username" maxlength="15"
<?php
if (isset($_POST["username"]))
    echo ' value="' . $_POST["username"] . '"';
?>
/>

<label for="username" class="error">
<?php echo $uname_error; ?>
</label>
<br/>

<p>Password</p>
<input type="password" class="form-control" name="password" maxlength="30"/>

<label for="password" class="error">
<?php echo $pwd_error; ?>
</label>

<?php 
if ($captcha_on) // insert a Captcha
{
    echo '<br/><br/><p class="error">Too many login attempts!' . 
         ' Please prove that you are human.</p>';
    if ($captch_error) // there is a Captcha error message
        echo '<p class="error">' . $captch_error . '</p>';
    echo Captcha();
}
else 
    echo '<br/>';
?>
<br/>

<input type="submit" class="btn" name="sub" value="Submit"/>

</form>
</div>
</div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>