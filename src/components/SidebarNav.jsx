import { useState } from 'react'

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
  list: (
    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 10h16M4 14h16M4 18h16" />
    </svg>
  ),
  cog: (
    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
  ),
  menu: (
    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
    </svg>
  )
}

export default function SidebarNav({ collapsed, onCollapse }) {
  const [activeItem, setActiveItem] = useState('All Fields')

  return (
    <aside className="h-screen bg-white border-r border-gray-200 shadow-sm flex flex-col transition-all duration-300 ease-in-out">
      {/* Header with collapse button */}
      <div className="h-16 flex items-center justify-between border-b px-4">
        {!collapsed && (
          <div className="flex items-center">
            <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
              <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <span className="ml-3 text-lg font-semibold text-gray-900">CCF</span>
          </div>
        )}
        <button
          onClick={onCollapse}
          className="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-colors"
        >
          {icons.menu}
        </button>
      </div>

      {/* Navigation items */}
      <nav className={`p-2 space-y-2 text-sm flex-grow ${collapsed ? 'items-center' : ''}`}>
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
                  {icons[item.icon]}
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

      {/* Footer */}
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
    </aside>
  )
}