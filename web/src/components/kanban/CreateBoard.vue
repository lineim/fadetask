<template>
  <div>
    <a-modal
      :title="title"
      v-model="showModal"
      :footer="null"
      :destroy-on-close="true"
      :force-render="false"
      :width="680"
      @afterClose="close"
    >
      <a-form-model
        layout="horizontal"
        ref="form"
        :model="form"
        :rules="formRules"
        v-bind="formLayout"
        class="mbl"
      >
        <a-form-model-item
          :label="$t('kanban.name_label')"
          ref="name"
          prop="name"
          required
        >
          <a-input
            v-model="form.name"
            :placeholder="$t('kanban.name_placeholder')"
          />
        </a-form-model-item>

        <a-form-model-item
          :label="$t('kanban.desc_label')"
          ref="desc"
          prop="desc"
        >
          <a-input
            v-model="form.desc"
            type="textarea"
            :rows="4"
            :placeholder="$t('kanban.desc_placeholder')"
          />
        </a-form-model-item>

        <a-form-model-item
          :label="$t('project.label')"
          ref="project"
          prop="project"
          :help="projectUuid ? $t('kanban.kanban_project_help') : ''"
        >
          <a-select
            v-model="projectUuid"
          >
            <a-select-option
              v-for="project in projects"
              :key="project.uuid"
              :value="project.uuid"
            >
              {{ project.name }}
            </a-select-option>
          </a-select>
        </a-form-model-item>

        <a-form-model-item
          v-if="fromKanban.uuid"
          :label="$t('kanban.kanban_template_label')"
          ref="from_uuid"
          prop="from_uuid"
          :help="$t('kanban.kanban_template_help')"
        >
          <a-input
            :placeholder="fromKanban.name"
            :disabled="true"
          />
        </a-form-model-item>

        <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
          <a-button
            type="primary"
            :disabled="form.name.length <= 0"
            :loading="submitting"
            @click="submit()"
          >
            {{ $t('save') }}
          </a-button>
        </a-form-model-item>
      </a-form-model>
    </a-modal>
  </div>
</template>

<script>

import api from '@/api';
import i18n from '@/i18n';

const nameRequiredMsg = i18n.t('kanban.name_requeired_msg');
const nameTooLongMsg  = i18n.t('kanban.name_too_long_msg');
const descTooLongMsg  = i18n.t('kanban.desc_too_long_msg');

const DEFAULT_FORM = {
    'name': '',
    'desc': '',
};

const DEFAULT_FROM_KANBAN = {
  'uuid': 0,
  'name': ''
};

export default {
  props: {
      'visible': {
        type: Boolean,
        default: false
      },
      'title': {
        type: String,
        default: 'New Kanban'
      },
      'fromKanban': {
        type: Object,
        default: () => DEFAULT_FROM_KANBAN
      },
      'selectProject': {
        type: String,
        default: ''
      }
  },
  data() {

    return {
      form: Object.assign({}, DEFAULT_FORM),
      formRules: {
          'name': [
              {required: true, message: nameRequiredMsg, trigger: 'change', whitespace: true },
              {max: 32, message: nameTooLongMsg, trigger: 'change', whitespace: true }
          ],
          'desc': [
              {max: 128, message: descTooLongMsg, trigger: 'change', whitespace: true },
          ],
      },
      formLayout: {
        labelCol: { span: 4 },
        wrapperCol: { span: 16 },
      },
      projects: [],
      projectUuid: '',
      submitting: false
    };
  },

   created() {
    this.projectUuid = this.selectProject;
    this.loadMyProject();
   },

   computed: {
    showModal: {
      get() {
        return this.visible;
      },
      set(val) {
        if (!val) {
          this.close();
        }
      }
    }
   },

   methods: {
    loadMyProject: function() {
      api.meProject().then(projects => {
        this.projects = projects;
        this.projects.unshift({uuid: '', name: i18n.t('project.select_tips')});
      });
    },

    submit: function() {
      this.$refs.form.validate(valid => {
        if (!valid) {
            return false;
        }
        this.submitting = true;
        let data = this.form;
        if (this.fromKanban.uuid) {
          data.from_uuid = this.fromKanban.uuid;
        }
        data.project_uuid = this.projectUuid;
        api.kanbanCreate({data: data}).then((res) => {
            this.submitting = false;
            this._resetform();
            let kanbanUUid = res.kanban_uuid;
            this.$message.success(i18n.t('kanban.create.success_msg'));
            this.close();
            this.goBoard(kanbanUUid);
        }).catch(err => {
            this.submitting = false;
            console.log(err);
        });
      });
    },

    goBoard(uuid) {
      this.$router.push({name: 'KanbanDetail', params: {id: uuid}});
    },

    _resetform: function() {
      this.form = Object.assign({}, DEFAULT_FORM);
    },


    close: function() {
      this._resetform();
      this.$emit('close');
    }
  }
}
</script>