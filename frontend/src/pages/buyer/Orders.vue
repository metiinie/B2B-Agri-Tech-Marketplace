<template>
  <div class="space-y-6 pb-6">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#E2E4E7] dark:border-[#30363D] pb-4">
      <div>
        <h1 class="text-2xl font-black text-[#1E2328] dark:text-[#F0F6FC] tracking-tight">
          {{ $t('orders.title') }} 📦
        </h1>
        <p class="text-xs text-[#5A6270] dark:text-[#8B949E] mt-0.5">
          {{ $t('orders.subtitle') }}
        </p>
      </div>

      <router-link to="/buyer/marketplace" 
        class="px-4 py-2 bg-[#E69500] text-white rounded-xl text-xs font-extrabold hover:bg-[#D48900] transition-colors self-start sm:self-auto flex items-center gap-1.5 shadow-2xs">
        <Store class="w-4 h-4" />
        <span>Source Produce</span>
      </router-link>
    </div>

    <!-- 4 Metrics Summary Bar -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-4 shadow-2xs">
        <div class="flex items-center justify-between text-[#5A6270] dark:text-[#8B949E]">
          <span class="text-[11px] font-bold uppercase">{{ $t('orders.totalOrders') }}</span>
          <Package class="w-4 h-4 text-[#0B57D0] dark:text-blue-400" />
        </div>
        <p class="text-2xl font-black text-[#1E2328] dark:text-[#F0F6FC] mt-1">{{ orders.length }}</p>
        <span class="text-[11px] text-[#5A6270] dark:text-[#8B949E] font-medium">{{ $t('B2B procurement batches') }}</span>
      </div>

      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-4 shadow-2xs">
        <div class="flex items-center justify-between text-[#5A6270] dark:text-[#8B949E]">
          <span class="text-[11px] font-bold uppercase">{{ $t('orders.activeShipments') }}</span>
          <Truck class="w-4 h-4 text-[#E69500]" />
        </div>
        <p class="text-2xl font-black text-[#1E2328] dark:text-[#F0F6FC] mt-1">{{ activeShipmentsCount }}</p>
        <span class="text-[11px] text-amber-700 dark:text-amber-400 font-semibold">{{ $t('En route or dispatched') }}</span>
      </div>

      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-4 shadow-2xs">
        <div class="flex items-center justify-between text-[#5A6270] dark:text-[#8B949E]">
          <span class="text-[11px] font-bold uppercase">{{ $t('orders.escrowLocked') }}</span>
          <ShieldCheck class="w-4 h-4 text-[#1E9444] dark:text-emerald-400" />
        </div>
        <p class="text-2xl font-black text-[#1E2328] dark:text-[#F0F6FC] mt-1">{{ formatETB(totalEscrowLockedETB) }}</p>
        <span class="text-[11px] text-[#1E9444] dark:text-emerald-400 font-semibold">{{ $t('Secured capital') }}</span>
      </div>

      <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl p-4 shadow-2xs">
        <div class="flex items-center justify-between text-[#5A6270] dark:text-[#8B949E]">
          <span class="text-[11px] font-bold uppercase">{{ $t('orders.completed') }}</span>
          <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
        </div>
        <p class="text-2xl font-black text-[#1E2328] dark:text-[#F0F6FC] mt-1">{{ completedOrdersCount }}</p>
        <span class="text-[11px] text-emerald-700 dark:text-emerald-400 font-semibold">{{ $t('Funds released to farmer') }}</span>
      </div>
    </div>

    <!-- Filter Tabs & Search Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <!-- Tabs -->
      <div class="flex flex-wrap gap-2">
        <button v-for="tab in filterTabs" :key="tab.value" @click="activeTab = tab.value"
          :class="['px-3.5 py-1.5 rounded-xl text-xs font-extrabold transition-all border cursor-pointer flex items-center gap-1.5',
            activeTab === tab.value ? 'bg-[#0B57D0] text-white border-[#0B57D0] shadow-xs' : 'bg-white dark:bg-[#161B22] text-[#5A6270] dark:text-[#8B949E] border-[#E2E4E7] dark:border-[#30363D] hover:border-[#0B57D0]']">
          <span>{{ $t(tab.label) }}</span>
          <span v-if="getTabCount(tab.value) > 0" 
            :class="['px-1.5 py-0.2 rounded-full text-[10px] font-bold', activeTab === tab.value ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-[#21262D] text-[#5A6270] dark:text-[#8B949E]']">
            {{ getTabCount(tab.value) }}
          </span>
        </button>
      </div>

      <!-- Search Input -->
      <div class="w-full sm:w-64">
        <div class="relative">
          <Search class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 absolute left-3 top-2.5" />
          <input type="text" v-model="searchQuery" :placeholder="$t('orders.searchOrders')" 
            class="w-full pl-8 pr-3 py-1.5 bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-xl text-xs font-bold text-[#1E2328] dark:text-[#F0F6FC] focus:outline-none focus:border-[#0B57D0] shadow-2xs" />
        </div>
      </div>
    </div>

    <!-- Orders List -->
    <div v-if="filteredOrders.length === 0" class="text-center py-12 bg-white dark:bg-[#161B22] rounded-2xl border border-[#E2E4E7] dark:border-[#30363D] space-y-2">
      <Package class="w-10 h-10 text-gray-400 dark:text-gray-500 mx-auto" />
      <p class="font-bold text-sm text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('orders.noOrdersTitle') }}</p>
      <p class="text-xs text-[#5A6270] dark:text-[#8B949E]">{{ $t('orders.noOrdersSub') }}</p>
      <router-link to="/buyer/marketplace" class="inline-block mt-2 px-4 py-2 bg-[#E69500] text-white rounded-xl text-xs font-extrabold hover:bg-[#D48900]">
        Go to Marketplace
      </router-link>
    </div>

    <!-- Redesigned B2B Compact Horizontal Order Cards -->
    <div v-else class="space-y-4">
      <div class="space-y-2.5">
        <div v-for="order in paginatedOrders" :key="order.id" 
          class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-xl px-4 py-3 shadow-2xs hover:border-[#0B57D0]/50 transition-all">
          
          <!-- Main Compact Horizontal Row -->
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <!-- Left: Crop Avatar + Produce & Parties -->
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 sm:w-10 sm:h-10 bg-[#EDFAF2] dark:bg-emerald-950/40 rounded-xl flex items-center justify-center text-xl border border-[#C3EFCF] dark:border-emerald-800/60 shrink-0 shadow-2xs">
                {{ order.listing?.cropEmoji || '🌾' }}
              </div>
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <h4 class="text-sm font-black text-[#1E2328] dark:text-[#F0F6FC]">
                    {{ $t(order.listing?.cropName) || $t('Produce Batch') }}
                  </h4>
                  <span class="px-1.5 py-0.2 rounded bg-gray-100 dark:bg-[#21262D] text-[#5A6270] dark:text-[#8B949E] text-[10px] font-bold">
                    {{ order.quantityKg?.toLocaleString() }} kg
                  </span>
                  <span class="text-xs font-mono font-bold text-[#1E2328] dark:text-[#F0F6FC]">
                    #{{ order.displayId }}
                  </span>
                  <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black capitalize border shadow-2xs', statusBadgeClass(order.status || 'placed')]">
                    {{ formatStatusLabel(order.status) }}
                  </span>
                </div>
                
                <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-0.5 text-[11px] text-[#5A6270] dark:text-[#8B949E]">
                  <span>{{ $t('orders.farmer') }}: <strong class="text-[#1E2328] dark:text-[#F0F6FC]">{{ order.farmer?.name || 'Dawit Bekele' }}</strong></span>
                  <span class="text-gray-300 dark:text-gray-600">•</span>
                  <span>{{ $t('Region') }}: <strong class="text-[#1E2328] dark:text-[#F0F6FC]">{{ order.listing?.region || 'Oromia' }}</strong></span>
                  <span class="text-gray-300 dark:text-gray-600 hidden sm:inline">•</span>
                  <span class="font-mono text-[10px] hidden sm:inline">{{ order.escrowReference }}</span>
                </div>
              </div>
            </div>

            <!-- Middle / Right: Escrow + Price + Actions -->
            <div class="flex items-center justify-between lg:justify-end gap-3 shrink-0 pt-2 lg:pt-0 border-t lg:border-t-0 border-gray-100 dark:border-[#30363D]">
              <!-- Escrow Tag -->
              <div class="flex items-center gap-1.5 text-[#0F5C2A] dark:text-emerald-300 bg-[#EDFAF2] dark:bg-emerald-950/40 px-2 py-0.5 rounded-md border border-[#C3EFCF] dark:border-emerald-800/60 text-[11px] font-bold">
                <ShieldCheck class="w-3.5 h-3.5 text-[#1E9444] dark:text-emerald-400" />
                <span>Escrow Secured</span>
              </div>

              <!-- Price -->
              <div class="text-right min-w-[85px]">
                <span class="text-sm sm:text-base font-black text-[#0B57D0] dark:text-blue-400 tracking-tight block">
                  {{ formatETB(order.totalAmountETB || 0) }}
                </span>
              </div>

              <!-- Quick Actions based on order status -->
              <!-- Enter PIN for Driver handoff -->
              <button v-if="order.status === 'in_transit' || order.status === 'dispatched'" 
                @click="openDeliveryModal(order)" 
                class="px-3 py-1.5 bg-[#E69500] text-white rounded-xl text-xs font-bold hover:bg-[#D48900] transition-colors shadow-2xs flex items-center gap-1 shrink-0 cursor-pointer">
                <Key class="w-3.5 h-3.5" />
                <span>Enter PIN</span>
              </button>

              <!-- Dispute Escrow Button for Paid/In-Transit/Delivered Orders -->
              <button v-if="['paid_in_escrow', 'in_transit', 'dispatched', 'delivered', 'completed', 'inspection_rejected'].includes(order.status)"
                @click="openDisputeModal(order)"
                class="px-2.5 py-1.5 border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/50 rounded-xl text-xs font-bold transition-colors shadow-2xs flex items-center gap-1 shrink-0 cursor-pointer"
                title="Report Quality/Delivery Issue">
                <AlertTriangle class="w-3.5 h-3.5" />
                <span>Dispute Escrow</span>
              <div v-else-if="['pending_payment', 'awaiting_buyer_payment', 'accepted', 'placed', 'pending_farmer_approval'].includes(order.status)" class="flex items-center gap-1.5 shrink-0">
                <!-- Delete Action -->
                <button @click="handleDeleteOrder(order)" class="p-2 text-rose-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/40 rounded-xl transition-colors cursor-pointer" title="Cancel/Delete Order">
                  <Trash2 class="w-4 h-4" />
                </button>

                <button @click="verifyPayment(order)" 
                  class="px-2.5 py-1.5 bg-white dark:bg-[#161B22] text-[#0B57D0] dark:text-blue-400 border border-[#0B57D0] dark:border-blue-400 rounded-xl text-xs font-bold hover:bg-blue-50 transition-colors shadow-2xs flex items-center gap-1 cursor-pointer">
                  <RefreshCw v-if="isVerifyingPayment === (order.displayId || order.id)" class="w-3.5 h-3.5 animate-spin" />
                  <span v-else>Verify</span>
                </button>

                <button @click="handlePayment(order)" 
                  :disabled="order.status !== 'accepted'"
                  :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition-colors shadow-2xs flex items-center gap-1 cursor-pointer',
                    order.status !== 'accepted' ? 'bg-gray-100 dark:bg-[#21262D] text-[#5A6270] dark:text-[#8B949E] border border-[#E2E4E7] dark:border-[#30363D] cursor-not-allowed opacity-70' : 'bg-[#0B57D0] text-white hover:bg-[#09429E]'
                  ]">
                  <CreditCard class="w-3.5 h-3.5" />
                  <span>{{ isProcessingPayment === (order.displayId || order.id) ? '...' : (order.status !== 'accepted' ? 'Awaiting Farmer' : 'Pay Chapa') }}</span>
                </button>
              </div>

              <!-- Toggle Lifecycle Drawer -->
              <button @click="toggleLifecycleDrawer(order.id)" 
                class="p-1.5 rounded-lg bg-gray-100 dark:bg-[#21262D] hover:bg-gray-200 dark:hover:bg-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] transition-colors cursor-pointer"
                :title="expandedLifecycleOrders[order.id] ? 'Hide Progress' : 'View Order Lifecycle'">
                <ChevronDown :class="['w-4 h-4 transition-transform duration-200', expandedLifecycleOrders[order.id] ? 'rotate-180' : '']" />
              </button>
            </div>
          </div>

          <!-- Admin Fraud / Resolution Verdict Banner for Buyer -->
          <div v-if="order.dispute" class="mt-3 p-3.5 rounded-xl bg-rose-50/60 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 space-y-1.5 animate-in fade-in duration-200">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-1.5 font-extrabold text-xs text-rose-800 dark:text-rose-300">
                <ShieldAlert class="w-4 h-4 text-rose-600 dark:text-rose-400" />
                <span>Admin Dispute Inspection & Verdict</span>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-rose-100 dark:bg-rose-900/60 text-rose-800 dark:text-rose-200">
                Status: {{ order.dispute.status }}
              </span>
            </div>

            <div v-if="order.dispute.resolutionNotes" class="p-2.5 bg-white dark:bg-[#161B22] rounded-lg border border-rose-100 dark:border-rose-900/30 text-xs space-y-0.5">
              <span class="block font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-wider text-[9px]">Official Admin Findings & Resolution Notes</span>
              <p class="font-medium text-[#1E2328] dark:text-[#F0F6FC]">{{ order.dispute.resolutionNotes }}</p>
            </div>
          </div>

          <!-- Collapsible Order Lifecycle Drawer -->
          <div v-if="expandedLifecycleOrders[order.id]" class="mt-3 pt-3 border-t border-gray-100 dark:border-[#30363D] space-y-2 animate-in fade-in duration-200">
            <OrderTimeline :status="order.status" />
            <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-[#5A6270] dark:text-[#8B949E] pt-1">
              <span>{{ $t('Chapa Escrow Ref') }}: <strong class="font-mono text-[#1E2328] dark:text-[#F0F6FC]">{{ order.escrowReference }}</strong></span>
              <span v-if="order.status === 'delivered' || order.status === 'completed'" class="text-emerald-700 dark:text-emerald-400 font-bold flex items-center gap-1">
                <CheckCircle2 class="w-3.5 h-3.5" /> Delivery verified & funds released to farmer.
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination Controls -->
      <Pagination 
        :currentPage="currentPage" 
        :totalPages="totalPages" 
        :totalItems="filteredOrders.length" 
        :itemsPerPage="itemsPerPage" 
        @update:currentPage="currentPage = $event" 
      />
    </div>

    <!-- Delivery Confirmation & PIN Modal -->
    <div v-if="selectedOrderForPIN" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="max-w-md w-full bg-white dark:bg-[#161B22] border dark:border-[#30363D] rounded-2xl p-6 shadow-2xl space-y-4 text-[#1E2328] dark:text-[#F0F6FC]">
        <div class="flex items-center justify-between border-b dark:border-[#30363D] pb-3">
          <div class="flex items-center gap-2">
            <ShieldCheck class="w-5 h-5 text-[#1E9444] dark:text-emerald-400" />
            <h3 class="text-base font-bold">{{ $t('orders.confirmDelivery') }}</h3>
          </div>
          <button @click="selectedOrderForPIN = null" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-2 text-xs">
          <p class="text-[#5A6270] dark:text-[#8B949E]">
            {{ $t('orders.handoffInstruction') }}
            <span class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ $t(selectedOrderForPIN.listing?.cropName) }}</span>.
          </p>
          <div class="p-3 bg-[#F8F9FA] dark:bg-[#21262D] rounded-xl space-y-1">
            <div class="flex justify-between font-semibold">
              <span>{{ $t('orders.orderId') }}:</span>
              <span class="font-bold">#{{ selectedOrderForPIN?.displayId || selectedOrderForPIN?.id }}</span>
            </div>
            <div class="flex justify-between font-semibold">
              <span>{{ $t('Escrow Release Payout') }}:</span>
              <span class="text-[#0B57D0] font-black">{{ formatETB(selectedOrderForPIN.totalAmountETB) }}</span>
            </div>
          </div>
        </div>

        <div class="space-y-1">
          <label class="text-xs font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ $t('orders.deliveryPin') }}</label>
          <input type="text" v-model="deliveryPin" maxlength="6" placeholder="e.g. 8921" 
            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#0D1117] border border-gray-300 dark:border-[#30363D] rounded-xl text-center text-lg font-black tracking-widest focus:outline-none focus:border-[#0B57D0] dark:text-[#F0F6FC]" />
        </div>

        <div class="flex gap-2 pt-2">
          <button @click="selectedOrderForPIN = null" class="flex-1 py-2.5 border border-gray-300 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] rounded-xl font-bold text-xs hover:bg-gray-50 dark:hover:bg-[#21262D]">
            {{ $t('Cancel') }}
          </button>
          <button @click="submitDeliveryPin" class="flex-1 py-2.5 bg-[#1E9444] text-white rounded-xl font-bold text-xs hover:bg-[#0F5C2A] shadow-2xs">
            Confirm & Release Payout
          </button>
        </div>
      </div>
    </div>

    <!-- FILE DISPUTE / ESCROW EXCEPTION MODAL -->
    <div v-if="selectedOrderForDispute" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="max-w-md w-full bg-white dark:bg-[#161B22] border border-gray-100 dark:border-[#30363D] rounded-3xl p-6 shadow-2xl space-y-4 text-[#1E2328] dark:text-[#F0F6FC]">
        <div class="flex items-center justify-between border-b dark:border-[#30363D] pb-3">
          <div class="flex items-center gap-2">
            <div class="p-2 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl border border-rose-100 dark:border-rose-800/40">
              <ShieldAlert class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-black text-[#1E2328] dark:text-[#F0F6FC]">Report Issue / Dispute Escrow</h3>
              <p class="text-[11px] text-[#5A6270] dark:text-[#8B949E]">Order #{{ selectedOrderForDispute.displayId || selectedOrderForDispute.id }}</p>
            </div>
          </div>
          <button @click="selectedOrderForDispute = null" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div class="space-y-1">
            <label class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">Claim Category</label>
            <select v-model="disputeType" class="w-full px-3 py-2 bg-gray-50 dark:bg-[#0D1117] border border-gray-200 dark:border-[#30363D] rounded-xl font-bold dark:text-[#F0F6FC]">
              <option value="quality_mismatch">Produce Quality Mismatch / Damaged Batch</option>
              <option value="delivery_delay">Major Delivery Delay / Non-Arrival</option>
              <option value="wrong_quantity">Quantity Shortfall / Weight Deficit</option>
              <option value="dispute">General Financial Dispute</option>
              <option value="other">Other Transport Exception</option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">Incident Description & Audit Evidence</label>
            <textarea v-model="disputeDescription" rows="4" placeholder="Provide detailed explanation of the produce condition, photos, or delivery failure..."
              class="w-full p-3 bg-gray-50 dark:bg-[#0D1117] border border-gray-200 dark:border-[#30363D] rounded-xl text-xs font-medium focus:outline-none focus:border-rose-500 dark:text-[#F0F6FC]"></textarea>
          </div>
        </div>

        <div class="flex gap-2 pt-2 border-t border-gray-100 dark:border-[#30363D]">
          <button @click="selectedOrderForDispute = null" class="flex-1 py-2.5 border border-gray-200 dark:border-[#30363D] text-[#1E2328] dark:text-[#F0F6FC] rounded-xl font-bold text-xs hover:bg-gray-50 dark:hover:bg-[#21262D]">
            Cancel
          </button>
          <button @click="submitDispute" :disabled="isSubmittingDispute" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold text-xs transition-colors shadow-sm flex items-center justify-center gap-1.5 disabled:opacity-50 cursor-pointer">
            <Loader2 v-if="isSubmittingDispute" class="w-4 h-4 animate-spin" />
            <span>Submit Dispute Claim</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Store, ShieldCheck, CheckCircle2, Package, Truck, Key, Search, ChevronDown, X, CreditCard, Clock, RefreshCw, ShieldAlert, AlertTriangle, Loader2, Trash2 } from 'lucide-vue-next'
import { useOrders } from '@/composables/useOrders'
import { useAlertModal } from '@/composables/useAlertModal'
import { formatETB } from '@/utils/helpers'
import { api } from '@/services/api'
import OrderTimeline from '@/components/shared/OrderTimeline.vue'
import Pagination from '@/components/common/Pagination.vue'

const { orders, confirmDelivery, refreshOrders, cancelOrder } = useOrders()
const { showAlert } = useAlertModal()

const activeTab = ref('all')
const searchQuery = ref('')
const selectedOrderForPIN = ref(null)
const deliveryPin = ref('')
const currentPage = ref(1)
const itemsPerPage = 6

const expandedLifecycleOrders = ref({})

const toggleLifecycleDrawer = (orderId) => {
  expandedLifecycleOrders.value[orderId] = !expandedLifecycleOrders.value[orderId]
}

const filterTabs = [
  { label: 'All Orders', value: 'all' },
  { label: 'Active Shipments', value: 'active' },
  { label: 'Completed Deliveries', value: 'completed' },
]

const activeShipmentsCount = computed(() => {
  return orders.value.filter(o => ['placed', 'confirmed', 'dispatched', 'in_transit'].includes(o.status)).length
})

const completedOrdersCount = computed(() => {
  return orders.value.filter(o => ['delivered', 'completed'].includes(o.status)).length
})

const totalEscrowLockedETB = computed(() => {
  return orders.value.reduce((acc, o) => acc + (o.totalAmountETB || 0), 0)
})

const getTabCount = (tab) => {
  if (tab === 'active') return activeShipmentsCount.value
  if (tab === 'completed') return completedOrdersCount.value
  return orders.value.length
}

const filteredOrders = computed(() => {
  let result = orders.value

  if (activeTab.value === 'active') {
    result = result.filter(o => ['placed', 'confirmed', 'dispatched', 'in_transit'].includes(o.status))
  } else if (activeTab.value === 'completed') {
    result = result.filter(o => ['delivered', 'completed'].includes(o.status))
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(o => 
      String(o.id || '').toLowerCase().includes(q) ||
      (o.listing?.cropName || '').toLowerCase().includes(q) ||
      (o.farmer?.name || '').toLowerCase().includes(q) ||
      (o.escrowReference || '').toLowerCase().includes(q)
    )
  }

  return result
})

const totalPages = computed(() => Math.ceil(filteredOrders.value.length / itemsPerPage) || 1)

const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredOrders.value.slice(start, start + itemsPerPage)
})

