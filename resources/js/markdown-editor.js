import { Editor, Extension, Node, mergeAttributes } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import Suggestion from '@tiptap/suggestion';
import { Markdown } from 'tiptap-markdown';

/**
 * Inline node serialised in Markdown as [[Titolo]] or [[Titolo|etichetta]].
 * The link is resolved server-side at render time.
 */
const WikiLink = Node.create({
    name: 'wikiLink',
    group: 'inline',
    inline: true,
    atom: true,

    addAttributes() {
        return {
            title: { default: '' },
            label: { default: null },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'span[data-wikilink]',
                getAttrs: (el) => ({
                    title: el.getAttribute('data-wikilink'),
                    label: el.getAttribute('data-label') || null,
                }),
            },
        ];
    },

    renderHTML({ node, HTMLAttributes }) {
        return [
            'span',
            mergeAttributes(HTMLAttributes, {
                'data-wikilink': node.attrs.title,
                'data-label': node.attrs.label,
                class: 'wikilink',
            }),
            node.attrs.label || node.attrs.title,
        ];
    },

    addStorage() {
        return {
            markdown: {
                serialize(state, node) {
                    const { title, label } = node.attrs;
                    state.write(label ? `[[${title}|${label}]]` : `[[${title}]]`);
                },
                parse: {
                    setup(markdownit) {
                        markdownit.inline.ruler.before('link', 'wikilink', (state, silent) => {
                            const start = state.pos;
                            if (state.src.charCodeAt(start) !== 0x5b || state.src.charCodeAt(start + 1) !== 0x5b) {
                                return false;
                            }
                            const end = state.src.indexOf(']]', start + 2);
                            if (end === -1) {
                                return false;
                            }
                            const inner = state.src.slice(start + 2, end);
                            if (!inner.trim() || /[\[\]\n]/.test(inner)) {
                                return false;
                            }
                            if (!silent) {
                                const [title, label] = inner.split('|');
                                const token = state.push('wikilink', '', 0);
                                token.meta = { title: title.trim(), label: label ? label.trim() : '' };
                            }
                            state.pos = end + 2;
                            return true;
                        });

                        markdownit.renderer.rules.wikilink = (tokens, idx) => {
                            const { title, label } = tokens[idx].meta;
                            const esc = markdownit.utils.escapeHtml;
                            return `<span data-wikilink="${esc(title)}" data-label="${esc(label)}">${esc(label || title)}</span>`;
                        };
                    },
                },
            },
        };
    },
});

function createSuggestionPopup() {
    let element = null;
    let selected = 0;
    let current = null;

    const draw = () => {
        element.innerHTML = '';
        if (current.items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'wikilink-suggest-empty';
            empty.textContent = 'Nessuna pagina trovata';
            element.appendChild(empty);
            return;
        }
        current.items.forEach((item, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'wikilink-suggest-item' + (index === selected ? ' is-selected' : '');
            button.innerHTML = '<span></span><small></small>';
            button.firstChild.textContent = item.title;
            button.lastChild.textContent = item.category || '';
            button.addEventListener('mousedown', (event) => {
                event.preventDefault();
                current.command({ title: item.title });
            });
            element.appendChild(button);
        });
    };

    const position = () => {
        if (!element || !current) {
            return;
        }
        let rect = current.clientRect && current.clientRect();
        if (!rect || (rect.left === 0 && rect.bottom === 0)) {
            try {
                rect = current.editor.view.coordsAtPos(current.range.to);
            } catch (e) {
                rect = null;
            }
        }
        if (!rect) {
            return;
        }
        element.style.left = `${Math.max(8, Math.min(rect.left, window.innerWidth - 264))}px`;
        element.style.top = `${rect.bottom + 6}px`;
    };

    const positionSoon = () => {
        position();
        requestAnimationFrame(position);
    };

    return {
        onStart(props) {
            current = props;
            selected = 0;
            element = document.createElement('div');
            element.className = 'wikilink-suggest';
            document.body.appendChild(element);
            draw();
            positionSoon();
        },
        onUpdate(props) {
            current = props;
            selected = 0;
            draw();
            positionSoon();
        },
        onKeyDown({ event }) {
            if (!element || current.items.length === 0) {
                return false;
            }
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                const step = event.key === 'ArrowDown' ? 1 : -1;
                selected = (selected + step + current.items.length) % current.items.length;
                draw();
                return true;
            }
            if (event.key === 'Enter' || event.key === 'Tab') {
                current.command({ title: current.items[selected].title });
                return true;
            }
            return false;
        },
        onExit() {
            element?.remove();
            element = null;
        },
    };
}

