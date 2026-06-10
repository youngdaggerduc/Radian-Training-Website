<?php
/**
 * Start Here — "your induction pack". Paper sign-in register, the route up
 * as job tickets on the scaffold rail (scroll-driven climber), induction
 * checklist on a clipboard, and a tear-off PERMIT TO BEGIN ticket.
 * Applied automatically to the page with slug 'start-here'. Uses home.css.
 */
get_header(); ?>

<div id="root"></div>

<script type="text/babel">
const { useState, useRef, useEffect } = React;
const U = window.RADIAN_URLS || {};

/* ── Spark divider (same as home) ── */
function SparkDivider() {
  const sparks = [];
  for (let i = 0; i < 10; i++) {
    const angle = (Math.PI * 2 * i) / 10 + Math.random();
    const distance = 40 + Math.random() * 50;
    sparks.push({
      dx: Math.cos(angle) * distance + 'px',
      dy: Math.sin(angle) * distance + 'px',
      delay: Math.random() * 1.8,
    });
  }
  return (
    <div className="spark-divider">
      <div className="line"/>
      <div className="center-dot"/>
      {sparks.map((s, i) => (
        <div key={i} className="spark" style={{
          '--dx': s.dx, '--dy': s.dy,
          animationDelay: s.delay + 's',
        }}/>
      ))}
    </div>
  );
}

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

/* ── Sign-in register ── */
function SignIn() {
  const today = new Date().toLocaleDateString('en-GB', {day:'numeric', month:'short', year:'numeric'});
  return (
    <div className="st-signin reveal">
      <div className="st-signin-head">
        <span className="st-signin-title">DELEGATE SIGN-IN — TOOLBOX REGISTER</span>
        <span className="st-signin-no">Form RHA-IND-01</span>
      </div>
      <div className="st-row"><span className="sig">K. Baptiste</span><span className="when">last month</span><span className="st">Level 1 ✓</span></div>
      <div className="st-row"><span className="sig">M. Persad</span><span className="when">3 weeks ago</span><span className="st">At Height ✓</span></div>
      <div className="st-row"><span className="sig">S. Mohammed</span><span className="when">last week</span><span className="st">Inspection ✓</span></div>
      <div className="st-row you"><span className="sig">( your name here )</span><span className="when">{today}</span><span className="st">Ready</span></div>
      <div className="st-signin-foot">— sign in below and we'll show you the route up ↓</div>
    </div>
  );
}

/* ── The route up — job tickets on the rail ── */
const PW_STEPS = [
  {lift:'BASE',   title:'New Entrant',                              desc:'No experience needed — a head for heights, steel-toe boots and you can start.', dur:null,     id:null},
  {lift:'LIFT 1', title:'CISRS OSTS Scaffolder Level 1',            desc:'Foundation skills: tube & fitting, system scaffolds, safe manual handling.',    dur:'5 days', id:'cisrs-l1'},
  {lift:'LIFT 2', title:'CISRS OSTS Scaffolder Level 2',            desc:'More complex structures and independent working as a qualified scaffolder.',    dur:'5 days', id:'cisrs-l2'},
  {lift:'LIFT 3', title:'CISRS OSTS Scaffolder Level 3 (Advanced)', desc:'Advanced structures, drawing interpretation, leading the gang.',                dur:'5 days', id:'cisrs-l3'},
];
const PW_TOPS = [
  {title:'Scaffolder Supervisor', desc:'Run the job — planning, gangs, programme and compliance.',     id:'cisrs-supervisor'},
  {title:'Scaffold Inspector',    desc:'Sign scaffolds off — basic to advanced statutory inspection.', id:'cisrs-basic-inspection'},
];

