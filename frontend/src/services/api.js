const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api'

export function getAuthToken() {
    return localStorage.getItem('agri_auth_token')
}

export function setAuthToken(token) {
    localStorage.setItem('agri_auth_token', token)
}

export function clearAuthToken() {
    localStorage.removeItem('agri_auth_token')
}

async function request(endpoint, options = {}) {
    const token = getAuthToken()
    const isFormData = options.body instanceof FormData

    const headers = {
        Accept: 'application/json',
        ...(options.headers || {}),
    }

    if (!isFormData) {
        headers['Content-Type'] = 'application/json'
    }

    if (token) {
        headers['Authorization'] = `Bearer ${token}`
    }

    const primaryUrl = `${API_BASE_URL}${endpoint}`

    try {
        let response = await fetch(primaryUrl, { ...options, headers })

        // Removed dangerous hardcoded localhost 404 fallback here

        const data = await response.json().catch(() => ({}))

        if (!response.ok) {
            if (response.status === 401) {
                clearAuthToken()
                localStorage.removeItem('agri_active_role')
            }
            const errorMessage = data?.error || data?.message || `HTTP ${response.status}: Server request failed`
            throw new Error(errorMessage)
        }

        return data
    } catch (err) {
        // If the fetch completely fails due to CORS or offline error, bubble it up cleanly.
        throw err
    }
}

export function normalizeEthiopianPhone(phone) {
    const raw = phone.trim()
    const digits = raw.replace(/[^\d]/g, '')

    if (/^0[79]\d{8}$/.test(digits)) {
        return '+251' + digits.substring(1)
    } else if (/^251[79]\d{8}$/.test(digits)) {
        return '+' + digits
    } else if (/^[79]\d{8}$/.test(digits)) {
        return '+251' + digits
    }
    return raw
}

export const api = {
    async requestOtp(phone) {
        const normalizedPhone = normalizeEthiopianPhone(phone)
        return request('/auth/request-otp', {
            method: 'POST',
            body: JSON.stringify({ phone: normalizedPhone }),
        })
    },

    async login(phone, password) {
        const normalizedPhone = normalizeEthiopianPhone(phone)
        return request('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ phone: normalizedPhone, password }),
        })
    },

    async register(data) {
        const normalizedData = {
            ...data,
            phone: normalizeEthiopianPhone(data.phone),
        }
        return request('/auth/register', {
            method: 'POST',
            body: JSON.stringify(normalizedData),
        })
    },

    async fetchCurrentUser() {
        return request('/user')
    },

    async updateProfile(data) {
        const isFormData = data instanceof FormData
        return request('/profile', {
            method: isFormData ? 'POST' : 'PUT',
            body: isFormData ? data : JSON.stringify(data),
        })
    },

    async submitCapabilityApplication(data) {
        return request('/capability-applications', {
            method: 'POST',
            body: JSON.stringify(data),
        })
    },

    async fetchMyCapabilityApplications() {
        return request('/capability-applications/my')
    },

    async fetchMyListings() {
        return request('/listings/my')
    },

    async fetchPublicListings(params) {
        const queryStr = params ? new URLSearchParams(params).toString() : ''
        return request(`/listings${queryStr ? `?${queryStr}` : ''}`)
    },

    async createListing(listingData) {
        const isFormData = listingData instanceof FormData
        return request('/listings', {
            method: 'POST',
            body: isFormData ? listingData : JSON.stringify(listingData),
        })
    },

    async updateListing(id, listingData) {
        const isFormData = listingData instanceof FormData
        return request(`/listings/${id}`, {
            method: isFormData ? 'POST' : 'PUT',
            body: isFormData ? listingData : JSON.stringify(listingData),
        })
    },

    async deleteListing(id) {
        return request(`/listings/${id}`, { method: 'DELETE' })
    },

    async fetchMyFulfillments() {
        return request('/fulfillments')
    },

    async updateFulfillmentStatus(id, statusAction, note) {
        return request(`/fulfillments/${id}/${statusAction}`, {
            method: 'POST',
            body: JSON.stringify({ note }),
        })
    },

    async fetchMyOrders() {
        return request('/orders')
    },

    async cancelOrder(orderId) {
        return request(`/orders/${orderId}`, { method: 'DELETE' })
    },

    async checkoutOrder(payload) {
        return request('/orders/checkout', {
            method: 'POST',
            body: JSON.stringify(payload || {}),
        })
    },

    async initiateOrderPayment(orderId) {
        return request(`/orders/${orderId}/pay`, { method: 'POST' })
    },

    async verifyOrderPayment(txRef) {
        return request(`/payments/verify/${txRef}`)
    },

    async verifyPendingPaymentForOrder(orderId) {
        return request(`/orders/${orderId}/verify-payment`, { method: 'POST' })
    },

    async verifyDeliveryPin(orderId, pin) {
        return request(`/orders/${orderId}/verify-delivery-pin`, {
            method: 'POST',
            body: JSON.stringify({ pin }),
        })
    },

    async fetchBuyerDashboardStats() {
        return request('/buyer/dashboard/stats')
    },

    async fetchPayoutSummary() {
        return request('/payouts/summary')
    },

    async fetchPayouts(page = 1) {
        return request(`/payouts?page=${page}`)
    },

    async createPaymentException(data) {
        return request('/payment-exceptions', {
            method: 'POST',
            body: JSON.stringify(data),
        })
    },

    async respondToPaymentException(id, farmerResponse) {
        return request(`/payment-exceptions/${id}/respond`, {
            method: 'POST',
            body: JSON.stringify({ farmer_response: farmerResponse }),
        })
    },

    async fetchMyPaymentExceptions() {
        return request('/payment-exceptions/my')
    },

    async getNotifications() {
        return request('/notifications')
    },

    async markAllNotificationsRead() {
        return request('/notifications/mark-all-read', { method: 'POST' })
    },

    async markNotificationRead(id) {
        return request(`/notifications/${id}/read`, { method: 'POST' })
    },

    async logout() {
        try {
            return await request('/auth/logout', { method: 'POST' })
        } finally {
            clearAuthToken()
        }
    },
}

