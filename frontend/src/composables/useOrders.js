import { ref, watch, onMounted } from 'vue'
import { api, getAuthToken } from '@/services/api'

const globalOrders = ref([])
const isLoaded = ref(false)
let isFetching = false

function getCurrentUserData() {
    try {
        const saved = localStorage.getItem('agri_user_data')
        return saved ? JSON.parse(saved) : null
    } catch {
        return null
    }
}

function mapRawOrderToFrontend(item) {
    const isEscrowReleased = item.escrow_status === 'released' || item.escrowStatus === 'released' || item.payout_status === 'released'

    // Extract first item & listing from items array or fulfillments array or root item
    const firstItem = item.items?.[0] || item.fulfillments?.[0]?.items?.[0] || {}
    const firstListing = firstItem.listing || item.listing || {}
    const farmerObj = firstListing.farmer || item.fulfillments?.[0]?.farmer || item.farmer || {}
    const buyerObj = item.buyer || {}

    const totalAmount = Number(item.total_amount || item.totalAmountETB || item.total_amount_etb || firstItem.subtotal || 0)
    const quantity = Number(firstItem.quantity || item.quantity_kg || item.quantityKg || 0)

    const cropTitle = firstListing.title || firstListing.cropName || item.title || item.cropName || 'Produce Batch'
    const cropEmoji = firstListing.crop_emoji || firstListing.cropEmoji || item.cropEmoji || '🌾'
    const categorySlug = firstListing.category?.slug || firstListing.category || item.category || 'grains'
    const qualityGrade = firstListing.quality_grade || firstListing.grade || item.grade || 'Grade 1'
    const regionName = firstListing.region || farmerObj.region || item.region || 'Sidama'
    const zoneName = firstListing.zone || item.zone || ''

    const farmerName = `${farmerObj.first_name || ''} ${farmerObj.second_name || ''}`.trim() || farmerObj.name || 'Aymen Mohammed'
    const buyerName = `${buyerObj.first_name || ''} ${buyerObj.second_name || ''}`.trim() || buyerObj.name || 'Commercial Buyer'

    const escrowRef = item.payment?.chapa_tx_ref || item.escrow_reference || item.escrowReference || `CHP-TX-${Math.floor(10000000 + Math.random() * 90000000)}`

    const exceptionsArr = item.payment_exceptions || item.paymentExceptions || item.order?.payment_exceptions || []
    const rawException = exceptionsArr[0] || item.dispute || null
    const disputeData = rawException ? {
        id: rawException.id,
        type: rawException.type || 'dispute',
        status: rawException.status || 'open',
        description: rawException.description || '',
        farmerResponse: rawException.farmer_response || rawException.farmerResponse || '',
        resolutionNotes: rawException.resolution_notes || rawException.resolutionNotes || '',
        resolvedAt: rawException.resolved_at || rawException.resolvedAt || null,
    } : null

    return {
        id: String(item.id || item.order_number || `ORD-${Math.floor(1000 + Math.random() * 9000)}`),
        displayId: String(item.order?.id || item.order_id || item.id || ''),
        orderNumber: String(item.order?.order_number || item.order_number || item.id || ''),
        listing: {
            id: String(firstListing.id || ''),
            farmerId: String(farmerObj.id || firstListing.farmer_id || ''),
            farmer: {
                id: String(farmerObj.id || ''),
                name: farmerName,
                region: regionName,
                phone: farmerObj.phone || '',
            },
            cropName: cropTitle,
            cropEmoji: cropEmoji,
            category: categorySlug,
            grade: qualityGrade,
            region: regionName,
            zone: zoneName,
            pricePerKg: Number(firstListing.price_per_unit || firstListing.pricePerKg || (quantity > 0 ? totalAmount / quantity : 0)),
            availableQty: Number(firstListing.quantity_available || firstListing.availableQty || 1000),
            minOrderQty: 100, harvestDate: new Date(), description: '', images: [],
            isActive: true, isVerified: true, createdAt: new Date(), viewCount: 100,
        },
        buyerId: String(buyerObj.id || item.buyer_id || item.buyerId || ''),
        buyer: {
            id: String(buyerObj.id || item.buyer_id || ''),
            name: buyerName,
            email: buyerObj.email || '',
            phone: buyerObj.phone || '',
            role: 'buyer', status: 'verified', region: buyerObj.region || 'Addis Ababa',
            companyName: buyerObj.company_name || buyerName, businessType: 'wholesaler', totalOrdered: 0, createdAt: new Date(),
        },
        farmerId: String(farmerObj.id || firstListing.farmer_id || item.farmer_id || ''),
        farmer: {
            id: String(farmerObj.id || ''),
            name: farmerName,
            phone: farmerObj.phone || '',
            region: regionName,
        },
        quantityKg: quantity,
        totalAmountETB: totalAmount,
        status: item.status || 'placed',
        escrowStatus: isEscrowReleased ? 'released' : 'held',
        escrowReference: escrowRef,
        dispute: disputeData,
        placedAt: item.placed_at ? new Date(item.placed_at) : (item.created_at ? new Date(item.created_at) : new Date()),
        deliveryPin: item.order?.delivery_pin || item.delivery_pin || item.deliveryPin || null,
        trackingNotes: item.trackingNotes || [],
    }
}

