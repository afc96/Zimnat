const storedTheme = localStorage.getItem('theme');
if (storedTheme) {
    document.documentElement.dataset.theme = storedTheme;
}

document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = next;
        localStorage.setItem('theme', next);
        button.closest('.profile-menu')?.classList.remove('open');
        button.closest('.profile-menu')?.querySelector('[data-profile-menu]')?.setAttribute('aria-expanded', 'false');
    });
});

document.querySelectorAll('[data-profile-menu]').forEach((button) => {
    const menu = button.closest('.profile-menu');
    button.addEventListener('click', (event) => {
        event.stopPropagation();
        const isOpen = menu.classList.toggle('open');
        button.setAttribute('aria-expanded', String(isOpen));
    });
});

document.addEventListener('click', (event) => {
    document.querySelectorAll('.profile-menu.open').forEach((menu) => {
        if (menu.contains(event.target)) {
            return;
        }
        menu.classList.remove('open');
        menu.querySelector('[data-profile-menu]')?.setAttribute('aria-expanded', 'false');
    });

    document.querySelectorAll('.filter-menu.open').forEach((menu) => {
        if (menu.contains(event.target)) {
            return;
        }
        menu.classList.remove('open');
        menu.querySelector('[data-filter-menu]')?.setAttribute('aria-expanded', 'false');
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }
    document.querySelectorAll('.profile-menu.open').forEach((menu) => {
        menu.classList.remove('open');
        menu.querySelector('[data-profile-menu]')?.setAttribute('aria-expanded', 'false');
    });
    document.querySelectorAll('.filter-menu.open').forEach((menu) => {
        menu.classList.remove('open');
        menu.querySelector('[data-filter-menu]')?.setAttribute('aria-expanded', 'false');
    });
});

document.querySelectorAll('[data-filter-menu]').forEach((button) => {
    const menu = button.closest('.filter-menu');
    button.addEventListener('click', (event) => {
        event.stopPropagation();
        const isOpen = menu.classList.toggle('open');
        button.setAttribute('aria-expanded', String(isOpen));
    });
});

document.querySelectorAll('[data-demo-login]').forEach((button) => {
    button.addEventListener('click', () => {
        const form = button.closest('form');
        const email = form?.querySelector('[data-login-email]');
        const password = form?.querySelector('[data-login-password]');
        if (email) {
            email.value = button.dataset.email || '';
            email.focus();
        }
        if (password) {
            password.value = 'password';
        }
    });
});

document.querySelectorAll('[data-dismiss]').forEach((button) => {
    button.addEventListener('click', () => {
        const flash = button.closest('.flash');
        flash?.classList.add('is-leaving');
        setTimeout(() => flash?.remove(), 160);
    });
});

document.querySelectorAll('.flash[role="status"]').forEach((flash) => {
    setTimeout(() => {
        flash.classList.add('is-leaving');
        setTimeout(() => flash.remove(), 160);
    }, 5500);
});

document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!confirm(form.dataset.confirm || 'Continue?')) {
            event.preventDefault();
        }
    });
});

let lastDialogTrigger = null;

const openDialog = (id, trigger = null) => {
    const dialog = document.getElementById(id);
    if (dialog?.showModal) {
        lastDialogTrigger = trigger;
        dialog.showModal();
        dialog.querySelector('[data-dialog-close], input, select, textarea, button, a')?.focus({ preventScroll: true });
    }
};

document.querySelectorAll('[data-dialog-open]').forEach((trigger) => {
    const open = () => openDialog(trigger.dataset.dialogOpen, trigger);
    trigger.addEventListener('click', (event) => {
        if (event.target.closest('a, button, input, select, textarea, label')) {
            return;
        }
        open();
    });
    trigger.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            open();
        }
    });
});

document.querySelectorAll('button[data-dialog-open]').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.stopPropagation();
        openDialog(button.dataset.dialogOpen, button);
    });
});

document.querySelectorAll('[data-dialog-close]').forEach((button) => {
    button.addEventListener('click', () => button.closest('dialog')?.close());
});

document.querySelectorAll('dialog.modal').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
    dialog.addEventListener('close', () => {
        if (lastDialogTrigger && document.contains(lastDialogTrigger)) {
            lastDialogTrigger.focus({ preventScroll: true });
        }
    });
});

if (window.__openDialogOnLoad) {
    openDialog(window.__openDialogOnLoad);
}

document.querySelectorAll('[data-validate]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (event.defaultPrevented) {
            return;
        }
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }
        if (!form.checkValidity()) {
            return;
        }
        form.dataset.submitting = 'true';
        form.classList.add('is-submitting');
        const submitter = event.submitter;
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            if (button !== submitter) {
                button.disabled = true;
            }
        });
        if (submitter) {
            submitter.setAttribute('aria-busy', 'true');
            submitter.dataset.originalText = submitter.textContent;
            submitter.textContent = 'Working...';
        }
    });
});

document.querySelectorAll('[data-print-page], [data-print-summary]').forEach((button) => {
    button.addEventListener('click', () => window.print());
});

