export default [
    {
        name: "AdminUsers",
        url: "/admin/users",
        method: "GET",
        disableLoading: false
    },

    {
        name: "AdminNewUser",
        url: "/admin/user",
        method: "POST",
        disableLoading: false
    },

    {
        name: "AdminUser",
        url: "/admin/user/{uuid}",
        method: "GET",
        disableLoading: false
    },

    {
        name: "AdminUserCheckEmail",
        url: "/admin//user/checkEmail",
        method: "POST",
        disableLoading: false
    },

    {
        name: "AdminUserCheckMobile",
        url: "/admin/user/checkMobile",
        method: "POST",
        disableLoading: false
    },
    {
        name: "AdminUserUpdateVerify",
        url: "/admin/user/{uuid}/updateVerify",
        method: "POST",
        disableLoading: false
    },
    {
        name: "AdminUserUpdatePassword",
        url: "/admin/user/{uuid}/updatePassword",
        method: "POST",
        disableLoading: false
    },
    {
        name: "AdminLoginLogs",
        url: "/admin/login/logs",
        method: "GET",
        disableLoading: false
    }
    
];
  