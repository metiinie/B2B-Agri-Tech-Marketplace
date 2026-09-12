<template>
  <div class="min-h-screen bg-slate-50 dark:bg-[#0D1117] font-sans selection:bg-blue-500/30 selection:text-blue-900 dark:selection:text-blue-100 overflow-x-hidden">
    <!-- Navbar -->
    <nav class="fixed top-0 w-full z-50 transition-all duration-300" :class="{ 'bg-white/70 dark:bg-[#0D1117]/70 backdrop-blur-xl border-b border-slate-200/50 dark:border-slate-800/50 shadow-sm': scrolled, 'bg-transparent': !scrolled }">
      <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
        <div class="group cursor-pointer">
          <QelemMedaLogo :size="42" variant="full" :showTagline="true" class="transform group-hover:scale-105 transition-all duration-300" />
        </div>
        
        <div class="hidden md:flex items-center gap-8">
          <a href="#marketplace" class="text-sm font-semibold text-slate-600 hover:text-[#0B57D0] dark:text-slate-400 dark:hover:text-blue-400 transition-colors">{{ $t('landing.navMarketplace') }}</a>
          <a href="#features" class="text-sm font-semibold text-slate-600 hover:text-[#0B57D0] dark:text-slate-400 dark:hover:text-blue-400 transition-colors">{{ $t('landing.navFeatures') }}</a>
          <a href="#how-it-works" class="text-sm font-semibold text-slate-600 hover:text-[#0B57D0] dark:text-slate-400 dark:hover:text-blue-400 transition-colors">{{ $t('landing.navHowItWorks') }}</a>
        </div>

        <div class="flex items-center gap-4">
          <ThemeToggle />
          <LanguageToggle />
          <template v-if="token">
            <router-link :to="dashboardRoute" class="text-sm font-bold bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-5 py-2.5 rounded-full hover:bg-[#0B57D0] dark:hover:bg-blue-500 hover:text-white transition-all shadow-md hover:shadow-xl hover:-translate-y-0.5 duration-300 flex items-center gap-2">
              {{ $t('landing.navGoToDashboard') }}
              <ArrowRight class="w-4 h-4" />
            </router-link>
          </template>
          <template v-else>
            <router-link to="/login" class="text-sm font-bold text-slate-700 dark:text-slate-300 hover:text-[#0B57D0] dark:hover:text-blue-400 transition-colors">{{ $t('landing.navSignIn') }}</router-link>
            <router-link to="/register" class="text-sm font-bold bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-5 py-2.5 rounded-full hover:bg-[#0B57D0] dark:hover:bg-blue-500 hover:text-white dark:hover:text-white transition-all shadow-md hover:shadow-xl hover:-translate-y-0.5 duration-300">
              {{ $t('landing.navGetStarted') }}
            </router-link>
          </template>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <main class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
      <!-- Decorative Background Elements -->
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-blue-400/20 dark:bg-blue-500/10 rounded-full blur-3xl opacity-50 animate-pulse pointer-events-none"></div>
      <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-emerald-400/10 dark:bg-emerald-500/5 rounded-full blur-3xl opacity-60 pointer-events-none"></div>
      
      <div class="max-w-7xl mx-auto px-6 relative z-10 text-center">
        
        <h1 class="text-5xl md:text-7xl font-black text-slate-900 dark:text-white tracking-tight leading-[1.1] mb-6 animate-fade-in-up animation-delay-100">
          {{ $t('landing.heroMainTitle1') }}<span class="text-transparent bg-clip-text bg-gradient-to-r from-[#0B57D0] to-teal-400">{{ $t('landing.heroMainTitle2') }}</span><br />
          {{ $t('landing.heroMainTitle3') }}
        </h1>
        
        <p class="text-lg md:text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto mb-10 leading-relaxed font-medium animate-fade-in-up animation-delay-200">
          {{ $t('landing.heroSubtitle') }}
        </p>
        
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 animate-fade-in-up animation-delay-300">
          <template v-if="token">
            <router-link :to="dashboardRoute" class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-[#0B57D0] to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-2xl font-bold shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-2 group">
              {{ $t('landing.heroAccessDashboard') }}
              <ArrowRight class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
            </router-link>
          </template>
          <template v-else>
            <router-link to="/register" class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-[#0B57D0] to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-2xl font-bold shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-2 group">
              {{ $t('landing.heroStartTrading') }}
              <ArrowRight class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
            </router-link>
          </template>
          
          <a href="#features" class="w-full sm:w-auto px-8 py-4 bg-white dark:bg-[#161B22] text-slate-700 dark:text-slate-200 rounded-2xl font-bold shadow-md hover:shadow-lg hover:-translate-y-1 border border-slate-200 dark:border-slate-800 transition-all duration-300 flex items-center justify-center gap-2">
            {{ $t('landing.heroExploreFeatures') }}
          </a>
        </div>
      </div>
    </main>

    <!-- Marketplace Section -->
    <section id="marketplace" class="py-24 bg-slate-50 dark:bg-[#0D1117] relative border-t border-slate-200/50 dark:border-slate-800/50">
      <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-12">
          <h2 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white mb-4">{{ $t('landing.marketTitle') }}</h2>
          <p class="text-slate-600 dark:text-slate-400 text-lg font-medium">{{ $t('landing.marketSubtitle') }}</p>
        </div>
        
        <div v-if="isLoadingListings" class="flex justify-center p-12">
          <div class="w-10 h-10 border-4 border-[#0B57D0] border-t-transparent rounded-full animate-spin"></div>
        </div>
        <div v-else-if="featuredListings.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <ListingCard v-for="listing in featuredListings" :key="listing.id" :listing="listing" @addToCart="requireLogin" />
        </div>
        <div v-else class="text-center py-12 bg-white dark:bg-[#161B22] rounded-3xl border border-slate-200 dark:border-slate-800">
           <Sprout class="w-12 h-12 text-slate-400 mx-auto mb-4" />
           <p class="text-slate-600 dark:text-slate-400 font-bold text-lg">{{ $t('landing.marketNoListings') }}</p>
        </div>

        <div class="text-center mt-12">
           <button @click="requireLogin" class="bg-[#0B57D0] hover:bg-blue-600 text-white font-bold px-8 py-3 rounded-xl transition-colors shadow-lg shadow-blue-500/30">{{ $t('landing.marketSignInToViewAll') }}</button>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-white dark:bg-[#161B22] relative border-y border-slate-200/50 dark:border-slate-800/50">
      <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white mb-4">{{ $t('landing.featTitle') }}</h2>
          <p class="text-slate-600 dark:text-slate-400 text-lg font-medium">{{ $t('landing.featSubtitle') }}</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <!-- Feature 1 -->
          <div class="p-8 rounded-3xl bg-slate-50 dark:bg-[#0D1117] border border-slate-100 dark:border-slate-800/60 hover:shadow-2xl hover:shadow-blue-500/5 hover:-translate-y-2 transition-all duration-300 group">
            <div class="w-14 h-14 rounded-2xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-6 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform duration-300">
              <ShieldCheck class="w-7 h-7" />
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ $t('landing.feat1Title') }}</h3>
            <p class="text-slate-600 dark:text-slate-400 font-medium leading-relaxed">
              {{ $t('landing.feat1Desc') }}
            </p>
          </div>

          <!-- Feature 2 -->
          <div class="p-8 rounded-3xl bg-slate-50 dark:bg-[#0D1117] border border-slate-100 dark:border-slate-800/60 hover:shadow-2xl hover:shadow-emerald-500/5 hover:-translate-y-2 transition-all duration-300 group">
            <div class="w-14 h-14 rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-6 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform duration-300">
              <Leaf class="w-7 h-7" />
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ $t('landing.feat2Title') }}</h3>
            <p class="text-slate-600 dark:text-slate-400 font-medium leading-relaxed">
              {{ $t('landing.feat2Desc') }}
            </p>
          </div>

          <!-- Feature 3 -->
          <div class="p-8 rounded-3xl bg-slate-50 dark:bg-[#0D1117] border border-slate-100 dark:border-slate-800/60 hover:shadow-2xl hover:shadow-amber-500/5 hover:-translate-y-2 transition-all duration-300 group">
            <div class="w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-6 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform duration-300">
              <LineChart class="w-7 h-7" />
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ $t('landing.feat3Title') }}</h3>
            <p class="text-slate-600 dark:text-slate-400 font-medium leading-relaxed">
              {{ $t('landing.feat3Desc') }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-24 bg-slate-50 dark:bg-[#0D1117] relative">
      <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white mb-4">{{ $t('landing.howItWorksTitle') }}</h2>
        </div>
        <div class="grid md:grid-cols-4 gap-6 text-center">
          <div class="relative">
            <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 text-2xl font-black flex items-center justify-center mx-auto mb-4 z-10 relative">1</div>
            <h4 class="font-bold text-slate-900 dark:text-white mb-2">{{ $t('landing.how1Title') }}</h4>
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $t('landing.how1Desc') }}</p>
          </div>
          <div class="relative">
            <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 text-2xl font-black flex items-center justify-center mx-auto mb-4 z-10 relative">2</div>
            <h4 class="font-bold text-slate-900 dark:text-white mb-2">{{ $t('landing.how2Title') }}</h4>
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $t('landing.how2Desc') }}</p>
          </div>
          <div class="relative">
            <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 text-2xl font-black flex items-center justify-center mx-auto mb-4 z-10 relative">3</div>
            <h4 class="font-bold text-slate-900 dark:text-white mb-2">{{ $t('landing.how3Title') }}</h4>
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $t('landing.how3Desc') }}</p>
          </div>
          <div class="relative">
            <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 text-2xl font-black flex items-center justify-center mx-auto mb-4 z-10 relative">4</div>
            <h4 class="font-bold text-slate-900 dark:text-white mb-2">{{ $t('landing.how4Title') }}</h4>
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $t('landing.how4Desc') }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer CTA -->
    <footer class="py-20 relative overflow-hidden">
      <div class="absolute inset-0 bg-[#0B57D0] dark:bg-blue-900/90 transform -skew-y-2 scale-110 z-0 origin-top-left"></div>
      <div class="max-w-4xl mx-auto px-6 relative z-10 text-center">
        <h2 class="text-4xl font-black text-white mb-6">{{ $t('landing.footerTitle') }}</h2>
        <p class="text-blue-100 text-lg mb-10 max-w-2xl mx-auto">
          {{ $t('landing.footerDesc') }}
        </p>
        <template v-if="!token">
          <router-link to="/register" class="inline-flex items-center gap-2 px-8 py-4 bg-white text-[#0B57D0] rounded-2xl font-bold text-lg shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-300">
            {{ $t('landing.footerCreateAccount') }}
            <UserPlus class="w-5 h-5" />
          </router-link>
        </template>
        <template v-else>
          <router-link :to="dashboardRoute" class="inline-flex items-center gap-2 px-8 py-4 bg-white text-[#0B57D0] rounded-2xl font-bold text-lg shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-300">
            {{ $t('landing.footerGoToDashboard') }}
            <ArrowRight class="w-5 h-5" />
          </router-link>
        </template>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Sprout, ArrowRight, ShieldCheck, Leaf, LineChart, UserPlus } from 'lucide-vue-next'
