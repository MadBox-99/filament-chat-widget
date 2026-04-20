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
        ".fcw-chat-btn{position:fixed;width:60px;height:60px;border-radius:50%;border:0;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.2);z-index:2147483646;display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;transition:transform .2s}" +
        ".fcw-chat-btn:hover{transform:scale(1.05)}" +
        ".fcw-chat-panel{position:fixed;width:360px;max-width:calc(100vw - 32px);height:520px;max-height:calc(100vh - 120px);background:#fff;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.25);z-index:2147483647;display:none;flex-direction:column;overflow:hidden;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}" +
        ".fcw-chat-panel.open{display:flex}" +
        ".fcw-chat-header{padding:14px 16px;color:#fff;display:flex;align-items:center;justify-content:space-between;font-weight:600}" +
        ".fcw-chat-close{background:transparent;border:0;color:#fff;cursor:pointer;font-size:22px;line-height:1;padding:0 4px}" +
        ".fcw-chat-body{flex:1;overflow-y:auto;padding:12px;background:#f7f7f8;display:flex;flex-direction:column;gap:8px}" +
        ".fcw-chat-msg{max-width:80%;padding:8px 12px;border-radius:12px;font-size:14px;line-height:1.4;word-wrap:break-word}" +
        ".fcw-chat-msg.visitor{align-self:flex-end;color:#fff}" +
        ".fcw-chat-msg.agent,.fcw-chat-msg.system{align-self:flex-start;background:#fff;color:#222;border:1px solid #e5e5e5}" +
        ".fcw-chat-intro{padding:16px;display:flex;flex-direction:column;gap:10px;font-size:14px;color:#333}" +
        ".fcw-chat-intro input{padding:8px 10px;border:1px solid #ddd;border-radius:8px;font-size:14px;width:100%;box-sizing:border-box}" +
        ".fcw-chat-input{border-top:1px solid #e5e5e5;padding:8px;display:flex;gap:8px;background:#fff}" +
        ".fcw-chat-input textarea{flex:1;resize:none;border:1px solid #ddd;border-radius:8px;padding:8px;font-family:inherit;font-size:14px;max-height:80px}" +
        ".fcw-chat-send{border:0;border-radius:8px;padding:0 14px;color:#fff;cursor:pointer;font-weight:600;font-size:14px}" +
        ".fcw-chat-send:disabled{opacity:.5;cursor:not-allowed}" +
        "@media(max-width:480px){.fcw-chat-panel{width:calc(100vw - 16px);height:calc(100vh - 100px)}}";
    document.head.appendChild(style);

    var button = document.createElement("button");
    button.className = "fcw-chat-btn";
    button.type = "button";
    button.innerHTML = "&#128172;";
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

    function applyColor() {
        var color = (config && config.color) || "#6366f1";
        button.style.background = color;
        var header = panel.querySelector(".fcw-chat-header");
        if (header) {
            header.style.background = color;
        }
        var visitorMsgs = panel.querySelectorAll(".fcw-chat-msg.visitor");
        for (var i = 0; i < visitorMsgs.length; i++) {
            visitorMsgs[i].style.background = color;
        }
        var sendBtn = panel.querySelector(".fcw-chat-send");
        if (sendBtn) {
            sendBtn.style.background = color;
        }
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

    function renderIntro() {
        var welcome = (config && config.welcome_message) || "";
        var labels = (config && config.labels) || {};
        panel.innerHTML =
            renderHeader() +
            '<div class="fcw-chat-body"></div>' +
            '<div class="fcw-chat-intro">' +
            (welcome ? "<div>" + escapeHtml(welcome) + "</div>" : "") +
            '<input type="text" class="fcw-chat-name" placeholder="' + escapeHtml(labels.name || "Name (optional)") + '" autocomplete="name">' +
            '<input type="email" class="fcw-chat-email" placeholder="' + escapeHtml(labels.email || "Email (optional)") + '" autocomplete="email">' +
            '<button type="button" class="fcw-chat-start fcw-chat-send" style="padding:10px">' + escapeHtml(labels.start || "Start conversation") + '</button>' +
            "</div>";
        applyColor();
        panel.querySelector(".fcw-chat-close").addEventListener("click", closePanel);
        panel.querySelector(".fcw-chat-start").addEventListener("click", startConversation);
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
            if (m.sender_type === "visitor") {
                div.style.background = (config && config.color) || "#6366f1";
            }
            body.appendChild(div);
        }
        body.scrollTop = body.scrollHeight;
    }

    function startConversation() {
        var name = panel.querySelector(".fcw-chat-name").value.trim();
        var email = panel.querySelector(".fcw-chat-email").value.trim();
        var body = { slug: slug };
        if (name) {
            body.visitor_name = name;
        }
        if (email) {
            body.visitor_email = email;
        }
        fetch(url("/conversations"), {
            method: "POST",
            headers: { "Content-Type": "application/json", Accept: "application/json" },
            body: JSON.stringify(body),
        })
            .then(function (r) {
                return r.ok ? r.json() : Promise.reject(r);
            })
            .then(function (data) {
                uuid = data.uuid;
                try {
                    window.localStorage.setItem(storageKey, uuid);
                } catch (e) {}
                renderConversation();
                appendMessages(data.messages || []);
                startPolling();
            })
            .catch(function () {});
    }

    function sendMessage(text) {
        text = (text || "").trim();
        if (!text || !uuid) {
            return;
        }
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
        if (uuid) {
            renderConversation();
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
                    renderIntro();
                });
            startPolling();
        } else {
            renderIntro();
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
            })
            .catch(function () {});
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
