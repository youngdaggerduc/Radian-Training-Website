<?php get_header(); ?>

<div id="root"></div>

<script type="text/babel">
const { useState, useEffect, useMemo } = React;
const U = window.RADIAN_URLS || {};
const courseUrl = (id) => U.course + '?id=' + id;

const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

/* Generate plausible training dates per-course over next 6 months */
function generateDates(courseId) {
  const today = new Date('2026-05-27');
  const slots = [];
  // 4-6 future date slots per course
  const seeds = {
    'cisrs-l1': [[5,'2026-06-08','London',8],[6,'2026-07-13','Manchester',7],[7,'2026-08-10','London',6],[8,'2026-09-14','London',8],[9,'2026-10-12','Birmingham',6]],
    'cisrs-l2': [[5,'2026-06-15','London',6],[6,'2026-07-20','London',8],[7,'2026-08-24','Manchester',7],[8,'2026-09-21','London',6]],
    'cisrs-l3': [[5,'2026-07-06','London',5],[6,'2026-08-17','London',5],[7,'2026-10-19','Birmingham',4],[8,'2026-11-23','London',6]],
    'cisrs-basic-inspection': [[3,'2026-06-22','London',6],[3,'2026-07-27','Manchester',5],[3,'2026-09-07','London',6],[3,'2026-10-26','Birmingham',5]],
    'cisrs-advanced-inspection': [[2,'2026-07-02','London',4],[2,'2026-08-13','London',5],[2,'2026-10-08','Manchester',4],[2,'2026-11-12','London',5]],
    'cisrs-supervisor': [[3,'2026-06-29','London',6],[3,'2026-08-03','London',5],[3,'2026-09-28','Birmingham',6],[3,'2026-11-02','London',5]],
    'gms-wah': [[1,'2026-06-04','London',12],[1,'2026-06-25','Manchester',10],[1,'2026-07-16','London',12],[1,'2026-08-06','London',10],[1,'2026-09-03','Birmingham',12],[1,'2026-10-01','London',10]],
    'gms-rescue-basic': [[1,'2026-06-12','London',10],[1,'2026-07-10','London',8],[1,'2026-08-14','Manchester',10],[1,'2026-09-11','London',9],[1,'2026-10-09','London',10]],
    'gms-rescue-advanced': [[2,'2026-06-18','London',8],[2,'2026-07-23','London',6],[2,'2026-09-17','Manchester',8],[2,'2026-11-05','London',7]],
    'gms-rescue-refresher': [[1,'2026-06-05','London',10],[1,'2026-07-03','London',9],[1,'2026-08-07','Manchester',10],[1,'2026-09-04','London',10],[1,'2026-10-02','Birmingham',10]],
  };
  return (seeds[courseId] || []).map(([days, dateStr, venue, spots], i) => {
    const start = new Date(dateStr + 'T00:00:00');
    const end = new Date(start);
    end.setDate(end.getDate() + days - 1);
    return {
      id: `${courseId}-${i}`,
      start, end, venue, spots,
      startStr: start.toLocaleDateString('en-GB', {weekday:'short', day:'numeric', month:'short'}),
      endStr: end.toLocaleDateString('en-GB', {weekday:'short', day:'numeric', month:'short'}),
      year: start.getFullYear(),
    };
  });
}

function getCourseId() {
  const p = new URLSearchParams(window.location.search);
  return p.get('id') || 'cisrs-l1';
}

function generateRef() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let r = 'RDN-';
  for (let i = 0; i < 6; i++) r += chars[Math.floor(Math.random()*chars.length)];
  return r;
}

function StepPill({ num, label, title, status, onClick }) {
  return (
    <button className={`step-pill ${status}`} onClick={onClick} disabled={status==='locked'}>
      <div className="step-num">{status==='complete'?'✓':num}</div>
      <div className="step-info">
        <div className="step-label">{label}</div>
        <div className="step-title">{title}</div>
      </div>
    </button>
  );
}

