import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import toast from 'react-hot-toast'
import { useAuth } from '../context/AuthContext'

export default function LoginPage() {
  const { login } = useAuth()
  const navigate  = useNavigate()

  const [form, setForm]       = useState({ email: '', password: '' })
  const [errors, setErrors]   = useState({})
  const [showPw, setShowPw]   = useState(false)
  const [loading, setLoading] = useState(false)
  const [remember, setRemember] = useState(false)
  const [showForgot, setShowForgot] = useState(false)
  const [fpEmail, setFpEmail] = useState('')
  const [fpLoading, setFpLoading] = useState(false)

  const validate = () => {
    const e = {}
    if (!form.email)    e.email    = 'Email is required.'
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) e.email = 'Enter a valid email.'
    if (!form.password) e.password = 'Password is required.'
    return e
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    const errs = validate()
    if (Object.keys(errs).length) { setErrors(errs); return }
    setLoading(true)
    try {
      await login(form.email, form.password)
      toast.success('Welcome back!')
      navigate('/dashboard')
    } catch (err) {
      const msg = err.response?.data?.message || 'Login failed.'
      toast.error(msg)
      if (msg.includes('not found')) setErrors({ email: msg })
      else setErrors({ password: msg })
    } finally {
      setLoading(false)
    }
  }

  const handleForgot = async (e) => {
    e.preventDefault()
    setFpLoading(true)
    await new Promise(r => setTimeout(r, 1500))
    toast.success('Password reset link sent! Check your email.')
    setShowForgot(false)
    setFpEmail('')
    setFpLoading(false)
  }

  return (
    <div className="min-h-screen grid grid-cols-1 lg:grid-cols-2">

      {/* ── LEFT PANEL ── */}
      <div className="relative hidden lg:flex flex-col items-center justify-center overflow-hidden"
           style={{ background: 'linear-gradient(160deg,#0d3b6e 0%,#1565c0 55%,#1b5e20 100%)' }}>
        {/* decorative blobs */}
        <div className="absolute -top-32 -right-24 w-96 h-96 rounded-full bg-white/5" />
        <div className="absolute -bottom-20 -left-16 w-72 h-72 rounded-full bg-white/5" />
        <div className="absolute top-1/2 right-12 w-40 h-40 rounded-full bg-white/5" />

        <motion.div className="relative z-10 flex flex-col items-center text-center px-12"
          initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: .6 }}>

          {/* Logo */}
          <motion.div className="w-28 h-28 rounded-3xl bg-white/15 backdrop-blur border-2 border-white/25
                                  flex items-center justify-center text-5xl text-white mb-6 shadow-2xl"
            animate={{ boxShadow: ['0 0 0 0 rgba(255,255,255,.1)', '0 0 0 20px rgba(255,255,255,0)', '0 0 0 0 rgba(255,255,255,.1)'] }}
            transition={{ duration: 3, repeat: Infinity }}>
            <i className="fas fa-seedling" />
          </motion.div>

          <h1 className="text-5xl font-black text-white tracking-widest mb-1">LYDO</h1>
          <p className="text-white/70 font-medium mb-6">Local Youth Development Office</p>
          <div className="w-12 h-1 rounded bg-white/40 mb-4" />
          <p className="text-white/80 text-sm flex items-center gap-2 mb-2">
            <i className="fas fa-map-marker-alt text-green-300" /> Sta. Cruz, Laguna
          </p>
          <p className="text-white/50 text-sm italic max-w-xs leading-relaxed mb-10">
            "Empowering the Youth of Sta. Cruz Laguna"
          </p>

          {/* Stats */}
          <div className="flex gap-8 bg-white/10 backdrop-blur border border-white/15 rounded-2xl px-8 py-5">
            {[['5,000+','Members'],['30+','Programs'],['50+','Events']].map(([n,l]) => (
              <div key={l} className="text-center">
                <span className="block text-2xl font-black text-white">{n}</span>
                <span className="text-xs text-white/60 font-medium">{l}</span>
              </div>
            ))}
          </div>
        </motion.div>

        <p className="absolute bottom-6 text-white/30 text-xs z-10">
          © 2026 Municipal Government of Sta. Cruz, Laguna
        </p>
      </div>

      {/* ── RIGHT PANEL ── */}
      <div className="flex flex-col items-center justify-center bg-white px-6 py-12 relative">
        <Link to="/" className="absolute top-6 left-6 flex items-center gap-2 text-sm font-semibold
                                 text-gray-500 hover:text-primary transition-colors">
          <i className="fas fa-arrow-left" /> Back to Home
        </Link>

        <motion.div className="w-full max-w-md"
          initial={{ opacity: 0, y: 24 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: .5 }}>

          <div className="text-center mb-8">
            <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-dark to-primary
                            flex items-center justify-center text-white text-2xl mx-auto mb-4">
              <i className="fas fa-sign-in-alt" />
            </div>
            <h2 className="text-3xl font-black mb-2">Welcome Back!</h2>
            <p className="text-gray-500 text-sm">Login to access your youth dashboard and programs.</p>
          </div>

          <form onSubmit={handleSubmit} noValidate className="space-y-5">
            {/* Email */}
            <div>
              <label className="form-label">Email Address</label>
              <div className="relative">
                <i className="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
                <input type="email" placeholder="juan@email.com"
                  className={`input-field pl-10 ${errors.email ? 'error' : ''}`}
                  value={form.email}
                  onChange={e => { setForm(f => ({...f, email: e.target.value})); setErrors(v => ({...v, email:''})) }} />
              </div>
              {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email}</p>}
            </div>

            {/* Password */}
            <div>
              <label className="form-label">Password</label>
              <div className="relative">
                <i className="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
                <input type={showPw ? 'text' : 'password'} placeholder="Enter your password"
                  className={`input-field pl-10 pr-12 ${errors.password ? 'error' : ''}`}
                  value={form.password}
                  onChange={e => { setForm(f => ({...f, password: e.target.value})); setErrors(v => ({...v, password:''})) }} />
                <button type="button" onClick={() => setShowPw(v => !v)}
                  className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary transition-colors">
                  <i className={`fas ${showPw ? 'fa-eye-slash' : 'fa-eye'}`} />
                </button>
              </div>
              {errors.password && <p className="text-red-500 text-xs mt-1">{errors.password}</p>}
            </div>

            {/* Options */}
            <div className="flex items-center justify-between">
              <label className="flex items-center gap-2 cursor-pointer text-sm text-gray-600">
                <input type="checkbox" checked={remember} onChange={e => setRemember(e.target.checked)}
                  className="w-4 h-4 accent-primary rounded" />
                Remember Me
              </label>
              <button type="button" onClick={() => setShowForgot(true)}
                className="text-sm text-primary font-semibold hover:underline">
                Forgot Password?
              </button>
            </div>

            <button type="submit" className="btn-primary" disabled={loading}>
              {loading
                ? <><i className="fas fa-spinner fa-spin" /> Logging in...</>
                : <><i className="fas fa-sign-in-alt" /> Login Here</>}
            </button>
          </form>

          <div className="flex items-center gap-3 my-6">
            <div className="flex-1 h-px bg-gray-200" />
            <span className="text-gray-400 text-sm">or</span>
            <div className="flex-1 h-px bg-gray-200" />
          </div>

          <p className="text-center text-sm text-gray-600">
            Don't have an account?{' '}
            <Link to="/#get-started" className="text-primary font-bold hover:underline">Register here</Link>
          </p>
        </motion.div>
      </div>

      {/* ── FORGOT PASSWORD MODAL ── */}
      {showForgot && (
        <motion.div className="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4"
          initial={{ opacity: 0 }} animate={{ opacity: 1 }}
          onClick={e => e.target === e.currentTarget && setShowForgot(false)}>
          <motion.div className="bg-white rounded-2xl p-8 w-full max-w-sm shadow-2xl relative"
            initial={{ scale: .95, y: 20 }} animate={{ scale: 1, y: 0 }}>
            <button onClick={() => setShowForgot(false)}
              className="absolute top-4 right-4 w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200
                         flex items-center justify-center text-gray-500 transition-colors">
              <i className="fas fa-times text-sm" />
            </button>
            <div className="w-14 h-14 rounded-2xl bg-primary-pale flex items-center justify-center
                            text-primary text-2xl mx-auto mb-4">
              <i className="fas fa-key" />
            </div>
            <h3 className="text-xl font-black text-center mb-2">Forgot Password?</h3>
            <p className="text-gray-500 text-sm text-center mb-6">
              Enter your registered email and we'll send you a reset link.
            </p>
            <form onSubmit={handleForgot} className="space-y-4">
              <div className="relative">
                <i className="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
                <input type="email" placeholder="juan@email.com" required
                  className="input-field pl-10" value={fpEmail}
                  onChange={e => setFpEmail(e.target.value)} />
              </div>
              <button type="submit" className="btn-primary" disabled={fpLoading}>
                {fpLoading
                  ? <><i className="fas fa-spinner fa-spin" /> Sending...</>
                  : <><i className="fas fa-paper-plane" /> Send Reset Link</>}
              </button>
            </form>
            <button onClick={() => setShowForgot(false)}
              className="flex items-center gap-2 text-sm text-gray-500 hover:text-primary
                         transition-colors mx-auto mt-4">
              <i className="fas fa-arrow-left" /> Back to Login
            </button>
          </motion.div>
        </motion.div>
      )}
    </div>
  )
}
