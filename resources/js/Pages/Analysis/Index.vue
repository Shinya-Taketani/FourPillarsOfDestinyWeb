<script setup>
import { ref } from 'vue';
import axios from 'axios';

const form = ref({
    name: '',
    birthday: '2026-04-25T10:00',
    longitude: 135.45
});

const result = ref(null);
const loading = ref(false);

const submit = async () => {
    loading.value = true;
    try {
        const response = await axios.post('/api/analyze', form.value);
        result.value = response.data.data;
    } catch (error) {
        alert('鑑定に失敗しました');
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="max-w-4xl mx-auto p-6 bg-gray-50 min-h-screen">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 border-b-2 border-indigo-500 pb-2">泰山流 四柱推命鑑定</h1>

        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">お名前</label>
                    <input v-model="form.name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">生年月日</label>
                    <input v-model="form.birthday" type="datetime-local" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">出生地の経度</label>
                    <input v-model="form.longitude" type="number" step="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
            <button @click="submit" :disabled="loading" class="mt-6 w-full py-3 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition">
                {{ loading ? '鑑定中...' : '運命を算出する' }}
            </button>
        </div>

        <div v-if="result" class="grid grid-cols-1 md:grid-cols-2 gap-8 animate-fade-in">
            <div class="bg-white p-6 rounded-xl shadow-md border-t-4 border-indigo-500">
                <h2 class="text-xl font-bold mb-4">基本命式</h2>
                <div class="grid grid-cols-4 gap-2 text-center">
                    <div v-for="(pillar, key) in result.pillars" :key="key" class="border p-2 rounded bg-gray-50">
                        <div class="text-xs text-gray-500">{{ key }}</div>
                        <div class="text-2xl font-serif font-bold text-indigo-900">{{ pillar.kanji }}</div>
                        <div class="text-xs text-indigo-600">{{ pillar.ten_god.name }}</div>
                        <div class="text-xs text-orange-600">{{ pillar.twelve_life_stage.name }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md border-t-4 border-green-500">
                <h2 class="text-xl font-bold mb-4">五行バランス</h2>
                <div class="space-y-3">
                    <div v-for="item in result.five_elements_scores" :key="item.element">
                        <div class="flex justify-between text-sm mb-1">
                            <span>{{ item.element }}</span>
                            <span class="font-bold">{{ item.score }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full" :style="{ width: (item.score * 10) + '%', backgroundColor: item.color }"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
