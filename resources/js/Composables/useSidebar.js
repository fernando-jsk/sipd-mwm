import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

// Shared state
const isCollapsed = ref(
    typeof window !== 'undefined'
        ? localStorage.getItem('sipd_sidebar_collapsed') === 'true'
        : false
);
const isMobileOpen = ref(false);

// Auto-close mobile drawer when navigating to a new page
if (typeof window !== 'undefined') {
    router.on('finish', () => {
        isMobileOpen.value = false;
    });

    // Keyboard shortcut Ctrl+B / Cmd+B to toggle sidebar
    window.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
            const target = e.target;
            if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable)) {
                return;
            }
            e.preventDefault();
            isCollapsed.value = !isCollapsed.value;
            localStorage.setItem('sipd_sidebar_collapsed', isCollapsed.value ? 'true' : 'false');
        }
    });
}

export function useSidebar() {
    const toggleSidebar = () => {
        isCollapsed.value = !isCollapsed.value;
        if (typeof window !== 'undefined') {
            localStorage.setItem('sipd_sidebar_collapsed', isCollapsed.value ? 'true' : 'false');
        }
    };

    const toggleMobile = () => {
        isMobileOpen.value = !isMobileOpen.value;
    };

    const closeMobile = () => {
        isMobileOpen.value = false;
    };

    const openMobile = () => {
        isMobileOpen.value = true;
    };

    return {
        isCollapsed,
        isMobileOpen,
        toggleSidebar,
        toggleMobile,
        closeMobile,
        openMobile,
    };
}
