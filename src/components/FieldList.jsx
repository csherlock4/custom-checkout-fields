import { useState, useMemo } from 'react'
import { Switch } from '@headlessui/react'
import { 
  MagnifyingGlassIcon, 
  DocumentIcon,
  TrashIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  ArrowsUpDownIcon
} from '@heroicons/react/24/outline'
import { PencilIcon } from '@heroicons/react/20/solid'
import { Copy, Trash } from 'lucide-react'

const fieldTypes = [
  { value: '', label: 'All Types' },
  { value: 'text', label: 'Text Input' },
  { value: 'textarea', label: 'Textarea' },
  { value: 'select', label: 'Select Dropdown' },
  { value: 'email', label: 'Email' },
  { value: 'tel', label: 'Phone' }
]

// Field type pill badge colors
const fieldTypeColors = {
  text: 'bg-blue-100 text-blue-600',
  textarea: 'bg-indigo-100 text-indigo-600',
  select: 'bg-emerald-100 text-emerald-600',
  email: 'bg-purple-100 text-purple-600',
  tel: 'bg-orange-100 text-orange-600'
}

const statusOptions = [
  { value: '', label: 'All Status' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
]

export default function FieldList({ 
  fields, 
  onUpdateField, 
  onDeleteField, 
  onBulkDelete, 
  loading 
}) {
  const [searchTerm, setSearchTerm] = useState('')
  const [typeFilter, setTypeFilter] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [selectedFields, setSelectedFields] = useState(new Set())
  const [sortConfig, setSortConfig] = useState({ key: 'label', direction: 'asc' })

  // Filtered and sorted fields
  const filteredFields = useMemo(() => {
    let result = fields.filter(field => {
      const matchesSearch = field.label.toLowerCase().includes(searchTerm.toLowerCase())
      const matchesType = !typeFilter || field.type === typeFilter
      const matchesStatus = !statusFilter || 
        (statusFilter === 'active' && field.enabled) ||
        (statusFilter === 'inactive' && !field.enabled)
      
      return matchesSearch && matchesType && matchesStatus
    })

    // Apply sorting
    result.sort((a, b) => {
      if (sortConfig.key) {
        const aValue = a[sortConfig.key]
        const bValue = b[sortConfig.key]
        
        if (aValue < bValue) return sortConfig.direction === 'asc' ? -1 : 1
        if (aValue > bValue) return sortConfig.direction === 'asc' ? 1 : -1
      }
      return 0
    })

    return result
  }, [fields, searchTerm, typeFilter, statusFilter, sortConfig])

  const handleSort = (key) => {
    setSortConfig(current => ({
      key,
      direction: current.key === key && current.direction === 'asc' ? 'desc' : 'asc'
    }))
  }

  const handleSelectField = (fieldId) => {
    const newSelected = new Set(selectedFields)
    if (newSelected.has(fieldId)) {
      newSelected.delete(fieldId)
    } else {
      newSelected.add(fieldId)
    }
    setSelectedFields(newSelected)
  }

  const handleSelectAll = (e) => {
    if (e.target.checked) {
      setSelectedFields(new Set(filteredFields.map(field => field.id)))
    } else {
      setSelectedFields(new Set())
    }
  }

  const handleBulkDelete = () => {
    if (selectedFields.size === 0) return
    
    if (confirm(`Are you sure you want to delete ${selectedFields.size} field${selectedFields.size !== 1 ? 's' : ''}?`)) {
      onBulkDelete(Array.from(selectedFields))
      setSelectedFields(new Set())
    }
  }

  const SortIcon = ({ column }) => {
    if (sortConfig.key !== column) {
      return (
        <ArrowsUpDownIcon className="w-4 h-4 text-gray-400" />
      )
    }
    
    return sortConfig.direction === 'asc' ? (
      <ChevronUpIcon className="w-4 h-4 text-blue-600" />
    ) : (
      <ChevronDownIcon className="w-4 h-4 text-blue-600" />
    )
  }

  return (
    <div style={{gap: '24px'}} className="flex flex-col">
      {/* Search and Filters - WP Admin spacing */}
      <div className="bg-white shadow rounded-lg">
        <div style={{padding: '24px'}}>
          <div className="flex flex-col sm:flex-row gap-4">
            {/* Search */}
            <div className="relative flex-1">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
              </div>
              <input
                type="text"
                placeholder="Search fields..."
                className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>

            {/* Type Filter */}
            <select
              className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              value={typeFilter}
              onChange={(e) => setTypeFilter(e.target.value)}
            >
              {fieldTypes.map(type => (
                <option key={type.value} value={type.value}>{type.label}</option>
              ))}
            </select>

            {/* Status Filter */}
            <select
              className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
            >
              {statusOptions.map(status => (
                <option key={status.value} value={status.value}>{status.label}</option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Bulk Actions Toolbar */}
      {selectedFields.size > 0 && (
        <div className="bg-blue-50 border border-blue-200 rounded-lg" style={{padding: '24px'}}>
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <span className="text-sm font-medium text-blue-900">
                {selectedFields.size} field{selectedFields.size !== 1 ? 's' : ''} selected
              </span>
              <button
                onClick={() => setSelectedFields(new Set())}
                className="text-sm text-blue-700 hover:text-blue-800"
              >
                Clear selection
              </button>
            </div>
            <button
              onClick={handleBulkDelete}
              className="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
              <Trash className="w-4 h-4 mr-2" />
              Delete Selected
            </button>
          </div>
        </div>
      )}

      {/* Fields Table */}
      <div className="bg-white shadow rounded-lg overflow-hidden">
        {loading ? (
          <div className="p-8 text-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p className="mt-2 text-sm text-gray-500">Loading fields...</p>
          </div>
        ) : filteredFields.length === 0 ? (
          <div className="text-center py-12">
            <DocumentIcon className="mx-auto h-12 w-12 text-gray-400" />
            <h3 className="mt-2 text-sm font-medium text-gray-900">No fields found</h3>
            <p className="mt-1 text-sm text-gray-500">
              {searchTerm || typeFilter || statusFilter 
                ? 'Try adjusting your search or filter criteria.'
                : 'Get started by creating your first custom checkout field.'
              }
            </p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th style={{padding: '12px'}} className="text-left">
                    <input
                      type="checkbox"
                      checked={selectedFields.size === filteredFields.length && filteredFields.length > 0}
                      onChange={handleSelectAll}
                      className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                  </th>
                  <th style={{padding: '12px'}} className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <button
                      onClick={() => handleSort('label')}
                      className="flex items-center space-x-1 hover:text-gray-700"
                    >
                      <span>Field Label</span>
                      <SortIcon column="label" />
                    </button>
                  </th>
                  <th style={{padding: '12px'}} className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <button
                      onClick={() => handleSort('type')}
                      className="flex items-center space-x-1 hover:text-gray-700"
                    >
                      <span>Type</span>
                      <SortIcon column="type" />
                    </button>
                  </th>
                  <th style={{padding: '12px'}} className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Position
                  </th>
                  <th style={{padding: '12px'}} className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th style={{padding: '12px'}} className="text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredFields.map((field, index) => (
                  <tr key={field.id} className="hover:bg-neutral-50">
                    <td style={{padding: '12px'}}>
                      <input
                        type="checkbox"
                        checked={selectedFields.has(field.id)}
                        onChange={() => handleSelectField(field.id)}
                        className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                      />
                    </td>
                    <td style={{padding: '12px'}}>
                      <input
                        type="text"
                        className="block w-full border-0 bg-transparent text-sm font-medium text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 rounded-md px-2 py-1"
                        value={field.label}
                        onChange={(e) => onUpdateField(field.id, { label: e.target.value })}
                      />
                    </td>
                    <td style={{padding: '12px'}}>
                      <div className="flex items-center space-x-2">
                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                          fieldTypeColors[field.type] || 'bg-gray-100 text-gray-600'
                        }`}>
                          {fieldTypes.find(t => t.value === field.type)?.label || field.type}
                        </span>
                        <select
                          className="block border-0 bg-transparent text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 rounded-md px-2 py-1"
                          value={field.type}
                          onChange={(e) => onUpdateField(field.id, { type: e.target.value })}
                        >
                          <option value="text">Text</option>
                          <option value="textarea">Textarea</option>
                          <option value="select">Select</option>
                          <option value="email">Email</option>
                          <option value="tel">Phone</option>
                        </select>
                      </div>
                    </td>
                    <td style={{padding: '12px'}}>
                      <select
                        className="block w-full border-0 bg-transparent text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 rounded-md px-2 py-1"
                        value={field.position}
                        onChange={(e) => onUpdateField(field.id, { position: e.target.value })}
                      >
                        <option value="after_billing">After Billing</option>
                        <option value="after_shipping">After Shipping</option>
                        <option value="before_payment">Before Payment</option>
                      </select>
                    </td>
                    <td style={{padding: '12px'}}>
                      <div className="flex items-center space-x-2">
                        <Switch
                          checked={field.enabled}
                          onChange={(enabled) => onUpdateField(field.id, { enabled })}
                          className={`${field.enabled ? 'bg-green-500' : 'bg-gray-200'} relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2`}
                        >
                          <span className="sr-only">Enable field</span>
                          <span
                            className={`${field.enabled ? 'translate-x-6' : 'translate-x-1'} inline-block h-4 w-4 transform rounded-full bg-white transition-transform`}
                          />
                        </Switch>
                        {field.required && (
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Required
                          </span>
                        )}
                      </div>
                    </td>
                    <td style={{padding: '12px'}} className="text-right">
                      <div className="flex items-center justify-end space-x-2">
                        <button
                          onClick={() => {/* TODO: implement edit */}}
                          className="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors"
                          title="Edit field"
                        >
                          <span className="sr-only">Edit field</span>
                          <PencilIcon className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => {/* TODO: implement duplicate */}}
                          className="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-md transition-colors"
                          title="Duplicate field"
                        >
                          <span className="sr-only">Duplicate field</span>
                          <Copy className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => onDeleteField(field.id)}
                          className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors"
                          title="Delete field"
                        >
                          <span className="sr-only">Delete field</span>
                          <Trash className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  )
}