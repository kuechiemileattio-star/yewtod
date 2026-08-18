import { FONTS, T } from "../theme.js";

export const GLOBAL_STYLES = `${FONTS}
  * { box-sizing: border-box; }
  body { margin: 0; background: ${T.paper}; }
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

  .ytd-app-shell { position: relative; overflow: hidden; background-size: 180% 180% !important; animation: ytdAmbient 24s ease-in-out infinite; }
  .ytd-app-shell::before { content: ""; position: fixed; inset: 0; pointer-events: none; opacity: .26; background-image: linear-gradient(${T.line}38 1px, transparent 1px), linear-gradient(90deg, ${T.line}38 1px, transparent 1px); background-size: 44px 44px; mask-image: linear-gradient(to bottom, black, transparent 72%); }
  .ytd-app-shell::after { content: ""; position: absolute; z-index: 0; top: -20%; left: -20%; width: 34%; height: 150%; pointer-events: none; background: linear-gradient(90deg, transparent, ${T.lime}14, transparent); filter: blur(12px); animation: ytdLightSweep 14s ease-in-out 2s infinite; }
  .ytd-page-anim { animation: ytdFadeUp .65s cubic-bezier(.16,1,.3,1) both; position: relative; z-index: 1; }
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
  .ytd-admin-tab:hover { background: ${T.paperAlt}90; padding-left: 16px; }
  .ytd-table-row { transition: background .18s ease; }
  .ytd-table-row:hover { background: ${T.paperAlt}70; }
  .ytd-stat-card { transition: transform .25s ease, box-shadow .25s ease; }
  .ytd-stat-card:hover { transform: translateY(-4px); box-shadow: 0 10px 24px -14px ${T.ink}55; }
  .ytd-admin-shell { background: linear-gradient(135deg, ${T.paper} 0%, ${T.paperAlt} 100%) !important; }
  .ytd-admin-main { background: linear-gradient(135deg, ${T.paper} 0%, ${T.paperAlt} 100%); }
  .ytd-admin-topbar { min-height: 44px; display: flex; align-items: center; justify-content: space-between; gap: 18px; padding-bottom: 24px; margin-bottom: 24px; border-bottom: 1px solid ${T.line}; }
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
  .ytd-dashboard-new { display: flex; flex-direction: column; gap: 24px; }
  .ytd-dashboard-hero { position: relative; overflow: hidden; display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; padding: 30px 32px; min-height: 190px; color: ${T.paper}; background: ${T.greenDeep}; border-radius: 16px; animation: ytdDashboardRise .6s cubic-bezier(.16,1,.3,1) both; }
  .ytd-dashboard-hero::after { content: ''; position: absolute; width: 260px; height: 260px; right: 11%; top: -155px; border: 1px solid ${T.lime}88; border-radius: 50%; box-shadow: 0 0 0 26px ${T.lime}18, 0 0 0 54px ${T.lime}0D; animation: ytdFloat 6s ease-in-out infinite; }
  .ytd-dashboard-eyebrow, .ytd-dashboard-panel-heading span { font: 10px 'JetBrains Mono', monospace; letter-spacing: .1em; text-transform: uppercase; color: ${T.green}; }
  .ytd-dashboard-hero .ytd-dashboard-eyebrow { color: ${T.lime}; }
  .ytd-dashboard-hero h1 { position: relative; z-index: 1; margin: 10px 0 6px; font: 400 clamp(30px, 4vw, 46px) 'Fraunces', serif; letter-spacing: -.04em; }
  .ytd-dashboard-hero p { position: relative; z-index: 1; max-width: 500px; margin: 0; color: ${T.paper}B8; font: 13px/1.6 'Plus Jakarta Sans', sans-serif; }
  .ytd-dashboard-action { position: relative; z-index: 2; display: inline-flex; align-items: center; gap: 8px; padding: 13px 16px; border: 0; color: ${T.ink}; background: ${T.lime}; cursor: pointer; font: 11px 'JetBrains Mono', monospace; transition: transform .25s ease, background .25s ease; }
  .ytd-dashboard-action:hover { transform: translateY(-4px); background: ${T.paper}; }
  .ytd-dashboard-stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
  .ytd-dashboard-stat { padding: 20px; background: ${T.paper}; border: 1px solid ${T.line}; border-radius: 12px; box-shadow: 0 12px 26px -24px ${T.greenDeep}; opacity: 0; animation: ytdDashboardRise .55s cubic-bezier(.16,1,.3,1) both; }
  .ytd-dashboard-stat-top { display: flex; align-items: center; justify-content: space-between; color: ${T.green}; font: 10px 'JetBrains Mono', monospace; text-transform: uppercase; }
  .ytd-dashboard-stat strong { display: block; margin: 18px 0 5px; color: ${T.ink}; font: 500 38px 'Fraunces', serif; }
  .ytd-dashboard-stat small { color: ${T.inkSoft}; font: 11px 'Plus Jakarta Sans', sans-serif; }
  .ytd-dashboard-grid { display: grid; grid-template-columns: 1.45fr 1fr; gap: 24px; }
  .ytd-dashboard-panel { padding: 24px; background: ${T.paper}; border: 1px solid ${T.line}; border-radius: 14px; box-shadow: 0 14px 30px -26px ${T.greenDeep}; animation: ytdDashboardRise .65s .2s cubic-bezier(.16,1,.3,1) both; }
  .ytd-dashboard-panel-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
  .ytd-dashboard-panel-heading h2 { margin: 7px 0 0; color: ${T.ink}; font: 500 24px 'Fraunces', serif; }
  .ytd-dashboard-panel-heading button { display: inline-flex; align-items: center; gap: 5px; border: 0; background: none; color: ${T.green}; cursor: pointer; font: 10px 'JetBrains Mono', monospace; }
  .ytd-dashboard-post, .ytd-dashboard-collab { display: grid; align-items: center; gap: 12px; padding: 14px 0; border-top: 1px solid ${T.line}; transition: transform .25s ease, padding-left .25s ease, background .25s ease; }
  .ytd-dashboard-post { grid-template-columns: 32px 1fr auto; }
  .ytd-dashboard-post:hover, .ytd-dashboard-collab:hover { padding-left: 8px; transform: translateX(4px); background: ${T.paperAlt}; }
  .ytd-dashboard-index { color: ${T.green}; font: 11px 'JetBrains Mono', monospace; }
  .ytd-dashboard-post strong, .ytd-dashboard-collab strong { display: block; color: ${T.ink}; font: 600 13px/1.35 'Plus Jakarta Sans', sans-serif; }
  .ytd-dashboard-post small, .ytd-dashboard-collab small { display: block; margin-top: 4px; color: ${T.inkSoft}; font: 10px 'JetBrains Mono', monospace; }
  .ytd-dashboard-collab { grid-template-columns: 34px 1fr auto; }
  .ytd-dashboard-avatar { display: grid; place-items: center; width: 30px; height: 30px; color: ${T.paper}; background: ${T.red}; border-radius: 50%; font: 600 13px 'Plus Jakarta Sans', sans-serif; }
  .ytd-dashboard-chart { animation-delay: .3s; }
  .ytd-dashboard-bars { display: grid; gap: 13px; }
  .ytd-dashboard-bar-row { display: grid; grid-template-columns: 190px 1fr 22px; align-items: center; gap: 14px; color: ${T.inkSoft}; font: 11px 'JetBrains Mono', monospace; }
  .ytd-dashboard-bar-row > div { height: 9px; overflow: hidden; background: ${T.paperAlt}; border-radius: 20px; }
  .ytd-dashboard-bar-row i { display: block; height: 100%; background: linear-gradient(90deg, ${T.green}, ${T.red}); border-radius: inherit; animation: ytdBorderDraw .9s .35s both; }
  .ytd-dashboard-bar-row strong { color: ${T.ink}; text-align: right; }
  .ytd-footer-cta { position: relative; overflow: hidden; }
  .ytd-footer-cta::before { content: ""; position: absolute; width: 280px; height: 280px; right: 8%; top: -160px; border: 1px solid ${T.lime}66; border-radius: 50%; box-shadow: 0 0 0 24px ${T.lime}12, 0 0 0 48px ${T.lime}08; animation: ytdFloat 6s ease-in-out infinite; }
  .ytd-footer-cta-button { transition: transform .25s ease, background .25s ease, box-shadow .25s ease; }
  .ytd-footer-cta-button:hover { transform: translateY(-4px) rotate(-1deg); background: ${T.paper} !important; box-shadow: 0 12px 24px -12px #0008; }
  .ytd-footer-link { display: block; padding: 0; border: 0; background: none; text-align: left; transition: color .2s ease, transform .2s ease; }
  .ytd-footer-link:hover { color: ${T.green} !important; transform: translateX(6px); }
  .ytd-footer-brand { animation: ytdAdminIn .7s .15s both; }
  .ytd-hero-visual { animation: ytdFadeUp .9s .18s cubic-bezier(.16,1,.3,1) both; }
  .ytd-hero-visual:hover { transform: translateY(-8px) rotate(1deg); transition: transform .45s cubic-bezier(.16,1,.3,1); }
  .ytd-form-panel { position: relative; overflow: hidden; }
  .ytd-form-panel::after { content: ""; position: absolute; inset: 0 auto 0 -30%; width: 22%; background: ${T.paper}33; transform: skewX(-20deg); pointer-events: none; }
  .ytd-form-panel:hover::after { animation: ytdShimmer 1.2s ease both; }

  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { scroll-behavior: auto !important; }
    .ytd-reveal, .ytd-stagger > *, .ytd-page-anim, .ytd-node-pulse circle:first-child, .ytd-node-line-draw, .ytd-app-shell, .ytd-cover img, .ytd-logo-btn, .ytd-hero-visual, .ytd-scroll-progress, .ytd-footer-cta::before, .ytd-admin-view, .ytd-admin-view-dashboard .ytd-stat-card, .ytd-admin-shell table tbody tr, .ytd-section-label { animation: none !important; transition: none !important; opacity: 1 !important; transform: none !important; stroke-dashoffset: 0 !important; }
  }
  @media (max-width: 860px) {
    .ytd-desktop-nav { display: none !important; }
    .ytd-mobile-toggle { display: block !important; }
    .ytd-hero-grid, .ytd-grid-4, .ytd-grid-3, .ytd-founder, .ytd-form-row, .ytd-dash-grid, .ytd-stat-grid, .ytd-home-hero { grid-template-columns: 1fr !important; }
    .ytd-hero-visual { order: -1; max-width: 560px; width: 100%; margin: 0 auto 12px; }
    .ytd-qa-row { grid-template-columns: 1fr !important; gap: 10px !important; }
    .ytd-admin-sidebar { display: none; }
    .ytd-admin-main { padding: 24px 20px 54px !important; }
    .ytd-admin-topbar { align-items: flex-start; flex-direction: column; }
    .ytd-dashboard-stat-grid, .ytd-dashboard-grid { grid-template-columns: 1fr !important; }
    .ytd-dashboard-hero { align-items: flex-start; flex-direction: column; padding: 24px; }
    .ytd-dashboard-bar-row { grid-template-columns: 1fr 80px 18px; gap: 8px; font-size: 9px; }
    .ytd-admin-welcome { align-items: flex-start; flex-direction: column; padding: 22px; }
    .ytd-media-grid { grid-template-columns: repeat(3, 1fr) !important; }
    .ytd-admin-books { grid-template-columns: 1fr !important; }
  }
`;
