<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$adminHelpRole = strtolower(trim((string) ($_SESSION['role'] ?? '')));
if (!in_array($adminHelpRole, ['admin', 'super admin', 'superadmin'], true)) {
    return;
}

if (empty($_SESSION['admin_help_chat_csrf'])) {
    $_SESSION['admin_help_chat_csrf'] = bin2hex(random_bytes(32));
}
$adminHelpProfile = [
    'name' => (string) ($_SESSION['name'] ?? 'Admin'),
    'picture' => '',
    'url' => in_array($adminHelpRole, ['super admin', 'superadmin'], true) ? 'super_admin_setting.php' : 'admin_settings.php',
];
if (isset($conn) && $conn instanceof mysqli && isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../profile_picture_helpers.php';
    $adminHelpProfileStmt = $conn->prepare('SELECT first_name, profile_picture FROM users WHERE id = ? LIMIT 1');
    if (!$adminHelpProfileStmt) {
        error_log('Unable to prepare Admin Guide profile query: ' . $conn->error);
    } else {
        $adminHelpUserId = (int) $_SESSION['user_id'];
        $adminHelpProfileStmt->bind_param('i', $adminHelpUserId);
        if ($adminHelpProfileStmt->execute()) {
            $adminHelpProfileRow = $adminHelpProfileStmt->get_result()->fetch_assoc();
            if ($adminHelpProfileRow) {
                $adminHelpProfile['name'] = (string) $adminHelpProfileRow['first_name'];
                $adminHelpProfile['picture'] = profilePictureUrl($adminHelpProfileRow['profile_picture'] ?? '');
            }
        } else {
            error_log('Unable to load Admin Guide profile: ' . $adminHelpProfileStmt->error);
        }
        $adminHelpProfileStmt->close();
    }
}
$adminHelpProfileLabel = trim($adminHelpProfile['name']) !== '' ? trim($adminHelpProfile['name']) : 'Admin';
$adminHelpProfileInitial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($adminHelpProfileLabel, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($adminHelpProfileLabel, 0, 1));
?>
<div id="adminHelpChat" class="admin-help-chat" data-csrf="<?= htmlspecialchars($_SESSION['admin_help_chat_csrf'], ENT_QUOTES, 'UTF-8') ?>">
    <button class="admin-help-launcher" id="adminHelpLauncher" type="button" aria-label="Open admin guide" aria-expanded="false">
        <span aria-hidden="true">?</span> AI Guide
    </button>
    <section class="admin-help-panel" id="adminHelpPanel" aria-label="Admin guide chat" data-profile-name="<?= htmlspecialchars($adminHelpProfileLabel, ENT_QUOTES, 'UTF-8') ?>" data-profile-picture="<?= htmlspecialchars($adminHelpProfile['picture'], ENT_QUOTES, 'UTF-8') ?>" data-profile-initial="<?= htmlspecialchars($adminHelpProfileInitial, ENT_QUOTES, 'UTF-8') ?>" data-profile-url="<?= htmlspecialchars($adminHelpProfile['url'], ENT_QUOTES, 'UTF-8') ?>" hidden>
        <header class="admin-help-header">
            <img class="admin-help-logo" src="../images/logo.jpg" alt="Alawihao Health Center logo">
            <div>
                <strong>Admin Guide</strong>
                <span>Ask about schedules, registrations, and admin tools</span>
            </div>
            <button type="button" id="adminHelpClose" aria-label="Close chat">&times;</button>
        </header>
        <div class="admin-help-notice">Guide only ito at walang access sa patient records. Huwag maglagay ng pangalan o personal na impormasyon.</div>
        <div class="admin-help-messages" id="adminHelpMessages" aria-live="polite">
            <div class="admin-help-message-row assistant">
                <img class="admin-help-avatar assistant-avatar" src="../images/logo.jpg" alt="Alawihao AI Guide">
                <div class="admin-help-message assistant">Hello! Maaari kitang gabayan sa admin tools at magpaliwanag ng fields sa maternal o child registration forms. Ano ang kailangan mo?</div>
            </div>
        </div>
        <div class="admin-help-prompts" id="adminHelpPrompts">
            <button type="button" data-question="Paano mag-set ng schedule?">Set schedule</button>
            <button type="button" data-question="Paano mag-review ng reschedule request?">Reschedule request</button>
            <button type="button" data-question="Paano mag-approve ng schedule edit?">Schedule edit approval</button>
            <button type="button" data-question="Paano mag-approve ng registration?">Review approvals</button>
            <button type="button" data-question="Paano mag-register ng bata o infant?">Register patient</button>
            <button type="button" data-question="Paano maghanap ng patient record?">Find records</button>
            <button type="button" data-question="Paano mag-manage ng vaccine stock?">Vaccine stock</button>
            <button type="button" data-question="Ano ang PHIC CAT sa registration form?">PHIC CAT</button>
            <button type="button" data-question="Ano ang LMP?">LMP</button>
        </div>
        <form class="admin-help-form" id="adminHelpForm">
            <label class="admin-help-sr-only" for="adminHelpInput">Ask an admin question</label>
            <input id="adminHelpInput" type="text" maxlength="500" autocomplete="off" placeholder="I-type ang tanong..." required>
            <button type="submit" aria-label="Send question">&#10148;</button>
        </form>
    </section>
