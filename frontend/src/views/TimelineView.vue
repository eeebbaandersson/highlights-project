<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import CategoryIcon from '../components/CategoryIcon.vue'
import { highlightService } from '../service/highlightService.js'
import HighlightDetailModal from "../components/HighlightDetailModal.vue";
import CreateHighlightModal from "../components/CreateHighlightModal.vue";
import CategoryFilter from "../components/CategoryFilter.vue";

const currentYear = new Date().getFullYear()
const selectedYear = ref(currentYear)
const highlights = ref([])
const isLoading = ref(true)
const errorMessage = ref('')
const selectedCategory = ref('')

const locallyCreated = new Map()
let pendingChanges = null

const selectedHighlight = ref(null)
const isCreateOpen = ref(false)

const defaultCategories = [
    'Achievement',
    'Career',
    'Celebration',
    'Concert',
    'Education',
    'Family',
    'Home',
    'Milestone',
    'Personal',
    'Project',
    'SmallWin',
    'Training',
    'Travel',
]

const availableCategories = computed(() => {
    const fromHighlights = highlights.value
        .map((item) => item.category)
        .filter(Boolean)
    return [...new Set([...defaultCategories, ...fromHighlights])]
})

watch(availableCategories, (categories) => {
    if (selectedCategory.value && !categories.includes(selectedCategory.value)) {
        selectedCategory.value = ''
    }
})

const months = [
    { index: 1, label: 'JAN' },
    { index: 2, label: 'FEB' },
    { index: 3, label: 'MAR' },
    { index: 4, label: 'APR' },
    { index: 5, label: 'MAY' },
    { index: 6, label: 'JUN' },
    { index: 7, label: 'JUL' },
    { index: 8, label: 'AUG' },
    { index: 9, label: 'SEP' },
    { index: 10, label: 'OCT' },
    { index: 11, label: 'NOV' },
    { index: 12, label: 'DEC' },
]

function parseDateParts(isoDate) {
    if (!isoDate) return { year: null, month: null, day: null }
    const [year, month, day] = String(isoDate).split('-').map(Number)
    return { year, month, day }
}

const availableYears = computed(() => {
    const years = new Set([currentYear])

    for (const item of highlights.value) {
        const { year } = parseDateParts(item.date)
        if (Number.isInteger(year) && year > 0) {
            years.add(year)
        }
    }

    return [...years].sort((a, b) => a - b)
})

const currentYearIndex = computed(() =>
    availableYears.value.indexOf(selectedYear.value)
)

const canGoPrevYear = computed(() => currentYearIndex.value > 0)

const canGoNextYear = computed(() =>
    currentYearIndex.value !== -1 &&
    currentYearIndex.value < availableYears.value.length - 1
)

function goToPrevYear() {
    if (canGoPrevYear.value) {
        selectedYear.value = availableYears.value[currentYearIndex.value - 1]
    }
}

function goToNextYear() {
    if (canGoNextYear.value) {
        selectedYear.value = availableYears.value[currentYearIndex.value + 1]
    }
}

watch(availableYears, (years) => {
    if (!years.length) return
    if (!years.includes(selectedYear.value)) {
        selectedYear.value = years.includes(currentYear)
            ? currentYear
            : years[years.length - 1]
    }
}, { immediate: true })

async function fetchHighlights() {
    if (pendingChanges) return

    const changes = new Map()
    pendingChanges = changes
    isLoading.value = true

    try {
        const data = await highlightService.getAll()
        const merged = new Map(data.map((item) => [item.id, item]))

        for (const [id, item] of locallyCreated) {
            if (merged.has(id) && !changes.has(id)) {
                locallyCreated.delete(id)
            } else {
                merged.set(id, item)
            }
        }

        for (const [id, item] of changes) {
            if (item === null) {
                merged.delete(id)
            } else {
                merged.set(id, item)
            }
        }

        highlights.value = [...merged.values()]
        errorMessage.value = ''
    } catch (error) {
        errorMessage.value = error?.message || 'Could not fetch timeline.'
        console.error('[TimelineView] Error during fetching highlights', error)
    } finally {
        pendingChanges = null
        isLoading.value = false
    }
}