export function mapBackendUserToFrontend(rawUser) {
    let role = 'buyer'
    const activeCapabilities = []
    const pendingApplications = []

    if (rawUser.is_admin || rawUser.isAdmin) {
        role = 'admin'
        activeCapabilities.push('admin')
    } else if (rawUser.capabilities && Array.isArray(rawUser.capabilities)) {
        rawUser.capabilities.forEach((c) => {
            const type = typeof c === 'string' ? c : c.capability_type
            const status = typeof c === 'object' && c.status ? c.status : 'active'
            if (status === 'active' && type) {
                activeCapabilities.push(type)
            }
        })

        if (activeCapabilities.includes('farmer')) role = 'farmer'
        else if (activeCapabilities.includes('buyer')) role = 'buyer'
    }

    if (rawUser.capability_applications && Array.isArray(rawUser.capability_applications)) {
        rawUser.capability_applications.forEach((app) => {
            if (app.status === 'pending' && app.capability_type) {
                pendingApplications.push(app.capability_type)
            }
        })
    }

    if (activeCapabilities.length === 0 && role !== 'admin') {
        activeCapabilities.push('buyer')
    }

    const savedRole = localStorage.getItem('agri_active_role')
    const activeRole = (role !== 'admin' && savedRole && activeCapabilities.includes(savedRole)) ? savedRole : role

    const name = rawUser.name || `${rawUser.first_name || ''} ${rawUser.second_name || ''}`.trim() || 'User'

    return {
        id: String(rawUser.id || 'usr-1'),
        name,
        email: rawUser.email || `${rawUser.phone}@agri.et`,
        phone: rawUser.phone || '',
        role: activeRole,
        activeRole,
        capabilities: activeCapabilities,
        pendingApplications,
        status: rawUser.account_status || 'verified',
        region: rawUser.region || 'Addis Ababa',
        avatar: rawUser.profile_photo_url || rawUser.profile_photo_path
            ? (rawUser.profile_photo_url || `http://127.0.0.1:8000/storage/${rawUser.profile_photo_path}`)
            : undefined,
        createdAt: rawUser.created_at ? new Date(rawUser.created_at) : new Date(),
    }
}
