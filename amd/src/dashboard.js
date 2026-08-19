// This file is part of Moodle - http://moodle.org/

import Ajax from 'core/ajax';
import ModalEvents from 'core/modal_events';
import ModalSaveCancel from 'core/modal_save_cancel';
import Notification from 'core/notification';
import * as Confirm from 'core/notification';
import {getString} from 'core/str';

const renderOptions = (items, selected, name) => items.map(item =>
    `<label class="d-block"><input type="checkbox" name="${name}" value="${item.id}" ` +
    `${selected.includes(item.id) ? 'checked' : ''}> ${item.name}</label>`
).join('');

const updateQuickActions = (buttonGroup, rules, labels) => {
    const openButton = buttonGroup.querySelector('[data-action="quick-open"]');
    const closeButton = buttonGroup.querySelector('[data-action="quick-close"]');
    const closed = !rules.everyone && rules.groupids.length === 0 && rules.userids.length === 0;
    const isSection = openButton.dataset.itemtype === 'section';
    openButton.disabled = rules.everyone;
    openButton.setAttribute('aria-disabled', rules.everyone ? 'true' : 'false');
    let openLabel = isSection ? labels.open : labels.openaction;
    if (rules.everyone) {
        openLabel = `✓ ${labels.alreadyopen}`;
    }
    openButton.querySelector('[data-region="quick-label"]').textContent = openLabel;
    openButton.title = rules.everyone ? labels.alreadyopenhelp : labels.open;
    closeButton.disabled = closed;
    closeButton.setAttribute('aria-disabled', closed ? 'true' : 'false');
    let closeLabel = isSection ? labels.closeeveryone : labels.close;
    if (closed) {
        closeLabel = `✓ ${labels.alreadyclosed}`;
    }
    closeButton.querySelector('[data-region="quick-label"]').textContent = closeLabel;
    closeButton.title = closed ? labels.alreadyclosedhelp : labels.close;
};

const updateSectionChildren = (button, rules) => {
    if (button.dataset.itemtype !== 'section') {
        return;
    }
    const section = button.closest('section');
    const controls = section.querySelector('[data-region="module-controls"]');
    const notice = section.querySelector('[data-region="section-closed-notice"]');
    if (!controls || !notice) {
        return;
    }
    const closed = !rules.everyone && rules.groupids.length === 0 && rules.userids.length === 0;
    controls.hidden = closed;
    notice.hidden = !closed;
    controls.classList.toggle('d-none', closed);
    notice.classList.toggle('d-none', !closed);
};

const edit = async(config, button) => {
    const itemtype = button.dataset.itemtype;
    const itemid = Number(button.dataset.itemid);
    try {
        const rules = await Ajax.call([{
            methodname: 'availability_managed_get_item_rules',
            args: {courseid: config.courseid, itemtype, itemid},
        }])[0];
        const [title, everyone, groups, users] = await Promise.all([
            getString('edititem', 'availability_managed', button.dataset.itemname),
            getString('everyone', 'availability_managed'),
            getString('groups', 'availability_managed'),
            getString('users', 'availability_managed'),
        ]);
        const groupOptions = renderOptions(config.groups, rules.groupids, 'groups');
        const userOptions = renderOptions(config.users, rules.userids, 'users');
        const checked = rules.everyone ? 'checked' : '';
        const body = `<div data-region="managed-editor">
            <label class="d-block mb-3">
                <input type="checkbox" name="everyone" ${checked}> ${everyone}
            </label>
            <fieldset class="mb-3"><legend class="h6">${groups}</legend>${groupOptions}</fieldset>
            <fieldset><legend class="h6">${users}</legend>
                <div class="overflow-auto" style="max-height:15rem">${userOptions}</div>
            </fieldset></div>`;
        const modal = await ModalSaveCancel.create({title, body, show: true, removeOnClose: true});
        modal.getRoot().on(ModalEvents.save, async event => {
            event.preventDefault();
            const root = modal.getRoot()[0];
            const values = selector => [...root.querySelectorAll(`${selector}:checked`)].map(input => Number(input.value));
            try {
                const result = await Ajax.call([{
                    methodname: 'availability_managed_set_item_rules',
                    args: {
                        courseid: config.courseid, itemtype, itemid,
                        everyone: root.querySelector('[name="everyone"]').checked,
                        groupids: values('[name="groups"]'), userids: values('[name="users"]'),
                    },
                }])[0];
                const buttonGroup = button.closest('.btn-group');
                button.querySelector('[data-region="summary"]').textContent = result.summary;
                updateQuickActions(buttonGroup, result, config.quicklabels);
                updateSectionChildren(button, result);
                modal.hide();
                Notification.addNotification({message: await getString('changessaved'), type: 'success'});
            } catch (error) {
                Notification.exception(error);
            }
        });
    } catch (error) {
        Notification.exception(error);
    }
};

