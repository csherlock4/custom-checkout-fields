import { Switch } from '@headlessui/react'
import { 
  DocumentIcon,
  TrashIcon 
} from '@heroicons/react/24/outline'
import { PencilIcon } from '@heroicons/react/20/solid'
import { Copy } from 'lucide-react'

// Field type pill badge colors
const fieldTypeColors = {
  text: 'bg-blue-100 text-blue-600',
  textarea: 'bg-indigo-100 text-indigo-600',
  select: 'bg-emerald-100 text-emerald-600',
  email: 'bg-purple-100 text-purple-600',
  tel: 'bg-orange-100 text-orange-600'
}

const fieldTypes = [
  { value: 'text', label: 'Text Input' },
  { value: 'textarea', label: 'Textarea' },
  { value: 'select', label: 'Select Dropdown' },
  { value: 'email', label: 'Email' },
  { value: 'tel', label: 'Phone' }
]

export default function FieldTable({ fields, onEdit, onDelete, onDuplicate, onToggle }) {
  if (fields.length === 0) {
    return (
      <div className="text-center py-12">
        <DocumentIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900">No fields found</h3>
        <p className="mt-1 text-sm text-gray-500">
          Get started by creating your first custom checkout field.
        </p>
      </div>
    )
  }

  return (
    <div className="w-full overflow-x-auto max-w-full">
      <table className="min-w-full bg-white divide-y divide-gray-200 text-sm table-fixed">
        <thead className="bg-gray-50 text-xs uppercase text-gray-500 font-medium">
          <tr>
            <th className="px-4 py-3 text-left w-[40%]">Name</th>
            <th className="px-4 py-3 text-left w-[15%]">Type</th>
            <th className="px-4 py-3 text-left w-[20%]">Position</th>
            <th className="px-4 py-3 text-left w-[10%]">Status</th>
            <th className="pr-6 pl-4 py-3 !text-right w-[15%]">Actions</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-100">
          {fields.map((field) => (
            <tr key={field.id} className="hover:bg-gray-50 transition-colors">
              <td className="px-4 py-3">
                <div className="flex flex-col">
                  <span className="font-medium text-gray-900">{field.label}</span>
                  {field.placeholder && (
                    <span className="text-xs text-gray-500 mt-1">{field.placeholder}</span>
                  )}
                  {field.required && (
                    <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 mt-1 w-fit">
                      Required
                    </span>
                  )}
                </div>
              </td>
              <td className="px-4 py-3">
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                  fieldTypeColors[field.type] || 'bg-gray-100 text-gray-600'
                }`}>
                  {fieldTypes.find(t => t.value === field.type)?.label || field.type}
                </span>
              </td>
              <td className="px-4 py-3">
                <span className="text-sm text-gray-900">
                  {field.position === 'after_billing' && 'After Billing'}
                  {field.position === 'after_shipping' && 'After Shipping'}
                  {field.position === 'before_payment' && 'Before Payment'}
                </span>
              </td>
              <td className="px-4 py-3">
                <Switch
                  checked={field.enabled}
                  onChange={(enabled) => onToggle(field.id, enabled)}
                  className={`${field.enabled ? 'bg-green-500' : 'bg-gray-200'} relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2`}
                >
                  <span className="sr-only">Enable field</span>
                  <span
                    className={`${field.enabled ? 'translate-x-6' : 'translate-x-1'} inline-block h-4 w-4 transform rounded-full bg-white transition-transform`}
                  />
                </Switch>
              </td>
              <td className="pr-6 pl-4 py-3 text-right">
                <div className="flex items-center justify-end space-x-2">
                  <button
                    onClick={() => onEdit(field)}
                    className="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors"
                    title="Edit field"
                  >
                    <span className="sr-only">Edit field</span>
                    <PencilIcon className="w-4 h-4" />
                  </button>
                  <button
                    onClick={() => onDuplicate(field)}
                    className="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-md transition-colors"
                    title="Duplicate field"
                  >
                    <span className="sr-only">Duplicate field</span>
        <Copy className="w-4 h-4" />
                  </button>
                  <button
                    onClick={() => onDelete(field.id)}
                    className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors"
                    title="Delete field"
                  >
                    <span className="sr-only">Delete field</span>
                    <TrashIcon className="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}