import { useEffect, useRef, useState } from "react";
import { BOOKS, WORKS, ADMIN_COLLABS } from "../data.js";

function readRoute(works = WORKS, books = BOOKS) {
  const segments = window.location.hash.replace(/^#\/?/, "").split("/").filter(Boolean);
  const section = segments[0] || "home";
  const id = segments[1];

  if (section === "work" && id) return { page: "work-detail", activeWork: works.find(work => work.id === id) || null };
  if (section === "book" && id) return { page: "book-detail", activeBook: books.find(book => book.id === id) || null };
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

export default function useAppNavigation(works = WORKS, books = BOOKS) {
  const [route, setRoute] = useState(() => readRoute(works, books));
  const navigationDepth = useRef(0);
  const scrollPositions = useRef({});
  const pendingNavigation = useRef(null);
  const lastHandledHash = useRef("");
  const previousRoute = useRef("");

  useEffect(() => {
    if (!window.location.hash) window.history.replaceState({}, "", "#/home");
    const handleRouteChange = () => {
      const nextHash = window.location.hash || "#/home";
      if (lastHandledHash.current === nextHash) return;
      lastHandledHash.current = nextHash;
      const navigationType = pendingNavigation.current;
      pendingNavigation.current = null;
      setRoute(readRoute(works, books));
      navigationDepth.current = Math.max(0, navigationDepth.current - 1);
      const nextScroll = navigationType === "back" || !navigationType ? scrollPositions.current[nextHash] : 0;
      window.requestAnimationFrame(() => window.scrollTo({ top: nextScroll || 0, behavior: "smooth" }));
    };
    window.addEventListener("hashchange", handleRouteChange);
    window.addEventListener("popstate", handleRouteChange);
    return () => {
      window.removeEventListener("hashchange", handleRouteChange);
      window.removeEventListener("popstate", handleRouteChange);
    };
  }, [works, books]);

  function navigate(page, item) {
    const nextHash = routeFor(page, item);
    if (window.location.hash === nextHash) return;
    const currentHash = window.location.hash || "#/home";
    scrollPositions.current[currentHash] = window.scrollY;
    if (page === "work-detail" || page === "book-detail" || page === "collab-detail") previousRoute.current = currentHash;
    pendingNavigation.current = "forward";
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
    if (previousRoute.current) {
      pendingNavigation.current = "back";
      const targetHash = previousRoute.current;
      previousRoute.current = "";
      window.location.hash = targetHash.slice(1);
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
