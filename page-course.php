<?php get_header(); ?>

<div id="root"></div>

<script type="text/babel">
/* ── Printable course brief: opens a one-pager and triggers print/save-as-PDF ── */
function downloadBrief(course, courseId) {
  if (!course) return;
  const esc = (s) => String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;');
  const list = (v) => {
    if (!v) return '';
    const arr = Array.isArray(v) ? v : [v];
    return '<ul>' + arr.map(x => {
      if (typeof x === 'string') return '<li>' + x + '</li>';
      if (x && typeof x === 'object') {
        const head = x.title || x.name || '';
        const items = Array.isArray(x.items || x.topics) ? (x.items || x.topics) : [];
        return '<li><b>' + esc(head) + '</b>' + (items.length ? '<ul>' + items.map(t=>'<li>'+esc(t)+'</li>').join('') + '</ul>' : '') + '</li>';
      }
      return '';
    }).join('') + '</ul>';
  };
  const block = (label, html) => html ? '<div class="sec"><h2>' + label + '</h2>' + html + '</div>' : '';

  const w = window.open('', '_blank');
  if (!w) return;
  w.document.write('<!DOCTYPE html><html><head><title>' + esc(course.title) + ' — Course Brief</title><style>'
    + '@page{margin:18mm;} *{box-sizing:border-box;margin:0;padding:0;}'
    + 'body{font-family:Segoe UI,Arial,sans-serif;color:#16243c;font-size:11.5px;line-height:1.6;padding:28px;}'
    + '.bar{display:flex;height:7px;margin-bottom:18px;}'
    + '.bar span{flex:1;}'
    + '.brand{display:flex;justify-content:space-between;align-items:baseline;border-bottom:3px solid #0a1628;padding-bottom:10px;margin-bottom:18px;}'
    + '.brand b{font-size:17px;letter-spacing:2px;}'
    + '.brand i{font-style:normal;color:#888;font-size:10px;letter-spacing:1.5px;text-transform:uppercase;}'
    + 'h1{font-size:23px;color:#0a1628;line-height:1.2;margin-bottom:4px;}'
    + '.code{color:#e8890a;font-weight:700;font-size:11px;letter-spacing:2px;text-transform:uppercase;margin-bottom:16px;}'
    + '.meta{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:#d8dee8;border:1px solid #d8dee8;margin-bottom:20px;}'
    + '.meta div{background:#f4f6fa;padding:8px 12px;}'
    + '.meta small{display:block;color:#777;font-size:8.5px;letter-spacing:1.2px;text-transform:uppercase;}'
    + '.meta b{font-size:12px;}'
    + '.sec{margin-bottom:14px;}'
    + 'h2{font-size:12px;color:#e8890a;letter-spacing:2px;text-transform:uppercase;border-bottom:1px dashed #ccc;padding-bottom:3px;margin-bottom:7px;}'
    + 'ul{margin-left:17px;} li{margin-bottom:3px;}'
    + '.foot{margin-top:22px;border-top:3px solid #0a1628;padding-top:10px;display:flex;justify-content:space-between;color:#777;font-size:9.5px;}'
    + '</style></head><body>'
    + '<div class="bar"><span style="background:#c04080"></span><span style="background:#d83220"></span><span style="background:#7030a0"></span><span style="background:#f07820"></span><span style="background:#f8cc10"></span></div>'
    + '<div class="brand"><b>RADIAN H.A. LIMITED</b><i>Course Brief · ' + esc(courseId) + '</i></div>'
    + '<h1>' + esc(course.title) + '</h1>'
    + '<div class="code">' + esc(course.category || '') + (course.code ? ' · ' + esc(course.code) : '') + '</div>'
    + '<div class="meta">'
    + '<div><small>Duration</small><b>' + esc(course.duration || '—') + '</b></div>'
    + '<div><small>Training Time</small><b>' + esc(course.trainingTime || '—') + '</b></div>'
    + '<div><small>Type</small><b>' + esc(course.courseType || '—') + '</b></div>'
    + '<div><small>Price</small><b>TT$' + esc(course.price || '—') + '</b></div>'
    + '</div>'
    + block('Who Should Attend', course.whoShouldAttend ? '<p>' + course.whoShouldAttend + '</p>' : '')
    + block('Prerequisites', course.prerequisites ? '<p>' + course.prerequisites + '</p>' : '')
    + block('Objectives', list(course.objectives))
    + block('Curriculum', list(course.curriculum))
    + block('Certification', course.certifications ? (Array.isArray(course.certifications) ? list(course.certifications) : '<p>' + esc(course.certifications) + '</p>') : '')
    + block('PPE', course.ppe ? (Array.isArray(course.ppe) ? list(course.ppe) : '<p>' + esc(course.ppe) + '</p>') : '')
    + '<div class="foot"><span>training@radianhalimited.com · +1 (868) 555-0142</span><span>CISRS OTS Approved Training Centre · Trinidad &amp; Tobago</span></div>'
    + '</body></html>');
  w.document.close();
  w.onload = () => { w.focus(); w.print(); };
}
const { useState, useEffect, useMemo } = React;
const U = window.RADIAN_URLS || {};
const courseUrl = (id) => U.course + '?id=' + id;
const EnrollUrl  = (id) => U.Enroll + '?id=' + id;

