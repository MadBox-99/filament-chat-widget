/**
 * Filament Chat Widget — embeddable browser widget.
 *
 * Usage:
 *   <script src="https://your-app.test/chat/embed.js"
 *           data-team="{tenant_slug}" async></script>
 *
 * Optional attributes:
 *   data-prefix  route prefix when `routes.prefix` is not "chat"
 *   data-locale  label language (defaults to <html lang>, then the browser)
 *
 * Fetches the widget config, renders a floating button + chat panel, keeps
 * the conversation uuid in localStorage and polls for new messages: quickly
 * while the panel is open, slowly in the background to show an unread badge.
 */
(function () {
    "use strict";

    if (typeof window === "undefined" || typeof document === "undefined") {
        return;
    }

    var script = document.currentScript;
    if (!script || !script.getAttribute("data-team")) {
        var scripts = document.querySelectorAll("script[data-team]");
        script = scripts.length ? scripts[scripts.length - 1] : null;
    }
    if (!script) {
        return;
    }

    var slug = script.getAttribute("data-team");
    if (!slug) {
        return;
    }

    var baseUrl;
    try {
        baseUrl = new URL(script.src).origin;
    } catch (error) {
        return;
    }

    var mountedAttr = "data-fcw-chat-mounted";
    if (document.documentElement.getAttribute(mountedAttr) === "1") {
        return;
    }
    document.documentElement.setAttribute(mountedAttr, "1");

    var routePrefix = (script.getAttribute("data-prefix") || "chat").replace(/^\/+|\/+$/g, "");
    var locale = script.getAttribute("data-locale") || document.documentElement.lang || navigator.language || "";
    var storageKey = "fcw-chat-" + slug;
    var seenKey = storageKey + "-seen";
    var OPEN_POLL_MS = 4000;
    var BACKGROUND_POLL_MS = 20000;
    var FCW_FONT = "-apple-system,BlinkMacSystemFont,'Segoe UI',Inter,Roboto,sans-serif";

    var config = null;
    var labels = {};
    var uuid = storageGet(storageKey);
    var lastId = 0;
    var lastSeenId = parseInt(storageGet(seenKey) || "0", 10) || 0;
    // Conversations started by an older widget version have no "seen" marker;
    // treat their existing history as read instead of flashing a huge badge.
    var trackUnread = !uuid || storageGet(seenKey) !== null;
    var renderedIds = {};
    var unread = 0;
    var pollTimer = null;
    var polling = false;
    var sending = false;
    var panelOpen = false;
    var historyLoaded = false;

    function storageGet(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    function storageSet(key, value) {
        try {
            if (value === null) {
                window.localStorage.removeItem(key);
            } else {
                window.localStorage.setItem(key, value);
            }
        } catch (e) {}
    }

    function label(key, fallback) {
        return labels[key] || fallback;
    }

    function url(path) {
        return baseUrl + "/" + routePrefix + path;
    }

    function request(path, options) {
        options = options || {};
        var headers = { Accept: "application/json" };
        if (options.body) {
            headers["Content-Type"] = "application/json";
        }
        return fetch(url(path), {
            method: options.method || "GET",
            headers: headers,
            body: options.body ? JSON.stringify(options.body) : undefined,
        }).then(function (r) {
            if (!r.ok) {
                var error = new Error("HTTP " + r.status);
                error.status = r.status;
                throw error;
            }
            return r.json();
        });
    }

    var style = document.createElement("style");
    style.textContent =
        "@keyframes fcw-pop{0%{transform:translateY(16px) scale(.96);opacity:0}100%{transform:translateY(0) scale(1);opacity:1}}" +
        "@keyframes fcw-fade{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}" +
        "@keyframes fcw-pulse{0%,100%{box-shadow:0 12px 32px -8px rgba(0,0,0,.25),0 0 0 0 var(--fcw-color,#6366f1)}50%{box-shadow:0 12px 32px -8px rgba(0,0,0,.25),0 0 0 10px transparent}}" +
        ".fcw-chat-btn,.fcw-chat-panel{all:initial;font-family:" + FCW_FONT + ";-webkit-font-smoothing:antialiased;color-scheme:light}" +
        ".fcw-chat-btn,.fcw-chat-btn *,.fcw-chat-panel *{box-sizing:border-box;font-family:inherit;text-transform:none;letter-spacing:normal;font-variant:normal;font-style:normal;text-decoration:none;text-indent:0;text-shadow:none;margin:0;padding:0;border:0;line-height:normal;color:inherit}" +
        ".fcw-chat-btn{position:fixed;width:60px;height:60px;border-radius:50%;cursor:pointer;box-shadow:0 12px 32px -8px rgba(0,0,0,.28),0 2px 6px rgba(0,0,0,.12);z-index:2147483646;display:flex;align-items:center;justify-content:center;color:#fff;background:var(--fcw-color,#6366f1);transition:transform .2s ease,box-shadow .2s ease;animation:fcw-pulse 3s ease-in-out infinite}" +
        ".fcw-chat-btn:hover{transform:translateY(-2px) scale(1.04);box-shadow:0 16px 40px -8px rgba(0,0,0,.35),0 4px 12px rgba(0,0,0,.14)}" +
        ".fcw-chat-btn:active{transform:scale(.96)}" +
        ".fcw-chat-btn svg{width:26px;height:26px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}" +
        ".fcw-chat-panel{position:fixed;width:380px;max-width:calc(100vw - 24px);height:560px;max-height:calc(100vh - 120px);background:#fff;color:#1a1b1f;border-radius:20px;box-shadow:0 24px 56px -12px rgba(0,0,0,.28),0 2px 8px rgba(0,0,0,.08);z-index:2147483647;display:none;flex-direction:column;overflow:hidden;font-size:14px}" +
        ".fcw-chat-panel.open{display:flex;animation:fcw-pop .22s cubic-bezier(.2,.8,.2,1)}" +
        ".fcw-chat-header{padding:16px 18px;color:#fff;display:flex;align-items:center;justify-content:space-between;font-weight:600;font-size:15px;background:var(--fcw-color,#6366f1);flex-shrink:0}" +
        ".fcw-chat-close{background:transparent;color:#fff;cursor:pointer;font-size:20px;line-height:1;padding:6px;border-radius:8px;transition:background .15s ease;opacity:.85}" +
        ".fcw-chat-close:hover{background:rgba(255,255,255,.18);opacity:1}" +
        ".fcw-chat-body{flex:1;overflow-y:auto;padding:16px 14px;background:#f7f8fa;display:flex;flex-direction:column;gap:6px;scroll-behavior:smooth}" +
        ".fcw-chat-body::-webkit-scrollbar{width:6px}" +
        ".fcw-chat-body::-webkit-scrollbar-thumb{background:rgba(0,0,0,.15);border-radius:3px}" +
        ".fcw-chat-body::-webkit-scrollbar-thumb:hover{background:rgba(0,0,0,.25)}" +
        ".fcw-chat-msg{max-width:78%;padding:9px 13px;font-size:14px;line-height:1.45;word-wrap:break-word;animation:fcw-fade .18s ease-out;white-space:pre-wrap}" +
        ".fcw-chat-msg.visitor{align-self:flex-end;color:#fff;background:var(--fcw-color,#6366f1);border-radius:16px 16px 4px 16px;box-shadow:0 1px 2px rgba(0,0,0,.08)}" +
        ".fcw-chat-msg.agent{align-self:flex-start;background:#fff;color:#1a1b1f;border-radius:16px 16px 16px 4px;box-shadow:0 1px 2px rgba(0,0,0,.06),0 0 0 1px rgba(0,0,0,.04)}" +
        ".fcw-chat-msg.system{align-self:center;max-width:90%;background:rgba(0,0,0,.04);color:#4b5563;font-size:12.5px;text-align:center;padding:7px 14px;border-radius:999px}" +
        ".fcw-chat-input{border-top:1px solid rgba(0,0,0,.06);padding:10px 10px 12px;display:flex;gap:8px;background:#fff;align-items:flex-end;flex-shrink:0}" +
        ".fcw-chat-input textarea{flex:1;resize:none;border:1px solid rgba(0,0,0,.1);border-radius:14px;padding:10px 14px;font-family:inherit;font-size:14px;line-height:1.4;max-height:100px;background:#f7f8fa;color:#1a1b1f;outline:none;transition:border-color .15s ease,box-shadow .15s ease,background .15s ease;-webkit-appearance:none;appearance:none}" +
        ".fcw-chat-input textarea::placeholder{color:#9ca3af;text-transform:none;letter-spacing:normal;font-weight:400}" +
        ".fcw-chat-input textarea:focus{border-color:var(--fcw-color,#6366f1);background:#fff;box-shadow:0 0 0 3px rgba(0,0,0,.06)}" +
        ".fcw-chat-send{border-radius:12px;padding:0 16px;height:40px;color:#fff;cursor:pointer;font-weight:600;font-size:14px;background:var(--fcw-color,#6366f1);transition:transform .1s ease,filter .15s ease;white-space:nowrap;-webkit-appearance:none;appearance:none}" +
        ".fcw-chat-send:hover{filter:brightness(1.08)}" +
        ".fcw-chat-send:active{transform:scale(.97)}" +
        ".fcw-chat-send:disabled{opacity:.5;cursor:not-allowed;filter:none}" +
        ".fcw-chat-badge{position:absolute;top:-4px;right:-4px;min-width:20px;height:20px;padding:0 6px;border-radius:999px;background:#ef4444;color:#fff;font-size:11px;font-weight:700;line-height:20px;text-align:center;box-shadow:0 0 0 2px #fff;display:none}" +
        ".fcw-chat-badge.visible{display:block}" +
        ".fcw-chat-notice{align-self:stretch;background:#fff7ed;color:#9a3412;font-size:12.5px;line-height:1.4;padding:8px 12px;border-radius:10px;box-shadow:0 0 0 1px rgba(154,52,18,.12)}" +
        ".fcw-chat-error{display:none;padding:6px 12px 0;background:#fff;color:#b91c1c;font-size:12px;line-height:1.4}" +
        ".fcw-chat-error.visible{display:block}" +
        ".fcw-chat-panel .fcw-chat-link{color:inherit;text-decoration:underline;text-underline-offset:2px;cursor:pointer;word-break:break-word}" +
        ".fcw-chat-panel .fcw-chat-video{display:block;margin-top:8px;width:250px;max-width:100%;white-space:normal}" +
        ".fcw-chat-panel .fcw-chat-video-play{position:relative;display:block;width:100%;aspect-ratio:16/9;border-radius:10px;overflow:hidden;background:#111827;cursor:pointer}" +
        ".fcw-chat-panel .fcw-chat-video-play img{display:block;width:100%;height:100%;object-fit:cover}" +
        ".fcw-chat-panel .fcw-chat-video-icon{position:absolute;left:50%;top:50%;width:54px;height:38px;margin:-19px 0 0 -27px;border-radius:10px;background:rgba(0,0,0,.72);transition:background .15s ease}" +
        ".fcw-chat-panel .fcw-chat-video-icon:after{content:'';position:absolute;left:22px;top:11px;border-style:solid;border-width:8px 0 8px 13px;border-color:transparent transparent transparent #fff}" +
        ".fcw-chat-panel .fcw-chat-video-play:hover .fcw-chat-video-icon,.fcw-chat-panel .fcw-chat-video-play:focus-visible .fcw-chat-video-icon{background:var(--fcw-color,#6366f1)}" +
        ".fcw-chat-panel .fcw-chat-video iframe{display:block;width:100%;aspect-ratio:16/9;border:0;border-radius:10px;background:#000}" +
        ".fcw-chat-panel .fcw-chat-video-open{display:inline-block;margin-top:4px;font-size:12px;opacity:.85;text-decoration:underline;color:inherit;cursor:pointer}" +
        ".fcw-chat-panel .fcw-chat-video.blocked .fcw-chat-video-play,.fcw-chat-panel .fcw-chat-video.blocked iframe{display:none}" +
        "@media(prefers-reduced-motion:reduce){.fcw-chat-btn,.fcw-chat-panel.open,.fcw-chat-msg{animation:none}}" +
        "@media(max-width:480px){.fcw-chat-panel{width:calc(100vw - 16px);height:calc(100vh - 96px);border-radius:16px}.fcw-chat-btn{width:56px;height:56px}}";
    document.head.appendChild(style);

    var button = document.createElement("button");
    button.className = "fcw-chat-btn";
    button.type = "button";
    button.setAttribute("aria-expanded", "false");
    button.innerHTML =
        '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>' +
        '<span class="fcw-chat-badge" aria-hidden="true"></span>';
    var badge = button.querySelector(".fcw-chat-badge");

    var panel = document.createElement("div");
    panel.className = "fcw-chat-panel";
    panel.setAttribute("role", "dialog");

    var body, textarea, sendBtn, errorBox;

    function isValidColor(value) {
        if (!value || typeof value !== "string") {
            return false;
        }
        var test = document.createElement("div").style;
        test.color = "";
        test.color = value;
        return test.color !== "";
    }

    function applyAppearance() {
        var color = isValidColor(config.color) ? config.color : "#6366f1";
        button.style.setProperty("--fcw-color", color);
        panel.style.setProperty("--fcw-color", color);

        var left = config.position === "bottom-left";
        button.style.bottom = "20px";
        panel.style.bottom = "90px";
        button.style.left = panel.style.left = left ? "20px" : "auto";
        button.style.right = panel.style.right = left ? "auto" : "20px";

        if (config.custom_css) {
            var custom = document.createElement("style");
            custom.setAttribute("data-fcw-custom", "1");
            custom.textContent = config.custom_css;
            document.head.appendChild(custom);
        }
    }

    function buildPanel() {
        panel.setAttribute("aria-label", config.title || "Chat");
        button.setAttribute("aria-label", label("open_chat", "Open chat"));
        panel.innerHTML =
            '<div class="fcw-chat-header"><span></span>' +
            '<button type="button" class="fcw-chat-close">&times;</button></div>' +
            '<div class="fcw-chat-body" aria-live="polite"></div>' +
            '<div class="fcw-chat-error" role="alert"></div>' +
            '<div class="fcw-chat-input"><textarea rows="2"></textarea>' +
            '<button type="button" class="fcw-chat-send"></button></div>';

        panel.querySelector(".fcw-chat-header span").textContent = config.title || "Chat";
        var closeBtn = panel.querySelector(".fcw-chat-close");
        closeBtn.setAttribute("aria-label", label("close", "Close"));
        closeBtn.addEventListener("click", closePanel);

        body = panel.querySelector(".fcw-chat-body");
        errorBox = panel.querySelector(".fcw-chat-error");
        textarea = panel.querySelector("textarea");
        textarea.placeholder = label("placeholder", "Type a message...");
        textarea.setAttribute("aria-label", label("placeholder", "Type a message..."));
        sendBtn = panel.querySelector(".fcw-chat-send");
        sendBtn.textContent = label("send", "Send");

        sendBtn.addEventListener("click", sendMessage);
        textarea.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey && !e.isComposing) {
                e.preventDefault();
                sendMessage();
            }
        });
        panel.addEventListener("keydown", function (e) {
            if (e.key === "Escape") {
                closePanel();
                button.focus();
            }
        });

        if (config.welcome_message) {
            addBubble("system", config.welcome_message);
        }
        if (config.is_online === false) {
            var notice = document.createElement("div");
            notice.className = "fcw-chat-notice";
            notice.textContent = config.offline_message || label("offline", "We're offline right now, but leave a message.");
            body.appendChild(notice);
        }
    }

    // Web and e-mail addresses in a message; mirrors Support\MessageFormatter.
    var LINK_PATTERN = /(https?:\/\/[^\s<>"]+[^\s<>".,;:!?)\]'»”])|(www\.[^\s<>"]+[^\s<>".,;:!?)\]'»”])|([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})/g;

    function youtubeVideo(id, href) {
        return {
            url: href,
            embed: "https://www.youtube-nocookie.com/embed/" + id,
            thumb: "https://i.ytimg.com/vi/" + id + "/hqdefault.jpg",
        };
    }

    function vimeoVideo(id, hash, href) {
        return {
            url: href,
            embed: "https://player.vimeo.com/video/" + id + (hash ? "?h=" + encodeURIComponent(hash) : ""),
            thumb: null,
        };
    }

    function videoOf(href) {
        var parsed;
        try {
            parsed = new URL(href);
        } catch (e) {
            return null;
        }
        var host = parsed.hostname.toLowerCase().replace(/^(www\.|m\.)/, "");
        var path = parsed.pathname;
        var match;

        if (host === "youtu.be" && (match = /^\/([A-Za-z0-9_-]{11})/.exec(path))) {
            return youtubeVideo(match[1], href);
        }
        if (host === "youtube.com" || host === "youtube-nocookie.com") {
            var v = parsed.searchParams.get("v");
            if (path === "/watch" && v && /^[A-Za-z0-9_-]{11}$/.test(v)) {
                return youtubeVideo(v, href);
            }
            if ((match = /^\/(?:shorts|embed|live)\/([A-Za-z0-9_-]{11})/.exec(path))) {
                return youtubeVideo(match[1], href);
            }
        }
        if (host === "vimeo.com" && (match = /^\/(?:channels\/[^\/]+\/)?(\d+)(?:\/([0-9a-f]+))?/.exec(path))) {
            return vimeoVideo(match[1], match[2], href);
        }
        if (host === "player.vimeo.com" && (match = /^\/video\/(\d+)/.exec(path))) {
            return vimeoVideo(match[1], parsed.searchParams.get("h"), href);
        }
        return null;
    }

    /**
     * Append the text to the container as text nodes and links (never as HTML)
     * and return the distinct YouTube / Vimeo videos it links to.
     */
    function renderText(container, text) {
        var value = text === null || text === undefined ? "" : String(text);
        var videos = [];
        var seen = {};
        var last = 0;
        var match;

        LINK_PATTERN.lastIndex = 0;
        while ((match = LINK_PATTERN.exec(value)) !== null) {
            if (match.index > last) {
                container.appendChild(document.createTextNode(value.slice(last, match.index)));
            }
            var href = match[1] ? match[1] : match[2] ? "https://" + match[2] : "mailto:" + match[3];
            var link = document.createElement("a");
            link.className = "fcw-chat-link";
            link.href = href;
            link.textContent = match[0];
            if (!match[3]) {
                link.target = "_blank";
                link.rel = "noopener noreferrer nofollow";
                var video = videoOf(href);
                if (video && !seen[video.embed]) {
                    seen[video.embed] = true;
                    videos.push(video);
                }
            }
            container.appendChild(link);
            last = match.index + match[0].length;
        }
        if (last < value.length) {
            container.appendChild(document.createTextNode(value.slice(last)));
        }
        return videos;
    }

    /**
     * A thumbnail with a play button; the player is only loaded (and starts
     * playing) when the visitor clicks it.
     */
    function videoCard(video) {
        var card = document.createElement("div");
        card.className = "fcw-chat-video";

        var play = document.createElement("button");
        play.type = "button";
        play.className = "fcw-chat-video-play";
        play.setAttribute("aria-label", label("play_video", "Play video"));
        if (video.thumb) {
            var img = document.createElement("img");
            img.alt = "";
            img.loading = "lazy";
            img.addEventListener("error", function () {
                img.parentNode && img.parentNode.removeChild(img);
            });
            img.src = video.thumb;
            play.appendChild(img);
        }
        var icon = document.createElement("span");
        icon.className = "fcw-chat-video-icon";
        icon.setAttribute("aria-hidden", "true");
        play.appendChild(icon);

        play.addEventListener("click", function () {
            var frame = document.createElement("iframe");
            frame.src = video.embed + (video.embed.indexOf("?") === -1 ? "?" : "&") + "autoplay=1";
            frame.title = label("play_video", "Play video");
            frame.setAttribute("allow", "autoplay; fullscreen; picture-in-picture; encrypted-media");
            frame.setAttribute("allowfullscreen", "");
            frame.setAttribute("referrerpolicy", "strict-origin-when-cross-origin");
            card.replaceChild(frame, play);
        });
        card.appendChild(play);

        var open = document.createElement("a");
        open.className = "fcw-chat-video-open";
        open.href = video.url;
        open.target = "_blank";
        open.rel = "noopener noreferrer nofollow";
        open.textContent = label("open_video", "Open video") + " ↗";
        card.appendChild(open);

        return card;
    }

    // A host page whose Content-Security-Policy forbids the player keeps only the link.
    document.addEventListener("securitypolicyviolation", function (event) {
        var blocked = String(event.blockedURI || "");
        if (!/youtube-nocookie\.com|player\.vimeo\.com/.test(blocked)) {
            return;
        }
        var frames = panel.querySelectorAll(".fcw-chat-video iframe");
        for (var i = 0; i < frames.length; i++) {
            if (frames[i].src.indexOf(blocked) === 0 || blocked.indexOf(new URL(frames[i].src).origin) === 0) {
                frames[i].parentNode.classList.add("blocked");
            }
        }
    });

    function addBubble(type, text, id) {
        var div = document.createElement("div");
        div.className = "fcw-chat-msg " + type;
        var videos = renderText(div, text);
        for (var v = 0; v < videos.length; v++) {
            div.appendChild(videoCard(videos[v]));
        }
        if (id) {
            div.setAttribute("data-id", String(id));
            // Keep server order even when history arrives after a newer message.
            var nodes = body.querySelectorAll(".fcw-chat-msg[data-id]");
            for (var i = 0; i < nodes.length; i++) {
                if (parseInt(nodes[i].getAttribute("data-id"), 10) > id) {
                    body.insertBefore(div, nodes[i]);
                    return;
                }
            }
        }
        body.appendChild(div);
    }

    function scrollToBottom() {
        body.scrollTop = body.scrollHeight;
    }

    function appendMessages(messages) {
        if (!messages || !messages.length) {
            return;
        }
        var added = false;
        for (var i = 0; i < messages.length; i++) {
            var m = messages[i];
            if (!m || renderedIds[m.id]) {
                continue;
            }
            renderedIds[m.id] = true;
            if (m.id > lastId) {
                lastId = m.id;
            }
            var type = m.sender_type === "visitor" || m.sender_type === "system" ? m.sender_type : "agent";
            addBubble(type, m.message, m.id);
            if (trackUnread && type !== "visitor" && m.id > lastSeenId && !panelOpen) {
                unread++;
            }
            added = true;
        }
        if (added) {
            if (panelOpen) {
                markSeen();
            }
            updateBadge();
            scrollToBottom();
        }
    }

    function markSeen() {
        unread = 0;
        if (lastId > lastSeenId) {
            lastSeenId = lastId;
            storageSet(seenKey, String(lastSeenId));
        }
        updateBadge();
    }

    function updateBadge() {
        if (unread > 0) {
            badge.textContent = unread > 9 ? "9+" : String(unread);
            badge.classList.add("visible");
            button.setAttribute("aria-label", label("unread", "New message") + " (" + unread + ")");
        } else {
            badge.classList.remove("visible");
            button.setAttribute("aria-label", label("open_chat", "Open chat"));
        }
    }

    function showError(message) {
        errorBox.textContent = message || "";
        errorBox.classList.toggle("visible", !!message);
    }

    function forgetConversation() {
        uuid = null;
        storageSet(storageKey, null);
        storageSet(seenKey, null);
    }

    function ensureConversation() {
        if (uuid) {
            return Promise.resolve(uuid);
        }
        return request("/conversations", { method: "POST", body: { slug: slug } }).then(function (data) {
            uuid = data.uuid;
            storageSet(storageKey, uuid);
            appendMessages(data.messages || []);
            return uuid;
        });
    }

    function sendMessage() {
        var text = (textarea.value || "").trim();
        if (!text || sending) {
            return;
        }
        sending = true;
        sendBtn.disabled = true;
        showError("");

        ensureConversation()
            .then(function (id) {
                return request("/conversations/" + encodeURIComponent(id) + "/messages", {
                    method: "POST",
                    body: { message: text },
                });
            })
            .then(function (data) {
                if (textarea.value.trim() === text) {
                    textarea.value = "";
                }
                appendMessages(data && data.message ? [data.message] : []);
                schedulePoll();
            })
            .catch(function (error) {
                if (error && error.status === 404) {
                    forgetConversation();
                }
                showError(label("send_failed", "Your message could not be sent. Please try again."));
            })
            .then(function () {
                sending = false;
                sendBtn.disabled = false;
                textarea.focus();
            });
    }

    function poll() {
        if (!config || !uuid || polling) {
            return Promise.resolve();
        }
        polling = true;
        var since = historyLoaded ? lastId : 0;
        return request("/conversations/" + encodeURIComponent(uuid) + "/messages?since=" + since)
            .then(function (data) {
                historyLoaded = true;
                appendMessages((data && data.messages) || []);
                if (!trackUnread) {
                    trackUnread = true;
                    markSeen();
                }
            })
            .catch(function (error) {
                if (error && error.status === 404) {
                    forgetConversation();
                }
            })
            .then(function () {
                polling = false;
            });
    }

    function schedulePoll() {
        if (pollTimer) {
            window.clearTimeout(pollTimer);
            pollTimer = null;
        }
        if (!config || !uuid || document.hidden) {
            return;
        }
        pollTimer = window.setTimeout(function () {
            poll().then(schedulePoll);
        }, panelOpen ? OPEN_POLL_MS : BACKGROUND_POLL_MS);
    }

    function openPanel() {
        panelOpen = true;
        panel.classList.add("open");
        button.setAttribute("aria-expanded", "true");
        markSeen();
        scrollToBottom();
        textarea.focus();
        poll().then(schedulePoll);
    }

    function closePanel() {
        panelOpen = false;
        panel.classList.remove("open");
        button.setAttribute("aria-expanded", "false");
        schedulePoll();
    }

    button.addEventListener("click", function () {
        if (panelOpen) {
            closePanel();
        } else {
            openPanel();
        }
    });

    document.addEventListener("visibilitychange", function () {
        if (document.hidden) {
            schedulePoll();
        } else {
            poll().then(schedulePoll);
        }
    });

    // Livewire SPA navigation (wire:navigate, Filament ->spa()) swaps <body>: put the widget back.
    document.addEventListener("livewire:navigated", function () {
        if (!config) {
            return;
        }
        if (!style.isConnected) {
            document.head.appendChild(style);
        }
        var custom = document.querySelector("style[data-fcw-custom]");
        if (config.custom_css && !custom) {
            applyAppearance();
        }
        if (!button.isConnected) {
            document.body.appendChild(button);
        }
        if (!panel.isConnected) {
            document.body.appendChild(panel);
        }
    });

    function init() {
        request("/widget/" + encodeURIComponent(slug) + (locale ? "?locale=" + encodeURIComponent(locale) : ""))
            .then(function (data) {
                config = data || {};
                labels = config.labels || {};
                buildPanel();
                applyAppearance();
                document.body.appendChild(button);
                document.body.appendChild(panel);
                return poll();
            })
            .then(schedulePoll)
            .catch(function () {});
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
