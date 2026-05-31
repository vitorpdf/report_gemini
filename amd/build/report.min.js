
define(['jquery'], function($) {

    'use strict';

    /**
     * Escape HTML to prevent XSS.
     * @param {*} val
     * @returns {string}
     */
    function esc(val) {
        if (val === null || val === undefined) { return '—'; }
        return String(val)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /**
     * Format integer with pt-BR thousands separator.
     * @param {*} val
     * @returns {string}
     */
    function fmtNumber(val) {
        var n = parseFloat(val);
        if (isNaN(n)) { return esc(val); }
        return n.toLocaleString('pt-BR');
    }

    /**
     * Build the complete <table> inner HTML.
     * @param {Array}  columns      [[key, label], ...]
     * @param {Array}  numericCols  Array of keys that are numeric.
     * @param {Array}  items        Data rows.
     * @returns {string}
     */
    function buildTable(columns, numericCols, items) {
        var numSet = {};
        (numericCols || []).forEach(function(k) { numSet[k] = true; });

        // Also auto-detect numeric columns from first row.
        if (items.length > 0) {
            columns.forEach(function(col) {
                if (typeof items[0][col[0]] === 'number') {
                    numSet[col[0]] = true;
                }
            });
        }

        // ── <thead> ────────────────────────────────────────────────────────────
        var thead = '<thead class="table-dark"><tr>';
        columns.forEach(function(col) {
            var align = numSet[col[0]] ? ' class="text-end"' : '';
            thead += '<th scope="col"' + align + '>' + esc(col[1]) + '</th>';
        });
        thead += '</tr></thead>';

        // ── <tbody> ────────────────────────────────────────────────────────────
        var tbody = '<tbody>';
        items.forEach(function(row) {
            tbody += '<tr>';
            columns.forEach(function(col) {
                var k   = col[0];
                var val = row[k];
                if (numSet[k]) {
                    tbody += '<td class="text-end font-monospace">' + fmtNumber(val) + '</td>';
                } else {
                    tbody += '<td>' + esc(val) + '</td>';
                }
            });
            tbody += '</tr>';
        });
        tbody += '</tbody>';

        return thead + tbody;
    }

    /**
     * Initialise the report page.
     * @param {Object} params
     */
    function init(params) {
        var $select   = $('#gemini-preset-select');
        var $genBtn   = $('#gemini-generate-btn');
        var $clearBtn = $('#gemini-clear-btn');
        var $error    = $('#gemini-error');
        var $results  = $('#gemini-results');
        var $title    = $('#gemini-results-title');
        var $table    = $('#gemini-table');
        var $meta     = $('#gemini-meta');
        var $spinner  = $genBtn.find('.spinner-border');
        var $label    = $genBtn.find('.btn-label');

        function showError(msg) {
            $error.removeClass('d-none').html('<strong>⚠ Erro:</strong> ' + esc(msg));
            $results.addClass('d-none');
        }

        function hideError() {
            $error.addClass('d-none').html('');
        }

        function setLoading(on) {
            $genBtn.prop('disabled', on);
            $select.prop('disabled', on);
            if (on) {
                $spinner.removeClass('d-none');
                $label.text(params.strings.generating);
            } else {
                $spinner.addClass('d-none');
                $label.text(params.strings.generate);
            }
        }

        function generateReport() {
            var preset = $select.val();
            if (!preset) { return; }

            hideError();
            $results.addClass('d-none');
            $clearBtn.addClass('d-none');
            setLoading(true);

            $.ajax({
                url:         params.ajax_url,
                method:      'POST',
                contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                data: {
                    sesskey: params.sesskey,
                    preset:  preset,
                },
                dataType: 'json',
            })
            .done(function(data) {
                if (data.error) {
                    showError(data.error);
                    return;
                }

                if (!data.items || data.items.length === 0) {
                    showError(params.strings.errornoresults);
                    return;
                }

                // Render table.
                $title.text(data.title);
                $table.html(buildTable(data.columns, data.numeric_cols, data.items));

                // Meta info.
                var now = new Date();
                var ts  = now.toLocaleDateString('pt-BR') + ' às ' + now.toLocaleTimeString('pt-BR');
                $meta.html(
                    '📊 <strong>' + data.count + '</strong> registro(s) &nbsp;·&nbsp; 🕒 ' + esc(ts)
                );

                $results.removeClass('d-none');
                $clearBtn.removeClass('d-none');

                $('html, body').animate({ scrollTop: $results.offset().top - 80 }, 400);
            })
            .fail(function(xhr) {
                var msg = 'Erro inesperado ao contactar o servidor.';
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.error) { msg = r.error; }
                } catch (e) { /* ignore */ }
                showError(msg);
            })
            .always(function() {
                setLoading(false);
            });
        }

        function clearReport() {
            $results.addClass('d-none');
            $clearBtn.addClass('d-none');
            $table.html('');
            $title.text('');
            $meta.html('');
            hideError();
        }

        $genBtn.on('click', generateReport);
        $clearBtn.on('click', clearReport);
        $select.on('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); generateReport(); }
        });
    }

    return { init: init };
});
