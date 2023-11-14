<template>
  <div v-if="stanDards.length">
    <div
      class="feed-planning-wrapper"
      v-for="(item) in stanDards"
      :key="item.sectionId"
    >
      <a-row
        type="flex"
        justify="center"
        align="top"
      >
        <a-col :span="24">
          <div class="feed-planning-title">
            {{ item.sectionName }}
          </div>
          <p
            v-for="(list) in item.questions"
            :key="list.id"
          >
            <a-form-item 
              :label="list.question" 
              :label-col="{ span: 6, class: 'planing-label' }" 
              :wrapper-col="{ span: 12 }"
            >
              <div v-if="list.feedType === 'STANDARD'">
                <a-radio-group  
                  button-style="solid"
                  v-decorator="[
                    `${JSON.stringify({'questionId': list.id, 'sectionId': item.sectionId})}`,
                    { 
                      rules: [{ required: list.required, message: '请选择' }],
                      initialValue: list.result || '',
                    },
                  ]" 
                >
                  <a-radio-button value="1">
                    1
                  </a-radio-button>
                  <a-radio-button value="2">
                    2
                  </a-radio-button>
                  <a-radio-button value="3">
                    3
                  </a-radio-button>
                  <a-radio-button value="4">
                    4
                  </a-radio-button>
                  <a-radio-button value="5">
                    5
                  </a-radio-button>
                  <a-radio-button value="0">
                    n/a
                  </a-radio-button>
                </a-radio-group>
              </div>   

              <div v-else-if="list.feedType === 'COMMENT'">
                <a-textarea
                  placeholder=""
                  :autosize="{ minRows: 6, maxRows: 6 }"
                  v-decorator="[
                    `${JSON.stringify({'questionId': list.id, 'sectionId': item.sectionId})}`,
                    { 
                      rules: [{ required: list.required, message: '请输入' }],
                      initialValue: list.result || ''
                    },
                  ]"
                />
              </div>
            </a-form-item>
          </p>          
        </a-col>
      </a-row>
    </div>
  </div>
</template>

<script>
export default {
  components:{},
  props:{
    stanDards: {
      type: Array,
      default: function() {
        return [];
      },
    }
  },
  data(){
    return {
    }
  },
  created(){

  },
  mounted(){},
  computed:{},
  methods:{},
  watch:{},
}
</script>
<style scoped>
.feed-planning-wrapper {
  margin: 0 auto;
  padding: 10px 0;
  width: 66.66666667%;
}

.feed-planning-title {
  margin-bottom: 20px;
  padding: 20px;
  font-size: 20px;
  line-height: 1;
  text-align: left;
  background-color: #ddd;
}

.feed-planning-wrapper >>> .planing-label  {
  margin-top: 4px;
  padding-right: 6px;
  line-height: 16px;
  height: 32px;
  white-space: normal;
  text-align: left;
}

</style>