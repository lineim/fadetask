<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;

Route::any('/test', function ($request) {
    return response('test');
});

Route::any('/route-test', 'app\controller\Index@index');

Route::post('/api/auth', 'app\controller\Auth@auth');

// 用户
Route::post('/api/reg', 'app\controller\User@reg');
Route::post('/api/login', 'app\controller\User@login');

// 项目
Route::post('/api/project', 'app\controller\Project@add');
Route::get('/api/projects', 'app\controller\Project@list');
Route::get('/api/project/{uuid}', 'app\controller\Project@get');
Route::post('/api/project/{uuid}/update', 'app\controller\Project@update');
Route::post('/api/project/{uuid}/close', 'app\controller\Project@close');
Route::post('/api/project/{uuid}/open', 'app\controller\Project@open');
Route::post('/api/project/{uuid}/kanban', 'app\controller\ProjectKanban@add');
Route::post('/api/project/{uuid}/kanban/delete', 'app\controller\ProjectKanban@delete');
Route::get('/api/project/{uuid}/kanban', 'app\controller\ProjectKanban@search');
Route::get('/api/project/{uuid}/member', 'app\controller\ProjectMember@search');
Route::get('/api/project/{uuid}/member/invert/link', 'app\controller\ProjectMember@invertLink');
Route::post('/api/project/{uuid}/member/invert', 'app\controller\ProjectMember@joinByToken');
Route::post('/api/project/{uuid}/member/role', 'app\controller\ProjectMember@changeRole');
Route::post('/api/project/{uuid}/member/remove', 'app\controller\ProjectMember@remove');
Route::get('/api/project/{uuid}/stat/overview', 'app\controller\ProjectOverview@get');

// 迭代
Route::post('/api/sprint/create', 'app\controller\Sprint@add');
Route::get('/api/project/{uuid}/sprints', 'app\controller\Sprint@list');

// 看板
Route::get('/api/kanban/board', 'app\controller\Kanban@board');
Route::get('/api/kanban/search', 'app\controller\Kanban@search');
Route::post('/api/kanban/create', 'app\controller\Kanban@add');
Route::post('/api/kanban/{id}/join', 'app\controller\Kanban@join');
Route::get('/api/kanban/{uuid}', 'app\controller\Kanban@detail');
Route::post('/api/kanban/{id}', 'app\controller\Kanban@update');
Route::delete('/api/kanban/{id}', 'app\controller\Kanban@close');
Route::post('/api/kanban/{id}/unclose', 'app\controller\Kanban@cancelClose');
Route::post('/api/kanban/{id}/wip/set', 'app\controller\Kanban@setWip');
Route::post('/api/kanban/{id}/favorite', 'app\controller\Kanban@favorite');
Route::get('/api/kanban/{id}/favorites', 'app\controller\Me@favorites');
Route::post('/api/kanban/{id}/unfavorite', 'app\controller\Kanban@unfavorite');

// 我的看板
Route::get('/api/my/kanban', 'app\controller\Kanban@my');
Route::get('/api/kanban/recently/visit', 'app\controller\Kanban@recentlyView');
Route::get('/api/kanban/closed/all', 'app\controller\Kanban@closed');

// 看板成员
Route::group('/api/kanban/{id}/member', function () {
    Route::get('s', 'app\controller\KanbanMember@list');
    Route::get('/search', 'app\controller\KanbanMember@search');
    Route::post('', 'app\controller\KanbanMember@add');
    Route::delete('/{memberId}', 'app\controller\KanbanMember@remove');
    Route::post('/{memberId}/role/admin', 'app\controller\KanbanMember@setRoleAdmin');
    Route::post('/{memberId}/role/user', 'app\controller\KanbanMember@setRoleUser');
    Route::get('/isAdmin', 'app\controller\KanbanMember@admin');
    Route::post('/invite', 'app\controller\KanbanMember@invite');
    Route::post('/invite/join', 'app\controller\KanbanMember@inviteJoin');
    Route::get('/invite/link', 'app\controller\KanbanMember@joinLink');
});

// 看板列表
Route::post('/api/kanban/{id}/list', 'app\controller\KanbanList@create');
Route::put('/api/kanban/{id}/list/{listId}', 'app\controller\KanbanList@update');

