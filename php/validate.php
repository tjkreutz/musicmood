<?php
/* validate.php contains all of the site's functionality regarding input
 * checking/validation.
 */
 
//// EMPTY INPUT 

function isEmpty($post_name, $input_name) // input_name needs "a" or "an" added
{
    if (isset($_POST[$post_name]) && empty($_POST[$post_name])) 
        // input field set, but empty
        return 'Please enter ' . $input_name . '.';
    return "";
}

//// CHECK E-MAIL

function checkMail($email) 
{
    $mail_error = isEmpty("email", "an e-mail address");
    
    if ($mail_error) // error message is not empty
        return $mail_error;
        
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) // invalid e-mail
        return 'This is not a valid e-mail address.';
}

//// CHECK IF INPUT EXISTS IN DATABASE  

function existsInDB($db_handle, $query, $value, $input_name, $db_name, $to_lowercase = 0)
{       
    $q_handle = $db_handle->prepare($query);
    $q_handle->bindParam(1, $value);
    $q_handle->execute();
    $arr = $q_handle->fetch(PDO::FETCH_ASSOC);
    
    if ($to_lowercase)
    {
        $arr[$db_name] = strtolower($arr[$db_name]);
        $value = strtolower($value);
    }
    
    if ($arr[$db_name] != $value) // e-mail doesn't exist
        return "";
        
    return "That " . $input_name . " does already exist.";
}

//// CHECK BIRTH DATE 
  
function checkBdate($bdate) // check birth date with a regular expression
{
    $bdate_error = isEmpty("b_date", "a birth date");
    
    if ($bdate_error) // error message is not empty
        return $bdate_error;
        
    else if (preg_match("/\d{1,2}-\d{1,2}-\d{4}/", $_POST["b_date"])) 
        // birtday matches regular expression
    {
        $datemax = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        $datepart = explode("-", $_POST["b_date"]);
        
        if ($datepart[1] < 1 || $datepart[1] > 12) 
            return 'Please enter a valid birth month.';
        
        else if ($datepart[2] < 1900 || $datepart[2] > date('Y') - 4)
            return 'Please enter a valid birth year.';
        
        else if ($datepart[0] < 1 || $datepart[0] > $datemax[$datepart[1] - 1])
            return 'Please enter a valid birth day.';
        
        else // valid birth date
            return "";
    } 
    
    return 'Please use a format like DD-MM-YYYY.';
}

//// ADDING AND CHECKING CAPTCHA'S 

if(file_exists("php/recaptchalib.php"))
    require("php/recaptchalib.php");
else 
    exit('Sorry, the Captcha library could not be found.');

function Captcha() // a very simple Captcha wrapper which returns Captcha HTML
{   
    if(file_exists("php/config.php"))
        require("php/config.php"); // obtain the public Captcha key
    else 
        exit('Sorry, the configuration file could not be found.');
        
    $secure = 1;
    return recaptcha_get_html($publickey, null, $secure);
}

function CaptchaOK() // check if the Captcha value is correct
{
    if(file_exists("php/config.php"))
        require("php/config.php"); // obtain the private Captcha key
    else 
        exit('Sorry, the configuration file could not be found.');
        
    if(isset($_POST["recaptcha_response_field"]))
    {
        $resp = recaptcha_check_answer($privatekey,
                                       $_SERVER["REMOTE_ADDR"],
                                       $_POST["recaptcha_challenge_field"],
                                       $_POST["recaptcha_response_field"]);
        if($resp->is_valid) // Captcha correct!
            return "";
        return 'Sorry, but your Captcha value is incorrect.';
    }
}

//// KEEP TRACK OF LOGIN ATTEMPTS

/* 
 * Evaluate login attempts, based on whether a user is "trusted" (correct login) or "untrusted"
 * (incorrect login).
 */

function evalAttempt($db_handle, $trusted_user) 
{
    // check if a user with current IP has submitted something earlier
    $q_handle = $db_handle->prepare("select user_ip, attempt from login_attempts where user_ip = ?");
    $ip = ip2long($_SERVER["REMOTE_ADDR"]);

    if (!$ip) {
        return 0;
    }

    $q_handle->bindParam(1, $ip);
    $q_handle->execute();
    $arr = $q_handle->fetch(PDO::FETCH_ASSOC);

    if (empty($arr["user_ip"])) // this IP is unknown, put it into the database
    {
        $q_handle = $db_handle->prepare("insert into login_attempts values(?,?)");
        $q_handle->bindParam(1, $ip);

        if ($trusted_user) // user provided right login data, reset attempts
        {
            $q_handle->bindValue(2, 0, PDO::PARAM_INT);
            $q_handle->execute();
            return 0;
        }

        $q_handle->bindValue(2, 1, PDO::PARAM_INT); // first login attempt
        $q_handle->execute();
    }
    else // we know this IP
    {
        if ($arr["attempt"] == 5 && !$trusted_user) // beyond 5 login attempts: turn Captcha on
            return 1;

        // increment the attempt (or reset attempt for trusted users)
        $q_handle = $db_handle->prepare("update login_attempts set attempt = ? where user_ip = ?");

        if ($trusted_user) // user provided right correct data, reset attempts
        {
            $q_handle->bindValue(1, 0, PDO::PARAM_INT);
            $q_handle->bindParam(2, $ip);
            $q_handle->execute();
            return 0;
        }

        $q_handle->bindValue(1, ++$arr["attempt"], PDO::PARAM_INT);
        $q_handle->bindParam(2, $ip);
        $q_handle->execute();
    }
    return 0; // do not turn Captcha on
}

//// CHECK YOUTUBE LINKS

function checkYoutubeLink($link) // check validity of Youtube link
{
    $vidID = "";
    // Source: http://stackoverflow.com/questions/3392993/php-regex-to-get-youtube-video-id
    parse_str(parse_url($link, PHP_URL_QUERY), $parsed_array);
    if (!empty($parsed_array))
        $vidID = $parsed_array['v'];  
    
    if (!$vidID)
        return array("This is not a valid Youtube link.", $vidID);
    
    // Only uncomment if your webserver allows file_get_contents...
    /*if ($vid_check = file_get_contents("https://gdata.youtube.com/feeds/api/videos/" . $vidID))
        // try to get info about this video ID from the Youtube API
    {
        $youtube_errors = array("Private video", "Invalid id", "Video not found");
        if (in_array($vid_check, $youtube_errors))
            return "This link doesn't link to a valid Youtube video.";
    }*/
    
    if (strlen($vidID) != 11) // Youtube ID has invalid length
        return array("This Youtube video ID is invalid.", $vidID);

    return array("", $vidID);
}

//// VALIDATE ACCOUNT INTEGRITY

function validateIntegrity($db_handle)
{
    if(file_exists("php/playlistmood.php"))
        require_once("php/playlistmood.php"); // use playlistmood.php to obtain points
    else 
        exit('Sorry, the playlist/mood evaluation library could not be found.');
    
    $score = updateScore($db_handle); 
    if ($score < -15) // delete untrusted user
    {
        $qh = $db_handle->prepare("delete from rates where username = ?");
        $qh->bindParam(1, $_SESSION["usr"]);
        $qh->execute();
        $qh = $db_handle->prepare("delete from uploads where username = ?");
        $qh->bindParam(1, $_SESSION["usr"]);
        $qh->execute();
        $qh = $db_handle->prepare("delete from User where username = ?");
        $qh->bindParam(1, $_SESSION["usr"]);
        $qh->execute();
        return 0;
    }
    return 1;
}
?>