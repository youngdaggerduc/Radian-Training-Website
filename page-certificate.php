<?php
/**
 * Verify a Certificate — "The Records Office". A manila folder of training
 * records: paper records-request slip → riffled index cards while the AJAX
 * lookup runs → the certificate itself on paper (letterhead, rubber status
 * stamp, foil seal, instructor signature) or a red-ink RETURN MEMO.
 * Applied automatically to the page with slug 'certificate'.
 */
get_header(); ?>

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
  <div class="page-hero-label">The Records Office</div>
  <h1>VERIFY A<br/><span class="dim">CERTIFICATE</span></h1>
  <p>Every certificate we issue is on file. Give us a last name and a certificate number and we'll pull the record — or check a CISRS card through the official NOCN portal.</p>
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

const STAMP = { valid:'VALID', expired:'EXPIRED', soon:'EXPIRES SOON' };

const Letterhead = ({cls}) => (
  <div className={cls}>
    <span style={{background:'#c04080'}}/><span style={{background:'#d83220'}}/><span style={{background:'#7030a0'}}/><span style={{background:'#f07820'}}/><span style={{background:'#f8cc10'}}/>
  </div>
);

/* ── Foil seal with rotating registry ring ── */
function Seal() {
  return (
    <div className="cred-seal" aria-hidden="true">
      <svg className="cred-seal-ring" viewBox="0 0 96 96">
        <defs>
          <path id="sealPath" d="M48,48 m-38,0 a38,38 0 1,1 76,0 a38,38 0 1,1 -76,0"/>
        </defs>
        <circle cx="48" cy="48" r="46" fill="none" stroke="rgba(232,137,10,0.55)" strokeWidth="1.5"/>
        <circle cx="48" cy="48" r="38" fill="none" stroke="rgba(28,39,64,0.25)" strokeWidth="1"/>
        <text className="cred-seal-text">
          <textPath href="#sealPath" startOffset="0">
            ★ RADIAN H.A. LIMITED ★ TRAINING REGISTRY ★ VERIFIED AUTHENTIC ★
          </textPath>
        </text>
      </svg>
      <div className="cred-seal-core">R</div>
    </div>
  );
}

