import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

/**
 * Global Search Controller
 * 
 * Manages the global command palette/search modal.
 * Integration: Attached directly to the LiveComponent root.
 */
export default class extends Controller {
    static targets = ['input', 'result', 'trigger'];
    static values = {
        open: Boolean
    };

    async connect() {
        this.selectedIndex = -1;
        this.boundHandleKeydown = this.handleKeydown.bind(this);
        window.addEventListener('keydown', this.boundHandleKeydown, true);

        // Initialize Live Component interface
        try {
            this.component = await getComponent(this.element);

            // Hook into re-renders to restore focus when results update
            if (this.component) {
                this.component.on('render:finished', () => {
                    if (this.hasInputTarget) {
                        // Maintain focus if lost during re-render
                        if (document.activeElement !== this.inputTarget) {
                            this.inputTarget.focus();
                        }
                    }
                });
            }
        } catch (error) {
            console.error('[GlobalSearch] Failed to initialize component:', error);
        }
    }

    disconnect() {
        window.removeEventListener('keydown', this.boundHandleKeydown, true);
    }

    handleKeydown(event) {
        const isOpen = this.hasInputTarget;
        const isCommandK = (event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k';

        if (isCommandK) {
            this.stopEvent(event);
            return isOpen ? this.focusInput() : this.open();
        }

        if (!isOpen) return;

        switch (event.key) {
            case 'Escape':
                this.stopEvent(event);
                this.close();
                break;
            case 'ArrowDown':
                event.preventDefault();
                this.navigate(1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.navigate(-1);
                break;
            case 'Enter':
                this.selectCurrent(event);
                break;
        }
    }

    open() {
        // Since we are on the root component, we can call actions directly
        // Fallback to trigger click if component isn't ready
        if (this.component) {
            this.component.action('open');
        } else {
            // Robust fallback: trigger click
            const trigger = this.hasTriggerTarget
                ? this.triggerTarget
                : document.getElementById('global-search-trigger');

            if (trigger) {
                trigger.focus();
                trigger.click();
            }
        }
    }

    close() {
        if (this.component) {
            this.component.action('close');
        }
    }

    focusInput() {
        if (this.hasInputTarget) {
            this.inputTarget.focus();
            this.inputTarget.select();
        }
    }

    navigate(direction) {
        const items = this.resultTargets;
        if (!items.length) return;

        if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
            this.toggleHighlight(items[this.selectedIndex], false);
        }

        this.selectedIndex += direction;
        if (this.selectedIndex < 0) this.selectedIndex = items.length - 1;
        if (this.selectedIndex >= items.length) this.selectedIndex = 0;

        const current = items[this.selectedIndex];
        this.toggleHighlight(current, true);
        current.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    selectCurrent(event) {
        if (this.selectedIndex >= 0 && this.resultTargets[this.selectedIndex]) {
            event.preventDefault();
            this.resultTargets[this.selectedIndex].click();
        }
    }

    resetNavigation() {
        this.selectedIndex = -1;
        this.resultTargets.forEach(item => this.toggleHighlight(item, false));
    }

    toggleHighlight(element, isActive) {
        const activeClasses = ['bg-gray-100', 'dark:bg-gray-700'];
        const inactiveClasses = ['hover:bg-gray-50', 'dark:hover:bg-gray-700/50'];

        if (isActive) {
            element.classList.add(...activeClasses);
            element.classList.remove(...inactiveClasses);
        } else {
            element.classList.remove(...activeClasses);
            element.classList.add(...inactiveClasses);
        }
    }

    stopEvent(event) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
    }

    // Stimulus callback when input appears
    inputTargetConnected() {
        this.selectedIndex = -1;
        setTimeout(() => this.focusInput(), 50);
    }
}
