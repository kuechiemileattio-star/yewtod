import React, { useState, useEffect, useRef } from "react";

/* Révélation au scroll : anime un bloc dès qu'il entre dans le viewport */
export function useInView(threshold = 0.15) {
  const ref = useRef(null);
  const [inView, setInView] = useState(false);
  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const obs = new IntersectionObserver((entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) { setInView(true); obs.unobserve(e.target); }
      });
    }, { threshold });
    obs.observe(el);
    return () => obs.disconnect();
  }, []);
  return [ref, inView];
}

export default function Reveal({ children, delay = 0, style, as = "div", ...rest }) {
  const [ref, inView] = useInView();
  const Tag = as;
  return (
    <Tag ref={ref} className={`ytd-reveal ${inView ? "ytd-reveal-in" : ""}`} style={{ transitionDelay: `${delay}ms`, ...style }} {...rest}>
      {children}
    </Tag>
  );
}
