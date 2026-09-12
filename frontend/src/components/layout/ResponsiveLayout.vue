<template>
  <div class="min-h-screen bg-[#F8F9FA] dark:bg-[#0D1117] flex flex-col font-sans transition-colors">
    <!-- Settings Modal -->
    <div v-if="isSettingsOpen" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="max-w-md w-full bg-white dark:bg-[#161B22] text-[#1E2328] dark:text-[#F0F6FC] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-6 shadow-2xl space-y-5">
        <div class="flex items-center justify-between border-b border-[#E2E4E7] dark:border-[#30363D] pb-3">
          <div class="flex items-center gap-2">
            <Settings class="w-5 h-5 text-[#1E9444]" />
            <h3 class="text-lg font-bold">{{ t('accountSettings') }}</h3>
          </div>
          <button @click="isSettingsOpen = false" class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-[#21262D] text-gray-500 dark:text-[#8B949E] cursor-pointer">
            <X class="w-5 h-5" />
          </button>
        </div>
        <div class="space-y-4 text-xs">
          <div class="p-3 bg-[#F8F9FA] dark:bg-[#21262D] border border-transparent dark:border-[#30363D] rounded-xl space-y-1">
            <span class="font-bold block text-[#1E2328] dark:text-[#F0F6FC]">{{ t('profileName') }}</span>
            <span class="text-[#5A6270] dark:text-[#8B949E]">{{ user?.name || 'User' }}</span>
          </div>
          <div class="p-3 bg-[#F8F9FA] dark:bg-[#21262D] border border-transparent dark:border-[#30363D] rounded-xl space-y-1">
            <span class="font-bold block text-[#1E2328] dark:text-[#F0F6FC]">{{ t('phoneNumber') }}</span>
            <span class="text-[#5A6270] dark:text-[#8B949E]">{{ user?.phone || 'Not provided' }}</span>
          </div>
          <div class="p-3 bg-[#F8F9FA] dark:bg-[#21262D] border border-transparent dark:border-[#30363D] rounded-xl flex items-center justify-between cursor-pointer hover:bg-gray-100 dark:hover:bg-[#30363D] transition-colors" @click="smsEnabled = !smsEnabled">
            <div>
              <span class="font-bold block text-[#1E2328] dark:text-[#F0F6FC]">{{ t('smsNotifications') }}</span>
              <span class="text-[10px] text-[#5A6270] dark:text-[#8B949E]">{{ t('smsAlertsDesc') }}</span>
            </div>
            <button type="button" :class="['relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none', smsEnabled ? 'bg-[#1E9444]' : 'bg-gray-300 dark:bg-[#5A6270]']">
              <span aria-hidden="true" :class="['pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-xs ring-0 transition duration-200 ease-in-out', smsEnabled ? 'translate-x-4' : 'translate-x-0']" />
            </button>
          </div>
        </div>
        <button @click="isSettingsOpen = false" class="w-full py-2.5 rounded-xl bg-[#1E9444] text-white font-bold text-xs hover:bg-[#0F5C2A] cursor-pointer">
          {{ t('saveCloseSettings') }}
        </button>
      </div>
    </div>

    <!-- Top Navigation Header -->
    <header :class="['sticky top-0 z-40 px-4 py-3 border-b flex items-center justify-between shadow-xs transition-colors',
      isFarmerTheme ? 'bg-[#062E15] text-white border-[#1E9444]/30' : 'bg-white/95 dark:bg-[#161B22]/95 backdrop-blur-md text-[#1E2328] dark:text-[#F0F6FC] border-[#E2E4E7] dark:border-[#30363D]']">
      <div class="flex items-center gap-3">
        <button @click="isSidebarOpen = !isSidebarOpen" aria-label="Toggle Side Navigation"
          :class="['hidden md:flex items-center justify-center p-2 rounded-lg transition-colors cursor-pointer',
            isFarmerTheme ? 'hover:bg-[#0F5C2A] text-white' : 'hover:bg-[#F0F1F2] dark:hover:bg-[#21262D] text-[#1E2328] dark:text-[#F0F6FC]']">
          <X v-if="isSidebarOpen" class="w-5 h-5 stroke-[2.5]" />
          <Menu v-else class="w-5 h-5 stroke-[2.5]" />
        </button>
        <div @click="goHome" class="flex items-center gap-2 cursor-pointer">
          <QelemMedaLogo :size="34" className="shrink-0" />
        </div>
      </div>

      <!-- Right: Profile dropdown -->
      <div class="flex items-center gap-3 relative" ref="dropdownRef">
        <NotificationBell :variant="isFarmerTheme ? 'farmer' : 'default'" />
        <LanguageToggle :variant="isFarmerTheme ? 'farmer' : 'default'" />
        <ThemeToggle />

        <!-- Profile Trigger -->
        <button @click="isProfileMenuOpen = !isProfileMenuOpen"
          :class="['flex items-center justify-center w-10 h-10 rounded-full border transition-all duration-200 shadow-xs cursor-pointer p-0 overflow-hidden',
            isFarmerTheme ? 'border-white/30 hover:border-white text-white' : 'border-gray-300 dark:border-[#30363D] hover:border-[#1E9444] dark:hover:border-[#34D399] text-[#1E2328] dark:text-[#F0F6FC]']">
          <img v-if="user?.avatar" :src="user?.avatar" alt="Profile avatar" class="w-full h-full object-cover shrink-0" />
          <div v-else class="w-full h-full bg-[#1E9444] text-white flex items-center justify-center text-sm font-extrabold shrink-0">
            {{ user?.name?.[0] || 'U' }}
          </div>
        </button>

        <!-- Dropdown Popover -->
        <Transition name="slide">
          <div v-if="isProfileMenuOpen" class="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] shadow-2xl rounded-2xl p-2 z-50 text-[#1E2328] dark:text-[#F0F6FC]">
            <div class="absolute -top-1.5 right-6 w-3 h-3 bg-white dark:bg-[#161B22] border-t border-l border-[#E2E4E7] dark:border-[#30363D] rotate-45" />
            <div class="p-3 bg-gradient-to-r from-[#EDFAF2] to-emerald-50 dark:from-[#062E15] dark:to-[#042611] rounded-xl mb-1.5 border border-[#C3EFCF] dark:border-[#1E9444]/40">
              <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full bg-[#1E9444] text-white flex items-center justify-center text-sm font-bold shadow-xs overflow-hidden shrink-0">
                  <img v-if="user?.avatar" :src="user?.avatar" alt="Profile" class="w-full h-full object-cover shrink-0" />
                  <span v-else>{{ user?.name?.[0] || 'U' }}</span>
                </div>
                <div class="overflow-hidden">
                  <h4 class="text-xs font-extrabold text-[#0F5C2A] dark:text-[#34D399] truncate">{{ user?.name || 'User' }}</h4>
                  <p class="text-[10px] text-[#5A6270] dark:text-[#8B949E] truncate">{{ user?.phone || user?.email || t('activeMember') }}</p>
                </div>
              </div>
              <div class="mt-2 pt-2 border-t border-[#C3EFCF] dark:border-[#1E9444]/40 flex items-center justify-between text-[10px]">
                <span class="font-bold text-[#0F5C2A] dark:text-[#34D399] uppercase">{{ t(user?.role === 'farmer' ? 'Farmer' : user?.role === 'admin' ? 'Admin' : 'Buyer') }}</span>
                <span class="flex items-center gap-1 text-emerald-700 dark:text-emerald-400 font-semibold">
                  <CheckCircle2 class="w-3 h-3 text-[#1E9444]" /> {{ t('verified') }}
                </span>
              </div>
            </div>

            <div class="space-y-1 text-xs">
              <button @click="goToProfile" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#F0F1F2] dark:hover:bg-[#21262D] transition-colors font-bold text-[#1E2328] dark:text-[#F0F6FC] cursor-pointer">
                <User class="w-4 h-4 text-[#1E9444]" />
                <span>{{ isAdmin ? t('adminDashboard') : t('profile') }}</span>
              </button>

              <template v-if="!isAdmin">
                <button v-if="hasFarmerCapability && hasBuyerCapability" @click="handleRoleSwitchDropdown"
                  class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#EDFAF2] dark:hover:bg-[#062E15] transition-colors font-bold text-[#0F5C2A] dark:text-[#34D399] cursor-pointer">
                  <ArrowLeftRight class="w-4 h-4 text-[#1E9444]" />
                  <div class="text-left flex-1 flex items-center justify-between">
                    <span>{{ t('switchCapability') }}</span>
                    <span class="px-1.5 py-0.5 bg-[#1E9444] text-white rounded-md text-[9px] font-extrabold capitalize">
                      {{ user?.role === 'farmer' ? t('Buyer') : t('Farmer') }}
                    </span>
                  </div>
                </button>
                <button v-else @click="isProfileMenuOpen = false; $router.push('/apply')"
                  class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#F0F1F2] dark:hover:bg-[#21262D] transition-colors font-bold text-[#1E2328] dark:text-[#F0F6FC] cursor-pointer">
                  <ShieldCheck class="w-4 h-4 text-[#1E9444]" />
                  <div class="text-left flex-1 flex items-center justify-between">
                    <span>{{ t('updateCapability') }}</span>
                    <span v-if="pendingApplications.length > 0" class="px-1.5 py-0.5 bg-[#1E9444] text-white rounded-md text-[9px] font-extrabold">Pending</span>
                  </div>
                </button>
              </template>

              <div class="border-t border-gray-100 dark:border-[#30363D] my-1" />
              <button @click="handleLogout" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-red-50 dark:hover:bg-red-950/30 text-red-600 dark:text-red-400 transition-colors font-bold cursor-pointer">
                <LogOut class="w-4 h-4 text-red-600 dark:text-red-400" />
                <span>{{ t('logout') }}</span>
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </header>

    <div class="flex-1 flex relative">
      <!-- Desktop Sidebar -->
      <aside :class="['hidden md:flex flex-col border-r fixed inset-y-0 left-0 pt-16 z-30 transition-all duration-300 ease-in-out',
        isSidebarOpen ? 'w-64' : 'w-20',
        isFarmerTheme ? 'bg-[#062E15] border-[#1E9444]/30 text-white' : 'bg-white dark:bg-[#161B22] border-[#E2E4E7] dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC]']">
        <nav class="p-3 flex-1 space-y-1.5 overflow-y-auto">
          <template v-for="(item, index) in navItems" :key="item.path">
            <div v-if="showCategoryHeader(item, index)" class="pt-3 pb-1 px-3 text-[10px] font-extrabold text-[#9BA1AA] dark:text-[#6E7681] tracking-wider uppercase">
              {{ t(item.category) }}
            </div>
            <button @click="$router.push(item.path)" :title="!isSidebarOpen ? t(item.label) : undefined"
              :class="['w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all group cursor-pointer',
                isNavActive(item) ? (isFarmerTheme ? 'bg-[#1E9444] text-white shadow-xs font-bold' : 'bg-[#EDFAF2] dark:bg-[#062E15] text-[#0F5C2A] dark:text-[#34D399] shadow-2xs font-bold border-l-3 border-[#1E9444]')
                  : (isFarmerTheme ? 'text-[#C3EFCF] hover:bg-[#0F5C2A] hover:text-white' : 'text-[#1E2328] dark:text-[#F0F6FC] hover:bg-[#F0F1F2] dark:hover:bg-[#21262D]'),
                !isSidebarOpen ? 'justify-center px-0' : '']">
              <div class="flex items-center gap-3 overflow-hidden">
                <component :is="item.icon"
                  :class="['w-4 h-4 shrink-0', isNavActive(item) ? (isFarmerTheme ? 'text-white' : 'text-[#1E9444]') : 'text-[#5A6270] dark:text-[#8B949E] group-hover:text-[#1E2328] dark:group-hover:text-white']" />
                <span v-if="isSidebarOpen" class="truncate">{{ t(item.label) }}</span>
              </div>
              <div v-if="isSidebarOpen" class="flex items-center gap-1.5 shrink-0">
                <span v-if="item.badge" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[#E69500] text-white shadow-2xs">
                  {{ item.badge }}
                </span>
                <ChevronRight :class="['w-3.5 h-3.5 shrink-0', isNavActive(item) ? (isFarmerTheme ? 'text-white' : 'text-[#1E9444]') : 'text-gray-400 dark:text-gray-500 opacity-60 group-hover:opacity-100']" />
              </div>
              <span v-else-if="item.badge" class="absolute top-1 right-2 w-2 h-2 rounded-full bg-[#E69500]" />
            </button>
          </template>
        </nav>

        <div v-if="isSidebarOpen" class="p-3 border-t border-gray-200/50 dark:border-[#30363D] m-3 rounded-xl bg-gray-50/60 dark:bg-[#21262D]/60 text-xs">
          <div class="flex items-center gap-2">
            <User class="w-3.5 h-3.5 text-[#1E9444]" />
            <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC] capitalize">{{ t(user?.role === 'farmer' ? 'Farmer' : user?.role === 'admin' ? 'Admin' : 'Buyer') }} {{ t('Mode Active') }}</span>
          </div>
        </div>
      </aside>

      <!-- Main Content -->
      <main :class="['flex-1 w-full pb-20 md:pb-8 transition-all duration-300 ease-in-out', isSidebarOpen ? 'md:ml-64' : 'md:ml-20']">
        <div class="max-w-7xl mx-auto p-4 md:p-6">
          <router-view />
        </div>
      </main>
    </div>

    <!-- Mobile Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-[#161B22] border-t border-[#E2E4E7] dark:border-[#30363D] py-2 px-4 flex items-center justify-around md:hidden shadow-[0_-2px_10px_rgba(0,0,0,0.05)]">
      <button v-for="item in navItems" :key="item.path" @click="$router.push(item.path)"
        :class="['flex flex-col items-center gap-0.5 text-center min-w-[56px] py-1 transition-colors relative cursor-pointer',
          isNavActive(item) ? 'text-[#1E9444]' : 'text-[#9BA1AA] dark:text-[#8B949E] hover:text-[#5A6270] dark:hover:text-white']">
        <div class="relative">
          <component :is="item.icon" :class="['w-5 h-5', isNavActive(item) ? 'stroke-[2.5]' : 'stroke-[1.75]']" />
          <span v-if="item.badge" class="absolute -top-1.5 -right-2.5 px-1.5 py-0.2 min-w-[16px] h-[16px] text-[9px] font-black bg-[#E69500] text-white rounded-full flex items-center justify-center border border-white dark:border-[#161B22] shadow-2xs">
            {{ item.badge }}
          </span>
        </div>
        <span :class="['text-[11px]', isNavActive(item) ? 'font-bold' : 'font-medium']">{{ t(item.label) }}</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Menu, X, LogOut, User, ArrowLeftRight, ChevronDown, ChevronRight, Settings, ShieldCheck, CheckCircle2 } from 'lucide-vue-next'
