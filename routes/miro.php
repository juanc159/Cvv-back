<?php

use App\Http\Controllers\MiroController; 
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Miro
|--------------------------------------------------------------------------
*/
 
Route::get('/miro/details', [MiroController::class, 'details']);

Route::get('/miro/project_boards', [MiroController::class, 'project_boards']);

Route::get('/miro/addJoinees', [MiroController::class, 'addJoinees']);

Route::post('/miro/createOrUpdateMiniTextEditor', [MiroController::class, 'createOrUpdateMiniTextEditor']); 

Route::post('/miro/createOrUpdateStickyNote', [MiroController::class, 'createOrUpdateStickyNote']); 

Route::post('/miro/createOrUpdateTextCaption', [MiroController::class, 'createOrUpdateTextCaption']); 

Route::post('/miro/createOrUpdateDrawing', [MiroController::class, 'createOrUpdateDrawing']); 
