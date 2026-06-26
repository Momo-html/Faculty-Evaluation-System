function searchTable() {
    const input =
        document.getElementById("userSearch")?.value.toUpperCase() || "";
    const deptFilter =
        document.getElementById("tableDeptFilter")?.value || "All";
    const rows = document.querySelectorAll("#userTable tbody tr");

    rows.forEach((row) => {
        const text = row.innerText.toUpperCase();
        const rowDept = row.getAttribute("data-dept") || "";

        const matchesSearch = text.includes(input);
        const matchesDept = deptFilter === "All" || rowDept === deptFilter;

        row.style.display = matchesSearch && matchesDept ? "" : "none";
    });
}

function toggleQuickAdd() {
    const container = document.getElementById("quickAddContainer");
    if (!container) return;
    const isHidden = window.getComputedStyle(container).display === "none";
    container.style.display = isHidden ? "block" : "none";
    if (isHidden) {
        setTimeout(
            () =>
                (
                    document.getElementById("facId") ||
                    document.getElementById("stuId")
                )?.focus(),
            10,
        );
    }
}

async function handleCSVUpload(input) {
    const file = input.files[0];
    if (!file?.name.endsWith(".csv")) return alert("Upload a valid CSV.");

    const formData = new FormData();
    formData.append("csv_file", file);

    if (await API.request("/admin/import-users", "POST", formData)) {
        alert("Data imported successfully!");
        location.reload();
    }
    input.value = "";
}
