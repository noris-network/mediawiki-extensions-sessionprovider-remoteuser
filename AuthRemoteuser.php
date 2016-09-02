<?php

$wgAuthRemoteuserName = isset( $_SERVER["AUTHENTICATE_CN"] )
    ? $_SERVER["AUTHENTICATE_CN"]
    : '';

/* User's Mail */
$wgAuthRemoteuserMail = isset( $_SERVER["AUTHENTICATE_MAIL"] )
    ? $_SERVER["AUTHENTICATE_MAIL"]
    : '';

