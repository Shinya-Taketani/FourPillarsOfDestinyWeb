<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { Radar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, PointElement, LineElement, RadialLinearScale, Filler } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, PointElement, LineElement, RadialLinearScale, Filler);

// 修正：1980年・京都(135.76)・09:00をデフォルトに設定
const person1 = ref({ name: '自分', birthday: '1980-01-01T09:00', longitude: 135.76, gender: 'male' });
const person2 = ref({ name: '相手', birthday: '1980-01-01T09:00', longitude: 135.76, gender: 'female' });
const result = ref(null);
const loading = ref(false);

const regions = [
    { label: '北海道・東北', cities: [{n:'北海道', l:141.35}, {n:'青森', l:140.74}, {n:'岩手', l:141.15}, {n:'宮城', l:140.87}, {n:'秋田', l:140.10}, {n:'山形', l:140.34}, {n:'福島', l:140.47}] },
    { label: '関東', cities: [{n:'茨城', l:140.45}, {n:'栃木', l:139.88}, {n:'群馬', l:139.06}, {n:'埼玉', l:139.65}, {n:'千葉', l:140.12}, {n:'東京', l:139.69}, {n:'神奈川', l:139.64}] },
    { label: '中部', cities: [{n:'新潟', l:139.02}, {n:'富山', l:137.21}, {n:'石川', l:136.63}, {n:'福井', l:136.22}, {n:'山梨', l:138.57}, {n:'長野', l:138.18}, {n:'岐阜', l:136.72}, {n:'静岡', l:138.38}, {n:'愛知', l:136.91}] },
    { label: '近畿', cities: [{n:'三重', l:136.51}, {n:'滋賀', l:135.87}, {n:'京都', l:135.76}, {n:'大阪', l:135.50}, {n:'兵庫', l:135.19}, {n:'奈良', l:135.83}, {n:'和歌山', l:135.17}, {n:'明石', l:135.00}] },
    { label: '中国・四国', cities: [{n:'鳥取', l:134.24}, {n:'島根', l:133.05}, {n:'岡山', l:133.92}, {n:'広島', l:132.45}, {n:'山口', l:131.47}, {n:'徳島', l:134.56}, {n:'香川', l:134.04}, {n:'愛媛', l:132.77}, {n:'高知', l:133.53}] },
    { label: '九州・沖縄', cities: [{n:'福岡', l:130.40}, {n:'佐賀', l:130.30}, {n:'長崎', l:129.87}, {n:'熊本', l:130.74}, {n:'大分', l:131.60}, {n:'宮崎', l:131.42}, {n:'鹿児島', l:130.56}, {n:'沖縄', l:127.68}] }
];

const setCityLng = (p, l) => { p.longitude = l; };

const submit = async () => {
    loading.value = true;
    try {
        const response = await axios.post('/api/analyze-compatibility', { person1: person1.value, person2: person2.value });
        result.value = response.data.data;
    } catch (error) { alert('鑑定エラーが発生しました。'); } finally { loading.value = false; }
};

const chartData = computed(() => {
    if (!result.value) return null;
    return {
        labels: result.value.person1.five_elements_scores.map(s => s.element),
        datasets: [
            { label: person1.value.name, data: result.value.person1.five_elements_scores.map(s => s.score), backgroundColor: 'rgba(79, 70, 229, 0.2)', borderColor: 'indigo', borderWidth: 2 },
            { label: person2.value.name, data: result.value.person2.five_elements_scores.map(s => s.score), backgroundColor: 'rgba(220, 38, 38, 0.2)', borderColor: 'red', borderWidth: 2 }
        ]
    };
});
const chartOptions = { responsive: true, maintainAspectRatio: false, scales: { r: { suggestedMin: 0, suggestedMax: 10 } } };
</script>

