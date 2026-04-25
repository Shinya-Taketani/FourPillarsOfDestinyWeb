<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { Radar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, PointElement, LineElement, RadialLinearScale, Filler } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, PointElement, LineElement, RadialLinearScale, Filler);

const form = ref({ name: '鑑定者', birthday: '1980-01-01T12:00', longitude: 135.45, gender: 'male' });
const result = ref(null);
const loading = ref(false);

const submit = async () => {
    loading.value = true;
    try {
        const response = await axios.post('/api/analyze', form.value);
        result.value = response.data.data;
    } catch (error) { alert('鑑定エラーが発生しました。'); } finally { loading.value = false; }
};

const chartData = computed(() => {
    if (!result.value) return null;
    return {
        labels: result.value.five_elements_scores.map(s => s.element),
        datasets: [{
            label: '五行力量',
            data: result.value.five_elements_scores.map(s => s.score),
            backgroundColor: 'rgba(79, 70, 229, 0.2)', borderColor: 'rgba(79, 70, 229, 1)', borderWidth: 3,
            pointBackgroundColor: 'rgba(79, 70, 229, 1)',
        }]
    };
});
const chartOptions = { responsive: true, maintainAspectRatio: false, scales: { r: { suggestedMin: 0, suggestedMax: 10, ticks: { stepSize: 2 } } } };
</script>