function getCourseId() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id') || 'cisrs-l1';
}

function NotFound() {
  return (
    <div className="not-found">
      <h2>Course Not Found</h2>
      <p>We couldn't find the course you were looking for.</p>
      <a href={U.cisrs} style={{padding:'14px 32px', background:'var(--orange)', color:'var(--navy)', textDecoration:'none', fontWeight:700, fontSize:'0.88rem', letterSpacing:'0.1em', textTransform:'uppercase'}}>← Back to CISRS OTS</a>
    </div>
  );
}

function App() {
  const [courseId, setCourseId] = useState(getCourseId());
  const [tab, setTab] = useState('overview');

  useEffect(() => {
    const handler = () => { setCourseId(getCourseId()); setTab('overview'); window.scrollTo(0, 0); };
    window.addEventListener('popstate', handler);
    return () => window.removeEventListener('popstate', handler);
  }, []);

  const course = window.COURSES_DATA[courseId];

  useEffect(() => {
    if (course) {
      document.title = course.title + ' — Radian H.A. Limited Training';
    }
  }, [course]);

  useEffect(() => {
    const obs = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); }});
    }, { threshold: 0.1 });
    document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
    return () => obs.disconnect();
  }, [tab, courseId]);

  if (!course) return <><div className="not-found"><NotFound/></div></>;

  const isRescue = course.category === 'rescue' || course.category === 'wah';
  const parentPage = course.category === 'cisrs' ? U.cisrs : U.getmie;
  const parentName = course.category === 'cisrs' ? 'CISRS OTS' : 'Getmie Safe';

  const related = Object.entries(window.COURSES_DATA)
    .filter(([id, c]) => id !== courseId && c.category === course.category)
    .slice(0, 3);

  return (
    <>
      {/* HERO */}
      <div className="course-hero">
        <div className="course-hero-bg"></div>
        <div className="course-hero-inner">
          <div className="breadcrumb">
            <a href={U.home}>Home</a>
            <span className="sep">/</span>
            <a href={parentPage}>{parentName}</a>
            <span className="sep">/</span>
            <span className="current">{course.title}</span>
          </div>
          <div className="course-hero-grid">
            <div>
              <div className="course-hero-tags">
                <span className={`ch-tag code ${course.category!=='cisrs'?'rescue':''}`}>{course.code}</span>
                <span className="ch-tag type">{course.courseType}</span>
              </div>
              <h1>{course.title}</h1>
              <div className="jp-stamp">Method Statement · Job Pack {course.code}</div>
            </div>
            <div className="course-meta-cards">
              <div className="meta-card">
                <div className="meta-card-label">Duration</div>
                <div className="meta-card-value">{course.duration}</div>
              </div>
              <div className="meta-card">
                <div className="meta-card-label">Training Time</div>
                <div className="meta-card-value" style={{fontSize:'1rem'}}>{course.trainingTime}</div>
              </div>
              <div className="meta-card price" style={{gridColumn:'1/-1'}}>
                <div className="meta-card-label">Course Fee</div>
                <div className="meta-card-value">TT${course.price}</div>
                <div className="vat">VAT inclusive</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* TABS */}
      <div className="tabs-section">
        <div className="tabs-bar">
          <button className={`tab-btn ${tab==='overview'?'active':''}`} onClick={()=>setTab('overview')}>
            <span className="tab-num">01</span>Overview
          </button>
          <button className={`tab-btn ${tab==='curriculum'?'active':''}`} onClick={()=>setTab('curriculum')}>
            <span className="tab-num">02</span>Curriculum
          </button>
        </div>

        <div className="content-grid">
          <div className="content-main">
            {tab === 'overview' ? (
              <div className="tab-panel" key="overview">
                <h2 className="section-h reveal">Outline</h2>
                <div className="outline-block reveal">
                  <div className="outline-block-label">Who Should Attend</div>
                  <div className="outline-block-text">{course.whoShouldAttend}</div>
                </div>
                <div className="outline-block reveal">
                  <div className="outline-block-label">Prerequisites</div>
                  <div className="outline-block-text" dangerouslySetInnerHTML={{__html: course.prerequisites}}/>
                </div>
                <div className="outline-block reveal">
                  <div className="outline-block-label">Course Objectives</div>
                  <div className="outline-block-text">{course.objectives}</div>
                </div>
                {course.specifications && (
                  <div className="outline-block reveal">
                    <div className="outline-block-label">Specifications</div>
                    <div className="outline-block-text" dangerouslySetInnerHTML={{__html: course.specifications}}/>
                  </div>
                )}
                {course.careerPath && (
                  <div className="outline-block reveal">
                    <div className="outline-block-label">Career Path</div>
                    <div className="outline-block-text">{course.careerPath}</div>
                  </div>
                )}

                <h2 className="section-h reveal">PPE Requirements</h2>
                <p className="reveal" style={{marginBottom:20, fontSize:'0.95rem', color:'rgba(248,250,255,0.6)', lineHeight:1.8}}>
                  Delegates attending {course.category==='cisrs' || course.code==='GMS-WAH' || course.category==='rescue' || course.category==='wah'?'any of our practical courses':'this course'} are required to be fully outfitted in PPE:
                </p>
                <div className="ppe-list reveal">
                  {course.ppe.map((p, i) => (
                    <div className="ppe-item" key={i}>
                      <span className="ppe-item-icon">✓</span>
                      <span>{p}</span>
                    </div>
                  ))}
                </div>
                {course.ppeNote && (
                  <div className="ppe-note reveal">{course.ppeNote}</div>
                )}

                <h2 className="section-h reveal">Certifications</h2>
                <div className="cert-block reveal">
                  <div className="cert-block-icon">📜</div>
                  <div className="cert-block-text" dangerouslySetInnerHTML={{__html: course.certifications}}/>
                </div>
              </div>
            ) : (
              <div className="tab-panel" key="curriculum">
                <h2 className="section-h reveal">Course Contents</h2>
                <div className="curr-grid">
                  <div className="curr-block theory reveal">
                    <div className="curr-block-label">Track A</div>
                    <div className="curr-block-title">{course.curriculum.theoryTitle || 'Core Theory Units'}</div>
                    <div className="curr-list">
                      {course.curriculum.theory.map((item, i) => (
                        <div key={i} className="curr-item">
                          <span className="curr-item-num">{String(i+1).padStart(2,'0')}</span>
                          <span>
                            {typeof item === 'string' ? item : (
                              <>
                                {item.title}
                                {item.sub && <span className="curr-item-sub">{item.sub}</span>}
                                {item.bullets && (
                                  <ul className="curr-item-bullets">
                                    {item.bullets.map((b,j)=><li key={j}>{b}</li>)}
                                  </ul>
                                )}
                              </>
                            )}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>

                  {course.curriculum.practical && (
                    <div className="curr-block practical reveal">
                      <div className="curr-block-label">Track B</div>
                      <div className="curr-block-title">{course.curriculum.practicalTitle || 'Practical Units'}</div>
                      <div className="curr-list">
                        {course.curriculum.practical.map((item, i) => (
                          <div key={i} className="curr-item">
                            <span className="curr-item-num">{String(i+1).padStart(2,'0')}</span>
                            <span>
                              {typeof item === 'string' ? item : (
                                <>
                                  {item.title}
                                  {item.sub && <span className="curr-item-sub">{item.sub}</span>}
                                  {item.bullets && (
                                    <ul className="curr-item-bullets">
                                      {item.bullets.map((b,j)=><li key={j}>{b}</li>)}
                                    </ul>
                                  )}
                                </>
                              )}
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>

          {/* SIDEBAR */}
          <div className="sidebar">
            <div className="sb-card Enroll">
              <div className="sb-card-title">Enroll Now</div>
              <div className="sb-price-large">TT${course.price}</div>
              <div className="sb-price-vat">VAT inclusive</div>
              <button className="sb-btn" onClick={()=>window.location.href=EnrollUrl(courseId)}>
                <span>Enroll on Course</span><span>→</span>
              </button>
              <button className="sb-btn outline" onClick={()=>window.location.href=U.home+'#calendar'}>View Dates</button>
              <button className="sb-btn outline" onClick={()=>downloadBrief(course, courseId)}>⬇ Course Brief (PDF)</button>
            </div>

            <div className="sb-card">
              <div className="sb-card-title">Course Facts</div>
              <div className="sb-fact-row">
                <span className="sb-fact-label">Course Code</span>
                <span className="sb-fact-value">{course.code}</span>
              </div>
              <div className="sb-fact-row">
                <span className="sb-fact-label">Duration</span>
                <span className="sb-fact-value">{course.duration}</span>
              </div>
              <div className="sb-fact-row">
                <span className="sb-fact-label">Daily Hours</span>
                <span className="sb-fact-value" style={{fontSize:'0.78rem'}}>{course.trainingTime}</span>
              </div>
              <div className="sb-fact-row">
                <span className="sb-fact-label">Category</span>
                <span className="sb-fact-value">{course.courseType}</span>
              </div>
              <div className="sb-fact-row">
                <span className="sb-fact-label">Certificate</span>
                <span className="sb-fact-value">5 Years Valid</span>
              </div>
            </div>

            <div className="sb-card">
              <div className="sb-card-title">Need Help?</div>
              <div style={{fontSize:'0.85rem', color:'rgba(248,250,255,0.6)', lineHeight:1.7, marginBottom:14}}>
                Questions about this course or group Enrollments?
              </div>
              <div style={{fontSize:'0.82rem', color:'var(--orange-light)', marginBottom:6}}>📧 training@radianhalimited.com</div>
              <div style={{fontSize:'0.82rem', color:'rgba(248,250,255,0.6)'}}>📞 Contact us for details</div>
            </div>
          </div>
        </div>
      </div>

      {/* RELATED */}
      {related.length > 0 && (
        <section className="related-section">
          <div className="related-inner">
            <div className="related-header">
              <div>
                <div style={{fontSize:'0.72rem', letterSpacing:'0.18em', textTransform:'uppercase', color:'var(--orange)', fontWeight:700, marginBottom:8}}>Explore More</div>
                <div className="related-title">Related {parentName} Courses</div>
              </div>
              <a href={parentPage} style={{color:'var(--orange-light)', textDecoration:'none', fontSize:'0.85rem', fontWeight:600}}>View all courses →</a>
            </div>
            <div className="related-grid">
              {related.map(([id, c]) => (
                <a key={id} href={courseUrl(id)} className="related-card">
                  <div className="related-card-tag">{c.code}</div>
                  <div className="related-card-title">{c.title}</div>
                  <div className="related-card-meta">
                    <span>⏱ {c.duration}</span>
                    <span style={{marginLeft:'auto', color:'var(--orange)'}}>TT${c.price}</span>
                  </div>
                </a>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* FOOTER */}
      <div className="footer-bottom">
        <p>© 2026 Radian H.A. Limited Training. All rights reserved.</p>
        <p style={{color:'rgba(248,250,255,0.2)', fontSize:'0.72rem'}}>CISRS Accredited Scaffold Training Provider</p>
      </div>
    </>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App/>);
</script>

<?php get_footer(); ?>

