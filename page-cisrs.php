<?php get_header(); ?>

<!-- HERO -->
<div class="page-hero">
  <div class="page-hero-bg"></div>
  <div class="page-hero-inner">
    <div class="page-hero-content">
      <div class="cisrs-pill">CISRS OTS</div>
      <h1>SCAFFOLDING<br/><span class="dim">TRAINING</span></h1>
      <p>The CISRS Overseas Scheme — a comprehensive range of internationally-recognised scaffolding courses for operatives, supervisors, managers, and inspectors.</p>
      <div class="page-hero-stats">
        <div class="ph-stat">
          <div class="ph-stat-num" data-count="6">6</div>
          <div class="ph-stat-label">Courses Available</div>
        </div>
        <div class="ph-stat">
          <div class="ph-stat-num" data-count="4">4</div>
          <div class="ph-stat-label">Skill Levels</div>
        </div>
        <div class="ph-stat">
          <div class="ph-stat-num" data-count="20" data-suffix="+">20+</div>
          <div class="ph-stat-label">Years Experience</div>
        </div>
        <div class="ph-stat">
          <div class="ph-stat-num" data-count="1500" data-suffix="+" data-sep>1,500+</div>
          <div class="ph-stat-label">Trained</div>
        </div>
      </div>
    </div>
    <div class="hero-3d-wrap">
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

const COURSES = [
  {
    id: 'cisrs-l1',
    code: 'CISRS-L1',
    name: 'CISRS OSTS Scaffolder Level 1',
    level: 'Level 1',
    days: 5,
    price: '8,437.50',
    icon: '🏗️',
    blurb: 'Entry-level course covering the fundamentals of safe scaffolding erection, dismantling, and inspection for new operatives.',
    learn: [
      'Safe handling of scaffold components',
      'Basic erection of independent and putlog scaffolds',
      'Use of personal protective equipment (PPE)',
      'Understanding scaffold loading and stability',
      'Site safety procedures and risk awareness',
    ],
    prereq: 'No prior scaffolding experience required. Suitable for entry-level operatives.',
    assessment: 'Practical demonstration + written assessment + viva',
  },
  {
    id: 'cisrs-l2',
    code: 'CISRS-L2',
    name: 'CISRS OSTS Scaffolder Level 2',
    level: 'Level 2',
    days: 5,
    price: '8,437.50',
    icon: '🔧',
    blurb: 'Intermediate course building on Level 1, covering more complex scaffolding structures, including birdcage and tube and fitting.',
    learn: [
      'Erection of birdcage scaffolds',
      'Tube and fitting scaffold systems',
      'Complex tie arrangements and bracing',
      'Cantilever and putlog applications',
      'Advanced risk assessment',
    ],
    prereq: 'CISRS Level 1 certification required.',
    assessment: 'Practical demonstration + written assessment + viva',
  },
  {
    id: 'cisrs-l3',
    code: 'CISRS-L3',
    name: 'CISRS OSTS Scaffolder Level 3 (Advanced)',
    level: 'Level 3',
    days: 5,
    price: '9,000.00',
    icon: '⚙️',
    blurb: 'Advanced course for experienced scaffolders covering specialist structures, suspended scaffolds, and complex configurations.',
    learn: [
      'Suspended scaffold systems',
      'Specialist and bespoke configurations',
      'Truss-out and outrigger scaffolds',
      'Loading bays and protection fans',
      'Complex multi-storey scaffolds',
    ],
    prereq: 'CISRS Level 2 certification + minimum 12 months experience.',
    assessment: 'Practical demonstration + written assessment + viva',
  },
  {
    id: 'cisrs-basic-inspection',
    code: 'CISRS-BI',
    name: 'CISRS OSTS Basic Scaffolder Inspection',
    level: 'Inspection',
    days: 3,
    price: '6,750.00',
    icon: '🔍',
    blurb: 'Comprehensive training for conducting basic scaffold inspections in compliance with British standards and statutory requirements.',
    learn: [
      'Scaffold inspection procedures',
      'Identifying common defects and risks',
      'Statutory inspection requirements',
      'Completing inspection records and reports',
      'Communicating findings effectively',
    ],
    prereq: 'Basic scaffolding knowledge or qualification recommended.',
    assessment: 'Practical inspection + written assessment',
  },
  {
    id: 'cisrs-advanced-inspection',
    code: 'CISRS-AI',
    name: 'CISRS OSTS Advanced Scaffolder Inspection',
    level: 'Inspection',
    days: 2,
    price: '6,500.00',
    icon: '🛠️',
    blurb: 'Advanced inspection training for senior personnel responsible for complex scaffold structures and specialist inspections.',
    learn: [
      'Inspection of complex scaffold structures',
      'Loading and structural calculations review',
      'Specialist scaffold configurations',
      'Advanced reporting and compliance',
      'Risk-based inspection methodology',
    ],
    prereq: 'CISRS Basic Inspection certification required.',
    assessment: 'Practical inspection + written assessment',
  },
  {
    id: 'cisrs-supervisor',
    code: 'CISRS-SUP',
    name: 'CISRS OSTS Scaffolder Supervisor',
    level: 'Supervision',
    days: 3,
    price: '7,875.00',
    icon: '👷',
    blurb: 'Training for scaffolding supervisors covering site leadership, team management, safety oversight, and quality control.',
    learn: [
      'Site supervision and team management',
      'Safety oversight and toolbox talks',
      'Quality control and compliance',
      'Coordinating multi-team operations',
      'Statutory duties of a supervisor',
    ],
    prereq: 'CISRS Level 2 minimum + supervisory experience recommended.',
    assessment: 'Practical leadership scenarios + written assessment',
  },
];

