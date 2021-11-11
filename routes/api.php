<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('register', [AuthApiController::class,'registeruser'])->name('register');
Route::post('confirm',[AuthApiController::class,'confirmregisterUser']);
Route::post('login',[AuthApiController::class,'login']);
Route::group(['middleware' => 'auth:api'], function(){
  Route::post('invite',[AuthApiController::class,'inviteUser'])->middleware('admin');
  Route::post('profile',[AuthApiController::class,'updateProfile']);
  Route::get('logout',[AuthApiController::class,'logout']);
});
