import React from 'react';
import { School, Plus, List } from 'lucide-react';

const Navigation = ({ currentPage, onPageChange }) => {
  return (
    <nav className="bg-white shadow-lg sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16">
          <div className="flex items-center">
            <div className="flex items-center space-x-2">
              <School className="w-8 h-8 text-blue-600" />
              <span className="text-xl font-bold text-gray-900">SchoolHub</span>
            </div>
          </div>
          
          <div className="flex items-center space-x-4">
            <button
              onClick={() => onPageChange('show')}
              className={`flex items-center space-x-2 px-4 py-2 rounded-lg transition-all ${
                currentPage === 'show'
                  ? 'bg-blue-100 text-blue-700 font-medium'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
              }`}
            >
              <List className="w-4 h-4" />
              <span className="hidden sm:inline">View Schools</span>
            </button>
            
            <button
              onClick={() => onPageChange('add')}
              className={`flex items-center space-x-2 px-4 py-2 rounded-lg transition-all ${
                currentPage === 'add'
                  ? 'bg-blue-100 text-blue-700 font-medium'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
              }`}
            >
              <Plus className="w-4 h-4" />
              <span className="hidden sm:inline">Add School</span>
            </button>
          </div>
        </div>
      </div>
    </nav>
  );
};

export default Navigation;