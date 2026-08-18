import React from "react";
import NavBar from "./NavBar.jsx";
import Footer from "./Footer.jsx";

export default function PublicLayout({ page, setPage, transitionKey, children }) {
  return (
    <>
      <NavBar page={page} setPage={setPage} />
      <main key={transitionKey || page} className="ytd-page-anim">
        {children}
      </main>
      <Footer setPage={setPage} />
    </>
  );
}
