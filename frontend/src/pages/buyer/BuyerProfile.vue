<template>
  <div class="space-y-6 max-w-4xl pb-6">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#E2E4E7] dark:border-[#30363D] pb-4">
      <div class="flex items-center gap-3">
        <button @click="$router.back()" class="w-9 h-9 rounded-xl border border-[#E2E4E7] dark:border-[#30363D] flex items-center justify-center hover:bg-[#F0F1F2] dark:hover:bg-[#21262D] transition-colors bg-white dark:bg-[#161B22] shadow-2xs">
          <ArrowLeft class="w-5 h-5 text-[#1E2328] dark:text-[#F0F6FC]" />
        </button>
        <div>
          <h1 class="text-2xl font-black text-[#1E2328] dark:text-[#F0F6FC] tracking-tight">
            {{ $t('buyer.profileTitle') }} 👤
          </h1>
          <p class="text-xs text-[#5A6270] dark:text-[#8B949E] mt-0.5">
            {{ $t('buyer.profileSub') }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button @click="openEditModal" 
          class="px-4 py-2 bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] hover:border-[#0B57D0] text-[#1E2328] dark:text-[#F0F6FC] hover:text-[#0B57D0] rounded-xl text-xs font-extrabold transition-colors shadow-2xs flex items-center gap-1.5 cursor-pointer">
          <Edit3 class="w-3.5 h-3.5 text-[#0B57D0] dark:text-blue-400" />
          <span>{{ $t('Edit Profile') }}</span>
        </button>
        <button @click="openLogoutModal" 
          class="px-4 py-2 bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/60 hover:bg-red-100 dark:hover:bg-red-900/60 rounded-xl text-xs font-extrabold transition-colors flex items-center gap-1.5 cursor-pointer">
          <LogOut class="w-3.5 h-3.5" />
          <span>{{ $t('Sign Out') }}</span>
        </button>
      </div>
    </div>

    <!-- Success Toast Notification -->
    <div v-if="successMsg" class="p-3.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs font-bold rounded-xl flex items-center justify-between">
      <div class="flex items-center gap-2">
        <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
        <span>{{ $t(successMsg) }}</span>
      </div>
      <button @click="successMsg = ''" class="text-emerald-700 dark:text-emerald-400 hover:text-emerald-900 font-extrabold text-sm">&times;</button>
    </div>

    <!-- Identity Header Card -->
    <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-6 shadow-2xs space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center gap-5">
        <!-- Avatar with Interactive Photo Upload Button -->
        <div class="relative group cursor-pointer" @click="triggerPhotoUpload">
          <div v-if="customPhotoUrl || user?.avatar" 
            class="w-18 h-18 rounded-2xl overflow-hidden border-2 border-[#0B57D0] shadow-md">
            <img :src="customPhotoUrl || user?.avatar" alt="Profile" class="w-full h-full object-cover" />
          </div>
          <div v-else 
            class="w-18 h-18 rounded-2xl bg-gradient-to-br from-[#0B57D0] via-[#09429E] to-[#1E9444] text-white flex items-center justify-center text-2xl font-black shadow-md border-2 border-amber-300">
            {{ user?.name?.[0] || 'A' }}
          </div>

          <!-- Photo Change Overlay -->
          <div class="absolute inset-0 bg-black/40 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
            <Camera class="w-5 h-5" />
          </div>
          <button class="absolute -bottom-1 -right-1 w-6 h-6 bg-[#E69500] text-white rounded-full flex items-center justify-center shadow-md border-2 border-white">
            <Camera class="w-3 h-3" />
          </button>
        </div>

        <!-- Hidden File Input for Avatar Upload -->
        <input type="file" ref="fileInput" accept="image/*" class="hidden" @change="handlePhotoChange" />

        <div class="space-y-1.5 flex-1">
          <div class="flex items-center gap-2 flex-wrap">
            <h2 class="text-xl font-black text-[#1E2328] dark:text-[#F0F6FC] tracking-tight">{{ user?.name || $t('buyer.portalBadge') }}</h2>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 flex items-center gap-1">
              <ShieldCheck class="w-3.5 h-3.5 text-[#1E9444] dark:text-emerald-400" />
              <span>{{ $t('badges.verifiedBuyer') }}</span>
            </span>
          </div>

          <p class="text-xs text-[#5A6270] dark:text-[#8B949E] flex items-center gap-2 font-semibold flex-wrap">
            <Phone class="w-3.5 h-3.5 text-[#0B57D0] dark:text-blue-400" />
            <span>{{ user?.phone || $t('No phone set') }}</span>
            <span>•</span>
            <span class="text-[#1E2328] dark:text-[#F0F6FC]">{{ $t(user?.region) || 'Addis Ababa' }}</span>
            <span>•</span>
            <button @click="triggerPhotoUpload" class="text-[11px] font-extrabold text-[#0B57D0] dark:text-blue-400 hover:underline">
              {{ $t('Change Photo') }}
            </button>
          </p>

          <!-- Badges -->
          <div class="pt-1 flex items-center gap-2 flex-wrap">
            <span class="px-2.5 py-1 rounded-lg text-[11px] font-extrabold bg-blue-50 dark:bg-blue-950/40 text-[#0B57D0] dark:text-blue-400 border border-blue-200 dark:border-blue-800/60 flex items-center gap-1">
              <Wallet class="w-3 h-3 text-[#0B57D0] dark:text-blue-400" />
              <span>{{ $t('badges.escrowProtected') }}</span>
            </span>

            <span class="px-2.5 py-1 rounded-lg text-[11px] font-extrabold bg-amber-50 dark:bg-amber-950/40 text-[#E69500] dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 flex items-center gap-1">
              <Clock class="w-3 h-3 text-[#E69500]" />
              <span>{{ $t('Verified Account') }}</span>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Commercial & Logistics Information Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-5 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-[#21262D] pb-3">
          <div class="flex items-center gap-2">
            <Building2 class="w-4 h-4 text-[#0B57D0] dark:text-blue-400" />
            <h3 class="text-sm font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('Commercial Sourcing Identity') }}</h3>
          </div>
          <span class="text-[10px] font-extrabold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-md">{{ $t('Verified') }}</span>
        </div>

        <div class="space-y-3 text-xs">
          <div class="flex justify-between py-1.5 border-b border-gray-50 dark:border-[#21262D]">
            <span class="text-[#5A6270] dark:text-[#8B949E] font-semibold">{{ $t('Registered Full Name') }}:</span>
            <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ user?.name || $t('Not set') }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-gray-50 dark:border-[#21262D]">
            <span class="text-[#5A6270] dark:text-[#8B949E] font-semibold">{{ $t('auth.mobilePhone') }}:</span>
            <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ user?.phone || $t('Not set') }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-gray-50 dark:border-[#21262D]">
            <span class="text-[#5A6270] dark:text-[#8B949E] font-semibold">{{ $t('buyer.companyName') }}:</span>
            <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ user?.businessName || $t('Not specified') }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-gray-50 dark:border-[#21262D]">
            <span class="text-[#5A6270] dark:text-[#8B949E] font-semibold">{{ $t('buyer.tinNumber') }}:</span>
            <span class="font-mono font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ user?.tinNumber || $t('Not specified') }}</span>
          </div>
          <div class="flex justify-between py-1.5">
            <span class="text-[#5A6270] dark:text-[#8B949E] font-semibold">{{ $t('Primary Commercial Region') }}:</span>
            <span class="font-bold text-[#0B57D0] dark:text-blue-400">{{ $t(user?.region) || 'Addis Ababa' }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-5 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-[#21262D] pb-3">
          <div class="flex items-center gap-2">
            <CreditCard class="w-4 h-4 text-[#E69500]" />
            <h3 class="text-sm font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('Logistics & Security') }}</h3>
          </div>
          <span class="text-[10px] font-extrabold text-[#E69500] dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 rounded-md">{{ $t('Active') }}</span>
        </div>

        <div class="space-y-3 text-xs">
          <div class="flex justify-between py-1.5 border-b border-gray-50 dark:border-[#21262D]">
            <span class="text-[#5A6270] dark:text-[#8B949E] font-semibold">{{ $t('buyer.warehouseAddress') }}:</span>
            <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ user?.deliveryHub || 'Kality Central Hub' }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-gray-50 dark:border-[#21262D]">
            <span class="text-[#5A6270] dark:text-[#8B949E] font-semibold">{{ $t('Escrow Security') }}:</span>
            <span class="font-bold text-[#1E9444] dark:text-emerald-400">{{ $t('Chapa Escrow Protected') }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-gray-50 dark:border-[#21262D]">
            <span class="text-[#5A6270] dark:text-[#8B949E] font-semibold">{{ $t('Settlement Currency') }}:</span>
            <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('ETB (Ethiopian Birr)') }}</span>
          </div>
          <div class="flex justify-between py-1.5">
            <span class="text-[#5A6270] dark:text-[#8B949E] font-semibold">{{ $t('Handover Auth') }}:</span>
            <span class="font-bold text-[#0B57D0] dark:text-blue-400">{{ $t('4-Digit Dynamic PIN') }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Profile Modal with Tabbed Sections -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="max-w-lg w-full bg-white dark:bg-[#161B22] rounded-3xl p-6 shadow-2xl space-y-5 text-[#1E2328] dark:text-[#F0F6FC] border border-gray-100 dark:border-[#30363D] animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-[#21262D] pb-3">
          <div class="flex items-center gap-2.5">
            <div class="p-2 bg-blue-50 dark:bg-blue-950/40 text-[#0B57D0] dark:text-blue-400 rounded-xl">
              <UserCheck class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-black text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('Edit Buyer Profile') }}</h3>
              <p class="text-[11px] text-[#5A6270] dark:text-[#8B949E]">{{ $t('Manage credentials, security, & commercial info') }}</p>
            </div>
          </div>
          <button @click="showEditModal = false" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#21262D] rounded-xl transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex border-b border-gray-100 dark:border-[#21262D] text-xs font-bold gap-4">
          <button 
            @click="activeTab = 'credentials'" 
            :class="[
              'pb-2.5 border-b-2 transition-colors flex items-center gap-1.5 cursor-pointer',
              activeTab === 'credentials' ? 'border-[#0B57D0] dark:border-blue-400 text-[#0B57D0] dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'
            ]"
          >
            <User class="w-3.5 h-3.5" />
            <span>{{ $t('Credentials & Password') }}</span>
          </button>
          <button 
            @click="activeTab = 'commercial'" 
            :class="[
              'pb-2.5 border-b-2 transition-colors flex items-center gap-1.5 cursor-pointer',
              activeTab === 'commercial' ? 'border-[#0B57D0] dark:border-blue-400 text-[#0B57D0] dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'
            ]"
          >
            <Building2 class="w-3.5 h-3.5" />
            <span>{{ $t('Commercial & Logistics') }}</span>
          </button>
        </div>

        <!-- Error Alert -->
        <div v-if="modalError" class="p-3 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 text-red-700 dark:text-red-400 text-xs font-bold rounded-xl flex items-center gap-2">
          <AlertCircle class="w-4 h-4 text-red-600 shrink-0" />
          <span>{{ $t(modalError) }}</span>
        </div>

        <!-- Tab 1: Credentials & Security -->
        <div v-if="activeTab === 'credentials'" class="space-y-4 text-xs">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-extrabold text-[#1E2328] dark:text-[#F0F6FC] mb-1">{{ $t('auth.firstName') }}</label>
              <input type="text" v-model="form.first_name" :placeholder="$t('auth.firstName')"
                class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-[#0D1117] border border-gray-200 dark:border-[#30363D] dark:text-[#F0F6FC] rounded-xl font-bold text-xs focus:outline-none focus:border-[#0B57D0] focus:bg-white dark:focus:bg-[#0D1117] transition-all" />
            </div>
            <div>
              <label class="block font-extrabold text-[#1E2328] dark:text-[#F0F6FC] mb-1">{{ $t('auth.secondName') }}</label>
              <input type="text" v-model="form.second_name" :placeholder="$t('auth.secondName')"
                class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-[#0D1117] border border-gray-200 dark:border-[#30363D] dark:text-[#F0F6FC] rounded-xl font-bold text-xs focus:outline-none focus:border-[#0B57D0] focus:bg-white dark:focus:bg-[#0D1117] transition-all" />
            </div>
          </div>

          <div>
            <label class="block font-extrabold text-[#1E2328] dark:text-[#F0F6FC] mb-1">{{ $t('auth.mobilePhone') }}</label>
            <div class="relative">
              <Phone class="w-4 h-4 text-gray-400 dark:text-gray-500 absolute left-3 top-3" />
              <input type="text" v-model="form.phone" placeholder="+251 911 000 000"
                class="w-full pl-9 pr-3.5 py-2.5 bg-gray-50 dark:bg-[#0D1117] border border-gray-200 dark:border-[#30363D] dark:text-[#F0F6FC] rounded-xl font-bold text-xs focus:outline-none focus:border-[#0B57D0] focus:bg-white dark:focus:bg-[#0D1117] transition-all" />
            </div>
          </div>

          <div class="pt-2 border-t border-gray-100 dark:border-[#21262D]">
            <h4 class="font-black text-[#1E2328] dark:text-[#F0F6FC] mb-2 flex items-center gap-1.5">
              <Key class="w-3.5 h-3.5 text-[#0B57D0] dark:text-blue-400" />
              <span>{{ $t('Change Password (Optional)') }}</span>
            </h4>
            <div class="space-y-2.5">
              <div>
                <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1">{{ $t('Current Password') }}</label>
                <input type="password" v-model="form.current_password" :placeholder="$t('Enter current password if changing')"
                  class="w-full px-3.5 py-2 bg-gray-50 dark:bg-[#0D1117] border border-gray-200 dark:border-[#30363D] dark:text-[#F0F6FC] rounded-xl font-semibold text-xs focus:outline-none focus:border-[#0B57D0] focus:bg-white dark:focus:bg-[#0D1117] transition-all" />
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                  <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1">{{ $t('New Password') }}</label>
                  <input type="password" v-model="form.new_password" placeholder="At least 6 chars"
                    class="w-full px-3.5 py-2 bg-gray-50 dark:bg-[#0D1117] border border-gray-200 dark:border-[#30363D] dark:text-[#F0F6FC] rounded-xl font-semibold text-xs focus:outline-none focus:border-[#0B57D0] focus:bg-white dark:focus:bg-[#0D1117] transition-all" />
                </div>
                <div>
                  <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1">{{ $t('auth.confirmPassword') }}</label>
                  <input type="password" v-model="form.confirm_password" placeholder="Re-type new password"
                    class="w-full px-3.5 py-2 bg-gray-50 dark:bg-[#0D1117] border border-gray-200 dark:border-[#30363D] dark:text-[#F0F6FC] rounded-xl font-semibold text-xs focus:outline-none focus:border-[#0B57D0] focus:bg-white dark:focus:bg-[#0D1117] transition-all" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 2: Commercial & Logistics -->
        <div v-if="activeTab === 'commercial'" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-extrabold text-[#1E2328] dark:text-[#F0F6FC] mb-1">{{ $t('buyer.companyName') }}</label>
            <input type="text" v-model="form.businessName" placeholder="e.g. Addis Supermarket PLC"
              class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-[#0D1117] border border-gray-200 dark:border-[#30363D] dark:text-[#F0F6FC] rounded-xl font-bold text-xs focus:outline-none focus:border-[#0B57D0] focus:bg-white dark:focus:bg-[#0D1117] transition-all" />
          </div>

          <div>
            <label class="block font-extrabold text-[#1E2328] dark:text-[#F0F6FC] mb-1">{{ $t('buyer.tinNumber') }}</label>
            <input type="text" v-model="form.tinNumber" placeholder="e.g. 0098765432"
              class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-[#0D1117] border border-gray-200 dark:border-[#30363D] dark:text-[#F0F6FC] rounded-xl font-mono font-bold text-xs focus:outline-none focus:border-[#0B57D0] focus:bg-white dark:focus:bg-[#0D1117] transition-all" />
          </div>

          <div>
            <label class="block font-extrabold text-[#1E2328] dark:text-[#F0F6FC] mb-1">{{ $t('auth.selectRegion') }}</label>
            <select v-model="form.region" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-[#0D1117] border border-gray-200 dark:border-[#30363D] dark:text-[#F0F6FC] rounded-xl font-bold text-xs focus:outline-none focus:border-[#0B57D0] focus:bg-white dark:focus:bg-[#0D1117] transition-all">
              <option value="Addis Ababa">{{ $t('Addis Ababa') }}</option>
              <option value="Oromia">{{ $t('Oromia') }}</option>
              <option value="Amhara">{{ $t('Amhara') }}</option>
              <option value="Sidama">{{ $t('Sidama') }}</option>
              <option value="SNNPR">{{ $t('SNNPR') }}</option>
              <option value="Dire Dawa">{{ $t('Dire Dawa') }}</option>
              <option value="Somali">{{ $t('Somali') }}</option>
              <option value="Tigray">{{ $t('Tigray') }}</option>
            </select>
          </div>

          <div>
            <label class="block font-extrabold text-[#1E2328] dark:text-[#F0F6FC] mb-1">{{ $t('buyer.warehouseAddress') }}</label>
            <input type="text" v-model="form.deliveryHub" placeholder="e.g. Kality Central Logistics Depot"
              class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-[#0D1117] border border-gray-200 dark:border-[#30363D] dark:text-[#F0F6FC] rounded-xl font-bold text-xs focus:outline-none focus:border-[#0B57D0] focus:bg-white dark:focus:bg-[#0D1117] transition-all" />
          </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex gap-2.5 pt-3 border-t border-gray-100 dark:border-[#21262D]">
          <button @click="showEditModal = false" class="flex-1 py-2.5 border border-gray-200 dark:border-[#30363D] rounded-xl font-bold text-xs text-gray-700 dark:text-[#F0F6FC] hover:bg-gray-50 dark:hover:bg-[#21262D] transition-colors">
            {{ $t('Cancel') }}
          </button>
          <button @click="saveProfile" :disabled="isSaving"
            class="flex-1 py-2.5 bg-[#0B57D0] hover:bg-[#09429E] text-white rounded-xl font-bold text-xs transition-colors shadow-sm flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer">
            <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
            <span>{{ $t('Save Profile') }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { 
  ShieldCheck, Phone, Building2, CreditCard, Edit3, LogOut, 
  Camera, Wallet, Clock, X, User, Key, UserCheck, AlertCircle, CheckCircle2, Loader2, ArrowLeft 
} from 'lucide-vue-next'
import { useAuth } from '@/composables/useAuth'
import { api } from '@/services/api'

const { user, logout, openLogoutModal, updateUserProfile } = useAuth()
const showEditModal = ref(false)
const activeTab = ref('credentials')
const fileInput = ref(null)
const customPhotoUrl = ref(null)
const modalError = ref('')
const successMsg = ref('')
const isSaving = ref(false)

const form = reactive({
  first_name: '',
  second_name: '',
  phone: '',
  current_password: '',
  new_password: '',
  confirm_password: '',
  businessName: '',
  tinNumber: '',
  region: '',
  deliveryHub: ''
})

const openEditModal = () => {
  modalError.value = ''
  const nameParts = (user.value?.name || '').split(' ')
  form.first_name = user.value?.first_name || nameParts[0] || ''
  form.second_name = user.value?.second_name || nameParts.slice(1).join(' ') || ''
  form.phone = user.value?.phone || ''
  form.current_password = ''
  form.new_password = ''
  form.confirm_password = ''
  form.businessName = user.value?.businessName || ''
  form.tinNumber = user.value?.tinNumber || ''
  form.region = user.value?.region || 'Addis Ababa'
  form.deliveryHub = user.value?.deliveryHub || 'Kality Central Hub'
  activeTab.value = 'credentials'
  showEditModal.value = true
}

const triggerPhotoUpload = () => {
  if (fileInput.value) {
    fileInput.value.click()
  }
}

const handlePhotoChange = async (e) => {
  const file = e.target.files?.[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = async (event) => {
      const base64Data = event.target.result
      customPhotoUrl.value = base64Data
      if (user.value) {
        user.value.avatar = base64Data
        localStorage.setItem('agri_user_data', JSON.stringify(user.value))
      }
    }
    reader.readAsDataURL(file)

    try {
      const formData = new FormData()
      formData.append('profile_photo', file)
      await api.updateProfile(formData)
      successMsg.value = 'Profile photo updated successfully!'
    } catch {
      // offline fallback
    }
  }
}

const saveProfile = async () => {
  modalError.value = ''

  if (form.new_password) {
    if (!form.current_password) {
      modalError.value = 'Please enter your current password to change password.'
      activeTab.value = 'credentials'
      return
    }
    if (form.new_password.length < 6) {
      modalError.value = 'New password must be at least 6 characters long.'
      activeTab.value = 'credentials'
      return
    }
    if (form.new_password !== form.confirm_password) {
      modalError.value = 'New password and confirmation do not match.'
      activeTab.value = 'credentials'
      return
    }
  }

  isSaving.value = true

  try {
    await updateUserProfile({
      first_name: form.first_name,
      second_name: form.second_name,
      phone: form.phone,
      current_password: form.current_password || undefined,
      new_password: form.new_password || undefined,
      businessName: form.businessName,
      tinNumber: form.tinNumber,
      region: form.region,
      deliveryHub: form.deliveryHub,
    })

    showEditModal.value = false
    successMsg.value = 'Profile updated successfully!'
    setTimeout(() => {
      successMsg.value = ''
    }, 4000)
  } catch (err) {
    modalError.value = err.message || 'Failed to save profile changes. Please try again.'
  } finally {
    isSaving.value = false
  }
}
</script>
