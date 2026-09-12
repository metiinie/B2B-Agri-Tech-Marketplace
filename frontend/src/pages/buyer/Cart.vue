<template>
  <div class="space-y-6 max-w-4xl mx-auto pb-12">
    <!-- Top Header -->
    <div class="flex items-center justify-between border-b border-[#E2E4E7] dark:border-[#30363D] pb-4">
      <div class="flex items-center gap-3">
        <router-link to="/buyer/marketplace" class="p-2 hover:bg-gray-100 dark:hover:bg-[#21262D] rounded-xl text-gray-500 dark:text-gray-400 transition-colors">
          <ArrowLeft class="w-5 h-5" />
        </router-link>
        <div>
          <h1 class="text-xl font-black text-[#1E2328] dark:text-[#F0F6FC] tracking-tight">{{ $t('cart.title') }}</h1>
          <p class="text-xs text-[#5A6270] dark:text-[#8B949E]">{{ $t('cart.subtitle') }}</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button 
          v-if="cartItems.length > 0"
          @click="selectAll(!allSelected)" 
          class="text-xs font-bold text-[#0B57D0] dark:text-blue-400 hover:underline px-2 py-1"
        >
          {{ allSelected ? $t('cart.deselectAll') : $t('cart.selectAll') }}
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div v-if="cartItems.length > 0" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- Cart Items List (7 cols) -->
      <div class="lg:col-span-7 space-y-4">
        <div 
          v-for="item in cartItems" 
          :key="item.id"
          :class="[
            'bg-white dark:bg-[#161B22] border rounded-2xl p-4 transition-all shadow-2xs space-y-3',
            item.selected ? 'border-[#0B57D0] dark:border-blue-500 ring-1 ring-[#0B57D0]/20 dark:ring-blue-500/20' : 'border-[#E2E4E7] dark:border-[#30363D]'
          ]"
        >
          <div class="flex items-start gap-3">
            <!-- Select Checkbox -->
            <input 
              type="checkbox" 
              :checked="item.selected" 
              @change="toggleSelect(item.id)" 
              class="mt-1.5 w-4 h-4 accent-[#0B57D0] rounded cursor-pointer shrink-0" 
            />

            <!-- Produce Image / Emoji -->
            <div class="w-14 h-14 bg-[#F8F9FA] dark:bg-[#21262D] border border-gray-100 dark:border-[#30363D] rounded-xl flex items-center justify-center text-2xl shrink-0">
              {{ item.listing?.cropEmoji || '🌾' }}
            </div>

            <!-- Item Details -->
            <div class="flex-1 min-w-0 space-y-3">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <h3 class="text-sm font-bold text-[#1E2328] dark:text-[#F0F6FC] truncate">{{ item.listing?.cropName || $t('orders.crop') }}</h3>
                  <p class="text-[11px] text-[#5A6270] dark:text-[#8B949E]">
                    {{ item.listing?.farmer?.name || 'Producer' }} · {{ item.listing?.region || 'Ethiopia' }} · 
                    <span class="font-bold text-[#0B57D0] dark:text-blue-400">{{ item.listing?.grade || 'Grade 1' }}</span>
                  </p>
                </div>

                <!-- Price displayed prominently above quantity/unit controls -->
                <div class="flex items-center gap-3 shrink-0">
                  <div class="flex flex-col items-end text-right leading-tight">
                    <span class="font-black text-[#1E9444] dark:text-emerald-400 text-base leading-tight">
                      {{ formatETB(getItemSubtotal(item)) }}
                    </span>
                    <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 font-medium leading-tight">
                      {{ formatETB(item.listing?.pricePerKg) }}/{{ item.unit || 'kg' }}
                    </span>
                  </div>
                  <button @click="removeFromCart(item.id)" class="text-gray-400 dark:text-gray-500 hover:text-red-500 dark:hover:text-red-400 p-1">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <!-- Quantity Selector & Unit Dropdown (Full width row) -->
              <div class="flex items-center justify-between gap-3 pt-2 border-t border-gray-100 dark:border-[#21262D] text-xs">
                <div class="flex items-center gap-2">
                  <!-- Minus Button -->
                  <button 
                    type="button"
                    @click="updateQuantity(item.id, item.quantityKg - 1)"
                    :disabled="item.quantityKg <= 1"
                    class="w-10 h-10 bg-gray-100 dark:bg-[#21262D] hover:bg-gray-200 dark:hover:bg-[#30363D] text-gray-800 dark:text-[#F0F6FC] border border-gray-300 dark:border-[#30363D] disabled:opacity-40 disabled:hover:bg-gray-100 dark:disabled:hover:bg-[#21262D] rounded-xl font-black text-lg flex items-center justify-center transition-all cursor-pointer disabled:cursor-not-allowed shadow-2xs"
                    title="Decrease quantity by 1"
                  >
                    -
                  </button>
                  
                  <!-- Direct Quantity Input -->
                  <input 
                    type="number" 
                    :value="item.quantityKg"
                    @input="updateQuantity(item.id, $event.target.value)"
                    min="1"
                    :max="item.unit === 'Quintals' ? Math.max(1, Math.floor((item.listing?.availableQty || 100000) / 100)) : (item.listing?.availableQty || 100000)"
                    step="1"
                    class="w-20 py-2 bg-white dark:bg-[#0D1117] border border-[#E2E4E7] dark:border-[#30363D] focus:border-[#0B57D0] dark:focus:border-blue-500 focus:outline-none font-black text-[#1E2328] dark:text-[#F0F6FC] text-center rounded-xl text-sm shadow-2xs"
                  />

                  <!-- Plus Button -->
                  <button 
                    type="button"
                    @click="updateQuantity(item.id, item.quantityKg + 1)"
                    :disabled="item.quantityKg >= (item.unit === 'Quintals' ? Math.max(1, Math.floor((item.listing?.availableQty || 100000) / 100)) : (item.listing?.availableQty || 100000))"
                    class="w-10 h-10 bg-[#0B57D0]/10 dark:bg-blue-900/30 text-[#0B57D0] dark:text-blue-400 hover:bg-[#0B57D0] dark:hover:bg-blue-600 hover:text-white dark:hover:text-white border border-[#0B57D0]/30 dark:border-blue-700/50 disabled:opacity-40 rounded-xl font-black text-lg flex items-center justify-center transition-all cursor-pointer disabled:cursor-not-allowed shadow-2xs"
                    title="Increase quantity by 1"
                  >
                    +
                  </button>

                  <!-- Unit Selector Dropdown -->
                  <select 
                    :value="item.unit || 'KG'" 
                    @change="updateUnit(item.id, $event.target.value)"
                    class="px-2.5 py-2 bg-white dark:bg-[#0D1117] border border-[#E2E4E7] dark:border-[#30363D] focus:border-[#0B57D0] dark:focus:border-blue-500 focus:outline-none font-bold text-[#1E2328] dark:text-[#F0F6FC] rounded-xl text-xs shadow-2xs cursor-pointer"
                  >
                    <option value="KG">KG</option>
                    <option value="Quintals">Quintals (100 KG)</option>
                    <option value="Litres">Litres (L)</option>
                  </select>
                </div>

                <span class="text-[10px] text-gray-400 dark:text-gray-500 block text-right">
                  {{ $t('marketplace.availableStock') }}: {{ (item.listing?.availableQty || 10000).toLocaleString() }} {{ item.unit || 'kg' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Financial Summary & Bulk Checkout (5 cols) -->
      <div class="lg:col-span-5 space-y-6">
        <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-6 shadow-md space-y-5 sticky top-6">
          <h3 class="text-base font-bold text-[#1E2328] dark:text-[#F0F6FC] border-b border-gray-100 dark:border-[#21262D] pb-3 flex items-center gap-2">
            <ShoppingCart class="w-4 h-4 text-[#0B57D0] dark:text-blue-400" />
            <span>{{ $t('cart.orderSummary') }}</span>
          </h3>

          <div class="space-y-3 text-xs">
            <div class="flex justify-between items-center text-[#5A6270] dark:text-[#8B949E]">
              <span>{{ $t('cart.selectedItems') }}</span>
              <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ selectedCount }} / {{ cartItems.length }}</span>
            </div>

            <div class="flex justify-between items-center text-[#5A6270] dark:text-[#8B949E]">
              <span>Produce Subtotal</span>
              <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ formatETB(selectedSubtotal) }}</span>
            </div>

            <div class="flex justify-between items-center text-[#5A6270] dark:text-[#8B949E]">
              <span>{{ $t('cart.escrowFee') }}</span>
              <span class="font-bold text-emerald-600 dark:text-emerald-400">+{{ formatETB(escrowFee) }}</span>
            </div>

            <div class="border-t border-dashed border-gray-200 dark:border-[#30363D] pt-3 flex justify-between items-end gap-2">
              <div>
                <span class="text-xs font-bold text-[#5A6270] dark:text-[#8B949E] block">{{ $t('cart.totalPayable') }}</span>
                <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ $t('cart.escrowSecuredOrder') }}</span>
              </div>
              <span class="text-2xl font-black text-[#1E9444] dark:text-emerald-400 tracking-tight whitespace-nowrap">
                {{ formatETB(totalPayableWithFee) }}
              </span>
            </div>
          </div>

          <div class="bg-[#EDFAF2] dark:bg-emerald-950/40 border border-[#C3EFCF] dark:border-emerald-800/60 rounded-xl p-3.5 flex items-center gap-2.5 text-xs text-[#0F5C2A] dark:text-emerald-300">
            <ShieldCheck class="w-4 h-4 text-[#1E9444] dark:text-emerald-400 shrink-0" />
            <span>{{ $t('checkout.deliveryPinNotice') }}</span>
          </div>

          <button 
            @click="proceedToCheckout" 
            :disabled="selectedCount === 0"
            class="w-full py-4 rounded-xl bg-[#1E9444] text-white font-bold text-sm shadow-md hover:bg-[#0F5C2A] disabled:opacity-50 flex items-center justify-center gap-2 transition-all cursor-pointer"
          >
            <span>{{ $t('cart.proceedToCheckout') }} ({{ selectedCount }})</span>
            <ArrowRight class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Empty Cart View -->
    <div v-else class="text-center py-16 bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-3xl p-8 max-w-md mx-auto space-y-4 shadow-2xs">
      <div class="w-16 h-16 bg-gray-100 dark:bg-[#21262D] rounded-full flex items-center justify-center mx-auto text-gray-400 dark:text-gray-500">
        <ShoppingCart class="w-8 h-8" />
      </div>
      <h3 class="font-bold text-lg text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('cart.emptyCartTitle') }}</h3>
      <p class="text-xs text-[#5A6270] dark:text-[#8B949E]">{{ $t('cart.emptyCartSub') }}</p>
      <router-link to="/buyer/marketplace" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#0B57D0] text-white font-bold text-xs rounded-xl hover:bg-[#09429E] transition-colors shadow-2xs">
        {{ $t('cart.sourceProduce') }}
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowLeft, Trash2, ShoppingCart, ShieldCheck, ArrowRight } from 'lucide-vue-next'
import { useCart } from '@/composables/useCart'
import { formatETB } from '@/utils/helpers'

const router = useRouter()
const { cartItems, selectedItems, selectedCount, selectedSubtotal, escrowFee, totalPayableWithFee, removeFromCart, updateQuantity, updateUnit, getItemSubtotal, toggleSelect, selectAll } = useCart()

const allSelected = computed(() => {
  return cartItems.value.length > 0 && cartItems.value.every(i => i.selected)
})

const getItemStep = (item) => {
  const min = item.listing?.minOrderQty || 10
  if (min < 50) return 10
  if (min < 200) return 50
  if (min < 1000) return 100
  return 250
}

const proceedToCheckout = () => {
  if (selectedItems.value.length === 0) return
  router.push('/buyer/checkout')
}
</script>
