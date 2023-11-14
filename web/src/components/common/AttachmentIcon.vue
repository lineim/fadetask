<template>
  <div class="attachment-icon">
    <div class="text">
      {{ type }}
    </div>
  </div>
</template>
<script>
export default {
    props: {
        type: {
            type: String,
            default: '',
            required: false
        },
    }
}
</script>
<style scoped lang="less">
    .attachment-icon {
        width: 24px;
        height: 32px;
        background: linear-gradient(225deg, transparent 7px, #f3f3f3 0 );
        position: relative;
        border-radius: 0.2em;
        display: inline-block;
    }
    .attachment-icon::after {
        content: "";
        position: absolute;
        right: 0px;
        top:0px;
        background: linear-gradient(-135deg, transparent 50%, #f3f3f3 0);
        width: 10px;
        height:  10px;
        border-bottom-left-radius: 2px;
        box-shadow: -0.2em 0.2em 0.2em rgba(0, 0, 0, 0.3);
    }
    .text {
        font-size: 8px;
        width: 100%;
        text-align: center;
        height: 8px;
        line-height: 8px;
        position: absolute;
        top: 16px;
        background: rgb(245, 74, 69);
        color: #fff;
        text-overflow: ellipsis;
        overflow: hidden; 
    }
</style>