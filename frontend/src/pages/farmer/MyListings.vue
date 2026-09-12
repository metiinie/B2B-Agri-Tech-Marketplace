<template>
  <div class="w-full flex flex-col min-h-full pb-8 max-w-5xl mx-auto space-y-5">
    <div class="bg-gradient-to-r from-[#062E15] via-[#0F5C2A] to-[#0B57D0] text-white p-6 rounded-3xl shadow-sm relative overflow-hidden">
      <div class="absolute -top-10 -right-10 w-40 h-40 bg-[#E69500]/20 rounded-full blur-2xl pointer-events-none" />
      <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-[#0B57D0]/30 rounded-full blur-2xl pointer-events-none" />
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10">
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">My Produce Listings</h1>
            <Sparkles class="w-5 h-5 text-[#E69500]" />
          </div>
          <p class="text-xs text-[#C3EFCF] mt-1 font-medium">Manage and track your active crop inventory on AgriGate</p>
        </div>
        <router-link to="/farmer/listings/new" class="px-4 py-2.5 rounded-xl bg-[#1E9444] hover:bg-[#0F5C2A] text-white text-xs font-extrabold flex items-center gap-1.5 shadow-xs transition-colors shrink-0 cursor-pointer">
          <Plus class="w-4 h-4 stroke-[2.5]" /><span>Post New Listing</span>
        </router-link>
      </div>
    </div>

    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 px-1">
      <div class="flex gap-2 overflow-x-auto pb-1 sm:pb-0">
        <button @click="statusFilter = 'all'" :class="['px-4 py-2 rounded-xl text-xs font-extrabold border transition-all cursor-pointer', statusFilter === 'all' ? 'bg-[#1E9444] border-[#1E9444] text-white shadow-2xs' : 'bg-white dark:bg-[#161B22] border-[#E2E4E7] dark:border-[#30363D] text-[#5A6270] dark:text-[#8B949E] hover:bg-gray-50 dark:hover:bg-[#21262D]']">
          {{ $t('farmer.allListings') }} ({{ displayListings.length }})
        </button>
        <button @click="statusFilter = 'live'" :class="['px-4 py-2 rounded-xl text-xs font-extrabold border transition-all cursor-pointer', statusFilter === 'live' ? 'bg-[#EDFAF2] dark:bg-emerald-950/40 border-[#C3EFCF] dark:border-emerald-800/60 text-[#0F5C2A] dark:text-emerald-300 shadow-2xs' : 'bg-white dark:bg-[#161B22] border-[#E2E4E7] dark:border-[#30363D] text-[#5A6270] dark:text-[#8B949E] hover:bg-gray-50 dark:hover:bg-[#21262D]']">
          {{ $t('farmer.liveProduce') }} ({{ displayListings.filter(l => l.isActive).length }})
        </button>
        <button @click="statusFilter = 'pending'" :class="['px-4 py-2 rounded-xl text-xs font-extrabold border transition-all cursor-pointer', statusFilter === 'pending' ? 'bg-[#FFF8EC] dark:bg-amber-950/40 border-[#F5B73A] dark:border-amber-800/60 text-[#D88C0A] dark:text-amber-300 shadow-2xs' : 'bg-white dark:bg-[#161B22] border-[#E2E4E7] dark:border-[#30363D] text-[#5A6270] dark:text-[#8B949E] hover:bg-gray-50 dark:hover:bg-[#21262D]']">
          {{ $t('farmer.pendingReview') }} (0)
        </button>
      </div>
      <div class="flex items-center gap-3 self-end sm:self-auto">
        <select 
          v-model="sortBy" 
          class="px-3 py-1.5 bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] text-xs font-bold rounded-lg text-[#5A6270] dark:text-[#8B949E] focus:outline-none focus:border-[#1E9444]"
        >
          <option value="newest">{{ $t('Newest First', 'Newest First') }}</option>
          <option value="oldest">{{ $t('Oldest First', 'Oldest First') }}</option>
          <option value="price_asc">{{ $t('Price: Low to High', 'Price: Low to High') }}</option>
          <option value="price_desc">{{ $t('Price: High to Low', 'Price: High to Low') }}</option>
        </select>
        <div class="text-xs text-[#5A6270] dark:text-[#8B949E] font-bold">
          {{ $t('farmer.showingActiveItems', { count: filteredListings.length }) }}
        </div>
      </div>
    </div>

    <!-- EMPTY STATE FOR FARMER WITH NO LISTINGS -->
    <div v-if="filteredListings.length === 0" class="text-center py-16 bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-3xl p-8 shadow-2xs">
      <div class="w-16 h-16 mx-auto bg-[#EDFAF2] dark:bg-emerald-950/40 rounded-2xl flex items-center justify-center mb-3 border border-[#C3EFCF] dark:border-emerald-800/60">
        <Sprout class="w-8 h-8 text-[#1E9444] dark:text-emerald-400" />
      </div>
      <h3 class="text-base font-extrabold text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('farmer.noListingsFound') }}</h3>
      <p class="text-xs text-[#5A6270] dark:text-[#8B949E] mt-1 max-w-sm mx-auto font-medium">
        {{ $t('farmer.noListingsSub') }}
      </p>
      <router-link to="/farmer/listings/new" class="inline-flex items-center gap-2 mt-4 px-4.5 py-2.5 rounded-xl bg-[#1E9444] hover:bg-[#0F5C2A] text-white text-xs font-extrabold shadow-sm transition-all cursor-pointer">
        <Plus class="w-4 h-4 stroke-[2.5]" />
        <span>{{ $t('farmer.postFirstListing') }}</span>
      </router-link>
    </div>

    <div v-else class="space-y-4">
      <div class="space-y-2.5">
        <div v-for="item in paginatedListings" :key="item.id" 
          class="bg-white dark:bg-[#161B22] border border-[#FBE3D0] dark:border-[#30363D] rounded-xl px-4 py-3 shadow-2xs hover:border-[#E69500] transition-all">
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <!-- Left: Image/Emoji & Title/Grade -->
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-[#21262D] border border-orange-100 dark:border-[#30363D] flex items-center justify-center text-xl shrink-0 shadow-2xs overflow-hidden">
                <img v-if="getListingImage(item)" :src="getListingImage(item)" class="w-full h-full object-cover" />
                <span v-else>{{ item.cropEmoji }}</span>
              </div>
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="text-sm font-black text-[#1E2328] dark:text-[#F0F6FC]">{{ $t(item.cropName) }}</h3>
                  <span class="text-[9px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 flex items-center gap-1">
                    <CheckCircle2 class="w-3 h-3 text-[#1E9444] dark:text-emerald-400" /> {{ $t('badges.verifiedGrade') }}
                  </span>
                  <span class="text-[10px] text-[#5A6270] dark:text-[#8B949E] font-medium">
                    #{{ String(item.id || '').slice(-6) }}
                  </span>
                </div>
                <p class="text-[11px] text-[#5A6270] dark:text-[#8B949E] mt-0.5 font-medium">
                  {{ $t(farmer?.region) || item.region || 'Sidama' }} {{ $t('Region') }} · {{ $t(item.grade) || 'Grade 1' }}
                </p>
              </div>
            </div>

            <!-- Middle: Price, Quantity, MOQ -->
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
              <div>
                <span class="text-[10px] text-[#5A6270] dark:text-[#8B949E] block uppercase font-bold">{{ $t('marketplace.pricePerKg') }}</span>
                <span class="font-black text-[#1E9444] dark:text-emerald-400">{{ formatETB(item.pricePerKg) }}/{{ $t('kg') }}</span>
              </div>
              <div class="border-l border-gray-200 dark:border-[#30363D] pl-3">
                <span class="text-[10px] text-[#5A6270] dark:text-[#8B949E] block uppercase font-bold">{{ $t('marketplace.availableQuantity') }}</span>
                <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ item.availableQty?.toLocaleString() }} kg</span>
              </div>
              <div class="border-l border-gray-200 dark:border-[#30363D] pl-3">
                <span class="text-[10px] text-[#5A6270] dark:text-[#8B949E] block uppercase font-bold">MOQ</span>
                <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ item.minOrderQty ? `${item.minOrderQty.toLocaleString()} kg` : '500 kg' }}</span>
              </div>
            </div>

            <!-- Right: Status Badge & Actions -->
            <div class="flex items-center justify-between lg:justify-end gap-2.5 shrink-0 pt-2 lg:pt-0 border-t lg:border-t-0 border-gray-100 dark:border-[#30363D]">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-[#EDFAF2] dark:bg-emerald-950/40 text-[#0F5C2A] dark:text-emerald-300 border border-[#C3EFCF] dark:border-emerald-800/60">
                {{ $t('badges.live') }}
              </span>

              <div class="flex items-center gap-1.5">
                <button @click="openDeleteModal(item)" 
                  class="px-2.5 py-1 rounded-lg border border-red-200 dark:border-red-900/50 bg-white dark:bg-[#161B22] hover:bg-red-50 dark:hover:bg-red-950/30 text-red-600 dark:text-red-400 font-bold text-xs transition-colors flex items-center gap-1 cursor-pointer shadow-2xs">
                  <Trash2 class="w-3.5 h-3.5" /><span>{{ $t('common.delete') || 'Delete' }}</span>
                </button>
                <router-link :to="`/farmer/listings/edit/${item.id}`" 
                  class="px-2.5 py-1 rounded-lg border border-[#FBE3D0] bg-white dark:bg-[#161B22] hover:bg-orange-50/50 text-[#1E9444] font-bold text-xs transition-colors flex items-center gap-1 cursor-pointer shadow-2xs">
                  <Edit2 class="w-3.5 h-3.5" /><span>{{ $t('Edit') }}</span>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination Controls -->
      <Pagination 
        :currentPage="currentPage" 
        :totalPages="totalPages" 
        :totalItems="filteredListings.length" 
        :itemsPerPage="itemsPerPage" 
        @update:currentPage="currentPage = $event" 
        @refresh="refreshListings"
      />
    </div>

    <!-- Custom Delete Confirmation Modal (Sign Out Style) -->
    <DeleteListingModal 
      :isOpen="isDeleteModalOpen" 
      :listingTitle="targetListingToDelete ? $t(targetListingToDelete.cropName) || targetListingToDelete.cropName : ''" 
      :isDeleting="isDeleting" 
      @close="closeDeleteModal" 
      @confirm="confirmDelete" 
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Plus, Edit2, Package, CheckCircle2, Sparkles, Trash2, Sprout } from 'lucide-vue-next'
import { useListings } from '@/composables/useListings'
import { useAuth } from '@/composables/useAuth'
import { formatETB } from '@/utils/helpers'
import Pagination from '@/components/common/Pagination.vue'
import DeleteListingModal from '@/components/common/DeleteListingModal.vue'

