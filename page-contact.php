<?php
/**
 * Contact Us — "THE SITE OFFICE" (slug `contact`, uses home.css + co-* section).
 * Reached from the About ▾ nav dropdown. Info cards + map on the left; the
 * message form is a paper "WHILE YOU WERE OUT" message-pad slip wired to the
 * same radian_contact_submit endpoint as the home-page contact section.
 */
get_header(); ?>

<div id="root"></div>

<script type="text/babel">
const { useState, useEffect } = React;
const U = window.RADIAN_URLS || {};

function PageHero({label, line1, dim, sub}) {
  return (
    <section style={{padding:'175px 60px 10px', textAlign:'center', position:'relative', overflow:'hidden',
                     background:'linear-gradient(160deg,#0a1628 0%,#112240 60%,#0d1e3a 100%)'}}>
      <div className="scaffold-bg"></div>
      <div style={{position:'relative'}}>
        <div className="section-label" style={{justifyContent:'center'}}>{label}</div>
        <h1 className="section-title" style={{fontSize:'clamp(3rem,6vw,5.5rem)'}}>{line1}<br/><span className="dim">{dim}</span></h1>
        <p style={{marginTop:20, fontSize:'1rem', color:'rgba(248,250,255,0.55)', maxWidth:580, margin:'20px auto 0', lineHeight:1.7}}>{sub}</p>
      </div>
    </section>
  );
}

const INFO = [
  {icon:'📞', label:'Call us',     value:'+1 (868) 280-4598', href:'tel:+18682804598'},
  {icon:'💬', label:'WhatsApp',    value:'Chat with the team', href:'https://wa.me/18682804598?text='+encodeURIComponent('Hi Radian, I would like information about your training courses.')},
  {icon:'✉️', label:'Email',       value:'training@rhatt.com', href:'mailto:training@rhatt.com'},
  {icon:'🕗', label:'Site hours',  value:'Mon – Fri · 07:00 – 16:00', href:null},
];

/* ── The message pad — paper slip → radian_contact_submit ── */
function MessageSlip() {
  const [form, setForm] = useState({name:'', email:'', phone:'', message:'', company:''}); // company = honeypot
  const [state, setState] = useState('idle'); // idle | sending | sent | error
  const update = (k,v) => { setForm(f=>({...f,[k]:v})); if(state==='error') setState('idle'); };

  const submit = async () => {
    if (state === 'sending') return;
    if (!form.name.trim() || !form.email.trim() || !form.message.trim()) { setState('error'); return; }
    setState('sending');
    try {
      const cfg = window.RADIAN_CONTACT || {};
      const body = new URLSearchParams({action:'radian_contact', nonce:cfg.nonce||'', ...form});
      const res = await fetch(cfg.ajaxUrl, {method:'POST', body});
      const j = await res.json();
      setState(j.success ? 'sent' : 'error');
    } catch (e) { setState('error'); }
  };

  return (
    <div className="co-slip reveal-right">
      <div className="co-slip-head">
        <span className="co-slip-title">WHILE YOU WERE OUT</span>
        <span className="co-slip-no">Form RHA-MSG-01</span>
      </div>

      {state === 'sent' ? (
        <div className="co-sent">
          <div className="co-sent-stamp">MESSAGE<br/>TAKEN</div>
          <p className="co-sent-note">— passed to the site office. We'll get back to you within one working day. ✓</p>
        </div>
      ) : (
        <>
          <div className="co-to">To: <b>the Site Office</b> · Radian H.A. Limited, Claxton Bay</div>
          <div className="co-field-grid">
            <div className="co-field">
              <label>From (your name)</label>
              <input type="text" placeholder="Your name" value={form.name} onChange={e=>update('name',e.target.value)}/>
            </div>
            <div className="co-field">
              <label>Phone (optional)</label>
              <input type="tel" placeholder="+1 (868) …" value={form.phone} onChange={e=>update('phone',e.target.value)}/>
            </div>
          </div>
          <div className="co-field">
            <label>Email</label>
            <input type="email" placeholder="you@company.com" value={form.email} onChange={e=>update('email',e.target.value)}/>
          </div>
          <div className="co-field">
            <label>Message</label>
            <textarea rows="6" placeholder="Which course, how many people, preferred dates — or just say hello…" value={form.message} onChange={e=>update('message',e.target.value)}/>
          </div>
          <input type="text" className="ct-hp" tabIndex="-1" autoComplete="off" value={form.company} onChange={e=>update('company',e.target.value)} aria-hidden="true"/>
          {state==='error' && <div className="co-err">we need your name, a valid email, and a message ✗</div>}
          <button className="btn-primary co-send" onClick={submit} disabled={state==='sending'}>
            {state==='sending' ? 'Sending…' : 'Leave The Message →'}
          </button>
          <div className="co-slip-foot">— the kettle's on; we answer fast. ☕</div>
        </>
      )}
    </div>
  );
}

/* ── App ── */
function App() {
  useEffect(() => { const t = setTimeout(startReveals, 60); return () => clearTimeout(t); }, []);
  return (
    <>
      <PageHero label="Get In Touch" line1="THE SITE" dim="OFFICE"
        sub="Call, WhatsApp, or leave a message on the pad — Building 2, Plaisance Park Industrial Estate, Claxton Bay, Trinidad."/>
      <section className="co-section" id="contact">
        <div className="co-inner">
          <div className="co-grid">
            <div className="reveal-left">
              <div className="ct-cards">
                {INFO.map(i=>(
                  i.href
                    ? <a key={i.label} className="ct-card" href={i.href} target={i.href.startsWith('http')?'_blank':undefined} rel="noopener noreferrer">
                        <span className="ct-ico">{i.icon}</span>
                        <span><span className="ct-label">{i.label}</span><span className="ct-value">{i.value}</span></span>
                      </a>
                    : <div key={i.label} className="ct-card">
                        <span className="ct-ico">{i.icon}</span>
                        <span><span className="ct-label">{i.label}</span><span className="ct-value">{i.value}</span></span>
                      </div>
                ))}
              </div>
              <div className="ct-map">
                <iframe
                  title="Radian Training Centre location"
                  src="https://www.openstreetmap.org/export/embed.html?bbox=-61.490%2C10.330%2C-61.410%2C10.400&layer=mapnik"
                  loading="lazy" referrerPolicy="no-referrer-when-downgrade"></iframe>
                <div className="ct-map-tag">Building 2, Plaisance Park Industrial Estate, Claxton Bay</div>
              </div>
              <aside className="ct-sticky" aria-hidden="true">
                Walk-ins welcome Mon – Fri, 07:00 – 16:00 — ask at the gate for the duty instructor. 👷
                <small>— the Site Office</small>
              </aside>
            </div>
            <MessageSlip/>
          </div>
        </div>
      </section>
    </>
  );
}

/* Reveal choreography — runs AFTER React commits (same pattern as about page). */
function startReveals() {
  if (window.RadianMotion && RadianMotion.ready() && RadianMotion.init()) return;
  const obs = new IntersectionObserver((es) => {
    es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal,.reveal-left,.reveal-right').forEach(el => obs.observe(el));
}

ReactDOM.createRoot(document.getElementById('root')).render(<App/>);
</script>

<?php get_footer(); ?>
