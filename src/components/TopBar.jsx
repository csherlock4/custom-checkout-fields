import { useState, useEffect } from 'react'
import { Bars3Icon } from '@heroicons/react/24/outline'

export default function TopBar({ onToggleSidebar, sidebarCollapsed }) {
  const [scrolled, setScrolled] = useState(false)

  useEffect(() => {
    const handleScroll = () => {
      const isScrolled = window.scrollY > 10
      setScrolled(isScrolled)
    }

    window.addEventListener('scroll', handleScroll)
    return () => window.removeEventListener('scroll', handleScroll)
  }, [])

  return (
    <div className={`sticky top-0 z-40 bg-white border-b border-gray-200 transition-shadow duration-200 ${
      scrolled ? 'shadow-sm' : ''
    }`}>
      <div className="flex items-center justify-between px-6 py-4">
        {/* Left side - Brand and navigation context */}
        <div className="flex items-center space-x-4">
          <button
            onClick={onToggleSidebar}
            className="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-colors"
          >
            <Bars3Icon className="w-6 h-6" />
          </button>
          
          <div>
            <h1 className="text-xl font-semibold text-gray-900">Custom Checkout Fields</h1>
            <p className="text-sm text-gray-500">Manage your WooCommerce checkout customizations</p>
          </div>
        </div>

        {/* Right side - Future expandable area */}
        <div className="flex items-center space-x-4">
          {/* Plugin Status Indicator */}
          <div className="flex items-center space-x-2">
            <div className="w-2 h-2 bg-green-400 rounded-full"></div>
            <span className="text-sm text-gray-500 hidden sm:block">Active</span>
          </div>

          {/* Future: User menu, notifications, etc. */}
          <div className="flex items-center space-x-2 text-sm text-gray-500">
            <span className="hidden lg:block">v1.0.0</span>
          </div>
        </div>
      </div>
    </div>
  )
}