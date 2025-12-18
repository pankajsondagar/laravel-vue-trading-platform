import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'
import { useToast } from './toast'

export const useTradingStore = defineStore('trading', () => {
  const balance = ref('0')
  const assets = ref([])
  const orders = ref([])
  const orderbook = ref({ buy_orders: [], sell_orders: [] })
  const loading = ref(false)

  const toast = useToast()

  const fetchProfile = async () => {
    try {
      const response = await axios.get('/api/profile')
      balance.value = response.data.balance
      assets.value = response.data.assets
    } catch (error) {
      console.error('Failed to fetch profile:', error)
    }
  }

  const fetchOrders = async (filters = {}) => {
    try {
      loading.value = true
      const response = await axios.get('/api/orders', { params: filters })
      orders.value = response.data.orders
    } catch (error) {
      console.error('Failed to fetch orders:', error)
    } finally {
      loading.value = false
    }
  }

  const fetchOrderbook = async (symbol) => {
    try {
      const response = await axios.get('/api/orderbook', { params: { symbol } })
      orderbook.value = response.data
    } catch (error) {
      console.error('Failed to fetch orderbook:', error)
    }
  }

  const createOrder = async (orderData) => {
    try {
      const response = await axios.post('/api/orders', orderData)
      toast.success(response.data.message)
      
      // Refresh data
      await Promise.all([
        fetchProfile(),
        fetchOrders(),
        fetchOrderbook(orderData.symbol)
      ])
      
      return response.data
    } catch (error) {
      const message = error.response?.data?.error || 'Failed to create order'
      toast.error(message)
      throw error
    }
  }

  const cancelOrder = async (orderId) => {
    try {
      const response = await axios.post(`/api/orders/${orderId}/cancel`)
      toast.success(response.data.message)
      
      // Refresh data
      await Promise.all([
        fetchProfile(),
        fetchOrders()
      ])
      
      return response.data
    } catch (error) {
      const message = error.response?.data?.error || 'Failed to cancel order'
      toast.error(message)
      throw error
    }
  }

  const handleOrderMatched = (event) => {
    toast.success(event.message)
    
    // Update balance and assets
    balance.value = event.user.balance
    assets.value = event.user.assets
    
    // Update order status in list
    const orderIndex = orders.value.findIndex(o => 
      (o.side === event.trade.side && o.status === 1)
    )
    if (orderIndex !== -1) {
      orders.value[orderIndex].status = 2
      orders.value[orderIndex].status_label = 'Filled'
    }
  }

  const getAssetBalance = (symbol) => {
    const asset = assets.value.find(a => a.symbol === symbol)
    return asset ? asset.amount : '0'
  }

  const getAssetLockedBalance = (symbol) => {
    const asset = assets.value.find(a => a.symbol === symbol)
    return asset ? asset.locked_amount : '0'
  }

  return {
    balance,
    assets,
    orders,
    orderbook,
    loading,
    fetchProfile,
    fetchOrders,
    fetchOrderbook,
    createOrder,
    cancelOrder,
    handleOrderMatched,
    getAssetBalance,
    getAssetLockedBalance,
  }
})