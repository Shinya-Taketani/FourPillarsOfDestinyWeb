<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { Radar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, PointElement, LineElement, RadialLinearScale, Filler } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, PointElement, LineElement, RadialLinearScale, Filler);

// 1980年・京都(135.76)・09:00をデフォルトに設定
const form = ref({ name: '鑑定者', birthday: '1980-01-01T09:00', longitude: 135.76, gender: 'male' });
const result = ref(null);
const loading = ref(false);
const activeMonth = ref(null);

const regions = [
    { label: '北海道・東北', cities: [{n:'北海道', l:141.35}, {n:'青森', l:140.74}, {n:'岩手', l:141.15}, {n:'宮城', l:140.87}, {n:'秋田', l:140.10}, {n:'山形', l:140.34}, {n:'福島', l:140.47}] },
    { label: '関東', cities: [{n:'茨城', l:140.45}, {n:'栃木', l:139.88}, {n:'群馬', l:139.06}, {n:'埼玉', l:139.65}, {n:'千葉', l:140.12}, {n:'東京', l:139.69}, {n:'神奈川', l:139.64}] },
    { label: '中部', cities: [{n:'新潟', l:139.02}, {n:'富山', l:137.21}, {n:'石川', l:136.63}, {n:'福井', l:136.22}, {n:'山梨', l:138.57}, {n:'長野', l:138.18}, {n:'岐阜', l:136.72}, {n:'静岡', l:138.38}, {n:'愛知', l:136.91}] },
    { label: '近畿', cities: [{n:'三重', l:136.51}, {n:'滋賀', l:135.87}, {n:'京都', l:135.76}, {n:'大阪', l:135.50}, {n:'兵庫', l:135.19}, {n:'奈良', l:135.83}, {n:'和歌山', l:135.17}, {n:'明石', l:135.00}] },
    { label: '中国・四国', cities: [{n:'鳥取', l:134.24}, {n:'島根', l:133.05}, {n:'岡山', l:133.92}, {n:'広島', l:132.45}, {n:'山口', l:131.47}, {n:'徳島', l:134.56}, {n:'香川', l:134.04}, {n:'愛媛', l:132.77}, {n:'高知', l:133.53}] },
    { label: '九州・沖縄', cities: [{n:'福岡', l:130.40}, {n:'佐賀', l:130.30}, {n:'長崎', l:129.87}, {n:'熊本', l:130.74}, {n:'大分', l:131.60}, {n:'宮崎', l:131.42}, {n:'鹿児島', l:130.56}, {n:'沖縄', l:127.68}] }
];

const setCityLng = (lng) => { form.value.longitude = lng; };

const submit = async () => {
    loading.value = true;
    try {
        const response = await axios.post('/api/analyze', form.value);
        result.value = response.data.data;
    } catch (error) { alert('鑑定エラーが発生しました。'); } finally { loading.value = false; }
};

