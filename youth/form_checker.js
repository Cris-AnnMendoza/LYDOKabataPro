/**
 * Client-side "Check My Submission First" panel
 * Works for accreditation, volunteer, and scholarship forms
 *
 * Usage:
 *   FormChecker.init({
 *     formId:       'myForm',
 *     checkBtnId:   'checkBtn',
 *     submitBtnId:  'submitBtn',
 *     panelId:      'checkPanel',
 *     requiredFields: [ { id:'field_id', label:'Field Label' }, ... ],
 *     requiredFiles:  [ { id:'file_input_id', label:'Document Name' }, ... ],
 *   });
 */

const FormChecker = (() => {

  function checkFields(requiredFields) {
    const issues  = [];
    const passed  = [];
    requiredFields.forEach(f => {
      const el  = document.getElementById(f.id);
      const val = el ? el.value.trim() : '';
      if (!val) {
        issues.push({ id: f.id, label: f.label, msg: `<strong>${f.label}</strong> is required.` });
      } else {
        passed.push(f.label);
      }
    });
    return { issues, passed };
  }

  function checkFiles(requiredFiles, sessionUploaded) {
    const issues   = [];
    const uploaded = [];
    requiredFiles.forEach(f => {
      const el = document.getElementById(f.id);
      // Check actual current file input state OR previously tracked
      const hasFile = (el && el.files && el.files.length > 0) || sessionUploaded.includes(f.id);
      if (!hasFile) {
        issues.push({ id: f.id, label: f.label, msg: `Missing: <strong>${f.label}</strong>` });
      } else {
        uploaded.push(f.id);
      }
    });
    return { issues, uploaded };
  }

  function calcScore(fieldResult, fileResult, totalFields, totalFiles) {
    const fScore = totalFields > 0 ? Math.round((fieldResult.passed.length / totalFields) * 60) : 60;
    const dScore = totalFiles  > 0 ? Math.round((fileResult.uploaded.length / totalFiles) * 40) : 40;
    const total  = fScore + dScore;
    const canSubmit = fieldResult.issues.length === 0 && fileResult.issues.length === 0;
    let color, label, icon;
    if (total >= 90) { color = '#2e7d32'; label = 'Ready to Submit'; icon = 'fa-check-circle'; }
    else if (total >= 70) { color = '#1565c0'; label = 'Almost Ready'; icon = 'fa-info-circle'; }
    else if (total >= 50) { color = '#f57f17'; label = 'Needs More Info'; icon = 'fa-exclamation-triangle'; }
    else { color = '#c62828'; label = 'Incomplete'; icon = 'fa-times-circle'; }
    return { score: total, color, label, icon, canSubmit };
  }

  function renderPanel(panelId, fieldResult, fileResult, scoreResult, requiredFiles, sessionUploaded) {
    const panel = document.getElementById(panelId);
    if (!panel) return;
    const { score, color, label, icon, canSubmit } = scoreResult;

    const docRows = requiredFiles.map(f => {
      const hasIssue = fileResult.issues.some(i => i.id === f.id);
      const uploaded = fileResult.uploaded.includes(f.id);
      if (hasIssue) return `<div style="display:flex;align-items:center;gap:7px;padding:3px 0;font-size:.8rem"><i class="fas fa-times-circle" style="color:#c62828;width:14px"></i><span style="color:#c62828">${f.label}</span></div>`;
      if (uploaded) return `<div style="display:flex;align-items:center;gap:7px;padding:3px 0;font-size:.8rem"><i class="fas fa-check-circle" style="color:#2e7d32;width:14px"></i><span style="color:#2e7d32">${f.label}</span></div>`;
      return `<div style="display:flex;align-items:center;gap:7px;padding:3px 0;font-size:.8rem"><i class="fas fa-circle" style="color:#94a3b8;width:14px;font-size:.4rem"></i><span style="color:#94a3b8">${f.label}</span></div>`;
    }).join('');

    const fieldIssueRows = fieldResult.issues.map(i =>
      `<div style="display:flex;align-items:flex-start;gap:6px;padding:3px 0;font-size:.8rem;color:#c62828"><i class="fas fa-times-circle" style="margin-top:2px;flex-shrink:0"></i><span>${i.msg}</span></div>`
    ).join('');

    const passedText = fieldResult.passed.length
      ? `<div style="margin-top:8px;font-size:.72rem;color:#2e7d32"><strong>✓</strong> ${fieldResult.passed.slice(0,5).join(', ')}${fieldResult.passed.length > 5 ? ' +' + (fieldResult.passed.length - 5) + ' more' : ''}</div>`
      : '';

    const resultBar = canSubmit
      ? `<div style="padding:10px 18px;background:#e8f5e9;font-size:.83rem;color:#2e7d32;border-top:1px solid #e2e8f0;font-weight:600"><i class="fas fa-check-circle"></i> <strong>Ready to submit!</strong> All checks passed.</div>`
      : `<div style="padding:10px 18px;background:#ffebee;font-size:.83rem;color:#c62828;border-top:1px solid #e2e8f0;font-weight:600"><i class="fas fa-lock"></i> <strong>Not ready.</strong> Fix the issues above before submitting.</div>`;

    panel.innerHTML = `
      <div style="border:1.5px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:16px">
        <div style="padding:12px 18px;background:linear-gradient(135deg,#0d3b6e,#1565c0);color:#fff;display:flex;align-items:center;justify-content:space-between">
          <span style="font-weight:700;font-size:.9rem"><i class="fas fa-robot" style="margin-right:7px"></i>Submission Check</span>
          <span style="background:${color};color:#fff;padding:3px 12px;border-radius:50px;font-size:.78rem;font-weight:700"><i class="fas ${icon}"></i> ${label} — ${score}%</span>
        </div>
        <div style="padding:12px 18px;border-bottom:1px solid #e2e8f0;background:#f8fafc">
          <div style="display:flex;justify-content:space-between;font-size:.78rem;color:#475569;margin-bottom:5px"><span>Completeness</span><span style="font-weight:700;color:${color}">${score}%</span></div>
          <div style="height:9px;background:#e2e8f0;border-radius:50px;overflow:hidden"><div style="height:100%;width:${score}%;background:${color};border-radius:50px;transition:width .6s ease"></div></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0">
          <div style="padding:14px 18px;border-right:1px solid #e2e8f0">
            <div style="font-size:.78rem;font-weight:700;color:#1e293b;margin-bottom:10px"><i class="fas fa-list-check" style="color:#1565c0;margin-right:5px"></i>Form Fields</div>
            ${fieldIssueRows || '<div style="color:#2e7d32;font-size:.82rem"><i class="fas fa-check-circle"></i> All required fields filled</div>'}
            ${passedText}
          </div>
          <div style="padding:14px 18px">
            <div style="font-size:.78rem;font-weight:700;color:#1e293b;margin-bottom:10px"><i class="fas fa-paperclip" style="color:#1565c0;margin-right:5px"></i>Documents</div>
            ${docRows}
          </div>
        </div>
        ${resultBar}
      </div>`;

    panel.style.display = 'block';
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function init(config) {
    const {
      formId, checkBtnId, submitBtnId, panelId,
      requiredFields = [], requiredFiles = [],
      sessionUploaded = [],
    } = config;

    const checkBtn  = document.getElementById(checkBtnId);
    const submitBtn = document.getElementById(submitBtnId);

    if (!checkBtn) return;

    // Track which files have been selected in this session
    const localUploaded = [...sessionUploaded];
    requiredFiles.forEach(f => {
      const el = document.getElementById(f.id);
      if (el) {
        el.addEventListener('change', () => {
          if (el.files && el.files.length > 0 && !localUploaded.includes(f.id)) {
            localUploaded.push(f.id);
          }
        });
      }
    });

    checkBtn.addEventListener('click', (e) => {
      e.preventDefault();
      // Rebuild uploaded list from actual current file input states
      requiredFiles.forEach(f => {
        const el = document.getElementById(f.id);
        if (el && el.files && el.files.length > 0 && !localUploaded.includes(f.id)) {
          localUploaded.push(f.id);
        }
      });
      const fieldResult = checkFields(requiredFields);
      const fileResult  = checkFiles(requiredFiles, localUploaded);
      const scoreResult = calcScore(fieldResult, fileResult, requiredFields.length, requiredFiles.length);
      renderPanel(panelId, fieldResult, fileResult, scoreResult, requiredFiles, localUploaded);

      // Enable/disable submit
      if (submitBtn) {
        submitBtn.disabled = !scoreResult.canSubmit;
        submitBtn.style.opacity = scoreResult.canSubmit ? '1' : '0.5';
        submitBtn.style.cursor  = scoreResult.canSubmit ? 'pointer' : 'not-allowed';
      }
    });

    // Re-run check live when fields change (debounced)
    let debounce;
    const panel = document.getElementById(panelId);
    if (panel && panel.innerHTML.trim()) {
      requiredFields.forEach(f => {
        const el = document.getElementById(f.id);
        if (el) el.addEventListener('input', () => {
          clearTimeout(debounce);
          debounce = setTimeout(() => checkBtn.click(), 400);
        });
      });
    }
  }

  return { init };
})();
