<template>
  <div class="not-found">
    <h1>404 - ページが見つかりません</h1>
    <p>お探しのページは存在しないか、移動された可能性があります。</p>
    <router-link to="/">ホームに戻る</router-link>
  </div>
</template>

<script>
export default {
  name: "NotFound"
}
</script>

<style scoped>
.not-found {
  text-align: center;
  padding: 2rem;
}
</style>
