import { useState, useEffect } from 'react'
import SidebarNav from './SidebarNav'

export default function Layout({ children }) {
  const [collapsed, setCollapsed] = useState(() => {
    const saved = localStorage.getItem('ccf-sidebar-collapsed')
    return saved ? JSON.parse(saved) : false
  })

  useEffect(() => {
    localStorage.setItem('ccf-sidebar-collapsed', JSON.stringify(collapsed))
  }, [collapsed])

  const handleCollapse = () => {
    setCollapsed(!collapsed)
  }

  return (
    <div className="h-screen flex bg-gray-50 text-gray-900 font-sans overflow-hidden">
      {/* Sidebar Container */}
      <div className={`transition-all duration-300 ease-in-out ${collapsed ? 'w-16' : 'w-64'}`}>
        <SidebarNav collapsed={collapsed} onCollapse={handleCollapse} />
      </div>

      {/* Main Content Container */}
      <div className="flex-1 flex flex-col overflow-auto transition-all duration-300 ease-in-out">
        {children}
      </div>
    </div>
  )
}