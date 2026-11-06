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
        return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
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
        var _c = useState({ state: 'open', reason: '', search: '', older_than_hours: '', page: 1 }), filters = _c[0], setFilters = _c[1];
        var _d = useState(1), totalPages = _d[0], setTotalPages = _d[1];
        var _e = useState(0), total = _e[0], setTotal = _e[1];
        var _f = useState(false), loading = _f[0], setLoading = _f[1];
        var _g = useState(''), error = _g[0], setError = _g[1];
        var _j = useState(''), notice = _j[0], setNotice = _j[1];
        var _k = useState(''), note = _k[0], setNote = _k[1];
        var _l = useState(''), nextState = _l[0], setNextState = _l[1];
        var _m = useState(''), outcome = _m[0], setOutcome = _m[1];
        var _n = useState(false), saving = _n[0], setSaving = _n[1];

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
        }, [filters.state, filters.reason, filters.search, filters.older_than_hours, filters.page]);

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
                })
                .catch(function (requestError) {
                    setError(requestError.message || __('Could not open this case.', 'wpessential-order-rescue-hub'));
                });
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
            selected.order ? h('section', { className: 'wperh-order-summary' },
                h('h3', null, __('Order summary', 'wpessential-order-rescue-hub')),
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
                        ['open', 'triaged', 'waiting', 'closed', 'all'].map(function (state) {
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
                h('div', { className: 'wperh-table-wrap' },
                    h('table', { className: 'widefat striped wperh-table' },
                        h('thead', null, h('tr', null,
                            h('th', { scope: 'col' }, __('Order', 'wpessential-order-rescue-hub')),
                            h('th', { scope: 'col' }, __('Exception', 'wpessential-order-rescue-hub')),
                            h('th', { scope: 'col' }, __('First detected', 'wpessential-order-rescue-hub')),
                            h('th', { scope: 'col' }, __('Priority', 'wpessential-order-rescue-hub')),
                            h('th', { scope: 'col' }, __('Case state', 'wpessential-order-rescue-hub')),
                            h('th', { scope: 'col' }, __('Last updated', 'wpessential-order-rescue-hub')),
                            h('th', { scope: 'col' }, __('Action', 'wpessential-order-rescue-hub'))
                        )),
                        h('tbody', null,
                            loading ? h('tr', null, h('td', { colSpan: 7, className: 'wperh-loading' }, __('Loading cases…', 'wpessential-order-rescue-hub'))) :
                            rows.length ? rows : h('tr', null, h('td', { colSpan: 7, className: 'wperh-empty' }, total ? __('No cases match these filters.', 'wpessential-order-rescue-hub') : __('No exceptions need attention. New qualifying order issues will appear here.', 'wpessential-order-rescue-hub')))
                        )
                    )
                ),
                h('nav', { className: 'wperh-pagination', 'aria-label': __('Case pages', 'wpessential-order-rescue-hub') },
                    h('button', { type: 'button', className: 'button', disabled: filters.page <= 1 || loading, onClick: function () { updateFilter('page', filters.page - 1); } }, __('Previous', 'wpessential-order-rescue-hub')),
                    h('span', { 'aria-live': 'polite' }, __('Page', 'wpessential-order-rescue-hub') + ' ' + filters.page + ' / ' + totalPages),
                    h('button', { type: 'button', className: 'button', disabled: filters.page >= totalPages || loading, onClick: function () { updateFilter('page', filters.page + 1); } }, __('Next', 'wpessential-order-rescue-hub'))
                )
            ),
            detail
        );
    }

    var root = document.getElementById('wperh-app');
    if (root) {
        wp.element.render(wp.element.createElement(App), root);
    }
}(window.wp));
