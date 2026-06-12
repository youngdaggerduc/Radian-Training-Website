<?php get_header(); ?>

<div id="root"></div>

<script type="text/babel">
const { useState, useEffect, useRef, useCallback } = React;
const U = window.RADIAN_URLS || {};

/* ── Scaffold SVG ── */
function ScaffoldSVG() {
  return (
    <svg className="scaffold-poles" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
      {[80,200,360,520,720,920,1080,1240,1380].map((x,i)=>(
        <line key={`v${i}`} x1={x} y1="0" x2={x} y2="900" stroke="rgba(255,255,255,0.04)" strokeWidth="3"/>
      ))}
      {[120,260,420,600,750].map((y,i)=>(
        <line key={`h${i}`} x1="0" y1={y} x2="1440" y2={y} stroke="rgba(255,255,255,0.035)" strokeWidth="2"/>
      ))}
      {[80,200,360,520,720,920,1080,1240].map(x=>
        [120,260,420,600,750].map((y)=>(
          <circle key={`j${x}${y}`} cx={x} cy={y} r="5" fill="none" stroke="rgba(232,137,10,0.18)" strokeWidth="1.5"/>
        ))
      )}
      <line x1="80" y1="120" x2="200" y2="260" stroke="rgba(255,255,255,0.04)" strokeWidth="2"/>
      <line x1="200" y1="120" x2="360" y2="260" stroke="rgba(255,255,255,0.04)" strokeWidth="2"/>
      <line x1="1240" y1="120" x2="1380" y2="260" stroke="rgba(255,255,255,0.04)" strokeWidth="2"/>
      <line x1="1080" y1="120" x2="1240" y2="260" stroke="rgba(255,255,255,0.04)" strokeWidth="2"/>
    </svg>
  );
}

/* ── Welding Spark Divider ── */
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

/* ── Scroll Progress Bar ── */
function ScrollProgress() {
  const [pct, setPct] = useState(0);
  useEffect(() => {
    const h = () => {
      const h = document.documentElement;
      const max = h.scrollHeight - h.clientHeight;
      setPct(max > 0 ? (h.scrollTop / max) * 100 : 0);
    };
    window.addEventListener('scroll', h);
    return () => window.removeEventListener('scroll', h);
  }, []);
  return (
    <div className="scroll-progress">
      <div className="scroll-progress-fill" style={{width: pct + '%'}}/>
    </div>
  );
}

function FloatingPoles() {
  const poles=[
    {w:3,h:140,left:'8%',delay:'0s',dur:'14s',r:'12deg'},
    {w:2,h:90,left:'18%',delay:'3s',dur:'11s',r:'-8deg'},
    {w:4,h:180,left:'75%',delay:'1s',dur:'16s',r:'5deg'},
    {w:2,h:120,left:'85%',delay:'5s',dur:'12s',r:'-15deg'},
    {w:3,h:100,left:'60%',delay:'2s',dur:'18s',r:'20deg'},
    {w:2,h:160,left:'42%',delay:'7s',dur:'13s',r:'-3deg'},
  ];
  return <>{poles.map((p,i)=>(
    <div key={i} className="float-pole" style={{width:p.w,height:p.h,left:p.left,bottom:'-200px','--r':p.r,animationDuration:p.dur,animationDelay:p.delay}}/>
  ))}</>;
}

