import React from "react";
import { ExternalLink, Image as ImageIcon, Play } from "lucide-react";
import { T } from "../theme.js";

export function isVideoMedia(url = "") {
  return /\.(mp4|webm|ogg)(\?.*)?$/i.test(url) || /youtube\.com|youtu\.be|vimeo\.com/i.test(url);
}

export function extractMediaUrls(value = "") {
  return String(value).match(/https?:\/\/[^\s]+/g)?.map(url => url.replace(/[),.;]+$/, "")) || [];
}

function youtubeEmbedUrl(url) {
  try {
    const parsed = new URL(url);
    const videoId = parsed.hostname.includes("youtu.be") ? parsed.pathname.slice(1) : parsed.searchParams.get("v");
    return videoId ? `https://www.youtube.com/embed/${videoId}?start=0&end=120&rel=0` : url;
  } catch {
    return url;
  }
}

function youtubeWatchUrl(url) {
  try {
    const parsed = new URL(url);
    const videoId = parsed.hostname.includes("youtu.be") ? parsed.pathname.slice(1) : parsed.searchParams.get("v");
    return videoId ? `https://www.youtube.com/watch?v=${videoId}` : url;
  } catch {
    return url;
  }
}

export default function MediaDisplay({ type, url, imageUrl, videoUrl, alt = "", className = "", compact = false }) {
  const resolvedImage = imageUrl || (type !== "video" && !isVideoMedia(url) ? url : "");
  const resolvedVideo = videoUrl || (type === "video" || isVideoMedia(url) ? url : "");
  if (!resolvedImage && !resolvedVideo) return null;
  const renderVideo = media => {
    if (/youtube\.com|youtu\.be|vimeo\.com/i.test(media)) {
      const isYoutube = /youtube\.com|youtu\.be/i.test(media);
      return <div className="ytd-media-player"><div className="ytd-media-embed"><iframe src={isYoutube ? youtubeEmbedUrl(media) : media} title={alt || "Vidéo de la publication"} loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowFullScreen /></div>{isYoutube && <div className="ytd-media-player-footer"><span><Play size={13} /> Aperçu gratuit · 2 minutes</span><a href={youtubeWatchUrl(media)} target="_blank" rel="noreferrer">Continuer sur YouTube <ExternalLink size={13} /></a></div>}</div>;
    }
    return <div className="ytd-media-player"><video className="ytd-media-video" src={media} controls preload="metadata" onTimeUpdate={event => { if (event.currentTarget.currentTime >= 120) { event.currentTarget.pause(); event.currentTarget.currentTime = 120; } }} /><div className="ytd-media-player-footer"><span><Play size={13} /> Aperçu gratuit · 2 minutes</span></div></div>;
  };
  if (resolvedImage && resolvedVideo) return <div className={`ytd-media-gallery ${className}`}><div className="ytd-media-gallery-image"><img src={resolvedImage} alt={alt} loading="lazy" /><span className="ytd-media-badge"><ImageIcon size={12} /> Image</span></div>{renderVideo(resolvedVideo)}</div>;
  if (resolvedVideo) return <div className={className}>{renderVideo(resolvedVideo)}</div>;
  return <div className={`ytd-media-image-wrap ${className}`}><img src={resolvedImage} alt={alt} loading="lazy" /><span className="ytd-media-badge"><ImageIcon size={12} /> {compact ? "Image" : "Image de la publication"}</span></div>;
}

export function MediaPlaceholder({ type = "image" }) {
  return <div className="ytd-media-placeholder"><Play size={18} color={T.green} /> Média {type === "video" ? "vidéo" : "image"} à venir</div>;
}
