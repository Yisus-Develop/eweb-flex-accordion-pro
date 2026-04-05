/**
 * Stamped Passport Menu - Agency Level Engine v1.6.3
 * FIXED: NO AUTOMATIC JUMPS ON MOBILE
 */

document.addEventListener('DOMContentLoaded', function() {
    const isMobile = window.innerWidth < 768;
    const tabTriggers = document.querySelectorAll('.spfa-tab-trigger');
    const sections = document.querySelectorAll('.spfa-menu-section');
    const catContents = document.querySelectorAll('.spfa-category-content');
    const dishCards = document.querySelectorAll('.spfa-dish-card');

    function openTab(sectionSlug, catSlug) {
        sections.forEach(s => {
            if (s.id === `section-${sectionSlug}`) s.classList.add('active');
            else s.classList.remove('active');
        });

        catContents.forEach(c => {
            if (c.id === `cat-${sectionSlug}-${catSlug}`) c.classList.add('active');
            else c.classList.remove('active');
        });

        tabTriggers.forEach(t => {
            if (t.dataset.section === sectionSlug && t.dataset.category === catSlug) t.classList.add('active');
            else t.classList.remove('active');
        });

        const targetSection = document.getElementById(`section-${sectionSlug}`);
        if (targetSection) {
            const offset = isMobile ? 80 : 100;
            const bodyRect = document.body.getBoundingClientRect().top;
            const elementRect = targetSection.getBoundingClientRect().top;
            window.scrollTo({ top: elementRect - bodyRect - offset, behavior: 'smooth' });
        }
    }

    // 1. Tab Clicks (ALWAYS ACTIVE)
    tabTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openTab(this.dataset.section, this.dataset.category);
        });
    });

    // 2. Mobile Dish Expansion
    if (isMobile) {
        dishCards.forEach(card => {
            card.addEventListener('click', function() {
                const wasActive = this.classList.contains('spfa-mobile-expanded');
                dishCards.forEach(c => c.classList.remove('spfa-mobile-expanded'));
                if (!wasActive) this.classList.add('spfa-mobile-expanded');
            });
        });
    }

    // 3. Desktop Hover Fix (Visuals Only) - DISABLED ON MOBILE
    document.querySelectorAll('.spfa-nav-visuals').forEach(visual => {
        visual.addEventListener('click', function() {
            if (isMobile) return; // EN MOVIL NO HACER NADA AL TOCAR LA IMAGEN
            
            const card = this.closest('.spfa-nav-card');
            const firstTab = card.querySelector('.spfa-tab-trigger');
            if (firstTab) openTab(firstTab.dataset.section, firstTab.dataset.category);
        });
    });
});
