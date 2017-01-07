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

if (!isset($_SESSION["playlist"]) || empty($_SESSION["playlist"]))
    // no playlist set or empty playlist, so generate a new one
{
    $db_handle = setupDBConnection();
    $qh = $db_handle->prepare("select * from Song natural join artist order by rand() limit 100");
        // we will fetch 100 songs at random for the index page
    generatePlaylist($qh, "playlist", "songlen", "songcount");
}

// $votebool: looking at the time between votes, is this user allowed to vote?
$votebool = 1;

// does the user's score validate (is it not below -15?)
$validate = 1;

if (!empty($_POST))
{
    if ((isset($_POST["nextbutton"]) || (isset($_POST["autonext"]) && 
        $_POST["autonext"])) && !empty($_SESSION["playlist"])) 
        // next button has been pressed, or after automatic advancement to next video
    {    
        if (isset($_POST["moods"]) || isset($_POST["spamfav"])) 
            //form has been filled in
        {
            $db_handle = setupDBConnection();
            if(isset($_SESSION["usr"])) // check if the user is logged in 
            {
                $songid = $_SESSION["playlist"][$_SESSION["songcount"]]["song_ID"];
                $votebool = evalVoteTime($db_handle, $songid);
                if ($votebool)
                {
                    // user is allowed to vote
                    insertMood($db_handle, "moods");
                    insertMood($db_handle, "spamfav");
                    if (isset($_POST["moods"]))
                    {
                        // only increase or decrease score if mood form is set
                        $score_arr = evalVote($db_handle, $songid);
                        $scoreincrease = $score_arr[0];
                        $score = $score_arr[1];
                    }
                }
            }
            else
            // user is not logged in, and cannot vote, refer to the login page
            {
                header("Location: login.php");
                exit;
            }
        }
        ++$_SESSION["songcount"];
        if ($_SESSION["songcount"] > $_SESSION["songlen"]) 
            // start at beginning of song array when songcount becomes greater than songlen
            $_SESSION["songcount"] = 0;
    }
    else if (isset($_POST["prev"])) // previous button has been pressed
    {
        --$_SESSION["songcount"]; 
        if ($_SESSION["songcount"] < 0)
            // go to the end of song array when songcount becomes smaller than 0
            $_SESSION["songcount"] = $_SESSION["songlen"];
    }
}

if (empty($_SESSION["playlist"])) // playlist is still empty, so the database is empty
{
    $songlink = '""';
    $songtitle = "Please upload some songs!";
    $songartist = "Artist unknown";
}    
else // playlist contains songs, database isn't empty
{
    $songarr = $_SESSION["playlist"][$_SESSION["songcount"]]; // next/previous song
    $songid = $songarr["song_ID"];
    $songlink = '"https://www.youtube.com/embed/' . $songid . '?enablejsapi=1"';
    $songtitle = $songarr["title"];
    $songartist = $songarr["name"];
}
 
/* Is the user allowed to vote at this time? Reason for a second check:
 * - We want to show the "Please wait... " message BEFORE a user tries to submit
 *   a vote on a just voted song.
 * - Otherwise, if the database contains a single song, users are able to vote 
 *   twice. */
if (isset($_SESSION["usr"]) && !empty($songid))
{
    $db_handle = setupDBConnection();
    $votebool = evalVoteTime($db_handle, $songid);
    
    if(file_exists("php/validate.php"))
        require_once("php/validate.php"); // contains input checking/validation
    else 
        exit("<p>Sorry, the validation library could not be found.</p>"); 
    
    $validate = validateIntegrity($db_handle);
}

if ($votebool)
    // note: this will display for unregistered users too!
    $votemessage = "makes me feel nothing"; 
else
    $votemessage = "Please wait before voting again on this song.";
    
if (!$validate)
{
    $votemessage = "Sorry, because your score is below -15 your account has been deleted.";
    $_SESSION = array();
    session_destroy();
}

// display score after vote
if (isset($score_arr))
{
    if ($scoreincrease)
    {
        switch($score) // $votemessage is the message that people before or after a vote
        {
            case 100:
                $votemessage = "Your rank is now <b>Roadie</b>, because your score reached 100!";
                break;
            case 200:
                $votemessage = "Your rank is now <b>Beginner</b>, because your score reached 200!";
                break;
            case 400:
                $votemessage = "Your rank is now <b>Average Joe</b>, because your score reached 400!";
                break;
            case 800:
                $votemessage = "Your rank is now <b>Hero</b>, because your score reached 800!";
                break;
            case 1600:
                $votemessage = "Your rank is now <b>Legend</b>, because your score reached 1600!";
                break;
            case 5000:
                $votemessage = "Your rank is now <b>ADMIN</b>, because your score reached 5000!";
                $db_handle = setupDBConnection();
                setAdminStatus($db_handle, 1);
                break;
        default:
                $votemessage = "You have earned points! Your score is now $score.";
        }
    }
    else
    {  
        switch($score) // $votemessage is the message that people see before or after a vote
        {
            case 99:
                $votemessage = "You've lost your <b>Roadie</b> rank, because your score is below 100!";
                break;
            case 199:
                $votemessage = "You've lost your <b>Beginner</b> rank, because your score is below 200!";
                break;
            case 399:
                $votemessage = "You've lost your <b>Average Joe</b> rank, because your score is below 400!";
                break;
            case 799:
                $votemessage = "You've lost your <b>Hero</b> rank, because your score is below 800!";
                break;
            case 1599:
                $votemessage = "You've lost your <b>Legend</b> rank, because your score is below 1600!";
                break;
            case 4999:
                $votemessage = "You've lost your <b>ADMIN</b> rank, because your score is below 5000!";
                $db_handle = setupDBConnection();
                setAdminStatus($db_handle, 0);
                break;
            default:
                $votemessage = "You have lost points! Your score is now $score.";
        }
    }
}
?>

<body>
  
<?php include 'menu.php'; ?>
  
<div id="videowrapper">
    
<form action=index.php method=post id="prev">
<input type="submit" name="prev" value="" alt="previous" />
</form>

<iframe id="player" width="560" height="315" src=<?php echo $songlink;?> allowfullscreen ></iframe>

<form action=index.php method=post id="next">
<input type="hidden" name="autonext" id="next" value=0 />
<input type="hidden" name="songid" id="songid" 
       value="<?php if(!empty($songid)) echo $songid; ?>" />
<input type="submit" name="nextbutton" value="" alt="next" />

</div>

<div id="nextform">

<?php echo '<h3 id="titleheader">' . $songartist . " - " . $songtitle . "</h3>"; ?>   

<input type="radio" id="favorite" name="spamfav" value="favorite" alt="Favorite" />
<label for="favorite" title="Mark as favorite" alt="favorite"></label>

<input type="radio" id="spam" name="spamfav" value="spam" alt="Spam" />
<label for="spam" title="Mark as spam" alt="spam"></label>

<p id="titlep"><?php echo $votemessage; ?></p>

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
changeMoodText("makes me feel ");
</script>

<br/>
      
<?php include 'footer.php'; ?>  

</body>

</html>