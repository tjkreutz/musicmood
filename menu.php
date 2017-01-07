<ul class="menu">
    <li> <a href="playlists.php">PLAYLISTS</a></li>
    <li> <a href="upload.php">UPLOAD</a></li>
    <li> <a href="registration.php">REGISTRATION</a></li>
    <li> <a 
    <?php
    if (isset($_SESSION["admin"]) && $_SESSION["admin"])
        echo 'href="controlpanel.php">CONTROL PANEL';
    else
        echo 'href="mymusicmood.php">MY MUSICMOOD';
    ?>    
    </a></li>
  
    <li> <form action=login.php method=post>
    <?php 
    if (isset($_SESSION["usr"]))
        echo '<input type="submit" value="LOGOUT" name="logout" class="text_button">';
    else
        echo '<input type="submit" value="LOGIN" name="login" class="text_button">';
    ?>
    </form>
    </li> 
    <br/>
    <a href="index.php"><img src="img/logo.png" onmouseover="this.src='img/logoinvert.png'" onmouseout="this.src='img/logo.png'" /></a>
    <hr/>
</ul>