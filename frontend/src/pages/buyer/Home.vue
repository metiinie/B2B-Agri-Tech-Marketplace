<template>
  <div class="space-y-6 pb-6">
    <!-- Top Welcome Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E4E7] dark:border-[#30363D] pb-5">
      <div>
        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-[#0B57D0]/10 dark:bg-blue-900/30 text-[#0B57D0] dark:text-blue-400 text-[11px] font-extrabold mb-1">
          <span class="w-2 h-2 rounded-full bg-[#E69500]" />
          <span>{{ $t('buyer.portalBadge') }}</span>
        </div>
        <h1 class="text-2xl font-black text-[#1E2328] dark:text-[#F0F6FC] tracking-tight">
          {{ $t('buyer.welcomeBack') }}, <span class="text-[#0B57D0] dark:text-blue-400">{{ firstName }}</span> 👋
        </h1>
        <p class="text-xs text-[#5A6270] dark:text-[#8B949E] mt-0.5">
          {{ $t('buyer.welcomeSub') }}
        </p>
      </div>
    </div>

    <!-- 4 High-Impact Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-[#5A6270] dark:text-[#8B949E] uppercase tracking-wider">{{ $t('buyer.activeOrders') }}</span>
          <div class="w-9 h-9 rounded-xl bg-[#0B57D0]/10 dark:bg-blue-900/30 text-[#0B57D0] dark:text-blue-400 flex items-center justify-center font-bold">
            <ShoppingCart class="w-4 h-4" />
          </div>
        </div>
        <p class="text-2xl sm:text-3xl font-black text-[#1E2328] dark:text-[#F0F6FC] mt-2">{{ dashboardStats.active_orders }}</p>
        <div class="mt-2 flex items-center gap-1.5 text-[11px] font-semibold text-[#0B57D0] dark:text-blue-400">
          <span class="w-2 h-2 rounded-full bg-[#0B57D0] dark:bg-blue-400 animate-pulse" />
          <span>{{ dashboardStats.pending_handoffs_count }} {{ $t('buyer.awaitingHandoff') }}</span>
        </div>
      </div>

      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-[#5A6270] dark:text-[#8B949E] uppercase tracking-wider">{{ $t('buyer.totalSpent') }}</span>
          <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-[#E69500] dark:text-amber-300 flex items-center justify-center font-bold">
            <Wallet class="w-4 h-4" />
          </div>
        </div>
        <p class="text-2xl sm:text-3xl font-black text-[#1E2328] dark:text-[#F0F6FC] mt-2">{{ formatETB(dashboardStats.total_procurement_etb) }}</p>
        <p class="mt-2 text-[11px] font-semibold text-[#5A6270] dark:text-[#8B949E]">{{ $t('buyer.securedViaChapa') }}</p>
      </div>

      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-[#5A6270] dark:text-[#8B949E] uppercase tracking-wider">{{ $t('buyer.coopFarmers') }}</span>
          <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-[#1E9444] dark:text-emerald-400 flex items-center justify-center font-bold">
            <Users class="w-4 h-4" />
          </div>
        </div>
        <p class="text-2xl sm:text-3xl font-black text-[#1E2328] dark:text-[#F0F6FC] mt-2">{{ dashboardStats.verified_farmers_count.toLocaleString() }}+</p>
        <p class="mt-2 text-[11px] font-semibold text-[#1E9444] dark:text-emerald-400 flex items-center gap-1">
          <Building2 class="w-3 h-3 text-[#1E9444] dark:text-emerald-400" />
          <span>{{ dashboardStats.primary_unions_count }} {{ $t('farmer.primaryUnionCoops') }}</span>
        </p>
      </div>

      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-[#5A6270] dark:text-[#8B949E] uppercase tracking-wider">{{ $t('common.Carts') }}</span>
          <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-[#E69500] dark:text-amber-300 flex items-center justify-center font-bold">
            <Bookmark class="w-4 h-4" />
          </div>
        </div>
        <p class="text-2xl sm:text-3xl font-black text-[#1E2328] dark:text-[#F0F6FC] mt-2">{{ cartItems.length }}</p>
        <p class="mt-2 text-[11px] font-semibold text-amber-700 dark:text-amber-300">{{ $t('cart.title') }}</p>
      </div>
    </div>

    <!-- Featured Direct-From-Farm Produce Grid -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('buyer.recentProcurement') }}</h2>
          <p class="text-xs text-[#5A6270] dark:text-[#8B949E]">{{ $t('marketplace.subtitle') }}</p>
        </div>
        <router-link to="/buyer/marketplace" class="text-xs font-extrabold text-[#0B57D0] dark:text-blue-400 hover:underline flex items-center gap-1">
          <span>{{ $t('marketplace.browseMarketplace') }} →</span>
        </router-link>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <ListingCard v-for="listing in featuredListingsToShow" :key="listing.id" :listing="listing" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ShoppingCart, Wallet, Users, Bookmark, Building2 } from 'lucide-vue-next'
import { useAuth } from '@/composables/useAuth'
import { useListings } from '@/composables/useListings'
import { useCart } from '@/composables/useCart'
import { api } from '@/services/api'
import { formatETB } from '@/utils/helpers'
import ListingCard from '@/components/shared/ListingCard.vue'

const { user } = useAuth()
const { listings } = useListings()
const { cartItems } = useCart()

const firstName = computed(() => {
  return user.value?.name?.split(' ')[0] || 'Buyer'
})

const dashboardStats = ref({
  active_orders: 0,
  total_procurement_etb: 0,
  regional_hubs_count: 0,
  primary_unions_count: 0,
  verified_farmers_count: 0,
  pending_handoffs_count: 0,
  active_contracts_count: 0,
  cart_items_count: 0,
})

const backendFeaturedListings = ref([])

const featuredListingsToShow = computed(() => {
  if (backendFeaturedListings.value.length > 0) {
    return backendFeaturedListings.value
  }
  return listings.value.slice(0, 3)
})

onMounted(async () => {
  try {
    const data = await api.fetchBuyerDashboardStats()
    if (data) {
      if (data.stats) {
        dashboardStats.value = {
          active_orders: data.stats.active_orders || 0,
          total_procurement_etb: data.stats.total_procurement_etb || 0,
          regional_hubs_count: data.stats.regional_hubs_count || 0,
          primary_unions_count: data.stats.primary_unions_count || 0,
          verified_farmers_count: data.stats.verified_farmers_count || 0,
          pending_handoffs_count: data.stats.pending_handoffs_count || 0,
          active_contracts_count: data.stats.active_contracts_count || 0,
          cart_items_count: data.stats.cart_items_count || 0,
        }
      }
      if (data.featured_listings && Array.isArray(data.featured_listings)) {
        backendFeaturedListings.value = data.featured_listings
      }
    }
  } catch {
    // Keep defaults / composable fallbacks
  }
})
</script>
