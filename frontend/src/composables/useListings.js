import { ref, onMounted, watch } from 'vue'
import { api, getAuthToken } from '@/services/api'

const DEFAULT_PRODUCE = []

function mapRawListingToFrontend(item) {
    const farmerObj = item.farmer || {}
    const farmerFirstName = farmerObj.first_name || ''
    const farmerSecondName = farmerObj.second_name || ''
    const farmerFullName = `${farmerFirstName} ${farmerSecondName}`.trim() || farmerObj.name || 'Dawit Bekele'

    let imagesList = []
    if (Array.isArray(item.images) && item.images.length > 0) {
        imagesList = item.images.map(img => {
            if (typeof img === 'string') {
                return img.startsWith('http') || img.startsWith('blob:') || img.startsWith('data:')
                    ? img
                    : `http://127.0.0.1:8000/storage/${img.replace(/^\/?storage\//, '')}`
            }
            if (img instanceof File || img instanceof Blob) {
                return URL.createObjectURL(img)
            }
            if (img && img.image_path) {
                return img.image_path.startsWith('http') || img.image_path.startsWith('blob:') || img.image_path.startsWith('data:')
                    ? img.image_path
                    : `http://127.0.0.1:8000/storage/${img.image_path.replace(/^\/?storage\//, '')}`
            }
            if (img && img.url) {
                return img.url.startsWith('http') || img.url.startsWith('blob:') || img.url.startsWith('data:')
                    ? img.url
                    : `http://127.0.0.1:8000/storage/${img.url.replace(/^\/?storage\//, '')}`
            }
            return null
        }).filter(Boolean)
    }

    let primaryImg = item.image_url || (item.image_path
        ? (item.image_path.startsWith('http') || item.image_path.startsWith('blob:') || item.image_path.startsWith('data:')
            ? item.image_path
            : `http://127.0.0.1:8000/storage/${item.image_path.replace(/^\/?storage\//, '')}`)
        : null)

    if (primaryImg && (primaryImg instanceof File || primaryImg instanceof Blob)) {
        primaryImg = URL.createObjectURL(primaryImg)
    }

    if (!primaryImg && imagesList.length > 0) {
        primaryImg = imagesList[0]
    }
    if (primaryImg && !imagesList.includes(primaryImg)) {
        imagesList.unshift(primaryImg)
    }

    return {
        id: String(item.id),
        farmerId: String(item.farmer_id || item.farmerId || 'farmer-1'),
        farmer: item.farmer ? {
            id: String(item.farmer.id || 'farmer-1'),
            name: farmerFullName,
            email: item.farmer.email || 'farmer@agri.et',
            phone: item.farmer.phone || '+251 912 345 678',
            role: 'farmer', status: 'verified', region: item.farmer.region || 'SNNPR',
            bank_code: farmerObj.bank_code || farmerObj.bank_name || 'CBE',
            bank_name: farmerObj.bank_name || 'Commercial Bank of Ethiopia',
            account_number: farmerObj.account_number || farmerObj.account_number_masked || '1000123456789',
            account_name: farmerObj.account_name || farmerFullName,
            farmSize: item.farmer.farmSize || 0, totalEarned: 0, rating: 0, reviewCount: 0, crops: [], createdAt: new Date(),
        } : { name: 'Unknown Farmer', role: 'farmer', region: 'Unknown' },
        cropName: item.title || item.cropName || 'Produce',
        cropEmoji: item.crop_emoji || item.cropEmoji || '🌾',
        category: item.category?.slug || item.category || 'grains',
        grade: item.grade || item.quality_grade || 'Grade 1',
        region: item.region || item.farmer?.region || 'Ethiopia',
        zone: item.zone || 'Zone 1',
        process: item.process || 'Natural',
        pricePerKg: Number(item.price_per_unit ?? item.pricePerKg ?? 50),
        availableQty: Number(item.quantity_available ?? item.availableQty ?? 1000),
        minOrderQty: Number(item.min_order_qty ?? item.min_order_quantity ?? item.minOrderQty ?? 100),
        harvestDate: item.harvest_date ? new Date(item.harvest_date) : new Date(),
        description: item.description || '',
        primaryImage: primaryImg,
        images: imagesList.length > 0 ? imagesList : (item.image_path ? [`http://127.0.0.1:8000/storage/${item.image_path}`] : (item.images || [])),
        isActive: item.status === 'active' || item.isActive !== false,
        isVerified: true,
        createdAt: item.created_at ? new Date(item.created_at) : new Date(),
        viewCount: item.view_count || item.viewCount || 1,
    }
}

