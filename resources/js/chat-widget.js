/**
 * Filament Chat Widget — embeddable browser widget.
 *
 * Usage:
 *   <script src="https://your-app.test/vendor/filament-chat-widget/chat-widget.js"
 *           data-team="{tenant_slug}" async></script>
 *
 * Reads the `data-team` attribute, fetches widget config from the host,
 * renders a floating button + chat panel, persists conversation uuid in
 * localStorage, and polls for new messages while the panel is open.
 */
(function () {
    "use strict";

    if (typeof window === "undefined" || typeof document === "undefined") {
        return;
    }

    var scripts = document.querySelectorAll("script[data-team]");
    if (!scripts || scripts.length === 0) {
        return;
    }

    var script = scripts[scripts.length - 1];
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

    var routePrefix = script.getAttribute("data-prefix") || "chat";
    var storageKey = "fcw-chat-" + slug;
    var mountedAttr = "data-fcw-chat-mounted";
    if (document.documentElement.getAttribute(mountedAttr) === "1") {
        return;
    }
    document.documentElement.setAttribute(mountedAttr, "1");

    var config = null;
    var uuid = null;
    var lastId = 0;
    var pollTimer = null;
    var panelOpen = false;

    try {
        uuid = window.localStorage.getItem(storageKey);
    } catch (e) {
        uuid = null;
    }

    var style = document.createElement("style");
    style.textContent =
        "@keyframes fcw-pop{0%{transform:translateY(16px) scale(.96);opacity:0}100%{transform:translateY(0) scale(1);opacity:1}}" +
        "@keyframes fcw-fade{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}" +
        "@keyframes fcw-pulse{0%,100%{box-shadow:0 12px 32px -8px rgba(0,0,0,.25),0 0 0 0 var(--fcw-color,#6366f1)}50%{box-shadow:0 12px 32px -8px rgba(0,0,0,.25),0 0 0 10px rgba(99,102,241,0)}}" +
        ".fcw-chat-btn{position:fixed;width:60px;height:60px;border-radius:50%;border:0;cursor:pointer;box-shadow:0 12px 32px -8px rgba(0,0,0,.28),0 2px 6px rgba(0,0,0,.12);z-index:2147483646;display:flex;align-items:center;justify-content:center;color:#fff;font-size:26px;background:var(--fcw-color,#6366f1);transition:transform .2s ease,box-shadow .2s ease;animation:fcw-pulse 3s ease-in-out infinite}" +
        ".fcw-chat-btn:hover{transform:translateY(-2px) scale(1.04);box-shadow:0 16px 40px -8px rgba(0,0,0,.35),0 4px 12px rgba(0,0,0,.14)}" +
        ".fcw-chat-btn:active{transform:scale(.96)}" +
        ".fcw-chat-btn svg{width:26px;height:26px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}" +
        ".fcw-chat-panel{position:fixed;width:380px;max-width:calc(100vw - 24px);height:560px;max-height:calc(100vh - 120px);background:#fff;border-radius:20px;box-shadow:0 24px 56px -12px rgba(0,0,0,.28),0 2px 8px rgba(0,0,0,.08);z-index:2147483647;display:none;flex-direction:column;overflow:hidden;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Inter,Roboto,sans-serif;-webkit-font-smoothing:antialiased}" +
        ".fcw-chat-panel.open{display:flex;animation:fcw-pop .22s cubic-bezier(.2,.8,.2,1)}" +
        ".fcw-chat-header{padding:16px 18px;color:#fff;display:flex;align-items:center;justify-content:space-between;font-weight:600;font-size:15px;background:var(--fcw-color,#6366f1);position:relative}" +
        ".fcw-chat-header::after{content:'';position:absolute;left:0;right:0;bottom:-10px;height:10px;background:inherit;-webkit-mask-image:linear-gradient(to bottom,rgba(0,0,0,.15),transparent);mask-image:linear-gradient(to bottom,rgba(0,0,0,.15),transparent);pointer-events:none}" +
        ".fcw-chat-close{background:transparent;border:0;color:#fff;cursor:pointer;font-size:20px;line-height:1;padding:6px;border-radius:8px;transition:background .15s ease;opacity:.85}" +
        ".fcw-chat-close:hover{background:rgba(255,255,255,.18);opacity:1}" +
        ".fcw-chat-body{flex:1;overflow-y:auto;padding:16px 14px;background:#f7f8fa;display:flex;flex-direction:column;gap:6px;scroll-behavior:smooth}" +
        ".fcw-chat-body::-webkit-scrollbar{width:6px}" +
        ".fcw-chat-body::-webkit-scrollbar-thumb{background:rgba(0,0,0,.15);border-radius:3px}" +
        ".fcw-chat-body::-webkit-scrollbar-thumb:hover{background:rgba(0,0,0,.25)}" +
        ".fcw-chat-msg{max-width:78%;padding:9px 13px;font-size:14px;line-height:1.45;word-wrap:break-word;animation:fcw-fade .18s ease-out}" +
        ".fcw-chat-msg.visitor{align-self:flex-end;color:#fff;background:var(--fcw-color,#6366f1);border-radius:16px 16px 4px 16px;box-shadow:0 1px 2px rgba(0,0,0,.08)}" +
        ".fcw-chat-msg.agent{align-self:flex-start;background:#fff;color:#1a1b1f;border-radius:16px 16px 16px 4px;box-shadow:0 1px 2px rgba(0,0,0,.06),0 0 0 1px rgba(0,0,0,.04)}" +
        ".fcw-chat-msg.system{align-self:center;max-width:90%;background:rgba(0,0,0,.04);color:#4b5563;font-size:12.5px;text-align:center;padding:7px 14px;border-radius:999px;font-style:normal}" +
        ".fcw-chat-input{border-top:1px solid rgba(0,0,0,.06);padding:10px 10px 12px;display:flex;gap:8px;background:#fff;align-items:flex-end}" +
        ".fcw-chat-input textarea{flex:1;resize:none;border:1px solid rgba(0,0,0,.1);border-radius:14px;padding:10px 14px;font-family:inherit;font-size:14px;line-height:1.4;max-height:100px;background:#f7f8fa;color:#1a1b1f;outline:none;transition:border-color .15s ease,box-shadow .15s ease,background .15s ease}" +
        ".fcw-chat-input textarea:focus{border-color:var(--fcw-color,#6366f1);background:#fff;box-shadow:0 0 0 3px rgba(99,102,241,.12)}" +
        ".fcw-chat-send{border:0;border-radius:12px;padding:0 16px;height:40px;color:#fff;cursor:pointer;font-weight:600;font-size:14px;background:var(--fcw-color,#6366f1);transition:transform .1s ease,filter .15s ease;white-space:nowrap}" +
        ".fcw-chat-send:hover{filter:brightness(1.08)}" +
        ".fcw-chat-send:active{transform:scale(.97)}" +
        ".fcw-chat-send:disabled{opacity:.5;cursor:not-allowed;filter:none}" +
        "@media(max-width:480px){.fcw-chat-panel{width:calc(100vw - 16px);height:calc(100vh - 96px);border-radius:16px}.fcw-chat-btn{width:56px;height:56px}}" +
        "@media(prefers-color-scheme:dark){.fcw-chat-panel{background:#1a1b1f;color:#e6e7ea}.fcw-chat-body{background:#111214}.fcw-chat-msg.agent{background:#24262b;color:#e6e7ea;box-shadow:0 1px 2px rgba(0,0,0,.3),0 0 0 1px rgba(255,255,255,.04)}.fcw-chat-msg.system{background:rgba(255,255,255,.05);color:#9ca3af}.fcw-chat-input{background:#1a1b1f;border-top-color:rgba(255,255,255,.06)}.fcw-chat-input textarea{background:#111214;color:#e6e7ea;border-color:rgba(255,255,255,.08)}.fcw-chat-input textarea:focus{background:#1a1b1f}}";
    document.head.appendChild(style);

    var button = document.createElement("button");
    button.className = "fcw-chat-btn";
    button.type = "button";
    button.innerHTML =
        '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>';
    button.setAttribute("aria-label", "Chat");

    var panel = document.createElement("div");
    panel.className = "fcw-chat-panel";

    function url(path) {
        return baseUrl + "/" + routePrefix.replace(/^\/+|\/+$/g, "") + path;
    }

    function applyPosition() {
        var position = (config && config.position) || "bottom-right";
        var offset = "20px";
        button.style.bottom = offset;
        panel.style.bottom = "90px";
        if (position === "bottom-left") {
            button.style.left = offset;
            button.style.right = "auto";
            panel.style.left = offset;
            panel.style.right = "auto";
        } else {
            button.style.right = offset;
            button.style.left = "auto";
            panel.style.right = offset;
            panel.style.left = "auto";
        }
    }

    function isValidColor(value) {
        if (!value || typeof value !== "string") {
            return false;
        }
        var test = document.createElement("div").style;
        test.color = "";
        test.color = value;
        return test.color !== "";
    }

    function applyColor() {
        var color = config && config.color;
        if (!isValidColor(color)) {
            color = "#6366f1";
        }
        button.style.setProperty("--fcw-color", color);
        panel.style.setProperty("--fcw-color", color);
    }

    var customStyleNode = null;
    function applyCustomCss() {
        var css = (config && config.custom_css) || "";
        if (!css) {
            if (customStyleNode && customStyleNode.parentNode) {
                customStyleNode.parentNode.removeChild(customStyleNode);
                customStyleNode = null;
            }
            return;
        }
        if (!customStyleNode) {
            customStyleNode = document.createElement("style");
            customStyleNode.setAttribute("data-fcw-custom", "1");
            document.head.appendChild(customStyleNode);
        }
        customStyleNode.textContent = css;
    }

    function escapeHtml(str) {
        var div = document.createElement("div");
        div.textContent = String(str == null ? "" : str);
        return div.innerHTML;
    }

    function renderHeader() {
        var title = (config && config.title) || "Chat";
        return (
            '<div class="fcw-chat-header"><span>' +
            escapeHtml(title) +
            '</span><button type="button" class="fcw-chat-close" aria-label="Close">&times;</button></div>'
        );
    }

    function renderConversation() {
        var labels = (config && config.labels) || {};
        panel.innerHTML =
            renderHeader() +
            '<div class="fcw-chat-body"></div>' +
            '<div class="fcw-chat-input">' +
            '<textarea rows="2" placeholder="' + escapeHtml(labels.placeholder || "Type a message...") + '"></textarea>' +
            '<button type="button" class="fcw-chat-send">' + escapeHtml(labels.send || "Send") + '</button>' +
            "</div>";
        applyColor();
        panel.querySelector(".fcw-chat-close").addEventListener("click", closePanel);
        var textarea = panel.querySelector("textarea");
        var sendBtn = panel.querySelector(".fcw-chat-send");
        sendBtn.addEventListener("click", function () {
            sendMessage(textarea.value);
            textarea.value = "";
        });
        textarea.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                sendMessage(textarea.value);
                textarea.value = "";
            }
        });
    }

    function appendMessages(messages) {
        if (!messages || !messages.length) {
            return;
        }
        var body = panel.querySelector(".fcw-chat-body");
        if (!body) {
            return;
        }
        for (var i = 0; i < messages.length; i++) {
            var m = messages[i];
            if (m.id > lastId) {
                lastId = m.id;
            }
            var div = document.createElement("div");
            div.className = "fcw-chat-msg " + (m.sender_type || "agent");
            div.textContent = m.message;
            body.appendChild(div);
        }
        body.scrollTop = body.scrollHeight;
    }

    function renderWelcome() {
        var welcome = (config && config.welcome_message) || "";
        if (!welcome) {
            return;
        }
        var body = panel.querySelector(".fcw-chat-body");
        if (!body || body.querySelector(".fcw-chat-welcome")) {
            return;
        }
        var div = document.createElement("div");
        div.className = "fcw-chat-msg system fcw-chat-welcome";
        div.textContent = welcome;
        body.appendChild(div);
        body.scrollTop = body.scrollHeight;
    }

    function ensureConversation(callback) {
        if (uuid) {
            callback();
            return;
        }
        fetch(url("/conversations"), {
            method: "POST",
            headers: { "Content-Type": "application/json", Accept: "application/json" },
            body: JSON.stringify({ slug: slug }),
        })
            .then(function (r) {
                return r.ok ? r.json() : Promise.reject(r);
            })
            .then(function (data) {
                uuid = data.uuid;
                try {
                    window.localStorage.setItem(storageKey, uuid);
                } catch (e) {}
                appendMessages(data.messages || []);
                startPolling();
                callback();
            })
            .catch(function () {});
    }

    function sendMessage(text) {
        text = (text || "").trim();
        if (!text) {
            return;
        }
        ensureConversation(function () {
            fetch(url("/conversations/" + encodeURIComponent(uuid) + "/messages"), {
                method: "POST",
                headers: { "Content-Type": "application/json", Accept: "application/json" },
                body: JSON.stringify({ message: text }),
            })
                .then(function (r) {
                    return r.ok ? r.json() : Promise.reject(r);
                })
                .then(function (data) {
                    if (data && data.message) {
                        appendMessages([data.message]);
                    }
                })
                .catch(function () {});
        });
    }

    function pollMessages() {
        if (!uuid) {
            return;
        }
        fetch(
            url("/conversations/" + encodeURIComponent(uuid) + "/messages?since=" + lastId),
            { headers: { Accept: "application/json" } }
        )
            .then(function (r) {
                return r.ok ? r.json() : Promise.reject(r);
            })
            .then(function (data) {
                appendMessages((data && data.messages) || []);
            })
            .catch(function () {});
    }

    function startPolling() {
        stopPolling();
        pollTimer = window.setInterval(pollMessages, 5000);
    }

    function stopPolling() {
        if (pollTimer) {
            window.clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    function openPanel() {
        panelOpen = true;
        panel.classList.add("open");
        renderConversation();
        renderWelcome();
        if (uuid) {
            fetch(
                url("/conversations/" + encodeURIComponent(uuid) + "/messages"),
                { headers: { Accept: "application/json" } }
            )
                .then(function (r) {
                    return r.ok ? r.json() : Promise.reject(r);
                })
                .then(function (data) {
                    lastId = 0;
                    appendMessages((data && data.messages) || []);
                })
                .catch(function () {
                    try {
                        window.localStorage.removeItem(storageKey);
                    } catch (e) {}
                    uuid = null;
                });
            startPolling();
        }
    }

    function closePanel() {
        panelOpen = false;
        panel.classList.remove("open");
        stopPolling();
    }

    button.addEventListener("click", function () {
        if (panelOpen) {
            closePanel();
        } else {
            openPanel();
        }
    });

    function init() {
        fetch(url("/widget/" + encodeURIComponent(slug)), {
            headers: { Accept: "application/json" },
        })
            .then(function (r) {
                return r.ok ? r.json() : Promise.reject(r);
            })
            .then(function (data) {
                config = data;
                document.body.appendChild(button);
                document.body.appendChild(panel);
                applyPosition();
                applyColor();
                applyCustomCss();
            })
            .catch(function () {});
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
