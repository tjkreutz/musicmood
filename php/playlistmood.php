<?php
/* 
 * Contains functions related to mood evaluation (voting, keeping track of score) and
 * playlists (generation).
 */

//// INSERT MOOD VOTE IN DATABASE

function insertMood($db_handle, $mood_post) 
    // put mood-, favorite- and spam-votes into database
{
    if (isset($_POST[$mood_post]))
    {    
        $qh = $db_handle->prepare("insert into mood_vector values(?, ?, ?) on duplicate
                                   key update votes = votes + ?");
        $mood = $_POST[$mood_post];
        $qh->bindParam(1, $mood);
        
        if ($mood == "spam") // what's the spam vote value of this user?
            $voteval = getRankPoints($db_handle, "spam");
        else // otherwise, just increase the mood vector by 1
            
            /* IMPORTANT NOTE: this means that a mood vote ALWAYS stays at 1, the 
            * system must not be influenced by the taste of higher ranked users!
            * Only spam votes and rank score (points) will change if a user has 
            * a higher rank. */
            $voteval = 1;            

        $qh->bindParam(2, $voteval);
        $song = $_POST["songid"];
        $qh->bindParam(3, $song);
        $qh->bindParam(4, $voteval);
        $qh->execute();

        $qh = $db_handle->prepare("insert into rates values(?, (select email from User 
                                   where username = ?), ?, ?, ?)");
        $qh->bindParam(1, $_SESSION["usr"]);
        $qh->bindParam(2, $_SESSION["usr"]);
        $qh->bindParam(3, $song);
        $time = time();
        $qh->bindParam(4, $time); 
        $qh->bindParam(5, $mood);
        $qh->execute();
    }
}

function getRankPoints($db_handle, $mood)
    // get spam vote ($mood == "spam") or score increase ($mood == "mood") value of user
{
    $score = updateScore($db_handle);

    if ($score < 0) // untrusted user
    {
        if ($mood == "spam")
            return 0;
        else
            return 1;
    }
    else if ($score >= 0 && $score < 100) // normal user
    {
        if ($mood == "spam")
            return 1;
        else
            return 1;
    }
    else if ($score >= 100 && $score < 200) // roadie
    {
        if ($mood == "spam")
            return 2;
        else
            return 2;
    }    
    else if ($score >= 200 && $score < 400) // beginner
    {
        if ($mood == "spam")
            return 4;
        else
            return 3;
    }
    else if ($score >= 400 && $score < 800) // average joe
    {
        if ($mood == "spam")
            return 8;
        else
            return 4;
    }
    else if ($score >= 800 && $score < 1600) // hero
    {
        if ($mood == "spam")
            return 16;
        else
            return 5;
    }
    else if ($score >= 1600 && $score < 5000) // legend
    {
        if ($mood == "spam")
            return 32;
        else
            return 6;
    }
    else if ($score >= 5000) // admin
    {
        if ($mood == "spam")
            return 64;
        else
            return 7;
    }
}

//// EVALUATE VOTE TIME OF USER

function evalVoteTime($db_handle, $songid) // how long ago did a user vote? 
{
    if (!empty($songid))
    {
        $qh = $db_handle->prepare("select time_stamp, mood_name from rates 
                                   where username = ? and song_ID = ?");
        $qh->bindParam(1, $_SESSION["usr"]);
        $qh->bindParam(2, $songid);
        $qh->execute();
        while ($row = $qh->fetch(PDO::FETCH_ASSOC))
        {
            if (time() - $row["time_stamp"] < 900) 
                // users have to wait 15 minutes before being able to vote again
                return 0;
        }
    }
    return 1;
}

//// GENERATE PLAYLIST

function generatePlaylist($qh, $playlist_name, $playlist_len_name, $songcount_name) 
    // generate playlist given a prepared query
{
    $qh->execute();
    $arr = $qh->fetchAll();
    $_SESSION[$playlist_name] = $arr;
    $_SESSION[$playlist_len_name] = count($arr) - 1; // - 1 because arrays start at 0
    $_SESSION[$songcount_name] = 0; // keeps track of song number
}

//// EVALUATE USER VOTE

function evalVote($db_handle, $songid) 
{
    $qh = $db_handle->prepare('select mood_name, votes from mood_vector where 
                               mood_name <> "spam" and mood_name <> "favorite" 
                               and song_ID = ? group by mood_name order by 
                               votes desc');
    $qh->bindParam(1, $songid);
    $qh->execute();
    $vote_arr = $qh->fetchAll();
    
    // dominating mood
    $highest_votes = $vote_arr[0]["votes"];
    $highest_mood = $vote_arr[0]["mood_name"];
    
    $increase = 0;
  
    if ($highest_mood == $_POST["moods"] || $highest_votes < 10) 
        // user voted on the "right" mood or $highest_votes is low: increase score
        $increase = 1;
    
    foreach ($vote_arr as $mood_row) // look for moods close to the "right" mood
    {
        if ($mood_row["votes"] >= (int)($highest_votes - $highest_votes * 0.1) &&
            $mood_row["mood_name"] == $_POST["moods"])
            // allow a difference of 10%
            $increase = 1;
    }
    
    $increasepoints = getRankPoints($db_handle, "mood");
    $score = updateScore($db_handle, $increase, $increasepoints); 
    return array($increase, $score);
}

//// INCREASE OR DECREASE USER SCORE

// A future feature could be to add a custom value to $increasepoints for higher ranks. 

function updateScore($db_handle, $increase = 2, $increasepoints = 1)
{
    // increase or decrease score
    if ($increase == 1)
    {
        $qh = $db_handle->prepare("update User set points = points + ? where username = ?");
        $qh->bindParam(1, $increasepoints);
        $qh->bindParam(2, $_SESSION["usr"]);
        $qh->execute();
    }
    else if ($increase == 0)
    {
        $qh = $db_handle->prepare("update User set points = points - ? where username = ?");
        $qh->bindParam(1, $increasepoints);
        $qh->bindParam(2, $_SESSION["usr"]);
        $qh->execute();
    }
    
    // fetch and return updated score    
    $qh = $db_handle->prepare("select points from User where username = ?");
    $qh->bindParam(1, $_SESSION["usr"]);
    $qh->execute();
    $arr = $qh->fetch(PDO::FETCH_ASSOC);
    return $arr["points"];
}

//// CHANGE USER ADMIN STATUS

function setAdminStatus($db_handle, $bool) // set admin status to 1 or 0 for user
{
    $qh = $db_handle->prepare("update User set isadmin = ? where username = ?");
    $qh->bindParam(1, $bool);
    $qh->bindParam(2, $_SESSION["usr"]);
    $qh->execute();
    $_SESSION["admin"] = $bool;
}
?>