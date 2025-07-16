import { useState } from 'react'
import Toolbar from './Toolbar'
import FieldTable from './FieldTable'
import AddFieldForm from './AddFieldForm'

export default function FieldListApp({
  fields,
  loading,
  error,
  onUpdateField,
  onDeleteField,
  onBulkDelete,
  onAddField,
  newField,
  setNewField,
  showAddForm,
  setShowAddForm,
  status,
  getStatusColor
}) {
  const [search, setSearch] = useState('')

  const handleAdd = () => {
    setShowAddForm(true)
  }

  const handleEdit = (field) => {
    // TODO: Implement edit field functionality
  }

  const handleDelete = (fieldId) => {
    onDeleteField(fieldId)
  }

  const handleDuplicate = (field) => {
    // TODO: Implement duplicate field functionality
  }

  const handleToggle = (fieldId, enabled) => {
    onUpdateField(fieldId, { enabled })
  }

  // Filter fields based on search
  const filteredFields = fields.filter(field => 
    field.label?.toLowerCase().includes(search.toLowerCase()) ||
    field.type?.toLowerCase().includes(search.toLowerCase())
  )

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span className="ml-2 text-sm text-gray-500">Loading fields...</span>
      </div>
    )
  }

  if (error) {
    return (
      <div className="p-4 text-red-600 bg-red-50 border border-red-200 rounded">
        {error}
      </div>
    )
  }

  return (
    <div className="w-full px-4 py-1">
      {/* Status Messages */}
      {status && (
        <div className={`rounded-md p-4 mb-4 ${getStatusColor(status)}`}>
          <div className="flex items-center">
            {status.includes('Saving') ? (
              <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-current mr-3"></div>
            ) : status.includes('Error') || status.includes('failed') ? (
              <svg className="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            ) : (
              <svg className="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
              </svg>
            )}
            <p className="text-sm font-medium">{status}</p>
          </div>
        </div>
      )}

      {/* Add Field Form */}
      {showAddForm && (
        <AddFieldForm
          newField={newField}
          setNewField={setNewField}
          onAddField={onAddField}
          onCancel={() => setShowAddForm(false)}
        />
      )}

      <div className="mb-2 border-b border-gray-200 pb-2">
        <Toolbar 
          search={search} 
          onSearch={setSearch} 
          onAdd={handleAdd} 
        />
      </div>

      <FieldTable
        fields={filteredFields}
        onEdit={handleEdit}
        onDelete={handleDelete}
        onDuplicate={handleDuplicate}
        onToggle={handleToggle}
      />
    </div>
  )
}