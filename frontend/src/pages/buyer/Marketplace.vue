<template>
  <div>
    <div class="space-y-6 pb-6">
      <!-- Top Search Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E4E7] dark:border-[#30363D] pb-5">
        <!-- Search Input (Left) -->
        <div class="w-full sm:w-80">
          <div class="relative">
            <Search class="w-4 h-4 text-gray-400 dark:text-gray-500 absolute left-3.5 top-3" />
            <input 
              type="text" 
              v-model="searchQuery" 
              :placeholder="t('searchPlaceholder')" 
              class="w-full pl-9 pr-4 py-2 bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] placeholder-gray-400 dark:placeholder-gray-500 rounded-xl text-xs font-bold focus:outline-none focus:border-[#E69500] shadow-2xs" 
            />
          </div>
        </div>

        <!-- Sort Dropdown (Right edge) -->
        <select 
          v-model="sortBy" 
          class="shrink-0 px-4 py-2 bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] rounded-xl text-xs font-bold focus:outline-none focus:border-[#E69500] shadow-2xs cursor-pointer"
        >
          <option value="newest">{{ t('Newest First', 'Newest First') }}</option>
          <option value="oldest">{{ t('Oldest First', 'Oldest First') }}</option>
          <option value="price_asc">{{ t('Price: Low to High', 'Price: Low to High') }}</option>
          <option value="price_desc">{{ t('Price: High to Low', 'Price: High to Low') }}</option>
        </select>
      </div>

      <!-- Category Filter Chips -->
      <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-0.5">
        <button 
          v-for="cat in categories" 
          :key="cat.value" 
          @click="activeCategory = cat.value"
          :class="['px-3.5 py-2 rounded-xl text-xs font-extrabold border transition-all flex items-center gap-2 shrink-0 cursor-pointer shadow-2xs group', 
            activeCategory === cat.value 
              ? 'bg-[#1E9444] text-white border-[#1E9444] shadow-xs' 
              : 'bg-white dark:bg-[#161B22] text-[#5A6270] dark:text-[#8B949E] border-[#E2E4E7] dark:border-[#30363D] hover:border-[#1E9444] hover:text-[#1E2328] dark:hover:text-[#F0F6FC]']"
        >
          <div class="w-5 h-5 rounded-full overflow-hidden shrink-0 border border-black/10 bg-gray-100 dark:bg-[#21262D] flex items-center justify-center">
            <img v-if="cat.image" :src="cat.image" :alt="cat.label" class="w-full h-full object-cover group-hover:scale-110 transition-transform" />
            <span v-else class="text-[10px]">{{ cat.emoji }}</span>
          </div>
          <span>{{ t(cat.key, cat.label) }}</span>
        </button>
      </div>

      <!-- Listings Grid -->
      <div v-if="filteredListings.length > 0" class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <ListingCard 
            v-for="listing in paginatedListings" 
            :key="listing.id" 
            :listing="listing" 
            @addToCart="handleCartAction"
          />
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

      <!-- Empty State -->
      <div v-else class="text-center text-[#5A6270] dark:text-[#8B949E] py-12 bg-white dark:bg-[#161B22] rounded-2xl border border-[#E2E4E7] dark:border-[#30363D] space-y-2">
        <p class="font-bold text-base text-[#1E2328] dark:text-[#F0F6FC]">{{ t('noListingsFound') }}</p>
        <p class="text-xs">{{ t('tryAdjusting') }}</p>
        <button 
          @click="activeCategory = 'all'; searchQuery = ''" 
          class="mt-2 px-4 py-2 bg-[#E69500] text-white font-extrabold text-xs rounded-xl hover:bg-[#D48900] transition-colors shadow-2xs cursor-pointer"
        >
          {{ t('resetFilters') }}
        </button>
      </div>
    </div>



    <!-- BUYER AUTH RESTRICTION MODAL -->
    <BuyerAuthModal :isOpen="showAuthModal" @close="showAuthModal = false" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Search, User, UserPlus } from 'lucide-vue-next'
import { useAuth } from '@/composables/useAuth'
import { useListings } from '@/composables/useListings'
import { useLanguage } from '@/composables/useLanguage'
import QelemMedaLogo from '@/components/common/QelemMedaLogo.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import LanguageToggle from '@/components/common/LanguageToggle.vue'
import ListingCard from '@/components/shared/ListingCard.vue'
import BuyerAuthModal from '@/components/shared/BuyerAuthModal.vue'
import Pagination from '@/components/common/Pagination.vue'
import { CATEGORY_PHOTOS } from '@/utils/categoryImages'

const route = useRoute()
const router = useRouter()
const { filterListings, refreshListings } = useListings()
const { isAuthenticated, user } = useAuth()
const { t } = useLanguage()

const activeCategory = ref('all')
const searchQuery = ref('')
const sortBy = ref('newest')
const showAuthModal = ref(false)

const currentPage = ref(1)
const itemsPerPage = 6



const categories = [
  { value: 'all', key: 'allCrops', label: 'All Crops', emoji: '🌾', image: CATEGORY_PHOTOS.grains },
  { value: 'coffee', key: 'coffee', label: 'Coffee', emoji: '☕', image: CATEGORY_PHOTOS.coffee },
  { value: 'grains', key: 'grains', label: 'Grains', emoji: '🌾', image: CATEGORY_PHOTOS.grains },
  { value: 'spices', key: 'spices', label: 'Spices', emoji: '🌶️', image: CATEGORY_PHOTOS.spices },
  { value: 'oilseeds', key: 'oilseeds', label: 'Oilseeds', emoji: '🥜', image: CATEGORY_PHOTOS.oilseeds },
  { value: 'pulses', key: 'pulses', label: 'Pulses', emoji: '🫘', image: CATEGORY_PHOTOS.pulses },
  { value: 'vegetables', key: 'vegetables', label: 'Vegetables', emoji: '🥬', image: CATEGORY_PHOTOS.vegetables },
  { value: 'fruits', key: 'fruits', label: 'Fruits', emoji: '🍋', image: CATEGORY_PHOTOS.fruits },
]

const setCategoryFromQuery = () => {
  if (route.query.category) {
    const matched = categories.find(c => c.value === route.query.category)
    if (matched) {
      activeCategory.value = matched.value
    }
  }
}

onMounted(setCategoryFromQuery)
watch(() => route.query.category, setCategoryFromQuery)

const filteredListings = computed(() => filterListings(activeCategory.value, searchQuery.value, sortBy.value))

const totalPages = computed(() => Math.ceil(filteredListings.value.length / itemsPerPage) || 1)

const paginatedListings = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredListings.value.slice(start, start + itemsPerPage)
})

// Reset pagination to page 1 whenever search, category, or sort changes
watch([searchQuery, activeCategory, sortBy], () => {
  currentPage.value = 1
})

function goToDashboard() {
  if (user.value?.role === 'farmer') router.push('/farmer')
  else if (user.value?.role === 'admin') router.push('/admin')
  else router.push('/buyer')
}

function handleCartAction() {
  if (!isAuthenticated.value) {
    showAuthModal.value = true
  }
}
</script>
