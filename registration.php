<?php

error_reporting(-1);
ini_set("display_errors", 1); /* Debugging: uncomment if needed */

//---------------------------------------- SESSION MANAGEMENT -----------------------------------------\\

if(file_exists("php/session_dbconnect.php"))
    require_once("php/session_dbconnect.php"); // contains session management/DB connection
else 
    exit("<p>Sorry, the session management/DB connection library could not be found.</p>"); 

startSession();
?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta charset="UTF-8"/>
<title>MUSICMOOD - Registration Page</title>
<link href="css/css.css" rel="stylesheet" type="text/css">
<link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>

<body>

<div id="container">

<?php include 'menu.php'; ?> 
 
<div id="content">

<script type="text/javascript">
var RecaptchaOptions = 
{
    theme : 'white' // set a nice Captcha theme
};
</script>

<h2>Registration</h2>

<?php
//--------------------------------------- FUNCTION DEFINITIONS ----------------------------------------\\

function createUser($db_handle) // put a new user in the database
{
    //hash = password_hash($_POST["password"], PASSWORD_DEFAULT); // for future PHP versions
    if(file_exists("php/PasswordHash.php"))
        require("php/PasswordHash.php"); // PHPass hashing algorithm
    else 
        exit("<p>Sorry, the hashing library could not be found.</p>");

    $hasher = new PasswordHash(12, false);
    $hash = $hasher->HashPassword($_POST["password"]);
    
    $datepart = explode("-", $_POST["b_date"]);
    $b_date = $datepart[2] . $datepart[1] . $datepart[0]; // format suitable for MySQL

    $q_handle = $db_handle->prepare("insert into User values(?,?,?,?,?,?,?)");
    $q_handle->bindParam(1, $_POST["username"]);
    $q_handle->bindParam(2, $_POST["email"]);
    $q_handle->bindParam(3, $hash);
    $q_handle->bindParam(4, $_POST["gender"]);
    $q_handle->bindParam(5, $b_date);
    $q_handle->bindValue(6, 0, PDO::PARAM_INT);
    $q_handle->bindValue(7, 0, PDO::PARAM_INT);
    $q_handle->execute();
}

//--------------------------------------------- MAIN PART ---------------------------------------------\\

if(file_exists("php/validate.php"))
    require_once("php/validate.php"); // contains input checking/validation
else 
    exit("<p>Sorry, the validation library could not be found.</p>"); 

$mail_error = $uname_error = $pwd_error = $bdate_error = $captch_error = "";

if (!empty($_POST) && isset($_POST["sub"])) // $_POST is set
{             
    $mail_error = checkMail($_POST["email"]);
    $uname_error = isEmpty("username", "a username");
    $pwd_error = isEmpty("password", "a password");
    $bdate_error = checkBdate($_POST["b_date"]);
    $captch_error = CaptchaOK();

    if (!$mail_error && !$uname_error && !$pwd_error && !$bdate_error && !$captch_error) // no input errors
    {
        $db_handle = setupDBConnection();
        $mail_error = existsInDB($db_handle, "select email from User where email = ?", 
                                 $_POST["email"], "e-mail", "email", 1);
        $uname_error = existsInDB($db_handle, "select username from User where username = ?", 
                                  $_POST["username"], "username", "username", 1);
        if (!$mail_error && !$uname_error) // username & email do not exist, register user 
        {
            createUser($db_handle);
            // expire the session of other users
            $_SESSION = array();
            session_destroy();
            // don't show the form, echo the remaining part of the page and exit
            echo '<p class="success">Success! Welcome to MUSICMOOD!<p>
                  <p>Please click <a href="login.php">here</a> to log in.</p>';
            echo '</div></div>';
            include 'footer.php';
            echo '</body></html>';
            exit;
        }
    }
}
?>

<div class="form-align">

<form action="registration.php#formbookmark" id="formbookmark" method="post">

<p>E-mail</p>

<input type="text" class="form-control" name="email" maxlength="50"
<?php
if (!empty($_POST))
    echo ' value="' . $_POST["email"] . '"';
?>
/>

<label for="email" class="error">
<?php echo $mail_error; ?>
</label>

<p>Username</p>
<input type="text" class="form-control" name="username" maxlength="15"
<?php
if (!empty($_POST))
    echo ' value="' . $_POST["username"] . '"';
?>
/>

<label for="username" class="error">
<?php echo $uname_error; ?>
</label>

<p>Password</p>
<input type="password" class="form-control" name="password" maxlength="30"/>
<label for="password" class="error">
<?php echo $pwd_error; ?>
</label>

<p>Birth date</p>
<input type="text" class="form-control" name="b_date" maxlength="10"
<?php 
if (!empty($_POST))
    echo ' value="' . $_POST["b_date"] . '"';
?>
/>
<label for="b_date" class="error">

<?php echo $bdate_error; ?>
</label>

<p>Gender</p> 
<p>          
<select name="gender">

<option value="M"
<?php
if (!empty($_POST) && $_POST["gender"] == 'M')
    echo 'selected="selected"';
?>
>M</option>

<option value="F"
<?php
if (!empty($_POST) && $_POST["gender"] == 'F')
    echo 'selected="selected"';
?>
>F</option>
</select>
</p>
<br/>

<input type="submit" class="btn" name="sub" value="Submit"/>
</form>
</div>
</div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>