function wikiLinkSuggestion(pages) {
    return Extension.create({
        name: 'wikiLinkSuggestion',
        addProseMirrorPlugins() {
            return [
                Suggestion({
                    editor: this.editor,
                    char: '[[',
                    allowSpaces: true,
                    items: ({ query }) => {
                        const needle = query.toLowerCase();
                        return pages.filter((page) => page.title.toLowerCase().includes(needle)).slice(0, 8);
                    },
                    command: ({ editor, range, props }) => {
                        editor
                            .chain()
                            .focus()
                            .insertContentAt(range, [{ type: 'wikiLink', attrs: { title: props.title } }, { type: 'text', text: ' ' }])
                            .run();
                    },
                    render: createSuggestionPopup,
                }),
            ];
        },
    });
}

export function registerMarkdownEditor(Alpine) {
    Alpine.data('markdownEditor', (config = {}) => ({
        value: config.value ?? '',
        mode: 'visual',
        active: {},
        // Declared so each component owns it instead of writing to a shared parent scope.
        editorRef: null,

        init() {
            // Kept out of Alpine's reactive proxy, which breaks ProseMirror.
            const editor = new Editor({
                element: this.$refs.surface,
                extensions: [
                    StarterKit.configure({ heading: { levels: [1, 2, 3] } }),
                    Placeholder.configure({ placeholder: config.placeholder || '' }),
                    WikiLink,
                    wikiLinkSuggestion(config.pages || []),
                    Markdown.configure({ html: true, breaks: true, linkify: false }),
                ],
                content: this.value ?? '',
                onUpdate: ({ editor }) => {
                    this.value = editor.storage.markdown.getMarkdown();
                },
                onTransaction: ({ editor }) => {
                    this.active = {
                        bold: editor.isActive('bold'),
                        italic: editor.isActive('italic'),
                        h2: editor.isActive('heading', { level: 2 }),
                        h3: editor.isActive('heading', { level: 3 }),
                        bullet: editor.isActive('bulletList'),
                        ordered: editor.isActive('orderedList'),
                        quote: editor.isActive('blockquote'),
                    };
                },
            });
            this.editorRef = () => editor;

            this.$watch('value', (value) => {
                if (this.mode === 'visual' && value !== editor.storage.markdown.getMarkdown()) {
                    editor.commands.setContent(value ?? '', { emitUpdate: false });
                }
            });

        },

        destroy() {
            this.editorRef?.().destroy();
        },

        setMode(mode) {
            if (mode === 'visual') {
                this.editorRef().commands.setContent(this.value ?? '', { emitUpdate: false });
            }
            this.mode = mode;
        },

        run(command) {
            const editor = this.editorRef();
            if (command === 'wikilink' && editor.state.doc.textBetween(Math.max(0, editor.state.selection.from - 2), editor.state.selection.from) === '[[') {
                editor.commands.focus();
                return;
            }
            const chain = editor.chain().focus();
            ({
                bold: () => chain.toggleBold(),
                italic: () => chain.toggleItalic(),
                h2: () => chain.toggleHeading({ level: 2 }),
                h3: () => chain.toggleHeading({ level: 3 }),
                bullet: () => chain.toggleBulletList(),
                ordered: () => chain.toggleOrderedList(),
                quote: () => chain.toggleBlockquote(),
                wikilink: () => chain.insertContent('[['),
            })[command]().run();
        },
    }));
}
