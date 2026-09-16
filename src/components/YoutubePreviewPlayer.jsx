import { useEffect, useRef } from "react";

// The `end=` param on a bare iframe embed URL is unreliable — YouTube often
// ignores it. Actually enforcing a stop time requires the JS IFrame Player
// API, polling playback time and calling pauseVideo() ourselves.
let apiPromise = null;
function loadYoutubeApi() {
  if (window.YT?.Player) return Promise.resolve(window.YT);
  if (apiPromise) return apiPromise;
  apiPromise = new Promise(resolve => {
    const previous = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = () => { previous?.(); resolve(window.YT); };
    const tag = document.createElement("script");
    tag.src = "https://www.youtube.com/iframe_api";
    document.head.appendChild(tag);
  });
  return apiPromise;
}

/** A YouTube embed that actually pauses at `cutoffSeconds` (default 2 minutes). */
export default function YoutubePreviewPlayer({ videoId, title, cutoffSeconds = 120, onCutoff }) {
  const containerRef = useRef(null);
  const playerRef = useRef(null);
  const intervalRef = useRef(null);

  useEffect(() => {
    let cancelled = false;
    loadYoutubeApi().then(YT => {
      if (cancelled || !containerRef.current) return;
      playerRef.current = new YT.Player(containerRef.current, {
        videoId,
        playerVars: { rel: 0, playsinline: 1 },
        events: {
          onStateChange: event => {
            clearInterval(intervalRef.current);
            if (event.data === YT.PlayerState.PLAYING) {
              intervalRef.current = setInterval(() => {
                const t = playerRef.current?.getCurrentTime?.();
                if (t != null && t >= cutoffSeconds) {
                  playerRef.current.pauseVideo();
                  clearInterval(intervalRef.current);
                  onCutoff?.();
                }
              }, 400);
            }
          },
        },
      });
    });
    return () => {
      cancelled = true;
      clearInterval(intervalRef.current);
      try { playerRef.current?.destroy?.(); } catch { /* already gone */ }
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [videoId]);

  return <div ref={containerRef} title={title} style={{ width: "100%", height: "100%" }} />;
}
