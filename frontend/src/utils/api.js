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

// Cache for role settings
let roleSettingsCache = null;
let roleSettingsCacheExpiry = null;

export const loadRoleSettings = async () => {
  console.log('🔧 loadRoleSettings: Starting role settings load');

  // Check cache first
  if (roleSettingsCache && roleSettingsCacheExpiry && Date.now() < roleSettingsCacheExpiry) {
    console.log('🔧 loadRoleSettings: Returning cached role settings:', roleSettingsCache);
    return roleSettingsCache;
  }

  // Get current user role for API access decisions - ADMIN USERS ONLY ACCESS SITE SETTINGS API
  // ALL users get roles directly from user_roles table via site-settings API
  const token = localStorage.getItem('token');
  if (!token) {
    console.log('🔧 loadRoleSettings: No token found, returning null');
    return null;
  }

  try {
    const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')));
    const roleId = payload.role_id;
    const userId = payload.user_id;

    console.log('🔧 loadRoleSettings: User role_id:', roleId, 'user_id:', userId);

    // ALWAYS fetch from site-settings API for ALL authenticated users
    // The API will return merged settings (user_roles table + site settings overrides)
    console.log('🔧 loadRoleSettings: Fetching role settings from site-settings API for all authenticated users');

    try {
      const response = await apiFetch('/backend/api/site-settings.php');
      console.log('🔧 loadRoleSettings: Site settings API response received, ok:', response.ok);

      if (response.ok) {
        const data = await response.json();
        console.log('🔧 loadRoleSettings: Site settings API data:', data);

        if (data.role_settings) {
          // Cache the final role settings for 5 minutes
          roleSettingsCache = data.role_settings;
          roleSettingsCacheExpiry = Date.now() + (5 * 60 * 1000);
          console.log('🔧 loadRoleSettings: Cached roleSettings from API:', roleSettingsCache);
          console.log('🔧 loadRoleSettings: Returning final roleSettings:', roleSettingsCache);
          return roleSettingsCache;
        } else {
          console.warn('🔧 loadRoleSettings: API response missing role_settings, using emergency defaults');
        }
      } else {
        console.warn('🔧 loadRoleSettings: Site settings API response not ok:', response.status);
      }
    } catch (apiError) {
      console.warn('🔧 loadRoleSettings: Failed to fetch site settings API:', apiError);
    }

  } catch (error) {
    console.warn('🔧 loadRoleSettings: Failed to parse token:', error);
  }

  // Emergency fallback - use hardcoded defaults that match user_roles table
  console.log('🔧 loadRoleSettings: Using emergency fallback defaults');
  const emergencyDefaults = {
    1: 'Client User',
    2: 'Client Admin',
    3: 'Service Provider Admin',
    4: 'Service Provider Technician',
    5: 'System Administrator'
  };

  // Cache emergency defaults for 1 minute only (shorter cache time)
  roleSettingsCache = emergencyDefaults;
  roleSettingsCacheExpiry = Date.now() + (1 * 60 * 1000); // 1 minute cache
  console.log('🔧 loadRoleSettings: Returning emergency fallback:', emergencyDefaults);
  return emergencyDefaults;
};

// Ensure no duplicate getRoleDisplayName functions exist
export const getRoleDisplayName = async (roleId) => {
  console.log('🔧 getRoleDisplayName called with roleId:', roleId);
  const roleSettings = await loadRoleSettings();
  const result = roleSettings[roleId] || `Role ${roleId}`;
  console.log('🔧 getRoleDisplayName returning:', result, 'for roleId:', roleId);
  return result;
};

export const apiFetch = async (endpoint, options = {}) => {
  const isLoginRequest = endpoint.includes('/auth.php');

  // Check for expired token before making request (skip for login)
  if (!isLoginRequest && handleTokenExpiration()) {
    throw new Error('Token expired - redirecting to login');
  }

  const url = apiUrl(endpoint);
  const token = localStorage.getItem('token');

  // For live server compatibility, pass token as query parameter instead of Authorization header
  // Skip token for login requests
  let finalUrl = url;
  if (token && !isLoginRequest) {
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
  // Skip automatic handling for login requests - let them handle 401s as auth errors
  if (response.status === 401 && !isLoginRequest) {
    clearExpiredToken();
    // Redirect to login page
    if (window.location.pathname !== '/') {
      window.location.href = '/';
    }
    throw new Error('Authentication failed - redirecting to login');
  }

  return response;
};
