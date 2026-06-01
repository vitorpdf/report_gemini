
define(['jquery'], function($) {

    'use strict';

    // ── Helpers ───────────────────────────────────────────────────────────────

    function esc(val) {
        if (val === null || val === undefined) { return '—'; }
        return String(val)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function fmtNumber(val) {
        var n = parseFloat(val);
        if (isNaN(n)) { return esc(val); }
        return n.toLocaleString('pt-BR');
    }

    function buildTable(columns, numericCols, items) {
        var numSet = {};
        (numericCols || []).forEach(function(k) { numSet[k] = true; });

        // Auto-detect numeric from first row.
        if (items.length > 0) {
            columns.forEach(function(col) {
                if (typeof items[0][col[0]] === 'number') { numSet[col[0]] = true; }
            });
        }

        var thead = '<thead class="table-dark"><tr>';
        columns.forEach(function(col) {
            var cls = numSet[col[0]] ? ' class="text-end"' : '';
            thead += '<th scope="col"' + cls + '>' + esc(col[1]) + '</th>';
        });
        thead += '</tr></thead>';

        var tbody = '<tbody>';
        items.forEach(function(row) {
            tbody += '<tr>';
            columns.forEach(function(col) {
                var k = col[0];
                if (numSet[k]) {
                    tbody += '<td class="text-end font-monospace">' + fmtNumber(row[k]) + '</td>';
                } else {
                    tbody += '<td>' + esc(row[k]) + '</td>';
                }
            });
            tbody += '</tr>';
        });
        tbody += '</tbody>';

        return thead + tbody;
    }

    // ── Init ──────────────────────────────────────────────────────────────────

    function init(params) {
        var $error    = $('#gemini-error');
        var $results  = $('#gemini-results');
        var $title    = $('#gemini-results-title');
        var $table    = $('#gemini-table');
        var $meta     = $('#gemini-meta');
        var $clearBtn = $('#gemini-clear-btn');

        // Track which button is currently loading.
        var $activeBtn = null;

        function showError(msg) {
            $error.removeClass('d-none').html('<strong>⚠ Erro:</strong> ' + esc(msg));
            $results.addClass('d-none');
            // Scroll to error.
            $('html, body').animate({ scrollTop: $error.offset().top - 80 }, 300);
        }

        function hideError() {
            $error.addClass('d-none').html('');
        }

        function setLoading($btn, on) {
            // All preset buttons.
            $('.gemini-preset-btn').prop('disabled', on);

            var $spinner = $btn.find('.spinner-border');
            var $label   = $btn.find('.btn-label');
            if (on) {
                $spinner.removeClass('d-none');
                $label.text(params.strings.generating);
            } else {
                $spinner.addClass('d-none');
                $label.text(params.strings.generate);
            }
        }

        function clearReport() {
            $results.addClass('d-none');
            $table.html('');
            $title.text('');
            $meta.html('');
            hideError();
        }

        // ── Bind each preset button ───────────────────────────────────────────
        $(document).on('click', '.gemini-preset-btn', function() {
            var $btn   = $(this);
            var preset = $btn.data('preset');

            if (!preset) { return; }

            hideError();
            clearReport();
            setLoading($btn, true);
            $activeBtn = $btn;

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
                    // Show debug info in console to help diagnose.
                    if (data.debug) {
                        console.warn('[Gemini Report] Raw AI response (first 800 chars):', data.debug);
                    }
                    return;
                }

                if (!data.items || data.items.length === 0) {
                    showError(params.strings.errornoresults);
                    return;
                }

                // Render.
                $title.text(data.title);
                $table.html(buildTable(data.columns, data.numeric_cols, data.items));

                var now = new Date();
                var ts  = now.toLocaleDateString('pt-BR') + ' às ' + now.toLocaleTimeString('pt-BR');
                $meta.html(
                    '📊 <strong>' + data.count + '</strong> registro(s) &nbsp;·&nbsp; 🕒 Gerado em ' + esc(ts)
                );

                $results.removeClass('d-none');

                // Smooth scroll to results.
                $('html, body').animate({ scrollTop: $results.offset().top - 80 }, 400);
            })
            .fail(function(xhr) {
                var msg = 'Erro inesperado ao contactar o servidor.';
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r && r.error) { msg = r.error; }
                } catch (e) { /* ignore */ }
                showError(msg);
            })
            .always(function() {
                setLoading($btn, false);
                $activeBtn = null;
            });
        });

        $clearBtn.on('click', clearReport);
    }

    return { init: init };
});
