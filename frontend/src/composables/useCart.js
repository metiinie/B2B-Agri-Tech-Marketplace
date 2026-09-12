import { ref, computed, watch } from 'vue'
import { api, getAuthToken } from '@/services/api'

const cartItems = ref([])
const isLoaded = ref(false)

function loadCartFromStorage() {
    const saved = localStorage.getItem('agri_cart_items')
    if (saved) {
        try {
            cartItems.value = JSON.parse(saved)
            isLoaded.value = true
            return
        } catch { /* ignore */ }
    }
    cartItems.value = []
    isLoaded.value = true
}

export function useCart() {
    if (!isLoaded.value) {
        loadCartFromStorage()
        watch(cartItems, (val) => {
            localStorage.setItem('agri_cart_items', JSON.stringify(val))
        }, { deep: true })
    }

    const addToCart = (listing, quantityKg = null, unit = 'KG') => {
        const qty = quantityKg || 1
        const existingIndex = cartItems.value.findIndex(item => item.listingId === listing.id || item.listing?.id === listing.id)

        if (existingIndex > -1) {
            cartItems.value[existingIndex].quantityKg += qty
            cartItems.value[existingIndex].selected = true
        } else {
            cartItems.value.unshift({
                id: `cart-${Date.now()}`,
                listingId: listing.id,
                listing,
                quantityKg: qty,
                unit: unit || 'KG',
                selected: true
            })
        }
    }

    const removeFromCart = (cartItemId) => {
        cartItems.value = cartItems.value.filter(item => item.id !== cartItemId)
    }

    const updateQuantity = (cartItemId, newQty) => {
        const item = cartItems.value.find(i => i.id === cartItemId)
        if (item) {
            const parsed = parseInt(newQty)
            const availableKg = item.listing?.availableQty !== undefined && item.listing?.availableQty !== null ? item.listing.availableQty : 100000
            const max = item.unit === 'Quintals' ? Math.max(1, Math.floor(availableKg / 100)) : availableKg
            if (isNaN(parsed) || parsed < 1) {
                item.quantityKg = 1
            } else if (parsed > max) {
                item.quantityKg = Math.max(max, 1)
            } else {
                item.quantityKg = parsed
            }
        }
    }

    const updateUnit = (cartItemId, newUnit) => {
        const item = cartItems.value.find(i => i.id === cartItemId)
        if (item) {
            item.unit = newUnit
            const availableKg = item.listing?.availableQty !== undefined && item.listing?.availableQty !== null ? item.listing.availableQty : 100000
            const max = item.unit === 'Quintals' ? Math.max(1, Math.floor(availableKg / 100)) : availableKg
            if (item.quantityKg > max) {
                item.quantityKg = Math.max(max, 1)
            }
        }
    }

    const getItemSubtotal = (item) => {
        if (!item || !item.listing) return 0
        const price = item.listing.pricePerKg || 0
        const qty = item.quantityKg || 1
        if (item.unit === 'Quintals') {
            return price * 100 * qty
        }
        return price * qty
    }

    const toggleSelect = (cartItemId) => {
        const item = cartItems.value.find(i => i.id === cartItemId)
        if (item) {
            item.selected = !item.selected
        }
    }

    const selectAll = (val = true) => {
        cartItems.value.forEach(item => { item.selected = val })
    }

    const clearCart = () => {
        cartItems.value = []
    }

    const selectedItems = computed(() => cartItems.value.filter(i => i.selected))
    const selectedCount = computed(() => selectedItems.value.length)
    const totalCartCount = computed(() => cartItems.value.length)

    const selectedSubtotal = computed(() => {
        return selectedItems.value.reduce((sum, item) => {
            return sum + getItemSubtotal(item)
        }, 0)
    })

    const escrowFee = computed(() => {
        return selectedSubtotal.value * 0.015
    })

    const totalPayableWithFee = computed(() => {
        return selectedSubtotal.value + escrowFee.value
    })

    return {
        cartItems,
        selectedItems,
        selectedCount,
        totalCartCount,
        selectedSubtotal,
        escrowFee,
        totalPayableWithFee,
        addToCart,
        removeFromCart,
        updateQuantity,
        updateUnit,
        getItemSubtotal,
        toggleSelect,
        selectAll,
        clearCart
    }
}
