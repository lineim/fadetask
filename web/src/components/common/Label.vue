<template>
  <div>
    <div v-if="!isEditModel">
      <div
        class="modal-label-container"
        :style="containerStyle"
      >
        <draggable
          :list="labelsForDisplay"
          :disabled="false"
          class="list-group"
          ghost-class="ghost"
          @start="dragging = true"
          @end="sort"
        >
          <div 
            class="modal-label-list" 
            v-for="label in labelsForDisplay" 
            :key="label.id" 
          >
            <span 
              class="modal-label-color-block" 
              @click.stop="labelChange(label.id)"
              :style="{background: label.color}"
            >
              <span class="modal-label-select-icon">
                <a-icon 
                  v-if="isSelected(label.id)" 
                  type="check-square" 
                />
              </span>
              {{ label.name }}
              <a-button
                class="pull-right"
                type="link"
                size="small"
                @click.stop="editLabel(label)"
                style="color: #fff; margin-right: 5px;"
                icon="edit"
              />
            </span>
          </div>
        </draggable>
      </div>
      <a-button
        class="mtm"
        type="primary"
        icon="plus"
        @click="newLabel"
        block
      >
        {{ $t('kanban.label.new_label') }}
      </a-button>
    </div>
      
    <div v-if="isEditModel">
      <a-form-model
        layout="vertical"
        :model="form"
        :rules="rules"
        v-bind="{}"
      >
        <a-form-model-item
          class="mbm"
          :label="$t('kanban.label.form.name_label')"
          prop="name"
          ref="name"
        >
          <a-input
            v-model="form.name"
            placeholder=""
          />
        </a-form-model-item>
        <a-form-model-item
          class="mbm"
          :label="$t('kanban.label.form.color_label')"
        >
          <div class="label-pop-color-select">
            <span
              class="label-pop-color-select-item"
              v-for="color in colors"
              @click="selectColor(color)"
              :key="color"
              :style="{backgroundColor: color}"
            >
              <a-icon
                v-if="colorSelected(color)"
                type="check"
              />
            </span>
          </div>
        </a-form-model-item>
        <a-form-model-item>
          <div class="d-flex flex-jc-sp">
            <a-popconfirm
              :title="$t('kanban.label.delete_tips')"
              :ok-text="$t('yes')"
              :cancel-text="$t('no')"
              @confirm="delLabel(form.id)"
            >
              <a-button
                class="pull-right"
                v-if="form.id > 0"
                type="danger"
              >
                {{ $t('delete') }}
              </a-button>
            </a-popconfirm>
            <div> 
              <a-button
                type="default"
                @click="cancel"
                class="mrs"
              >
                {{ $t('cancel') }}
              </a-button>
              <a-button
                type="primary"
                :disabled="btnDisabled"
                @click="submit"
              >
                {{ $t('submit') }}
              </a-button>
            </div>
          </div>
        </a-form-model-item>
      </a-form-model>
    </div>
  </div>
</template>
  
<script>
// this.$emit('add');
// this.$emit('edit');
// this.$emit('selected');
// this.$emit('unselected');
const EMPTY_LABEL = {name: "", color: "", id: 0};
const EDIT_MODEL_ADD = 'add';
const EDIT_MODEL_EDIT = 'edit';

import {mutilArrHasItem} from '@/utils/index';
import draggable from "vuedraggable";
import api from "@/api";

