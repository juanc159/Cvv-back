<?php

use App\Http\Controllers\CommentController;
use Illuminate\Support\Facades\Route;
 

/*
|--------------------------------------------------------------------------
| comment
|--------------------------------------------------------------------------
*/

Route::get('/comment/paginate', [CommentController::class, 'paginate']);

Route::get('/comment/create', [CommentController::class, 'create']);

Route::post('/comment/store', [CommentController::class, 'store']);

Route::get('/comment/{id}/edit', [CommentController::class, 'edit']);

Route::post('/comment/update/{id}', [CommentController::class, 'update']);

Route::delete('/comment/delete/{id}', [CommentController::class, 'delete']);

Route::post('/comment/changeStatus', [CommentController::class, 'changeStatus']);
