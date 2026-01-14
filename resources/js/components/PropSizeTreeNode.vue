<script setup lang="ts">
import { computed, ref } from 'vue'

interface PropSizeNode {
    key: string
    path: string
    type: 'object' | 'array' | 'scalar'
    ownSizeKb: number
    totalSizeKb: number
    childCount: number
    children: PropSizeNode[]
    truncated?: boolean
}

const props = defineProps<{
    node: PropSizeNode
    rootTotalSizeKb: number
    depth?: number
}>()

const depth = computed(() => props.depth ?? 0)

// All nodes start collapsed, except root
const isExpanded = ref(depth.value === 0)

const hasChildren = computed(() => props.node.children.length > 0)

const isExpandable = computed(() => props.node.type !== 'scalar' && hasChildren.value)

// Calculate size percentage relative to root total
const sizePercentage = computed(() => {
    if (props.rootTotalSizeKb === 0) return 0
    return (props.node.totalSizeKb / props.rootTotalSizeKb) * 100
})

// Color based on size percentage (green < 10%, yellow 10-30%, red > 30%)
const sizeColorClass = computed(() => {
    const pct = sizePercentage.value
    if (pct >= 30) return 'text-red-600 dark:text-red-400'
    if (pct >= 10) return 'text-amber-600 dark:text-amber-400'
    return 'text-green-600 dark:text-green-400'
})

const barColorClass = computed(() => {
    const pct = sizePercentage.value
    if (pct >= 30) return 'bg-red-500'
    if (pct >= 10) return 'bg-amber-500'
    return 'bg-green-500'
})

// Type icon based on node type
const typeIcon = computed(() => {
    switch (props.node.type) {
        case 'array':
            return '[]'
        case 'object':
            return '{}'
        default:
            return '·'
    }
})

const typeLabel = computed(() => {
    switch (props.node.type) {
        case 'array':
            return `Array(${props.node.childCount})`
        case 'object':
            return `Object(${props.node.childCount})`
        default:
            return ''
    }
})

function formatSize(kb: number): string {
    if (kb >= 1) {
        return `${kb.toFixed(2)} KB`
    }
    const bytes = Math.round(kb * 1024)
    return `${bytes} B`
}

function toggle() {
    if (isExpandable.value) {
        isExpanded.value = !isExpanded.value
    }
}
</script>

<template>
    <div class="select-none">
        <!-- Node row -->
        <div
            class="flex items-center gap-1 py-0.5 px-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700/50 group"
            :style="{ paddingLeft: `${depth * 12 + 4}px` }"
        >
            <!-- Expand/collapse icon -->
            <button
                v-if="isExpandable"
                @click="toggle"
                class="w-4 h-4 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 shrink-0"
            >
                <svg
                    class="w-3 h-3 transition-transform duration-150"
                    :class="{ 'rotate-90': isExpanded }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <span v-else class="w-4 shrink-0"></span>

            <!-- Type indicator -->
            <span class="text-xs font-mono text-gray-400 dark:text-gray-500 w-5 shrink-0">{{ typeIcon }}</span>

            <!-- Key name -->
            <span
                class="font-mono text-xs text-gray-800 dark:text-gray-200 truncate cursor-pointer"
                :class="{ 'font-semibold': depth === 0 }"
                @click="toggle"
                :title="node.path || 'root'"
            >
                {{ node.key }}
            </span>

            <!-- Type label for arrays/objects -->
            <span v-if="typeLabel" class="text-xs text-gray-400 dark:text-gray-500 shrink-0">
                {{ typeLabel }}
            </span>

            <!-- Spacer -->
            <div class="flex-1 min-w-2"></div>

            <!-- Size bar (visual indicator) -->
            <div class="w-12 h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden shrink-0">
                <div
                    class="h-full rounded-full transition-all duration-300"
                    :class="barColorClass"
                    :style="{ width: `${Math.min(100, sizePercentage)}%` }"
                ></div>
            </div>

            <!-- Size value -->
            <span
                class="text-xs font-mono tabular-nums w-16 text-right shrink-0"
                :class="sizeColorClass"
            >
                {{ formatSize(node.totalSizeKb) }}
            </span>
        </div>

        <!-- Children (recursive) -->
        <div v-if="isExpandable && isExpanded" class="border-l border-gray-200 dark:border-gray-700 ml-2">
            <PropSizeTreeNode
                v-for="child in node.children"
                :key="child.path"
                :node="child"
                :root-total-size-kb="rootTotalSizeKb"
                :depth="depth + 1"
            />
        </div>

        <!-- Truncation indicator -->
        <div
            v-if="node.truncated && isExpanded"
            class="text-xs text-gray-400 dark:text-gray-500 italic py-0.5"
            :style="{ paddingLeft: `${(depth + 1) * 12 + 24}px` }"
        >
            (max depth reached)
        </div>
    </div>
</template>
