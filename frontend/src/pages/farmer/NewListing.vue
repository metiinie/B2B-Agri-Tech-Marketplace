<template>
  <div class="w-full flex flex-col min-h-full bg-[#F8F9FA] dark:bg-[#0D1117] pb-28">
    <!-- Header -->
    <div class="px-4 py-3.5 bg-white dark:bg-[#161B22] border-b border-[#E2E4E7] dark:border-[#30363D] flex items-center justify-between sticky top-14 z-30 shadow-2xs">
      <div class="flex items-center gap-3">
        <button @click="$router.back()" class="w-9 h-9 rounded-xl border border-[#E2E4E7] dark:border-[#30363D] flex items-center justify-center hover:bg-[#F0F1F2] dark:hover:bg-[#21262D] transition-colors">
          <ArrowLeft class="w-5 h-5 text-[#1E2328] dark:text-[#F0F6FC]" />
        </button>
        <div>
          <h2 class="text-base font-black text-[#1E2328] dark:text-[#F0F6FC] tracking-tight">{{ $t('farmer.newListingTitle') }}</h2>
          <p class="text-[11px] text-[#5A6270] dark:text-[#8B949E]">Post new produce batch to B2B marketplace</p>
        </div>
      </div>
      <span class="px-2.5 py-1 rounded-full bg-[#EDFAF2] dark:bg-emerald-950/40 text-[#1E9444] dark:text-emerald-400 border border-[#C3EFCF] dark:border-emerald-800/60 text-[11px] font-bold flex items-center gap-1">
        <Sparkles class="w-3.5 h-3.5" /> Direct Sourcing
      </span>
    </div>

    <!-- Modern Post UI Form Container -->
    <form @submit.prevent="handleSubmit" class="p-4 md:p-6 max-w-4xl mx-auto w-full space-y-6">
      <div v-if="submitError" class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 rounded-2xl flex items-center gap-3 text-xs text-red-700 dark:text-red-400 font-bold shadow-2xs">
        <AlertCircle class="w-5 h-5 text-red-600 shrink-0" />
        <span>{{ $t(submitError) }}</span>
      </div>

      <!-- Single Main Card -->
      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-3xl p-5 sm:p-6 shadow-sm flex flex-col md:flex-row gap-6 relative">
        
        <!-- Left / Main Column: Post specifics -->
        <div class="flex-1 space-y-5">
          <!-- Textarea acting like a post compose box -->
          <div class="flex gap-3 sm:gap-4">
             <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full border-2 border-[#1E9444] bg-emerald-50 dark:bg-emerald-950 flex items-center justify-center font-black text-emerald-800 dark:text-emerald-300 shrink-0 overflow-hidden shadow-xs">
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
                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] rounded-xl text-sm font-bold focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-colors" />
            </div>
            <div>
              <label class="text-[10px] font-extrabold text-gray-500 dark:text-[#8B949E] uppercase tracking-wider block mb-1.5">{{ $t('farmer.category') }} <span class="text-red-500">*</span></label>
              <select v-model="category" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] rounded-xl text-sm font-bold focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-colors cursor-pointer">
                <option value="coffee">☕ {{ $t('Coffee') }}</option>
                <option value="grains">🌾 {{ $t('Grains & Cereals') }}</option>
                <option value="spices">🌿 {{ $t('Spices') }}</option>
                <option value="oilseeds">🥜 {{ $t('Oilseeds') }}</option>
                <option value="pulses">🫘 {{ $t('Pulses') }}</option>
                <option value="roots">🧅 {{ $t('Roots & Tubers') }}</option>
                <option value="fruits">🍋 {{ $t('Fruits') }}</option>
                <option value="vegetables">🥬 {{ $t('Vegetables') }}</option>
              </select>
            </div>
            <div>
              <label class="text-[10px] font-extrabold text-gray-500 dark:text-[#8B949E] uppercase tracking-wider block mb-1.5">{{ $t('Unit') }} <span class="text-red-500">*</span></label>
              <select v-model="unit" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] rounded-xl text-sm font-bold focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-colors cursor-pointer">
                <option value="kg">KG</option>
                <option value="Quintals">Quintals</option>
                <option value="Tonnes">Tonnes</option>
                <option value="Liters">Liters</option>
                <option value="Boxes">Boxes</option>
              </select>
            </div>
            <div>
              <label class="text-[10px] font-extrabold text-gray-500 dark:text-[#8B949E] uppercase tracking-wider block mb-1.5">Price / {{ unit }} <span class="text-red-500">*</span></label>
              <div class="relative">
                 <span class="absolute left-3 top-[11px] text-sm font-black text-emerald-600 dark:text-emerald-400">Br</span>
                 <input type="number" v-model.number="pricePerKg" required min="1" step="0.01" placeholder="85.00"
                   class="w-full pl-9 pr-4 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E9444] dark:text-[#34D399] rounded-xl text-sm font-black focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-colors" />
              </div>
            </div>
            <div>
              <label class="text-[10px] font-extrabold text-gray-500 dark:text-[#8B949E] uppercase tracking-wider block mb-1.5">Quantity ({{ unit }}) <span class="text-red-500">*</span></label>
              <input type="number" v-model.number="availableQty" required min="1" placeholder="5000"
                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] rounded-xl text-sm font-bold focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-colors" />
            </div>
          </div>
        </div>

        <!-- Right / Last Column: Add Photo UI -->
        <div class="md:w-64 shrink-0 flex flex-col gap-3 border-t md:border-t-0 md:border-l border-gray-100 dark:border-[#30363D] pt-4 md:pt-0 md:pl-6">
           <div class="flex items-center justify-between">
             <h3 class="text-[10px] font-extrabold text-gray-500 dark:text-[#8B949E] uppercase tracking-wider">{{ $t('farmer.uploadImages') }}</h3>
             <span class="text-[9px] font-black text-[#1E9444] dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-full border border-emerald-100 dark:border-emerald-800/60">{{ photoPreviews.length }}/5</span>
           </div>
           
           <label for="fileUploadBtn" 
              :class="['flex items-center justify-center gap-2 p-3 border-2 border-dashed rounded-xl cursor-pointer transition-colors',
                photoPreviews.length >= 5 ? 'opacity-50 pointer-events-none border-gray-200 dark:border-[#30363D]' : 'border-[#C3EFCF] dark:border-emerald-800/60 hover:bg-[#F0FDF4] dark:hover:bg-[#161B22] hover:border-[#1E9444]']">
              <Camera class="w-5 h-5 text-[#1E9444] dark:text-emerald-400 shrink-0" />
              <span class="text-xs font-black text-[#1E9444] dark:text-emerald-400">{{ $t('Add Photo') }}</span>
              <input type="file" id="fileUploadBtn" multiple accept="image/*" @change="handleFileSelect" class="hidden" :disabled="photoPreviews.length >= 5" />
           </label>

           <div v-if="isCompressing" class="flex items-center justify-center gap-1.5 p-2 bg-blue-50 dark:bg-blue-950/40 rounded-lg text-[10px] text-blue-700 dark:text-blue-300 font-bold animate-pulse">
             <Loader2 class="w-3 h-3 animate-spin" /><span>Processing...</span>
           </div>

           <!-- Minimal Image Previews Grid -->
           <div v-if="photoPreviews.length > 0" class="grid grid-cols-2 gap-2 mt-1">
             <div v-for="(src, i) in photoPreviews" :key="i" class="relative group rounded-xl overflow-hidden aspect-square border border-gray-200 dark:border-[#30363D] shadow-2xs">
               <img :src="src" class="w-full h-full object-cover" />
               <button type="button" @click.prevent="removePhoto(i)" class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white w-5 h-5 rounded-full text-xs flex items-center justify-center font-black shadow-md">×</button>
             </div>
           </div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="flex justify-end pt-2">
         <button 
           type="submit" 
           :disabled="isSubmitting || isCompressing" 
           class="px-8 py-3.5 bg-[#1E9444] hover:bg-[#0F5C2A] text-white rounded-xl font-black text-sm flex items-center gap-2 shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:hover:translate-y-0 cursor-pointer"
         >
           <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
           <span>{{ isSubmitting ? $t('Publishing...') : $t('Post to Marketplace') }}</span>
           <Send class="w-4 h-4 shrink-0" />
         </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowLeft, Camera, Sparkles, Loader2, AlertCircle, Send } from 'lucide-vue-next'
