<?php
/**
 * About — company story, instructors, delegates, credentials.
 * Applied automatically to the page with slug 'about'. Uses home.css.
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
    <section style={{padding:'175px 60px 30px', textAlign:'center', position:'relative', overflow:'hidden',
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

function GoSection({title, sub}) {
  return (
    <section className="cta-section" id="go" style={{padding:'110px 60px', textAlign:'center', position:'relative'}}>
      <h2 className="cta-title reveal">{title}</h2>
      <p className="cta-sub reveal" style={{maxWidth:540, margin:'22px auto 0'}}>{sub}</p>
      <div className="reveal" style={{marginTop:36, display:'flex', gap:16, justifyContent:'center', flexWrap:'wrap'}}>
        <button className="btn-primary" style={{padding:'18px 52px', fontSize:'1rem'}} onClick={()=>window.location.href=U.enrol}>Enroll Now</button>
        <button className="btn-outline" onClick={()=>window.location.href=U.home+'#calendar'}>View Training Dates</button>
      </div>
    </section>
  );
}
/* ── Instructors ── */
const INSTRUCTORS = (window.RADIAN_DATA && RADIAN_DATA.instructors) || [];

function InstructorsSection() {
  return (
    <section className="team-section" id="team">
      <div className="team-inner">
        <div className="section-label reveal">The Team</div>
        <h2 className="section-title reveal" style={{marginBottom:64}}>MEET YOUR<br/><span className="dim">INSTRUCTORS</span></h2>
        <div className="ins-grid">
          {INSTRUCTORS.map((p,i)=>(
            <div className="ins-item reveal" key={p.reg} style={{transitionDelay:`${i*0.08}s`}}>
              <div className="ins-hang" style={{animationDelay:`${i*0.7}s`}}>
                <div className="ins-strap l"/><div className="ins-strap r"/>
                <div className="ins-clip"/>
                <div className="ins-badge">
                  <div className="ins-head">RADIAN H.A. · SITE PASS</div>
                  <div className="ins-photo">{p.name.split(' ').map(w=>w[0]).join('')}</div>
                  <div className="ins-name">{p.name}</div>
                  <div className="ins-role">{p.role}</div>
                  <div className="ins-meta">
                    <span>CISRS Reg <b>{p.reg}</b></span>
                    <span><b>{p.years}</b> yrs on the tools</span>
                  </div>
                  <div className="ins-tags">{p.tags.map(t=><span key={t}>{t}</span>)}</div>
                  <div className="ins-bar"/>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ── Testimonials — scafftags ── */
const TESTIMONIALS = (window.RADIAN_DATA && RADIAN_DATA.testimonials) || [];

function TestimonialsSection() {
  return (
    <section className="tags-section" id="testimonials">
      <div className="tags-inner">
        <div className="section-label reveal">Word From The Workforce</div>
        <h2 className="section-title reveal">SIGNED OFF<br/><span className="dim">BY OUR DELEGATES</span></h2>
        <div className="tags-rail reveal"/>
        <div className="tags-row">
          {TESTIMONIALS.map((t,i)=>(
            <div className="tag-item reveal" key={t.name} style={{transitionDelay:`${i*0.1}s`}}>
              <div className="tag-hang" style={{animationDelay:`${i*0.9}s`, '--tilt':`${(i%2===0?-1:1)*(1.2+i*0.3)}deg`}}>
                <div className="tag-tie"/>
                <div className="scafftag">
                  <div className="tag-head"><span className="tag-hole"/>INSPECTED &amp; APPROVED</div>
                  <div className="tag-quote">&ldquo;{t.quote}&rdquo;</div>
                  <div className="tag-sig">
                    <div className="tag-name">{t.name}</div>
                    <div className="tag-org">{t.org}</div>
                  </div>
                  <div className="tag-course">{t.course}</div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ── Accreditations ── */
const ACCREDS = (window.RADIAN_DATA && RADIAN_DATA.accreds) || [];

function AccredSectionWrap() {
  return (
    <section className="acc-section" id="credentials">
      <div className="acc-inner">
        <div className="section-label reveal">Credentials</div>
        <h2 className="section-title reveal" style={{marginBottom:56}}>ACCREDITED.<br/><span className="dim">AUDITED. INSURED.</span></h2>
        <div className="acc-grid">
          {ACCREDS.map((a,i)=>(
            <div className="acc-plaque reveal" key={a.title} style={{transitionDelay:`${i*0.08}s`}}>
              <div className="acc-seal">{a.seal}</div>
              <div className="acc-title">{a.title}</div>
              <div className="acc-sub">{a.sub}</div>
              <div className="acc-ref">{a.ref}</div>
              <div className="acc-since">{a.since}</div>
            </div>
          ))}
        </div>
        <div className="acc-note reveal">Certificate and registration numbers can be verified on request — or check any Radian training certificate instantly on our <a href={U.cert}>verification portal</a>.</div>
      </div>
    </section>
  );
}

/* ── App ── */
function App() {
  useEffect(() => { const t = setTimeout(startReveals, 60); return () => clearTimeout(t); }, []);
  return (
    <>
      <PageHero label="Who We Are" line1="ABOUT" dim="RADIAN" sub="Twenty years of building competence, safety and careers across the scaffolding workforce — meet the company, the people and the credentials behind the training."/>
<section id="story">
        <div className="section">
          <div className="section-label reveal">Our Story</div>
          <h2 className="section-title reveal" data-crane="1">BUILDING THE<br/><span className="dim">SCAFFOLDING</span><br/>WORKFORCE</h2>
          <div className="about-grid">
            <div className="about-text reveal-left">
              <p>Radian H.A. Limited has been a trusted British Standard scaffold training provider for over two decades. Our extensive programmes ensure every participant gains the skills needed for a successful, safe career in scaffolding.</p>
              <p>We are committed to building capacity and competence across the scaffolding workforce — delivering a higher standard of work and safety at every level.</p>
              <div className="about-pillars">
                {[
                  {icon:'⚙️',text:'20+ years as a British Standard scaffold training provider'},
                  {icon:'📜',text:'Industry-recognised qualifications, kickstart your career'},
                  {icon:'📈',text:'Enhance your qualifications and advance your opportunities'},
                  {icon:'🤝',text:'Committed to higher safety standards across the industry'},
                ].map((p,i)=>(
                  <div className="pillar" key={i} style={{transitionDelay:`${i*0.08}s`}}>
                    <div className="pillar-icon">{p.icon}</div>
                    <div className="pillar-text">{p.text}</div>
                  </div>
                ))}
              </div>
            </div>
            <div className="reveal-right">
              <div className="img-frame" style={{marginBottom:16, aspectRatio:'16/10'}}>
                <img src={U.theme+'/assets/media/cisrs%20training.jpg'} alt="CISRS scaffold training in action" loading="lazy" decoding="async" style={{width:'100%',height:'100%',objectFit:'cover',display:'block'}}/>
                <div className="img-caption">
                  <div className="img-caption-tag">Radian H.A. Limited</div>
                  <div className="img-caption-text">Our Training In Action</div>
                </div>
              </div>
              <div className="big-stat-cards">
                <div className="stat-card">
                  <div className="stat-card-num">20+</div>
                  <div className="stat-card-label">Years of Training Experience</div>
                </div>
                <div className="stat-card">
                  <div className="stat-card-num">1,500+</div>
                  <div className="stat-card-label">Participants Trained</div>
                </div>
                <div className="stat-card" style={{gridColumn:'1/-1',background:'rgba(232,137,10,0.06)',borderColor:'rgba(232,137,10,0.15)'}}>
                  <div style={{fontFamily:"'Bebas Neue',sans-serif",fontSize:'1.6rem',letterSpacing:'0.06em',marginBottom:12}}>CISRS ACCREDITED</div>
                  <div style={{fontSize:'0.85rem',color:'rgba(248,250,255,0.55)',lineHeight:1.6}}>Our significant achievements validate our commitment to building the capacity and competence of the scaffolding workforce.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <SparkDivider/>

      <InstructorsSection/>

      <SparkDivider/>

      <TestimonialsSection/>

      <SparkDivider/>

      <AccredSectionWrap/>

      <GoSection title={<>TRAIN WITH<br/><span style={{color:'var(--orange)'}}>PEOPLE WHO BUILD</span> PEOPLE</>} sub="Pick your course, meet us in the training yard, and leave with a qualification the industry recognises."/>
    </>
  );
}
/* Reveal choreography — must run AFTER React commits the DOM (useEffect in App
   calls this). GSAP/ScrollTrigger when available, IntersectionObserver fallback
   otherwise — same pattern as the other pages. */
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