<template>
  <div class="space-y-6 pb-6 max-w-5xl mx-auto flex flex-col">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#E2E4E7] dark:border-[#30363D] pb-4">
      <div>
        <h1 class="text-2xl font-black text-[#1E2328] dark:text-[#F0F6FC] tracking-tight">
          Earnings & Payouts 💰
        </h1>
        <p class="text-xs text-[#5A6270] dark:text-[#8B949E] mt-0.5">
          Track your marketplace settlements, pending escrow releases, and history.
        </p>
      </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <!-- Total Paid Out -->
      <div class="bg-gradient-to-br from-emerald-50 to-emerald-100/50 dark:from-emerald-950/40 dark:to-[#161B22] border border-emerald-200 dark:border-emerald-800/50 rounded-3xl p-5 shadow-2xs relative overflow-hidden">
        <div class="flex items-center justify-between relative z-10">
          <span class="text-xs font-black uppercase text-emerald-800 dark:text-emerald-400 tracking-wider">Total Released</span>
          <div class="w-8 h-8 rounded-xl bg-emerald-200/50 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-700 dark:text-emerald-300">
            <CheckCircle2 class="w-4 h-4" />
          </div>
        </div>
        <p class="text-3xl font-black text-[#1E9444] dark:text-emerald-400 mt-2 relative z-10 tracking-tight">
          {{ formatETB(summary.total_paid_out) }}
        </p>
        <p class="text-[11px] font-bold text-emerald-700 dark:text-emerald-500 mt-1 relative z-10">Successfully transferred to your account</p>
      </div>

      <!-- Pending Escrow Payouts -->
      <div class="bg-gradient-to-br from-amber-50 to-amber-100/50 dark:from-amber-950/40 dark:to-[#161B22] border border-amber-200 dark:border-amber-800/50 rounded-3xl p-5 shadow-2xs relative overflow-hidden">
        <div class="flex items-center justify-between relative z-10">
          <span class="text-xs font-black uppercase text-amber-800 dark:text-amber-400 tracking-wider">Pending Release</span>
          <div class="w-8 h-8 rounded-xl bg-amber-200/50 dark:bg-amber-900/50 flex items-center justify-center text-amber-700 dark:text-amber-300">
            <Clock class="w-4 h-4" />
          </div>
        </div>
        <p class="text-3xl font-black text-[#E69500] mt-2 relative z-10 tracking-tight">
          {{ formatETB(summary.pending_payout) }}
        </p>
        <p class="text-[11px] font-bold text-amber-700 dark:text-amber-500 mt-1 relative z-10">Awaiting PIN verification delivery</p>
      </div>
    </div>

    <!-- Payout History List -->
    <div class="bg-white dark:bg-[#161B22] border border-[#E2E4E7] dark:border-[#30363D] rounded-2xl shadow-xs overflow-hidden">
      <div class="px-5 py-4 border-b border-[#E2E4E7] dark:border-[#30363D] bg-gray-50/50 dark:bg-[#21262D]">
        <h3 class="text-sm font-black text-[#1E2328] dark:text-[#F0F6FC] flex items-center gap-2">
          <Wallet class="w-4 h-4 text-[#0B57D0] dark:text-blue-400" />
          <span>Payout History</span>
        </h3>
      </div>
      
      <!-- Loading State -->
      <div v-if="isLoading" class="p-8 text-center text-gray-500 dark:text-gray-400 flex flex-col items-center justify-center gap-2">
        <Loader2 class="w-6 h-6 animate-spin text-[#0B57D0]" />
        <span class="text-xs font-bold">Loading payouts...</span>
      </div>

      <!-- Empty State -->
      <div v-else-if="payouts.length === 0" class="p-10 text-center">
        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-3">
          <FileText class="w-5 h-5 text-gray-400" />
        </div>
        <p class="text-sm font-bold text-[#1E2328] dark:text-[#F0F6FC]">No payouts yet</p>
        <p class="text-xs text-[#5A6270] dark:text-[#8B949E] mt-1">Your payout history will appear here once orders are completed.</p>
      </div>

      <!-- Data State -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs whitespace-nowrap">
          <thead class="text-[10px] uppercase font-black tracking-wider text-[#5A6270] dark:text-[#8B949E] bg-[#F8F9FA] dark:bg-[#0D1117]">
            <tr>
              <th class="px-5 py-3 border-b border-[#E2E4E7] dark:border-[#30363D]">Date/Ref</th>
              <th class="px-5 py-3 border-b border-[#E2E4E7] dark:border-[#30363D]">Order Details</th>
              <th class="px-5 py-3 border-b border-[#E2E4E7] dark:border-[#30363D]">Status</th>
              <th class="px-5 py-3 border-b border-[#E2E4E7] dark:border-[#30363D] text-right">Amount (ETB)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#E2E4E7] dark:divide-[#30363D]">
            <tr v-for="payout in payouts" :key="payout.id" class="hover:bg-gray-50 dark:hover:bg-[#21262D] transition-colors">
              <td class="px-5 py-4">
                <div class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">{{ formatDate(payout.created_at) }}</div>
                <div class="text-[10px] font-mono text-[#5A6270] dark:text-[#8B949E] mt-0.5">{{ payout.reference }}</div>
              </td>
              <td class="px-5 py-4">
                <div class="font-bold text-[#1E2328] dark:text-[#F0F6FC]">
                  {{ payout.fulfillment?.items?.[0]?.listing?.title || 'B2B Produce Order' }}
                </div>
                <div class="text-[10px] font-bold text-[#0B57D0] dark:text-blue-400 mt-0.5">
                  Order #{{ payout.fulfillment?.order?.order_number || 'N/A' }}
                </div>
              </td>
              <td class="px-5 py-4">
                <span :class="['px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider', statusBadgeClass(payout.status)]">
                  {{ payout.status }}
                </span>
                <div v-if="payout.processed_at" class="text-[10px] font-medium text-gray-500 dark:text-gray-400 mt-1">
                  Cleared: {{ formatDate(payout.processed_at) }}
                </div>
              </td>
              <td class="px-5 py-4 text-right">
                <span class="font-black text-[#1E9444] dark:text-emerald-400 text-sm tracking-tight">
                  +{{ formatETB(payout.amount) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="px-5 py-3 border-t border-[#E2E4E7] dark:border-[#30363D] bg-gray-50 dark:bg-[#0D1117] flex items-center justify-between">
        <span class="text-xs font-bold text-[#5A6270] dark:text-[#8B949E]">
          Page {{ currentPage }} of {{ totalPages }}
        </span>
        <div class="flex gap-2">
          <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1"
            class="px-3 py-1.5 rounded-lg border border-[#E2E4E7] dark:border-[#30363D] text-xs font-extrabold text-[#1E2328] dark:text-[#F0F6FC] disabled:opacity-50 hover:bg-gray-100 dark:hover:bg-[#21262D]">
            Prev
          </button>
          <button @click="changePage(currentPage + 1)" :disabled="currentPage >= totalPages"
            class="px-3 py-1.5 rounded-lg border border-[#E2E4E7] dark:border-[#30363D] text-xs font-extrabold text-[#1E2328] dark:text-[#F0F6FC] disabled:opacity-50 hover:bg-gray-100 dark:hover:bg-[#21262D]">
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Wallet, CheckCircle2, Clock, FileText, Loader2 } from 'lucide-vue-next'
import { api } from '@/services/api'

const summary = ref({ total_paid_out: 0, pending_payout: 0 })
const payouts = ref([])
const isLoading = ref(true)
const currentPage = ref(1)
const totalPages = ref(1)

const formatETB = (val) => {
  return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric'
  })
}

const statusBadgeClass = (status) => {
  const map = {
    processed: 'bg-emerald-100/50 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
    pending: 'bg-amber-100/50 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
    failed: 'bg-rose-100/50 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
  }
  return map[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
}

const fetchData = async (page = 1) => {
  isLoading.value = true
  try {
    const summaryRes = await api.fetchPayoutSummary()
    if (summaryRes?.summary) {
      summary.value = summaryRes.summary
    }

    const res = await api.fetchPayouts(page)
    if (res?.data) {
      payouts.value = res.data
      currentPage.value = res.meta?.current_page || 1
      totalPages.value = res.meta?.last_page || 1
    }
  } catch (error) {
    console.error('Failed to load payouts:', error)
  } finally {
    isLoading.value = false
  }
}

const changePage = (newPage) => {
  if (newPage > 0 && newPage <= totalPages.value) {
    fetchData(newPage)
  }
}

onMounted(() => {
  fetchData(1)
})
</script>
