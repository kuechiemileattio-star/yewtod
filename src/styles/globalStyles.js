import { FONTS, T } from "../theme.js";

export const GLOBAL_STYLES = `${FONTS}
  * { box-sizing: border-box; }
  html, body, #root { margin: 0; padding: 0; min-width: 320px; }
  body { overflow-x: hidden; background: ${T.paper}; }
  html { scroll-behavior: smooth; }
  body, button, input, select, textarea { font-family: 'Plus Jakarta Sans', sans-serif; }
  h1, h2, h3, h4, h5, h6 { font-family: 'Fraunces', serif !important; letter-spacing: -.02em; }
  code, pre, [style*="IBM Plex Mono"], [style*="Space Mono"] { font-family: 'JetBrains Mono', monospace !important; }
  button, input, select, textarea { font: inherit; }
  input, select, textarea, button { transition: border-color .2s ease, box-shadow .2s ease, background .2s ease, color .2s ease, transform .2s ease; }
  input:focus, select:focus, textarea:focus { border-color: ${T.green} !important; box-shadow: 0 0 0 3px ${T.green}24; outline: none; }

  @keyframes ytdFadeUp { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes ytdPulse { 0%, 100% { opacity: 1; } 50% { opacity: .35; } }
  @keyframes ytdDash { to { stroke-dashoffset: 0; } }
  @keyframes ytdAmbient { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
  @keyframes ytdFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
  @keyframes ytdImageReveal { from { opacity: 0; transform: scale(1.08); } to { opacity: .86; transform: scale(1); } }
  @keyframes ytdShimmer { from { transform: translateX(-110%); } to { transform: translateX(110%); } }
  @keyframes ytdMenuIn { from { opacity: 0; transform: translateY(-16px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes ytdBarPulse { 0%, 100% { box-shadow: 0 0 0 ${T.green}00; } 50% { box-shadow: 0 0 12px ${T.green}AA; } }
  @keyframes ytdLightSweep { 0% { transform: translateX(-28%) rotate(-12deg); opacity: 0; } 20%, 70% { opacity: .32; } 100% { transform: translateX(88%) rotate(-12deg); opacity: 0; } }
  @keyframes ytdAdminIn { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes ytdAdminSlide { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }
  @keyframes ytdButtonShine { from { transform: translateX(-140%) skewX(-18deg); } to { transform: translateX(220%) skewX(-18deg); } }
  @keyframes ytdLabelIn { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }
  @keyframes ytdBorderDraw { from { transform: scaleX(0); transform-origin: left; } to { transform: scaleX(1); transform-origin: left; } }
  @keyframes ytdNumberIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes ytdDashboardRise { from { opacity: 0; transform: translateY(22px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes ytdMarquee { from { transform: translateX(0); } to { transform: translateX(calc(-100% - 40px)); } }
  @keyframes ytdMeetMark { from { opacity: 0; transform: rotate(-8deg) scale(.86); } to { opacity: 1; transform: rotate(4deg) scale(1); } }
  @keyframes ytdCollabSuccess { from { opacity: 0; transform: scale(.94) translateY(14px); } to { opacity: 1; transform: scale(1) translateY(0); } }
  @keyframes ytdOrbit { from { transform: rotate(0deg) translateX(0); } 50% { transform: rotate(180deg) translateX(8px); } to { transform: rotate(360deg) translateX(0); } }
  @keyframes ytdAccentPulse { 0%, 100% { opacity: .35; transform: scale(.96); } 50% { opacity: .7; transform: scale(1); } }

  .ytd-app-shell { position: relative; min-height: 100vh; overflow-x: clip; background-size: 180% 180% !important; animation: ytdAmbient 24s ease-in-out infinite; }
  .ytd-app-shell::before { content: ""; position: fixed; inset: 0; pointer-events: none; opacity: .26; background-image: linear-gradient(${T.line}38 1px, transparent 1px), linear-gradient(90deg, ${T.line}38 1px, transparent 1px); background-size: 44px 44px; mask-image: linear-gradient(to bottom, black, transparent 72%); }
  .ytd-app-shell::after { content: ""; position: fixed; z-index: 0; top: 0; left: -20%; width: 34%; height: 100vh; pointer-events: none; background: linear-gradient(90deg, transparent, ${T.lime}14, transparent); filter: blur(12px); animation: ytdLightSweep 14s ease-in-out 2s infinite; }
  .ytd-home-page { position: relative; z-index: 1; }
  .ytd-platform-intro { position: relative; }
  .ytd-meet-page { position: relative; z-index: 1; }
  .ytd-collab-page { position: relative; z-index: 1; }
  .ytd-collab-hero { display: grid; grid-template-columns: 1.2fr .8fr; align-items: end; gap: 60px; padding-bottom: 62px; animation: ytdFadeUp .7s cubic-bezier(.16,1,.3,1) both; }
  .ytd-collab-hero p { max-width: 620px; margin-bottom: 0 !important; font: 16px/1.7 'Plus Jakarta Sans', sans-serif !important; }
  .ytd-collab-hero-note { display: flex; align-items: center; gap: 12px; max-width: 230px; padding: 18px; color: ${T.paper}; background: ${T.red}; border-radius: 12px; font: 11px/1.5 'JetBrains Mono', monospace; transform: rotate(3deg); animation: ytdMeetMark .8s .15s cubic-bezier(.16,1,.3,1) both; }
  .ytd-collab-layout { display: grid; grid-template-columns: .72fr 1.28fr; gap: 24px; align-items: start; }
  .ytd-collab-aside { padding: 26px; color: ${T.paper}; background: ${T.greenDeep}; border-radius: 14px; animation: ytdDashboardRise .65s .12s cubic-bezier(.16,1,.3,1) both; }
  .ytd-collab-aside-label { display: block; margin-bottom: 22px; color: ${T.lime}; font: 10px 'JetBrains Mono', monospace; letter-spacing: .1em; text-transform: uppercase; }
  .ytd-collab-type { display: flex; align-items: flex-start; gap: 12px; padding: 18px 0; border-top: 1px solid ${T.paper}22; opacity: 0; animation: ytdAdminSlide .55s cubic-bezier(.16,1,.3,1) both; }
  .ytd-collab-type svg { flex: 0 0 auto; color: ${T.lime}; }
  .ytd-collab-type strong, .ytd-collab-type span { display: block; }
  .ytd-collab-type strong { margin-bottom: 5px; font: 500 20px 'Fraunces', serif; }
  .ytd-collab-type span { color: ${T.paper}AA; font: 11px/1.5 'Plus Jakarta Sans', sans-serif; }
  .ytd-collab-type:hover { color: ${T.lime}; transform: translateX(6px); transition: transform .25s ease, color .25s ease; }
  .ytd-collab-form-wrap { padding: 28px; background: ${T.paper}; border: 1px solid ${T.line}; border-radius: 14px; box-shadow: 0 18px 32px -28px ${T.greenDeep}; }
  .ytd-collab-form { opacity: 1 !important; transform: none !important; }
  .ytd-collab-form input, .ytd-collab-form select, .ytd-collab-form textarea { border-radius: 6px !important; }
  .ytd-collab-success { animation: ytdCollabSuccess .6s cubic-bezier(.16,1,.3,1) both; border-radius: 12px; }
  .ytd-collab-success svg { animation: ytdMeetMark .6s .15s both; }
  .ytd-meet-hero { display: grid; grid-template-columns: 1.25fr .75fr; align-items: center; gap: 60px; min-height: 430px; padding-bottom: 70px; }
  .ytd-meet-hero-copy { animation: ytdFadeUp .7s cubic-bezier(.16,1,.3,1) both; }
  .ytd-meet-lead { max-width: 600px; margin: 0; color: ${T.inkSoft}; font: 17px/1.7 'Plus Jakarta Sans', sans-serif; }
  .ytd-meet-hero-mark { display: flex; flex-direction: column; align-items: center; justify-content: space-between; gap: 30px; width: 230px; height: 260px; margin: auto; padding: 28px; color: ${T.paper}; background: ${T.greenDeep}; border-radius: 48% 52% 46% 54%; transform: rotate(4deg); animation: ytdMeetMark .85s .18s cubic-bezier(.16,1,.3,1) both; box-shadow: 18px 18px 0 ${T.lime}; }
  .ytd-meet-hero-mark span { text-align: center; font: 11px/1.5 'JetBrains Mono', monospace; letter-spacing: .1em; text-transform: uppercase; }
  .ytd-meet-method { padding: 0 0 86px; }
  .ytd-meet-method-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
  .ytd-meet-method-card { min-height: 220px; padding: 24px; background: ${T.paper}; border: 1px solid ${T.line}; border-radius: 12px; opacity: 0; animation: ytdDashboardRise .65s cubic-bezier(.16,1,.3,1) both; transition: transform .3s ease, background .3s ease, box-shadow .3s ease; }
  .ytd-meet-method-card:hover { transform: translateY(-8px); background: ${T.paperAlt}; box-shadow: 0 18px 30px -24px ${T.greenDeep}; }
  .ytd-meet-method-card > div { display: grid; place-items: center; width: 42px; height: 42px; color: ${T.paper}; background: ${T.red}; border-radius: 50%; transition: transform .35s ease; }
  .ytd-meet-method-card:hover > div { transform: rotate(12deg) scale(1.1); }
  .ytd-meet-method-card h2 { margin: 28px 0 8px; color: ${T.ink}; font: 500 24px 'Fraunces', serif; }
  .ytd-meet-method-card p { margin: 0; color: ${T.inkSoft}; font: 13px/1.65 'Plus Jakarta Sans', sans-serif; }
  .ytd-meet-questions { padding-top: 10px; }
  .ytd-meet-questions .ytd-qa-row { transition: transform .3s ease, padding-left .3s ease; }
  .ytd-meet-questions .ytd-qa-row:hover { transform: translateX(8px); padding-left: 12px; border-left: 3px solid ${T.green}; }
  .ytd-founder-panel { padding: 30px; background: linear-gradient(135deg, ${T.paperAlt}, ${T.paper}); border: 1px solid ${T.line}; border-radius: 16px; }
  .ytd-founder-portrait { position: relative; overflow: hidden; border-radius: 44% 56% 52% 48%; animation: ytdFloat 5s ease-in-out infinite; }
  .ytd-founder-portrait::after { content: 'Y'; color: ${T.lime}; font: 180px 'Fraunces', serif; opacity: .25; }
  .ytd-platform-intro-heading { display: grid; grid-template-columns: .7fr 1.3fr; gap: 30px; align-items: end; margin-bottom: 26px; }
  .ytd-platform-intro-heading > span { color: ${T.green}; font: 10px 'JetBrains Mono', monospace; letter-spacing: .1em; text-transform: uppercase; }
  .ytd-platform-intro-heading h2 { max-width: 700px; margin: 0; color: ${T.ink}; font: 400 clamp(28px, 4vw, 44px) 'Fraunces', serif; line-height: 1.04; letter-spacing: -.04em; }
  .ytd-platform-pillars { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
  .ytd-platform-pillar { min-height: 190px; padding: 22px; border: 1px solid ${T.line}; background: ${T.paper}; border-radius: 12px; opacity: 0; animation: ytdDashboardRise .65s cubic-bezier(.16,1,.3,1) both; transition: transform .3s ease, background .3s ease, box-shadow .3s ease; }
  .ytd-platform-pillar:hover { transform: translateY(-6px); background: ${T.paperAlt}; box-shadow: 0 16px 28px -24px ${T.greenDeep}; }
  .ytd-platform-icon { display: grid; place-items: center; width: 40px; height: 40px; color: ${T.paper}; background: ${T.greenDeep}; border-radius: 50%; transition: transform .35s ease, background .35s ease; }
  .ytd-platform-pillar:hover .ytd-platform-icon { transform: rotate(12deg) scale(1.1); background: ${T.green}; }
  .ytd-platform-pillar h3 { margin: 22px 0 7px; color: ${T.ink}; font: 500 23px 'Fraunces', serif; }
  .ytd-platform-pillar p { margin: 0; color: ${T.inkSoft}; font: 13px/1.6 'Plus Jakarta Sans', sans-serif; }
  .ytd-editorial-section { position: relative; }
  .ytd-editorial-section::before { content: ''; position: absolute; left: 24px; right: 24px; top: 0; height: 1px; background: linear-gradient(90deg, transparent, ${T.line}, transparent); opacity: .75; }
  .ytd-editorial-section-tinted { padding-top: 28px !important; }
  .ytd-editorial-section-tinted::before { display: none; }
  .ytd-editorial-section-tinted::after { content: ''; position: absolute; z-index: -1; inset: 0 0 28px; background: linear-gradient(135deg, ${T.paperAlt}88, transparent 72%); border-radius: 20px; pointer-events: none; }
  .ytd-page-anim { animation: ytdFadeUp .65s cubic-bezier(.16,1,.3,1) both; position: relative; z-index: 1; }
  .ytd-page-anim, .ytd-home-page, .ytd-collab-page, .ytd-meet-page, .ytd-book-detail-page { min-width: 0; }
  .ytd-scroll-progress { position: absolute; left: 0; top: 0; height: 3px; z-index: 2; background: ${T.lime}; animation: ytdBarPulse 2.4s ease-in-out infinite; transition: width .12s ease-out; }
  .ytd-navbar { box-shadow: 0 10px 30px -26px ${T.greenDeep}; }
  .ytd-navbar .ytd-nav-link-active { box-shadow: inset 0 -3px 0 ${T.green}; }
  .ytd-mobile-menu { max-height: 0; opacity: 0; overflow: hidden; padding-top: 0 !important; padding-bottom: 0 !important; transition: max-height .4s cubic-bezier(.16,1,.3,1), opacity .25s ease, padding .4s ease; }
  .ytd-mobile-menu-open { max-height: 360px; opacity: 1; padding-top: 12px !important; padding-bottom: 20px !important; animation: ytdMenuIn .4s cubic-bezier(.16,1,.3,1) both; }
  .ytd-mobile-menu button { transform: translateX(-12px); opacity: 0; transition: transform .3s ease, opacity .3s ease, color .2s ease, background .2s ease; }
  .ytd-mobile-menu-open button { transform: translateX(0); opacity: 1; }
  .ytd-mobile-menu-open button:nth-child(1) { transition-delay: .04s; }
  .ytd-mobile-menu-open button:nth-child(2) { transition-delay: .08s; }
  .ytd-mobile-menu-open button:nth-child(3) { transition-delay: .12s; }
  .ytd-mobile-menu-open button:nth-child(4) { transition-delay: .16s; }
  .ytd-mobile-menu-open button:nth-child(5) { transition-delay: .2s; }
  .ytd-mobile-menu-open button:nth-child(6) { transition-delay: .24s; }
  .ytd-reveal { opacity: 0; transform: translateY(34px) scale(.985); transition: opacity .8s cubic-bezier(.16,1,.3,1), transform .8s cubic-bezier(.16,1,.3,1); }
  .ytd-reveal-in { opacity: 1; transform: translateY(0) scale(1); }
  .ytd-stagger > * { opacity: 0; animation: ytdFadeUp .7s cubic-bezier(.16,1,.3,1) both; }
  .ytd-stagger > *:nth-child(1) { animation-delay: .05s; }
  .ytd-stagger > *:nth-child(2) { animation-delay: .16s; }
  .ytd-stagger > *:nth-child(3) { animation-delay: .27s; }
  .ytd-stagger > *:nth-child(4) { animation-delay: .38s; }
  .ytd-node-line-draw { stroke-dasharray: 16; stroke-dashoffset: 16; animation: ytdDash 1s cubic-bezier(.16,1,.3,1) .25s both; }
  .ytd-node-pulse circle:first-child { animation: ytdPulse 3.2s ease-in-out 1.2s infinite; }
  .ytd-logo-btn { transition: opacity .2s ease, transform .2s ease; }
  .ytd-logo-btn:hover { opacity: .78; animation: ytdFloat 1.4s ease-in-out infinite; }

  .ytd-nav-link { position: relative; border-radius: 3px; transition: color .2s ease, background .25s ease, transform .25s ease !important; }
  .ytd-nav-link:hover { background: ${T.paperAlt}; transform: translateY(-2px); }
  .ytd-nav-link-active { background: ${T.lime}33 !important; color: ${T.greenDeep} !important; }
  .ytd-nav-link::after { content: ""; position: absolute; left: 0; right: 0; bottom: -4px; height: 2px; background: ${T.green}; transform: scaleX(0); transform-origin: left; transition: transform .28s cubic-bezier(.16,1,.3,1); }
  .ytd-nav-link:hover::after, .ytd-nav-link-active::after { transform: scaleX(1); }
  .ytd-admin-pill:hover { background: ${T.greenDeep} !important; color: ${T.paper} !important; transform: translateY(-2px); }
  .ytd-btn { position: relative; overflow: hidden; transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease; }
  .ytd-btn > *:not(.ytd-btn-shine) { position: relative; z-index: 1; }
  .ytd-btn-shine { position: absolute; inset: -30% auto -30% -35%; width: 22%; background: ${T.paper}66; transform: skewX(-18deg); pointer-events: none; }
  .ytd-btn:hover .ytd-btn-shine { animation: ytdButtonShine .7s ease both; }
  .ytd-btn:hover { transform: translateY(-3px); box-shadow: 0 12px 24px -10px ${T.ink}66; }
  .ytd-btn:active { transform: translateY(0); }
  .ytd-btn:focus-visible, .ytd-nav-link:focus-visible, .ytd-mobile-toggle:focus-visible { outline: 2px solid ${T.lime}; outline-offset: 3px; }
  .ytd-btn svg { transition: transform .25s ease; }
  .ytd-btn:hover svg { transform: translateX(4px); }
  .ytd-pill { transition: transform .2s ease, border-color .2s ease, background .2s ease; }
  .ytd-pill:hover { transform: translateY(-2px); border-color: ${T.green} !important; }
  .ytd-tag { display: inline-block; transition: color .25s ease, background .25s ease, border-color .25s ease, transform .25s ease; }
  .ytd-tag:hover { color: ${T.greenDeep} !important; background: ${T.lime}55; border-color: ${T.green} !important; transform: translateY(-2px); }
  .ytd-section-label { animation: ytdLabelIn .65s cubic-bezier(.16,1,.3,1) both; }
  .ytd-section-label .ytd-node-pulse { transition: transform .3s ease; }
  .ytd-section-label:hover .ytd-node-pulse { transform: rotate(18deg) scale(1.12); }
  .ytd-section-label::after { content: ''; display: block; width: 42px; height: 1px; margin-left: 4px; background: ${T.green}; animation: ytdBorderDraw .8s .2s both; }

  .ytd-card, .ytd-book-card { position: relative; border-radius: 12px; transition: transform .35s cubic-bezier(.16,1,.3,1), box-shadow .35s ease; }
  .ytd-card::after, .ytd-book-card::after { content: ''; position: absolute; left: 0; right: 0; bottom: -1px; height: 3px; background: ${T.lime}; transform: scaleX(0); transition: transform .35s cubic-bezier(.16,1,.3,1); }
  .ytd-card:hover, .ytd-book-card:hover { transform: translateY(-7px); box-shadow: 0 18px 30px -24px ${T.greenDeep}; }
  .ytd-card:hover::after, .ytd-book-card:hover::after { transform: scaleX(1); }
  .ytd-cover { transition: transform .4s cubic-bezier(.16,1,.3,1), box-shadow .4s ease; }
  .ytd-cover img { animation: ytdImageReveal .9s cubic-bezier(.16,1,.3,1) both; transition: transform .7s cubic-bezier(.16,1,.3,1), filter .7s ease; }
  .ytd-cover-label { position: relative; z-index: 2; transition: transform .3s ease, background .3s ease, color .3s ease; }
  .ytd-cover:hover .ytd-cover-label { transform: translateX(5px); background: ${T.lime} !important; color: ${T.ink} !important; }
  .ytd-cover-lines { transition: opacity .4s ease, transform .7s ease; }
  .ytd-cover:hover .ytd-cover-lines { transform: scale(1.08) rotate(-2deg); }
  .ytd-card:hover .ytd-cover { transform: scale(1.025); box-shadow: 0 14px 30px -14px ${T.ink}55; }
  .ytd-card:hover .ytd-cover img { transform: scale(1.08); filter: saturate(1.08) contrast(1.04); }
  .ytd-row { transition: background .2s ease, padding-left .2s ease; }
  .ytd-row:hover { background: ${T.paperAlt}CC; padding-left: 8px; }
  .ytd-admin-tab { transition: background .2s ease, border-color .2s ease, padding-left .2s ease; }
  .ytd-brand-logo-frame { position: relative; display: block; width: 156px; height: 38px; overflow: hidden; }
  .ytd-brand-logo { position: absolute; top: -330%; left: -36%; width: 182%; max-width: none; height: auto; mix-blend-mode: multiply; }
  .ytd-brand-logo-dark .ytd-brand-logo { filter: invert(1); mix-blend-mode: screen; }
  .ytd-admin-sidebar .ytd-brand-logo-frame { width: 142px; height: 35px; }
  .ytd-footer-brand .ytd-brand-logo-frame { width: 178px; height: 44px; }
  .ytd-admin-tab:hover { background: ${T.paperAlt}90; padding-left: 16px; }
  .ytd-table-row { transition: background .18s ease; }
  .ytd-table-row:hover { background: ${T.paperAlt}70; }
  .ytd-stat-card { transition: transform .25s ease, box-shadow .25s ease; }
  .ytd-stat-card:hover { transform: translateY(-4px); box-shadow: 0 10px 24px -14px ${T.ink}55; }
  .ytd-admin-shell { background: linear-gradient(135deg, ${T.paper} 0%, ${T.paperAlt} 100%) !important; }
  .ytd-admin-main { background: linear-gradient(135deg, ${T.paper} 0%, ${T.paperAlt} 100%); }
  .ytd-admin-topbar { min-height: 44px; display: flex; align-items: center; justify-content: space-between; gap: 18px; padding-bottom: 24px; margin-bottom: 24px; border-bottom: 1px solid ${T.line}; }
  .ytd-admin-mobile-tabs { display: none; }
  .ytd-admin-mobile-tab { display: inline-flex; align-items: center; gap: 7px; flex: 0 0 auto; padding: 9px 12px; border: 1px solid ${T.line}; border-radius: 4px; color: ${T.inkSoft}; background: ${T.paper}; cursor: pointer; font: 11px 'Plus Jakarta Sans', sans-serif; white-space: nowrap; }
  .ytd-admin-mobile-tab[aria-current='page'] { border-color: ${T.greenDeep}; color: ${T.paper}; background: ${T.greenDeep}; }
  .ytd-admin-mobile-tab:focus-visible, .ytd-admin-tab:focus-visible { outline: 2px solid ${T.lime}; outline-offset: 2px; }
  .ytd-admin-topbar strong { display: block; font-family: 'DM Serif Display', serif; color: ${T.ink}; font-size: 25px; font-weight: 400; margin-top: 4px; }
  .ytd-admin-kicker { display: block; font-family: 'Space Mono', monospace; color: ${T.green}; font-size: 10px; text-transform: uppercase; letter-spacing: .1em; }
  .ytd-admin-live { display: flex; align-items: center; gap: 8px; font-family: 'Space Mono', monospace; color: ${T.inkSoft}; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
  .ytd-admin-live span { width: 7px; height: 7px; border-radius: 50%; background: ${T.green}; box-shadow: 0 0 0 4px ${T.green}22; animation: ytdPulse 2s ease-in-out infinite; }
  .ytd-admin-view { animation: ytdAdminIn .55s cubic-bezier(.16,1,.3,1) both; }
  .ytd-admin-view-dashboard .ytd-stat-card { opacity: 0; animation: ytdAdminIn .55s cubic-bezier(.16,1,.3,1) both; }
  .ytd-admin-view-dashboard .ytd-stat-card:nth-child(1) { animation-delay: .04s; }
  .ytd-admin-view-dashboard .ytd-stat-card:nth-child(2) { animation-delay: .1s; }
  .ytd-admin-view-dashboard .ytd-stat-card:nth-child(3) { animation-delay: .16s; }
  .ytd-admin-view-dashboard .ytd-stat-card:nth-child(4) { animation-delay: .22s; }
  .ytd-admin-welcome { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 24px; margin: 0 0 26px; padding: 26px 28px; border-radius: 14px; color: ${T.paper}; background: linear-gradient(110deg, ${T.greenDeep}, ${T.red}); box-shadow: 0 18px 34px -26px ${T.greenDeep}; animation: ytdAdminIn .65s .08s cubic-bezier(.16,1,.3,1) both; }
  .ytd-admin-welcome::after { content: ''; position: absolute; width: 240px; height: 240px; right: 16%; top: -150px; border: 1px solid ${T.lime}77; border-radius: 50%; box-shadow: 0 0 0 22px ${T.lime}18, 0 0 0 44px ${T.lime}10; animation: ytdFloat 5s ease-in-out infinite; }
  .ytd-admin-welcome span { font: 10px 'Space Mono', monospace; color: ${T.lime}; letter-spacing: .12em; }
  .ytd-admin-welcome h2 { position: relative; z-index: 1; margin: 8px 0 5px; font: 400 28px 'DM Serif Display', serif; }
  .ytd-admin-welcome p { position: relative; z-index: 1; margin: 0; max-width: 520px; color: ${T.paper}CC; font: 13px/1.5 'Manrope', sans-serif; }
  .ytd-admin-welcome button { position: relative; z-index: 2; display: inline-flex; align-items: center; gap: 8px; border: 0; padding: 12px 15px; color: ${T.ink}; background: ${T.lime}; cursor: pointer; font: 11px 'Space Mono', monospace; transition: transform .25s ease, background .25s ease; }
  .ytd-admin-welcome button:hover { transform: translateY(-3px); background: ${T.paper}; }
  .ytd-admin-view-dashboard h1 { position: relative; display: inline-block; }
  .ytd-admin-view-dashboard h1::after { content: ''; position: absolute; left: 0; right: 0; bottom: -6px; height: 3px; background: ${T.lime}; animation: ytdBorderDraw .7s .2s both; }
  .ytd-stat-card div:nth-child(2) { animation: ytdNumberIn .6s .3s both; }
  .ytd-admin-panel { border-radius: 12px; box-shadow: 0 10px 30px -26px ${T.ink}99; }
  .ytd-admin-list-panel { padding: 22px; background: ${T.paper}; border: 1px solid ${T.line}; }
  .ytd-admin-list-panel > div { transition: transform .22s ease, background .22s ease; border-radius: 4px; }
  .ytd-admin-list-panel > div:hover { transform: translateX(5px); background: ${T.paperAlt}; padding-left: 8px !important; }
  .ytd-admin-form { border-radius: 8px; box-shadow: 0 14px 30px -26px ${T.ink}99; }
  .ytd-admin-add-bar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; padding: 12px 14px; border: 1px solid ${T.line}; background: ${T.paperAlt}; color: ${T.inkSoft}; font: 10px 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: .06em; }
  .ytd-admin-content-modal { display: grid; gap: 18px; width: min(560px, 100%); max-height: min(88dvh, 700px); overflow-y: auto; padding: 28px; border: 1px solid ${T.line}; border-radius: 14px; background: ${T.paper}; box-shadow: 0 28px 70px -30px ${T.ink}; animation: ytdAdminModalIn .45s cubic-bezier(.16,1,.3,1) both; }
  .ytd-admin-section-heading { display: flex; align-items: end; justify-content: space-between; gap: 24px; margin-bottom: 26px; }
  .ytd-admin-section-heading h1 { margin: 5px 0 6px; color: ${T.ink}; font: 500 32px 'Fraunces', serif; }
  .ytd-admin-section-heading p { margin: 0; color: ${T.inkSoft}; font: 13px/1.5 'Plus Jakarta Sans', sans-serif; }
  .ytd-admin-book-toolbar { display: flex; gap: 14px; align-items: center; margin-bottom: 22px; padding: 10px; border: 1px solid ${T.line}; background: ${T.paper}; }
  .ytd-admin-search { display: flex; align-items: center; gap: 8px; min-width: 270px; padding: 9px 11px; color: ${T.inkSoft}; background: ${T.paperAlt}; }
  .ytd-admin-search input { width: 100%; border: 0; outline: 0; color: ${T.ink}; background: transparent; font: 12px 'Plus Jakarta Sans', sans-serif; }
  .ytd-admin-filter-scroll { display: flex; gap: 6px; overflow-x: auto; }
  .ytd-admin-filter-scroll button { flex: 0 0 auto; padding: 7px 10px; border: 1px solid transparent; color: ${T.inkSoft}; background: transparent; cursor: pointer; font: 10px 'JetBrains Mono', monospace; }
  .ytd-admin-filter-scroll button:hover, .ytd-admin-filter-scroll button.is-active { border-color: ${T.greenDeep}; color: ${T.paper}; background: ${T.greenDeep}; }
  .ytd-admin-book-editor { display: grid; gap: 16px; margin-bottom: 24px; padding: 22px; border: 1px solid ${T.green}; background: ${T.paper}; box-shadow: 0 18px 35px -28px ${T.greenDeep}; animation: ytdAdminIn .4s cubic-bezier(.16,1,.3,1) both; }
  .ytd-admin-book-editor-head { display: flex; align-items: start; justify-content: space-between; gap: 12px; }
  .ytd-admin-book-editor-head h2 { margin: 5px 0 0; font: 500 24px 'Fraunces', serif; }
  .ytd-admin-book-editor-head button, .ytd-admin-icon-button { display: grid; place-items: center; width: 32px; height: 32px; border: 1px solid ${T.line}; color: ${T.inkSoft}; background: transparent; cursor: pointer; }
  .ytd-admin-book-fields { display: grid; grid-template-columns: 1.4fr 1.1fr 1fr 1fr; gap: 12px; }
  .ytd-admin-book-editor-actions { position: sticky; bottom: -1px; z-index: 3; display: flex; gap: 9px; padding: 12px 0 2px; background: ${T.paper}; }
  .ytd-admin-modal-backdrop { position: fixed; z-index: 100; inset: 0; display: flex; align-items: center; justify-content: center; overflow-y: auto; padding: 28px; overscroll-behavior: contain; background: ${T.ink}88; animation: ytdAdminFade .25s ease both; }
  .ytd-admin-book-modal { width: min(700px, 100%); min-height: 0; max-height: min(88dvh, 820px); box-sizing: border-box; overflow-y: auto; overscroll-behavior: contain; -webkit-overflow-scrolling: touch; margin: auto; border: 1px solid ${T.line}; border-radius: 14px; box-shadow: 0 28px 70px -30px ${T.ink}; animation: ytdAdminModalIn .45s cubic-bezier(.16,1,.3,1) both; }
  .ytd-admin-book-preview { display: grid; align-content: start; gap: 12px; padding: 28px; color: ${T.ink}; background: ${T.paper}; }
  .ytd-admin-book-preview-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
  .ytd-admin-book-preview-head button { display: grid; place-items: center; border: 1px solid ${T.line}; color: ${T.inkSoft}; background: transparent; cursor: pointer; }
  .ytd-admin-book-preview-cover { width: 100%; max-height: 280px; object-fit: cover; border: 1px solid ${T.line}; }
  .ytd-admin-book-preview h2 { margin: 0; font: 500 32px/1.1 'Fraunces', serif; }
  .ytd-admin-book-preview-author { margin: 0; color: ${T.inkSoft}; font: 13px 'Plus Jakarta Sans', sans-serif; }
  .ytd-admin-book-preview-description { margin: 4px 0 0; color: ${T.inkSoft}; font: 14px/1.55 'Plus Jakarta Sans', sans-serif; }
  .ytd-admin-book-preview-note { margin-top: 10px; padding: 16px; border-left: 3px solid ${T.red}; background: ${T.paperAlt}; }
  .ytd-admin-book-preview-note span { color: ${T.red}; font: 10px 'JetBrains Mono', monospace; text-transform: uppercase; }
  .ytd-admin-book-preview-note p { margin: 8px 0 0; color: ${T.ink}; font: 18px/1.5 'Fraunces', serif; }
  .ytd-admin-book-preview-reviews { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid ${T.line}; color: ${T.inkSoft}; font: 10px 'JetBrains Mono', monospace; text-transform: uppercase; }
  .ytd-admin-book-preview-reviews strong { color: ${T.green}; }
  .ytd-admin-book-preview-review-list { display: grid; gap: 8px; }
  .ytd-admin-book-preview-review-list article { padding: 12px; border: 1px solid ${T.line}; background: ${T.paperAlt}; }
  .ytd-admin-book-preview-review-list strong { color: ${T.ink}; font: 600 12px 'Plus Jakarta Sans', sans-serif; }
  .ytd-admin-book-preview-review-list small { margin-left: 8px; color: ${T.inkSoft}; font: 9px 'JetBrains Mono', monospace; }
  .ytd-admin-book-preview-review-list p { margin: 7px 0 0; color: ${T.ink}; font: 14px/1.45 'Fraunces', serif; }
  .ytd-admin-book-preview-review-list > p { margin: 0; color: ${T.inkSoft}; font: 12px 'Plus Jakarta Sans', sans-serif; }
  .ytd-admin-book-preview-actions { position: sticky; bottom: -1px; z-index: 3; display: flex; align-items: center; gap: 10px; margin-top: 10px; padding: 12px 0 2px; background: ${T.paper}; }
  .ytd-admin-book-preview-actions a { display: inline-flex; align-items: center; gap: 6px; color: ${T.green}; font: 12px 'JetBrains Mono', monospace; }
  .ytd-book-detail-cover { animation: ytdImageReveal .7s cubic-bezier(.16,1,.3,1) both; }
  .ytd-book-detail-copy { animation: ytdAdminIn .55s .12s cubic-bezier(.16,1,.3,1) both; }
  .ytd-book-reviews { margin-top: 72px; padding-top: 30px; border-top: 1px solid ${T.line}; animation: ytdAdminIn .55s .2s cubic-bezier(.16,1,.3,1) both; }
  .ytd-book-reviews-heading { display: flex; align-items: end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
  .ytd-book-reviews-heading h2 { margin: 7px 0 0; color: ${T.ink}; font: 500 30px 'Fraunces', serif; }
  .ytd-book-reviews-heading strong { color: ${T.green}; font: 28px 'Fraunces', serif; }
  .ytd-book-review-list { display: grid; gap: 12px; }
  .ytd-book-review { padding: 18px; border: 1px solid ${T.line}; background: ${T.paper}; animation: ytdAdminIn .45s cubic-bezier(.16,1,.3,1) both; }
  .ytd-book-review > div { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
  .ytd-book-review strong { color: ${T.ink}; font: 600 13px 'Plus Jakarta Sans', sans-serif; }
  .ytd-book-review small { color: ${T.inkSoft}; font: 10px 'JetBrains Mono', monospace; }
  .ytd-book-review p { margin: 10px 0 0; color: ${T.ink}; font: 17px/1.55 'Fraunces', serif; }
  .ytd-book-empty-review { color: ${T.inkSoft}; font: 14px 'Plus Jakarta Sans', sans-serif; }
  .ytd-book-review-form { display: grid; gap: 10px; margin-top: 24px; padding: 20px; border: 1px solid ${T.line}; background: ${T.paperAlt}; }
  .ytd-book-review-form input, .ytd-book-review-form textarea { width: 100%; box-sizing: border-box; padding: 12px; border: 1px solid ${T.line}; outline: none; color: ${T.ink}; background: ${T.paper}; font: 13px 'Plus Jakarta Sans', sans-serif; }
  .ytd-book-review-form textarea { resize: vertical; }
  .ytd-admin-books { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
  .ytd-admin-book-card { position: relative; display: flex; align-items: stretch; min-width: 0; overflow: hidden; border: 1px solid ${T.line}; background: ${T.paper}; opacity: 0; animation: ytdAdminIn .5s cubic-bezier(.16,1,.3,1) both; transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease; }
  .ytd-admin-book-card:hover { transform: translateY(-5px); border-color: ${T.green}; box-shadow: 0 16px 28px -22px ${T.greenDeep}; }
  .ytd-admin-book-cover { display: grid; place-items: center; width: 62px; flex: 0 0 62px; color: ${T.paper}; }
  .ytd-admin-book-cover img { width: 100%; height: 100%; object-fit: cover; opacity: .9; }
  .ytd-admin-book-cover img + svg { display: none; }
  .ytd-admin-book-copy { min-width: 0; padding: 15px 38px 15px 16px; }
  .ytd-admin-book-copy > span { color: ${T.green}; font: 9px 'JetBrains Mono', monospace; text-transform: uppercase; }
  .ytd-admin-book-copy h2 { overflow: hidden; margin: 5px 0 2px; color: ${T.ink}; font: 500 18px/1.15 'Fraunces', serif; text-overflow: ellipsis; white-space: nowrap; }
  .ytd-admin-book-copy small { color: ${T.inkSoft}; font: 11px 'Plus Jakarta Sans', sans-serif; }
  .ytd-admin-book-copy p { display: -webkit-box; overflow: hidden; margin: 8px 0 0; color: ${T.inkSoft}; font: 11px/1.4 'Plus Jakarta Sans', sans-serif; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
  .ytd-admin-book-card .ytd-admin-icon-button { position: absolute; top: 12px; right: 12px; transition: color .2s ease, background .2s ease, transform .2s ease; }
  .ytd-admin-book-card .ytd-admin-icon-button:hover { transform: rotate(-8deg); color: ${T.ink}; background: ${T.lime}; }
  .ytd-admin-team-section { display: grid; gap: 18px; margin-top: 34px; padding-top: 26px; border-top: 1px solid ${T.line}; }
  .ytd-admin-team-heading { display: flex; align-items: start; justify-content: space-between; gap: 18px; }
  .ytd-admin-team-heading h2 { margin: 5px 0 5px; color: ${T.ink}; font: 500 27px 'Fraunces', serif; }
  .ytd-admin-team-heading p { margin: 0; color: ${T.inkSoft}; font: 12px/1.5 'Plus Jakarta Sans', sans-serif; }
  .ytd-admin-team-count { padding: 6px 9px; color: ${T.greenDeep}; background: ${T.lime}; font: 10px 'JetBrains Mono', monospace; white-space: nowrap; }
  .ytd-admin-role-legend { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
  .ytd-admin-role-legend > div { padding: 11px; border: 1px solid ${T.line}; background: ${T.paper}; }
  .ytd-admin-role-legend strong { display: block; color: ${T.ink}; font: 600 11px 'Plus Jakarta Sans', sans-serif; }
  .ytd-admin-role-legend span { display: block; margin-top: 5px; color: ${T.inkSoft}; font: 10px/1.4 'Plus Jakarta Sans', sans-serif; }
  .ytd-admin-members { display: grid; gap: 8px; }
  .ytd-admin-member { display: flex; align-items: center; gap: 12px; padding: 13px; border: 1px solid ${T.line}; background: ${T.paper}; transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease; }
  .ytd-admin-member:hover { transform: translateX(4px); border-color: ${T.green}; box-shadow: 0 10px 24px -20px ${T.greenDeep}; }
  .ytd-admin-member-avatar { display: grid; place-items: center; width: 34px; height: 34px; flex: 0 0 34px; color: ${T.paper}; background: ${T.greenDeep}; border-radius: 50%; font: 600 13px 'Plus Jakarta Sans', sans-serif; }
  .ytd-admin-member-main { min-width: 0; flex: 1; }
  .ytd-admin-member-main strong { display: block; color: ${T.ink}; font: 600 13px 'Plus Jakarta Sans', sans-serif; }
  .ytd-admin-member-main small { display: block; margin-top: 2px; color: ${T.inkSoft}; font: 11px 'JetBrains Mono', monospace; }
  .ytd-admin-permissions { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 8px; }
  .ytd-admin-permissions span { padding: 3px 6px; color: ${T.inkSoft}; background: ${T.paperAlt}; font: 9px 'JetBrains Mono', monospace; }
  .ytd-admin-member-actions { display: grid; justify-items: end; gap: 7px; }
  .ytd-admin-member-actions select { padding: 7px 8px; border: 1px solid ${T.line}; color: ${T.ink}; background: ${T.paper}; font: 10px 'Plus Jakarta Sans', sans-serif; }
  .ytd-admin-member-status { color: ${T.green}; font: 9px 'JetBrains Mono', monospace; }
  .ytd-admin-invite-form { display: grid; grid-template-columns: 1fr 170px auto; gap: 9px; align-items: end; }
  .ytd-admin-settings { max-width: 980px; }
  .ytd-admin-settings-card { display: grid; gap: 20px; padding: 24px; border: 1px solid ${T.line}; background: ${T.paper}; box-shadow: 0 16px 32px -28px ${T.greenDeep}; animation: ytdAdminIn .5s .08s cubic-bezier(.16,1,.3,1) both; }
  .ytd-admin-settings-card-heading { display: flex; align-items: start; justify-content: space-between; gap: 16px; padding-bottom: 16px; border-bottom: 1px solid ${T.line}; }
  .ytd-admin-settings-card-heading span:first-child { color: ${T.green}; font: 10px 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: .08em; }
  .ytd-admin-settings-card-heading h2 { margin: 6px 0 0; color: ${T.ink}; font: 500 25px 'Fraunces', serif; }
  .ytd-admin-settings-index { color: ${T.inkSoft}; font: 11px 'JetBrains Mono', monospace; }
  .ytd-admin-settings-fields { display: grid; grid-template-columns: 1fr 1.4fr 1fr; gap: 14px; }
  @keyframes ytdAdminFade { from { opacity: 0; } to { opacity: 1; } }
  @keyframes ytdAdminDrawer { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
  @keyframes ytdAdminModalIn { from { opacity: 0; transform: translateY(18px) scale(.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
  .ytd-admin-tab { position: relative; overflow: hidden; }
  .ytd-admin-tab::after { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: ${T.lime}; transform: scaleY(0); transition: transform .25s ease; }
  .ytd-admin-tab:hover::after, .ytd-admin-tab[aria-current='page']::after { transform: scaleY(1); }
  .ytd-admin-back svg, .ytd-admin-tab svg { transition: transform .25s ease; }
  .ytd-admin-back:hover svg { transform: translateX(-4px); }
  .ytd-admin-tab:hover svg { transform: scale(1.12); }
  .ytd-admin-books > div, .ytd-media-grid > div { transition: transform .3s cubic-bezier(.16,1,.3,1), box-shadow .3s ease, border-color .3s ease; }
  .ytd-admin-books > div:hover, .ytd-media-grid > div:hover { transform: translateY(-5px); border-color: ${T.green}; box-shadow: 0 14px 24px -18px ${T.ink}; }
  .ytd-books-grid .ytd-reveal { height: 100%; }
  .ytd-book-card { height: 100%; }
  .ytd-book-cover img { transition: transform .7s cubic-bezier(.16,1,.3,1), filter .7s ease; animation: ytdImageReveal .8s cubic-bezier(.16,1,.3,1) both; }
  .ytd-book-card:hover .ytd-book-cover img { transform: scale(1.08); filter: saturate(1.12) contrast(1.05); }
  .ytd-book-card:hover .ytd-book-cover::after { transform: translateX(0); }
  .ytd-book-cover::after { content: ''; position: absolute; inset: 0; background: linear-gradient(115deg, transparent 30%, ${T.lime}44 50%, transparent 70%); transform: translateX(-100%); transition: transform .7s ease; pointer-events: none; }
  .ytd-media-grid > div { position: relative; overflow: hidden; }
  .ytd-media-grid > div::after { content: 'Aperçu'; position: absolute; inset: auto 0 0; padding: 7px; background: ${T.greenDeep}E6; color: ${T.lime}; font: 10px 'Space Mono', monospace; text-align: center; transform: translateY(100%); transition: transform .25s ease; }
  .ytd-media-grid > div:hover::after { transform: translateY(0); }
  .ytd-admin-shell table tbody tr { animation: ytdAdminSlide .45s both; }
  .ytd-admin-shell table tbody tr:nth-child(2) { animation-delay: .04s; }
  .ytd-admin-shell table tbody tr:nth-child(3) { animation-delay: .08s; }
  .ytd-admin-shell table tbody tr:nth-child(4) { animation-delay: .12s; }
  .ytd-admin-shell table tbody tr:nth-child(5) { animation-delay: .16s; }
  .ytd-dashboard-new { display: flex; min-width: 0; flex-direction: column; gap: 24px; }
  .ytd-dashboard-hero { position: relative; overflow: hidden; display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; min-width: 0; padding: 30px 32px; min-height: 190px; color: ${T.paper}; background: ${T.greenDeep}; border-radius: 16px; animation: ytdDashboardRise .6s cubic-bezier(.16,1,.3,1) both; }
  .ytd-dashboard-hero > div:first-child { min-width: 0; }
  .ytd-dashboard-hero::after { content: ''; position: absolute; width: 260px; height: 260px; right: 11%; top: -155px; border: 1px solid ${T.lime}88; border-radius: 50%; box-shadow: 0 0 0 26px ${T.lime}18, 0 0 0 54px ${T.lime}0D; animation: ytdFloat 6s ease-in-out infinite; }
  .ytd-dashboard-eyebrow, .ytd-dashboard-panel-heading span { font: 10px 'JetBrains Mono', monospace; letter-spacing: .1em; text-transform: uppercase; color: ${T.green}; }
  .ytd-dashboard-hero .ytd-dashboard-eyebrow { color: ${T.lime}; }
  .ytd-dashboard-hero h1 { position: relative; z-index: 1; margin: 10px 0 6px; font: 400 clamp(30px, 4vw, 46px) 'Fraunces', serif; letter-spacing: -.04em; }
  .ytd-dashboard-hero p { position: relative; z-index: 1; max-width: 500px; margin: 0; color: ${T.paper}B8; font: 13px/1.6 'Plus Jakarta Sans', sans-serif; }
  .ytd-dashboard-action { position: relative; z-index: 2; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; gap: 8px; padding: 13px 16px; border: 0; color: ${T.ink}; background: ${T.lime}; cursor: pointer; font: 11px 'JetBrains Mono', monospace; transition: transform .25s ease, background .25s ease; }
  .ytd-dashboard-action:hover { transform: translateY(-4px); background: ${T.paper}; }
  .ytd-dashboard-stat-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
  .ytd-dashboard-stat { padding: 20px; background: ${T.paper}; border: 1px solid ${T.line}; border-radius: 12px; box-shadow: 0 12px 26px -24px ${T.greenDeep}; opacity: 0; animation: ytdDashboardRise .55s cubic-bezier(.16,1,.3,1) both; }
  .ytd-dashboard-stat { transition: transform .3s cubic-bezier(.16,1,.3,1), border-color .3s ease, box-shadow .3s ease; }
  .ytd-dashboard-stat:hover { transform: translateY(-7px); border-color: ${T.green}; box-shadow: 0 18px 32px -24px ${T.greenDeep}; }
  .ytd-dashboard-stat-top svg { transition: transform .35s ease; }
  .ytd-dashboard-stat:hover .ytd-dashboard-stat-top svg { transform: rotate(-12deg) scale(1.18); }
  .ytd-dashboard-stat-top { display: flex; min-width: 0; align-items: center; justify-content: space-between; gap: 8px; color: ${T.green}; font: 10px 'JetBrains Mono', monospace; text-transform: uppercase; }
  .ytd-dashboard-stat-top span { min-width: 0; overflow-wrap: anywhere; }
  .ytd-dashboard-stat strong { display: block; margin: 18px 0 5px; color: ${T.ink}; font: 500 38px 'Fraunces', serif; }
  .ytd-dashboard-stat small { color: ${T.inkSoft}; font: 11px 'Plus Jakarta Sans', sans-serif; }
  .ytd-dashboard-quick-actions { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; padding: 12px 14px; border: 1px solid ${T.line}; background: ${T.paperAlt}; border-radius: 10px; animation: ytdDashboardRise .55s .18s cubic-bezier(.16,1,.3,1) both; }
  .ytd-dashboard-quick-actions > span { margin-right: 5px; color: ${T.inkSoft}; font: 10px 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: .08em; }
  .ytd-dashboard-quick-actions button { display: inline-flex; align-items: center; gap: 6px; padding: 8px 10px; border: 1px solid ${T.line}; color: ${T.ink}; background: ${T.paper}; cursor: pointer; font: 10px 'JetBrains Mono', monospace; transition: transform .25s ease, color .25s ease, background .25s ease, border-color .25s ease; }
  .ytd-dashboard-quick-actions button:hover { transform: translateY(-3px); color: ${T.paper}; background: ${T.greenDeep}; border-color: ${T.greenDeep}; }
  .ytd-dashboard-quick-actions button svg { transition: transform .25s ease; }
  .ytd-dashboard-quick-actions button:hover svg { transform: rotate(8deg) scale(1.15); }
  .ytd-dashboard-grid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(280px, 1fr); gap: 24px; min-width: 0; }
  .ytd-dashboard-panel { min-width: 0; padding: 24px; background: ${T.paper}; border: 1px solid ${T.line}; border-radius: 14px; box-shadow: 0 14px 30px -26px ${T.greenDeep}; animation: ytdDashboardRise .65s .2s cubic-bezier(.16,1,.3,1) both; }
  .ytd-dashboard-panel:hover { box-shadow: 0 20px 36px -28px ${T.greenDeep}; }
  .ytd-dashboard-panel-heading { display: flex; min-width: 0; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
  .ytd-dashboard-panel-heading > div { min-width: 0; }
  .ytd-dashboard-panel-heading h2 { margin: 7px 0 0; color: ${T.ink}; font: 500 24px/1.12 'Fraunces', serif; overflow-wrap: anywhere; }
  .ytd-dashboard-panel-heading button { display: inline-flex; align-items: center; gap: 5px; border: 0; background: none; color: ${T.green}; cursor: pointer; font: 10px 'JetBrains Mono', monospace; }
  .ytd-dashboard-post, .ytd-dashboard-collab { display: grid; align-items: center; gap: 12px; min-width: 0; padding: 14px 0; border-top: 1px solid ${T.line}; transition: transform .25s ease, padding-left .25s ease, background .25s ease; }
  .ytd-dashboard-post { grid-template-columns: 32px minmax(0, 1fr) auto; }
  .ytd-dashboard-post:hover, .ytd-dashboard-collab:hover { padding-left: 8px; transform: translateX(4px); background: ${T.paperAlt}; }
  .ytd-dashboard-index { color: ${T.green}; font: 11px 'JetBrains Mono', monospace; }
  .ytd-dashboard-post strong, .ytd-dashboard-collab strong { display: block; color: ${T.ink}; font: 600 13px/1.35 'Plus Jakarta Sans', sans-serif; }
  .ytd-dashboard-post small, .ytd-dashboard-collab small { display: block; margin-top: 4px; color: ${T.inkSoft}; font: 10px 'JetBrains Mono', monospace; }
  .ytd-dashboard-collab { grid-template-columns: 34px minmax(0, 1fr) auto; }
  .ytd-dashboard-post > div, .ytd-dashboard-collab > div:nth-child(2) { min-width: 0; }
  .ytd-dashboard-avatar { display: grid; place-items: center; width: 30px; height: 30px; color: ${T.paper}; background: ${T.red}; border-radius: 50%; font: 600 13px 'Plus Jakarta Sans', sans-serif; }
  .ytd-dashboard-chart { animation-delay: .3s; }
  .ytd-dashboard-bars { display: grid; gap: 13px; }
  .ytd-dashboard-bar-row { display: grid; grid-template-columns: minmax(120px, 190px) minmax(0, 1fr) 22px; align-items: center; gap: 14px; min-width: 0; color: ${T.inkSoft}; font: 11px 'JetBrains Mono', monospace; }
  .ytd-dashboard-bar-row > span { min-width: 0; overflow-wrap: anywhere; }
  .ytd-dashboard-bar-row > div { height: 9px; overflow: hidden; background: ${T.paperAlt}; border-radius: 20px; }
  .ytd-dashboard-bar-row i { display: block; height: 100%; background: linear-gradient(90deg, ${T.green}, ${T.red}); border-radius: inherit; animation: ytdBorderDraw .9s .35s both; }
  .ytd-dashboard-bar-row:hover i { filter: brightness(1.12); box-shadow: 0 0 10px ${T.red}66; }
  .ytd-dashboard-bar-row strong { color: ${T.ink}; text-align: right; }
  .ytd-footer-cta { position: relative; overflow: hidden; }
  .ytd-footer { display: block; width: 100%; clear: both; margin: 0; padding: 0; background: ${T.ink}; }
  .ytd-footer-legal { margin: 0; background: ${T.ink}; }
  .ytd-footer-cta { color: ${T.paper}; background: linear-gradient(115deg, ${T.greenDeep} 0%, ${T.greenDeep} 58%, ${T.red} 150%); }
  .ytd-footer-cta::after { content: ''; position: absolute; width: 420px; height: 420px; right: -90px; top: -250px; border: 1px solid ${T.lime}66; border-radius: 50%; box-shadow: 0 0 0 26px ${T.lime}10, 0 0 0 54px ${T.lime}08; animation: ytdAccentPulse 5s ease-in-out infinite; pointer-events: none; }
  .ytd-footer-eyebrow, .ytd-footer-heading { position: relative; z-index: 1; display: block; color: ${T.lime}; font: 10px 'JetBrains Mono', monospace; letter-spacing: .12em; text-transform: uppercase; }
  .ytd-footer-cta h2 { position: relative; z-index: 1; max-width: 640px; margin: 10px 0 8px; color: ${T.paper}; font: 400 clamp(30px, 4vw, 52px) 'Fraunces', serif; letter-spacing: -.04em; line-height: 1.02; }
  .ytd-footer-cta p { position: relative; z-index: 1; max-width: 480px; margin: 0; color: ${T.paper}B8; font: 13px/1.55 'Plus Jakarta Sans', sans-serif; }
  .ytd-footer-cta-button { position: relative; z-index: 2; display: inline-flex; align-items: center; gap: 8px; border: 0; padding: 14px 16px; color: ${T.ink}; background: ${T.lime}; cursor: pointer; font: 10px 'JetBrains Mono', monospace; transition: transform .3s cubic-bezier(.16,1,.3,1), background .3s ease, box-shadow .3s ease; }
  .ytd-footer-cta-button:hover { transform: translateY(-6px) rotate(-1deg); background: ${T.paper}; box-shadow: 0 16px 28px -16px #0009; }
  .ytd-footer-cta-button svg:last-child { transition: transform .25s ease; }
  .ytd-footer-cta-button:hover svg:last-child { transform: translate(4px, -4px); }
  .ytd-footer-marquee { display: flex; gap: 40px; overflow: hidden; padding: 13px 0; color: ${T.greenDeep}; background: ${T.lime}; white-space: nowrap; font: 10px 'JetBrains Mono', monospace; letter-spacing: .08em; }
  .ytd-footer-marquee span { flex: 0 0 auto; animation: ytdMarquee 24s linear infinite; }
  .ytd-footer-base { color: ${T.paper}; background: ${T.ink}; }
  .ytd-footer-heading { margin-bottom: 16px; color: ${T.red}; }
  .ytd-footer-link { display: flex; align-items: center; gap: 7px; width: max-content; padding: 0; margin: 0 0 10px; border: 0; color: ${T.paper}CC; background: none; text-align: left; cursor: pointer; font: 13px 'Plus Jakarta Sans', sans-serif; transition: color .25s ease, transform .25s ease; }
  .ytd-footer-link:hover { color: ${T.lime} !important; transform: translateX(7px); }
  .ytd-footer-link svg { opacity: 0; transform: translate(-4px, 4px); transition: opacity .25s ease, transform .25s ease; }
  .ytd-footer-link:hover svg { opacity: 1; transform: translate(0, 0); }
  .ytd-footer-cta::before { content: ""; position: absolute; width: 280px; height: 280px; right: 8%; top: -160px; border: 1px solid ${T.lime}66; border-radius: 50%; box-shadow: 0 0 0 24px ${T.lime}12, 0 0 0 48px ${T.lime}08; animation: ytdFloat 6s ease-in-out infinite; }
  .ytd-footer-cta-button { transition: transform .25s ease, background .25s ease, box-shadow .25s ease; }
  .ytd-footer-cta-button:hover { transform: translateY(-4px) rotate(-1deg); background: ${T.paper} !important; box-shadow: 0 12px 24px -12px #0008; }
  .ytd-footer-link { display: block; padding: 0; border: 0; background: none; text-align: left; transition: color .2s ease, transform .2s ease; }
  .ytd-footer-link:hover { color: ${T.green} !important; transform: translateX(6px); }
  .ytd-footer-brand { animation: ytdAdminIn .7s .15s both; }
  .ytd-hero-visual { animation: ytdFadeUp .9s .18s cubic-bezier(.16,1,.3,1) both; }
  .ytd-editorial-hero .ytd-hero-visual::before, .ytd-editorial-hero .ytd-hero-visual::after { content: ''; position: absolute; z-index: -1; border: 1px solid ${T.red}55; border-radius: 50%; pointer-events: none; }
  .ytd-editorial-hero .ytd-hero-visual::before { inset: -18px 22px 20px -18px; animation: ytdOrbit 12s linear infinite; }
  .ytd-editorial-hero .ytd-hero-visual::after { width: 14px; height: 14px; right: 2%; top: 18%; background: ${T.green}; border: 0; animation: ytdAccentPulse 3s ease-in-out infinite; }
  .ytd-hero-visual:hover { transform: translateY(-8px) rotate(1deg); transition: transform .45s cubic-bezier(.16,1,.3,1); }
  .ytd-form-panel { position: relative; overflow: hidden; }
  .ytd-form-panel::after { content: ""; position: absolute; inset: 0 auto 0 -30%; width: 22%; background: ${T.paper}33; transform: skewX(-20deg); pointer-events: none; }
  .ytd-form-panel:hover::after { animation: ytdShimmer 1.2s ease both; }
  .ytd-newsletter-panel { position: relative; overflow: hidden; border-radius: 16px; box-shadow: 0 20px 40px -30px ${T.greenDeep}; }
  .ytd-newsletter-panel::before { content: ''; position: absolute; width: 260px; height: 260px; right: -80px; top: -120px; border: 1px solid ${T.lime}55; border-radius: 50%; box-shadow: 0 0 0 22px ${T.lime}12, 0 0 0 44px ${T.lime}08; animation: ytdAccentPulse 5s ease-in-out infinite; pointer-events: none; }
  .ytd-home-hero, .ytd-hero-grid, .ytd-grid-4, .ytd-grid-3, .ytd-dashboard-grid { min-width: 0; }
  .ytd-dashboard-post strong, .ytd-dashboard-collab strong { overflow-wrap: anywhere; }
  .ytd-admin-main { overflow: hidden; }
  .ytd-admin-main table { min-width: 720px; }
  .ytd-admin-main table { display: block; overflow-x: auto; }
  .ytd-row h4 { min-width: 0; }

  @media (max-width: 1100px) {
    .ytd-home-hero { gap: 34px !important; }
    .ytd-grid-4 { grid-template-columns: repeat(2, 1fr) !important; gap: 22px !important; }
    .ytd-dashboard-stat-grid { grid-template-columns: repeat(2, 1fr); }
    .ytd-dashboard-grid { grid-template-columns: 1fr 1fr; gap: 16px; }
    .ytd-dashboard-bar-row { grid-template-columns: 160px 1fr 22px; }
    .ytd-collab-hero, .ytd-meet-hero { gap: 34px; }
    .ytd-collab-layout { grid-template-columns: .85fr 1.15fr; }
  }

  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { scroll-behavior: auto !important; }
    .ytd-reveal, .ytd-stagger > *, .ytd-page-anim, .ytd-node-pulse circle:first-child, .ytd-node-line-draw, .ytd-app-shell, .ytd-cover img, .ytd-logo-btn, .ytd-hero-visual, .ytd-scroll-progress, .ytd-footer-cta::before, .ytd-admin-view, .ytd-admin-view-dashboard .ytd-stat-card, .ytd-admin-shell table tbody tr, .ytd-section-label, .ytd-dashboard-new, .ytd-dashboard-stat, .ytd-dashboard-panel, .ytd-dashboard-quick-actions, .ytd-admin-modal-backdrop, .ytd-admin-book-modal, .ytd-editorial-hero .ytd-hero-visual::before, .ytd-editorial-hero .ytd-hero-visual::after, .ytd-newsletter-panel::before, .ytd-footer-cta::after, .ytd-footer-marquee span, .ytd-platform-pillar, .ytd-meet-hero-copy, .ytd-meet-hero-mark, .ytd-meet-method-card, .ytd-founder-portrait, .ytd-collab-hero, .ytd-collab-hero-note, .ytd-collab-aside, .ytd-collab-type, .ytd-collab-success { animation: none !important; transition: none !important; opacity: 1 !important; transform: none !important; stroke-dashoffset: 0 !important; }
  }
  @media (hover: none) {
    .ytd-card:hover, .ytd-book-card:hover, .ytd-platform-pillar:hover, .ytd-platform-pillar:hover .ytd-platform-icon, .ytd-meet-method-card:hover, .ytd-admin-book-card:hover, .ytd-admin-member:hover, .ytd-hero-visual:hover { transform: none; }
    .ytd-btn:hover { transform: none; box-shadow: none; }
  }
  @media (max-width: 860px) {
    .ytd-desktop-nav { display: none !important; }
    .ytd-mobile-toggle { display: block !important; }
    .ytd-hero-grid, .ytd-grid-3, .ytd-founder, .ytd-form-row, .ytd-dash-grid, .ytd-stat-grid, .ytd-home-hero { grid-template-columns: 1fr !important; }
    .ytd-grid-4 { grid-template-columns: repeat(2, 1fr) !important; }
    .ytd-hero-visual { order: -1; max-width: 560px; width: 100%; margin: 0 auto 12px; }
    .ytd-collab-hero, .ytd-meet-hero { grid-template-columns: 1fr; gap: 30px; }
    .ytd-collab-hero-note { justify-self: start; }
    .ytd-collab-layout { grid-template-columns: 1fr; }
    .ytd-collab-aside { order: 2; }
    .ytd-collab-form-wrap { order: 1; }
    .ytd-qa-row { grid-template-columns: 1fr !important; gap: 10px !important; }
    .ytd-admin-sidebar { display: none; }
    .ytd-admin-main { padding: 24px 20px 54px !important; }
    .ytd-admin-mobile-tabs { display: flex; gap: 8px; overflow-x: auto; margin: -4px 0 22px; padding: 2px 2px 8px; scrollbar-width: thin; }
    .ytd-admin-topbar { align-items: flex-start; flex-direction: column; }
    .ytd-dashboard-stat-grid, .ytd-dashboard-grid { grid-template-columns: 1fr !important; }
    .ytd-dashboard-hero { align-items: flex-start; flex-direction: column; padding: 24px; }
    .ytd-dashboard-bar-row { grid-template-columns: 1fr 80px 18px; gap: 8px; font-size: 9px; }
    .ytd-admin-welcome { align-items: flex-start; flex-direction: column; padding: 22px; }
    .ytd-media-grid { grid-template-columns: repeat(3, 1fr) !important; }
    .ytd-admin-books { grid-template-columns: 1fr !important; }
    .ytd-book-detail-grid { grid-template-columns: minmax(0, 220px) 1fr !important; gap: 28px !important; }
    .ytd-book-detail-page { padding-top: 38px !important; }
  }
  @media (max-width: 640px) {
    .ytd-footer { margin: 0; }
    .ytd-footer-cta-inner { gap: 24px !important; }
    .ytd-footer-cta-button { width: 100%; justify-content: center; }
    .ytd-footer-marquee { padding: 11px 0; font-size: 9px; }
    .ytd-footer-base > div { grid-template-columns: 1fr !important; gap: 26px !important; }
    .ytd-footer-brand, .ytd-footer-brand p { max-width: 100% !important; }
    .ytd-footer-legal { padding: 16px 18px !important; line-height: 1.5; }
    .ytd-app-shell { background-size: 240% 240% !important; animation-duration: 40s; }
    .ytd-app-shell::after { animation-duration: 22s; opacity: .45; }
    .ytd-navbar > div:not(.ytd-scroll-progress) { padding-left: 16px !important; padding-right: 16px !important; }
    .ytd-logo-btn span { font-size: 20px !important; }
    .ytd-logo-btn .ytd-brand-logo-frame { width: 132px; height: 32px; }
    .ytd-mobile-menu { padding-left: 16px !important; padding-right: 16px !important; }
    .ytd-home-hero { padding: 44px 18px 38px !important; gap: 22px !important; }
    .ytd-home-hero .ytd-btn { width: 100%; justify-content: center; }
    .ytd-platform-intro { padding: 0 18px 48px !important; }
    .ytd-platform-intro-heading { grid-template-columns: 1fr; gap: 10px; }
    .ytd-platform-pillars { grid-template-columns: 1fr; }
    .ytd-platform-pillar { min-height: auto; }
    .ytd-collab-hero { grid-template-columns: 1fr; gap: 24px; padding-bottom: 42px; }
    .ytd-collab-hero-note { margin-left: auto; }
    .ytd-collab-layout { grid-template-columns: 1fr; }
    .ytd-collab-aside, .ytd-collab-form-wrap { padding: 20px; }
    .ytd-meet-hero { grid-template-columns: 1fr; gap: 34px; min-height: auto; padding-bottom: 54px; }
    .ytd-meet-hero-mark { width: 190px; height: 220px; margin: 0 auto; }
    .ytd-meet-lead { font-size: 15px; }
    .ytd-meet-method-grid { grid-template-columns: 1fr; }
    .ytd-meet-method-card { min-height: auto; }
    .ytd-founder-panel { grid-template-columns: 1fr !important; gap: 28px !important; padding: 20px; }
    .ytd-founder-portrait { width: 180px !important; height: 220px !important; }
    .ytd-home-hero h1 { font-size: clamp(34px, 12vw, 48px) !important; }
    .ytd-home-hero p { font-size: 15px !important; line-height: 1.6 !important; }
    .ytd-hero-visual { min-height: 280px !important; }
    .ytd-grid-4 { grid-template-columns: 1fr !important; }
    .ytd-grid-3 { gap: 22px !important; }
    .ytd-book-detail-grid { grid-template-columns: 1fr !important; gap: 28px !important; }
    .ytd-book-detail-cover { width: min(220px, 72vw) !important; margin: 0 auto; }
    .ytd-book-detail-copy h1 { font-size: clamp(32px, 10vw, 46px) !important; }
    .ytd-book-detail-copy > p { font-size: 17px !important; }
    .ytd-row { align-items: flex-start !important; flex-direction: column !important; gap: 8px !important; padding: 16px 0 !important; }
    .ytd-row > div { width: 100%; }
    .ytd-row h4 { white-space: normal !important; font-size: 16px !important; }
    .ytd-dashboard-stat-grid { grid-template-columns: 1fr; }
    .ytd-dashboard-quick-actions { align-items: stretch; flex-direction: column; }
    .ytd-dashboard-quick-actions > span { margin-bottom: 2px; }
    .ytd-dashboard-quick-actions button { justify-content: center; }
    .ytd-dashboard-panel { padding: 18px; }
    .ytd-dashboard-panel-heading h2 { font-size: 21px; }
    .ytd-dashboard-post, .ytd-dashboard-collab { grid-template-columns: 26px 1fr; }
    .ytd-dashboard-post > .ytd-tag, .ytd-dashboard-collab > .ytd-tag { grid-column: 2; justify-self: start; }
    .ytd-dashboard-bar-row { grid-template-columns: 1fr 58px 18px; font-size: 8px; }
    .ytd-footer-cta h2 { font-size: 28px !important; }
    .ytd-footer-cta-inner { grid-template-columns: 1fr !important; align-items: start !important; padding: 42px 18px !important; }
    .ytd-footer-base > div { grid-template-columns: 1fr 1fr !important; gap: 30px !important; padding: 38px 18px 28px !important; }
    .ytd-footer-brand { grid-column: 1 / -1; max-width: none !important; }
    .ytd-books-grid { grid-template-columns: 1fr !important; }
    .ytd-book-cover { height: 260px !important; }
    .ytd-admin-main { padding: 20px 14px 44px !important; }
    .ytd-admin-mobile-tabs { margin-right: -14px; margin-left: -14px; padding-right: 14px; padding-left: 14px; }
    .ytd-admin-topbar { gap: 10px; padding-bottom: 18px; margin-bottom: 18px; }
    .ytd-admin-topbar strong { font-size: 22px; }
    .ytd-admin-live { font-size: 9px; }
    .ytd-dashboard-hero { min-height: 0; padding: 20px; gap: 20px; }
    .ytd-dashboard-hero h1 { font-size: 34px; }
    .ytd-dashboard-action { width: 100%; justify-content: center; }
    .ytd-btn { min-height: 44px; }
    .ytd-dashboard-panel-heading { gap: 10px; }
    .ytd-dashboard-panel-heading button { flex-shrink: 0; }
    .ytd-dashboard-post, .ytd-dashboard-collab { align-items: start; }
    .ytd-dashboard-post > .ytd-status-pill, .ytd-dashboard-collab > .ytd-status-pill { grid-column: 2; justify-self: start; }
    .ytd-dashboard-bar-row { grid-template-columns: minmax(0, 1fr) 52px 16px; }
    .ytd-admin-form input, .ytd-admin-form select { width: 100% !important; }
    .ytd-admin-form > * { width: 100%; }
    .ytd-admin-form button { width: 100%; justify-content: center; }
    .ytd-admin-section-heading { align-items: flex-start; flex-direction: column; gap: 14px; }
    .ytd-admin-section-heading .ytd-btn { width: 100%; justify-content: center; }
    .ytd-admin-book-toolbar { align-items: stretch; flex-direction: column; }
    .ytd-admin-search { min-width: 0; }
    .ytd-admin-book-fields { grid-template-columns: 1fr; }
    .ytd-admin-book-editor-actions { flex-direction: column; }
    .ytd-admin-book-editor-actions .ytd-btn { justify-content: center; }
    .ytd-admin-modal-backdrop { align-items: center; padding: 14px; }
    .ytd-admin-book-modal { width: 100%; max-height: 94dvh; padding: 18px !important; border-radius: 12px; }
    .ytd-admin-add-bar { align-items: stretch; flex-direction: column; }
    .ytd-admin-add-bar .ytd-btn { width: 100%; justify-content: center; }
    .ytd-admin-content-modal { max-height: 94dvh; padding: 18px; border-radius: 12px; }
    .ytd-admin-settings-card { padding: 18px; }
    .ytd-admin-settings-fields { grid-template-columns: 1fr; }
    .ytd-book-reviews { margin-top: 48px; }
    .ytd-book-review-form .ytd-btn { justify-content: center; }
    .ytd-admin-books { grid-template-columns: 1fr !important; }
    .ytd-admin-team-heading { align-items: flex-start; flex-direction: column; }
    .ytd-admin-role-legend { grid-template-columns: 1fr 1fr; }
    .ytd-admin-member { align-items: flex-start; flex-wrap: wrap; }
    .ytd-admin-member-actions { width: 100%; grid-template-columns: 1fr auto; display: grid; justify-items: stretch; align-items: center; }
    .ytd-admin-member-status { text-align: right; }
    .ytd-admin-invite-form { grid-template-columns: 1fr; }
    .ytd-admin-invite-form .ytd-btn { justify-content: center; }
    .ytd-collab-form-wrap { padding: 16px; }
    .ytd-collab-hero-note { max-width: 100%; }
    .ytd-book-reviews-heading h2 { font-size: 25px; }
    .ytd-book-review p { font-size: 16px; }
  }
`;