function getDeletedListingIds() {
    try {
        const saved = localStorage.getItem('agri_deleted_listing_ids')
        return saved ? JSON.parse(saved) : []
    } catch {
        return []
    }
}

function addDeletedListingId(id) {
    if (!id) return
    try {
        const ids = getDeletedListingIds()
        const strId = String(id)
        if (!ids.includes(strId)) {
            ids.push(strId)
            localStorage.setItem('agri_deleted_listing_ids', JSON.stringify(ids))
        }
    } catch { /* ignore */ }
}

function removeDeletedListingId(id) {
    if (!id) return
    try {
        const ids = getDeletedListingIds().filter(i => String(i) !== String(id))
        localStorage.setItem('agri_deleted_listing_ids', JSON.stringify(ids))
    } catch { /* ignore */ }
}

export function useListings() {
    const listings = ref([])
    const isLoading = ref(false)

    // Load from localStorage or use default produce list
    const saved = localStorage.getItem('agri_listings')
    const deletedIds = getDeletedListingIds()
    if (saved) {
        try {
            const parsed = JSON.parse(saved)
            if (Array.isArray(parsed) && parsed.length > 0) {
                listings.value = parsed
                    .filter(item => !deletedIds.includes(String(item.id)))
                    .map((item) => ({
                        ...item,
                        harvestDate: new Date(item.harvestDate),
                        createdAt: new Date(item.createdAt),
                    }))
            } else {
                listings.value = DEFAULT_PRODUCE.filter(item => !deletedIds.includes(String(item.id)))
            }
        } catch {
            listings.value = DEFAULT_PRODUCE.filter(item => !deletedIds.includes(String(item.id)))
        }
    } else {
        listings.value = DEFAULT_PRODUCE.filter(item => !deletedIds.includes(String(item.id)))
    }

    const refreshListings = async () => {
        isLoading.value = true
        try {
            const userData = localStorage.getItem('agri_user_data')
            const token = getAuthToken()
            let role = 'buyer'
            if (userData) {
                try {
                    const parsed = JSON.parse(userData)
                    role = parsed.activeRole || parsed.role || 'buyer'
                } catch { /* ignore */ }
            }

            const res = (token && role === 'farmer')
                ? await api.fetchMyListings()
                : await api.fetchPublicListings()

            const rawItems = Array.isArray(res) ? res : (res?.data || [])
            const mapped = rawItems.map(mapRawListingToFrontend)
            const currentDeletedIds = getDeletedListingIds()
            listings.value = mapped.filter(item => !currentDeletedIds.includes(String(item.id)))
        } catch {
            // Keep current listings if API fails
        } finally {
            isLoading.value = false
        }
    }

    // Persist to localStorage on change
    watch(listings, (val) => {
        localStorage.setItem('agri_listings', JSON.stringify(val))
    }, { deep: true })

    // Refresh on mount
    onMounted(() => {
        refreshListings()
    })

    const addListing = async (newListingData) => {
        const token = getAuthToken()

        const filesToUpload = newListingData.rawFiles ||
            (newListingData.images || []).filter(f => typeof window !== 'undefined' && (f instanceof File || f instanceof Blob))

        const stringImages = (newListingData.images || []).map(img => {
            if (typeof img === 'string') return img
            return null
        }).filter(Boolean)

        const primaryUploadedImg = newListingData.primaryImage || (stringImages.length > 0 ? stringImages[0] : null)

        if (token) {
            try {
                const formData = new FormData()
                formData.append('title', newListingData.cropName || 'Produce Batch')

                const categoryMap = {
                    'grains': 1,
                    'cereals-grains': 1,
                    'oilseeds': 2,
                    'coffee': 3,
                    'vegetables': 4,
                    'fruits': 5,
                    'honey-bee-products': 6,
                    'dairy-products': 7,
                    'spices': 8,
                    'pulses': 1,
                    'roots': 4,
                }
                const catId = categoryMap[newListingData.category] || 1
                formData.append('category_id', catId)

                if (newListingData.description) formData.append('description', newListingData.description)
                formData.append('unit', 'kg')
                formData.append('price_per_unit', newListingData.pricePerKg || 1)
                formData.append('quantity_available', newListingData.availableQty || 1)
                if (newListingData.minOrderQty) formData.append('minimum_order_quantity', newListingData.minOrderQty)

                if (newListingData.harvestDate) {
                    try {
                        const d = new Date(newListingData.harvestDate)
                        if (!isNaN(d.getTime())) {
                            formData.append('harvest_date', d.toISOString().split('T')[0])
                        }
                    } catch { /* ignore */ }
                }
                if (newListingData.grade) formData.append('quality_grade', newListingData.grade)
                if (newListingData.region) formData.append('region', newListingData.region)
                if (newListingData.zone) formData.append('zone', newListingData.zone)
                if (newListingData.process) formData.append('process', newListingData.process)

                if (filesToUpload && filesToUpload.length > 0) {
                    filesToUpload.forEach((file) => {
                        formData.append('images[]', file)
                    })
                }

                const res = await api.createListing(formData)
                const rawObj = res?.listing || res?.data || res
                if (rawObj) {
                    const created = mapRawListingToFrontend(rawObj)
                    listings.value = [created, ...listings.value]
                    return created
                }
            } catch (err) {
                console.error('API createListing failed:', err)
                throw err
            }
        }

        const mappedImages = (await Promise.all((newListingData.images || []).map(async img => {
            if (img instanceof File || img instanceof Blob) {
                return new Promise((resolve) => {
                    const reader = new FileReader()
                    reader.onloadend = () => resolve(reader.result)
                    reader.onerror = () => resolve(URL.createObjectURL(img))
                    reader.readAsDataURL(img)
                })
            }
            return typeof img === 'string' ? img : null;
        }))).filter(Boolean)

        const finalImages = mappedImages.length > 0
            ? mappedImages
            : (stringImages.length > 0 ? stringImages : (primaryUploadedImg ? [primaryUploadedImg] : []))

        const finalPrimary = mappedImages.length > 0
            ? mappedImages[0]
            : (primaryUploadedImg || (finalImages.length > 0 ? finalImages[0] : null))

        const created = {
            ...newListingData,
            id: `listing-${Date.now()}`,
            primaryImage: finalPrimary,
            images: finalImages,
            createdAt: new Date(),
            viewCount: 1,
        }
        delete created.rawFiles
        listings.value = [created, ...listings.value]
        return created
    }

    const getListingById = (id) => {
        if (!id) return null
        return listings.value.find((item) => String(item.id) === String(id))
    }

    const filterListings = (category, query, sortBy = 'newest') => {
        let result = listings.value.filter((item) => {
            const matchCat = !category || category === 'all' || item.category === category
            const matchQuery =
                !query ||
                item.cropName.toLowerCase().includes(query.toLowerCase()) ||
                item.region.toLowerCase().includes(query.toLowerCase()) ||
                item.farmer?.name?.toLowerCase().includes(query.toLowerCase()) ||
                item.grade.toLowerCase().includes(query.toLowerCase())
            return matchCat && matchQuery
        })

        switch (sortBy) {
            case 'oldest':
                result.sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt))
                break;
            case 'price_asc':
                result.sort((a, b) => a.pricePerKg - b.pricePerKg)
                break;
            case 'price_desc':
                result.sort((a, b) => b.pricePerKg - a.pricePerKg)
                break;
            case 'newest':
            default:
                result.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
                break;
        }

        return result
    }

    const updateListing = async (id, updatedData) => {
        const token = getAuthToken()

        const filesToUpload = updatedData.rawFiles || []
        const hasFiles = filesToUpload.length > 0

        if (token) {
            try {
                let payload

                if (hasFiles) {
                    payload = new FormData()
                    payload.append('_method', 'PUT') // Fix for Laravel multipart form-data
                    if (updatedData.cropName) payload.append('title', updatedData.cropName)
                    if (updatedData.grade) payload.append('quality_grade', updatedData.grade)
                    if (updatedData.region) payload.append('region', updatedData.region)
                    if (updatedData.zone) payload.append('zone', updatedData.zone)
                    if (updatedData.availableQty) payload.append('quantity_available', updatedData.availableQty)
                    if (updatedData.pricePerKg) payload.append('price_per_unit', updatedData.pricePerKg)
                    if (updatedData.description) payload.append('description', updatedData.description)
                    if (updatedData.category) {
                        const catId = { 'grains': 1, 'oilseeds': 2, 'coffee': 3, 'vegetables': 4, 'fruits': 5, 'spices': 8 }[updatedData.category] || 1
                        payload.append('category_id', catId)
                    }

                    filesToUpload.forEach((file) => {
                        payload.append('images[]', file)
                    })
                } else {
                    payload = {
                        title: updatedData.cropName,
                        quality_grade: updatedData.grade,
                        region: updatedData.region,
                        zone: updatedData.zone,
                        quantity_available: updatedData.availableQty,
                        price_per_unit: updatedData.pricePerKg,
                        description: updatedData.description,
                    }
                }

                const res = await api.updateListing(id, payload)
                const rawObj = res?.listing || res?.data || res
                if (rawObj && typeof rawObj === 'object') {
                    const mapped = mapRawListingToFrontend(rawObj)
                    const idx = listings.value.findIndex(l => String(l.id) === String(id))
                    if (idx !== -1) {
                        listings.value[idx] = { ...listings.value[idx], ...mapped }
                    }
                    return mapped
                }
            } catch (err) {
                console.error('API updateListing failed, updating local state:', err)
            }
        }

        // Fallback local update
        const idx = listings.value.findIndex(l => String(l.id) === String(id))
        if (idx !== -1) {
            listings.value[idx] = {
                ...listings.value[idx],
                ...updatedData,
                cropName: updatedData.cropName || listings.value[idx].cropName,
                grade: updatedData.grade || listings.value[idx].grade,
                region: updatedData.region || listings.value[idx].region,
                zone: updatedData.zone || listings.value[idx].zone,
                availableQty: updatedData.availableQty ?? listings.value[idx].availableQty,
                pricePerKg: updatedData.pricePerKg ?? listings.value[idx].pricePerKg,
                description: updatedData.description ?? listings.value[idx].description,
            }
            if (updatedData.images && updatedData.images.length > 0) {
                listings.value[idx].images = updatedData.images
                listings.value[idx].primaryImage = updatedData.images[0]
            }
            return listings.value[idx]
        }
        return null
    }

    const deleteListing = async (id) => {
        addDeletedListingId(id)
        try {
            await api.deleteListing(id)
        } catch (err) {
            console.error('Failed to delete listing on backend, removing locally:', err)
        } finally {
            listings.value = listings.value.filter(l => String(l.id) !== String(id))
        }
        return true
    }

    return {
        listings,
        isLoading,
        refreshListings,
        addListing,
        updateListing,
        deleteListing,
        getListingById,
        filterListings,
    }
}

