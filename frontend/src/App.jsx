import React, { useState } from 'react';
import Navigation from './components/Navigation.jsx';
import AddSchools from './components/AddSchools.jsx';
import ShowSchools from './components/ShowSchools.jsx';

function App() {
  const [currentPage, setCurrentPage] = useState('show');

  const handleAddSuccess = () => {
    setCurrentPage('show');
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <Navigation currentPage={currentPage} onPageChange={setCurrentPage} />
      
      {currentPage === 'add' ? (
        <AddSchools onSuccess={handleAddSuccess} />
      ) : (
        <ShowSchools />
      )}
    </div>
  );
}

export default App;