<script setup>
import { ref, watch, nextTick, onBeforeUnmount } from 'vue'
import CategoryIcon from './CategoryIcon.vue'
import { highlightService } from '../service/highlightService.js'

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    defaultYear: {
        type: Number,
        default: new Date().getFullYear(),
    },
})

const emit = defineEmits(['close', 'created'])

const isSaving = ref(false)
const errorMessage = ref('')
let formSession = 0

const dialogEl = ref(null)
const titleEl = ref(null)
let opener = null
let openRequest = 0

const form = ref({
    title: '',
    date: '',
    category: '',
    description: '',
})

function getInitialDate() {
    const now = new Date()
    const month = String(now.getMonth() + 1).padStart(2, '0')
    const day = String(now.getDate()).padStart(2, '0')
    const year = props.defaultYear || now.getFullYear()
    return `${year}-${month}-${day}`
}

function resetForm() {
    errorMessage.value = ''
    form.value = {
        title: '',
        date: getInitialDate(),
        category: '',
        description: '',
    }
}

function restoreFocus() {
    if (opener?.isConnected && typeof opener.focus === 'function') {
        opener.focus()
    }
    opener = null
}

watch(
    () => props.open,
    async (isOpen) => {
        const request = ++openRequest
        formSession += 1

        if (!isOpen) {
            if (dialogEl.value?.open) dialogEl.value.close()
            restoreFocus()
            return
        }

        opener = document.activeElement
        resetForm()
        isSaving.value = false
        await nextTick()

        if (request !== openRequest || !props.open || !dialogEl.value) return

        if (!dialogEl.value.open) dialogEl.value.showModal()
        titleEl.value?.focus()
    },
    { immediate: true, flush: 'sync' }
)

function closeModal() {
    formSession += 1
    isSaving.value = false
    emit('close')
}

function handleDialogClick(event) {
    if (event.target !== dialogEl.value) return

    const rect = dialogEl.value.getBoundingClientRect()
    if (
        event.clientX < rect.left || event.clientX > rect.right ||
        event.clientY < rect.top || event.clientY > rect.bottom
    ) {
        closeModal()
    }
}

async function handleCreate() {
    if (isSaving.value || !form.value.title.trim() || !form.value.date) return

    const requestSession = formSession
    const isCurrentSession = () =>
        props.open && requestSession === formSession

    isSaving.value = true
    errorMessage.value = ''

    try {
        const created = await highlightService.create({
            title: form.value.title.trim(),
            date: form.value.date,
            category: form.value.category || null,
            description: form.value.description.trim() || null,
        })

        emit('created', created)
        if (isCurrentSession()) closeModal()
    } catch (error) {
        if (isCurrentSession()) {
            errorMessage.value = error?.message || 'Could not create highlight.'
        }
    } finally {
        if (isCurrentSession()) isSaving.value = false
    }
}

onBeforeUnmount(() => {
    ++openRequest
    formSession += 1
    if (dialogEl.value?.open) dialogEl.value.close()
    restoreFocus()
})
</script>

<template>
    <Teleport to="body">
        <dialog
            ref="dialogEl"
            class="modal-card"
            aria-label="New highlight"
            @cancel.prevent="closeModal()"
            @click="handleDialogClick"
        >
            <header class="modal-header">
                <div class="modal-meta">
                    <CategoryIcon :category="form.category" class="meta-icon" />
                    <span class="meta-category">New highlight</span>
                </div>
                <button
                    type="button"
                    class="btn-close"
                    aria-label="Close"
                    @click="closeModal()"
                >
                    ✕
                </button>
            </header>

            <p v-if="errorMessage" class="modal-error">{{ errorMessage }}</p>

            <form class="modal-form" @submit.prevent="handleCreate">
                <label class="field">
                    <span>Title</span>
                    <input
                        ref="titleEl"
                        v-model="form.title"
                        type="text"
                        required
                    />
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
                    <textarea
                        v-model="form.description"
                        rows="4"
                        placeholder="Optional notes..."
                    />
                </label>

                <footer class="modal-footer">
                    <button
                        type="button"
                        class="btn-text"
                        @click="closeModal()"
                    >
                        CANCEL
                    </button>
                    <button
                        type="submit"
                        class="btn-primary"
                        :disabled="isSaving"
                    >
                        {{ isSaving ? 'Creating...' : 'CREATE' }}
                    </button>
                </footer>
            </form>
        </dialog>
    </Teleport>
</template>

<style scoped>
.modal-card {
    box-sizing: border-box;
    width: calc(100vw - 2rem);
    max-width: 460px;
    max-height: calc(100dvh - 2rem);
    margin: auto;
    overflow-y: auto;
    background: #0d141a;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 14px;
    padding: 1.5rem 1.75rem;
    color: rgba(255, 255, 255, 0.92);
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
}

.modal-card::backdrop {
    background: rgba(5, 10, 14, 0.72);
    backdrop-filter: blur(6px);
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
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 0.75rem;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-width: 0;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.55);
}

.field input,
.field select,
.field textarea {
    box-sizing: border-box;
    width: 100%;
    min-width: 0;
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
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.btn-primary,
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

.btn-text {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: rgba(255, 255, 255, 0.55);
}

.btn-text:hover {
    color: rgba(255, 255, 255, 0.9);
}

@media (max-width: 520px) {
    .modal-card {
        padding: 1.25rem 1.15rem;
    }

    .field-row {
        grid-template-columns: 1fr;
    }
}
</style>
