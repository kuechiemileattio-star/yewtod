import React, { createContext, useCallback, useContext, useEffect, useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

const AuthContext = createContext(null);

function normalizePermissions(rows) {
  return new Set((rows || []).map(row => (typeof row === "string" ? row : Object.values(row)[0])));
}

export function AuthProvider({ children }) {
  const [session, setSession] = useState(null);
  const [profile, setProfile] = useState(null);
  const [permissions, setPermissions] = useState(new Set());
  const [loading, setLoading] = useState(true);

  const loadProfile = useCallback(async userId => {
    if (!userId) {
      setProfile(null);
      setPermissions(new Set());
      return;
    }
    const [{ data: profileRow }, { data: permRows }] = await Promise.all([
      supabase.from("profiles").select("*, role:roles(id, name)").eq("id", userId).maybeSingle(),
      supabase.rpc("get_my_permissions"),
    ]);
    setProfile(profileRow || null);
    setPermissions(normalizePermissions(permRows));
  }, []);

  useEffect(() => {
    let active = true;
    supabase.auth.getSession().then(async ({ data }) => {
      if (!active) return;
      setSession(data.session);
      await loadProfile(data.session?.user?.id);
      if (active) setLoading(false);
    });
    const { data: subscription } = supabase.auth.onAuthStateChange(async (_event, nextSession) => {
      setSession(nextSession);
      await loadProfile(nextSession?.user?.id);
      setLoading(false);
    });
    return () => {
      active = false;
      subscription.subscription.unsubscribe();
    };
  }, [loadProfile]);

  async function signIn(email, password) {
    const { error } = await supabase.auth.signInWithPassword({ email, password });
    if (error) throw error;
  }

  async function signOut() {
    await supabase.auth.signOut();
  }

  function hasPermission(key) {
    return permissions.has(key);
  }

  const value = {
    session,
    profile,
    permissions,
    loading,
    hasPermission,
    signIn,
    signOut,
    reload: () => loadProfile(session?.user?.id),
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within AuthProvider");
  return ctx;
}
