import { ref } from 'vue';

export function useValidationErrors() {
    const validationErrors = ref({});
    const generalError = ref('');

    const clearErrors = () => {
        validationErrors.value = {};
        generalError.value = '';
    };

    const getFieldError = (field) => validationErrors.value[field]?.[0] ?? '';

    const getAnyFieldError = (fields) => {
        for (const field of fields) {
            const message = getFieldError(field);
            if (message) return message;
        }

        return '';
    };

    const hasFieldError = (field) => Boolean(getFieldError(field));

    const readBlobError = async (data) => {
        if (!(data instanceof Blob)) return data;

        try {
            return JSON.parse(await data.text());
        } catch {
            return null;
        }
    };

    const setErrorsFromAxiosError = async (error, fallbackMessage = '通信エラーが発生しました。') => {
        clearErrors();

        const status = error?.response?.status;
        const data = await readBlobError(error?.response?.data);

        if (status === 422 && data?.errors) {
            validationErrors.value = data.errors;
            generalError.value = data.message ?? '入力内容を確認してください。';
            return;
        }

        generalError.value = data?.message ?? fallbackMessage;
    };

    return {
        validationErrors,
        generalError,
        clearErrors,
        getFieldError,
        getAnyFieldError,
        hasFieldError,
        setErrorsFromAxiosError,
    };
}
