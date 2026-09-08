const API_BASE = "http://localhost:8000/api";

async function apiPost(endpoint, data) {
    const res = await fetch(`${API_BASE}/${endpoint}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    });
    const json = await res.json();
    if (!res.ok) throw new Error(json.error || "Request failed");
    return json;
}

async function apiGet(endpoint, params = {}) {
    const query = new URLSearchParams(params).toString();
    const res = await fetch(`${API_BASE}/${endpoint}?${query}`);
    const json = await res.json();
    if (!res.ok) throw new Error(json.error || "Request failed");
    return json;
}

function showMessage(el, text, type = "success") {
    el.textContent = text;
    el.className = `message ${type}`;
    el.style.display = "block";
}

function saveSession(data) {
    localStorage.setItem("agrilink_session", JSON.stringify(data));
}

function getSession() {
    const raw = localStorage.getItem("agrilink_session");
    return raw ? JSON.parse(raw) : null;
}