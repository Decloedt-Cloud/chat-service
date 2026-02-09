<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \Illuminate\Support\Facades\DB::table('users')->get(['id', 'wap_user_id', 'name', 'email', 'avatar', 'gender']);
echo json_encode($users, JSON_PRETTY_PRINT);
