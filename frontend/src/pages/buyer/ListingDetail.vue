<template>
  <div>
    <div class="space-y-6 max-w-3xl">
      <div class="flex items-center gap-3 mb-2">
        <button @click="$router.back()" class="w-9 h-9 rounded-xl border border-[#E2E4E7] dark:border-[#30363D] flex items-center justify-center hover:bg-[#F0F1F2] dark:hover:bg-[#21262D] transition-colors bg-white dark:bg-[#161B22] shadow-2xs">
          <ArrowLeft class="w-5 h-5 text-[#1E2328] dark:text-[#F0F6FC]" />
        </button>
        <span class="text-xs font-bold text-[#5A6270] dark:text-[#8B949E] uppercase tracking-wider">{{ t('backToMarketplace') }}</span>
      </div>

      <div v-if="listing" class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl overflow-hidden shadow-sm">
        
        <!-- Native Images Gallery or Emoji Fallback -->
        <div v-if="displayImages.length > 0" class="flex flex-col">
          <div class="h-64 md:h-80 w-full overflow-hidden bg-black flex items-center justify-center">
            <img :src="displayImages[activeImageIndex]" class="max-h-full max-w-full object-contain object-center" />
          </div>
          <div v-if="displayImages.length > 1" class="flex gap-2 p-3 overflow-x-auto bg-[#F8F9FA] dark:bg-[#21262D] border-b border-[#E2E4E7] dark:border-[#30363D]">
            <img 
              v-for="(img, idx) in displayImages" :key="idx" 
              :src="img" 
              @click="activeImageIndex = idx"
              class="w-16 h-16 object-cover rounded-lg cursor-pointer border-2 transition-all"
              :class="activeImageIndex === idx ? 'border-[#1E9444] shadow-md opacity-100' : 'border-[#E2E4E7] dark:border-[#30363D] opacity-60 hover:opacity-100'" 
            />
          </div>
        </div>
        <div v-else class="h-48 bg-gradient-to-br from-[#062E15] to-[#1E9444] flex items-center justify-center">
          <span class="text-7xl">{{ listing.cropEmoji }}</span>
        </div>
        
        <div class="p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h1 class="text-[20px] font-black text-[#1E2328] dark:text-[#F0F6FC]">{{ listing.cropName }}</h1>
            <VerifiedBadge v-if="listing.isVerified" />
          </div>

          <p class="text-[13px] text-[#5A6270] dark:text-[#8B949E] leading-relaxed">{{ listing.description }}</p>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-[13px]">
            <div class="bg-[#F8F9FA] dark:bg-[#21262D] p-3 rounded-xl">
              <span class="font-bold text-[#5A6270] dark:text-[#8B949E] block text-[11px]">{{ t('price') }}</span>
              <span class="font-black text-[#1E9444] dark:text-emerald-400">{{ formatETB(listing.pricePerKg) }}/{{ listing.unit || 'kg' }}</span>
            </div>
            <div class="bg-[#F8F9FA] dark:bg-[#21262D] p-3 rounded-xl">
              <span class="font-bold text-[#5A6270] dark:text-[#8B949E] block text-[11px]">{{ t('availableStock') }}</span>
              <span class="font-black text-[#1E2328] dark:text-[#F0F6FC]">{{ listing.availableQty?.toLocaleString() }} {{ listing.unit || 'kg' }}</span>
            </div>
            <div class="bg-[#F8F9FA] dark:bg-[#21262D] p-3 rounded-xl">
              <span class="font-bold text-[#5A6270] dark:text-[#8B949E] block text-[11px]">{{ t('grade') }}</span>
              <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ listing.grade }}</span>
            </div>
            <div class="bg-[#F8F9FA] dark:bg-[#21262D] p-3 rounded-xl">
              <span class="font-bold text-[#5A6270] dark:text-[#8B949E] block text-[11px]">{{ t('region') }}</span>
              <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ listing.region }}</span>
            </div>
          </div>

          <!-- Producer & Payout Information Card -->
          <div class="bg-[#F4FBF7] dark:bg-[#0E2014] border border-[#C3EFCF] dark:border-emerald-900/60 rounded-2xl p-5 space-y-4 text-xs">
            <div class="flex items-center justify-between border-b border-[#C3EFCF]/60 dark:border-emerald-900/60 pb-3">
              <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-full bg-[#1E9444] text-white font-black flex items-center justify-center text-sm shadow-xs shrink-0">
                  {{ listing.farmer?.name?.[0] || 'F' }}
                </div>
                <div>
                  <h3 class="text-sm font-black text-[#0F5C2A] dark:text-emerald-300 flex items-center gap-1.5">
                    <span>{{ listing.farmer?.name || 'Verified Farmer' }}</span>
                    <ShieldCheck class="w-4 h-4 text-[#1E9444] dark:text-emerald-400" />
                  </h3>
                  <p class="text-[11px] text-[#5A6270] dark:text-[#8B949E] font-medium">Verified Agricultural Producer • {{ listing.region }}</p>
                </div>
              </div>
              <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-[#1E9444] text-white shadow-2xs">
                Verified Seller
              </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
              <!-- Farmer Phone -->
              <div class="bg-white dark:bg-[#161B22] p-3.5 rounded-xl border border-[#E2E4E7] dark:border-[#30363D] flex items-center gap-3 shadow-2xs">
                <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-[#1E9444] dark:text-emerald-400 rounded-xl shrink-0">
                  <Phone class="w-4 h-4" />
                </div>
                <div class="overflow-hidden">
                  <span class="text-[#5A6270] dark:text-[#8B949E] font-bold block text-[10px] uppercase tracking-wider">Farmer Direct Phone</span>
                  <a :href="'tel:' + (listing.farmer?.phone || '')" class="font-black text-[#1E2328] dark:text-[#F0F6FC] hover:text-[#1E9444] dark:hover:text-emerald-400 text-xs transition-colors block truncate">
                    {{ listing.farmer?.phone || '+251 912 345 678' }}
                  </a>
                </div>
              </div>

              <!-- Payout Account / CBE / Telebirr -->
              <div class="bg-white dark:bg-[#161B22] p-3.5 rounded-xl border border-[#E2E4E7] dark:border-[#30363D] flex items-center gap-3 shadow-2xs">
                <div class="p-2.5 bg-blue-50 dark:bg-blue-950/40 text-[#0B57D0] dark:text-blue-400 rounded-xl shrink-0">
                  <CreditCard class="w-4 h-4" />
                </div>
                <div class="overflow-hidden">
                  <span class="text-[#5A6270] dark:text-[#8B949E] font-bold block text-[10px] uppercase tracking-wider">Payout Account (CBE / Bank)</span>
                  <div class="font-black text-[#1E2328] dark:text-[#F0F6FC] text-xs flex items-center gap-1.5 truncate mt-0.5">
                    <span class="uppercase text-[9px] px-1.5 py-0.2 bg-blue-50 dark:bg-blue-950/40 text-[#0B57D0] dark:text-blue-400 border border-blue-200 dark:border-blue-800/60 rounded font-black shrink-0">
                      {{ listing.farmer?.bank_code || listing.farmer?.bank_name || 'CBE' }}
                    </span>
                    <span class="font-mono text-xs">{{ listing.farmer?.account_number || listing.farmer?.account_number_masked || '1000123456789' }}</span>
                  </div>
                  <span v-if="listing.farmer?.account_name" class="text-[10px] text-gray-500 dark:text-gray-400 font-bold block truncate mt-0.5">
                    Holder: {{ listing.farmer.account_name }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <EscrowBanner />

          <!-- Purchase / Checkout Action Button -->
          <button 
            @click="handleCheckoutClick" 
            class="block w-full py-3.5 rounded-xl bg-[#1E9444] hover:bg-[#0F5C2A] text-white font-extrabold text-[15px] text-center shadow-md transition-all cursor-pointer"
          >
            {{ t('buyNow') }}
          </button>
        </div>
      </div>

      <div v-else class="text-center py-12 bg-white dark:bg-[#161B22] rounded-2xl border border-gray-200 dark:border-[#30363D] p-6 space-y-2">
        <p class="text-[#5A6270] dark:text-[#8B949E] font-bold">{{ t('noListingsFound') }}</p>
        <router-link to="/marketplace" class="text-[#1E9444] dark:text-emerald-400 font-bold hover:underline inline-block">{{ t('agriMarketplace') }}</router-link>
      </div>
    </div>



    <!-- BUYER AUTH RESTRICTION MODAL -->
    <BuyerAuthModal :isOpen="showAuthModal" @close="showAuthModal = false" />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { User, UserPlus, Phone, CreditCard, ShieldCheck, Building2, ArrowLeft } from 'lucide-vue-next'
import { useListings } from '@/composables/useListings'
import { useAuth } from '@/composables/useAuth'
import { useLanguage } from '@/composables/useLanguage'
import { formatETB } from '@/utils/helpers'
import QelemMedaLogo from '@/components/common/QelemMedaLogo.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import LanguageToggle from '@/components/common/LanguageToggle.vue'
import VerifiedBadge from '@/components/shared/VerifiedBadge.vue'
import EscrowBanner from '@/components/shared/EscrowBanner.vue'
import BuyerAuthModal from '@/components/shared/BuyerAuthModal.vue'

const route = useRoute()
const router = useRouter()
const { getListingById } = useListings()
const { isAuthenticated, user } = useAuth()
const { t } = useLanguage()

const listing = computed(() => getListingById(route.params.id))
const activeImageIndex = ref(0)
const showAuthModal = ref(false)

const displayImages = computed(() => {
  if (!listing.value) return []
  const imgs = []
  if (listing.value.primaryImage) {
    imgs.push(listing.value.primaryImage)
  }
  if (Array.isArray(listing.value.images)) {
    listing.value.images.forEach(img => {
      const url = typeof img === 'string' ? img : (img?.image_path || img?.url)
      if (url) {
        const fullUrl = (url.startsWith('http') || url.startsWith('blob:') || url.startsWith('data:'))
          ? url 
          : `http://127.0.0.1:8000/storage/${url.replace(/^\/?storage\//, '')}`
        if (!imgs.includes(fullUrl)) imgs.push(fullUrl)
      }
    })
  }
  if (imgs.length === 0 && (listing.value.image_url || listing.value.image_path)) {
    const url = listing.value.image_url || listing.value.image_path
    if (url) {
      const fullUrl = (url.startsWith('http') || url.startsWith('blob:') || url.startsWith('data:'))
        ? url 
        : `http://127.0.0.1:8000/storage/${url.replace(/^\/?storage\//, '')}`
      if (!imgs.includes(fullUrl)) imgs.push(fullUrl)
    }
  }
  return imgs
})



watch(listing, () => activeImageIndex.value = 0)

function goToDashboard() {
  if (user.value?.role === 'farmer') router.push('/farmer')
  else if (user.value?.role === 'admin') router.push('/admin')
  else router.push('/buyer')
}

function handleCheckoutClick() {
  if (!isAuthenticated.value) {
    showAuthModal.value = true
  } else {
    router.push(`/buyer/checkout/${listing.value.id}`)
  }
}
</script>
