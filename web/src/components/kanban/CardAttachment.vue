<template>
  <a-popover 
    :destroy-tooltip-on-hide="true"
    @visibleChange="visibleChange"
    overlay-class-name="card-upload-popover no-arrow-popover"
    trigger="click"
  >
    <div
      class="popover-title"
      slot="title"
    >
      <span
        class="mrs cursor-pointer"
        v-if="fromCard"
        @click="closeFromCard"
      ><a-icon type="left" /></span>
      {{ title }}
    </div>
    <div
      slot="content"
      class="lg-popover-content"
    >
      <a-upload
        v-if="!fromCard"
        class="uploader"
        name="attachment"
        :multiple="true"
        :show-upload-list="false"
        :action="uploadAction"
        :custom-request="upload"
        :headers="uploadHeader"
        :before-upload="beforeUpload"
        @change="uploadProcess"
      >
        <a-button
          type="primary"
          block
        >
          从电脑上传
        </a-button>
      </a-upload>

      <a-button
        v-if="!fromCard"
        @click="showFromCard"
        block
      >
        从其他卡片选择
      </a-button>

      <div
        v-if="!fromCard"
        class="attachment-list"
      >
        <div
          v-for="(file, key) in fileList"
          :key="key"
          class="attachment-item"
        >
          <div 
            class="attachment-item-info"
          > 
            <div class="attachment-name">
              <a-tooltip
                :title="file.name"
              >
                {{ file.name }}
              </a-tooltip>
            </div>
            <a-tooltip title="移除附件">
              <span
                class="attachment-remove"
                @click="remove(file)"
              ><a-icon type="delete" /></span>
            </a-tooltip>
          </div>
          <div>
            <a-progress
              class="mrxs"
              :width="16"
              :percent="file.percent"
            />
          </div>
        </div>
      </div>

      <div v-if="fromCard">
        <a-input-search 
          placeholder="文件名或卡片名" 
          v-model="keyword"
          enter-button 
          @search="searchAttachmentForCopy" 
        />
        <a-divider />
        <a-list
          class="for-copy-attachments"
          :loading="loading"
          item-layout="horizontal"
          :data-source="attachmentsForCopy"
        >
          <a-spin v-if="loading" />
          <a-list-item
            slot="renderItem"
            slot-scope="item"
          >
            <a
              slot="actions"
              @click="copy(item.id)"
            >复制</a>
            <a-list-item-meta
              :description="'卡片：' + item.task_title"
            >
              <span slot="title">{{ item.org_name }}</span>
            </a-list-item-meta>
          </a-list-item>
        </a-list>
      </div>
    </div>

    <slot name="trigger">
      <a-button 
        size="small" 
        icon="file" 
        style="margin-bottom: 10px;" 
        block
      >
        附件
      </a-button>
    </slot>
  </a-popover>
</template>
<script>
import api from "@/api";
// import i18n from '../../i18n';
import store from "@/store";
import '@/less/color.less';
import { removeArrItem } from '../../utils';
import OSS from 'ali-oss';

