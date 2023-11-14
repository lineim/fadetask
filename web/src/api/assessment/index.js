export default [
  {
    name: "createAssessments",
    url: "/assessments",
    method: "POST"
  },
  {
    name: "singleQueryAssessment",
    url: "/assessment/{assessmentId}",
    method: "GET"
  },
  
  {
    name: "queryAssessments",
    url: "/assessments",
    method: "GET"
  },
  {
    name: "downloadReport",
    url: "/assessment/{assessmentId}/report/download",
    method: "GET"
  },
  {
    name: "sendReport",
    url: "/assessment/{assessmentId}/report/send",
    method: "POST"
  },
  {
    name: "deleteAssessments",
    url: "/assessment/{assessmentId}",
    method: "DELETE"
  },
  {
    name: "getAssessmentsUsers",
    url: "/assessment/{assessmentId}/users",
    method: "GET"
  },
  {
    name: "getFillableAssessmentsUsers",
    url: "/assessment/{assessmentId}/users/{uuid}",
    method: "GET"
  },
  {
    name: "remindAllAssessments",
    url: "/assessment/{assessmentId}/users/notification",
    method: "POST"
  },
  {
    name: "remindFillFeedbackers",
    url: "/assessment/{assessmentId}/fill/feedbackers/notification",
    method: "POST"
  },
  {
    name: "singleAssessments",
    url: "/assessment/{assessmentId}/user/{userId}/notification",
    method: "POST"
  },
  {
    name: "setAssessmentUsers",
    url: "/assessment/{assessmentId}/users",
    method: "POST"
  },
  {
    name: "fillAssessmentUser",
    url: "/assessment/{assessmentId}/users/{uuid}",
    method: "POST"
  },
  {
    name: "getAssessmentsFeedback",
    url: "/assessment/{assessmentId}/feedback/result",
    method: "GET"
  },
  {
    name: "getFillableAssessmentsFeedback",
    url: "/assessment/{assessmentId}/users/{uuid}/feedback/result",
    method: "GET"
  },
  {
    name: "setFillableAssessmentsFeedback",
    url: "/assessment/{assessmentId}/users/{uuid}/feedback/result",
    method: "POST"
  },
  {
    name: "setAssessmentsFeedback",
    url: "/assessment/{assessmentId}/feedback/result",
    method: "POST"
  },
  {
    name: "singleAssessmentFeedBack",
    url: "/assessment/{assessmentId}/user/{userId}/feedback/result",
    method: "GET"
  },
  {
    name: "confirmAssessmentResult",
    url: "/assessment/{assessmentId}/confirm/result",
    method: "POST"
  },
  {
    name: "againAssessmentFeedback",
    url: "/assessment/{assessmentId}/user/{userId}/reset",
    method: "POST"
  },
  {
    name: "deleteAssessmentFeedback",
    url: "/assessment/{assessmentId}/user/{userId}/delete",
    method: "POST"
  }
];
