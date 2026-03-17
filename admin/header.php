<head>
    <meta charset="utf-8" />
    <title>Ethio Social Worker - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="A fully responsive admin theme which can be used to build CRM, CMS,ERP etc." name="description" />
    <meta content="Techzaa" name="author" />

    <!-- App favicon -->
    <!-- <link rel="shortcut icon" href="assets/images/favicon.ico"> -->

    <!-- Datatables css -->
    <link href="assets/vendor/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/vendor/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet"
        type="text/css" />
    <link href="assets/vendor/datatables.net-fixedcolumns-bs5/css/fixedColumns.bootstrap5.min.css" rel="stylesheet"
        type="text/css" />
    <link href="assets/vendor/datatables.net-fixedheader-bs5/css/fixedHeader.bootstrap5.min.css" rel="stylesheet"
        type="text/css" />
    <link href="assets/vendor/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css" rel="stylesheet"
        type="text/css" />
    <link href="assets/vendor/datatables.net-select-bs5/css/select.bootstrap5.min.css" rel="stylesheet"
        type="text/css" />

    <!-- Theme Config Js -->
    <script src="assets/js/config.js"></script>

    <!-- App css -->
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style" />

    <!-- Icons css -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    
    <!-- Design System CSS (UI/UX Enhancements) -->
    <link href="assets/css/design-system.css" rel="stylesheet" type="text/css" />
    
    <!-- Icon System Consistency CSS -->
    <link href="assets/css/icons-consistency.css" rel="stylesheet" type="text/css" />

    <!-- Temporary override: disable any global overlay/blur/preloader issues -->
    <style>
        /* Kill all known overlays that can dim/blur the UI EXCEPT modal backdrop */
        #preloader,
        #status,
        #ds-loading-overlay,
        .ds-loading-overlay,
        .ds-loading-overlay-dark,
        .ui-widget-overlay {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        /* Remove any accidental global blur/dimming */
        html, body, body * {
            filter: none !important;
            backdrop-filter: none !important;
        }
    </style>

    <!-- Safety JS: remove any unexpected full-screen overlays with huge z-index -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(function () {
                var elements = document.querySelectorAll('body *');
                elements.forEach(function (el) {
                    try {
                        var style = window.getComputedStyle(el);
                        var z = parseInt(style.zIndex || '0', 10);
                        if ((style.position === 'fixed' || style.position === 'absolute') &&
                            z >= 9999 &&
                            (style.width === '100%' || style.height === '100%')) {
                            el.style.display = 'none';
                            el.style.visibility = 'hidden';
                            el.style.pointerEvents = 'none';
                            el.style.opacity = 0;
                        }
                    } catch (e) {
                        // ignore
                    }
                });
            }, 500);
        });
    </script>
</head>