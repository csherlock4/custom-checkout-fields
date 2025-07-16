import { createRoot } from 'react-dom/client'
import OrderMetaBox from './OrderMetaBox.jsx'

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('ccf-order-meta-root')
  if (container) {
    // Get order ID from the container data attribute
    const orderId = container.dataset.orderId
    
    if (orderId) {
      const root = createRoot(container)
      root.render(<OrderMetaBox orderId={orderId} />)
    } else {
      container.innerHTML = '<div class="p-4 text-red-600">Error: No order ID found</div>'
    }
  }
})
