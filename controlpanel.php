<?php 

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
        
startSession();

if(!isset($_SESSION["usr"]) || !$_SESSION["admin"])
    // someone is unknown, logged out or has no admin rights: redirect to login
{
    header("Location: " . $url . "login.php");
    echo("<p>You have to login to be able to view this page.</p>"); 
        // if - for whatever reason - someone misleads the header, show info 
    exit;
}

$db_handle = setupDBConnection();

// delete song when removal is pressed.	 
if (!empty($_POST))
{
    if ((isset($_POST["removal"]))) 
    {
	 $songid=$_POST["removal"];
	 $db_handle = setupDBConnection();
     $qh = $db_handle->prepare("DELETE FROM artist WHERE song_ID='" . $songid . "';
	 DELETE FROM mood_vector WHERE song_ID='" . $songid . "';
	 DELETE FROM genre WHERE song_ID='" . $songid . "';
	 DELETE FROM Song WHERE song_ID='" . $songid . "';");
     $qh->execute();  
     }
}

//find songs with most spam votes
$qh = $db_handle->prepare("SELECT * FROM Song NATURAL JOIN artist NATURAL JOIN mood_vector WHERE mood_name = 'spam' ORDER BY votes DESC");
$qh->execute();   
?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta charset="UTF-8"/>
<title>MUSICMOOD - Control Panel</title>
<link href="css/css.css" rel="stylesheet" type="text/css">
</head>

<div id="container">

<?php include 'menu.php'; ?>
 
<div id="content">
<?php 
if (!empty($_POST))
{
    if ((isset($_POST["removal"]))) 
    {
	 $songid=$_POST["removal"];
	 $db_handle = setupDBConnection();
     $qh2 = $db_handle->prepare("DELETE FROM Song WHERE song_ID='" . $songid . "'");
     $qh2->execute();  
     }
}
	     
$escaped_uname = htmlentities($_SESSION["usr"]); // make username safe to output
?>

	<h2>Control Panel</h2>
	<p>Welcome, <?php echo $escaped_uname; ?>. This is your personal control panel. <br/>
	<img src="img/achievements/admin.png" alt="Smiley face"> <br/>
	
	In this panel you can see which songs are marked as spam. <br/>
	Please delete a clip when it is nog a song or when a song is in bad quality <br/>
	When you see a typo, we would like you to correct this.</p>
	
<center><?php 
echo "<form action=controlpanel.php method=post><p><table border='1'><tr><td>Song ID</td><td>Title</td><td>Artist</td><td>Votes</td><td>Remove</td></tr>";
for ($i=0;$i<20;$i++){
 $row = $qh->fetch(PDO::FETCH_ASSOC);
 echo "<tr><td>" . $row['song_ID'] . "</td><td>" . $row['title'] . "</td><td>" . $row['name'] . "</td><td>" . $row['votes'] . "</td><td>
 <button type='submit' value='" . $row['song_ID'] . "' name='removal'>Remove</button></td></tr>";}
 ?></table></p></center>
	

</div>
</div>

<?php include 'footer.php'; ?>

</body>

</html>