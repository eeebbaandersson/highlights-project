<script setup>
defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    categories: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:modelValue'])

function onChange(event) {
    emit('update:modelValue', event.target.value)
}
</script>

<template>
    <div
        class="category-filter"
        :class="{ 'is-active': Boolean(modelValue) }"
    >
        <label for="category-filter-select" class="filter-label">
            Filter by category
        </label>
        <div class="filter-row">
            <div class="select-wrapper">
                <select
                    id="category-filter-select"
                    :value="modelValue"
                    class="category-select"
                    @change="onChange"
                >
                    <option value="">Show All</option>
                    <option
                        v-for="category in categories"
                        :key="category"
                        :value="category"
                    >
                        {{ category }}
                    </option>
                </select>
                <svg
                    class="select-chevron"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 16 16"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4 6 4 4 4-4" />
                </svg>
            </div>

            <button
                v-if="modelValue"
                type="button"
                class="btn-reset"
                aria-label="Reset category filter"
                title="Reset filter"
                @click="emit('update:modelValue', '')"
            >
                Reset
            </button>
        </div>
    </div>
</template>

<style scoped>
.category-filter {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.45rem;
}

.filter-label {
    font-size: 0.60rem;
    font-weight: 500;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.38);
    padding-right: 2px;
    transition: color 0.2s ease;
}

.category-filter.is-active .filter-label {
    color: rgba(103, 232, 249, 0.72);
}

.filter-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.select-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
}

.category-select,
.btn-reset {
    box-sizing: border-box;
    height: 36px;
    border-radius: 10px;
    font: inherit;
    font-size: 0.80rem;
    letter-spacing: 0.04em;
    line-height: 1;
    transition: all 0.2s ease;
}

.category-select {
    appearance: none;
    background-color: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.12);
    min-width: 144px;
    padding: 0 2rem 0 0.8rem;
    color: rgba(255, 255, 255, 0.85);
    cursor: pointer;
}

.category-filter.is-active .category-select {
    border-color: rgba(103, 232, 249, 0.35);
    background-color: rgba(103, 232, 249, 0.05);
    color: #67e8f9;
}

.category-select:hover,
.category-select:focus-visible {
    background-color: rgba(255, 255, 255, 0.08);
    border-color: rgba(103, 232, 249, 0.45);
    color: #67e8f9;
    outline: none;
}

.category-select option {
    background: #0d141a;
    color: rgba(255, 255, 255, 0.9);
}

.select-chevron {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    width: 13px;
    height: 13px;
    color: rgba(255, 255, 255, 0.55);
    pointer-events: none;
    transition: color 0.2s ease;
}

.category-filter.is-active .select-chevron,
.select-wrapper:hover .select-chevron,
.category-select:focus-visible + .select-chevron {
    color: #67e8f9;
}

.btn-reset {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.85rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: rgba(255, 255, 255, 0.65);
    cursor: pointer;
}

.btn-reset:hover,
.btn-reset:focus-visible {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(103, 232, 249, 0.45);
    color: #67e8f9;
    outline: none;
}
</style>
