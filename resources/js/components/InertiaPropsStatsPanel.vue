<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { t } from '@/lib/i18n.js';
import { ChevronLeft } from 'lucide-vue-next';

const props = usePage().props
const isExpanded = ref(false)
const showAnimation = ref(false)

onMounted(() => {
    if (shouldShow.value) {
        showAnimation.value = true
    }
})

const shouldShow = computed(() => {
    if (props.app.env === 'production') {
        return false
    }

    if (props._inertiaPayloadTotalSizeInKb === undefined || props._inertiaPayloadTotalSizeInKb === null) {
        return false
    }

    return true
})

const hasErrors = computed(() => {
    return hasDuplicateKeys.value || isPayloadExceeded.value
})

const hasDuplicateKeys = computed(() => {
    return props._inertiaPayloadDuplicateKeys !== null && props._inertiaPayloadDuplicateKeys !== ''
})

const isPayloadExceeded = computed(() => {
    return props._inertiaPayloadExceededInKb > 0
})

const errorCount = computed(() => {
    let count = 0
    if (hasDuplicateKeys.value) count++
    if (isPayloadExceeded.value) count++
    return count
})

watch(() => props._inertiaPayloadTotalSizeInKb, (newValue) => {
    if (newValue && shouldShow.value) {
        console.table({
            'Inertia Payload Total Size (KB)': props._inertiaPayloadTotalSizeInKb.toFixed(2),
            'Inertia Payload Component Size (KB)': props._inertiaPayloadComponentSizeInKb.toFixed(2),
            'Inertia Payload Threshold (KB)': props._inertiaPayloadThresholdInKb.toFixed(2),
            'Inertia Payload Exceeded By (KB)': props._inertiaPayloadExceededInKb.toFixed(2),
            'Inertia Same Keys': props._inertiaPayloadDuplicateKeys,
        })

        // Trigger animation on new page load
        showAnimation.value = false
        setTimeout(() => {
            showAnimation.value = true
        }, 100)
    }
})
</script>

<template>
    <div v-if="hasErrors && shouldShow" class="fixed right-0 top-1/2 -translate-y-1/2 z-50">
        <!-- Tab/Label -->
        <button
            v-if="!isExpanded"
            @click="isExpanded = true"
            class="bg-red-400 hover:bg-red-500 text-white px-0.5 py-3 rounded-l-lg shadow-lg hover:bg-gray-700 transition-colors flex flex-col items-center gap-2 cursor-pointer"
            :class="{ 'animate-slide-peek': showAnimation }"
        >
            <ChevronLeft />
        </button>

        <!-- Expanded Panel -->
        <div
            v-if="isExpanded"
            class="bg-white border dark:bg-gray-800 shadow-xl rounded-l-lg w-96 max-h-[80vh] overflow-hidden flex flex-col"
        >
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    {{ t('Inertia Payload Stats') }}
                </h3>
                <button
                    @click="isExpanded = false"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="overflow-y-auto p-4 space-y-4">
                <!-- Errors -->
                <div v-if="hasErrors" class="space-y-3">
                    <!-- Duplicate Keys Error -->
                    <div v-if="hasDuplicateKeys" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="font-medium text-red-900 dark:text-red-200">
                                    {{ t('Same key in shared and page props!') }}
                                </p>
                                <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                                    {{ t('You use the same key(s) in the Inertia shared props and the page props! Key(s): :keys', {
                                        keys: props._inertiaPayloadDuplicateKeys,
                                    }) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Payload Exceeded Error -->
                    <div v-if="isPayloadExceeded" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="font-medium text-red-900 dark:text-red-200">
                                    {{ t('Inertia page props too large!') }}
                                </p>
                                <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                                    {{ t('The current Inertia page props size is :size KB, which exceeds the recommended maximum of :maxSize KB by :exceededBy KB.', {
                                        size: props._inertiaPayloadTotalSizeInKb.toFixed(2),
                                        maxSize: props._inertiaPayloadThresholdInKb.toFixed(2),
                                        exceededBy: props._inertiaPayloadExceededInKb.toFixed(2),
                                    }) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Table -->
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr>
                                <td class="py-2 text-gray-600 dark:text-gray-400">{{ t('Total Size') }}</td>
                                <td class="py-2 text-right font-medium text-gray-900 dark:text-white">
                                    {{ props._inertiaPayloadTotalSizeInKb.toFixed(2) }} KB
                                </td>
                            </tr>
                            <tr>
                                <td class="py-2 text-gray-600 dark:text-gray-400">{{ t('Component Size') }}</td>
                                <td class="py-2 text-right font-medium text-gray-900 dark:text-white">
                                    {{ props._inertiaPayloadComponentSizeInKb.toFixed(2) }} KB
                                </td>
                            </tr>
                            <tr>
                                <td class="py-2 text-gray-600 dark:text-gray-400">{{ t('Threshold') }}</td>
                                <td class="py-2 text-right font-medium text-gray-900 dark:text-white">
                                    {{ props._inertiaPayloadThresholdInKb.toFixed(2) }} KB
                                </td>
                            </tr>
                            <tr v-if="props._inertiaPayloadExceededInKb > 0">
                                <td class="py-2 text-gray-600 dark:text-gray-400">{{ t('Exceeded By') }}</td>
                                <td class="py-2 text-right font-medium text-red-600 dark:text-red-400">
                                    {{ props._inertiaPayloadExceededInKb.toFixed(2) }} KB
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes slide-peek {
    0%, 100% {
        padding-right: 0.125rem;
    }
    15%, 35% {
        padding-right: calc(0.125rem + 24px);
    }
    25%, 45% {
        padding-right: 0.125rem;
    }
}

.animate-slide-peek {
    animation: slide-peek 2s ease-in-out;
}
</style>
