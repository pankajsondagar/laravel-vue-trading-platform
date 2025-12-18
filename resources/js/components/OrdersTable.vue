<template>
  <div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">My Orders</h2>
      
      <!-- Filters -->
      <div class="flex space-x-2">
        <select
          v-model="filters.symbol"
          @change="applyFilters"
          class="px-3 py-1 text-sm border border-gray-300 rounded-md"
        >
          <option value="">All Symbols</option>
          <option value="BTC">BTC</option>
          <option value="ETH">ETH</option>
          <option value="SOL">SOL</option>
          <option value="XRP">XRP</option>
        </select>

        <select
          v-model="filters.side"
          @change="applyFilters"
          class="px-3 py-1 text-sm border border-gray-300 rounded-md"
        >
          <option value="">All Sides</option>
          <option value="buy">Buy</option>
          <option value="sell">Sell</option>
        </select>

        <select
          v-model="filters.status"
          @change="applyFilters"
          class="px-3 py-1 text-sm border border-gray-300 rounded-md"
        >
          <option value="">All Status</option>
          <option value="1">Open</option>
          <option value="2">Filled</option>
          <option value="3">Cancelled</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="tradingStore.loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Orders Table -->
    <div v-else-if="tradingStore.orders.length > 0" class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="border-b border-gray-200">
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Symbol</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Side</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Price</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Amount</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Total</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Status</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="order in tradingStore.orders"
            :key="order.id"
            class="border-b border-gray-100 hover:bg-gray-50 transition"
          >
            <td class="px-4 py-3 text-sm text-gray-600">
              {{ formatDate(order.created_at) }}
            </td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900">
              {{ order.symbol }}
            </td>
            <td class="px-4 py-3">
              <span
                :class="[
                  'inline-block px-2 py-1 text-xs font-semibold rounded',
                  order.side === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                ]"
              >
                {{ order.side.toUpperCase() }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-right text-gray-900">
              ${{ formatNumber(order.price) }}
            </td>
            <td class="px-4 py-3 text-sm text-right text-gray-900">
              {{ formatNumber(order.amount) }}
            </td>
            <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">
              ${{ formatNumber(order.total) }}
            </td>
            <td class="px-4 py-3 text-center">
              <span
                :class="[
                  'inline-block px-2 py-1 text-xs font-semibold rounded',
                  getStatusClass(order.status)
                ]"
              >
                {{ order.status_label }}
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <button
                v-if="order.status === 1"
                @click="handleCancel(order.id)"
                class="px-3 py-1 text-xs font-medium text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition"
              >
                Cancel
              </button>
              <span v-else class="text-xs text-gray-400">-</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
      </svg>
      <p class="mt-4 text-sm text-gray-600">No orders found</p>
    </div>
  </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue'
import { useTradingStore } from '../stores/trading'

const tradingStore = useTradingStore()

const filters = reactive({
  symbol: '',
  side: '',
  status: '',
})

onMounted(() => {
  applyFilters()
})

const applyFilters = async () => {
  const params = {}
  if (filters.symbol) params.symbol = filters.symbol
  if (filters.side) params.side = filters.side
  if (filters.status) params.status = filters.status
  
  await tradingStore.fetchOrders(params)
}

const handleCancel = async (orderId) => {
  if (confirm('Are you sure you want to cancel this order?')) {
    await tradingStore.cancelOrder(orderId)
  }
}

const getStatusClass = (status) => {
  switch (status) {
    case 1: return 'bg-blue-100 text-blue-800'
    case 2: return 'bg-green-100 text-green-800'
    case 3: return 'bg-gray-100 text-gray-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const formatNumber = (value) => {
  return parseFloat(value).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 8,
  })
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>