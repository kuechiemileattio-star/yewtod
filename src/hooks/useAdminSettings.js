import { useCallback, useEffect, useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

export function useAdminSettings() {
  const [settings, setSettings] = useState({});
  const [socialLinks, setSocialLinks] = useState([]);
  const [loading, setLoading] = useState(true);

  const reload = useCallback(async () => {
    setLoading(true);
    const [{ data: settingsRows }, { data: linkRows }] = await Promise.all([
      supabase.from("site_settings").select("key, value"),
      supabase.from("social_links").select("*").order("display_order", { ascending: true }),
    ]);
    const map = {};
    (settingsRows || []).forEach(row => { map[row.key] = row.value; });
    setSettings(map);
    setSocialLinks(linkRows || []);
    setLoading(false);
  }, []);

  useEffect(() => { reload(); }, [reload]);

  async function saveSetting(key, value) {
    const { error } = await supabase.from("site_settings").upsert({ key, value }, { onConflict: "key" });
    if (error) throw error;
    await reload();
  }

  async function addSocialLink(platform, url) {
    const { error } = await supabase.from("social_links").insert({ platform, url, display_order: socialLinks.length });
    if (error) throw error;
    await reload();
  }

  async function removeSocialLink(id) {
    const { error } = await supabase.from("social_links").delete().eq("id", id);
    if (error) throw error;
    await reload();
  }

  return { settings, socialLinks, loading, saveSetting, addSocialLink, removeSocialLink, reload };
}
