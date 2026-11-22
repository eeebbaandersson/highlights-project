<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import CategoryIcon from './CategoryIcon.vue'
import { highlightService } from '../service/highlightService.js'

const props = defineProps({
    highlight: {
        type: Object,
        default: null,
    },
    categories: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['close', 'updated', 'deleted'])

const isEditing = ref(false)
const isSaving = ref(false)
const isDeleting = ref(false)
const confirmDelete = ref(false)
const errorMessage = ref('')

const form = ref({
    title: '',
    date: '',
    category: '',
    description: '',
})

watch(
    () => props.highlight,
    (item) => {
        if (!item) return
        isEditing.value = false
        confirmDelete.value = false
        errorMessage.value = ''
        form.value = {
            title: item.title || '',
            date: item.date || '',
            category: item.category || '',
            description: item.description || '',
        }
    },
    { immediate: true }
)

function handleKeydown(event) {
    if (event.key === 'Escape' && props.highlight) {
        emit('close')
    }
}

onMounted(() => window.addEventListener('keydown', handleKeydown))
onUnmounted(() => window.removeEventListener('keydown', handleKeydown))

async function handleSave() {
    if (!props.highlight || !form.value.title.trim() || !form.value.date) return

    isSaving.value = true
    errorMessage.value = ''

    try {
        const updated = await highlightService.update(props.highlight.id, {
            title: form.value.title.trim(),
            date: form.value.date,
            category: form.value.category || null,
            description: form.value.description.trim() || null,
        })

        isEditing.value = false
        emit('updated', updated)
    } catch (error) {
        errorMessage.value = error?.message || 'Could not update highlight.'
    } finally {
        isSaving.value = false
    }
}

async function handleDelete() {
    if (!props.highlight) return

    const deletedId = props.highlight.id
    isDeleting.value = true
    errorMessage.value = ''

    try {
        await highlightService.remove(deletedId)
        emit('deleted', deletedId)
    } catch (error) {
        errorMessage.value = error?.message || 'Could not delete highlight.'
    } finally {
        isDeleting.value = false
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="highlight"
            class="modal-backdrop"
            @click.self="emit('close')"
        >
            <div class="modal-card" role="dialog" aria-modal="true">
                <header class="modal-header">
                    <div class="modal-meta">
                        <CategoryIcon :category="highlight.category" class="meta-icon" />
                        <span class="meta-category">{{ highlight.category || 'Uncategorized' }}</span>
                        <span class="meta-separator">·</span>
                        <span class="meta-date">{{ highlight.formattedDate || highlight.date }}</span>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        aria-label="Close"
                        @click="emit('close')"
                    >
                        ✕
                    </button>
                </header>

                <p v-if="errorMessage" class="modal-error">{{ errorMessage }}</p>

                <div v-if="!isEditing" class="modal-body">
                    <h2 class="modal-title">{{ highlight.title }}</h2>
                    <p v-if="highlight.description" class="modal-description">
                        {{ highlight.description }}
                    </p>
                    <p v-else class="modal-description is-empty">
                        No further description was added for this highlight.
                    </p>

                    <footer class="modal-footer">
                        <div class="delete-group" :class="{ 'is-confirming': confirmDelete }">
                            <button
                                v-if="!confirmDelete"
                                type="button"
                                class="btn-icon-danger"
                                aria-label="Delete highlight"
                                title="Delete highlight"
                                @click="confirmDelete = true"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="action-icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                            <template v-else>
                                <span class="confirm-text">Delete highlight?</span>
                                <div class="confirm-actions">
                                    <button
                                        type="button"
                                        class="btn-danger"
                                        :disabled="isDeleting"
                                        @click="handleDelete"
                                    >
                                        {{ isDeleting ? 'Deleting...' : 'CONFIRM' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="btn-text"
                                        @click="confirmDelete = false"
                                    >
                                        CANCEL
                                    </button>
                                </div>
                            </template>
                        </div>

                        <button
                            v-if="!confirmDelete"
                            type="button"
                            class="btn-icon"
                            aria-label="Edit highlight"
                            title="Edit highlight"
                            @click="isEditing = true"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="action-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </button>
                    </footer>
                </div>

                <form v-else class="modal-form" @submit.prevent="handleSave">
                    <label class="field">
                        <span>Title</span>
                        <input v-model="form.title" type="text" required />
                    </label>

                    <div class="field-row">
                        <label class="field">
                            <span>Category</span>
                            <select v-if="categories.length" v-model="form.category">
                                <option value="">No category</option>
                                <option
                                    v-for="cat in categories"
                                    :key="cat"
                                    :value="cat"
                                >
                                    {{ cat }}
                                </option>
                            </select>
                            <input v-else v-model="form.category" type="text" />
                        </label>
                        <label class="field">
                            <span>Date</span>
                            <input v-model="form.date" type="date" required />
                        </label>
                    </div>

                    <label class="field">
                        <span>Description</span>
                        <textarea v-model="form.description" rows="4" />
                    </label>

                    <footer class="modal-footer">
                        <button
                            type="button"
                            class="btn-text"
                            @click="isEditing = false"
                        >
                            CANCEL
                        </button>
                        <button
                            type="submit"
                            class="btn-primary"
                            :disabled="isSaving"
                        >
                            {{ isSaving ? 'Saving...' : 'SAVE' }}
                        </button>
                    </footer>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(5, 10, 14, 0.72);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    z-index: 50;
}

.modal-card {
    width: 100%;
    max-width: 460px;
    background: #0d141a;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 14px;
    padding: 1.5rem 1.75rem;
    color: rgba(255, 255, 255, 0.92);
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.1rem;
}

.modal-meta {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.72rem;
    letter-spacing: 0.08em;
    color: rgba(255, 255, 255, 0.5);
    text-transform: uppercase;
}

.meta-icon {
    width: 15px;
    height: 15px;
    color: #67e8f9;
}

.btn-close {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.45);
    cursor: pointer;
    font-size: 0.95rem;
    padding: 0.25rem;
}

.btn-close:hover {
    color: rgba(255, 255, 255, 0.9);
}

.delete-group.is-confirming {
    width: 100%;
    justify-content: space-between;
    gap: 0.6rem;
}

.confirm-actions {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 500;
    margin: 0 0 0.75rem;
    line-height: 1.35;
}

.modal-description {
    font-size: 0.9rem;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.74);
    margin: 0;
    white-space: pre-line;
}

.modal-description.is-empty {
    color: rgba(255, 255, 255, 0.36);
    font-style: italic;
}

.modal-error {
    font-size: 0.8rem;
    color: #fca5a5;
    margin: 0 0 1rem;
}

.modal-form {
    display: flex;
    flex-direction: column;
    gap: 0.95rem;
}

.field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.55);
}