<template>
    <div class="max-w-7xl mx-auto p-6 bg-gray-50 min-h-screen text-gray-800">
        <div class="bg-white p-8 rounded-xl shadow-lg mb-8 border-b-8 border-indigo-600">
            <h1 class="text-3xl font-black mb-8 text-indigo-900 border-l-8 border-indigo-600 pl-4">泰山流 運命鑑定</h1>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <input v-model="form.name" type="text" class="border-2 p-3 rounded-lg text-xl font-bold">
                <input v-model="form.birthday" type="datetime-local" class="border-2 p-3 rounded-lg text-xl font-bold">
                <select v-model="form.gender" class="border-2 p-3 rounded-lg text-xl font-bold"><option value="male">男性</option><option value="female">女性</option></select>
                <input v-model="form.longitude" type="number" step="0.01" class="border-2 p-3 rounded-lg text-xl font-bold">
            </div>
            <button @click="submit" :disabled="loading" class="mt-8 w-full bg-indigo-600 text-white py-5 rounded-xl text-2xl font-black shadow-xl active:scale-95 disabled:bg-gray-400">
                {{ loading ? '算出中...' : '運命を算出する' }}
            </button>
        </div>

        <div v-if="result" class="animate-in">
            <div class="bg-gradient-to-r from-red-500 to-orange-500 p-1 rounded-2xl mb-8 shadow-xl">
                <div class="bg-white p-8 rounded-xl flex flex-col lg:flex-row items-center gap-8">
                    <div class="text-center shrink-0 border-r-0 lg:border-r-4 border-orange-100 pr-0 lg:pr-8">
                        <div class="text-2xl font-black text-orange-500 uppercase">Yearly 2026</div>
                        <div class="text-7xl font-serif font-black text-red-600 my-2">{{ result.saiun.kanji }}</div>
                        <div class="inline-block px-6 py-2 bg-red-600 text-white text-3xl rounded-xl font-black shadow-lg">{{ result.saiun.ten_god }}</div>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-2xl font-black text-gray-400 mb-2">2026年の運勢テーマ</h2>
                        <p class="text-3xl font-bold text-gray-700 leading-relaxed italic">“ {{ result.appraisal.saiun_comment }} ”</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-md mb-8 border-t-8 border-orange-500">
                <h2 class="text-3xl font-black mb-8 border-l-8 border-orange-500 pl-4">2026年 月々の運勢 (月運)</h2>
                <div class="space-y-6">
                    <div v-for="(m, idx) in result.getsuun" :key="idx" class="flex flex-col lg:flex-row items-stretch bg-orange-50 rounded-2xl p-6 gap-6 border-2 border-orange-100 shadow-sm">
                        <div class="flex items-center gap-6 w-full lg:w-80 shrink-0 lg:border-r-2 border-orange-200 pr-0 lg:pr-6">
                            <div class="w-24 text-center"><div class="text-4xl font-black text-orange-600">{{ m.month_name }}</div></div>
                            <div class="flex-1 text-center"><div class="text-5xl font-serif font-black text-gray-900 mb-2">{{ m.kanji }}</div><div class="px-4 py-1 bg-orange-600 text-white text-lg rounded-lg font-black shadow-md">{{ m.ten_god }}</div></div>
                        </div>
                        <div class="w-full bg-white p-6 rounded-xl border-2 border-orange-50 flex items-center shadow-inner">
                            <p class="text-2xl font-bold text-gray-700 leading-relaxed italic">“ {{ result.appraisal.getsuun_comments[idx]?.comment }} ”</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <div class="lg:col-span-2 bg-white p-8 rounded-xl shadow-md border-t-8 border-indigo-500 text-center">
                    <h2 class="text-3xl font-black mb-6">基本命式</h2>
                    <div class="grid grid-cols-4 gap-4">
                        <div v-for="(p, k) in result.pillars" :key="k" class="p-6 rounded-2xl bg-gray-50 border-2">
                            <div class="text-xs font-bold text-gray-400 mb-2 uppercase">{{ k }}</div>
                            <div class="text-5xl font-serif font-black mb-4 text-indigo-950">{{ p.kanji }}</div>
                            <div class="bg-indigo-100 text-indigo-700 text-xl font-black p-2 rounded-lg mb-3 shadow-sm">{{ p.ten_god.name }}</div>
                            <div class="my-3 border-y-2 py-3 bg-white text-xl font-black text-emerald-700"><span class="text-xs text-gray-400 block font-normal">蔵干</span>{{ p.zokan.ten_god_name }}</div>
                            <div class="text-xl text-orange-600 font-black">{{ p.twelve_life_stage.name }}</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-xl shadow-md border-t-8 border-emerald-500 text-center">
                    <h2 class="text-3xl font-black mb-6 text-center">五行力量</h2>
                    <div class="h-80"><Radar v-if="chartData" :data="chartData" :options="chartOptions" /></div>
                </div>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-md mb-8 border-t-8 border-indigo-600">
                <h2 class="text-3xl font-black mb-8 border-l-8 border-indigo-600 pl-4 flex justify-between items-center">
                    <span>一生の歩み (大運)</span><span class="text-xl font-bold text-gray-400 italic">{{ result.dayun.start_age }}歳立運</span>
                </h2>
                <div class="space-y-6">
                    <div v-for="(c, i) in result.dayun.cycles" :key="i" class="flex flex-col lg:flex-row items-stretch bg-indigo-50 rounded-2xl p-6 gap-6 border-2 border-indigo-100 shadow-sm">
                        <div class="flex items-center gap-6 w-full lg:w-80 shrink-0 lg:border-r-2 border-indigo-200 pr-0 lg:pr-6">
                            <div class="w-24 text-center"><div class="text-4xl font-black text-indigo-700">{{ c.age }}歳〜</div></div>
                            <div class="flex-1 text-center"><div class="text-5xl font-serif font-black text-gray-900 mb-2">{{ c.kanji }}</div><div class="px-4 py-1 bg-indigo-600 text-white text-lg rounded-lg font-black shadow-md">{{ c.ten_god }}</div></div>
                        </div>
                        <div class="w-full bg-white p-6 rounded-xl border-2 border-indigo-50 flex items-center shadow-inner">
                            <p class="text-2xl font-bold text-gray-700 leading-relaxed italic">“ {{ result.appraisal.dayun_comments[i]?.comment }} ”</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div v-for="(v, k) in {性格:result.appraisal.personality, 仕事:result.appraisal.work, 助言:result.appraisal.balance}" :key="k" class="p-8 rounded-2xl border-l-8 shadow-lg bg-white" :class="{'border-indigo-600':k==='性格','border-emerald-600':k==='仕事','border-amber-600':k==='助言'}">
                    <h3 class="text-2xl font-black mb-4">{{ k }}の本質</h3><p class="text-xl font-bold text-gray-700 leading-loose">{{ v }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-in { animation: fadeIn 1s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
</style>
