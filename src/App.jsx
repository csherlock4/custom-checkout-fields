import { useEffect, useState } from 'react'

export default function App() {
  const [fields, setFields] = useState([])
  const [status, setStatus] = useState('')
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [showAddForm, setShowAddForm] = useState(false)
  const [newField, setNewField] = useState({
    label: '',
    type: 'text',
    required: false,
    enabled: true,
    placeholder: '',
    position: 'after_billing'
  })

  useEffect(() => {
    console.log('[CCF Admin] Loading settings...')
    wp.apiRequest({ 
      path: '/wp/v2/settings' 
    }).then((data) => {
      console.log('[CCF Admin] Settings loaded:', data)
      setFields(data.ccf_fields || [])
      setLoading(false)
    }).catch(err => {
      console.error('[CCF Admin] Error loading settings:', err)
      setError('Could not load settings. Check REST API permissions.')
      setLoading(false)
    })
  }, [])

  const saveFields = (updatedFields) => {
    setStatus('Saving...')
    console.log('[CCF Admin] Saving fields:', updatedFields)
    
    wp.apiRequest({
      path: '/wp/v2/settings',
      method: 'POST',
      data: { ccf_fields: updatedFields },
    }).then((response) => {
      console.log('[CCF Admin] Save response:', response)
      setStatus('Saved successfully!')
      setTimeout(() => setStatus(''), 3000)
    }).catch(err => {
      console.error('[CCF Admin] Error saving:', err)
      setStatus('Error saving! Check console for details.')
    })
  }

  const addField = () => {
    if (!newField.label.trim()) return
    
    const field = {
      ...newField,
      id: 'ccf_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
    }
    
    const updatedFields = [...fields, field]
    setFields(updatedFields)
    saveFields(updatedFields)
    
    // Reset form
    setNewField({
      label: '',
      type: 'text',
      required: false,
      enabled: true,
      placeholder: '',
      position: 'after_billing'
    })
    setShowAddForm(false)
  }

  const updateField = (index, updates) => {
    const updatedFields = fields.map((field, i) => 
      i === index ? { ...field, ...updates } : field
    )
    setFields(updatedFields)
    saveFields(updatedFields)
  }

  const deleteField = (index) => {
    if (!confirm('Are you sure you want to delete this field?')) return
    
    const updatedFields = fields.filter((_, i) => i !== index)
    setFields(updatedFields)
    saveFields(updatedFields)
  }

  if (loading) {
    return <div className="p-4">Loading settings...</div>
  }

  if (error) {
    return <div className="p-4 text-red-600 bg-red-50 border border-red-200 rounded">{error}</div>
  }

  return (
    <div className="p-6 max-w-6xl">
      <div className="bg-white rounded-lg shadow-sm border">
        <div className="p-6 border-b">
          <div className="flex justify-between items-center">
            <h1 className="text-2xl font-bold text-gray-900">Custom Checkout Fields</h1>
            <button
              onClick={() => setShowAddForm(!showAddForm)}
              className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium"
            >
              {showAddForm ? 'Cancel' : 'Add New Field'}
            </button>
          </div>
          
          {status && (
            <div className={`mt-4 p-3 rounded ${
              status.includes('Error') ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'
            }`}>
              {status}
            </div>
          )}
        </div>

        {showAddForm && (
          <div className="p-6 border-b bg-gray-50">
            <h3 className="text-lg font-medium mb-4">Add New Field</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Field Label *
                </label>
                <input
                  type="text"
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value={newField.label}
                  onChange={(e) => setNewField({...newField, label: e.target.value})}
                  placeholder="e.g., Medical ID Number"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Field Type
                </label>
                <select
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value={newField.type}
                  onChange={(e) => setNewField({...newField, type: e.target.value})}
                >
                  <option value="text">Text</option>
                  <option value="textarea">Textarea</option>
                  <option value="select">Select</option>
                  <option value="email">Email</option>
                  <option value="tel">Phone</option>
                </select>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Placeholder Text
                </label>
                <input
                  type="text"
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value={newField.placeholder}
                  onChange={(e) => setNewField({...newField, placeholder: e.target.value})}
                  placeholder="Enter placeholder text..."
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Position
                </label>
                <select
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value={newField.position}
                  onChange={(e) => setNewField({...newField, position: e.target.value})}
                >
                  <option value="after_billing">After Billing</option>
                  <option value="after_shipping">After Shipping</option>
                  <option value="before_payment">Before Payment</option>
                </select>
              </div>
              
              <div className="flex items-center space-x-4">
                <label className="flex items-center">
                  <input
                    type="checkbox"
                    className="mr-2"
                    checked={newField.required}
                    onChange={(e) => setNewField({...newField, required: e.target.checked})}
                  />
                  Required Field
                </label>
                
                <label className="flex items-center">
                  <input
                    type="checkbox"
                    className="mr-2"
                    checked={newField.enabled}
                    onChange={(e) => setNewField({...newField, enabled: e.target.checked})}
                  />
                  Enabled
                </label>
              </div>
            </div>
            
            <div className="mt-4">
              <button
                onClick={addField}
                disabled={!newField.label.trim()}
                className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed font-medium mr-2"
              >
                Add Field
              </button>
              <button
                onClick={() => setShowAddForm(false)}
                className="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-medium"
              >
                Cancel
              </button>
            </div>
          </div>
        )}

        <div className="p-6">
          {fields.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <p>No custom fields created yet.</p>
              <p className="text-sm">Click "Add New Field" to get started.</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full border-collapse border border-gray-200">
                <thead>
                  <tr className="bg-gray-50">
                    <th className="border border-gray-200 px-4 py-2 text-left">Label</th>
                    <th className="border border-gray-200 px-4 py-2 text-left">Type</th>
                    <th className="border border-gray-200 px-4 py-2 text-left">Position</th>
                    <th className="border border-gray-200 px-4 py-2 text-center">Required</th>
                    <th className="border border-gray-200 px-4 py-2 text-center">Enabled</th>
                    <th className="border border-gray-200 px-4 py-2 text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {fields.map((field, index) => (
                    <tr key={field.id} className="hover:bg-gray-50">
                      <td className="border border-gray-200 px-4 py-2">
                        <input
                          type="text"
                          className="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                          value={field.label}
                          onChange={(e) => updateField(index, { label: e.target.value })}
                        />
                      </td>
                      <td className="border border-gray-200 px-4 py-2">
                        <select
                          className="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                          value={field.type}
                          onChange={(e) => updateField(index, { type: e.target.value })}
                        >
                          <option value="text">Text</option>
                          <option value="textarea">Textarea</option>
                          <option value="select">Select</option>
                          <option value="email">Email</option>
                          <option value="tel">Phone</option>
                        </select>
                      </td>
                      <td className="border border-gray-200 px-4 py-2">
                        <select
                          className="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                          value={field.position}
                          onChange={(e) => updateField(index, { position: e.target.value })}
                        >
                          <option value="after_billing">After Billing</option>
                          <option value="after_shipping">After Shipping</option>
                          <option value="before_payment">Before Payment</option>
                        </select>
                      </td>
                      <td className="border border-gray-200 px-4 py-2 text-center">
                        <input
                          type="checkbox"
                          checked={field.required}
                          onChange={(e) => updateField(index, { required: e.target.checked })}
                        />
                      </td>
                      <td className="border border-gray-200 px-4 py-2 text-center">
                        <input
                          type="checkbox"
                          checked={field.enabled}
                          onChange={(e) => updateField(index, { enabled: e.target.checked })}
                        />
                      </td>
                      <td className="border border-gray-200 px-4 py-2 text-center">
                        <button
                          onClick={() => deleteField(index)}
                          className="px-2 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700"
                        >
                          Delete
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}