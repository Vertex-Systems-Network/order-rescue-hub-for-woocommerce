(function (wp) {
    if (!wp || !wp.element || !wp.apiFetch) {
        return;
    }

    var h = wp.element.createElement;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    var __ = wp.i18n ? wp.i18n.__ : function (text) { return text; };

    function formatDate(value) {
        if (!value) {
            return '—';
        }
        var date = new Date(value.replace(' ', 'T') + 'Z');
        if (Number.isNaN(date.getTime())) {
            return value;
        }
        var zone = window.wperhSettings && window.wperhSettings.timezone;
        if (zone && /^[+-]\\d{2}:\\d{2}$/.test(zone)) {
            var sign = '-' === zone.charAt(0) ? -1 : 1;
            var parts = zone.slice(1).split(':').map(Number);
            var shifted = new Date(date.getTime() + sign * (parts[0] * 60 + parts[1]) * 60000);
            return shifted.toLocaleString(undefined, { timeZone: 'UTC' });
        }
        try {
            return zone ? date.toLocaleString(undefined, { timeZone: zone }) : date.toLocaleString();
        } catch (error) {
            return date.toLocaleString();
        }
    }

    function labelForReason(reason) {
        var labels = {
            pending_payment: __('Pending payment', 'wpessential-order-rescue-hub'),
            failed_payment: __('Failed payment', 'wpessential-order-rescue-hub'),
            missing_shipping: __('Missing shipping details', 'wpessential-order-rescue-hub'),
            stock_discrepancy: __('Stock issue', 'wpessential-order-rescue-hub'),
            processing_delay: __('Processing delay', 'wpessential-order-rescue-hub')
        };
        return labels[reason] || reason.replace(/_/g, ' ');
    }

    function labelForState(state) {
        var labels = {
            open: __('Open', 'wpessential-order-rescue-hub'),
            triaged: __('Triaged', 'wpessential-order-rescue-hub'),
            waiting: __('Waiting', 'wpessential-order-rescue-hub'),
            closed: __('Closed', 'wpessential-order-rescue-hub'),
            assigned: __('Assigned', 'wpessential-order-rescue-hub'),
            escalated: __('Escalated', 'wpessential-order-rescue-hub')
        };
        return labels[state] || state;
    }

    function App() {
        var _a = useState([]), cases = _a[0], setCases = _a[1];
        var _b = useState(null), selected = _b[0], setSelected = _b[1];
        var _c = useState({ state: 'open', reason: '', search: '', older_than_hours: '', assignee_id: '', sla_state: '', page: 1 }), filters = _c[0], setFilters = _c[1];
        var _d = useState(1), totalPages = _d[0], setTotalPages = _d[1];
        var _e = useState(0), total = _e[0], setTotal = _e[1];
        var _f = useState(false), loading = _f[0], setLoading = _f[1];
        var _g = useState(''), error = _g[0], setError = _g[1];
        var _j = useState(''), notice = _j[0], setNotice = _j[1];
        var _k = useState(''), note = _k[0], setNote = _k[1];
        var _l = useState(''), nextState = _l[0], setNextState = _l[1];
        var _m = useState(''), outcome = _m[0], setOutcome = _m[1];
        var _n = useState(false), saving = _n[0], setSaving = _n[1];
        var _o = useState(null), proSettings = _o[0], setProSettings = _o[1];
        var _p = useState([]), team = _p[0], setTeam = _p[1];
        var _q = useState(''), assignee = _q[0], setAssignee = _q[1];

        function loadCases() {
            setLoading(true);
            setError('');
            var query = new URLSearchParams();
            Object.keys(filters).forEach(function (key) {
                if (filters[key] !== '' && filters[key] !== null) {
                    query.set(key, filters[key]);
                }
            });
            query.set('per_page', String((window.wperhSettings && window.wperhSettings.pageSize) || 25));
            wp.apiFetch({ path: '/wperh/v1/cases?' + query.toString() })
                .then(function (response) {
                    setCases(response.items || []);
                    setTotal(Number(response.total || 0));
                    setTotalPages(Math.max(1, Number(response.total_pages || 1)));
                })
                .catch(function (requestError) {
                    setError(requestError.message || __('Could not load exception cases.', 'wpessential-order-rescue-hub'));
                })
                .finally(function () {
                    setLoading(false);
                });
        }

        useEffect(function () {
            loadCases();
        }, [filters.state, filters.reason, filters.search, filters.older_than_hours, filters.assignee_id, filters.sla_state, filters.page]);

        useEffect(function () {
            if (!(window.wperhSettings && window.wperhSettings.isPro)) {
                return;
            }
            var requests = [wp.apiFetch({ path: '/wperh/v1/pro/users' })];
            if (window.wperhSettings.canManageSettings) {
                requests.push(wp.apiFetch({ path: '/wperh/v1/pro/settings' }));
            }
            Promise.all(requests).then(function (result) {
                setTeam(result[0] || []);
                if (result[1]) {
                    setProSettings(result[1]);
                }
            }).catch(function (requestError) {
                setError(requestError.message || __('Could not load Pro settings.', 'wpessential-order-rescue-hub'));
            });
        }, []);

        function updateFilter(key, value) {
            setFilters(function (current) {
                var updated = Object.assign({}, current);
                updated[key] = value;
                if ('page' !== key) {
                    updated.page = 1;
                }
                return updated;
            });
        }

        function openCase(caseId) {
            setError('');
            setNotice('');
            setSelected(null);
            wp.apiFetch({ path: '/wperh/v1/cases/' + caseId })
                .then(function (item) {
                    setSelected(item);
                    setNextState(item.state);
                    setOutcome(item.outcome || '');
                    setAssignee(item.assignee_id ? String(item.assignee_id) : '');
                })
                .catch(function (requestError) {
                    setError(requestError.message || __('Could not open this case.', 'wpessential-order-rescue-hub'));
                });
        }

        function updatePro(key, value) {
            setProSettings(function (current) {
                return Object.assign({}, current || {}, { [key]: value });
            });
        }

        function updateRule(index, key, value) {
            setProSettings(function (current) {
                var rules = (current.rules || []).slice();
                rules[index] = Object.assign({}, rules[index], { [key]: value });
                return Object.assign({}, current, { rules: rules });
            });
        }

        function saveProSettings(event) {
            event.preventDefault();
            if (!proSettings) {
                return;
            }
            var invalidRule = (proSettings.rules || []).find(function (rule) {
                return !String(rule.name || '').trim() || ('assign_case' === rule.action && !Number(rule.value)) || ('set_priority' === rule.action && (Number(rule.value) < 1 || Number(rule.value) > 100));
            });
            if (invalidRule) {
                setError(__('Each workflow needs a name and a valid action value.', 'wpessential-order-rescue-hub'));
                return;
            }
            setSaving(true);
            setError('');
            var data = Object.assign({}, proSettings);
            data.recipients = String(proSettings.recipients_text || (proSettings.recipients || []).join(',')).split(/[,;]+/).map(function (email) { return email.trim(); }).filter(Boolean);
            data.holidays = String(proSettings.holidays_text || (proSettings.holidays || []).join(',')).split(/[;,\\n]+/).map(function (date) { return date.trim(); }).filter(Boolean);
            delete data.recipients_text;
            delete data.holidays_text;
            wp.apiFetch({ path: '/wperh/v1/pro/settings', method: 'POST', data: data })
                .then(function (saved) {
                    setProSettings(saved);
                    setNotice(__('Pro settings saved.', 'wpessential-order-rescue-hub'));
                })
                .catch(function (requestError) {
                    setError(requestError.message || __('Could not save Pro settings.', 'wpessential-order-rescue-hub'));
                })
                .finally(function () { setSaving(false); });
        }

        function testAlert() {
            setSaving(true);
            wp.apiFetch({ path: '/wperh/v1/pro/alerts/test', method: 'POST', data: {} })
                .then(function () { setNotice(__('Test alert sent to the configured administrators.', 'wpessential-order-rescue-hub')); })
                .catch(function (requestError) { setError(requestError.message || __('Test alert failed.', 'wpessential-order-rescue-hub')); })
                .finally(function () { setSaving(false); });
        }

        function saveAssignment(event) {
            event.preventDefault();
            if (!selected) {
                return;
            }
            setSaving(true);
            wp.apiFetch({
                path: '/wperh/v1/cases/' + selected.case_id + '/assignment',
                method: 'POST',
                data: { user_id: Number(assignee) }
            }).then(function () {
                setNotice(__('Case assignment saved.', 'wpessential-order-rescue-hub'));
                openCase(selected.case_id);
                loadCases();
            }).catch(function (requestError) {
                setError(requestError.message || __('Could not assign this case.', 'wpessential-order-rescue-hub'));
            }).finally(function () { setSaving(false); });
        }

        function addWorkflowRule() {
            var rules = (proSettings.rules || []).concat([{
                id: 'rule-' + Date.now(), name: '', enabled: false, dry_run: true,
                trigger: 'case.opened', reason: '', min_age_hours: 0,
                action: 'admin_alert', value: '', version: 1
            }]);
            updatePro('rules', rules);
        }

        function removeWorkflowRule(index) {
            updatePro('rules', (proSettings.rules || []).filter(function (_, i) { return i !== index; }));
        }

        function workflowRuleEditor(rule, index) {
            return h('fieldset', { className: 'wperh-rule-card', key: rule.id || index },
                h('legend', null, rule.name || __('New workflow rule', 'wpessential-order-rescue-hub')),
                h('label', null, __('Rule name', 'wpessential-order-rescue-hub'),
                    h('input', { type: 'text', maxLength: 80, value: rule.name, onChange: function (event) { updateRule(index, 'name', event.target.value); } })
                ),
                h('label', null, __('When', 'wpessential-order-rescue-hub'),
                    h('select', { value: rule.trigger, onChange: function (event) { updateRule(index, 'trigger', event.target.value); } },
                        h('option', { value: 'case.opened' }, __('A case opens', 'wpessential-order-rescue-hub')),
                        h('option', { value: 'case.age_check' }, __('Scheduled age check', 'wpessential-order-rescue-hub')),
                h('option', { value: 'sla.warning' }, __('SLA is approaching', 'wpessential-order-rescue-hub')),
                        h('option', { value: 'sla.breached' }, __('SLA is breached', 'wpessential-order-rescue-hub'))
                    )
                ),
                h('label', null, __('Only for case state (optional)', 'wpessential-order-rescue-hub'),
                    h('select', { value: rule.case_state || '', onChange: function (event) { updateRule(index, 'case_state', event.target.value); } },
                        h('option', { value: '' }, __('Any state', 'wpessential-order-rescue-hub')),
                        ['open', 'triaged', 'assigned', 'waiting', 'escalated'].map(function (state) { return h('option', { key: state, value: state }, labelForState(state)); })
                    )
                ),
                h('label', null, __('Only for reason (optional)', 'wpessential-order-rescue-hub'),
                    h('select', { value: rule.reason || '', onChange: function (event) { updateRule(index, 'reason', event.target.value); } },
                        h('option', { value: '' }, __('Any reason', 'wpessential-order-rescue-hub')),
                        ['pending_payment', 'failed_payment', 'missing_shipping', 'stock_discrepancy', 'processing_delay'].map(function (reason) {
                            return h('option', { key: reason, value: reason }, labelForReason(reason));
                        })
                    )
                ),
                h('label', null, __('Minimum age (hours)', 'wpessential-order-rescue-hub'),
                    h('input', { type: 'number', min: 0, max: 2160, value: rule.min_age_hours, onChange: function (event) { updateRule(index, 'min_age_hours', Number(event.target.value)); } })
                ),
                h('label', null, __('Then', 'wpessential-order-rescue-hub'),
                    h('select', { value: rule.action, onChange: function (event) { updateRule(index, 'action', event.target.value); } },
                        h('option', { value: 'admin_alert' }, __('Notify administrators', 'wpessential-order-rescue-hub')),
                        h('option', { value: 'assign_case' }, __('Assign case', 'wpessential-order-rescue-hub')),
                        h('option', { value: 'set_priority' }, __('Set case priority', 'wpessential-order-rescue-hub')),
                        h('option', { value: 'set_case_state' }, __('Change case state', 'wpessential-order-rescue-hub'))
                    )
                ),
                'assign_case' === rule.action ? h('label', null, __('Assign to', 'wpessential-order-rescue-hub'),
                    h('select', { value: rule.value || '', onChange: function (event) { updateRule(index, 'value', event.target.value); } },
                        h('option', { value: '' }, __('Choose a staff member', 'wpessential-order-rescue-hub')),
                        team.map(function (member) { return h('option', { key: member.id, value: String(member.id) }, member.name); })
                    )
                ) : 'set_priority' === rule.action ? h('label', null, __('Priority (1 to 100)', 'wpessential-order-rescue-hub'),
                    h('input', { type: 'number', min: 1, max: 100, value: rule.value || 50, onChange: function (event) { updateRule(index, 'value', event.target.value); } })
                ) : 'set_case_state' === rule.action ? h('label', null, __('Case state', 'wpessential-order-rescue-hub'),
                    h('select', { value: rule.value || 'triaged', onChange: function (event) { updateRule(index, 'value', event.target.value); } },
                        h('option', { value: 'open' }, __('Open', 'wpessential-order-rescue-hub')),
                        h('option', { value: 'triaged' }, __('Triaged', 'wpessential-order-rescue-hub')),
                        h('option', { value: 'waiting' }, __('Waiting', 'wpessential-order-rescue-hub'))
                    )
                ) : null,
                h('label', { className: 'wperh-inline-check' },
                    h('input', { type: 'checkbox', checked: !!rule.enabled, onChange: function (event) { updateRule(index, 'enabled', event.target.checked); } }),
                    __('Enable this rule', 'wpessential-order-rescue-hub')
                ),
                h('label', { className: 'wperh-inline-check' },
                    h('input', { type: 'checkbox', checked: !!rule.dry_run, onChange: function (event) { updateRule(index, 'dry_run', event.target.checked); } }),
                    __('Dry run only (recommended)', 'wpessential-order-rescue-hub')
                ),
                h('button', { type: 'button', className: 'button-link-delete', onClick: function () { removeWorkflowRule(index); } }, __('Remove rule', 'wpessential-order-rescue-hub'))
            );
        }

        function saveNote(event) {
            event.preventDefault();
            if (!selected || !note.trim()) {
                return;
            }
            setSaving(true);
            setError('');
            wp.apiFetch({
                path: '/wperh/v1/cases/' + selected.case_id + '/notes',
                method: 'POST',
                data: { note: note }
            }).then(function (updated) {
                setSelected(updated);
                setNote('');
                setNotice(__('Private note saved.', 'wpessential-order-rescue-hub'));
                loadCases();
            }).catch(function (requestError) {
                setError(requestError.message || __('Could not save the private note.', 'wpessential-order-rescue-hub'));
            }).finally(function () {
                setSaving(false);
            });
        }

        function saveTransition(event) {
            event.preventDefault();
            if (!selected || nextState === selected.state) {
                return;
            }
            setSaving(true);
            setError('');
            wp.apiFetch({
                path: '/wperh/v1/cases/' + selected.case_id + '/transition',
                method: 'POST',
                data: {
                    state: nextState,
                    outcome: outcome,
                    row_version: selected.row_version
                }
            }).then(function (updated) {
                setSelected(updated);
                setNotice(__('Case updated.', 'wpessential-order-rescue-hub'));
                loadCases();
            }).catch(function (requestError) {
                setError(requestError.message || __('Could not update this case.', 'wpessential-order-rescue-hub'));
            }).finally(function () {
                setSaving(false);
            });
        }

        var searchTimer = null;
        function searchChanged(event) {
            var value = event.target.value;
            if (searchTimer) {
                window.clearTimeout(searchTimer);
            }
            searchTimer = window.setTimeout(function () {
                setFilters(function (current) {
                    return Object.assign({}, current, { search: value, page: 1 });
                });
            }, 250);
        }

        var proPanel = window.wperhSettings && window.wperhSettings.isPro && window.wperhSettings.canManageSettings && proSettings ? h('section', { className: 'wperh-pro-panel', 'aria-labelledby': 'wperh-pro-settings-title' },
            h('h2', { id: 'wperh-pro-settings-title' }, __('Pro settings', 'wpessential-order-rescue-hub')),
            h('form', { onSubmit: saveProSettings },
                h('fieldset', { className: 'wperh-settings-card' },
                    h('legend', null, __('Admin alerts', 'wpessential-order-rescue-hub')),
                    h('label', { className: 'wperh-inline-check' },
                        h('input', { type: 'checkbox', checked: !!proSettings.alerts_enabled, onChange: function (event) { updatePro('alerts_enabled', event.target.checked); } }),
                        __('Email administrators about new cases and SLA changes', 'wpessential-order-rescue-hub')
                    ),
                    h('label', null, __('Recipient email addresses (comma separated)', 'wpessential-order-rescue-hub'),
                        h('input', { type: 'text', value: proSettings.recipients_text !== undefined ? proSettings.recipients_text : (proSettings.recipients || []).join(', '), onChange: function (event) { updatePro('recipients_text', event.target.value); } })
                    ),
                    h('button', { type: 'button', className: 'button', onClick: testAlert, disabled: saving }, __('Send test alert', 'wpessential-order-rescue-hub'))
                ),
                h('fieldset', { className: 'wperh-settings-card' },
                    h('legend', null, __('SLA policy', 'wpessential-order-rescue-hub')),
                    h('p', null, __('Store timezone: ', 'wpessential-order-rescue-hub') + proSettings.timezone),
                    h('p', null, __('SLA policy changes apply to newly detected cases. Open cases retain their recorded deadlines.', 'wpessential-order-rescue-hub')),
                    h('p', null, __('Store timezone: ', 'wpessential-order-rescue-hub') + proSettings.timezone),
                    h('label', null, __('First response target (hours)', 'wpessential-order-rescue-hub'),
                        h('input', { type: 'number', min: 1, max: 720, value: proSettings.response_hours, onChange: function (event) { updatePro('response_hours', Number(event.target.value)); } })
                    ),
                    h('label', null, __('Resolution target (hours)', 'wpessential-order-rescue-hub'),
                        h('input', { type: 'number', min: 1, max: 2160, value: proSettings.resolution_hours, onChange: function (event) { updatePro('resolution_hours', Number(event.target.value)); } })
                    ),
                    h('label', null, __('Warning threshold (% of target elapsed)', 'wpessential-order-rescue-hub'),
                        h('input', { type: 'number', min: 25, max: 95, value: proSettings.warning_percent, onChange: function (event) { updatePro('warning_percent', Number(event.target.value)); } })
                    ),
                    h('label', null, __('Clock', 'wpessential-order-rescue-hub'),
                        h('select', { value: proSettings.clock_mode, onChange: function (event) { updatePro('clock_mode', event.target.value); } },
                            h('option', { value: 'calendar' }, __('Calendar hours', 'wpessential-order-rescue-hub')),
                            h('option', { value: 'business' }, __('Business hours', 'wpessential-order-rescue-hub'))
                        )
                    ),
                    'business' === proSettings.clock_mode ? h('div', { className: 'wperh-business-hours' },
                        h('label', null, __('Workday starts', 'wpessential-order-rescue-hub'), h('input', { type: 'time', value: proSettings.work_start, onChange: function (event) { updatePro('work_start', event.target.value); } })),
                        h('label', null, __('Workday ends', 'wpessential-order-rescue-hub'), h('input', { type: 'time', value: proSettings.work_end, onChange: function (event) { updatePro('work_end', event.target.value); } })),
                        h('label', null, __('Timezone (IANA, e.g. Europe/London)', 'wpessential-order-rescue-hub'), h('input', { type: 'text', value: proSettings.timezone, onChange: function (event) { updatePro('timezone', event.target.value); } })),
                        h('label', null, __('Holidays (YYYY-MM-DD, comma separated)', 'wpessential-order-rescue-hub'), h('input', { type: 'text', value: proSettings.holidays_text !== undefined ? proSettings.holidays_text : (proSettings.holidays || []).join(', '), onChange: function (event) { updatePro('holidays_text', event.target.value); } })),
                        h('fieldset', null,
                            h('legend', null, __('Working days', 'wpessential-order-rescue-hub')),
                            ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map(function (day, index) {
                                var dayNumber = index + 1;
                                return h('label', { className: 'wperh-inline-check', key: day },
                                    h('input', { type: 'checkbox', checked: (proSettings.workdays || []).indexOf(dayNumber) !== -1, onChange: function (event) {
                                        var days = (proSettings.workdays || []).slice();
                                        updatePro('workdays', event.target.checked ? days.concat([dayNumber]) : days.filter(function (value) { return value !== dayNumber; }));
                                    } }),
                                    day
                                );
                            })
                        )
                    ) : null
                ),
                h('div', { className: 'wperh-approval-policy' },
                    h('strong', null, __('Order action safety', 'wpessential-order-rescue-hub')),
                    h('p', null, __('Payment capture, refunds, cancellations, and payment-related order status changes remain disabled. If introduced in a later release, each action must require explicit human approval and a fresh order-state check.', 'wpessential-order-rescue-hub'))
                ),
                h('div', { className: 'wperh-workflows-heading' },
                    h('h3', null, __('Workflow rules', 'wpessential-order-rescue-hub')),
                    h('p', null, __('Rules are limited to internal case actions and administrator notices. They never capture, refund, cancel, or change WooCommerce orders.', 'wpessential-order-rescue-hub'))
                ),
                (proSettings.rules || []).map(workflowRuleEditor),
                h('button', { type: 'button', className: 'button', onClick: addWorkflowRule }, __('Add workflow rule', 'wpessential-order-rescue-hub')),
                h('button', { type: 'submit', className: 'button button-primary', disabled: saving }, saving ? __('Saving…', 'wpessential-order-rescue-hub') : __('Save Pro settings', 'wpessential-order-rescue-hub'))
            )
        ) : null;

        var rows = cases.map(function (item) {
            var reasons = (item.reason_codes || []).map(labelForReason).join(', ');
            var age = item.first_detected_at ? formatDate(item.first_detected_at) : '—';
            return h('tr', { key: item.case_id },
                h('td', null, h('button', {
                    type: 'button',
                    className: 'wperh-link-button',
                    onClick: function () { openCase(item.case_id); }
                }, '#' + item.order_id)),
                h('td', null, h('strong', null, reasons || __('Review case details', 'wpessential-order-rescue-hub'))),
                h('td', null, age),
                h('td', null, h('span', { className: 'wperh-priority' }, item.priority <= 20 ? __('High', 'wpessential-order-rescue-hub') : __('Normal', 'wpessential-order-rescue-hub'))),
                h('td', null, h('span', { className: 'wperh-state wperh-state-' + item.state }, labelForState(item.state))),
                window.wperhSettings && window.wperhSettings.isPro ? h('td', null,
                    h('span', { className: 'wperh-sla-state wperh-sla-' + (item.sla_state || 'running') }, item.sla_state || __('Starting', 'wpessential-order-rescue-hub')),
                    item.sla_resolution_due_at ? h('small', null, formatDate(item.sla_resolution_due_at)) : null
                ) : null,
                window.wperhSettings && window.wperhSettings.isPro ? h('td', null, team.filter(function (member) { return Number(member.id) === Number(item.assignee_id); }).map(function (member) { return member.name; })[0] || __('Unassigned', 'wpessential-order-rescue-hub')) : null,
                h('td', null, formatDate(item.updated_at)),
                h('td', null, h('button', {
                    type: 'button',
                    className: 'button button-secondary',
                    onClick: function () { openCase(item.case_id); }
                }, __('Review', 'wpessential-order-rescue-hub')))
            );
        });

        var detail = selected ? h('aside', {
            className: 'wperh-detail',
            role: 'region',
            'aria-labelledby': 'wperh-detail-title'
        },
            h('div', { className: 'wperh-detail-header' },
                h('div', null,
                    h('h2', { id: 'wperh-detail-title' }, __('Order', 'wpessential-order-rescue-hub') + ' #' + selected.order_id),
                    h('p', null, (selected.reason_codes || []).map(labelForReason).join(', ') || __('No active reason recorded', 'wpessential-order-rescue-hub'))
                ),
                h('button', { type: 'button', className: 'button', onClick: function () { setSelected(null); }, 'aria-label': __('Close case details', 'wpessential-order-rescue-hub') }, '×')
            ),
            window.wperhSettings && window.wperhSettings.isPro ? h('section', { className: 'wperh-pro-case-fields', 'aria-label': __('Pro case routing and SLA', 'wpessential-order-rescue-hub') },
                h('p', null, h('strong', null, __('SLA status: ', 'wpessential-order-rescue-hub')), selected.sla_state || __('Starting', 'wpessential-order-rescue-hub')),
                h('p', null, __('Deadlines use the store timezone: ', 'wpessential-order-rescue-hub') + ((window.wperhSettings && window.wperhSettings.timezone) || 'UTC')),
                h('p', null, h('strong', null, __('Assigned to: ', 'wpessential-order-rescue-hub')), team.filter(function (member) { return Number(member.id) === Number(selected.assignee_id); }).map(function (member) { return member.name; })[0] || __('Unassigned', 'wpessential-order-rescue-hub')),
                h('p', null, h('strong', null, __('Assigned to: ', 'wpessential-order-rescue-hub')), team.filter(function (member) { return Number(member.id) === Number(selected.assignee_id); }).map(function (member) { return member.name; })[0] || __('Unassigned', 'wpessential-order-rescue-hub')),
                selected.sla_response_due_at ? h('p', null, __('First response due: ', 'wpessential-order-rescue-hub') + formatDate(selected.sla_response_due_at)) : null,
                selected.sla_resolution_due_at ? h('p', null, __('Resolution due: ', 'wpessential-order-rescue-hub') + formatDate(selected.sla_resolution_due_at)) : null,
                h('form', { onSubmit: saveAssignment },
                    h('label', { htmlFor: 'wperh-assignee' }, __('Assign to staff member', 'wpessential-order-rescue-hub')),
                    h('select', { id: 'wperh-assignee', value: assignee, onChange: function (event) { setAssignee(event.target.value); } },
                        h('option', { value: '' }, __('Unassign case', 'wpessential-order-rescue-hub')),
                        team.map(function (member) { return h('option', { key: member.id, value: String(member.id) }, member.name); })
                    ),
                    h('button', { type: 'submit', className: 'button', disabled: saving }, __('Save assignment', 'wpessential-order-rescue-hub'))
                )
            ) : null,
            selected.order ? h('section', { className: 'wperh-order-summary' },
                h('h3', null, __('Order summary', 'wpessential-order-rescue-hub')),
                selected.order.admin_url ? h('p', null, h('a', { href: selected.order.admin_url }, __('Open order in WooCommerce', 'wpessential-order-rescue-hub'))) : null,
                h('dl', null,
                    h('dt', null, __('WooCommerce status', 'wpessential-order-rescue-hub')),
                    h('dd', null, selected.order.status),
                    h('dt', null, __('Payment method', 'wpessential-order-rescue-hub')),
                    h('dd', null, selected.order.payment_method || '—'),
                    h('dt', null, __('Order total', 'wpessential-order-rescue-hub')),
                    h('dd', null, selected.order.total_label || (selected.order.total + ' ' + selected.order.currency)),
                    h('dt', null, __('Shipping details', 'wpessential-order-rescue-hub')),
                    h('dd', null, selected.order.shipping_missing ? __('Incomplete', 'wpessential-order-rescue-hub') : __('Available or not required', 'wpessential-order-rescue-hub')),
                    h('dt', null, __('Created', 'wpessential-order-rescue-hub')),
                    h('dd', null, selected.order.date || '—')
                ),
                selected.order.items && selected.order.items.length ? h('ul', { className: 'wperh-item-list' },
                    selected.order.items.map(function (item, index) {
                        return h('li', { key: index }, item.name + ' × ' + item.quantity);
                    })
                ) : null
            ) : h('p', { className: 'notice inline' }, __('The WooCommerce order is unavailable. Case history is retained.', 'wpessential-order-rescue-hub')),
            h('form', { className: 'wperh-transition-form', onSubmit: saveTransition },
                h('label', { htmlFor: 'wperh-state' }, __('Case state', 'wpessential-order-rescue-hub')),
                h('select', { id: 'wperh-state', value: nextState, onChange: function (event) { setNextState(event.target.value); } },
                    ['open', 'triaged', 'waiting', 'closed'].map(function (state) {
                        return h('option', { key: state, value: state }, labelForState(state));
                    })
                ),
                'closed' === nextState ? h('div', { className: 'wperh-outcome-field' },
                    h('label', { htmlFor: 'wperh-outcome' }, __('Resolution outcome', 'wpessential-order-rescue-hub')),
                    h('select', { id: 'wperh-outcome', value: outcome, required: true, onChange: function (event) { setOutcome(event.target.value); } },
                        h('option', { value: '' }, __('Choose an outcome', 'wpessential-order-rescue-hub')),
                        h('option', { value: 'resolved' }, __('Resolved', 'wpessential-order-rescue-hub')),
                        h('option', { value: 'dismissed' }, __('Dismissed', 'wpessential-order-rescue-hub')),
                        h('option', { value: 'duplicate' }, __('Duplicate', 'wpessential-order-rescue-hub')),
                        h('option', { value: 'no_action_required' }, __('No action required', 'wpessential-order-rescue-hub'))
                    )
                ) : null,
                h('button', { type: 'submit', className: 'button button-primary', disabled: saving || nextState === selected.state }, saving ? __('Saving…', 'wpessential-order-rescue-hub') : __('Save case state', 'wpessential-order-rescue-hub'))
            ),
            h('form', { className: 'wperh-note-form', onSubmit: saveNote },
                h('label', { htmlFor: 'wperh-note' }, __('Private resolution note', 'wpessential-order-rescue-hub')),
                h('p', { className: 'description' }, __('Internal Hub note only. This is not sent to the customer.', 'wpessential-order-rescue-hub')),
                h('textarea', { id: 'wperh-note', rows: 4, maxLength: 2000, value: note, onChange: function (event) { setNote(event.target.value); } }),
                h('button', { type: 'submit', className: 'button', disabled: saving || !note.trim() }, __('Add private note', 'wpessential-order-rescue-hub'))
            ),
            h('section', { className: 'wperh-history' },
                h('h3', null, __('Case history', 'wpessential-order-rescue-hub')),
                selected.events && selected.events.length ? h('ol', null, selected.events.map(function (event) {
                    var description = event.event_type.replace(/_/g, ' ');
                    if (event.payload && event.payload.note) {
                        description += ': ' + event.payload.note;
                    } else if (event.payload && event.payload.to) {
                        description += ': ' + labelForState(event.payload.to);
                    } else if (event.payload && event.payload.reasons) {
                        description += ': ' + event.payload.reasons.map(labelForReason).join(', ');
                    }
                    return h('li', { key: event.event_id },
                        h('strong', null, description),
                        h('span', null, (event.actor_name ? event.actor_name + ' · ' : '') + formatDate(event.occurred_at))
                    );
                })) : h('p', null, __('No activity recorded yet.', 'wpessential-order-rescue-hub'))
            )
        ) : null;

        return h('div', { className: 'wperh-layout' },
            h('main', { className: 'wperh-main' },
                h('div', { className: 'wperh-heading' },
                    h('div', null,
                        h('h2', null, __('Exceptions', 'wpessential-order-rescue-hub')),
                        h('p', null, __('Review order exceptions, understand why they were flagged, and record the resolution.', 'wpessential-order-rescue-hub'))
                    ),
                    h('span', { className: 'wperh-count' }, total + ' ' + __('cases', 'wpessential-order-rescue-hub'))
                ),
                error ? h('div', { className: 'notice notice-error' }, h('p', null, error)) : null,
                notice ? h('div', { className: 'notice notice-success' }, h('p', null, notice)) : null,
                h('div', { className: 'wperh-filters' },
                    h('label', { htmlFor: 'wperh-search' }, __('Search order ID', 'wpessential-order-rescue-hub')),
                    h('input', { id: 'wperh-search', type: 'search', placeholder: __('e.g. 1042', 'wpessential-order-rescue-hub'), onChange: searchChanged }),
                    h('label', { htmlFor: 'wperh-state-filter' }, __('Case state', 'wpessential-order-rescue-hub')),
                    h('select', { id: 'wperh-state-filter', value: filters.state, onChange: function (event) { updateFilter('state', event.target.value); } },
                        ['open', 'triaged', 'assigned', 'waiting', 'escalated', 'closed', 'all'].map(function (state) {
                            return h('option', { key: state, value: state }, 'all' === state ? __('All states', 'wpessential-order-rescue-hub') : labelForState(state));
                        })
                    ),
                    h('label', { htmlFor: 'wperh-reason-filter' }, __('Exception reason', 'wpessential-order-rescue-hub')),
                    h('select', { id: 'wperh-reason-filter', value: filters.reason, onChange: function (event) { updateFilter('reason', event.target.value); } },
                        h('option', { value: '' }, __('All reasons', 'wpessential-order-rescue-hub')),
                        ['pending_payment', 'failed_payment', 'missing_shipping', 'stock_discrepancy', 'processing_delay'].map(function (reason) {
                            return h('option', { key: reason, value: reason }, labelForReason(reason));
                        })
                    ),
                    h('label', { htmlFor: 'wperh-age-filter' }, __('Minimum age', 'wpessential-order-rescue-hub')),
                    h('select', { id: 'wperh-age-filter', value: filters.older_than_hours, onChange: function (event) { updateFilter('older_than_hours', event.target.value); } },
                        h('option', { value: '' }, __('Any age', 'wpessential-order-rescue-hub')),
                        h('option', { value: '24' }, __('Older than 24 hours', 'wpessential-order-rescue-hub')),
                        h('option', { value: '72' }, __('Older than 3 days', 'wpessential-order-rescue-hub')),
                        h('option', { value: '168' }, __('Older than 7 days', 'wpessential-order-rescue-hub'))
                    )
                ),
                window.wperhSettings && window.wperhSettings.isPro ? h('div', { className: 'wperh-pro-filters' },
                    h('label', { htmlFor: 'wperh-owner-filter' }, __('Assigned owner', 'wpessential-order-rescue-hub')),
                    h('select', { id: 'wperh-owner-filter', value: filters.assignee_id, onChange: function (event) { updateFilter('assignee_id', event.target.value); } },
                        h('option', { value: '' }, __('All owners', 'wpessential-order-rescue-hub')),
                        team.map(function (member) { return h('option', { key: member.id, value: String(member.id) }, member.name); })
                    ),
                    h('label', { htmlFor: 'wperh-sla-filter' }, __('SLA state', 'wpessential-order-rescue-hub')),
                    h('select', { id: 'wperh-sla-filter', value: filters.sla_state, onChange: function (event) { updateFilter('sla_state', event.target.value); } },
                        h('option', { value: '' }, __('All SLA states', 'wpessential-order-rescue-hub')),
                        ['running', 'warning', 'breached', 'paused', 'met'].map(function (state) { return h('option', { key: state, value: state }, state); })
                    )
                ) : null,
                h('div', { className: 'wperh-table-wrap' },
                    h('table', { className: 'widefat striped wperh-table' },
                        h('thead', null, h('tr', null,
                            h('th', { scope: 'col' }, __('Order', 'wpessential-order-rescue-hub')),
                            h('th', { scope: 'col' }, __('Exception', 'wpessential-order-rescue-hub')),
                            h('th', { scope: 'col' }, __('First detected', 'wpessential-order-rescue-hub')),
                            h('th', { scope: 'col' }, __('Priority', 'wpessential-order-rescue-hub')),
                            h('th', { scope: 'col' }, __('Case state', 'wpessential-order-rescue-hub')),
                            window.wperhSettings && window.wperhSettings.isPro ? h('th', { scope: 'col' }, __('SLA', 'wpessential-order-rescue-hub')) : null,
                            window.wperhSettings && window.wperhSettings.isPro ? h('th', { scope: 'col' }, __('Assigned to', 'wpessential-order-rescue-hub')) : null,
                            h('th', { scope: 'col' }, __('Last updated', 'wpessential-order-rescue-hub')),
                            h('th', { scope: 'col' }, __('Action', 'wpessential-order-rescue-hub'))
                        )),
                        h('tbody', null,
                            loading ? h('tr', null, h('td', { colSpan: window.wperhSettings && window.wperhSettings.isPro ? 9 : 7, className: 'wperh-loading' }, __('Loading cases…', 'wpessential-order-rescue-hub'))) :
                            rows.length ? rows : h('tr', null, h('td', { colSpan: window.wperhSettings && window.wperhSettings.isPro ? 9 : 7, className: 'wperh-empty' }, total ? __('No cases match these filters.', 'wpessential-order-rescue-hub') : __('No exceptions need attention. New qualifying order issues will appear here.', 'wpessential-order-rescue-hub')))
                        )
                    )
                ),
                h('nav', { className: 'wperh-pagination', 'aria-label': __('Case pages', 'wpessential-order-rescue-hub') },
                    h('button', { type: 'button', className: 'button', disabled: filters.page <= 1 || loading, onClick: function () { updateFilter('page', filters.page - 1); } }, __('Previous', 'wpessential-order-rescue-hub')),
                    h('span', { 'aria-live': 'polite' }, __('Page', 'wpessential-order-rescue-hub') + ' ' + filters.page + ' / ' + totalPages),
                    h('button', { type: 'button', className: 'button', disabled: filters.page >= totalPages || loading, onClick: function () { updateFilter('page', filters.page + 1); } }, __('Next', 'wpessential-order-rescue-hub'))
                ),
                proPanel
            ),
            detail
        );
    }

    var root = document.getElementById('wperh-app');
    if (root) {
        wp.element.render(wp.element.createElement(App), root);
    }
}(window.wp));
