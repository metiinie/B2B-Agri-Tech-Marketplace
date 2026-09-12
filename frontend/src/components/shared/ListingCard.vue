<template>
  <!-- Row Variant -->
  <div v-if="variant === 'row'" @click="handleClick"
    :class="['flex items-center gap-3 p-3 bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl hover:border-[#1E9444] hover:bg-[#F8F9FA] dark:hover:bg-[#21262D] transition-all cursor-pointer shadow-2xs group', className]">
    <div class="w-12 h-12 rounded-xl bg-[#F0F1F2] dark:bg-[#21262D] flex items-center justify-center text-2xl shrink-0 group-hover:scale-105 transition-transform overflow-hidden">
      <img v-if="cardImage" :src="cardImage" class="w-full h-full object-cover" />
      <span v-else>{{ listing.cropEmoji }}</span>
    </div>
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-1.5">
        <h4 class="text-[14px] font-bold text-[#1E2328] dark:text-[#F0F6FC] truncate">{{ listing.cropName }}</h4>
        <VerifiedBadge v-if="listing.isVerified" size="sm" />
      </div>
      <p class="text-[12px] text-[#5A6270] dark:text-[#8B949E] truncate font-medium">
        {{ listing.farmer?.name }} · <span class="text-[#1E9444] dark:text-emerald-400 font-semibold">{{ listing.region }} {{ $t('farmer.primaryUnionCoops') }}</span>
      </p>
    </div>
    <div class="flex items-center gap-3 shrink-0">
      <div class="text-right">
        <span class="text-[15px] font-black text-[#1E9444] dark:text-emerald-400 block">{{ formatETB(listing.pricePerKg) }}</span>
        <span class="text-[11px] text-[#5A6270] dark:text-[#8B949E] font-medium uppercase tracking-wider">Per {{ listing.unit || 'kg' }}</span>
      </div>
      <button type="button" @click.stop="handleCartClick"
        :class="['w-9 h-9 rounded-xl flex items-center justify-center transition-colors shadow-2xs cursor-pointer',
          isAddedToCart ? 'bg-emerald-600 text-white' : 'bg-emerald-50 dark:bg-emerald-950/40 hover:bg-[#1E9444] text-[#1E9444] dark:text-emerald-400 hover:text-white dark:hover:text-white border border-emerald-200 dark:border-emerald-800/60']">
        <Check v-if="isAddedToCart" class="w-4 h-4" />
        <ShoppingCart v-else class="w-4 h-4" />
      </button>
    </div>
  </div>

  <!-- Grid Variant -->
  <div v-else @click="handleClick"
    :class="['bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-3xl overflow-hidden hover:border-[#1E9444] transition-all duration-200 cursor-pointer shadow-2xs hover:shadow-md flex flex-col group', className]">
    <div :class="['h-[135px] bg-gradient-to-br relative flex items-center justify-center overflow-hidden', getCategoryGradient(listing.category)]">
      <div class="absolute inset-0 bg-black/10 group-hover:bg-black/0 transition-colors" />
      <img v-if="cardImage" :src="cardImage" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 relative z-10" />
      <span v-else class="text-6xl drop-shadow-lg select-none group-hover:scale-110 transition-transform duration-200 relative z-10">
        {{ listing.cropEmoji }}
      </span>
      <div class="absolute top-2.5 left-2.5 z-20">
        <VerifiedBadge v-if="listing.isVerified" size="sm" />
      </div>
      <div class="absolute top-2.5 right-2.5 z-20">
        <GradeBadge :grade="listing.grade" />
      </div>
    </div>

    <div class="p-4 flex flex-col flex-1 justify-between gap-3">
      <div>
        <h3 class="text-[15px] font-black text-[#1E2328] dark:text-[#F0F6FC] leading-tight line-clamp-1 group-hover:text-[#1E9444] transition-colors">
          {{ listing.cropName }}
        </h3>
        <p class="text-[12px] text-[#5A6270] dark:text-[#8B949E] mt-1 font-medium truncate flex items-center gap-1">
          <span class="text-gray-800 dark:text-gray-200 font-bold">{{ listing.farmer?.name }}</span>
          <span>·</span>
          <span class="text-[#1E9444] dark:text-emerald-400 font-semibold">{{ listing.region }}</span>
        </p>
      </div>

      <div class="flex items-center justify-between pt-3 border-t border-[#F0F1F2] dark:border-[#21262D]">
        <div>
          <span class="text-[16px] font-black text-[#1E9444] dark:text-emerald-400 block leading-none">{{ formatETB(listing.pricePerKg) }}</span>
          <span class="text-[10px] text-[#5A6270] dark:text-[#8B949E] font-bold">
            {{ listing.availableQty >= 1000 ? `${(listing.availableQty / 1000).toFixed(1)} ${$t('common.tons')}` : `${listing.availableQty?.toLocaleString()} ${listing.unit || 'kg'}` }}
          </span>
        </div>
        <button type="button" @click.stop="handleCartClick"
          :class="['w-10 h-10 rounded-2xl flex items-center justify-center transition-all cursor-pointer shadow-2xs',
            isAddedToCart ? 'bg-emerald-700 text-white scale-105' : 'bg-[#EDFAF2] dark:bg-emerald-950/40 hover:bg-[#1E9444] text-[#1E9444] dark:text-emerald-400 hover:text-white dark:hover:text-white border border-[#C3EFCF] dark:border-emerald-800/60 hover:shadow-xs']">
          <Check v-if="isAddedToCart" class="w-4 h-4 stroke-[2.5]" />
          <ShoppingCart v-else class="w-4 h-4 stroke-[2]" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ShoppingCart, Check } from 'lucide-vue-next'