onMounted(fetchHighlights)

const highlightsByMonth = computed(() => {
    const grouped = {}
    for (let m = 1; m <= 12; m++) {
        grouped[m] = []
    }

    highlights.value.forEach((item) => {
        const { year, month, day } = parseDateParts(item.date)
        if (year === selectedYear.value && grouped[month]) {
            const monthLabel = months[month - 1]?.label || ''
            const isMatch =
                !selectedCategory.value || item.category === selectedCategory.value

            grouped[month].push({
                ...item,
                day,
                formattedDate: `${String(day).padStart(2, '0')} ${monthLabel}`,
                isMatch,
                isDimmed: Boolean(selectedCategory.value) && !isMatch,
            })
        }
    })

    Object.keys(grouped).forEach((m) => {
        grouped[m].sort((a, b) => a.day - b.day)
    })

    return grouped
})

const hasHighlightsForSelectedYear = computed(() =>
    Object.values(highlightsByMonth.value).some((items) => items.length > 0)
)

function handleHighlightCreated(createdItem) {
    if (!createdItem) return

    locallyCreated.set(createdItem.id, createdItem)
    pendingChanges?.set(createdItem.id, createdItem)

    highlights.value = [
        ...highlights.value.filter((item) => item.id !== createdItem.id),
        createdItem,
    ]

    const { year } = parseDateParts(createdItem.date)
    if (Number.isInteger(year) && year > 0) {
        selectedYear.value = year
    }

    if (errorMessage.value && !pendingChanges) {
        void fetchHighlights()
    }
}

function handleHighlightUpdated(updatedItem) {
    if (!updatedItem) return

    if (locallyCreated.has(updatedItem.id)) {
        locallyCreated.set(updatedItem.id, updatedItem)
    }
    pendingChanges?.set(updatedItem.id, updatedItem)

    highlights.value = highlights.value.map((item) =>
        item.id === updatedItem.id ? updatedItem : item
    )
    selectedHighlight.value = updatedItem
}

function handleHighlightDeleted(deletedId) {
    locallyCreated.delete(deletedId)
    pendingChanges?.set(deletedId, null)

    highlights.value = highlights.value.filter((item) => item.id !== deletedId)

    if (selectedHighlight.value?.id === deletedId) {
        selectedHighlight.value = null
    }
}
</script>

