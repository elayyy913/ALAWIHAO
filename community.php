<?php
session_start();
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/community_chat_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    $communityMember = communityChatCurrentMember($conn);
    if ($communityMember === null) {
        http_response_code(403);
        $communityAccessDenied = true;
    } else {
        ensureCommunityChatTable($conn);
        $communityAccessDenied = false;
        $_SESSION['community_chat_csrf'] ??= bin2hex(random_bytes(32));
    }
} catch (RuntimeException $error) {
    error_log($error->getMessage());
    http_response_code(500);
    $communityLoadError = true;
}
?>
<!DOCTYPE html>
<html lang="fil">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community | Alawihao Health Center</title>
    <style>
        :root { --community-green: #6b8e55; --community-ink: #26342b; --community-muted: #68756c; --sidebar-width: 280px; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f5f7f3; color: var(--community-ink); font-family: Arial, sans-serif; }

        /* Main area — inherits sidebar margin from admin_sidebar.php via .main-content */
        .community-main {
            min-height: 100vh;
            padding: 28px 28px 28px;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        /* Fill all available space — no artificial max-width */
        .community-wrap { width: 100%; height: 100%; }
        .community-heading { margin-bottom: 18px; }
        .community-heading h1 { margin: 0 0 6px; font-size: 1.7rem; }
        body.sidebar-closed .community-heading,
        .sidebar.is-hidden ~ .community-main .community-heading { padding-left: 44px; }

        /* Card fills the remaining viewport height */
        .community-card {
            height: calc(100vh - 140px);
            min-height: 440px;
            display: flex;
            flex-direction: column;
            background: white;
            border: 1px solid #e2e8df;
            border-radius: 14px;
            box-shadow: 0 8px 26px rgba(37, 54, 39, .07);
            overflow: hidden;
        }
        .community-card-head { padding: 16px 20px; border-bottom: 1px solid #e7ece4; display: flex; align-items: center; gap: 12px; }
        .community-logo { width: 42px; height: 42px; flex: 0 0 42px; border: 1px solid #e2e8df; border-radius: 50%; object-fit: cover; }
        .community-card-head strong { display: block; }
        .community-card-head small { display: block; margin-top: 3px; color: var(--community-muted); }
        .community-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 14px; }
        .community-empty { margin: auto; max-width: 360px; color: var(--community-muted); text-align: center; line-height: 1.5; }
        .community-message { max-width: min(72%, 680px); align-self: flex-start; }
        .community-message.mine { align-self: flex-end; }
        .community-message-content { min-width: 0; }
        .community-message-line { display: flex; align-items: flex-end; gap: 7px; }
        .community-message.mine .community-message-line { flex-direction: row-reverse; }
        .community-avatar-trigger { position: relative; width: 34px; height: 34px; flex: 0 0 34px; overflow: hidden; padding: 0; border: 0; border-radius: 50%; background: #e4efdd; color: #435248; cursor: pointer; }
        .community-avatar-trigger img { position: absolute; inset: 0; display: block; width: 100%; height: 100%; object-fit: cover; }
        .community-avatar-fallback { display: grid; width: 100%; height: 100%; place-items: center; font-weight: 700; }
        .community-message-content { min-width: 0; }
        .community-meta { display: flex; flex-wrap: wrap; gap: 8px; align-items: baseline; margin: 0 4px 5px; font-size: .78rem; color: var(--community-muted); }
        .community-meta .community-profile-link { padding: 0; border: 0; background: none; color: #435248; font: inherit; font-weight: 700; cursor: pointer; }
        .community-bubble { padding: 10px 13px; border-radius: 13px 13px 13px 3px; background: #f0f4ed; line-height: 1.45; overflow-wrap: anywhere; white-space: pre-wrap; }
        .community-message.mine .community-bubble { background: #e4efdd; border-radius: 13px 13px 3px 13px; }
        .community-compose { padding: 14px 16px; border-top: 1px solid #e7ece4; }
        .community-form { display: flex; align-items: flex-end; gap: 10px; }
        .community-form textarea { width: 100%; min-height: 46px; max-height: 130px; resize: vertical; padding: 12px; border: 1px solid #cfd9cb; border-radius: 9px; font: inherit; }
        .community-form textarea:focus { outline: 2px solid #bad0ad; border-color: var(--community-green); }
        .community-form button { min-height: 44px; padding: 0 19px; border: 0; border-radius: 9px; background: var(--community-green); color: white; font-weight: 700; cursor: pointer; }
        .community-form button:disabled { opacity: .6; cursor: wait; }
        .community-help { display: flex; justify-content: space-between; gap: 10px; margin-top: 8px; color: var(--community-muted); font-size: .78rem; }
        .community-status { min-height: 20px; margin: 7px 2px 0; color: #a33a32; font-size: .85rem; }
        .community-notice { padding: 22px; background: #fff; border: 1px solid #e2e8df; border-radius: 12px; line-height: 1.5; }
        .community-profile-dialog { position: fixed; inset: 50% auto auto 50%; width: min(300px, calc(100vw - 32px)); margin: 0; padding: 28px 22px 22px; transform: translate(-50%, -50%); border: 1px solid #dfe5da; border-radius: 16px; color: #263126; text-align: center; box-shadow: 0 12px 40px #17200f38; }
        .community-profile-dialog::backdrop { background: #17200f66; }
        .community-profile-close { position: absolute; top: 8px; right: 12px; border: 0; background: transparent; color: #68756c; font-size: 24px; cursor: pointer; }
        .community-profile-photo, .community-profile-fallback { width: 88px; height: 88px; margin: 0 auto 12px; border-radius: 50%; object-fit: cover; }
        .community-profile-fallback { display: grid; place-items: center; background: #e4efdd; color: #435248; font-size: 32px; font-weight: 700; }
        .community-profile-photo[hidden], .community-profile-fallback[hidden] { display: none; }
        .community-profile-dialog > strong, .community-profile-dialog > span:not(.community-profile-fallback) { display: block; }
        .community-profile-dialog > span:not(.community-profile-fallback) { margin-top: 5px; color: #68756c; font-size: 13px; }
        .community-profile-dialog p { margin: 14px 0 0; color: #68756c; font-size: 12px; }
        @media (max-width: 700px) {
            .community-main { padding: 18px 12px; }
            .community-card { height: calc(100vh - 130px); min-height: 360px; }
            .community-message { max-width: 90%; }
            .community-form { align-items: stretch; flex-direction: column; }
            .community-form button { align-self: flex-end; }
        }
    </style>
</head>
<body>
<?php
if (isset($communityMember) && $communityMember !== null) {
    if (in_array($communityMember['role'], ['Admin', 'Super Admin'], true)) {
        include __DIR__ . '/admin/admin_sidebar.php';
    } else {
        include __DIR__ . '/user_sidebar.php';
    }
}
?>
<main class="main-content community-main">
    <div class="community-wrap">
        <header class="community-heading">
            <h1>Community</h1>
        </header>
        <?php if (!empty($communityAccessDenied)): ?>
            <div class="community-notice" role="alert">Para lamang ito sa Admin, Super Admin, at mga user na may approved maternal registration o approved child record.</div>
        <?php elseif (!empty($communityLoadError)): ?>
            <div class="community-notice" role="alert">Hindi muna ma-access ang Community. Subukan ulit mamaya.</div>
        <?php else: ?>
            <section class="community-card" aria-label="Community group chat">
                <div class="community-card-head">
                    <img class="community-logo" src="images/logo.jpg" alt="Alawihao Health Center logo">
                    <div><strong>Alawihao Health Center</strong><small>Shared community chat</small></div>
                </div>
                <div class="community-messages" id="communityMessages" role="log" aria-live="polite">
                    <p class="community-empty" id="communityEmpty">Wala pang mensahe. Maging magalang at huwag magbahagi ng sensitibong detalye sa kalusugan o personal na impormasyon dito.</p>
                </div>
                <div class="community-compose">
                    <form class="community-form" id="communityForm">
                        <textarea id="communityMessage" name="message" maxlength="1000" placeholder="Isulat ang mensahe..." aria-label="Mensahe" required></textarea>
                        <button id="communitySend" type="submit">Send</button>
                    </form>
                    <p class="community-status" id="communityStatus" role="status"></p>
                </div>
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
    </div>
</main>
<?php if (empty($communityAccessDenied) && empty($communityLoadError)): ?>
<script>
(() => {
    const apiUrl = 'community_chat_api.php';
    const csrfToken = <?= json_encode($_SESSION['community_chat_csrf'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const currentUserId = <?= (int) $communityMember['id'] ?>;
    const messagesEl = document.getElementById('communityMessages');
    const emptyEl = document.getElementById('communityEmpty');
    const form = document.getElementById('communityForm');
    const input = document.getElementById('communityMessage');
    const sendButton = document.getElementById('communitySend');
    const statusEl = document.getElementById('communityStatus');
    const profileDialog = document.getElementById('communityProfileDialog');
    const profilePhoto = document.getElementById('communityProfilePhoto');
    const profileFallback = document.getElementById('communityProfileFallback');
    const profileName = document.getElementById('communityProfileName');
    const profileRole = document.getElementById('communityProfileRole');
    let lastMessageId = 0;
    let isLoading = false;

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
        avatar.className = 'community-avatar-trigger';
        avatar.setAttribute('aria-label', 'Tingnan ang profile ni ' + message.name);
        const fallback = document.createElement('span');
        fallback.className = 'community-avatar-fallback';
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

    function appendMessage(message) {
        if (emptyEl) emptyEl.remove();
        const shouldScroll = messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 80;
        const wrapper = document.createElement('article');
        wrapper.className = 'community-message' + (Number(message.user_id) === currentUserId ? ' mine' : '');

        const line = document.createElement('div');
        line.className = 'community-message-line';
        const avatar = createCommunityAvatar(message);
        const content = document.createElement('div');
        content.className = 'community-message-content';
        const meta = document.createElement('div');
        meta.className = 'community-meta';
        const name = document.createElement('strong');
        const profileLink = document.createElement('button');
        profileLink.type = 'button';
        profileLink.className = 'community-profile-link';
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
        bubble.className = 'community-bubble';
        bubble.textContent = message.message;
        content.append(meta, bubble);
        line.append(avatar, content);
        wrapper.appendChild(line);
        messagesEl.appendChild(wrapper);
        if (shouldScroll) messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    async function loadMessages() {
        if (isLoading) return;
        isLoading = true;
        try {
            const response = await fetch(apiUrl + '?after_id=' + lastMessageId, { credentials: 'same-origin', cache: 'no-store' });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Hindi ma-load ang mga mensahe.');
            data.messages.forEach((message) => {
                appendMessage(message);
                lastMessageId = Math.max(lastMessageId, Number(message.id));
            });
            if (data.messages.length > 0) {
                try {
                    localStorage.setItem('community_chat_last_seen_' + currentUserId, String(lastMessageId));
                } catch (error) {
                    // Chat remains usable when browser storage is unavailable.
                }
            }
        } catch (error) {
            statusEl.textContent = error.message || 'Hindi ma-load ang mga mensahe. Subukan ulit mamaya.';
        } finally {
            isLoading = false;
        }
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        statusEl.textContent = '';
        sendButton.disabled = true;
        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ message: input.value })
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Hindi naipadala ang mensahe.');
            input.value = '';
            await loadMessages();
        } catch (error) {
            statusEl.textContent = error.message || 'Hindi naipadala ang mensahe. Subukan ulit mamaya.';
        } finally {
            sendButton.disabled = false;
            input.focus();
        }
    });

    document.getElementById('communityProfileClose').addEventListener('click', () => profileDialog.close());
    loadMessages();
    window.setInterval(loadMessages, 5000);
})();
</script>
<?php endif; ?>
</body>
</html>