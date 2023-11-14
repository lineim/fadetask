export default [
  {
    name: "myCreateAssessments",
    url: "/my/assessments",
    method: "POST"
  },
  {
    name: "mySingleQueryAssessment",
    url: "/my/assessment/{assessmentId}",
    method: "GET"
  },
  
  {
    name: "myQueryAssessments",
    url: "/my/assessments",
    method: "GET"
  },
  {
    name: "myDownloadReport",
    url: "/my/assessment/{assessmentId}/report/download",
    method: "GET"
  },
  {
    name: "mySendReport",
    url: "/my/assessment/{assessmentId}/report/send",
    method: "POST"
  },
  {
    name: "myDeleteAssessments",
    url: "/my/assessment/{assessmentId}",
    method: "DELETE"
  },
  {
    name: "myGetAssessmentsUsers",
    url: "/my/assessment/{assessmentId}/users",
    method: "GET"
  },
  {
    name: "myRemindAllAssessments",
    url: "/my/assessment/{assessmentId}/users/notification",
    method: "POST"
  },
  {
    name: "myRemindFillFeedbackers",
    url: "/my/assessment/{assessmentId}/fill/feedbackers/notification",
    method: "POST"
  },
  {
    name: "mySingleAssessments",
    url: "/my/assessment/{assessmentId}/user/{userId}/notification",
    method: "POST"
  },
  {
    name: "mySetAssessmentUsers",
    url: "/my/assessment/{assessmentId}/users",
    method: "POST"
  },
  {
    name: "myFillAssessmentUser",
    url: "/my/assessment/{assessmentId}/users/{uuid}",
    method: "POST"
  },
  {
    name: "myGetAssessmentsFeedback",
    url: "/my/assessment/{assessmentId}/feedback/result",
    method: "GET"
  },
  {
    name: "mySetAssessmentsFeedback",
    url: "/my/assessment/{assessmentId}/feedback/result",
    method: "POST"
  },
  {
    name: "mySingleAssessmentFeedBack",
    url: "/my/assessment/{assessmentId}/user/{userId}/feedback/result",
    method: "GET"
  },
  {
    name: "myConfirmAssessmentResult",
    url: "/my/assessment/{assessmentId}/confirm/result",
    method: "POST"
  },
  {
    name: "myAgainAssessmentFeedback",
    url: "/my/assessment/{assessmentId}/user/{userId}/reset",
    method: "POST"
  },
  {
    name: "myDeleteAssessmentFeedback",
    url: "/my/assessment/{assessmentId}/user/{userId}/delete",
    method: "POST"
  }
];
