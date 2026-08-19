import React, { useState } from "react";
import { T } from "./theme.js";
import Admin from "./pages/Admin.jsx";
import PublicLayout from "./components/PublicLayout.jsx";
import { getPageComponent } from "./appRoutes.js";
import useAppNavigation from "./hooks/useAppNavigation.js";
import { GLOBAL_STYLES } from "./styles/globalStyles.js";
import { BOOKS } from "./data.js";

/* ============================================================
   APP RACINE
============================================================= */

export default function YewtodSS() {
  const { page, activeWork, activeBook, activeCollab, openWork, openBook, openCollab, go, back } = useAppNavigation();
  const [visitorReviews, setVisitorReviews] = useState(() => Object.fromEntries(BOOKS.map(book => [book.id, book.reviews || []])));

  function publishBookReview(bookId, review) {
    setVisitorReviews(current => ({ ...current, [bookId]: [review, ...(current[bookId] || [])] }));
  }

  const PageComponent = getPageComponent(page);
  const pageProps = page === "home"
    ? { setPage: go, openWork }
    : page === "works"
      ? { openWork }
      : page === "books"
        ? { openBook }
      : page === "work-detail"
        ? { work: activeWork, back, openWork }
        : page === "book-detail"
          ? { book: activeBook, reviews: visitorReviews[activeBook?.id] || [], onPublishReview: review => publishBookReview(activeBook.id, review), back, openBook }
          : page === "collab-detail"
            ? { collab: activeCollab, back }
        : {};

  return (
    <div className="ytd-app-shell" style={{ background: `radial-gradient(circle at 8% 4%, ${T.green}18 0, transparent 24%), radial-gradient(circle at 92% 34%, ${T.red}16 0, transparent 28%), linear-gradient(118deg, ${T.paper} 0%, ${T.paperAlt} 48%, ${T.paper} 100%)`, minHeight: "100vh", color: T.ink }}>
      <style>{GLOBAL_STYLES}</style>

      {page === "admin" ? (
        <Admin visitorReviews={visitorReviews} exitAdmin={() => go("home")} />
      ) : (
        <PublicLayout page={page} setPage={go} transitionKey={page + (activeWork ? activeWork.id : "") + (activeBook ? activeBook.id : "") + (activeCollab ? activeCollab.id : "")}>
          {page === "work-detail" && !activeWork ? null : <PageComponent {...pageProps} />}
        </PublicLayout>
      )}
    </div>
  );
}