<template>
    <main class="timeline-page">
        <header class="timeline-header">
            <div class="title-group">
                <div class="year-switcher">
                    <button
                        type="button"
                        class="year-nav-btn"
                        :disabled="!canGoPrevYear"
                        aria-label="Previous year"
                        title="Previous year"
                        @click="goToPrevYear"
                    >
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m10 3.5-4.5 4.5 4.5 4.5" />
                        </svg>
                    </button>

                    <h1>{{ selectedYear }}
                        <span class="overview">OVERVIEW</span>
                    </h1>

                    <button
                        type="button"
                        class="year-nav-btn"
                        :disabled="!canGoNextYear"
                        aria-label="Next year"
                        title="Next year"
                        @click="goToNextYear"
                    >
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 3.5 4.5 4.5-4.5 4.5" />
                        </svg>
                    </button>
                </div>
            </div>
            <CategoryFilter
                v-model="selectedCategory"
                :categories="availableCategories"
            />

                <button
                    type="button"
                    class="btn-add-highlight"
                    aria-label="Create highlight"
                    title="Create highlight"
                    @click="isCreateOpen = true"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="action-icon"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </button>

        </header>

        <p v-if="isLoading" class="timeline-status" role="status">
            Loading highlights…
        </p>

        <div v-if="errorMessage" class="timeline-error" role="alert">
            <div class="error-copy">
                <span class="error-title">Could not load all highlights</span>
                <span class="error-detail">{{ errorMessage }}</span>
                <span class="error-detail">Some highlights may be missing.</span>
            </div>
            <button
                type="button"
                class="btn-retry"
                :disabled="isLoading"
                @click="fetchHighlights"
            >
                Retry
            </button>
        </div>

        <p
            v-if="!isLoading && !errorMessage && !hasHighlightsForSelectedYear"
            class="timeline-status"
        >
            No highlights found for {{ selectedYear }}.
        </p>

        <section v-if="hasHighlightsForSelectedYear" class="timeline-scroll-container">
            <div class="timeline-grid">
                <div class="axis-line"></div>

                <div
                    v-for="(month, idx) in months"
                    :key="month.index"
                    class="month-column"
                    :class="idx % 2 === 0 ? 'is-top' : 'is-bottom'"
                >
                    <div class="month-marker">
                        <span class="month-label">{{ month.label }}</span>
                    </div>

                    <div
                        v-if="highlightsByMonth[month.index].length"
                        class="stem-wrapper"
                        :class="{
                            'is-inactive-filter':
                            Boolean(selectedCategory) &&
                            !highlightsByMonth[month.index].some((entry) => entry.isMatch),
                            }"
                    >
                        <div class="axis-dot"></div>
                        <div class="stem-line"></div>

                        <div class="items-stack">
                            <button
                                v-for="(item, itemIdx) in highlightsByMonth[month.index]"
                                :key="item.id"
                                type="button"
                                class="highlight-item"
                                :class="{ 'is-dimmed': item.isDimmed }"
                                :style="{ '--stagger': idx * 2 + itemIdx }"
                                @click="selectedHighlight = item"
                            >
                                <CategoryIcon :category="item.category" class="item-icon" />
                                <span class="item-content">
                                    <span class="item-date">{{ item.formattedDate }}</span>
                                    <span class="item-title">{{ item.title }}</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <CreateHighlightModal
        :open="isCreateOpen"
        :categories="availableCategories"
        :default-year="selectedYear"
        @close="isCreateOpen = false"
        @created="handleHighlightCreated"
    />
    <HighlightDetailModal
        :highlight="selectedHighlight"
        :categories="availableCategories"
        @close="selectedHighlight = null"
        @updated="handleHighlightUpdated"
        @deleted="handleHighlightDeleted"
    />
</template>

<style scoped>
.timeline-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    padding: 2.5rem 3.5rem;
    color: rgba(255, 255, 255, 0.92);
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 2rem;
}

.timeline-header h1 {
    font-size: 2rem;
    font-weight: 400;
    letter-spacing: -0.02em;
    margin: 0;
}

.year-switcher {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
}

.year-nav-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    padding: 0;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: rgba(255, 255, 255, 0.35);
    cursor: pointer;
    transition: color 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
}

.year-nav-btn svg {
    width: 24px;
    height: 24px;
    display: block;
}

.year-nav-btn:hover:not(:disabled),
.year-nav-btn:focus-visible:not(:disabled) {
    color: #67e8f9;
    background: rgba(255, 255, 255, 0.04);
    outline: none;
}

.year-nav-btn:disabled {
    opacity: 0.15;
    cursor: default;
    pointer-events: none;
}

.overview {
    color: rgba(255, 255, 255, 0.45);
}

.btn-add-highlight {
    position: fixed;
    right: 3.5rem;
    bottom: 2.5rem;
    z-index: 30;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(13, 20, 26, 0.88);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.14);
    color: rgba(255, 255, 255, 0.88);
    width: 44px;
    height: 44px;
    border-radius: 12px;
    cursor: pointer;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
    transition: all 0.2s ease;
}

.btn-add-highlight:hover {
    background: rgba(255, 255, 255, 0.09);
    border-color: rgba(103, 232, 249, 0.45);
    color: #67e8f9;
}

.action-icon {
    width: 17px;
    height: 17px;
    display: block;
}

.timeline-status {
    margin: 0;
    padding: 1.5rem 0;
    font-size: 0.85rem;
    letter-spacing: 0.04em;
    color: rgba(255, 255, 255, 0.45);
}

.timeline-error {
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: 2.5rem;
    max-width: 480px;
    align-self: flex-start;
    padding: 1.15rem 1.4rem;
    margin-bottom: 1.5rem;
    background: rgba(34, 22, 26, 0.72);
    border: 1px solid rgba(248, 113, 113, 0.18);
    border-radius: 14px;
}

.error-copy {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    text-align: left;
}

.error-title {
    font-size: 0.9rem;
    font-weight: 500;
    color: rgba(252, 165, 165, 0.85);
}

