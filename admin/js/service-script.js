// The 'services' variable is defined in a <script> tag in the main PHP file.

function renderServices() {
    const grid = document.getElementById('servicesGrid');
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();

    // The 'services' variable is populated by PHP
    let filtered = services.filter(s => {
        const nameMatch = s.name ? s.name.toLowerCase().includes(searchTerm) : false;
        const descriptionMatch = s.description ? s.description.toLowerCase().includes(searchTerm) : false;
        return nameMatch || descriptionMatch;
    });

    if (filtered.length === 0) {
        grid.innerHTML = `
            <div class="empty-state" style="grid-column: 1/-1;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>No services found matching your search.</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = filtered.map(service => {
        const badgeClass = `badge-${service.category || 'default'}`;

        return `
            <div class="service-card">
                <div class="card-header">
                    <div class="icon-wrapper">
                        <img src="${service.image}" alt="${service.name}">
                    </div>
                    <div class="card-actions">
                        <a href="edit-service.php?editid=${service.id}" class="icon-btn" title="Edit">✏️</a>
                        <a href="manage-service-new.php?delid=${service.id}" class="icon-btn" title="Delete" onclick="return confirm('Do you really want to Delete this service?');">🗑️</a>
                    </div>
                </div>
                <h3 class="service-title">${service.name || 'No Name'}</h3>
                <p class="service-description">${service.description || 'No description available.'}</p>
                <div class="service-details">
                    <div class="detail-row">
                        <span class="detail-label">Price</span>
                        <span class="detail-value">$${service.price !== null ? parseFloat(service.price).toFixed(2) : 'N/A'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Duration</span>
                        <span class="detail-value">${service.duration || 'N/A'} min</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Category</span>
                        <span class="category-badge ${badgeClass}">${service.category || 'Uncategorized'}</span>
                    </div>
                </div>
                <div class="service-stats">
                    <div class="stats-left">
                        This Month <span>${service.bookings} bookings</span>
                    </div>
                    <div class="stats-revenue">$${service.revenue !== null ? service.revenue.toFixed(2) : '0.00'}</div>
                </div>
            </div>
        `;
    }).join('');
}

function filterServices() {
    renderServices();
}

function deleteService(id) {
    if (confirm('Are you sure you want to delete this service?')) {
        // The link now handles the deletion, but if you wanted to do it via JS fetch:
        // window.location.href = `manage-service-new.php?delid=${id}`;
        
        // To reflect change immediately without page reload (optimistic update):
        const serviceIndex = services.findIndex(s => s.id === id);
        if (serviceIndex > -1) {
            services.splice(serviceIndex, 1);
            renderServices();
            // Then trigger the actual delete in the background
            fetch(`manage-service-new.php?delid=${id}`)
              .then(response => console.log('Deletion request sent.'))
              .catch(error => console.error('Error deleting service:', error));
        }
    }
    // Return false to prevent default link behavior if called from an onclick on an <a> tag
    return false; 
}

// Initial render when the page loads
document.addEventListener('DOMContentLoaded', renderServices);