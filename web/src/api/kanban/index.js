export default [
    {
      name: "kanbanBoard",
      url: "/kanban/board",
      method: "GET"
    },
    {
      name: "kanbanClosed",
      url: "/kanban/closed/all",
      method: "GET"
    },
    {
      name: "kanbanCreate",
      url: "/kanban/create",
      method: "POST"
    },
    {
      name: "kanbanClose",
      url: "/kanban/{id}",
      method: "DELETE"
    },
    {
      name: "kanbanUnclose",
      url: "/kanban/{id}/unclose",
      method: "POST"
    },
    {
      name: "kanbanFavorites",
      url: "/kanban/{id}/favorites",
      method: "GET",
      disableLoading: true
    },
    {
      name: "kanbanFavorite",
      url: "/kanban/{id}/favorite",
      method: "POST",
      disableLoading: true
    },
    {
      name: "kanbanUnFavorite",
      url: "/kanban/{id}/unfavorite",
      method: "POST",
      disableLoading: true
    },
    {
      name: "kanbanDetail",
      url: "/kanban/{id}",
      method: "GET",
      disableLoading: true
    },
    {
      name: "kanbanUpdate",
      url: "/kanban/{id}",
      method: "POST",
      disableLoading: true
    },
    {
      name: "listCreate",
      url: "/kanban/{id}/list",
      method: "POST",
      disableLoading: true
    },
    {
      name: "listUpdate",
      url: "/kanban/{id}/list/{listId}",
      method: "PUT",
      disableLoading: true
    },
    {
      name: "listAsComplete",
      url: "/kanban/{id}/list/completed",
      method: "POST",
      disableLoading: true
    },
    {
      name: "listAsUnComplete",
      url: "/kanban/{id}/list/uncompleted",
      method: "POST",
      disableLoading: true
    },
    {
      name: "wipSet",
      url: "/kanban/{id}/wip/set",
      method: "POST",
      disableLoading: true
    },
    {
      name: "cardCreate",
      url: "/kanban/{boardId}/task",
      method: "POST",
      disableLoading: true
    },
    {
      name: "card",
      url: "/kanban/{boardId}/task/{taskId}",
      method: "GET",
      disableLoading: true
    },
    {
      name: "cardSubtasks",
      url: "/kanban/{boardId}/task/{taskId}/subtasks",
      method: "GET",
      disableLoading: true
    },
    {
      name: "cardActivity",
      url: "/kanban/{boardId}/task/{taskId}/activity",
      method: "GET",
      disableLoading: true
    },
    {
      name: "saveDesc",
      url: "/kanban/{boardId}/task/{id}/desc",
      method: "POST",
      disableLoading: true
    },
    {
      name: "saveTitle",
      url: "/kanban/{boardId}/task/{id}/title",
      method: "POST",
      disableLoading: true
    },
    {
      name: "addLabel",
      url: "/task/{taskId}/label/{labelId}",
      method: "POST",
      disableLoading: true
    },
    {
      name: "sortLabel",
      url: "/kanban/{kanbanId}/label/sort",
      method: "POST",
      disableLoading: true
    },
    {
      name: "taskDone",
      url: "/kanban/{boardId}/task/{id}/done",
      method: "POST",
      disableLoading: true
    },
    {
      name: "taskUndone",
      url: "/kanban/{boardId}/task/{id}/undone",
      method: "POST",
      disableLoading: true
    },
    {
      name: "taskPriority",
      url: "/kanban/{boardId}/task/{id}/priority",
      method: "POST",
      disableLoading: true
    },
    {
      name: "rmLabel",
      url: "/task/{taskId}/label/{labelId}",
      method: "DELETE",
      disableLoading: true
    },
    {
      name: "listChange",
      url: "/kanban/{id}/task/list/change",
      method: "POST",
      disableLoading: true
    },
    {
      name: "listSort",
      url: "/kanban/{id}/task/list/sort",
      method: "PUT",
      disableLoading: true
    },
    {
      name: "setDate",
      url: "/kanban/{boardId}/task/{id}/date",
      method: "POST",
      disableLoading: true
    },
    {
      name: "clearDueDate",
      url: "/kanban/{boardId}/task/{id}/date",
      method: "DELETE",
      disableLoading: true
    },
    {
      name: "taskAttahcmentInit",
      url: '/kanban/task/{taskId}/attachment/oss',
      method: "POST",
      disableLoading: true
    },
    {
      name: "taskAttahcmentFinished",
      url: '/kanban/task/{taskId}/attachment/oss/finished',
      method: "POST",
      disableLoading: true
    },
    {
      name: "taskAttahcmentUrl",
      url: '/kanban/task/{taskId}/attachment/{uuid}/oss/url',
      method: "GET",
      disableLoading: true
    },
    {
      name: "taskAttachmentSearchForCopy",
      url: "/kanban/{boardId}/searchForCopy",
      method: "GET",
      disableLoading: true
    },
    {
      name: "taskAttachmentCopy",
      url: "/kanban/task/attachment/{id}/copy",
      method: "POST",
      disableLoading: true
    },
    {
      name: "delAttachment",
      url: "/kanban/{taskId}/task/{id}/attachment",
      method: "DELETE",
      disableLoading: true
    },
    {
      name: "cardMove",
      url: "/kanban/{id}/task/{taskId}/move",
      method: "POST",
      disableLoading: true
    },
    {
      name: "isKanbanAdmin",
      url: "/kanban/{id}/member/isAdmin",
      method: "GET",
      disableLoading: true
    },
    {
      name: "kanbanMemberInvite",
      url: "/kanban/{id}/member/invite",
      method: "POST",
      disableLoading: true
    },
    {
      name: "kanbanMemberInviteLink",
      url: "/kanban/{id}/member/invite/link",
      method: "GET",
      disableLoading: true
    },
    {
      name: "kanbanMemberInviteJoin",
      url: "/kanban/{id}/member/invite/join",
      method: "POST",
      disableLoading: true
    },
    {
      name: "kanbanMembers",
      url: "/kanban/{id}/members",
      method: "GET",
      disableLoading: true
    },
    {
      name: "kanbanMemberSearch",
      url: "/kanban/{id}/member/search",
      method: "GET",
      disableLoading: true
    },
    {
      name: "kanbanNewMember",
      url: "/kanban/{id}/member",
      method: "POST",
      disableLoading: true
    },
    {
      name: "kanbanMemberRemove",
      url: "/kanban/{id}/member/{memberId}",
      method: "DELETE",
      disableLoading: true
    },
    {
      name: "kanbanMemberSetAdmin",
      url: "/kanban/{id}/member/{memberId}/role/admin",
      method: "POST",
      disableLoading: true
    },
    {
      name: "kanbanMemberSetUser",
      url: "/kanban/{id}/member/{memberId}/role/user",
      method: "POST",
      disableLoading: true
    },
    {
      name: "cardMembers",
      url: "/kanban/task/{cardId}/members",
      method: "GET",
      disableLoading: true
    },
    {
      name: "cardMemberAdd",
      url: "/kanban/task/{cardId}/member",
      method: "POST",
      disableLoading: true
    },
    {
      name: "cardMemberRm",
      url: "/kanban/task/{cardId}/member/delete",
      method: "POST",
      disableLoading: true
    },
    {
      name: 'archivedTasks',
      url: '/kanban/{id}/archived/tasks',
      method: "GET",
      disableLoading: true
    },
    {
      name: "archive",
      url: "/kanban/{cardId}/task/archive",
      method: "POST",
      disableLoading: true
    },
    {
      name: "unarchive",
      url: "/kanban/{cardId}/task/unarchive",
      method: "POST",
      disableLoading: true
    },
    {
      name: 'archivedList',
      url: '/kanban/{id}/archived/list',
      method: "GET",
      disableLoading: true
    },
    {
      name: "archiveList",
      url: "/kanban/{listId}/list/archive",
      method: "POST"
    },
    {
      name: "unarchiveList",
      url: "/kanban/{listId}/list/unarchive",
      method: "POST"
    },
    {
      name: "myKanbans",
      url: "/my/kanban",
      method: "GET"
    },
    {
      name: "moveToBoard",
      url: '/kanban/{boardId}/task/{cardId}/move/to/other',
      method: 'POST'
    },
    {
      name: "cardCopy",
      url: '/kanban/{boardId}/task/{cardId}/copy',
      method: 'POST',
    },
    {
      name: "cardCopyToOther",
      url: '/kanban/{boardId}/task/{cardId}/copy/to/other',
      method: 'POST',
    },
    {
      name: "recentlyVisit",
      url: '/kanban/recently/visit',
      method: 'GET',
      disableLoading: true
    },
    {
      name: "search",
      url: '/kanban/search',
      method: 'GET'
    }, 
    {
      name: "kanbanLabelAdd",
      url: "/kanban/{kanbanId}/label",
      method: "POST",
      disableLoading: true
    },
    {
      name: "kanbanLabelEdit",
      url: "/kanban/{kanbanId}/label/{id}/edit",
      method: "POST",
      disableLoading: true
    },
    {
      name: "kanbanLabelDel",
      url: "/kanban/{kanbanId}/label/{id}/delete",
      method: "POST",
      disableLoading: true
    },
    {
      name: "kanbanDashboard",
      url: "/kanban/{kanbanId}/dashboard",
      method: "GET",
      disableLoading: true
    },

]