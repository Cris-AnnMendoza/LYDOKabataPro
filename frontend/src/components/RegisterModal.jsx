import { useState, useEffect } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import toast from 'react-hot-toast'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

const BARANGAYS = [
  'Barangay 1 - Poblacion','Barangay 2 - Poblacion','Barangay 3 - Poblacion',
  'Barangay 4 - Poblacion','Barangay 5 - Poblacion','Barangay 6 - Poblacion',
  'Barangay 7 - Poblacion','Barangay 8 - Poblacion','Bubukal','Calios','Duhat',
  'Gatid','Jasaan','Labuin','Luciano','Malinao','Palayan','Pook','Pulong Bayabas',
  'Saguimsim','Sampaloc','San Antonio','San Juan','Sirang Lupa','Tabuco','Talaga',
  'Sto. Angel Norte','Sto. Angel Sur',
]

const CLASSIFICATIONS = [
  { value: 'In School Youth',              icon: 'fa-graduation-cap' },
  { value: 'Out of School Youth',          icon: 'fa-door-open' },
  { value: 'Working Youth',                icon: 'fa-briefcase' },
  { value: 'Youth with Disability',        icon: 'fa-wheelchair' },
  { value: 'Indigenous Youth',             icon: 'fa-leaf' },
  { value: 'Children in Conflict with Law',icon: 'fa-balance-scale' },
]

const PROGRAMS = [
  'Education & Scholarship','Livelihood & Skills','Health & Wellness',
  'Leadership Development','Arts & Culture','Environment & Community',
]

const STEPS = ['Personal','Address','Classification','Education','Additional']

const INIT = {
  first_name:'', middle_name:'', last_name:'', suffix:'', gender:'',
  birthdate:'', age:'', civil_status:'', contact_number:'', email:'',
  password:'', confirm_password:'',
  house_number:'', street:'', barangay:'', municipality:'Sta. Cruz',
  province:'Laguna', zip_code:'4009',
  youth_classification:[],
  educational_status:'', school_name:'', course_or_grade:'',
  employment_status:'', organization_name:'', organization_type:'',
  organization_role:'', years_membership:'0',
  skills:'', interests:'', programs_interested:[], volunteer_availability:'',
  valid_id: null, profile_picture: null,
}

function OrgSelector({ form, set }) {
  const [orgs, setOrgs] = useState([])
  const [useCustom, setUseCustom] = useState(false)

  useEffect(() => {
    fetch('http://localhost/lydo-system/backend/api/organizations.php', { credentials: 'include' })
      .then(r => r.json())
      .then(d => { if (d.success) setOrgs(d.organizations) })
      .catch(() => {})
  }, [])

  return (
    <div className="border border-gray-200 rounded-xl p-4 bg-gray-50 space-y-3">
      <p className="text-xs font-bold text-primary-dark uppercase tracking-wide">Organization (optional)</p>
      {!useCustom ? (
        <div>
          <label className="form-label">Organization Name</label>
          <select className="input-field" value={form.organization_name}
            onChange={e => {
              if (e.target.value === '__custom__') { setUseCustom(true); set('organization_name', '') }
              else set('organization_name', e.target.value)
            }}>
            <option value="">— Not a member of any org —</option>
            {orgs.map(o => <option key={o.id} value={o.name}>{o.name}{o.category ? ` (${o.category})` : ''}</option>)}
            <option value="__custom__">+ Type a different organization name</option>
          </select>
        </div>
      ) : (
        <div>
          <label className="form-label">Organization Name</label>
          <div className="flex gap-2">
            <input className="input-field" placeholder="Enter organization name" value={form.organization_name}
              onChange={e => set('organization_name', e.target.value)} />
            <button type="button" onClick={() => { setUseCustom(false); set('organization_name', '') }}
              className="px-3 py-2 text-xs text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-100">
              ← List
            </button>
          </div>
        </div>
      )}
      {form.organization_name && (
        <div className="grid grid-cols-2 gap-3">
          <div><label className="form-label">Type of Organization</label>
            <select className="input-field" value={form.organization_type} onChange={e => set('organization_type', e.target.value)}>
              <option value="">Select type</option>
              {['Sangguniang Kabataan','Youth NGO','Religious Organization','Sports Club','Academic Organization','Community Group','Cultural Group','Other'].map(t => <option key={t}>{t}</option>)}
            </select>
          </div>
          <div><label className="form-label">Position / Role</label>
            <select className="input-field" value={form.organization_role} onChange={e => set('organization_role', e.target.value)}>
              <option value="">Select position</option>
              {['President','Vice President','Secretary','Treasurer','Auditor','PRO','Sergeant-at-Arms','Member','Officer','Coordinator','Adviser','Other'].map(p => <option key={p}>{p}</option>)}
            </select>
          </div>
          <div><label className="form-label">Years of Membership</label>
            <input type="number" className="input-field" placeholder="e.g. 2" min="0" max="20" value={form.years_membership} onChange={e => set('years_membership', e.target.value)} />
          </div>
        </div>
      )}
    </div>
  )
}

