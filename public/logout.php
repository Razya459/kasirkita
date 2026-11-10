<?php
/**
 * Logout - RestoKita POS Kasir
 */

use RestoKita\Auth;
use RestoKita\Helper;

require_once __DIR__ . '/../vendor/autoload.php';

Auth::logout();
Helper::setFlash('info', 'Anda telah berhasil logout.');
Helper::redirect('login.php');