const FOCUS_AREAS = [
  { num: '01', title: 'Scaffolding Operative Training', desc: 'Covers different skill levels from beginner to advanced scaffolder.' },
  { num: '02', title: 'Scaffold Inspection Training', desc: 'Enables individuals to conduct safe and compliant scaffold inspections.' },
  { num: '03', title: 'Management & Supervision', desc: 'Designed for supervisors, managers, and site leads.' },
  { num: '04', title: 'Scaffolding Awareness', desc: 'Provides a general understanding for those overseeing scaffolding operations.' },
];

function CourseModal({ course, onClose }) {
  useEffect(() => {
    const h = e => { if (e.key === 'Escape') onClose(); };
    window.addEventListener('keydown', h);
    document.body.style.overflow = 'hidden';
    return () => { window.removeEventListener('keydown', h); document.body.style.overflow = ''; };
  }, [onClose]);

  return (
    <div className="modal-overlay" onClick={e => { if (e.target === e.currentTarget) onClose(); }}>
      <div className="modal">
        <div className="modal-top">
          <button className="modal-close" onClick={onClose}>✕</button>
          <div className="mt-tag">{course.code} · CISRS OTS</div>
          <h3>{course.name}</h3>
        </div>
        <div className="modal-body">
          <div className="modal-meta-grid">
            <div className="modal-meta">
              <div className="modal-meta-label">Duration</div>
              <div className="modal-meta-value">{course.days} Days</div>
            </div>
            <div className="modal-meta">
              <div className="modal-meta-label">Level</div>
              <div className="modal-meta-value">{course.level}</div>
            </div>
            <div className="modal-meta">
              <div className="modal-meta-label">Price</div>
              <div className="modal-meta-value">TT${course.price}</div>
            </div>
          </div>

          <div className="modal-section-title">What You'll Learn</div>
          <div className="modal-list">
            {course.learn.map((l,i)=>(<div key={i} className="modal-list-item">{l}</div>))}
          </div>

          <div className="modal-section-title">Prerequisites</div>
          <div style={{fontSize:'0.9rem', color:'rgba(248,250,255,0.65)', lineHeight:1.7}}>{course.prereq}</div>

          <div className="modal-section-title">Assessment</div>
          <div style={{fontSize:'0.9rem', color:'rgba(248,250,255,0.65)', lineHeight:1.7}}>{course.assessment}</div>

          <button className="modal-btn" onClick={() => { onClose(); document.getElementById('cta').scrollIntoView({behavior:'smooth'}); }}>
            Enroll on This Course →
          </button>
          <div style={{marginTop:12, fontSize:'0.72rem', color:'rgba(248,250,255,0.3)', textAlign:'center'}}>
            VAT inclusive · Contact us for group rates and bulk Enrollments
          </div>
        </div>
      </div>
    </div>
  );
}

