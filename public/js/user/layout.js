/**
 * Global User Functionality
 * Handles Dropdowns and Auto-disappearing Alerts
 */

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

function clearPrivacySession() {
    // This wipes the "Accepted" flag so the notice returns
    sessionStorage.removeItem("privacyAccepted");
    // The actual logout is handled by the link's href or a form submission
}