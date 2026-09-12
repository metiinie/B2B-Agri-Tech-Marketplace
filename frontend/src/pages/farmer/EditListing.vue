<template>
  <div v-if="listing" class="w-full flex flex-col min-h-full bg-[#F8F9FA] dark:bg-[#0D1117] pb-28">
    <!-- Header -->
    <div class="px-4 py-3.5 bg-white dark:bg-[#161B22] border-b border-[#E2E4E7] dark:border-[#30363D] flex items-center justify-between sticky top-14 z-30 shadow-2xs">
      <div class="flex items-center gap-3">
        <button @click="$router.back()" class="w-9 h-9 rounded-xl border border-[#E2E4E7] dark:border-[#30363D] flex items-center justify-center hover:bg-[#F0F1F2] dark:hover:bg-[#21262D] transition-colors">
          <ArrowLeft class="w-5 h-5 text-[#1E2328] dark:text-[#F0F6FC]" />
        </button>
        <div>
          <h2 class="text-base font-black text-[#1E2328] dark:text-[#F0F6FC] tracking-tight">{{ $t('farmer.editListingTitle') }} #{{ listing.id }}</h2>
          <p class="text-[11px] text-[#5A6270] dark:text-[#8B949E]">Update crop availability, region, pricing, or description</p>
        </div>
      </div>
      <span class="px-2.5 py-1 rounded-full bg-blue-50 dark:bg-blue-950/40 text-[#0B57D0] dark:text-blue-400 border border-blue-200 dark:border-blue-800/60 text-[11px] font-bold">
        Edit Mode
      </span>
    </div>

    <!-- Modern Post UI Form Container -->
    <form @submit.prevent="handleSubmit" class="p-4 md:p-6 max-w-4xl mx-auto w-full space-y-6">
      <!-- Single Main Card -->
      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-3xl p-5 sm:p-6 shadow-sm flex flex-col md:flex-row gap-6 relative">
        
        <!-- Left / Main Column: Post specifics -->
        <div class="flex-1 space-y-5">
          <!-- Textarea acting like a post compose box -->
          <div class="flex gap-3 sm:gap-4">
             <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full border-2 border-blue-500 bg-blue-50 dark:bg-blue-950 flex items-center justify-center font-black text-blue-800 dark:text-blue-300 shrink-0 overflow-hidden shadow-xs">
                <img v-if="user?.avatar" :src="user?.avatar" class="w-full h-full object-cover"/>
                <span v-else class="text-sm sm:text-base">{{ user?.name?.[0] || 'F' }}</span>
             </div>
             <div class="flex-1">
                <textarea 
                  rows="3" 
                  v-model="description" 
                  required 
                  class="w-full bg-transparent border-0 text-lg font-bold resize-none focus:ring-0 p-0 text-[#1E2328] dark:text-[#F0F6FC] placeholder:text-gray-300 dark:placeholder:text-[#5A6270] focus:outline-none"
                  :placeholder="$t('What produce are you posting today? (e.g. Freshly harvested Sidama Coffee...)')" 
                ></textarea>
             </div>
          </div>
          
          <div class="border-t border-gray-100 dark:border-[#30363D] pt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Valuable Columns Only -->
            <div>
              <label class="text-[10px] font-extrabold text-gray-500 dark:text-[#8B949E] uppercase tracking-wider block mb-1.5">{{ $t('farmer.cropName') }} <span class="text-red-500">*</span></label>
              <input type="text" v-model="cropName" required placeholder="e.g. Sidama G1 Coffee" 
                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] rounded-xl text-sm font-bold focus:outline-none focus:border-blue-500 focus:bg-white dark:focus:bg-[#161B22] transition-colors" />
            </div>
            <div>
              <label class="text-[10px] font-extrabold text-gray-500 dark:text-[#8B949E] uppercase tracking-wider block mb-1.5">Category (Cannot edit here)</label>
              <div class="w-full px-4 py-2.5 bg-gray-100 dark:bg-[#30363D] border border-gray-200 dark:border-[#30363D] text-gray-500 dark:text-[#8B949E] rounded-xl text-sm font-bold opacity-80 cursor-not-allowed">
                {{ listing?.category || 'Crop Category' }}
              </div>
            </div>
            <div>
              <label class="text-[10px] font-extrabold text-gray-500 dark:text-[#8B949E] uppercase tracking-wider block mb-1.5">{{ $t('farmer.pricePerKgETB') }} <span class="text-red-500">*</span></label>
              <div class="relative">
                 <span class="absolute left-3 top-[11px] text-sm font-black text-blue-600 dark:text-blue-400">Br</span>
                 <input type="number" v-model.number="pricePerKg" required min="1" step="0.01" placeholder="85.00"
                   class="w-full pl-9 pr-4 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-blue-600 dark:text-[#60A5FA] rounded-xl text-sm font-black focus:outline-none focus:border-blue-500 focus:bg-white dark:focus:bg-[#161B22] transition-colors" />
              </div>
            </div>
            <div>
              <label class="text-[10px] font-extrabold text-gray-500 dark:text-[#8B949E] uppercase tracking-wider block mb-1.5">{{ $t('farmer.availableQuantityKg') }} (KG) <span class="text-red-500">*</span></label>
              <input type="number" v-model.number="availableQty" required min="1" placeholder="5000"
                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] rounded-xl text-sm font-bold focus:outline-none focus:border-blue-500 focus:bg-white dark:focus:bg-[#161B22] transition-colors" />
            </div>
          </div>
        </div>

        <!-- Right / Last Column: Add Photo UI -->
        <div class="md:w-64 shrink-0 flex flex-col gap-3 border-t md:border-t-0 md:border-l border-gray-100 dark:border-[#30363D] pt-4 md:pt-0 md:pl-6">
           <div class="flex items-center justify-between">
             <h3 class="text-[10px] font-extrabold text-gray-500 dark:text-[#8B949E] uppercase tracking-wider">{{ $t('farmer.uploadImages') }}</h3>
             <span class="text-[9px] font-black text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 px-2 py-0.5 rounded-full border border-blue-100 dark:border-blue-800/60">{{ photoPreviews.length }}/5</span>
           </div>
           
           <label for="fileUploadBtn" 
              :class="['flex items-center justify-center gap-2 p-3 border-2 border-dashed rounded-xl cursor-pointer transition-colors',
                photoPreviews.length >= 5 ? 'opacity-50 pointer-events-none border-gray-200 dark:border-[#30363D]' : 'border-blue-200 dark:border-blue-800/60 hover:bg-blue-50 hover:border-blue-500 dark:hover:bg-[#161B22]']">
              <Camera class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0" />
              <span class="text-xs font-black text-blue-600 dark:text-blue-400">{{ $t('Add Photo') }}</span>
              <input type="file" id="fileUploadBtn" multiple accept="image/*" @change="handleFileSelect" class="hidden" :disabled="photoPreviews.length >= 5" />
           </label>

           <div v-if="isCompressing" class="flex items-center justify-center gap-1.5 p-2 bg-blue-50 dark:bg-blue-950/40 rounded-lg text-[10px] text-blue-700 dark:text-blue-300 font-bold animate-pulse">
             <Loader2 class="w-3 h-3 animate-spin" /><span>Processing...</span>
           </div>

           <!-- Minimal Image Previews Grid -->
           <div v-if="photoPreviews.length > 0" class="grid grid-cols-2 gap-2 mt-1">
             <div v-for="(src, i) in photoPreviews" :key="i" class="relative group rounded-xl overflow-hidden aspect-square border border-gray-200 dark:border-[#30363D] shadow-2xs">
               <img :src="src" class="w-full h-full object-cover" />
               <!-- Show delete cross only if it's a new or removable photo, or allow replacing all -->
               <button type="button" @click.prevent="removePhoto(i)" class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white w-5 h-5 rounded-full text-xs flex items-center justify-center font-black shadow-md">×</button>
             </div>
           </div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="flex items-center justify-between pt-2 gap-4">
         <button 
           type="button" 
           @click="openDeleteModal" 
           class="px-5 py-3 rounded-xl text-red-600 dark:text-red-400 font-bold text-sm hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors cursor-pointer flex items-center gap-2"
         >
           <Trash2 class="w-4 h-4" />
           <span>{{ $t('Remove Post') }}</span>
         </button>

         <button 
           type="submit" 
           :disabled="isSaving || isCompressing || !isModified" 
           class="px-8 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-black text-sm flex items-center gap-2 shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:hover:translate-y-0 cursor-pointer"
         >
           <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
           <span>{{ isSaving ? $t('Saving...') : $t('Save Post update') }}</span>
           <Send class="w-4 h-4 shrink-0" />
         </button>
      </div>
    </form>

    <!-- Custom Delete Confirmation Modal (Sign Out Style) -->
    <DeleteListingModal 
      :isOpen="isDeleteModalOpen" 
      :listingTitle="listing ? ($t(listing.cropName) || listing.cropName) : ''" 
      :isDeleting="isDeleting" 
      @close="closeDeleteModal" 
      @confirm="confirmDelete" 
    />
  </div>
  <div v-else class="text-center py-16 bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-3xl max-w-md mx-auto my-12 p-8 shadow-2xs space-y-3">
    <p class="text-sm font-bold text-gray-700 dark:text-[#8B949E]">{{ $t('marketplace.listingNotFound') }}</p>
    <router-link to="/farmer/listings" class="inline-block px-4 py-2 bg-[#1E9444] text-white font-bold text-xs rounded-xl hover:bg-[#0F5C2A] transition-colors">
      {{ $t('farmer.myListingsTitle') }}
    </router-link>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Loader2, Wheat, MapPin, Coins, Sparkles, Trash2, Camera, Send } from 'lucide-vue-next'
