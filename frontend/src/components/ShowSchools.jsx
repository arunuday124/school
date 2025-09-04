import React, { useState, useEffect } from "react";
import { MapPin, Mail, Phone, Globe, School } from "lucide-react";

const ShowSchools = () => {
  const [schools, setSchools] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadSchools = async () => {
      try {
        const resp = await fetch("/school/backend/api.php/schools");
        if (!resp.ok) throw new Error("Failed to load");
        const data = await resp.json();
        setSchools(data);
      } catch (err) {
        // fallback to empty list
        setSchools([]);
      } finally {
        setLoading(false);
      }
    };

    loadSchools();
  }, []);

  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading schools...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-7xl mx-auto">
        <div className="text-center mb-12">
          <div className="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
            <School className="w-8 h-8 text-blue-600" />
          </div>
          <h1 className="text-4xl font-bold text-gray-900">Our Schools</h1>
          <p className="text-gray-600 mt-2">
            Discover educational institutions in your area
          </p>
        </div>

        {schools.length === 0 ? (
          <div className="text-center py-16">
            <School className="mx-auto h-16 w-16 text-gray-400 mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">
              No schools added yet
            </h3>
            <p className="text-gray-500">
              Start by adding your first school to see it here.
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {schools.map((school) => (
              <div
                key={school.id}
                className="group bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1"
              >
                <div className="relative h-48 bg-gray-200">
                  {(() => {
                    const placeholder =
                      "https://images.pexels.com/photos/207692/pexels-photo-207692.jpeg?auto=compress&cs=tinysrgb&w=800";
                    const imgSrc =
                      school.image && school.image.toString().trim() !== ""
                        ? school.image
                        : placeholder;
                    return (
                      <>
                        <img
                          src={imgSrc}
                          alt={school.name}
                          className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                          onError={(e) => {
                            e.target.src = placeholder;
                          }}
                        />
                        <div className="absolute inset-0 bg-black opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                      </>
                    );
                  })()}
                </div>

                <div className="p-6">
                  <h3 className="text-xl font-bold text-gray-900 mb-3 line-clamp-2">
                    {school.name}
                  </h3>

                  <div className="space-y-2 mb-4">
                    <div className="flex items-start text-gray-600">
                      <MapPin className="w-4 h-4 mt-1 mr-2 flex-shrink-0 text-gray-400" />
                      <span className="text-sm">{school.address}</span>
                    </div>

                    <div className="flex items-center text-gray-600">
                      <div className="w-4 h-4 mr-2 flex-shrink-0 bg-blue-100 rounded-full flex items-center justify-center">
                        <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                      </div>
                      <span className="text-sm font-medium">{school.city}</span>
                    </div>
                  </div>

                  <div className="space-y-2 pt-4 border-t border-gray-100">
                    {school.email && (
                      <div className="flex items-center text-gray-500 text-xs">
                        <Mail className="w-3 h-3 mr-2" />
                        <span className="truncate">{school.email}</span>
                      </div>
                    )}

                    {school.phone && (
                      <div className="flex items-center text-gray-500 text-xs">
                        <Phone className="w-3 h-3 mr-2" />
                        <span>{school.phone}</span>
                      </div>
                    )}

                    {school.website && (
                      <div className="flex items-center text-gray-500 text-xs">
                        <Globe className="w-3 h-3 mr-2" />
                        <a
                          href={school.website}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-blue-600 hover:text-blue-700 truncate"
                        >
                          Visit Website
                        </a>
                      </div>
                    )}
                  </div>

                  {school.description && (
                    <div className="mt-4 pt-4 border-t border-gray-100">
                      <p className="text-sm text-gray-600 line-clamp-2">
                        {school.description}
                      </p>
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default ShowSchools;