export default function RegisterModal({ onClose }) {
  const { register } = useAuth()
  const navigate = useNavigate()
  const [step, setStep] = useState(1)
  const [form, setForm] = useState(INIT)
  const [errors, setErrors] = useState({})
  const [loading, setLoading] = useState(false)
  const [showPw, setShowPw] = useState(false)
  const [showCPw, setShowCPw] = useState(false)

  const set = (field, value) => {
    setForm(f => ({ ...f, [field]: value }))
    setErrors(e => ({ ...e, [field]: '' }))
  }

  const toggleArr = (field, value) => {
    setForm(f => ({
      ...f,
      [field]: f[field].includes(value)
        ? f[field].filter(v => v !== value)
        : [...f[field], value],
    }))
  }

  const calcAge = (bdate) => {
    if (!bdate) return ''
    const today = new Date()
    const birth = new Date(bdate)
    let age = today.getFullYear() - birth.getFullYear()
    const m = today.getMonth() - birth.getMonth()
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--
    return String(age)
  }

  const validateStep = () => {
    const e = {}
    if (step === 1) {
      if (!form.first_name.trim()) e.first_name = 'Required'
      if (!form.last_name.trim())  e.last_name  = 'Required'
      if (!form.gender)            e.gender     = 'Required'
      if (!form.birthdate)         e.birthdate  = 'Required'
      if (!form.civil_status)      e.civil_status = 'Required'
      if (!form.contact_number.trim()) e.contact_number = 'Required'
      if (!form.email.trim())      e.email      = 'Required'
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) e.email = 'Invalid email'
      if (!form.password)          e.password   = 'Required'
      else if (form.password.length < 8) e.password = 'Min. 8 characters'
      if (form.password !== form.confirm_password) e.confirm_password = 'Passwords do not match'
    }
    if (step === 2) {
      if (!form.barangay) e.barangay = 'Required'
    }
    if (step === 3) {
      if (form.youth_classification.length === 0) e.youth_classification = 'Select at least one'
    }
    setErrors(e)
    return Object.keys(e).length === 0
  }

  const next = () => { if (validateStep()) setStep(s => s + 1) }
  const prev = () => setStep(s => s - 1)

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!validateStep()) return
    setLoading(true)
    try {
      const fd = new FormData()
      Object.entries(form).forEach(([k, v]) => {
        if (k === 'confirm_password') return
        if (k === 'valid_id' || k === 'profile_picture') {
          if (v) fd.append(k, v)
        } else if (Array.isArray(v)) {
          v.forEach(item => fd.append(`${k}[]`, item))
        } else {
          fd.append(k, v)
        }
      })
      await register(fd)
      toast.success('Registration successful! Welcome to LYDO.')
      onClose()
      navigate('/dashboard')
    } catch (err) {
      const msg = err.response?.data?.message || 'Registration failed.'
      toast.error(msg)
      if (msg.toLowerCase().includes('email')) {
        setStep(1)
        setErrors({ email: msg })
      }
    } finally {
      setLoading(false)
    }
  }

  const inputCls = (field) =>
    `input-field ${errors[field] ? 'error' : ''}`

  return (
    <motion.div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
      initial={{ opacity: 0 }} animate={{ opacity: 1 }}
      onClick={e => e.target === e.currentTarget && onClose()}>
      <motion.div className="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] flex flex-col shadow-2xl overflow-hidden"
        initial={{ scale: .95, y: 20 }} animate={{ scale: 1, y: 0 }}>

        {/* Header */}
        <div className="flex items-center gap-3 px-6 py-4 border-b border-gray-100 flex-shrink-0">
          <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-dark to-primary
                          flex items-center justify-center text-white">
            <i className="fas fa-user-plus" />
          </div>
          <div>
            <h2 className="font-black text-gray-800">New User Registration</h2>
            <p className="text-xs text-gray-500">Fill in your details to join LYDO Sta. Cruz Laguna</p>
          </div>
          <button onClick={onClose}
            className="ml-auto w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition-colors">
            <i className="fas fa-times text-sm" />
          </button>
        </div>

        {/* Step indicator */}
        <div className="flex items-center px-6 py-3 border-b border-gray-100 flex-shrink-0 overflow-x-auto">
          {STEPS.map((label, i) => {
            const n = i + 1
            const done = step > n
            const active = step === n
            return (
              <div key={label} className="flex items-center flex-shrink-0">
                <div className={`flex items-center gap-1.5 ${active ? 'text-primary' : done ? 'text-success-DEFAULT' : 'text-gray-400'}`}>
                  <div className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                                   ${active ? 'bg-primary text-white' : done ? 'bg-success-DEFAULT text-white' : 'bg-gray-100 text-gray-400'}`}>
                    {done ? <i className="fas fa-check text-xs" /> : n}
                  </div>
                  <span className="text-xs font-semibold hidden sm:block">{label}</span>
                </div>
                {i < STEPS.length - 1 && (
                  <div className={`w-6 sm:w-10 h-0.5 mx-1 ${step > n ? 'bg-success-DEFAULT' : 'bg-gray-200'}`} />
                )}
              </div>
            )
          })}
        </div>

        {/* Body */}
        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto px-6 py-5 space-y-4">
          <AnimatePresence mode="wait">
            <motion.div key={step}
              initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }}
              transition={{ duration: .2 }}>

              {/* STEP 1 — Personal */}
              {step === 1 && (
                <div className="space-y-4">
                  <p className="step-badge"><i className="fas fa-user" /> Personal Information</p>
                  <div className="grid grid-cols-3 gap-3">
                    <div><label className="form-label">First Name <span className="text-red-500">*</span></label>
                      <input className={inputCls('first_name')} placeholder="Juan" value={form.first_name} onChange={e => set('first_name', e.target.value)} />
                      {errors.first_name && <p className="text-red-500 text-xs mt-1">{errors.first_name}</p>}
                    </div>
                    <div><label className="form-label">Middle Name</label>
                      <input className="input-field" placeholder="Santos" value={form.middle_name} onChange={e => set('middle_name', e.target.value)} />
                    </div>
                    <div><label className="form-label">Last Name <span className="text-red-500">*</span></label>
                      <input className={inputCls('last_name')} placeholder="dela Cruz" value={form.last_name} onChange={e => set('last_name', e.target.value)} />
                      {errors.last_name && <p className="text-red-500 text-xs mt-1">{errors.last_name}</p>}
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-3">
                    <div><label className="form-label">Suffix</label>
                      <select className="input-field" value={form.suffix} onChange={e => set('suffix', e.target.value)}>
                        <option value="">None</option>
                        {['Jr.','Sr.','II','III','IV'].map(s => <option key={s}>{s}</option>)}
                      </select>
                    </div>
                    <div><label className="form-label">Gender <span className="text-red-500">*</span></label>
                      <select className={inputCls('gender')} value={form.gender} onChange={e => set('gender', e.target.value)}>
                        <option value="">Select gender</option>
                        {['Male','Female','Non-binary','Prefer not to say'].map(g => <option key={g}>{g}</option>)}
                      </select>
                      {errors.gender && <p className="text-red-500 text-xs mt-1">{errors.gender}</p>}
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-3">
                    <div><label className="form-label">Birthdate <span className="text-red-500">*</span></label>
                      <input type="date" className={inputCls('birthdate')} value={form.birthdate}
                        onChange={e => { set('birthdate', e.target.value); set('age', calcAge(e.target.value)) }} />
                      {errors.birthdate && <p className="text-red-500 text-xs mt-1">{errors.birthdate}</p>}
                    </div>
                    <div><label className="form-label">Age</label>
                      <input className="input-field bg-gray-100" readOnly value={form.age} placeholder="Auto-calculated" />
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-3">
                    <div><label className="form-label">Civil Status <span className="text-red-500">*</span></label>
                      <select className={inputCls('civil_status')} value={form.civil_status} onChange={e => set('civil_status', e.target.value)}>
                        <option value="">Select status</option>
                        {['Single','Married','Widowed','Separated'].map(s => <option key={s}>{s}</option>)}
                      </select>
                      {errors.civil_status && <p className="text-red-500 text-xs mt-1">{errors.civil_status}</p>}
                    </div>
                    <div><label className="form-label">Contact Number <span className="text-red-500">*</span></label>
                      <input className={inputCls('contact_number')} placeholder="09XX-XXX-XXXX" value={form.contact_number} onChange={e => set('contact_number', e.target.value)} />
                      {errors.contact_number && <p className="text-red-500 text-xs mt-1">{errors.contact_number}</p>}
                    </div>
                  </div>
                  <div><label className="form-label">Email Address <span className="text-red-500">*</span></label>
                    <input type="email" className={inputCls('email')} placeholder="juan@email.com" value={form.email} onChange={e => set('email', e.target.value)} />
                    {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email}</p>}
                  </div>
                  <div className="grid grid-cols-2 gap-3">
                    <div><label className="form-label">Password <span className="text-red-500">*</span></label>
                      <div className="relative">
                        <input type={showPw ? 'text' : 'password'} className={`${inputCls('password')} pr-10`}
                          placeholder="Min. 8 characters" value={form.password} onChange={e => set('password', e.target.value)} />
                        <button type="button" onClick={() => setShowPw(v => !v)}
                          className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary">
                          <i className={`fas ${showPw ? 'fa-eye-slash' : 'fa-eye'} text-sm`} />
                        </button>
                      </div>
                      {errors.password && <p className="text-red-500 text-xs mt-1">{errors.password}</p>}
                    </div>
                    <div><label className="form-label">Confirm Password <span className="text-red-500">*</span></label>
                      <div className="relative">
                        <input type={showCPw ? 'text' : 'password'} className={`${inputCls('confirm_password')} pr-10`}
                          placeholder="Repeat password" value={form.confirm_password} onChange={e => set('confirm_password', e.target.value)} />
                        <button type="button" onClick={() => setShowCPw(v => !v)}
                          className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary">
                          <i className={`fas ${showCPw ? 'fa-eye-slash' : 'fa-eye'} text-sm`} />
                        </button>
                      </div>
                      {errors.confirm_password && <p className="text-red-500 text-xs mt-1">{errors.confirm_password}</p>}
                    </div>
                  </div>
                </div>
              )}

              {/* STEP 2 — Address */}
              {step === 2 && (
                <div className="space-y-4">
                  <p className="step-badge"><i className="fas fa-map-marker-alt" /> Address Information</p>
                  <div><label className="form-label">House Number / Street</label>
                    <input className="input-field" placeholder="123 Rizal St." value={form.house_number}
                      onChange={e => { set('house_number', e.target.value); set('street', e.target.value) }} />
                  </div>
                  <div className="grid grid-cols-2 gap-3">
                    <div><label className="form-label">Barangay <span className="text-red-500">*</span></label>
                      <select className={inputCls('barangay')} value={form.barangay} onChange={e => set('barangay', e.target.value)}>
                        <option value="">Select barangay</option>
                        {BARANGAYS.map(b => <option key={b}>{b}</option>)}
                      </select>
                      {errors.barangay && <p className="text-red-500 text-xs mt-1">{errors.barangay}</p>}
                    </div>
                    <div><label className="form-label">Municipality</label>
                      <input className="input-field bg-gray-100" readOnly value={form.municipality} />
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-3">
                    <div><label className="form-label">Province</label>
                      <input className="input-field bg-gray-100" readOnly value={form.province} />
                    </div>
                    <div><label className="form-label">ZIP Code</label>
                      <input className="input-field bg-gray-100" readOnly value={form.zip_code} />
                    </div>
                  </div>
                </div>
              )}

              {/* STEP 3 — Classification */}
              {step === 3 && (
                <div className="space-y-4">
                  <p className="step-badge"><i className="fas fa-id-card" /> Youth Classification</p>
                  <p className="text-sm text-gray-500">Select all that apply to you.</p>
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    {CLASSIFICATIONS.map(({ value, icon }) => {
                      const checked = form.youth_classification.includes(value)
                      return (
                        <label key={value}
                          className={`flex flex-col items-center gap-2 p-4 rounded-xl border-2 cursor-pointer transition-all
                                      ${checked ? 'border-primary bg-primary-pale text-primary' : 'border-gray-200 hover:border-primary/40'}`}>
                          <input type="checkbox" className="sr-only" checked={checked}
                            onChange={() => toggleArr('youth_classification', value)} />
                          <i className={`fas ${icon} text-xl ${checked ? 'text-primary' : 'text-gray-400'}`} />
                          <span className="text-xs font-semibold text-center leading-tight">{value}</span>
                        </label>
                      )
                    })}
                  </div>
                  {errors.youth_classification && (
                    <p className="text-red-500 text-xs">{errors.youth_classification}</p>
                  )}
                </div>
              )}

              {/* STEP 4 — Education */}
              {step === 4 && (
                <div className="space-y-4">
                  <p className="step-badge"><i className="fas fa-graduation-cap" /> Education & Employment</p>
                  <div className="grid grid-cols-2 gap-3">
                    <div><label className="form-label">Educational Status</label>
                      <select className="input-field" value={form.educational_status} onChange={e => set('educational_status', e.target.value)}>
                        <option value="">Select status</option>
                        {['Elementary','High School','Senior High School','College','Vocational','Post-Graduate','Out of School'].map(s => <option key={s}>{s}</option>)}
                      </select>
                    </div>
                    <div><label className="form-label">Employment Status</label>
                      <select className="input-field" value={form.employment_status} onChange={e => set('employment_status', e.target.value)}>
                        <option value="">Select status</option>
                        {['Employed','Unemployed','Self-Employed','Student','Underemployed'].map(s => <option key={s}>{s}</option>)}
                      </select>
                    </div>
                  </div>
                  <div><label className="form-label">School Name</label>
                    <input className="input-field" placeholder="e.g. Sta. Cruz National High School" value={form.school_name} onChange={e => set('school_name', e.target.value)} />
                  </div>
                  <div><label className="form-label">Course / Grade Level</label>
                    <input className="input-field" placeholder="e.g. Grade 12 / BS Computer Science" value={form.course_or_grade} onChange={e => set('course_or_grade', e.target.value)} />
                  </div>
                  <OrgSelector form={form} set={set} />
                </div>
              )}

              {/* STEP 5 — Additional */}
              {step === 5 && (
                <div className="space-y-4">
                  <p className="step-badge"><i className="fas fa-plus-circle" /> Additional Information</p>
                  <div><label className="form-label">Skills</label>
                    <textarea className="input-field resize-none" rows={2} placeholder="e.g. Leadership, Public Speaking, Programming"
                      value={form.skills} onChange={e => set('skills', e.target.value)} />
                  </div>
                  <div><label className="form-label">Interests</label>
                    <textarea className="input-field resize-none" rows={2} placeholder="e.g. Sports, Music, Community Service"
                      value={form.interests} onChange={e => set('interests', e.target.value)} />
                  </div>
                  <div>
                    <label className="form-label">Programs Interested In</label>
                    <div className="grid grid-cols-2 gap-2">
                      {PROGRAMS.map(p => {
                        const checked = form.programs_interested.includes(p)
                        return (
                          <label key={p}
                            className={`flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer text-sm transition-all
                                        ${checked ? 'border-primary bg-primary-pale text-primary font-semibold' : 'border-gray-200 hover:border-primary/40'}`}>
                            <input type="checkbox" className="sr-only" checked={checked}
                              onChange={() => toggleArr('programs_interested', p)} />
                            <i className={`fas fa-check-circle text-xs ${checked ? 'text-primary' : 'text-gray-300'}`} />
                            {p}
                          </label>
                        )
                      })}
                    </div>
                  </div>
                  <div><label className="form-label">Volunteer Availability</label>
                    <select className="input-field" value={form.volunteer_availability} onChange={e => set('volunteer_availability', e.target.value)}>
                      <option value="">Select availability</option>
                      {['Weekdays','Weekends','Both','Not Available'].map(v => <option key={v}>{v}</option>)}
                    </select>
                  </div>
                  <div className="grid grid-cols-2 gap-3">
                    <div><label className="form-label">Valid ID <span className="text-gray-400 text-xs">(optional)</span></label>
                      <input type="file" accept=".jpg,.jpeg,.png,.pdf"
                        className="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-pale file:text-primary hover:file:bg-primary/10 cursor-pointer"
                        onChange={e => set('valid_id', e.target.files[0] || null)} />
                    </div>
                    <div><label className="form-label">Profile Picture <span className="text-gray-400 text-xs">(optional)</span></label>
                      <input type="file" accept=".jpg,.jpeg,.png"
                        className="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-pale file:text-primary hover:file:bg-primary/10 cursor-pointer"
                        onChange={e => set('profile_picture', e.target.files[0] || null)} />
                    </div>
                  </div>
                </div>
              )}

            </motion.div>
          </AnimatePresence>
        </form>

        {/* Footer */}
        <div className="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50 flex-shrink-0">
          <button type="button" onClick={step === 1 ? onClose : prev}
            className="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-gray-600 text-sm font-semibold hover:bg-gray-100 transition-colors">
            <i className={`fas ${step === 1 ? 'fa-times' : 'fa-arrow-left'} text-xs`} />
            {step === 1 ? 'Cancel' : 'Back'}
          </button>
          <span className="text-xs text-gray-400 font-medium">Step {step} of {STEPS.length}</span>
          {step < STEPS.length ? (
            <button type="button" onClick={next}
              className="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-primary-dark to-primary text-white text-sm font-bold hover:-translate-y-0.5 hover:shadow-lg hover:shadow-primary/30 transition-all">
              Next <i className="fas fa-arrow-right text-xs" />
            </button>
          ) : (
            <button type="button" onClick={handleSubmit} disabled={loading}
              className="btn-green px-5 py-2.5 text-sm disabled:opacity-60 disabled:cursor-not-allowed">
              {loading
                ? <><i className="fas fa-spinner fa-spin" /> Registering...</>
                : <><i className="fas fa-check" /> Complete Registration</>}
            </button>
          )}
        </div>
      </motion.div>
    </motion.div>
  )
}
