<?php get_header(); ?>

<div class="page-hero">
  <div class="page-hero-bg"></div>
  <div class="joint" style="top:80px;left:100px;"></div>
  <div class="joint" style="top:80px;right:100px;"></div>
  <div class="joint" style="bottom:40px;left:200px;"></div>
  <div class="joint" style="bottom:40px;right:200px;"></div>
  <div class="hero-emblem">
    <div class="hero-emblem-ring"></div>
    <svg viewBox="0 0 24 24" fill="none" stroke="#e8890a" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 2 L20 5 V11 C20 16 16.5 20 12 22 C7.5 20 4 16 4 11 V5 Z"/>
      <path d="M8.5 12 L11 14.5 L16 9" stroke="#ffb547" stroke-width="2"/>
    </svg>
  </div>
  <div class="page-hero-label">Certificate Portal</div>
  <h1>VERIFY A<br/><span class="dim">CERTIFICATE</span></h1>
  <p>Instantly confirm the authenticity of a Radian training certificate, or check a CISRS card through the official NOCN portal.</p>
</div>

<div id="root"></div>

<script type="text/babel">
const { useState, useRef, useEffect } = React;

/* ── Certificate lookup — calls the WordPress AJAX endpoint ── */
async function certLookup(lastName, certNo) {
  const { ajaxUrl, nonce } = window.RADIAN_CERT || {};
  if (!ajaxUrl) throw new Error('config');
  const body = new URLSearchParams({ action:'radian_cert_lookup', nonce, last_name:lastName, cert_no:certNo });
  const res  = await fetch(ajaxUrl, { method:'POST', body });
  const json = await res.json();
  if (json.success) return json.data;
  throw new Error(json.data?.message || 'not_found');
}

const STATUS_LABEL = { valid:'✓ Valid', expired:'✗ Expired', soon:'⚠ Expiring Soon' };

/* ── Rotating Seal ── */
function Seal() {
  return (
    <div className="cred-seal">
      <svg className="cred-seal-ring" viewBox="0 0 96 96">
        <defs>
          <path id="sealPath" d="M48,48 m-38,0 a38,38 0 1,1 76,0 a38,38 0 1,1 -76,0"/>
        </defs>
        <circle cx="48" cy="48" r="46" fill="none" stroke="rgba(34,201,122,0.3)" strokeWidth="1"/>
        <circle cx="48" cy="48" r="38" fill="none" stroke="rgba(34,201,122,0.2)" strokeWidth="1"/>
        <text className="cred-seal-text">
          <textPath href="#sealPath" startOffset="0">
            ★ VERIFIED AUTHENTIC ★ RADIAN H.A. LIMITED ★ VERIFIED ★ RADIAN ★
          </textPath>
        </text>
      </svg>
      <div className="cred-seal-core">✓</div>
    </div>
  );
}

