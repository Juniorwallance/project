// highlights.js - Frontend JavaScript for highlights functionality
// This file handles the frontend interactions for the highlights page

class HighlightsManager {
    constructor() {
        this.apiBaseUrl = 'highlights_api.php';
        this.currentFilter = 'all';
        this.currentPage = 1;
        this.highlightsPerPage = 12;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadHighlights();
        this.loadFeaturedHighlights();
    }

    setupEventListeners() {
        // Filter buttons
        const filterBtns = document.querySelectorAll('.filter-btn');
        filterBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleFilterChange(e.target.dataset.filter);
            });
        });

        // Watch buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('watch-btn')) {
                this.handleWatchClick(e.target);
            }
        });

        // Save buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('save-btn')) {
                this.handleSaveClick(e.target);
            }
        });

        // Newsletter form
        const newsletterForm = document.querySelector('.newsletter-form');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', (e) => {
                this.handleNewsletterSubmit(e);
            });
        }

        // Search functionality
        const searchInput = document.querySelector('#search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.handleSearch(e.target.value);
            });
        }
    }

    async loadHighlights() {
        try {
            const params = new URLSearchParams({
                category: this.currentFilter,
                page: this.currentPage,
                limit: this.highlightsPerPage
            });

            const response = await fetch(`${this.apiBaseUrl}?${params}`);
            const data = await response.json();

            if (response.ok) {
                this.renderHighlights(data.highlights);
                this.updatePagination(data);
            } else {
                console.error('Error loading highlights:', data.error);
                this.showError('Failed to load highlights');
            }
        } catch (error) {
            console.error('Error loading highlights:', error);
            this.showError('Failed to load highlights');
        }
    }

    async loadFeaturedHighlights() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/featured`);
            const data = await response.json();

            if (response.ok) {
                this.renderFeaturedHighlights(data);
            }
        } catch (error) {
            console.error('Error loading featured highlights:', error);
        }
    }

    renderHighlights(highlights) {
        const grid = document.querySelector('.highlights-grid');
        if (!grid) return;

        if (highlights.length === 0) {
            grid.innerHTML = '<p class="no-highlights">No highlights found for the selected filter.</p>';
            return;
        }

        grid.innerHTML = highlights.map(highlight => this.createHighlightCard(highlight)).join('');
    }

    renderFeaturedHighlights(highlights) {
        // This could be used to populate a featured section if needed
        console.log('Featured highlights loaded:', highlights);
    }

    createHighlightCard(highlight) {
        const categories = highlight.categories.split(',').map(cat => cat.trim()).join(' ');
        const postedDate = new Date(highlight.posted_date).toLocaleDateString();
        const views = this.formatNumber(highlight.views);
        const likes = this.formatNumber(highlight.likes);

        return `
            <div class="highlight-card" data-category="${categories}" data-id="${highlight.id}">
                <div class="highlight-thumbnail">
                    <img src="${highlight.thumbnail}" alt="${highlight.title}" loading="lazy">
                    <div class="play-button">▶</div>
                    <div class="video-duration">${highlight.duration}</div>
                </div>
                <div class="highlight-info">
                    <h3>${highlight.title}</h3>
                    <p class="highlight-meta">Posted: ${postedDate} • Views: ${views}</p>
                    <p class="highlight-desc">${highlight.description}</p>
                    <div class="highlight-actions">
                        <button class="watch-btn" data-id="${highlight.id}">Watch Now</button>
                        <button class="save-btn" data-id="${highlight.id}">Save</button>
                        <button class="like-btn" data-id="${highlight.id}">👍 ${likes}</button>
                    </div>
                </div>
            </div>
        `;
    }

    handleFilterChange(filter) {
        // Update active filter button
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-filter="${filter}"]`).classList.add('active');

        this.currentFilter = filter;
        this.currentPage = 1;
        this.loadHighlights();
    }

    handleWatchClick(button) {
        const highlightId = button.dataset.id;
        // In a real implementation, this would open a video player
        alert(`Video playback would start here for highlight ID: ${highlightId}`);
        
        // Track view
        this.trackView(highlightId);
    }

    handleSaveClick(button) {
        const highlightId = button.dataset.id;
        this.saveHighlight(highlightId, button);
    }

    async saveHighlight(highlightId, button) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/${highlightId}/save`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const data = await response.json();

            if (response.ok) {
                button.textContent = 'Saved';
                button.classList.add('saved');
                this.showSuccess('Highlight saved successfully');
            } else {
                this.showError(data.error || 'Failed to save highlight');
            }
        } catch (error) {
            console.error('Error saving highlight:', error);
            this.showError('Failed to save highlight');
        }
    }

    async trackView(highlightId) {
        try {
            await fetch(`${this.apiBaseUrl}/${highlightId}`, {
                method: 'GET'
            });
        } catch (error) {
            console.error('Error tracking view:', error);
        }
    }

    async likeHighlight(highlightId) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/${highlightId}/like`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const data = await response.json();

            if (response.ok) {
                // Update like count in UI
                const likeBtn = document.querySelector(`[data-id="${highlightId}"].like-btn`);
                if (likeBtn) {
                    likeBtn.textContent = `👍 ${this.formatNumber(data.likes)}`;
                }
            }
        } catch (error) {
            console.error('Error liking highlight:', error);
        }
    }

    handleSearch(searchTerm) {
        // Debounce search
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.performSearch(searchTerm);
        }, 300);
    }

    async performSearch(searchTerm) {
        try {
            const params = new URLSearchParams({
                category: this.currentFilter,
                search: searchTerm,
                page: 1,
                limit: this.highlightsPerPage
            });

            const response = await fetch(`${this.apiBaseUrl}?${params}`);
            const data = await response.json();

            if (response.ok) {
                this.renderHighlights(data.highlights);
                this.updatePagination(data);
            }
        } catch (error) {
            console.error('Error searching highlights:', error);
        }
    }

    async handleNewsletterSubmit(e) {
        e.preventDefault();
        
        const email = e.target.querySelector('input[type="email"]').value;
        if (!email) return;

        try {
            const response = await fetch(`${this.apiBaseUrl}/subscribe`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email })
            });

            const data = await response.json();

            if (response.ok) {
                this.showSuccess('Successfully subscribed to newsletter!');
                e.target.reset();
            } else {
                this.showError(data.error || 'Failed to subscribe');
            }
        } catch (error) {
            console.error('Error subscribing to newsletter:', error);
            this.showError('Failed to subscribe to newsletter');
        }
    }

    updatePagination(data) {
        // This would update pagination controls if they exist
        console.log('Pagination data:', data);
    }

    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        // Style the notification
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
            ${type === 'success' ? 'background-color: #27ae60;' : 'background-color: #e74c3c;'}
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.opacity = '1';
        }, 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
}

// Initialize highlights manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on the highlights page
    if (document.querySelector('.highlights-grid')) {
        new HighlightsManager();
    }
    
    // Keep existing filter functionality for backward compatibility
    const filterBtns = document.querySelectorAll('.filter-btn');
    const highlightCards = document.querySelectorAll('.highlight-card');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            filterBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            
            // Show/hide cards based on filter
            highlightCards.forEach(card => {
                if (filter === 'all' || card.dataset.category.includes(filter)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    
    // Simulate video play functionality
    const watchButtons = document.querySelectorAll('.watch-btn');
    watchButtons.forEach(button => {
        button.addEventListener('click', function() {
            alert('Video playback would start here. In a real implementation, this would open a video player.');
        });
    });
});: 'Server error', error: error.message });
    }
});

// Serve static files in production
if (process.env.NODE_ENV === 'production') {
    app.use(express.static(path.join(__dirname, '../client/build')));

    app.get('*', (req, res) => {
        res.sendFile(path.join(__dirname, '../client/build', 'index.html'));
    });
}

const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
});