watch([activeTab, searchQuery], () => {
  currentPage.value = 1
})

const statusBadgeClass = (status) => {
  const map = {
    placed: 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800/60',
    pending_payment: 'bg-yellow-50 dark:bg-yellow-950/40 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800/60',
    pending_farmer_approval: 'bg-yellow-50 dark:bg-yellow-950/40 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800/60',
    awaiting_buyer_payment: 'bg-orange-50 dark:bg-orange-950/40 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-800/60',
    paid_in_escrow: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60',
    confirmed: 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/60',
    dispatched: 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60',
    in_transit: 'bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200 border-amber-300 dark:border-amber-700/60',
    delivered: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60',
    completed: 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-200 border-emerald-300 dark:border-emerald-700/60',
    disputed: 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800/60',
  }
  return map[status] || 'bg-gray-100 dark:bg-[#21262D] text-gray-700 dark:text-gray-300 border-gray-200 dark:border-[#30363D]'
}

const formatStatusLabel = (status) => {
  const map = {
    delivered: 'Completed Handoff',
    completed: 'Completed Handoff',
    paid_in_escrow: 'Paid in Escrow',
    awaiting_buyer_payment: 'Awaiting Payment',
    pending_payment: 'Pending Payment',
    accepted: 'Accepted. Awaiting Pay.',
    in_transit: 'In Transit',
    dispatched: 'Dispatched',
    placed: 'Awaiting Farmer',
  }
  return map[status] || (status || 'placed').replace(/_/g, ' ')
}

