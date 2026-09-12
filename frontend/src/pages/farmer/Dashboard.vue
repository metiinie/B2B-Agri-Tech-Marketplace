<template>
  <div class="w-full flex flex-col min-h-full pb-8 max-w-5xl mx-auto space-y-5">
    <!-- WELCOME BANNER -->
    <div class="bg-gradient-to-r from-[#062E15] via-[#0F5C2A] to-[#0B57D0] text-white p-6 rounded-3xl shadow-sm relative overflow-hidden">
      <div class="absolute -top-10 -right-10 w-40 h-40 bg-[#E69500]/20 rounded-full blur-2xl pointer-events-none" />
      <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-[#0B57D0]/30 rounded-full blur-2xl pointer-events-none" />

      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10">
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
              {{ $t('farmer.welcome') }}, {{ farmer?.name || farmer?.first_name || $t('Loading...') }}
            </h2>
            <ShieldCheck class="w-5 h-5 text-[#E69500]" />
          </div>
          <p class="text-xs text-[#C3EFCF] mt-1 font-medium flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-[#E69500] animate-pulse" />
            <span>{{ $t(farmer?.region) || 'Addis Ababa' }} {{ $t('farmer.regionMember') }}</span>
          </p>
        </div>

        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-3.5 text-left sm:text-right shrink-0">
          <span class="text-[10px] font-extrabold text-[#C3EFCF] uppercase tracking-wider flex items-center justify-start sm:justify-end gap-1">
            <Sparkles class="w-3 h-3 text-[#E69500]" /> {{ $t('farmer.totalEarned') }}
          </span>
          <span class="text-xl font-black text-[#E69500] block leading-tight mt-0.5">
            {{ formatETB(totalEarnedETB) }}
          </span>
        </div>
      </div>
    </div>

    <!-- 6 METRIC STAT CARDS (Uniform Buyer Dashboard Style) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all relative overflow-hidden group flex flex-col justify-between space-y-3">
        <div class="flex items-start justify-between">
          <div>
            <span class="text-[11px] font-bold text-[#5A6270] dark:text-[#8B949E] uppercase tracking-wider">{{ $t('farmer.activeProduceListings') }}</span>
            <h3 class="text-2xl sm:text-3xl font-black text-[#1E2328] dark:text-[#F0F6FC] mt-1">{{ activeCount }}</h3>
          </div>
          <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-[#1E9444] dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/60 flex items-center justify-center shrink-0">
            <Zap class="w-4 h-4" />
          </div>
        </div>
        <span class="text-[11px] font-semibold text-[#1E9444] dark:text-emerald-400 flex items-center gap-1">
          <span class="w-2 h-2 rounded-full bg-[#1E9444] animate-pulse"></span>
          <span>{{ $t('farmer.liveOnMarketplace') }}</span>
        </span>
      </div>

      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all relative overflow-hidden group flex flex-col justify-between space-y-3">
        <div class="flex items-start justify-between">
          <div>
            <span class="text-[11px] font-bold text-[#5A6270] dark:text-[#8B949E] uppercase tracking-wider">{{ $t('farmer.regionalDepots') }}</span>
            <h3 class="text-2xl sm:text-3xl font-black text-[#1E2328] dark:text-[#F0F6FC] mt-1">{{ regionalDepotsCount }}</h3>
          </div>
          <div class="w-9 h-9 rounded-xl bg-[#0B57D0]/10 dark:bg-blue-950/40 text-[#0B57D0] dark:text-blue-400 border border-blue-100 dark:border-blue-800/60 flex items-center justify-center shrink-0">
            <Building2 class="w-4 h-4" />
          </div>
        </div>
        <span class="text-[11px] font-semibold text-[#0B57D0] dark:text-blue-400">{{ $t('farmer.supportedLogistics') }}</span>
      </div>

      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all relative overflow-hidden group flex flex-col justify-between space-y-3">
        <div class="flex items-start justify-between">
          <div>
            <span class="text-[11px] font-bold text-[#5A6270] dark:text-[#8B949E] uppercase tracking-wider">{{ $t('farmer.primaryUnionCoops') }}</span>
            <h3 class="text-2xl sm:text-3xl font-black text-[#1E2328] dark:text-[#F0F6FC] mt-1">{{ farmer?.union ? 1 : 0 }}</h3>
          </div>
          <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-[#1E9444] dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/60 flex items-center justify-center shrink-0">
            <UserCheck class="w-4 h-4" />
          </div>
        </div>
        <span class="text-[11px] font-semibold text-[#1E9444] dark:text-emerald-400">{{ $t(farmer?.union) || $t('None') }}</span>
      </div>

      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all relative overflow-hidden group flex flex-col justify-between space-y-3">
        <div class="flex items-start justify-between">
          <div>
            <span class="text-[11px] font-bold text-[#5A6270] dark:text-[#8B949E] uppercase tracking-wider">{{ $t('Verified Buyers') }}</span>
            <h3 class="text-2xl sm:text-3xl font-black text-[#1E2328] dark:text-[#F0F6FC] mt-1">{{ verifiedBuyersCount }}</h3>
          </div>
          <div class="w-9 h-9 rounded-xl bg-[#0B57D0]/10 dark:bg-blue-950/40 text-[#0B57D0] dark:text-blue-400 border border-blue-100 dark:border-blue-800/60 flex items-center justify-center shrink-0">
            <Users class="w-4 h-4" />
          </div>
        </div>
        <span class="text-[11px] font-semibold text-[#0B57D0] dark:text-blue-400">{{ $t('Commercial Escrow Buyers') }}</span>
      </div>

      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all relative overflow-hidden group flex flex-col justify-between space-y-3">
        <div class="flex items-start justify-between">
          <div>
            <span class="text-[11px] font-bold text-[#5A6270] dark:text-[#8B949E] uppercase tracking-wider">{{ $t('Orders Received') }}</span>
            <h3 class="text-2xl sm:text-3xl font-black text-[#1E2328] dark:text-[#F0F6FC] mt-1">{{ receivedOrdersCount }}</h3>
          </div>
          <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-[#E69500] dark:text-amber-300 border border-amber-100 dark:border-amber-800/60 flex items-center justify-center shrink-0">
            <PackageCheck class="w-4 h-4" />
          </div>
        </div>
        <span class="text-[11px] font-semibold text-amber-700 dark:text-amber-300">{{ $t('Pending Escrow Release:') }} {{ formatETB(pendingPayoutETB) }}</span>
      </div>

      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all relative overflow-hidden group flex flex-col justify-between space-y-3">
        <div class="flex items-start justify-between">
          <div>
            <span class="text-[11px] font-bold text-[#5A6270] dark:text-[#8B949E] uppercase tracking-wider">{{ $t('SMS Dispatch Notifications') }}</span>
            <h3 class="text-2xl sm:text-3xl font-black text-[#1E2328] dark:text-[#F0F6FC] mt-1">{{ smsDispatchCount }}</h3>
          </div>
          <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-[#1E9444] dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/60 flex items-center justify-center shrink-0">
            <Smartphone class="w-4 h-4" />
          </div>
        </div>
        <span class="text-[11px] font-semibold text-[#1E9444] dark:text-emerald-400">{{ $t('Active Mobile SMS Channel') }}</span>
      </div>
    </div>

    <!-- LIVE ORDER TRANSIT STATUS CARD -->
    <div v-if="orders.length > 0 && orders[0].status === 'in_transit'" @click="$router.push('/farmer/orders')"
      class="w-full bg-[#EDFAF2] dark:bg-emerald-950/40 border border-[#C3EFCF] dark:border-emerald-800/60 rounded-2xl p-4 flex items-center justify-between cursor-pointer hover:bg-[#D8F6E0] dark:hover:bg-emerald-900/40 transition-colors shadow-2xs">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-[#1E9444] text-white flex items-center justify-center shrink-0 shadow-xs"><Truck class="w-5 h-5" /></div>
        <div>
          <div class="flex items-center gap-2">
            <h4 class="text-xs font-extrabold text-[#0F5C2A] dark:text-emerald-300">{{ $t('orders.orderId') }} #{{ orders[0].id }} {{ $t('in_transit') }}</h4>
            <span class="px-2 py-0.5 rounded-full bg-[#1E9444] text-white text-[10px] font-extrabold">{{ $t('orders.paymentSecured') }}</span>
          </div>
          <p class="text-[11px] text-[#5A6270] dark:text-[#8B949E] mt-0.5 font-medium">{{ $t('Batch on its way to buyer hub.') }}</p>
        </div>
      </div>
      <ChevronRight class="w-5 h-5 text-[#1E9444] dark:text-emerald-400 shrink-0" />
    </div>

    <!-- PRODUCE LISTINGS SECTION -->
    <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-5 shadow-xs space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 dark:border-[#21262D] pb-3">
        <div class="flex items-center gap-2">
          <div class="w-2.5 h-2.5 rounded-full bg-[#1E9444] animate-pulse" />
          <h3 class="text-sm sm:text-base font-black text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('Produce & Order Status') }}</h3>
          <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-md border border-emerald-200 dark:border-emerald-800/60">• {{ $t('• Live just now') }}</span>
        </div>

        <div class="flex items-center gap-2">
          <router-link to="/farmer/listings" class="px-3.5 py-1.5 rounded-xl border border-gray-200 dark:border-[#30363D] bg-[#F8F9FA] dark:bg-[#21262D] hover:bg-gray-100 dark:hover:bg-[#30363D] text-xs font-bold text-[#1E2328] dark:text-[#F0F6FC] transition-colors flex items-center gap-1 cursor-pointer">
            <span>{{ $t('See All Listings') }}</span><ChevronRight class="w-3.5 h-3.5 text-[#1E9444] dark:text-emerald-400" />
          </router-link>
          <router-link to="/farmer/listings/new" class="px-4 py-1.5 rounded-xl bg-[#1E9444] text-white font-bold text-xs shadow-xs hover:bg-[#0F5C2A] transition-colors flex items-center gap-1.5 cursor-pointer">
            <Plus class="w-4 h-4 stroke-[2.5]" /><span>{{ $t('farmer.postNewListing') }}</span>
          </router-link>
        </div>
      </div>

      <div v-if="displayListings.length === 0" class="text-[#5A6270] dark:text-[#8B949E] text-xs text-center py-5">
        {{ $t('farmer.noListingsFound') }}
      </div>
      <div v-else class="space-y-2.5">
        <div v-for="item in displayListings" :key="item.id" @click="$router.push(`/farmer/listings/edit/${item.id}`)"
          class="bg-[#F8F9FA] dark:bg-[#21262D] border border-[#E2E4E7] dark:border-[#30363D] rounded-xl p-3.5 flex items-center justify-between hover:border-[#1E9444] hover:bg-white dark:hover:bg-[#161B22] transition-all cursor-pointer shadow-2xs">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-white dark:bg-[#161B22] border border-gray-200 dark:border-[#30363D] flex items-center justify-center text-2xl shrink-0 shadow-2xs overflow-hidden">
              <img v-if="item.primaryImage || (item.images && item.images.length > 0)" :src="item.primaryImage || item.images[0]" class="w-full h-full object-cover" />
              <span v-else>{{ item.cropEmoji }}</span>
            </div>
            <div>
              <h4 class="text-xs font-black text-[#1E2328] dark:text-[#F0F6FC]">{{ $t(item.cropName) }}</h4>
              <p class="text-[11px] text-[#5A6270] dark:text-[#8B949E] mt-0.5">{{ $t(farmer?.region) || 'Addis Ababa' }} {{ $t('Region · Verified Harvest') }}</p>
            </div>
          </div>
          <div class="text-right flex items-center gap-4">
            <div>
              <span class="text-xs font-black text-[#1E2328] dark:text-[#F0F6FC] block">{{ formatETB(item.pricePerKg) }}/{{ item.unit || 'kg' }}</span>
              <span class="text-[10px] font-bold text-[#1E9444] dark:text-emerald-400">{{ item.availableQty?.toLocaleString() }} {{ item.unit || 'kg' }} {{ $t('available') }}</span>
            </div>
            <span class="hidden sm:inline-block px-2.5 py-1 rounded-full text-[10px] font-black bg-[#EDFAF2] dark:bg-emerald-950/40 text-[#0F5C2A] dark:text-emerald-300 border border-[#C3EFCF] dark:border-emerald-800/60">{{ $t('badges.live') }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Plus, Truck, ChevronRight, Zap, Building2, UserCheck, Users, PackageCheck, Smartphone, ShieldCheck, Sparkles } from 'lucide-vue-next'
import { useAuth } from '@/composables/useAuth'
import { useListings } from '@/composables/useListings'
import { useOrders } from '@/composables/useOrders'
import { api } from '@/services/api'
import { formatETB } from '@/utils/helpers'

const { user } = useAuth()
const farmer = computed(() => user.value)
const { listings } = useListings()
const { orders } = useOrders()

const payoutSummary = ref({
  total_paid_out: 0,
  pending_escrow_amount: 0,
})

onMounted(async () => {
    try {
        const data = await api.fetchPayoutSummary()
        if (data && data.summary) {
            payoutSummary.value = data.summary
        }
    } catch {
       // fallback silently
    }
})

const farmerListings = computed(() => {
  if (!farmer.value) return []
  return listings.value.filter(l => 
    String(l.farmerId) === String(farmer.value.id) || 
    String(l.farmer?.id) === String(farmer.value.id) || 
    (farmer.value.phone && l.farmer?.phone === farmer.value.phone)
  )
})
const displayListings = computed(() => farmerListings.value.slice(0, 3))
const activeCount = computed(() => farmerListings.value.filter(l => l.isActive).length || 0)

const farmerOrders = computed(() => {
  if (!farmer.value) return []
  return orders.value.filter(o => 
    String(o.farmerId) === String(farmer.value.id) || 
    String(o.farmer?.id) === String(farmer.value.id) || 
    (farmer.value.phone && o.farmer?.phone === farmer.value.phone)
  )
})

const totalEarnedETB = computed(() => {
    if (payoutSummary.value.total_paid_out > 0) return payoutSummary.value.total_paid_out;
    return farmerOrders.value.filter(o => o.status === 'delivered').reduce((sum, o) => sum + (o.totalAmountETB || 0), 0) || (farmer.value?.totalEarned || 0);
})

const pendingPayoutETB = computed(() => {
    if (payoutSummary.value.pending_escrow_amount > 0) return payoutSummary.value.pending_escrow_amount;
    return farmerOrders.value.filter(o => o.escrowStatus === 'held').reduce((sum, o) => sum + (o.totalAmountETB || 0), 0) || 0;
})

const receivedOrdersCount = computed(() => farmerOrders.value.length || 0)

const verifiedBuyersCount = computed(() => {
    const buyers = new Set()
    farmerOrders.value.forEach(o => {
        if (o.buyerId) buyers.add(o.buyerId)
        if (o.buyer?.id) buyers.add(o.buyer?.id)
    })
    return buyers.size
})

const regionalDepotsCount = computed(() => {
    const depots = new Set()
    farmerOrders.value.forEach(o => {
        if (o.buyer?.deliveryHub) depots.add(o.buyer.deliveryHub)
        if (o.deliveryHub) depots.add(o.deliveryHub)
    })
    return depots.size
})

const smsDispatchCount = computed(() => {
    return farmerOrders.value.filter(o => o.status !== 'pending' && o.status !== 'placed').length
})

</script>
