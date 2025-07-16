import { useState } from 'react'
import React from 'react'
import { Bars3Icon, CogIcon, DocumentIcon } from '@heroicons/react/24/outline'
import { Settings } from 'lucide-react'

const navigation = [
  { 
    name: 'All Fields', 
    icon: 'list',
    current: true,
    tooltip: 'Manage custom checkout fields'
  },
  { 
    name: 'Settings', 
    icon: 'cog',
    current: false,
    tooltip: 'Plugin configuration'
  }
]

const icons = {
  list: Bars3Icon,
  cog: Settings,
  menu: Bars3Icon
}

export default function Sidebar({ collapsed, onToggle }) {
  const [activeItem, setActiveItem] = useState('All Fields')

  return (
    <div className={`bg-white border-r border-gray-200 shadow-sm flex flex-col transition-all duration-300 ease-in-out ${
      collapsed ? 'w-16' : 'w-64'
    }`}>
      {/* Logo/Brand Area */}
      <div className="flex items-center justify-between p-4 border-b border-gray-200">
        {!collapsed && (
          <div className="flex items-center">
            <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
              <DocumentIcon className="w-5 h-5 text-white" />
            </div>
            <span className="ml-3 text-lg font-semibold text-gray-900">CCF</span>
          </div>
        )}
        <button
          onClick={onToggle}
          className="p-1.5 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-colors"
        >
          <icons.menu className="w-6 h-6" />
        </button>
      </div>

      {/* Navigation */}
      <nav className="flex-1 px-2 py-4 space-y-2">
        {navigation.map((item) => {
          const isActive = activeItem === item.name
          return (
            <div key={item.name} className="relative group">
              <button
                onClick={() => setActiveItem(item.name)}
                className={`w-full flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                  isActive
                    ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                }`}
              >
                <span className={`flex-shrink-0 ${collapsed ? 'mx-auto' : 'mr-3'}`}>
                  {React.createElement(icons[item.icon], { className: 'w-6 h-6' })}
                  {React.createElement(icons[item.icon], { className: 'w-6 h-6 text-gray-500 group-hover:text-blue-600 transition-colors' })}
                </span>
                {!collapsed && (
                  <span className="truncate">{item.name}</span>
                )}
              </button>

              {/* Tooltip for collapsed state */}
              {collapsed && (
                <div className="absolute left-full top-1/2 transform -translate-y-1/2 ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">
                  {item.tooltip}
                  <div className="absolute right-full top-1/2 transform -translate-y-1/2 w-0 h-0 border-t-4 border-b-4 border-r-4 border-transparent border-r-gray-900"></div>
                </div>
              )}
            </div>
          )
        })}
      </nav>

      {/* Footer/Version Info */}
      <div className="border-t border-gray-200 p-4">
        {!collapsed ? (
          <div className="text-xs text-gray-500">
            <p className="font-medium">Custom Checkout Fields</p>
            <p>Version 1.0.0</p>
          </div>
        ) : (
          <div className="flex justify-center">
            <div className="w-2 h-2 bg-green-400 rounded-full"></div>
          </div>
        )}
      </div>
    </div>
  )
}