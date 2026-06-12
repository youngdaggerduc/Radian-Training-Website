/* ============================================================================
   assistant.js — "Ask the Site Office" floating AI chat (vanilla, polish.js
   idiom). Only loaded when the assistant is enabled + keyed (functions.php
   prints window.RADIAN_AI = {ajaxUrl, nonce} and enqueues this file).
   Conversation persists across pages via sessionStorage. All rendering is
   escaped; URLs in office replies become links.
============================================================================ */
(function () {
  'use strict';
  var CFG = window.RADIAN_AI;
  if (!CFG || !CFG.ajaxUrl) return;

  var STORE = 'radianAiChat';
  var GREETING = "Site office here 👷 — ask me about courses, prices, the route from Level 1 up, or how to enrol.";
  var CHIPS = [
    'Which course do I need?',
    'Course prices',
    'How do I enrol?',
    'Verify a certificate',
  ];

  var history = [];                       // [{role, content}]
  try { history = JSON.parse(sessionStorage.getItem(STORE) || '[]') || []; } catch (e) { history = []; }

  var open = false, busy = false;
  var panel, log, input, sendBtn, launcher;

  function save() {
    try { sessionStorage.setItem(STORE, JSON.stringify(history.slice(-20))); } catch (e) {}
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }
  function render(s) {
    return esc(s)
      .replace(/(https?:\/\/[^\s<)]+)/g, '<a href="$1" rel="noopener">$1</a>')
      .replace(/\n/g, '<br/>');
  }

  function bubble(role, text) {
    var b = document.createElement('div');
    b.className = 'rai-msg ' + (role === 'user' ? 'rai-you' : 'rai-office');
    b.innerHTML = render(text);
    log.appendChild(b);
    log.scrollTop = log.scrollHeight;
    return b;
  }

  function typing(on) {
    var t = log.querySelector('.rai-typing');
    if (on && !t) {
      t = document.createElement('div');
      t.className = 'rai-msg rai-office rai-typing';
      t.innerHTML = '<span></span><span></span><span></span>';
      log.appendChild(t);
      log.scrollTop = log.scrollHeight;
    } else if (!on && t) {
      t.remove();
    }
  }

  function send(text) {
    text = (text || '').trim();
    if (!text || busy) return;
    busy = true;
    sendBtn.disabled = true;
    input.value = '';
    var chips = panel.querySelector('.rai-chips');
    if (chips) chips.remove();

    bubble('user', text);
    var payloadHistory = history.slice(-8);
    history.push({ role: 'user', content: text });
    save();
    typing(true);

    var body = new URLSearchParams({
      action: 'radian_ai_chat',
      nonce: CFG.nonce || '',
      message: text,
      history: JSON.stringify(payloadHistory),
    });
    fetch(CFG.ajaxUrl, { method: 'POST', body: body })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        typing(false);
        var reply = j && j.success && j.data && j.data.reply
          ? j.data.reply
          : (j && j.data && j.data.message) || 'Radio static — try that again in a moment.';
        bubble('assistant', reply);
        if (j && j.success) { history.push({ role: 'assistant', content: reply }); save(); }
      })
      .catch(function () {
        typing(false);
        bubble('assistant', "Radio static on the line — try again in a moment, or WhatsApp the office on +1 (868) 280-4598.");
      })
      .finally(function () {
        busy = false;
        sendBtn.disabled = false;
        input.focus();
      });
  }

  function build() {
    // launcher
    launcher = document.createElement('button');
    launcher.id = 'rad-ai';
    launcher.setAttribute('aria-label', 'Ask the site office — AI assistant');
    launcher.innerHTML =
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
      + '<path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.9 8.9 0 0 1-3.2-.6L3 21l1.7-4.1a8.4 8.4 0 1 1 16.3-5.4Z"/>'
      + '<path d="M8.5 10.5h.01M12 10.5h.01M15.5 10.5h.01" stroke-width="2.6"/></svg>'
      + '<span class="rad-ai-tag">Ask the office</span>';
    document.body.appendChild(launcher);

    // panel
    panel = document.createElement('div');
    panel.id = 'rad-ai-panel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Site office assistant chat');
    panel.innerHTML =
      '<div class="rai-head">'
      + '<span class="rai-dot" aria-hidden="true"></span>'
      + '<span class="rai-title">SITE OFFICE — ASK RADIAN</span>'
      + '<button class="rai-close" aria-label="Close chat">✕</button>'
      + '</div>'
      + '<div class="rai-log"></div>'
      + '<div class="rai-foot">'
      + '<input class="rai-input" type="text" maxlength="600" placeholder="Ask about courses, prices, enrolment…" aria-label="Your message"/>'
      + '<button class="rai-send" aria-label="Send">→</button>'
      + '</div>'
      + '<div class="rai-note">AI assistant — answers come from our site notes; the office confirms anything important.</div>';
    document.body.appendChild(panel);

    log = panel.querySelector('.rai-log');
    input = panel.querySelector('.rai-input');
    sendBtn = panel.querySelector('.rai-send');

    // replay stored conversation (or greet)
    bubble('assistant', GREETING);
    history.forEach(function (m) { bubble(m.role === 'assistant' ? 'assistant' : 'user', m.content); });
    if (!history.length) {
      var chips = document.createElement('div');
      chips.className = 'rai-chips';
      CHIPS.forEach(function (c) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = c;
        b.addEventListener('click', function () { send(c); });
        chips.appendChild(b);
      });
      log.appendChild(chips);
    }

    launcher.addEventListener('click', function () { setOpen(!open); });
    panel.querySelector('.rai-close').addEventListener('click', function () { setOpen(false); });
    sendBtn.addEventListener('click', function () { send(input.value); });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(input.value); }
    });
    window.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && open) setOpen(false);
    });
  }

  function setOpen(o) {
    open = o;
    panel.classList.toggle('open', o);
    launcher.classList.toggle('open', o);
    launcher.setAttribute('aria-expanded', o ? 'true' : 'false');
    if (o) {
      log.scrollTop = log.scrollHeight;
      input.focus();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', build);
  } else {
    build();
  }
})();