import { useListings } from '@/composables/useListings'
import { useAuth } from '@/composables/useAuth'
import { useAlertModal } from '@/composables/useAlertModal'
import { formatETB } from '@/utils/helpers'
import { compressImageFiles } from '@/utils/imageCompressor'
import { CATEGORY_PHOTOS } from '@/utils/categoryImages'

const router = useRouter()
const { addListing } = useListings()
const { user } = useAuth()
const { showAlert } = useAlertModal()

const cropName = ref('')
const category = ref('coffee')
const grade = ref('Grade 1')
const processMethod = ref('Washed')
const unit = ref('kg')
const region = ref(user.value?.region || 'Amhara')
const zone = ref('Awi Zone, Injibara')
const availableQty = ref(5000)
const minOrderQty = ref(500)
const pricePerKg = ref(85)
const harvestDate = ref(new Date().toISOString().split('T')[0])
const moistureContent = ref(11.0)
const description = ref('')

const photos = ref([]) // Holds Raw File Objects
const photoPreviews = ref([]) // Holds Base64 Data URLs for preview & display
const photoDataUrls = ref([]) // Base64 data URLs for storage & instant display

const selectedCategoryImage = computed(() => CATEGORY_PHOTOS[category.value] || null)

const isCompressing = ref(false)

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
  
  if (photos.value.length + files.length > 5) {
    showAlert({
      title: 'Photo Upload Limit',
      message: 'Maximum 5 produce photos allowed per listing.',
      type: 'warning'
    })
  }
  
  const remainingSlots = 5 - photos.value.length
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
  photos.value.splice(index, 1)
  photoPreviews.value.splice(index, 1)
  photoDataUrls.value.splice(index, 1)
}

