export default [
  {
    // 学校搜索
    name: "getSchools",
    url: "/schools",
    method: "GET"
  },
  {
    name: "getSchoolSearchData",
    url: "/schools/all/datas",
    method: "GET"
  },
  {
    name: "getSchool",
    url: "/schools/{id}",
    method: "GET"
  },
  {
    name: "favoriteSchool",
    url: "/schools/{id}/favorites",
    method: "POST"
  },
  {
    name: "unFavoriteSchool",
    url: "/schools/{id}/favorites",
    method: "DELETE"
  },
  {
    name: "getSchoolArticles",
    url: "/schools/{id}/articles",
    method: "GET"
  },
  {
    name: "getSchoolCourses",
    url: "/schools/{id}/courses",
    method: "GET"
  },
  {
    name: "getSchoolClassroom",
    url: "/schools/{schoolId}/classrooms",
    method: "GET"
  }
];
