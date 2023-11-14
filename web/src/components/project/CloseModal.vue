<template>
  <a-modal
    v-model="showModal"
    @cancel="closed"
    width="600px"
    title="关闭项目"
    ok-text="关闭"
    @ok="submit"
  >
    <a-form-model
      :label-col="{span: 8}"
      :wrapper-col="{span: 14}"
    >
      <a-form-model-item
        label="如何处理项目中的看板？"
        prop="delivery"
      >
        <a-radio-group
          :default-value="1"
          v-model="closeKanban"
        >
          <a-radio :value="1">
            随项目一起关闭
            <a-popover>
              <template slot="content">
                随项目关闭时，会关闭项目下的所有看板；<br>
                且在项目重新打开时，不会自动打开看板，<br>
                需要在已关闭的看板列表中单独打开看板！
              </template>
              <a-icon
                style="margin-left: 2px;"
                type="info-circle"
              />
            </a-popover>
          </a-radio>
          <a-radio :value="0">
            保持原有状态
            <a-popover>
              <template slot="content">
                项目关闭后，项目中的看板保持原来的状态。
              </template>
              <a-icon
                style="margin-left: 2px;"
                type="info-circle"
              />
            </a-popover>
          </a-radio>
        </a-radio-group>
      </a-form-model-item>
    </a-form-model>
  </a-modal>
</template>

<script>
import api from '@/api';
export default {
    props: {
        uuid: {
            type: String,
            default: '',
            required: true
        },
        visiable: {
            type: Boolean,
            default: false,
            required: true
        }
    },
    data() {
        return {
            showModal: false,
            closeKanban: 1
        };
    },
    mounted() {
        this.showModal = this.visiable;
    },

    watch: {
        visiable: {
            handler: function(newVal) {
                this.showModal = newVal;
            },
            deep: true
        }
    },
    methods: {
        closed: function() {
            this.$emit('close');
        },
        submit: function() {
            const data = {close_kanban: this.closeKanban};
            api.closeProject({query: {uuid: this.uuid}, data: data}).then(() => {
                this.$message.success('看板已关闭！');
                this.$router.push({name: 'ProjectList'});
            });
        }
    }
}
</script>