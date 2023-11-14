<template>
  <a-modal
    :title="$t('board_nav.kanban.to_project')"
    :visible="visible"
    :confirm-loading="submiting"
    @cancel="modalCancel"
    @ok="onSubmit"
  >
    <a-form-model
      :label-col="{span: 4}"
      :wrapper-col="{span: 14}"
    >
      <a-form-model-item
        v-if="projects.length > 0"
        :label="$t('project.label')"
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
        <span v-if="projects.length <= 0">
          没有项目
        </span>
      </a-form-model-item>
    </a-form-model>
  </a-modal>
</template>
<script>
import api from '@/api';
import i18n from '../../i18n';

export default {
    props: {
      visible: {
        type: Boolean,
        default: false
      },
      kanbanUuid: {
        type: String,
        default: '',
        required: true
      },
      selectedProject: {
        type: String,
        default: '',
        required: false
      }
    },
    watch: {
      visible: function() {
        if (this.visible) {
          this.loadMyProject();
        }
      }
    },
    data() {
      return {
        projects: [],
        projectUuid: '',
        submiting: false
      };
    },
    created() {
      this.projectUuid = this.selectedProject;
    },

    methods: {
      loadMyProject: function() {
        const query = {manager_project: 1};
        api.meProject({params: query}).then(projects => {
          projects.unshift({uuid: "", name: "--请选择项目--"});
          this.projects = projects;
        });
      },
      modalCancel: function() {
        this.$emit('closed');
        this.submiting = false;
      },
      onSubmit: function() {
        if (!this.projectUuid) {
          this.$message.error(i18n.t('project.need_select_tips'));
          return;
        }
        this.submiting = true;
        api.addProjectKanban({query: {uuid: this.projectUuid}, data: {kanban_uuid: this.kanbanUuid}}).then(() => {
          this.$emit('associated', this.projectUuid);
          this.$message.success(i18n.t('board_nav.kanban.to_project_success'));
          this.submiting = false;
        }).catch(() => {
          this.submiting = false;
        });
      },
    }
}
</script>