function DateStep({ dates, selected, onSelect }) {
  if (dates.length === 0) {
    return (
      <div className="empty-dates">
        <h3>No Upcoming Dates Listed</h3>
        <p>Please contact our training team to discuss availability:<br/><span style={{color:'var(--orange-light)'}}>training@radianhalimited.com</span></p>
      </div>
    );
  }
  return (
    <div className="dates-grid">
      {dates.map(d => (
        <div key={d.id} className={`date-card ${selected?.id===d.id?'selected':''}`} onClick={()=>onSelect(d)}>
          <div className="date-card-top">
            <div className="date-day">{String(d.start.getDate()).padStart(2,'0')}</div>
            <div>
              <div className="date-month">{MONTHS[d.start.getMonth()].slice(0,3)}</div>
              <div className="date-year">{d.year}</div>
            </div>
          </div>
          <div className="date-meta">
            <div className="date-meta-row">📅 <span>{d.startStr} – {d.endStr}</span></div>
            <div className="date-meta-row">📍 <span>Training Centre, {d.venue}</span></div>
          </div>
          <div className={`date-spots ${d.spots<=5?'low':''}`}>● {d.spots} spots left</div>
        </div>
      ))}
    </div>
  );
}

function PeopleStep({ course, count, customCount, onSelect, onCustom }) {
  const price = parseFloat(course.price.replace(/,/g,''));
  const fmt = (n) => 'TT$' + n.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const customN = parseInt(customCount, 10) || 6;
  const customTotal = price * customN;

  return (
    <>
      <div className="people-grid">
        {[1,2,3,4,5].map(n => (
          <div key={n} className={`people-card ${count===n?'selected':''}`} onClick={()=>onSelect(n)}>
            <div className="people-num">{n}</div>
            <div className="people-label">{n===1?'Delegate':'Delegates'}</div>
            <div className="people-cost">
              Cost
              <div className="people-cost-value">{fmt(price*n)}</div>
              <div className="people-cost-vat">VAT inclusive</div>
            </div>
          </div>
        ))}
      </div>

      <div className={`people-plus ${count==='custom'?'selected':''}`} onClick={()=>onSelect('custom')}>
        <div className="pp-label">
          <div className="pp-label-top">6+ Delegates</div>
          <div className="pp-label-sub">Select attendees for larger groups — bulk rates may apply</div>
        </div>
        <input
          type="number" min="6" max="50"
          className="pp-input"
          value={count==='custom'?customCount:6}
          onChange={e => { onSelect('custom'); onCustom(e.target.value); }}
          onClick={e => e.stopPropagation()}
        />
        <div className="pp-cost">{count==='custom' ? fmt(customTotal) : fmt(price*6)}</div>
      </div>
    </>
  );
}