const setQuickState = async(config, button, everyone) => {
    try {
        const result = await Ajax.call([{
            methodname: 'availability_managed_set_item_rules',
            args: {
                courseid: config.courseid,
                itemtype: button.dataset.itemtype,
                itemid: Number(button.dataset.itemid),
                everyone,
                groupids: [],
                userids: [],
            },
        }])[0];
        const buttonGroup = button.closest('.btn-group');
        buttonGroup.querySelector('[data-region="summary"]').textContent = result.summary;
        updateQuickActions(buttonGroup, result, config.quicklabels);
        updateSectionChildren(button, result);
        Notification.addNotification({message: await getString('changessaved'), type: 'success'});
    } catch (error) {
        Notification.exception(error);
    }
};

const applyChildren = async(config, button) => {
    const message = await getString('applychildrenconfirm', 'availability_managed');
    Confirm.confirm('', message, await getString('yes'), await getString('no'), async() => {
        try {
            const result = await Ajax.call([{
                methodname: 'availability_managed_bulk_apply',
                args: {courseid: config.courseid, sectionid: Number(button.dataset.sectionid)},
            }])[0];
            const done = await getString('applychildrendone', 'availability_managed', result.updatedcount);
            Notification.addNotification({message: done, type: 'success'});
            window.location.reload();
        } catch (error) {
            Notification.exception(error);
        }
    });
};

const targetState = {scope: 'group', scopeid: 0};

const renderTargetOptions = (config, root) => {
    const items = targetState.scope === 'group' ? config.groups : config.users;
    const select = root.querySelector('[data-action="target-select"]');
    select.innerHTML = `<option value="">—</option>` + items.map(item =>
        `<option value="${item.id}">${item.name}</option>`
    ).join('');
    targetState.scopeid = 0;
    root.querySelector('[data-action="open-next"]').disabled = true;
    root.querySelector('[data-region="target-view"]').innerHTML = '';
};

const loadTargetView = async(config, root) => {
    if (!targetState.scopeid) {
        return;
    }
    try {
        const result = await Ajax.call([{
            methodname: 'availability_managed_get_target_view',
            args: {courseid: config.courseid, scope: targetState.scope, scopeid: targetState.scopeid},
        }])[0];
        const [openLabel, closedLabel] = await Promise.all([
            getString('open', 'availability_managed'),
            getString('closed', 'availability_managed'),
        ]);
        root.querySelector('[data-region="target-view"]').innerHTML = `<ul class="list-group">${result.sections.map(section =>
            `<li class="list-group-item d-flex justify-content-between"><span>${section.name}</span>` +
            `<strong>${section.open ? openLabel : closedLabel}</strong></li>`
        ).join('')}</ul>`;
    } catch (error) {
        Notification.exception(error);
    }
};

const openNext = async(config, root) => {
    try {
        const result = await Ajax.call([{
            methodname: 'availability_managed_open_next',
            args: {courseid: config.courseid, scope: targetState.scope, scopeid: targetState.scopeid},
        }])[0];
        const key = result.opened ? 'opennextdone' : 'opennextnone';
        const message = await getString(key, 'availability_managed', result.sectionname);
        Notification.addNotification({message, type: result.opened ? 'success' : 'info'});
        await loadTargetView(config, root);
    } catch (error) {
        Notification.exception(error);
    }
};

export const init = config => {
    const root = document.querySelector('[data-region="managed-dashboard"]');
    renderTargetOptions(config, root);
    root.addEventListener('click', event => {
        const button = event.target.closest('[data-action="edit"]');
        if (button) {
            edit(config, button);
        }
        const quickOpen = event.target.closest('[data-action="quick-open"]');
        const quickClose = event.target.closest('[data-action="quick-close"]');
        const bulk = event.target.closest('[data-action="apply-children"]');
        if (quickOpen) {
            setQuickState(config, quickOpen, true);
        } else if (quickClose) {
            setQuickState(config, quickClose, false);
        } else if (bulk) {
            applyChildren(config, bulk);
        }
        const scopeButton = event.target.closest('[data-action="target-scope"]');
        if (scopeButton) {
            root.querySelectorAll('[data-action="target-scope"]').forEach(item => item.classList.remove('active'));
            scopeButton.classList.add('active');
            targetState.scope = scopeButton.dataset.scope;
            renderTargetOptions(config, root);
        }
        if (event.target.closest('[data-action="open-next"]')) {
            openNext(config, root);
        }
    });
    root.querySelector('[data-action="target-select"]').addEventListener('change', event => {
        targetState.scopeid = Number(event.target.value);
        root.querySelector('[data-action="open-next"]').disabled = !targetState.scopeid;
        loadTargetView(config, root);
    });
    root.querySelector('[data-action="search"]').addEventListener('input', event => {
        const term = event.target.value.toLocaleLowerCase();
        root.querySelectorAll('[data-search-text]').forEach(item => {
            item.hidden = !item.dataset.searchText.toLocaleLowerCase().includes(term) &&
                !item.querySelector(`[data-search-text*="${CSS.escape(term)}" i]`);
        });
    });
};