/* ── Counter ── */
function useCounter(target, duration=1800) {
  const [count,setCount]=useState(0);
  const [started,setStarted]=useState(false);
  const ref=useRef();
  useEffect(()=>{
    const obs=new IntersectionObserver(([e])=>{
      if(e.isIntersecting&&!started){
        setStarted(true);
        const start=performance.now();
        const tick=(now)=>{
          const t=Math.min((now-start)/duration,1);
          const ease=1-Math.pow(1-t,3);
          setCount(Math.round(ease*target));
          if(t<1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
      }
    },{threshold:0.5});
    if(ref.current) obs.observe(ref.current);
    return ()=>obs.disconnect();
  },[started,target,duration]);
  return [count,ref];
}

/* ── TRAINING DATES DATA — editable in assets/data/site-data.json ── */
const TRAINING_EVENTS = (window.RADIAN_DATA && RADIAN_DATA.events) || [];

const TYPE_COLORS = {
  cisrs: {pill:'type-cisrs', dot:'#e8890a'},
  getmie: {pill:'type-getmie', dot:'#60b4ff'},
  rescue: {pill:'type-rescue', dot:'#ff7070'},
};
const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const DAYS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

/* ── Calendar ── */
const CAL_TYPES = [
  {key:'all',    label:'All Sessions', color:'#8aa4c8'},
  {key:'cisrs',  label:'CISRS',        color:'#e8890a'},
  {key:'getmie', label:'Getmie Safe',  color:'#60b4ff'},
  {key:'rescue', label:'Rescue',       color:'#ff7070'},
];
const spotsLevel = s => s<=5 ? 'low' : s<=9 ? 'mid' : 'high';

function downloadICS(ev) {
  const days = parseInt(ev.duration) || 1;
  const start = ev.date.replace(/-/g,'');
  const endD = new Date(ev.date); endD.setDate(endD.getDate()+days);
  const end = endD.toISOString().slice(0,10).replace(/-/g,'');
  const ics = ['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Radian HA Limited//Training//EN','BEGIN:VEVENT',
    'UID:radian-'+ev.id+'@rhatt.com',
    'DTSTART;VALUE=DATE:'+start,
    'DTEND;VALUE=DATE:'+end,
    'SUMMARY:'+ev.title+' — Radian Training',
    'LOCATION:'+ev.venue,
    'DESCRIPTION:'+ev.duration+' / '+ev.time+' / Radian H.A. Limited Training',
    'END:VEVENT','END:VCALENDAR'].join('\r\n');
  const a = document.createElement('a');
  a.href = 'data:text/calendar;charset=utf-8,'+encodeURIComponent(ics);
  a.download = ev.title.replace(/[^a-z0-9]+/gi,'-').toLowerCase()+'.ics';
  a.click();
}

function Calendar() {
  const today = new Date();
  const [viewYear, setViewYear] = useState(2026);
  const [viewMonth, setViewMonth] = useState(4); // 0-indexed, 4=May
  const [filter, setFilter] = useState('all');
  const [selectedEvent, setSelectedEvent] = useState(null);
  const [slideDir, setSlideDir] = useState('right');
  const [animKey, setAnimKey] = useState(0);

  const navigate = (dir) => {
    setSlideDir(dir);
    setAnimKey(k => k+1);
    let m = viewMonth + (dir === 'right' ? 1 : -1);
    let y = viewYear;
    if (m > 11) { m = 0; y++; }
    if (m < 0) { m = 11; y--; }
    setViewMonth(m);
    setViewYear(y);
  };

  const goToday = () => {
    if (viewMonth === today.getMonth() && viewYear === today.getFullYear()) return;
    setSlideDir((viewYear*12+viewMonth) < (today.getFullYear()*12+today.getMonth()) ? 'right' : 'left');
    setAnimKey(k => k+1);
    setViewMonth(today.getMonth());
    setViewYear(today.getFullYear());
  };

  // keyboard: arrows page the months while the calendar is on screen; Esc closes
  useEffect(() => {
    const h = (e) => {
      if (e.key === 'Escape') { setSelectedEvent(null); return; }
      if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
      const sec = document.getElementById('calendar');
      if (!sec) return;
      const r = sec.getBoundingClientRect();
      if (r.top > window.innerHeight * 0.7 || r.bottom < 160) return;
      navigate(e.key === 'ArrowRight' ? 'right' : 'left');
    };
    window.addEventListener('keydown', h);
    return () => window.removeEventListener('keydown', h);
  });

  // Build calendar days
  const firstDay = new Date(viewYear, viewMonth, 1).getDay();
  const daysInMonth = new Date(viewYear, viewMonth+1, 0).getDate();
  const daysInPrev = new Date(viewYear, viewMonth, 0).getDate();
  const cells = [];
  for (let i = firstDay - 1; i >= 0; i--) cells.push({day: daysInPrev-i, month:'prev'});
  for (let i = 1; i <= daysInMonth; i++) cells.push({day:i, month:'cur'});
  const remaining = 42 - cells.length;
  for (let i = 1; i <= remaining; i++) cells.push({day:i, month:'next'});

  // Filtered events, indexed by date string
  const filtered = filter === 'all' ? TRAINING_EVENTS : TRAINING_EVENTS.filter(e => e.type === filter);
  const eventsByDate = {};
  filtered.forEach(e => {
    if (!eventsByDate[e.date]) eventsByDate[e.date] = [];
    eventsByDate[e.date].push(e);
  });

  const getDateStr = (day, mo) => {
    const m = mo === 'cur' ? viewMonth : mo === 'prev' ? (viewMonth-1+12)%12 : (viewMonth+1)%12;
    const y = mo === 'cur' ? viewYear : mo === 'prev' ? (viewMonth === 0 ? viewYear-1 : viewYear) : (viewMonth === 11 ? viewYear+1 : viewYear);
    return `${y}-${String(m+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
  };

  const isToday = (day, mo) => {
    if (mo !== 'cur') return false;
    return day === today.getDate() && viewMonth === today.getMonth() && viewYear === today.getFullYear();
  };

  // This month's events (filtered + unfiltered for the chip counts)
  const monthStr = `${viewYear}-${String(viewMonth+1).padStart(2,'0')}`;
  const monthAll = TRAINING_EVENTS.filter(e => e.date.startsWith(monthStr));
  const monthEvents = filtered.filter(e => e.date.startsWith(monthStr)).sort((a,b)=>a.date.localeCompare(b.date));
  const monthSpots = monthEvents.reduce((s,e)=>s+e.spots, 0);
  const chipCount = (key) => key==='all' ? monthAll.length : monthAll.filter(e=>e.type===key).length;

  // season rail: position of the viewed month within the months that have events
  const railPct = (() => {
    const ds = TRAINING_EVENTS.map(e=>e.date).sort();
    const lo = ds[0].slice(0,7).split('-').map(Number);
    const hi = ds[ds.length-1].slice(0,7).split('-').map(Number);
    const loI = lo[0]*12+lo[1]-1, hiI = hi[0]*12+hi[1]-1, cur = viewYear*12+viewMonth;
    if (hiI === loI) return 100;
    return Math.max(0, Math.min(100, ((cur-loI)/(hiI-loI))*100));
  })();

  // Touch swipe
  const touchStart = useRef(null);
  const handleTouchStart = e => { touchStart.current = e.touches[0].clientX; };
  const handleTouchEnd = e => {
    if (touchStart.current === null) return;
    const diff = touchStart.current - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 50) navigate(diff > 0 ? 'right' : 'left');
    touchStart.current = null;
  };

  return (
    <div className="cal-layout">
      {/* Sidebar */}
      <div className="cal-sidebar">
        <div className="cal-upcoming-title">Upcoming in {MONTHS[viewMonth]}</div>
        <div className="cal-upcoming">
          {monthEvents.length === 0 && <div style={{color:'rgba(248,250,255,0.3)',fontSize:'0.85rem',padding:'16px 0'}}>No sessions this month</div>}
          {monthEvents.map(ev => {
            const d = new Date(ev.date);
            const mon = MONTHS[d.getMonth()].slice(0,3).toUpperCase();
            return (
              <div key={ev.id} className={`cal-event-item v2 ${selectedEvent?.id===ev.id?'selected':''}`}
                   style={{'--rail': TYPE_COLORS[ev.type].dot}} onClick={()=>setSelectedEvent(ev)}>
                <div className="cal-event-date-box">
                  <span className="day">{d.getDate()}</span>
                  <span className="mon">{mon}</span>
                </div>
                <div className="cal-event-info">
                  <div className="cal-event-name">{ev.title}</div>
                  <div className="cal-event-meta">{ev.duration} · {ev.venue.split(',').pop()}</div>
                  <div className="cal-cap">
                    <div className="cal-cap-bar"><div className={`cal-cap-fill ${spotsLevel(ev.spots)}`} style={{width: Math.min(ev.spots,15)/15*100+'%'}}/></div>
                    <span className={`cal-cap-num ${spotsLevel(ev.spots)}`}>{ev.spots} left</span>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
        <div className="cal-legend" style={{marginTop:24}}>
          <div className="cal-legend-item"><div className="cal-legend-dot" style={{background:'rgba(232,137,10,0.4)'}}/> CISRS</div>
          <div className="cal-legend-item"><div className="cal-legend-dot" style={{background:'rgba(100,180,255,0.4)'}}/> Getmie Safe</div>
          <div className="cal-legend-item"><div className="cal-legend-dot" style={{background:'rgba(255,100,100,0.4)'}}/> Rescue</div>
        </div>
      </div>

      {/* Calendar grid */}
      <div>
        <div className="cal-grid-wrap v2" onTouchStart={handleTouchStart} onTouchEnd={handleTouchEnd}>
          <div className="cal-header">
            <div>
              <div className="cal-month-label" key={'l'+animKey}>{MONTHS[viewMonth]} <span className="yr">{viewYear}</span></div>
              <div className="cal-month-stats">{monthEvents.length} session{monthEvents.length!==1?'s':''} · {monthSpots} place{monthSpots!==1?'s':''} open</div>
            </div>
            <div className="cal-controls">
              <button className="cal-nav-btn" aria-label="Previous month" onClick={()=>navigate('left')}>‹</button>
              <button className="cal-today-btn" onClick={goToday}>Today</button>
              <button className="cal-nav-btn" aria-label="Next month" onClick={()=>navigate('right')}>›</button>
            </div>
          </div>
          <div className="cal-rail"><div className="cal-rail-fill" style={{width: railPct+'%'}}/><div className="cal-rail-dot" style={{left: railPct+'%'}}/></div>
          <div className="cal-filters">
            {CAL_TYPES.map(t => (
              <button key={t.key} className={`cal-chip ${filter===t.key?'on':''}`} style={{'--chip': t.color}} onClick={()=>setFilter(t.key)}>
                <span className="cal-chip-dot"/>{t.label}<span className="cal-chip-n">{chipCount(t.key)}</span>
              </button>
            ))}
          </div>
          <div className="cal-dow">
            {DAYS.map(d=><div key={d} className="cal-dow-cell">{d}</div>)}
          </div>
          <div key={animKey} className={`cal-days ${slideDir==='right'?'cal-slide-enter':'cal-slide-enter-left'}`}>
            {cells.map((cell, idx) => {
              const ds = getDateStr(cell.day, cell.month);
              const evs = eventsByDate[ds] || [];
              const low = cell.month==='cur' && evs.some(e=>e.spots<=5);
              const isSel = selectedEvent && selectedEvent.date===ds && cell.month==='cur';
              return (
                <div
                  key={idx}
                  className={`cal-day ${cell.month!=='cur'?'other-month':''} ${isToday(cell.day,cell.month)?'today':''} ${evs.length>0&&cell.month==='cur'?'has-events':''} ${isSel?'cal-selected':''}`}
                  onClick={()=>{ if(evs.length>0&&cell.month==='cur') setSelectedEvent(evs[0]); }}
                >
                  <div className="cal-day-num">{cell.day}</div>
                  {low && <span className="cal-low" title="Filling fast"/>}
                  {cell.month==='cur' && evs.length>0 && (
                    <div className="cal-day-events">
                      {evs.slice(0,2).map(ev=>(
                        <div key={ev.id} className={`cal-day-pill ${TYPE_COLORS[ev.type].pill}`}
                             onClick={(e)=>{e.stopPropagation(); setSelectedEvent(ev);}}>
                          {ev.title.length>18?ev.title.slice(0,18)+'…':ev.title}
                        </div>
                      ))}
                      {evs.length>2&&<div style={{fontSize:'0.6rem',color:'rgba(248,250,255,0.3)',paddingLeft:4}}>+{evs.length-2} more</div>}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
        <div className="cal-kbd-hint">← → change month · click a session for its permit</div>
      </div>

      {/* Event popup — Permit to Train */}
      {selectedEvent && (
        <div className="event-modal-overlay" onClick={e=>{if(e.target===e.currentTarget)setSelectedEvent(null);}}>
          <div className="event-modal permit">
            <div className="em-tape"/>
            <div className="event-modal-top">
              <button className="em-close" onClick={()=>setSelectedEvent(null)}>✕</button>
              <div className="em-permit-no">Permit № RHA-TRN-{String(selectedEvent.id).padStart(4,'0')}</div>
              <div className="em-tag">{selectedEvent.type==='cisrs'?'CISRS OTS Training':selectedEvent.type==='getmie'?'Getmie Safe':'Rescue Training'}</div>
              <h3>{selectedEvent.title}</h3>
            </div>
            <div className="event-modal-body">
              {[
                {label:'Date', value: new Date(selectedEvent.date).toLocaleDateString('en-GB',{weekday:'long',year:'numeric',month:'long',day:'numeric'})},
                {label:'Time', value: selectedEvent.time},
                {label:'Duration', value: selectedEvent.duration},
                {label:'Venue', value: selectedEvent.venue},
              ].map(r=>(
                <div className="em-row" key={r.label}>
                  <span className="em-label">{r.label}</span>
                  <span className="em-value">{r.value}</span>
                </div>
              ))}
              <div className="em-row">
                <span className="em-label">Capacity</span>
                <span className="em-value" style={{flex:1}}>
                  <span className="em-meter"><span className={`em-meter-fill ${spotsLevel(selectedEvent.spots)}`} style={{width: Math.min(selectedEvent.spots,15)/15*100+'%'}}/></span>
                  <span className={`em-meter-label ${spotsLevel(selectedEvent.spots)}`}>{selectedEvent.spots} spots left{selectedEvent.spots<=5?' — filling fast':''}</span>
                </span>
              </div>
              <div className="em-actions">
                <button className="em-act primary" onClick={()=>{
                  const ev = selectedEvent;
                  window.location.href = ev.courseId
                    ? U.enrol + '?id=' + ev.courseId + '&date=' + ev.date
                    : U.enrol;
                }}>
                  Enroll on This Course →
                </button>
                <button className="em-act ghost" onClick={()=>downloadICS(selectedEvent)}>
                  ⬇ Add to Calendar
                </button>
              </div>
            </div>
            <div className="em-stamp">Approved</div>
          </div>
        </div>
      )}
    </div>
  );
}
/* ── Video Modal ── */
function VideoModal({video,onClose}) {
  useEffect(()=>{
    const h=e=>{if(e.key==='Escape')onClose();};
    window.addEventListener('keydown',h);
    return ()=>window.removeEventListener('keydown',h);
  },[onClose]);
  return (
    <div className="video-modal-overlay" onClick={e=>{if(e.target===e.currentTarget)onClose();}}>
      <div className="video-modal">
        <button className="video-modal-close" onClick={onClose}>✕</button>
        <div className="video-modal-inner">
          <div style={{fontSize:'3rem',opacity:0.3}}>▶</div>
          <div style={{textAlign:'center',padding:'0 40px'}}>
            <div style={{color:'rgba(255,255,255,0.4)',fontSize:'0.8rem',marginBottom:8}}>VIDEO PLACEHOLDER</div>
            <div style={{color:'rgba(255,255,255,0.2)',fontSize:'0.72rem'}}>Drop your video file here</div>
          </div>
        </div>
        <div className="video-modal-title">{video.title}</div>
      </div>
    </div>
  );
}

function VideoCard({tag,title,onClick}) {
  return (
    <div className="video-placeholder" onClick={onClick}>
      <div className="vp-bg"><div className="vp-stripe"/></div>
      <div className="vp-play">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="white"><polygon points="5,3 19,12 5,21"/></svg>
      </div>
      <div className="vp-label">
        <div className="vp-tag">{tag}</div>
        <div>{title}</div>
      </div>
    </div>
  );
}

/* ── Gallery + lightbox ── */
const GALLERY = [
  {src:'height%20training%20(1).jpg', cls:'g-tall', alt:'Height training',          cap:'Harness work on the training tower'},
  {src:'cisrs%20training.jpg',        cls:'g-wide', alt:'CISRS training',           cap:'CISRS practical — indoor training yard'},
  {src:'training.jpg',                cls:'',       alt:'Outdoor scaffold training', cap:'The outdoor scaffold & rescue tower'},
  {src:'height%20training%20(2).jpg', cls:'',       alt:'Working at height',        cap:'Two-rope work at height'},
  {src:'height%20training%20(3).jpg', cls:'g-wide', alt:'Rescue exercise',          cap:'Rescue exercise — controlled casualty lowering'},
  {src:'height%20training%20(1).jpg', cls:'',       alt:'Height training',          cap:'Climbing drills on the training tower'},
];

function GallerySection() {
  const [idx, setIdx] = useState(null);
  const open  = (i) => setIdx(i);
  const close = () => setIdx(null);
  const step  = (d) => setIdx(i => (i + d + GALLERY.length) % GALLERY.length);

  useEffect(() => {
    if (idx === null) { document.documentElement.classList.remove('glb-lock'); return; }
    document.documentElement.classList.add('glb-lock');
    const h = (e) => {
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowRight') step(1);
      if (e.key === 'ArrowLeft') step(-1);
    };
    window.addEventListener('keydown', h);
    return () => { window.removeEventListener('keydown', h); document.documentElement.classList.remove('glb-lock'); };
  }, [idx]);

  return (
    <section className="gallery-section" id="gallery">
      <div className="gallery-inner">
        <div className="section-label reveal">Inside Radian</div>
        <h2 className="section-title reveal">TRAINING<br/><span className="dim">GALLERY</span></h2>
        <p className="reveal" style={{marginTop:20, fontSize:'1rem', color:'rgba(248,250,255,0.5)', maxWidth:560}}>
          A look at our facilities, instructors, and delegates in action.
        </p>
        <div className="gallery-grid reveal">
          {GALLERY.map((g,i)=>(
            <div key={i} className={`img-frame glb-thumb ${g.cls}`} onClick={()=>open(i)} role="button" tabIndex="0"
                 onKeyDown={e=>{if(e.key==='Enter')open(i);}} aria-label={`View photo: ${g.cap}`}>
              <img src={U.theme+'/assets/media/'+g.src} alt={g.alt} loading="lazy" decoding="async"
                   style={{width:'100%',height:'100%',objectFit:'cover',display:'block'}}/>
              <span className="glb-zoom">⊕</span>
            </div>
          ))}
        </div>
      </div>

      {idx !== null && (
        <div className="glb-overlay" onClick={e=>{if(e.target===e.currentTarget)close();}}>
          <button className="glb-x" onClick={close} aria-label="Close">✕</button>
          <button className="glb-arrow l" onClick={()=>step(-1)} aria-label="Previous">‹</button>
          <figure className="glb-stage" key={idx}>
            <img src={U.theme+'/assets/media/'+GALLERY[idx].src} alt={GALLERY[idx].alt}/>
            <figcaption>
              <span className="glb-cap">{GALLERY[idx].cap}</span>
              <span className="glb-count">{String(idx+1).padStart(2,'0')} / {String(GALLERY.length).padStart(2,'0')}</span>
            </figcaption>
          </figure>
          <button className="glb-arrow r" onClick={()=>step(1)} aria-label="Next">›</button>
        </div>
      )}
    </section>
  );
}

/* ── Contact ── */
function ContactSection() {
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

  const INFO = [
    {icon:'📞', label:'Call us',     value:'+1 (868) 280-4598', href:'tel:+18682804598'},
    {icon:'💬', label:'WhatsApp',    value:'Chat with the team', href:'https://wa.me/18682804598?text='+encodeURIComponent('Hi Radian, I would like information about your training courses.')},
    {icon:'✉️', label:'Email',       value:'training@rhatt.com', href:'mailto:training@rhatt.com'},
    {icon:'🕗', label:'Site hours',  value:'Mon – Fri · 07:00 – 16:00', href:null},
  ];

  return (
    <section className="contact-section" id="contact">
      <div className="contact-inner">
        <div className="section-label reveal">Get In Touch</div>
        <h2 className="section-title reveal" style={{marginBottom:54}}>TALK TO<br/><span className="dim">THE SITE OFFICE</span></h2>
        <div className="contact-grid">
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
                src="https://www.openstreetmap.org/export/embed.html?bbox=-61.478%2C10.374%2C-61.446%2C10.398&layer=mapnik&marker=10.384%2C-61.460"
                loading="lazy" referrerPolicy="no-referrer-when-downgrade"></iframe>
              <div className="ct-map-tag">Building 2, Phoenix Park Industrial Estate, Claxton Bay</div>
            </div>
            <aside className="ct-sticky reveal" aria-hidden="true">
              WhatsApp is the fastest way to reach the yard — we answer between toolbox talks. ☕
              <small>— the Site Office</small>
            </aside>
          </div>
          <div className="ct-form reveal-right">
            <div className="ct-form-head">Send a message</div>
            {state === 'sent' ? (
              <div className="ct-sent">
                <div className="ct-sent-ico">✓</div>
                <div className="ct-sent-title">Message received</div>
                <p>Thanks — the site office will get back to you within one working day.</p>
              </div>
            ) : (
              <>
                <div className="ct-row">
                  <div className="ct-field">
                    <label>Name</label>
                    <input type="text" placeholder="Your name" value={form.name} onChange={e=>update('name',e.target.value)}/>
                  </div>
                  <div className="ct-field">
                    <label>Phone (optional)</label>
                    <input type="tel" placeholder="+1 (868) …" value={form.phone} onChange={e=>update('phone',e.target.value)}/>
                  </div>
                </div>
                <div className="ct-field">
                  <label>Email</label>
                  <input type="email" placeholder="you@company.com" value={form.email} onChange={e=>update('email',e.target.value)}/>
                </div>
                <div className="ct-field">
                  <label>Message</label>
                  <textarea rows="5" placeholder="Tell us which course you're interested in, group size, preferred dates…" value={form.message} onChange={e=>update('message',e.target.value)}/>
                </div>
                <input type="text" className="ct-hp" tabIndex="-1" autoComplete="off" value={form.company} onChange={e=>update('company',e.target.value)} aria-hidden="true"/>
                {state==='error' && <div className="ct-err">Please fill in your name, a valid email, and a message.</div>}
                <button className="btn-primary ct-submit" onClick={submit} disabled={state==='sending'}>
                  {state==='sending' ? 'Sending…' : 'Send Message →'}
                </button>
              </>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}

/* ── App ── */
function App() {
  const [activeVideoTab,setActiveVideoTab]=useState('cisrs');
  const [modalVideo,setModalVideo]=useState(null);
  const [activeCourse,setActiveCourse]=useState(null);

  useEffect(()=>{
    const nav=document.getElementById('navbar');
    const h=()=>nav.classList.toggle('scrolled',window.scrollY>60);
    window.addEventListener('scroll',h);
    return ()=>window.removeEventListener('scroll',h);
  },[]);

  useEffect(()=>{
    // GSAP owns reveals/parallax/counters when available; else fall back to IntersectionObserver
    if (window.RadianMotion && window.RadianMotion.init()) return;
    const obs=new IntersectionObserver(entries=>{
      entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');obs.unobserve(e.target);}});
    },{threshold:0.1});
    document.querySelectorAll('.reveal,.reveal-left,.reveal-right').forEach(el=>obs.observe(el));
    return ()=>obs.disconnect();
  },[]);

  // Mount the interactive 3D scaffold centrepiece in the hero
  useEffect(()=>{
    const mount=document.getElementById('hero3d-mount');
    let rig=null;
    if(mount && window.createScaffold3D){
      rig=window.createScaffold3D({container:mount, variant:'structure', bays:2, lifts:3});
    }
    return ()=>{ if(rig&&rig.destroy) rig.destroy(); };
  },[]);

  // Hero scrub: content drifts + fades, 3D parallaxes as you scroll away
  useEffect(()=>{
    if(!(window.gsap && window.ScrollTrigger)) return;
    if(window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const g=window.gsap; const made=[];
    const t1=g.to('.hero-content',{yPercent:-16,opacity:0.05,ease:'none',
      scrollTrigger:{trigger:'.hero',start:'top top',end:'bottom top',scrub:true}});
    const t2=g.to('.hero-3d-wrap',{yPercent:14,ease:'none',
      scrollTrigger:{trigger:'.hero',start:'top top',end:'bottom top',scrub:true}});
    const t3=g.to('.hero-stats-strip',{yPercent:30,opacity:0.2,ease:'none',
      scrollTrigger:{trigger:'.hero',start:'center top',end:'bottom top',scrub:true}});
    [t1,t2,t3].forEach(t=>t&&t.scrollTrigger&&made.push(t.scrollTrigger));
    return ()=>made.forEach(st=>st.kill());
  },[]);

  const [count25,ref25]=useCounter(25);
  const [count1500,ref1500]=useCounter(1500);

  const courses=[
    {icon:'🏗️',title:'Scaffolding Operative Training',desc:'Covers different skill levels from beginner to advanced scaffolder.',num:'01'},
    {icon:'🔍',title:'Scaffold Inspection Training',desc:'Enables individuals to conduct safe and compliant scaffold inspections.',num:'02'},
    {icon:'📋',title:'Management & Supervision',desc:'Designed for supervisors, managers, and site leads.',num:'03'},
    {icon:'👷',title:'Scaffolding Awareness',desc:'Provides a general understanding for those overseeing scaffolding operations.',num:'04'},
  ];

  const videoData={
    cisrs:[
      {tag:'CISRS OTS',title:'CISRS Scaffolding Operative Training Overview'},
      {tag:'CISRS OTS',title:'CISRS Scaffold Inspection & Safety Procedures'},
    ],
    getmie:[
      {tag:'Getmie Safe',title:'Working at Height — Risk & PPE Guide'},
      {tag:'Getmie Safe',title:'Getmie Safe Rescue System Training'},
    ],
    general:[
      {tag:'General',title:'Radian H.A. Limited — Training Programme Overview'},
    ],
  };
  const activeVideos=videoData[activeVideoTab];

  return (
    <>
      <ScrollProgress/>
      {/* HERO */}
      <section className="hero" id="home" onMouseMove={(e) => {
        const rect = e.currentTarget.getBoundingClientRect();
        const glow = document.getElementById('hero-glow');
        if (glow) {
          glow.style.left = (e.clientX - rect.left) + 'px';
          glow.style.top = (e.clientY - rect.top) + 'px';
          glow.style.opacity = '1';
        }
      }} onMouseLeave={() => { const g = document.getElementById('hero-glow'); if (g) g.style.opacity = '0'; }}>
        <div className="scaffold-bg"/>
        <FloatingPoles/>
        <div className="hero-glow" id="hero-glow" style={{opacity:0, left:'50%', top:'50%'}}/>
        <div className="hero-layout">
          <div className="hero-content">
            <div className="hero-badge">
              <span className="hero-badge-dot"/>
              CISRS Accredited Scaffold Training Provider
            </div>
            <h1 className="hero-title">
              <span style={{color:'rgba(248,250,255,0.35)'}}>RADIAN</span><br/>
              <span className="accent">H.A.</span> <span className="outline">LIMITED</span><br/>
              TRAINING
            </h1>
            <p className="hero-sub">
              25 years of industry-leading scaffolding training — building competence,
              safety, and careers across the scaffolding workforce.
            </p>
            <div className="hero-actions">
              <button className="btn-primary" onClick={()=>document.getElementById('cisrs').scrollIntoView({behavior:'smooth'})}>
                Explore Courses
              </button>
              <button className="btn-outline" onClick={()=>document.getElementById('about').scrollIntoView({behavior:'smooth'})}>
                Learn More
              </button>
            </div>
            <div className="scroll-hint">
              <span className="scroll-hint-text">Scroll</span>
              <div className="scroll-mouse"><div className="scroll-wheel"/></div>
            </div>
          </div>
          <div className="hero-3d-wrap">
            <div className="hero-3d" id="hero3d-mount"></div>
            <div className="hero-3d-hint">Drag to rotate · Tap a part to inspect</div>
          </div>
        </div>
        <div className="hero-stats-strip">
          <div className="hero-stat">
            <div className="hero-stat-num" ref={ref25}>{count25}</div>
            <div className="hero-stat-label">Years of Training<br/>Experience</div>
          </div>
          <div className="hero-stat">
            <div className="hero-stat-num" ref={ref1500}>{count1500}+</div>
            <div className="hero-stat-label">Participants<br/>Trained</div>
          </div>
          <div className="hero-stat">
            <div className="hero-stat-num">CISRS</div>
            <div className="hero-stat-label">British Standard<br/>Accredited</div>
          </div>
          <div className="hero-stat">
            <div className="hero-stat-num">4</div>
            <div className="hero-stat-label">Training<br/>Programmes</div>
          </div>
        </div>
      </section>

      {/* TRUST BAND — now a continuous marquee */}
      <div className="marquee-band">
        <div className="marquee-track">
          {[
            '🏆 CISRS OTS ACCREDITED',
            '25 YEARS EXPERIENCE',
            '🛡️ BRITISH STANDARD COMPLIANT',
            '1,500+ PARTICIPANTS TRAINED',
            '🎓 INDUSTRY RECOGNISED',
            '⚙️ PRACTICAL + THEORY',
            '📍 4 CORE PROGRAMMES',
            '⚠️ SAFETY FIRST',
            // duplicate for seamless loop
            '🏆 CISRS OTS ACCREDITED',
            '25 YEARS EXPERIENCE',
            '🛡️ BRITISH STANDARD COMPLIANT',
            '1,500+ PARTICIPANTS TRAINED',
            '🎓 INDUSTRY RECOGNISED',
            '⚙️ PRACTICAL + THEORY',
            '📍 4 CORE PROGRAMMES',
            '⚠️ SAFETY FIRST',
          ].map((t, i) => (
            <span className="marquee-item" key={i}>
              <span className="marquee-dot"/>
              {t}
            </span>
          ))}
        </div>
      </div>

      {/* ABOUT */}
      

      <SparkDivider/>

      {/* CISRS */}
      <section className="training-section" id="cisrs">
        <div className="training-inner">
          <div className="cisrs-header">
            <div>
              <div className="cisrs-badge reveal">CISRS OTS</div>
              <h2 className="section-title reveal" data-crane="1">OVERSEAS TRAINING<br/><span className="dim">SCHEME</span></h2>
            </div>
            <div className="cisrs-desc reveal-right">
              <p style={{marginBottom:16}}>The CISRS Overseas Scheme provides a comprehensive range of courses for scaffolding operatives, supervisors, managers, and scaffold inspectors.</p>
              <p>These courses incorporate both practical and theoretical elements and full assessment, ensuring competence at every level.</p>
            </div>
          </div>
          <div className="courses-grid">
            {courses.map((c,i)=>(
              <div className={`course-card reveal ${activeCourse===i?'active-course':''}`} key={i} style={{transitionDelay:`${i*0.1}s`}} onClick={()=>setActiveCourse(activeCourse===i?null:i)}>
                <div className="course-num">{c.num}</div>
                <div className="course-icon">{c.icon}</div>
                <div className="course-title">{c.title}</div>
                <div className="course-desc">{c.desc}</div>
                <div className="course-arrow">→</div>
              </div>
            ))}
          </div>
          <div className="img-frame reveal" style={{marginTop:48, aspectRatio:'21/7'}}>
            <img src={U.theme+'/assets/media/training.jpg'} alt="Outdoor scaffolding training" loading="lazy" decoding="async" style={{width:'100%',height:'100%',objectFit:'cover',display:'block'}}/>
            <div className="img-caption">
              <div className="img-caption-tag">CISRS OTS</div>
              <div className="img-caption-text">Hands-On Scaffolding Training</div>
            </div>
          </div>
        </div>
      </section>

      <SparkDivider/>

      {/* GETMIE */}
      <section className="training-section alt" id="getmie">
        <div className="training-inner">
          <div className="section-label reveal">Getmie Safe Programme</div>
          <h2 className="section-title reveal" style={{marginBottom:60}}>WORKING AT HEIGHT<br/><span className="dim">TRAINING & RESCUE</span></h2>
          <div className="getmie-grid">
            <div className="getmie-card reveal-left">
              <div className="getmie-card-accent"/>
              <div className="img-frame" style={{marginBottom:24, aspectRatio:'16/9'}}>
                <img src={U.theme+'/assets/media/height%20training%20(2).jpg'} alt="Working at height training" loading="lazy" decoding="async" style={{width:'100%',height:'100%',objectFit:'cover',display:'block'}}/>
              </div>
              <div className="getmie-label">Working at Height</div>
              <h3 className="getmie-title">Getmie Safe Working<br/>at Height</h3>
              <p className="getmie-desc">Our Getmie Safe Working at Height course covers risk assessment, equipment selection, PPE use, and safe work practices at height. It's not only designed for scaffolders, but all personnel exposed to fall risks.</p>
              <div style={{marginTop:32}}><button className="btn-primary" style={{fontSize:'0.82rem',padding:'12px 28px'}} onClick={()=>window.location.href=U.getmie}>Learn More →</button></div>
            </div>
            <div className="getmie-card reveal-right">
              <div className="getmie-card-accent"/>
              <div className="img-frame" style={{marginBottom:24, aspectRatio:'16/9'}}>
                <img src={U.theme+'/assets/media/height%20training%20(3).jpg'} alt="Rescue training exercise" loading="lazy" decoding="async" style={{width:'100%',height:'100%',objectFit:'cover',display:'block'}}/>
              </div>
              <div className="getmie-label">Emergency Rescue</div>
              <h3 className="getmie-title">Getmie Safe<br/>Rescue Training</h3>
              <p className="getmie-desc">For persons working at height, rescue is critical and can be life-saving. A successful rescue in minimum time is imperative. Our training focuses on safe rescue techniques, emergency equipment use, and team-based response using the Getmie Safe Rescue System.</p>
              <div style={{marginTop:32}}><button className="btn-primary" style={{fontSize:'0.82rem',padding:'12px 28px'}} onClick={()=>window.location.href=U.getmie}>Learn More →</button></div>
            </div>
          </div>
          <div className="rad-notice reveal">
            <div className="rad-notice-stamp">SAFETY<br/>NOTICE</div>
            <div>
              <div className="rad-notice-title">RESCUE IS A LIFE-SAVING SKILL</div>
              <div className="rad-notice-text">Our training prepares teams for real emergency scenarios — using the Getmie Safe Rescue System to deliver the fastest, safest possible response when it matters most.</div>
              <div className="rad-notice-sign">— pinned by the site office. Read before you climb.</div>
            </div>
          </div>
        </div>
      </section>

      {/* MARQUEE BAND — Dark stats variant */}
      <div className="marquee-band dark">
        <div className="marquee-track">
          {[
            {n:'25',l:'YEARS EXPERIENCE'},
            {n:'1,500+',l:'DELEGATES TRAINED'},
            {n:'6',l:'CISRS COURSES'},
            {n:'4',l:'GETMIE PROGRAMMES'},
            {n:'100%',l:'SAFETY FOCUSED'},
            {n:'5YR',l:'CERTIFICATE VALIDITY'},
            {n:'25',l:'YEARS EXPERIENCE'},
            {n:'1,500+',l:'DELEGATES TRAINED'},
            {n:'6',l:'CISRS COURSES'},
            {n:'4',l:'GETMIE PROGRAMMES'},
            {n:'100%',l:'SAFETY FOCUSED'},
            {n:'5YR',l:'CERTIFICATE VALIDITY'},
          ].map((s,i)=>(
            <span className="marquee-item" key={i}>
              <span className="accent">{s.n}</span> {s.l}
              <span className="marquee-dot"/>
            </span>
          ))}
        </div>
      </div>

      {/* CALENDAR */}
      <section className="calendar-section" id="calendar">
        <div className="calendar-inner">
          <div className="section-label reveal">Training Schedule</div>
          <h2 className="section-title reveal" style={{marginBottom:8}}>UPCOMING<br/><span className="dim">TRAINING DATES</span></h2>
          <p className="reveal" style={{marginTop:20,fontSize:'1rem',color:'rgba(248,250,255,0.5)',maxWidth:560}}>
            Click any highlighted date or event to view full details and Enroll. Swipe left or right to navigate months.
          </p>
          <Calendar/>
        </div>
      </section>

      <SparkDivider/>

      {/* GALLERY */}
      <GallerySection/>

      <SparkDivider/>

      {/* VIDEOS */}
      <section className="videos-section" id="videos">
        <div className="videos-inner">
          <div className="section-label reveal">See It In Action</div>
          <h2 className="section-title reveal" style={{marginBottom:48}}>TRAINING<br/><span className="dim">VIDEOS</span></h2>
          <div className="videos-tabs reveal">
            {[{key:'cisrs',label:'CISRS OTS Training'},{key:'getmie',label:'Getmie Safe Training'},{key:'general',label:'General Overview'}].map(t=>(
              <button key={t.key} className={`vtab ${activeVideoTab===t.key?'active':''}`} onClick={()=>setActiveVideoTab(t.key)}>{t.label}</button>
            ))}
          </div>
          <div className="videos-grid">
            {activeVideos.map((v,i)=>(
              <VideoCard key={`${activeVideoTab}-${i}`} tag={v.tag} title={v.title} onClick={()=>setModalVideo(v)}/>
            ))}
            {activeVideos.length < 3 && (
              <div style={{aspectRatio:'16/9',background:'rgba(232,137,10,0.04)',border:'1px dashed rgba(232,137,10,0.2)',display:'flex',alignItems:'center',justifyContent:'center',flexDirection:'column',gap:16,cursor:'pointer',transition:'all 0.3s'}}
                onMouseEnter={e=>{e.currentTarget.style.background='rgba(232,137,10,0.08)';}}
                onMouseLeave={e=>{e.currentTarget.style.background='rgba(232,137,10,0.04)';}}
                onClick={()=>document.getElementById('cta').scrollIntoView({behavior:'smooth'})}>
                <div style={{color:'var(--orange)',fontSize:'2rem'}}>+</div>
                <div style={{color:'rgba(248,250,255,0.4)',fontSize:'0.82rem',textAlign:'center',letterSpacing:'0.08em',textTransform:'uppercase'}}>Ready to Train?<br/><span style={{color:'var(--orange)'}}>Enroll Now</span></div>
              </div>
            )}
          </div>
        </div>
      </section>

      <ContactSection/>

      <SparkDivider/>

      {/* CTA */}
      <section className="cta-section" id="cta">
        <div className="section-label reveal" style={{justifyContent:'center'}}>Start Your Journey</div>
        <h2 className="cta-title reveal" data-crane="1">READY TO<br/><span style={{color:'var(--orange)'}}>BUILD</span> YOUR<br/>CAREER?</h2>
        <p className="cta-sub reveal">Enroll in our scaffolding training today to enhance your qualifications and advance your career opportunities with an industry-recognised qualification.</p>
        <div className="cta-actions reveal">
          <button className="btn-primary" style={{padding:'18px 52px',fontSize:'1rem'}} onClick={()=>window.location.href=U.cisrs}>Enroll Now</button>
          <button className="btn-outline" style={{padding:'16px 52px',fontSize:'1rem'}} onClick={()=>window.location.href=U.cert}>Contact Us</button>
        </div>
      </section>

      {/* FOOTER */}
      <div className="footer-bottom">
        <p>© 2026 Radian H.A. Limited Training. All rights reserved.</p>
        <p style={{color:'rgba(248,250,255,0.2)',fontSize:'0.72rem'}}>CISRS Accredited Scaffold Training Provider</p>
      </div>

      {modalVideo && <VideoModal video={modalVideo} onClose={()=>setModalVideo(null)}/>}
    </>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App/>);
</script>

<?php get_footer(); ?>

