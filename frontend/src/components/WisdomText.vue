<template>
  <div class="wisdom-text-container">
    <p class="wisdom-text">「{{ currentWisdom }}」</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const wisdoms = [
  '顺天应人，知命造命。',
  '万物皆有定时，播种有时，收获有时。',
  '心随境转是凡夫，境随心转是圣贤。',
  '命由己造，相由心生。',
  '祸兮福之所倚，福兮祸之所伏。',
  '行有不得，反求诸己。',
  '知人者智，自知者明。',
  '上善若水，水善利万物而不争。',
  '千里之行，始于足下。',
  '大音希声，大象无形。'
];

const currentWisdom = ref('');

onMounted(() => {
  const randomIndex = Math.floor(Math.random() * wisdoms.length);
  currentWisdom.value = wisdoms[randomIndex];
});
</script>

<style scoped>
.wisdom-text-container {
  margin-top: 20px;
  text-align: center;
  padding: 15px;
  opacity: 0.7;
}

.wisdom-text {
  font-size: 14px;
  color: var(--text-secondary, #666);
  font-style: italic;
  letter-spacing: 1px;
  margin: 0;
}
</style>