import { useListings } from '@/composables/useListings'
import { useAuth } from '@/composables/useAuth'
import { formatETB } from '@/utils/helpers'
import { compressImageFiles } from '@/utils/imageCompressor'
import DeleteListingModal from '@/components/common/DeleteListingModal.vue'

const route = useRoute()
const router = useRouter()
const { getListingById, updateListing, deleteListing, refreshListings } = useListings()
const { user } = useAuth()

const listing = computed(() => getListingById(route.params.id))
const cropName = ref('')
const grade = ref('Grade 1')
const category = ref('coffee')
const region = ref('Amhara')
const zone = ref('')
const availableQty = ref(0)
const pricePerKg = ref(0)
const description = ref('')
const isSaving = ref(false)

const isDeleteModalOpen = ref(false)
const isDeleting = ref(false)

const photos = ref([]) // Holds Raw File Objects
const photoPreviews = ref([]) // Holds Base64 Data URLs for preview & display
const photoDataUrls = ref([]) 
const isCompressing = ref(false)

const isModified = computed(() => {
  if (!listing.value) return false
  const originalPhotosLength = Array.isArray(listing.value.images) ? listing.value.images.length : (listing.value.primaryImage ? 1 : 0)
  
  return (
    cropName.value !== (listing.value.cropName || '') ||
    grade.value !== (listing.value.grade || 'Grade 1') ||
    region.value !== (listing.value.region || 'Amhara') ||
    zone.value !== (listing.value.zone || '') ||
    Number(availableQty.value) !== Number(listing.value.availableQty || 0) ||
    Number(pricePerKg.value) !== Number(listing.value.pricePerKg || 0) ||
    description.value !== (listing.value.description || '') ||
    photos.value.length > 0 || 
    photoPreviews.value.length !== originalPhotosLength
  )
})

