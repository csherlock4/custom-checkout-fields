import { useEffect, useState } from 'react'

export default function OrderMetaBox({ orderId }) {
  const [customFields, setCustomFields] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [isEditing, setIsEditing] = useState(false)
  const [editValues, setEditValues] = useState({})
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    if (!orderId) return
    
    // Use a custom REST endpoint to get order meta data
    wp.apiRequest({
      path: `/mccf/v1/order-meta/${orderId}`,
    }).then((data) => {
      setCustomFields(data.custom_fields || [])
      
      // Initialize edit values
      const initialEditValues = {}
      data.custom_fields.forEach(field => {
        initialEditValues[field.key] = field.value
      })
      setEditValues(initialEditValues)
      
      setLoading(false)
    }).catch(err => {
      setError('Could not load order meta data. Check console for details.')
      setLoading(false)
    })
  }, [orderId])

  const saveChanges = () => {
    setSaving(true)
    
    // Check what fields have changed
    const changedFields = []
    customFields.forEach(field => {
      if (editValues[field.key] !== field.value) {
        changedFields.push({
          key: field.key,
          value: editValues[field.key]
        })
      }
    })
    
    if (changedFields.length === 0) {
      setIsEditing(false)
      setSaving(false)
      return
    }
    
    wp.apiRequest({
      path: `/mccf/v1/order-meta/${orderId}`,
      method: 'POST',
      data: {
        fields: changedFields
      }
    }).then((response) => {
      // Update local state
      setCustomFields(prev => prev.map(field => ({
        ...field,
        value: editValues[field.key] || field.value
      })))
      
      setIsEditing(false)
      setSaving(false)
      
      // Show success message
      const notice = document.createElement('div')
      notice.className = 'notice notice-success is-dismissible'
      notice.innerHTML = '<p>Custom fields updated successfully!</p>'
      const target = document.querySelector('.wrap h1') || document.querySelector('.wrap h2') || document.querySelector('.wrap')
      if (target) {
        target.after(notice)
        setTimeout(() => notice.remove(), 3000)
      }
      
    }).catch(err => {
      setSaving(false)
      
      // Show error message
      const notice = document.createElement('div')
      notice.className = 'notice notice-error is-dismissible'
      notice.innerHTML = '<p>Error updating custom fields. Please try again.</p>'
      const target = document.querySelector('.wrap h1') || document.querySelector('.wrap h2') || document.querySelector('.wrap')
      if (target) {
        target.after(notice)
        setTimeout(() => notice.remove(), 5000)
      }
    })
  }

  const cancelEdit = () => {
    // Reset edit values to original values
    const resetValues = {}
    customFields.forEach(field => {
      resetValues[field.key] = field.value
    })
    setEditValues(resetValues)
    setIsEditing(false)
  }

  const handleInputChange = (fieldKey, value) => {
    setEditValues(prev => ({
      ...prev,
      [fieldKey]: value
    }))
  }

  if (loading) {
    return (
      <div className="p-4">
        <div className="flex items-center space-x-2">
          <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
          <span>Loading custom fields...</span>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="p-4">
        <div className="bg-red-50 border border-red-200 rounded p-3 text-red-700">
          {error}
        </div>
      </div>
    )
  }

  if (customFields.length === 0) {
    return (
      <div className="p-4">
        <p className="text-gray-500 italic">No custom fields found for this order.</p>
      </div>
    )
  }

  return (
    <div className="p-4">
      <div className="mb-4 flex items-center justify-between">
        <h4 className="text-lg font-semibold">Custom Checkout Fields</h4>
        <div className="flex items-center space-x-2">
          {!isEditing ? (
            <button
              onClick={() => setIsEditing(true)}
              className="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
            >
              Edit Fields
            </button>
          ) : (
            <div className="flex items-center space-x-2">
              <button
                onClick={saveChanges}
                disabled={saving}
                className="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm disabled:opacity-50"
              >
                {saving ? 'Saving...' : 'Save Changes'}
              </button>
              <button
                onClick={cancelEdit}
                disabled={saving}
                className="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm disabled:opacity-50"
              >
                Cancel
              </button>
            </div>
          )}
        </div>
      </div>

      <div className="space-y-4">
        {customFields.map((field, index) => (
          <div key={field.key} className="border border-gray-200 rounded p-4 bg-white">
            <div className="flex items-start justify-between mb-2">
              <label className="block text-sm font-medium text-gray-700">
                {field.label}
              </label>
              <span className="text-xs text-gray-500">
                {field.type}
              </span>
            </div>
            
            {isEditing ? (
              field.type === 'textarea' ? (
                <textarea
                  value={editValues[field.key] || ''}
                  onChange={(e) => handleInputChange(field.key, e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                  rows="3"
                />
              ) : (
                <input
                  type={field.type}
                  value={editValues[field.key] || ''}
                  onChange={(e) => handleInputChange(field.key, e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              )
            ) : (
              <div className="px-3 py-2 bg-gray-50 rounded border">
                {field.value ? (
                  <span className="text-gray-900">{field.value}</span>
                ) : (
                  <span className="text-gray-500 italic">No value</span>
                )}
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  )
}
