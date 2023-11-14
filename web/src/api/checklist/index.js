export default [
    {
        name: "addCheckList",
        url: "/kanban/{taskId}/task/checklist",
        method: "POST",
        disableLoading: true
    },
    {
        name: "updateCheckList",
        url: "/kanban/{taskId}/task/checklist/{id}/update",
        method: 'PUT',
        disableLoading: true
    },
    {
        name: "checkListMembers",
        url: "/kanban/{taskId}/task/checklist/{id}/members",
        method: "GET",
        disableLoading: true
    },
    {
        name: "doneCheckList",
        url: "/kanban/{taskId}/task/checklist/{id}/done",
        method: "PUT",
        disableLoading: true
    },
    {
        name: "addCheckListMember",
        url: "/kanban/{taskId}/task/checklist/{id}/member",
        method: "POST",
        disableLoading: true
    },
    {
        name: "delCheckListMember",
        url: "/kanban/{taskId}/task/checklist/{id}/member/{memberId}",
        method: "DELETE",
        disableLoading: true
    },
    {
        name: "undoneCheckList",
        url: "/kanban/{taskId}/task/checklist/{id}/undone",
        method: "PUT",
        disableLoading: true
    },
    {
        name: "delCheckList",
        url: "/kanban/{taskId}/task/checklist/{id}",
        method: "DELETE",
        disableLoading: true
    },
    {
        name: "checkListDuedate",
        url: "/kanban/{taskId}/task/checklist/{id}/duedate",
        method: "POST",
        disableLoading: true
    },
]