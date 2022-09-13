<?php

use App\Controllers\AuthController;
use App\Controllers\MemberController;

$app->post('/login', AuthController::class . ':login');
$app->post('/register', AuthController::class . ':register');

$app->group('/api', function () use ($app) {
  $app->get('/members', MemberController::class . ':index');
});
