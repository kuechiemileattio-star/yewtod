import React from "react";
import { T } from "./theme.js";
import Admin from "./pages/Admin.jsx";
import PublicLayout from "./components/PublicLayout.jsx";
import { getPageComponent } from "./appRoutes.js";
import useAppNavigation from "./hooks/useAppNavigation.js";
import { GLOBAL_STYLES } from "./styles/globalStyles.js";

/* ============================================================
   APP RACINE
============================================================= */

export default function YewtodSS() {
  const { page, activeWork, openWork, go } = useAppNavigation();

  const PageComponent = getPageComponent(page);
  const pageProps = page === "home"
    ? { setPage: go, openWork }
    : page === "works"
      ? { openWork }
      : page === "work-detail"
        ? { work: activeWork, back: () => go("works"), openWork }
        : {};

  return (
    <div className="ytd-app-shell" style={{ background: `radial-gradient(circle at 8% 4%, ${T.green}18 0, transparent 24%), radial-gradient(circle at 92% 34%, ${T.red}16 0, transparent 28%), linear-gradient(118deg, ${T.paper} 0%, ${T.paperAlt} 48%, ${T.paper} 100%)`, minHeight: "100vh", color: T.ink }}>
      <style>{GLOBAL_STYLES}</style>

      {page === "admin" ? (
        <Admin exitAdmin={() => go("home")} />
      ) : (
        <PublicLayout page={page} setPage={go} transitionKey={page + (activeWork ? activeWork.id : "")}>
          {page === "work-detail" && !activeWork ? null : <PageComponent {...pageProps} />}
        </PublicLayout>
      )}
    </div>
  );
}
