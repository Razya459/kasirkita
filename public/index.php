<?php
/**
 * Main Index Redirection
 */

use RestoKita\Auth;
use RestoKita\Helper;

require_once __DIR__ . '/../vendor/autoload.php';

Helper::startSession();

if (Auth::check()) {
    Helper::redirect('dashboard.php');
} else {
    Helper::redirect('login.php');
}
