import React from "react";
import logo from "../../Logo orange YetowdSS avec fond.png";

export default function BrandLogo({ dark = false, className = "" }) {
  let configuredLogo = "";
  try {
    const settings = JSON.parse(window.localStorage.getItem("yewtod-settings") || "{}");
    configuredLogo = settings.logoUrl || "";
  } catch {
    configuredLogo = "";
  }
  return (
    <span className={`ytd-brand-logo-frame ${dark ? "ytd-brand-logo-dark" : ""} ${className}`}>
      <img src={configuredLogo || logo} alt="Yewtod SS - Social Science" className="ytd-brand-logo" />
    </span>
  );
}
