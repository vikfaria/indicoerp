import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Create() {
  const { t } = useTranslation();
  const { data, setData, post, processing } = useForm({
    name: '',
    color: '#1890ff'
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post('/api/ticket-categories');
  };

  return (
    <>
      <Head title="Create Category" />
      <div className="p-6">
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-semibold mb-4">Create Ticket Category</h2>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium mb-2">Name</label>
              <input
                type="text"
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
                placeholder="Enter category name"
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
            </div>

            <div>
              <label className="block text-sm font-medium mb-2">Color</label>
              <input
                type="color"
                value={data.color}
                onChange={(e) => setData('color', e.target.value)}
                className="w-20 h-10 border border-gray-300 rounded-md"
              />
            </div>

            <div>
              <button
                type="submit"
                disabled={processing}
                className="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 disabled:opacity-50"
              >
                {processing ? 'Creating...' : t('Create Category')}
              </button>
            </div>
          </form>
        </div>
      </div>
    </>
  );
}