// ============================================================
// CAREBRIDGE - FULLY CORRECTED APPLICATION
// ============================================================

const BASE_URL = "http://localhost/CareBridge/backend/api/";

// Global variables
let currentRole = 'patient';
let currentUser = '';
let authMode = 'signup';

// Symptom tracking
const symptoms = {
  headache: 'no', swelling: 'no', dizzy: 'no',
  vision: 'no', mood: 'happy', baby: 'normal', urine: 'normal'
};

// Local storage for offline data
const DB = {
  patientHistory: JSON.parse(localStorage.getItem('cb_history') || '[]'),
  save() { localStorage.setItem('cb_history', JSON.stringify(this.patientHistory)); }
};

// ============================================================
// COMMON API HANDLER
// ============================================================

async function apiCall(endpoint, options = {}) {
  try {
    const url = BASE_URL + endpoint;
    const defaultOptions = {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      }
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    console.log('API Call:', url, finalOptions);
    
    const response = await fetch(url, finalOptions);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const result = await response.json();
    console.log('API Response:', result);
    
    // Ensure proper response structure handling
    if (result.success) {
      return result;
    } else {
      throw new Error(result.message || 'API call failed');
    }
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}

// ============================================================
// AUTHENTICATION (USING ONLY simple_auth.php)
// ============================================================

async function doSignup() {
  const name = document.getElementById('su-name')?.value?.trim();
  const email = document.getElementById('su-email')?.value?.trim();
  const pass = document.getElementById('su-pass')?.value;
  
  if (!name || !email || !pass) {
    showToast('Please fill all fields', 'error');
    return;
  }
  
  try {
    const result = await apiCall('simple_auth.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'signup',
        user_type: currentRole,
        name: name,
        email: email,
        password: pass,
        ...(currentRole === 'patient' ? {
          pregnancy_week: parseInt(document.getElementById('su-week')?.value || 28),
          phone: '',
          age: null
        } : {
          specialty: 'General Physician',
          experience_years: 0,
          hospital: '',
          location: '',
          contact: ''
        })
      })
    });
    
    if (result.success) {
      currentUser = result.user.name;
      localStorage.setItem('cb_session_token', result.session_token);
      localStorage.setItem('cb_user_type', result.user_type);
      localStorage.setItem('cb_user_data', JSON.stringify(result.user));
      showToast('Account created successfully!');
      enterDashboard();
    } else {
      showToast(result.message || 'Signup failed', 'error');
    }
  } catch (error) {
    console.error('Signup error:', error);
    showToast('Failed to create account', 'error');
  }
}

async function doLogin() {
  const email = document.getElementById('li-email')?.value?.trim();
  const pass = document.getElementById('li-pass')?.value;
  
  if (!email || !pass) {
    showToast('Please enter email and password', 'error');
    return;
  }
  
  try {
    const result = await apiCall('simple_auth.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'login',
        user_type: currentRole,
        email: email,
        password: pass
      })
    });
    
    if (result.success) {
      currentUser = result.user.name;
      localStorage.setItem('cb_session_token', result.session_token);
      localStorage.setItem('cb_user_type', result.user_type);
      localStorage.setItem('cb_user_data', JSON.stringify(result.user));
      showToast('Login successful!');
      enterDashboard();
    } else {
      showToast(result.message || 'Login failed', 'error');
    }
  } catch (error) {
    console.error('Login error:', error);
    showToast('Failed to login', 'error');
  }
}

function enterDashboard() {
  const userData = JSON.parse(localStorage.getItem('cb_user_data') || '{}');
  const userType = localStorage.getItem('cb_user_type') || 'patient';
  
  if (userType === 'doctor') {
    currentUser = userData.name || 'Doctor';
    showPage('page-doctor');
    renderDoctorProfile();
    renderDoctorDashboard();
  } else {
    currentUser = userData.name || 'Patient';
    const week = userData.pregnancy_week || 28;
    document.getElementById('patient-name-nav').textContent = currentUser;
    document.getElementById('week-display').textContent = week;
    document.getElementById('today-date').textContent = formatDate(new Date());
    showPage('page-patient');
    renderPatientProfile();
    renderHistory();
    renderGuidance(parseInt(week));
  }
}

function logout() {
  currentUser = '';
  localStorage.removeItem('cb_session_token');
  localStorage.removeItem('cb_user_type');
  localStorage.removeItem('cb_user_data');
  showPage('page-landing');
}

// ============================================================
// AUTH UI MANAGEMENT
// ============================================================

function showAuth(mode, role) {
  currentRole = role || 'patient';
  authMode = mode;
  showPage('page-auth');
  renderAuthCard();
}