function RouteSection() {
  const go = (id) => { if (id) window.location.href = U.course + '?id=' + id; };
  return (
    <section className="st-section" id="pathway">
      <div className="st-inner">
        <div className="section-label reveal">Career Pathway</div>
        <h2 className="section-title reveal" data-crane="1">THE ROUTE<br/><span className="dim">UP</span></h2>
        <p className="reveal" style={{marginTop:18, fontSize:'0.95rem', color:'rgba(248,250,255,0.5)', maxWidth:520}}>
          Every qualified scaffolder climbs the same scaffold — lift by lift. Your job tickets are already on the rail; scroll, and climb with them.
        </p>
        <div className="st-track">
          <div className="st-fill"/>
          <div className="st-climber"/>
          {PW_STEPS.map((s,i)=>(
            <div className={`st-step ${i%2===0?'':'right'} ${s.id?'click':''} reveal`} key={s.lift}
                 style={{transitionDelay:`${i*0.08}s`}} onClick={()=>go(s.id)}>
              <span className="st-node"/><span className="st-tie"/>
              <div className="st-card">
                <div className="st-lift">{s.lift}{s.dur && <span className="st-dur">{s.dur}</span>}</div>
                <div className="st-title">{s.title}</div>
                <div className="st-desc">{s.desc}</div>
                {s.id && <div className="st-go">view this course →</div>}
              </div>
            </div>
          ))}
          <div className="st-summit reveal">
            <div className="st-summit-label">⚑ TOPPING OUT — CHOOSE YOUR ROUTE</div>
            <div className="st-summit-grid">
              {PW_TOPS.map(t=>(
                <div className="st-card" key={t.id} onClick={()=>go(t.id)}>
                  <div className="st-title">{t.title}</div>
                  <div className="st-desc">{t.desc}</div>
                  <div className="st-go">view this course →</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

/* ── Induction checklist (clipboard) ── */
const FAQS = [
  {q:'Do I need experience before CISRS Level 1?', a:'No. Level 1 is the entry point — you need basic fitness, a head for heights and steel-toe boots. Everything else, including PPE and tools, is provided and taught from day one.'},
  {q:'What should I bring on the day?', a:'Photo ID and steel-toe boots. We supply helmets, harnesses, gloves, all tools and all scaffolding materials. Wear work clothes you can move in.'},
  {q:'Is the training practical or classroom-based?', a:'Both — roughly 70% of your time is hands-on in the training yard, with theory sessions and a full written + practical assessment to finish.'},
  {q:'How long does my certification last?', a:'Most CISRS OTS certifications are valid for three years. We recommend booking your refresher before expiry — and any Radian certificate can be checked on our Verify a Certificate page.'},
  {q:'What happens if I fail the assessment?', a:'You get detailed feedback from your instructor and one resit is included in your course fee. Our instructors work with you until the standard is met.'},
  {q:'Which course do I need — scaffolding or working at height?', a:'If you erect, alter or strike scaffolds, you need the CISRS route. If you work on or around scaffolds and are exposed to fall risk — trades, supervisors, inspectors — Getmie Safe Working at Height covers you.'},
  {q:'Can you train our whole crew?', a:'Yes. We run group and corporate bookings, and can schedule dedicated sessions for crews. Contact the site office for group rates and availability.'},
  {q:'How do I pay?', a:'Prices are in TT$. We take deposits to secure a seat and offer company invoicing for corporate bookings — payment details are confirmed when you enroll.'},
];

function FAQSection() {
  const [open, setOpen] = useState(0);
  return (
    <section className="fq-section" id="faq" style={{padding:'40px 0 30px'}}>
      <div className="st-clip reveal">
        <div className="st-letterhead">
          <span style={{background:'#c04080'}}/><span style={{background:'#d83220'}}/><span style={{background:'#7030a0'}}/><span style={{background:'#f07820'}}/><span style={{background:'#f8cc10'}}/>
        </div>
        <div className="st-clip-head">
          <span className="st-clip-title">SITE INDUCTION CHECKLIST</span>
          <span className="st-clip-sheet">Sheet 01</span>
        </div>
        <div className="fq-list">
          {FAQS.map((f,i)=>(
            <div className={`fq-item ${open===i?'open':''}`} key={i}>
              <button className="fq-q" onClick={()=>setOpen(open===i?-1:i)} aria-expanded={open===i}>
                <span className="fq-box">{open===i?'✓':''}</span>
                <span className="fq-text">{f.q}</span>
                <span className="fq-chev">⌄</span>
              </button>
              <div className="fq-a"><div className="fq-a-pad"><p>{f.a}</p></div></div>
            </div>
          ))}
        </div>
      </div>
      <div style={{maxWidth:880, margin:'0 auto', padding:'0 16px'}}>
        <aside className="st-sticky reveal" aria-hidden="true">
          PPE on, chin strap clicked, boots laced. See you in the yard. 👷
          <small>— D. Okafor, Lead Instructor</small>
        </aside>
      </div>
    </section>
  );
}

/* ── Tear-off permit ticket ── */
function TicketSection() {
  return (
    <div className="st-ticket-wrap" id="go">
      <div className="st-ticket reveal">
        <div className="st-ticket-main">
          <div className="st-ticket-kicker">RADIAN H.A. LIMITED · FORM RHA-PTB-01</div>
          <div className="st-ticket-title">PERMIT TO BEGIN</div>
          <div className="st-ticket-sub">Valid for one career. Non-transferable, no expiry — book your seat and bring this attitude on day one.</div>
          <div className="st-ticket-actions">
            <button className="btn-primary" style={{padding:'16px 44px'}} onClick={()=>window.location.href=U.enrol}>Enroll Now</button>
            <button className="btn-outline" onClick={()=>window.location.href=U.home+'#calendar'}>View Training Dates</button>
          </div>
        </div>
        <div className="st-ticket-stub">
          <span className="st-stub-admit">ADMIT ONE · LIFT 1</span>
          <span className="st-stub-bar"/>
          <span className="st-stub-no">№ RHA-0001</span>
        </div>
      </div>
    </div>
  );
}

/* ── App ── */
function App() {
  useEffect(() => {
    const t1 = setTimeout(startReveals, 60);
    const t2 = setTimeout(initRoute, 160);
    return () => { clearTimeout(t1); clearTimeout(t2); };
  }, []);
  return (
    <>
      <PageHero label="Induction Pack" line1="START" dim="HERE" sub="Everything you need before your first day — sign the register, study the route up, tick off the checklist, and tear your permit."/>
      <SignIn/>
      <RouteSection/>
      <SparkDivider/>
      <FAQSection/>
      <TicketSection/>
    </>
  );
}

/* Reveal choreography — runs AFTER React commits (called from App's useEffect).
   GSAP when available, IntersectionObserver fallback otherwise. */
function startReveals() {
  if (window.RadianMotion && RadianMotion.ready() && RadianMotion.init()) return;
  const obs = new IntersectionObserver((es) => {
    es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal,.reveal-left,.reveal-right').forEach(el => obs.observe(el));
}

/* The climber: scroll-scrubbed ascent of the route rail; each lift lights as
   the hard-hat passes it. */
function initRoute() {
  if (!(window.gsap && window.ScrollTrigger)) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.querySelectorAll('.st-step').forEach(el => el.classList.add('lit'));
    const f = document.querySelector('.st-fill'); if (f) f.style.height = '100%';
    return;
  }
  gsap.registerPlugin(ScrollTrigger);
  const track = document.querySelector('.st-track');
  if (!track) return;
  const scrub = { trigger: track, start: 'top 62%', end: 'bottom 70%', scrub: 0.5 };
  gsap.to('.st-fill',    { height: '100%', ease: 'none', scrollTrigger: scrub });
  gsap.to('.st-climber', { top: '100%', y: -18, ease: 'none', scrollTrigger: { trigger: track, start: 'top 62%', end: 'bottom 70%', scrub: 0.5 } });
  document.querySelectorAll('.st-step').forEach(el => {
    ScrollTrigger.create({
      trigger: el, start: 'top 64%',
      onEnter: () => el.classList.add('lit'),
      onLeaveBack: () => el.classList.remove('lit'),
    });
  });
  setTimeout(() => ScrollTrigger.refresh(), 500);
}

ReactDOM.createRoot(document.getElementById('root')).render(<App/>);
</script>

<?php get_footer(); ?>
