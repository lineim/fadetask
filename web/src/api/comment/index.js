export default [
    {
      name: "getComments",
      url: "/task/{taskId}/comments",
      method: "GET"
    },
    {
      name: "createComment",
      url: "/task/{taskId}/comment",
      method: "POST"
    },
    {
      name: "editComment",
      url: "/task/{taskId}/comment/{id}/edit",
      method: "POST"
    },
    {
      name: "deleteComment",
      url: "/task/{taskId}/comment/{id}/delete",
      method: "POST"
    },
]