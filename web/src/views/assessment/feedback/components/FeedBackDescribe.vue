<template>
  <div class="feed-describe-wrapper">
    <a-row
      type="flex"
      justify="center"
      align="top"
      class="feed-describe-row"
    >
      <a-col :span="23">
        <div class="feed-describe-title">
          {{ user.truename }}的360度绩效评估
        </div>
        
        <div class="feed-describe-item">
          360度绩效评估致力于帮助{{ user.truename }}突破自我。通过该评估，{{ user.truename }}能了解自己的优势，知道自己的不足，同时能得到相应的改善建议。
        </div>
        <div class="feed-describe-item">
          评估需要10~15分钟填写。强烈建议花5分钟在其优势和劣势的描述和建议上。为了帮助其更好的认识自己，请尽量客观详尽地做事实描述
        </div>
        <div class="feed-describe-item">
          感谢。
        </div>
      </a-col>
    </a-row>
  </div>
</template>

<script>
export default {
  components:{},
  props:{
    user: {
      type: Object,
      default: function() {
        return {};
      },
    }
  },
  data(){
    return {
    }
  },
  created(){},
  mounted(){},
  computed:{},
  methods:{},
  watch:{},
}
</script>
<style scoped>
body {
  background-color: #f5f5f5;
}

.feed-describe-wrapper {
  margin: 0 auto;
  width: 66.66666667%;
  border: 4px solid #ddd;
}

.feed-describe-row {
  padding: 30px 0;
}

.feed-describe-title {
  padding-bottom: 30px;
  font-size: 30px;
  line-height: 1;
  color: #333;
}

.feed-describe-item {
  padding-bottom: 20px;
  font-size: 18px;
  line-height: 1;
  color: #313131;
}
</style>