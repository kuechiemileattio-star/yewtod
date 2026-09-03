import React from "react";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import { T } from "./theme.js";
import { AuthProvider } from "./contexts/AuthContext.jsx";
import RequireAuth from "./components/RequireAuth.jsx";
import PublicLayout from "./components/PublicLayout.jsx";
import Home from "./pages/Home.jsx";
import Works from "./pages/Works.jsx";
import WorkDetail from "./pages/WorkDetail.jsx";
import Meet from "./pages/Meet.jsx";
import Books from "./pages/Books.jsx";
import BookDetail from "./pages/BookDetail.jsx";
import Collaborations from "./pages/Collaborations.jsx";
import Login from "./pages/Login.jsx";
import Onboarding from "./pages/Onboarding.jsx";
import DashboardShell from "./features/dashboard/DashboardShell.jsx";
import Overview from "./features/dashboard/Overview.jsx";
import BooksPanel from "./features/dashboard/BooksPanel.jsx";
import CollaborationsPanel from "./features/dashboard/CollaborationsPanel.jsx";
import SettingsPanel from "./features/dashboard/SettingsPanel.jsx";
import UsersRolesPanel from "./features/dashboard/UsersRolesPanel.jsx";
import { GLOBAL_STYLES } from "./styles/globalStyles.js";
import { PATHS } from "./lib/paths.js";

export default function YewtodSS() {
  return (
    <div className="ytd-app-shell" style={{ background: `radial-gradient(circle at 8% 4%, ${T.green}18 0, transparent 24%), radial-gradient(circle at 92% 34%, ${T.red}16 0, transparent 28%), linear-gradient(118deg, ${T.paper} 0%, ${T.paperAlt} 48%, ${T.paper} 100%)`, minHeight: "100vh", color: T.ink }}>
      <style>{GLOBAL_STYLES}</style>
      <BrowserRouter>
        <AuthProvider>
          <Routes>
            <Route element={<PublicLayout />}>
              <Route path={PATHS.home} element={<Home />} />
              <Route path={PATHS.works} element={<Works />} />
              <Route path="/works/:typeSlug/:slug" element={<WorkDetail />} />
              <Route path={PATHS.meet} element={<Meet />} />
              <Route path={PATHS.books} element={<Books />} />
              <Route path="/books/:slug" element={<BookDetail />} />
              <Route path={PATHS.collab} element={<Collaborations />} />
            </Route>

            <Route path="/login" element={<Login />} />
            <Route path="/onboarding" element={<Onboarding />} />

            <Route path={PATHS.dashboard} element={<RequireAuth><DashboardShell /></RequireAuth>}>
              <Route index element={<Overview />} />
              <Route path="books" element={<RequireAuth permission="manage_books"><BooksPanel /></RequireAuth>} />
              <Route path="collaborations" element={<RequireAuth permission="manage_collaborations"><CollaborationsPanel /></RequireAuth>} />
              <Route path="settings" element={<RequireAuth permission="manage_settings"><SettingsPanel /></RequireAuth>} />
              <Route path="users" element={<RequireAuth permission="manage_users"><UsersRolesPanel /></RequireAuth>} />
            </Route>

            <Route path="*" element={<Navigate to={PATHS.home} replace />} />
          </Routes>
        </AuthProvider>
      </BrowserRouter>
    </div>
  );
}
