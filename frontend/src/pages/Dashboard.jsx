import { useState, useEffect } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import toast from 'react-hot-toast'
import { useAuth } from '../context/AuthContext'
import api from '../api/axios'

export default function Dashboard() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const [stats, setStats] = useState({ requests: 0, notifications: 0 })

  useEffect(() => {
    api.get('/stats.php').then(r => setStats(r.data)).catch(() => {})
  }, [])

  const handleLogout = async () => {
    await logout()
    toast.success('Logged out successfully.')
    navigate('/login')
  }

  const initials = user
    ? (user.name || '').split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()
    : 'U'

  const quickLinks = [
    { href: 'http://localhost/lydo-system/youth/assistance.php',    icon: 'fa-hands-helping',  label: 'Assistance Request', bg: '#e3f2fd', color: '#1565c0' },
    { href: 'http://localhost/lydo-system/youth/accreditation.php', icon: 'fa-award',           label: 'Accreditation',      bg: '#e8f5e9', color: '#2e7d32' },
    { href: 'http://localhost/lydo-system/youth/notifications.php', icon: 'fa-bell',            label: 'Notifications',      bg: '#fff8e1', color: '#f57f17' },
    { href: 'http://localhost/lydo-system/youth/profile.php',       icon: 'fa-user-edit',       label: 'Edit Profile',       bg: '#f3e5f5', color: '#7b1fa2' },
    { href: 'http://localhost/lydo-system/youth/volunteer.php',     icon: 'fa-user-check',      label: 'Volunteer Program',  bg: '#e0f2f1', color: '#00796b' },
    { href: 'http://localhost/lydo-system/youth/scholarship.php',   icon: 'fa-graduation-cap',  label: 'Scholarship',        bg: '#fce4ec', color: '#c2185b' },
  ]

  const announcements = [
    { title: 'Youth Leadership Summit 2026 — Registration Open', date: 'June 15, 2026', venue: 'Sta. Cruz Municipal Hall',  tag: 'Leadership', tagColor: '#1565c0', tagBg: '#e3f2fd' },
    { title: 'Free Digital Skills Workshop — Limited Slots',     date: 'June 22, 2026', venue: 'Sta. Cruz Public Library',  tag: 'Skills',     tagColor: '#2e7d32', tagBg: '#e8f5e9' },
    { title: 'Inter-Barangay Sports Fest 2026',                  date: 'July 5, 2026',  venue: 'Sta. Cruz Sports Complex', tag: 'Sports',     tagColor: '#00796b', tagBg: '#e0f2f1' },
  ]

  const classification = Array.isArray(user?.youth_classification)
    ? user.youth_classification
    : (user?.youth_classification ? [user.youth_classification] : [])

  return (
    <div className="min-h-screen bg-gray-50 flex">
      {/* ── SIDEBAR ── */}
      <aside className={`fixed inset-y-0 left-0 z-40 w-64 bg-gradient-to-b from-primary-dark to-primary
                         flex flex-col transition-transform duration-300 shadow-2xl
                         ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'} lg:translate-x-0`}>
        {/* Logo */}
        <div className="flex items-center gap-3 px-5 py-5 border-b border-white/10">
          <div className="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center text-white text-lg">
            <i className="fas fa-seedling" />
          </div>
          <div>
            <p className="text-white font-black text-base leading-none">LYDO</p>
            <p className="text-white/60 text-xs">Sta. Cruz, Laguna</p>
          </div>
          <button onClick={() => setSidebarOpen(false)}
            className="ml-auto lg:hidden text-white/60 hover:text-white">
            <i className="fas fa-times" />
          </button>
        </div>

        {/* User card */}
        <div className="px-4 py-4 border-b border-white/10">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center
                            text-white font-bold text-sm flex-shrink-0">
              {initials}
            </div>
            <div className="min-w-0">
              <p className="text-white font-semibold text-sm truncate">{user?.name}</p>
              <p className="text-white/50 text-xs truncate">{user?.email}</p>
            </div>
          </div>
        </div>

        {/* Nav */}
        <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
          <p className="text-white/40 text-xs font-semibold uppercase tracking-wider px-3 mb-2">Main</p>
          {[
            ['Dashboard',          'fa-tachometer-alt', '#'],
            ['Assistance Request', 'fa-hands-helping',  'http://localhost/lydo-system/youth/assistance.php'],
            ['Accreditation',      'fa-award',          'http://localhost/lydo-system/youth/accreditation.php'],
            ['Volunteer Program',  'fa-user-check',     'http://localhost/lydo-system/youth/volunteer.php'],
            ['Scholarship',        'fa-graduation-cap', 'http://localhost/lydo-system/youth/scholarship.php'],
          ].map(([label, icon, href]) => (
            <a key={label} href={href}
              className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                          ${href === '#'
                            ? 'bg-white/20 text-white'
                            : 'text-white/70 hover:bg-white/10 hover:text-white'}`}>
              <i className={`fas ${icon} w-4 text-center`} />
              {label}
            </a>
          ))}
          <p className="text-white/40 text-xs font-semibold uppercase tracking-wider px-3 mt-4 mb-2">My Account</p>
          {[
            ['My Profile',     'fa-user-edit',  'http://localhost/lydo-system/youth/profile.php'],
            ['Notifications',  'fa-bell',       'http://localhost/lydo-system/youth/notifications.php'],
          ].map(([label, icon, href]) => (
            <a key={label} href={href}
              className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                         text-white/70 hover:bg-white/10 hover:text-white transition-colors">
              <i className={`fas ${icon} w-4 text-center`} />
              {label}
            </a>
          ))}
        </nav>

        {/* Logout */}
        <div className="px-3 py-4 border-t border-white/10">
          <button onClick={handleLogout}
            className="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-medium
                       text-white/70 hover:bg-white/10 hover:text-white transition-colors">
            <i className="fas fa-sign-out-alt w-4 text-center" />
            Logout
          </button>
        </div>
      </aside>

      {/* Overlay */}
      {sidebarOpen && (
        <div className="fixed inset-0 z-30 bg-black/50 lg:hidden"
             onClick={() => setSidebarOpen(false)} />
      )}

      {/* ── MAIN CONTENT ── */}
      <div className="flex-1 lg:ml-64 flex flex-col min-h-screen">
        {/* Topbar */}
        <header className="sticky top-0 z-20 bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3">
          <button onClick={() => setSidebarOpen(true)}
            className="lg:hidden w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-gray-600">
            <i className="fas fa-bars" />
          </button>
          <span className="font-bold text-gray-800">Dashboard</span>
          <div className="ml-auto flex items-center gap-3">
            <a href="http://localhost/lydo-system/youth/notifications.php"
               className="relative w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-primary-pale hover:text-primary transition-colors">
              <i className="fas fa-bell text-sm" />
              {stats.notifications > 0 && (
                <span className="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                  {stats.notifications}
                </span>
              )}
            </a>
            <div className="w-9 h-9 rounded-full bg-primary flex items-center justify-center text-white text-xs font-bold">
              {initials}
            </div>
          </div>
        </header>

        <main className="flex-1 p-4 md:p-6 space-y-5">
          {/* Welcome banner */}
          <motion.div
            initial={{ opacity: 0, y: 16 }} animate={{ opacity: 1, y: 0 }}
            className="bg-gradient-to-r from-primary-dark to-primary rounded-2xl p-5 text-white flex items-center justify-between flex-wrap gap-4">
            <div>
              <h2 className="text-xl font-black mb-1">Welcome back, {user?.name?.split(' ')[0]}! 👋</h2>
              <p className="text-white/75 text-sm">You're logged in to the LYDO Youth Portal of Sta. Cruz, Laguna.</p>
            </div>
            <div className="bg-white/15 border border-white/20 rounded-xl px-5 py-3 text-center">
              <span className="block text-xs text-white/60 mb-0.5">Barangay</span>
              <strong className="text-sm">{user?.barangay || '—'}</strong>
            </div>
          </motion.div>

          {/* Stat cards */}
          <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
            {[
              { icon: 'fa-hands-helping', bg: 'bg-blue-50',   color: 'text-primary',  val: stats.requests,      label: 'Active Requests' },
              { icon: 'fa-map-marker-alt',bg: 'bg-green-50',  color: 'text-success-DEFAULT', val: user?.barangay || '—', label: 'Barangay', small: true },
              { icon: 'fa-bell',          bg: 'bg-yellow-50', color: 'text-yellow-600', val: stats.notifications, label: 'Notifications' },
            ].map(({ icon, bg, color, val, label, small }) => (
              <motion.div key={label}
                initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }}
                className="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3 shadow-sm">
                <div className={`w-11 h-11 rounded-xl ${bg} ${color} flex items-center justify-center text-lg flex-shrink-0`}>
                  <i className={`fas ${icon}`} />
                </div>
                <div>
                  <span className={`block font-black leading-none mb-0.5 ${small ? 'text-base' : 'text-2xl'} text-gray-800`}>{val}</span>
                  <span className="text-xs text-gray-500 font-medium">{label}</span>
                </div>
              </motion.div>
            ))}
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {/* Profile summary */}
            <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
              <div className="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
                <i className="fas fa-user text-primary text-sm" />
                <span className="font-bold text-sm text-gray-800">My Profile</span>
                <a href="http://localhost/lydo-system/youth/profile.php"
                   className="ml-auto text-xs text-primary font-semibold hover:underline">Edit →</a>
              </div>
              <div>
                {[
                  ['Full Name',    user?.name],
                  ['Email',        user?.email],
                  ['Barangay',     user?.barangay],
                ].map(([label, val]) => (
                  <div key={label} className="flex gap-3 px-4 py-2.5 border-b border-gray-50 text-sm last:border-0">
                    <span className="w-24 flex-shrink-0 text-gray-500 font-medium">{label}</span>
                    <span className="text-gray-800 font-medium truncate">{val || '—'}</span>
                  </div>
                ))}
                {classification.length > 0 && (
                  <div className="flex gap-3 px-4 py-2.5 text-sm">
                    <span className="w-24 flex-shrink-0 text-gray-500 font-medium">Classification</span>
                    <div className="flex flex-wrap gap-1">
                      {classification.map(c => (
                        <span key={c} className="bg-primary-pale text-primary text-xs font-bold px-2.5 py-0.5 rounded-full">{c}</span>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Quick links */}
            <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
              <div className="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
                <i className="fas fa-th text-primary text-sm" />
                <span className="font-bold text-sm text-gray-800">Quick Links</span>
              </div>
              <div className="p-3 grid grid-cols-2 gap-2">
                {quickLinks.map(({ href, icon, label, bg, color }) => (
                  <a key={label} href={href}
                    className="flex items-center gap-2.5 p-3 rounded-xl border border-gray-200
                               text-gray-700 text-xs font-semibold hover:border-current transition-all"
                    style={{ '--hover-color': color }}
                    onMouseEnter={e => { e.currentTarget.style.background = bg; e.currentTarget.style.color = color; e.currentTarget.style.borderColor = color; }}
                    onMouseLeave={e => { e.currentTarget.style.background = ''; e.currentTarget.style.color = ''; e.currentTarget.style.borderColor = ''; }}>
                    <i className={`fas ${icon} text-sm flex-shrink-0`} style={{ color }} />
                    {label}
                  </a>
                ))}
              </div>
            </div>
          </div>

          {/* Announcements */}
          <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div className="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
              <i className="fas fa-bullhorn text-primary text-sm" />
              <span className="font-bold text-sm text-gray-800">Latest Announcements</span>
            </div>
            <div>
              {announcements.map(({ title, date, venue, tag, tagColor, tagBg }) => (
                <div key={title} className="flex items-start gap-3 px-4 py-3 border-b border-gray-50 last:border-0">
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-semibold text-gray-800 mb-1">{title}</p>
                    <div className="flex items-center gap-4 text-xs text-gray-500">
                      <span><i className="fas fa-calendar mr-1" />{date}</span>
                      <span><i className="fas fa-map-marker-alt mr-1" />{venue}</span>
                    </div>
                  </div>
                  <span className="text-xs font-bold px-2.5 py-1 rounded-full flex-shrink-0"
                        style={{ background: tagBg, color: tagColor }}>{tag}</span>
                </div>
              ))}
            </div>
          </div>
        </main>
      </div>
    </div>
  )
}
