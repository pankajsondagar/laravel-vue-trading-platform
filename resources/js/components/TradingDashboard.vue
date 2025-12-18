<template>
  <div class="space-y-6">
    <!-- Balance Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-500">USD Balance</h3>
        <p class="mt-2 text-3xl font-bold text-gray-900">${{ formatNumber(tradingStore.balance) }}</p>
      </div>

      <div v-for="asset in tradingStore.assets" :key="asset.symbol" class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-500">{{ asset.symbol }}</h3>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ formatNumber(asset.amount) }}</p>
        <p v-if="parseFloat(asset.locked_amount) > 0" class="text-xs text-gray-500 mt-1">
          Locked: {{ formatNumber(asset.locked_amount) }}
        </p>
      </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Order Form -->
      <OrderForm />

      <!-- Orderbook -->
      <Orderbook />
    </div>

    <!-- Orders Table -->
    <OrdersTable />
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useTradingStore } from '../stores/trading'
import { useAuthStore } from '../stores/auth'
import OrderForm from './OrderForm.vue'
import Orderbook from './Orderbook.vue'
import OrdersTable from './OrdersTable.vue'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

const tradingStore = useTradingStore()
const authStore = useAuthStore()

let echo = null

onMounted(async () => {
  await tradingStore.fetchProfile()
  await tradingStore.fetchOrders()

  // Setup Pusher for real-time updates
  window.Pusher = Pusher

  echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${authStore.token}`,
      },
    },
  })

  // Listen for order matched events
  echo.private(`user.${authStore.user.id}`)
    .listen('.OrderMatched', (event) => {
      tradingStore.handleOrderMatched(event)
      // Refresh orderbook if symbol matches current view
      if (event.trade.symbol) {
        tradingStore.fetchOrderbook(event.trade.symbol)
      }
    })
})

onUnmounted(() => {
  if (echo) {
    echo.disconnect()
  }
})

const formatNumber = (value) => {
  return parseFloat(value).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 8,
  })
}
</script>