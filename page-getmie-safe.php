<?php get_header(); ?>

<!-- HERO -->
<div class="page-hero">
  <div class="page-hero-bg"></div>
  <div class="page-hero-grid">
    <div>
      <div class="hero-pill">Getmie Safe</div>
      <h1>WORKING AT<br/><span class="accent">HEIGHT</span><br/><span class="dim">& RESCUE</span></h1>
      <p>Comprehensive working-at-height and rescue training designed for scaffolders and all personnel exposed to fall risks. Built around the Getmie Safe Rescue System.</p>
      <div class="page-hero-stats">
        <div class="ph-stat">
          <div class="ph-stat-num" data-count="4">4</div>
          <div class="ph-stat-label">Courses</div>
        </div>
        <div class="ph-stat">
          <div class="ph-stat-num">1–2</div>
          <div class="ph-stat-label">Day Programmes</div>
        </div>
        <div class="ph-stat">
          <div class="ph-stat-num" data-count="100" data-suffix="%">100%</div>
          <div class="ph-stat-label">Safety Focused</div>
        </div>
      </div>
    </div>
    <div class="hero-illus">
      <div class="hero-3d" id="hero3d-mount"></div>
      <div class="hero-3d-hint">Drag to rotate · Tap a part to inspect</div>
    </div>
  </div>
</div>

<div id="root"></div>

<script type="text/babel">
const { useState, useEffect } = React;
const U = window.RADIAN_URLS || {};
const courseUrl = (id) => U.course + '?id=' + id;

const WAH_COURSES = [
  {
    id: 'gms-wah',
    code: 'GMS-WAH',
    name: 'Getmie Safe Working at Height',
    days: 1,
    price: '675.00',
    icon: '🪜',
    type: 'wah',
    blurb: 'Foundational training in safe work practices at height — risk assessment, equipment selection, PPE, and hazard control.',
    learn: [
      'Risk assessment for working at height',
      'Selecting appropriate fall-protection equipment',
      'Correct use of PPE and harness systems',
      'Hierarchy of fall-protection controls',
      'Inspection of equipment before use',
      'Safe work practices at elevation',
    ],
    audience: 'Scaffolders and all personnel exposed to fall risks in any industry.',
    assessment: 'Practical demonstration + written assessment',
  },
];

const RESCUE_COURSES = [
  {
    id: 'gms-rescue-basic',
    code: 'GMS-RB',
    name: 'Basic Getmie Safe Rescue Training',
    days: 1,
    price: '1,687.50',
    icon: '🛟',
    type: 'rescue',
    blurb: 'Essential rescue techniques for personnel working at height — covering rapid response, basic recovery, and team coordination.',
    learn: [
      'Basic rescue principles for work at height',
      'Suspension trauma awareness and prevention',
      'Using the Getmie Safe Rescue System',
      'Team-based response protocols',
      'Casualty assessment and stabilisation',
      'Communication during emergencies',
    ],
    audience: 'Operatives and supervisors who may need to perform a rescue.',
    assessment: 'Practical rescue scenarios + written assessment',
  },
  {
    id: 'gms-rescue-advanced',
    code: 'GMS-RA',
    name: 'Advanced Getmie Safe Rescue Training',
    days: 2,
    price: '3,375.00',
    icon: '⛑️',
    type: 'rescue',
    blurb: 'Advanced two-day training for rescue team leaders — complex rescue scenarios, equipment mastery, and incident command.',
    learn: [
      'Advanced rescue equipment configurations',
      'Complex rescue scenarios at height',
      'Confined-space and obstructed rescues',
      'Rescue team leadership and command',
      'Incident planning and after-action review',
      'Rescuer self-rescue techniques',
    ],
    audience: 'Designated rescue team members and team leaders.',
    assessment: 'Multi-scenario practical assessment + written exam',
  },
  {
    id: 'gms-rescue-refresher',
    code: 'GMS-RR',
    name: 'Getmie Safe Rescue Refresher',
    days: 1,
    price: '1,687.50',
    icon: '🔄',
    type: 'rescue',
    blurb: 'Refresher course for personnel previously trained — keeps skills sharp and ensures compliance with current best practices.',
    learn: [
      'Updates on current rescue best practices',
      'Equipment refresher and inspection',
      'Refreshed rescue scenarios',
      'Skills consolidation and assessment',
      'Latest regulatory and safety updates',
    ],
    audience: 'Previously trained Getmie Safe Rescue personnel.',
    assessment: 'Skills consolidation + practical refresh',
  },
];