.field input,
.field select,
.field textarea {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    padding: 0.55rem 0.7rem;
    color: rgba(255, 255, 255, 0.92);
    font: inherit;
    font-size: 0.85rem;
}

.field select {
    appearance: none;
    -webkit-appearance: none;
    padding-right: 2rem;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='rgba(255,255,255,0.6)'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19.5 8.25-7.5 7.5-7.5-7.5' /%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.65rem center;
    background-size: 14px 14px;
}

.field select option {
    background: #0d141a;
    color: rgba(255, 255, 255, 0.92);
}

.field textarea {
    resize: vertical;
    min-height: 92px;
    max-width: 100%;
}

.field input:focus,
.field select:focus,
.field textarea:focus {
    outline: none;
    border-color: rgba(103, 232, 249, 0.55);
}

.modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.delete-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.confirm-text {
    font-size: 0.78rem;
    color: rgba(255, 255, 255, 0.6);
}

.btn-primary,
.btn-danger,
.btn-text {
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-primary {
    background: rgba(103, 232, 249, 0.16);
    border: 1px solid rgba(103, 232, 249, 0.4);
    color: #a5f3fc;
}

.btn-primary:hover {
    background: rgba(103, 232, 249, 0.24);
}

.btn-danger {
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid rgba(239, 68, 68, 0.45);
    color: #fecaca;
}

.btn-text {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: rgba(255, 255, 255, 0.55);
}

.btn-text:hover {
    color: rgba(255, 255, 255, 0.9);
}

.action-icon {
    width: 17px;
    height: 17px;
    display: block;
}

.btn-icon,
.btn-icon-danger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-icon {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: rgba(255, 255, 255, 0.78);
}

.btn-icon:hover {
    background: rgba(255, 255, 255, 0.09);
    border-color: rgba(103, 232, 249, 0.45);
    color: #67e8f9;
}

.btn-icon-danger {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: rgba(248, 113, 113, 0.7);
}

.btn-icon-danger:hover {
    background: rgba(239, 68, 68, 0.12);
    border-color: rgba(248, 113, 113, 0.35);
    color: #fca5a5;
}
</style>