import { useAuth } from '@/composables/useAuth'
import QelemMedaLogo from '@/components/common/QelemMedaLogo.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import LanguageToggle from '@/components/common/LanguageToggle.vue'
import NotificationBell from '@/components/common/NotificationBell.vue'
import { useLanguage } from '@/composables/useLanguage'

const props = defineProps({
  navItems: { type: Array, required: true },
  theme: { type: String, default: 'light' },
})

const route = useRoute()
const router = useRouter()
const { user, logout, openLogoutModal, hasFarmerCapability, hasBuyerCapability, switchRole, pendingApplications } = useAuth()
const { t } = useLanguage()

const isSidebarOpen = ref(true)
const isProfileMenuOpen = ref(false)
const isSettingsOpen = ref(false)
const smsEnabled = ref(true)
const dropdownRef = ref(null)

const isFarmerTheme = computed(() => props.theme === 'farmerDark' || user.value?.role === 'farmer' || route.path.startsWith('/farmer'))
const isAdmin = computed(() => user.value?.role === 'admin' || user.value?.is_admin)

const roleTitle = computed(() => {
  if (user.value?.role === 'admin') return 'Platform Administrator'
  if (user.value?.role === 'farmer') return 'Farmer Producer'
  if (user.value?.role === 'buyer') return 'Commercial Buyer'
  return 'Marketplace Member'
})

