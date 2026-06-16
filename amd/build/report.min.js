
define(['jquery'], function($) {
    'use strict';

    // ── Helpers ───────────────────────────────────────────────────────────────

    function esc(val) {
        if (val === null || val === undefined) { return '—'; }
        return String(val)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function fmtNumber(val) {
        var n = parseFloat(val);
        return isNaN(n) ? esc(val) : n.toLocaleString('pt-BR');
    }

    function buildTable(columns, numericCols, items) {
        var numSet = {};
        (numericCols || []).forEach(function(k) { numSet[k] = true; });
        if (items.length > 0) {
            columns.forEach(function(col) {
                if (typeof items[0][col[0]] === 'number') { numSet[col[0]] = true; }
            });
        }

        var thead = '<thead><tr>';
        columns.forEach(function(col) {
            thead += '<th scope="col"' + (numSet[col[0]] ? ' class="text-end"' : '') + '>' + esc(col[1]) + '</th>';
        });
        thead += '</tr></thead><tbody>';

        var tbody = '';
        items.forEach(function(row) {
            tbody += '<tr>';
            columns.forEach(function(col) {
                var k = col[0];
                tbody += numSet[k]
                    ? '<td class="text-end font-monospace">' + fmtNumber(row[k]) + '</td>'
                    : '<td>' + esc(row[k]) + '</td>';
            });
            tbody += '</tr>';
        });

        return thead + tbody + '</tbody>';
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    function init(params) {
        var $error      = $('#gemini-error');
        var $results    = $('#gemini-results');
        var $title      = $('#gemini-results-title');
        var $table      = $('#gemini-table');
        var $meta       = $('#gemini-meta');
        var $clearBtn   = $('#gemini-clear-btn');
        var $cacheBadge = $('#gemini-cache-badge');
        var $descBar    = $('#gemini-desc-bar');

        function showError(msg) {
            $error.removeClass('d-none').html('<strong>⚠ Erro:</strong> ' + esc(msg));
            $results.addClass('d-none');
            $('html, body').animate({ scrollTop: $error.offset().top - 80 }, 300);
        }

        function hideError() { $error.addClass('d-none').html(''); }

        // Mark the clicked button as active, reset others.
        function setActiveBtn($btn, on) {
            if (on) {
                $('.gemini-preset-btn').removeClass('active').prop('disabled', true);
                $btn.addClass('active');
                // Show description for this preset.
                var preset = $btn.data('preset');
                $descBar.removeClass('d-none');
                $('.rg-desc-text').addClass('d-none');
                $('#gemini-desc-' + preset).removeClass('d-none');
                // Spinner inside button.
                $btn.find('.spinner-border').removeClass('d-none');
                $btn.find('.rg-nav-label').text(params.strings.generating);
            } else {
                $btn.find('.spinner-border').addClass('d-none');
                $btn.find('.rg-nav-label').text(
                    $btn.data('origLabel') || params.strings.generate
                );
                $('.gemini-preset-btn').prop('disabled', false);
            }
        }

        function clearReport() {
            $results.addClass('d-none');
            $table.html('');
            $title.text('');
            $meta.html('');
            $cacheBadge.addClass('d-none');
            $descBar.addClass('d-none');
            $('.rg-desc-text').addClass('d-none');
            $('.gemini-preset-btn').removeClass('active');
            hideError();
        }

        // Store original labels before any modification.
        $('.gemini-preset-btn').each(function() {
            $(this).data('origLabel', $(this).find('.rg-nav-label').text());
        });

        // ── Bind preset buttons ───────────────────────────────────────────────
        $(document).on('click', '.gemini-preset-btn', function() {
            var $btn   = $(this);
            var preset = $btn.data('preset');
            if (!preset) { return; }

            hideError();
            clearReport();
            setActiveBtn($btn, true);

            $.ajax({
                url:         params.ajax_url,
                method:      'POST',
                contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                data:        { sesskey: params.sesskey, preset: preset },
                dataType:    'json',
                timeout:     120000,
            })
            .done(function(data) {
                if (data.error) {
                    showError(data.error);
                    if (data.debug) { console.warn('[Gemini Report] debug:', data.debug); }
                    return;
                }
                if (!data.items || !data.items.length) {
                    showError(params.strings.errornoresults);
                    return;
                }

                // ── Render ────────────────────────────────────────────────────
                $title.text(data.title);
                $table.html(buildTable(data.columns, data.numeric_cols, data.items));

                // Cache badge.
                if (data.from_cache) {
                    $cacheBadge.removeClass('d-none');
                } else {
                    $cacheBadge.addClass('d-none');
                }

                var now = new Date();
                var ts  = now.toLocaleDateString('pt-BR') + ' às ' + now.toLocaleTimeString('pt-BR');
                $meta.html('📊 <strong>' + data.count + '</strong> registro(s) &nbsp;·&nbsp; 🕒 ' + esc(ts)
                    + (data.from_cache ? ' &nbsp;·&nbsp; ⚡ do cache' : ' &nbsp;·&nbsp; 🌐 da IA'));

                $results.removeClass('d-none');
                $('html, body').animate({ scrollTop: $results.offset().top - 80 }, 400);
            })
            .fail(function(xhr) {
                var msg = 'Erro inesperado ao contactar o servidor.';
                try { var r = JSON.parse(xhr.responseText); if (r && r.error) { msg = r.error; } } catch(e) {}
                showError(msg);
            })
            .always(function() { setActiveBtn($btn, false); });
        });

        $clearBtn.on('click', clearReport);
    }

    return { init: init };
});
