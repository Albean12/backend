<?php

use Illuminate\Support\Facades\Route;
use App\Events\MyEvent;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/trigger-event', function () {
    event(new MyEvent('Hello, this is a test message!'));
    return 'Event has been triggered!';
});
