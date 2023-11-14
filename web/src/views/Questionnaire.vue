<template>
  <div>
    <div class="create-question-btn">
      <a-button
        type="primary"
        @click="add"
      >
        +创建问卷
      </a-button>
    </div>
    <a-drawer
      :destroy-on-close="true"
      :title="drawerTitle"
      :width="800"
      @close="closeDrawer"
      :visible="drawerVisible"
      :wrap-style="{height: 'calc(100% - 108px)',overflow: 'auto',paddingBottom: '108px'}"
    >
      <QuestionnaireForm 
        :action="formAction"
        :questionnaire-id="formQuestionnaireId"
        @submited="formSubmited"
        @canceled="formCanceled"
      />        
    </a-drawer>
    <a-divider />
    <a-table
      :columns="columns" 
      :data-source="questionnaires"
      :row-key="record => record.id"
      @change="tableChange"
    >
      <template
        slot="updatedTime"
        slot-scope="updatedTime"
      >
        {{ timeFormat(updatedTime) }}
      </template>

      <template
        slot="action"
        slot-scope="item"
      >
        <a-button-group>
          <a-button
            size="small"
            type="link"
            style="display: none"
          >
            预览
          </a-button>
          <a-button
            size="small"
            @click="edit(item.id)"
            type="link"
          >
            修改
          </a-button>
          <a-button
            size="small"
            @click="copy(item.id)"
            type="link"
          >
            复制
          </a-button>
          <a-popconfirm
            placement="top"
            ok-text="是"
            cancel-text="否"
            @confirm="deleteQuestionnaire(item.id)"
          >
            <template slot="title">
              确认删除问卷
            </template>
            <a-button
              size="small"
              type="link"
            >
              删除
            </a-button>
          </a-popconfirm>
        </a-button-group>
        <!-- {{questionnaire}} -->
      </template>
    </a-table>
  </div>
</template>

<script>
import api from '@/api'
import questionnaireForm from '@/components/questionnaire/Form.vue';
import {toDateTime} from '@/helper/datatime.js'

const columns = [
  {
    title: '名称',
    dataIndex: 'name',
    width: '25%',
    scopedSlots: { customRender: 'name' },
  },
  {
    title: '创建人',
    dataIndex: 'userName',
    width: '15%',
  },
  {
    title: '更新时间',
    dataIndex: 'updatedTime',
    scopedSlots: { customRender: 'updatedTime' },
    width: '30%',
  },
  {
    title: '操作',
    key: 'action',
    scopedSlots: { customRender: 'action' },
  },
];

export default {
    components: {
      'QuestionnaireForm': questionnaireForm,
    },
    data() {
        return {
            loading: true,
            columns: columns,
            questionnaires: [],
            pagination: {
              defaultPageSize: 10
            },
            drawerVisible: false,
            formAction: '',
            formQuestionnaireId: 0,
        }
    },

    created() {
        
    },
    mounted() {
      this.loadQuestionires();
      this.formAction = 'add';
    },
    methods: {
        tableChange() {

        },
        timeFormat(time) {
          return toDateTime(time);
        },
        loadQuestionires() {
          api.getQuestionnaires().then(res => {
            const pagination = { ...this.pagination };
            pagination.total = res.totalCount;
            this.loading = false;
            this.questionnaires = res.questionnaires;
            this.pagination = pagination;
          }).catch(err => {
            this.$message.error(err.message);
          });
        },
        showDrawer() {
          this.drawerVisible = true;
        },
        closeDrawer() {
          this.drawerVisible = false;
        },
        formSubmited() {
          this.loadQuestionires();
          this.closeDrawer();
        },
        formCanceled() {
          this.closeDrawer();
        },

        add() {
          this.formAction = 'add';
          this.formQuestionnaireId = 0;
          this.showDrawer();
        },
        
        edit(id) {
          this.formQuestionnaireId = id;
          this.formAction = 'edit';
          this.showDrawer();
        },

        copy(id) {
          this.formQuestionnaireId = id;
          this.formAction = 'copy';
          this.showDrawer();
        },

        deleteQuestionnaire(id) {
          let self = this;
          api.deleteQuestionnaire({data: {id: id}}).then(res => {
            if (res.success) {
              self.$message.success('删除成功');
              self.loadQuestionires();
              return ;
            }
            self.$message.error('删除失败');
          });
        }
    },
    computed: {
        showEmpty() {
            return !this.loading && this.questionnaires.length == 0;
        },

        drawerTitle() {
          switch (this.formAction) {
            case 'copy':
              return '复制问卷';
            case 'edit':
              return '编辑问卷';
            default:
              return '新问卷';
          }
        },
    }

}
</script>

<style scoped>
.create-question-btn {
  text-align: right;
}
</style>