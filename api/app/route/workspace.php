<?php
use Webman\Route;

Route::group('/workspace', function() {
    // api/v2/workspace/
    Route::get('/{uuid}',  'app\controller\workspace\Workspace@get');
})->middleware([
    app\middleware\WorkspaceAuthCheck::class
]);