const { listings, deleteListing, refreshListings } = useListings()
const { user } = useAuth()
const farmer = computed(() => user.value)
const statusFilter = ref('all')
const sortBy = ref('newest')

const currentPage = ref(1)
const itemsPerPage = 6

const isDeleteModalOpen = ref(false)
const targetListingToDelete = ref(null)
const isDeleting = ref(false)

const openDeleteModal = (item) => {
  targetListingToDelete.value = item
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  if (isDeleting.value) return
  isDeleteModalOpen.value = false
  targetListingToDelete.value = null
}

const confirmDelete = async () => {
  if (!targetListingToDelete.value) return
  isDeleting.value = true
  try {
    await deleteListing(targetListingToDelete.value.id)
  } finally {
    isDeleting.value = false
    isDeleteModalOpen.value = false
    targetListingToDelete.value = null
  }
}

const getListingImage = (item) => {
  const img = item.primaryImage || 
              (item.images && item.images.length > 0 ? item.images[0] : null) || 
              item.image_url || 
              item.image_path
  if (!img) return null
  if (typeof img === 'string') {
    if (img.startsWith('http') || img.startsWith('blob:') || img.startsWith('data:')) return img
    return `http://127.0.0.1:8000/storage/${img.replace(/^\/?storage\//, '')}`
  }
  return null
}

const farmerListings = computed(() => {
  if (!farmer.value) return []
  return listings.value.filter(l => 
    String(l.farmerId) === String(farmer.value.id) || 
    String(l.farmer?.id) === String(farmer.value.id) || 
    (farmer.value.phone && l.farmer?.phone === farmer.value.phone)
  )
})
const displayListings = computed(() => farmerListings.value.length > 0 ? farmerListings.value : listings.value.slice(0, 4))
const filteredListings = computed(() => {
  let result = displayListings.value.filter(l => {
    if (statusFilter.value === 'live') return l.isActive
    if (statusFilter.value === 'pending') return !l.isActive
    return true
  })

  switch (sortBy.value) {
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
})

const totalPages = computed(() => Math.ceil(filteredListings.value.length / itemsPerPage) || 1)

const paginatedListings = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredListings.value.slice(start, start + itemsPerPage)
})

// Reset to page 1 when filter or sort changes
watch([statusFilter, sortBy], () => {
  currentPage.value = 1
})
</script>

