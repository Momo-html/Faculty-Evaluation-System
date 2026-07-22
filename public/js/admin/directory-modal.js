function openDirectoryModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.showModal();
    document.body.classList.add("directory-modal-open");
}

function closeDirectoryModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.close();
}

document.addEventListener("click", (event) => {
    const modal = event.target.closest("dialog.directory-modal");
    if (modal && event.target === modal) modal.close();
});

document.addEventListener("close", (event) => {
    if (event.target.matches?.("dialog.directory-modal")) {
        document.body.classList.remove("directory-modal-open");
    }
}, true);
