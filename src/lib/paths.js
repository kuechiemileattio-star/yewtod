export const PATHS = {
  home: "/",
  works: "/works",
  meet: "/meet",
  books: "/books",
  collab: "/collaborations",
  dashboard: "/dashboard",
};

export function workPath(routeSlug, slug) {
  return `/works/${routeSlug}/${slug}`;
}

export function bookPath(slug) {
  return `/books/${slug}`;
}