// 看板任务
Route::group('/api/kanban/{kanbanId}/task', function () {
    Route::get('/{id}', 'app\controller\KanbanTask@get');
    Route::get('/{id}/subtasks', 'app\controller\KanbanTask@getSubtasksTree');
    Route::post('', 'app\controller\KanbanTask@add');
    Route::post('/{id}/date', 'app\controller\KanbanTask@setDate');
    Route::delete('/{id}/date', 'app\controller\KanbanTask@clearDueDate');
    Route::post('/{id}/desc', 'app\controller\KanbanTask@setDesc');
    Route::post('/{id}/title', 'app\controller\KanbanTask@setTitle');
    Route::get('/{id}/activity', 'app\controller\KanbanTask@activity');
    Route::post('/{taskId}/move', 'app\controller\KanbanTask@move');
    Route::post('/{taskId}/move/to/other', 'app\controller\KanbanTask@moveToOther');
    Route::post('/{id}/done', 'app\controller\KanbanTask@done');
    Route::post('/{id}/undone', 'app\controller\KanbanTask@undone');
    Route::post('/{id}/priority', 'app\controller\KanbanTask@priority');
    Route::post('/{taskId}/copy', 'app\controller\KanbanTask@copy');
    Route::post('/{taskId}/copy/to/other', 'app\controller\KanbanTask@copyToOther');
});

// 看板Label
Route::group('/api/kanban/{kanbanId}/label', function() {
    Route::get('/{id}', 'app\controller\KanbanLabel@get');
    Route::post('', 'app\controller\KanbanLabel@add');
    Route::post('/sort', 'app\controller\KanbanLabel@sort');
    Route::post('/{id}/edit', 'app\controller\KanbanLabel@edit');
    Route::post('/{id}/delete', 'app\controller\KanbanLabel@delete');
});

// 任务Label
Route::get('/api/label/{labelId}', 'app\controller\TaskLabel@get');
Route::put('/api/label/{labelId}', 'app\controller\TaskLabel@edit');
Route::get('/api/kanban/{kanbanId}/labels', 'app\controller\TaskLabel@list');
Route::post('/api/task/{taskId}/label/{labelId}', 'app\controller\TaskLabel@add');
Route::delete('/api/task/{taskId}/label/{labelId}', 'app\controller\TaskLabel@remove');

// 任务list
Route::post('/api/kanban/{id}/task/list/change', 'app\controller\KanbanTask@changeList');
Route::put('/api/kanban/list/{id}', 'app\controller\KanbanList@update');
Route::put('/api/kanban/{id}/task/list/sort', 'app\controller\KanbanList@changeListSort');

// 任务attachment
Route::get('/api/kanban/{kanbanId}/searchForCopy', 'app\controller\TaskAttachment@searchForCopy');
Route::post('/api/kanban/{taskId}/task/attachment', 'app\controller\TaskAttachment@add');
Route::post('/api/kanban/task/attachment/{id}/copy', 'app\controller\TaskAttachment@copyToTask');
Route::get('/api/kanban/task/attachment', 'app\controller\TaskAttachment@download');
Route::delete('/api/kanban/{taskId}/task/{id}/attachment', 'app\controller\TaskAttachment@delete');
// 对象存储
Route::get('/api/oss/ststoken', 'app\controller\Oss@stsToken');
Route::post('/api/kanban/task/{taskId}/attachment/oss', 'app\controller\TaskAttachment@ossUpload');
Route::post('/api/kanban/task/{taskId}/attachment/oss/finished', 'app\controller\TaskAttachment@ossUploadFinished');
Route::get('/api/kanban/task/{taskId}/attachment/{uuid}/oss/url', 'app\controller\TaskAttachment@ossUrl');

// 归档
Route::post('/api/kanban/{taskId}/task/archive', 'app\controller\KanbanTask@archive');
Route::post('/api/kanban/{taskId}/task/unarchive', 'app\controller\KanbanTask@unarchive');
Route::get('/api/kanban/{kanbanId}/archived/tasks', 'app\controller\KanbanTask@archivedTasks');

Route::post('/api/kanban/{listId}/list/archive', 'app\controller\KanbanList@archive');
Route::post('/api/kanban/{listId}/list/unarchive', 'app\controller\KanbanList@unarchive');
Route::get('/api/kanban/{kanbanId}/archived/list', 'app\controller\KanbanList@archivedList');

Route::post('/api/kanban/{listId}/list/completed', 'app\controller\KanbanList@completed');
Route::post('/api/kanban/{listId}/list/uncompleted', 'app\controller\KanbanList@cancelCompleted');

