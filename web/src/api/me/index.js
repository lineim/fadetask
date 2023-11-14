export default [
  {
    name: 'me',
    url: '/me',
    method: "GET",
    disableLoading: true
  },
  {
    name: 'meUpdate',
    url: '/me/update',
    method: "POST",
    disableLoading: true
  },
  {
    name: 'meUpdatePass',
    url: '/me/update/password',
    method: "POST",
    disableLoading: true
  },
  {
    name: "meTodo",
    url: "/me/todo",
    method: "GET"
  },
  {
    name: "meNotifications",
    url: "/me/notifications",
    method: "GET",
    disableLoading: true
  },
  {
    name: "meHasNotification",
    url: "/me/notification/unread",
    method: "GET",
    disableLoading: true
  },
  {
    name: 'readNotification',
    url: "/me/notifiction/readed",
    method: "POST",
    disableLoading: true
  },
  {
    name: 'meProject',
    url: '/me/project',
    method: "GET",
    disableLoading: true
  }
    
];