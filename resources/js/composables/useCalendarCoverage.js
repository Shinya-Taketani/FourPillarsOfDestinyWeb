import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

export const useCalendarCoverage = () => {
    const coverage = ref(null);

    const birthDateTimeMin = computed(() => (
        coverage.value ? `${coverage.value.birth_date.min_year}-01-01T00:00` : undefined
    ));
    const birthDateTimeMax = computed(() => (
        coverage.value ? `${coverage.value.birth_date.max_year}-12-31T23:59` : undefined
    ));

    onMounted(async () => {
        try {
            const response = await axios.get('/api/calendar-coverage');
            coverage.value = response.data.data;
        } catch {
            coverage.value = null;
        }
    });

    return {
        birthDateTimeMin,
        birthDateTimeMax,
    };
};