function readFileAsDataURL(file) {
  return new Promise((resolve) => {
    const reader = new FileReader()
    reader.onload = (e) => resolve(e.target.result)
    reader.onerror = () => resolve(URL.createObjectURL(file))
    reader.readAsDataURL(file)
  })
}

const handleFileSelect = async (event) => {
  const files = Array.from(event.target.files)
  if (!files.length) return
  
  if (photoPreviews.value.length + files.length > 5) {
     alert('Maximum 5 produce photos allowed per listing.')
  }
  
  const remainingSlots = 5 - photoPreviews.value.length
  const filesToProcess = files.slice(0, remainingSlots)

  isCompressing.value = true
  try {
    const compressedFiles = await compressImageFiles(filesToProcess, 1400, 1400, 0.8)
    for (const f of compressedFiles) {
      photos.value.push(f)
      const dataUrl = await readFileAsDataURL(f)
      photoPreviews.value.push(dataUrl)
      photoDataUrls.value.push(dataUrl)
    }
  } catch {
    for (const f of filesToProcess) {
      photos.value.push(f)
      const dataUrl = await readFileAsDataURL(f)
      photoPreviews.value.push(dataUrl)
      photoDataUrls.value.push(dataUrl)
    }
  } finally {
    isCompressing.value = false
  }
}

const removePhoto = (index) => {
  // If it's a newly added photo, remove it from raw list
  if (index >= (photoPreviews.value.length - photos.value.length)) {
     const relativeIndex = index - (photoPreviews.value.length - photos.value.length)
     photos.value.splice(relativeIndex, 1)
     photoDataUrls.value.splice(relativeIndex, 1)
  }
  photoPreviews.value.splice(index, 1)
}

const loadListing = () => {
  if (listing.value) {
    cropName.value = listing.value.cropName || ''
    grade.value = listing.value.grade || 'Grade 1'
    category.value = listing.value.category || 'coffee'
    region.value = listing.value.region || 'Amhara'
    zone.value = listing.value.zone || ''
    availableQty.value = listing.value.availableQty || 0
    pricePerKg.value = listing.value.pricePerKg || 0
    description.value = listing.value.description || ''
    
    // Load existing images
    if (Array.isArray(listing.value.images) && listing.value.images.length > 0) {
       photoPreviews.value = [...listing.value.images]
    } else if (listing.value.primaryImage) {
       photoPreviews.value = [listing.value.primaryImage]
    } else {
       photoPreviews.value = []
    }
    photos.value = []
    photoDataUrls.value = []
  }
}

onMounted(async () => {
  if (!listing.value) {
    await refreshListings()
  }
  loadListing()
})

watch(listing, loadListing, { immediate: true })

const handleSubmit = async () => {
  if (!listing.value) return
  isSaving.value = true
  try {
    await updateListing(listing.value.id, {
      cropName: cropName.value,
      grade: grade.value,
      category: category.value,
      region: region.value,
      zone: zone.value,
      availableQty: availableQty.value,
      pricePerKg: pricePerKg.value,
      description: description.value,
      rawFiles: photos.value, // triggers FormData upload
      images: photoPreviews.value // seamless local UI sync
    })
    router.push('/farmer/listings')
  } catch (err) {
    console.error('Failed to save listing changes:', err)
  } finally {
    isSaving.value = false
  }
}

const openDeleteModal = () => {
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  if (isDeleting.value) return
  isDeleteModalOpen.value = false
}

const confirmDelete = async () => {
  if (!listing.value) return
  isDeleting.value = true
  try {
    await deleteListing(listing.value.id)
    isDeleteModalOpen.value = false
    router.push('/farmer/listings')
  } finally {
    isDeleting.value = false
  }
}
</script>

