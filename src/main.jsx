import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App'
import './index.css'

// Global flag to prevent duplicate mounting
let appMounted = false

function mountApp() {
  // Prevent mounting multiple times
  if (appMounted) {
    return
  }
  
  const rootElement = document.getElementById('ccf-admin-root')
  
  if (!rootElement) {
    return
  }
  
  // Check if element already has React content
  if (rootElement.hasChildNodes()) {
    rootElement.innerHTML = ''
  }
  
  try {
    const root = ReactDOM.createRoot(rootElement)
    root.render(
      <App />
    )
    appMounted = true
  } catch (error) {
    // Fallback to legacy render if createRoot fails
    try {
      ReactDOM.render(
        <App />,
        rootElement
      )
      appMounted = true
    } catch (legacyError) {
      // Silent failure
    }
  }
}

// Single mounting strategy
if (document.readyState === 'loading') {
  // DOM is still loading, wait for DOMContentLoaded
  document.addEventListener('DOMContentLoaded', mountApp)
} else {
  // DOM is already loaded, mount immediately
  mountApp()
}