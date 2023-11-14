export default [
    {
      name: "getProject",
      url: "/project/{uuid}",
      method: "GET",
      disableLoading: true
    },
    {
      name: "getProjects",
      url: "/projects",
      method: "GET",
      disableLoading: true
    },
    {
      name: "addProject",
      url: "/project",
      method: "POST",
      disableLoading: true
    },
    {
      name: "updateProject",
      url: "/project/{uuid}/update",
      method: "POST",
      disableLoading: true
    },

    {
      name: "closeProject",
      url: "/project/{uuid}/close",
      method: "POST",
      disableLoading: true
    },
    
    {
      name: "openProject",
      url: "/project/{uuid}/open",
      method: "POST",
      disableLoading: true
    },

    {
      name: "projectKanban",
      url: "/project/{uuid}/kanban",
      method: "GET",
      disableLoading: true
    },
    {
      name: "addProjectKanban",
      url: "/project/{uuid}/kanban",
      method: "POST",
      disableLoading: true
    },
    {
      name: 'rmProjectKanban',
      url: "/project/{uuid}/kanban/delete",
      method: "POST",
      disableLoading: true
    },
    {
      name: "projectMember",
      url: "/project/{uuid}/member",
      method: "get",
      disableLoading: true
    },
    {
      name: 'projectInvertLink',
      url: '/project/{uuid}/member/invert/link',
      method: "get",
      disableLoading: true
    },
    {
      name: 'projectInvert',
      url: '/project/{uuid}/member/invert',
      method: "POST",
      disableLoading: true
    },
    {
      name: "projectMemberRole",
      url: "/project/{uuid}/member/role",
      method: "POST",
      disableLoading: true
    },
    {
      name: "projectMemberRemove",
      url: "/project/{uuid}/member/remove",
      method: "POST",
      disableLoading: true
    },
    {
      name: 'projectOverview',
      url: '/project/{uuid}/stat/overview',
      method: "GET",
      disableLoading: true
    }

];