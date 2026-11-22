import { api } from './api'

function normalizeDate(dateValue) {
    if (!dateValue) return ''
    if (typeof dateValue === 'string') return dateValue.slice(0, 10)
    if (typeof dateValue === 'object' && dateValue.date) {
        return String(dateValue.date).slice(0, 10)
    }
    return String(dateValue)
}

function normalizeHighlight(item) {
    return {
        id: item.id,
        date: normalizeDate(item.date),
        title: item.title,
        description: item.description ?? null,
        category: typeof item.category === 'string'
            ? item.category
            : item.category?.name ?? '',
    }
}

export const highlightService = {
    async getAll() {
        const data = await api.get('/highlights')
        const list = Array.isArray(data) ? data : (data.items ?? data.data ?? [])

        return list.map(normalizeHighlight)
    },

    async create(payload) {
        const created = await api.post('/highlights', payload)
        return created ? normalizeHighlight(created) : null
    },

    async update(id, payload) {
        const updated = await api.put(`/highlights/${id}`, payload)
        return updated ? normalizeHighlight(updated) : null
    },

    async remove(id) {
        return api.delete(`/highlights/${id}`)
    },
}
