import { createContext, useContext, useState, useEffect } from 'react'
import api from '../api/axios'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser]       = useState(null)
  const [loading, setLoading] = useState(true)

  // Restore session on page load
  useEffect(() => {
    api.get('/profile.php')
      .then(res => setUser(res.data.user))
      .catch(() => setUser(null))
      .finally(() => setLoading(false))
  }, [])

  const login = async (email, password) => {
    const res = await api.post('/login.php', { email, password })
    setUser(res.data.user)
    return res.data
  }

  const register = async (formData) => {
    const res = await api.post('/register.php', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    setUser(res.data.user)
    return res.data
  }

  const logout = async () => {
    await api.post('/logout.php')
    setUser(null)
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout }}>
      {children}
    </AuthContext.Provider>
  )
}

export const useAuth = () => useContext(AuthContext)
