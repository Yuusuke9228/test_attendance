<script setup>
import { ref, onMounted } from "vue";

onMounted(() => {
    setInterval(updateTime, 1000);
})
const date = ref(null);
const time = ref(null);
const week = ref(null);
const weekList = ref(['日', '月', '火', '水', '木', '金', '土']);
const holidayWeekList = ref(['土', '日']);
const updateTime = () => {
    let cd = new Date();
    time.value = zeroPadding(cd.getHours(), 2) + ':' + zeroPadding(cd.getMinutes(), 2) + ':' + zeroPadding(cd.getSeconds(), 2);
    date.value = zeroPadding(cd.getFullYear(), 4) + '-' + zeroPadding(cd.getMonth()+1, 2) + '-' + zeroPadding(cd.getDate(), 2);
    week.value =  weekList.value[cd.getDay()];
};

const zeroPadding = (num, digit) => {
    let zero = '';
    for(let i = 0; i < digit; i++) {
        zero += '0';
    }
    return (zero + num).slice(-digit);
}
</script>

<template>
  <div class="p-3 font-bold text-center">
    <div class="pb-0 text-lg text-green-600 border-b border-orange-200 date">
        <span class="">{{ date }}</span>
        <span class="pl-1" :class="{'text-red-600': holidayWeekList.includes(week)}">{{ week }}</span>
    </div>
    <p class="time text-md text-cyan-700">{{ time }}</p>
  </div>
</template>
