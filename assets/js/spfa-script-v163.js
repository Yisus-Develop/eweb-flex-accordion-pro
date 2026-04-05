/**
 * Stamped Passport Menu - Interactivity Engine 1.6.3 (Force Refresh)
 */

document.addEventListener('DOMContentLoaded', function() {
    const tabTriggers = document.querySelectorAll('.spfa-tab-trigger');
    const sections = document.querySelectorAll('.spfa-menu-section');
    const catContents = document.querySelectorAll('.spfa-category-content');
    const sectionsContainer = document.querySelector('.spfa-sections-container');

    console.log("🚀 SPFA Engine Loaded. Triggers found:", tabTriggers.length);

    function openTab(sectionSlug, catSlug) {
        console.log(`📡 Opening: Section[${sectionSlug}] Category[${catSlug}]`);

        // 1. Manage Sections (The big color blocks)
        sections.forEach(s => {
            if (s.id === `section-${sectionSlug}`) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });

        // 2. Manage Category Contents (The cream paper blocks)
        catContents.forEach(c => {
            if (c.id === `cat-${sectionSlug}-${catSlug}`) {
                c.classList.add('active');
            } else {
                c.classList.remove('active');
            }
        });

        // 3. Manage Tab Button Styles (The buttons in the hover menu)
        tabTriggers.forEach(t => {
            if (t.dataset.section === sectionSlug && t.dataset.category === catSlug) {
                t.classList.add('active');
            } else {
                t.classList.remove('active');
            }
        });

        // 4. Smooth scroll to content
        const targetSection = document.getElementById(`section-${sectionSlug}`);
        if (targetSection) {
            targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    tabTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            console.log("Click on tab:", this.dataset.category);
            e.preventDefault();
            e.stopPropagation();
            openTab(this.dataset.section, this.dataset.category);
        });
    });

    // Main Card visuals click also opens its first tab
    document.querySelectorAll('.spfa-nav-visuals').forEach(visual => {
        visual.addEventListener('click', function(e) {
            const card = this.closest('.spfa-nav-card');
            const firstTab = card.querySelector('.spfa-tab-trigger');
            if (firstTab) {
                console.log("Click on Card Visuals -> Opening first tab");
                openTab(firstTab.dataset.section, firstTab.dataset.category);
            }
        });
    });
});