export default {
  props: {
    kanbanId: {
      type: String,
      default: '',
      required: false
    },

    cardId: {
      type: Number,
      default: 0,
      required: false
    },

    title: {
      type: String,
      default: '',
      required: false
    },

  },
  comments: {
    
  },
  data() {
    return {
      uploadHeader: {'X-Auth-Token': store.state.token },
      fileList: [],
      uploadingClient: {},
      keyword: '',
      attachmentsForCopy: [],
      showLoadingMore: false,
      loading: false,
      fromCard: false,
      stsInfo: {},
    };
  },
  computed: {
    uploadAction: function() {
      return '/api/kanban/' + this.cardId + '/task/attachment';
    },
  },
  methods: {
    uploadProcess: function(info) {
      const file = info.file;
      if (info.file.status === 'done') {
        const response = info.file.response.data.attachments;
        const current = info.file.response.data.current;
        this.$emit('success', response);
        this.fileList.forEach((ele, index) => {
          if (ele.uid == file.uid) {
            this.fileList[index].id = current.id;
            this.$set(this.fileList, index, this.fileList[index]);
          }
        });
      }
      
      if (info.event) {
        const percent = info.event.percent;
        this.fileList.forEach((ele, index) => {
          if (ele.uid == file.uid) {
            this.fileList[index].percent = percent;
            this.$set(this.fileList, index, this.fileList[index]);
          }
        });
      }
    },

    beforeUpload: async function(file) {
      file.percent = 0;
      file.id = 0;
      let data = {
        size: file.size,
        name: file.name,
        mine_type: file.type
      };
      const res = await api.taskAttahcmentInit({query: {taskId: this.cardId}, data: data});
      this.stsInfo = res;
      file.uuid = res.uuid;
      this.fileList.unshift(file);
      return true;
    },

    uploadOssProcess: function(file, p, cpt) {
      const percent = parseInt(p*100);
      this.fileList.forEach((ele, index) => {
        if (ele.uid == file.uid) {
          this.fileList[index].percent = percent;
          this.fileList[index].abortCheckpoint = cpt;
          this.$set(this.fileList, index, this.fileList[index]);
        }
      });
      if (percent >= 100) {
        api.taskAttahcmentFinished({query: {taskId: this.cardId}, data: {uuid: this.stsInfo.uuid}}).then(resp => {
          const response = resp.attachments;
          const current = resp.current;
          this.$message.success('文件《' + file.name + '》上传完成！');
          this.$emit('success', response);
          this.fileList.forEach((ele, index) => {
            if (ele.uid == file.uid) {
              this.fileList[index].id = current.id;
              this.fileList[index].uuid = current.uuid;
              this.$set(this.fileList, index, this.fileList[index]);
            }
          });
        });
      }
    },

    upload: async function(e) {
      const file = e.file;
      if (file.size > this.stsInfo.sizeLimit) {
        this.$message.error('文件大小超过限制!');
        return false;
      }      
      const headers = {
        // 指定该Object被下载时的网页缓存行为。
        "Cache-Control": "no-cache",
        // 指定该Object被下载时的名称。
        "Content-Disposition": `attachment; filename=${encodeURIComponent(file.name)}`,
        // 指定该Object被下载时的内容编码格式。
        "Content-Encoding": "utf-8",
        // 指定过期时间，单位为毫秒。
        "Expires": "1000",
        // 指定Object的存储类型。
        // "x-oss-storage-class": "Standard",
        // 指定Object标签，可同时设置多个标签。
        // "x-oss-tagging": "Tag1=1&Tag2=2",
        // 指定初始化分片上传时是否覆盖同名Object。此处设置为true，表示禁止覆盖同名Object。
        "x-oss-forbid-overwrite": "false",
      };
      const options = {
        // 获取分片上传进度、断点和返回值。
        // eslint-disable-next-line
        progress: (p, cpt, res) => {
          // eslint-disable-next-line
          this.uploadOssProcess(file, p, cpt);
        },
        // 设置并发上传的分片数量。
        parallel: 4,
        // 设置分片大小。默认值为1 MB，最小值为100 KB。
        partSize: this.stsInfo.partSize,
        headers,
        mime: file.type,
      };
      const client = new OSS({
        // yourRegion填写Bucket所在地域。以华东1（杭州）为例，Region填写为oss-cn-hangzhou。
        endpoint: this.stsInfo.endpoint,
        accessKeyId: this.stsInfo.accessKeyId,
        accessKeySecret: this.stsInfo.accessKeySecret,
        stsToken: this.stsInfo.securityToken,
        refreshSTSToken: async () => {
        // 向您搭建的STS服务获取临时访问凭证。
          const info = await api.ossSts();
          return {
            accessKeyId: info.accessKeyId,
            accessKeySecret: info.accessKeySecret,
            stsToken: info.securityToken
          }
        },
        // 刷新临时访问凭证的时间间隔，单位为毫秒。
        refreshSTSTokenInterval: 300000,
        bucket: this.stsInfo.bucket,
      });
      this.uploadingClient[file.uid] = client;
      try {
          // 分片上传。
          const res = await client.multipartUpload(this.stsInfo.filePath, file, {
            ...options,
          });
          console.log(res)
        } catch (err) {
          console.log(err);
        }
    },

    remove: function(file) {
      const fileId = file.uuid;
      if (file.percent < 100) {
        const client = this.uploadingClient[file.uid];
        client.abortMultipartUpload(file.abortCheckpoint.name, file.abortCheckpoint.uploadId);
      }
      api.delAttachment({query: {taskId: this.cardId, id: fileId}}).then(() => {
        this.fileList = removeArrItem(this.fileList, function(item) {
          return item.uuid == fileId;
        });
        if (file.percent >= 100) { // 已上传完成的文件，需要发送deleted事件
          this.$emit('deleted', fileId);
        }
      });
    },

    showFromCard: function() {
      this.fromCard = true;
      this.searchAttachmentForCopy();
    },

    searchAttachmentForCopy: function() {
      this.showLoadingMore = false;
      this.loading = true;
      api.taskAttachmentSearchForCopy({
        query: {boardId: this.kanbanId},
        params: {keyword: this.keyword, exclude_task_id: this.cardId}
      }).then(res => {
        this.attachmentsForCopy = res;
        this.loading = false;
        if (res.length > 0) {
          this.showLoadingMore = true;
        }
      });
    },

    copy: function(fileId) {
      const data = {to_task_id: this.cardId};
      api.taskAttachmentCopy({query: {id: fileId}, data: data}).then(attachments => {
        this.$emit('success', attachments);
        this.searchAttachmentForCopy();
      });
    },

    closeFromCard: function() {
      this.fromCard = false;
    },

    visibleChange: function(v) {
      if (!v) {
        this.fileList.forEach((ele) => {
          if (ele.percent < 100) {
            this.$message.info('附件未上传完成，将在后台继续上传！');
          }
        });
        this._clear();
      }
    },

    _clear: function() {
      this._clearFileList();
      this.fromCard = false;
      this.keyword = '';
      this.attachmentsForCopy = [];
    },

    _clearFileList: function() {
      // let tmpFileList = [];
      // this.fileList.forEach((file) => {
      //   if (file.percent < 100) {
      //     tmpFileList.push(file);
      //   }
      // })
      // this.fileList = tmpFileList;
      this.fileList = [];
    },

  }
}
</script>
<style scoped lang="less">
  .attachment-list {
    margin-top: 1.2em;
    .attachment-item {
      padding: 0.5em 0.5em;
      border-radius: 0.2em;
      .attachment-item-info {
        display: flex;
        justify-content: baseline;
        position: relative;
        .attachment-name {
          overflow-x: hidden;
          white-space: nowrap;
          text-overflow: ellipsis;
          width: calc(~"100% - 2em");
        };
        .attachment-remove {
          cursor: pointer;
          display: inline;
          color: rgb(245, 74, 69);
        };
      };
      &:hover {
        background-color: rgba(155, 207, 255, 0.2);
      }
    }
  }

  .for-copy-attachments {
    height: 280px;
    overflow-y: scroll;
    overflow-x: hidden;
  }

  .attachment-item .ant-progress-circle .ant-progress-text .anticon {
    font-size: 0.8em;
  }

</style>