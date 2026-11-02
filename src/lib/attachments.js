// Pièces jointes du formulaire de collaboration (bucket PRIVÉ depuis la
// migration 022). Une ligne de `collaborations.attachments` peut contenir :
//   - un chemin "<uuid>.<ext>"                          (nouvelles demandes)
//   - l'ancienne URL publique de NOTRE bucket            (demandes antérieures)
//   - n'importe quoi d'autre : la colonne est remplie par un formulaire PUBLIC,
//     donc potentiellement par un attaquant via l'API (ex. "javascript:…").
// Seuls les deux premiers cas deviennent des liens (URL signée) ; le reste est
// affiché comme simple texte, jamais comme lien.

export const COLLAB_BUCKET = "collaboration-attachments";

const NAME_RE = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.(pdf|doc|docx|jpg|jpeg|png|webp)$/i;
// Anciennes pièces jointes (avant 022) : toute extension simple était acceptée.
const LEGACY_NAME_RE = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}(\.[a-z0-9]{1,10})?$/i;

/** Renvoie le chemin dans le bucket, ou null si la valeur n'est pas une pièce jointe valide. */
export function collabAttachmentPath(value, supabaseUrl) {
  if (typeof value !== "string" || !value || value.length > 2048) return null;
  if (NAME_RE.test(value)) return value;
  try {
    const url = new URL(value);
    const base = new URL(supabaseUrl);
    const prefix = `/storage/v1/object/public/${COLLAB_BUCKET}/`;
    if (url.origin !== base.origin || !url.pathname.startsWith(prefix)) return null;
    const name = decodeURIComponent(url.pathname.slice(prefix.length));
    return LEGACY_NAME_RE.test(name) ? name : null;
  } catch {
    return null;
  }
}