function DelegateStep({ count, delegates, onChange, errors }) {
  return (
    <div className="delegates-grid">
      {Array.from({length: count}).map((_, i) => {
        const d = delegates[i] || { name:'', dob:'', number:'' };
        const isComplete = d.name && d.dob && d.number;
        return (
          <div className="delegate-block" key={i}>
            <div className="delegate-block-header">
              <div className="delegate-num">{i+1}</div>
              <div className="delegate-title">Delegate {i+1}</div>
              <div className={`delegate-status ${isComplete?'show':''}`}>✓ Complete</div>
            </div>
            <div className="field-grid">
              <div className="field">
                <div className="field-label">Full Name</div>
                <input
                  className={`field-input ${errors[i]?.name?'error':''}`}
                  type="text" placeholder="e.g. James Mitchell"
                  value={d.name}
                  onChange={e => onChange(i, 'name', e.target.value)}
                />
                {errors[i]?.name && <div className="field-err">{errors[i].name}</div>}
              </div>
              <div className="field">
                <div className="field-label">Date of Birth</div>
                <input
                  className={`field-input ${errors[i]?.dob?'error':''}`}
                  type="date"
                  value={d.dob}
                  onChange={e => onChange(i, 'dob', e.target.value)}
                  max="2010-01-01"
                />
                {errors[i]?.dob && <div className="field-err">{errors[i].dob}</div>}
              </div>
              <div className="field">
                <div className="field-label">Contact Number</div>
                <input
                  className={`field-input ${errors[i]?.number?'error':''}`}
                  type="tel" placeholder="+1 868 555 0123"
                  value={d.number}
                  onChange={e => onChange(i, 'number', e.target.value)}
                />
                {errors[i]?.number && <div className="field-err">{errors[i].number}</div>}
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}

function ConfirmStep({ course, date, count, ref }) {
  return (
    <div className="confirm-panel">
      <div className="confirm-icon-wrap">✓</div>
      <h2 className="confirm-h">REQUEST<br/><span className="accent">SUBMITTED</span></h2>
      <p className="confirm-p">
        Your Enrollment request has been sent to the training team. They will reach out shortly with payment instructions, confirmation, and joining details.
      </p>
      <div className="confirm-ref">
        <div className="confirm-ref-item">
          <div className="confirm-ref-label">Reference</div>
          <div className="confirm-ref-value">{ref}</div>
        </div>
        <div className="confirm-ref-item">
          <div className="confirm-ref-label">Course</div>
          <div className="confirm-ref-value" style={{fontSize:'0.95rem'}}>{course.code}</div>
        </div>
        <div className="confirm-ref-item">
          <div className="confirm-ref-label">Delegates</div>
          <div className="confirm-ref-value">{count}</div>
        </div>
      </div>
      <div className="confirm-actions">
        <a href={U.home} className="btn btn-ghost" style={{textDecoration:'none'}}>← Return Home</a>
        <a href={courseUrl(course.id)} className="btn btn-primary" style={{textDecoration:'none'}}>View Course Page</a>
      </div>
    </div>
  );
}

function Summary({ course, date, count, customCount, total }) {
  const isCisrs = course.category === 'cisrs';
  return (
    <div className="summary">
      <div className="summary-header">
        <div className="summary-label">{isCisrs?'CISRS OTS':'Getmie Safe'} · {course.code}</div>
        <div className="summary-title">{course.title}</div>
      </div>
      <div className="summary-body">
        <div className="summary-row">
          <span className="sr-label">Duration</span>
          <span className="sr-value">{course.duration}</span>
        </div>
        <div className="summary-row">
          <span className="sr-label">Daily Hours</span>
          <span className="sr-value" style={{fontSize:'0.8rem'}}>{course.trainingTime}</span>
        </div>
        <div className="summary-row">
          <span className="sr-label">Per Delegate</span>
          <span className="sr-value">TT${course.price}</span>
        </div>
        <div className="summary-row">
          <span className="sr-label">Date</span>
          <span className={`sr-value ${!date?'dim':''}`}>
            {date ? `${date.startStr} – ${date.endStr}` : 'Not selected'}
          </span>
        </div>
        <div className="summary-row">
          <span className="sr-label">Venue</span>
          <span className={`sr-value ${!date?'dim':''}`}>
            {date ? date.venue : 'Not selected'}
          </span>
        </div>
        <div className="summary-row">
          <span className="sr-label">Delegates</span>
          <span className={`sr-value ${!count?'dim':''}`}>
            {count==='custom' ? customCount : count || 'Not selected'}
          </span>
        </div>
      </div>
      <div className="summary-total">
        <div>
          <div className="summary-total-label">Total Estimate</div>
          <div className="summary-total-vat">VAT inclusive</div>
        </div>
        <div className="summary-total-value">{total ? `TT$${total.toLocaleString('en-US',{minimumFractionDigits:2, maximumFractionDigits:2})}` : '—'}</div>
      </div>
    </div>
  );
}

function App() {
  const courseId = getCourseId();
  const course = window.COURSES_DATA[courseId];
  const dates = useMemo(() => course ? generateDates(courseId) : [], [courseId]);

  const [step, setStep] = useState(1);
  // pre-select a session when arriving from the calendar (?date=YYYY-MM-DD):
  // exact match if it exists, otherwise the nearest available session.
  const [selectedDate, setSelectedDate] = useState(() => {
    const want = new URLSearchParams(window.location.search).get('date');
    if (!want || !dates.length) return null;
    const t = new Date(want + 'T00:00:00').getTime();
    if (isNaN(t)) return null;
    let best = null, bestDiff = Infinity;
    dates.forEach(d => {
      const diff = Math.abs(d.start.getTime() - t);
      if (diff < bestDiff) { bestDiff = diff; best = d; }
    });
    return best;
  });
  const [peopleChoice, setPeopleChoice] = useState(null); // 1-5 or 'custom'
  const [customCount, setCustomCount] = useState('6');
  const [delegates, setDelegates] = useState([]);
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [submittedRef, setSubmittedRef] = useState(null);

  const numDelegates = peopleChoice === 'custom' ? Math.max(6, parseInt(customCount,10) || 6) : peopleChoice;
  const price = course ? parseFloat(course.price.replace(/,/g,'')) : 0;
  const total = numDelegates ? price * numDelegates : 0;

  if (!course) {
    return (
      <div style={{padding:'200px 60px', textAlign:'center'}}>
        <h2 style={{fontFamily:"'Bebas Neue',sans-serif", fontSize:'2.4rem', letterSpacing:'0.04em'}}>Course Not Found</h2>
        <p style={{color:'rgba(248,250,255,0.5)', marginTop:16, marginBottom:32}}>We couldn't find the course you're trying to Enroll on.</p>
        <a href={U.cisrs} style={{padding:'14px 32px', background:'var(--orange)', color:'var(--navy)', textDecoration:'none', fontWeight:700, fontSize:'0.88rem', letterSpacing:'0.1em', textTransform:'uppercase'}}>Browse Courses</a>
      </div>
    );
  }

  const updateDelegate = (idx, field, val) => {
    const next = [...delegates];
    next[idx] = { ...(next[idx] || {}), [field]: val };
    setDelegates(next);
    if (errors[idx]?.[field]) {
      const ne = {...errors};
      ne[idx] = { ...ne[idx], [field]: '' };
      setErrors(ne);
    }
  };

  const validateDelegates = () => {
    const e = {};
    for (let i = 0; i < numDelegates; i++) {
      const d = delegates[i] || {};
      const row = {};
      if (!d.name?.trim()) row.name = 'Required';
      if (!d.dob) row.dob = 'Required';
      if (!d.number?.trim()) row.number = 'Required';
      else if (d.number.replace(/[^0-9]/g,'').length < 7) row.number = 'Invalid phone';
      if (Object.keys(row).length) e[i] = row;
    }
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const submit = () => {
    if (!validateDelegates()) return;
    setSubmitting(true);
    setTimeout(() => {
      setSubmittedRef(generateRef());
      setStep(4);
      setSubmitting(false);
      window.scrollTo({top:0, behavior:'smooth'});
    }, 1500);
  };

  const stepStatus = (n) => {
    if (step === 4) return n === 4 ? 'active' : 'complete';
    if (n < step) return 'complete';
    if (n === step) return 'active';
    // locked unless prereq met
    if (n === 2 && !selectedDate) return 'locked';
    if (n === 3 && (!selectedDate || !peopleChoice)) return 'locked';
    return '';
  };

  const goToStep = (n) => {
    if (stepStatus(n) === 'locked') return;
    setStep(n);
    window.scrollTo({top:0, behavior:'smooth'});
  };

  return (
    <>
      <div className="Enroll-header">
        <div className="breadcrumb">
          <a href={U.home}>Home</a>
          <span className="sep">/</span>
          <a href={course.category==='cisrs'?U.cisrs:U.getmie}>{course.category==='cisrs'?'CISRS OTS':'Getmie Safe'}</a>
          <span className="sep">/</span>
          <a href={courseUrl(course.id)}>{course.title}</a>
          <span className="sep">/</span>
          <span className="current">Enroll</span>
        </div>
        <div className="Enroll-course-tag">{course.code} · {course.duration}</div>
        <div className="Enroll-title-row">
          <h1 className="Enroll-h1">Enroll ON<br/>{course.title}</h1>
        </div>
        <div className="Enroll-sub">Follow the steps below to request Enrollment. Our training team will confirm availability and send payment instructions.</div>
      </div>

      {step < 4 && (
        <div className="stepper-wrap">
          <div className="stepper">
            <StepPill num="1" label="Step One" title="Select Date" status={stepStatus(1)} onClick={()=>goToStep(1)}/>
            <StepPill num="2" label="Step Two" title="Number of People" status={stepStatus(2)} onClick={()=>goToStep(2)}/>
            <StepPill num="3" label="Step Three" title="Delegate Details" status={stepStatus(3)} onClick={()=>goToStep(3)}/>
          </div>
        </div>
      )}

      <div className="Enroll-main">
        <div>
          <div className="Enroll-panel" key={step}>
            {step === 1 && (
              <>
                <div className="panel-title">Step 1 — Select Your Training Date</div>
                <div className="panel-sub">Choose an upcoming session that suits your schedule. All sessions are held {course.trainingTime}.</div>
                <DateStep dates={dates} selected={selectedDate} onSelect={setSelectedDate}/>
                <div className="actions-bar">
                  <a href={courseUrl(course.id)} className="btn btn-ghost" style={{textDecoration:'none'}}>← Back to Course</a>
                  <button className="btn btn-primary" disabled={!selectedDate} onClick={()=>{ setStep(2); window.scrollTo({top:0,behavior:'smooth'}); }}>
                    Continue →
                  </button>
                </div>
              </>
            )}

            {step === 2 && (
              <>
                <div className="panel-title">Step 2 — Select The Number Of People</div>
                <div className="panel-sub">How many delegates will attend this session? Costs shown are VAT inclusive.</div>
                <PeopleStep course={course} count={peopleChoice} customCount={customCount} onSelect={setPeopleChoice} onCustom={setCustomCount}/>
                <div className="actions-bar">
                  <button className="btn btn-ghost" onClick={()=>setStep(1)}>← Back</button>
                  <button className="btn btn-primary" disabled={!peopleChoice} onClick={()=>{ setStep(3); window.scrollTo({top:0,behavior:'smooth'}); }}>
                    Continue →
                  </button>
                </div>
              </>
            )}

            {step === 3 && (
              <>
                <div className="panel-title">Step 3 — Delegate Details</div>
                <div className="panel-sub">Please enter the details for each delegate attending. All fields are required.</div>
                <DelegateStep count={numDelegates} delegates={delegates} onChange={updateDelegate} errors={errors}/>
                <div className="actions-bar">
                  <button className="btn btn-ghost" onClick={()=>setStep(2)} disabled={submitting}>← Back</button>
                  <button className="btn btn-primary" onClick={submit} disabled={submitting}>
                    {submitting ? (
                      <>
                        <div style={{width:14,height:14,border:'2px solid rgba(10,22,40,0.3)',borderTopColor:'var(--navy)',borderRadius:'50%',animation:'spin 0.7s linear infinite'}}/>
                        <span>Submitting</span>
                      </>
                    ) : (
                      <>
                        <span>Submit Request</span>
                        <span>→</span>
                      </>
                    )}
                  </button>
                </div>
              </>
            )}

            {step === 4 && submittedRef && (
              <ConfirmStep course={course} date={selectedDate} count={numDelegates} ref={submittedRef}/>
            )}
          </div>
        </div>

        <Summary course={course} date={selectedDate} count={peopleChoice} customCount={customCount} total={total}/>
      </div>

      <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
    </>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App/>);
</script>

<?php get_footer(); ?>