const isNavActive = (item) => {
  if (item.path === props.navItems[0]?.path) {
    return route.path === item.path
  }
  return route.path.startsWith(item.path)
}

const showCategoryHeader = (item, index) => {
  return isSidebarOpen.value && item.category && (index === 0 || props.navItems[index - 1].category !== item.category)
}

const goHome = () => {
  if (isAdmin.value) {
    router.push('/admin')
  } else {
    router.push(user.value?.role === 'farmer' ? '/farmer' : '/buyer')
  }
}

const goToProfile = () => {
  isProfileMenuOpen.value = false
  if (isAdmin.value) {
    router.push('/admin')
  } else {
    router.push(user.value?.role === 'farmer' ? '/farmer/profile' : '/buyer/profile')
  }
}

const handleRoleSwitch = () => {
  const nextRole = user.value?.role === 'farmer' ? 'buyer' : 'farmer'
  switchRole(nextRole)
  router.push(nextRole === 'farmer' ? '/farmer' : '/buyer')
}

const handleRoleSwitchDropdown = () => {
  isProfileMenuOpen.value = false
  handleRoleSwitch()
}

const handleLogout = () => {
  isProfileMenuOpen.value = false
  openLogoutModal()
}

// Close dropdown on click outside
const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isProfileMenuOpen.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside))
</script>
