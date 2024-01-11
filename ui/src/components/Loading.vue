<template>
  <div class="loading-screen">
    <n-card title="Loading...">
      <n-progress type="line" :percentage="100" :show-indicator="false" processing />
    </n-card>
  </div>
</template>
