import Home from "./pages/Home.jsx";
import Works from "./pages/Works.jsx";
import WorkDetail from "./pages/WorkDetail.jsx";
import Meet from "./pages/Meet.jsx";
import Books from "./pages/Books.jsx";
import Collaborations from "./pages/Collaborations.jsx";

export const PUBLIC_ROUTES = {
  home: Home,
  works: Works,
  "work-detail": WorkDetail,
  meet: Meet,
  books: Books,
  collab: Collaborations,
};

export function getPageComponent(page) {
  return PUBLIC_ROUTES[page] || Home;
}