export default {
    components: {
        draggable
    },
    props: {
        kanbanId: {
            type: Number,
            default: 0,
            required: true
        },

        labels: {
            type: Array,
            default: () => [],
            required: false
        },

        colors: {
            type: Array,
            default: () => [],
            required: false
        },

        selectedLabels: {
            type: Array,
            required: true
        },

        containerStyle: {
            type: Object,
            default: () => {},
            required: false
        }
    },
    data() {
        return {
            editModel: false,
            form: EMPTY_LABEL,
            selectedColor: '',
            titlePrefix: '',
            btnDisabled: false,
            labelsForDisplay: [],
            rules: {
                name: [
                    {required: false, message: "请输入名称", trigger: 'blur'},
                    {max: 8, message: "名称长度不能超过8个字符", trigger: 'blur'}
                ]
            }
        };
    },
    created() {
        let colors = this.colors;
        let tmpColors = colors.slice(0, 1);
        this.selectedColor = tmpColors.shift();
        this.labelsForDisplay = this.labels;
    },

    computed: {
        isEditModel: function() {
            return this.editModel;
        },
        colorSelected() {
            return function (color) {
                return color == this.selectedColor;
            }
        },
    },

    watch: {
    labels: {
        handler() {
            this.labelsForDisplay = this.labels;
        },
        deep: true
    },
    selectedLabels: {
        handler() {
        },
        deep: true
    }
    },

    methods: {
        isSelected: function(labelId) {
            return mutilArrHasItem(this.selectedLabels, 'id', labelId)
        },
        closeEditModel: function() {
            this.form = Object.assign(this.form, EMPTY_LABEL);
            console.log(this.form);
            this.editModel = false;
            this.$emit('offedit');
        },
        newLabel: function() {
            this.titlePrefix = '新增';
            this.editModel = EDIT_MODEL_ADD;
            this.$emit('onadd');
        },
        editLabel: function(label) {
            this.form = label;
            this.editModel = EDIT_MODEL_EDIT;
            this.selectedColor = label.color;
            this.$emit('onedit');
        },
        selectColor: function(color) {
            this.selectedColor = color;
        },
        sort: function() {
            let ids = [];
            this.labelsForDisplay.forEach(element => {
                ids.push(element.id);
            });
            api.sortLabel({query: {kanbanId: this.kanbanId}, data: {label_ids: ids.join(',')}}).then(() => {
                this.$emit('sorted', this.labelsForDisplay);
            });
        },
        submit: function() {
            this.btnDisabled = true;
            this.form.color = this.selectedColor;
            let data = this.form;
            if (this.editModel == EDIT_MODEL_ADD) {
                api.kanbanLabelAdd({
                    query: {kanbanId: this.kanbanId},
                    data: data
                }).then((newLabel) => {
                    this.btnDisabled = false;
                    this.closeEditModel();
                    if (!newLabel) {
                        return;
                    }
                    this.$emit('newlabel', newLabel);
                });
            } else {
                api.kanbanLabelEdit({
                    query: {kanbanId: this.kanbanId, id: data.id}, 
                    data: data
                }).then((newLabel) => {
                    this.btnDisabled = false;
                    this.closeEditModel();
                    if (!newLabel) {
                        return;
                    }
                    this.$emit('labelupdated', newLabel);
                });
            }
        },
        cancel: function() {
            this.closeEditModel();
        },
        delLabel: function(id) {
            api.kanbanLabelDel({query: {kanbanId: this.kanbanId, id: id}}).then(() => {
                this.closeEditModel();
                this.$emit('labeldeleted', id);
            });
        },
        labelChange: function(id) {
            if (mutilArrHasItem(this.selectedLabels, 'id', id)) {
                this.$emit('change', id, false); // 移除
            } else {
                this.$emit('change', id, true); // 添加
            }
        },
        visibleChange: function(v) {
            if (!v) {
                this.closeEditModel();
            }
        }
    }
}
</script>
  
<style scoped>
.modal-label-container {
    /* max-height: 260px; */
    overflow-y: overlay;
}

.modal-label-list {
    height: 37px;
    color: #ffffff;
    overflow-y: overlay;
    /* margin-right: 40px; */
}

.modal-label-color-block {
    border-radius: 4px;
    cursor: move;
    /* width: 180px; */
}

.modal-label-edit-icon {
    color: black;
}

.label-pop-color-select {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
    grid-gap: 5px;
}

.label-pop-color-select-item {
    height: 32px;
    width: 60px;
    display: inline-block;
    text-align: center;
    vertical-align: top;
    line-height: 32px;
    color: #ffffff;
    border-radius: 4px;
    margin-bottom: 10px;
    cursor: pointer;
}

</style>