import VerifiedBadge from './VerifiedBadge.vue'
import GradeBadge from './GradeBadge.vue'
import { formatETB } from '@/utils/helpers'
import { useCart } from '@/composables/useCart'
import { useAuth } from '@/composables/useAuth'

const props = defineProps({
  listing: { type: Object, required: true },
  variant: { type: String, default: 'grid' },
  className: { type: String, default: '' },
  isAddedToCart: { type: Boolean, default: false },
})

const emit = defineEmits(['addToCart'])
const router = useRouter()
const route = useRoute()
const { cartItems, addToCart, removeFromCart } = useCart()
const { isAuthenticated } = useAuth()

const isAddedToCart = computed(() => {
  if (props.isAddedToCart) return true
  return cartItems.value.some(item => 
    String(item.listingId) === String(props.listing.id) || 
    String(item.listing?.id) === String(props.listing.id)
  )
})

const cardImage = computed(() => {
  const img = props.listing.primaryImage || 
              (props.listing.images && props.listing.images.length > 0 ? props.listing.images[0] : null) || 
              props.listing.image_url || 
              props.listing.image_path
  if (!img) return null
  if (typeof img === 'string') {
    if (img.startsWith('http') || img.startsWith('blob:') || img.startsWith('data:')) return img
    return `http://127.0.0.1:8000/storage/${img.replace(/^\/?storage\//, '')}`
  }
  if (typeof window !== 'undefined' && (img instanceof File || img instanceof Blob)) {
    return URL.createObjectURL(img)
  }
  return null
})

const getCategoryGradient = (category) => {
  const gradients = {
    coffee: 'from-[#3E2723] via-[#4E342E] to-[#0F5C2A]',
    grains: 'from-[#2E7D32] via-[#388E3C] to-[#1E9444]',
    spices: 'from-[#D84315] via-[#E64A19] to-[#BF360C]',
    oilseeds: 'from-[#F57F17] via-[#FB8C00] to-[#E65100]',
    pulses: 'from-[#5D4037] via-[#6D4C41] to-[#3E2723]',
    roots: 'from-[#6A1B9A] via-[#8E24AA] to-[#4A148C]',
    fruits: 'from-[#EF6C00] via-[#F57C00] to-[#E65100]',
    vegetables: 'from-[#1B5E20] via-[#2E7D32] to-[#1E9444]',
  }
  return gradients[category] || 'from-[#062E15] via-[#0F5C2A] to-[#1E9444]'
}

const handleClick = () => {
  if (route.path.startsWith('/buyer')) {
    router.push(`/buyer/listing/${props.listing.id}`)
  } else {
    router.push(`/listing/${props.listing.id}`)
  }
}

const handleCartClick = (e) => {
  e?.stopPropagation?.()
  if (!isAuthenticated.value) {
    emit('addToCart', props.listing, e)
    return
  }
  const existing = cartItems.value.find(item => 
    String(item.listingId) === String(props.listing.id) || 
    String(item.listing?.id) === String(props.listing.id)
  )

  if (existing) {
    removeFromCart(existing.id)
  } else {
    addToCart(props.listing)
  }
  emit('addToCart', props.listing, e)
}
</script>
