import { useEffect, useState } from 'react'
import Layout from './components/Layout'
import AppRouter from './components/AppRouter'

// Status colors following design system
const statusColors = {
  success: 'bg-green-100 text-green-800',
  error: 'bg-red-100 text-red-800',
  warning: 'bg-yellow-100 text-yellow-800',
  info: 'bg-blue-100 text-blue-800',
  inactive: 'bg-gray-100 text-gray-800'
}

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
    // Check if wp.apiRequest is available
    if (typeof wp === 'undefined' || typeof wp.apiRequest === 'undefined') {
      setError('WordPress API not available. Please refresh the page.')
      setLoading(false)
      return
    }
    
    // Add timeout to prevent infinite loading
    const timeoutId = setTimeout(() => {
      setError('Request timed out. Please refresh the page.')
      setLoading(false)
    }, 10000) // 10 second timeout
    
    wp.apiRequest({ 
      path: '/wp/v2/settings' 
    }).then((data) => {
      clearTimeout(timeoutId)
      setFields(data.ccf_fields || [])
      setLoading(false)
    }).catch(err => {
      clearTimeout(timeoutId)
      setError('Could not load settings. Check REST API permissions.')
      setLoading(false)
    })
  }, [])

  const saveFields = (updatedFields) => {
    setStatus('Saving...')
    
    wp.apiRequest({
      path: '/wp/v2/settings',
      method: 'POST',
      data: { ccf_fields: updatedFields },
    }).then((response) => {
      // Verify the save was successful by checking the response
      if (JSON.stringify(response.ccf_fields) === JSON.stringify(updatedFields)) {
        setStatus('Saved successfully!')
        // Update local state to match server response to ensure consistency
        setFields(response.ccf_fields || [])
        setTimeout(() => setStatus(''), 3000)
      } else {
        // Try the alternative save method as fallback
        testDirectSave(updatedFields, true) // true indicates this is a fallback save
      }
    }).catch(err => {
      // Try the alternative save method as fallback
      testDirectSave(updatedFields, true) // true indicates this is a fallback save
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

  const deleteField = (fieldId) => {
    if (!confirm('Are you sure you want to delete this field?')) return
    
    const updatedFields = fields.filter(field => field.id !== fieldId)
    setFields(updatedFields)
    saveFields(updatedFields)
  }

  const updateFieldById = (fieldId, updates) => {
    const updatedFields = fields.map(field => 
      field.id === fieldId ? { ...field, ...updates } : field
    )
    setFields(updatedFields)
    saveFields(updatedFields)
  }

  const bulkDeleteFields = (fieldIds) => {
    const updatedFields = fields.filter(field => !fieldIds.includes(field.id))
    setFields(updatedFields)
    saveFields(updatedFields)
  }

  const getStatusColor = (status) => {
    if (status.includes('Error') || status.includes('failed')) return statusColors.error
    if (status.includes('Saving')) return statusColors.info
    return statusColors.success
  }

  const testDirectSave = (updatedFields, isFallback = false) => {
    wp.apiRequest({
      path: '/ccf/v1/test-save',
      method: 'POST',
      data: { fields: updatedFields },
    }).then((response) => {
      if (isFallback) {
        // This is being used as a fallback - check if it worked
        if (JSON.stringify(response.stored) === JSON.stringify(updatedFields)) {
          setStatus('Saved successfully!')
          setFields(response.stored || [])
          setTimeout(() => setStatus(''), 3000)
        } else {
          setStatus('Save failed! Please try again.')
        }
      }
    }).catch(err => {
      if (isFallback) {
        setStatus('Save failed! Please try again.')
      }
    })
  }

  if (loading) {
    return <div className="p-4">Loading settings...</div>
  }

  if (error) {
    return <div className="p-4 text-red-600 bg-red-50 border border-red-200 rounded">{error}</div>
  }

  return (
    <Layout>
      <AppRouter 
        fields={fields}
        loading={loading}
        error={error}
        onUpdateField={updateFieldById}
        onDeleteField={deleteField}
        onBulkDelete={bulkDeleteFields}
        onAddField={addField}
        newField={newField}
        setNewField={setNewField}
        showAddForm={showAddForm}
        setShowAddForm={setShowAddForm}
        status={status}
        getStatusColor={getStatusColor}
      />
    </Layout>
  )
}