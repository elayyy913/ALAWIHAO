<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['user_chatbot_csrf'])) {
    $_SESSION['user_chatbot_csrf'] = bin2hex(random_bytes(32));
}
?>
<div id="userChatbot" class="user-chatbot" data-csrf="<?= htmlspecialchars($_SESSION['user_chatbot_csrf'], ENT_QUOTES, 'UTF-8') ?>">
    <button class="user-chatbot-launcher" id="userChatbotLauncher" type="button" aria-label="Buksan ang health assistant" aria-expanded="false">
        <i class="fa-solid fa-message" aria-hidden="true"></i>
        <span>Health Help</span>
    </button>
    <section class="user-chatbot-panel" id="userChatbotPanel" aria-label="Health assistant" hidden>
        <header class="user-chatbot-header">
            <div>
                <strong>Alawihao Health Assistant</strong>
                <span>Gabay mula sa health articles at FAQs</span>
            </div>
            <button type="button" id="userChatbotClose" aria-label="Isara ang chat">&times;</button>
        </header>
        <div class="user-chatbot-notice">Pangkalahatang impormasyon lang ito. Huwag magpadala ng pangalan, contact details, o personal na health information.</div>
        <div class="user-chatbot-messages" id="userChatbotMessages" aria-live="polite">
            <div class="user-chatbot-message assistant">Kumusta! Makakatulong ako sa health articles, bakuna, at pag-navigate sa portal. Ano ang gusto mong malaman?</div>
        </div>
        <div class="user-chatbot-prompts" id="userChatbotPrompts">
            <button type="button" data-question="Paano ako magche-check ng schedule?">Schedule</button>
            <button type="button" data-question="Saan ako makakabasa tungkol sa bakuna?">Bakuna</button>
            <button type="button" data-question="Saan ko makikita ang health articles?">Health articles</button>
        </div>
        <form class="user-chatbot-form" id="userChatbotForm">
            <label class="sr-only" for="userChatbotInput">I-type ang tanong</label>
            <input id="userChatbotInput" type="text" maxlength="500" autocomplete="off" placeholder="I-type ang tanong..." required>
            <button type="submit" aria-label="Ipadala ang tanong"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button>
        </form>
    </section>
