import Api from "@/api";
import { getRequestName } from "@/utils";

const SUCCESS_CODE = 200;

export default {
  data() {
    return {
      resultModalText: "你确定要把评估报告发送给被评估人本人吗？",
      resultVisible: false,
      resultConfirmLoading: false,
      sendRecord: {}
    };
  },
  created() {
  },
  computed: {
    isMyRoute() {
      return this.$route.meta && this.$route.meta.isMyRoute;
    },
  },
  methods: {
    handleremind(record) {
      const map = {
        ["PRE_READY"]: `即将给${record.userTrueName}发送填写反馈者的邮件`,
        ["ASSING"]: `即将给${record.userTrueName}的反馈者发送填写反馈的邮件`,
      };

      this.$confirm({
        title: map[record.status] || "",
        onOk: () => {
          return this.handleremindItem(record);
        },
        onCancel() {
          console.log("Cancel");
        },
        cancelText: "取消",
        okText: "确认"
      });

    },
    handleremindItem(record) {
      let remindApi = getRequestName("remindAllAssessments", this.isMyRoute);

      if ("PRE_READY" == record.status) {
        remindApi = getRequestName("remindFillFeedbackers", this.isMyRoute);
      }
      
     return Api[remindApi]({
        query: {
          assessmentId: record.id
        }
      }).then(res => {
        if (res.code == SUCCESS_CODE) {
          this.$message.info("提醒成功");
        }
      });
    },
    handledelete(record, index) {
      this.$confirm({
        title: "是否删除这条评估?",
        onOk: () => {
          return this.deleteAssessmentsItem(record, index); 
        },
        onCancel() {
          console.log("Cancel");
        },
        cancelText: "取消",
        okText: "确认"
      });
    },
    deleteAssessmentsItem(record, index) {
      const requestName = getRequestName("deleteAssessments", this.isMyRoute);

      return Api[requestName]({
        query: {
          assessmentId: record.id
        }
      }).then(res => {
        console.log(res);
        this.tableData.splice(index, 1);
        if(this.tableData.length == 0 && this.pagination.current !=1 ) {
          this.pagination.current = this.pagination.current - 1;
        }

        this.handleTableChange(this.pagination);
      });
    },
    handletoview(record) {
      const name = getRequestName("AssessmentDetail", this.isMyRoute);
      
      this.$router.push({
        name,
        params: { assessmentId: record.id }
      });
    },
    handlepreviewreport(record, index) {
      console.log(record, index);
    },
    handleconfirmresult(record, index) {
      console.log(record, index);
      const requestName = getRequestName("confirmAssessmentResult", this.isMyRoute);

      this.$confirm({
        title: "是否对 \"" + record.userTrueName + "\" 的 \"" + record.assessmentName + "\" 评估确认结果 ？",
        onOk: () => {
          Api[requestName]({
            query: {
              assessmentId: record.id
            }
          }).then(res => {
            console.log(res);
            location.reload();
          });
        },
        onCancel() {
          console.log("Cancel");
        },
        cancelText: "取消",
        okText: "确认"
      });
    },
    handlemodify(record) {
      this.toEditorPage(record)
    },
    handlepublish(record) {
      this.toEditorPage(record)
    },
    handledownreport(record, index) {
      console.log(record, index);
      const requestName = getRequestName("downloadReport", this.isMyRoute);
      Api[requestName]({
        query: {
          assessmentId: record.id,
        },
        responseType: "arraybuffer",
      }).then(res => {
        let blob = new Blob([res], { type: "application/pdf" })
        let link = document.createElement("a")
        link.href = window.URL.createObjectURL(blob)
        link.download = "report.pdf"
        link.click()
      }).catch(() => {
        this.$message.warning("下载失败!");
      });
    },
    handleyourself(record) {
      this.resultVisible = true;
      this.sendRecord = record;
    },
    toEditorPage(record) {
      const name = this.$route.name == "AssessmentManage" ? "AssessmentEditor" : "myAssessmentEditor";

      this.$router.push({
        name,
        params: {
          assessmentId: record.id,
        }
      });
    },
    handleResultOk() {
      const requestName = getRequestName("sendReport", this.isMyRoute);

      this.resultConfirmLoading = true;
      Api[requestName]({
        query: {
          assessmentId: this.sendRecord.id
        }
      })
        .then(() => {
          this.$message.success("发送成功！");
          this.resultVisible = false;
        })
        .catch(() => {
          this.$message.warning("发送失败！");
        })
        .finally(() => {
          this.resultConfirmLoading = false;
        });
    },
  },
};