.error-detail {
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.42);
}

.btn-retry {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 10px;
    padding: 0.65rem 1.15rem;
    font: inherit;
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.86);
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.15s ease;
}

.btn-retry:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(103, 232, 249, 0.4);
    color: #67e8f9;
}

.timeline-scroll-container {
    flex: 1;
    display: flex;
    align-items: center;
    overflow-x: auto;
    padding: 2rem 0;
}

.timeline-grid {
    position: relative;
    display: grid;
    grid-template-columns: repeat(12, minmax(110px, 1fr));
    width: 100%;
    min-width: 1200px;
    height: 540px;
}

.axis-line {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(
        90deg,
        rgba(255, 255, 255, 0.04) 0%,
        rgba(255, 255, 255, 0.18) 15%,
        rgba(255, 255, 255, 0.18) 85%,
        rgba(255, 255, 255, 0.04) 100%
    );
}

.month-column {
    position: relative;
    height: 100%;
}

.month-marker {
    position: absolute;
    top: calc(50% + 10px);
    left: 0.6rem;
    background: transparent;
    padding: 0 0.35rem;
    z-index: 1;
}

.month-column.is-bottom .month-marker {
    top: auto;
    bottom: calc(50% + 10px);
}

.month-label {
    font-size: 0.65rem;
    letter-spacing: 0.35em;
    color: rgba(255, 255, 255, 0.36);
}

.stem-wrapper {
    position: absolute;
    left: 1.25rem;
    display: flex;
    flex-direction: column;
    z-index: 2;
}

.month-column.is-top .stem-wrapper {
    bottom: 50%;
    flex-direction: column-reverse;
}

.month-column.is-bottom .stem-wrapper {
    top: 50%;
    flex-direction: column;
}

.axis-dot {
    position: absolute;
    left: 0;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #67e8f9;
    box-shadow: 0 0 8px rgba(103, 232, 249, 0.55);
    z-index: 3;
}

.axis-dot,
.stem-line {
    transition: opacity 0.25s ease;
}

.stem-wrapper.is-inactive-filter .axis-dot,
.stem-wrapper.is-inactive-filter .stem-line {
    opacity: 0.18;
}

.stem-wrapper.is-inactive-filter:has(.highlight-item:hover) .axis-dot,
.stem-wrapper.is-inactive-filter:has(.highlight-item:hover) .stem-line {
    opacity: 0.75;
}

.month-column.is-top .axis-dot {
    bottom: 0;
    transform: translate(-50%, 50%);
}

.month-column.is-bottom .axis-dot {
    top: 0;
    transform: translate(-50%, -50%);
}

.stem-line {
    width: 0;
    height: 48px;
    border-left: 1px dashed rgba(255, 255, 255, 0.22);
    margin-left: -0.5px;
}

.items-stack {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    padding: 0.35rem 0;
    margin-left: -1px;
}

.month-column.is-top .items-stack {
    flex-direction: column-reverse;
}

