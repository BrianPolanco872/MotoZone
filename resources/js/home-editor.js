document.querySelectorAll('[data-home-editor]').forEach((editor) => {
    const panels = [...editor.querySelectorAll('[data-editor-panel]')];
    const tabs = [...editor.querySelectorAll('[data-editor-tab]')];
    const navigation = editor.querySelector('[data-editor-navigation]');
    const toolbar = editor.querySelector('[data-editor-toolbar]');
    const status = editor.querySelector('[data-editor-status]');
    const dirtyForms = new Set(editor.querySelectorAll('[data-editor-recovered]'));
    let activeForm;
    let submitting = false;

    const selectForm = (form) => {
        activeForm = form;
        if (status && form) status.textContent = `${form.dataset.editorForm}${dirtyForms.has(form) ? ' · Cambios sin guardar' : ''}`;
    };
    const activatePanel = (id) => {
        const panel = panels.find((item) => item.id === id);
        if (!panel) return;
        panels.forEach((item) => { item.hidden = item !== panel; });
        tabs.forEach((tab) => {
            const selected = tab.dataset.editorTab === id;
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
        });
        selectForm(panel.querySelector('details[open] [data-editor-form]') ?? panel.querySelector('[data-editor-form]'));
    };
    const panelFromHash = () => window.location.hash.slice(1) === 'decoracion' ? 'nuevos' : window.location.hash.slice(1);

    if (panels.length) {
        navigation.querySelector('.cms-tabs').setAttribute('role', 'tablist');
        panels.forEach((panel) => { panel.setAttribute('role', 'tabpanel'); });
        tabs.forEach((tab, index) => {
            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-controls', tab.dataset.editorTab);
            tab.addEventListener('click', (event) => {
                event.preventDefault();
                activatePanel(tab.dataset.editorTab);
                window.history.replaceState(null, '', tab.hash);
            });
            tab.addEventListener('keydown', (event) => {
                const direction = { ArrowLeft: -1, ArrowRight: 1 }[event.key];
                let target;
                if (direction) target = tabs[(index + direction + tabs.length) % tabs.length];
                if (event.key === 'Home') target = tabs[0];
                if (event.key === 'End') target = tabs.at(-1);
                if (!target) return;
                event.preventDefault();
                target.click();
                target.focus();
            });
        });
        const initial = navigation.dataset.initialPanel || panelFromHash();
        activatePanel(panels.some((panel) => panel.id === initial) ? initial : 'nuevos');
        window.addEventListener('hashchange', () => activatePanel(panelFromHash()));
        toolbar.hidden = false;
    }

    editor.addEventListener('focusin', (event) => {
        const form = event.target.closest('[data-editor-form]');
        if (form) selectForm(form);
    });
    const markDirty = (event) => {
        const form = event.target.closest('[data-editor-form]');
        if (!form || !event.target.name) return;
        dirtyForms.add(form);
        selectForm(form);
    };
    editor.addEventListener('input', markDirty);
    editor.addEventListener('change', markDirty);
    editor.addEventListener('invalid', (event) => {
        const panel = event.target.closest('[data-editor-panel]');
        if (panel) activatePanel(panel.id);
        let ancestor = event.target.parentElement;
        while (ancestor && ancestor !== editor) {
            if (ancestor.tagName === 'DETAILS') ancestor.open = true;
            ancestor = ancestor.parentElement;
        }
        selectForm(event.target.closest('[data-editor-form]'));
    }, true);
    editor.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        const otherChanges = [...dirtyForms].some((form) => form !== event.target);
        const warning = event.target.matches('[data-editor-form]')
            ? 'Hay cambios sin guardar en otros formularios. Continuar guardará solo este formulario y descartará los demás. ¿Continuar?'
            : 'Esta acción recargará el editor y descartará los borradores sin guardar. ¿Continuar?';
        if (otherChanges && !window.confirm(warning)) {
            event.preventDefault();
            return;
        }
        submitting = true;
    });
    window.addEventListener('beforeunload', (event) => {
        if (submitting || !dirtyForms.size) return;
        event.preventDefault();
        event.returnValue = '';
    });
    editor.querySelector('[data-editor-save]')?.addEventListener('click', () => activeForm?.requestSubmit());
    const discardForm = (form) => {
        const recovered = form.hasAttribute('data-editor-recovered');
        const warning = recovered
            ? 'Este borrador procede de un error de validación. Descartarlo recargará el editor y descartará todos los cambios sin guardar. ¿Continuar?'
            : '¿Descartar los cambios sin guardar de este formulario? Lo publicado no cambia.';
        if (!window.confirm(warning)) return false;
        if (recovered) {
            submitting = true;
            window.location.reload();
            return false;
        }
        form.reset();
        dirtyForms.delete(form);
        return true;
    };
    editor.querySelector('[data-editor-discard]')?.addEventListener('click', () => {
        if (!activeForm || !discardForm(activeForm)) return;
        selectForm(activeForm);
    });
    editor.querySelectorAll('[data-editor-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            const details = button.closest('[data-editor-card]').querySelector('[data-editor-card-details]');
            details.open = !details.open;
            if (details.open) details.querySelector('input:not([type="hidden"])')?.focus();
        });
    });
    editor.querySelectorAll('[data-editor-add], [data-editor-card-details]').forEach((details) => {
        details.addEventListener('toggle', () => {
            if (details.open) selectForm(details.querySelector('[data-editor-form]'));
        });
    });
    editor.querySelectorAll('[data-editor-cancel]').forEach((button) => {
        button.addEventListener('click', () => {
            const form = button.closest('[data-editor-form]');
            if (dirtyForms.has(form)) {
                if (!discardForm(form)) return;
            } else {
                form.reset();
            }
            form.closest('details').open = false;
            selectForm(form.closest('[data-editor-panel]').querySelector('[data-editor-form]'));
        });
    });

    editor.querySelector('[data-family-create]')?.addEventListener('click', (event) => {
        event.preventDefault();
        const details = editor.querySelector('#new-category-form');
        details.open = true;
        details.querySelector('[name="title"]').focus();
        details.scrollIntoView({ block: 'start' });
    });
    editor.querySelectorAll('[data-family-preset]').forEach((button) => {
        button.addEventListener('click', () => {
            const form = button.closest('details').querySelector('[data-card-form]');
            if (dirtyForms.has(form) && !window.confirm('¿Usar esta familia sugerida? Se sustituirán el nombre e icono del borrador.')) return;
            ['title', 'icon'].forEach((name) => {
                const input = form.elements.namedItem(name);
                input.value = button.dataset[name];
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
            form.querySelector('[name="title"]').focus();
        });
    });

    const descriptionDialog = editor.querySelector('[data-editor-description]');
    const descriptionDraft = editor.querySelector('[data-description-draft]');
    let descriptionSource;
    let descriptionTrigger;
    editor.querySelectorAll('[data-description-trigger]').forEach((button) => {
        button.hidden = false;
        button.addEventListener('click', () => {
            descriptionSource = document.getElementById(button.dataset.descriptionTrigger);
            descriptionTrigger = button;
            descriptionDraft.value = descriptionSource.value;
            descriptionDialog.showModal();
        });
    });
    editor.querySelector('[data-description-close]')?.addEventListener('click', () => descriptionDialog.close());
    editor.querySelector('[data-description-apply]')?.addEventListener('click', () => {
        descriptionSource.value = descriptionDraft.value;
        descriptionSource.dispatchEvent(new Event('input', { bubbles: true }));
        descriptionDialog.close();
    });
    descriptionDialog?.addEventListener('close', () => descriptionTrigger?.focus());

    editor.querySelectorAll('[data-home-confirm]').forEach((form) => {
        form.querySelector('button[type="submit"]').disabled = false;
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.homeConfirm)) event.preventDefault();
        });
    });

    editor.querySelectorAll('[data-hero-preview]').forEach((preview) => {
        editor.querySelectorAll('[data-preview-input]').forEach((input) => {
            const target = preview.querySelector(`[data-preview-text="${input.dataset.previewInput}"]`);
            if (target) {
                input.addEventListener('input', () => { target.textContent = input.value; });
                input.closest('form').addEventListener('reset', () => { target.textContent = input.defaultValue; });
            }
        });
    });

    editor.querySelectorAll('[data-media]').forEach((media) => {
        const main = media.querySelector('[data-media-main]');
        const mainUrl = media.querySelector('[data-media-main-url]');
        const mainUrlStatus = media.querySelector('[data-media-main-url-status]');
        const mainWrap = media.querySelector('[data-media-main-wrap]');
        const mainPreview = media.querySelector('[data-media-main-preview]');
        const mainRemove = media.querySelector('[data-media-main-remove]');
        const gallery = media.querySelector('[data-media-gallery]');
        const galleryUrl = media.querySelector('[data-media-gallery-url]');
        const galleryUrlPreview = media.querySelector('[data-media-gallery-url-preview]');
        const galleryUrlImage = media.querySelector('[data-media-gallery-url-image]');
        const galleryUrlStatus = media.querySelector('[data-media-gallery-url-status]');
        const newGallery = media.querySelector('[data-media-new-gallery]');
        const form = media.closest('form');
        let mainObjectUrl;
        let galleryObjectUrls = [];
        let files = [];
        const originalMainUrl = mainPreview?.getAttribute('src');
        const originalMainHidden = mainWrap?.hidden;
        const originalMainClassHidden = mainWrap?.classList.contains('hidden');
        const setVisible = (element, visible) => {
            if (!element) return;
            element.hidden = !visible;
            element.classList.toggle('hidden', !visible);
        };

        const previewDirectUrl = (input, image, wrapper, status, initialUrl, fallbackUrl) => {
            if (!input || !image) return () => {};
            let version = 0;

            const restore = () => {
                version += 1;
                input.setCustomValidity('');
                if (status) status.textContent = '';
                image.onload = null;
                image.onerror = null;
                if (fallbackUrl) image.src = fallbackUrl;
                else image.removeAttribute('src');
                setVisible(wrapper, Boolean(fallbackUrl));
            };

            const preview = () => {
                const currentVersion = ++version;
                const value = input.value.trim();

                if (!value || value === initialUrl) {
                    restore();
                    return;
                }

                let url;
                try {
                    url = new URL(value);
                } catch {
                    input.setCustomValidity('Pega una dirección directa HTTPS de una imagen.');
                    if (status) status.textContent = input.validationMessage;
                    restoreImage();
                    return;
                }

                if (url.protocol !== 'https:' || !url.hostname || url.username || url.password) {
                    input.setCustomValidity('Pega una dirección directa HTTPS de una imagen, sin credenciales.');
                    if (status) status.textContent = input.validationMessage;
                    restoreImage();
                    return;
                }

                input.setCustomValidity('Espera a que se compruebe la vista previa antes de guardar.');
                if (status) status.textContent = 'Cargando vista previa…';
                setVisible(wrapper, true);
                image.onload = () => {
                    if (currentVersion !== version) return;
                    input.setCustomValidity('');
                    if (status) status.textContent = 'Imagen cargada. Se guardará al enviar el formulario.';
                };
                image.onerror = () => {
                    if (currentVersion !== version) return;
                    input.setCustomValidity('No se pudo cargar. Se conservará la imagen guardada y no se guardará este enlace.');
                    if (status) status.textContent = input.validationMessage;
                    restoreImage();
                };
                image.src = value;
            };

            const restoreImage = () => {
                image.onload = null;
                image.onerror = null;
                if (fallbackUrl) image.src = fallbackUrl;
                else image.removeAttribute('src');
                setVisible(wrapper, Boolean(fallbackUrl));
            };

            input.addEventListener('input', preview);
            preview();

            return restore;
        };

        const restoreMainUrlPreview = previewDirectUrl(
            mainUrl,
            mainPreview,
            mainWrap,
            mainUrlStatus,
            mainUrl?.dataset.originalUrl ?? '',
            originalMainUrl,
        );
        const restoreGalleryUrlPreview = previewDirectUrl(
            galleryUrl,
            galleryUrlImage,
            galleryUrlPreview,
            galleryUrlStatus,
            '',
            '',
        );

        const revokeMain = () => {
            if (mainObjectUrl) URL.revokeObjectURL(mainObjectUrl);
            mainObjectUrl = undefined;
        };
        const revokeGallery = () => {
            galleryObjectUrls.forEach((url) => URL.revokeObjectURL(url));
            galleryObjectUrls = [];
        };

        main?.addEventListener('change', () => {
            const file = main.files[0];
            if (!file) return;
            if (mainUrl) {
                mainUrl.value = '';
                restoreMainUrlPreview();
            }
            revokeMain();
            mainObjectUrl = URL.createObjectURL(file);
            mainPreview.src = mainObjectUrl;
            mainWrap.hidden = false;
            mainWrap.classList.remove('hidden');
            if (mainRemove) mainRemove.checked = false;
        });

        mainUrl?.addEventListener('input', () => {
            if (main?.value) {
                main.value = '';
                revokeMain();
            }
            if (mainRemove) mainRemove.checked = false;
        });

        mainRemove?.addEventListener('change', () => {
            if (!mainRemove.checked) {
                restoreMainUrlPreview();
                return;
            }

            if (main) main.value = '';
            if (mainUrl) {
                mainUrl.value = '';
                mainUrl.dispatchEvent(new Event('input', { bubbles: true }));
            }
            revokeMain();
            if (mainPreview) mainPreview.removeAttribute('src');
            setVisible(mainWrap, false);
        });

        const syncFiles = () => {
            const transfer = new DataTransfer();
            files.forEach((file) => transfer.items.add(file));
            gallery.files = transfer.files;
        };

        const renderGallery = () => {
            revokeGallery();
            newGallery.replaceChildren();

            files.forEach((file, index) => {
                const url = URL.createObjectURL(file);
                galleryObjectUrls.push(url);

                const wrapper = document.createElement('div');
                wrapper.className = 'relative overflow-hidden rounded-lg border border-mz-border';

                const image = document.createElement('img');
                image.src = url;
                image.alt = `Foto nueva ${index + 1}`;
                image.className = 'h-20 w-full object-cover';

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'absolute right-1 top-1 flex size-6 items-center justify-center rounded-full bg-black/70 text-xs font-bold text-white';
                remove.textContent = '×';
                remove.setAttribute('aria-label', `Quitar foto nueva ${index + 1}`);
                remove.addEventListener('click', () => {
                    files.splice(index, 1);
                    syncFiles();
                    renderGallery();
                });

                wrapper.append(image, remove);
                newGallery.append(wrapper);
            });
        };

        gallery?.addEventListener('change', () => {
            files = [...files, ...gallery.files].slice(0, 6);
            syncFiles();
            renderGallery();
        });

        form.addEventListener('reset', () => {
            revokeMain();
            revokeGallery();
            files = [];
            newGallery?.replaceChildren();
            if (mainPreview) {
                if (originalMainUrl) mainPreview.src = originalMainUrl;
                else mainPreview.removeAttribute('src');
            }
            if (mainWrap) {
                mainWrap.hidden = originalMainHidden;
                mainWrap.classList.toggle('hidden', originalMainClassHidden);
            }
            mainUrl?.setCustomValidity('');
            galleryUrl?.setCustomValidity('');
            restoreMainUrlPreview();
            restoreGalleryUrlPreview();
        });

        window.addEventListener('pagehide', () => {
            revokeMain();
            revokeGallery();
        });
    });

    editor.querySelectorAll('[data-product-picker]').forEach((picker) => {
        const idInput = picker.querySelector('[data-product-id]');
        const search = picker.querySelector('[data-product-search]');
        const results = picker.querySelector('[data-product-results]');
        const chosen = picker.querySelector('[data-product-chosen]');
        const status = picker.querySelector('[data-product-status]');
        const clear = picker.querySelector('[data-product-clear]');
        const initialChosen = chosen.textContent;
        const initialProductId = idInput.value;
        let timer;
        let searchVersion = 0;

        const form = picker.closest('form');
        const empty = () => results.replaceChildren();
        const setField = (name, value) => {
            const field = form.elements.namedItem(name);
            if (!field) return;
            field.value = value;
            field.dispatchEvent(new Event('input', { bubbles: true }));
        };
        const choose = (product) => {
            idInput.value = product.id;
            idInput.dispatchEvent(new Event('change', { bubbles: true }));
            setField('title', product.name);
            if (Number(product.price) > 0) setField('price', Number(product.price).toFixed(2));
            chosen.textContent = `${product.name} · ${product.sku}`;
            search.value = '';
            empty();
            status.textContent = `${product.name} ${picker.dataset.pickerInstruction ?? 'vinculado. Guarda la card.'}`;
        };

        const runSearch = async () => {
            clearTimeout(timer);
            const version = ++searchVersion;
            const term = search.value.trim();
            if (term.length < 2) {
                empty();
                return;
            }
            try {
                const response = await fetch(`${picker.dataset.searchUrl}?q=${encodeURIComponent(term)}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('search');
                const { products } = await response.json();
                if (version !== searchVersion) return;
                empty();
                if (!products.length) {
                    status.textContent = 'Sin resultados. Prueba otro nombre, SKU o código de barras.';
                    return;
                }
                products.forEach((product) => {
                    const item = document.createElement('li');
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'flex w-full items-center justify-between gap-3 py-2 text-left text-sm hover:text-mz-accent-primary';
                    button.append(document.createTextNode(`${product.name} · ${product.sku}`));
                    button.addEventListener('click', () => choose(product));
                    item.append(button);
                    results.append(item);
                });
                status.textContent = 'Elige un producto de la lista.';
            } catch {
                if (version === searchVersion) status.textContent = 'No se pudo buscar. Intenta de nuevo.';
            }
        };

        search.addEventListener('input', () => {
            clearTimeout(timer);
            searchVersion++;
            timer = setTimeout(runSearch, 250);
        });

        // shortcut: los lectores de código de barras terminan con Enter, que aquí solo busca (no envía la card)
        search.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            runSearch();
        });

        clear.addEventListener('click', () => {
            clearTimeout(timer);
            searchVersion++;
            search.value = '';
            idInput.value = '';
            idInput.dispatchEvent(new Event('change', { bubbles: true }));
            chosen.textContent = 'Sin producto vinculado.';
            empty();
            status.textContent = 'Producto quitado. Guarda la card.';
        });
        picker.closest('form').addEventListener('reset', () => {
            clearTimeout(timer);
            searchVersion++;
            idInput.value = initialProductId;
            chosen.textContent = initialChosen;
            empty();
            status.textContent = '';
        });
    });
});

const revealHomeFamily = () => {
    const family = document.getElementById(window.location.hash.slice(1));
    if (family?.matches('details[data-home-family]')) family.open = true;
};
revealHomeFamily();
window.addEventListener('hashchange', revealHomeFamily);
