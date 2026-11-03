/**
 * parent-page-select
 *
 * A searchable, keyboard-accessible dropdown for selecting a parent wiki page.
 * Renders a text input that filters the list of options and a dropdown with
 * the filtered results. Falls back gracefully if JavaScript is unavailable.
 */
export function registerParentPageSelect(Alpine) {
    Alpine.data('parentPageSelect', (config = {}) => ({
        options: config.options || [],
        query: '',
        open: false,
        highlight: -1,
        selected: config.selected ?? '',

        init() {
            const match = this.options.find(o => String(o.id) === String(this.selected));
            if (match) {
                this.query = match.label;
            }
            this.$watch('selected', (value) => {
                const match = this.options.find(o => String(o.id) === String(value));
                this.query = match ? match.label : '';
            });
        },

        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (!q) {
                return this.options;
            }
            return this.options.filter(o =>
                o.label.toLowerCase().includes(q)
            );
        },

        get hasResults() {
            return this.filtered.length > 0;
        },

        select(option) {
            this.selected = option.id;
            this.query = option.label;
            this.open = false;
            this.highlight = -1;
        },

        clear() {
            this.selected = '';
            this.query = '';
            this.open = false;
            this.highlight = -1;
        },

        openDropdown() {
            this.open = true;
            this.highlight = -1;
        },

        closeDropdown() {
            this.open = false;
            this.highlight = -1;
        },

        toggleDropdown() {
            if (this.open) {
                this.closeDropdown();
            } else {
                this.openDropdown();
            }
        },

        moveHighlight(direction) {
            const max = this.filtered.length - 1;
            if (max < 0) return;
            this.highlight = Math.max(-1, Math.min(max, this.highlight + direction));
            this.$nextTick(() => {
                const list = this.$refs.list;
                if (!list) return;
                const item = list.children[this.highlight + 1];
                if (item) {
                    item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            });
        },

        confirmHighlight() {
            if (this.highlight >= 0 && this.filtered[this.highlight]) {
                this.select(this.filtered[this.highlight]);
            }
        },

        onKeyDown(event) {
            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    if (!this.open) this.openDropdown();
                    this.moveHighlight(1);
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    if (!this.open) this.openDropdown();
                    this.moveHighlight(-1);
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (this.open) {
                        this.confirmHighlight();
                    } else {
                        this.openDropdown();
                    }
                    break;
                case 'Escape':
                    event.preventDefault();
                    this.closeDropdown();
                    break;
                case 'Tab':
                    this.closeDropdown();
                    break;
            }
        },

        onInput() {
            if (!this.open) this.open = true;
            this.highlight = -1;
        },

        onClickOutside(event) {
            if (this.open && !this.$el.contains(event.target)) {
                this.closeDropdown();
            }
        },
    }));
}