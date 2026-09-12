<template>
  <div class="space-y-6 max-w-2xl mx-auto pb-12">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-[#E2E4E7] dark:border-[#30363D] pb-3">
      <div class="flex items-center gap-3">
        <router-link to="/buyer/cart" class="p-2 hover:bg-gray-100 dark:hover:bg-[#21262D] rounded-xl text-gray-500 dark:text-gray-400 transition-colors">
          <ArrowLeft class="w-5 h-5" />
        </router-link>
        <div>
          <h1 class="text-lg font-black text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('checkout.title') }}</h1>
          <p class="text-xs text-[#5A6270] dark:text-[#8B949E]">{{ $t('checkout.subtitle') }}</p>
        </div>
      </div>

      <span class="px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-[#1E9444] dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60 text-[11px] font-bold flex items-center gap-1">
        <ShieldCheck class="w-3.5 h-3.5" /> {{ $t('badges.escrowProtected') }}
      </span>
    </div>

    <!-- Main Content Container -->
    <div v-if="checkoutItems.length > 0" class="space-y-6">
      
      <!-- Selected Produce Batches List Card -->
      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-5 shadow-2xs space-y-4">
        <div class="flex justify-between items-center border-b border-gray-100 dark:border-[#21262D] pb-3">
          <span class="text-xs font-black text-[#1E2328] dark:text-[#F0F6FC] uppercase tracking-wider">
            {{ $t('cart.selectedItems') }} ({{ checkoutItems.length }})
          </span>
          <router-link to="/buyer/cart" class="text-xs font-bold text-[#0B57D0] dark:text-blue-400 hover:underline">
            {{ $t('cart.title') }}
          </router-link>
        </div>

        <div class="space-y-4 divide-y divide-gray-100 dark:divide-[#21262D]">
          <div 
            v-for="item in checkoutItems" 
            :key="item.id || item.listing?.id"
            class="pt-3 first:pt-0 space-y-3"
          >
            <div class="flex items-start gap-3">
              <div class="w-12 h-12 bg-[#F8F9FA] dark:bg-[#21262D] border border-gray-100 dark:border-[#30363D] rounded-xl flex items-center justify-center text-2xl shrink-0">
                {{ item.listing?.cropEmoji || '🌾' }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                  <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/40 text-[#0B57D0] dark:text-blue-400 text-[10px] font-bold">
                    {{ item.listing?.grade || 'Grade 1' }}
                  </span>
                </div>
                <h3 class="text-sm font-bold text-[#1E2328] dark:text-[#F0F6FC] truncate">{{ item.listing?.cropName }}</h3>
                <p class="text-[11px] text-[#5A6270] dark:text-[#8B949E]">
                  {{ item.listing?.farmer?.name || 'Producer' }} · {{ item.listing?.region || 'Ethiopia' }}
                </p>
              </div>

              <div class="flex flex-col items-end text-right shrink-0 leading-tight">
                <span class="text-sm font-black text-[#1E9444] dark:text-emerald-400 leading-tight">
                  {{ formatETB(getItemSubtotal(item)) }}
                </span>
                <span class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 font-medium leading-tight">
                  {{ formatETB(item.listing?.pricePerKg) }}/{{ item.unit || 'kg' }}
                </span>
              </div>
            </div>

            <!-- Quantity Controls per Item -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between bg-[#F8F9FA] dark:bg-[#21262D] p-3 rounded-xl border border-gray-100 dark:border-[#30363D] text-xs gap-3">
              <span class="text-gray-500 dark:text-gray-400 font-medium">{{ $t('common.quantity') }}:</span>
              <div class="flex items-center gap-2">
                <!-- Minus Button -->
                <button 
                  type="button"
                  @click="updateQty(item, (item.quantityKg || 1) - 1)"
                  :disabled="(item.quantityKg || 1) <= 1"
                  class="w-10 h-10 bg-white dark:bg-[#161B22] hover:bg-gray-100 dark:hover:bg-[#30363D] text-gray-800 dark:text-[#F0F6FC] border border-gray-300 dark:border-[#30363D] disabled:opacity-40 rounded-xl font-black text-lg flex items-center justify-center transition-all cursor-pointer disabled:cursor-not-allowed shadow-2xs"
                  title="Decrease volume by 1"
                >
                  -
                </button>

                <!-- Input Box -->
                <input 
                  type="number" 
                  :value="item.quantityKg || 1"
                  @input="updateQty(item, $event.target.value)"
                  min="1"
                  :max="item.unit === 'Quintals' ? Math.max(1, Math.floor((item.listing?.availableQty || 100000) / 100)) : (item.listing?.availableQty || 100000)"
                  step="1"
                  class="w-20 py-2 bg-white dark:bg-[#0D1117] border border-[#E2E4E7] dark:border-[#30363D] focus:border-[#0B57D0] dark:focus:border-blue-500 focus:outline-none font-black text-[#1E2328] dark:text-[#F0F6FC] text-center rounded-xl text-sm shadow-2xs"
                />

                <!-- Plus Button -->
                <button 
                  type="button"
                  @click="updateQty(item, (item.quantityKg || 1) + 1)"
                  :disabled="(item.quantityKg || 1) >= (item.unit === 'Quintals' ? Math.max(1, Math.floor((item.listing?.availableQty || 100000) / 100)) : (item.listing?.availableQty || 100000))"
                  class="w-10 h-10 bg-[#0B57D0]/10 dark:bg-blue-900/30 text-[#0B57D0] dark:text-blue-400 hover:bg-[#0B57D0] dark:hover:bg-blue-600 hover:text-white dark:hover:text-white border border-[#0B57D0]/30 dark:border-blue-700/50 disabled:opacity-40 rounded-xl font-black text-lg flex items-center justify-center transition-all cursor-pointer disabled:cursor-not-allowed shadow-2xs"
                  title="Increase volume by 1"
                >
                  +
                </button>

                <!-- Unit Dropdown -->
                <select 
                  :value="item.unit || 'KG'" 
                  @change="handleUnitChange(item, $event.target.value)"
                  class="px-2.5 py-2 bg-white dark:bg-[#0D1117] border border-[#E2E4E7] dark:border-[#30363D] focus:border-[#0B57D0] dark:focus:border-blue-500 focus:outline-none font-bold text-[#1E2328] dark:text-[#F0F6FC] rounded-xl text-xs shadow-2xs cursor-pointer"
                >
                  <option value="KG">KG</option>
                  <option value="Quintals">Quintals (100 KG)</option>
                  <option value="Litres">Litres (L)</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Target Delivery Logistics Hub Selector -->
      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-5 shadow-2xs space-y-2">
        <label class="text-xs font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('checkout.deliveryInformation') }}</label>
        <select 
          v-model="selectedHub" 
          class="w-full px-3.5 py-2.5 bg-[#F8F9FA] dark:bg-[#0D1117] border border-[#E2E4E7] dark:border-[#30363D] rounded-xl text-xs font-semibold text-[#1E2328] dark:text-[#F0F6FC] focus:border-[#0B57D0] dark:focus:border-blue-500 focus:outline-none cursor-pointer"
        >
          <option value="" disabled>Select Delivery Destination Hub</option>
          <option v-for="hub in availableHubs" :key="hub.id" :value="hub.id">{{ hub.name }}</option>
        </select>
      </div>

      <!-- Financial Breakdown Box -->
      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-5 shadow-2xs space-y-3">
        <h4 class="text-xs font-bold text-[#1E2328] dark:text-[#F0F6FC] uppercase border-b border-gray-100 dark:border-[#21262D] pb-2">{{ $t('cart.orderSummary') }}</h4>

        <div class="space-y-2 text-xs">
          <div 
            v-for="item in checkoutItems" 
            :key="`sum-${item.id || item.listing?.id}`"
            class="flex justify-between text-[#5A6270] dark:text-[#8B949E]"
          >
            <span>{{ item.listing?.cropName }} ({{ (item.quantityKg || 1).toLocaleString() }} {{ item.unit || 'KG' }})</span>
            <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">
              {{ formatETB(getItemSubtotal(item)) }}
            </span>
          </div>

          <div class="flex justify-between text-[#5A6270] dark:text-[#8B949E] pt-1">
            <span>Produce Subtotal</span>
            <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ formatETB(totalSubtotalETB) }}</span>
          </div>

          <div class="flex justify-between text-[#5A6270] dark:text-[#8B949E]">
            <span>{{ $t('cart.escrowFee') }} (1.5%)</span>
            <span class="font-bold text-emerald-600 dark:text-emerald-400">+{{ formatETB(escrowFeeETB) }}</span>
          </div>

          <div class="border-t border-dashed border-gray-200 dark:border-[#30363D] pt-3 flex justify-between items-center text-sm font-black">
            <span class="text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('cart.totalPayable') }}</span>
            <span class="text-[#1E9444] dark:text-emerald-400 text-xl">{{ formatETB(totalPayableETB) }}</span>
          </div>
        </div>
      </div>

      <!-- Chapa Escrow Protection Banner -->
      <p class="text-[11px] text-[#5A6270] dark:text-[#8B949E] flex items-center gap-2 bg-[#EDFAF2] dark:bg-emerald-950/40 p-3.5 rounded-xl border border-[#C3EFCF] dark:border-emerald-800/60">
        <Lock class="w-4 h-4 text-[#1E9444] dark:text-emerald-400 shrink-0" />
        <span>{{ $t('checkout.termsAcceptance') }}</span>
      </p>

      <!-- Pay Action Button -->
      <button 
        @click="handleCheckout" 
        :disabled="isProcessing || totalPayableETB <= 0"
        class="w-full py-4 rounded-xl bg-[#1E9444] text-white font-bold text-sm shadow-md hover:bg-[#0F5C2A] disabled:opacity-50 flex items-center justify-center gap-2 transition-colors cursor-pointer"
      >
        <Loader2 v-if="isProcessing" class="w-4 h-4 animate-spin" />
        <span v-else class="flex items-center gap-2">
          <ShieldCheck class="w-4 h-4" />
          <span>Send Contract Request to Farmer ({{ formatETB(totalPayableETB) }})</span>
        </span>
      </button>

    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12 bg-white dark:bg-[#161B22] border border-[#E2E8F0] dark:border-[#30363D] rounded-2xl p-6 text-[#5A6270] dark:text-[#8B949E] space-y-3">
      <p>{{ $t('cart.emptyCartTitle') }}</p>
      <router-link to="/buyer/marketplace" class="text-[#0B57D0] dark:text-blue-400 font-bold text-xs hover:underline">
        {{ $t('marketplace.browseMarketplace') }}
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, ShieldCheck, Lock, Loader2 } from 'lucide-vue-next'
import { useListings } from '@/composables/useListings'
import { useOrders } from '@/composables/useOrders'
import { useAuth } from '@/composables/useAuth'
import { useCart } from '@/composables/useCart'
import { useAlertModal } from '@/composables/useAlertModal'
import { api } from '@/services/api'
import { formatETB } from '@/utils/helpers'

const route = useRoute()
const router = useRouter()
const { getListingById, listings } = useListings()
const { refreshOrders } = useOrders()
const { user } = useAuth()
const { selectedItems, cartItems, updateQuantity, updateUnit, getItemSubtotal, clearCart } = useCart()
const { showAlert } = useAlertModal()

const isProcessing = ref(false)
const availableHubs = ref([
  { id: 'hub-addis', name: 'Addis Ababa Central Logistics Hub (Bole Lemi Zone)' },
  { id: 'hub-adama', name: 'Adama Commercial Grain & Produce Warehouse (Oromia Hub)' },
  { id: 'hub-hawassa', name: 'Hawassa Industrial & Agri-Tech Cold Storage (Sidama Hub)' },
  { id: 'hub-bahirdar', name: 'Bahir Dar Regional Aggregation & Processing Hub (Amhara Region)' },
  { id: 'hub-diredawa', name: 'Dire Dawa International Freight Terminal' },
  { id: 'hub-jimma', name: 'Jimma Coffee & Specialty Crops Aggregation Hub' },
  { id: 'hub-mekelle', name: 'Mekelle Regional Agro-Processing Center' },
  { id: 'hub-shashamane', name: 'Shashamane Southern Logistics Crossroads Terminal' }
])
const selectedHub = ref('hub-addis')

const checkoutItems = computed(() => {
  // 1. If explicit listing ID passed in URL path
  if (route.params.id) {
    const singleListing = getListingById(route.params.id)
    if (singleListing) {
      return [{
        id: `single-${singleListing.id}`,
        listing: singleListing,
        quantityKg: 1,
        unit: 'KG'
      }]
    }
  }

  // 2. Return selected items from cart if any selected
  if (selectedItems.value.length > 0) {
    return selectedItems.value
  }

  // 3. Return all items in cart if none selected
  if (cartItems.value.length > 0) {
    return cartItems.value
  }

  return []
})

const updateQty = (item, newQty) => {
  const parsed = parseInt(newQty)
  if (item.id && !item.id.startsWith('single-') && !item.id.startsWith('default-')) {
    updateQuantity(item.id, parsed)
  } else {
    const max = item.listing?.availableQty !== undefined && item.listing?.availableQty !== null ? item.listing.availableQty : 100000
    if (isNaN(parsed) || parsed < 1) {
      item.quantityKg = 1
    } else if (parsed > max) {
      item.quantityKg = Math.max(max, 1)
    } else {
      item.quantityKg = parsed
    }
  }
}

const handleUnitChange = (item, newUnit) => {
  if (item.id && !item.id.startsWith('single-') && !item.id.startsWith('default-')) {
    updateUnit(item.id, newUnit)
  } else {
    item.unit = newUnit
  }
}

const totalSubtotalETB = computed(() => {
  return checkoutItems.value.reduce((sum, item) => {
    return sum + getItemSubtotal(item)
  }, 0)
})

const escrowFeeETB = computed(() => {
  return totalSubtotalETB.value * 0.015
})

const totalPayableETB = computed(() => {
  return totalSubtotalETB.value + escrowFeeETB.value
})

const handleCheckout = async () => {
  if (checkoutItems.value.length === 0) return
  isProcessing.value = true

  try {
    const formattedItems = checkoutItems.value.map(item => {
      const rawId = item.listing?.id ? String(item.listing.id).replace(/[^0-9]/g, '') : ''
      const cleanListingId = parseInt(rawId) || 1
      const effectiveQtyKg = item.unit === 'Quintals' ? (item.quantityKg || 1) * 100 : (item.quantityKg || 1)
      return {
        listing_id: cleanListingId,
        quantity_kg: effectiveQtyKg
      }
    })

    // 1. Reserve stock & create orders on backend for all items
    let orderId = null
    const checkoutRes = await api.checkoutOrder({ 
      items: formattedItems,
      listing_id: formattedItems[0]?.listing_id,
      quantity_kg: formattedItems[0]?.quantity_kg,
      delivery_hub_id: selectedHub.value
    }).catch((err) => {
      throw err
    })

    if (checkoutRes?.order?.id) {
      orderId = checkoutRes.order.id
    }

    // Sync global orders state from backend so the new order appears immediately
    await refreshOrders()

    clearCart()
    router.push('/buyer/orders')
  } catch (err) {
    showAlert({
      title: 'Checkout Error',
      message: err.message || 'Order initiation failed.',
      type: 'error'
    })
  } finally {
    isProcessing.value = false
  }
}
</script>
