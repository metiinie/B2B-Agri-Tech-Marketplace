<template>
  <div class="w-full flex flex-col min-h-full pb-8 max-w-5xl mx-auto space-y-5">
    <div class="flex items-center gap-3">
      <button @click="$router.back()" class="w-9 h-9 rounded-xl border border-[#E2E4E7] dark:border-[#30363D] flex items-center justify-center hover:bg-[#F0F1F2] dark:hover:bg-[#21262D] transition-colors bg-white dark:bg-[#161B22] shadow-2xs">
        <ArrowLeft class="w-5 h-5 text-[#1E2328] dark:text-[#F0F6FC]" />
      </button>
      <span class="text-xs font-bold text-[#5A6270] dark:text-[#8B949E] uppercase tracking-wider">{{ $t('Back') }}</span>
    </div>
    <!-- Top Header Banner (Telegram-style UX) -->
    <div class="bg-gradient-to-br from-[#062E15] via-[#0F5C2A] to-[#0B57D0] text-white pt-10 pb-8 px-6 rounded-3xl shadow-sm relative overflow-hidden flex flex-col items-center text-center gap-4">
      <div class="absolute -top-10 right-10 w-48 h-48 bg-[#E69500]/25 rounded-full blur-3xl pointer-events-none" />
      <div class="absolute bottom-0 left-10 w-48 h-48 bg-[#1E9444]/30 rounded-full blur-3xl pointer-events-none" />
      
      <!-- Interactive Avatar upload / display -->
      <div class="relative group cursor-pointer z-10" @click="triggerPhotoUpload" title="Update Profile Photo">
        <div v-if="customPhotoUrl || farmer?.avatar" class="w-28 h-28 sm:w-32 sm:h-32 rounded-full overflow-hidden border-4 border-white shadow-xl bg-[#062E15] transition-transform duration-300 group-hover:scale-105">
          <img :src="customPhotoUrl || farmer?.avatar" alt="Profile" class="w-full h-full object-cover" />
        </div>
        <div v-else class="w-28 h-28 sm:w-32 sm:h-32 rounded-full bg-white/10 backdrop-blur-md border-4 border-white/50 hover:border-white flex items-center justify-center text-5xl font-black text-white shadow-xl transition-all duration-300 group-hover:scale-105">
          {{ farmer?.name?.[0] || 'D' }}
        </div>
        
        <!-- Telegram style floating camera badge -->
        <div class="absolute bottom-1 right-1 w-9 h-9 sm:w-10 sm:h-10 bg-[#1E9444] border-[3px] border-white rounded-full flex items-center justify-center shadow-lg transition-transform hover:scale-110 group-hover:bg-[#0F5C2A]">
          <Camera class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
        </div>
      </div>
      <input type="file" ref="fileInput" accept="image/*" class="hidden" @change="handlePhotoChange" />

      <div class="relative z-10 space-y-1 mt-1">
        <div class="flex items-center justify-center gap-2 flex-wrap">
          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">{{ farmer?.name || 'Dawit Bekele' }}</h1>
          <span class="px-2.5 py-1 rounded-full text-[10px] sm:text-xs font-extrabold bg-amber-400 text-amber-950 flex items-center gap-1 shadow-xs">
            <ShieldCheck class="w-3.5 h-3.5" />
            <span>{{ $t('badges.verifiedProducer') }}</span>
          </span>
        </div>
        <p class="text-sm text-[#C3EFCF] font-medium flex items-center justify-center gap-2 flex-wrap opacity-90">
          <span>{{ $t(farmer?.region) || 'Sidama' }} {{ $t('farmer.regionMember') }}</span>
          <span class="opacity-40">•</span>
          <span>{{ farmer?.phone || $t('No phone set') }}</span>
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-center gap-3 relative z-10 shrink-0 mt-3 w-full max-w-sm">
        <button @click="openEditModal" 
          class="flex-1 py-3 bg-white/15 hover:bg-white/25 border border-white/30 hover:border-white/50 text-white rounded-xl text-sm font-black transition-all flex items-center justify-center gap-2 cursor-pointer backdrop-blur-md shadow-sm">
          <Edit3 class="w-4 h-4 text-amber-300" />
          <span>{{ $t('Edit Profile') }}</span>
        </button>
        <button @click="openLogoutModal" 
          class="px-5 py-3 bg-red-500/20 hover:bg-red-500/30 border border-red-300/40 hover:border-red-300/70 text-red-100 rounded-xl text-sm font-black transition-all flex items-center justify-center gap-2 cursor-pointer backdrop-blur-md shadow-sm">
          <LogOut class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Success Toast Notification -->
    <div v-if="successMsg" class="p-3.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-300 text-xs font-bold rounded-xl flex items-center justify-between">
      <div class="flex items-center gap-2">
        <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
        <span>{{ $t(successMsg) }}</span>
      </div>
      <button @click="successMsg = ''" class="text-emerald-700 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-200 font-extrabold text-sm">&times;</button>
    </div>

    <!-- Producer Identity & Verification Grid -->
    <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-6 shadow-xs space-y-6">
      <div class="flex items-center justify-between border-b border-gray-100 dark:border-[#30363D] pb-3">
        <h3 class="text-base font-black text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('farmer.farmerProfileTitle') }}</h3>
        <span class="text-xs font-bold text-[#1E9444] dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 rounded-lg border border-emerald-200 dark:border-emerald-800/50">
          {{ $t('Chapa Escrow Settlement Ready') }}
        </span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs">
        <div class="bg-[#F8F9FA] dark:bg-[#21262D] p-4 rounded-xl border border-[#E2E4E7] dark:border-[#30363D]">
          <span class="text-[#5A6270] dark:text-[#8B949E] font-bold block mb-1">{{ $t('Registered Full Name') }}</span>
          <span class="text-sm font-black text-[#1E2328] dark:text-[#F0F6FC]">{{ farmer?.name || $t('Not set') }}</span>
        </div>

        <div class="bg-[#F8F9FA] dark:bg-[#21262D] p-4 rounded-xl border border-[#E2E4E7] dark:border-[#30363D]">
          <span class="text-[#5A6270] dark:text-[#8B949E] font-bold block mb-1">{{ $t('auth.mobilePhone') }}</span>
          <span class="text-sm font-black text-[#1E2328] dark:text-[#F0F6FC]">{{ farmer?.phone || $t('Not set') }}</span>
        </div>

        <div class="bg-[#F8F9FA] dark:bg-[#21262D] p-4 rounded-xl border border-[#E2E4E7] dark:border-[#30363D]">
          <span class="text-[#5A6270] dark:text-[#8B949E] font-bold block mb-1">{{ $t('Region') }}</span>
          <span class="text-sm font-black text-[#1E9444] dark:text-emerald-400">{{ $t(farmer?.region) || 'Sidama' }}</span>
        </div>

        <div class="bg-[#F8F9FA] dark:bg-[#21262D] p-4 rounded-xl border border-[#E2E4E7] dark:border-[#30363D]">
          <span class="text-[#5A6270] dark:text-[#8B949E] font-bold block mb-1">{{ $t('auth.farmSizeHectares') }}</span>
          <span class="text-sm font-black text-[#1E2328] dark:text-[#F0F6FC]">{{ farmer?.farmSize ? farmer.farmSize + ' ' + $t('Hectares') : $t('Not specified') }}</span>
        </div>

        <div class="bg-[#F8F9FA] dark:bg-[#21262D] p-4 rounded-xl border border-[#E2E4E7] dark:border-[#30363D]">
          <span class="text-[#5A6270] dark:text-[#8B949E] font-bold block mb-1">{{ $t('auth.primaryCrops') }}</span>
          <span class="text-sm font-black text-[#1E2328] dark:text-[#F0F6FC]">
            {{ Array.isArray(farmer?.crops) ? farmer.crops.join(', ') : (farmer?.crops || $t('Not specified')) }}
          </span>
        </div>

        <div class="bg-[#F8F9FA] dark:bg-[#21262D] p-4 rounded-xl border border-[#E2E4E7] dark:border-[#30363D]">
          <span class="text-[#5A6270] dark:text-[#8B949E] font-bold block mb-1">{{ $t('farmer.primaryUnionCoops') }}</span>
          <span class="text-sm font-black text-[#0B57D0] dark:text-blue-400">{{ $t(farmer?.union) || $t('Independent Producer') }}</span>
        </div>

        <div class="bg-[#F8F9FA] dark:bg-[#21262D] p-4 rounded-xl border border-[#E2E4E7] dark:border-[#30363D] sm:col-span-2 md:col-span-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <span class="text-[#5A6270] dark:text-[#8B949E] font-bold block text-xs">{{ $t('farmer.payoutMethod') }}</span>
            <span class="text-sm font-black text-[#1E2328] dark:text-[#F0F6FC] flex items-center gap-2 mt-0.5">
              <span class="text-[#0B57D0] dark:text-blue-400 uppercase font-mono font-bold px-2 py-0.5 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/50 rounded-md text-xs">
                {{ farmer?.bank_code || farmer?.bank_name || 'CBE' }}
              </span>
              <span>{{ farmer?.account_number ? ('******' + farmer.account_number.slice(-4)) : $t('Not configured') }}</span>
              <span v-if="farmer?.account_name" class="text-xs font-semibold text-gray-500 dark:text-gray-400">({{ farmer.account_name }})</span>
            </span>
          </div>
          <button @click="openEditModal" class="text-xs font-black text-[#0B57D0] dark:text-blue-400 hover:underline self-start sm:self-auto cursor-pointer">
            {{ $t('Payout Account Settings') }} &rarr;
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Profile Modal with Tabbed Sections -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="max-w-lg w-full bg-white dark:bg-[#161B22] rounded-3xl p-6 shadow-2xl space-y-5 text-[#1E2328] dark:text-[#F0F6FC] border border-gray-100 dark:border-[#30363D] animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-[#30363D] pb-3">
          <div class="flex items-center gap-2.5">
            <div class="p-2 bg-emerald-50 dark:bg-emerald-950/40 text-[#1E9444] dark:text-emerald-400 rounded-xl">
              <UserCheck class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-black text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('Edit Farmer Profile') }}</h3>
              <p class="text-[11px] text-[#5A6270] dark:text-[#8B949E]">{{ $t('farmer.farmerProfileSub') }}</p>
            </div>
          </div>
          <button @click="showEditModal = false" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#21262D] rounded-xl transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex border-b border-gray-100 dark:border-[#30363D] text-xs font-bold gap-4">
          <button 
            @click="activeTab = 'credentials'" 
            :class="[
              'pb-2.5 border-b-2 transition-colors flex items-center gap-1.5 cursor-pointer',
              activeTab === 'credentials' ? 'border-[#1E9444] dark:border-emerald-400 text-[#1E9444] dark:text-emerald-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-[#F0F6FC]'
            ]"
          >
            <User class="w-3.5 h-3.5" />
            <span>{{ $t('Credentials & Password') }}</span>
          </button>
          <button 
            @click="activeTab = 'farm'" 
            :class="[
              'pb-2.5 border-b-2 transition-colors flex items-center gap-1.5 cursor-pointer',
              activeTab === 'farm' ? 'border-[#1E9444] dark:border-emerald-400 text-[#1E9444] dark:text-emerald-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-[#F0F6FC]'
            ]"
          >
            <Sprout class="w-3.5 h-3.5" />
            <span>{{ $t('Farm & Payout Account') }}</span>
          </button>
        </div>

        <!-- Error Alert -->
        <div v-if="modalError" class="p-3 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/50 text-red-700 dark:text-red-300 text-xs font-bold rounded-xl flex items-center gap-2">
          <AlertCircle class="w-4 h-4 text-red-600 dark:text-red-400 shrink-0" />
          <span>{{ $t(modalError) }}</span>
        </div>

        <!-- Tab 1: Credentials & Security -->
        <div v-if="activeTab === 'credentials'" class="space-y-4 text-xs">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-extrabold text-[#1E2328] dark:text-[#F0F6FC] mb-1">{{ $t('auth.firstName') }}</label>
              <input type="text" v-model="form.first_name" :placeholder="$t('auth.firstName')"
                class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] placeholder:text-gray-400 dark:placeholder:text-[#8B949E] rounded-xl font-bold text-xs focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-all" />
            </div>
            <div>
              <label class="block font-extrabold text-[#1E2328] dark:text-[#F0F6FC] mb-1">{{ $t('auth.secondName') }}</label>
              <input type="text" v-model="form.second_name" :placeholder="$t('auth.secondName')"
                class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] placeholder:text-gray-400 dark:placeholder:text-[#8B949E] rounded-xl font-bold text-xs focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-all" />
            </div>
          </div>

          <div>
            <label class="block font-extrabold text-[#1E2328] dark:text-[#F0F6FC] mb-1">{{ $t('auth.mobilePhone') }}</label>
            <div class="relative">
              <Phone class="w-4 h-4 text-gray-400 absolute left-3 top-3" />
              <input type="text" v-model="form.phone" placeholder="+251 912 345 678"
                class="w-full pl-9 pr-3.5 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] placeholder:text-gray-400 dark:placeholder:text-[#8B949E] rounded-xl font-bold text-xs focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-all" />
            </div>
          </div>

          <div class="pt-2 border-t border-gray-100 dark:border-[#30363D]">
            <h4 class="font-black text-[#1E2328] dark:text-[#F0F6FC] mb-2 flex items-center gap-1.5">
              <Key class="w-3.5 h-3.5 text-[#1E9444]" />
              <span>{{ $t('Change Password (Optional)') }}</span>
            </h4>
            <div class="space-y-2.5">
              <div>
                <label class="block text-[11px] font-bold text-gray-600 dark:text-[#8B949E] mb-1">{{ $t('Current Password') }}</label>
                <input type="password" v-model="form.current_password" :placeholder="$t('Enter current password if changing')"
                  class="w-full px-3.5 py-2 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] placeholder:text-gray-400 dark:placeholder:text-[#8B949E] rounded-xl font-semibold text-xs focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-all" />
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                  <label class="block text-[11px] font-bold text-gray-600 dark:text-[#8B949E] mb-1">{{ $t('New Password') }}</label>
                  <input type="password" v-model="form.new_password" placeholder="At least 6 chars"
                    class="w-full px-3.5 py-2 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] placeholder:text-gray-400 dark:placeholder:text-[#8B949E] rounded-xl font-semibold text-xs focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-all" />
                </div>
                <div>
                  <label class="block text-[11px] font-bold text-gray-600 dark:text-[#8B949E] mb-1">{{ $t('auth.confirmPassword') }}</label>
                  <input type="password" v-model="form.confirm_password" placeholder="Re-type new password"
                    class="w-full px-3.5 py-2 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] placeholder:text-gray-400 dark:placeholder:text-[#8B949E] rounded-xl font-semibold text-xs focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-all" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 2: Farm & Payout Details -->
        <div v-if="activeTab === 'farm'" class="space-y-3.5 text-xs">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-extrabold text-[#1E2328] dark:text-[#F0F6FC] mb-1">{{ $t('Region') }}</label>
              <select v-model="form.region" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] rounded-xl font-bold text-xs focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-all">
                <option value="Sidama">{{ $t('Sidama') }}</option>
                <option value="Oromia">{{ $t('Oromia') }}</option>
                <option value="Amhara">{{ $t('Amhara') }}</option>
                <option value="SNNPR">{{ $t('SNNPR') }}</option>
                <option value="South Ethiopia">{{ $t('South Ethiopia') }}</option>
                <option value="Tigray">{{ $t('Tigray') }}</option>
                <option value="Harari">{{ $t('Harari') }}</option>
              </select>
            </div>
            <div>
              <label class="block font-extrabold text-[#1E2328] dark:text-[#F0F6FC] mb-1">{{ $t('auth.farmSizeHectares') }}</label>
              <input type="number" step="0.5" v-model="form.farmSize" placeholder="e.g. 14.5"
                class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] placeholder:text-gray-400 dark:placeholder:text-[#8B949E] rounded-xl font-bold text-xs focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-all" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-extrabold text-[#1E2328] dark:text-[#F0F6FC] mb-1">{{ $t('auth.primaryCrops') }}</label>
              <input type="text" v-model="form.crops" placeholder="e.g. Coffee, Teff, Spices"
                class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] placeholder:text-gray-400 dark:placeholder:text-[#8B949E] rounded-xl font-bold text-xs focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-all" />
            </div>
            <div>
              <label class="block font-extrabold text-[#1E2328] dark:text-[#F0F6FC] mb-1">{{ $t('farmer.primaryUnionCoops') }}</label>
              <input type="text" v-model="form.union" placeholder="e.g. Sidama Farmers Union"
                class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] placeholder:text-gray-400 dark:placeholder:text-[#8B949E] rounded-xl font-bold text-xs focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-all" />
            </div>
          </div>

          <div class="pt-2 border-t border-gray-100 dark:border-[#30363D]">
            <h4 class="font-black text-[#1E2328] dark:text-[#F0F6FC] mb-2 flex items-center gap-1.5">
              <CreditCard class="w-3.5 h-3.5 text-[#1E9444]" />
              <span>{{ $t('farmer.payoutMethod') }}</span>
            </h4>
            <div class="space-y-2.5">
              <div>
                <label class="block text-[11px] font-bold text-gray-600 dark:text-[#8B949E] mb-1">{{ $t('farmer.payoutMethod') }}</label>
                <select v-model="form.bank_code" class="w-full px-3.5 py-2 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] rounded-xl font-bold text-xs focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-all">
                  <option value="CBE">Commercial Bank of Ethiopia (CBE)</option>
                  <option value="TELEBIRR">Telebirr Mobile Money</option>
                  <option value="DASHEN">Dashen Bank</option>
                  <option value="AWASH">Awash Bank</option>
                  <option value="ABYSSINIA">Bank of Abyssinia</option>
                  <option value="COOP">Cooperative Bank of Oromia</option>
                </select>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                  <label class="block text-[11px] font-bold text-gray-600 dark:text-[#8B949E] mb-1">{{ $t('Account Number / Telebirr Phone') }}</label>
                  <input type="text" v-model="form.account_number" placeholder="1000..."
                    class="w-full px-3.5 py-2 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] placeholder:text-gray-400 dark:placeholder:text-[#8B949E] font-mono font-bold text-xs focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-all" />
                </div>
                <div>
                  <label class="block text-[11px] font-bold text-gray-600 dark:text-[#8B949E] mb-1">{{ $t('marketplace.holder') }}</label>
                  <input type="text" v-model="form.account_name" placeholder="Full name as on account"
                    class="w-full px-3.5 py-2 bg-gray-50 dark:bg-[#21262D] border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] placeholder:text-gray-400 dark:placeholder:text-[#8B949E] rounded-xl font-semibold text-xs focus:outline-none focus:border-[#1E9444] focus:bg-white dark:focus:bg-[#161B22] transition-all" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex gap-2.5 pt-3 border-t border-gray-100 dark:border-[#30363D]">
          <button @click="showEditModal = false" class="flex-1 py-2.5 border border-gray-200 dark:border-[#30363D] rounded-xl font-bold text-xs text-gray-700 dark:text-[#8B949E] hover:bg-gray-50 dark:hover:bg-[#21262D] transition-colors cursor-pointer">
            {{ $t('Cancel') }}
          </button>
          <button @click="saveProfile" :disabled="isSaving"
            class="flex-1 py-2.5 bg-[#1E9444] hover:bg-[#167033] text-white rounded-xl font-bold text-xs transition-colors shadow-sm flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer">
            <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
            <span>{{ $t('Save Profile') }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { 
  ShieldCheck, Phone, Edit3, LogOut, Camera, X, User, Key, 
  UserCheck, AlertCircle, CheckCircle2, Loader2, Sprout, CreditCard, ArrowLeft 
} from 'lucide-vue-next'
import { useAuth } from '@/composables/useAuth'
import { api } from '@/services/api'
import { compressImageFile } from '@/utils/imageCompressor'

const { user, logout, openLogoutModal, updateUserProfile } = useAuth()
const farmer = computed(() => user.value)

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
  region: '',
  farmSize: '',
  crops: '',
  union: '',
  bank_code: '',
  bank_name: '',
  account_number: '',
  account_name: ''
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
  form.region = user.value?.region || 'Sidama'
  form.farmSize = user.value?.farmSize || 14.5
  form.crops = user.value?.crops?.join(', ') || 'Coffee, Teff, Sesame'
  form.union = user.value?.union || 'Sidama Union'
  form.bank_code = user.value?.bank_code || 'CBE'
  form.bank_name = user.value?.bank_name || 'Commercial Bank of Ethiopia'
  form.account_number = user.value?.account_number || ''
  form.account_name = user.value?.account_name || user.value?.name || ''
  activeTab.value = 'credentials'
  showEditModal.value = true
}

const triggerPhotoUpload = () => {
  if (fileInput.value) {
    fileInput.value.click()
  }
}

const handlePhotoChange = async (e) => {
  let file = e.target.files?.[0]
  if (file) {
    try {
      file = await compressImageFile(file, 800, 800, 0.8)
    } catch { /* fallback */ }

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
      region: form.region,
      farmSize: form.farmSize,
      crops: form.crops,
      union: form.union,
      bank_code: form.bank_code,
      bank_name: form.bank_name,
      account_number: form.account_number,
      account_name: form.account_name,
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
