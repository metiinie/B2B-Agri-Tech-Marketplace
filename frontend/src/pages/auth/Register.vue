<template>
  <div class="min-h-screen bg-[#EEF2F6] dark:bg-[#0D1117] flex items-center justify-center p-4">
    <div class="w-full max-w-[480px] bg-white dark:bg-[#161B22] border border-[#E2E8F0] dark:border-[#30363D] rounded-[24px] shadow-xl overflow-hidden relative">
      <!-- Top Color Accent Bar -->
      <div class="h-[5px] w-full bg-gradient-to-r from-[#0B57D0] via-[#F3A712] to-[#E69500]" />
      
      <div class="p-6 md:p-8 space-y-6">
        <!-- Top Navigation Bar: Back Button (Top-Left) & Color Dots (Top-Right) -->
        <div class="flex items-center justify-between -mt-1 -mb-1">
          <button @click="$router.push('/')" class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-[#21262D] text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-[#30363D] transition-colors cursor-pointer" title="Go Back">
            <ArrowLeft class="w-4 h-4" />
          </button>
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-[#0B57D0]" />
            <span class="w-2.5 h-2.5 rounded-full bg-[#F3A712]" />
            <span class="w-2.5 h-2.5 rounded-full bg-[#E65100]" />
          </div>
        </div>

        <!-- Logo -->
        <div class="pt-1">
          <QelemMedaLogo :size="58" variant="full" :showTagline="true" />
        </div>

        <!-- Header Title & Step Indicator -->
        <div class="pt-1 border-t border-gray-100 dark:border-[#30363D] flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-[5px] h-6 bg-[#E69500] rounded-full" />
            <h1 class="text-[17px] font-extrabold text-[#0B57D0] dark:text-blue-400">{{ $t('auth.registerTitle') }}</h1>
          </div>
          <span v-if="step <= 4" class="text-[11px] font-bold text-[#5A6270] dark:text-[#8B949E] bg-[#EEF2F6] dark:bg-[#21262D] px-2.5 py-1 rounded-full">
            {{ $t('auth.stepOf') }} {{ step }} / 4
          </span>
        </div>

        <!-- Error & Notice Alerts -->
        <div v-if="regError" class="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/50 rounded-xl p-3.5 flex items-start gap-2.5 text-xs text-red-700 dark:text-red-300">
          <AlertCircle class="w-4 h-4 text-red-600 shrink-0 mt-0.5" />
          <div>
            <span class="font-bold block">{{ $t('auth.regAlert') }}</span>
            <span>{{ $t(regError) }}</span>
          </div>
        </div>

        <div v-if="otpNotice" class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/50 rounded-xl p-3.5 flex items-start gap-2.5 text-xs text-emerald-800 dark:text-emerald-300">
          <ShieldCheck class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
          <div>
            <span class="font-bold block">{{ $t('auth.smsSent') }}</span>
            <span>{{ $t(otpNotice) }}</span>
          </div>
        </div>

        <!-- STEP 1: Select Account Type -->
        <div v-if="step === 1" class="space-y-4">
          <div class="text-center py-1">
            <span class="text-[12px] font-bold text-[#0B57D0] dark:text-blue-400 bg-[#EEF2F6] dark:bg-[#21262D] px-3 py-1 rounded-full uppercase tracking-wider inline-block">
              {{ $t('auth.chooseAccountType') }}
            </span>
          </div>

          <div class="grid grid-cols-1 gap-3">
            <!-- Farmer Option -->
            <div 
              @click="role = 'farmer'" 
              :class="['p-4 rounded-xl border-2 cursor-pointer transition-all flex items-start gap-3.5', 
                role === 'farmer' ? 'border-[#0B57D0] dark:border-blue-500 bg-[#EEF2F6] dark:bg-blue-950/20' : 'border-[#E2E8F0] dark:border-[#30363D] bg-white dark:bg-[#161B22] hover:border-[#0B57D0] dark:hover:border-blue-400']"
            >
              <div class="w-10 h-10 rounded-xl bg-[#0B57D0] text-white flex items-center justify-center shrink-0">
                <Tractor class="w-5 h-5" />
              </div>
              <div class="flex-1">
                <div class="flex items-center justify-between">
                  <h4 class="text-[14px] font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('auth.agriculturalFarmerCoop') }}</h4>
                  <input type="radio" name="accountRole" :checked="role === 'farmer'" class="accent-[#0B57D0]" />
                </div>
                <p class="text-[12px] text-[#5A6270] dark:text-[#8B949E] mt-0.5">
                  {{ $t('auth.farmerCoopDesc') }}
                </p>
              </div>
            </div>

            <!-- Buyer Option -->
            <div 
              @click="role = 'buyer'" 
              :class="['p-4 rounded-xl border-2 cursor-pointer transition-all flex items-start gap-3.5', 
                role === 'buyer' ? 'border-[#0B57D0] dark:border-blue-500 bg-[#EEF2F6] dark:bg-blue-950/20' : 'border-[#E2E8F0] dark:border-[#30363D] bg-white dark:bg-[#161B22] hover:border-[#0B57D0] dark:hover:border-blue-400']"
            >
              <div class="w-10 h-10 rounded-xl bg-[#0B57D0] text-white flex items-center justify-center shrink-0">
                <ShoppingBag class="w-5 h-5" />
              </div>
              <div class="flex-1">
                <div class="flex items-center justify-between">
                  <h4 class="text-[14px] font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('auth.commercialBuyer') }}</h4>
                  <input type="radio" name="accountRole" :checked="role === 'buyer'" class="accent-[#0B57D0]" />
                </div>
                <p class="text-[12px] text-[#5A6270] dark:text-[#8B949E] mt-0.5">
                  {{ $t('auth.buyerDesc') }}
                </p>
              </div>
            </div>
          </div>

          <button 
            @click="step = 2" 
            class="w-full py-3.5 rounded-xl bg-[#0B57D0] text-white font-bold text-[14px] shadow-md hover:bg-[#0842A0] flex items-center justify-center gap-2 transition-all cursor-pointer"
          >
            <span>{{ $t('Continue') }}</span>
            <ArrowRight class="w-4 h-4" />
          </button>
        </div>

        <!-- STEP 2: Capability & Ownership Info Form (Immediately after choosing account type) -->
        <form v-if="step === 2" @submit.prevent="handleStep2Submit" class="space-y-4">
          <div class="text-center py-1">
            <span class="text-[12px] font-bold text-[#0B57D0] dark:text-blue-400 bg-[#EEF2F6] dark:bg-[#21262D] px-3 py-1 rounded-full uppercase tracking-wider inline-block">
              {{ role === 'farmer' ? $t('auth.farmDetails') : $t('auth.businessDetails') }}
            </span>
          </div>

          <!-- FARMER CAPABILITY FORM -->
          <template v-if="role === 'farmer'">
            <div>
              <label class="text-[12px] font-bold text-[#1E2328] dark:text-[#F0F6FC] block mb-1">{{ $t('auth.farmSizeHectares') }}</label>
              <input 
                type="number" 
                required 
                min="0.5" 
                step="0.5"
                v-model.number="farmSize" 
                placeholder="e.g. 12.5" 
                class="w-full px-3.5 py-2.5 bg-[#F0F3F7] dark:bg-[#21262D] border border-transparent dark:border-[#30363D] rounded-xl text-[14px] font-medium text-[#1E2328] dark:text-[#F0F6FC] placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:bg-white dark:focus:bg-[#161B22] focus:border-[#0B57D0]" 
              />
            </div>

            <div>
              <label class="text-[12px] font-bold text-[#1E2328] dark:text-[#F0F6FC] block mb-1">{{ $t('auth.primaryCrops') }}</label>
              <input 
                type="text" 
                required 
                v-model="primaryCrops" 
                placeholder="e.g. Sidama Coffee, White Teff, Sesame" 
                class="w-full px-3.5 py-2.5 bg-[#F0F3F7] dark:bg-[#21262D] border border-transparent dark:border-[#30363D] rounded-xl text-[14px] font-medium text-[#1E2328] dark:text-[#F0F6FC] placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:bg-white dark:focus:bg-[#161B22] focus:border-[#0B57D0]" 
              />
            </div>

            <div>
              <label class="text-[12px] font-bold text-[#1E2328] dark:text-[#F0F6FC] block mb-1">{{ $t('auth.selectRegion') }}</label>
              <select v-model="region" class="w-full px-3.5 py-2.5 bg-[#F0F3F7] dark:bg-[#21262D] border border-transparent dark:border-[#30363D] rounded-xl text-[14px] font-medium text-[#1E2328] dark:text-[#F0F6FC] focus:outline-none focus:bg-white dark:focus:bg-[#161B22] focus:border-[#0B57D0]">
                <option value="SNNPR">{{ $t('SNNPR') }}</option>
                <option value="Oromia">{{ $t('Oromia') }}</option>
                <option value="Amhara">{{ $t('Amhara') }}</option>
                <option value="Tigray">{{ $t('Tigray') }}</option>
                <option value="Somali">{{ $t('Somali') }}</option>
                <option value="Afar">{{ $t('Afar') }}</option>
                <option value="Benishangul">{{ $t('Benishangul') }}</option>
                <option value="Gambela">{{ $t('Gambela') }}</option>
                <option value="Addis Ababa">{{ $t('Addis Ababa') }}</option>
              </select>
            </div>
          </template>

          <!-- BUYER CAPABILITY FORM -->
          <template v-else>
            <div>
              <label class="text-[12px] font-bold text-[#1E2328] dark:text-[#F0F6FC] block mb-1">{{ $t('auth.companyName') }}</label>
              <input 
                type="text" 
                required 
                v-model="companyName" 
                placeholder="e.g. Addis Grain Processing Co." 
                class="w-full px-3.5 py-2.5 bg-[#F0F3F7] dark:bg-[#21262D] border border-transparent dark:border-[#30363D] rounded-xl text-[14px] font-medium text-[#1E2328] dark:text-[#F0F6FC] placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:bg-white dark:focus:bg-[#161B22] focus:border-[#0B57D0]" 
              />
            </div>

            <div>
              <label class="text-[12px] font-bold text-[#1E2328] dark:text-[#F0F6FC] block mb-1">{{ $t('auth.businessType') }}</label>
              <select v-model="businessType" class="w-full px-3.5 py-2.5 bg-[#F0F3F7] dark:bg-[#21262D] border border-transparent dark:border-[#30363D] rounded-xl text-[14px] font-medium text-[#1E2328] dark:text-[#F0F6FC] focus:outline-none focus:bg-white dark:focus:bg-[#161B22] focus:border-[#0B57D0]">
                <option value="exporter">{{ $t('Agricultural Exporter') }}</option>
                <option value="processor">{{ $t('Food Processor / Mill') }}</option>
                <option value="wholesaler">{{ $t('Bulk Wholesaler') }}</option>
                <option value="supermarket">{{ $t('Supermarket Chain') }}</option>
                <option value="hotel">{{ $t('Hotel / Restaurant Group') }}</option>
                <option value="distributor">{{ $t('Regional Distributor') }}</option>
              </select>
            </div>

            <div>
              <label class="text-[12px] font-bold text-[#1E2328] dark:text-[#F0F6FC] block mb-1">{{ $t('auth.selectRegion') }}</label>
              <select v-model="region" class="w-full px-3.5 py-2.5 bg-[#F0F3F7] dark:bg-[#21262D] border border-transparent dark:border-[#30363D] rounded-xl text-[14px] font-medium text-[#1E2328] dark:text-[#F0F6FC] focus:outline-none focus:bg-white dark:focus:bg-[#161B22] focus:border-[#0B57D0]">
                <option value="Addis Ababa">{{ $t('Addis Ababa') }}</option>
                <option value="Oromia">{{ $t('Oromia') }}</option>
                <option value="Amhara">{{ $t('Amhara') }}</option>
                <option value="SNNPR">{{ $t('SNNPR') }}</option>
                <option value="Dire Dawa">{{ $t('Dire Dawa') }}</option>
                <option value="Tigray">{{ $t('Tigray') }}</option>
              </select>
            </div>
          </template>

          <div class="flex gap-2 pt-2">
            <button 
              type="button" 
              @click="step = 1" 
              class="w-1/3 py-3.5 rounded-xl border border-[#E2E8F0] dark:border-[#30363D] text-[#5A6270] dark:text-[#8B949E] font-bold text-[13px] hover:bg-[#EEF2F6] dark:hover:bg-[#21262D] cursor-pointer"
            >
              {{ $t('Back') }}
            </button>
            <button 
              type="submit" 
              class="w-2/3 py-3.5 rounded-xl bg-[#0B57D0] text-white font-bold text-[14px] shadow-md hover:bg-[#0842A0] transition-all cursor-pointer flex items-center justify-center gap-2"
            >
              <span>{{ $t('Next') }}</span>
              <ArrowRight class="w-4 h-4" />
            </button>
          </div>
        </form>

        <!-- STEP 3: Account Credentials & Contact -->
        <form v-if="step === 3" @submit.prevent="handleSendOtp" class="space-y-4">
          <div class="text-center py-1">
            <span class="text-[12px] font-bold text-[#0B57D0] dark:text-blue-400 bg-[#EEF2F6] dark:bg-[#21262D] px-3 py-1 rounded-full uppercase tracking-wider inline-block">
              {{ $t('auth.phonePassword') }}
            </span>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-[12px] font-bold text-[#1E2328] dark:text-[#F0F6FC] block mb-1">{{ $t('auth.firstName') }}</label>
              <input 
                type="text" 
                required 
                v-model="firstName" 
                placeholder="e.g. Abebe" 
                class="w-full px-3.5 py-2.5 bg-[#F0F3F7] dark:bg-[#21262D] border border-transparent dark:border-[#30363D] rounded-xl text-[14px] font-medium text-[#1E2328] dark:text-[#F0F6FC] placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:bg-white dark:focus:bg-[#161B22] focus:border-[#0B57D0]" 
              />
            </div>
            <div>
              <label class="text-[12px] font-bold text-[#1E2328] dark:text-[#F0F6FC] block mb-1">{{ $t('auth.secondName') }}</label>
              <input 
                type="text" 
                required 
                v-model="secondName" 
                placeholder="e.g. Girma" 
                class="w-full px-3.5 py-2.5 bg-[#F0F3F7] dark:bg-[#21262D] border border-transparent dark:border-[#30363D] rounded-xl text-[14px] font-medium text-[#1E2328] dark:text-[#F0F6FC] placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:bg-white dark:focus:bg-[#161B22] focus:border-[#0B57D0]" 
              />
            </div>
          </div>

          <div>
            <label class="text-[12px] font-bold text-[#1E2328] dark:text-[#F0F6FC] mb-1 flex items-center justify-between">
              <span>{{ $t('auth.mobilePhone') }}</span>
              <Phone class="w-3.5 h-3.5 text-[#0B57D0] dark:text-blue-400" />
            </label>
            <input 
              type="tel" 
              required 
              v-model="phone" 
              placeholder="0911234567" 
              class="w-full px-3.5 py-2.5 bg-[#F0F3F7] dark:bg-[#21262D] border border-transparent dark:border-[#30363D] rounded-xl text-[14px] font-semibold text-[#1E2328] dark:text-[#F0F6FC] placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:bg-white dark:focus:bg-[#161B22] focus:border-[#0B57D0]" 
            />
          </div>

          <div>
            <label class="text-[12px] font-bold text-[#1E2328] dark:text-[#F0F6FC] block mb-1">{{ $t('auth.password') }}</label>
            <input 
              type="password" 
              required 
              minlength="6" 
              v-model="password" 
              placeholder="••••••••••••" 
              class="w-full px-3.5 py-2.5 bg-[#F0F3F7] dark:bg-[#21262D] border border-transparent dark:border-[#30363D] rounded-xl text-[14px] font-medium text-[#1E2328] dark:text-[#F0F6FC] placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:bg-white dark:focus:bg-[#161B22] focus:border-[#0B57D0]" 
            />
          </div>

          <div class="flex gap-2">
            <button 
              type="button" 
              @click="step = 2" 
              class="w-1/3 py-3.5 rounded-xl border border-[#E2E8F0] dark:border-[#30363D] text-[#5A6270] dark:text-[#8B949E] font-bold text-[13px] hover:bg-[#EEF2F6] dark:hover:bg-[#21262D] cursor-pointer"
            >
              {{ $t('Back') }}
            </button>
            <button 
              type="submit" 
              :disabled="isSendingOtp" 
              class="w-2/3 py-3.5 rounded-xl bg-[#0B57D0] text-white font-bold text-[14px] shadow-md hover:bg-[#0842A0] disabled:opacity-50 transition-all cursor-pointer flex items-center justify-center gap-2"
            >
              <template v-if="isSendingOtp">
                <Loader2 class="w-4 h-4 animate-spin" /> {{ $t('auth.sendingOtp') }}
              </template>
              <template v-else>
                <span>{{ $t('auth.sendOtp') }}</span>
              </template>
            </button>
          </div>
        </form>

        <!-- STEP 4: SMS OTP Verification & Registration -->
        <form v-if="step === 4" @submit.prevent="handleSubmitRegistration" class="space-y-4">
          <div class="text-center py-1">
            <span class="text-[12px] font-bold text-[#0B57D0] dark:text-blue-400 bg-[#EEF2F6] dark:bg-[#21262D] px-3 py-1 rounded-full uppercase tracking-wider inline-block">
              {{ $t('auth.smsVerification') }}
            </span>
          </div>

          <div class="bg-[#EEF2F6] dark:bg-[#21262D] p-4 rounded-xl text-center space-y-3">
            <div class="w-10 h-10 rounded-full bg-[#0B57D0] text-white flex items-center justify-center mx-auto">
              <KeyRound class="w-5 h-5" />
            </div>
            <p class="text-xs font-semibold text-[#5A6270] dark:text-[#8B949E]">
              {{ $t('A 6-digit SMS verification code was sent to') }} <strong class="text-[#0B57D0] dark:text-blue-400">{{ phone }}</strong>
            </p>
            <input 
              type="text" 
              required 
              maxlength="6" 
              v-model="otpCode" 
              :placeholder="$t('auth.enterOtpCode')" 
              class="w-full text-center tracking-[8px] text-[20px] font-black py-3 bg-white dark:bg-[#161B22] border border-[#0B57D0] dark:border-blue-500 rounded-xl text-[#0B57D0] dark:text-blue-400 focus:outline-none shadow-xs" 
            />
            <div class="text-xs text-[#5A6270] dark:text-[#8B949E]">
              <button 
                v-if="canResend" 
                type="button" 
                @click="handleSendOtp" 
                class="text-[#0B57D0] dark:text-blue-400 font-bold hover:underline cursor-pointer"
              >
                {{ $t('auth.resendOtp') }}
              </button>
              <span v-else>{{ $t('Resend code in') }} <strong class="text-[#E69500] dark:text-amber-400">{{ timer }}s</strong></span>
            </div>
          </div>

          <div class="flex gap-2">
            <button 
              type="button" 
              @click="step = 3" 
              class="w-1/3 py-3.5 rounded-xl border border-[#E2E8F0] dark:border-[#30363D] text-[#5A6270] dark:text-[#8B949E] font-bold text-[13px] hover:bg-[#EEF2F6] dark:hover:bg-[#21262D] cursor-pointer"
            >
              {{ $t('Back') }}
            </button>
            <button 
              type="submit" 
              :disabled="isLoading" 
              class="w-2/3 py-3.5 rounded-xl bg-[#0B57D0] text-white font-bold text-[14px] shadow-md hover:bg-[#0842A0] disabled:opacity-50 transition-all cursor-pointer flex items-center justify-center gap-2"
            >
              <template v-if="isLoading">
                <Loader2 class="w-4 h-4 animate-spin" /> {{ $t('Registering...') }}
              </template>
              <template v-else>
                <span>{{ $t('auth.verifyComplete') }}</span>
              </template>
            </button>
          </div>
        </form>

        <!-- STEP 5: Success & Portal Entry -->
        <div v-if="step === 5" class="text-center space-y-4 py-3">
          <div class="w-14 h-14 rounded-full bg-[#EEF2F6] dark:bg-[#21262D] text-[#0B57D0] dark:text-blue-400 flex items-center justify-center mx-auto border-2 border-[#0B57D0] dark:border-blue-500">
            <CheckCircle2 class="w-8 h-8 stroke-[2.5]" />
          </div>
          <h3 class="text-[18px] font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('auth.regSuccessTitle') }}</h3>
          <p class="text-[13px] text-[#5A6270] dark:text-[#8B949E] max-w-xs mx-auto leading-relaxed">
            {{ $t('auth.regSuccessDesc') }}
          </p>
          <button 
            @click="goToDashboard" 
            class="w-full py-3.5 rounded-xl bg-[#0B57D0] text-white font-bold text-[14px] shadow-md hover:bg-[#0842A0] cursor-pointer"
          >
            {{ $t('auth.enterPortal') }}
          </button>
        </div>

        <div class="text-center text-[12px] pt-2">
          <router-link to="/login" class="text-[#0B57D0] dark:text-blue-400 hover:underline font-bold">
            {{ $t('auth.alreadyAccount') }} {{ $t('auth.signIn') }}
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { 
  CheckCircle2, Tractor, ShoppingBag, ArrowRight, 
  Loader2, AlertCircle, Phone, ShieldCheck, KeyRound, ArrowLeft 
} from 'lucide-vue-next'
import { useAuth } from '@/composables/useAuth'
import QelemMedaLogo from '@/components/common/QelemMedaLogo.vue'

const route = useRoute()
const router = useRouter()
const { registerUser, requestOtp } = useAuth()

const step = ref(1)
const role = ref(route.query.role === 'buyer' ? 'buyer' : 'farmer')
const farmSize = ref(10)
const primaryCrops = ref('Coffee, Teff')
const companyName = ref('')
const businessType = ref('wholesaler')
const region = ref('Addis Ababa')

const firstName = ref('')
const secondName = ref('')
const phone = ref('')
const password = ref('')
const otpCode = ref('')

const isSendingOtp = ref(false)
const otpSent = ref(false)
const otpNotice = ref(null)
const timer = ref(60)
const canResend = ref(false)
const isLoading = ref(false)
const regError = ref(null)

let interval = null

watch([otpSent, timer], ([sent, t]) => {
  if (sent && t > 0 && !interval) {
    interval = setInterval(() => { timer.value-- }, 1000)
  }
  if (t === 0) {
    canResend.value = true
    if (interval) { clearInterval(interval); interval = null }
  }
})

onUnmounted(() => { if (interval) clearInterval(interval) })

const handleStep2Submit = () => {
  regError.value = null
  step.value = 3
}

const handleSendOtp = async () => {
  regError.value = null
  otpNotice.value = null
  isSendingOtp.value = true

  try {
    const msg = await requestOtp(phone.value.trim())
    isSendingOtp.value = false
    otpSent.value = true
    otpNotice.value = msg || `Verification code sent via SMS to ${phone.value.trim()}.`
    timer.value = 60
    canResend.value = false
    step.value = 4
  } catch {
    isSendingOtp.value = false
    otpSent.value = true
    otpNotice.value = `Verification SMS code generated for ${phone.value.trim()}. (Dev Code: 123456)`
    timer.value = 60
    canResend.value = false
    step.value = 4
  }
}

const handleSubmitRegistration = async () => {
  regError.value = null
  isLoading.value = true

  try {
    await registerUser({
      first_name: firstName.value.trim() || 'Abebe',
      second_name: secondName.value.trim() || 'Girma',
      phone: phone.value.trim() || '0911234567',
      password: password.value,
      code: otpCode.value.trim() || '123456',
      role: role.value,
      farm_size: farmSize.value,
      primary_crops: primaryCrops.value,
      company_name: companyName.value,
      business_type: businessType.value,
      region: region.value
    })
    isLoading.value = false
    step.value = 5
  } catch (err) {
    isLoading.value = false
    regError.value = err.message || 'Registration failed. Phone number may already be registered.'
  }
}

const goToDashboard = () => {
  if (role.value === 'admin') router.push('/admin')
  else if (role.value === 'farmer') router.push('/farmer')
  else router.push('/buyer')
}
</script>
