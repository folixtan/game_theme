define([], function () {
    'use strict';

    /**
     * Parse batch-import textarea content into structured per-row data.
     *
     * Input format — one line = one option (one table row):
     *   Comma-separated pairs:  value|storeId, value|storeId, ...
     *   No delimiter → value goes to store 0 (Admin/Global)
     *
     *   Examples:
     *     Red                              → [{0: "Red"}]
     *     Red|0,红色|1,Rouge|3              → [{0: "Red", 1: "红色", 3: "Rouge"}]
     *     Blue|2                           → [{2: "Blue"}]
     *
     * @param  {string} text  Raw textarea value
     * @return {Array<Object<number,string>>}  [{storeId: label, ...}, ...]
     */
    function parse(text) {
        var lines = text.split('\n').filter(function (line) {
            return line.trim().length > 0;
        });

        return lines.map(function (rawLine) {
            var pairs = rawLine.trim().split(','),
                rowData = {};

            pairs.forEach(function (pair) {
                var pipePos = pair.indexOf('|'),
                    label,
                    storeId = 0;

                if (pipePos >= 0) {
                    label = pair.substring(0, pipePos).trim();
                    storeId = parseInt(pair.substring(pipePos + 1).trim(), 10);
                    if (isNaN(storeId)) { storeId = 0; }
                } else {
                    label = pair.trim();
                }

                if (label) {
                    rowData[storeId] = label;
                }
            });

            return rowData;
        });
    }

    return { parse: parse };
});