</div>
<style>
    .admin-help-chat, .admin-help-chat * { box-sizing: border-box; }
    .admin-help-chat { position: fixed; right: 22px; bottom: 22px; z-index: 9999; font-family: "Segoe UI", Arial, sans-serif; color: #263126; }
    .admin-help-launcher { display: flex; align-items: center; gap: 9px; border: 0; border-radius: 28px; padding: 13px 18px; background: #2d5016; color: #fff; box-shadow: 0 5px 20px #0003; font: 600 14px "Segoe UI", Arial, sans-serif; cursor: pointer; }
    .admin-help-launcher:hover { background: #5a7c3a; }
    .admin-help-launcher span { display: inline-flex; align-items: center; justify-content: center; width: 19px; height: 19px; border: 1px solid #fff; border-radius: 50%; }
    .admin-help-panel { position: absolute; right: 0; bottom: 62px; display: flex; flex-direction: column; width: min(370px, calc(100vw - 28px)); height: min(540px, calc(100vh - 110px)); background: #fff; border: 1px solid #dfe5da; border-radius: 16px; overflow: hidden; box-shadow: 0 12px 40px #17200f38; }
    .admin-help-panel[hidden] { display: none; }
    .admin-help-header { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 13px 15px; color: #fff; background: #2d5016; }
    .admin-help-logo { width: 36px; height: 36px; flex: 0 0 36px; border: 1px solid #ffffff80; border-radius: 50%; object-fit: cover; background: #fff; }
    .admin-help-header div { display: flex; flex-direction: column; gap: 3px; }
    .admin-help-header strong { font-size: 14px; }
    .admin-help-header span { font-size: 11px; opacity: .88; }
    .admin-help-header button { border: 0; background: transparent; color: #fff; font-size: 25px; line-height: 1; cursor: pointer; }
    .admin-help-notice { padding: 9px 13px; background: #f1f5ed; color: #59614f; font-size: 11px; line-height: 1.4; border-bottom: 1px solid #e8ece3; }
    .admin-help-messages { display: flex; flex: 1; flex-direction: column; gap: 10px; padding: 14px; overflow-y: auto; background: #fbfcf9; }
    .admin-help-message-row { display: flex; align-items: flex-end; gap: 7px; max-width: 100%; }
    .admin-help-message-row.user { align-self: flex-end; flex-direction: row-reverse; }
    .admin-help-avatar { width: 30px; height: 30px; flex: 0 0 30px; border-radius: 50%; object-fit: cover; }
    .admin-help-avatar.assistant-avatar { border: 1px solid #dfe5da; background: #fff; }
    .admin-help-profile-link { display: grid; overflow: hidden; place-items: center; background: #e4efdd; color: #435248; font-size: 13px; font-weight: 700; text-decoration: none; }
    .admin-help-profile-link img { display: block; width: 100%; height: 100%; object-fit: cover; }
    .admin-help-message { max-width: 88%; padding: 10px 12px; border-radius: 12px; font-size: 13px; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; }
    .admin-help-message-row .admin-help-message { max-width: calc(100% - 37px); }
    .admin-help-message.assistant { background: #eef3e9; }
    .admin-help-message.user { color: #fff; background: #2d5016; }
    .admin-help-links { margin-top: 8px; }
    .admin-help-links a { color: #35572b; font-weight: 600; }
    .admin-help-prompts { display: flex; flex-wrap: wrap; gap: 6px; padding: 0 12px 10px; background: #fbfcf9; }
    .admin-help-prompts button { border: 1px solid #ccd7c2; border-radius: 16px; padding: 6px 9px; color: #4c603e; background: #fff; font-size: 11px; cursor: pointer; }
    .admin-help-form { display: flex; gap: 8px; padding: 10px 12px; border-top: 1px solid #e8ece3; }
    .admin-help-form input { flex: 1; min-width: 0; border: 1px solid #d8ded2; border-radius: 20px; padding: 10px 13px; outline: none; font-size: 13px; }
    .admin-help-form input:focus { border-color: #6b8e55; box-shadow: 0 0 0 2px #6b8e5525; }
    .admin-help-form button { width: 40px; height: 40px; border: 0; border-radius: 50%; color: #fff; background: #2d5016; cursor: pointer; }
    .admin-help-form button:disabled { opacity: .6; cursor: wait; }
    .admin-help-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }






    @media (max-width: 480px) { .admin-help-chat { right: 12px; bottom: 12px; } .admin-help-panel { bottom: 60px; height: min(540px, calc(100vh - 90px)); } }
</style>
<script>
(() => {
    const root = document.getElementById('adminHelpChat');
    if (!root) return;
    document.body.appendChild(root);

    const launcher = document.getElementById('adminHelpLauncher');
    const panel = document.getElementById('adminHelpPanel');
    const closeButton = document.getElementById('adminHelpClose');
    const form = document.getElementById('adminHelpForm');
    const input = document.getElementById('adminHelpInput');
    const messages = document.getElementById('adminHelpMessages');
    const submitButton = form.querySelector('button');
    const profileName = panel.dataset.profileName;
    const profilePicture = panel.dataset.profilePicture;
    const profileInitial = panel.dataset.profileInitial;

    function setOpen(open) {
        panel.hidden = !open;
        launcher.setAttribute('aria-expanded', String(open));
        if (open) input.focus();
    }

    function addMessage(text, kind, links) {
        const row = document.createElement('div');
        row.className = `admin-help-message-row ${kind}`;
        const avatar = kind === 'assistant'
            ? document.createElement('img')
            : document.createElement('a');
        avatar.className = `admin-help-avatar ${kind === 'assistant' ? 'assistant-avatar' : 'admin-help-profile-link'}`;
        if (kind === 'assistant') {
            avatar.src = '../images/logo.jpg';
            avatar.alt = 'Alawihao AI Guide';
        } else {
            avatar.href = panel.dataset.profileUrl;
            avatar.setAttribute('aria-label', `View profile for ${profileName}`);
            if (profilePicture) {
                const profileImage = document.createElement('img');
                profileImage.src = profilePicture;
                profileImage.alt = '';
                profileImage.onerror = () => {
                    avatar.textContent = profileInitial;
                };
                avatar.appendChild(profileImage);
            } else {
                avatar.textContent = profileInitial;
            }
        }

        const message = document.createElement('div');
        message.className = `admin-help-message ${kind}`;
        message.textContent = text;
        if (Array.isArray(links) && links.length) {
            const linkArea = document.createElement('div');
            linkArea.className = 'admin-help-links';
            links.forEach((item, index) => {
                if (index) linkArea.append(' · ');
                const link = document.createElement('a');
                link.href = item.url;
                link.textContent = item.label;
                linkArea.appendChild(link);
            });
            message.appendChild(linkArea);
        }
        row.append(avatar, message);
        messages.appendChild(row);
        messages.scrollTop = messages.scrollHeight;
        return row;
    }

    async function sendQuestion(question) {
        const cleanQuestion = question.trim();
        if (!cleanQuestion || submitButton.disabled) return;
        addMessage(cleanQuestion, 'user');
        input.value = '';
        submitButton.disabled = true;
        const pending = addMessage('Sandali, hinahanap ko ang tamang guide...', 'assistant');
        try {
            const response = await fetch('admin_help_chat_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': root.dataset.csrf },
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
            addMessage('Hindi ko maabot ang admin guide ngayon. Pakisubukan ulit mamaya.', 'assistant');
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
    document.getElementById('adminHelpPrompts').addEventListener('click', event => {
        const button = event.target.closest('button[data-question]');
        if (button) sendQuestion(button.dataset.question);
    });
})();
</script>