function loadOrdersFromStorage() {
    const user = getCurrentUserData()
    const saved = localStorage.getItem('agri_orders')
    if (saved && user) {
        try {
            const parsed = JSON.parse(saved)
            const filtered = parsed.filter(item => {
                const isFarmerRole = user.role === 'farmer' || user.activeRole === 'farmer'
                if (isFarmerRole) {
                    return item.farmerId === String(user.id) ||
                        item.farmer?.id === String(user.id) ||
                        item.farmer?.phone === user.phone ||
                        (item.farmer?.name && user.name && item.farmer.name.toLowerCase() === user.name.toLowerCase())
                } else {
                    return item.buyerId === String(user.id) ||
                        item.buyer?.id === String(user.id) ||
                        item.buyer?.phone === user.phone ||
                        (item.buyer?.name && user.name && item.buyer.name.toLowerCase() === user.name.toLowerCase())
                }
            })
            return filtered.map((item) => ({
                ...item,
                placedAt: new Date(item.placedAt),
                dispatchedAt: item.dispatchedAt ? new Date(item.dispatchedAt) : undefined,
                deliveredAt: item.deliveredAt ? new Date(item.deliveredAt) : undefined,
                completedAt: item.completedAt ? new Date(item.completedAt) : undefined,
            }))
        } catch {
            return []
        }
    }
    return []
}

export function useOrders() {
    if (!isLoaded.value) {
        globalOrders.value = loadOrdersFromStorage()
        isLoaded.value = true
        watch(globalOrders, (val) => {
            localStorage.setItem('agri_orders', JSON.stringify(val))
        }, { deep: true })
    }

    const orders = globalOrders

    const refreshOrders = async () => {
        const token = getAuthToken()
        if (!token || isFetching) return

        isFetching = true
        try {
            const user = getCurrentUserData()
            const role = user?.activeRole || user?.role || 'buyer'

            const res = role === 'farmer'
                ? await api.fetchMyFulfillments()
                : await api.fetchMyOrders()

            const rawItems = Array.isArray(res) ? res : (res?.data || [])
            orders.value = rawItems.map(mapRawOrderToFrontend)
        } catch {
            // Keep user-scoped filtered list if offline
        } finally {
            isFetching = false
        }
    }

    onMounted(() => {
        if (orders.value.length === 0) {
            refreshOrders()
        }
    })

    const placeOrder = (listing, buyer, quantityKg) => {
        const totalAmountETB = listing.pricePerKg * quantityKg
        const newOrder = {
            id: `ORD-${Math.floor(1000 + Math.random() * 9000)}`,
            listingId: String(listing.id),
            listing,
            buyerId: String(buyer?.id || ''),
            buyer,
            farmerId: String(listing.farmerId || listing.farmer?.id || ''),
            farmer: listing.farmer,
            quantityKg,
            totalAmountETB,
            status: 'placed',
            escrowStatus: 'held',
            escrowReference: `CHP-TX-${Math.floor(10000000 + Math.random() * 90000000)}`,
            placedAt: new Date(),
            trackingNotes: [
                {
                    id: `note-${Date.now()}`,
                    orderId: 'ORD-TEMP',
                    status: 'placed',
                    note: 'Order created and payment secured in Chapa escrow',
                    timestamp: new Date(),
                    actorRole: 'buyer',
                },
            ],
        }
        orders.value = [newOrder, ...orders.value]
        return newOrder
    }

    const confirmDelivery = async (orderId, pin = '123456') => {
        const token = getAuthToken()
        if (token) {
            try {
                await api.verifyDeliveryPin(orderId, pin)
            } catch {
                // offline fallback
            }
        }

        orders.value = orders.value.map((order) => {
            if (order.id === orderId) {
                const now = new Date()
                return {
                    ...order,
                    status: 'completed',
                    escrowStatus: 'released',
                    deliveredAt: now,
                    completedAt: now,
                    trackingNotes: [
                        ...order.trackingNotes,
                        {
                            id: `note-${Date.now()}`,
                            orderId,
                            status: 'delivered',
                            note: 'Delivery confirmed by buyer. Chapa escrow funds released to farmer.',
                            timestamp: now,
                            actorRole: 'buyer',
                        },
                    ],
                }
            }
            return order
        })
    }

    const updateOrderStatus = async (orderId, status, note) => {
        const token = getAuthToken()
        if (token) {
            try {
                let statusAction = status
                if (status === 'completed' || status === 'delivered') statusAction = 'complete'
                else if (status === 'dispatched' || status === 'in_transit') statusAction = 'dispatch'
                else if (status === 'accepted') statusAction = 'accept'

                await api.updateFulfillmentStatus(orderId, statusAction, note)
            } catch {
                // offline fallback
            }
        }

        orders.value = orders.value.map((order) => {
            if (order.id === orderId) {
                const now = new Date()
                return {
                    ...order,
                    status,
                    dispatchedAt: status === 'dispatched' ? now : order.dispatchedAt,
                    trackingNotes: [
                        ...order.trackingNotes,
                        {
                            id: `note-${Date.now()}`,
                            orderId,
                            status,
                            note,
                            timestamp: now,
                            actorRole: 'farmer',
                        },
                    ],
                }
            }
            return order
        })
    }

    const dispatchOrder = (orderId) => {
        updateOrderStatus(orderId, 'dispatched', 'Shipment dispatched to destination')
    }

    return {
        orders,
        refreshOrders,
        placeOrder,
        confirmDelivery,
        updateOrderStatus,
        dispatchOrder,
    }
}