@keyframes highlightSoftEnter {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.highlight-item {
    display: flex;
    align-items: flex-start;
    gap: 0.50rem;
    margin-left: -5px;
    cursor: pointer;
    padding: 0;
    border: 0;
    background: transparent;
    color: inherit;
    font: inherit;
    text-align: left;
    opacity: 0;
    animation: highlightSoftEnter 1.8s ease-out forwards;
    --stagger: 0;
    animation-delay: calc(min(var(--stagger, 0), 12) * 25ms);
    transition: transform 0.2s ease;
}

.highlight-item:focus-visible {
    outline: 2px solid #67e8f9;
    outline-offset: 3px;
}

.highlight-item:hover {
    transform: scale(1.02);
}

.highlight-item .item-icon,
.highlight-item .item-content {
    transition: opacity 0.25s ease, color 0.2s ease;
}

.highlight-item.is-dimmed .item-icon,
.highlight-item.is-dimmed .item-content {
    opacity: 0.12;
}

.highlight-item.is-dimmed:hover .item-icon,
.highlight-item.is-dimmed:hover .item-content,
.highlight-item.is-dimmed:focus-visible .item-icon,
.highlight-item.is-dimmed:focus-visible .item-content {
    opacity: 0.92;
}

.item-icon {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    margin-top: 12px;
    margin-left: -2px;
    color: rgba(255, 255, 255, 0.42);
    transition: color 0.2s ease;
}

.highlight-item:hover .item-icon {
    color: #67e8f9;
    opacity: 1;
}

.item-content {
    width: 165px;
}

.item-date {
    display: block;
    font-size: 0.60rem;
    letter-spacing: 0.12em;
    color: rgba(255, 255, 255, 0.44);
    margin-bottom: 0.1rem;
    transition: color 0.2s ease;
}

.highlight-item:hover .item-date {
    color: #67e8f9;
}

.item-title {
    display: block;
    font-size: 0.80rem;
    font-weight: 500;
    line-height: 1.4;
    margin: 0;
    color: rgba(255, 255, 255, 0.70);
    transition: color 0.2s ease;
}

.highlight-item:hover .item-title {
    color: rgba(255, 255, 255, 0.96);
}

/* Mobile view */
@media (max-width: 768px) {
    .timeline-page {
        padding: 1.5rem 1.25rem 5.5rem;
        overflow-x: hidden;
    }

    .year-switcher {
        gap: 0.5rem;
    }

    .year-nav-btn {
        width: 24px;
        height: 24px;
    }

    .year-nav-btn svg {
        width: 16px;
        height: 16px;
    }

    .btn-add-highlight {
        right: 1.25rem;
        bottom: calc(1.25rem + env(safe-area-inset-bottom, 0px));
        width: 42px;
        height: 42px;
        padding: 0;
        flex-shrink: 0;
    }

    .timeline-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.9rem;
        margin-bottom: 1.5rem;
        margin-top: calc(1rem + env(safe-area-inset-top, 0px));
    }

    .title-group {
        min-width: 0;
    }

    .timeline-header h1 {
        font-size: 1.35rem;
    }

    .timeline-scroll-container {
        display: block;
        overflow-x: visible;
        padding: 0.5rem 0;
    }

    .timeline-grid {
        display: flex;
        flex-direction: column;
        width: 100%;
        min-width: 0;
        height: auto;
        gap: 0.25rem;
        padding-left: 2.25rem;
    }

    .axis-line {
        top: 0;
        bottom: 0;
        left: 0.65rem;
        right: auto;
        width: 1px;
        height: auto;
        background: linear-gradient(
            180deg,
            rgba(255, 255, 255, 0.04) 0%,
            rgba(255, 255, 255, 0.18) 12%,
            rgba(255, 255, 255, 0.18) 88%,
            rgba(255, 255, 255, 0.04) 100%
        );
    }

    .month-column {
        height: auto;
        padding: 0.65rem 0;
    }

    .month-column.is-top .stem-wrapper,
    .month-column.is-bottom .stem-wrapper {
        position: relative;
        top: auto;
        bottom: auto;
        left: 0;
        flex-direction: row;
        align-items: flex-start;
        margin-top: 0.6rem;
    }

    .month-column.is-top .items-stack,
    .month-column.is-bottom .items-stack {
        flex-direction: column;
        gap: 0.85rem;
        padding: 0;
        margin-left: 0.5rem;
        margin-top: -1.5px;
    }

    .month-marker,
    .month-column.is-bottom .month-marker {
        position: relative;
        top: auto;
        bottom: auto;
        left: 0;
        transform: none;
        padding: 0;
    }

    .month-column.is-top .axis-dot,
    .month-column.is-bottom .axis-dot {
        top: 1.15rem;
        bottom: auto;
        left: -1.6rem;
        transform: translate(-50%, -50%);
    }

    .stem-line {
        width: 18px;
        height: 0;
        border-left: none;
        border-top: 1px dashed rgba(255, 255, 255, 0.22);
        margin-left: -1.6rem;
        margin-top: 1.15rem;
        flex-shrink: 0;
    }

    .item-content {
        flex: 1;
        width: auto;
        min-width: 0;
    }

    .highlight-item:hover {
        transform: translatex(2px);
    }
}

@media (prefers-reduced-motion: reduce) {
    .highlight-item {
        opacity: 1;
        animation: none;
    }
}
</style>
