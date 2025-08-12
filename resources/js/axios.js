import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_WEBRSUD_URL,
  headers: {
    Authorization: `Bearer ${import.meta.env.VITE_WEBRSUD_TOKEN}`,
    Accept: 'application/json',
  },
});

export default api;
