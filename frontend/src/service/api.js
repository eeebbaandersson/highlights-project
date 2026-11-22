const BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api'

async function request(endpoint, options = {}) {
    const config = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...options.headers,
        },
        ...options,
    }

    if (config.body && typeof config.body === 'object') {
        config.body = JSON.stringify(config.body)
    }

    const response = await fetch(`${BASE_URL}${endpoint}`, config)

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        throw new Error(errorData?.error || errorData?.message || `HTTP-error: ${response.status}`)
    }

    if (response.status === 204) return null

    return response.json()
}

export const api = {
    get: (endpoint) => request(endpoint, { method: 'GET' }),
    post: (endpoint, body) => request(endpoint, { method: 'POST', body }),
    put: (endpoint, body) => request(endpoint, { method: 'PUT', body }),
    delete: (endpoint) => request(endpoint, { method: 'DELETE' }),
}
