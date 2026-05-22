/* eslint-disable */
/**
 * Product alternative-UOM editor.
 * Reads window.__ALL_UOMS__ (array of {uom_id, uom_name}).
 * Optionally reads window.__EXISTING_ALT_UOMS__ for prefill on edit page.
 * Writes the current rows as JSON into #alt_uoms_json on every change and on form submit.
 */
(function ($) {
    'use strict';

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function getAllUomsLookup() {
        var lookup = {};
        (window.__ALL_UOMS__ || []).forEach(function (row) {
            lookup[String(row.uom_name).toLowerCase()] = parseInt(row.uom_id, 10) || 0;
        });
        return lookup;
    }

    /**
     * Returns the list of eligible UOMs for the Alt UOM dropdown,
     * sourced from the "Additional UOM" multi-select on the product form.
     * Each entry: {uom_id, uom_name}. Names with no matching item_uom row
     * still appear with uom_id = 0 (they'll be auto-created on product save).
     */
    function getEligibleUoms() {
        var lookup = getAllUomsLookup();
        var selectedNames = $('#additional_uoms').val() || [];
        var out = [];
        var seen = {};
        selectedNames.forEach(function (name) {
            var key = String(name).toLowerCase();
            if (!key || seen[key]) { return; }
            seen[key] = true;
            out.push({ uom_id: lookup[key] || 0, uom_name: name });
        });
        return out;
    }

    function buildUomOptions(selectedId, selectedName, baseUomName) {
        var html = '<option value="">-- Select UOM --</option>';
        var eligible = getEligibleUoms();
        var baseLower = String(baseUomName || '').toLowerCase();
        var selIdInt = parseInt(selectedId, 10) || 0;
        var selNameLower = String(selectedName || '').toLowerCase();
        eligible.forEach(function (row) {
            var nameLower = String(row.uom_name).toLowerCase();
            if (nameLower === baseLower) { return; }
            var sel = '';
            if (selIdInt && parseInt(row.uom_id, 10) === selIdInt) { sel = ' selected'; }
            else if (selNameLower && nameLower === selNameLower) { sel = ' selected'; }
            html += '<option value="' + (row.uom_id || 0) + '" data-name="' + escapeHtml(row.uom_name) + '"' + sel + '>' + escapeHtml(row.uom_name) + '</option>';
        });
        return html;
    }

    function getBaseUomName() {
        return ($('#unit_of_measure').val() || '').toString().trim();
    }

    function refreshBaseUomLabel() {
        var name = getBaseUomName();
        $('#baseUomLabel').text(name || '(select Unit of Measure above)');
    }

    function rebuildOptionsForAllRows() {
        var baseName = getBaseUomName();
        $('#altUomTable tbody tr').each(function () {
            var $sel = $(this).find('select.alt-uom-select');
            var currentId = $sel.val();
            var currentName = $sel.find('option:selected').data('name') || '';
            $sel.html(buildUomOptions(currentId, currentName, baseName));
        });
    }

    function addRow(prefill) {
        prefill = prefill || {};
        var baseName = getBaseUomName();
        var $tbody = $('#altUomTable tbody');
        var $tr = $(
            '<tr>' +
                '<td><select class="form-control input-sm alt-uom-select">' + buildUomOptions(prefill.uom_id, prefill.uom_name, baseName) + '</select></td>' +
                '<td><input type="number" class="form-control input-sm alt-qty-per" min="0" step="0.0001" value="' + escapeHtml(prefill.qty_per_uom != null ? prefill.qty_per_uom : '') + '"></td>' +
                '<td style="text-align:center;"><input type="radio" name="default_purchase_uom" class="alt-default-purchase"' + (prefill.is_default_purchase ? ' checked' : '') + '></td>' +
                '<td style="text-align:center;"><input type="radio" name="default_sales_uom" class="alt-default-sales"' + (prefill.is_default_sales ? ' checked' : '') + '></td>' +
                '<td style="text-align:center;"><button type="button" class="btn btn-xs btn-danger alt-remove"><i class="fa fa-trash"></i></button></td>' +
            '</tr>'
        );
        $tbody.append($tr);
        syncJson();
    }

    function syncJson() {
        var rows = [];
        $('#altUomTable tbody tr').each(function () {
            var $tr = $(this);
            var $opt = $tr.find('.alt-uom-select option:selected');
            var uomId = parseInt($opt.val() || 0, 10);
            var uomName = $opt.data('name') || $opt.text() || '';
            var qty = parseFloat($tr.find('.alt-qty-per').val() || 0);
            if (!uomName || !qty || qty <= 0) { return; }
            rows.push({
                uom_id: uomId,
                uom_name: uomName,
                qty_per_uom: qty,
                is_default_purchase: $tr.find('.alt-default-purchase').is(':checked') ? 1 : 0,
                is_default_sales: $tr.find('.alt-default-sales').is(':checked') ? 1 : 0
            });
        });
        $('#alt_uoms_json').val(JSON.stringify(rows));
    }

    $(function () {
        if (!$('#altUomTable').length) { return; }

        // Prefill from existing alt UOMs (edit page)
        var existing = window.__EXISTING_ALT_UOMS__ || [];
        if (existing && existing.length) {
            existing.forEach(function (r) {
                addRow({
                    uom_id: r.uom_id,
                    uom_name: r.uom_name,
                    qty_per_uom: r.qty_per_uom,
                    is_default_purchase: parseInt(r.is_default_purchase, 10) === 1,
                    is_default_sales: parseInt(r.is_default_sales, 10) === 1
                });
            });
        }
        refreshBaseUomLabel();

        $('#btnAddAltUom').on('click', function () { addRow(); });
        $(document).on('change input', '#altUomTable .alt-uom-select, #altUomTable .alt-qty-per, #altUomTable .alt-default-purchase, #altUomTable .alt-default-sales', syncJson);
        $(document).on('click', '#altUomTable .alt-remove', function () { $(this).closest('tr').remove(); syncJson(); });
        $('#unit_of_measure').on('change', function () { refreshBaseUomLabel(); rebuildOptionsForAllRows(); syncJson(); });
        $('#additional_uoms').on('change', function () { rebuildOptionsForAllRows(); syncJson(); });

        // Make sure the JSON is up to date on submit
        $('form').on('submit', function () { syncJson(); });
    });
}(jQuery));
