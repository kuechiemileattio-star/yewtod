import React, { useState } from "react";
import { Link } from "react-router-dom";
import { Star, Twitter, Linkedin, Facebook, Send } from "lucide-react";
import { T } from "../../theme.js";
import { fmtDate } from "../../lib/contentTypes.js";
import { PATHS, workPath } from "../../lib/paths.js";
import { useContentComments } from "../../hooks/useComments.js";
import { InteractiveSommaire } from "./shared.jsx";

/**
 * Very light "structure" reader for an article's plain-text `content` — no
 * markdown, just the same convention already used for PDF-extracted chapters
 * (a short standalone line acts as a heading for the paragraph(s) that
 * follow it). Lines starting with •/-/* become a bullet list. This can't
 * invent structure a given article's text doesn't actually have — it only
 * surfaces what's really there.
 */
function ContentBlocks({ text }) {
  if (!text) return null;
  const blocks = text.split(/\n\s*\n/).map(b => b.trim()).filter(Boolean);
  const isBullet = l => /^[•\-*]\s+/.test(l);
  return (
    <>
      {blocks.map((block, i) => {
        const lines = block.split("\n").map(l => l.trim()).filter(Boolean);
        const looksLikeHeading = lines.length > 1 && lines[0].length <= 70 && !/[.!?:]$/.test(lines[0]) && !isBullet(lines[0]);
        const heading = looksLikeHeading ? lines[0] : null;
        const bodyLines = looksLikeHeading ? lines.slice(1) : lines;
        const bulletLines = bodyLines.filter(isBullet);
        const proseLines = bodyLines.filter(l => !isBullet(l));
        return (
          <div key={i} className="ytd-afp-block">
            {heading && <h2>{heading}</h2>}
            {proseLines.length > 0 && <p>{proseLines.join(" ")}</p>}
            {bulletLines.length > 0 && <ul>{bulletLines.map((l, j) => <li key={j}>{l.replace(/^[•\-*]\s+/, "")}</li>)}</ul>}
          </div>
        );
      })}
    </>
  );
}

function CommentsSection({ table, contentId }) {
  const { comments, publishComment } = useContentComments(table, contentId);
  const [author, setAuthor] = useState("");
  const [text, setText] = useState("");
  const [error, setError] = useState("");

  async function submit(e) {
    e.preventDefault();
    if (!author.trim() || !text.trim()) return;
    try {
      await publishComment({ author: author.trim(), text: text.trim() });
      setAuthor(""); setText(""); setError("");
    } catch (err) {
      setError(err.message || "Erreur lors de l'envoi de l'avis.");
    }
  }

  return (
    <section className="ytd-afp-comments">
      <span className="ytd-afp-comments-heading">Avis ({comments.length})</span>
      <div className="ytd-afp-comments-list">
        {comments.length ? comments.map(c => (
          <article key={c.id} className="ytd-afp-comment">
            <div><strong>{c.author}</strong><small>{fmtDate(c.date)}</small></div>
            <p>{c.text}</p>
          </article>
        )) : <p className="ytd-afp-comments-empty">Aucun avis pour le moment. Soyez le premier à réagir.</p>}
      </div>
      <form className="ytd-afp-comment-form" onSubmit={submit}>
        <input required value={author} onChange={e => setAuthor(e.target.value)} placeholder="Votre nom" />
        <textarea required rows={3} value={text} onChange={e => setText(e.target.value)} placeholder="Votre avis sur cet article" />
        {error && <p className="ytd-afp-comment-error">{error}</p>}
        <button type="submit"><Send size={14} /> Publier l'avis</button>
      </form>
    </section>
  );
}

