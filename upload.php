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
?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta charset="UTF-8"/>
<title>MUSICMOOD - Upload Songs</title>
<link href="css/css.css" rel="stylesheet" type="text/css">
<link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>

<div id="container">

<?php include 'menu.php'; ?>

<div id="content">

<h2>Upload</h2>

<?php
if(!isset($_SESSION["usr"])) // unknown/logged out person visits upload page
{
    echo '<p class="error">You have to <a href="registration.php">register</a> or
          <a href="login.php">login</a> to be able to view this page.</p>'; 
    echo '</div></div>';
    include 'footer.php';
    echo '</body></html>';
    exit;
}

//--------------------------------------- FUNCTION DEFINITIONS ----------------------------------------\\

function uploadSong($db_handle, $title, $artist, $genre, $vidID)
{
    // upload a song to the database
    $q_handle = $db_handle->prepare("insert into Song values(?,?)");
    $q_handle->bindParam(1, $vidID);
    $q_handle->bindParam(2, $title);
    $q_handle->execute();
    $q_handle = $db_handle->prepare("insert into artist values(?,?)");
    $q_handle->bindParam(1, $artist);
    $q_handle->bindParam(2, $vidID);
    $q_handle->execute();
    $q_handle = $db_handle->prepare("insert into genre values(?,?)");
    $q_handle->bindParam(1, $genre);
    $q_handle->bindParam(2, $vidID);
    $q_handle->execute();
    
    // fetch e-mail from the database
    $q_handle = $db_handle->prepare("select email from User where username = ?");
    $q_handle->bindParam(1, $_SESSION["usr"]);
    $q_handle->execute();
    $arr = $q_handle->fetch(PDO::FETCH_ASSOC);
    $email = $arr["email"];
    
    // put username, e-mail, song ID and timestamp into the database
    $q_handle = $db_handle->prepare("insert into uploads values(?,?,?,?)");
    $q_handle->bindParam(1, $_SESSION["usr"]);
    $q_handle->bindParam(2, $email);
    $q_handle->bindParam(3, $vidID);
    $timestamp = time();
    $q_handle->bindParam(4, $timestamp);
    $q_handle->execute();
    
    // don't show the form, echo the remaining part of the page and exit
            echo '<p class="success">Success! Your video has been uploaded!</p>
                  <p>Please click <a href="upload.php">here</a> to upload another song.</p>';
            echo '</div></div>';
            include 'footer.php';
            echo '</body></html>';
            exit;
}

//--------------------------------------------- MAIN PART ---------------------------------------------\\

if(file_exists("php/validate.php"))
    require_once("php/validate.php"); // contains input checking/validation
else 
    exit("<p>Sorry, the validation library could not be found.</p>"); 

$artist_error = $song_error = $link_error = $genre_error = "";

if (!empty($_POST) && isset($_POST["sub"])) // $_POST is set
{             
    $artist_error = isEmpty("artist", "an artist name");
    $song_error = isEmpty("song", "a song title");
    $genre_error = isEmpty("genre", "the song's genre");
    $link_array = checkYoutubeLink($_POST["link"]);
    $link_error = $link_array[0];
    $vidID = $link_array[1];

    if (!$artist_error && !$song_error && !$genre_error && !$link_error) // no input errors
    {
        $db_handle = setupDBConnection();
        $link_error = existsInDB($db_handle, "select song_ID from Song where song_ID = ?",
                                 $vidID, "Youtube video", "song_ID");
        $song_error = existsInDB($db_handle, "select title from Song where title = ?",
                                 $_POST["song"], "song", "title", 1);        
        $artist_error = existsInDB($db_handle, "select name from artist where name = ?",
                                   $_POST["artist"], "song", "name", 1);
        if (!$link_error && (!$song_error || !$artist_error))
        {      
            // Youtube video ID and song or artist combination do not exist, submit video 
            $song_error = $artist_error = "";
            uploadSong($db_handle, $_POST["song"], $_POST["artist"], $_POST["genre"], $vidID);
        }
    }
}
?>

<div class="form-align">
 
<form action="upload.php" method="post">

<p>Youtube link</p>

<input type="text" class="form-control" name="link"
<?php
if (!empty($_POST))
    echo ' value="' . $_POST["link"] . '"';
?>
/>

<label for="link" class="error">
<? echo $link_error; ?>
</label>
<br/>

<p>Song title</p>
  
<input type="text" class="form-control" name="song" maxlength="50"
<?php
if (!empty($_POST))
    echo ' value="' . $_POST["song"] . '"';
?>
/>

<label for="song" class="error">
<? echo $song_error; ?>
</label>
<br/>

<p>Artist name</p>

<input type="text" class="form-control" name="artist" maxlength="50"
<?php
if (!empty($_POST))
    echo ' value="' . $_POST["artist"] . '"';
?>
/>

<label for="artist" class="error">
<? echo $artist_error; ?>
</label>
<br/>

<p>Genre</p>
  
<input type="text" class="form-control" name="genre" maxlength="50"
<?php
if (!empty($_POST))
    echo ' value="' . $_POST["genre"] . '"';
?>
/>

<label for="genre" class="error">
<? echo $genre_error; ?>
</label>
<br/><br/>

<input type="submit" class="btn" name="sub" value="Submit"/>
</form>

</div>
</div>
</div>

<?php include 'footer.php'; ?>

</body>

</html>