const isSubmitting = ref(false)
const submitError = ref(null)

const categoryEmojis = { coffee: '☕', grains: '🌾', spices: '🌿', oilseeds: '🥜', pulses: '🫘', roots: '🧅', fruits: '🍋', vegetables: '🥬' }

const handleSubmit = async () => {
  submitError.value = null
  isSubmitting.value = true
  
  try {
    await addListing({
      farmerId: user.value?.id || 'farmer-1',
      farmer: user.value,
      cropName: cropName.value || 'Ethiopian Agriculture Batch',
      cropEmoji: categoryEmojis[category.value] || '🌾',
      category: category.value,
      grade: grade.value,
      region: region.value,
      zone: zone.value,
      process: processMethod.value,
      unit: unit.value,
      pricePerKg: pricePerKg.value || 85,
      availableQty: availableQty.value || 5000,
      minOrderQty: minOrderQty.value || 500,
      harvestDate: new Date(harvestDate.value),
      moistureContent: moistureContent.value || 11.0,
      description: description.value || 'Highland Ethiopian farm direct produce harvest.',
      primaryImage: photoDataUrls.value[0] || null,
      images: photoDataUrls.value,
      rawFiles: photos.value,
      isActive: true,
      isVerified: true,
    })
    isSubmitting.value = false
    router.push('/farmer/listings')
  } catch (err) {
    isSubmitting.value = false
    submitError.value = err.message || 'Failed to post crop listing. Please verify your details.'
  }
}
</script>
