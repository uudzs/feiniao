class ProtectionSystem {
    constructor() {
        this.contentContainer = document.getElementById('chapter_content');
    }
    init() {
        this.disableContentSelection();
        this.preventContextMenu();
        this.disableDevTools();
        this.interceptShortcuts();
        this.restoreContent();
    }
    disableContentSelection() {
        this.contentContainer.classList.add('unselectable');
    }
    preventContextMenu() {
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
        });
    }
    disableDevTools() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F12') {
                e.preventDefault();
            }
        });
    }
    interceptShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && (e.key === 'u' || e.key === 'U')) {
                e.preventDefault();
            }
            if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
                e.preventDefault();
            }
            if (e.ctrlKey && e.shiftKey && e.key === 'I') {
                e.preventDefault();
            }
            if (e.ctrlKey && (e.key === 'a' || e.key === 'A')) {
                e.preventDefault();
            }
            if (e.ctrlKey && (e.key === 'c' || e.key === 'C')) {
                e.preventDefault();
            }
        });
    }
    restoreContent() {
        const paragraphs = Array.from(this.contentContainer.querySelectorAll('p[data-id]'));
        paragraphs.sort((a, b) => {
            return parseInt(a.dataset.id) - parseInt(b.dataset.id);
        });
        this.contentContainer.innerHTML = '';
        paragraphs.forEach(p => {
            p.removeAttribute('data-id');
            this.contentContainer.appendChild(p);
        });
    }
}