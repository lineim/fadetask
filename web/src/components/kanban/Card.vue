<template>
  <div>
    <draggable
      tag="v-layout"
    > 
      <div
        v-for="card in cards"
        :key="card.id"
        @click="onCardClick(card.id)"
        class="card"
      >
        <div class="">
          <span
            class="label-small"
            v-for="label in card.labels"
            :key="label.id"
            :style="{background: label.color}"
          />
        </div>
        <div class="card-title">
          {{ card.title }}
        </div>
        <div class="card-footer">
          <span v-if="card.attachment_num > 0"><a-icon type="file" />{{ card.attachment_num }}</span>
          <span
            v-if="card.end_time"
            class="due-date"
            :style="getDueDateStyle(card.end_date)"
          >{{ card.end_date }}</span>

          <a-avatar
            v-for="(avatar,index) in card.members"
            :key="index"
            style="color: #f56a00; backgroundColor: #fde3cf; float: right;"
          >
            UA
          </a-avatar>
        </div>
      </div>
    </draggable>
  </div>
</template>
<script>
import moment from 'moment';
import draggable from "vuedraggable";

export default {
  props: ['cards'],
  components: {draggable},
  methods: {
    onCardClick: function(id) {
      this.$emit('click', id);
    },
    getDueDateStyle: function(endDate) {
      const now = moment();
      const end = moment(endDate);
      let styles = {};
      if (now.isAfter(end)) {
        styles.color = 'rgb(235, 91, 70)';
      }
      return styles;
    },
  }
}
</script>
<style>
.card {
  padding: 6px 8px 2px;
  border-radius: 4px;
  background: #ffffff;
  box-shadow: 0 1px 0 rgba(9, 30, 66, 0.25);
  margin-bottom: 8px;
  cursor: pointer;
  min-height: 100px;
}

.card-title {
  margin-bottom: 15px;
}

.card:hover {
  box-shadow: 1px 2px 1px rgba(9, 30, 66, 0.25);
}

.card-labels {
  overflow: hidden;
  /* padding: 6px 8px 2px; */
  position: relative;
  z-index: 10;
  margin-bottom: 2px;
}

.label-small {
  height: 18px;
  line-height: 18px;
  width: 40px;
  border-radius: 2px;
  display: inline-block;
  margin-right: 5px;
  vertical-align: top;
}

.card-title {
  clear: both;
  display: block;
  margin: 0 0 4px;
  /* overflow: hidden; */
  text-decoration: none;
  word-wrap: break-word;
  word-break: break-all;
  overflow-wrap: break-word;
  color: #172b4d;
}

.card-footer {
  line-height: 32px;
  overflow: hidden;
}

.card-footer .due-date {
  font-size: 12px;
}
</style>