function CourseModal({ course, onClose }) {
  const isRescue = course.type === 'rescue';
  useEffect(() => {
    const h = e => { if (e.key === 'Escape') onClose(); };
    window.addEventListener('keydown', h);
    document.body.style.overflow = 'hidden';
    return () => { window.removeEventListener('keydown', h); document.body.style.overflow = ''; };
  }, [onClose]);

  return (
    <div className="modal-overlay" onClick={e => { if (e.target === e.currentTarget) onClose(); }}>
      <div className="modal">
        <div className={`modal-top ${isRescue?'rescue-top':''}`}>
          <button className="modal-close" onClick={onClose}>✕</button>
          <div className="mt-tag">{course.code} · {isRescue?'Getmie Safe Rescue':'Getmie Safe'}</div>
          <h3>{course.name}</h3>
        </div>
        <div className="modal-body">
          <div className="modal-meta-grid">
            <div className="modal-meta">
              <div className="modal-meta-label">Duration</div>
              <div className="modal-meta-value">{course.days} Day{course.days>1?'s':''}</div>
            </div>
            <div className="modal-meta">
              <div className="modal-meta-label">Category</div>
              <div className="modal-meta-value">{isRescue?'Rescue':'Height'}</div>
            </div>
            <div className="modal-meta">
              <div className="modal-meta-label">Price</div>
              <div className="modal-meta-value">TT${course.price}</div>
            </div>
          </div>

          <div className={`modal-section-title ${isRescue?'rescue-title':''}`}>What You'll Learn</div>
          <div className="modal-list">
            {course.learn.map((l,i) => (
              <div key={i} className={`modal-list-item ${isRescue?'rescue-item':''}`}>{l}</div>
            ))}
          </div>

          <div className={`modal-section-title ${isRescue?'rescue-title':''}`}>Who Should Attend</div>
          <div style={{fontSize:'0.9rem', color:'rgba(248,250,255,0.65)', lineHeight:1.7}}>{course.audience}</div>

          <div className={`modal-section-title ${isRescue?'rescue-title':''}`}>Assessment</div>
          <div style={{fontSize:'0.9rem', color:'rgba(248,250,255,0.65)', lineHeight:1.7}}>{course.assessment}</div>

          <button className="modal-btn" style={isRescue?{background:'var(--rescue)'}:{}} onClick={() => { onClose(); document.getElementById('cta').scrollIntoView({behavior:'smooth'}); }}>
            Enroll on This Course →
          </button>
          <div style={{marginTop:12, fontSize:'0.72rem', color:'rgba(248,250,255,0.3)', textAlign:'center'}}>
            VAT inclusive · Group rates available
          </div>
        </div>
      </div>
    </div>
  );
}

function VideoModal({ title, tag, onClose }) {
  useEffect(() => {
    const h = e => { if (e.key === 'Escape') onClose(); };
    window.addEventListener('keydown', h);
    return () => window.removeEventListener('keydown', h);
  }, [onClose]);
  return (
    <div className="modal-overlay" onClick={e => { if (e.target === e.currentTarget) onClose(); }}>
      <div style={{width:'min(900px, 92vw)', background:'var(--navy-mid)', border:'1px solid rgba(255,255,255,0.08)', position:'relative'}}>
        <button onClick={onClose} style={{position:'absolute',top:-48,right:0,background:'none',border:'none',color:'rgba(255,255,255,0.6)',fontSize:'1.5rem',cursor:'pointer'}}>✕</button>
        <div style={{aspectRatio:'16/9', background:'#000', display:'flex', alignItems:'center', justifyContent:'center', flexDirection:'column', gap:12}}>
          <div style={{fontSize:'3rem', opacity:0.3}}>▶</div>
          <div style={{color:'rgba(255,255,255,0.4)', fontSize:'0.85rem'}}>VIDEO PLACEHOLDER</div>
          <div style={{color:'rgba(255,255,255,0.2)', fontSize:'0.72rem'}}>Drop your {tag} video here</div>
        </div>
        <div style={{padding:'20px 28px', fontFamily:"'Bebas Neue', sans-serif", fontSize:'1.4rem', letterSpacing:'0.08em', borderTop:'1px solid rgba(255,255,255,0.06)'}}>
          {title}
        </div>
      </div>
    </div>
  );
}