function VideoModal({ onClose }) {
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
          <div style={{color:'rgba(255,255,255,0.2)', fontSize:'0.72rem'}}>Drop your CISRS training video here</div>
        </div>
        <div style={{padding:'20px 28px', fontFamily:"'Bebas Neue', sans-serif", fontSize:'1.6rem', letterSpacing:'0.08em', borderTop:'1px solid rgba(255,255,255,0.06)'}}>
          CISRS OTS Training Programme — Overview
        </div>
      </div>
    </div>
  );
}

function App() {
  const [selectedCourse, setSelectedCourse] = useState(null);
  const [showVideo, setShowVideo] = useState(false);

  useEffect(() => {
    // 3D scaffold (inspection motif) in the hero
    const mount = document.getElementById('hero3d-mount');
    let rig = null;
    if (mount && window.createScaffold3D) {
      rig = window.createScaffold3D({ container: mount, variant: 'structure', bays: 2, lifts: 3 });
    }
    // GSAP owns reveals / counters / horizontal strip; else fall back to IntersectionObserver
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
      {/* OVERVIEW */}
      <section className="overview-section">
        <div className="overview-grid">
          <div className="reveal">
            <div className="section-label">About CISRS OTS</div>
            <h2 className="section-title" data-crane="1">A COMPLETE<br/><span className="dim">PROGRAMME</span></h2>
            <div className="overview-text" style={{marginTop:32}}>
              <p>The CISRS Overseas Scheme provides a comprehensive range of courses for scaffolding operatives, supervisors, managers, and scaffold inspectors.</p>
              <p>These courses incorporate both <strong style={{color:'var(--orange-light)'}}>practical and theoretical elements</strong> with full assessment, ensuring industry-recognised competence at every level.</p>
            </div>
          </div>

          <div className="reveal">
            <div className="section-label">Four Focus Areas</div>
            <div className="sow" style={{marginTop:32}}>
              <div className="sow-head">
                <span className="sow-title">SCOPE OF WORKS — CISRS OTS</span>
                <span className="sow-no">Sheet RHA-SOW-01</span>
              </div>
              {FOCUS_AREAS.map(a => (
                <div className="sow-row" key={a.num}>
                  <span className="sow-num">{a.num}</span>
                  <span>
                    <span className="sow-item-title">{a.title}</span>
                    <span className="sow-item-desc">{a.desc}</span>
                  </span>
                </div>
              ))}
              <div className="sow-foot">— every level assessed to the same CISRS standard.</div>
            </div>
          </div>
        </div>
        <div className="img-frame reveal" style={{marginTop:64, aspectRatio:'21/8'}}>
          <image-slot id="cisrs-overview" shape="rect" placeholder="Drop a CISRS training / facility photo" style={{width:'100%',height:'100%'}}></image-slot>
          <div className="img-caption">
            <div className="img-caption-tag">Our Facility</div>
            <div className="img-caption-text">Purpose-Built Scaffolding Training Ground</div>
          </div>
        </div>
      </section>

      <div className="pole-divider"/>

      {/* COURSES — horizontal pinned strip */}
      <section className="hstrip" id="courses" data-hsection>
        <div className="hstrip-track" data-htrack>
          <div className="hstrip-intro">
            <div className="section-label">CISRS OTS Catalogue</div>
            <h2 className="section-title">SIX<br/><span className="dim">SPECIALIST</span><br/>COURSES</h2>
            <p style={{marginTop:18, fontSize:'0.95rem', color:'rgba(248,250,255,0.55)', lineHeight:1.7, maxWidth:300}}>
              Both practical and theoretical, with full CISRS assessment. Scroll across to explore the catalogue.
            </p>
            <div className="scroll-arrow"><span className="bar"></span> Scroll</div>
          </div>
          {COURSES.map((c, idx) => (
            <div className="course-card" key={c.id} onClick={() => window.location.href=courseUrl(c.id)}>
              <div className="course-card-bg-num">{String(idx+1).padStart(2,'0')}</div>
              <div className="course-card-icon">{c.icon}</div>
              <div className="course-tag-row">
                <span className="course-tag cisrs">{c.code}</span>
                <span className="course-tag days">{c.days} Days</span>
                <span className="course-tag level">{c.level}</span>
              </div>
              <div className="course-name">{c.name}</div>
              <div className="course-blurb">{c.blurb}</div>
              <div className="course-bottom">
                <div className="course-price-block">
                  <div className="course-price-label">Course Fee</div>
                  <div className="course-price">TT${c.price}</div>
                  <div className="course-price-vat">VAT inclusive</div>
                </div>
                <button className="course-enrol" onClick={(e) => { e.stopPropagation(); window.location.href=courseUrl(c.id); }}>
                  Details →
                </button>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* GALLERY */}
      <section className="courses-section">
        <div className="courses-inner">
          <div className="gallery-grid reveal">
            <div className="img-frame g-wide">
              <image-slot id="cisrs-gallery-1" shape="rect" placeholder="Drop a photo" style={{width:'100%',height:'100%'}}></image-slot>
            </div>
            <div className="img-frame">
              <image-slot id="cisrs-gallery-2" shape="rect" placeholder="Drop a photo" style={{width:'100%',height:'100%'}}></image-slot>
            </div>
            <div className="img-frame">
              <image-slot id="cisrs-gallery-3" shape="rect" placeholder="Drop a photo" style={{width:'100%',height:'100%'}}></image-slot>
            </div>
          </div>
        </div>
      </section>

      <div className="pole-divider"/>

      {/* VIDEO */}
      <section className="video-section">
        <div className="video-inner">
          <div className="section-label reveal" style={{justifyContent:'center'}}>See It In Action</div>
          <h2 className="section-title reveal">WATCH THE<br/><span className="dim">CISRS OTS OVERVIEW</span></h2>
          <p className="reveal" style={{marginTop:20, fontSize:'1rem', color:'rgba(248,250,255,0.55)', maxWidth:560, marginLeft:'auto', marginRight:'auto', lineHeight:1.7}}>
            Get an inside look at our CISRS Overseas Scheme training — facilities, instructors, and the practical assessments that prepare you for a successful career.
          </p>

          <div className="video-frame reveal" onClick={() => setShowVideo(true)}>
            <div className="vf-stripe"></div>
            <div className="vf-placeholder-text">VIDEO PLACEHOLDER · 16:9</div>
            <div className="vf-play">
              <svg width="36" height="36" viewBox="0 0 24 24" fill="white"><polygon points="5,3 19,12 5,21"/></svg>
            </div>
            <div className="vf-label">
              <div className="vf-tag">CISRS OTS Training</div>
              <div className="vf-title">Programme Overview & Facilities</div>
            </div>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="cta-section" id="cta">
        <div className="section-label reveal" style={{justifyContent:'center'}}>Build Your Future</div>
        <h2 className="cta-title reveal" data-crane="1">START YOUR<br/><span style={{color:'var(--orange)'}}>CISRS</span> JOURNEY</h2>
        <p className="cta-sub reveal">Enroll in CISRS OTS training today. Get industry-recognised qualifications and advance your scaffolding career with confidence.</p>
        <div className="cta-actions reveal">
          <button className="btn-primary" style={{padding:'18px 52px', fontSize:'1rem'}} onClick={() => window.location.href=courseUrl('cisrs-l1')}>Enroll Now</button>
          <button className="btn-outline" style={{padding:'16px 52px', fontSize:'1rem'}} onClick={() => window.location.href=U.home+'#calendar'}>View Schedule</button>
        </div>
      </section>

      {/* FOOTER */}
      <div className="footer-bottom">
        <p>© 2026 Radian H.A. Limited Training. All rights reserved.</p>
        <p style={{color:'rgba(248,250,255,0.2)', fontSize:'0.72rem'}}>CISRS Accredited Scaffold Training Provider</p>
      </div>

      {selectedCourse && <CourseModal course={selectedCourse} onClose={() => setSelectedCourse(null)}/>}
      {showVideo && <VideoModal onClose={() => setShowVideo(false)}/>}
    </>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App/>);
</script>

<?php get_footer(); ?>