const openDeliveryModal = (order) => {
  selectedOrderForPIN.value = order
  deliveryPin.value = ''
}

const submitDeliveryPin = async () => {
  if (selectedOrderForPIN.value) {
    const targetOrder = selectedOrderForPIN.value
    await confirmDelivery(targetOrder.id, deliveryPin.value)
    if (typeof targetOrder === 'object') {
      targetOrder.status = 'completed'
      targetOrder.escrowStatus = 'released'
    }
    selectedOrderForPIN.value = null
    deliveryPin.value = ''
    await refreshOrders()
  }
}

const isProcessingPayment = ref(null)
const isVerifyingPayment = ref(null)

const verifyPayment = async (order) => {
  const targetId = typeof order === 'object' ? (order.displayId || order.id) : order
  if (isVerifyingPayment.value) return
  isVerifyingPayment.value = targetId
  
  try {
    const res = await api.verifyPendingPaymentForOrder(targetId)
    if (res && res.message) {
      showAlert({ title: 'Payment Verification', message: res.message, type: 'success' })
    }
    if (typeof order === 'object') {
      order.status = 'paid_in_escrow'
      order.escrowStatus = 'held'
    }
    await refreshOrders()
  } catch (err) {
    showAlert({ 
      title: 'Payment Verification Status', 
      message: err.message || 'Payment is not yet verified. Please complete payment in the Chapa tester and try again.', 
      type: 'warning' 
    })
  } finally {
    isVerifyingPayment.value = null
  }
}

