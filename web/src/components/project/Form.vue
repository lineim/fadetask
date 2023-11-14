<template>
  <div class="question-form-wrapper">
    <a-form
      :form="form"
      @submit="submitHandle"
      layout="horizontal"
    >
      <a-form-item 
        :label="$t('project.create.name')"
        :label-col="{ span: 4 }"
        :wrapper-col="{ span: 18 }"
      >
        <a-input
          v-decorator="['name', {
            rules: [
              { required: true, message: $t('project.name_requeired_msg') },
              { max: titleMaxLen, message: $t('project.name_too_long_tips', {max: titleMaxLen})}
            ]
          }]"
          :placeholder="$t('project.create.name')"
        />
      </a-form-item>
      <a-form-item 
        :label="$t('project.create.description')"
        :label-col="{ span: 4 }"
        :wrapper-col="{ span: 18 }"
      >
        <a-textarea
          v-decorator="[
            'desc',
            {rules: [
              { max: descMaxLen, message: $t('project.name_too_long_tips', {max: descMaxLen})}
            ]}
          ]"
          :placeholder="$t('project.create.description_placeholder')"
          :rows="6"
        />
      </a-form-item>
      <a-form-item
        :label-col="{ span: 4 }"
        :wrapper-col="{ span: 18, offset: 4 }"
      >
        <a-button
          :style="{marginRight: '8px'}"
          @click="cacneled()"
        >
          {{ $t('cancel') }}
        </a-button>
        <a-button
          type="primary"
          :loading="submiting"
          html-type="submit"
        >
          {{ $t('submit') }}
        </a-button>
      </a-form-item>
    </a-form>
  </div>
</template>
<script>
import api from '@/api'
import { TITLE_MAX_LEN, DESC_MAX_LEN } from '../../components/project';

export default {
  props: {
    // 编辑或复制时传入
    projectUuid: {
      type: String,
      default: "",
      required: false
    },
    // add, edite or copy
    action: {
      type: String,
      default: "add",
      required: false
    }
  }, 
  data() {
    return {
      form: this.$form.createForm(this),
      titleMaxLen: TITLE_MAX_LEN,
      descMaxLen: DESC_MAX_LEN,
      submiting: false,
      project: {},
    }
  },
  computed: {
  },
  watch: {
    action: {
      immediate: true,
      handler (newAction) {
        this.form.resetFields();
        if (newAction == 'add') {
          return ;
        }
        this.loadProject();
      }
    },
    projectUuid: {
      immediate: true,
      handler () {
        this.form.resetFields();
        this.loadProject();
      }
    },

    project: {
      immediate: true,
      handler() {
        let fieldsValue = {
          'name': this.project.name,
          'desc': this.project.description
        };
        this.form.setFieldsValue(fieldsValue);
      }
    }
  },
  methods: {

    loadProject() {
      if (!this.projectUuid) {
        return;
      }
      api.getProject({query: {uuid: this.projectUuid}}).then(project => {
        this.project = project;
      });
    },

    submitHandle(e) {
      e.preventDefault();

      this.form.validateFields((err, values) => {
        if (err) {
          return;
        }
        if (this.action != 'edit') {
          return this.addProject(values);
        }
        return this.updateProject(values);
      });
    },

    addProject(values) {
      this.submiting = true;
      api.addProject({data: values}).then(($uuid) => {
        this.form.resetFields();
        this.$emit('submited', $uuid);
        this.submiting = false;
      }).catch(() => {
        this.submiting = false;
      });
    },
    
    updateProject(values) {
      this.submiting = true;
      api.updateProject({query: {uuid: this.projectUuid}, data: values}).then(() => {
        this.form.resetFields();
        this.$emit('submited', this.projectUuid);
        this.submiting = false;
      }).catch(() => {
        this.submiting = false;
      });
    },

    cacneled() {
      this.form.resetFields();
      this.$emit('canceled'); // 付组件通过v-on:canceled 监听当前组件canceled事件
    }
  }
}
</script>


<style scoped>

</style>