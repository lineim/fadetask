<?php
use Webman\Route;

Route::group('/workspace', function() {
    // api/v2/workspace/
    Route::get('/{uuid}',  'app\controller\workspace\Workspace@get');
    Route::put('/{uuid}',  'app\controller\workspace\Workspace@put');
    Route::group('/{uuid}/member', function () {
        Route::get('',  'app\controller\workspace\WorkspaceMember@list');
    });
    Route::group('/{uuid}/task_type', function () {
        Route::get('',  'app\controller\workspace\WorkspaceTaskType@list');
        Route::post('',  'app\controller\workspace\WorkspaceTaskType@add');
    });
})->middleware([
    app\middleware\WorkspaceAuthCheck::class
]);