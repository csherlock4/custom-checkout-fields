import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App'
import './index.css'

// Wait for DOM to be ready and check if element exists
document.addEventListener('DOMContentLoaded', function() {
  const rootElement = document.getElementById('ccf-admin-root')
  
  if (!rootElement) {
    console.error('[CCF] Root element #ccf-admin-root not found')
    return
  }
  
  try {
    const root = ReactDOM.createRoot(rootElement)
    root.render(
      <React.StrictMode>
        <App />
      </React.StrictMode>
    )
    console.log('[CCF] React app mounted successfully')
  } catch (error) {
    console.error('[CCF] Error mounting React app:', error)
    // Fallback to legacy render if createRoot fails
    try {
      ReactDOM.render(
        <React.StrictMode>
          <App />
        </React.StrictMode>,
        rootElement
      )
      console.log('[CCF] React app mounted with legacy render')
    } catch (legacyError) {
      console.error('[CCF] Legacy render also failed:', legacyError)
    }
  }
})

// Also try immediate execution in case DOMContentLoaded already fired
if (document.readyState === 'loading') {
  // DOM is still loading, wait for DOMContentLoaded
} else {
  // DOM is already loaded
  const rootElement = document.getElementById('ccf-admin-root')
  if (rootElement) {
    try {
      const root = ReactDOM.createRoot(rootElement)
      root.render(
        <React.StrictMode>
          <App />
        </React.StrictMode>
      )
      console.log('[CCF] React app mounted immediately')
    } catch (error) {
      console.error('[CCF] Immediate mount failed:', error)
    }
  }
}