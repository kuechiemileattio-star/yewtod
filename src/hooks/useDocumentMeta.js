import { useEffect } from "react";

function ensureMetaTag(name) {
  let tag = document.querySelector(`meta[name="${name}"]`);
  if (!tag) {
    tag = document.createElement("meta");
    tag.setAttribute("name", name);
    document.head.appendChild(tag);
  }
  return tag;
}

/** Sets document.title and the meta description for the current page. */
export default function useDocumentMeta(title, description) {
  useEffect(() => {
    const previousTitle = document.title;
    document.title = title ? `${title} · Yewtod SS` : "Yewtod SS";
    const descriptionTag = ensureMetaTag("description");
    const previousDescription = descriptionTag.getAttribute("content");
    if (description) descriptionTag.setAttribute("content", description);
    return () => {
      document.title = previousTitle;
      if (previousDescription !== null) descriptionTag.setAttribute("content", previousDescription);
    };
  }, [title, description]);
}
