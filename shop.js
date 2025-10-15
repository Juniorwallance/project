// shop.js
// JS for shop page

document.addEventListener('DOMContentLoaded', function() {
    // Highlight nav link
    const navLinks = document.querySelectorAll('.nav-list a');
    navLinks.forEach(link => {
        if (window.location.pathname.endsWith(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });
    // Add shop-specific JS here
});