</div>
<style>
    .user-chatbot, .user-chatbot * { box-sizing: border-box; }
    .user-chatbot { position: fixed; right: 22px; bottom: 22px; z-index: 9999; font-family: "Segoe UI", Arial, sans-serif; color: #263126; }
    .user-chatbot-launcher { display: flex; align-items: center; gap: 9px; border: 0; border-radius: 28px; padding: 13px 18px; background: #5A6B47; color: #fff; box-shadow: 0 5px 20px #0003; font: 600 14px "Segoe UI", Arial, sans-serif; cursor: pointer; }
    .user-chatbot-launcher:hover { background: #465638; }
    .user-chatbot-launcher i { font-size: 17px; }
    .user-chatbot-panel { position: absolute; right: 0; bottom: 62px; display: flex; flex-direction: column; width: min(370px, calc(100vw - 28px)); height: min(540px, calc(100vh - 110px)); background: #fff; border: 1px solid #dfe5da; border-radius: 16px; overflow: hidden; box-shadow: 0 12px 40px #17200f38; }
    .user-chatbot-panel[hidden] { display: none; }
    .user-chatbot-header { display: flex; justify-content: space-between; align-items: center; padding: 15px 16px; color: #fff; background: #5A6B47; }
    .user-chatbot-header div { display: flex; flex-direction: column; gap: 3px; }
    .user-chatbot-header strong { font-size: 14px; }
    .user-chatbot-header span { font-size: 11px; opacity: .88; }
    .user-chatbot-header button { border: 0; background: transparent; color: #fff; font-size: 25px; line-height: 1; cursor: pointer; }
    .user-chatbot-notice { padding: 9px 13px; background: #f5f7f1; color: #59614f; font-size: 11px; line-height: 1.4; border-bottom: 1px solid #e8ece3; }
    .user-chatbot-messages { display: flex; flex: 1; flex-direction: column; gap: 10px; padding: 14px; overflow-y: auto; background: #fbfcf9; }
    .user-chatbot-message { max-width: 88%; padding: 10px 12px; border-radius: 12px; font-size: 13px; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; }
    .user-chatbot-message.assistant { align-self: flex-start; background: #eef3e9; }
    .user-chatbot-message.user { align-self: flex-end; color: #fff; background: #5A6B47; }
    .user-chatbot-message a { color: #35572b; font-weight: 600; }
    .user-chatbot-prompts { display: flex; flex-wrap: wrap; gap: 6px; padding: 0 12px 10px; background: #fbfcf9; }
    .user-chatbot-prompts button { border: 1px solid #ccd7c2; border-radius: 16px; padding: 6px 9px; color: #4c603e; background: #fff; font-size: 11px; cursor: pointer; }
    .user-chatbot-form { display: flex; gap: 8px; padding: 10px 12px; border-top: 1px solid #e8ece3; }
    .user-chatbot-form input { flex: 1; min-width: 0; border: 1px solid #d8ded2; border-radius: 20px; padding: 10px 13px; outline: none; font-size: 13px; }
    .user-chatbot-form input:focus { border-color: #6b8e55; box-shadow: 0 0 0 2px #6b8e5525; }
    .user-chatbot-form button { width: 40px; height: 40px; border: 0; border-radius: 50%; color: #fff; background: #5A6B47; cursor: pointer; }
    .user-chatbot-form button:disabled { opacity: .6; cursor: wait; }
    .user-chatbot .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
    [data-theme="dark"] .user-chatbot-panel { background: #242826; border-color: #3a403a; color: #e8ede5; }
    [data-theme="dark"] .user-chatbot-notice, [data-theme="dark"] .user-chatbot-messages, [data-theme="dark"] .user-chatbot-prompts { background: #1e211e; color: #cbd4c5; }
    [data-theme="dark"] .user-chatbot-message.assistant { background: #30382e; color: #e8ede5; }
    [data-theme="dark"] .user-chatbot-prompts button { background: #242826; color: #cfe0c3; border-color: #52614a; }
    [data-theme="dark"] .user-chatbot-form { border-color: #3a403a; }
    [data-theme="dark"] .user-chatbot-form input { background: #1e211e; color: #e8ede5; border-color: #52614a; }
    @media (max-width: 480px) { .user-chatbot { right: 12px; bottom: 12px; } .user-chatbot-panel { bottom: 60px; height: min(540px, calc(100vh - 90px)); } }
</style>
<script>
(() => {
    const root = document.getElementById('userChatbot');
    if (!root) return;
    document.body.appendChild(root);

    const launcher = document.getElementById('userChatbotLauncher');
    const panel = document.getElementById('userChatbotPanel');
    const closeButton = document.getElementById('userChatbotClose');
    const form = document.getElementById('userChatbotForm');
    const input = document.getElementById('userChatbotInput');
    const messages = document.getElementById('userChatbotMessages');
    const submitButton = form.querySelector('button');
    const csrfToken = root.dataset.csrf;

    function setOpen(open) {
        panel.hidden = !open;
        launcher.setAttribute('aria-expanded', String(open));
        if (open) input.focus();
    }

    function addMessage(text, kind, links) {
        const message = document.createElement('div');
        message.className = `user-chatbot-message ${kind}`;
        message.textContent = text;
        if (Array.isArray(links) && links.length) {
            const linkArea = document.createElement('div');
            linkArea.style.marginTop = '8px';
            links.forEach((item, index) => {
                if (index) linkArea.append(' · ');
                const link = document.createElement('a');
                link.href = item.url;
                link.textContent = item.label;
                linkArea.appendChild(link);
            });
            message.appendChild(linkArea);
        }
        messages.appendChild(message);
        messages.scrollTop = messages.scrollHeight;
        return message;
    }

    async function sendQuestion(question) {
        const cleanQuestion = question.trim();
        if (!cleanQuestion || submitButton.disabled) return;
        addMessage(cleanQuestion, 'user');
        input.value = '';
        submitButton.disabled = true;
        const pending = addMessage('Sandali, hinahanap ko ang tamang impormasyon...', 'assistant');
        try {
            const response = await fetch('user_chatbot_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ question: cleanQuestion })
            });
            const data = await response.json();
            pending.remove();
            if (!response.ok || !data.success) {
                addMessage(data.message || 'Hindi muna ako makasagot ngayon. Pakisubukan ulit mamaya.', 'assistant');
                return;
            }
            addMessage(data.answer + (data.notice ? '\n\n' + data.notice : ''), 'assistant', data.links);
        } catch (error) {
            pending.remove();
            addMessage('Hindi ko maabot ang assistant ngayon. Pakisubukan ulit mamaya.', 'assistant');
        } finally {
            submitButton.disabled = false;
            input.focus();
        }
    }

    launcher.addEventListener('click', () => setOpen(panel.hidden));
    closeButton.addEventListener('click', () => setOpen(false));
    form.addEventListener('submit', event => {
        event.preventDefault();
        sendQuestion(input.value);
    });
    document.getElementById('userChatbotPrompts').addEventListener('click', event => {
        const button = event.target.closest('button[data-question]');
        if (button) sendQuestion(button.dataset.question);
    });
})();
</script>
