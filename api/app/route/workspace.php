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
    Route::group('/{uuid}/space', function () {
        Route::get('',  'app\controller\workspace\WorkspaceProject@list');
        Route::get('/listTree',  'app\controller\workspace\WorkspaceProject@listForTree');
        Route::post('',  'app\controller\workspace\WorkspaceProject@add');
        Route::get('/{spaceUuid}/overview',  'app\controller\workspace\WorkspaceProject@overview');

        // List or kanban
        Route::group('/{spaceUuid}/list', function () {
            Route::get('/{listUuid}',  'app\controller\workspace\SpaceList@get');
            Route::post('',  'app\controller\workspace\SpaceList@add');
        });
    });
})->middleware([
    app\middleware\WorkspaceAuthCheck::class
]);

Route::group('/workspace', function() {
    Route::post('/member/join',  'app\controller\workspace\WorkspaceMember@join');
})->middleware([]);