const handlePayment = async (order) => {
  const targetId = typeof order === 'object' ? (order.displayId || order.id) : order
  if (isProcessingPayment.value) return
  isProcessingPayment.value = targetId
  
  try {
    const res = await api.initiateOrderPayment(targetId)
    if (res && res.checkout_url) {
      window.open(res.checkout_url, '_blank')
    }
  } catch (err) {
    showAlert({ 
      title: 'Payment Initiation Error', 
      message: err.message || 'Payment initiation failed. Please try again.', 
      type: 'error' 
    })
  } finally {
    isProcessingPayment.value = null
  }
}

const handleDeleteOrder = async (order) => {
  const targetId = order.displayId || order.id
  if (confirm(`Are you sure you want to cancel and delete order #${targetId}?`)) {
    try {
      await cancelOrder(order.id)
      await refreshOrders()
      showAlert({ title: 'Order Cancelled', message: 'The order has been removed from your manifest.', type: 'success' })
    } catch (err) {
      showAlert({ title: 'Delete Failed', message: 'Failed to cancel order.', type: 'error' })
    }
  }
}

const selectedOrderForDispute = ref(null)
const disputeType = ref('quality_mismatch')
const disputeDescription = ref('')
const isSubmittingDispute = ref(false)

const openDisputeModal = (order) => {
  selectedOrderForDispute.value = order
  disputeType.value = 'quality_mismatch'
  disputeDescription.value = ''
}

