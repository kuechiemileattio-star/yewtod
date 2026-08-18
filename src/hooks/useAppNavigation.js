import { useState } from "react";

export default function useAppNavigation() {
  const [page, setPage] = useState("home");
  const [activeWork, setActiveWork] = useState(null);

  function scrollToTop() {
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function openWork(work) {
    setActiveWork(work);
    setPage("work-detail");
    scrollToTop();
  }

  function go(nextPage) {
    setPage(nextPage);
    setActiveWork(null);
    scrollToTop();
  }

  return { page, activeWork, openWork, go };
}
