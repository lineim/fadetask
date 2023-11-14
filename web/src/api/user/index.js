export default [
  {
    name: "emailAvailable",
    url: "/account/email/available",
    method: "POST",
    disableLoading: true
  },
  {
    name: "resetPassEmail",
    url: "/account/resetPass/email",
    method: "POST",
    disableLoading: true
  },
  {
    name: "restPass",
    url: "/account/resetPass",
    method: "POST",
    disableLoading: true
  },
  {
    name: "mobileAvailable",
    url: "/account/mobile/available",
    method: "POST",
    disableLoading: true
  },
  {
    name: "sendRegVerifyCode",
    url: "/account/send/reg/code",
    method: "POST",
    disableLoading: true
  },
  {
    name: "reg",
    url: "/reg",
    method: "POST",
    disableLoading: false
  },
  {
    name: "login",
    url: "/login",
    method: "POST",
    disableLoading: false
  },
  {
    name: "sendLoginSmsCode",
    url: "/account/send/sms_code",
    method: "POST",
    disableLoading: true,
  },
  {
    name: "loginBySmsCode",
    url: "/account/login/by_sms_code",
    method: "POST",
    disableLoading: true,
  }
];
