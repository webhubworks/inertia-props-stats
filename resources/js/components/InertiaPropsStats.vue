<script setup>
import { onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { toast } from 'vue-sonner';
import { t } from '@/lib/i18n.js';

const props = usePage().props

watch(() => props._inertiaPayloadTotalSizeInKb, (newValue) => {
    if (newValue) {
        warnAboutPayloadExceeded()
    }
})

onMounted(() => {
    warnAboutPayloadExceeded()
})

const warnAboutPayloadExceeded = () => {
    if(props.app.env === 'production') {
        return
    }

    if(props._inertiaPayloadTotalSizeInKb === undefined || props._inertiaPayloadTotalSizeInKb === null){
        return
    }

    console.table({
        'Inertia Payload Total Size (KB)': props._inertiaPayloadTotalSizeInKb.toFixed(2),
        'Inertia Payload Component Size (KB)': props._inertiaPayloadComponentSizeInKb.toFixed(2),
        'Inertia Payload Threshold (KB)': props._inertiaPayloadThresholdInKb.toFixed(2),
        'Inertia Payload Exceeded By (KB)': props._inertiaPayloadExceededInKb.toFixed(2),
        'Inertia Same Keys': props._inertiaPayloadSameKeys,
    })

    if(props._inertiaPayloadSameKeys !== null && props._inertiaPayloadSameKeys !==''){
        toast.error(t('Same key in shared and page props!'), {
            description: ''
                + t('You use the same key(s) in the Inertia shared props and the page props! Key(s): :keys', {
                    keys: props._inertiaPayloadSameKeys,
                })
        });
    }

    if(props._inertiaPayloadExceededInKb <= 0){
        return
    }

    toast.error(t('Inertia page props too large!'), {
        description: ''
            + t('The current Inertia page props size is :size KB, which exceeds the recommended maximum of :maxSize KB by :exceededBy KB.', {
                size: props._inertiaPayloadTotalSizeInKb.toFixed(2),
                maxSize: props._inertiaPayloadThresholdInKb.toFixed(2),
                exceededBy: props._inertiaPayloadExceededInKb.toFixed(2),
            })
    });
}
</script>

<template>

</template>
