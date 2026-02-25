import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

type EventParams = Record<string, string | number | boolean | null | undefined>;

export function trackEvent(name: string, params?: EventParams): void {
    if (typeof window.gtag === 'function') {
        window.gtag('event', name, params);
    }
}

export function useAnalytics(): void {
    let removeListener: (() => void) | null = null;

    onMounted(() => {
        removeListener = router.on('navigate', (event) => {
            if (typeof window.gtag === 'function') {
                window.gtag('event', 'page_view', {
                    page_path: event.detail.page.url,
                    page_title: document.title,
                });
            }
        });
    });

    onUnmounted(() => {
        removeListener?.();
    });
}