function CourseRow({ course, onClick }) {
  const isRescue = course.type === 'rescue';
  const goTo = () => window.location.href = courseUrl(course.id);
  return (
    <div className={`course-row ${isRescue?'rescue-row':''}`} onClick={goTo}>
      <div className="course-row-icon">{course.icon}</div>
      <div className="course-row-body">
        <div className="course-row-tags">
          <span className={`crt-tag cat ${isRescue?'rescue':''}`}>{course.code}</span>
          <span className="crt-tag days">{course.days} Day{course.days>1?'s':''}</span>
        </div>
        <div className="course-row-name">{course.name}</div>
        <div className="course-row-blurb">{course.blurb}</div>
      </div>
      <div className="course-row-right">
        <div className="course-row-price">
          <div className="crp-label">Price</div>
          <div className="crp-num">TT${course.price}</div>
          <div className="crp-vat">VAT inclusive</div>
        </div>
        <button className="course-row-cta" style={isRescue?{background:'var(--rescue)'}:{}} onClick={(e)=>{e.stopPropagation(); goTo();}}>
          Details →
        </button>
      </div>
    </div>
  );
}

function App() {
  const [selectedCourse, setSelectedCourse] = useState(null);
  const [video, setVideo] = useState(null);

  useEffect(() => {
    // 3D rescue scene (scaffold tower + suspended worker on a lifeline) in the hero
    const mount = document.getElementById('hero3d-mount');
    let rig = null;
    if (mount && window.createScaffold3D) {
      rig = window.createScaffold3D({ container: mount, variant: 'rescue', bays: 1, lifts: 3 });
    }
    // GSAP owns reveals / counters; else fall back to IntersectionObserver
    let cleanupObs = null;
    if (!(window.RadianMotion && window.RadianMotion.init())) {
      const obs = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); }});
      }, { threshold: 0.1 });
      document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
      cleanupObs = () => obs.disconnect();
    }
    return () => { if (rig && rig.destroy) rig.destroy(); if (cleanupObs) cleanupObs(); };
  }, []);

  return (
    <>
      {/* ── TRACK 1: WORKING AT HEIGHT ── */}
      <section className="programme-track">
        <div className="programme-inner">
          <div className="track-header">
            <div className="reveal">
              <div className="section-label">Track 01 · Working at Height</div>
              <h2 className="section-title" data-crane="1">FALL<br/><span className="dim">PROTECTION</span></h2>
              <div className="track-features">
                <div className="track-feature"><span className="track-feature-icon">✓</span> Risk Assessment</div>
                <div className="track-feature"><span className="track-feature-icon">✓</span> Equipment Selection</div>
                <div className="track-feature"><span className="track-feature-icon">✓</span> PPE Use</div>
                <div className="track-feature"><span className="track-feature-icon">✓</span> Safe Practices</div>
              </div>
            </div>
            <div className="reveal" style={{paddingTop:60}}>
              <p>Our Getmie Safe Working at Height course covers <strong style={{color:'var(--orange-light)'}}>risk assessment, equipment selection, PPE use, and safe work practices at height</strong>.</p>
              <p>It's not only designed for scaffolders, but all personnel exposed to fall risks — making it one of the most universally vital programmes we offer.</p>
            </div>
          </div>

          {/* Media: Video + Image for Working at Height section */}
          <div className="track-media reveal">
            <div className="track-video" onClick={() => setVideo({title:'Working at Height — Safety in Practice', tag:'Working at Height'})}>
              <div className="tv-stripe"></div>
              <div className="tv-placeholder-text">VIDEO PLACEHOLDER · 16:9</div>
              <div className="tv-play">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="white"><polygon points="5,3 19,12 5,21"/></svg>
              </div>
              <div className="tv-label">
                <div className="tv-tag">Working at Height</div>
                <div className="tv-title">Safety in Practice — Course Overview</div>
              </div>
            </div>
            <div className="img-frame">
              <image-slot id="gms-wah-photo" shape="rect" placeholder="Drop a working-at-height photo" style={{width:'100%',height:'100%'}}></image-slot>
              <div className="img-caption">
                <div className="img-caption-tag">Working at Height</div>
                <div className="img-caption-text">PPE & Fall Protection in Practice</div>
              </div>
            </div>
          </div>

          <div className="courses-list">
            {WAH_COURSES.map(c => (
              <CourseRow key={c.id} course={c} onClick={() => setSelectedCourse(c)}/>
            ))}
          </div>
        </div>
      </section>

      <div className="pole-divider"/>

      {/* ── TRACK 2: RESCUE ── */}
      <section className="programme-track alt-bg">
        <div className="programme-inner">
          <div className="track-header">
            <div className="reveal">
              <div className="section-label" style={{color:'var(--rescue)'}}>Track 02 · Rescue Training</div>
              <h2 className="section-title">RESCUE<br/><span className="dim">SYSTEM</span></h2>
              <div className="track-features">
                <div className="track-feature"><span className="track-feature-icon" style={{color:'var(--rescue)'}}>✓</span> Rescue Techniques</div>
                <div className="track-feature"><span className="track-feature-icon" style={{color:'var(--rescue)'}}>✓</span> Emergency Equipment</div>
                <div className="track-feature"><span className="track-feature-icon" style={{color:'var(--rescue)'}}>✓</span> Team Response</div>
                <div className="track-feature"><span className="track-feature-icon" style={{color:'var(--rescue)'}}>✓</span> Rapid Response</div>
              </div>
            </div>
            <div className="reveal" style={{paddingTop:60}}>
              <p>For persons working at height, <strong style={{color:'var(--rescue)'}}>rescue is critical and can be life-saving</strong>. A successful rescue in minimum time is imperative and can be achieved with suitable equipment and training.</p>
              <p>Our Getmie Safe Rescue Training is focused on safe rescue techniques, emergency equipment use, and team-based response using the Getmie Safe Rescue System.</p>
            </div>
          </div>

          {/* Critical rescue callout */}
          <div className="rescue-banner reveal">
            <div className="rescue-banner-icon">⚠️</div>
            <div>
              <div className="rescue-banner-title">RESCUE IS A LIFE-SAVING SKILL</div>
              <div className="rescue-banner-text">A successful rescue in minimum time is imperative — every second counts when a worker is suspended after a fall. Our training prepares your team for real emergency scenarios using the Getmie Safe Rescue System.</div>
            </div>
          </div>

          {/* Media: Video + Image for Rescue section */}
          <div className="track-media reveal">
            <div className="track-video" onClick={() => setVideo({title:'Getmie Safe Rescue System — Live Training', tag:'Rescue Training'})}>
              <div className="tv-stripe"></div>
              <div className="tv-placeholder-text">VIDEO PLACEHOLDER · 16:9</div>
              <div className="tv-play" style={{background:'rgba(255,112,112,0.9)'}}>
                <svg width="32" height="32" viewBox="0 0 24 24" fill="white"><polygon points="5,3 19,12 5,21"/></svg>
              </div>
              <div className="tv-label">
                <div className="tv-tag" style={{color:'var(--rescue)'}}>Rescue Training</div>
                <div className="tv-title">Getmie Safe Rescue System — In Action</div>
              </div>
            </div>
            <div className="img-frame rescue">
              <image-slot id="gms-rescue-photo" shape="rect" placeholder="Drop a rescue training photo" style={{width:'100%',height:'100%'}}></image-slot>
              <div className="img-caption">
                <div className="img-caption-tag rescue">Rescue Training</div>
                <div className="img-caption-text">Team-Based Emergency Response</div>
              </div>
            </div>
          </div>

          <div className="courses-list">
            {RESCUE_COURSES.map(c => (
              <CourseRow key={c.id} course={c} onClick={() => setSelectedCourse(c)}/>
            ))}
          </div>
        </div>
      </section>

      <div className="pole-divider"/>

      {/* CTA */}
      <section className="cta-section" id="cta">
        <div className="section-label reveal" style={{justifyContent:'center'}}>Train For Safety</div>
        <h2 className="cta-title reveal" data-crane="1">PREPARE<br/>FOR THE<br/><span style={{color:'var(--orange)'}}>UNEXPECTED</span></h2>
        <p className="cta-sub reveal">Enroll in Getmie Safe training today. Protect your team, comply with safety standards, and be ready when rescue counts most.</p>
        <div className="cta-actions reveal">
          <button className="btn-primary" style={{padding:'18px 52px', fontSize:'1rem'}} onClick={() => window.location.href=courseUrl('gms-wah')}>Enroll Now</button>
          <button className="btn-outline" style={{padding:'16px 52px', fontSize:'1rem'}} onClick={() => window.location.href=U.home+'#calendar'}>View Schedule</button>
        </div>
      </section>

      {/* FOOTER */}
      <div className="footer-bottom">
        <p>© 2026 Radian H.A. Limited Training. All rights reserved.</p>
        <p style={{color:'rgba(248,250,255,0.2)', fontSize:'0.72rem'}}>CISRS Accredited Scaffold Training Provider</p>
      </div>

      {selectedCourse && <CourseModal course={selectedCourse} onClose={() => setSelectedCourse(null)}/>}
      {video && <VideoModal title={video.title} tag={video.tag} onClose={() => setVideo(null)}/>}
    </>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App/>);
</script>

<?php get_footer(); ?>