/* ── The certificate, pulled from the file ── */
function Credential({ data, onReset, onRequest }) {
  const now = new Date();
  const verifiedOn = now.toLocaleDateString('en-GB', {day:'numeric', month:'short', year:'numeric'}) + ', ' + now.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
  const pass = data.results.toLowerCase().includes('pass');

  return (
    <div>
      <div className="papercert">
        <Letterhead cls="pc-letterhead"/>
        <div className="pc-inner">
          <div className={`pc-watermark ${data.expiryStatus==='expired'?'expired':''}`}>{data.expiryStatus==='expired'?'EXPIRED':'VERIFIED'}</div>

          <div className="pc-head">
            <div>
              <div className="pc-brand">RADIAN H.A. LIMITED</div>
              <div className="pc-brand-sub">CISRS-Accredited Training Provider · Trinidad &amp; Tobago</div>
            </div>
            <div className="pc-no">№ {data.certificateNo}</div>
          </div>
          <div className="pc-rule"></div>

          <div className="pc-type">CERTIFICATE OF TRAINING</div>
          <div className="pc-presented">this is to certify that</div>
          <div className="pc-name">{data.firstName} {data.lastName}</div>
          <div className="pc-name-rule"></div>
          <div className="pc-completed">{pass ? 'has successfully completed' : 'attended and completed'}</div>
          <div className="pc-course">{data.courseName}</div>

          <div className="pc-grid">
            <div className="pc-cell"><label>CISRS Student ID</label><b>{data.cisrsStudentId}</b></div>
            <div className="pc-cell"><label>Result</label><b className={pass?'pass':''}>{data.results}</b></div>
            <div className="pc-cell"><label>Venue</label><b>{data.venue}</b></div>
          </div>

          <div className="pc-dates">
            <div className="pc-date"><label>Course Start</label><b>{data.startDate}</b></div>
            <div className="pc-date"><label>Completed</label><b>{data.endDate}</b></div>
            <div className="pc-date"><label>Expires</label><b>{data.expiryDate}</b></div>
            <div className={`pc-stamp ${data.expiryStatus}`}>{STAMP[data.expiryStatus]}<small>Radian Registry</small></div>
          </div>

          <div className="pc-foot">
            <div className="pc-sig">
              <div className="pc-sig-name">{data.instructorName}</div>
              <div className="pc-sig-line"></div>
              <div className="pc-sig-label">Instructor · CISRS № {data.instructorNo}</div>
            </div>
            <Seal/>
            <div className="pc-verify-note">pulled from the registry<br/>{verifiedOn} ✓</div>
          </div>
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

/* ── Radian records-request slip ── */
function RadianPanel({ onRequest }) {
  const [lastName, setLastName] = useState('');
  const [certNo, setCertNo] = useState('');
  const [status, setStatus] = useState('idle');
  const [result, setResult] = useState(null);
  const [errors, setErrors] = useState({});

  const handleVerify = async () => {
    const e = {};
    if (!lastName.trim()) e.lastName = 'we need the holder’s last name ✗';
    if (!certNo.trim())   e.certNo   = 'we need the certificate number ✗';
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
      <div className="slip pull">
        <div className="pull-stack" aria-hidden="true">
          <span className="pull-card c1"></span><span className="pull-card c2"></span><span className="pull-card c3"></span>
        </div>
        <div className="pull-status">PULLING THE FILE</div>
        <div className="pull-sub">checking the registry ledger<span className="scan-dots"><span></span><span></span><span></span></span></div>
      </div>
    );
  }

  if (status === 'success') {
    return <Credential data={result} onReset={reset} onRequest={onRequest}/>;
  }

  if (status === 'fail') {
    return (
      <div className="slip memo">
        <div className="memo-stamp">NO RECORD<br/>ON FILE</div>
        <div className="slip-head">
          <span className="slip-title">RETURN MEMO — RECORDS OFFICE</span>
          <span className="slip-no">Form RHA-VER-02</span>
        </div>
        <p className="slip-note">We went through the registry and couldn't match a certificate to those details. Nine times out of ten it's one of these:</p>
        <div className="memo-list">
          <div className="memo-row"><span className="memo-x">✗</span>The certificate number was entered differently to how it's printed</div>
          <div className="memo-row"><span className="memo-x">✗</span>The last name doesn't match the certificate exactly</div>
          <div className="memo-row"><span className="memo-x">✗</span>The certificate was not issued by Radian H.A. Limited</div>
        </div>
        <div className="slip-foot-note">still stuck? write to the site office — <b>training@rhatt.com</b></div>
        <div className="result-actions">
          <button className="btn-sm btn-sm-primary" onClick={reset}>Try Again</button>
          <button className="btn-sm btn-sm-outline" onClick={onRequest}>Request a Certificate</button>
        </div>
      </div>
    );
  }

  return (
    <div className="slip">
      <Letterhead cls="slip-letterhead"/>
      <div className="slip-head">
        <span className="slip-title">RECORDS REQUEST — VERIFICATION</span>
        <span className="slip-no">Form RHA-VER-01</span>
      </div>
      <p className="slip-note">Fill in the holder's last name and the certificate number exactly as they appear on the certificate, and we'll pull the record. Verification is instant.</p>
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
          <input className={`form-input ${errors.certNo?'error':''}`} type="text" placeholder="RDN-2025-00513" value={certNo}
            onChange={e=>{setCertNo(e.target.value); setErrors(p=>({...p,certNo:''}));}}
            onKeyDown={e=>{if(e.key==='Enter') handleVerify();}}/>
          {errors.certNo && <div className="field-err">{errors.certNo}</div>}
        </div>
      </div>
      <div className="form-hint" style={{marginBottom:24}}>format: RDN-YYYY-NNNNN — printed top right of your certificate ✎</div>
      <button className="btn-full" onClick={handleVerify}>
        <span>Pull The Record</span><span>→</span>
      </button>
      <div style={{marginTop:16, textAlign:'center'}}>
        <button className="link-btn" onClick={onRequest}>Don't have your certificate? Request a copy →</button>
      </div>
    </div>
  );
}

/* ── CISRS transfer slip → NOCN ── */
function CISRSPanel() {
  return (
    <div className="slip">
      <Letterhead cls="slip-letterhead"/>
      <div className="slip-head">
        <span className="slip-title">TRANSFER SLIP — CISRS CARD CHECK</span>
        <span className="slip-no">Form RHA-VER-03</span>
      </div>
      <p className="slip-note">CISRS cards aren't held in our filing — they live in the official NOCN registry. Take this slip to their counter and they'll check the card number while you wait.</p>
      <div className="ts-dest">
        <label>Deliver To</label>
        <div className="ts-url"><span>↗</span> nocn.org.uk — CISRS Card Checker</div>
      </div>
      <a href="https://www.nocn.org.uk/cisrs/card-checker/" target="_blank" rel="noopener noreferrer" style={{textDecoration:'none'}}>
        <button className="btn-full">
          <span>Open NOCN Card Checker</span>
          <span style={{opacity:0.6, fontSize:'0.8rem'}}>↗</span>
        </button>
      </a>
      <div className="slip-foot-note">having trouble? the site office will help — <b>training@rhatt.com</b></div>
    </div>
  );
}

/* ── Request Modal — duplicate-certificate requisition (front-end demo) ── */
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
    if (!form.firstName.trim()) e.firstName = 'required ✗';
    if (!form.lastName.trim()) e.lastName = 'required ✗';
    if (!form.email.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) e.email = 'valid email required ✗';
    if (!file) e.file = 'please attach a proof of identity ✗';
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
          <div>
            <div className="modal-title">Request a Certificate</div>
            <span className="modal-form-no">Form RHA-REQ-01 · Duplicate Requisition</span>
          </div>
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
                <div className="form-hint">your certificate will be sent here</div>
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
              <div style={{marginBottom:14,fontSize:'0.82rem',color:'#4d566e', lineHeight:1.6}}>
                To protect certificate holders, we require a copy of a valid photo ID.
              </div>
              <div className="id-type-grid">
                {[{v:'passport',l:'Passport',i:'🛂'},{v:'driving',l:'Driving Licence',i:'🚗'},{v:'national',l:'National ID',i:'🪪'}].map(opt=>(
                  <button key={opt.v} className={`id-type-btn ${form.idType===opt.v?'active':''}`} onClick={()=>update('idType',opt.v)}>
                    <span className="ico">{opt.i}</span>{opt.l}
                  </button>
                ))}
              </div>
              <div className="file-drop" onClick={()=>fileRef.current.click()} style={errors.file?{borderColor:'#c8372d'}:{}}>
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
                  <button onClick={()=>setFile(null)} style={{background:'none',border:'none',color:'#4d566e',cursor:'pointer',fontSize:'1rem'}}>✕</button>
                </div>
              )}
              {errors.file&&<div className="field-err" style={{marginTop:8}}>{errors.file}</div>}

              <button className="btn-full" onClick={handleSubmit} disabled={submitting} style={{marginTop:28}}>
                {submitting ? (<><div className="spinner"/><span>Submitting</span></>) : (<><span>Submit Request</span><span>→</span></>)}
              </button>
              <div style={{marginTop:14,fontSize:'0.75rem',color:'rgba(28,39,64,0.55)',textAlign:'center',lineHeight:1.6}}>
                Reviewed within 2–3 business days. We'll contact you at the email provided.
              </div>
            </>
          ) : (
            <div className="modal-success">
              <div className="modal-success-icon">✓</div>
              <h3>Request Logged</h3>
              <p>Your requisition is in the tray. We'll review your identity document and send your certificate to <strong style={{color:'#1c2740'}}>{form.email}</strong> within 2–3 business days.</p>
              <button className="btn-full" onClick={onClose} style={{marginTop:30, maxWidth:200, marginLeft:'auto', marginRight:'auto'}}>Done</button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

/* ── App — the folder ── */
function App() {
  const [method, setMethod] = useState('radian');
  const [showModal, setShowModal] = useState(false);

  return (
    <>
      <div className="cert-page">
        <div className="folder-tabs">
          <button className={`folder-tab ${method==='radian'?'active':''}`} onClick={()=>setMethod('radian')}>
            <span className="ft-title">Radian Certificate</span>
            <span className="ft-sub">verified right here at the counter</span>
          </button>
          <button className={`folder-tab ${method==='cisrs'?'active':''}`} onClick={()=>setMethod('cisrs')}>
            <span className="ft-title">CISRS Card</span>
            <span className="ft-sub">held in the NOCN registry</span>
          </button>
        </div>
        <div className="folder-body">
          <div className="folder-sticker">File RHA-TR · Training Records</div>
          {method === 'radian' ? <RadianPanel onRequest={()=>setShowModal(true)}/> : <CISRSPanel/>}
        </div>
      </div>

      {showModal && <RequestModal onClose={()=>setShowModal(false)}/>}
    </>
  );
}


ReactDOM.createRoot(document.getElementById('root')).render(<App/>);
</script>

<?php get_footer(); ?>
