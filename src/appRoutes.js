import Home from "./pages/Home.jsx";
import Works from "./pages/Works.jsx";
import WorkDetail from "./pages/WorkDetail.jsx";
import Meet from "./pages/Meet.jsx";
import Books from "./pages/Books.jsx";
import Collaborations from "./pages/Collaborations.jsx";
import BookDetail from "./pages/BookDetail.jsx";
import CollaborationDetail from "./pages/CollaborationDetail.jsx";

export const PUBLIC_ROUTES = {
  home: Home,
  works: Works,
  "work-detail": WorkDetail,
  meet: Meet,
  books: Books,
  collab: Collaborations,
  "book-detail": BookDetail,
  "collab-detail": CollaborationDetail,
};

export function getPageComponent(page) {
  return PUBLIC_ROUTES[page] || Home;
}