Route::group('/api/kanban/{taskId}/task/checklist', function () {
    Route::post('', 'app\controller\KanbanTaskCheckList@add');
    Route::put('/{id}/update', 'app\controller\KanbanTaskCheckList@update');
    Route::put('/{id}/done', 'app\controller\KanbanTaskCheckList@done');
    Route::put('/{id}/undone', 'app\controller\KanbanTaskCheckList@undone');
    Route::delete('/{id}', 'app\controller\KanbanTaskCheckList@delete');
    Route::get('/{id}/members', 'app\controller\KanbanTaskCheckList@members');
    Route::post('/{id}/member', 'app\controller\KanbanTaskCheckList@setMember');
    Route::delete('/{id}/member/{memberId}', 'app\controller\KanbanTaskCheckList@rmMember');
    Route::post('/{id}/duedate', 'app\controller\KanbanTaskCheckList@setDuetime');
});

Route::group('/api/kanban/task/{taskId}/member', function () {
    Route::get('s', 'app\controller\TaskMember@list');
    Route::post('', 'app\controller\KanbanTask@setMember');
    Route::post('/delete', 'app\controller\KanbanTask@removeMember');
});

Route::group('/api/me', function () {
    Route::get('', 'app\controller\Me@get');
    Route::post('/update', 'app\controller\Me@update');
    Route::post('/update/password', 'app\controller\Me@updatePass');
    Route::get('/todo', 'app\controller\Me@todo');
    Route::get('/notifications', 'app\controller\Me@notifications');
    Route::get('/notification/unread', 'app\controller\Me@hasNotification');
    Route::post('/notifiction/readed', 'app\controller\Me@notificationReaded');
    Route::get('/project', 'app\controller\Me@project');
});

// 配置
Route::group('/api/setting', function () {
    Route::get('/login', 'app\controller\LoginSetting@get');
    Route::put('/login/{type}', 'app\controller\LoginSetting@put');
    Route::get('/admin/login/{type}', 'app\controller\LoginSetting@adminGet');
});

// 账户
Route::group('/api/account', function () {
    Route::post('/email/available', 'app\controller\Account@emailAvailable');
    Route::post('/resetPass/email', 'app\controller\Account@resetPassEmail');
    Route::post('/resetPass', 'app\controller\Account@resetPass');
    Route::post('/mobile/available', 'app\controller\Account@mobileAvailable');
    Route::post('/send/reg/code', 'app\controller\Account@regVerifyCode');
    Route::post('/send/sms_code', 'app\controller\Auth@sendSmsLogin');
    Route::post('/login/by_sms_code', 'app\controller\Auth@loginByCode');
});

// 评论
Route::group('/api/task/{taskId}/comment', function () {
    Route::get('s', 'app\controller\TaskComment@get');
    Route::post('', 'app\controller\TaskComment@add');
    Route::post('/{id}/edit', 'app\controller\TaskComment@edit');
    Route::post('/{id}/delete', 'app\controller\TaskComment@delete');
});

// 看板自定义字段
Route::group('/api/kanban/{kanbanId}/customfield', function() {
    Route::post('', 'app\controller\CustomField@add');
    Route::post('/edit', 'app\controller\CustomField@edit');
});

// 卡片自定义字段
Route::group('/api/task/{taskId}/field/{id}/val', function() {
    Route::post('', 'app\controller\CustomField@setFieldVal');
});

// 自定义字段
Route::group('/api/customfield', function() {
    Route::get('/{id}', 'app\controller\CustomField@get');
    Route::post('/{id}/edit', 'app\controller\CustomField@edit');
    Route::delete('/{id}', 'app\controller\CustomField@del');
    Route::post('/{fieldId}/addOption', 'app\controller\CustomField@addOption');
    Route::post('/option/{id}', 'app\controller\CustomField@setOption');
    Route::delete('/option/{id}', 'app\controller\CustomField@delOption');
});

// 看板统计
Route::get('/api/kanban/{id}/dashboard', 'app\controller\Kanban@dashborad');

// 管理后台
Route::group('/api/admin', function () {
    Route::get('/users', 'app\controller\admin\User@search');
    Route::post('/user', 'app\controller\admin\User@add');
    Route::get('/user/{uuid}', 'app\controller\admin\User@get');
    Route::post('/user/checkEmail', 'app\controller\admin\User@checkEmail');
    Route::post('/user/checkMobile', 'app\controller\admin\User@checkMobile');
    Route::post('/user/{uuid}/updateVerify', 'app\controller\admin\User@updateVerify');
    Route::post('/user/{uuid}/updatePassword', 'app\controller\admin\User@updatePassword');
    Route::get('/login/logs', 'app\controller\admin\LoginLog@search');
});

Route::group('/api/v2/', function() {
    require_once app_path() . '/route/workspace.php';
});