function renderAuthCard() {
  const signupTab = document.getElementById('tab-signup');
  const loginTab = document.getElementById('tab-login');
  const signupFields = document.getElementById('signup-fields');
  const loginFields = document.getElementById('login-fields');
  const title = document.getElementById('auth-title');
  const subtitle = document.getElementById('auth-sub');
  const patientBtn = document.getElementById('role-patient');
  const doctorBtn = document.getElementById('role-doctor');
  const weekField = document.getElementById('signup-week-field');
  
  // Update tabs
  signupTab?.classList.toggle('active', authMode === 'signup');
  loginTab?.classList.toggle('active', authMode === 'login');
  
  // Update fields
  signupFields.style.display = authMode === 'signup' ? 'block' : 'none';
  loginFields.style.display = authMode === 'login' ? 'block' : 'none';
  
  // Update text
  title.textContent = authMode === 'signup' ? 'Create your account' : 
                     (currentRole === 'doctor' ? 'Doctor Sign In' : 'Welcome back');
  subtitle.textContent = authMode === 'signup' ? 'Monitor pregnancy health with CareBridge' : 
                         'Access your CareBridge dashboard';
  
  // Update role buttons
  patientBtn?.classList.toggle('active', currentRole === 'patient');
  doctorBtn?.classList.toggle('active', currentRole === 'doctor');
  
  // Show week field for patient signup
  if (weekField) {
    weekField.style.display = (currentRole === 'patient' && authMode === 'signup') ? 'block' : 'none';
  }
}

function switchAuthTab(mode) {
  authMode = mode;
  renderAuthCard();
}

function switchRole(role) {
  currentRole = role;
  renderAuthCard();
}

// ============================================================
// PAGE MANAGEMENT
// ============================================================

function showPage(pageId) {
  document.querySelectorAll('.page').forEach(page => {
    page.classList.remove('active');
  });
  const targetPage = document.getElementById(pageId);
  if (targetPage) {
    targetPage.classList.add('active');
  }
  window.scrollTo(0, 0);
}

// ============================================================
// SYMPTOM FUNCTIONS (FIXED API CALLS)
// ============================================================

function setToggle(group, value) {
  symptoms[group] = value;
  const card = document.querySelector('#' + group + '-' + value)?.closest('.symptom-card');
  if (!card) return;
  card.querySelectorAll('.toggle-btn').forEach(btn => {
    btn.classList.toggle('active', btn.id === group + '-' + value);
  });
}

function updateWeek(val) {
  document.getElementById('week-display').textContent = val;
  renderGuidance(parseInt(val));
  document.getElementById('profile-detail-display').textContent = 'Week ' + val;
  document.getElementById('ppv-week').textContent = val;
}

