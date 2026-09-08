import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost/lydo-system/backend/api',
  withCredentials: true,          // send PHP session cookie
  headers: { 'Content-Type': 'application/json' },
})

export default api
