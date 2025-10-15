// fantasy.js
// JS for fantasy league page

document.addEventListener('DOMContentLoaded', function() {
    // Example: Highlight nav link
    const navLinks = document.querySelectorAll('.nav-list a');
    navLinks.forEach(link => {
        if (window.location.pathname.endsWith(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });
    // Add fantasy-specific JS here
});
