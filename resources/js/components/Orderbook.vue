<template>
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">
      Orderbook - {{ tradingStore.orderbook.symbol || 'BTC' }}
    </h2>

    <div class="space-y-4">
      <!-- Sell Orders (asks) -->
      <div>
        <h3 class="text-sm font-semibold text-gray-700 mb-2">Sell Orders</h3>
        <div class="space-y-1">
          <div v-if="tradingStore.orderbook.sell_orders.length === 0" 
               class="text-sm text-gray-500 text-center py-4">
            No sell orders
          </div>
          <div
            v-for="order in tradingStore.orderbook.sell_orders.slice(0, 10)"
            :key="order.id"
            class="flex justify-between items-center p-2 bg-red-50 rounded hover:bg-red-100 transition"
          >
            <span class="text-sm font-medium text-red-600">${{ formatNumber(order.price) }}</span>
            <span class="text-sm text-gray-600">{{ formatNumber(order.amount) }}</span>
            <span class="text-sm text-gray-500">${{ formatNumber(order.total) }}</span>
          </div>
        </div>
      </div>

      <!-- Spread Indicator -->
      <div v-if="spread !== null" class="py-3 text-center border-y border-gray-200">
        <span class="text-xs text-gray-500">Spread: </span>
        <span class="text-sm font-bold text-gray-700">${{ formatNumber(spread) }}</span>
      </div>

      <!-- Buy Orders (bids) -->
      <div>
        <h3 class="text-sm font-semibold text-gray-700 mb-2">Buy Orders</h3>
        <div class="space-y-1">
          <div v-if="tradingStore.orderbook.buy_orders.length === 0" 
               class="text-sm text-gray-500 text-center py-4">
            No buy orders
          </div>
          <div
            v-for="order in tradingStore.orderbook.buy_orders.slice(0, 10)"
            :key="order.id"
            class="flex justify-between items-center p-2 bg-green-50 rounded hover:bg-green-100 transition"
          >
            <span class="text-sm font-medium text-green-600">${{ formatNumber(order.price) }}</span>
            <span class="text-sm text-gray-600">{{ formatNumber(order.amount) }}</span>
            <span class="text-sm text-gray-500">${{ formatNumber(order.total) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div class="mt-4 pt-4 border-t border-gray-200">
      <div class="flex justify-between text-xs text-gray-500">
        <span>Price (USD)</span>
        <span>Amount</span>
        <span>Total (USD)</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useTradingStore } from '../stores/trading'

const tradingStore = useTradingStore()

const spread = computed(() => {
  const sellOrders = tradingStore.orderbook.sell_orders
  const buyOrders = tradingStore.orderbook.buy_orders
  
  if (sellOrders.length === 0 || buyOrders.length === 0) {
    return null
  }
  
  const lowestAsk = parseFloat(sellOrders[0].price)
  const highestBid = parseFloat(buyOrders[0].price)
  
  return lowestAsk - highestBid
})

const formatNumber = (value) => {
  return parseFloat(value).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 8,
  })
}
</script>