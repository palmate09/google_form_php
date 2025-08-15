<?php
    date_default_timezone_set('Asia/Kolkata');

    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    echo $expires; 

?>