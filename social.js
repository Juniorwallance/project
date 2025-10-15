// social.js
// JS for social network page

document.addEventListener('DOMContentLoaded', function() {
    // Forum post simulation
    const postButtons = document.querySelectorAll('.forum-category button');
    postButtons.forEach((btn, idx) => {
        btn.addEventListener('click', function() {
            let input;
            if (idx === 2) {
                input = btn.previousElementSibling;
                if (input && input.files.length > 0) {
                    alert('Video uploaded: ' + input.files[0].name + '\n(This is a demo. No actual upload performed.)');
                } else {
                    alert('Please select a video file to upload.');
                }
            } else {
                input = btn.previousElementSibling;
                if (input && input.value.trim() !== '') {
                    alert('Post submitted: ' + input.value);
                    input.value = '';
                } else {
                    alert('Please enter your message before posting.');
                }
            }
        });
    });
    // Highlight active nav link
    const navLinks = document.querySelectorAll('.nav-list a');
    navLinks.forEach(link => {
        if (window.location.pathname.endsWith(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });
});
