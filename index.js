// index.js
// JS for homepage interactivity

document.addEventListener('DOMContentLoaded', function() {
    // Highlight active nav link
    const navLinks = document.querySelectorAll('.nav-list a');
    navLinks.forEach(link => {
        if (window.location.pathname.endsWith(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });
});
