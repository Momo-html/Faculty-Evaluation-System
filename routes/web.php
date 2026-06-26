<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/user/login');

require __DIR__.'/web/auth.php';
require __DIR__.'/web/admin.php';
require __DIR__.'/web/superadmin.php';
require __DIR__.'/web/user.php';
