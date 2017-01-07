<?php 
/* 
 * session_dbconnect.php contains two functions that are used quite 
 * often on the site:
 * - Connecting to the database
 * - Starting a session and keeping track of session expiration
 */
 
//// DATABASE CONNECTION

function setupDBConnection()
{
    if(file_exists('php/config.php')) // change the location to from where session_dbconnect.php is included!
        require('php/config.php');    // obtain database configuration
    else 
        exit("<p>Sorry, the configuration file could not be found.</p>");

    try
    { 
        $db_handle = new PDO("mysql:dbname=$dbname;host=$host;port=3306;", $username, $password);
	$db_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db_handle->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $db_handle;
    }
    catch (PDOException $e)
    {
        echo "Connection failed, something's wrong: " . $e->getMessage();
        exit;
    }
}

//// SESSION MANAGEMENT

function startSession() // start session and keep track of session expiration
{
    $secure = 1;
    $httponly = 1;
    session_set_cookie_params(7*24*3600, "", "", $secure, $httponly); 
    session_start();

    if (!isset($_SESSION["expire"]) || (time() > $_SESSION["expire"])) 
    {
        // new visit or old session has expired: destroy old session...
        $_SESSION = array();
        session_destroy(); 
        // ...and start a new one/create a new session ID
        session_start();
    }
    
    $_SESSION["expire"] = time() + 7*24*3600; // set (idle) timeout time
}
