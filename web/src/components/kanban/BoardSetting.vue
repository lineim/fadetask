<template>
  <a-drawer
    title="看板设置"
    placement="right"
    :closable="true"
    :visible="visible"
    :mask="false"
    :destroy-on-close="true"
    :width="380"
    :get-container="container"
    :wrap-style="{ position: 'absolute' }"
    @close="close"
  >
    <a-empty v-if="emptyBoard">
      <span slot="description">看板不存在</span>
    </a-empty>
  </a-drawer>
</template>
<script>
  import api from "@/api";
  export default {
    data() {
        return {
            board: {},
            emptyBoard: false,
        };
    },
    props: {
        boardId: {
            type: Number,
            default: 0
        },
        visible: {
            type: Boolean,
            default: false
        },
        container: {
            type: String,
            default: 'body'
        }
    },
    methods: {
        close: function() {
            this.$emit('close');
        },

        getContainer: function() {
            return this.$refs.board;
        },

        loadBoard: function() {
            api
            .kanbanDetail({ query: { id: this.boardId } })
            .then(data => {
                this.kanban = data.kanban;
                this.list = data.list;
                this.listCards = data.list_tasks;
                this.setTitle(this.kanban.name);
            })
            .catch(err => {
                console.error(err);
            });
        }
    }
  }
</script>
