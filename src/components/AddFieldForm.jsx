export default function AddFieldForm({ newField, setNewField, onAddField, onCancel }) {
  const handleSubmit = (e) => {
    e.preventDefault()
    if (newField.label.trim()) {
      onAddField()
    }
  }

  return (
    <div className="bg-white shadow rounded-lg mb-4">
      <div className="px-4 py-5 sm:p-6">
        <div className="border-b border-gray-200 pb-4 mb-6">
          <h2 className="text-lg font-medium text-gray-900">Add New Field</h2>
          <p className="mt-1 text-sm text-gray-500">Create a custom field to collect additional customer information</p>
        </div>
        
        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label htmlFor="field-label" className="block text-sm font-medium text-gray-700 mb-2">
                Field Label *
              </label>
              <input
                id="field-label"
                type="text"
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                value={newField.label}
                onChange={(e) => setNewField({...newField, label: e.target.value})}
                placeholder="e.g., Medical ID Number"
                required
              />
              <p className="mt-1 text-sm text-gray-500">The label shown to customers on checkout</p>
            </div>

            <div>
              <label htmlFor="field-type" className="block text-sm font-medium text-gray-700 mb-2">
                Field Type
              </label>
              <select
                id="field-type"
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                value={newField.type}
                onChange={(e) => setNewField({...newField, type: e.target.value})}
              >
                <option value="text">Text Input</option>
                <option value="textarea">Textarea</option>
                <option value="select">Select Dropdown</option>
                <option value="email">Email</option>
                <option value="tel">Phone Number</option>
              </select>
            </div>

            <div>
              <label htmlFor="field-placeholder" className="block text-sm font-medium text-gray-700 mb-2">
                Placeholder Text
              </label>
              <input
                id="field-placeholder"
                type="text"
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                value={newField.placeholder}
                onChange={(e) => setNewField({...newField, placeholder: e.target.value})}
                placeholder="Optional placeholder text"
              />
              <p className="mt-1 text-sm text-gray-500">Help text shown inside the empty field</p>
            </div>

            <div>
              <label htmlFor="field-position" className="block text-sm font-medium text-gray-700 mb-2">
                Position
              </label>
              <select
                id="field-position"
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                value={newField.position}
                onChange={(e) => setNewField({...newField, position: e.target.value})}
              >
                <option value="after_billing">After Billing Details</option>
                <option value="after_shipping">After Shipping Details</option>
                <option value="before_payment">Before Payment Method</option>
              </select>
            </div>
          </div>

          <div className="border-t border-gray-200 pt-6">
            <h3 className="text-sm font-medium text-gray-900 mb-4">Field Options</h3>
            <div className="space-y-3">
              <label className="flex items-center">
                <input
                  type="checkbox"
                  className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                  checked={newField.required}
                  onChange={(e) => setNewField({...newField, required: e.target.checked})}
                />
                <span className="ml-2 text-sm text-gray-700">Required field</span>
              </label>
              <label className="flex items-center">
                <input
                  type="checkbox"
                  className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                  checked={newField.enabled}
                  onChange={(e) => setNewField({...newField, enabled: e.target.checked})}
                />
                <span className="ml-2 text-sm text-gray-700">Field enabled</span>
              </label>
            </div>
          </div>

          <div className="flex justify-end space-x-3">
            <button
              type="button"
              onClick={onCancel}
              className="bg-white border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-blue-500 inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 ease-in-out"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={!newField.label.trim()}
              className="bg-blue-600 hover:bg-blue-700 text-white border-transparent focus:ring-blue-500 inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200 ease-in-out"
            >
              Add Field
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}