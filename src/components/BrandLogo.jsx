import React from "react";
import logo from "../../Logo orange YetowdSS avec fond.png";

export default function BrandLogo({ dark = false, className = "" }) {
  return (
    <span className={`ytd-brand-logo-frame ${dark ? "ytd-brand-logo-dark" : ""} ${className}`}>
      <img src={logo} alt="Yewtod SS - Social Science" className="ytd-brand-logo" />
    </span>
  );
}
