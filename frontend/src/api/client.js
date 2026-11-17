import axios from 'axios';

// URL de base de l'API PHP. Modifiable via une variable d'environnement Vite.
const BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const client = axios.create({
  baseURL: BASE_URL,
  headers: { 'Content-Type': 'application/json' },
});

const pendingGetRequests = new Map();

export function getShared(url, config = {}) {
  const params = Object.entries(config.params || {}).sort(([a], [b]) => a.localeCompare(b));
  const token = localStorage.getItem('tt_token');
  const key = JSON.stringify([url, params, config.responseType || 'json', token]);
  const pendingRequest = pendingGetRequests.get(key);
  if (pendingRequest) return pendingRequest;

  const request = client.get(url, config).finally(() => {
    if (pendingGetRequests.get(key) === request) {
      pendingGetRequests.delete(key);
    }
  });
  pendingGetRequests.set(key, request);
  return request;
}

// Injecte automatiquement le jeton JWT dans chaque requête
client.interceptors.request.use((config) => {
  const token = localStorage.getItem('tt_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Déconnecte l'utilisateur si le jeton est invalide/expiré
client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && localStorage.getItem('tt_token')) {
      localStorage.removeItem('tt_token');
      localStorage.removeItem('tt_user');
      if (!window.location.pathname.startsWith('/login')) {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

// Petit utilitaire pour extraire le message d'erreur de l'API
export function getErrorMessage(error, fallback = 'Une erreur est survenue.') {
  return error?.response?.data?.message || fallback;
}

export default client;