/* ── Digital Credential ── */
function Credential({ data, onReset, onRequest }) {
  const [fillW, setFillW] = useState('8%');
  useEffect(() => {
    const target = data.expiryStatus === 'expired' ? '92%' : data.expiryStatus === 'soon' ? '70%' : '46%';
    const t = setTimeout(() => setFillW(target), 200);
    return () => clearTimeout(t);
  }, [data]);

  const now = new Date();
  const verifiedOn = now.toLocaleDateString('en-GB', {day:'numeric', month:'short', year:'numeric'}) + ' · ' + now.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});

  return (
    <div>
      <div className="credential">
        <div className="cred-watermark">VERIFIED</div>
        <div className="cred-topbar">
          <div className="cred-brand">
            <div className="mark">R</div>
            <div className="name">RADIAN H.A. LIMITED</div>
          </div>
          <div className="cred-verified-tag">
            <span className="tick">✓</span>
            <span>Verified Authentic</span>
          </div>
        </div>

        <Seal/>

        <div className="cred-body">
          <div className="cred-intro">Certificate of Training</div>
          <div className="cred-cert-type">{data.results.toLowerCase().includes('pass') ? 'Successfully Completed' : 'Record of Attendance'}</div>

          <div className="cred-presented">This certifies that</div>
          <div className="cred-name">{data.firstName} {data.lastName}</div>
          <div className="cred-completed">has successfully completed</div>
          <div className="cred-course">{data.courseName}</div>

          <div className="cred-details">
            <div className="cred-detail">
              <div className="cred-detail-label">CISRS Student ID</div>
              <div className="cred-detail-value">{data.cisrsStudentId}</div>
            </div>
            <div className="cred-detail">
              <div className="cred-detail-label">Certificate No.</div>
              <div className="cred-detail-value mono">{data.certificateNo}</div>
            </div>
            <div className="cred-detail">
              <div className="cred-detail-label">Result</div>
              <div className="cred-detail-value pass">{data.results}</div>
            </div>
            <div className="cred-detail">
              <div className="cred-detail-label">Instructor</div>
              <div className="cred-detail-value">{data.instructorName}</div>
            </div>
            <div className="cred-detail">
              <div className="cred-detail-label">Instructor No.</div>
              <div className="cred-detail-value">{data.instructorNo}</div>
            </div>
            <div className="cred-detail">
              <div className="cred-detail-label">Venue</div>
              <div className="cred-detail-value" style={{fontSize:'0.82rem'}}>{data.venue}</div>
            </div>
          </div>

          <div className="cred-validity">
            <div className="cred-validity-head">
              <div className="cred-validity-title">Validity Period</div>
              <div className={`cred-status-badge ${data.expiryStatus}`}>{STATUS_LABEL[data.expiryStatus]}</div>
            </div>
            <div className="timeline">
              <div className="timeline-fill" style={{width: fillW}}></div>
              <div className="timeline-node done">
                <div className="timeline-dot"></div>
                <div className="timeline-label">Start</div>
                <div className="timeline-date">{data.startDate}</div>
              </div>
              <div className="timeline-node end">
                <div className="timeline-dot"></div>
                <div className="timeline-label">Completed</div>
                <div className="timeline-date">{data.endDate}</div>
              </div>
              <div className="timeline-node expiry">
                <div className="timeline-dot"></div>
                <div className="timeline-label">Expires</div>
                <div className="timeline-date">{data.expiryDate}</div>
              </div>
            </div>
          </div>
        </div>

        <div className="cred-footer">
          <div className="cred-footer-item">
            <div className="cred-footer-label">Verified On</div>
            <div className="cred-footer-value">{verifiedOn}</div>
          </div>
          <div className="cred-footer-item">
            <div className="cred-footer-label">Issuing Body</div>
            <div className="cred-footer-value">CISRS Accredited · Radian H.A. Ltd</div>
          </div>
          <div className="cred-qr" title="Verification code"></div>
        </div>
      </div>

      <div className="result-actions">
        <button className="btn-sm btn-sm-primary" onClick={()=>window.print()}>🖨 Print / Save PDF</button>
        <button className="btn-sm btn-sm-outline" onClick={onReset}>Verify Another</button>
        <button className="btn-sm btn-sm-outline" onClick={onRequest}>Request Copy</button>
      </div>
    </div>
  );
}

