document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('auditDetailsModal');
    const changesList = document.getElementById('auditChangesList');
    const subtitle = document.getElementById('auditModalSubtitle');

    if (!modal || !changesList) {
        return;
    }

    const formatValue = (value) => {
        if (value === null || value === undefined || value === '') {
            return 'None';
        }

        if (typeof value === 'object') {
            return JSON.stringify(value, null, 2);
        }

        return String(value);
    };

    const fillText = (name, value) => {
        const node = modal.querySelector(`[data-audit-field="${name}"]`);

        if (node) {
            node.textContent = value || 'N/A';
        }
    };

    const renderChanges = (changes) => {
        changesList.innerHTML = '';

        if (!Array.isArray(changes) || changes.length === 0) {
            const empty = document.createElement('p');
            empty.textContent = 'No old or new values were recorded for this activity.';
            changesList.appendChild(empty);
            return;
        }

        changes.forEach((change) => {
            const row = document.createElement('div');
            row.className = 'audit-change-row';

            const field = document.createElement('div');
            const fieldLabel = document.createElement('span');
            const fieldValue = document.createElement('strong');
            fieldLabel.textContent = 'Changed Field';
            fieldValue.textContent = change.field || 'N/A';
            field.append(fieldLabel, fieldValue);

            const oldValue = document.createElement('div');
            const oldLabel = document.createElement('span');
            const oldCode = document.createElement('code');
            oldLabel.textContent = 'Old Value';
            oldCode.textContent = formatValue(change.old);
            oldValue.append(oldLabel, oldCode);

            const newValue = document.createElement('div');
            const newLabel = document.createElement('span');
            const newCode = document.createElement('code');
            newLabel.textContent = 'New Value';
            newCode.textContent = formatValue(change.new);
            newValue.append(newLabel, newCode);

            row.append(field, oldValue, newValue);
            changesList.appendChild(row);
        });
    };

    const openModal = (details) => {
        subtitle.textContent = `${details.action || 'Activity'} - ${details.module || 'N/A'}`;
        fillText('user', details.user);
        fillText('role', details.role);
        fillText('action', details.action);
        fillText('module', details.module);
        fillText('dateTime', details.dateTime);
        fillText('ipAddress', details.ipAddress);
        fillText('description', details.description);
        fillText('userAgent', details.userAgent);
        renderChanges(details.changes);
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        modal.hidden = true;
        document.body.style.overflow = '';
    };

    document.querySelectorAll('[data-audit-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const dataNode = document.getElementById(button.dataset.auditTarget);

            if (!dataNode) {
                return;
            }

            try {
                openModal(JSON.parse(dataNode.textContent));
            } catch (error) {
                openModal({
                    action: 'N/A',
                    module: 'N/A',
                    description: 'The audit details could not be loaded.',
                    changes: [],
                });
            }
        });
    });

    document.querySelectorAll('[data-audit-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
});
