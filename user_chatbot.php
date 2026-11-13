<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['user_chatbot_csrf'])) {
    $_SESSION['user_chatbot_csrf'] = bin2hex(random_bytes(32));
}
if (!empty($show_community_link) && empty($_SESSION['community_chat_csrf'])) {
    $_SESSION['community_chat_csrf'] = bin2hex(random_bytes(32));
}
$chatbotProfile = [
    'name' => (string) ($_SESSION['name'] ?? 'Profile'),
    'picture' => '',
];
if (isset($conn) && $conn instanceof mysqli && isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/profile_picture_helpers.php';
    $chatbotProfileStmt = $conn->prepare('SELECT first_name, profile_picture FROM users WHERE id = ? LIMIT 1');
    if (!$chatbotProfileStmt) {
        error_log('Unable to prepare Health Help profile query: ' . $conn->error);
    } else {
        $chatbotUserId = (int) $_SESSION['user_id'];
        $chatbotProfileStmt->bind_param('i', $chatbotUserId);
        if ($chatbotProfileStmt->execute()) {
            $chatbotProfileRow = $chatbotProfileStmt->get_result()->fetch_assoc();
            if ($chatbotProfileRow) {
                $chatbotProfile['name'] = (string) $chatbotProfileRow['first_name'];
                $chatbotProfile['picture'] = profilePictureUrl($chatbotProfileRow['profile_picture'] ?? '');
            }
        } else {
            error_log('Unable to load Health Help profile: ' . $chatbotProfileStmt->error);
        }
        $chatbotProfileStmt->close();
    }
}
$chatbotProfileLabel = trim($chatbotProfile['name']) !== '' ? trim($chatbotProfile['name']) : 'Profile';
$chatbotProfileInitial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($chatbotProfileLabel, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($chatbotProfileLabel, 0, 1));
?>
<div id="userChatbot" class="user-chatbot<?= !empty($show_community_link) ? ' has-community-shortcut' : '' ?>" data-csrf="<?= htmlspecialchars($_SESSION['user_chatbot_csrf'], ENT_QUOTES, 'UTF-8') ?>"<?php if (!empty($show_community_link)): ?> data-community-csrf="<?= htmlspecialchars($_SESSION['community_chat_csrf'], ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>>
    <?php if (!empty($show_community_link)): ?>
    <button class="user-chatbot-launcher user-community-shortcut" id="link-community" type="button" aria-label="Buksan ang Community chat" aria-expanded="false" data-user-id="<?= (int) $_SESSION['user_id'] ?>">
        <svg class="community-shortcut-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3C6.48 3 2 6.69 2 11.25c0 2.26 1.1 4.3 2.88 5.76L4 21l4.62-2.31c1.06.34 2.2.52 3.38.52 5.52 0 10-3.69 10-8.25S17.52 3 12 3Z"></path>
            <path class="community-shortcut-icon-detail" d="M7.5 11h9M7.5 14h6"></path>
        </svg>
        <span>Community</span>
        <span class="community-unread-dot" data-community-unread-dot aria-label="May bagong mensahe" hidden></span>
    </button>
    <section class="community-chat-panel" id="communityChatPanel" aria-label="Community chat" hidden>
        <header class="community-chat-header">
            <img class="community-chat-logo" src="images/logo.jpg" alt="Alawihao Health Center logo">
            <div>
                <strong>Alawihao Health Center</strong>
                <span>Community chat</span>
            </div>
            <button type="button" id="communityChatClose" aria-label="Isara ang Community chat">&times;</button>
        </header>
        <div class="community-chat-messages" id="communityChatMessages" role="log" aria-live="polite">
            <p class="community-chat-empty" id="communityChatEmpty">Wala pang mensahe. Maging magalang at huwag magbahagi ng sensitibong detalye sa kalusugan o personal na impormasyon dito.</p>
        </div>
        <p class="community-chat-status" id="communityChatStatus" role="status"></p>
        <form class="community-chat-form" id="communityChatForm">
            <label class="sr-only" for="communityChatInput">Isulat ang mensahe sa Community</label>
            <input id="communityChatInput" type="text" maxlength="1000" autocomplete="off" placeholder="Isulat ang mensahe..." required>
            <button type="submit" aria-label="Ipadala ang mensahe">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7Z"></path></svg>
            </button>
        </form>
    </section>
    <dialog class="community-profile-dialog" id="communityProfileDialog" aria-labelledby="communityProfileName">
        <button class="community-profile-close" id="communityProfileClose" type="button" aria-label="Isara ang profile">&times;</button>
        <img class="community-profile-photo" id="communityProfilePhoto" alt="" hidden>
        <span class="community-profile-fallback" id="communityProfileFallback" aria-hidden="true"></span>
        <strong id="communityProfileName"></strong>
        <span id="communityProfileRole"></span>
        <p>Community member</p>
    </dialog>
    <?php endif; ?>
    <button class="user-chatbot-launcher" id="userChatbotLauncher" type="button" aria-label="Buksan ang Health Guide" aria-expanded="false">
        <i class="fa-solid fa-message" aria-hidden="true"></i>
                <span>AI Guide</span>
    </button>
    <section class="user-chatbot-panel" id="userChatbotPanel" aria-label="Health and Portal Guide" data-profile-name="<?= htmlspecialchars($chatbotProfileLabel, ENT_QUOTES, 'UTF-8') ?>" data-profile-picture="<?= htmlspecialchars($chatbotProfile['picture'], ENT_QUOTES, 'UTF-8') ?>" data-profile-initial="<?= htmlspecialchars($chatbotProfileInitial, ENT_QUOTES, 'UTF-8') ?>" hidden>
        <header class="user-chatbot-header">
            <img class="user-chatbot-logo" src="images/logo.jpg" alt="Alawihao Health Center logo">
            <div>
                <strong>Alawihao Health &amp; Portal Guide</strong>
                <span>Ask about registration, schedules, records, and portal features</span>
            </div>
            <button type="button" id="userChatbotClose" aria-label="Isara ang chat">&times;</button>
        </header>
        <div class="user-chatbot-notice">Pangkalahatang impormasyon lang ito. Huwag magpadala ng pangalan, contact details, o personal na health information.</div>
        <div class="user-chatbot-messages" id="userChatbotMessages" aria-live="polite">
            <div class="user-chatbot-message-row assistant">
                <img class="user-chatbot-avatar assistant-avatar" src="images/logo.jpg" alt="Alawihao Health and Portal Guide">
                <div class="user-chatbot-message assistant">Kumusta! Itanong kung saan makikita ang registration, schedule, records, account settings, o health articles at gagabayan kita sa portal.</div>
            </div>
        </div>
        <div class="user-chatbot-prompts" id="userChatbotPrompts">
            <button type="button" data-question="Saan ako mag-register ng maternal o child?">Registration</button>
            <button type="button" data-question="Paano mag-register ng panibagong pregnancy gamit ang saved personal details?">Another case</button>
            <button type="button" data-question="Paano ako magche-check ng schedule?">Schedule</button>
            <button type="button" data-question="Paano ko kakanselahin ang pending reschedule request?">Cancel reschedule</button>
            <button type="button" data-question="Saan ko makikita ang maternal at child records?">My Records</button>
            <button type="button" data-question="Saan ko babaguhin ang account settings?">Settings</button>
            <button type="button" data-question="Saan ako makakabasa tungkol sa bakuna?">Bakuna</button>
            <button type="button" data-question="Saan ko makikita ang health articles?">Health articles</button>
            <button type="button" data-question="Ano ang PHIC CAT sa registration form?">PHIC CAT</button>
            <button type="button" data-question="Ano ang LMP?">LMP</button>
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
    .user-community-shortcut { position: absolute; right: 0; bottom: calc(100% + 10px); text-decoration: none; white-space: nowrap; }
    .user-community-shortcut .community-shortcut-icon { width: 17px; height: 17px; fill: currentColor; }
    .user-community-shortcut .community-shortcut-icon-detail { fill: none; stroke: #5A6B47; stroke-width: 1.7; stroke-linecap: round; }
    .user-community-shortcut .community-unread-dot { position: absolute; top: 2px; right: 2px; width: 9px; height: 9px; border-radius: 50%; background: #d93025; box-shadow: 0 0 0 2px #5A6B47; }
    .user-community-shortcut .community-unread-dot[hidden] { display: none; }
    .community-chat-panel { position: absolute; right: 0; bottom: 106px; display: flex; flex-direction: column; width: min(370px, calc(100vw - 28px)); height: min(540px, calc(100vh - 154px)); min-height: 300px; background: #fff; border: 1px solid #dfe5da; border-radius: 16px; overflow: hidden; box-shadow: 0 12px 40px #17200f38; }
    .community-chat-panel[hidden] { display: none; }
    .community-chat-header { display: flex; align-items: center; gap: 10px; padding: 13px 15px; color: #fff; background: #5A6B47; }
    .community-chat-header div { display: flex; flex: 1; flex-direction: column; gap: 3px; }
    .community-chat-header strong { font-size: 13px; }
    .community-chat-header div span { font-size: 11px; opacity: .88; }
    .community-chat-header > button { border: 0; background: transparent; color: #fff; font-size: 25px; line-height: 1; cursor: pointer; }
    .community-chat-logo { width: 36px; height: 36px; flex: 0 0 36px; border: 1px solid #ffffff80; border-radius: 50%; object-fit: cover; background: #fff; }
    .community-chat-messages { display: flex; flex: 1; flex-direction: column; gap: 10px; padding: 12px; overflow-y: auto; background: #fbfcf9; }
    .community-chat-empty { margin: auto; max-width: 290px; color: #68756c; font-size: 12px; line-height: 1.5; text-align: center; }
    .community-chat-message { display: flex; max-width: 88%; flex-direction: column; align-self: flex-start; gap: 3px; }
    .community-chat-message.mine { align-self: flex-end; }
    .community-chat-line { display: flex; align-items: flex-end; gap: 6px; }
    .community-chat-message.mine .community-chat-line { flex-direction: row-reverse; }
    .community-chat-avatar-trigger { position: relative; width: 28px; height: 28px; flex: 0 0 28px; overflow: hidden; padding: 0; border: 0; border-radius: 50%; background: #e4efdd; color: #435248; cursor: pointer; }
    .community-chat-avatar-trigger img { position: absolute; inset: 0; display: block; width: 100%; height: 100%; object-fit: cover; }
    .community-chat-avatar-trigger .community-chat-avatar-fallback { display: grid; width: 100%; height: 100%; place-items: center; font-size: 12px; font-weight: 700; }
    .community-chat-content { min-width: 0; }
    .community-chat-meta { display: flex; flex-wrap: wrap; gap: 5px; margin: 0 4px; color: #68756c; font-size: 10px; }
    .community-chat-meta .community-chat-profile-link { padding: 0; border: 0; background: none; color: #435248; font: inherit; font-weight: 700; cursor: pointer; }
    .community-chat-bubble { padding: 8px 11px; border-radius: 13px 13px 13px 3px; background: #eef3e9; font-size: 13px; line-height: 1.4; overflow-wrap: anywhere; white-space: pre-wrap; }
    .community-chat-message.mine .community-chat-bubble { border-radius: 13px 13px 3px 13px; background: #e4efdd; }
    .community-chat-status { min-height: 0; margin: 0; padding: 0 12px; color: #a33a32; font-size: 11px; }
    .community-chat-status:not(:empty) { padding-top: 6px; }
    .community-chat-form { display: flex; align-items: center; gap: 8px; padding: 9px 10px; border-top: 1px solid #e8ece3; }
    .community-chat-form input { flex: 1; min-width: 0; border: 1px solid #d8ded2; border-radius: 20px; padding: 10px 13px; outline: none; font-size: 13px; }
    .community-chat-form input:focus { border-color: #6b8e55; box-shadow: 0 0 0 2px #6b8e5525; }
    .community-chat-form button { width: 40px; height: 40px; flex: 0 0 40px; border: 0; border-radius: 50%; color: #fff; background: #5A6B47; cursor: pointer; }
    .community-chat-form button svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .community-chat-form button:disabled { opacity: .6; cursor: wait; }
    .community-profile-dialog { position: fixed; inset: 50% auto auto 50%; width: min(300px, calc(100vw - 32px)); margin: 0; padding: 28px 22px 22px; transform: translate(-50%, -50%); border: 1px solid #dfe5da; border-radius: 16px; color: #263126; text-align: center; box-shadow: 0 12px 40px #17200f38; }
    .community-profile-dialog::backdrop { background: #17200f66; }
    .community-profile-close { position: absolute; top: 8px; right: 12px; border: 0; background: transparent; color: #68756c; font-size: 24px; cursor: pointer; }
    .community-profile-photo, .community-profile-fallback { width: 88px; height: 88px; margin: 0 auto 12px; border-radius: 50%; object-fit: cover; }
    .community-profile-fallback { display: grid; place-items: center; background: #e4efdd; color: #435248; font-size: 32px; font-weight: 700; }
    .community-profile-photo[hidden], .community-profile-fallback[hidden] { display: none; }
    .community-profile-dialog > strong, .community-profile-dialog > span:not(.community-profile-fallback) { display: block; }
    .community-profile-dialog > span:not(.community-profile-fallback) { margin-top: 5px; color: #68756c; font-size: 13px; }
    .community-profile-dialog p { margin: 14px 0 0; color: #68756c; font-size: 12px; }
    .user-chatbot-launcher { display: flex; align-items: center; gap: 9px; border: 0; border-radius: 28px; padding: 13px 18px; background: #5A6B47; color: #fff; box-shadow: 0 5px 20px #0003; font: 600 14px "Segoe UI", Arial, sans-serif; cursor: pointer; }
    .user-chatbot-launcher:hover { background: #465638; }
    .user-chatbot-launcher i { font-size: 17px; }
    .user-chatbot-panel { position: absolute; right: 0; bottom: 62px; display: flex; flex-direction: column; width: min(370px, calc(100vw - 28px)); height: min(540px, calc(100vh - 110px)); background: #fff; border: 1px solid #dfe5da; border-radius: 16px; overflow: hidden; box-shadow: 0 12px 40px #17200f38; }
    .user-chatbot.has-community-shortcut .user-chatbot-panel { bottom: 106px; }
    .user-chatbot-panel[hidden] { display: none; }
    .user-chatbot-header { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 13px 15px; color: #fff; background: #5A6B47; }
    .user-chatbot-logo { width: 36px; height: 36px; flex: 0 0 36px; border: 1px solid #ffffff80; border-radius: 50%; object-fit: cover; background: #fff; }
    .user-chatbot-header div { display: flex; flex-direction: column; gap: 3px; }
    .user-chatbot-header strong { font-size: 14px; }
    .user-chatbot-header span { font-size: 11px; opacity: .88; }
    .user-chatbot-header button { border: 0; background: transparent; color: #fff; font-size: 25px; line-height: 1; cursor: pointer; }
    .user-chatbot-notice { padding: 9px 13px; background: #f5f7f1; color: #59614f; font-size: 11px; line-height: 1.4; border-bottom: 1px solid #e8ece3; }
    .user-chatbot-messages { display: flex; flex: 1; flex-direction: column; gap: 10px; padding: 14px; overflow-y: auto; background: #fbfcf9; }
    .user-chatbot-message-row { display: flex; align-items: flex-end; gap: 7px; max-width: 100%; }
    .user-chatbot-message-row.user { align-self: flex-end; flex-direction: row-reverse; }
    .user-chatbot-avatar { width: 30px; height: 30px; flex: 0 0 30px; border-radius: 50%; object-fit: cover; }
    .user-chatbot-avatar.assistant-avatar { border: 1px solid #dfe5da; background: #fff; }
    .user-chatbot-profile-link { display: grid; overflow: hidden; place-items: center; background: #e4efdd; color: #435248; font-size: 13px; font-weight: 700; text-decoration: none; }
    .user-chatbot-profile-link img { display: block; width: 100%; height: 100%; object-fit: cover; }
    .user-chatbot-message { max-width: 88%; padding: 10px 12px; border-radius: 12px; font-size: 13px; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; }
    .user-chatbot-message-row .user-chatbot-message { max-width: calc(100% - 37px); }
    .user-chatbot-message.assistant { background: #eef3e9; }
    .user-chatbot-message.user { color: #fff; background: #5A6B47; }
    .user-chatbot-message a { color: #35572b; font-weight: 600; }
    .user-chatbot-prompts { display: flex; flex-wrap: wrap; gap: 6px; padding: 0 12px 10px; background: #fbfcf9; }
    .user-chatbot-prompts button { border: 1px solid #ccd7c2; border-radius: 16px; padding: 6px 9px; color: #4c603e; background: #fff; font-size: 11px; cursor: pointer; }
    .user-chatbot-form { display: flex; gap: 8px; padding: 10px 12px; border-top: 1px solid #e8ece3; }
    .user-chatbot-form input { flex: 1; min-width: 0; border: 1px solid #d8ded2; border-radius: 20px; padding: 10px 13px; outline: none; font-size: 13px; }
    .user-chatbot-form input:focus { border-color: #6b8e55; box-shadow: 0 0 0 2px #6b8e5525; }
    .user-chatbot-form button { width: 40px; height: 40px; border: 0; border-radius: 50%; color: #fff; background: #5A6B47; cursor: pointer; }
    .user-chatbot-form button:disabled { opacity: .6; cursor: wait; }
    .user-chatbot .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }






    @media (max-width: 480px) {
        .user-chatbot { right: 12px; bottom: 12px; }
        .user-chatbot-panel { bottom: 60px; height: min(540px, calc(100vh - 90px)); }
        .user-chatbot.has-community-shortcut .user-chatbot-panel,
        .community-chat-panel { bottom: 104px; height: min(540px, calc(100vh - 134px)); }
    }
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
    const communityLauncher = document.getElementById('link-community');
    const communityPanel = document.getElementById('communityChatPanel');

    if (communityLauncher && communityPanel) {
        const communityClose = document.getElementById('communityChatClose');
        const communityForm = document.getElementById('communityChatForm');
        const communityInput = document.getElementById('communityChatInput');
        const communityMessages = document.getElementById('communityChatMessages');
        const communityEmpty = document.getElementById('communityChatEmpty');
        const communityStatus = document.getElementById('communityChatStatus');
        const communitySend = communityForm.querySelector('button');
        const communityUnread = communityLauncher.querySelector('[data-community-unread-dot]');
        const profileDialog = document.getElementById('communityProfileDialog');
        const profilePhoto = document.getElementById('communityProfilePhoto');
        const profileFallback = document.getElementById('communityProfileFallback');
        const profileName = document.getElementById('communityProfileName');
        const profileRole = document.getElementById('communityProfileRole');
        const communityCsrf = root.dataset.communityCsrf;
        const communityStorageKey = 'community_chat_last_seen_' + communityLauncher.dataset.userId;
        let lastCommunityMessageId = 0;
        let isLoadingCommunityMessages = false;

        function setCommunityOpen(open) {
            communityPanel.hidden = !open;
            communityLauncher.setAttribute('aria-expanded', String(open));
            if (open) {
                setOpen(false);
                loadCommunityMessages();
                communityInput.focus();
            }
        }

        function showCommunityProfile(message) {
            profileName.textContent = message.name;
            profileRole.textContent = message.role;
            profileFallback.textContent = message.name.trim().charAt(0).toUpperCase() || '?';
            profileFallback.hidden = Boolean(message.profile_picture);
            profilePhoto.hidden = !message.profile_picture;
            profilePhoto.removeAttribute('src');
            profilePhoto.onerror = () => {
                profilePhoto.hidden = true;
                profileFallback.hidden = false;
            };
            if (message.profile_picture) profilePhoto.src = message.profile_picture;
            profileDialog.showModal();
        }

        function createCommunityAvatar(message) {
            const avatar = document.createElement('button');
            avatar.type = 'button';
            avatar.className = 'community-chat-avatar-trigger';
            avatar.setAttribute('aria-label', 'Tingnan ang profile ni ' + message.name);
            const fallback = document.createElement('span');
            fallback.className = 'community-chat-avatar-fallback';
            fallback.textContent = message.name.trim().charAt(0).toUpperCase() || '?';
            avatar.appendChild(fallback);
            if (message.profile_picture) {
                const image = document.createElement('img');
                image.src = message.profile_picture;
                image.alt = '';
                image.onerror = () => image.remove();
                avatar.appendChild(image);
            }
            avatar.addEventListener('click', () => showCommunityProfile(message));
            return avatar;
        }

        function appendCommunityMessage(message) {
            if (communityEmpty) communityEmpty.remove();
            const shouldScroll = communityMessages.scrollHeight - communityMessages.scrollTop - communityMessages.clientHeight < 80;
            const wrapper = document.createElement('article');
            wrapper.className = 'community-chat-message' + (Number(message.user_id) === Number(communityLauncher.dataset.userId) ? ' mine' : '');

            const line = document.createElement('div');
            line.className = 'community-chat-line';
            const avatar = createCommunityAvatar(message);
            const content = document.createElement('div');
            content.className = 'community-chat-content';
            const meta = document.createElement('div');
            meta.className = 'community-chat-meta';
            const name = document.createElement('strong');
            const profileLink = document.createElement('button');
            profileLink.type = 'button';
            profileLink.className = 'community-chat-profile-link';
            profileLink.textContent = message.name;
            profileLink.setAttribute('aria-label', 'Tingnan ang profile ni ' + message.name);
            profileLink.addEventListener('click', () => showCommunityProfile(message));
            name.appendChild(profileLink);
            const role = document.createElement('span');
            role.textContent = message.role;
            const time = document.createElement('time');
            time.textContent = message.created_at;
            meta.append(name, role, time);

            const bubble = document.createElement('div');
            bubble.className = 'community-chat-bubble';
            bubble.textContent = message.message;
            content.append(meta, bubble);
            line.append(avatar, content);
            wrapper.appendChild(line);
            communityMessages.appendChild(wrapper);
            if (shouldScroll) communityMessages.scrollTop = communityMessages.scrollHeight;
        }

        async function loadCommunityMessages() {
            if (isLoadingCommunityMessages || communityPanel.hidden) return;
            isLoadingCommunityMessages = true;
            try {
                const response = await fetch('community_chat_api.php?after_id=' + lastCommunityMessageId, {
                    credentials: 'same-origin',
                    cache: 'no-store'
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Hindi ma-load ang mga mensahe.');
                }
                data.messages.forEach((message) => {
                    appendCommunityMessage(message);
                    lastCommunityMessageId = Math.max(lastCommunityMessageId, Number(message.id));
                });
                if (data.messages.length > 0) {
                    try {
                        localStorage.setItem(communityStorageKey, String(lastCommunityMessageId));
                    } catch (error) {
                        // Keep the chat usable when browser storage is unavailable.
                    }
                    communityUnread.hidden = true;
                }
                communityStatus.textContent = '';
            } catch (error) {
                communityStatus.textContent = error.message || 'Hindi ma-load ang mga mensahe. Subukan ulit mamaya.';
            } finally {
                isLoadingCommunityMessages = false;
            }
        }

        communityLauncher.addEventListener('click', () => setCommunityOpen(communityPanel.hidden));
        communityClose.addEventListener('click', () => setCommunityOpen(false));
        document.getElementById('communityProfileClose').addEventListener('click', () => profileDialog.close());
        communityForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            communityStatus.textContent = '';
            communitySend.disabled = true;
            try {
                const response = await fetch('community_chat_api.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': communityCsrf },
                    body: JSON.stringify({ message: communityInput.value })
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Hindi naipadala ang mensahe.');
                }
                communityInput.value = '';
                await loadCommunityMessages();
            } catch (error) {
                communityStatus.textContent = error.message || 'Hindi naipadala ang mensahe. Subukan ulit mamaya.';
            } finally {
                communitySend.disabled = false;
                communityInput.focus();
            }
        });
        window.setInterval(loadCommunityMessages, 5000);
    }

    function setOpen(open) {
        panel.hidden = !open;
        launcher.setAttribute('aria-expanded', String(open));
        if (open) input.focus();
    }

    function addMessage(text, kind, links) {
        const row = document.createElement('div');
        row.className = `user-chatbot-message-row ${kind}`;
        const avatar = kind === 'assistant'
            ? document.createElement('img')
            : document.createElement('a');
        avatar.className = `user-chatbot-avatar ${kind === 'assistant' ? 'assistant-avatar' : 'user-chatbot-profile-link'}`;
        if (kind === 'assistant') {
            avatar.src = 'images/logo.jpg';
            avatar.alt = 'Alawihao Health Assistant';
        } else {
            avatar.href = 'user_profile.php';
            avatar.setAttribute('aria-label', `Tingnan ang profile ni ${panel.dataset.profileName}`);
            if (panel.dataset.profilePicture) {
                const profileImage = document.createElement('img');
                profileImage.src = panel.dataset.profilePicture;
                profileImage.alt = '';
                profileImage.onerror = () => {
                    avatar.textContent = panel.dataset.profileInitial;
                };
                avatar.appendChild(profileImage);
            } else {
                avatar.textContent = panel.dataset.profileInitial;
            }
        }

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

    launcher.addEventListener('click', () => {
        if (communityPanel) {
            communityPanel.hidden = true;
            communityLauncher.setAttribute('aria-expanded', 'false');
        }
        setOpen(panel.hidden);
    });
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
