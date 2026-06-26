/**
 * Home Page Functionality
 * Handles Privacy Consent and Evaluation Form Switching
 */

/**
 * Handles the Privacy Consent Logic
 */
function acceptPrivacy() {
    console.log("Accepting Privacy...");
    const notice = document.getElementById("privacy-notice");
    const content = document.getElementById("main-content");

    if (notice && content) {
        sessionStorage.setItem("privacyAccepted", "true");
        notice.style.display = "none";
        content.style.display = "block";
    }
}

/**
 * Switch from the subject list to the specific evaluation form
 */
function startEval(mappingId, subjectName, instructorName) {
    const mappingInput = document.getElementById("mapping_id_input");
    const evalTitle = document.getElementById("eval-title");
    const evalInstructor = document.getElementById("eval-instructor");
    const subjectList = document.getElementById("subject-list");
    const evalForm = document.getElementById("evaluation-form");

    if (mappingInput) mappingInput.value = mappingId;
    if (evalTitle) evalTitle.innerText = subjectName;
    if (evalInstructor) evalInstructor.innerText = "Evaluating: " + instructorName;

    if (subjectList) subjectList.classList.remove("active");
    if (evalForm) evalForm.classList.add("active");
    
    window.scrollTo(0, 0);
}

/**
 * Switch back to the list of subjects
 */
function showList() {
    const evalForm = document.getElementById("evaluation-form");
    const subjectList = document.getElementById("subject-list");

    if (evalForm) evalForm.classList.remove("active");
    if (subjectList) subjectList.classList.add("active");
}

/**
 * Initialization for Home Page
 */
document.addEventListener("DOMContentLoaded", function () {
    const notice = document.getElementById("privacy-notice");
    const content = document.getElementById("main-content");
    
    // Check Privacy Session Status
    const status = sessionStorage.getItem("privacyAccepted");
    console.log("Privacy Status in Session:", status);

    if (status === "true") {
        if (notice) notice.style.display = "none";
        if (content) content.style.display = "block";
    } else {
        if (notice) notice.style.display = "block";
        if (content) content.style.display = "none";
    }
});