const submitDispute = async () => {
  if (!selectedOrderForDispute.value) return
  if (!disputeDescription.value.trim()) {
    showAlert({ title: 'Description Required', message: 'Please describe the produce quality or delivery issue.', type: 'warning' })
    return
  }

  isSubmittingDispute.value = true
  try {
    const targetOrder = selectedOrderForDispute.value
    const orderIdNum = Number(targetOrder.displayId || targetOrder.id)
    const paymentIdNum = targetOrder.payment_id ? Number(targetOrder.payment_id) : null

    const payload = {
      type: disputeType.value || 'quality_mismatch',
      description: disputeDescription.value
    }
    if (paymentIdNum) payload.payment_id = paymentIdNum
    if (orderIdNum) payload.order_id = orderIdNum
    
    await api.createPaymentException(payload)

    showAlert({
      title: 'Dispute Claim Logged',
      message: 'Escrow funds are now locked under Admin Arbitrage review. An administrator will inspect the claim.',
      type: 'success'
    })

    selectedOrderForDispute.value = null
    disputeDescription.value = ''
    await refreshOrders()
  } catch (err) {
    showAlert({
      title: 'Dispute Submission Error',
      message: err.message || 'Failed to submit dispute claim. Please verify payment status.',
      type: 'error'
    })
  } finally {
    isSubmittingDispute.value = false
  }
}
</script>
