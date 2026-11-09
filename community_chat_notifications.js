(() => {
    const communityLink = document.getElementById('link-community');
    const unreadDot = document.querySelector('[data-community-unread-dot]');
    if (!communityLink || !unreadDot) return;

    const userId = communityLink.dataset.userId;
    const storageKey = 'community_chat_last_seen_' + userId;
    const isCommunityPage = window.location.pathname.endsWith('/community.php');
    const scriptUrl = document.currentScript && document.currentScript.src;
    const apiUrl = scriptUrl
        ? new URL('community_chat_api.php', scriptUrl)
        : new URL('community_chat_api.php', window.location.href);
    let hasInitialised = false;
    let isChecking = false;

    function readLastSeen() {
        try {
            const value = localStorage.getItem(storageKey);
            return value !== null && /^\d+$/.test(value) ? value : null;
        } catch (error) {
            return null;
        }
    }

    function saveLastSeen(id) {
        try {
            localStorage.setItem(storageKey, String(id));
        } catch (error) {
            return false;
        }
        return true;
    }

    async function checkForNewMessages() {
        if (isChecking) return;
        isChecking = true;
        try {
            const lastSeen = readLastSeen();
            const cursor = lastSeen === null ? 0 : lastSeen;
            apiUrl.search = new URLSearchParams({ after_id: cursor }).toString();
            const response = await fetch(apiUrl, { credentials: 'same-origin', cache: 'no-store' });
            const data = await response.json();
            if (!response.ok || !data.success) return;

            if (!hasInitialised && lastSeen === null) {
                if (data.messages.length > 0) {
                    saveLastSeen(data.messages[data.messages.length - 1].id);
                }
                hasInitialised = true;
                return;
            }

            hasInitialised = true;
            if (data.messages.length > 0) {
                if (isCommunityPage) {
                    saveLastSeen(data.messages[data.messages.length - 1].id);
                    unreadDot.hidden = true;
                } else {
                    unreadDot.hidden = false;
                }
            }
        } catch (error) {
            // Keep the sidebar available if the chat API is temporarily unreachable.
        } finally {
            isChecking = false;
        }
    }

    checkForNewMessages();
    window.setInterval(checkForNewMessages, 10000);
})();