export default function ArticleFullPage({ work, otherArticles, shareTo }) {
  const [cited, setCited] = useState(false);
  const theme = (work.themes || "").split("\n").map(t => t.trim()).filter(Boolean)[0];
  const avatarUrl = work.authorProfile?.avatarUrl;

  // "À lire aussi" = la sélection manuelle (similarArticles, par titre) ;
  // à défaut, on complète avec d'autres articles récents.
  const similarTitles = (work.similarArticles || "").split("\n").map(t => t.trim()).filter(Boolean);
  const picked = similarTitles.map(title => otherArticles.find(a => a.title === title)).filter(Boolean);
  const related = (picked.length > 0 ? picked : otherArticles).slice(0, 4);

  async function handleCite() {
    const year = new Date(work.date || Date.now()).getFullYear();
    const text = `${work.author} (${year}). ${work.title}. ${work.category}, Yewtod SS.`;
    try {
      await navigator.clipboard.writeText(text);
      setCited(true);
      setTimeout(() => setCited(false), 2000);
    } catch { /* clipboard unavailable — nothing we can do silently */ }
  }

  return (
    <div className="ytd-afp">
      <nav className="ytd-afp-breadcrumb">
        <Link to={PATHS.home}>Accueil</Link> <span>›</span> <Link to={PATHS.works}>Publications</Link>
        {theme && <><span>›</span> <span className="is-current">{theme}</span></>}
      </nav>

      <header className="ytd-afp-hero" style={work.coverImage ? { backgroundImage: `linear-gradient(180deg, rgba(10,14,22,.55) 0%, rgba(10,14,22,.94) 100%), url(${work.coverImage})` } : undefined}>
        <div className="ytd-afp-hero-inner">
          <div className="ytd-afp-hero-badges">
            <span className="ytd-afp-badge">Article</span>
            <span className="ytd-afp-badge ytd-afp-badge-outline">Français</span>
          </div>
          <h1>{work.title}</h1>
          {work.excerpt && <p className="ytd-afp-hero-excerpt">{work.excerpt}</p>}
          <div className="ytd-afp-hero-byline">
            Par <strong>{work.author}</strong> · {fmtDate(work.date)}
            {theme && <span className="ytd-afp-badge ytd-afp-badge-theme">{theme}</span>}
          </div>
        </div>
      </header>

      <div className="ytd-afp-layout">
        <main className="ytd-afp-main">
          <div className="ytd-afp-actions">
            <button type="button" onClick={handleCite}><Star size={14} /> {cited ? "Copié !" : "Citer"}</button>
            <div className="ytd-afp-share">
              <span>Partager</span>
              <button type="button" onClick={() => shareTo("twitter")} aria-label="Partager sur X"><Twitter size={14} /></button>
              <button type="button" onClick={() => shareTo("linkedin")} aria-label="Partager sur LinkedIn"><Linkedin size={14} /></button>
              <button type="button" onClick={() => shareTo("facebook")} aria-label="Partager sur Facebook"><Facebook size={14} /></button>
            </div>
          </div>

          {work.tableOfContents?.length > 0
            ? <InteractiveSommaire items={work.tableOfContents} />
            : <ContentBlocks text={work.content || work.summary} />}

          <CommentsSection table={work.table} contentId={work.id} />
        </main>

        <aside className="ytd-afp-aside">
          <div className="ytd-afp-author-card">
            <div className="ytd-afp-author-avatar">
              {avatarUrl ? <img src={avatarUrl} alt="" /> : (work.author || "Y").charAt(0)}
            </div>
            <div>
              <span>Auteur</span>
              <strong>{work.author}</strong>
            </div>
          </div>

          <div className="ytd-afp-fiche">
            <span>Fiche publication</span>
            <dl>
              <div><dt>Date</dt><dd>{fmtDate(work.date)}</dd></div>
              <div><dt>Type</dt><dd>{work.category}</dd></div>
              {theme && <div><dt>Axe</dt><dd>{theme}</dd></div>}
              <div><dt>Langue</dt><dd>Français</dd></div>
            </dl>
          </div>

          {related.length > 0 && (
            <div className="ytd-afp-related">
              <span>À lire aussi</span>
              {related.map(r => (
                <Link key={r.id} to={workPath(r.routeSlug, r.slug)} className="ytd-afp-related-item">
                  <div className="ytd-afp-related-thumb" style={{ background: `${r.tone || T.green}22` }}>
                    {r.coverImage && <img src={r.coverImage} alt="" />}
                  </div>
                  <div>
                    <strong>{r.title}</strong>
                    <small>{fmtDate(r.date)}</small>
                  </div>
                </Link>
              ))}
            </div>
          )}
        </aside>
      </div>
    </div>
  );
}
