<?php 
/*
error_reporting(-1);
ini_set("display_errors", 1); /* Debugging: uncomment if needed */

//---------------------------------------- SESSION MANAGEMENT -----------------------------------------\\

if(file_exists("php/session_dbconnect.php"))
        require_once("php/session_dbconnect.php");
    else 
        exit("<p>Sorry, the function library could not be found.</p>"); 

if(file_exists('php/config.php'))
        require('php/config.php'); // obtain website url
    else 
        exit("<p>Sorry, the configuration file could not be found.</p>");
		
if(file_exists("php/playlistmood.php"))
    require_once("php/playlistmood.php"); // contains playlist and mood evaluation functions
else 
    exit("<p>Sorry, the playlist/mood evaluation library could not be found.</p>");

startSession();

if(!isset($_SESSION["usr"]) || $_SESSION["admin"])
    // someone is unknown, logged out or has admin rights: redirect to login
{
    header("Location: " . $url . "login.php");
    echo("<p>You have to login to be able to view this page.</p>"); 
        // if - for whatever reason - someone misleads the header, show info 
    exit;
}
	?>
	
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta charset="UTF-8"/>
<title>MUSICMOOD - My MUSICMOOD</title>
<link href="css/css.css" rel="stylesheet" type="text/css">
<link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>

<div id="container">

<?php include 'menu.php'; ?>
 
<div id="content">

<h2>My MUSICMOOD</h2>

<?php 
$escaped_uname = htmlentities($_SESSION["usr"]); // make username safe to output
?>
<p>Welcome, <?php echo $escaped_uname; ?>. This is your personal page.</p>
<?php
$db_handle=setupDBConnection();
$score = getRankPoints($db_handle, "mood");
 switch($score) // $votemessage is the message that people before or after a vote
        {
            case 1:
                echo " Your score is still locked, please keep voting to earn your first achievement </br></br> <img src='img/achievements/locked.jpg'> ";
                break;
            case 2:
                echo "You are now a roadie! </br></br><img src='img/achievements/roadie.jpg'> ";
                break;
            case 3:
                echo "You are now a beginner!  </br></br><img src='img/achievements/beginner.jpg'>";
                break;
            case 4:
                echo "You are now an average Joe! </br></br><img src='img/achievements/averagejoe.jpg'>";
                break;
            case 5:
                echo "You are now a hero!!! </br></br><img src='img/achievements/hero.jpg'>";
                break;
            case 6:
                echo "You are a LEGEND. </br></br><img src='img/achievements/legend.jpg'>";
                break;
			case 7:
                echo "You are an admin. </br></br><img src='img/achievements/admin.png'>";
                break;
        default:
                echo "Something is wrong. You cannot see your rank right now.";
        }
     ?>
	
</div>
</div>

<?php include 'footer.php'; ?>

</body>

</html>