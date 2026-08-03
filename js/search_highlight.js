// BlogBay Global Search Highlight & Keyboard Navigation Script
document.addEventListener("DOMContentLoaded", function() {
    // Inject CSS for light-purple search highlighting
    if (!document.getElementById("search-highlight-style")) {
        const style = document.createElement("style");
        style.id = "search-highlight-style";
        style.textContent = `
            mark.search-highlight, .search-highlight {
                background: rgba(147, 104, 184, 0.35) !important;
                color: #4b1f69 !important;
                font-weight: 800 !important;
                border-radius: 4px !important;
                padding: 1px 5px !important;
                box-shadow: 0 0 0 1px rgba(147, 104, 184, 0.5) !important;
                display: inline-block !important;
            }
        `;
        document.head.appendChild(style);
    }

    const searchInputs = document.querySelectorAll("#blogSearch");
    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get("search") || "";

    searchInputs.forEach(input => {
        if (searchParam && !input.value) {
            input.value = searchParam;
        }

        // Search icon click / Enter key press navigation
        const searchBox = input.closest(".hero-search-box");
        if (searchBox) {
            const icon = searchBox.querySelector("i");
            if (icon) {
                icon.style.cursor = "pointer";
                icon.addEventListener("click", function() {
                    triggerSearchNavigation(input.value);
                });
            }
        }

        input.addEventListener("keydown", function(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                triggerSearchNavigation(this.value);
            }
        });

        // Live highlight on typing
        input.addEventListener("input", function() {
            performTextHighlight(this.value.trim());
        });
    });

    // Auto-highlight matches if search parameter is present in URL
    if (searchParam) {
        performTextHighlight(searchParam.trim());
    }
});

function triggerSearchNavigation(query) {
    const trimmed = query.trim();
    if (trimmed) {
        window.location.href = "dashboard.php?search=" + encodeURIComponent(trimmed) + "#blogGrid";
    } else {
        window.location.href = "dashboard.php#blogGrid";
    }
}

function performTextHighlight(searchTerm) {
    // Remove existing highlights
    document.querySelectorAll("mark.search-highlight").forEach(mark => {
        const parent = mark.parentNode;
        if (parent) {
            parent.replaceChild(document.createTextNode(mark.textContent), mark);
            parent.normalize();
        }
    });

    if (!searchTerm || searchTerm.length < 1) return;

    // Elements to scan
    const targetSelectors = [
        "h1", "h2", "h3", "h4", "p", ".blog-title", ".blog-card-title",
        ".blog-content", ".blog-card-text", ".card-title", ".guide-card-title",
        ".guide-card-desc", ".review-blog-title", ".review-text",
        ".review-item-text", ".author-name", ".review-author"
    ];

    const elements = document.querySelectorAll(targetSelectors.join(", "));
    const safeTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${safeTerm})`, "gi");

    elements.forEach(el => {
        // Skip header nav, inputs, buttons, and scripts
        if (el.closest("header") || el.closest("script") || el.closest("style") || el.closest("form") || el.closest("button")) {
            return;
        }

        highlightNode(el, regex);
    });
}

function highlightNode(node, regex) {
    if (node.nodeType === 3) { // Text node
        const match = node.data.match(regex);
        if (match) {
            const span = document.createElement("span");
            span.innerHTML = node.data.replace(regex, '<mark class="search-highlight">$1</mark>');
            node.parentNode.replaceChild(span, node);
        }
    } else if (node.nodeType === 1 && node.childNodes && !/(script|style|mark|button|input|textarea)/i.test(node.tagName)) {
        for (let i = 0; i < node.childNodes.length; i++) {
            highlightNode(node.childNodes[i], regex);
        }
    }
}

// MOBILE HAMBURGER MENU DROPDOWN HANDLER
function toggleMobileMenu(btn) {
    const dropdown = document.getElementById("mobileDropdownMenu");
    if (!dropdown) return;
    
    dropdown.classList.toggle("open");
    
    if (btn) {
        const icon = btn.querySelector("i");
        if (icon) {
            if (dropdown.classList.contains("open")) {
                icon.className = "fa-solid fa-xmark";
            } else {
                icon.className = "fa-solid fa-bars";
            }
        }
    }
}

document.addEventListener("click", function(e) {
    const dropdown = document.getElementById("mobileDropdownMenu");
    const toggleBtn = document.querySelector(".mobile-menu-toggle");
    if (dropdown && dropdown.classList.contains("open")) {
        if (!dropdown.contains(e.target) && (!toggleBtn || !toggleBtn.contains(e.target))) {
            dropdown.classList.remove("open");
            if (toggleBtn) {
                const icon = toggleBtn.querySelector("i");
                if (icon) icon.className = "fa-solid fa-bars";
            }
        }
    }
});
