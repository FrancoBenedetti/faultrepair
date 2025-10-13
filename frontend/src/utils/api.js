// API utility functions
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '';

export const apiUrl = (endpoint) => {
  // If API_BASE_URL is set, use it, otherwise assume relative URLs
  return API_BASE_URL ? `${API_BASE_URL}${endpoint}` : endpoint;
};

// Token utility functions
export const isTokenExpired = () => {
  const token = localStorage.getItem('token');
  if (!token) return true;

  try {
    const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')));
    if (payload.exp && Date.now() >= payload.exp * 1000) {
      return true;
    }
    return false;
  } catch (error) {
    // Invalid token format
    return true;
  }
};

export const clearExpiredToken = () => {
  const token = localStorage.getItem('token');
  if (token && isTokenExpired()) {
    localStorage.removeItem('token');
    return true;
  }
  return false;
};

export const handleTokenExpiration = () => {
  if (clearExpiredToken()) {
    // Redirect to login page
    if (window.location.pathname !== '/') {
      window.location.href = '/';
    }
    return true;
  }
  return false;
};

export const apiFetch = async (endpoint, options = {}) => {
  // Check for expired token before making request
  if (handleTokenExpiration()) {
    throw new Error('Token expired - redirecting to login');
  }

  const url = apiUrl(endpoint);
  const token = localStorage.getItem('token');

  // For live server compatibility, pass token as query parameter instead of Authorization header
  let finalUrl = url;
  if (token) {
    const separator = url.includes('?') ? '&' : '?';
    finalUrl = `${url}${separator}token=${encodeURIComponent(token)}`;
  }

  const config = {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...options.headers,
    },
  };

  const response = await fetch(finalUrl, config);

  // Handle 401 Unauthorized responses (token expired or invalid)
  if (response.status === 401) {
    clearExpiredToken();
    // Redirect to login page
    if (window.location.pathname !== '/') {
      window.location.href = '/';
    }
    throw new Error('Authentication failed - redirecting to login');
  }

  return response;
};
