import { useState } from "react";

export default function useAppNavigation() {
  const [page, setPage] = useState("home");
  const [activeWork, setActiveWork] = useState(null);
  const [activeBook, setActiveBook] = useState(null);
  const [activeCollab, setActiveCollab] = useState(null);
  const [history, setHistory] = useState([]);

  function scrollToTop() {
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function openWork(work) {
    setHistory(current => [...current, page]);
    setActiveWork(work);
    setActiveBook(null);
    setActiveCollab(null);
    setPage("work-detail");
    scrollToTop();
  }

  function go(nextPage) {
    setHistory(current => [...current, page]);
    setPage(nextPage);
    setActiveWork(null);
    setActiveBook(null);
    setActiveCollab(null);
    scrollToTop();
  }

  function openBook(book) {
    setHistory(current => [...current, page]);
    setActiveBook(book);
    setActiveWork(null);
    setActiveCollab(null);
    setPage("book-detail");
    scrollToTop();
  }

  function openCollab(collab) {
    setHistory(current => [...current, page]);
    setActiveCollab(collab);
    setActiveWork(null);
    setActiveBook(null);
    setPage("collab-detail");
    scrollToTop();
  }

  function back() {
    const previous = history[history.length - 1] || "home";
    setHistory(current => current.slice(0, -1));
    setPage(previous);
    setActiveWork(null);
    setActiveBook(null);
    setActiveCollab(null);
    scrollToTop();
  }

  return { page, activeWork, activeBook, activeCollab, openWork, openBook, openCollab, go, back };
}