document.querySelectorAll('[data-server-filter]').forEach((form) => {
    let timer;
    const search = form.querySelector('input[type="search"]');
    form.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', () => form.requestSubmit());
    });
    search?.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => form.requestSubmit(), 450);
    });
});

document.querySelectorAll('[data-tabs]').forEach((tabs) => {
    const buttons = Array.from(tabs.querySelectorAll('[data-tab]'));
    const panels = Array.from(tabs.querySelectorAll('[data-tab-panel]'));
    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.dataset.tab;
            buttons.forEach((item) => item.classList.toggle('active', item === button));
            panels.forEach((panel) => {
                panel.classList.toggle('active', panel.dataset.tabPanel === target);
            });
        });
    });
});

document.querySelectorAll('[data-client-selector]').forEach((select) => {
    let clients = {};
    try {
        clients = JSON.parse(select.dataset.clients || '{}');
    } catch {
        clients = {};
    }

    const form = select.closest('form');
    const map = {
        client_name: 'client_name',
        client_email: 'client_email',
        client_phone: 'client_phone',
        client_type: 'client_type',
        alternate_phone: 'alternate_phone',
        national_id: 'national_id',
        tax_number: 'tax_number',
        address_line1: 'address_line1',
        suburb: 'suburb',
        city: 'city',
        province: 'province',
        country: 'country',
        preferred_contact: 'preferred_contact',
        segment: 'segment',
        client_status: 'client_status',
        client_notes: 'client_notes'
    };

    const applyClient = () => {
        const client = clients[select.value];
        if (!client || !form) {
            form?.querySelectorAll('[data-client-linked]').forEach((field) => {
                field.removeAttribute('data-client-linked');
            });
            return;
        }

        Object.entries(map).forEach(([name, key]) => {
            const field = form.querySelector(`[name="${name}"]`);
            if (!field) {
                return;
            }
            field.value = client[key] ?? '';
            field.setAttribute('data-client-linked', 'true');
        });
    };

    select.addEventListener('change', applyClient);
});

document.querySelectorAll('[data-bulk-form]').forEach((form) => {
    const all = form.querySelector('[data-select-all]');
    const rows = Array.from(form.querySelectorAll('[data-row-select]'));
    const count = form.querySelector('[data-selected-count]');
    const selectionActions = Array.from(document.querySelectorAll(`[form="${form.id}"][data-requires-selection]`));
    const update = () => {
        const selected = rows.filter((checkbox) => checkbox.checked).length;
        form.classList.toggle('has-selection', selected > 0);
        rows.forEach((checkbox) => {
            checkbox.closest('tr')?.classList.toggle('is-selected', checkbox.checked);
        });
        if (count) {
            count.textContent = `${selected} selected`;
        }
        selectionActions.forEach((button) => {
            button.disabled = selected === 0;
        });
        if (all) {
            all.checked = selected > 0 && selected === rows.length;
            all.indeterminate = selected > 0 && selected < rows.length;
        }
    };
    all?.addEventListener('change', () => {
        rows.forEach((checkbox) => {
            checkbox.checked = all.checked;
        });
        update();
    });
    [...rows, all].filter(Boolean).forEach((checkbox) => {
        checkbox.addEventListener('click', (event) => event.stopPropagation());
    });
    rows.forEach((checkbox) => checkbox.addEventListener('change', update));
    update();
});

document.querySelectorAll('[data-dropzone]').forEach((zone) => {
    const input = zone.querySelector('input[type="file"]');
    const label = zone.querySelector('.drop-target');
    const title = label?.querySelector('strong');
    const subtitle = label?.querySelector('small');
    const selected = zone.querySelector('[data-selected-file]');
    const selectedName = zone.querySelector('[data-selected-file-name]');
    const selectedMeta = zone.querySelector('[data-selected-file-meta]');
    const submit = zone.querySelector('[data-upload-submit]');

    const setName = () => {
        const file = input.files?.[0];
        if (!file) {
            zone.classList.remove('has-file');
            if (selected) {
                selected.hidden = true;
            }
            if (submit) {
                submit.disabled = true;
            }
            return;
        }
        const sizeKb = Math.max(1, Math.ceil(file.size / 1024));
        const type = file.type || 'Selected file';
        zone.classList.add('has-file');
        if (title) {
            title.textContent = 'Document ready to upload';
        }
        if (subtitle) {
            subtitle.textContent = 'Review the file below, then upload.';
        }
        if (selected) {
            selected.hidden = false;
        }
        if (selectedName) {
            selectedName.textContent = file.name;
        }
        if (selectedMeta) {
            selectedMeta.textContent = `${sizeKb} KB · ${type}`;
        }
        if (submit) {
            submit.disabled = false;
        }
    };

    input?.addEventListener('change', setName);

    ['dragenter', 'dragover'].forEach((eventName) => {
        zone.addEventListener(eventName, (event) => {
            event.preventDefault();
            zone.classList.add('dragging');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        zone.addEventListener(eventName, (event) => {
            event.preventDefault();
            zone.classList.remove('dragging');
        });
    });

    zone.addEventListener('drop', (event) => {
        if (!input || !event.dataTransfer?.files?.length) {
            return;
        }
        input.files = event.dataTransfer.files;
        setName();
    });
});
