/**
 * core.js - Global API & UI Initialization
 */
const API = {
    getCsrf: () => document.querySelector('meta[name="csrf-token"]')?.content,

    // Centralized Request Handler
    async request(url, method = "POST", data = null) {
        const options = {
            method,
            headers: {
                "X-CSRF-TOKEN": this.getCsrf(),
                Accept: "application/json",
            },
        };

        if (data && !(data instanceof FormData)) {
            options.headers["Content-Type"] = "application/json";
            options.body = JSON.stringify(data);
        } else if (data) {
            options.body = data;
        }

        try {
            const response = await fetch(url, options);
            const result = await response.json();
            if (!response.ok)
                throw new Error(result.message || "Action failed");
            return result;
        } catch (err) {
            alert(err.message);
            console.error(`API Error [${url}]:`, err);
            return null;
        }
    },
};

document.addEventListener("DOMContentLoaded", () => {
    // QR Generation for the Dashboard
    const qrEl = document.getElementById("qrcode");
    if (qrEl && typeof QRCode !== "undefined") {
        new QRCode(qrEl, {
            text: "https://feucavite.edu.ph/eval",
            width: 160,
            height: 160,
            colorDark: "#274C07",
        });
    }

    const sidebarToggle = document.querySelector("[data-sidebar-toggle]");
    const sidebarClose = document.querySelector("[data-sidebar-close]");
    const closeSidebar = () => {
        document.body.classList.remove("sidebar-open");
        sidebarToggle?.setAttribute("aria-expanded", "false");
    };

    sidebarToggle?.addEventListener("click", () => {
        const isOpen = document.body.classList.toggle("sidebar-open");
        sidebarToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });

    sidebarClose?.addEventListener("click", closeSidebar);

    window.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeSidebar();
        }
    });
});

function toggleDropdown() {
    const dropdown = document.getElementById("user-dropdown");
    const icon = document.querySelector(".toggle-icon");
    
    if (dropdown) {
        dropdown.classList.toggle("show");
    }
    
    if (icon) {
        icon.style.transform = dropdown.classList.contains("show") ? "rotate(180deg)" : "rotate(0deg)";
    }
}

function clearPrivacySession() {
    sessionStorage.removeItem("privacyAccepted");
}

document.addEventListener("DOMContentLoaded", function () {
    // 1. Handle Auto-disappearing Alerts (Success & Errors)
    const alerts = document.querySelectorAll('.alert-success, .alert-danger, [class^="alert-"]');
    
    alerts.forEach((alert) => {
        setTimeout(function () {
            alert.style.transition = "all 0.5s ease";
            alert.style.opacity = "0";
            alert.style.transform = "translateX(20px)";
            
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 500);
        }, 5000);
    });

    // 2. Global Click Listener to close dropdown when clicking elsewhere
    window.addEventListener('click', function(e) {
        const trigger = document.querySelector('.user-profile-trigger');
        const dropdown = document.getElementById("user-dropdown");
        const icon = document.querySelector(".toggle-icon");

        if (trigger && !trigger.contains(e.target)) {
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
                if (icon) icon.style.transform = "rotate(0deg)";
            }
        }
    });
});