const toggleMonth = (idx) => { activeMonth.value = activeMonth.value === idx ? null : idx; };

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
            <h1 class="text-3xl font-black mb-8 text-indigo-900 border-l-8 border-indigo-600 pl-4">運命鑑定</h1>
            
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col"><span class="text-xs font-bold text-gray-400 mb-1 ml-1">氏名</span><input v-model="form.name" type="text" class="border-2 p-3 rounded-lg text-xl font-bold focus:border-indigo-500 outline-none"></div>
                        <div class="flex flex-col"><span class="text-xs font-bold text-gray-400 mb-1 ml-1">生年月日</span><input v-model="form.birthday" type="datetime-local" class="border-2 p-3 rounded-lg text-xl font-bold focus:border-indigo-500 outline-none"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col"><span class="text-xs font-bold text-gray-400 mb-1 ml-1">性別</span><select v-model="form.gender" class="border-2 p-3 rounded-lg text-xl font-bold focus:border-indigo-500 outline-none"><option value="male">男性</option><option value="female">女性</option></select></div>
                        <div class="flex flex-col"><span class="text-xs font-bold text-gray-400 mb-1 ml-1">出生地経度</span><input v-model="form.longitude" type="number" step="0.01" class="border-2 p-3 rounded-lg text-xl font-bold focus:border-indigo-500 outline-none text-indigo-600"></div>
                    </div>
                </div>

                <div class="bg-gray-100 p-6 rounded-xl border-2 border-dashed border-gray-200">
                    <span class="text-xs font-black text-indigo-400 mb-4 block uppercase tracking-widest">出生都道府県を選択 (経度自動入力)</span>
                    <div class="space-y-5 max-h-[250px] overflow-y-auto pr-2 custom-scrollbar">
                        <div v-for="region in regions" :key="region.label" class="space-y-2">
                            <div class="text-xs font-bold text-indigo-400 mb-2 border-b border-indigo-50 pb-1 uppercase">{{ region.label }}</div>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                <button v-for="city in region.cities" :key="city.n" @click="setCityLng(city.l)" 
                                    class="py-2.5 px-2 border rounded-lg text-sm font-bold transition-all shadow-sm active:scale-95"
                                    :class="form.longitude === city.l 
                                        ? 'bg-indigo-600 text-white border-indigo-600' 
                                        : 'bg-white text-gray-600 border-gray-200 hover:bg-indigo-50 hover:text-indigo-600'">
                                    {{ city.n }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button @click="submit" :disabled="loading" class="mt-8 w-full bg-indigo-600 text-white py-5 rounded-xl text-2xl font-black shadow-xl disabled:bg-gray-400 active:scale-[0.98] transition-transform">
                {{ loading ? '算出中...' : '運命を算出する' }}
            </button>
        </div>

        <div v-if="result" class="animate-in">
            <div class="bg-gradient-to-r from-red-500 to-orange-500 p-1 rounded-2xl mb-8 shadow-xl">
                <div class="bg-white p-8 rounded-xl flex flex-col lg:flex-row items-center gap-8 text-center lg:text-left">
                    <div class="shrink-0 lg:border-r-4 border-orange-100 pr-0 lg:pr-8">
                        <div class="text-2xl font-black text-orange-500 uppercase">Yearly 2026</div>
                        <div class="text-7xl font-serif font-black text-red-600 my-2 leading-none">{{ result.saiun.kanji }}</div>
                        <div class="inline-block px-6 py-2 bg-red-600 text-white text-3xl rounded-xl font-black">{{ result.saiun.ten_god }}</div>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-2xl font-black text-gray-400 mb-2">2026年の運勢テーマ</h2>
                        <p class="text-4xl font-bold text-gray-700 leading-relaxed italic">“ {{ result.appraisal.saiun_comment }} ”</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-md mb-8 border-t-8 border-orange-500">
                <h2 class="text-3xl font-black mb-8 border-l-8 border-orange-500 pl-4 text-orange-900">2026年 月運</h2>
                <div class="space-y-6">
                    <div v-for="(m, idx) in result.getsuun" :key="idx" @click="toggleMonth(idx)" class="cursor-pointer group">
                        <div class="flex flex-col lg:flex-row bg-orange-50 rounded-2xl p-6 gap-6 border-2 border-orange-100 shadow-sm hover:bg-orange-100 transition">
                            <div class="flex items-center gap-6 w-full lg:w-80 shrink-0 lg:border-r-2 border-orange-200 pr-0 lg:pr-6 text-center">
                                <div class="w-24 font-black text-4xl text-orange-600">{{ m.month_name }}</div>
                                <div class="flex-1"><div class="text-5xl font-serif font-black text-gray-900 mb-2 leading-none">{{ m.kanji }}</div><div class="px-4 py-1 bg-orange-600 text-white text-lg rounded-lg font-black shadow-md">{{ m.ten_god }}</div></div>
                            </div>
                            <div class="w-full bg-white p-6 rounded-xl border-2 border-orange-50 flex items-center shadow-inner">
                                <p class="text-2xl font-bold text-gray-700 italic flex-1 leading-relaxed text-center lg:text-left">“ {{ result.appraisal.getsuun_comments[idx]?.comment }} ”</p>
                                <span class="text-orange-400 font-black text-xl ml-4">{{ activeMonth === idx ? '▲ 閉じる' : '▼ 日運' }}</span>
                            </div>
                        </div>
                        <div v-if="activeMonth === idx" class="mt-4 bg-white rounded-2xl border-4 border-orange-200 p-8 grid grid-cols-1 md:grid-cols-2 gap-6 animate-in">
                            <div v-for="d in m.days" :key="d.day" class="flex items-center gap-6 p-6 bg-gray-50 rounded-2xl border-2 shadow-sm">
                                <div class="w-16 text-center font-black text-3xl text-orange-600">{{ d.day }}日</div>
                                <div class="text-4xl font-serif font-black text-gray-900 leading-none">{{ d.kanji }}</div>
                                <div class="px-4 py-1 bg-indigo-600 text-white text-lg rounded-lg font-black shadow-sm text-center min-w-[80px]">{{ d.ten_god }}</div>
                                <div class="flex-1 text-xl font-bold text-gray-600">{{ result.appraisal.nichiun_meanings[d.ten_god] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8 text-center">
                <div class="lg:col-span-2 bg-white p-8 rounded-xl shadow-md border-t-8 border-indigo-500">
                    <h2 class="text-3xl font-black mb-6 text-gray-700">基本命式</h2>
                    <div class="grid grid-cols-4 gap-4">
                        <div v-for="(p, k) in result.pillars" :key="k" class="p-6 rounded-2xl bg-gray-50 border-2 shadow-sm flex flex-col items-center">
                            <div class="text-xs font-bold text-gray-400 mb-2 uppercase">{{ k }}</div>
                            <div class="text-5xl font-serif font-black mb-4 text-indigo-950 leading-none">{{ p.kanji }}</div>
                            <div class="w-full bg-indigo-100 text-indigo-700 text-xl font-black p-2 rounded-lg mb-3 shadow-sm">{{ p.ten_god.name }}</div>
                            <div class="w-full my-3 border-y-2 py-3 bg-white text-xl font-black text-emerald-700 shadow-sm"><span class="text-xs text-gray-400 block font-normal">蔵干</span>{{ p.zokan.ten_god_name }}</div>
                            <div class="text-xl text-orange-600 font-black">{{ p.twelve_life_stage.name }}</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-xl shadow-md border-t-8 border-emerald-500 text-center flex flex-col">
                    <h2 class="text-3xl font-black mb-6">五行力量</h2>
                    <div class="flex-1 min-h-[320px]"><Radar v-if="chartData" :data="chartData" :options="chartOptions" /></div>
                </div>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-md mb-8 border-t-8 border-indigo-600">
                <h2 class="text-3xl font-black mb-8 border-l-8 border-indigo-600 pl-4 flex justify-between items-center text-indigo-900">
                    <span>一生の歩み (大運)</span><span class="text-xl font-bold text-indigo-600 bg-indigo-50 px-4 py-1 rounded-lg shadow-inner">立運：{{ result.dayun.start_age_full }}</span>
                </h2>
                <div class="space-y-6">
                    <div v-for="(c, i) in result.dayun.cycles" :key="i" class="flex flex-col lg:flex-row items-stretch bg-indigo-50 rounded-2xl p-6 gap-6 border-2 border-indigo-100 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-6 w-full lg:w-80 shrink-0 lg:border-r-2 border-indigo-200 pr-0 lg:pr-6 text-center lg:text-left">
                            <div class="w-24 text-center font-black text-4xl text-indigo-700">{{ c.age }}歳〜</div>
                            <div class="flex-1 text-center"><div class="text-5xl font-serif font-black text-gray-900 mb-2 leading-none">{{ c.kanji }}</div><div class="px-4 py-1 bg-indigo-600 text-white text-lg rounded-lg font-black shadow-md text-center">{{ c.ten_god }}</div></div>
                        </div>
                        <div class="w-full bg-white p-6 rounded-xl border-2 border-indigo-50 flex items-center shadow-inner text-2xl font-bold text-gray-700 leading-relaxed italic text-center lg:text-left">“ {{ result.appraisal.dayun_comments[i]?.comment }} ”</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div v-for="(v, k) in {性格:result.appraisal.personality, 仕事:result.appraisal.work, 助言:result.appraisal.balance}" :key="k" class="p-8 rounded-2xl border-l-8 shadow-lg bg-white" :class="{'border-indigo-600':k==='性格','border-emerald-600':k==='仕事','border-amber-600':k==='助言'}">
                    <h3 class="text-2xl font-black mb-4">{{ k }}の本質</h3><p class="text-2xl font-bold text-gray-700 leading-loose">{{ v }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-in { animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