/* ── Radian Verify Panel ── */
function RadianPanel({ onRequest }) {
  const [lastName, setLastName] = useState('');
  const [certNo, setCertNo] = useState('');
  const [status, setStatus] = useState('idle');
  const [result, setResult] = useState(null);
  const [errors, setErrors] = useState({});

  const handleVerify = async () => {
    const e = {};
    if (!lastName.trim()) e.lastName = 'Please enter your last name';
    if (!certNo.trim())   e.certNo   = 'Please enter your certificate number';
    if (Object.keys(e).length) { setErrors(e); return; }
    setErrors({});
    setStatus('verifying');
    try {
      const rec = await certLookup(lastName.trim(), certNo.trim());
      setResult(rec); setStatus('success');
    } catch (err) {
      setStatus('fail');
    }
  };

  const reset = () => { setStatus('idle'); setResult(null); setLastName(''); setCertNo(''); setErrors({}); };

  if (status === 'verifying') {
    return (
      <div className="panel">
        <div className="panel-accent"></div>
        <div className="scanner">
          <div className="scanner-card">
            <div className="scanner-beam"></div>
            <div className="sk-line" style={{top:'24px', left:'24px', width:'60%'}}></div>
            <div className="sk-line" style={{top:'48px', left:'24px', width:'80%'}}></div>
            <div className="sk-line" style={{top:'72px', left:'24px', width:'45%'}}></div>
            <div className="sk-line" style={{top:'112px', left:'24px', width:'70%', height:'20px'}}></div>
            <div className="sk-line" style={{top:'146px', left:'24px', width:'50%'}}></div>
          </div>
          <div className="scanner-status">VERIFYING</div>
          <div className="scanner-sub">Checking the Radian certificate registry<span className="scan-dots"><span></span><span></span><span></span></span></div>
        </div>
      </div>
    );
  }

  if (status === 'success') {
    return <Credential data={result} onReset={reset} onRequest={onRequest}/>;
  }

  if (status === 'fail') {
    return (
      <div className="fail-card">
        <div className="fail-head">
          <div className="fail-icon">✕</div>
          <div>
            <div className="fail-title">No Match Found</div>
            <div className="fail-sub">We couldn't verify a certificate with those details.</div>
          </div>
        </div>
        <div className="fail-body">
          <p>This usually means one of the following:</p>
          <div className="fail-list">
            <div className="fail-list-item">The certificate number was entered incorrectly</div>
            <div className="fail-list-item">The last name doesn't match the certificate exactly</div>
            <div className="fail-list-item">The certificate was not issued by Radian H.A. Limited</div>
          </div>
          <p style={{marginTop:18, marginBottom:0, color:'rgba(248,250,255,0.4)'}}>Still stuck? Email <span style={{color:'var(--orange-light)'}}>training@radianhalimited.com</span></p>
          <div className="result-actions">
            <button className="btn-sm btn-sm-primary" onClick={reset}>Try Again</button>
            <button className="btn-sm btn-sm-outline" onClick={onRequest}>Request a Certificate</button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="panel">
      <div className="panel-accent"></div>
      <div className="panel-head">
        <h2>Radian Certificate Lookup</h2>
        <p>Enter the last name and certificate number exactly as they appear on the certificate. Verification is instant.</p>
      </div>
      <div className="panel-body">
        <div className="form-row">
          <div className="form-group" style={{marginBottom:0}}>
            <label className="form-label">Last Name</label>
            <input className={`form-input ${errors.lastName?'error':''}`} type="text" placeholder="e.g. Mitchell" value={lastName}
              onChange={e=>{setLastName(e.target.value); setErrors(p=>({...p,lastName:''}));}}
              onKeyDown={e=>{if(e.key==='Enter') handleVerify();}}/>
            {errors.lastName && <div className="field-err">{errors.lastName}</div>}
          </div>
          <div className="form-group" style={{marginBottom:0}}>
            <label className="form-label">Certificate Number</label>
            <input className={`form-input ${errors.certNo?'error':''}`} type="text" placeholder="RDN-2024-00142" value={certNo}
              onChange={e=>{setCertNo(e.target.value); setErrors(p=>({...p,certNo:''}));}}
              onKeyDown={e=>{if(e.key==='Enter') handleVerify();}}/>
            {errors.certNo && <div className="field-err">{errors.certNo}</div>}
          </div>
        </div>
        <div className="form-hint" style={{marginBottom:24}}>Format: RDN-YYYY-NNNNN — printed on your certificate and record card.</div>
        <button className="btn-full" onClick={handleVerify}>
          <span>Verify Certificate</span><span>→</span>
        </button>
        <div style={{marginTop:18, textAlign:'center'}}>
          <button className="link-btn" onClick={onRequest}>Don't have your certificate? Request one →</button>
        </div>
      </div>
    </div>
  );
}

/* ── CISRS Panel ── */
function CISRSPanel() {
  return (
    <div className="panel">
      <div className="panel-accent"></div>
      <div className="panel-head">
        <h2>CISRS Card Checker</h2>
        <p>CISRS cards are managed and verified by NOCN. We'll send you to their official Card Checker to confirm a card's validity.</p>
      </div>
      <div className="panel-body">
        <div className="notice">
          <strong>Official NOCN Portal</strong>
          The button below opens the official NOCN Card Checker in a new tab, where you can enter the CISRS card number to confirm it is valid and current.
        </div>
        <div className="redirect-box">
          <div className="label">You will be redirected to</div>
          <div className="url"><span style={{color:'var(--orange)'}}>↗</span> nocn.org.uk — CISRS Card Checker</div>
        </div>
        <a href="https://www.nocn.org.uk/cisrs/card-checker/" target="_blank" rel="noopener noreferrer" style={{textDecoration:'none'}}>
          <button className="btn-full btn-external">
            <span>Open NOCN Card Checker</span>
            <span style={{opacity:0.6, fontSize:'0.8rem'}}>↗</span>
          </button>
        </a>
        <div className="help-box">
          Having trouble? Contact us at <span style={{color:'var(--orange-light)'}}>training@radianhalimited.com</span> and we'll assist with card verification.
        </div>
      </div>
    </div>
  );
}

/* ── Request Modal ── */
function RequestModal({ onClose }) {
  const [step, setStep] = useState(1);
  const [form, setForm] = useState({ firstName:'', lastName:'', email:'', certNo:'', reason:'', idType:'passport' });
  const [file, setFile] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const fileRef = useRef();
  const update = (k,v) => setForm(f=>({...f,[k]:v}));

  const handleSubmit = () => {
    const e = {};
    if (!form.firstName.trim()) e.firstName = 'Required';
    if (!form.lastName.trim()) e.lastName = 'Required';
    if (!form.email.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) e.email = 'Valid email required';
    if (!file) e.file = 'Please upload a proof of identity';
    if (Object.keys(e).length) { setErrors(e); return; }
    setErrors({}); setSubmitting(true);
    setTimeout(()=>{ setSubmitting(false); setStep(2); }, 1800);
  };

  useEffect(() => {
    const h = e => { if (e.key === 'Escape') onClose(); };
    window.addEventListener('keydown', h);
    return () => window.removeEventListener('keydown', h);
  }, [onClose]);

  return (
    <div className="modal-overlay" onClick={e=>{if(e.target===e.currentTarget)onClose();}}>
      <div className="modal">
        <div className="modal-header">
          <div className="modal-title">Request a Certificate</div>
          <button className="modal-close" onClick={onClose}>✕</button>
        </div>
        <div className="modal-body">
          {step === 1 ? (
            <>
              <div className="modal-section-label">Your Details</div>
              <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:16}}>
                <div className="form-group" style={{marginBottom:0}}>
                  <label className="form-label">First Name</label>
                  <input className={`form-input ${errors.firstName?'error':''}`} type="text" placeholder="First name" value={form.firstName} onChange={e=>update('firstName',e.target.value)}/>
                  {errors.firstName&&<div className="field-err">{errors.firstName}</div>}
                </div>
                <div className="form-group" style={{marginBottom:0}}>
                  <label className="form-label">Last Name</label>
                  <input className={`form-input ${errors.lastName?'error':''}`} type="text" placeholder="Last name" value={form.lastName} onChange={e=>update('lastName',e.target.value)}/>
                  {errors.lastName&&<div className="field-err">{errors.lastName}</div>}
                </div>
              </div>
              <div className="form-group" style={{marginTop:18}}>
                <label className="form-label">Email Address</label>
                <input className={`form-input ${errors.email?'error':''}`} type="email" placeholder="your@email.com" value={form.email} onChange={e=>update('email',e.target.value)}/>
                {errors.email&&<div className="field-err">{errors.email}</div>}
                <div className="form-hint">Your certificate will be sent here</div>
              </div>
              <div className="form-group">
                <label className="form-label">Certificate Number (if known)</label>
                <input className="form-input" type="text" placeholder="RDN-YYYY-NNNNN (optional)" value={form.certNo} onChange={e=>update('certNo',e.target.value)}/>
              </div>
              <div className="form-group">
                <label className="form-label">Reason for Request</label>
                <select className="form-input" value={form.reason} onChange={e=>update('reason',e.target.value)} style={{cursor:'pointer'}}>
                  <option value="">Select a reason…</option>
                  <option value="lost">Lost original certificate</option>
                  <option value="damaged">Certificate damaged</option>
                  <option value="employer">Required by employer</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div className="modal-section-label">Proof of Identity</div>
              <div style={{marginBottom:14,fontSize:'0.82rem',color:'rgba(248,250,255,0.45)', lineHeight:1.6}}>
                To protect certificate holders, we require a copy of a valid photo ID.
              </div>
              <div className="id-type-grid">
                {[{v:'passport',l:'Passport',i:'🛂'},{v:'driving',l:'Driving Licence',i:'🚗'},{v:'national',l:'National ID',i:'🪪'}].map(opt=>(
                  <button key={opt.v} className={`id-type-btn ${form.idType===opt.v?'active':''}`} onClick={()=>update('idType',opt.v)}>
                    <span className="ico">{opt.i}</span>{opt.l}
                  </button>
                ))}
              </div>
              <div className="file-drop" onClick={()=>fileRef.current.click()} style={errors.file?{borderColor:'var(--red)'}:{}}>
                <div className="file-drop-icon">📎</div>
                <div className="file-drop-text">
                  <strong>Click to upload</strong> or drag and drop<br/>
                  <span style={{fontSize:'0.75rem'}}>JPG, PNG or PDF · Max 5MB</span>
                </div>
                <input ref={fileRef} type="file" accept=".jpg,.jpeg,.png,.pdf" style={{display:'none'}} onChange={e=>{if(e.target.files[0]) setFile(e.target.files[0]);}}/>
              </div>
              {file && (
                <div className="file-accepted">
                  <span style={{fontSize:'1.2rem'}}>✓</span>
                  <span className="file-accepted-name">{file.name}</span>
                  <button onClick={()=>setFile(null)} style={{background:'none',border:'none',color:'rgba(248,250,255,0.3)',cursor:'pointer',fontSize:'1rem'}}>✕</button>
                </div>
              )}
              {errors.file&&<div className="field-err" style={{marginTop:8}}>{errors.file}</div>}

              <button className="btn-full" onClick={handleSubmit} disabled={submitting} style={{marginTop:28}}>
                {submitting ? (<><div className="spinner"/><span>Submitting</span></>) : (<><span>Submit Request</span><span>→</span></>)}
              </button>
              <div style={{marginTop:14,fontSize:'0.75rem',color:'rgba(248,250,255,0.25)',textAlign:'center',lineHeight:1.6}}>
                Reviewed within 2–3 business days. We'll contact you at the email provided.
              </div>
            </>
          ) : (
            <div className="modal-success">
              <div className="modal-success-icon">✓</div>
              <h3>Request Submitted</h3>
              <p>Your certificate request has been received. We'll review your identity document and send your certificate to <strong style={{color:'var(--white)'}}>{form.email}</strong> within 2–3 business days.</p>
              <button className="btn-full" onClick={onClose} style={{marginTop:30, maxWidth:200, marginLeft:'auto', marginRight:'auto'}}>Done</button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

/* ── App ── */
function App() {
  const [method, setMethod] = useState('radian');
  const [showModal, setShowModal] = useState(false);

  return (
    <>
      <div className="cert-page">
        <div className="method-toggle">
          <div className={`method-slider ${method==='cisrs'?'right':''}`}></div>
          <button className={`method-tab ${method==='radian'?'active':''}`} onClick={()=>setMethod('radian')}>
            <div className="method-tab-icon">📜</div>
            <div className="method-tab-text">
              <div className="method-tab-title">Radian Certificate</div>
              <div className="method-tab-sub">Verify instantly here</div>
            </div>
          </button>
          <button className={`method-tab ${method==='cisrs'?'active':''}`} onClick={()=>setMethod('cisrs')}>
            <div className="method-tab-icon">🪪</div>
            <div className="method-tab-text">
              <div className="method-tab-title">CISRS Card</div>
              <div className="method-tab-sub">Via NOCN portal</div>
            </div>
          </button>
        </div>

        {method === 'radian' ? <RadianPanel onRequest={()=>setShowModal(true)}/> : <CISRSPanel/>}
      </div>

      {showModal && <RequestModal onClose={()=>setShowModal(false)}/>}
    </>
  );
}


ReactDOM.createRoot(document.getElementById('root')).render(<App/>);
</script>

<?php get_footer(); ?>