<template>
    <Head title="相性精密鑑定" />
    <div class="max-w-7xl mx-auto p-6 bg-gray-50 min-h-screen font-sans">
        <h1 class="text-4xl font-black text-indigo-900 mb-8 border-l-8 border-indigo-600 pl-4 uppercase">相性精密鑑定</h1>
        
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
            <div v-for="(p, i) in [person1, person2]" :key="i" class="bg-white p-8 rounded-2xl shadow-xl border-t-8 transition-all hover:shadow-2xl" :class="i===0?'border-indigo-500':'border-red-500'">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="font-black text-2xl text-gray-700 tracking-wider">{{ i===0?'鑑定者（自分）':'お相手' }}</h2>
                    <span class="px-4 py-1 rounded-full text-xs font-bold" :class="i===0?'bg-indigo-100 text-indigo-600':'bg-red-100 text-red-600'">Target {{ i+1 }}</span>
                </div>
                
                <div class="space-y-4 mb-6">
                    <div class="flex flex-col"><span class="text-xs font-bold text-gray-400 mb-1 ml-1">氏名</span><input v-model="p.name" type="text" class="border-2 p-3 rounded-xl text-xl font-bold focus:border-indigo-500 outline-none"></div>
                    <div class="flex flex-col"><span class="text-xs font-bold text-gray-400 mb-1 ml-1">生年月日</span><input v-model="p.birthday" type="datetime-local" class="border-2 p-3 rounded-xl text-xl font-bold focus:border-indigo-500 outline-none"></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col"><span class="text-xs font-bold text-gray-400 mb-1 ml-1">性別</span><select v-model="p.gender" class="border-2 p-3 rounded-xl text-xl font-bold focus:border-indigo-500 outline-none"><option value="male">男性</option><option value="female">女性</option></select></div>
                        <div class="flex flex-col"><span class="text-xs font-bold text-gray-400 mb-1 ml-1">経度</span><input v-model="p.longitude" type="number" step="0.01" class="border-2 p-3 rounded-xl text-xl font-bold focus:border-indigo-500 outline-none text-indigo-600"></div>
                    </div>
                </div>

                <div class="bg-gray-100 p-6 rounded-2xl border-2 border-dashed border-gray-200">
                    <span class="text-xs font-black text-gray-400 mb-4 block uppercase tracking-widest">都道府県を選択（経度セット）</span>
                    <div class="space-y-5 max-h-[250px] overflow-y-auto pr-2 custom-scrollbar">
                        <div v-for="region in regions" :key="region.label">
                            <div class="text-xs font-bold text-indigo-400 mb-2 border-b border-indigo-50 pb-1">{{ region.label }}</div>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                <button v-for="city in region.cities" :key="city.n" @click="setCityLng(p, city.l)" 
                                    class="py-2.5 px-2 border rounded-lg text-sm font-bold transition-all shadow-sm active:scale-95"
                                    :class="p.longitude === city.l ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 hover:bg-indigo-50 hover:text-indigo-600'">
                                    {{ city.n }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button @click="submit" :disabled="loading" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-6 rounded-2xl text-3xl font-black shadow-2xl transition-all active:scale-[0.98] disabled:bg-gray-300">
            {{ loading ? '精密相性算出中...' : '二人の相性を精密鑑定する' }}
        </button>

        <div v-if="result" class="mt-4">
            <form :action="route('compatibility.pdf')" method="POST" target="_blank">
                <input type="hidden" name="_token" :value="$page.props.csrf_token">
                <!-- 1人目のデータ -->
                <input type="hidden" name="person1[name]" :value="person1.name">
                <input type="hidden" name="person1[birthday]" :value="person1.birthday">
                <input type="hidden" name="person1[longitude]" :value="person1.longitude">
                <input type="hidden" name="person1[gender]" :value="person1.gender">
                <!-- 2人目のデータ -->
                <input type="hidden" name="person2[name]" :value="person2.name">
                <input type="hidden" name="person2[birthday]" :value="person2.birthday">
                <input type="hidden" name="person2[longitude]" :value="person2.longitude">
                <input type="hidden" name="person2[gender]" :value="person2.gender">

                <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white py-6 rounded-2xl text-2xl font-black shadow-xl transition-all active:scale-[0.98] flex items-center justify-center gap-3">
                    <span>鑑定書をPDFで保存する</span>
                </button>
            </form>
        </div>

        <div v-if="result" class="mt-12 space-y-10 animate-in">
            <div class="text-center bg-white p-12 rounded-[40px] shadow-2xl border-b-[16px] border-indigo-600 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-10 font-black text-9xl text-indigo-950 uppercase italic">Compass</div>
                <div class="relative z-10">
                    <div class="text-gray-400 font-black uppercase tracking-[0.3em] mb-4">Total Compatibility Score</div>
                    <div class="text-[12rem] font-black text-indigo-600 leading-none mb-6 drop-shadow-lg">{{ result.compatibility.total_score }}<span class="text-4xl text-gray-300 ml-2">pts</span></div>
                    <p class="text-4xl font-bold text-gray-700 italic drop-shadow-sm">“ {{ result.compatibility.summary }} ”</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div class="bg-white p-10 rounded-[30px] shadow-xl border-t-8 border-emerald-500">
                    <h3 class="text-2xl font-black mb-8 text-gray-700 tracking-widest text-center uppercase">五行エネルギーの共鳴</h3>
                    <div class="h-[400px]"><Radar :data="chartData" :options="chartOptions" /></div>
                </div>

                <div class="space-y-6">
                    <div v-for="(rel, key) in result.compatibility.details" :key="key" 
                         class="p-8 rounded-[30px] bg-white shadow-xl border-l-[12px] transition-transform hover:scale-[1.02]" 
                         :class="rel.score > 0 ? 'border-emerald-500' : 'border-orange-500'">
                        <div class="flex justify-between items-start mb-6">
                            <div class="text-xs font-black text-gray-400 uppercase tracking-widest">{{ key === 'year_relation' ? 'ルーツ' : (key === 'stem_relation' ? '精神' : '活力') }}の相性</div>
                            <span class="text-2xl font-black" :class="rel.score > 0 ? 'text-emerald-600' : 'text-orange-600'">
                                {{ rel.score > 0 ? '＋' : '' }}{{ rel.score }}
                            </span>
                        </div>
                        <div class="text-3xl font-black mb-6 text-indigo-950">{{ rel.name }}</div>
                        
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-6 rounded-2xl border-2 border-gray-100 shadow-inner">
                                <span class="text-[10px] font-black text-gray-400 block mb-2 uppercase tracking-tighter">【 結 論 】</span>
                                <p class="text-xl font-bold text-gray-700 leading-relaxed">{{ rel.conclusion }}</p>
                            </div>
                            <div class="p-6 rounded-2xl border-2 border-indigo-50 bg-indigo-50/10">
                                <span class="text-[10px] font-black text-indigo-300 block mb-2 uppercase tracking-tighter">【 助 言 】</span>
                                <p class="text-xl font-bold text-indigo-600 leading-relaxed italic">“ {{ rel.advice }} ”</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-in { animation: fadeIn 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
