// API utility functions
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '';

export const apiUrl = (endpoint) => {
  // If API_BASE_URL is set, use it, otherwise assume relative URLs
  return API_BASE_URL ? `${API_BASE_URL}${endpoint}` : endpoint;
};

export const apiFetch = async (endpoint, options = {}) => {
  const url = apiUrl(endpoint);
  const token = localStorage.getItem('token');

  const defaultHeaders = {
    'Content-Type': 'application/json',
  };

  // For live server compatibility, pass token as query parameter instead of Authorization header
  let finalUrl = url;
  if (token) {
    const separator = url.includes('?') ? '&' : '?';
    finalUrl = `${url}${separator}token=${encodeURIComponent(token)}`;
  }

  const config = {
    ...options,
    headers: {
      ...defaultHeaders,
      ...options.headers,
    },
  };

  return fetch(finalUrl, config);
};
