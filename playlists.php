<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta charset="UTF-8"/>
<title>MUSICMOOD - Rate your music's mood!</title>
<link href="css/css.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>

<?php

error_reporting(-1);
ini_set("display_errors", 1); /* Debugging: uncomment if needed */

if(file_exists("php/session_dbconnect.php"))
    require_once("php/session_dbconnect.php"); // contains session management/DB connection
else 
    exit("<p>Sorry, the session management/DB connection library could not be found.</p>"); 
   
if(file_exists('php/config.php'))
    require_once('php/config.php'); // obtain website URL
else 
    exit("<p>Sorry, the configuration file could not be found.</p>");
    
if(file_exists("php/playlistmood.php"))
    require_once("php/playlistmood.php"); // contains playlist and mood evaluation functions
else 
    exit("<p>Sorry, the playlist/mood evaluation library could not be found.</p>"); 

startSession();

if (!empty($_POST))
{
    if ((isset($_POST["nextbutton"]) || (isset($_POST["autonext"]) && $_POST["autonext"]))) 
        // next button has been pressed, or after automatic advancement to next video
    {    
        
        if (isset($_POST["moods"]))
            //form has been filled in
        {
            $db_handle = setupDBConnection();
            // select the 100 highest rated songs for a mood and shuffle them
            if ($_POST["moods"] == "favorite")
            {
                if(isset($_SESSION["usr"])) // check if the user is logged in 
                {
                    $qh = $db_handle->prepare("select * from (select * from rates natural 
                                               join mood_vector natural join artist natural
                                               join Song where mood_name = ? and username = ?
                                               order by votes desc limit 100) as top order by
                                               rand()");
                    $qh->bindParam(1, $_POST["moods"]);
                    $qh->bindParam(2, $_SESSION["usr"]);
                }
                else
                    // user is not logged in, so we cannot have a look at his or her 
                    // favorite songs: refer to the login page
                {
                    header("Location: login.php");
                    exit;
                }
            }
            else
            {
                $qh = $db_handle->prepare("select * from (select * from Song natural join
                                           artist natural join mood_vector where mood_name
                                           = ? order by votes desc limit 100) as top order
                                           by rand()");
                $qh->bindParam(1, $_POST["moods"]);
            }                          
            generatePlaylist($qh, "playlist2", "songlen2", "songcount2");
        }
        
        ++$_SESSION["songcount2"];
        if ($_SESSION["songcount2"] > $_SESSION["songlen2"]) 
            // start at beginning of song array when songcount becomes greater than songlen
            $_SESSION["songcount2"] = 0;
    }
    else if (isset($_POST["prev"])) // previous button has been pressed
    {
        --$_SESSION["songcount2"]; 
        if ($_SESSION["songcount2"] < 0)
            // go to the end of song array when songcount becomes smaller than 0
            $_SESSION["songcount2"] = $_SESSION["songlen2"];
    }
}

if (!isset($_SESSION["playlist2"])) // no playlists page playlist set, provide some info
{
    $songlink = '""';
    $songartist = "Choose a mood";
    $songtitle = "Press the next button";
}
else if (isset($_SESSION["playlist2"]) && empty($_SESSION["playlist2"])) 
    // playlist is still empty, so the database is empty or not enough votes
{
    $songlink = '""';
    $songartist = "No songs have this mood";
    $songtitle = "Please vote on some songs!";
}    
else // playlist contains songs, database isn't empty
{
    $songarr = $_SESSION["playlist2"][$_SESSION["songcount2"]]; // next/previous song
    $songid = $songarr["song_ID"];
    $songlink = '"https://www.youtube.com/embed/' . $songid . '?enablejsapi=1"';
    $songtitle = $songarr["title"];
    $songartist = $songarr["name"];
}
?>

<body>
  
<?php include 'menu.php'; ?>
  
<div id="videowrapper">
    
<form action=playlists.php method=post id="prev">
<input type="submit" name="prev" value="" alt="previous" />
</form>

<iframe id="player" width="560" height="315" src=<?php echo $songlink;?> allowfullscreen ></iframe>

<form action=playlists.php method=post id="next" >
<input type="hidden" name="autonext" id="next" value=0 />
<input type="hidden" name="songid" id="songid" 
       value="<?php if(!empty($songid)) echo $songid; ?>" />
<input type="submit" name="nextbutton" value="" alt="next" />

</div> 

<div id="nextform">

<?php echo '<h3 id="titleheader">' . $songartist . " - " . $songtitle . "</h3>"; ?>

<input type="radio" id="favorite" name="moods" value="favorite" alt="Favorite" />
<label for="favorite" title="Favorite" alt="favorite"></label>

<p id="titlep">I feel... nothing</p>

<input type="radio" id="hyper" name="moods" value="hyper" />
<label for="hyper" title="Hyper" alt="Hyper"></label>

<input type="radio" id="happy" name="moods" value="happy" />
<label for="happy" title="Happy" alt="Happy"></label>

<input type="radio" id="relaxed" name="moods" value="relaxed"/>
<label for="relaxed" title="Relaxed" alt="Relaxed"></label>

<input type="radio" id="sad" name="moods" value="sad" />
<label for="sad" title="Sad" alt="Sad"></label>

<input type="radio" id="confused" name="moods" value="confused" />
<label for="confused" title="Confused" alt="Confused"></label>

<input type="radio" id="angry" name="moods" value="angry" />
<label for="angry" title="Angry" alt="Angry"></label>

</form>

</div>

<script type="text/javascript" src="js/iframeAPI.js"></script>

<script type="text/javascript">  
changeMoodText("I feel... ");
</script>

<br/>
      
<?php include 'footer.php'; ?>  

</body>

</html>