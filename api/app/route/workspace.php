<?php
use Webman\Route;

Route::group('/workspace', function() {
    // api/v2/workspace/
    Route::get('/{uuid}',  'app\controller\workspace\Workspace@get');
    Route::put('/{uuid}',  'app\controller\workspace\Workspace@put');
    Route::group('/{uuid}/member', function () {
        Route::get('',  'app\controller\workspace\WorkspaceMember@list');
        Route::post('/invite',  'app\controller\workspace\WorkspaceMember@invite');
        Route::delete('/{memberId}',  'app\controller\workspace\WorkspaceMember@delete');
        Route::put('/{memberId}/role',  'app\controller\workspace\WorkspaceMember@changeRole');
    });
    Route::group('/{uuid}/task_type', function () {
        Route::get('',  'app\controller\workspace\WorkspaceTaskType@list');
        Route::post('',  'app\controller\workspace\WorkspaceTaskType@add');
    });
    Route::group('/{uuid}/project', function () {
        Route::get('',  'app\controller\workspace\WorkspaceProject@list');
        // Route::post('',  'app\controller\workspace\WorkspaceProject@add');
    });
})->middleware([
    app\middleware\WorkspaceAuthCheck::class
]);

Route::group('/workspace', function() {
    Route::post('/member/join',  'app\controller\workspace\WorkspaceMember@join');
})->middleware([]);