import { getAuthToken } from '@/services/api'
import { useRouter } from 'vue-router'
import { useListings } from '@/composables/useListings'
import ListingCard from '@/components/shared/ListingCard.vue'
import QelemMedaLogo from '@/components/common/QelemMedaLogo.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import LanguageToggle from '@/components/common/LanguageToggle.vue'

const router = useRouter()
const { listings, isLoading: isLoadingListings } = useListings()

const featuredListings = computed(() => listings.value.slice(0, 6))

const requireLogin = () => {
   router.push('/login')
}

const scrolled = ref(false)

const handleScroll = () => {
  scrolled.value = window.scrollY > 20
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})

const token = ref(getAuthToken())

const dashboardRoute = computed(() => {
  try {
    const userDataStr = localStorage.getItem('agri_user_data')
    if (userDataStr) {
      const u = JSON.parse(userDataStr)
      if (u.role === 'farmer' || u.activeRole === 'farmer') return '/farmer'
      if (u.role === 'admin' || u.activeRole === 'admin') return '/admin'
      return '/buyer'
    }
  } catch(e) { }
  return '/buyer'
})
</script>

<style scoped>
.animate-fade-in-up {
  opacity: 0;
  transform: translateY(20px);
  animation: fade-in-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.animation-delay-100 { animation-delay: 100ms; }
.animation-delay-200 { animation-delay: 200ms; }
.animation-delay-300 { animation-delay: 300ms; }

@keyframes fade-in-up {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
