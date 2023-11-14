import auth from "./auth/index";
import loginSetting from "./login-setting/index";
import user from "./user/index";
import questionnaire from "./questionnaire";
import personnelSelector from "./personnel-selector";
import assessment from "./assessment";
import myAssessment from "./my-assessment";
import project from "./project";
import sprint from "./sprint";
import kanban from "./kanban";
import comment from "./comment";
import customfield from "./customfield";
import checkList from "./checklist";
import me from "./me";
import admin from "./admin";

const config = [...auth, ...loginSetting, ...user, 
    ...questionnaire, ...personnelSelector, 
    ...assessment, ...myAssessment, ...project,
    ...sprint, ...kanban, ...me, ...admin, ...comment,
    ...customfield, ...checkList
];

export default config;
