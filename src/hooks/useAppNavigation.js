import { useEffect, useRef, useState } from "react";
import { BOOKS, WORKS, ADMIN_COLLABS } from "../data.js";

function readRoute() {
  const segments = window.location.hash.replace(/^#\/?/, "").split("/").filter(Boolean);
  const section = segments[0] || "home";
  const id = segments[1];

  if (section === "work" && id) return { page: "work-detail", activeWork: WORKS.find(work => work.id === id) || null };
  if (section === "book" && id) return { page: "book-detail", activeBook: BOOKS.find(book => book.id === id) || null };
  if (section === "collaboration" && id) return { page: "collab-detail", activeCollab: ADMIN_COLLABS.find(collab => collab.id === id) || null };
  if (["home", "works", "meet", "books", "collab", "admin"].includes(section)) return { page: section };
  return { page: "home" };
}

function routeFor(page, item) {
  if (page === "work-detail") return `#/work/${item.id}`;
  if (page === "book-detail") return `#/book/${item.id}`;
  if (page === "collab-detail") return `#/collaboration/${item.id}`;
  return `#/${page}`;
}

export default function useAppNavigation() {
  const [route, setRoute] = useState(readRoute);
  const navigationDepth = useRef(0);

  useEffect(() => {
    if (!window.location.hash) window.history.replaceState({}, "", "#/home");
    const handleRouteChange = () => {
      setRoute(readRoute());
      navigationDepth.current = Math.max(0, navigationDepth.current - 1);
      window.scrollTo({ top: 0, behavior: "smooth" });
    };
    window.addEventListener("hashchange", handleRouteChange);
    window.addEventListener("popstate", handleRouteChange);
    return () => {
      window.removeEventListener("hashchange", handleRouteChange);
      window.removeEventListener("popstate", handleRouteChange);
    };
  }, []);

  function navigate(page, item) {
    const nextHash = routeFor(page, item);
    if (window.location.hash === nextHash) return;
    navigationDepth.current += 1;
    window.location.hash = nextHash.slice(1);
  }

  function go(nextPage) {
    navigate(nextPage);
  }

  function openWork(work) {
    navigate("work-detail", work);
  }

  function openBook(book) {
    navigate("book-detail", book);
  }

  function openCollab(collab) {
    navigate("collab-detail", collab);
  }

  function back() {
    if (navigationDepth.current > 0) {
      window.history.back();
      return;
    }
    navigate("home");
  }

  return {
    page: route.page,
    activeWork: route.activeWork || null,
    activeBook: route.activeBook || null,
    activeCollab: route.activeCollab || null,
    openWork,
    openBook,
    openCollab,
    go,
    back,
  };
}
