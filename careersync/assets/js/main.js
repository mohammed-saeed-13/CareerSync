// ============================================================
// CareerSync – Global JavaScript
// ============================================================

// ---- Theme Toggle -------------------------------------------
(function () {
  const THEME_KEY = 'careersync_theme';

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem(THEME_KEY, theme);
    document.querySelectorAll('.theme-toggle').forEach(btn => {
      btn.innerHTML = theme === 'dark'
        ? '<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 3a9 9 0 1 0 9 9c0-.46-.04-.92-.1-1.36a5.389 5.389 0 0 1-4.4 2.26 5.403 5.403 0 0 1-3.14-9.8c-.44-.06-.9-.1-1.36-.1z"/></svg> Light'
        : '<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 7a5 5 0 1 0 0 10A5 5 0 0 0 12 7zm0-5a1 1 0 0 1 1 1v2a1 1 0 0 1-2 0V3a1 1 0 0 1 1-1zm0 16a1 1 0 0 1 1 1v2a1 1 0 0 1-2 0v-2a1 1 0 0 1 1-1zm9-9h2a1 1 0 0 1 0 2h-2a1 1 0 0 1 0-2zM1 12H3a1 1 0 0 1 0 2H1a1 1 0 0 1 0-2z"/></svg> Dark';
    });
  }

  window.toggleTheme = function () {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    applyTheme(current === 'dark' ? 'light' : 'dark');
  };

  // Apply saved theme on load
  const saved = localStorage.getItem(THEME_KEY) || 'light';
  applyTheme(saved);
})();

// ---- Chatbot ------------------------------------------------
const Chatbot = {
  open: false,

  init() {
    const fab = document.getElementById('chatbot-fab');
    const win = document.getElementById('chatbot-window');
    const closeBtn = document.getElementById('chatbot-close');
    const sendBtn = document.getElementById('chatbot-send');
    const input = document.getElementById('chatbot-input');

    if (!fab) return;

    fab.addEventListener('click', () => this.toggle());
    closeBtn?.addEventListener('click', () => this.toggle(false));
    sendBtn?.addEventListener('click', () => this.send());
    input?.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) this.send(); });
  },

  toggle(state) {
    this.open = state !== undefined ? state : !this.open;
    const win = document.getElementById('chatbot-window');
    if (win) {
      win.classList.toggle('open', this.open);
      if (this.open && document.getElementById('chatbot-messages').children.length === 0) {
        this.addMessage('bot', '👋 Hi! I\'m your CareerSync AI assistant. Ask me about eligible drives, interview schedules, resume tips, or your placement readiness!');
      }
    }
  },

  addMessage(role, text) {
    const msgs = document.getElementById('chatbot-messages');
    const div = document.createElement('div');
    div.className = `chat-msg ${role}`;
    div.innerHTML = text.replace(/\n/g, '<br>');
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
    return div;
  },

  showTyping() {
    const msgs = document.getElementById('chatbot-messages');
    const div = document.createElement('div');
    div.className = 'chat-msg bot chat-typing';
    div.id = 'typing-indicator';
    div.innerHTML = '<span></span><span></span><span></span>';
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
  },

  hideTyping() {
    document.getElementById('typing-indicator')?.remove();
  },

  async send() {
    const input = document.getElementById('chatbot-input');
    const msg = input.value.trim();
    if (!msg) return;

    this.addMessage('user', msg);
    input.value = '';
    this.showTyping();

    try {
      const res = await fetch('/careersync/api/chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN || '' },
        body: JSON.stringify({ message: msg }),
      });
      const data = await res.json();
      this.hideTyping();
      this.addMessage('bot', data.response || 'Sorry, I could not process your request.');
    } catch {
      this.hideTyping();
      this.addMessage('bot', 'Connection error. Please try again.');
    }
  },
};

// ---- Notification Badge ------------------------------------
async function loadNotificationCount() {
  const badge = document.getElementById('notif-badge');
  if (!badge) return;
  try {
    const r = await fetch('/careersync/api/notifications.php?count=1');
    const d = await r.json();
    badge.textContent = d.count || 0;
    badge.style.display = d.count > 0 ? 'inline-flex' : 'none';
  } catch {}
}

// ---- Flash Auto-hide ---------------------------------------
document.querySelectorAll('.alert[data-autohide]').forEach(alert => {
  setTimeout(() => { alert.style.opacity = '0'; setTimeout(() => alert.remove(), 400); }, 4000);
});

// ---- Init ---------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  Chatbot.init();
  loadNotificationCount();

  // Animate stat numbers
  document.querySelectorAll('.stat-value[data-val]').forEach(el => {
    const target = parseInt(el.dataset.val, 10);
    let current = 0;
    const step = Math.ceil(target / 30);
    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = current.toLocaleString();
      if (current >= target) clearInterval(timer);
    }, 30);
  });
});
