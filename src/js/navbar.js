// js/navbar.js
function toggleSettings() {
    const settingsMenu = document.getElementById('settingsMenu');
    if (!settingsMenu) return;
    const isHidden = settingsMenu.style.display === 'none' || !settingsMenu.style.display;
    settingsMenu.style.display = isHidden ? 'block' : 'none';
}

function confirmDelete() {
    const confirmation = prompt("Type CONFIRM to delete your account:");
    if (confirmation === "CONFIRM") {
        const form = document.getElementById('deleteForm');
        if (form) form.submit();
    }
}
