/**
 * RequireJS Configuration for ThirdPartyOrder Module
 */
var config = {
    map: {
        '*': {
            'clipboard': 'https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min'
        }
    },
    shim: {
        'clipboard': {
            exports: 'ClipboardJS'
        }
    }
};