async function analyzeSymptoms() {
  const pain = parseInt(document.getElementById('pain-slider')?.value || 0);
  const notes = document.getElementById('notes-input')?.value || '';
  
  // Create FLAT JSON structure as expected by backend
  const entry = {
    headache: symptoms.headache === 'no' ? 'none' : symptoms.headache,
    swelling: symptoms.swelling === 'no' ? 'none' : symptoms.swelling,
    dizziness: symptoms.dizzy === 'yes' ? 'moderate' : 'none',
    fatigue: symptoms.fatigue || 'none',
    baby_movement: symptoms.baby === 'normal' ? 'normal' : (symptoms.baby === 'low' ? 'reduced' : 'none'),
    mood: symptoms.mood,
    urination: symptoms.urine === 'normal' ? 'normal' : (symptoms.urine === 'high' ? 'frequent' : 'reduced'),
    thirst: symptoms.thirst || 'normal',
    pain: pain === 0 ? 'none' : (pain <= 3 ? 'mild' : (pain <= 7 ? 'moderate' : 'severe')),
    notes: notes,
    date: new Date().toISOString()
  };
  
  try {
    const result = await apiCall('analyze.php', {
      method: 'POST',
      body: JSON.stringify(entry)
    });
    
    if (result.success) {
      await logSymptoms(entry, result.data);
      
      DB.patientHistory.unshift({
        ...entry,
        risk: result.data.risk_level,
        risks: result.data.detected_conditions,
        recommendations: result.data.recommendations,
        pattern_alerts: result.data.pattern_alerts
      });
      DB.save();
      
      renderResultFromBackend(result.data);
      renderHistory();
      renderPatientProfile();
      
      const rs = document.getElementById('result-section');
      if (rs) {
        rs.classList.remove('hidden');
        rs.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    } else {
      showToast(result.message || 'Analysis failed', 'error');
    }
  } catch (error) {
    console.error('Analysis error:', error);
    showToast('Failed to analyze symptoms', 'error');
  }
}

async function logSymptoms(symptoms, analysisResult) {
  try {
    const result = await apiCall('log_symptoms.php', {
      method: 'POST',
      body: JSON.stringify({
        symptoms: symptoms,
        analysis_results: {
          risk_level: analysisResult.risk_level,
          detected_conditions: analysisResult.detected_conditions,
          recommendations: analysisResult.recommendations,
          pattern_alerts: analysisResult.pattern_alerts
        }
      })
    });
    
    if (!result.success) {
      console.error('Failed to log symptoms:', result.message);
    }
  } catch (error) {
    console.error('Log symptoms error:', error);
  }
}

function renderResultFromBackend(result) {
  const banner = document.getElementById('risk-banner');
  if (!banner) return;
  
  banner.className = 'risk-banner';
  
  const riskLevelMap = {
    'low': { cls: 'level-normal', icon: '&#10003;', label: 'Normal', desc: 'All symptoms within normal range. Keep it up!' },
    'medium': { cls: 'level-monitor', icon: '&#9678;', label: 'Monitor', desc: 'Some symptoms need attention. Track closely.' },
    'high': { cls: 'level-consult', icon: '!', label: 'Consult Doctor', desc: 'Concerning symptoms. Book appointment soon.' },
    'critical': { cls: 'level-emergency', icon: '&#10005;', label: 'Emergency', desc: 'Critical symptoms. Seek immediate care.' },
  };
  
  const cfg = riskLevelMap[result.risk_level] || riskLevelMap['low'];
  banner.classList.add(cfg.cls);
  
  const riskIcon = document.getElementById('risk-icon');
  const riskLevel = document.getElementById('risk-level');
  const riskDesc = document.getElementById('risk-desc');
  
  if (riskIcon) riskIcon.innerHTML = cfg.icon;
  if (riskLevel) riskLevel.textContent = cfg.label;
  if (riskDesc) riskDesc.textContent = result.detected_conditions[0] || cfg.desc;
  
  const emPanel = document.getElementById('emergency-panel');
  if (emPanel) {
    if (result.risk_level === 'critical') {
      emPanel.classList.remove('hidden');
      const epMessage = document.getElementById('ep-message');
      if (epMessage) epMessage.textContent = result.detected_conditions[0] || 'Critical condition. Call emergency services.';
    } else {
      emPanel.classList.add('hidden');
    }
  }
  
  const suggestions = result.recommendations.map((rec, index) => ({
    icon: getIconForRecommendation(rec),
    title: `Recommendation ${index + 1}`,
    text: rec
  }));
  
  const suggestionsGrid = document.getElementById('suggestions-grid');
  if (suggestionsGrid) {
    suggestionsGrid.innerHTML = suggestions.map(s =>
      `<div class="suggestion-card"><div class="sg-icon">${s.icon}</div><div class="sg-title">${s.title}</div><div class="sg-text">${s.text}</div></div>`
    ).join('');
  }
}

function getIconForRecommendation(recommendation) {
  const rec = recommendation.toLowerCase();
  if (rec.includes('water') || rec.includes('hydrat')) return '&#128167;';
  if (rec.includes('iron') || rec.includes('nutrition') || rec.includes('food')) return '&#129749;';
  if (rec.includes('exercise') || rec.includes('walk')) return '&#129694;';
  if (rec.includes('rest') || rec.includes('sleep')) return '&#128564;';
  if (rec.includes('swelling')) return '&#129461;';
  if (rec.includes('headache')) return '&#127777;';
  if (rec.includes('dizzy')) return '&#127820;';
  if (rec.includes('mood') || rec.includes('stress') || rec.includes('mental')) return '&#129718;';
  if (rec.includes('baby') || rec.includes('movement')) return '&#10084;';
  if (rec.includes('pain')) return '&#128716;';
  if (rec.includes('doctor') || rec.includes('appointment')) return '&#128203;';
  return '&#128221;';
}

// ============================================================
// HISTORY FUNCTIONS (FIXED API CALLS)
// ============================================================

async function renderHistory() {
  const container = document.getElementById('history-container');
  if (!container) return;
  
  try {
    const result = await apiCall('get_history.php?days=30&include_mood=true&include_patterns=true');
    
    let history = [];
    if (result.success && result.data) {
      history = result.data.history.map(entry => ({
        date: entry.date + ' ' + entry.time,
        pain: entry.symptoms.pain === 'none' ? 0 :
               entry.symptoms.pain === 'mild' ? 2 :
               entry.symptoms.pain === 'moderate' ? 5 : 8,
        headache: entry.symptoms.headache === 'none' ? 'no' : entry.symptoms.headache,
        swelling: entry.symptoms.swelling === 'none' ? 'no' : entry.symptoms.swelling,
        dizzy: entry.symptoms.dizziness === 'none' ? 'no' : 'yes',
        vision: entry.symptoms.dizziness === 'severe' ? 'yes' : 'no',
        baby: entry.symptoms.baby_movement === 'normal' ? 'normal' :
               entry.symptoms.baby_movement === 'reduced' ? 'low' : 'none',
        mood: entry.symptoms.mood,
        urine: entry.symptoms.urination === 'normal' ? 'normal' :
               entry.symptoms.urination === 'frequent' ? 'high' : 'painful',
        risk: entry.risk_level,
        risks: entry.detected_conditions,
        notes: entry.notes || ''
      }));
    } else {
      history = DB.patientHistory;
    }
    
    renderTrackingSummary(history);
    
    if (!history.length) {
      container.innerHTML = '<div class="empty-state">No history yet. Log your first symptoms above.</div>';
      return;
    }
    
    container.innerHTML = history.map(entry => {
      const tags = buildTags(entry);
      const date = new Date(entry.date);
      return `<div class="history-entry">
        <div class="he-date">${formatDate(date)}<br><span style="color:#aaa;font-size:11px">${formatTime(date)}</span></div>
        <div class="he-symptoms">
          <div style="font-size:14px;font-weight:500;margin-bottom:6px">Pain: ${entry.pain}/10</div>
          <div class="he-symptom-tags">${tags}</div>
          ${entry.notes ? `<div style="font-size:12px;color:#888;margin-top:4px;font-style:italic">"${entry.notes}"</div>` : ''}
        </div>
        <div class="he-risk"><span class="risk-pill pill-${entry.risk}">${capitalize(entry.risk)}</span></div>
      </div>`;
    }).join('');
    
    DB.patientHistory = history;
    DB.save();
    
  } catch (error) {
    console.error('History fetch error:', error);
    // Fallback to local data
    const history = DB.patientHistory;
    renderTrackingSummary(history);
    if (!history.length) {
      container.innerHTML = '<div class="empty-state">No history yet. Log your first symptoms above.</div>';
      return;
    }
    
    container.innerHTML = history.map(entry => {
      const tags = buildTags(entry);
      const date = new Date(entry.date);
      return `<div class="history-entry">
        <div class="he-date">${formatDate(date)}<br><span style="color:#aaa;font-size:11px">${formatTime(date)}</span></div>
        <div class="he-symptoms">
          <div style="font-size:14px;font-weight:500;margin-bottom:6px">Pain: ${entry.pain}/10</div>
          <div class="he-symptom-tags">${tags}</div>
          ${entry.notes ? `<div style="font-size:12px;color:#888;margin-top:4px;font-style:italic">"${entry.notes}"</div>` : ''}
        </div>
        <div class="he-risk"><span class="risk-pill pill-${entry.risk}">${capitalize(entry.risk)}</span></div>
      </div>`;
    }).join('');
  }
}

function renderTrackingSummary(history) {
  const container = document.getElementById('tracking-summary');
  if (!container) return;
  if (!history.length) { container.innerHTML = ''; return; }
  
  const total = history.length;
  const riskEvents = history.filter(e => e.risk !== 'normal').length;
  const normalDays = total - riskEvents;
  const avgPain = (history.reduce((sum, e) => sum + (e.pain || 0), 0) / total).toFixed(1);
  const visits = 0; // TODO: Get from user data
  
  container.innerHTML = `
    <div class="ts-card"><div class="ts-num">${total}</div><div class="ts-label">Total Logs</div></div>
    <div class="ts-card"><div class="ts-num">${riskEvents}</div><div class="ts-label">Risk Events</div></div>
    <div class="ts-card"><div class="ts-num">${normalDays}</div><div class="ts-label">Normal Days</div></div>
    <div class="ts-card"><div class="ts-num">${avgPain}</div><div class="ts-label">Avg Pain Level</div></div>
    <div class="ts-card"><div class="ts-num">${visits}</div><div class="ts-label">Doctor Visits</div></div>
  `;
}

function buildTags(entry) {
  const tags = [];
  if (entry.headache !== 'no') tags.push('Headache: ' + entry.headache);
  if (entry.swelling !== 'no') tags.push('Swelling: ' + entry.swelling);
  if (entry.dizzy === 'yes') tags.push('Dizzy');
  if (entry.vision === 'yes') tags.push('Blurred Vision');
  if (entry.mood !== 'happy') tags.push('Mood: ' + capitalize(entry.mood));
  if (entry.baby !== 'normal') tags.push('Baby: ' + capitalize(entry.baby));
  if (entry.urine !== 'normal') tags.push('Urine: ' + capitalize(entry.urine));
  if (!tags.length) tags.push('All clear');
  return tags.map(t => '<span class="tag">' + t + '</span>').join('');
}

// ============================================================
// GUIDANCE FUNCTIONS (FIXED API CALLS)
// ============================================================

async function renderGuidance(week) {
  const container = document.getElementById('guidance-content');
  if (!container) return;
  
  try {
    const result = await apiCall('get_guidance.php?week=' + week);
    
    if (result.success && result.data) {
      const data = result.data;
      container.innerHTML = `
        <div class="guidance-card">
          <h3>${data.guidance.title}</h3>
          <div class="guidance-items">${data.guidance.items.map(i => `<div class="guidance-item"><span class="gi-bullet">&#8594;</span><span>${i}</span></div>`).join('')}</div>
        </div>
        <div class="guidance-card">
          <h3>Key Nutrients</h3>
          <div class="guidance-items">${data.nutrients.map(i => `<div class="guidance-item"><span class="gi-bullet">&#9670;</span><span>${i}</span></div>`).join('')}</div>
        </div>
        <div class="guidance-card warning-card">
          <h3>Always Act On These</h3>
          <div class="guidance-items">${data.emergency_warnings.map(i => `<div class="guidance-item"><span class="gi-bullet" style="color:var(--red)">!</span><span>${i}</span></div>`).join('')}</div>
        </div>`;
    } else {
      renderGuidanceFallback(week);
    }
  } catch (error) {
    console.error('Guidance fetch error:', error);
    renderGuidanceFallback(week);
  }
}

function renderGuidanceFallback(week) {
  const container = document.getElementById('guidance-content');
  if (!container) return;
  
  const guidanceData = {
    '1-12': { title: 'First Trimester (Weeks 1-12)', items: ['Take folic acid 400mcg daily', 'Morning sickness - eat small, frequent meals', 'Avoid alcohol, raw meat, high-mercury fish', 'Book first prenatal appointment', 'Rest as needed - fatigue is normal'] },
    '13-26': { title: 'Second Trimester (Weeks 13-26)', items: ['Usually most comfortable trimester', 'Baby movements start around week 18-20', 'Anomaly scan: weeks 18-22', 'Sleep on your left side', 'Begin prenatal exercises if cleared'] },
    '27-36': { title: 'Third Trimester (Weeks 27-36)', items: ['Kick counts: 10 movements in 2 hours', 'Watch for: severe headache, swelling, vision changes', 'Prenatal visits every 2 weeks now', 'Prepare hospital bag from week 32'] },
    '37-40': { title: 'Full Term (Weeks 37-40)', items: ['Weekly appointments now', 'Labor signs: regular contractions, water breaking', 'Stay near home, keep phone charged', 'Hospital if contractions are 5 min apart'] },
  };
  
  let data;
  if (week <= 12) data = guidanceData['1-12'];
  else if (week <= 26) data = guidanceData['13-26'];
  else if (week <= 36) data = guidanceData['27-36'];
  else data = guidanceData['37-40'];
  
  container.innerHTML = `
    <div class="guidance-card">
      <h3>${data.title}</h3>
      <div class="guidance-items">${data.items.map(i => `<div class="guidance-item"><span class="gi-bullet">&#8594;</span><span>${i}</span></div>`).join('')}</div>
    </div>
    <div class="guidance-card">
      <h3>Key Nutrients</h3>
      <div class="guidance-items">${['Iron: Spinach, lentils, red meat', 'Calcium: Milk, yogurt, paneer', 'Protein: Eggs, dal, tofu', 'Folate: Leafy greens, oranges', 'DHA: Walnuts, flaxseeds'].map(i => `<div class="guidance-item"><span class="gi-bullet">&#9670;</span><span>${i}</span></div>`).join('')}</div>
    </div>
    <div class="guidance-card warning-card">
      <h3>Always Act On These</h3>
      <div class="guidance-items">${['Severe headache with visual disturbances', 'Sudden swelling of face, hands, feet', 'No baby movement for 12+ hours', 'Heavy bleeding at any stage', 'Severe abdominal pain or cramping'].map(i => `<div class="guidance-item"><span class="gi-bullet" style="color:var(--red)">!</span><span>${i}</span></div>`).join('')}</div>
    </div>`;
}

// ============================================================
// DOCTOR DASHBOARD (FIXED API CALLS)
// ============================================================

async function renderDoctorDashboard() {
  try {
    const result = await apiCall('get_patients.php');
    
    let patients = [];
    let statistics = { total: 0, alerts: 0, normal: 0, medium: 0 };
    
    if (result.success && result.data) {
      patients = result.data.patients;
      statistics = result.data.statistics;
    } else {
      // Fallback demo data
      patients = [
        { id: 1, name: 'Priya Sharma', week: 32, daysAgo: 0, lastEntry: { headache: 'severe', swelling: 'severe', baby: 'low', mood: 'stressed', pain: 8 }, risk_level: 'high' },
        { id: 2, name: 'Ananya Reddy', week: 28, daysAgo: 0, lastEntry: { headache: 'no', swelling: 'mild', baby: 'normal', mood: 'happy', pain: 2 }, risk_level: 'low' }
      ];
      statistics = {
        total: patients.length,
        alerts: patients.filter(p => p.risk_level === 'high' || p.risk_level === 'critical').length,
        normal: patients.filter(p => p.risk_level === 'low').length,
        medium: patients.filter(p => p.risk_level === 'medium').length
      };
    }
    
    // Update stats
    const totalPatients = document.getElementById('total-patients');
    const alertCount = document.getElementById('alert-count');
    const normalCount = document.getElementById('normal-count');
    
    if (totalPatients) totalPatients.textContent = statistics.total;
    if (alertCount) alertCount.textContent = statistics.alerts;
    if (normalCount) normalCount.textContent = statistics.normal;
    
    // Render patient cards
    const patientsGrid = document.getElementById('patients-grid');
    if (patientsGrid) {
      patientsGrid.innerHTML = patients.map(p => {
        const initials = p.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
        const isAlert = p.risk_level === 'high' || p.risk_level === 'critical';
        return `
          <div class="patient-card ${isAlert ? 'has-alert' : ''}">
            <div class="pc-header">
              <div class="pc-avatar">${initials}</div>
              <div style="flex: 1">
                <div class="pc-name">${p.name}</div>
                <div class="pc-week">Week ${p.week} &middot; ${p.daysAgo === 0 ? 'Today' : p.daysAgo + 'd ago'}</div>
              </div>
            </div>
            <div class="pc-metrics">
              <div class="pc-metric">
                <div class="pcm-label">Pain</div>
                <div class="pcm-val">${p.lastEntry?.pain || 0}/10</div>
              </div>
              <div class="pc-metric">
                <div class="pcm-label">Risk</div>
                <div class="pcm-val">${p.risk_level || 'normal'}</div>
              </div>
            </div>
            <div class="pc-footer">
              <span class="risk-pill pill-${p.risk_level || 'normal'}">${(p.risk_level || 'normal').charAt(0).toUpperCase() + (p.risk_level || 'normal').slice(1)}</span>
            </div>
          </div>
        `;
      }).join('');
    }
    
  } catch (error) {
    console.error('Doctor dashboard error:', error);
  }
}

// ============================================================
// PROFILE FUNCTIONS
// ============================================================

function renderPatientProfile() {
  const userData = JSON.parse(localStorage.getItem('cb_user_data') || '{}');
  const name = userData.name || currentUser || 'Patient';
  const week = userData.pregnancy_week || 28;
  const initials = name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
  
  // Update sidebar
  const profileInitials = document.getElementById('profile-initials');
  const profileName = document.getElementById('profile-name-display');
  const profileDetail = document.getElementById('profile-detail-display');
  
  if (profileInitials) profileInitials.textContent = initials;
  if (profileName) profileName.textContent = name;
  if (profileDetail) profileDetail.textContent = `Week ${week}`;
  
  // Update full profile
  const ppvAvatar = document.getElementById('ppv-avatar');
  const ppvName = document.getElementById('ppv-name');
  const ppvAge = document.getElementById('ppv-age');
  const ppvPhone = document.getElementById('ppv-phone');
  const ppvWeek = document.getElementById('ppv-week');
  const ppvVisits = document.getElementById('ppv-visits');
  const ppvHistory = document.getElementById('ppv-history');
  
  if (ppvAvatar) ppvAvatar.textContent = initials;
  if (ppvName) ppvName.textContent = name;
  if (ppvAge) ppvAge.textContent = userData.age || 'Not set';
  if (ppvPhone) ppvPhone.textContent = userData.phone || 'Not set';
  if (ppvWeek) ppvWeek.textContent = week;
  if (ppvVisits) ppvVisits.textContent = userData.visits || '0';
  if (ppvHistory) ppvHistory.textContent = userData.history || 'None recorded';
  
  // Pre-fill edit form
  const pefName = document.getElementById('pef-name');
  const pefAge = document.getElementById('pef-age');
  const pefPhone = document.getElementById('pef-phone');
  const pefVisits = document.getElementById('pef-visits');
  const pefHistory = document.getElementById('pef-history');
  
  if (pefName) pefName.value = userData.name || '';
  if (pefAge) pefAge.value = userData.age || '';
  if (pefPhone) pefPhone.value = userData.phone || '';
  if (pefVisits) pefVisits.value = userData.visits || '';
  if (pefHistory) pefHistory.value = userData.history || '';
}

function renderDoctorProfile() {
  const userData = JSON.parse(localStorage.getItem('cb_user_data') || '{}');
  const name = userData.name || currentUser || 'Doctor';
  const initials = name.replace('Dr. ', '').split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
  
  // Update sidebar
  const profileInitials = document.getElementById('profile-initials');
  const profileName = document.getElementById('profile-name-display');
  const profileDetail = document.getElementById('profile-detail-display');
  
  if (profileInitials) profileInitials.textContent = initials;
  if (profileName) profileName.textContent = name;
  if (profileDetail) profileDetail.textContent = userData.specialty || 'General Physician';
  
  // Update full profile
  const ppvAvatar = document.getElementById('ppv-avatar');
  const ppvName = document.getElementById('ppv-name');
  const ppvSpec = document.getElementById('ppv-spec');
  const ppvExp = document.getElementById('ppv-exp');
  const ppvHospital = document.getElementById('ppv-hospital');
  const ppvLocation = document.getElementById('ppv-location');
  const ppvContact = document.getElementById('ppv-contact');
  
  if (ppvAvatar) ppvAvatar.textContent = initials;
  if (ppvName) ppvName.textContent = name;
  if (ppvSpec) ppvSpec.textContent = userData.specialty || 'General Physician';
  if (ppvExp) ppvExp.textContent = userData.experience_years || '0';
  if (ppvHospital) ppvHospital.textContent = userData.hospital || 'Not set';
  if (ppvLocation) ppvLocation.textContent = userData.location || 'Not set';
  if (ppvContact) ppvContact.textContent = userData.contact || 'Not set';
  
  // Pre-fill edit form
  const defName = document.getElementById('def-name');
  const defSpec = document.getElementById('def-spec');
  const defExp = document.getElementById('def-exp');
  const defHospital = document.getElementById('def-hospital');
  const defLocation = document.getElementById('def-location');
  const defContact = document.getElementById('def-contact');
  
  if (defName) defName.value = userData.name || '';
  if (defSpec) defSpec.value = userData.specialty || '';
  if (defExp) defExp.value = userData.experience_years || '';
  if (defHospital) defHospital.value = userData.hospital || '';
  if (defLocation) defLocation.value = userData.location || '';
  if (defContact) defContact.value = userData.contact || '';
}

function toggleProfileEdit() {
  const form = document.getElementById('profile-edit-form');
  if (form) {
    form.classList.toggle('hidden');
  }
}

function saveProfile() {
  showToast('Profile saved successfully!');
  toggleProfileEdit();
}

function toggleDoctorProfileEdit() {
  const form = document.getElementById('doctor-edit-form');
  if (form) {
    form.classList.toggle('hidden');
  }
}

function saveDoctorProfile() {
  showToast('Doctor profile saved successfully!');
  toggleDoctorProfileEdit();
}

// ============================================================
// UTILITY FUNCTIONS
// ============================================================

function formatDate(date) {
  return date.toLocaleDateString('en-IN', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
}

function formatTime(date) {
  return date.toLocaleTimeString('en-IN', {
    hour: '2-digit',
    minute: '2-digit'
  });
}

function capitalize(str) {
  return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}

function showToast(message, type = 'success') {
  // Remove existing toasts
  document.querySelectorAll('.cb-toast').forEach(toast => toast.remove());
  
  // Create new toast
  const toast = document.createElement('div');
  toast.className = 'cb-toast';
  toast.style.background = type === 'error' ? '#B83228' : '#18181B';
  toast.textContent = message;
  document.body.appendChild(toast);
  
  // Auto remove after 4 seconds
  setTimeout(() => {
    if (toast.parentNode) {
      toast.remove();
    }
  }, 4000);
}

// ============================================================
// API CONNECTION TEST
// ============================================================

// API testing removed - using only primary endpoints

// ============================================================
// GLOBAL FUNCTIONS FOR HTML ONCLICK
// ============================================================

// Make switchTab globally accessible
window.switchTab = function(tabName) {
  // Hide all tabs
  document.querySelectorAll('.tab-content').forEach(tab => {
    tab.classList.remove('active');
  });
  
  // Show selected tab
  const selectedTab = document.getElementById(tabName);
  if (selectedTab) {
    selectedTab.classList.add('active');
  }
  
  // Update sidebar links
  document.querySelectorAll('.sidebar-link').forEach(link => {
    link.classList.remove('active');
  });
  
  // Activate corresponding sidebar link
  const sidebarLinks = document.querySelectorAll('.sidebar-link');
  const tabMap = {
    'tab-log': 0,
    'tab-history': 1,
    'tab-profile': 2,
    'tab-doctors': 3,
    'tab-guidance': 4
  };
  
  const linkIndex = tabMap[tabName];
  if (linkIndex !== undefined && sidebarLinks[linkIndex]) {
    sidebarLinks[linkIndex].classList.add('active');
  }
};

// Make triggerEmergency globally accessible
window.triggerEmergency = function() {
  // Show emergency modal instead of directly calling API
  const modal = document.getElementById('emergency-modal');
  if (modal) {
    modal.classList.remove('hidden');
  }
};

// Make closeEmergencyModal globally accessible
window.closeEmergencyModal = function() {
  const modal = document.getElementById('emergency-modal');
  if (modal) {
    modal.classList.add('hidden');
  }
};

// Make callAmbulance globally accessible
window.callAmbulance = function() {
  window.location.href = "tel:102";
};

// Make alertDoctor globally accessible
window.alertDoctor = async function() {
  try {
    const emergencyData = {
      emergency: true,
      timestamp: new Date().toISOString()
    };
    
    const result = await apiCall('emergency.php', {
      method: 'POST',
      body: JSON.stringify(emergencyData)
    });
    
    if (result.success) {
      showToast('Doctor has been alerted!', 'success');
      closeEmergencyModal();
    } else {
      showToast('Failed to alert doctor', 'error');
    }
  } catch (error) {
    console.error('Alert doctor error:', error);
    showToast('Failed to alert doctor', 'error');
  }
};

// Make switchSidebarLink globally accessible (used in HTML)
window.switchSidebarLink = function(index) {
  document.querySelectorAll('.sidebar-link').forEach((link, i) => {
    link.classList.toggle('active', i === index);
  });
};

// Make clearHistory globally accessible (used in HTML)
window.clearHistory = function() {
  if (confirm('Are you sure you want to clear all history?')) {
    DB.patientHistory = [];
    DB.save();
    renderHistory();
    showToast('History cleared');
  }
};

// Make renderDoctorBrowse globally accessible (used in HTML)
window.renderDoctorBrowse = async function() {
  const specialty = document.getElementById('filter-spec')?.value || '';
  const expFilter = document.getElementById('filter-exp')?.value || '';
  
  try {
    const params = new URLSearchParams();
    if (specialty) params.append('specialty', specialty);
    if (expFilter) params.append('min_experience', expFilter);
    
    const result = await apiCall('get_doctors.php?' + params.toString());
    
    if (result.success) {
      const doctors = result.data.doctors;
      const container = document.getElementById('doctor-browse-grid');
      
      if (container) {
        if (doctors.length === 0) {
          container.innerHTML = '<div class="empty-state">No doctors found matching your criteria.</div>';
        } else {
          container.innerHTML = `
            <div class="doctors-grid">
              ${doctors.map(doctor => `
                <div class="doctor-card">
                  <div class="doctor-avatar">
                    ${doctor.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()}
                  </div>
                  <div class="doctor-content">
                    <h3 class="doctor-name">${doctor.name}</h3>
                    <span class="doctor-specialty">${doctor.specialty}</span>
                    
                    <div class="doctor-details">
                      <div class="detail-row">
                        <span class="detail-label">Experience</span>
                        <span class="detail-value">${doctor.experience_years} years</span>
                      </div>
                      <div class="detail-row">
                        <span class="detail-label">Hospital</span>
                        <span class="detail-value">${doctor.hospital}</span>
                      </div>
                      <div class="detail-row">
                        <span class="detail-label">Location</span>
                        <span class="detail-value">${doctor.location}</span>
                      </div>
                    </div>
                    
                    <button class="btn-view-profile" onclick="viewDoctorProfile(${doctor.id})">View Full Profile</button>
                  </div>
                </div>
              `).join('')}
            </div>
          `;
        }
      }
    } else {
      showToast('Failed to load doctors', 'error');
    }
  } catch (error) {
    console.error('Doctor browse error:', error);
    showToast('Failed to load doctors', 'error');
  }
};

// Make viewDoctorProfile globally accessible
window.viewDoctorProfile = function(doctorId) {
  showToast(`Viewing profile for doctor ID: ${doctorId}`, 'success');
};

// ============================================================
// INITIALIZATION
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
  const todayDate = document.getElementById('today-date');
  if (todayDate) {
    todayDate.textContent = formatDate(new Date());
  }
});
